# IP GET Logger

A WordPress plugin for tracking, logging, and notifying about specific GET requests to your site.

## Description

IP GET Logger is a powerful WordPress plugin designed to help website administrators monitor and track specific GET requests made to their websites. The plugin allows you to define GET request patterns to watch for, log details of matched requests, and optionally receive email notifications when matches are found.

## Features

- **GET Request Tracking**: Define specific GET request patterns to monitor
- **Email Notifications**: Receive instant notifications when matching requests are detected
- **Logging System**: Keep detailed logs of all matched requests
- **Import/Export**: Easily import or export your GET request patterns
- **Database Storage**: All settings and requests are stored in a dedicated database table
- **User-friendly Admin Interface**: Intuitive settings page for easy configuration
- **Security**: Built with WordPress security best practices

## Installation

1. Upload the `ip-get-logger` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to the 'IP GET Logger' settings page to configure the plugin

## Configuration

### General Settings

- **Enable Logging**: Toggle logging functionality on/off
- **Email Notifications**: Enable/disable email notifications for matched requests
- **Notification Email**: Set the email address to receive notifications
- **Email Subject**: Customize the subject line for notification emails

### GET Request Patterns

Add GET request patterns to track in the format:
- `param1=value1&param2=value2`

The plugin will match these patterns against incoming GET requests.

### Database Options

- Option to delete the plugin's database table when uninstalling

## Usage Examples

### Example 1: Track login attempts with specific parameters

Add a pattern: `action=login&username=admin`

This will track any GET request that attempts to login as admin through a URL.

### Example 2: Monitor access to specific files

Add a pattern: `file=../config.php`

This will track potential directory traversal attempts.

### Example 3: Watch for suspicious parameters

Add a pattern: `eval=`

This will track requests that might be attempting code injection.

## Frequently Asked Questions

**Q: Can I use wildcards in GET request patterns?**
A: Currently, the plugin matches exact patterns. Partial matching may be added in future versions.

**Q: Will this plugin slow down my website?**
A: No, the plugin is designed to be lightweight and only processes GET requests without affecting page loading times.

**Q: How do I check the logs?**
A: Logs are stored in the specified log file path and can be viewed from the plugin's admin interface.

## Changelog

1.0.0 - 30.03.2025
- Initial release