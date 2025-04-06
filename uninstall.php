<?php
/**
 * Файл деінсталяції плагіна IP GET Logger
 *
 * Цей файл викликається, коли користувач видаляє плагін через адміністративну панель.
 */

// Перевірка, що WordPress викликає цей файл напряму
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Отримуємо глобальний об'єкт $wpdb для роботи з базою даних
global $wpdb;

// Визначаємо назву таблиці
$table_name = $wpdb->prefix . 'ip_get_logger';

// SQL запит для видалення таблиці
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Видалення опцій, пов'язаних з плагіном, з таблиці wp_options (на випадок, якщо вони там є)
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ip_get_logger_%'");

// Для мультисайту: видалення таблиць і опцій на всіх сайтах мережі
if (is_multisite()) {
    // Отримуємо всі сайти
    $sites = get_sites();
    
    foreach ($sites as $site) {
        switch_to_blog($site->blog_id);
        
        // Видаляємо таблиці для кожного сайту
        $site_table_name = $wpdb->prefix . 'ip_get_logger';
        $wpdb->query("DROP TABLE IF EXISTS $site_table_name");
        
        // Видаляємо специфічні для сайту опції
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ip_get_logger_%'");
        
        restore_current_blog();
    }
}

// ПРИМІТКА: Папка з логами не видаляється навмисне згідно з вимогами користувача 