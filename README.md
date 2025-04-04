# IP Search Log

WordPress plugin for logging user search queries on the site.

[![GitHub release (latest by date)](https://img.shields.io/github/v/release/pekarskyi/ip-search-log?style=for-the-badge)](https://GitHub.com/pekarskyi/ip-search-log/releases/)

## Description

IP Search Log keeps track of user search queries, storing data about:
- Search queries
- Date of the query
- Number of repeated queries

The plugin provides a convenient interface in the WordPress admin panel for viewing and analyzing stored data.

## Features

- **Query Logging**: Automatic saving of all user search queries.
- **Query Grouping**: Identical queries are grouped with a count.
- **Admin Panel**: Separate menu item in WordPress admin with a table of queries.
- **Data Sorting**: Ability to sort by different fields (query, date, count).
- **Advanced Filtering**: Filter queries by date range and search terms.
- **Data Clearing**: Ability to clear all log records.

## Installation

### Option 1:

1. Download the IP Search Log plugin (green Code button - Download ZIP).
2. Upload it to your WordPress site. Make sure the plugin folder is named "ip-search-log" (the name doesn't affect how the plugin works, but it does affect receiving future updates).
3. Activate the plugin.

### Option 2 (recommended):

1. Install and activate this plugin (plugin installer): https://github.com/pekarskyi/ip-installer
2. Using the `IP Installer` plugin, install and activate the `IP Search Log plugin`.

After activating the plugin, a log file will be created to store search queries.

## Usage

1. Go to the "IP Search Log" menu in the WordPress admin panel
2. View the table of user search queries
3. Use the date filters and search box to find specific queries
4. Sort data by clicking on column headers (Date or Count)
5. Use the "Clear All Records" button to delete all saved data

## Localization

- English

The plugin supports localization and can be translated into any language using WordPress translation tools. All text strings in the code are wrapped with translation functions.

To create translations:
1. Use tools like Poedit to create .po and .mo files
2. Save the translation files in the `/languages` directory of the plugin

## Special Features

- All search queries are stored in a log file for optimal performance
- The administrator interface is built on the standard `WP_List_Table class`
- Responsive interface with confirmation modals for destructive actions
- Automatic GitHub updates system

## Security

- The plugin uses WordPress nonces to protect against CSRF attacks
- All input data is sanitized and verified before use
- Log files are stored in a protected directory with .htaccess restrictions

## Compatibility

- Requires WordPress: 6.7.0 or higher
- Requires PHP: 7.4 or higher
- Tested up to WordPress: 6.7.2

## Changelog

1.1.0 - 05.04.2025:
- IMPROVED: Enhanced sorting functionality for Date and Count columns
- IMPROVED: Better date filtering interface with datepicker
- ADDED: Automatic updates from GitHub repository

1.0.0 - 23.03.2025:
- ADDED: Initial release with basic logging functionality
- ADDED: Plugin version check and update function