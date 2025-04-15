<div class="wrap">
    <h1><?php echo esc_html__('Patterns', 'ip-get-logger'); ?></h1>
    
    <div class="ip-get-logger-container">
        <div class="ip-get-logger-card">
            <h2><?php echo esc_html__('Add New Pattern', 'ip-get-logger'); ?></h2>
            <form id="ip-get-logger-add-form">
                <div class="form-group">
                    <label for="get-request"><?php echo esc_html__('URL or request pattern:', 'ip-get-logger'); ?></label>
                    <input type="text" id="get-request" name="get-request" class="regular-text" placeholder="<?php echo esc_attr__('Enter URL or request pattern', 'ip-get-logger'); ?>" required>
                    <p class="description"><?php echo esc_html__('HTML tags are supported, e.g., /?q=<iframe> or /?q=<script>', 'ip-get-logger'); ?></p>
                </div>
                <div class="form-group">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Add Pattern', 'ip-get-logger'); ?></button>
                </div>
            </form>

            <div style="background: #F0F0F1; padding: 10px; border-left: 4px solid #2271b1; margin: 10px 0;">
                <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('The plugin does not track or collect anonymous data about websites, users, or requests.', 'ip-get-logger'); ?><br>
                <?php echo esc_html__('If you discover new malicious GET requests, please inform me so that I can add them to the patterns database.', 'ip-get-logger'); ?></p>
                <p><span class="dashicons dashicons-shield"></span> <?php echo esc_html__('Report a new malicious pattern:', 'ip-get-logger'); ?>
                <ul>
                    <li>- <a href="https://github.com/pekarskyi/ip-get-logger/issues" target="_blank"><?php echo esc_html__('on Github - Issues - New issue', 'ip-get-logger'); ?></a></li>
                    <li>- <?php echo esc_html__('send me an email:', 'ip-get-logger'); ?> <a href="mailto:inwebpress@gmail.com">inwebpress@gmail.com</a></li>
                    <li>- <a href="https://telegram.im/@sovka7" target="_blank"><?php echo esc_html__('write to me in Telegram', 'ip-get-logger'); ?></a></li>
                </ul>
                 
            </p>
            </div>
        </div>
        
        <div class="ip-get-logger-card">
            
            <form id="ip-get-logger-import-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="import-file"><?php echo esc_html__('Import patterns from file (.txt):', 'ip-get-logger'); ?></label>
                    <input type="file" id="import-file" name="import-file" accept=".txt" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Import', 'ip-get-logger'); ?></button>
                </div>
            </form>          
            
            <hr/>

            <form id="ip-get-logger-export-form">
                <div class="form-group">
                    <label><?php echo esc_html__('Export patterns to file:', 'ip-get-logger'); ?></label>
                </div>
                <div class="form-group">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Export', 'ip-get-logger'); ?></button>
                </div>
            </form>
          
            <hr/>

            <form id="ip-get-logger-update-from-github-form">
                <div class="form-group">
                    <label><?php echo esc_html__('Update patterns from repository:', 'ip-get-logger'); ?> 
                    <span class="description">(<?php echo esc_html__('patterns', 'ip-get-logger'); ?> <?php echo is_numeric($remote_requests_count) ? $remote_requests_count : esc_html($remote_requests_count); ?>)</span></label>
                </div>
                <div class="form-group">
                    <button type="submit" class="button green"><?php echo esc_html__('Update patterns', 'ip-get-logger'); ?></button>
                </div>
            </form>
           
            <hr/>

            <form id="ip-get-logger-clear-database-form">
                <div class="form-group">
                    <label><?php echo esc_html__('Clear patterns:', 'ip-get-logger'); ?> 
                    <span class="description">(<?php echo esc_html__('patterns', 'ip-get-logger'); ?> <?php echo $total_requests_count; ?>)</span></label>
                </div>
                <div class="form-group">
                    <button type="submit" class="button red"><?php echo esc_html__('Clear Patterns', 'ip-get-logger'); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="ip-get-logger-requests-list">
        
        <?php if (empty($get_requests)) : ?>
            <p><?php echo esc_html__('No saved patterns.', 'ip-get-logger'); ?></p>
        <?php else : ?>
            <div class="tablenav top">
                <div class="flex-container">
                    <div class="alignleft actions">
                        <form method="get" class="ip-get-logger-search-form">
                            <input type="hidden" name="page" value="ip-get-logger-db">
                            <input type="hidden" name="per_page" value="<?php echo esc_attr($per_page); ?>">
                            <input type="hidden" name="paged" value="1">
                            <input type="search" name="search" id="ip-get-logger-search-input" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr__('Search patterns...', 'ip-get-logger'); ?>">
                            <input type="submit" class="button" value="<?php echo esc_attr__('Search', 'ip-get-logger'); ?>">
                        </form>
                    </div>
                    <div class="alignleft actions">
                        <form method="get" class="ip-get-logger-per-page-form">
                            <input type="hidden" name="page" value="ip-get-logger-db">
                            <input type="hidden" name="search" value="<?php echo esc_attr($search); ?>">
                            <label for="per_page"><?php echo esc_html__('Per Page:', 'ip-get-logger'); ?></label>
                            <select name="per_page" id="per_page">
                                <option value="10" <?php selected($per_page, 10); ?>>10</option>
                                <option value="20" <?php selected($per_page, 20); ?>>20</option>
                                <option value="50" <?php selected($per_page, 50); ?>>50</option>
                                <option value="100" <?php selected($per_page, 100); ?>>100</option>
                            </select>
                        </form>
                    </div>
                </div>
                
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
                                    'page' => 'ip-get-logger-db',
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
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('#', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('Pattern', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('Actions', 'ip-get-logger'); ?></th>
                    </tr>
                </thead>
                <tbody id="ip-get-logger-requests-tbody">
                    <?php 
                    // Обчислюємо зсув для номера строки
                    $page_offset = ($current_page - 1) * $per_page;
                    foreach ($paged_requests as $index => $item) : 
                        // Використовуємо оригінальний індекс з масиву
                        $original_index = $item['index'];
                        $request = $item['request'];
                    ?>
                        <tr>
                            <td><?php echo $page_offset + $index + 1; ?></td>
                            <td data-request="<?php echo esc_attr($request); ?>"><?php echo htmlspecialchars($request); ?></td>
                            <td>
                                <button class="button button-small ip-get-logger-edit-btn" data-index="<?php echo $original_index; ?>"><?php echo esc_html__('Edit', 'ip-get-logger'); ?></button>
                                <button class="button button-small ip-get-logger-delete-btn" data-index="<?php echo $original_index; ?>"><?php echo esc_html__('Delete', 'ip-get-logger'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="tablenav bottom">
                <div class="flex-container"></div>
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
                                    'page' => 'ip-get-logger-db',
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
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальне вікно для редагування запиту -->
<div id="ip-get-logger-edit-modal" class="ip-get-logger-modal" style="display: none;">
    <div class="ip-get-logger-modal-content">
        <span class="ip-get-logger-modal-close">&times;</span>
        <h3><?php echo esc_html__('Edit GET Pattern', 'ip-get-logger'); ?></h3>
        <form id="ip-get-logger-edit-form">
            <input type="hidden" id="edit-request-index" name="edit-request-index">
            <div class="form-group">
                <label for="edit-request"><?php echo esc_html__('URL or request pattern:', 'ip-get-logger'); ?></label>
                <input type="text" id="edit-request" name="edit-request" class="regular-text" required>
                <p class="description"><?php echo esc_html__('HTML tags are supported, e.g., /?q=<iframe> or /?q=<script>', 'ip-get-logger'); ?></p>
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
                params.set('page', 'ip-get-logger-db');
            }
            
            const newUrl = baseUrl + '?' + params.toString();
            
            // Перезавантажуємо сторінку
            window.location.href = newUrl;
        }
    });

    // Додавання нового запиту
    $('#ip-get-logger-add-form').on('submit', function(e) {
        e.preventDefault();
        
        const request = $('#get-request').val();
        
        $.ajax({
            url: ip_get_logger_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ip_get_logger_add_request',
                nonce: ip_get_logger_params.nonce,
                request: request
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while adding the pattern', 'ip-get-logger')); ?>');
            }
        });
    });
    
    // Видалення запиту
    $('.ip-get-logger-delete-btn').on('click', function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to delete this pattern?', 'ip-get-logger')); ?>')) {
            const index = $(this).data('index');
            
            $.ajax({
                url: ip_get_logger_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'ip_get_logger_delete_request',
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
                    alert('<?php echo esc_js(__('An error occurred while deleting the pattern', 'ip-get-logger')); ?>');
                }
            });
        }
    });
    
    // Редагування запиту - відкриття модального вікна
    $('.ip-get-logger-edit-btn').on('click', function() {
        const index = $(this).data('index');
        const request = $(this).closest('tr').find('td:nth-child(2)').data('request');
        
        $('#edit-request-index').val(index);
        $('#edit-request').val(request);
        $('#ip-get-logger-edit-modal').show();
    });
    
    // Закриття модального вікна
    $('.ip-get-logger-modal-close, .ip-get-logger-modal-cancel').on('click', function() {
        $('#ip-get-logger-edit-modal').hide();
    });
    
    // Збереження змін після редагування
    $('#ip-get-logger-edit-form').on('submit', function(e) {
        e.preventDefault();
        
        const index = $('#edit-request-index').val();
        const request = $('#edit-request').val();
        
        $.ajax({
            url: ip_get_logger_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ip_get_logger_edit_request',
                nonce: ip_get_logger_params.nonce,
                index: index,
                request: request
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while editing the pattern', 'ip-get-logger')); ?>');
            }
        });
    });
    
    // Імпорт запитів
    $('#ip-get-logger-import-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'ip_get_logger_import');
        formData.append('nonce', ip_get_logger_params.nonce);
        formData.append('import_file', $('#import-file')[0].files[0]);
        
        $.ajax({
            url: ip_get_logger_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while importing patterns', 'ip-get-logger')); ?>');
            }
        });
    });
    
    // Експорт запитів
    $('#ip-get-logger-export-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ip_get_logger_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ip_get_logger_export',
                nonce: ip_get_logger_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.export_url;
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred while exporting patterns', 'ip-get-logger')); ?>');
            }
        });
    });
    
    // Оновлення бази з GitHub
    $('#ip-get-logger-update-from-github-form').on('submit', function(e) {
        e.preventDefault();
        
        const updateButton = $(this).find('button[type="submit"]');
        const originalText = updateButton.text();
        
        // Змінюємо текст кнопки, щоб показати, що йде процес
        updateButton.text('<?php echo esc_js(__('Updating...', 'ip-get-logger')); ?>').prop('disabled', true);
        
        $.ajax({
            url: ip_get_logger_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ip_get_logger_update_from_github',
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
                alert('<?php echo esc_js(__('An error occurred while updating the list of patterns from the repository!', 'ip-get-logger')); ?>');
                // Повертаємо оригінальний текст кнопки
                updateButton.text(originalText).prop('disabled', false);
            }
        });
    });

    // Очищення бази даних
    $('#ip-get-logger-clear-database-form').on('submit', function(e) {
        e.preventDefault();
        
        if (confirm('<?php echo esc_js(__('Are you sure you want to clear the list of patterns?', 'ip-get-logger')); ?>')) {
            const clearButton = $(this).find('button[type="submit"]');
            const originalText = clearButton.text();
            
            // Змінюємо текст кнопки, щоб показати, що йде процес
            clearButton.text('<?php echo esc_js(__('Clearing...', 'ip-get-logger')); ?>').prop('disabled', true);
            
            $.ajax({
                url: ip_get_logger_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'ip_get_logger_clear_database',
                    nonce: ip_get_logger_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        window.location.reload();
                    } else {
                        alert(response.data);
                        // Повертаємо оригінальний текст кнопки
                        clearButton.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('An error occurred while clearing the list of patterns.', 'ip-get-logger')); ?>');
                    // Повертаємо оригінальний текст кнопки
                    clearButton.text(originalText).prop('disabled', false);
                }
            });
        }
    });
});
</script> 