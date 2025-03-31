<div class="wrap">
    <h1><?php echo esc_html__('IP GET Logger Settings', 'ip-get-logger'); ?></h1>
    
    <?php settings_errors('ip_get_logger_settings'); ?>
    
    <form method="post" action="">
        <?php
        settings_fields('ip_get_logger_settings');
        do_settings_sections('ip-get-logger-settings');
        submit_button(__('Save Settings', 'ip-get-logger'));
        ?>
    </form>
    
    <div class="ip-get-logger-info-section">
        <h2><?php echo esc_html__('Database Information', 'ip-get-logger'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php echo esc_html__('Database Table', 'ip-get-logger'); ?></th>
                <td>
                    <?php 
                    global $wpdb;
                    echo '<code>' . $wpdb->prefix . IP_GET_LOGGER_TABLE . '</code>';
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Number of patterns in Database', 'ip-get-logger'); ?></th>
                <td>
                    <?php echo count($this->get_requests); ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="ip-get-logger-info-section">
        <h2><?php echo esc_html__('Plugin Information', 'ip-get-logger'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php echo esc_html__('Version', 'ip-get-logger'); ?></th>
                <td>
                    <code><?php echo esc_html(IP_GET_LOGGER_VERSION); ?></code>
                </td>
            </tr>
        </table>
    </div>
</div> 