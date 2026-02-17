<?php
/*
Plugin Name: Trash Log
Version: 1.0.1
Version Boilerplate: 3.5.0
Plugin URI: https://beapi.fr
Description: Simply logs everything sent to trash - sponsored by CDC Habitat
Author: Be API Technical team
Author URI: https://beapi.fr
Domain Path: languages
Text Domain: trash-log

----

Copyright 2021 Be API Technical team (human@beapi.fr)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Plugin constants
define( 'TRASH_LOG_VERSION', '1.0.0' );
define( 'TRASH_LOG_VIEWS_FOLDER_NAME', 'trash-log' );

// Plugin URL and PATH
define( 'TRASH_LOG_URL', plugin_dir_url( __FILE__ ) );
define( 'TRASH_LOG_DIR', plugin_dir_path( __FILE__ ) );
define( 'TRASH_LOG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load autoloader
require_once TRASH_LOG_DIR . 'autoload.php';

// Instantiate the loader
$loader = new \BEAPI\Trash_Log\Autoloader();

// Register the autoloader
$loader->register();

// Register the base directories for the namespace prefix
$loader->addNamespace( 'BEAPI\Trash_Log', TRASH_LOG_DIR . 'classes' );

add_action( 'plugins_loaded', 'init_trash_log_plugin' );
/**
 * Init the plugin.
 *
 * @return void
 */
function init_trash_log_plugin(): void {
	\BEAPI\Trash_Log\Main::get_instance();
	\BEAPI\Trash_Log\Logger::get_instance();
	\BEAPI\Trash_Log\CSV_Handler::get_instance();
	\BEAPI\Trash_Log\Admin::get_instance();
}
