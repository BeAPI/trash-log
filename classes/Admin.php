<?php

namespace Trash_Log\Trash_Log;

/**
 * Handles admin page and AJAX actions.
 *
 * @since 1.0.0
 */
class Admin {
	/**
	 * Use the trait
	 */
	use Singleton;

	/**
	 * Page hook suffix.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Initialize the admin.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function init(): void {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', [ $this, 'add_admin_menu' ] );
		} else {
			add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_trash_log_download_csv', [ $this, 'ajax_download_csv' ] );
		add_action( 'wp_ajax_trash_log_delete_csv', [ $this, 'ajax_delete_csv' ] );
		add_action( 'wp_ajax_trash_log_generate_csv', [ $this, 'ajax_generate_csv' ] );
		add_action( 'wp_ajax_trash_log_purge_logs', [ $this, 'ajax_purge_logs' ] );

		add_action( 'init', [ $this, 'handle_secured_csv_download' ] );
	}

	/**
	 * Check if current user has permission to access admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	private function current_user_can_access(): bool {
		if ( is_multisite() ) {
			return is_super_admin();
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Verify nonce for AJAX requests.
	 *
	 * @since 1.0.0
	 *
	 * @param string $nonce_action The nonce action name.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	private function verify_nonce( string $nonce_action ): bool {
		if ( ! isset( $_POST['nonce'] ) ) {
			return false;
		}

		return wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $nonce_action );
	}

	/**
	 * Verify nonce for GET requests.
	 *
	 * @since 1.0.0
	 *
	 * @param string $nonce_action The nonce action name.
	 * @return bool True if nonce is valid, false otherwise.
	 */
	private function verify_get_nonce( string $nonce_action ): bool {
		if ( ! isset( $_GET['nonce'] ) ) {
			return false;
		}

		return wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), $nonce_action );
	}

	/**
	 * Verify AJAX request (nonce and permissions).
	 *
	 * @since 1.0.0
	 *
	 * @param string $nonce_action The nonce action name.
	 * @return bool True if request is valid, false otherwise.
	 */
	private function verify_ajax_request( string $nonce_action ): bool {
		if ( ! $this->verify_nonce( $nonce_action ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'trash-log' ) ] );
			return false;
		}

		if ( ! $this->current_user_can_access() ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'trash-log' ) ] );
			return false;
		}

		return true;
	}

	/**
	 * Add admin menu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		if ( ! $this->current_user_can_access() ) {
			return;
		}

		$page_title = __( 'Trash Log', 'trash-log' );
		$menu_title = __( 'Trash Log', 'trash-log' );
		$capability = is_multisite() ? 'manage_network' : 'manage_options';
		$menu_slug  = 'trash-log';

		if ( is_multisite() ) {
			$this->page_hook = add_submenu_page(
				'settings.php',
				$page_title,
				$menu_title,
				$capability,
				$menu_slug,
				[ $this, 'render_admin_page' ]
			);
		} else {
			$this->page_hook = add_management_page(
				$page_title,
				$menu_title,
				$capability,
				$menu_slug,
				[ $this, 'render_admin_page' ]
			);
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( string $hook_suffix ): void {
		if ( $this->page_hook !== $hook_suffix ) {
			return;
		}

		if ( ! $this->current_user_can_access() ) {
			return;
		}

		wp_enqueue_script(
			'trash-log-admin',
			TRASH_LOG_URL . 'assets/js/admin.js',
			[ 'jquery', 'wp-i18n' ],
			TRASH_LOG_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'trash-log-admin',
				'trash-log',
				TRASH_LOG_DIR . 'languages'
			);
		}

		wp_localize_script(
			'trash-log-admin',
			'trashLogAdmin',
			[
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'trash_log_admin' ),
				'confirmText'      => __( 'Are you sure you want to delete the CSV file?', 'trash-log' ),
				'confirmPurgeText' => __( 'Are you sure you want to purge all log entries from the database? This action cannot be undone.', 'trash-log' ),
			]
		);
	}

	/**
	 * Render admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		if ( ! $this->current_user_can_access() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'trash-log' ) );
		}

		$csv_handler   = CSV_Handler::get_instance();
		$logger        = Logger::get_instance();
		$csv_exists    = $csv_handler->csv_exists();
		$csv_size      = $csv_handler->get_csv_size();
		$csv_url       = $csv_handler->get_csv_url();
		$db_size       = $logger->get_database_size();
		$entries_count = $logger->get_entries_count();

		Helpers::render(
			'admin-page',
			[
				'csv_exists'    => $csv_exists,
				'csv_size'      => $csv_size,
				'csv_url'       => $csv_url,
				'db_size'       => $db_size,
				'entries_count' => $entries_count,
			]
		);
	}

	/**
	 * Handle secured CSV download via GET request (for direct links).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_secured_csv_download(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Early return before nonce check.
		if ( ! isset( $_GET['action'] ) || 'trash_log_download_csv' !== $_GET['action'] ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'You must be logged in to access this file.', 'trash-log' ), esc_html__( 'Error', 'trash-log' ), [ 'response' => 403 ] );
		}

		if ( ! $this->verify_get_nonce( 'trash_log_download_csv' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'trash-log' ), esc_html__( 'Error', 'trash-log' ), [ 'response' => 403 ] );
		}

		if ( ! $this->current_user_can_access() ) {
			wp_die( esc_html__( 'You do not have permission to access this file.', 'trash-log' ), esc_html__( 'Error', 'trash-log' ), [ 'response' => 403 ] );
		}

		$csv_handler = CSV_Handler::get_instance();

		if ( ! $csv_handler->csv_exists() ) {
			$result = $csv_handler->generate_csv();
			if ( is_wp_error( $result ) ) {
				wp_die( esc_html( $result->get_error_message() ), esc_html__( 'Error', 'trash-log' ), [ 'response' => 500 ] );
			}
		}

		$csv_handler->download_csv();
	}

	/**
	 * Handle AJAX request to download CSV.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_download_csv(): void {
		if ( ! $this->verify_ajax_request( 'trash_log_admin' ) ) {
			return;
		}

		$csv_handler = CSV_Handler::get_instance();

		if ( ! $csv_handler->csv_exists() ) {
			$result = $csv_handler->generate_csv();
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( [ 'message' => $result->get_error_message() ] );
			}
		}

		$csv_handler->download_csv();
	}

	/**
	 * Handle AJAX request to delete CSV.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_delete_csv(): void {
		if ( ! $this->verify_ajax_request( 'trash_log_admin' ) ) {
			return;
		}

		$csv_handler = CSV_Handler::get_instance();
		$result      = $csv_handler->delete_csv();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [ 'message' => __( 'CSV file deleted successfully.', 'trash-log' ) ] );
	}

	/**
	 * Handle AJAX request to generate CSV.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_generate_csv(): void {
		if ( ! $this->verify_ajax_request( 'trash_log_admin' ) ) {
			return;
		}

		$csv_handler = CSV_Handler::get_instance();
		$result      = $csv_handler->generate_csv();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		$csv_size = $csv_handler->get_csv_size();
		$csv_url  = $csv_handler->get_csv_url();

		wp_send_json_success(
			[
				'message' => __( 'CSV file generated successfully.', 'trash-log' ),
				'size'    => $csv_size,
				'url'     => $csv_url,
			]
		);
	}

	/**
	 * Handle AJAX request to purge log entries.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_purge_logs(): void {
		if ( ! $this->verify_ajax_request( 'trash_log_admin' ) ) {
			return;
		}

		$logger = Logger::get_instance();
		$result = $logger->clear_entries();

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Failed to purge log entries.', 'trash-log' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'All log entries have been purged from the database.', 'trash-log' ) ] );
	}
}
