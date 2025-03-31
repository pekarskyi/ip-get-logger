<div class="wrap">
    <h1><?php echo esc_html__('URL Matching Test', 'ip-get-logger'); ?></h1>
    
    <div class="ip-get-logger-test-url-form">
        <form method="post" action="">
            <?php wp_nonce_field('ip_get_logger_test_url', 'test_url_nonce'); ?>
            
            <div class="ip-get-logger-form-group">
                <label for="test_url"><?php echo esc_html__('Enter URL to test:', 'ip-get-logger'); ?></label>
                <input type="text" id="test_url" name="test_url" class="regular-text" 
                    value="<?php echo isset($_POST['test_url']) ? esc_attr($_POST['test_url']) : ''; ?>" 
                    placeholder="<?php echo esc_attr__('https://example.com/ or just example.com', 'ip-get-logger'); ?>" required>
            </div>
            
            <div class="ip-get-logger-form-group">
                <label for="test_html_tag" class="checkbox-label">
                    <input type="checkbox" id="test_html_tag" name="test_html_tag" 
                        <?php checked(isset($_POST['test_html_tag'])); ?>>
                    <?php echo esc_html__('Test with HTML tags (will add ?q=<iframe> to URL)', 'ip-get-logger'); ?>
                </label>
            </div>
            
            <div class="ip-get-logger-form-group">
                <button type="submit" class="button button-primary"><?php echo esc_html__('Test URL', 'ip-get-logger'); ?></button>
            </div>
        </form>
    </div>
    
    <?php if (isset($test_results)) : ?>
        <div class="ip-get-logger-test-results">
            <h2><?php echo esc_html__('Test Results', 'ip-get-logger'); ?></h2>
            
            <div class="ip-get-logger-result-section">
                <h3><?php echo esc_html__('Tested URL', 'ip-get-logger'); ?></h3>
                <code><?php echo esc_html($test_results['test_url']); ?></code>
            </div>
            
            <div class="ip-get-logger-result-section">
                <h3><?php echo esc_html__('Match Found', 'ip-get-logger'); ?></h3>
                <?php if ($test_results['match_found']) : ?>
                    <div class="ip-get-logger-match-success">
                        <span class="dashicons dashicons-yes"></span> <?php echo esc_html__('Yes, URL matched one or more patterns', 'ip-get-logger'); ?>
                    </div>
                <?php else : ?>
                    <div class="ip-get-logger-match-failure">
                        <span class="dashicons dashicons-no"></span> <?php echo esc_html__('No matches found', 'ip-get-logger'); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($test_results['matches'])) : ?>
                <div class="ip-get-logger-result-section">
                    <h3><?php echo esc_html__('Matching Patterns', 'ip-get-logger'); ?></h3>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('URL Variant', 'ip-get-logger'); ?></th>
                                <th><?php echo esc_html__('Matched Pattern', 'ip-get-logger'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($test_results['matches'] as $url => $pattern) : ?>
                                <tr>
                                    <td><code><?php echo esc_html($url); ?></code></td>
                                    <td><code><?php echo esc_html($pattern); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($test_results['html_tag_patterns'])) : ?>
                <div class="ip-get-logger-result-section">
                    <h3><?php echo esc_html__('Patterns with HTML Tags', 'ip-get-logger'); ?></h3>
                    <p><?php echo esc_html__('These patterns in your database contain HTML tags:', 'ip-get-logger'); ?></p>
                    <ul class="ip-get-logger-html-patterns">
                        <?php foreach ($test_results['html_tag_patterns'] as $pattern) : ?>
                            <li><code><?php echo esc_html($pattern); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="ip-get-logger-result-section">
                <h3><?php echo esc_html__('URLs Checked', 'ip-get-logger'); ?></h3>
                <div class="ip-get-logger-urls-checked">
                    <ul>
                        <?php foreach ($test_results['urls_checked'] as $url) : ?>
                            <li>
                                <code><?php echo esc_html($url); ?></code>
                                <?php if (isset($test_results['matches'][$url])) : ?>
                                    <span class="dashicons dashicons-yes" title="<?php echo esc_attr__('Matched', 'ip-get-logger'); ?>"></span>
                                <?php else : ?>
                                    <span class="dashicons dashicons-no" title="<?php echo esc_attr__('Not matched', 'ip-get-logger'); ?>"></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="ip-get-logger-help-section">
        <h2><?php echo esc_html__('Help', 'ip-get-logger'); ?></h2>
        <div class="ip-get-logger-help-content">
            <p><?php echo esc_html__('This tool allows you to test if a URL would be matched by any of your configured patterns.', 'ip-get-logger'); ?></p>
            <p><?php echo esc_html__('When you enter a URL, the system will:', 'ip-get-logger'); ?></p>
            <ol>
                <li><?php echo esc_html__('Break it down into different variants (full URL, path, filename, etc.)', 'ip-get-logger'); ?></li>
                <li><?php echo esc_html__('Check each variant against all your patterns', 'ip-get-logger'); ?></li>
                <li><?php echo esc_html__('Show you which patterns matched and which variants were checked', 'ip-get-logger'); ?></li>
            </ol>
            <p><?php echo esc_html__('This can help you troubleshoot why certain URLs are not being detected or logged.', 'ip-get-logger'); ?></p>
        </div>
    </div>
</div>

<style>
.ip-get-logger-form-group {
    margin-bottom: 15px;
}
.ip-get-logger-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
.checkbox-label {
    font-weight: normal !important;
    display: flex !important;
    align-items: center;
    gap: 5px;
}
.ip-get-logger-test-results {
    margin-top: 30px;
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 3px;
}
.ip-get-logger-result-section {
    margin-bottom: 20px;
}
.ip-get-logger-match-success {
    color: green;
    font-weight: bold;
}
.ip-get-logger-match-failure {
    color: red;
    font-weight: bold;
}
.ip-get-logger-urls-checked ul,
.ip-get-logger-html-patterns,
.ip-get-logger-patterns-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.ip-get-logger-urls-checked li,
.ip-get-logger-patterns-list li {
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.ip-get-logger-help-section {
    margin-top: 30px;
}
.ip-get-logger-html-tag-icon {
    color: #0073aa;
}
.ip-get-logger-add-html-pattern {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
#add-html-pattern-result.success {
    color: green;
    font-weight: bold;
}
#add-html-pattern-result.error {
    color: red;
    font-weight: bold;
}
</style> 