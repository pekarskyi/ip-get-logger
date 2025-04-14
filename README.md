![https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_header.jpg](https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_header.jpg)

# IP GET Logger

WordPress plugin for monitoring, logging, and alerting suspicious GET requests on your website. It acts as an additional security layer, helping you detect potential threats at an early stage.

[Читати опис українською мовою](https://github.com/pekarskyi/ip-get-logger/blob/master/README_UA.md) <img src="https://github.com/pekarskyi/assets/raw/master/flags/ua.svg" width="17">

[![GitHub release (latest by date)](https://img.shields.io/github/v/release/pekarskyi/ip-get-logger?style=for-the-badge)](https://GitHub.com/pekarskyi/ip-get-logger/releases/)

### How It Works  
Attackers may use specific GET requests to search for vulnerabilities in your system. The plugin automatically:  
- Monitors such requests  
- Logs them for review  
- Alerts the administrator about potential threats  

### Benefits  
- Monitoring of suspicious activity  
- Rapid detection of threats (SQL injections, XSS, LFI/RFI/RCE, CSRF attacks, etc.)  
- Quick response capabilities  

With this plugin, you can enhance your website's security and proactively prevent potential attacks.

## Description

IP GET Logger is a powerful WordPress plugin designed to help website administrators monitor and track specific GET requests made to their websites. The plugin allows you to define GET request patterns to watch for, log details of matched requests, and optionally receive email notifications when matches are found.

## Features

### Main features
- **GET Request Tracking**: Define specific GET request patterns to monitor
- **Logging System**: Keep detailed logs of all matched requests
- **Email Notifications**: Receive instant notifications when matching requests are detected
- **Email Throttling**: Limit notification frequency to prevent inbox flooding
- **Persistent Logs**: Logs are stored in the `/wp-content/ip-get-logger-logs/` directory and preserved during plugin updates
- **Device Detection**: Identify and log the device type (Desktop, Mobile, Tablet, Bot)
- **Geolocation**: Determine and log the country from which requests originate

### Patterns Management
- **Import/Export**: Easily import or export your GET request patterns
- **Patterns Management**: Search, filter, and paginate through your patterns collection
- **Update Patterns**: Update patterns from the global database repository
- **Clear Patterns**: Easily clear all patterns with one click

### Exclude Patterns
- **Exclude Patterns**: Define URL patterns that should be ignored by the logger
- **Auto-update Exclude Patterns**: Update exclusion patterns from the remote repository

### Other features
- **User-friendly Admin Interface**: Intuitive settings page for easy configuration
- **Test URL**: Test your URL against defined patterns
- **Plugin update system**: Stay updated with the latest version

## Screenshots

Logs
![https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_log.jpg](https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_log.jpg)

Patterns
![https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_db.jpg](https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_db.jpg)

Exclude Patterns
![https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_exclude.jpg](https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_exclude.jpg)

Settings
![https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_sett.jpg](https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_sett.jpg)

URL Matching Test
![https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_test-url.jpg](https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_test-url.jpg)

Email Notifications
![https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_email.jpg](https://github.com/pekarskyi/assets/raw/master/ip-get-logger/ip-get-logger_email.jpg)

## Installation

### Option 1:

1. Download the `IP GET Logger` plugin (green Code button - Download ZIP). Unpack it.
2. Upload the `ip-get-logger` folder to the `/wp-content/plugins/` directory. Make sure the plugin folder is named `ip-get-logger` (the name doesn't affect how the plugin works, but it does affect receiving future updates).
3. Activate the plugin. 

### Option 2 (recommended):

1. Install and activate this plugin (plugin installer): https://github.com/pekarskyi/ip-installer
2. Using the `IP Installer` plugin, install and activate the `IP GET Logger`.

## What to do after activating the plugin?

1. Configure necessary parameters in the plugin settings.
2. Go to the `Patterns` section and click the `Update Patterns` button to install a list of patterns against which requests will be checked and logged.
3. Go to the `Exclude Patterns` section and click the `Update Exclude Patterns` button to install a list of exclusions. The plugin will ignore requests with URLs that match these exclusion patterns.

The plugin allows you to add your own patterns and exclusions that won't be erased after updating the main database. You have the ability to add, edit, and delete patterns and exclusions.

The list of patterns and exclusions is periodically updated by the plugin author.

If you have any questions, suggestions, found bugs, or discovered new malicious requests, please report them on GitHub in the [Issues section](https://github.com/pekarskyi/ip-get-logger/issues).

## Example: Watch for suspicious parameters

Add a pattern: `eval=`

This will track requests that might be attempting code injection.

## Languages:
- English
- Українська

The plugin supports the creation of localization for any language.

## Changelog

1.0.1 - 14.04.2025
- Added Logs Export (XLS, HTML)
- Updated localization

1.0.0 - 06.04.2025
- Initial release