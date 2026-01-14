# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-14

### Added
- Initial release of Trash Log plugin
- Automatic logging of posts, pages, and media sent to trash
- Detailed logging information including:
  - Contributor name (user who performed the deletion)
  - Deleted item title
  - Deletion date (DD/MM/YYYY format)
  - Media file size (for attachments, in human-readable format)
  - Deleted item URL (with slug instead of ID)
- Admin interface accessible via **Tools > Trash Log** (or **Settings > Trash Log** in multisite)
- Statistics display:
  - Number of logged entries
  - Database size used by log entries
- CSV export functionality:
  - Generate CSV files from logged entries
  - Excel-compatible format with UTF-8 BOM
  - Download existing CSV file
  - Delete CSV file
  - Regenerate CSV file
- Secure CSV file protection:
  - Protected download endpoint with nonce verification
  - `.htaccess` file protection for Apache servers
- Multisite support:
  - Centralized log management at network level
  - Network admin menu for super administrators
- Translation support:
  - Textdomain automatically loaded from `/languages`
  - JavaScript translation support via `wp-i18n`
  - French translation (fr_FR) included
- PSR-4 autoloading system for efficient class loading
- Duplicate prevention during logging using transients
- Permission checks for all admin interface access
- Nonce verification for all AJAX actions

### Security
- CSV files protected against direct access
- Secure download endpoint requiring authentication and nonce verification
- Permission checks ensure only authorized users can access logs
- Input sanitization and output escaping throughout the codebase
