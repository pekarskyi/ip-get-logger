<?php
/**
 * Основний клас для обробки GET-запитів
 */
class IP_Get_Logger {

    /**
     * Налаштування
     *
     * @var array
     */
    private $options;

    /**
     * Список GET-запитів
     *
     * @var array
     */
    private $get_requests;

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
     * Перевірка запиту на відповідність шаблонам
     *
     * @param WP_REST_Request|null $request Запит для перевірки (лише для REST API)
     * @return boolean True, якщо запит відповідає шаблону
     */
    public function check_request($request = null) {
        global $ip_get_logger_processed_requests;
        
        // Отримуємо дані запиту
        $request_method = $_SERVER['REQUEST_METHOD'];
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        // Отримуємо повний URL запиту
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $request_url = $protocol . '://' . $host . $request_uri;
        
        // Декодуємо URL для порівняння з патернами, що містять HTML-теги
        $decoded_request_url = urldecode($request_url);
        $double_decoded_url = urldecode($decoded_request_url);
        
        // Якщо цей URL вже було оброблено, пропускаємо
        if (isset($ip_get_logger_processed_requests) && in_array($request_url, $ip_get_logger_processed_requests)) {
            return false;
        }
        
        // Ініціалізуємо масив оброблених запитів, якщо він не існує
        if (!isset($ip_get_logger_processed_requests)) {
            $ip_get_logger_processed_requests = array();
        }
        
        // Перевіряємо чи відстежуємо всі запити або лише GET
        $track_get_only = ip_get_logger_get_option('track_get_only', false);
        
        if ($track_get_only && $request_method !== 'GET') {
            return false;
        }
        
        // Перевіряємо URL на відповідність шаблонам виключення
        $exclude_patterns = ip_get_logger_get_option('exclude_patterns', array());
        
        foreach ($exclude_patterns as $pattern) {
            if (strpos($request_url, $pattern) !== false || 
                strpos($decoded_request_url, $pattern) !== false || 
                strpos($double_decoded_url, $pattern) !== false) {
                // URL містить шаблон виключення, пропускаємо його
                return false;
            }
        }
        
        // Отримуємо шаблони для перевірки
        $get_requests = ip_get_logger_get_option('get_requests', array());
        
        // Створюємо масив URL для перевірки
        $urls_to_check = array($request_url, $decoded_request_url, $double_decoded_url);
        
        // Якщо це REST API запит, додатково перевіряємо URL без базового шляху REST API
        if ($request instanceof WP_REST_Request) {
            $rest_url = $request->get_route();
            $decoded_rest_url = urldecode($rest_url);
            $double_decoded_rest_url = urldecode($decoded_rest_url);
            
            $urls_to_check[] = $rest_url;
            $urls_to_check[] = $decoded_rest_url;
            $urls_to_check[] = $double_decoded_rest_url;
        }
        
        // Перевіряємо всі шаблони
        $matched_pattern = false;
        
        foreach ($get_requests as $pattern) {
            // Перевіряємо за допомогою методу match_request
            if ($this->match_request($request_url) || 
                $this->match_request($decoded_request_url) || 
                $this->match_request($double_decoded_url)) {
                $matched_pattern = $this->match_request($request_url) ?: 
                                   $this->match_request($decoded_request_url) ?: 
                                   $this->match_request($double_decoded_url);
                break;
            }
        }
        
        // Якщо знайдено відповідність шаблону
        if ($matched_pattern) {
            // Додаємо URL до оброблених
            $ip_get_logger_processed_requests[] = $request_url;
            
            // Записуємо в лог
            $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
            
            // Перевіряємо чи існує директорія для логів
            if (!file_exists(IP_GET_LOGGER_LOGS_DIR)) {
                wp_mkdir_p(IP_GET_LOGGER_LOGS_DIR);
            }
            
            // Отримуємо User-Agent
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Not provided';
            
            // Визначаємо тип пристрою
            $device_type = $this->get_device_type($user_agent);
            
            // Визначаємо країну за IP
            $country_code = $this->get_country_by_ip($_SERVER['REMOTE_ADDR']);
            
            $log_data = array(
                'method' => $request_method,
                'url' => $request_url,
                'matched_pattern' => $matched_pattern,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'country' => $country_code,
                'user_agent' => $user_agent,
                'device_type' => $device_type,
                'timestamp' => current_time('mysql'),
                'hook' => current_filter()
            );
            
            // Записуємо в лог-файл
            $log_entry = json_encode($log_data) . PHP_EOL;
            file_put_contents($log_file, $log_entry, FILE_APPEND);
            
            // Відправляємо сповіщення, якщо потрібно
            $this->maybe_send_notification($request_url);
            
            return true;
        }
        
        return false;
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
        
        // Декодуємо URL для порівняння з патернами, що містять HTML-теги
        $decoded_url = urldecode($url);
        
        // Додаткове декодування для випадків подвійного кодування
        $double_decoded_url = urldecode($decoded_url);
        
        foreach ($get_requests as $pattern) {
            // Перевіряємо пряме співпадіння
            if (strpos($url, $pattern) !== false || 
                strpos($decoded_url, $pattern) !== false || 
                strpos($double_decoded_url, $pattern) !== false) {
                return $pattern;
            }
            
            // Спеціальна обробка для патернів, які містять HTML-теги
            if (strpos($pattern, '<') !== false || strpos($pattern, '>') !== false) {
                // Перевіряємо пряме співпадіння без preg_quote, щоб збереглися HTML-теги
                $pattern_regex = str_replace('/', '\/', $pattern);
                
                if (preg_match('/' . $pattern_regex . '/', $url) || 
                    preg_match('/' . $pattern_regex . '/', $decoded_url) ||
                    preg_match('/' . $pattern_regex . '/', $double_decoded_url)) {
                    return $pattern;
                }
                
                // Додаткова перевірка для URL-кодованих тегів
                $encoded_pattern = str_replace(['<', '>'], ['%3C', '%3E'], $pattern);
                $partial_encoded_pattern_1 = str_replace('<', '%3C', $pattern);
                $partial_encoded_pattern_2 = str_replace('>', '%3E', $pattern);
                
                if (strpos($url, $encoded_pattern) !== false || 
                    strpos($url, $partial_encoded_pattern_1) !== false || 
                    strpos($url, $partial_encoded_pattern_2) !== false) {
                    return $pattern;
                }
                
                // Додаткова перевірка для подвійного URL-кодування
                $double_encoded_pattern = str_replace(
                    ['<', '>'], 
                    ['%253C', '%253E'], 
                    $pattern
                );
                
                if (strpos($url, $double_encoded_pattern) !== false) {
                    return $pattern;
                }
            }
            
            // Перевіряємо шаблони з зірочками
            if (strpos($pattern, '*') !== false) {
                // Екрануємо все, крім зірочок, для використання в регулярному виразі
                $pattern_safe = preg_quote($pattern, '/');
                // Замінюємо зірочки на .* (будь-які символи)
                $pattern_regex = str_replace('\*', '.*', $pattern_safe);
                
                if (preg_match('/' . $pattern_regex . '/', $url) || 
                   preg_match('/' . $pattern_regex . '/', $decoded_url) ||
                   preg_match('/' . $pattern_regex . '/', $double_decoded_url)) {
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
     * Логування запиту
     *
     * @param string $url URL запиту
     * @param string $matched_pattern Шаблон, який співпав
     * @param string $hook Хук, через який було викликано функцію
     */
    private function log_request($url, $matched_pattern, $hook = '') {
        $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
        
        $log_data = array(
            'method' => 'GET',
            'url' => $url,
            'matched_pattern' => $matched_pattern,
            'ip' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Not provided',
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
     * Відправляє сповіщення про збіг шаблону, якщо це налаштовано
     * 
     * @param string $request_url URL, який відповідає шаблону
     */
    private function maybe_send_notification($request_url) {
        // Перевіряємо, чи потрібно відправляти сповіщення
        $settings = ip_get_logger_get_option('settings', array());
        $send_notifications = isset($settings['send_notifications']) ? $settings['send_notifications'] : 1;
        
        if ($send_notifications && !empty($settings['email_recipient'])) {
            // Підготовка даних для відправки
            $to = $settings['email_recipient'];
            $subject = isset($settings['email_subject']) ? $settings['email_subject'] : __('Suspicious request detected on your site', 'ip-get-logger');
            $message_template = isset($settings['email_message']) ? $settings['email_message'] : __('A GET request matching your database has been detected: {request}', 'ip-get-logger');
            
            // Отримуємо додаткову інформацію
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Not provided';
            $device_type = $this->get_device_type($user_agent);
            $country_code = $this->get_country_by_ip($_SERVER['REMOTE_ADDR']);
            $date = current_time('Y-m-d');
            $time = current_time('H:i:s');
            
            // Замінюємо змінні у повідомленні
            $message = str_replace(
                array('{request}', '{ip}', '{date}', '{time}', '{user_agent}', '{country}', '{device_type}'),
                array(
                    $request_url,
                    $_SERVER['REMOTE_ADDR'],
                    $date,
                    $time,
                    $user_agent,
                    $country_code,
                    $device_type
                ),
                $message_template
            );
            
            // Додаємо детальну інформацію про запит у вигляді HTML-таблиці
            $message .= '<br><br>';
            $message .= '<h3>' . __('Request details:', 'ip-get-logger') . '</h3>';
            $message .= '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
            $message .= '<tr><th style="text-align: left; background-color: #f2f2f2;">' . __('Parameter', 'ip-get-logger') . '</th><th style="text-align: left; background-color: #f2f2f2;">' . __('Value', 'ip-get-logger') . '</th></tr>';
            $message .= '<tr><td>' . __('URL', 'ip-get-logger') . '</td><td>' . $request_url . '</td></tr>';
            $message .= '<tr><td>' . __('IP Address', 'ip-get-logger') . '</td><td>' . $_SERVER['REMOTE_ADDR'] . '</td></tr>';
            $message .= '<tr><td>' . __('Country', 'ip-get-logger') . '</td><td>' . $country_code . '</td></tr>';
            $message .= '<tr><td>' . __('Device Type', 'ip-get-logger') . '</td><td>' . $device_type . '</td></tr>';
            $message .= '<tr><td>' . __('Date', 'ip-get-logger') . '</td><td>' . $date . '</td></tr>';
            $message .= '<tr><td>' . __('Time', 'ip-get-logger') . '</td><td>' . $time . '</td></tr>';
            $message .= '<tr><td>' . __('User Agent', 'ip-get-logger') . '</td><td>' . $user_agent . '</td></tr>';
            
            // Додаємо інформацію про метод та HTTP_HOST
            $http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'Not provided';
            $request_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'Not provided';
            $message .= '<tr><td>' . __('Request Method', 'ip-get-logger') . '</td><td>' . $request_method . '</td></tr>';
            $message .= '<tr><td>' . __('HTTP Host', 'ip-get-logger') . '</td><td>' . $http_host . '</td></tr>';
            $message .= '</table>';
            
            // Встановлюємо заголовки
            $headers = array(
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
            );
            
            // Відправляємо повідомлення
            wp_mail($to, $subject, $message, $headers);
        }
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
        $query = isset($parsed_url['query']) ? $parsed_url['query'] : '';
        
        // Повний URL та його декодовані версії
        $full_path = $path . ($query ? '?' . $query : '');
        $decoded_full_path = urldecode($full_path);
        $double_decoded_full_path = urldecode($decoded_full_path);
        
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
        
        // Додаємо запит, якщо він є
        if (!empty($query)) {
            $query_part = '?' . $query;
            $decoded_query = urldecode($query_part);
            $double_decoded_query = urldecode($decoded_query);
            
            $additional_paths[] = $query_part;
            $additional_paths[] = $decoded_query;
            $additional_paths[] = $double_decoded_query;
            
            // Додаємо варіанти з HTML-тегами в запиті
            if (strpos($query, '<') !== false || strpos($query, '>') !== false || 
                strpos($decoded_query, '<') !== false || strpos($decoded_query, '>') !== false ||
                strpos($double_decoded_query, '<') !== false || strpos($double_decoded_query, '>') !== false) {
                
                // Додаємо URL з повним запитом
                $additional_paths[] = $path . $query_part;
                $additional_paths[] = $path . $decoded_query;
                $additional_paths[] = $path . $double_decoded_query;
                
                // Додаємо тільки запит для перевірки
                $additional_paths[] = $query;
                $additional_paths[] = urldecode($query);
                $additional_paths[] = urldecode(urldecode($query));
                
                // Додаємо варіанти з закодованими HTML-тегами
                if (strpos($query, '<') !== false || strpos($query, '>') !== false) {
                    $encoded_query = str_replace(['<', '>'], ['%3C', '%3E'], $query);
                    $additional_paths[] = '?' . $encoded_query;
                    $additional_paths[] = $encoded_query;
                }
                
                if (strpos($decoded_query, '<') !== false || strpos($decoded_query, '>') !== false) {
                    $encoded_decoded_query = str_replace(['<', '>'], ['%3C', '%3E'], urldecode($query));
                    $additional_paths[] = '?' . $encoded_decoded_query;
                    $additional_paths[] = $encoded_decoded_query;
                }
            }
        }
        
        // Створюємо список URL для перевірки
        $urls_to_check = array_merge(
            array(
                $test_url,                // Повний URL
                $full_path,               // Повний шлях з запитом
                $decoded_full_path,       // Декодований повний шлях
                $double_decoded_full_path, // Двічі декодований повний шлях
                $path,                    // Шлях
                $path_without_domain,     // Шлях без домену
                $filename                 // Тільки ім'я файлу
            ),
            $additional_paths
        );
        
        // Додаємо специфічний паттерн для запиту з iframe
        if (strpos($test_url, 'iframe') !== false || 
            strpos($decoded_full_path, 'iframe') !== false || 
            strpos($double_decoded_full_path, 'iframe') !== false) {
            $urls_to_check[] = '/?q=<iframe>';
            $urls_to_check[] = '?q=<iframe>';
            $urls_to_check[] = 'q=<iframe>';
            $urls_to_check[] = '<iframe>';
        }
        
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
     * @param WP_REST_Response $response Відповідь REST API
     * @param WP_REST_Server $server Сервер REST API
     * @param WP_REST_Request $request Запит REST API
     * @return WP_REST_Response Відповідь REST API без змін
     */
    public function check_rest_request($response, $server, $request) {
        global $ip_get_logger_processed_requests;
        
        // Отримуємо URL запиту
        $request_url = $this->get_current_url();
        $decoded_request_url = urldecode($request_url);
        $double_decoded_url = urldecode($decoded_request_url);
        
        // Якщо цей URL вже було оброблено, пропускаємо
        if (isset($ip_get_logger_processed_requests) && in_array($request_url, $ip_get_logger_processed_requests)) {
            return $response;
        }
        
        // Ініціалізуємо масив оброблених запитів, якщо він не існує
        if (!isset($ip_get_logger_processed_requests)) {
            $ip_get_logger_processed_requests = array();
        }
        
        // Перевіряємо чи відстежуємо всі запити або лише GET
        $track_get_only = ip_get_logger_get_option('track_get_only', false);
        
        if ($track_get_only && $request->get_method() !== 'GET') {
            return $response;
        }
        
        // Отримуємо шлях запиту
        $route = $request->get_route();
        $decoded_route = urldecode($route);
        $double_decoded_route = urldecode($decoded_route);
        
        // Створюємо масив URL для перевірки
        $urls_to_check = array(
            $request_url,
            $decoded_request_url,
            $double_decoded_url,
            $route,
            $decoded_route,
            $double_decoded_route
        );
        
        // Перевіряємо за допомогою методу check_request
        $result = $this->check_request($request);
        
        // Якщо не знайдено співпадіння, перевіряємо окремо шлях запиту
        if (!$result) {
            // Перевіряємо всі URL у масиві
            foreach ($urls_to_check as $url) {
                $matched_pattern = $this->match_request($url);
                
                if ($matched_pattern) {
                    // Додаємо URL до оброблених
                    $ip_get_logger_processed_requests[] = $request_url;
                    
                    // Записуємо в лог
                    $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
                    
                    // Перевіряємо чи існує директорія для логів
                    if (!file_exists(IP_GET_LOGGER_LOGS_DIR)) {
                        wp_mkdir_p(IP_GET_LOGGER_LOGS_DIR);
                    }
                    
                    // Отримуємо User-Agent
                    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Not provided';
                    
                    // Визначаємо тип пристрою
                    $device_type = $this->get_device_type($user_agent);
                    
                    // Визначаємо країну за IP
                    $country_code = $this->get_country_by_ip($_SERVER['REMOTE_ADDR']);
                    
                    $log_data = array(
                        'method' => $request->get_method(),
                        'url' => $request_url,
                        'route' => $route,
                        'matched_pattern' => $matched_pattern,
                        'ip' => $_SERVER['REMOTE_ADDR'],
                        'country' => $country_code,
                        'user_agent' => $user_agent,
                        'device_type' => $device_type,
                        'timestamp' => current_time('mysql'),
                        'hook' => 'rest_api_init'
                    );
                    
                    // Записуємо в лог-файл
                    $log_entry = json_encode($log_data) . PHP_EOL;
                    file_put_contents($log_file, $log_entry, FILE_APPEND);
                    
                    // Відправляємо сповіщення, якщо потрібно
                    $this->maybe_send_notification($request_url);
                    
                    break;
                }
            }
        }
        
        return $response;
    }

    /**
     * Визначає тип пристрою на основі User-Agent
     * 
     * @param string $user_agent Рядок User-Agent
     * @return string Тип пристрою (Desktop, Mobile, Tablet, Bot, Unknown)
     */
    public function get_device_type($user_agent) {
        $user_agent = strtolower($user_agent);
        
        // Боти
        if (preg_match('/(googlebot|bingbot|yandexbot|slurp|duckduckbot|baiduspider|facebookexternalhit|twitterbot|rogerbot|linkedinbot|embedly|quora link preview|showyoubot|outbrain|pinterest|slackbot|vkShare|W3C_Validator|whatsapp)/i', $user_agent)) {
            return 'Bot';
        }
        
        // Мобільні пристрої
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $user_agent)) {
            return 'Mobile';
        }
        
        // Планшети
        if (preg_match('/(android|bb\d+|meego).+tablet|ipad|playbook|silk/i', $user_agent) || 
            (preg_match('/tablet/i', $user_agent) && !preg_match('/RX-34/i', $user_agent)) || 
            preg_match('/FOLIO/i', $user_agent)) {
            return 'Tablet';
        }
        
        // Десктопи
        if (preg_match('/(mozilla|chrome|safari|firefox|msie|trident)/i', $user_agent) && !preg_match('/(android|ipad|playbook|silk|mobile|tablet)/i', $user_agent)) {
            return 'Desktop';
        }
        
        // Якщо жоден з патернів не співпав
        return 'Unknown';
    }

    /**
     * Визначає країну за IP-адресою
     * 
     * @param string $ip IP-адреса
     * @return string Код країни або 'Unknown'
     */
    public function get_country_by_ip($ip) {
        // Перевірка на локальні IP-адреси
        if (in_array($ip, array('127.0.0.1', '::1')) || 
            strpos($ip, '192.168.') === 0 || 
            strpos($ip, '10.') === 0 || 
            strpos($ip, '172.16.') === 0) {
            return 'Local';
        }
        
        // Спробуємо отримати інформацію через безкоштовний GeoIP API
        $api_url = 'http://ip-api.com/json/' . $ip . '?fields=status,countryCode,country';
        
        // Створюємо транзієнт для кешування результатів
        $transient_name = 'ip_geo_' . md5($ip);
        $cached_result = get_transient($transient_name);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        $response = wp_remote_get($api_url, array(
            'timeout' => 5,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return 'Unknown';
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['status']) && $data['status'] === 'success' && !empty($data['countryCode'])) {
            // Зберігаємо результат в кеш на 7 днів
            set_transient($transient_name, $data['countryCode'], 7 * DAY_IN_SECONDS);
            return $data['countryCode'];
        }
        
        // Альтернативний API, якщо перший не відповів
        $api_url_alt = 'https://ipapi.co/' . $ip . '/country/';
        
        $response_alt = wp_remote_get($api_url_alt, array(
            'timeout' => 5,
            'sslverify' => false
        ));
        
        if (!is_wp_error($response_alt)) {
            $country_code = trim(wp_remote_retrieve_body($response_alt));
            
            if (!empty($country_code) && strlen($country_code) === 2) {
                // Зберігаємо результат в кеш на 7 днів
                set_transient($transient_name, $country_code, 7 * DAY_IN_SECONDS);
                return $country_code;
            }
        }
        
        return 'Unknown';
    }
}