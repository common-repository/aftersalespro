<?php

/**
 *
 * @link              https://aftersalespro.gr
 * @since             1.0.0
 * @package           Aftersalesprogr
 *
 * @wordpress-plugin
 * Plugin Name:       AfterSalesPro
 * Description:       Automate the process of voucher/label creation, shipments tracking, customers notification, cash on delivery and returns monitoring and many more.
 * Version:           1.2.4
 * Author:            AfterSalesPro
 * Author URI:        https://aftersalespro.gr
 * License:           GNU GPLv3
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       aftersalespro
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

const AFTERSALESPROGR_VERSION = '1.2.4';
const AFTERSALESPROGR_SLUG    = 'aftersalespro';
const AFTERSALESPROGR_SLUG_F  = 'aftersalesprogr';

register_activation_hook( __FILE__, function () {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aftersalesprogr-activator.php';
	Aftersalesprogr_Activator::activate();
} );

register_deactivation_hook( __FILE__, function () {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aftersalesprogr-deactivator.php';
	Aftersalesprogr_Deactivator::deactivate();
} );

require plugin_dir_path( __FILE__ ) . 'includes/class-aftersalesprogr.php';

add_action( 'init', function () {
	load_plugin_textdomain( 'aftersalespro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

$plugin = new Aftersalesprogr();
$plugin->run();
