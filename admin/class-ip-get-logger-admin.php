<?php
/**
 * Клас для адміністративної частини плагіна
 */
class IP_Get_Logger_Admin {

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
     * Конструктор
     */
    public function __construct() {
        $this->options = ip_get_logger_get_option('settings', array());
        $this->get_requests = ip_get_logger_get_option('get_requests', array());
    }

    /**
     * Ініціалізація адмін-частини
     */
    public function init() {
        // Додаємо меню в адмінці
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Реєструємо налаштування
        add_action('admin_init', array($this, 'register_settings'));

        // Додаємо AJAX-хендлери
        add_action('wp_ajax_ip_get_logger_import', array($this, 'ajax_import_requests'));
        add_action('wp_ajax_ip_get_logger_export', array($this, 'ajax_export_requests'));
        add_action('wp_ajax_ip_get_logger_clear_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_ip_get_logger_add_request', array($this, 'ajax_add_request'));
        add_action('wp_ajax_ip_get_logger_delete_request', array($this, 'ajax_delete_request'));
        add_action('wp_ajax_ip_get_logger_edit_request', array($this, 'ajax_edit_request'));
        add_action('wp_ajax_ip_get_logger_update_from_github', array($this, 'ajax_update_from_github'));
        add_action('wp_ajax_ip_get_logger_clear_database', array($this, 'ajax_clear_database'));
    }

    /**
     * Додавання меню в адмінку
     */
    public function add_admin_menu() {
        add_menu_page(
            'IP GET Logger',
            'IP GET Logger',
            'manage_options',
            'ip-get-logger',
            array($this, 'display_logs_page'),
            'dashicons-list-view',
            100
        );
        
        add_submenu_page(
            'ip-get-logger',
            __('GET Requests Logs', 'ip-get-logger'),
            __('GET Requests Logs', 'ip-get-logger'),
            'manage_options',
            'ip-get-logger',
            array($this, 'display_logs_page')
        );
        
        add_submenu_page(
            'ip-get-logger',
            __('Patterns', 'ip-get-logger'),
            __('Patterns', 'ip-get-logger'),
            'manage_options',
            'ip-get-logger-db',
            array($this, 'display_requests_page')
        );
        
        add_submenu_page(
            'ip-get-logger',
            __('Settings', 'ip-get-logger'),
            __('Settings', 'ip-get-logger'),
            'manage_options',
            'ip-get-logger-settings',
            array($this, 'display_settings_page')
        );
        
        add_submenu_page(
            'ip-get-logger',
            __('Test URL', 'ip-get-logger'),
            __('Test URL', 'ip-get-logger'),
            'manage_options',
            'ip-get-logger-test-url',
            array($this, 'display_test_url_page')
        );
    }

    /**
     * Реєстрація налаштувань
     */
    public function register_settings() {
        register_setting('ip_get_logger_settings', 'ip_get_logger_form_settings', array($this, 'sanitize_settings'));
        
        add_settings_section(
            'ip_get_logger_email_section',
            __('Notification Settings', 'ip-get-logger'),
            array($this, 'email_section_callback'),
            'ip-get-logger-settings'
        );
        
        add_settings_field(
            'send_notifications',
            __('Enable Email Notifications', 'ip-get-logger'),
            array($this, 'send_notifications_callback'),
            'ip-get-logger-settings',
            'ip_get_logger_email_section'
        );
        
        add_settings_field(
            'email_recipient',
            __('Recipient Email', 'ip-get-logger'),
            array($this, 'email_recipient_callback'),
            'ip-get-logger-settings',
            'ip_get_logger_email_section'
        );
        
        add_settings_field(
            'email_subject',
            __('Email Subject', 'ip-get-logger'),
            array($this, 'email_subject_callback'),
            'ip-get-logger-settings',
            'ip_get_logger_email_section'
        );
        
        add_settings_field(
            'email_message',
            __('Email Message', 'ip-get-logger'),
            array($this, 'email_message_callback'),
            'ip-get-logger-settings',
            'ip_get_logger_email_section'
        );
        
        add_settings_section(
            'ip_get_logger_logs_section',
            __('Log Settings', 'ip-get-logger'),
            array($this, 'logs_section_callback'),
            'ip-get-logger-settings'
        );
        
        add_settings_field(
            'auto_cleanup_days',
            __('Auto-cleanup logs (days)', 'ip-get-logger'),
            array($this, 'auto_cleanup_days_callback'),
            'ip-get-logger-settings',
            'ip_get_logger_logs_section'
        );
        
        add_settings_section(
            'ip_get_logger_database_section',
            __('Database Settings', 'ip-get-logger'),
            array($this, 'database_section_callback'),
            'ip-get-logger-settings'
        );
        
        add_settings_field(
            'delete_table_on_uninstall',
            __('Delete data when uninstalling the plugin', 'ip-get-logger'),
            array($this, 'delete_table_callback'),
            'ip-get-logger-settings',
            'ip_get_logger_database_section'
        );
    }

