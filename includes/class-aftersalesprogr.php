<?php

/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Aftersalesprogr
 * @subpackage Aftersalesprogr/includes
 * @author     AfterSalesPro <integrations@aftersalespro.gr>
 */
class Aftersalesprogr {
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Aftersalesprogr_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}


	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aftersalesprogr-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aftersalesprogr-ordermapper.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aftersalesprogr-woocommerce.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-aftersalesprogr-admin-navigation.php';


		$this->loader = new Aftersalesprogr_Loader();
	}

	private function define_admin_hooks() {

		$plugin_admin_nav = new Aftersalesprogr_Admin_Navigation();


		$this->loader->add_action( 'admin_menu', $plugin_admin_nav, 'admin_navigation_options' );
	}

	private function define_public_hooks() {
		add_shortcode( 'aftersalesprogr_tracking_widget', function () {
			if ( get_option( 'aftersalesprogr_trackingwidget_status', '0' ) == '0' ) {
				return 'Please enable tracking widget.';
			}

			$html = '<iframe style="float: left; border: 0;" width="100%" height="600" src="';

			$html           .= get_option( 'aftersalesprogr_trackingwidget_uuid', '' );
			$voucher_number = sanitize_text_field( isset( $_GET['voucher_number'] ) ) ? $_GET['voucher_number'] : null;
			if ( $voucher_number ):
				$html .= '?voucher_number=';
				$html .= $voucher_number;
			endif;
			$html .= '"></iframe>';

			return $html;
		} );
	}

	public function run() {
		add_action( 'admin_enqueue_scripts', function () {
			wp_enqueue_style(
				AFTERSALESPROGR_SLUG_F,
				plugin_dir_url( __FILE__ ) . '../admin/css/aftersalesprogr-admin.css',
				[],
				AFTERSALESPROGR_VERSION
			);
		}, 0);


		add_action( 'parse_request', function ( $wp ) {
			// Check if the request is /apple-app-site-association
			if ( 'asp-api' !== $wp->request ) {
				return;
			}

			require_once plugin_dir_path( __FILE__ ) . 'class-aftersalesprogr-endpoints.php';
			exit;
		}, 0 );

		$this->loader->run();
	}
}
