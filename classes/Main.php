<?php

namespace Trash_Log\Trash_Log;

/**
 * The purpose of the main class is to init all the plugin base code like :
 *  - Taxonomies
 *  - Post types
 *  - Shortcodes
 *  - Posts to posts relations etc.
 *  - Loading the text domain
 *
 * Class Main
 * @package Trash_Log\Trash_Log
 */
class Main {
	/**
	 * Use the trait
	 */
	use Singleton;

	protected function init(): void {
		add_action( 'init', [ $this, 'init_translations' ] );

		// Initialize plugin components.
		Logger::get_instance();
		CSV_Handler::get_instance();
		Admin::get_instance();
	}

	/**
	 * Load the plugin translation
	 */
	public function init_translations(): void {
		// Load translations
		load_plugin_textdomain( 'trash-log', false, dirname( TRASH_LOG_PLUGIN_BASENAME ) . '/languages' );
	}
}
