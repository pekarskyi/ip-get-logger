<?php
/**
 * Основний клас для обробки GET-запитів
 */
class IP_Get_Logger {

    /**
     * Опції плагіна
     *
     * @var array
     */
    private $options;

    /**
     * Запити для відстеження
     *
     * @var array
     */
    private $get_requests;

    /**
     * Кеш статус-кодів URL
     *
     * @var array
     */
    private static $status_code_cache = array();

    /**
     * Конструктор
     */
    public function __construct() {
        $this->options = ip_get_logger_get_option('settings', array());
        $this->get_requests = ip_get_logger_get_option('get_requests', array());
    }

    /**
     * Ініціалізація плагіна
     */
    public function init() {
        // Хук для фронтенду, який виконується після визначення 404 статусу
        add_action('template_redirect', array($this, 'check_request'), 999);
        
        // Хук для адміністративної частини
        add_action('admin_init', array($this, 'check_request'), 999);
        
        // Хук для прямих запитів до файлів (перед WordPress)
        add_action('parse_request', array($this, 'check_request'), 1);
        
        // Хук для REST API запитів
        add_action('rest_api_init', array($this, 'check_request'), 1);
        
        // Додатковий хук для перехоплення REST API запитів
        add_filter('rest_pre_dispatch', array($this, 'check_rest_request'), 10, 3);
    }

    /**
     * Перевірка GET-запитів
     */
    public function check_request() {
        global $ip_get_logger_processed_requests;
        
        // Отримуємо URL запиту
        $request_url = $this->get_current_url();
        
        // Якщо цей URL вже було оброблено, пропускаємо
        if (in_array($request_url, $ip_get_logger_processed_requests)) {
            return;
        }
        
        // Перевіряємо метод запиту
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }
        
        // Визначаємо через який хук зараз виконується
        $current_hook = current_filter();
        
        // Додаємо URL до оброблених
        $ip_get_logger_processed_requests[] = $request_url;
        
        // Перевіряємо URL на співпадіння з шаблонами
        $urls_to_check = $this->get_urls_to_check($request_url);
        
