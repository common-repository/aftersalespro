<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Aftersalesprogr
 * @subpackage Aftersalesprogr/includes
 * @author     AfterSalesPro <integrations@aftersalespro.gr>
 */

class Aftersalesprogr_Deactivator {
	public static function deactivate() {
		try {
			wp_remote_post( 'https://northapi.com/api/integrations/wordpress/v4/status', [
				'body'    => wp_json_encode( [ 'url' => get_site_url(), 'action' => 'deactivate' ] ),
				'headers' => [
					'Accept'        => 'application/json',
					'Content-Type'  => 'application/json',
					'Cache-Control' => 'no-cache',
				],
			] );
		} catch ( Exception $e ) {
		}
	}
}
