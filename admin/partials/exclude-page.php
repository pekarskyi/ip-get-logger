<div class="wrap">
    <h1><?php echo esc_html__('Exclude Patterns', 'ip-get-logger'); ?></h1>
    
    <div class="ip-get-logger-container">
        <div class="ip-get-logger-card">
            <h2><?php echo esc_html__('Add New Exclude Pattern', 'ip-get-logger'); ?></h2>
            <form id="ip-get-logger-add-exclude-form">
                <div class="form-group">
                    <label for="exclude-pattern"><?php echo esc_html__('URL pattern to exclude:', 'ip-get-logger'); ?></label>
                    <input type="text" id="exclude-pattern" name="exclude-pattern" class="regular-text" placeholder="<?php echo esc_attr__('Enter URL pattern to exclude', 'ip-get-logger'); ?>" required>
                    <p class="description"><?php echo esc_html__('Requests containing this pattern will be ignored and not logged.', 'ip-get-logger'); ?></p>
                </div>
                <div class="form-group">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Add Exclude Pattern', 'ip-get-logger'); ?></button>
                </div>
            </form>

            <div style="background: #F0F0F1; padding: 10px; border-left: 4px solid #2271b1; margin: 10px 0;">
                <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('Use exclude patterns to ignore specific URL patterns. For example:', 'ip-get-logger'); ?></p>
                <ul>
                    <li><?php echo esc_html__('/wp-admin/ - Ignore requests to admin area', 'ip-get-logger'); ?></li>
                    <li><?php echo esc_html__('/wp-json/ - Ignore REST API requests', 'ip-get-logger'); ?></li>
                    <li><?php echo esc_html__('/wp-content/ - Ignore requests to wp-content directory', 'ip-get-logger'); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="ip-get-logger-card">
            <form id="ip-get-logger-update-exclude-patterns-form">
                <div class="form-group">
                    <label><?php echo esc_html__('Update exclude patterns from repository:', 'ip-get-logger'); ?> 
                    <span class="description">(<?php echo esc_html__('patterns', 'ip-get-logger'); ?> <?php echo $remote_patterns_count; ?>)</span></label>
                </div>
                <div class="form-group">
                    <button type="submit" class="button green"><?php echo esc_html__('Update Exclude Patterns', 'ip-get-logger'); ?></button>
                </div>
            </form>
            
            <form id="ip-get-logger-clear-exclude-patterns-form">
                <div class="form-group">
                    <label><?php echo esc_html__('Clear exclude patterns:', 'ip-get-logger'); ?> 
                    <span class="description">(<?php echo esc_html__('patterns', 'ip-get-logger'); ?> <?php echo $total_patterns_count; ?>)</span></label>
                </div>
                <div class="form-group">
                    <button type="submit" class="button red"><?php echo esc_html__('Clear All Exclude Patterns', 'ip-get-logger'); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="ip-get-logger-requests-list">
        <h2><?php echo esc_html__('Saved Exclude Patterns List', 'ip-get-logger'); ?></h2>
        
        <?php if (empty($exclude_patterns)) : ?>
            <p><?php echo esc_html__('No saved exclude patterns.', 'ip-get-logger'); ?></p>
        <?php else : ?>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get" class="ip-get-logger-search-form">
                        <input type="hidden" name="page" value="ip-get-logger-exclude">
                        <input type="hidden" name="per_page" value="<?php echo esc_attr($per_page); ?>">
                        <input type="hidden" name="paged" value="1">
                        <input type="search" name="search" id="ip-get-logger-search-input" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Search patterns...', 'ip-get-logger'); ?>">
                        <input type="submit" class="button" value="<?php echo esc_attr__('Search', 'ip-get-logger'); ?>">
                    </form>
                </div>
                <div class="alignleft actions">
                    <form method="get" class="ip-get-logger-per-page-form">
                        <input type="hidden" name="page" value="ip-get-logger-exclude">
                        <input type="hidden" name="search" value="<?php echo esc_attr($search); ?>">
                        <label><?php echo esc_html__('Items per page:', 'ip-get-logger'); ?></label>
                        <select name="per_page" id="per_page">
                            <option value="10" <?php selected($per_page, 10); ?>>10</option>
                            <option value="20" <?php selected($per_page, 20); ?>>20</option>
                            <option value="50" <?php selected($per_page, 50); ?>>50</option>
                            <option value="100" <?php selected($per_page, 100); ?>>100</option>
                        </select>
                    </form>
                </div>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo sprintf(_n('%s item', '%s items', $total_items, 'ip-get-logger'), number_format_i18n($total_items)); ?></span>
                    <span class="pagination-links">
                        <?php
                        // Генеруємо URL із збереженням параметрів фільтрів
                        $base_url = add_query_arg(
                            array(
                                'page' => 'ip-get-logger-exclude',
                                'search' => $search,
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
                        <th scope="col"><?php echo esc_html__('#', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('Exclude Pattern', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('Actions', 'ip-get-logger'); ?></th>
                    </tr>
                </thead>
                <tbody id="ip-get-logger-exclude-patterns-tbody">
                    <?php 
                    // Обчислюємо зсув для номера строки
                    $page_offset = ($current_page - 1) * $per_page;
                    foreach ($paged_patterns as $index => $item) : 
                        // Використовуємо оригінальний індекс з масиву
                        $original_index = $item['index'];
                        $pattern = $item['pattern'];
                    ?>
                        <tr>
                            <td><?php echo $page_offset + $index + 1; ?></td>
                            <td data-pattern="<?php echo esc_attr($pattern); ?>"><?php echo htmlspecialchars($pattern); ?></td>
                            <td>
                                <button class="button button-small ip-get-logger-edit-exclude-btn" data-index="<?php echo $original_index; ?>"><?php echo esc_html__('Edit', 'ip-get-logger'); ?></button>
                                <button class="button button-small ip-get-logger-delete-exclude-btn" data-index="<?php echo $original_index; ?>"><?php echo esc_html__('Delete', 'ip-get-logger'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo sprintf(_n('%s item', '%s items', $total_items, 'ip-get-logger'), number_format_i18n($total_items)); ?></span>
                    <span class="pagination-links">
                        <?php
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

<!-- Модальне вікно для редагування шаблону виключення -->
<div id="ip-get-logger-edit-exclude-modal" class="ip-get-logger-modal" style="display: none;">
    <div class="ip-get-logger-modal-content">
        <span class="ip-get-logger-modal-close">&times;</span>
        <h3><?php echo esc_html__('Edit Exclude Pattern', 'ip-get-logger'); ?></h3>
        <form id="ip-get-logger-edit-exclude-form">
            <input type="hidden" id="edit-exclude-pattern-index" name="edit-exclude-pattern-index">
            <div class="form-group">
                <label for="edit-exclude-pattern"><?php echo esc_html__('URL pattern to exclude:', 'ip-get-logger'); ?></label>
                <input type="text" id="edit-exclude-pattern" name="edit-exclude-pattern" class="regular-text" required>
                <p class="description"><?php echo esc_html__('Requests containing this pattern will be ignored and not logged.', 'ip-get-logger'); ?></p>
            </div>
            <div class="form-group">
                <button type="submit" class="button button-primary"><?php echo esc_html__('Save', 'ip-get-logger'); ?></button>
                <button type="button" class="button ip-get-logger-modal-cancel"><?php echo esc_html__('Cancel', 'ip-get-logger'); ?></button>
            </div>
        </form>
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

    // Перезавантаження сторінки при очищенні поля пошуку
    $('#ip-get-logger-search-input').on('input', function() {
        if ($(this).val() === '') {
            const currentUrl = window.location.href;
            const baseUrl = currentUrl.split('?')[0];
            const params = new URLSearchParams(window.location.search);
            
            // Видаляємо параметр пошуку
            params.delete('search');
            
            // Зберігаємо інші параметри і встановлюємо правильний slug сторінки
            if (!params.has('page')) {
                params.set('page', 'ip-get-logger-exclude');
            }
            
            const newUrl = baseUrl + '?' + params.toString();
            
            // Перезавантажуємо сторінку
            window.location.href = newUrl;
        }
    });

    // Додавання нового шаблону виключення
    $('#ip-get-logger-add-exclude-form').on('submit', function(e) {
        e.preventDefault();
        
        const pattern = $('#exclude-pattern').val();
        
        $.ajax({
            url: ip_get_logger_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ip_get_logger_add_exclude_pattern',
                nonce: ip_get_logger_params.nonce,
                pattern: pattern
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while adding the exclude pattern', 'ip-get-logger')); ?>');
            }
        });
    });
    
    // Видалення шаблону виключення
    $('.ip-get-logger-delete-exclude-btn').on('click', function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to delete this exclude pattern?', 'ip-get-logger')); ?>')) {
            const index = $(this).data('index');
            
            $.ajax({
                url: ip_get_logger_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'ip_get_logger_delete_exclude_pattern',
                    nonce: ip_get_logger_params.nonce,
                    index: index
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('An error occurred while deleting the exclude pattern', 'ip-get-logger')); ?>');
                }
            });
        }
    });
    
    // Редагування шаблону виключення (відкриття модального вікна)
    $('.ip-get-logger-edit-exclude-btn').on('click', function() {
        const index = $(this).data('index');
        const pattern = $(this).closest('tr').find('td:nth-child(2)').data('pattern');
        
        $('#edit-exclude-pattern-index').val(index);
        $('#edit-exclude-pattern').val(pattern);
        
        $('#ip-get-logger-edit-exclude-modal').show();
    });
    
    // Закриття модального вікна
    $('.ip-get-logger-modal-close, .ip-get-logger-modal-cancel').on('click', function() {
        $('.ip-get-logger-modal').hide();
    });
    
    // Збереження змін
    $('#ip-get-logger-edit-exclude-form').on('submit', function(e) {
        e.preventDefault();
        
        const index = $('#edit-exclude-pattern-index').val();
        const pattern = $('#edit-exclude-pattern').val();
        
        $.ajax({
            url: ip_get_logger_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ip_get_logger_edit_exclude_pattern',
                nonce: ip_get_logger_params.nonce,
                index: index,
                pattern: pattern
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while updating the exclude pattern', 'ip-get-logger')); ?>');
            }
        });
    });
    
    // Очищення всіх шаблонів виключень
    $('#ip-get-logger-clear-exclude-patterns-form').on('submit', function(e) {
        e.preventDefault();
        
        if (confirm('<?php echo esc_js(__('Are you sure you want to clear all exclude patterns?', 'ip-get-logger')); ?>')) {
            const clearButton = $(this).find('button[type="submit"]');
            const originalText = clearButton.text();
            
            // Змінюємо текст кнопки, щоб показати, що йде процес
            clearButton.text('<?php echo esc_js(__('Clearing...', 'ip-get-logger')); ?>').prop('disabled', true);
            
            $.ajax({
                url: ip_get_logger_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'ip_get_logger_clear_exclude_patterns',
                    nonce: ip_get_logger_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data);
                        // Повертаємо оригінальний текст кнопки
                        clearButton.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('An error occurred while clearing exclude patterns', 'ip-get-logger')); ?>');
                    // Повертаємо оригінальний текст кнопки
                    clearButton.text(originalText).prop('disabled', false);
                }
            });
        }
    });
    
    // Оновлення шаблонів виключень з репозиторію GitHub
    $('#ip-get-logger-update-exclude-patterns-form').on('submit', function(e) {
        e.preventDefault();
        
        const updateButton = $(this).find('button[type="submit"]');
        const originalText = updateButton.text();
        
        // Змінюємо текст кнопки, щоб показати, що йде процес
        updateButton.text('<?php echo esc_js(__('Updating...', 'ip-get-logger')); ?>').prop('disabled', true);
        
        $.ajax({
            url: ip_get_logger_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ip_get_logger_update_exclude_patterns_from_github',
                nonce: ip_get_logger_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    alert(response.data);
                    // Повертаємо оригінальний текст кнопки
                    updateButton.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while updating exclude patterns', 'ip-get-logger')); ?>');
                // Повертаємо оригінальний текст кнопки
                updateButton.text(originalText).prop('disabled', false);
            }
        });
    });
});
</script> 