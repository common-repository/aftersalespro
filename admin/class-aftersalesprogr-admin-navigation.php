<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 */
class Aftersalesprogr_Admin_Navigation {
	/**
	 * Add Menu Items in Admin Section.
	 */
	public function admin_navigation_options() {
		add_menu_page(
			'AfterSalesPro.gr | Shipment Management Application',
			'AfterSalesPro',
			'manage_options',
			'aftersalesprogr',
			[ $this, 'aftersalesprogr_admin_dashboard' ],
			plugin_dir_url( __FILE__ ) . '../admin/images/icon.png',
			0
		);
	}

	/**
	 * Dashboard.
	 */
	public function aftersalesprogr_admin_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

	    include 'partials/header.php';

		self::handle_post_requests();

		if ( get_option( 'aftersalesprogr_api_token', '' ) == '' ) {
			$message = [
				'class' => 'error',
				'text'  => 'Παρακαλώ συμπληρώστε το API Token.',
			];
			self::generate_page_message( [ $message ] );
		}

		$this->showDashboard();

	    include 'partials/footer.php';
	}

	/**
	 * Shows HTML Dashboard.
	 */
	private static function showDashboard() {
		$setupInformation = [
			[
				'label' => 'Wordpress Version',
				'value' => get_bloginfo( 'version' ),
			],
			[
				'label' => 'PHP Version',
				'value' => phpversion(),
			],
			[
				'label' => 'WooCommerce Version',
				'value' => get_option( 'woocommerce_version' ),
			],
			[
				'label' => 'WordPress Address (URL)',
				'value' => get_bloginfo( 'wpurl' ),
			],
		];

		$settings = get_option( 'woocommerce_Aftersalespro_ShippingMethod_settings' );
		if ( empty( $settings ) ) {
            $settings = [
				'enabled'               => 'no',
				'fallbackMethodEnabled' => 'no',
            ];
		}

		$validCredentials = null;
		$token            = get_option( 'aftersalesprogr_api_token', '' );
		if ( $token != '' ) {
			$carriersResponse = self::getCarriers();
			if ( $carriersResponse['code'] == 200 ) {
				$validCredentials = true;
			} else {
				$validCredentials = false;
			}
		}
		include 'partials/dashboard.php';
	}

	static function getCarriers() {
        $args = [
            'headers' => [
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . get_option( 'aftersalesprogr_api_token', '' ),
            ]
        ];
		$responseRaw = wp_remote_get( 'https://northapi.com/api/3.0/carrier', $args );
		$httpCode    = wp_remote_retrieve_response_code( $responseRaw );
		$response    = wp_remote_retrieve_body( $responseRaw );
		$response    = json_decode( $response, true );

		return [
			'code'     => $httpCode,
			'carriers' => $response,
		];
	}

	/**
	 * Shows HTML message.
	 *
	 * @param $messages
	 */
	public static function generate_page_message( $messages ) {
		foreach ( $messages as $message ) {
			?>
            <div class="alert alert-<?php echo esc_attr( $message['class'] ); ?>">
                <p><?php echo esc_attr( $message['text'] ); ?></p>
            </div>
			<?php
		}
	}

	/**
	 * Handle the save of POST Request to options.
	 */
	private function handle_post_requests() {
		if ( ! empty( $_POST ) ) {
			$messages = [];

			if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'aftersalesprogr' ) ) {
				if ( isset( $_POST['aftersalesprogr_api_token'] ) ) {
					$messages[] = $this->save_option(
						'aftersalesprogr_api_token',
						'API Token'
					);
				}
				if ( isset( $_POST['aftersalesprogr_trackingwidget_status'] ) ) {
					$messages[] = $this->save_option(
						'aftersalesprogr_trackingwidget_status',
						'Tracking Widget Status',
						'intval'
					);
				}
				if ( isset( $_POST['aftersalesprogr_trackingwidget_uuid'] ) ) {
					$messages[] = $this->save_option(
						'aftersalesprogr_trackingwidget_uuid',
						'Tracking Widget UUID',
						'sanitize_url'
					);
				}
				if ( isset( $_POST['aftersalesprogr_order_data_mapper'] ) ) {
					$messages[] = $this->save_option(
						'aftersalesprogr_order_data_mapper',
						'Order Data Mapper fn'
					);
				}
				if ( isset( $_POST['aftersalesprogr_product_mapper'] ) ) {
					$messages[] = $this->save_option(
						'aftersalesprogr_product_mapper',
						'Product Mapper fn'
					);
				}

				if ( isset( $_POST['enabled'] ) ) {
					$options = [
						'enabled'                => trim( $_POST['enabled'] ) == 'yes' ? 'yes' : 'no',
						'freeShippingUpperLimit' => intval( trim( $_POST['freeShippingUpperLimit'] ) ),
						'defaultWeight'          => intval( trim( $_POST['defaultWeight'] ) ),
						'fallbackMethodEnabled'  => trim( $_POST['fallbackMethodEnabled'] ) == 'yes' ? 'yes' : 'no',
						'fallbackMethodTitle'    => sanitize_text_field( trim( $_POST['fallbackMethodTitle'] ) ),
						'fallbackMethodCost'     => intval( trim( $_POST['fallbackMethodCost'] ) ),
					];
					update_option( 'woocommerce_Aftersalespro_ShippingMethod_settings', $options );
					$messages[] = [
						'class' => 'success',
						'text'  => 'Οι ρυθμίσεις για το Checkout αποθηκεύτηκαν.',
					];
				}
			} else {
				$messages[] = [
					'class' => 'error',
					'text'  => 'Forbidden.',
				];
			}

			self::generate_page_message( $messages );
		}
	}

	/**
	 * Update option to database.
	 *
	 * @param $key
	 * @param $label
	 * @param string $sanitize_fn
	 * @return void|array
	 */
	private function save_option( $key, $label, string $sanitize_fn = 'sanitize_text_field' ) {
		if ( isset( $_POST[ $key ] ) ) {
			// if wrong sanitize function is provided, use the default one
			if ( ! in_array( $sanitize_fn, [ 'intval', 'sanitize_url', 'sanitize_text_field' ] ) ) {
				$sanitize_fn = 'sanitize_text_field';
			}
			update_option( $key, ( $sanitize_fn )( trim( $_POST[ $key ] ) ) );

			return [
				'class' => 'success',
				'text'  => "Το πεδίο $label ενημερώθηκε.",
			];
		}
	}
}
