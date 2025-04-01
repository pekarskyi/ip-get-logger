<?php
/**
 * Plugin Name: IP GET Logger
 * Description: Plugin for tracking GET requests to the site and logging them
 * Version: 1.2.2
 * Author: InwebPress
 * Author URI: https://inwebpress.com
 * Plugin URI: https://github.com/pekarskyi/ip-get-logger
 * Text Domain: ip-get-logger
 * Domain Path: /languages
 */

// Захист від прямого доступу
if (!defined('ABSPATH')) {
    exit;
}

// Отримання версії плагіна з його заголовка
function ip_get_logger_get_plugin_version() {
    $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), 'plugin');
    return $plugin_data['Version'];
}

// Визначення констант
define('IP_GET_LOGGER_VERSION', ip_get_logger_get_plugin_version());
define('IP_GET_LOGGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IP_GET_LOGGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IP_GET_LOGGER_LOGS_DIR', WP_CONTENT_DIR . '/ip-get-logger-logs/');
define('IP_GET_LOGGER_TABLE', 'ip_get_logger');

// Глобальна змінна для відслідковування вже зареєстрованих запитів
global $ip_get_logger_processed_requests;
$ip_get_logger_processed_requests = array();

// Глобальна змінна для зберігання повідомлень, які потрібно відправити
global $ip_get_logger_notifications;
$ip_get_logger_notifications = array();

// Підключення необхідних файлів
require_once(IP_GET_LOGGER_PLUGIN_DIR . 'includes/class-ip-get-logger.php');
require_once(IP_GET_LOGGER_PLUGIN_DIR . 'admin/class-ip-get-logger-admin.php');

/**
 * Відловлює запити максимально рано
 */
function ip_get_logger_early_request_capture() {
    // Перевіряємо чи потрібно відстежувати запити
    if (!ip_get_logger_get_option('track_requests', false)) {
        return;
    }

    // Перевіряємо чи відстежуємо лише GET-запити
    if (ip_get_logger_get_option('track_get_only', false) && 'GET' !== $_SERVER['REQUEST_METHOD']) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $decoded_request_uri = urldecode($request_uri);
    $double_decoded_uri = urldecode($decoded_request_uri);
    
    // Отримуємо список шаблонів GET-запитів для відслідковування
    $get_requests = ip_get_logger_get_option('get_requests', array());

    foreach ($get_requests as $pattern) {
        // Перевіряємо пряме співпадіння
        if (strpos($request_uri, $pattern) !== false || 
            strpos($decoded_request_uri, $pattern) !== false ||
            strpos($double_decoded_uri, $pattern) !== false) {
            ip_get_logger_log_match($pattern);
            return;
        }
            
        // Спеціальна обробка для патернів, які містять HTML-теги
        if (strpos($pattern, '<') !== false || strpos($pattern, '>') !== false) {
            // Перевіряємо пряме співпадіння без preg_quote, щоб збереглися HTML-теги
            $pattern_regex = str_replace('/', '\/', $pattern);
            
            if (preg_match('/' . $pattern_regex . '/', $request_uri) || 
                preg_match('/' . $pattern_regex . '/', $decoded_request_uri) ||
                preg_match('/' . $pattern_regex . '/', $double_decoded_uri)) {
                ip_get_logger_log_match($pattern);
                return;
            }
            
            // Додаткова перевірка для URL-кодованих тегів
            $encoded_pattern = str_replace(['<', '>'], ['%3C', '%3E'], $pattern);
            $partial_encoded_pattern_1 = str_replace('<', '%3C', $pattern);
            $partial_encoded_pattern_2 = str_replace('>', '%3E', $pattern);
            
            if (strpos($request_uri, $encoded_pattern) !== false || 
                strpos($request_uri, $partial_encoded_pattern_1) !== false || 
                strpos($request_uri, $partial_encoded_pattern_2) !== false) {
                ip_get_logger_log_match($pattern);
                return;
            }
            
            // Додаткова перевірка для подвійного URL-кодування
            $double_encoded_pattern = str_replace(
                ['<', '>'], 
                ['%253C', '%253E'], 
                $pattern
            );
            
            if (strpos($request_uri, $double_encoded_pattern) !== false) {
                ip_get_logger_log_match($pattern);
                return;
            }
        }
        
        // Перевіряємо шаблони з зірочками
        if (strpos($pattern, '*') !== false) {
            // Екрануємо все, крім зірочок, для використання в регулярному виразі
            $pattern_safe = preg_quote($pattern, '/');
            // Замінюємо зірочки на .* (будь-які символи)
            $pattern_regex = str_replace('\*', '.*', $pattern_safe);
            
            if (preg_match('/' . $pattern_regex . '/', $request_uri) || 
               preg_match('/' . $pattern_regex . '/', $decoded_request_uri) ||
               preg_match('/' . $pattern_regex . '/', $double_decoded_uri)) {
                ip_get_logger_log_match($pattern);
                return;
            }
        }
    }
}

