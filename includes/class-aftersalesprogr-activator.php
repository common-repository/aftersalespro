<?php

/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    Aftersalesprogr
 * @subpackage Aftersalesprogr/includes
 * @author     AfterSalesPro <integrations@aftersalespro.gr>
 */

class Aftersalesprogr_Activator {
	public static function activate() {
		try {
			wp_remote_post( 'https://northapi.com/api/integrations/wordpress/v4/status', [
				'body'    => wp_json_encode( [ 'url' => get_site_url(), 'action' => 'activate' ] ),
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
