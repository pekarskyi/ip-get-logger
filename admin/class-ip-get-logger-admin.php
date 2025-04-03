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
     * Шаблони для виключення
     *
     * @var array
     */
    private $exclude_patterns;

    /**
     * Конструктор
     */
    public function __construct() {
        $this->options = ip_get_logger_get_option('settings', array());
        $this->get_requests = ip_get_logger_get_option('get_requests', array());
        $this->exclude_patterns = ip_get_logger_get_option('exclude_patterns', array());
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
        
        // AJAX-хендлери для шаблонів виключень
        add_action('wp_ajax_ip_get_logger_add_exclude_pattern', array($this, 'ajax_add_exclude_pattern'));
        add_action('wp_ajax_ip_get_logger_edit_exclude_pattern', array($this, 'ajax_edit_exclude_pattern'));
        add_action('wp_ajax_ip_get_logger_delete_exclude_pattern', array($this, 'ajax_delete_exclude_pattern'));
        add_action('wp_ajax_ip_get_logger_clear_exclude_patterns', array($this, 'ajax_clear_exclude_patterns'));
        add_action('wp_ajax_ip_get_logger_update_exclude_patterns_from_github', array($this, 'ajax_update_exclude_patterns_from_github'));
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
            __('Exclude Patterns', 'ip-get-logger'),
            __('Exclude Patterns', 'ip-get-logger'),
            'manage_options',
            'ip-get-logger-exclude',
            array($this, 'display_exclude_page')
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
        register_setting(
            'ip_get_logger_settings',
            'ip_get_logger_form_settings',
            array($this, 'sanitize_settings')
        );
        
        // Секція налаштувань email
        add_settings_section(
            'ip_get_logger_email_section',
            __('Email Notifications', 'ip-get-logger'),
            array($this, 'email_section_callback'),
            'ip-get-logger-settings'
        );
        
        add_settings_field(
            'send_notifications',
            __('Send Notifications', 'ip-get-logger'),
            array($this, 'send_notifications_callback'),
            'ip-get-logger-settings',
            'ip_get_logger_email_section'
        );
        
        add_settings_field(
            'email_throttle',
            __('Email Throttle', 'ip-get-logger'),
            array($this, 'email_throttle_callback'),
            'ip-get-logger-settings',
            'ip_get_logger_email_section'
        );
        
        add_settings_field(
            'email_recipient',
            __('Email Recipient', 'ip-get-logger'),
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
        
        $filter_date = isset($_GET['filter_date']) ? sanitize_text_field($_GET['filter_date']) : '';
        $filter_ip = isset($_GET['filter_ip']) ? sanitize_text_field($_GET['filter_ip']) : '';
        $filter_country = isset($_GET['filter_country']) ? sanitize_text_field($_GET['filter_country']) : '';
        $filter_url = isset($_GET['filter_url']) ? sanitize_text_field($_GET['filter_url']) : '';
        $filter_user_agent = isset($_GET['filter_user_agent']) ? sanitize_text_field($_GET['filter_user_agent']) : '';
        
        // Параметри пагінації
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
        $per_page = max(10, min(100, $per_page)); // Обмежуємо значення від 10 до 100
        
        // Отримання логів
        $logs = $this->get_logs();
        $all_logs = array();
        
        // Створюємо екземпляр класу IP_Get_Logger для використання його методів
        $logger = new IP_Get_Logger();
        
        foreach ($logs as $log_entry) {
            $log_data = json_decode($log_entry, true);
            
            // Додаємо тип пристрою, якщо його немає
            if (!isset($log_data['device_type']) && isset($log_data['user_agent'])) {
                $log_data['device_type'] = $logger->get_device_type($log_data['user_agent']);
            }
            
            // Конвертуємо старі локалізовані значення типів пристроїв на нові англійські
            if (isset($log_data['device_type'])) {
                // Масив відповідностей локалізованих значень до англійських
                $device_type_map = array(
                    'Desktop' => 'Desktop',
                    'Mobile' => 'Mobile',
                    'Tablet' => 'Tablet',
                    'Bot' => 'Bot',
                    'Unknown' => 'Unknown'
                );
                
                // Якщо значення перекладене, замінюємо його на англійське
                if (isset($device_type_map[$log_data['device_type']])) {
                    $log_data['device_type'] = $device_type_map[$log_data['device_type']];
                }
            }
            
            // Додаємо країну, якщо її немає
            if (!isset($log_data['country']) && isset($log_data['ip'])) {
                $log_data['country'] = $logger->get_country_by_ip($log_data['ip']);
            }
            
            // Конвертуємо старі локалізовані значення країн на англійські
            if (isset($log_data['country'])) {
                // Масив відповідностей локалізованих значень до англійських
                $country_map = array(
                    'Local' => 'Local Network',
                    'Unknown' => 'Unknown'
                );
                
                // Якщо значення перекладене, замінюємо його на англійське
                if (isset($country_map[$log_data['country']])) {
                    $log_data['country'] = $country_map[$log_data['country']];
                }
            }
            
            // Фільтрація логів
            $match = true;
            
            if (!empty($filter_date) || !empty($filter_ip) || !empty($filter_country) || !empty($filter_url) || !empty($filter_user_agent)) {
                if (!empty($filter_date)) {
                    if (isset($log_data['timestamp'])) {
                        $log_date = date('Y-m-d', strtotime($log_data['timestamp']));
                        if ($log_date !== $filter_date) {
                            $match = false;
                        }
                    } else {
                        $match = false;
                    }
                }
                
                if (!empty($filter_ip)) {
                    if (!isset($log_data['ip']) || strpos($log_data['ip'], $filter_ip) === false) {
                        $match = false;
                    }
                }
                
                if (!empty($filter_country)) {
                    if (!isset($log_data['country']) || stripos($log_data['country'], $filter_country) === false) {
                        $match = false;
                    }
                }
                
                if (!empty($filter_url)) {
                    if (!isset($log_data['url']) || strpos($log_data['url'], $filter_url) === false) {
                        $match = false;
                    }
                }
                
                if (!empty($filter_user_agent)) {
                    if (!isset($log_data['user_agent']) || stripos($log_data['user_agent'], $filter_user_agent) === false) {
                        $match = false;
                    }
                }
            }
            
            if ($match) {
                $all_logs[] = $log_data;
            }
        }
        
        // Сортування логів за часом (від найновіших до найстаріших)
        usort($all_logs, function($a, $b) {
            $time_a = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
            $time_b = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
            return $time_b - $time_a; // За спаданням (найновіші вгорі)
        });
        
        // Пагінація
        $total_items = count($all_logs);
        $total_pages = ceil($total_items / $per_page);
        $current_page = isset($_GET['paged']) ? max(1, min($total_pages, intval($_GET['paged']))) : 1;
        
        // Отримуємо логи для поточної сторінки
        $offset = ($current_page - 1) * $per_page;
        $processed_logs = array_slice($all_logs, $offset, $per_page);
        
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
        echo '<p>' . __('Configure email notifications for GET request matches', 'ip-get-logger') . '</p>';
    }
    
    /**
     * Колбек для поля відправки сповіщень
     */
    public function send_notifications_callback() {
        $send_notifications = isset($this->options['send_notifications']) ? intval($this->options['send_notifications']) : 1;
        echo '<label><input type="checkbox" id="send_notifications" name="ip_get_logger_form_settings[send_notifications]" value="1" ' . checked(1, $send_notifications, false) . ' /> ' . __('Send email notifications when a request matches a pattern', 'ip-get-logger') . '</label>';
    }
    
    /**
     * Колбек для поля обмеження частоти відправлення сповіщень
     */
    public function email_throttle_callback() {
        $throttle_values = array(
            0 => __('No limit (send all notifications)', 'ip-get-logger'),
            1 => __('1 minute', 'ip-get-logger'),
            5 => __('5 minutes', 'ip-get-logger'),
            10 => __('10 minutes', 'ip-get-logger'),
            30 => __('30 minutes', 'ip-get-logger'),
            60 => __('1 hour', 'ip-get-logger'),
            360 => __('6 hours', 'ip-get-logger'),
            720 => __('12 hours', 'ip-get-logger'),
            1440 => __('24 hours', 'ip-get-logger')
        );
        
        $throttle = isset($this->options['email_throttle']) ? intval($this->options['email_throttle']) : 0;
        
        echo '<select id="email_throttle" name="ip_get_logger_form_settings[email_throttle]">';
        foreach ($throttle_values as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($throttle, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('For example, if we set 5 minutes, and multiple suspicious requests are detected within a 5-minute period, only one email notification will be sent. The next notification can be sent only 5 minutes after the previous one.', 'ip-get-logger') . '</p>';
    }
    
    /**
     * Колбек для поля отримувача email
     */
    public function email_recipient_callback() {
        $email = isset($this->options['email_recipient']) ? $this->options['email_recipient'] : get_option('admin_email');
        echo '<input type="email" id="email_recipient" name="ip_get_logger_form_settings[email_recipient]" value="' . esc_attr($email) . '" class="regular-text" />';
    }
    
    /**
     * Колбек для поля теми листа
     */
    public function email_subject_callback() {
        $subject = isset($this->options['email_subject']) ? $this->options['email_subject'] : __('Suspicious request detected on your site', 'ip-get-logger');
        echo '<input type="text" id="email_subject" name="ip_get_logger_form_settings[email_subject]" value="' . esc_attr($subject) . '" class="regular-text" />';
    }
    
    /**
     * Колбек для поля тексту повідомлення
     */
    public function email_message_callback() {
        $message = isset($this->options['email_message']) ? $this->options['email_message'] : __('A GET request matching your database has been detected: {request}', 'ip-get-logger');
        echo '<textarea id="email_message" name="ip_get_logger_form_settings[email_message]" rows="5" class="large-text">' . esc_textarea($message) . '</textarea>';
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
        $input['email_throttle'] = isset($input['email_throttle']) ? intval($input['email_throttle']) : 0;
        
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
        $settings['send_notifications'] = isset($input['send_notifications']) ? 1 : 0;
        $settings['email_throttle'] = isset($input['email_throttle']) ? intval($input['email_throttle']) : 0;
        
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
        
        // Перевіряємо, чи існує файл
        if (file_exists($log_file)) {
            // Очищуємо файл
            $result = file_put_contents($log_file, '');
            
            if ($result === false) {
                wp_send_json_error(__('Failed to clear logs. Check file permissions.', 'ip-get-logger'));
                return;
            }
            
            // Додатково очищуємо кеш опкоду PHP, якщо він активований
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
        }
        
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
    
    /**
     * Отримання кількості шаблонів виключення з віддаленого репозиторію
     *
     * @return int|string Кількість шаблонів або повідомлення про помилку
     */
    private function get_remote_exclude_patterns_count() {
        $remote_url = 'https://raw.githubusercontent.com/pekarskyi/ip-get-logger-db/main/ip-get-logger-exclude.txt';
        
        $response = wp_remote_get($remote_url);
        
        if (is_wp_error($response)) {
            return __('Error fetching remote data', 'ip-get-logger');
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            return __('No data available', 'ip-get-logger');
        }
        
        // Розбиваємо текст на рядки та фільтруємо порожні та коментарі
        $lines = array_filter(explode("\n", $body), function($line) {
            return !empty(trim($line)) && strpos(trim($line), '#') !== 0;
        });
        
        return count($lines);
    }

    /**
     * Виведення сторінки з шаблонами виключень
     */
    public function display_exclude_page() {
        // Підключаємо необхідні скрипти і стилі
        wp_enqueue_style('ip-get-logger-admin', IP_GET_LOGGER_PLUGIN_URL . 'admin/css/ip-get-logger-admin.css', array(), IP_GET_LOGGER_VERSION);
        wp_enqueue_script('ip-get-logger-admin', IP_GET_LOGGER_PLUGIN_URL . 'admin/js/ip-get-logger-admin.js', array('jquery'), IP_GET_LOGGER_VERSION, true);
        wp_localize_script('ip-get-logger-admin', 'ip_get_logger_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ip-get-logger-nonce')
        ));
        
        // Отримуємо збережені шаблони виключень
        $exclude_patterns = $this->exclude_patterns;
        
        // Зберігаємо повну кількість записів незалежно від пошуку
        $total_patterns_count = count($exclude_patterns);
        
        // Отримуємо кількість шаблонів з віддаленого репозиторію
        $remote_patterns_count = $this->get_remote_exclude_patterns_count();
        
        // Параметри пошуку
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        // Зберігаємо оригінальні індекси при фільтрації
        $filtered_patterns = array();
        
        // Фільтруємо запити за пошуковим запитом
        if (!empty($search)) {
            foreach ($exclude_patterns as $index => $pattern) {
                if (stripos($pattern, $search) !== false) {
                    $filtered_patterns[] = array(
                        'index' => $index,
                        'pattern' => $pattern
                    );
                }
            }
        } else {
            // Якщо немає пошуку, просто додаємо всі запити з оригінальними індексами
            foreach ($exclude_patterns as $index => $pattern) {
                $filtered_patterns[] = array(
                    'index' => $index,
                    'pattern' => $pattern
                );
            }
        }
        
        // Параметри пагінації
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
        $per_page = max(10, min(100, $per_page)); // Обмежуємо значення від 10 до 100
        
        $total_items = count($filtered_patterns);
        $total_pages = ceil($total_items / $per_page);
        $current_page = isset($_GET['paged']) ? max(1, min($total_pages, intval($_GET['paged']))) : 1;
        
        // Отримуємо запити для поточної сторінки
        $offset = ($current_page - 1) * $per_page;
        $paged_patterns = array_slice($filtered_patterns, $offset, $per_page);
        
        // Виводимо шаблон
        include(IP_GET_LOGGER_PLUGIN_DIR . 'admin/partials/exclude-page.php');
    }

    /**
     * AJAX-обробник для додавання шаблону виключення
     */
    public function ajax_add_exclude_pattern() {
        // Перевіряємо nonce
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        // Перевіряємо права
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to perform this operation', 'ip-get-logger'));
            return;
        }
        
        // Отримуємо і валідуємо шаблон виключення
        $pattern = isset($_POST['pattern']) ? wp_unslash($_POST['pattern']) : '';
        
        // Базова валідація, видаляємо контрольні символи та зайві пробіли
        $pattern = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $pattern));
        
        if (empty($pattern)) {
            wp_send_json_error(__('Exclude pattern cannot be empty', 'ip-get-logger'));
            return;
        }
        
        // Перевіряємо чи шаблон вже існує
        if (in_array($pattern, $this->exclude_patterns)) {
            wp_send_json_error(__('This exclude pattern already exists', 'ip-get-logger'));
            return;
        }
        
        // Додаємо шаблон
        $this->exclude_patterns[] = $pattern;
        
        // Зберігаємо оновлені шаблони
        ip_get_logger_update_option('exclude_patterns', $this->exclude_patterns);
        
        wp_send_json_success(array(
            'message' => __('Exclude pattern added successfully', 'ip-get-logger'),
            'patterns' => $this->exclude_patterns
        ));
    }
    
    /**
     * AJAX-обробник для редагування шаблону виключення
     */
    public function ajax_edit_exclude_pattern() {
        // Перевіряємо nonce
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        // Перевіряємо права
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to perform this operation', 'ip-get-logger'));
            return;
        }
        
        // Отримуємо індекс шаблону
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        
        // Отримуємо актуальний список шаблонів з опцій
        $all_patterns = ip_get_logger_get_option('exclude_patterns', array());
        
        if ($index < 0 || !isset($all_patterns[$index])) {
            wp_send_json_error(__('Exclude pattern not found', 'ip-get-logger'));
            return;
        }
        
        // Отримуємо і валідуємо шаблон
        $pattern = isset($_POST['pattern']) ? wp_unslash($_POST['pattern']) : '';
        
        // Базова валідація, видаляємо контрольні символи та зайві пробіли
        $pattern = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $pattern));
        
        if (empty($pattern)) {
            wp_send_json_error(__('Exclude pattern cannot be empty', 'ip-get-logger'));
            return;
        }
        
        // Перевіряємо чи шаблон вже існує в іншому індексі
        foreach ($all_patterns as $i => $existing_pattern) {
            if ($i != $index && $existing_pattern === $pattern) {
                wp_send_json_error(__('This exclude pattern already exists', 'ip-get-logger'));
                return;
            }
        }
        
        // Оновлюємо шаблон
        $all_patterns[$index] = $pattern;
        
        // Зберігаємо оновлені шаблони
        ip_get_logger_update_option('exclude_patterns', $all_patterns);
        
        // Оновлюємо локальний масив шаблонів
        $this->exclude_patterns = $all_patterns;
        
        wp_send_json_success(array(
            'message' => __('Exclude pattern updated successfully', 'ip-get-logger'),
            'patterns' => $all_patterns
        ));
    }
    
    /**
     * AJAX-обробник для видалення шаблону виключення
     */
    public function ajax_delete_exclude_pattern() {
        // Перевіряємо nonce
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        // Перевіряємо права
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions to perform this operation', 'ip-get-logger'));
            return;
        }
        
        // Отримуємо індекс шаблону
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        
        // Отримуємо актуальний список шаблонів з опцій
        $all_patterns = ip_get_logger_get_option('exclude_patterns', array());
        
        if ($index < 0 || !isset($all_patterns[$index])) {
            wp_send_json_error(__('Exclude pattern not found', 'ip-get-logger'));
            return;
        }
        
        // Видаляємо шаблон
        array_splice($all_patterns, $index, 1);
        
        // Зберігаємо оновлені шаблони
        ip_get_logger_update_option('exclude_patterns', $all_patterns);
        
        // Оновлюємо локальний масив шаблонів
        $this->exclude_patterns = $all_patterns;
        
        wp_send_json_success(array(
            'message' => __('Exclude pattern deleted successfully', 'ip-get-logger'),
            'patterns' => $all_patterns
        ));
    }
    
    /**
     * AJAX-обробник для очищення всіх шаблонів виключень
     */
    public function ajax_clear_exclude_patterns() {
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'ip-get-logger'));
        }
        
        // Очищаємо масив шаблонів
        $this->exclude_patterns = array();
        
        // Зберігаємо оновлений масив
        ip_get_logger_update_option('exclude_patterns', array());
        
        wp_send_json_success(array(
            'message' => __('The list of exclude patterns is successfully cleared.', 'ip-get-logger')
        ));
    }
    
    /**
     * AJAX-обробник для оновлення шаблонів виключень з репозиторію GitHub
     */
    public function ajax_update_exclude_patterns_from_github() {
        check_ajax_referer('ip-get-logger-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'ip-get-logger'));
        }
        
        // URL файлу в GitHub
        $github_file_url = 'https://raw.githubusercontent.com/pekarskyi/ip-get-logger-db/main/ip-get-logger-exclude.txt';
        
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
            wp_send_json_error(__('No exclude patterns found in the repository.', 'ip-get-logger'));
            return;
        }
        
        // Розбиваємо зміст файлу на рядки та видаляємо порожні рядки
        $patterns = array_filter(explode("\n", $content), function($line) {
            return !empty(trim($line)) && strpos(trim($line), '#') !== 0;
        });
        
        // Видаляємо зайві пробіли та дублікати
        $patterns = array_unique(array_map('trim', $patterns));
        
        if (empty($patterns)) {
            wp_send_json_error(__('No valid exclude patterns found in the repository.', 'ip-get-logger'));
            return;
        }
        
        // Отримуємо поточні шаблони виключення
        $current_patterns = ip_get_logger_get_option('exclude_patterns', array());
        
        // Визначаємо, які шаблони є новими
        $new_patterns = array_diff($patterns, $current_patterns);
        
        // Визначаємо кількість доданих шаблонів
        $added_count = count($new_patterns);
        
        // Оновлюємо масив шаблонів виключення
        $updated_patterns = array_values(array_unique(array_merge($current_patterns, $patterns)));
        
        // Зберігаємо оновлений масив
        ip_get_logger_update_option('exclude_patterns', $updated_patterns);
        
        // Оновлюємо локальний масив шаблонів
        $this->exclude_patterns = $updated_patterns;
        
        wp_send_json_success(array(
            'message' => sprintf(
                _n(
                    'Successfully added %d new exclude pattern from the repository.',
                    'Successfully added %d new exclude patterns from the repository.',
                    $added_count,
                    'ip-get-logger'
                ),
                $added_count
            ),
            'patterns' => $updated_patterns,
            'total_patterns' => count($updated_patterns),
            'added_patterns' => $added_count
        ));
    }
} 