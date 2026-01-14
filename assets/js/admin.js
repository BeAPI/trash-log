	/**
	 * Admin JavaScript for Trash Log plugin.
	 *
	 * @since 1.0.0
	 */
	(function( $ ) {
		'use strict';

		const { __ } = wp.i18n;

	/**
	 * Show admin notice.
	 *
	 * @param {string} message Message to display.
	 * @param {string} type    Notice type (success, error, warning, info).
	 */
	function showNotice( message, type ) {
		type = type || 'info';
		const noticeClass = 'notice notice-' + type + ' is-dismissible';
		const notice = $( '<div></div>' )
			.addClass( noticeClass )
			.html( '<p>' + message + '</p>' );

		$( '#trash-log-messages' ).html( notice );
		notice.appendTo( '#trash-log-messages' );

		// Auto-dismiss after 5 seconds.
		setTimeout( function() {
			notice.fadeOut( function() {
				$( this ).remove();
			} );
		}, 5000 );
	}

	/**
	 * Handle delete CSV button click.
	 */
	$( '#trash-log-delete-csv' ).on( 'click', function( e ) {
		e.preventDefault();

		if ( ! confirm( trashLogAdmin.confirmText ) ) {
			return;
		}

		const button = $( this );
		const originalText = button.text();
		button.prop( 'disabled', true ).text( __( 'Deleting...', 'trash-log' ) );

		$.ajax( {
			url: trashLogAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'trash_log_delete_csv',
				nonce: trashLogAdmin.nonce,
			},
			success: function( response ) {
				if ( response.success ) {
					showNotice( response.data.message, 'success' );
					// Reload page after 1 second.
					setTimeout( function() {
						location.reload();
					}, 1000 );
				} else {
					showNotice( response.data.message || __( 'An error occurred.', 'trash-log' ), 'error' );
					button.prop( 'disabled', false ).text( originalText );
				}
			},
			error: function() {
				showNotice( __( 'An error occurred while deleting the CSV file.', 'trash-log' ), 'error' );
				button.prop( 'disabled', false ).text( originalText );
			},
		} );
	} );

	/**
	 * Handle generate CSV button click.
	 */
	$( '#trash-log-generate-csv' ).on( 'click', function( e ) {
		e.preventDefault();

		const button = $( this );
		const originalText = button.text();
		button.prop( 'disabled', true ).text( __( 'Generating...', 'trash-log' ) );

		$.ajax( {
			url: trashLogAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'trash_log_generate_csv',
				nonce: trashLogAdmin.nonce,
			},
			success: function( response ) {
				if ( response.success ) {
					showNotice( response.data.message, 'success' );
					// Reload page after 1 second.
					setTimeout( function() {
						location.reload();
					}, 1000 );
				} else {
					showNotice( response.data.message || __( 'An error occurred.', 'trash-log' ), 'error' );
					button.prop( 'disabled', false ).text( originalText );
				}
			},
			error: function() {
				showNotice( __( 'An error occurred while generating the CSV file.', 'trash-log' ), 'error' );
				button.prop( 'disabled', false ).text( originalText );
			},
		} );
	} );

	/**
	 * Handle regenerate CSV button click.
	 */
	$( '#trash-log-regenerate-csv' ).on( 'click', function( e ) {
		e.preventDefault();

		const button = $( this );
		const originalText = button.text();
		button.prop( 'disabled', true ).text( __( 'Regenerating...', 'trash-log' ) );

		$.ajax( {
			url: trashLogAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'trash_log_generate_csv',
				nonce: trashLogAdmin.nonce,
			},
			success: function( response ) {
				if ( response.success ) {
					showNotice( response.data.message, 'success' );
					// Reload page after 1 second.
					setTimeout( function() {
						location.reload();
					}, 1000 );
				} else {
					showNotice( response.data.message || __( 'An error occurred.', 'trash-log' ), 'error' );
					button.prop( 'disabled', false ).text( originalText );
				}
			},
			error: function() {
				showNotice( __( 'An error occurred while regenerating the CSV file.', 'trash-log' ), 'error' );
				button.prop( 'disabled', false ).text( originalText );
			},
		} );
	} );

	/**
	 * Handle purge logs button click.
	 */
	$( '#trash-log-purge-logs' ).on( 'click', function( e ) {
		e.preventDefault();

		if ( ! confirm( trashLogAdmin.confirmPurgeText ) ) {
			return;
		}

		const button = $( this );
		const originalText = button.text();
		button.prop( 'disabled', true ).text( __( 'Purging...', 'trash-log' ) );

		$.ajax( {
			url: trashLogAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'trash_log_purge_logs',
				nonce: trashLogAdmin.nonce,
			},
			success: function( response ) {
				if ( response.success ) {
					showNotice( response.data.message, 'success' );
					// Reload page after 1 second.
					setTimeout( function() {
						location.reload();
					}, 1000 );
				} else {
					showNotice( response.data.message || __( 'An error occurred.', 'trash-log' ), 'error' );
					button.prop( 'disabled', false ).text( originalText );
				}
			},
			error: function() {
				showNotice( __( 'An error occurred while purging log entries.', 'trash-log' ), 'error' );
				button.prop( 'disabled', false ).text( originalText );
			},
		} );
	} );
})( jQuery );

