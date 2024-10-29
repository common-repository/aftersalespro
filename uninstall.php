<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://aftersalespro.gr
 * @since      1.0.0
 *
 * @package    Aftersalesprogr
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

try {
	wp_remote_post( 'https://northapi.com/api/integrations/wordpress/v4/status', [
		'body'    => wp_json_encode( [ 'url' => get_site_url(), 'action' => 'uninstall' ] ),
		'headers' => [
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
			'Cache-Control' => 'no-cache',
		],
	] );
} catch ( Exception $e ) {
}
