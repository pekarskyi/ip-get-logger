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

## Example: Watch for suspicious parameters

Add a pattern: `eval=`

This will track requests that might be attempting code injection.

## Changelog

1.0.0 - 30.03.2025
- Initial release