<?php

namespace BEAPI\Trash_Log;

/**
 * Handles logging of trashed posts, pages, and media.
 *
 * @since 1.0.0
 */
class Logger {
	/**
	 * Use the trait
	 */
	use Singleton;

	/**
	 * Option name for storing trash log entries.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const OPTION_NAME = 'trash_log_entries';

	/**
	 * Initialize the logger.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function init(): void {
		// log posts, pages, custom post types
		add_action( 'wp_trash_post', [ $this, 'log_post_trash' ], 10, 1 );

		// log attachments
		add_action( 'delete_attachment', [ $this, 'log_deleted_attachment' ], 5, 1 );
	}

	/**
	 * Log deleted attachment (fires when attachment is deleted).
	 * This fires after deletion, but we try to get cached data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function log_deleted_attachment( int $post_id ): void {
		$transient_key = 'trash_log_logged_' . $post_id;
		if ( get_transient( $transient_key ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || 'attachment' !== $post->post_type ) {
			return;
		}

		set_transient( $transient_key, true, 60 );

		// Log the attachment.
		$this->log_trashed_post( $post_id );
	}

	/**
	 * Log post trash (fires before post is moved to trash).
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function log_post_trash( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		// Skip attachments (handled by delete_attachment hook).
		if ( 'attachment' === $post->post_type ) {
			return;
		}

		$transient_key = 'trash_log_logged_' . $post_id;
		if ( get_transient( $transient_key ) ) {
			return;
		}

		set_transient( $transient_key, true, 60 );
		$this->log_trashed_post( $post_id );
	}

	/**
	 * Log a trashed post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The post ID.
	 * @return void
	 */
	public function log_trashed_post( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		// Get contributor name.
		$user_id          = get_current_user_id();
		$contributor_name = '';
		if ( $user_id > 0 ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$contributor_name = $user->display_name;
			}
		}

		// Fallback: use "automatic action" if no current user (e.g., cron context).
		if ( empty( $contributor_name ) ) {
			$contributor_name = __( 'Automatic action', 'trash-log' );
		}

		// Get deletion date.
		$trash_time = get_post_meta( $post_id, '_wp_trash_meta_time', true );
		if ( ! empty( $trash_time ) && is_numeric( $trash_time ) ) {
			$deletion_date = Helpers::format_date( (string) $trash_time, 'U', 'd/m/Y' );
		} else {
			$deletion_date = Helpers::format_date( (string) time(), 'U', 'd/m/Y' );
		}

		// Get content type label.
		$post_type_obj = get_post_type_object( $post->post_type );
		$deleted_item  = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;

		// Get media size (only for attachments).
		$media_size = '';
		if ( 'attachment' === $post->post_type ) {
			$media_size = $this->get_attachment_file_size( $post_id );
		}

		// Get URL.
		$url = '';
		if ( 'attachment' === $post->post_type ) {
			$url = wp_get_attachment_url( $post_id );
		} else {
			$url = get_permalink( $post_id );
		}

		if ( empty( $url ) ) {
			$url = '';
		}

		$log_entry = [
			'contributor_name' => sanitize_text_field( $contributor_name ),
			'deleted_item'     => sanitize_text_field( $deleted_item ),
			'deletion_date'    => sanitize_text_field( $deletion_date ),
			'media_size'       => sanitize_text_field( $media_size ),
			'url'              => esc_url_raw( $url ),
			'timestamp'        => time(),
		];

		$entries = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $entries ) ) {
			$entries = [];
		}

		$entries[] = $log_entry;

		update_option( self::OPTION_NAME, $entries, false );

		// Mark as logged to avoid duplicates from multiple hooks.
		set_transient( 'trash_log_logged_' . $post_id, true, 60 );
	}

	/**
	 * Get attachment file size in human readable format.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id The attachment post ID.
	 * @return string Formatted file size or empty string if not found.
	 */
	private function get_attachment_file_size( int $post_id ): string {
		$metadata = wp_get_attachment_metadata( $post_id );
		if ( isset( $metadata['filesize'] ) && $metadata['filesize'] > 0 ) {
			return size_format( $metadata['filesize'], 1 );
		}

		// Fallback: try to get file size from file system.
		$attached_file = get_attached_file( $post_id );
		if ( empty( $attached_file ) || ! file_exists( $attached_file ) ) {
			return '';
		}

		$file_size = filesize( $attached_file );
		if ( false === $file_size || $file_size <= 0 ) {
			return '';
		}

		return size_format( $file_size, 1 );
	}

	/**
	 * Get all log entries.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of log entries.
	 */
	public function get_entries(): array {
		$entries = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $entries ) ) {
			return [];
		}

		return $entries;
	}

	/**
	 * Clear all log entries.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_entries(): bool {
		return delete_option( self::OPTION_NAME );
	}

	/**
	 * Get estimated database size for log entries.
	 *
	 * @since 1.0.0
	 *
	 * @return string Formatted size in human readable format.
	 */
	public function get_database_size(): string {
		return Helpers::get_option_size( self::OPTION_NAME );
	}

	/**
	 * Get number of log entries.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of log entries.
	 */
	public function get_entries_count(): int {
		$entries = $this->get_entries();
		return count( $entries );
	}
}