    /**
     * Виведення сторінки з базою запитів
     */
    public function display_requests_page() {
        // Підключаємо необхідні скрипти і стилі
        wp_enqueue_style('ip-get-logger-admin', IP_GET_LOGGER_PLUGIN_URL . 'admin/css/ip-get-logger-admin.css', array(), IP_GET_LOGGER_VERSION);
        wp_enqueue_script('ip-get-logger-admin', IP_GET_LOGGER_PLUGIN_URL . 'admin/js/ip-get-logger-admin.js', array('jquery'), IP_GET_LOGGER_VERSION, true);
        wp_localize_script('ip-get-logger-admin', 'ip_get_logger_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ip-get-logger-nonce')
        ));
        
        // Отримуємо збережені запити
        $get_requests = $this->get_requests;
        
        // Зберігаємо повну кількість записів незалежно від пошуку
        $total_requests_count = count($get_requests);
        
        // Отримуємо кількість записів з віддаленого репозиторію
        $remote_requests_count = $this->get_remote_requests_count();
        
        // Параметри пошуку
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        // Зберігаємо оригінальні індекси при фільтрації
        $filtered_requests = array();
        
        // Фільтруємо запити за пошуковим запитом
        if (!empty($search)) {
            foreach ($get_requests as $index => $request) {
                if (stripos($request, $search) !== false) {
                    $filtered_requests[] = array(
                        'index' => $index,
                        'request' => $request
                    );
                }
            }
        } else {
            // Якщо немає пошуку, просто додаємо всі запити з оригінальними індексами
            foreach ($get_requests as $index => $request) {
                $filtered_requests[] = array(
                    'index' => $index,
                    'request' => $request
                );
            }
        }
        
        // Параметри пагінації
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
        $per_page = max(10, min(100, $per_page)); // Обмежуємо значення від 10 до 100
        
        $total_items = count($filtered_requests);
        $total_pages = ceil($total_items / $per_page);
        $current_page = isset($_GET['paged']) ? max(1, min($total_pages, intval($_GET['paged']))) : 1;
        
        // Отримуємо запити для поточної сторінки
        $offset = ($current_page - 1) * $per_page;
        $paged_requests = array_slice($filtered_requests, $offset, $per_page);
        
        // Виводимо шаблон
        include(IP_GET_LOGGER_PLUGIN_DIR . 'admin/partials/requests-page.php');
    }
    
    /**
     * Виведення сторінки з логами
     */
    public function display_logs_page() {
        // Підключаємо необхідні скрипти і стилі
        wp_enqueue_style('ip-get-logger-admin', IP_GET_LOGGER_PLUGIN_URL . 'admin/css/ip-get-logger-admin.css', array(), IP_GET_LOGGER_VERSION);
        wp_enqueue_script('ip-get-logger-admin', IP_GET_LOGGER_PLUGIN_URL . 'admin/js/ip-get-logger-admin.js', array('jquery'), IP_GET_LOGGER_VERSION, true);
        wp_localize_script('ip-get-logger-admin', 'ip_get_logger_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ip-get-logger-nonce')
        ));
        
        // Отримуємо логи
        $logs = $this->get_logs();
        
        // Параметри фільтрації
        $filter_date = isset($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : '';
        $filter_ip = isset($_GET['filter_ip']) ? sanitize_text_field($_GET['filter_ip']) : '';
        $filter_url = isset($_GET['filter_url']) ? sanitize_text_field($_GET['filter_url']) : '';
        $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
        
        // Фільтруємо логи
        if (!empty($filter_date) || !empty($filter_ip) || !empty($filter_url) || !empty($filter_status)) {
            $filtered_logs = array();
            
            foreach ($logs as $log) {
                $log_data = json_decode($log, true);
                
                // Перевіряємо чи лог відповідає всім фільтрам
                $match = true;
                
                if (!empty($filter_date) && strpos($log_data['timestamp'], $filter_date) === false) {
                    $match = false;
                }
                
                if (!empty($filter_ip) && strpos($log_data['ip'], $filter_ip) === false) {
                    $match = false;
                }
                
                if (!empty($filter_url) && strpos($log_data['url'], $filter_url) === false) {
                    $match = false;
                }
                
                if (!empty($filter_status) && $log_data['status_code'] != $filter_status) {
                    $match = false;
                }
                
                if ($match) {
                    $filtered_logs[] = $log;
                }
            }
            
            $logs = $filtered_logs;
        }
        
        // Виводимо шаблон
        include(IP_GET_LOGGER_PLUGIN_DIR . 'admin/partials/logs-page.php');
    }
    
    /**
     * Виведення сторінки налаштувань
     */
    public function display_settings_page() {
        // Якщо форма була відправлена, зберігаємо налаштування
        if (isset($_POST['submit']) && isset($_POST['ip_get_logger_form_settings'])) {
            $this->save_settings($_POST['ip_get_logger_form_settings']);
        }
        
        // Виводимо шаблон
        include(IP_GET_LOGGER_PLUGIN_DIR . 'admin/partials/settings-page.php');
    }

    /**
     * Колбек для секції налаштувань email
     */
    public function email_section_callback() {
        echo '<p>' . __('Configure parameters for sending email notifications when a GET request matches', 'ip-get-logger') . '</p>';
    }
    
    /**
     * Колбек для вмикання/вимикання сповіщень
     */
    public function send_notifications_callback() {
        $enabled = isset($this->options['send_notifications']) ? $this->options['send_notifications'] : 1;
        echo '<label><input type="checkbox" id="send_notifications" name="ip_get_logger_form_settings[send_notifications]" value="1" ' . checked(1, $enabled, false) . ' /> ' . __('Send email notifications when a GET request matches', 'ip-get-logger') . '</label>';
    }
    
    /**
     * Колбек для поля email отримувача
     */
    public function email_recipient_callback() {
        $email = isset($this->options['email_recipient']) ? $this->options['email_recipient'] : get_option('admin_email');
        echo '<input type="email" id="email_recipient" name="ip_get_logger_form_settings[email_recipient]" value="' . esc_attr($email) . '" class="regular-text" />';
    }
    
    /**
     * Колбек для поля теми листа
     */
    public function email_subject_callback() {
        $subject = isset($this->options['email_subject']) ? $this->options['email_subject'] : __('GET Request Match Found', 'ip-get-logger');
        echo '<input type="text" id="email_subject" name="ip_get_logger_form_settings[email_subject]" value="' . esc_attr($subject) . '" class="regular-text" />';
    }
    
    /**
     * Колбек для поля тексту повідомлення
     */
    public function email_message_callback() {
        $message = isset($this->options['email_message']) ? $this->options['email_message'] : __('A GET request matching your database has been detected: {request}', 'ip-get-logger');
        echo '<textarea id="email_message" name="ip_get_logger_form_settings[email_message]" rows="5" class="large-text">' . esc_textarea($message) . '</textarea>';
        echo '<p class="description">' . __('Use {request} to display the request URL', 'ip-get-logger') . '</p>';
    }
    
    /**
     * Колбек для секції налаштувань логів
     */
    public function logs_section_callback() {
        echo '<p>' . __('Configure parameters for logging GET requests', 'ip-get-logger') . '</p>';
    }
    
    /**
     * Колбек для поля автоочищення логів
     */
    public function auto_cleanup_days_callback() {
        $days = isset($this->options['auto_cleanup_days']) ? intval($this->options['auto_cleanup_days']) : 30;
        echo '<input type="number" id="auto_cleanup_days" name="ip_get_logger_form_settings[auto_cleanup_days]" value="' . esc_attr($days) . '" class="small-text" min="0" />';
        echo '<p class="description">' . __('Specify the number of days to keep logs (0 - never delete)', 'ip-get-logger') . '</p>';
    }
    
    /**
     * Колбек для секції налаштувань бази даних
     */
    public function database_section_callback() {
        echo '<p>' . __('Configure database parameters', 'ip-get-logger') . '</p>';
    }
    
    /**
     * Колбек для поля видалення таблиці при деінсталяції
     */
    public function delete_table_callback() {
        $delete_table = isset($this->options['delete_table_on_uninstall']) ? intval($this->options['delete_table_on_uninstall']) : 1;
        echo '<label><input type="checkbox" id="delete_table_on_uninstall" name="ip_get_logger_form_settings[delete_table_on_uninstall]" value="1" ' . checked(1, $delete_table, false) . ' /> ' . __('Delete database table when uninstalling the plugin', 'ip-get-logger') . '</label>';
        echo '<p class="description">' . __('Warning! If this option is enabled, all patterns and settings will be deleted along with the plugin!', 'ip-get-logger') . '</p>';
    }
    
    /**
     * Санітизація введених даних
     */
    public function sanitize_settings($input) {
        $input['email_recipient'] = sanitize_email($input['email_recipient']);
        $input['email_subject'] = sanitize_text_field($input['email_subject']);
        $input['email_message'] = wp_kses_post($input['email_message']);
        $input['auto_cleanup_days'] = intval($input['auto_cleanup_days']);
        $input['delete_table_on_uninstall'] = isset($input['delete_table_on_uninstall']) ? 1 : 0;
        $input['send_notifications'] = isset($input['send_notifications']) ? 1 : 0;
        
        return $input;
    }
    
    /**
     * Зберігаємо налаштування в БД
     */
    private function save_settings($input) {
        $settings = $this->options;
        
        // Оновлюємо налаштування
        $settings['email_recipient'] = sanitize_email($input['email_recipient']);
        $settings['email_subject'] = sanitize_text_field($input['email_subject']);
        $settings['email_message'] = sanitize_textarea_field($input['email_message']);
        $settings['auto_cleanup_days'] = intval($input['auto_cleanup_days']);
        $settings['delete_table_on_uninstall'] = isset($input['delete_table_on_uninstall']) ? 1 : 0;
        
        // Зберігаємо налаштування
        ip_get_logger_update_option('settings', $settings);
        
        // Оновлюємо локальну копію налаштувань
        $this->options = $settings;
        
        // Додаємо повідомлення про успішне збереження
        add_settings_error(
            'ip_get_logger_settings',
            'settings_updated',
            __('Settings saved successfully', 'ip-get-logger'),
            'updated'
        );
    }

    /**
     * AJAX-обробник для імпорту запитів
     */
    public function ajax_import_requests() {
        // Перевіряємо nonce
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        // Перевіряємо права
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to perform this operation', 'ip-get-logger'));
            return;
        }
        
        // Перевіряємо наявність файлу
        if (empty($_FILES['import_file'])) {
            wp_send_json_error(__('No file uploaded', 'ip-get-logger'));
            return;
        }
        
        $file = $_FILES['import_file'];
        
        // Перевіряємо розширення файлу
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($ext !== 'txt') {
            wp_send_json_error(__('Only .txt files are allowed', 'ip-get-logger'));
            return;
        }
        
        // Читаємо вміст файлу
        $content = file_get_contents($file['tmp_name']);
        
        if ($content === false) {
            wp_send_json_error(__('Failed to read file', 'ip-get-logger'));
            return;
        }
        
        // Розбиваємо на рядки
        $lines = explode("\n", $content);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);
        
        // Додаємо до існуючих запитів
        $this->get_requests = array_unique(array_merge($this->get_requests, $lines));
        
        // Зберігаємо оновлені запити
        ip_get_logger_update_option('get_requests', $this->get_requests);
        
        wp_send_json_success(array(
            'message' => sprintf(
                _n('Imported %d pattern', 'Imported %d patterns', count($lines), 'ip-get-logger'),
                count($lines)
            ),
            'requests' => $this->get_requests
        ));
    }
    
    /**
     * AJAX-обробник для експорту запитів
     */
    public function ajax_export_requests() {
        // Перевіряємо nonce
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        // Перевіряємо права
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to perform this operation', 'ip-get-logger'));
            return;
        }
        
        $get_requests = $this->get_requests;
        
        if (empty($get_requests)) {
            wp_send_json_error(__('No patterns to export', 'ip-get-logger'));
            return;
        }
        
        // Створюємо тимчасовий файл в правильній директорії
        $temp_dir = get_temp_dir();
        $file_name = 'ip-get-logger-export-' . time() . '.txt';
        $file_path = $temp_dir . $file_name;
        
        // Записуємо запити у файл
        file_put_contents($file_path, implode("\n", $get_requests));
        
        // Готуємо URL для завантаження
        $export_url = add_query_arg(array(
            'action' => 'ip_get_logger_download_export',
            'nonce' => wp_create_nonce('ip-get-logger-export-nonce'),
            'file' => $file_name
        ), admin_url('admin-ajax.php'));
        
        wp_send_json_success(array('export_url' => $export_url));
    }
    
    /**
     * AJAX-обробник для очищення логів
     */
    public function ajax_clear_logs() {
        // Перевіряємо nonce
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        // Перевіряємо права
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to perform this operation', 'ip-get-logger'));
            return;
        }
        
        $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
        
        // Очищуємо файл
        file_put_contents($log_file, '');
        
        wp_send_json_success(array('message' => __('Logs cleared successfully', 'ip-get-logger')));
    }
    
    /**
     * AJAX-обробник для додавання запиту
     */
    public function ajax_add_request() {
        // Перевіряємо nonce
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        // Перевіряємо права
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to perform this operation', 'ip-get-logger'));
            return;
        }
        
        // Отримуємо і валідуємо запит, без видалення HTML-тегів
        $request = isset($_POST['request']) ? wp_unslash($_POST['request']) : '';
        
        // Базова валідація, видаляємо контрольні символи та зайві пробіли
        $request = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $request));
        
        if (empty($request)) {
            wp_send_json_error(__('Pattern cannot be empty', 'ip-get-logger'));
            return;
        }
        
        // Перевіряємо чи запит вже існує
        if (in_array($request, $this->get_requests)) {
            wp_send_json_error(__('This pattern already exists in the database', 'ip-get-logger'));
            return;
        }
        
        // Додаємо запит
        $this->get_requests[] = $request;
        
        // Зберігаємо оновлені запити
        ip_get_logger_update_option('get_requests', $this->get_requests);
        
        wp_send_json_success(array(
            'message' => __('Pattern added successfully', 'ip-get-logger'),
            'requests' => $this->get_requests
        ));
    }
    
    /**
     * AJAX-обробник для редагування запиту
     */
    public function ajax_edit_request() {
        // Перевіряємо nonce
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        // Перевіряємо права
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to perform this operation', 'ip-get-logger'));
            return;
        }
        
        // Отримуємо індекс запиту
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        
        // Отримуємо актуальний список запитів з опцій
        $all_requests = ip_get_logger_get_option('get_requests', array());
        
        if ($index < 0 || !isset($all_requests[$index])) {
            wp_send_json_error(__('Pattern not found', 'ip-get-logger'));
            return;
        }
        
        // Отримуємо і валідуємо запит, без видалення HTML-тегів
        $request = isset($_POST['request']) ? wp_unslash($_POST['request']) : '';
        
        // Базова валідація, видаляємо контрольні символи та зайві пробіли
        $request = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $request));
        
        if (empty($request)) {
            wp_send_json_error(__('Pattern cannot be empty', 'ip-get-logger'));
            return;
        }
        
        // Перевіряємо чи запит вже існує в іншому індексі
        foreach ($all_requests as $i => $existing_request) {
            if ($i != $index && $existing_request === $request) {
                wp_send_json_error(__('This pattern already exists in the database', 'ip-get-logger'));
                return;
            }
        }
        
        // Оновлюємо запит
        $all_requests[$index] = $request;
        
        // Зберігаємо оновлені запити
        ip_get_logger_update_option('get_requests', $all_requests);
        
        // Оновлюємо локальний масив запитів
        $this->get_requests = $all_requests;
        
        wp_send_json_success(array(
            'message' => __('Pattern updated successfully', 'ip-get-logger'),
            'requests' => $all_requests
        ));
    }
    
    /**
     * AJAX-обробник для видалення запиту
     */
    public function ajax_delete_request() {
        // Перевіряємо nonce
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        // Перевіряємо права
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to perform this operation', 'ip-get-logger'));
            return;
        }
        
        // Отримуємо індекс запиту
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        
        // Отримуємо актуальний список запитів з опцій
        $all_requests = ip_get_logger_get_option('get_requests', array());
        
        if ($index < 0 || !isset($all_requests[$index])) {
            wp_send_json_error(__('Pattern not found', 'ip-get-logger'));
            return;
        }
        
        // Видаляємо запит
        array_splice($all_requests, $index, 1);
        
        // Зберігаємо оновлені запити
        ip_get_logger_update_option('get_requests', $all_requests);
        
        // Оновлюємо локальний масив запитів
        $this->get_requests = $all_requests;
        
        wp_send_json_success(array(
            'message' => __('Pattern deleted successfully', 'ip-get-logger'),
            'requests' => $all_requests
        ));
    }
    
    /**
     * AJAX-обробник для оновлення бази запитів з GitHub
     */
    public function ajax_update_from_github() {
        // Перевіряємо nonce
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        // Перевіряємо права
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to perform this operation', 'ip-get-logger'));
            return;
        }
        
        // URL файлу в GitHub
        $github_file_url = 'https://raw.githubusercontent.com/pekarskyi/ip-get-logger-db/main/ip-get-logger-import.txt';
        
        // Використовуємо WordPress HTTP API для безпечного отримання файлу
        $response = wp_remote_get($github_file_url);
        
        // Перевіряємо на помилки
        if (is_wp_error($response)) {
            wp_send_json_error(sprintf(
                __('Failed to fetch data from GitHub: %s', 'ip-get-logger'),
                $response->get_error_message()
            ));
            return;
        }
        
        // Перевіряємо HTTP код відповіді
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            wp_send_json_error(sprintf(
                __('Failed to fetch data from GitHub. HTTP Response Code: %s', 'ip-get-logger'),
                $response_code
            ));
            return;
        }
        
        // Отримуємо тіло відповіді (вміст файлу)
        $content = wp_remote_retrieve_body($response);
        
        if (empty($content)) {
            wp_send_json_error(__('Empty response from GitHub', 'ip-get-logger'));
            return;
        }
        
        // Розбиваємо на рядки
        $lines = explode("\n", $content);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);
        
        // Отримуємо поточні запити
        $current_requests = $this->get_requests;
        
        // Лічильники для статистики
        $added_count = 0;
        $duplicated_count = 0;
        
        // Додаємо нові запити, які ще не існують
        foreach ($lines as $request) {
            if (!in_array($request, $current_requests)) {
                $current_requests[] = $request;
                $added_count++;
            } else {
                $duplicated_count++;
            }
        }
        
        // Якщо є нові запити, оновлюємо базу
        if ($added_count > 0) {
            // Зберігаємо оновлені запити
            ip_get_logger_update_option('get_requests', $current_requests);
            $this->get_requests = $current_requests;
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Database updated successfully: %d new patterns added, %d duplicates skipped.', 'ip-get-logger'),
                    $added_count,
                    $duplicated_count
                ),
                'requests' => $current_requests
            ));
        } else {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Your database is up to date. No new patterns added. %d duplicates skipped.', 'ip-get-logger'),
                    $duplicated_count
                ),
                'requests' => $current_requests
            ));
        }
    }

    /**
     * AJAX-обробник для очищення бази даних
     */
    public function ajax_clear_database() {
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'ip-get-logger'));
        }
        
        // Очищаємо масив запитів
        $this->get_requests = array();
        
        // Зберігаємо оновлений масив
        ip_get_logger_update_option('get_requests', array());
        
        wp_send_json_success(array(
            'message' => __('The list of patterns is successfully cleared.', 'ip-get-logger')
        ));
    }

    /**
     * Отримати логи з файлу
     *
     * @return array
     */
    private function get_logs() {
        $log_file = IP_GET_LOGGER_LOGS_DIR . 'requests.log';
        
        if (!file_exists($log_file)) {
            return array();
        }
        
        $logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Сортування логів за часом (останні спочатку)
        usort($logs, function($a, $b) {
            $a_data = json_decode($a, true);
            $b_data = json_decode($b, true);
            
            if (!isset($a_data['timestamp']) || !isset($b_data['timestamp'])) {
                return 0;
            }
            
            return strtotime($b_data['timestamp']) - strtotime($a_data['timestamp']);
        });
        
        return $logs;
    }

    /**
     * Відображення сторінки тестування URL
     */
    public function display_test_url_page() {
        $test_results = null;
        $test_html_tag = false;
        
        // Перевіряємо чи була надіслана форма
        if (isset($_POST['test_url']) && isset($_POST['test_url_nonce']) && wp_verify_nonce($_POST['test_url_nonce'], 'ip_get_logger_test_url')) {
            $test_url = sanitize_text_field($_POST['test_url']);
            $test_html_tag = isset($_POST['test_html_tag']) ? true : false;
            
            // Якщо користувач вибрав тестування HTML-тегів, додаємо <iframe> до URL
            if ($test_html_tag && strpos($test_url, '<iframe>') === false) {
                // Якщо URL містить параметри запиту
                if (strpos($test_url, '?') !== false) {
                    // Додаємо iframe як додатковий параметр
                    $test_url .= '&q=<iframe>';
                } else {
                    // Додаємо iframe як перший параметр
                    $test_url .= '?q=<iframe>';
                }
            }
            
            // Створюємо об'єкт класу IP_Get_Logger
            $logger = new IP_Get_Logger();
            
            // Тестуємо URL
            $test_results = $logger->test_url_matching($test_url);
            
            // Додатково перевіряємо наявність HTML-тегів у шаблонах
            $get_requests = ip_get_logger_get_option('get_requests', array());
            $html_tag_patterns = array();
            
            foreach ($get_requests as $pattern) {
                if (strpos($pattern, '<') !== false || strpos($pattern, '>') !== false) {
                    $html_tag_patterns[] = $pattern;
                }
            }
            
            $test_results['html_tag_patterns'] = $html_tag_patterns;
        }
        
        // Виводимо шаблон
        include(IP_GET_LOGGER_PLUGIN_DIR . 'admin/partials/test-url-page.php');
    }

    /**
     * Отримання кількості записів з віддаленого репозиторію
     *
     * @return int|string Кількість записів або повідомлення про помилку
     */
    private function get_remote_requests_count() {
        $remote_url = 'https://raw.githubusercontent.com/pekarskyi/ip-get-logger-db/main/ip-get-logger-import.txt';
        
        $response = wp_remote_get($remote_url);
        
        if (is_wp_error($response)) {
            return __('Error fetching remote data', 'ip-get-logger');
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            return __('No data available', 'ip-get-logger');
        }
        
        // Розбиваємо текст на рядки та фільтруємо порожні
        $lines = array_filter(explode("\n", $body));
        
        return count($lines);
    }
} 