/**
 * Логує збіг шаблону та запиту
 * 
 * @param string $pattern Шаблон, який був знайдений
 * @return void
 */
function ip_get_logger_log_match($pattern) {
    global $ip_get_logger_processed_requests, $ip_get_logger_notifications;
    
    // Отримуємо URL запиту
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    
    // Отримуємо повний URL запиту
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $request_url = $protocol . '://' . $host . $request_uri;
    
    // Якщо цей URL вже було оброблено, пропускаємо
    if (isset($ip_get_logger_processed_requests) && in_array($request_url, $ip_get_logger_processed_requests)) {
        return;
    }
    
    // Ініціалізуємо масив оброблених запитів, якщо він не існує
    if (!isset($ip_get_logger_processed_requests)) {
        $ip_get_logger_processed_requests = array();
    }
    
    // Додаємо URL до оброблених
    $ip_get_logger_processed_requests[] = $request_url;
    
    // Записуємо в лог
    $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
    
    // Перевіряємо чи існує директорія для логів
    if (!file_exists(IP_GET_LOGGER_LOGS_DIR)) {
        wp_mkdir_p(IP_GET_LOGGER_LOGS_DIR);
    }
    
    // Визначаємо тип запиту для запису в лог
    $is_static_file = false;
    $file_extensions = array('.txt', '.log', '.sql', '.php', '.js', '.json', '.xml', '.css', '.svg', '.html', '.htm');
    
    foreach ($file_extensions as $extension) {
        if (strpos($request_uri, $extension) !== false) {
            $is_static_file = true;
            break;
        }
    }
    
    // Отримуємо User-Agent
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Not provided';
    
    // Визначаємо тип пристрою за допомогою класу IP_Get_Logger
    $logger = new IP_Get_Logger();
    $device_type = $logger->get_device_type($user_agent);
    
    // Визначаємо країну за IP
    $country_code = $logger->get_country_by_ip($_SERVER['REMOTE_ADDR']);
    
    $log_data = array(
        'method' => $_SERVER['REQUEST_METHOD'],
        'url' => $request_url,
        'matched_pattern' => $pattern,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'country' => $country_code,
        'user_agent' => $user_agent,
        'device_type' => $device_type,
        'timestamp' => current_time('mysql'),
        'hook' => 'early_capture'
    );
    
    // Записуємо в лог-файл
    $log_entry = json_encode($log_data) . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // Перевіряємо, чи потрібно відправляти сповіщення
    $settings = ip_get_logger_get_option('settings', array());
    $send_notifications = isset($settings['send_notifications']) ? $settings['send_notifications'] : 1;
    
    if ($send_notifications && !empty($settings['email_recipient'])) {
        // Ініціалізуємо масив сповіщень, якщо він не існує
        if (!isset($ip_get_logger_notifications)) {
            $ip_get_logger_notifications = array();
        }
        
        // Зберігаємо дані сповіщення для подальшої відправки
        $notification = array(
            'to' => $settings['email_recipient'],
            'subject' => isset($settings['email_subject']) ? $settings['email_subject'] : __('Suspicious request detected on your site', 'ip-get-logger'),
            'message' => isset($settings['email_message']) ? $settings['email_message'] : __('A GET request matching your database has been detected: {request}', 'ip-get-logger'),
            'request_url' => $request_url,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'country' => $country_code,
            'user_agent' => $user_agent,
            'device_type' => $device_type,
            'method' => $_SERVER['REQUEST_METHOD'],
            'timestamp' => current_time('mysql')
        );
        
        // Додаємо сповіщення до списку
        $ip_get_logger_notifications[] = $notification;
    }
}

