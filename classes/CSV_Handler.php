<?php

namespace BEAPI\Trash_Log;

/**
 * Handles CSV file generation, storage, and management.
 *
 * @since 1.0.0
 */
class CSV_Handler {
	/**
	 * Use the trait
	 */
	use Singleton;

	/**
	 * CSV filename.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const CSV_FILENAME = 'trash-log.csv';

	/**
	 * Directory name for storing CSV files.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const CSV_DIR = 'trash-log';

	/**
	 * Get the directory path for CSV files.
	 *
	 * @since 1.0.0
	 *
	 * @return string Directory path.
	 */
	private function get_csv_dir(): string {
		$upload_dir = wp_upload_dir();
		if ( $upload_dir['error'] ) {
			return '';
		}

		return trailingslashit( $upload_dir['basedir'] ) . self::CSV_DIR;
	}

	/**
	 * Get the CSV file path.
	 *
	 * @since 1.0.0
	 *
	 * @return string CSV file path.
	 */
	public function get_csv_path(): string {
		$dir = $this->get_csv_dir();
		if ( empty( $dir ) ) {
			return '';
		}

		return trailingslashit( $dir ) . self::CSV_FILENAME;
	}

	/**
	 * Get the CSV file URL (secured endpoint).
	 *
	 * @since 1.0.0
	 *
	 * @return string CSV file URL (secured endpoint).
	 */
	public function get_csv_url(): string {
		return add_query_arg(
			[
				'action' => 'trash_log_download_csv',
				'nonce'  => wp_create_nonce( 'trash_log_download_csv' ),
			],
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Get the CSV file size in human readable format.
	 *
	 * @since 1.0.0
	 *
	 * @return string Formatted file size or empty string if file doesn't exist.
	 */
	public function get_csv_size(): string {
		$file_path = $this->get_csv_path();
		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			return '';
		}

		$file_size = filesize( $file_path );
		if ( false === $file_size || $file_size <= 0 ) {
			return '';
		}

		return size_format( $file_size, 1 );
	}

	/**
	 * Check if CSV file exists.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if file exists, false otherwise.
	 */
	public function csv_exists(): bool {
		$file_path = $this->get_csv_path();
		if ( empty( $file_path ) ) {
			return false;
		}

		return file_exists( $file_path );
	}

	/**
	 * Generate and save CSV file from log entries.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function generate_csv() {
		$logger  = Logger::get_instance();
		$entries = $logger->get_entries();

		if ( empty( $entries ) ) {
			return new \WP_Error(
				'no_entries',
				__( 'No log entries found.', 'trash-log' )
			);
		}

		$csv_dir = $this->get_csv_dir();
		if ( empty( $csv_dir ) ) {
			return new \WP_Error(
				'upload_dir_error',
				__( 'Unable to access upload directory.', 'trash-log' )
			);
		}

		if ( ! file_exists( $csv_dir ) ) {
			$created = wp_mkdir_p( $csv_dir );
			if ( ! $created ) {
				return new \WP_Error(
					'create_dir_failed',
					__( 'Failed to create CSV directory.', 'trash-log' )
				);
			}
		}

		$this->protect_csv_directory( $csv_dir );

		$csv_path = $this->get_csv_path();
		if ( empty( $csv_path ) ) {
			return new \WP_Error(
				'invalid_path',
				__( 'Invalid CSV file path.', 'trash-log' )
			);
		}

		$file_handle = fopen( $csv_path, 'w' );
		if ( false === $file_handle ) {
			return new \WP_Error(
				'file_open_failed',
				__( 'Failed to open CSV file for writing.', 'trash-log' )
			);
		}

		// Write BOM for Excel compatibility.
		fprintf( $file_handle, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		$header = [
			__( 'Contributor Name', 'trash-log' ),
			__( 'Content Type', 'trash-log' ),
			__( 'Date', 'trash-log' ),
			__( 'Document Size', 'trash-log' ),
			__( 'URL', 'trash-log' ),
		];
		fputcsv( $file_handle, $header, ';' );

		foreach ( $entries as $entry ) {
			$row = [
				$entry['contributor_name'] ?? '',
				$entry['deleted_item'] ?? '',
				$entry['deletion_date'] ?? '',
				$entry['media_size'] ?? '',
				$entry['url'] ?? '',
			];
			fputcsv( $file_handle, $row, ';' );
		}

		fclose( $file_handle );

		return true;
	}

	/**
	 * Delete the CSV file.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function delete_csv() {
		$file_path = $this->get_csv_path();
		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			return new \WP_Error(
				'file_not_found',
				__( 'CSV file not found.', 'trash-log' )
			);
		}

		$deleted = unlink( $file_path );
		if ( ! $deleted ) {
			return new \WP_Error(
				'delete_failed',
				__( 'Failed to delete CSV file.', 'trash-log' )
			);
		}

		return true;
	}

	/**
	 * Force download of CSV file.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function download_csv(): void {
		$file_path = $this->get_csv_path();
		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			wp_die( esc_html__( 'CSV file not found.', 'trash-log' ) );
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . self::CSV_FILENAME . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		readfile( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile

		exit;
	}

	/**
	 * Check if current user has permission to access CSV.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if user has permission, false otherwise.
	 */
	public function current_user_can_access(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Protect specific CSV file with .htaccess file using WordPress markers.
	 *
	 * @since 1.0.0
	 *
	 * @param string $csv_dir CSV directory path.
	 * @return bool True on success, false on failure.
	 */
	private function protect_csv_directory( string $csv_dir ): bool {
		if ( empty( $csv_dir ) ) {
			return false;
		}

		$htaccess_file = trailingslashit( $csv_dir ) . '.htaccess';
		$marker        = 'Trash Log';
		$insertion     = [
			'<Files ' . self::CSV_FILENAME . '>',
			'    Order allow,deny',
			'    Deny from all',
			'</Files>',
		];

		return insert_with_markers( $htaccess_file, $marker, $insertion );
	}
}
