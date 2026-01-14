# Trash Log for WordPress

**Version:** 1.0.0

**Author:** Be API Technical team

**Plugin URI:** <https://beapi.fr>

## Description

**Trash Log** is a WordPress plugin that automatically logs everything sent to trash. The plugin tracks deleted posts, pages, and media, recording detailed information such as contributor name, deletion date, media file size, and URLs. Administrators can view these logs via a dedicated admin page and export the data as CSV.

## Features

* **Automatic logging:**
  * Logs all posts, pages, and media sent to trash.
  * Captures detailed information: contributor name, deleted item, date, media size, URL.
* **Admin interface:**
  * Dedicated page in the **Tools > Trash Log** menu (or **Settings > Trash Log** in multisite).
  * Statistics display: number of entries, database size.
  * Log management: purge database entries.
* **CSV export:**
  * Generate CSV files with all logged data.
  * Excel compatible (UTF-8 BOM).
  * Secure download via protected endpoint.
  * CSV file protection via `.htaccess`.
* **Media management:**
  * Automatic detection of media file size before deletion.
  * Logging of deleted media URLs.
* **Multisite compatible:**
  * Centralized log management in multisite environments.
  * Network menu for super administrators.
* **Translation ready:**
  * Textdomain automatically loaded from `/languages`.
  * JavaScript translation support via `wp-i18n`.
* **Security & robustness:**
  * Permission checks for admin interface access.
  * CSV file protection against direct access.
  * Nonce verification for all AJAX actions.
  * Duplicate prevention during logging.
  * URLs logged with slug instead of ID for better readability.

## Installation

1. Download and upload the plugin to your WordPress site, then activate it.
2. Go to **Tools > Trash Log** (or **Settings > Trash Log** in multisite) to view the logs.

**Via Composer :**

```
composer require beapi/trash-log
```

## Configuration

The plugin works automatically upon activation. No configuration is required.

### Admin page

On the **Trash Log** page, you can:

* **View statistics:**
  * Number of logged entries.
  * Database size used.
* **Manage CSV file:**
  * Generate a CSV file from logged entries.
  * Download existing CSV file.
  * Delete CSV file.
  * Regenerate CSV file.
* **Purge logs:**
  * Delete all database entries (irreversible action).

## How it works

* The plugin uses WordPress hooks to intercept deletions:
  * `wp_trash_post` hook for posts, pages, and custom post types (fires before post is moved to trash).
  * `delete_attachment` hook for media files.
* Whenever a post, page, or media is sent to trash, the plugin automatically logs:
  * The contributor name who performed the action.
  * The deleted item title.
  * The deletion date (DD/MM/YYYY format).
  * The media file size (if applicable, captured before deletion).
  * The deleted item URL (with slug instead of ID for better readability).
* Data is stored in WordPress `wp_options` table under the `trash_log_entries` option.
* CSV files are generated on demand and stored in `wp-content/uploads/trash-log/`, protected against direct access.
* The plugin uses PSR-4 autoloading for efficient class loading.
* In multisite environments, logs are managed at the network level.

## CSV data structure

The generated CSV file contains the following columns:

* **Contributor name**: Display name of the user who deleted the item.
* **Deleted item**: Title of the deleted post, page, or media.
* **Date**: Deletion date in DD/MM/YYYY format.
* **Document size**: Media file size (if applicable, human-readable format).
* **URL**: URL of the deleted item (with slug, not ID).

## Requirements

* PHP **7.4** or higher
* WordPress **5.0** or higher

## License

This plugin is licensed under the GPLv2 or later. The development of this plugin is sponsored by CDC Habitat, a leading provider of social housing in France.

## Support

For any questions or issues, please open an issue on the GitHub repository.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed list of changes.
