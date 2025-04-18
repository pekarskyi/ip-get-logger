<div class="wrap">
    <h1><?php echo esc_html__('GET Requests Logs', 'ip-get-logger'); ?></h1>
    
    <div class="ip-get-logger-filter-container">
        <form method="get" action="<?php echo admin_url('admin.php'); ?>">
            <input type="hidden" name="page" value="ip-get-logger">
            
            <div class="ip-get-logger-filter-group">
                <label for="filter_date"><?php echo esc_html__('Date:', 'ip-get-logger'); ?></label>
                <input type="date" id="filter_date" name="filter_date" value="<?php echo esc_attr($filter_date); ?>">
            </div>
            
            <div class="ip-get-logger-filter-group">
                <label for="filter_ip"><?php echo esc_html__('IP:', 'ip-get-logger'); ?></label>
                <input type="text" id="filter_ip" name="filter_ip" value="<?php echo esc_attr($filter_ip); ?>" placeholder="<?php echo esc_attr__('Filter by IP', 'ip-get-logger'); ?>">
            </div>
            
            <div class="ip-get-logger-filter-group">
                <label for="filter_country"><?php echo esc_html__('Country:', 'ip-get-logger'); ?></label>
                <input type="text" id="filter_country" name="filter_country" value="<?php echo esc_attr($filter_country); ?>" placeholder="<?php echo esc_attr__('Filter by country', 'ip-get-logger'); ?>">
            </div>
            
            <div class="ip-get-logger-filter-group">
                <label for="filter_url"><?php echo esc_html__('URL:', 'ip-get-logger'); ?></label>
                <input type="text" id="filter_url" name="filter_url" value="<?php echo esc_attr($filter_url); ?>" placeholder="<?php echo esc_attr__('Filter by URL', 'ip-get-logger'); ?>">
            </div>
            
            <div class="ip-get-logger-filter-group">
                <label for="filter_user_agent"><?php echo esc_html__('User Agent:', 'ip-get-logger'); ?></label>
                <input type="text" id="filter_user_agent" name="filter_user_agent" value="<?php echo esc_attr($filter_user_agent); ?>" placeholder="<?php echo esc_attr__('Filter by User Agent', 'ip-get-logger'); ?>">
            </div>
            
            <div class="ip-get-logger-filter-group">
                <label for="per_page"><?php echo esc_html__('Per Page:', 'ip-get-logger'); ?></label>
                <select id="per_page" name="per_page">
                    <option value="10" <?php selected($per_page, 10); ?>>10</option>
                    <option value="20" <?php selected($per_page, 20); ?>>20</option>
                    <option value="50" <?php selected($per_page, 50); ?>>50</option>
                    <option value="100" <?php selected($per_page, 100); ?>>100</option>
                </select>
            </div>
            
            <div class="ip-get-logger-filter-controls">
                <button type="submit" class="button button-primary"><?php echo esc_html__('Filter', 'ip-get-logger'); ?></button>
                <a href="<?php echo admin_url('admin.php?page=ip-get-logger'); ?>" class="button button-primary"><?php echo esc_html__('Reset Filters', 'ip-get-logger'); ?></a>
                <button type="button" id="ip-get-logger-export-logs-btn" class="button green"><?php echo esc_html__('Export Logs', 'ip-get-logger'); ?></button>
                <button type="button" id="ip-get-logger-clear-logs-btn" class="button red"><?php echo esc_html__('Clear Logs', 'ip-get-logger'); ?></button>
            </div>
        </form>
    </div>
    
    <div class="ip-get-logger-logs-list">
        
        <?php if (empty($processed_logs)) : ?>
            <p><?php echo esc_html__('No saved logs.', 'ip-get-logger'); ?></p>
        <?php else : ?>
            <div class="ip-get-logger-pagination">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php echo sprintf(
                            _n('%s item', '%s items', $total_items, 'ip-get-logger'), 
                            number_format_i18n($total_items)
                        ); ?>
                    </span>
                    
                    <span class="pagination-links">
                        <?php
                        // Генеруємо URL із збереженням параметрів фільтрів
                        $base_url = add_query_arg(
                            array(
                                'page' => 'ip-get-logger',
                                'filter_date' => $filter_date,
                                'filter_ip' => $filter_ip,
                                'filter_country' => $filter_country,
                                'filter_url' => $filter_url,
                                'filter_user_agent' => $filter_user_agent,
                                'per_page' => $per_page
                            ),
                            admin_url('admin.php')
                        );
                        
                        // Першу сторінку
                        if ($current_page > 1) {
                            echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1, $base_url)) . '"><span class="screen-reader-text">' . __('First page', 'ip-get-logger') . '</span><span aria-hidden="true">&laquo;</span></a>';
                        } else {
                            echo '<span class="first-page button disabled" aria-hidden="true">&laquo;</span>';
                        }
                        
                        // Попередню сторінку
                        if ($current_page > 1) {
                            echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1, $base_url)) . '"><span class="screen-reader-text">' . __('Previous page', 'ip-get-logger') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                        } else {
                            echo '<span class="prev-page button disabled" aria-hidden="true">&lsaquo;</span>';
                        }
                        
                        // Поточна/загальна сторінки
                        echo '<span class="paging-input">' . $current_page . ' / ' . $total_pages . '</span>';
                        
                        // Наступну сторінку
                        if ($current_page < $total_pages) {
                            echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1, $base_url)) . '"><span class="screen-reader-text">' . __('Next page', 'ip-get-logger') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                        } else {
                            echo '<span class="next-page button disabled" aria-hidden="true">&rsaquo;</span>';
                        }
                        
                        // Останню сторінку
                        if ($current_page < $total_pages) {
                            echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages, $base_url)) . '"><span class="screen-reader-text">' . __('Last page', 'ip-get-logger') . '</span><span aria-hidden="true">&raquo;</span></a>';
                        } else {
                            echo '<span class="last-page button disabled" aria-hidden="true">&raquo;</span>';
                        }
                        ?>
                    </span>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('Date & Time', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('Method', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('URL', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('Pattern', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('IP', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('Country', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('User Agent', 'ip-get-logger'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($processed_logs as $log_data) : ?>
                        <tr>
                            <td>
                                <?php 
                                    if (!empty($log_data['timestamp'])) {
                                        $timestamp = strtotime($log_data['timestamp']);
                                        echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp));
                                    } else {
                                        echo '-';
                                    }
                                ?>
                            </td>
                            <td><?php echo esc_html($log_data['method'] ?? ''); ?></td>
                            <td>
                                <div class="ip-get-logger-url-container">
                                    <a href="<?php echo esc_url($log_data['url'] ?? ''); ?>" target="_blank" title="<?php echo esc_attr($log_data['url'] ?? ''); ?>">
                                        <?php echo esc_html(substr($log_data['url'] ?? '', 0, 70) . (strlen($log_data['url'] ?? '') > 70 ? '...' : '')); ?>
                                    </a>
                                </div>
                            </td>
                            <td><?php echo esc_html($log_data['matched_pattern'] ?? ''); ?></td>
                            <td><?php echo esc_html($log_data['ip'] ?? ''); ?></td>
                            <td><?php echo esc_html($log_data['country'] ?? ''); ?></td>
                            <td><?php echo esc_html($log_data['user_agent'] ?? 'Unknown'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="ip-get-logger-pagination">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php echo sprintf(
                            _n('%s item', '%s items', $total_items, 'ip-get-logger'), 
                            number_format_i18n($total_items)
                        ); ?>
                    </span>
                    
                    <span class="pagination-links">
                        <?php
                        // Генеруємо URL із збереженням параметрів фільтрів
                        $base_url = add_query_arg(
                            array(
                                'page' => 'ip-get-logger',
                                'filter_date' => $filter_date,
                                'filter_ip' => $filter_ip,
                                'filter_country' => $filter_country,
                                'filter_url' => $filter_url,
                                'filter_user_agent' => $filter_user_agent,
                                'per_page' => $per_page
                            ),
                            admin_url('admin.php')
                        );
                        
                        // Першу сторінку
                        if ($current_page > 1) {
                            echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1, $base_url)) . '"><span class="screen-reader-text">' . __('First page', 'ip-get-logger') . '</span><span aria-hidden="true">&laquo;</span></a>';
                        } else {
                            echo '<span class="first-page button disabled" aria-hidden="true">&laquo;</span>';
                        }
                        
                        // Попередню сторінку
                        if ($current_page > 1) {
                            echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1, $base_url)) . '"><span class="screen-reader-text">' . __('Previous page', 'ip-get-logger') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                        } else {
                            echo '<span class="prev-page button disabled" aria-hidden="true">&lsaquo;</span>';
                        }
                        
                        // Поточна/загальна сторінки
                        echo '<span class="paging-input">' . $current_page . ' / ' . $total_pages . '</span>';
                        
                        // Наступну сторінку
                        if ($current_page < $total_pages) {
                            echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1, $base_url)) . '"><span class="screen-reader-text">' . __('Next page', 'ip-get-logger') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                        } else {
                            echo '<span class="next-page button disabled" aria-hidden="true">&rsaquo;</span>';
                        }
                        
                        // Останню сторінку
                        if ($current_page < $total_pages) {
                            echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages, $base_url)) . '"><span class="screen-reader-text">' . __('Last page', 'ip-get-logger') . '</span><span aria-hidden="true">&raquo;</span></a>';
                        } else {
                            echo '<span class="last-page button disabled" aria-hidden="true">&raquo;</span>';
                        }
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Автоматичне оновлення сторінки при зміні кількості записів на сторінці
    $('#per_page').on('change', function() {
        // Отримуємо поточний URL
        var currentUrl = window.location.href;
        
        // Створюємо об'єкт для аналізу URL
        var urlObj = new URL(currentUrl);
        
        // Оновлюємо параметр per_page
        urlObj.searchParams.set('per_page', $(this).val());
        
        // Видаляємо параметр paged, щоб повернутися на першу сторінку
        urlObj.searchParams.delete('paged');
        
        // Переходимо на новий URL
        window.location.href = urlObj.toString();
    });

    // Очищення логів
    $('#ip-get-logger-clear-logs-btn').on('click', function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to clear all logs?', 'ip-get-logger')); ?>')) {
            var $button = $(this);
            var originalText = $button.text();
            
            // Змінюємо текст кнопки і додаємо атрибут disabled
            $button.text('<?php echo esc_js(__('Clearing...', 'ip-get-logger')); ?>').prop('disabled', true);
            
            $.ajax({
                url: ip_get_logger_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'ip_get_logger_clear_logs',
                    nonce: ip_get_logger_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Додаємо випадковий параметр timestamp для запобігання кешування
                        window.location.href = '<?php echo admin_url('admin.php?page=ip-get-logger'); ?>&nocache=' + new Date().getTime();
                    } else {
                        // Повертаємо початковий стан кнопки
                        $button.text(originalText).prop('disabled', false);
                        alert(response.data);
                    }
                },
                error: function() {
                    // Повертаємо початковий стан кнопки
                    $button.text(originalText).prop('disabled', false);
                    alert('<?php echo esc_js(__('An error occurred while clearing logs', 'ip-get-logger')); ?>');
                }
            });
        }
    });

    // Експорт логів
    $('#ip-get-logger-export-logs-btn').on('click', function() {
        $('#ip-get-logger-export-modal').show();
    });

    // Закриття модального вікна
    $('.ip-get-logger-modal-close').on('click', function() {
        $('.ip-get-logger-modal').hide();
    });

    // Закриття модального вікна при кліку поза ним
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('ip-get-logger-modal')) {
            $('.ip-get-logger-modal').hide();
        }
    });

    // Обробка експорту при виборі формату
    $('#ip-get-logger-export-form').on('submit', function(e) {
        e.preventDefault();
        
        var format = $('#export_format').val();
        
        $.ajax({
            url: ip_get_logger_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ip_get_logger_export_logs',
                nonce: ip_get_logger_params.nonce,
                format: format,
                filter_date: '<?php echo esc_js($filter_date); ?>',
                filter_ip: '<?php echo esc_js($filter_ip); ?>',
                filter_country: '<?php echo esc_js($filter_country); ?>',
                filter_url: '<?php echo esc_js($filter_url); ?>',
                filter_user_agent: '<?php echo esc_js($filter_user_agent); ?>'
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.export_url;
                    // Приховуємо модальне вікно після успішного експорту
                    $('#ip-get-logger-export-modal').hide();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while exporting logs', 'ip-get-logger')); ?>');
            }
        });
    });
});
</script>

<!-- Модальне вікно для вибору формату експорту -->
<div id="ip-get-logger-export-modal" class="ip-get-logger-modal">
    <div class="ip-get-logger-modal-content">
        <span class="ip-get-logger-modal-close">&times;</span>
        <h2><?php echo esc_html__('Export Logs', 'ip-get-logger'); ?></h2>
        <form id="ip-get-logger-export-form" method="post">
            <p>
                <label for="export_format"><?php echo esc_html__('Select Export Format:', 'ip-get-logger'); ?></label>
                <select id="export_format" name="export_format">
                    <option value="excel" selected><?php echo esc_html__('Excel (XLS)', 'ip-get-logger'); ?></option>
                    <option value="html"><?php echo esc_html__('HTML', 'ip-get-logger'); ?></option>
                </select>
            </p>
            <p>
                <button type="submit" class="button button-primary"><?php echo esc_html__('Export', 'ip-get-logger'); ?></button>
            </p>
        </form>
    </div>
</div> 