<div class="wrap">
    <h1><?php echo esc_html__('GET Requests Database', 'ip-get-logger'); ?></h1>
    
    <div class="ip-get-logger-container">
        <div class="ip-get-logger-card">
            <h2><?php echo esc_html__('Add New Request', 'ip-get-logger'); ?></h2>
            <form id="ip-get-logger-add-form">
                <div class="form-group">
                    <label for="get-request"><?php echo esc_html__('URL or request pattern:', 'ip-get-logger'); ?></label>
                    <input type="text" id="get-request" name="get-request" class="regular-text" placeholder="<?php echo esc_attr__('Enter URL or request pattern', 'ip-get-logger'); ?>" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Add Request', 'ip-get-logger'); ?></button>
                </div>
            </form>
            <hr>

            <div style="background: #F0F0F1; padding: 10px; border-left: 4px solid #2271b1; margin: 10px 0;">
                <p><span class="dashicons dashicons-info"></span> <?php echo esc_html__('The plugin does not track or collect anonymous data about websites, users, or requests.', 'ip-get-logger'); ?><br>
                <?php echo esc_html__('If you discover new malicious GET requests, please inform me so that I can add them to the request database.', 'ip-get-logger'); ?></p>
                <p><span class="dashicons dashicons-shield"></span> <?php echo esc_html__('Report a new malicious request:', 'ip-get-logger'); ?>
                <ul>
                    <li>- <a href="https://github.com/pekarskyi/ip-get-logger/issues" target="_blank"><?php echo esc_html__('on Github - Issues - New issue', 'ip-get-logger'); ?></a></li>
                    <li>- <?php echo esc_html__('send me an email:', 'ip-get-logger'); ?> <a href="mailto:ipgetlogger@gmail.com">ipgetlogger@gmail.com</a></li>
                    <li>- <a href="https://telegram.im/@sovka7" target="_blank"><?php echo esc_html__('write to me in Telegram', 'ip-get-logger'); ?></a></li>
                </ul>
                 
            </p>
            </div>
        </div>
        
        <div class="ip-get-logger-card">
            <h2><?php echo esc_html__('Import / Export', 'ip-get-logger'); ?></h2>
            <form id="ip-get-logger-import-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="import-file"><?php echo esc_html__('Import requests from file (.txt):', 'ip-get-logger'); ?></label>
                    <input type="file" id="import-file" name="import-file" accept=".txt" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="button"><?php echo esc_html__('Import', 'ip-get-logger'); ?></button>
                </div>
            </form>
            
            <hr>
            
            <form id="ip-get-logger-export-form">
                <div class="form-group">
                    <label><?php echo esc_html__('Export requests to file:', 'ip-get-logger'); ?></label>
                </div>
                <div class="form-group">
                    <button type="submit" class="button"><?php echo esc_html__('Export', 'ip-get-logger'); ?></button>
                </div>
            </form>

            <hr>
            
            <form id="ip-get-logger-update-from-github-form">
                <div class="form-group">
                    <label><?php echo esc_html__('Update database from repository:', 'ip-get-logger'); ?></label>
                </div>
                <div class="form-group">
                    <button type="submit" class="button"><?php echo esc_html__('Update Database', 'ip-get-logger'); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="ip-get-logger-requests-list">
        <h2><?php echo esc_html__('Saved Requests List', 'ip-get-logger'); ?></h2>
        
        <?php if (empty($get_requests)) : ?>
            <p><?php echo esc_html__('No saved requests.', 'ip-get-logger'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('#', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('GET Request', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('Actions', 'ip-get-logger'); ?></th>
                    </tr>
                </thead>
                <tbody id="ip-get-logger-requests-tbody">
                    <?php foreach ($get_requests as $index => $request) : ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td data-request="<?php echo esc_attr($request); ?>"><?php echo esc_html($request); ?></td>
                            <td>
                                <button class="button button-small ip-get-logger-edit-btn" data-index="<?php echo $index; ?>"><?php echo esc_html__('Edit', 'ip-get-logger'); ?></button>
                                <button class="button button-small ip-get-logger-delete-btn" data-index="<?php echo $index; ?>"><?php echo esc_html__('Delete', 'ip-get-logger'); ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Модальне вікно для редагування запиту -->
<div id="ip-get-logger-edit-modal" class="ip-get-logger-modal" style="display: none;">
    <div class="ip-get-logger-modal-content">
        <span class="ip-get-logger-modal-close">&times;</span>
        <h3><?php echo esc_html__('Edit GET Request', 'ip-get-logger'); ?></h3>
        <form id="ip-get-logger-edit-form">
            <input type="hidden" id="edit-request-index" name="edit-request-index">
            <div class="form-group">
                <label for="edit-request"><?php echo esc_html__('URL or request pattern:', 'ip-get-logger'); ?></label>
                <input type="text" id="edit-request" name="edit-request" class="regular-text" required>
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
                alert('<?php echo esc_js(__('An error occurred while adding the request', 'ip-get-logger')); ?>');
            }
        });
    });
    
    // Видалення запиту
    $('.ip-get-logger-delete-btn').on('click', function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to delete this request?', 'ip-get-logger')); ?>')) {
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
                    alert('<?php echo esc_js(__('An error occurred while deleting the request', 'ip-get-logger')); ?>');
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
                alert('<?php echo esc_js(__('An error occurred while editing the request', 'ip-get-logger')); ?>');
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
                alert('<?php echo esc_js(__('An error occurred while importing requests', 'ip-get-logger')); ?>');
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
                alert('<?php echo esc_js(__('An error occurred while exporting requests', 'ip-get-logger')); ?>');
            }
        });
    });
    
    // Оновлення бази з GitHub
    $('#ip-get-logger-update-from-github-form').on('submit', function(e) {
        e.preventDefault();
        
        if (confirm('<?php echo esc_js(__('Do you want to update the database from repository?', 'ip-get-logger')); ?>')) {
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
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('An error occurred while updating the database from GitHub', 'ip-get-logger')); ?>');
                }
            });
        }
    });
});
</script> 