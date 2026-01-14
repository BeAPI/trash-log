<?php
/**
 * Admin page template for Trash Log.
 *
 * @since 1.0.0
 *
 * @var bool   $csv_exists    Whether CSV file exists.
 * @var string $csv_size      CSV file size in human readable format.
 * @var string $csv_url       CSV file URL.
 * @var string $db_size       Database size for log entries in human readable format.
 * @var int    $entries_count Number of log entries.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="trash-log-admin">
		<?php if ( $csv_exists ) : ?>
			<div class="trash-log-info">
				<h2><?php esc_html_e( 'CSV File Information', 'trash-log' ); ?></h2>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'File Size', 'trash-log' ); ?>
							</th>
							<td>
								<strong><?php echo esc_html( $csv_size ); ?></strong>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'File URL', 'trash-log' ); ?>
							</th>
							<td>
								<a href="<?php echo esc_url( $csv_url ); ?>" target="_blank">
									<?php echo esc_html( $csv_url ); ?>
								</a>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'No CSV file has been generated yet. Click the button below to generate it from the logged entries.', 'trash-log' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="trash-log-actions">
			<?php if ( $csv_exists ) : ?>
				<p>
					<a href="<?php echo esc_url( $csv_url ); ?>" class="button button-primary">
						<?php esc_html_e( 'Download CSV', 'trash-log' ); ?>
					</a>
					<button type="button" class="button button-secondary" id="trash-log-delete-csv">
						<?php esc_html_e( 'Delete CSV', 'trash-log' ); ?>
					</button>
					<button type="button" class="button button-secondary" id="trash-log-regenerate-csv">
						<?php esc_html_e( 'Regenerate CSV', 'trash-log' ); ?>
					</button>
				</p>
			<?php else : ?>
				<p>
					<button type="button" class="button button-primary" id="trash-log-generate-csv">
						<?php esc_html_e( 'Generate CSV', 'trash-log' ); ?>
					</button>
				</p>
			<?php endif; ?>
		</div>

		<div id="trash-log-messages"></div>

		<div class="trash-log-database">
			<h2><?php esc_html_e( 'Database Logs', 'trash-log' ); ?></h2>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Number of Entries', 'trash-log' ); ?>
						</th>
						<td>
							<strong><?php echo esc_html( number_format_i18n( $entries_count ) ); ?></strong>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Database Size', 'trash-log' ); ?>
						</th>
						<td>
							<strong><?php echo esc_html( $db_size ? $db_size : __( 'N/A', 'trash-log' ) ); ?></strong>
						</td>
					</tr>
				</tbody>
			</table>
			<p>
				<button type="button" class="button button-secondary" id="trash-log-purge-logs">
					<?php esc_html_e( 'Purge All Logs', 'trash-log' ); ?>
				</button>
			</p>
		</div>
	</div>
</div>

