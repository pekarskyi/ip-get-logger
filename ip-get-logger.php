<?php
/**
 * Plugin Name: IP GET Logger
 * Description: Plugin for tracking GET requests to the site and logging them
 * Version: 1.0.0
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
define('IP_GET_LOGGER_LOGS_DIR', IP_GET_LOGGER_PLUGIN_DIR . 'logs/');
define('IP_GET_LOGGER_TABLE', 'ip_get_logger');

// Глобальна змінна для відслідковування вже зареєстрованих запитів
global $ip_get_logger_processed_requests;
$ip_get_logger_processed_requests = array();

// Підключення необхідних файлів
require_once(IP_GET_LOGGER_PLUGIN_DIR . 'includes/class-ip-get-logger.php');
require_once(IP_GET_LOGGER_PLUGIN_DIR . 'admin/class-ip-get-logger-admin.php');

// Перехоплення запитів до статичних файлів перед завантаженням WordPress
function ip_get_logger_early_request_capture() {
    global $ip_get_logger_processed_requests;
    
    // Перехоплюємо тільки GET-запити
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }
    
    // Отримуємо URL запиту
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    
    // Отримуємо повний URL запиту
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $request_url = $protocol . '://' . $host . $request_uri;
    
    // Якщо цей URL вже було оброблено, пропускаємо
    if (in_array($request_url, $ip_get_logger_processed_requests)) {
        return;
    }
    
    // Перевіряємо чи запит до статичного файлу
    $file_extensions = array('.txt', '.log', '.sql', '.php', '.js', '.json', '.xml', '.css', '.svg', '.html', '.htm');
    $is_static_file = false;
    
    foreach ($file_extensions as $extension) {
        if (strpos($request_uri, $extension) !== false) {
            $is_static_file = true;
            break;
        }
    }
    
    // Якщо це не статичний файл, пропускаємо
    if (!$is_static_file) {
        return;
    }
    
    // Отримуємо список шаблонів для перевірки
    $get_requests = ip_get_logger_get_option('get_requests', array());
    
    // Перевіряємо чи є співпадіння з шаблонами
    $matched_pattern = false;
    foreach ($get_requests as $pattern) {
        // Перевіряємо пряме співпадіння
        if (strpos($request_uri, $pattern) !== false) {
            $matched_pattern = $pattern;
            break;
        }
        
        // Перевіряємо шаблони з зірочками
        if (strpos($pattern, '*') !== false) {
            $pattern_regex = str_replace('*', '.*', $pattern);
            if (preg_match('/' . preg_quote($pattern_regex, '/') . '/', $request_uri)) {
                $matched_pattern = $pattern;
                break;
            }
        }
    }
    
    // Якщо є співпадіння, логуємо запит
    if ($matched_pattern) {
        // Додаємо URL до оброблених
        $ip_get_logger_processed_requests[] = $request_url;
        
        // Записуємо в лог
        $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
        
        // Перевіряємо чи існує директорія для логів
        if (!file_exists(IP_GET_LOGGER_LOGS_DIR)) {
            wp_mkdir_p(IP_GET_LOGGER_LOGS_DIR);
        }
        
        // Визначаємо статус-код для статичного файлу
        $status_code = 200; // За замовчуванням
        
        if ($is_static_file) {
            // Отримуємо відносний шлях із запиту
            $path = parse_url($request_uri, PHP_URL_PATH);
            
            // Формуємо повний шлях до файлу
            $file_path = ABSPATH . ltrim($path, '/');
            
            // Перевіряємо наявність файлу
            if (file_exists($file_path) && is_file($file_path)) {
                $status_code = 200;
            } else {
                $status_code = 404;
            }
        }
        
        $log_data = array(
            'method' => 'GET',
            'url' => $request_url,
            'matched_pattern' => $matched_pattern,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Not provided',
            'status_code' => $status_code,
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
            // Відправляємо сповіщення
            $to = $settings['email_recipient'];
            $subject = isset($settings['email_subject']) ? $settings['email_subject'] : __('GET Request Match Found', 'ip-get-logger');
            
            $message = isset($settings['email_message']) ? $settings['email_message'] : __('A GET request matching your database has been detected: {request}', 'ip-get-logger');
            $message = str_replace('{request}', $request_url, $message);
            
            // Додаємо додаткову інформацію
            $message .= '<br><br>';
            $message .= __('Request details:', 'ip-get-logger') . '<br>';
            $message .= __('IP Address:', 'ip-get-logger') . ' ' . $_SERVER['REMOTE_ADDR'] . '<br>';
            $message .= __('User Agent:', 'ip-get-logger') . ' ' . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Not provided') . '<br>';
            $message .= __('Date and Time:', 'ip-get-logger') . ' ' . current_time('mysql') . '<br>';
            
            $headers = array('Content-Type: text/html; charset=UTF-8');
            
            // Відправляємо лист
            wp_mail($to, $subject, $message, $headers);
        }
    }
}

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
    
    // Створення таблиці в базі даних
    $table_name = $wpdb->prefix . IP_GET_LOGGER_TABLE;
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
    
    // Додавання початкових налаштувань
    $default_options = array(
        'email_recipient' => get_option('admin_email'),
        'email_subject' => __('GET Request Match Found', 'ip-get-logger'),
        'email_message' => __('A GET request matching your database has been detected: {request}', 'ip-get-logger'),
        'auto_cleanup_days' => 30,
        'delete_table_on_uninstall' => 1,
        'send_notifications' => 1,
    );
    
    // Зберігаємо опції в новій таблиці
    ip_get_logger_update_option('settings', $default_options);
    
    // Створюємо порожній масив для запитів
    ip_get_logger_update_option('get_requests', array());
    
    // Створення лог-файлу
    $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
    if (!file_exists($log_file)) {
        file_put_contents($log_file, '');
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
    
    // Перевіряємо чи потрібно видаляти таблицю
    $delete_table = ip_get_logger_get_option('settings', array());
    $delete_table = isset($delete_table['delete_table_on_uninstall']) ? $delete_table['delete_table_on_uninstall'] : 0;
    
    if ($delete_table) {
        // Видаляємо таблицю
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