// Функція для відправки відкладених сповіщень
function ip_get_logger_send_notifications() {
    global $ip_get_logger_notifications;
    
    // Перевіряємо чи є сповіщення для відправки
    if (!empty($ip_get_logger_notifications)) {
        foreach ($ip_get_logger_notifications as $notification) {
            $to = $notification['to'];
            $subject = $notification['subject'];
            $message_template = $notification['message'];
            
            // Отримуємо дані про запит
            $request_url = $notification['request_url'];
            $ip = $notification['ip'];
            $country = $notification['country'];
            $user_agent = $notification['user_agent'];
            $device_type = $notification['device_type'];
            $timestamp = $notification['timestamp'];
            $date = date('Y-m-d', strtotime($timestamp));
            $time = date('H:i:s', strtotime($timestamp));
            
            // Замінюємо змінні у повідомленні
            $message = str_replace(
                array('{request}', '{ip}', '{date}', '{time}', '{user_agent}', '{country}', '{device_type}'),
                array(
                    $request_url,
                    $ip,
                    $date,
                    $time,
                    $user_agent,
                    $country,
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
            $message .= '<tr><td>' . __('IP Address', 'ip-get-logger') . '</td><td>' . $ip . '</td></tr>';
            $message .= '<tr><td>' . __('Country', 'ip-get-logger') . '</td><td>' . $country . '</td></tr>';
            $message .= '<tr><td>' . __('Device Type', 'ip-get-logger') . '</td><td>' . $device_type . '</td></tr>';
            $message .= '<tr><td>' . __('Date', 'ip-get-logger') . '</td><td>' . $date . '</td></tr>';
            $message .= '<tr><td>' . __('Time', 'ip-get-logger') . '</td><td>' . $time . '</td></tr>';
            $message .= '<tr><td>' . __('User Agent', 'ip-get-logger') . '</td><td>' . $user_agent . '</td></tr>';
            
            // Додаємо інформацію про метод та HTTP_HOST, якщо вони є
            $http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $request_method = isset($notification['method']) ? $notification['method'] : 'GET';
            
            if (!empty($http_host)) {
                $message .= '<tr><td>' . __('HTTP Host', 'ip-get-logger') . '</td><td>' . $http_host . '</td></tr>';
            }
            
            $message .= '<tr><td>' . __('Request Method', 'ip-get-logger') . '</td><td>' . $request_method . '</td></tr>';
            $message .= '</table>';
            
            $headers = array('Content-Type: text/html; charset=UTF-8');
            
            // Відправляємо лист
            wp_mail($to, $subject, $message, $headers);
        }
        
        // Очищаємо список сповіщень
        $ip_get_logger_notifications = array();
    }
}
add_action('init', 'ip_get_logger_send_notifications');

// Викликаємо функцію перехоплення перед завантаженням WordPress
ip_get_logger_early_request_capture();

// Завантаження текстового домену для перекладів
function ip_get_logger_load_textdomain() {
    load_plugin_textdomain('ip-get-logger', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'ip_get_logger_load_textdomain', 9);

// Активація плагіна
register_activation_hook(__FILE__, 'ip_get_logger_activate');
function ip_get_logger_activate() {
    global $wpdb;
    
    // Перевіряємо, чи існує таблиця плагіна (для визначення, чи це нова установка, чи оновлення)
    $table_name = $wpdb->prefix . IP_GET_LOGGER_TABLE;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    
    // Створення директорії для логів
    if (!file_exists(IP_GET_LOGGER_LOGS_DIR)) {
        wp_mkdir_p(IP_GET_LOGGER_LOGS_DIR);
    }
    
    // Створення .htaccess для захисту директорії логів
    $htaccess_file = IP_GET_LOGGER_LOGS_DIR . '.htaccess';
    if (!file_exists($htaccess_file)) {
        $htaccess_content = "Order deny,allow\nDeny from all";
        file_put_contents($htaccess_file, $htaccess_content);
    }
    
    // Створення index.php для додаткового захисту директорії логів
    $index_file = IP_GET_LOGGER_LOGS_DIR . 'index.php';
    if (!file_exists($index_file)) {
        $index_content = "<?php\n// Silence is golden.";
        file_put_contents($index_file, $index_content);
    }
    
    // Створення порожнього лог-файлу, якщо не існує
    $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
    if (!file_exists($log_file)) {
        file_put_contents($log_file, '');
    }
    
    // Створення таблиці в базі даних, якщо вона не існує
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        option_name varchar(191) NOT NULL,
        option_value longtext NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY option_name (option_name)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Додавання початкових налаштувань тільки для нової установки
    if (!$table_exists) {
        // Якщо це нова установка, встановлюємо значення за замовчуванням
        $default_options = array(
            'email_recipient' => get_option('admin_email'),
            'email_subject' => __('Suspicious request detected on your site', 'ip-get-logger'),
            'email_message' => __('A GET request matching your database has been detected: {request}', 'ip-get-logger'),
            'auto_cleanup_days' => 30,
            'delete_table_on_uninstall' => 1,
            'send_notifications' => 1,
            'email_throttle' => 5, // За замовчуванням обмеження 5 хвилин
        );
        
        // Зберігаємо опції в новій таблиці
        ip_get_logger_update_option('settings', $default_options);
        
        // Створюємо порожній масив для запитів
        ip_get_logger_update_option('get_requests', array());
        
        // Створюємо порожній масив для шаблонів виключення, якщо вони підтримуються
        ip_get_logger_update_option('exclude_patterns', array());
    }
}

// Деактивація плагіна
register_deactivation_hook(__FILE__, 'ip_get_logger_deactivate');
function ip_get_logger_deactivate() {
    // Операції при деактивації
}

// Видалення плагіна
register_uninstall_hook(__FILE__, 'ip_get_logger_uninstall');
function ip_get_logger_uninstall() {
    global $wpdb;
    
    // Перевіряємо, чи це справжнє видалення плагіна, а не оновлення
    // При оновленні зазвичай WP_UNINSTALL_PLUGIN не визначено або не встановлено на шлях плагіна
    if (!defined('WP_UNINSTALL_PLUGIN') || WP_UNINSTALL_PLUGIN !== plugin_basename(__FILE__)) {
        return; // Це оновлення або інша ситуація, не видаляємо дані
    }
    
    // Перевіряємо чи потрібно видаляти таблицю
    $delete_table = ip_get_logger_get_option('settings', array());
    $delete_table = isset($delete_table['delete_table_on_uninstall']) ? $delete_table['delete_table_on_uninstall'] : 0;
    
    if ($delete_table) {
        // Видаляємо таблицю тільки при справжньому видаленні плагіна
        $table_name = $wpdb->prefix . IP_GET_LOGGER_TABLE;
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }
}

// Функція для отримання опцій з таблиці бази даних
function ip_get_logger_get_option($option_name, $default = false) {
    global $wpdb;
    $table_name = $wpdb->prefix . IP_GET_LOGGER_TABLE;
    
    $query = $wpdb->prepare(
        "SELECT option_value FROM {$table_name} WHERE option_name = %s LIMIT 1",
        $option_name
    );
    
    $result = $wpdb->get_var($query);
    
    if ($result === null) {
        return $default;
    }
    
    return maybe_unserialize($result);
}

// Функція для оновлення опцій в таблиці бази даних
function ip_get_logger_update_option($option_name, $option_value) {
    global $wpdb;
    $table_name = $wpdb->prefix . IP_GET_LOGGER_TABLE;
    
    $serialized_value = maybe_serialize($option_value);
    
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE option_name = %s",
            $option_name
        )
    );
    
    if ($exists) {
        $result = $wpdb->update(
            $table_name,
            array('option_value' => $serialized_value),
            array('option_name' => $option_name),
            array('%s'),
            array('%s')
        );
    } else {
        $result = $wpdb->insert(
            $table_name,
            array(
                'option_name' => $option_name,
                'option_value' => $serialized_value
            ),
            array('%s', '%s')
        );
    }
    
    return $result !== false;
}

// Ініціалізація плагіна
function ip_get_logger_init() {
    $ip_get_logger = new IP_Get_Logger();
    $ip_get_logger->init();
    
    if (is_admin()) {
        $ip_get_logger_admin = new IP_Get_Logger_Admin();
        $ip_get_logger_admin->init();
    }
}
add_action('plugins_loaded', 'ip_get_logger_init', 10);

// AJAX-хендлер для завантаження експортованих файлів
add_action('wp_ajax_ip_get_logger_download_export', 'ip_get_logger_download_export');
function ip_get_logger_download_export() {
    // Перевіряємо nonce
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'ip-get-logger-export-nonce')) {
        wp_die(__('Security error', 'ip-get-logger'));
    }
    
    // Перевіряємо права
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions', 'ip-get-logger'));
    }
    
    // Перевіряємо наявність файлу
    if (!isset($_GET['file'])) {
        wp_die(__('File not found', 'ip-get-logger'));
    }
    
    // Обмежуємо доступ лише до тимчасових файлів
    $file_name = sanitize_file_name($_GET['file']);
    if (strpos($file_name, 'ip-get-logger-export-') !== 0) {
        wp_die(__('Invalid file', 'ip-get-logger'));
    }
    
    // Отримуємо шлях до файлу
    $file_path = get_temp_dir() . $file_name;
    
    // Перевіряємо чи файл існує
    if (!file_exists($file_path)) {
        wp_die(__('File not found', 'ip-get-logger'));
    }
    
    // Відправляємо файл для завантаження
    header('Content-Description: File Transfer');
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="ip-get-logger-export.txt"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    
    // Видаляємо тимчасовий файл
    @unlink($file_path);
    
    exit;
}

// Adding update check via GitHub
require_once plugin_dir_path( __FILE__ ) . 'updates/github-updater.php';
if ( function_exists( 'ip_get_logger_github_updater_init' ) ) {
    ip_get_logger_github_updater_init(
        __FILE__,       // Plugin file path
        'pekarskyi',     // Your GitHub username
        '',              // Access token (empty)
        'ip-get-logger' // Repository name (optional)
        // Other parameters are determined automatically
    );
} 