        foreach ($urls_to_check as $url) {
            $match = $this->match_request($url);
            if ($match) {
                // Логуємо запит
                $this->log_request($request_url, $match, $current_hook);
                
                // Надсилаємо сповіщення
                $this->send_notification($request_url);
                
                return;
            }
        }
    }

    /**
     * Перевіряємо, чи URL співпадає з одним із шаблонів
     *
     * @param string $url URL для перевірки
     * @return bool|string Шаблон, який співпав, або false
     */
    private function match_request($url) {
        // Отримуємо шаблони для перевірки
        $get_requests = ip_get_logger_get_option('get_requests', array());
        
        foreach ($get_requests as $pattern) {
            // Перевіряємо пряме співпадіння
            if (strpos($url, $pattern) !== false) {
                return $pattern;
            }
            
            // Перевіряємо шаблони з зірочками
            if (strpos($pattern, '*') !== false) {
                $pattern_regex = str_replace('*', '.*', $pattern);
                if (preg_match('/' . preg_quote($pattern_regex, '/') . '/', $url)) {
                    return $pattern;
                }
            }
        }
        
        return false;
    }

    /**
     * Отримати поточний URL
     *
     * @return string Повний URL
     */
    private function get_current_url() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        return $protocol . '://' . $host . $request_uri;
    }

    /**
     * Отримати всі варіанти URL для перевірки
     *
     * @param string $request_url URL запиту
     * @return array Масив URL для перевірки
     */
    private function get_urls_to_check($request_url) {
        // Отримуємо різні варіанти URL для перевірки
        $parsed_url = parse_url($request_url);
        
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        
        // Очищуємо шлях від / на початку
        $path_without_domain = ltrim($path, '/');
        
        // Отримуємо тільки ім'я файлу, якщо воно є
        $filename = basename($path);
        
        // Масив URL для перевірки
        $urls_to_check = array(
            $request_url,          // Повний URL
            $request_uri,          // URI з параметрами
            $path,                 // Шлях
            $path_without_domain,  // Шлях без домену
            $filename              // Тільки ім'я файлу
        );
        
        return array_unique($urls_to_check);
    }

    /**
     * Отримати поточний HTTP статус-код
     *
     * @return string|int HTTP статус-код або "Невідомо" для статичних файлів
     */
    private function get_http_status_code() {
        // Отримуємо відносний шлях із запиту
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $request_url = $this->get_current_url();
        
        // Перевіряємо чи запит до статичного файлу (включно з PHP)
        $file_extensions = array('.txt', '.log', '.sql', '.php', '.js', '.json', '.xml', '.css', '.svg', '.html', '.htm');
        $is_static_file = false;
        
        foreach ($file_extensions as $extension) {
            if (strpos($request_uri, $extension) !== false) {
                $is_static_file = true;
                break;
            }
        }
        
        // Для статичних файлів перевіряємо чи вони існують
        if ($is_static_file) {
            // Отримуємо відносний шлях із запиту
            $path = parse_url($request_uri, PHP_URL_PATH);
            
            // Формуємо повний шлях до файлу
            $file_path = ABSPATH . ltrim($path, '/');
            
            // Якщо файл існує, повертаємо 200, інакше 404
            if (file_exists($file_path) && is_file($file_path)) {
                return 200;
            } else {
                return 404;
            }
        }
        
        // Для нестатичних файлів визначаємо код через стандартні механізми
        $cache_key = md5($request_url);
        if (isset(self::$status_code_cache[$cache_key])) {
            return self::$status_code_cache[$cache_key];
        }
        
        // 1. Перевіряємо стандартний спосіб отримання коду
        $current_code = http_response_code();
        
        // 2. Перевіряємо функції WordPress
        if (function_exists('is_404') && is_404()) {
            self::$status_code_cache[$cache_key] = 404;
            return 404;
        }
        
        // 3. Для адміністративної частини повертаємо звичайний код
        if (is_admin()) {
            self::$status_code_cache[$cache_key] = $current_code;
            return $current_code;
        }
        
        // 4. Якщо це redirect у WordPress
        if (function_exists('wp_redirect_status') && wp_redirect_status() !== 0) {
            $redirect_code = wp_redirect_status();
            self::$status_code_cache[$cache_key] = $redirect_code;
            return $redirect_code;
        }
        
        // За замовчуванням повертаємо поточний код статусу
        self::$status_code_cache[$cache_key] = $current_code;
        return $current_code;
    }

    /**
     * Конвертує URL в шлях до файлу
     *
     * @param string $url URL для конвертації
     * @return string|bool Шлях до файлу або false у випадку помилки
     */
    private function convert_url_to_path($url) {
        // Отримуємо шлях без параметрів
        $clean_url = parse_url($url, PHP_URL_PATH);
        
        // Базові шляхи для пошуку
        $possible_paths = array(
            ABSPATH . ltrim($clean_url, '/'),
            WP_CONTENT_DIR . '/plugins/' . basename($clean_url),
            WP_CONTENT_DIR . '/themes/' . basename($clean_url),
            get_template_directory() . '/' . basename($clean_url)
        );
        
        // Якщо це шлях до wp-content
        if (strpos($clean_url, '/wp-content/') !== false) {
            $content_path = str_replace('/wp-content/', '', $clean_url);
            $possible_paths[] = WP_CONTENT_DIR . '/' . ltrim($content_path, '/');
        }
        
        // Перевіряємо всі можливі шляхи
        foreach ($possible_paths as $path) {
            if (file_exists($path) && is_file($path)) {
                return $path;
            }
        }
        
        return false;
    }

    /**
     * Логування запиту
     *
     * @param string $url URL запиту
     * @param string $matched_pattern Шаблон, який співпав
     * @param string $hook Хук, через який було викликано функцію
     */
    private function log_request($url, $matched_pattern, $hook = '') {
        $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
        
        // Отримуємо статус-код або "Невідомо" для статичних файлів
        $status_code = $this->get_http_status_code();
        
        $log_data = array(
            'method' => 'GET',
            'url' => $url,
            'matched_pattern' => $matched_pattern,
            'ip' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Not provided',
            'status_code' => $status_code,
            'timestamp' => current_time('mysql'),
            'hook' => $hook
        );
        
        // Форматуємо лог у одну строку
        $log_entry = json_encode($log_data) . PHP_EOL;
        
        // Записуємо в лог-файл
        file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        // Перевірка чи потрібно очистити старі логи
        $this->maybe_cleanup_logs();
    }

    /**
     * Отримати IP користувача
     *
     * @return string
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return $ip;
    }

    /**
     * Відправка сповіщення
     *
     * @param string $url URL запиту
     * @return bool Результат відправки
     */
    private function send_notification($url) {
        // Перевіряємо наявність адреси отримувача
        if (empty($this->options['email_recipient'])) {
            return false;
        }
        
        // Перевіряємо наявність опції для відправки сповіщень
        if (isset($this->options['send_notifications']) && $this->options['send_notifications'] == 0) {
            return false;
        }
        
        $to = $this->options['email_recipient'];
        $subject = isset($this->options['email_subject']) ? $this->options['email_subject'] : __('GET Request Match Found', 'ip-get-logger');
        
        $message = isset($this->options['email_message']) ? $this->options['email_message'] : __('A GET request matching your database has been detected: {request}', 'ip-get-logger');
        $message = str_replace('{request}', $url, $message);
        
        // Додаємо додаткову інформацію до повідомлення
        $message .= '<br><br>';
        $message .= __('Request details:', 'ip-get-logger') . '<br>';
        $message .= __('IP Address:', 'ip-get-logger') . ' ' . $this->get_client_ip() . '<br>';
        $message .= __('User Agent:', 'ip-get-logger') . ' ' . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Not provided') . '<br>';
        $message .= __('Date and Time:', 'ip-get-logger') . ' ' . current_time('mysql') . '<br>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Відправка електронного листа
        $mail_sent = wp_mail($to, $subject, $message, $headers);
        
        return $mail_sent;
    }

    /**
     * Очищення старих логів
     */
    private function maybe_cleanup_logs() {
        // Перевіряємо чи активне автоочищення
        if (empty($this->options['auto_cleanup_days']) || intval($this->options['auto_cleanup_days']) <= 0) {
            return;
        }
        
        $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
        
        // Перевіряємо чи файл логів існує
        if (!file_exists($log_file)) {
            return;
        }
        
        // Отримуємо вміст лог-файлу
        $logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if (empty($logs)) {
            return;
        }
        
        $days = intval($this->options['auto_cleanup_days']);
        $current_time = current_time('timestamp');
        $max_age = $days * 24 * 60 * 60; // конвертація днів у секунди
        
        $new_logs = array();
        
        foreach ($logs as $log_entry) {
            $log_data = json_decode($log_entry, true);
            
            if (isset($log_data['timestamp'])) {
                $log_time = strtotime($log_data['timestamp']);
                
                // Додаємо лог, якщо він не старіший за максимальний вік
                if (($current_time - $log_time) <= $max_age) {
                    $new_logs[] = $log_entry;
                }
            } else {
                // Якщо немає мітки часу, зберігаємо запис
                $new_logs[] = $log_entry;
            }
        }
        
        // Записуємо оновлені логи
        file_put_contents($log_file, implode(PHP_EOL, $new_logs) . (empty($new_logs) ? '' : PHP_EOL));
    }

    /**
     * Тестування обробки URL запиту
     * Цю функцію можна викликати з адмін-панелі для тестування розпізнавання URL
     *
     * @param string $test_url URL для тестування
     * @return array Результати тестування
     */
    public function test_url_matching($test_url) {
        // Очищаємо URL від зайвих символів
        $test_url = trim($test_url);
        
        // Додаємо протокол, якщо необхідно
        if (!preg_match('~^(?:f|ht)tps?://~i', $test_url)) {
            $test_url = 'http://' . $test_url;
        }
        
        // Розбираємо URL
        $parsed_url = parse_url($test_url);
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        
        // Очищаємо шлях від / на початку
        $path_without_domain = ltrim($path, '/');
        
        // Отримуємо тільки ім'я файлу, якщо воно є
        $filename = basename($path);
        
        // Розділяємо шлях на частини
        $path_parts = explode('/', $path_without_domain);
        $additional_paths = array();
        
        // Додаємо частини шляху для перевірки
        $current_path = '';
        foreach ($path_parts as $part) {
            $current_path .= ($current_path ? '/' : '') . $part;
            $additional_paths[] = $current_path;
        }
        
        // Додаємо варіанти з readme.txt окремо, оскільки це часто шукають
        if ($filename === 'readme.txt') {
            $additional_paths[] = 'wp-content/plugins/*/' . $filename;
            $additional_paths[] = '*/' . $filename;
            $additional_paths[] = '*' . $filename;
        }
        
        // Створюємо список URL для перевірки
        $urls_to_check = array_merge(
            array(
                $test_url,          // Повний URL
                $path,              // Шлях
                $path_without_domain, // Шлях без домену
                $filename           // Тільки ім'я файлу
            ),
            $additional_paths
        );
        
        // Видаляємо дублікати
        $urls_to_check = array_unique($urls_to_check);
        
        // Перевіряємо кожен URL
        $results = array(
            'test_url' => $test_url,
            'urls_checked' => $urls_to_check,
            'matches' => array()
        );
        
        foreach ($urls_to_check as $url) {
            $match = $this->match_request($url);
            if ($match) {
                $results['matches'][$url] = $match;
            }
        }
        
        $results['match_found'] = !empty($results['matches']);
        
        return $results;
    }

    /**
     * Перевірка REST API запитів
     *
     * @param mixed $result Результат запиту, за замовчуванням null
     * @param WP_REST_Server $server Об'єкт REST сервера
     * @param WP_REST_Request $request Об'єкт REST запиту
     * @return mixed Початковий результат (для подальшої обробки)
     */
    public function check_rest_request($result, $server, $request) {
        global $ip_get_logger_processed_requests;
        
        // Отримуємо шлях до REST API ендпоінта
        $route = $request->get_route();
        
        // Отримуємо повний URL запиту
        $request_url = $this->get_current_url();
        
        // Якщо цей URL вже було оброблено, пропускаємо
        if (in_array($request_url, $ip_get_logger_processed_requests)) {
            return $result;
        }
        
        // Якщо це не GET запит, пропускаємо
        if ($request->get_method() !== 'GET') {
            return $result;
        }
        
        // Додаткові варіанти для перевірки REST запиту
        $urls_to_check = array(
            $route,
            '*' . $route,
            '*/' . $route,
            $route . '*',
            'wp-json' . $route,
            '/wp-json' . $route,
            'wp-json' . $route . '*',
            '*wp-json' . $route,
            '*' . 'wp-json' . $route,
        );
        
        // Перевіряємо кожен URL
        $matched_request = false;
        foreach ($urls_to_check as $url) {
            $match = $this->match_request($url);
            if ($match) {
                $matched_request = $match;
                break;
            }
        }
        
        if ($matched_request) {
            // Додаємо URL до оброблених запитів
            $ip_get_logger_processed_requests[] = $request_url;
            
            // Визначаємо статус-код використовуючи спільну функцію
            $status_code = $this->get_http_status_code();
            
            // Логуємо запит
            $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
            
            $log_data = array(
                'method' => 'GET',
                'url' => $request_url,
                'matched_pattern' => $matched_request,
                'ip' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Not provided',
                'status_code' => $status_code,
                'timestamp' => current_time('mysql'),
                'hook' => 'rest_pre_dispatch'
            );
            
            // Форматуємо лог у одну строку
            $log_entry = json_encode($log_data) . PHP_EOL;
            
            // Записуємо в лог-файл
            file_put_contents($log_file, $log_entry, FILE_APPEND);
            
            // Надсилаємо сповіщення
            $this->send_notification($request_url);
        }
        
        return $result;
    }
}