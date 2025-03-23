# IP Search Log

WordPress plugin for logging user search queries on the site.

[![GitHub release (latest by date)](https://img.shields.io/github/v/release/pekarskyi/ip-search-log?style=for-the-badge)](https://GitHub.com/pekarskyi/ip-search-log/releases/)

## Description

IP Search Log keeps track of user search queries, storing data about:
- Search queries
- Date and time of the query
- User's IP address
- Number of repeated queries

The plugin provides a convenient interface in the WordPress admin panel for viewing and analyzing stored data, as well as the ability to export this data to Excel format.

## Features

- **Query Logging**: Automatic saving of all user search queries.
- **Query Grouping**: Identical queries from the same IP address are grouped with a count.
- **Admin Panel**: Separate menu item in WordPress admin with a table of queries.
- **Data Sorting**: Ability to sort by different fields (query, date, IP, count).
- **Data Export**: Export all data to Excel (.xlsx) or CSV format.
- **Data Clearing**: Ability to clear all log records.

## Installation

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. After activating the plugin, a table will be created in the database to store search queries

## Usage

1. Go to the "IP Search Log" menu in the WordPress admin panel
2. View the table of user search queries
3. Use the "Export to Excel" button to get data in .xlsx format
4. Use the "Clear All Records" button to delete all saved data

## Localization

The plugin supports localization and can be translated into any language using WordPress translation tools. All text strings in the code are wrapped with translation functions.

To create translations:
1. Use tools like Poedit to create .po and .mo files
2. Save the translation files in the `/languages` directory of the plugin

## Special Features

- The plugin uses the PhpSpreadsheet library for Excel export if available. If not - a backup CSV export mechanism is used.
- All search queries are stored in a separate database table for optimal performance.
- The administrator interface is built on the standard WP_List_Table class to provide a unified look.

## Security

- The plugin uses WordPress nonces to protect against CSRF attacks
- All input data is sanitized and verified before use
- Exported files are stored in a protected directory

## Changelog

1.3 - 23.03.2025:
- ADDED: plugin version check and update function