<div class="wrap">
    <h1><?php echo esc_html__('GET Requests Logs', 'ip-get-logger'); ?></h1>
    
    <div class="ip-get-logger-filter-container">
        <form method="get" action="">
            <input type="hidden" name="page" value="ip-get-logger-logs">
            
            <div class="ip-get-logger-filter-group">
                <label for="filter_date"><?php echo esc_html__('Date:', 'ip-get-logger'); ?></label>
                <input type="date" id="filter_date" name="filter_date" value="<?php echo esc_attr($filter_date); ?>">
            </div>
            
            <div class="ip-get-logger-filter-group">
                <label for="filter_ip"><?php echo esc_html__('IP:', 'ip-get-logger'); ?></label>
                <input type="text" id="filter_ip" name="filter_ip" value="<?php echo esc_attr($filter_ip); ?>" placeholder="<?php echo esc_attr__('Filter by IP', 'ip-get-logger'); ?>">
            </div>
            
            <div class="ip-get-logger-filter-group">
                <label for="filter_url"><?php echo esc_html__('URL:', 'ip-get-logger'); ?></label>
                <input type="text" id="filter_url" name="filter_url" value="<?php echo esc_attr($filter_url); ?>" placeholder="<?php echo esc_attr__('Filter by URL', 'ip-get-logger'); ?>">
            </div>
            
            <div class="ip-get-logger-filter-group">
                <label for="filter_status"><?php echo esc_html__('HTTP code:', 'ip-get-logger'); ?></label>
                <input type="text" id="filter_status" name="filter_status" value="<?php echo esc_attr($filter_status); ?>" placeholder="<?php echo esc_attr__('Filter by HTTP code', 'ip-get-logger'); ?>">
            </div>
            
            <div class="ip-get-logger-filter-controls">
                <button type="submit" class="button"><?php echo esc_html__('Filter', 'ip-get-logger'); ?></button>
                <a href="?page=ip-get-logger-logs" class="button"><?php echo esc_html__('Reset Filters', 'ip-get-logger'); ?></a>
            </div>
        </form>
    </div>
    
    <div class="ip-get-logger-clear-logs">
        <button id="ip-get-logger-clear-logs-btn" class="button button-secondary"><?php echo esc_html__('Clear Logs', 'ip-get-logger'); ?></button>
    </div>
    
    <div class="ip-get-logger-logs-list">
        <h2><?php echo esc_html__('Logs List', 'ip-get-logger'); ?></h2>
        
        <?php if (empty($logs)) : ?>
            <p><?php echo esc_html__('No saved logs.', 'ip-get-logger'); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('Date & Time', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('Method', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('URL', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('Pattern', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('IP', 'ip-get-logger'); ?></th>
                        <th scope="col"><?php echo esc_html__('HTTP code', 'ip-get-logger'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : 
                        $log_data = json_decode($log, true);
                        if (empty($log_data)) continue;
                    ?>
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
                                        <?php echo esc_html(substr($log_data['url'] ?? '', 0, 50) . (strlen($log_data['url'] ?? '') > 50 ? '...' : '')); ?>
                                    </a>
                                </div>
                            </td>
                            <td><?php echo esc_html($log_data['matched_pattern'] ?? ''); ?></td>
                            <td><?php echo esc_html($log_data['ip'] ?? ''); ?></td>
                            <td><?php echo esc_html($log_data['status_code'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Очищення логів
    $('#ip-get-logger-clear-logs-btn').on('click', function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to clear all logs?', 'ip-get-logger')); ?>')) {
            $.ajax({
                url: ip_get_logger_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'ip_get_logger_clear_logs',
                    nonce: ip_get_logger_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('<?php echo esc_js(__('An error occurred while clearing logs', 'ip-get-logger')); ?>');
                }
            });
        }
    });
});
</script> 