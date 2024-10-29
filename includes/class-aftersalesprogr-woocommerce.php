<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! in_array(
	'woocommerce/woocommerce.php',
	apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
) ) {
	return;
}

add_action( 'woocommerce_shipping_init', function () {
	class Aftersalespro_ShippingMethod extends WC_Shipping_Method {
		public function __construct() {
			$this->id                 = 'Aftersalespro_ShippingMethod';
			$this->title              = 'AfterSalesPro';
			$this->method_title       = 'AfterSalesPro Shipping';
			$this->method_description = '';

			$settings      = Aftersalespro_ShippingMethod::getSettings();
			$this->enabled = $settings['enabled'];

			$this->init_form_fields();
		}

		public static function getSettings() {
			$settings = get_option( 'woocommerce_Aftersalespro_ShippingMethod_settings' );
			if ( empty( $settings ) ) {
				$settings = array(
					'enabled'               => 'no',
					'fallbackMethodEnabled' => 'no',
				);
			}

			return $settings;
		}

		function init_form_fields() {
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		function admin_options() {
			?>
            <h2><?php esc_html_e( 'AfterSalesPro Shipping', 'aftersalespro' ); ?></h2>
            <br/>
            <p style="font-size: 18px;">
				<?php esc_html_e( 'Settings have moved', 'aftersalespro' ); ?>
                <a href="/wp-admin/admin.php?page=aftersalesprogr">
					<?php esc_html_e( 'here.', 'aftersalespro' ); ?>
                </a>
            </p>
            <style>#mainform p.submit {
                    display: none;
                ]</style>
			<?php
		}

		public function calculate_shipping( $package = [] ) {
			$settings = Aftersalespro_ShippingMethod::getSettings();
			if ( $settings['enabled'] !== 'yes' ) {
				return;
			}

			$orderTotalCost = 0;
			$weightTotal    = 0;
			foreach ( $package['contents'] as $values ) {
				$_product = $values['data'];

				$tempWeight = (int) $settings['defaultWeight'];
				if ( is_numeric( $_product->get_weight() ) ) {
					$tempWeight = $_product->get_weight() * $values['quantity'];
				}
				$weightTotal    += $tempWeight * 1000;
				$orderTotalCost += $_product->get_price() * $values['quantity'];
			}

			$endpoint      = 'https://northapi.com/api/3.0/shipmentQuote';
			$zipcode       = $package['destination']['postcode'] ?? null;
			$country       = $package['destination']['country'] ?? null;
			$area          = $package['destination']['city'] ?? null;
			$isCod         = 0;
			$endpoint      .= "?zipcode=$zipcode&weightInGram=$weightTotal&isCod=$isCod&countryCode=$country&area=$area";
			$ratesResponse = wp_remote_get(
				$endpoint,
				array(
					'headers' => [
						'Accept'        => 'application/json',
						'Authorization' => 'Bearer ' . get_option( 'aftersalesprogr_api_token', '' ),
						'Cache-Control' => 'no-cache',
						'Content-Type'  => 'application/json',
						'accept'        => 'application/json',
					]
				)
			);

			$httpCode = wp_remote_retrieve_response_code( $ratesResponse );
			$response = wp_remote_retrieve_body( $ratesResponse );
			$response = json_decode( $response, true );

            if ($httpCode === 422) {
                return;
            }

			if ( $httpCode !== 200 && $settings['fallbackMethodEnabled'] == 'yes' ) {
				$rate = array(
					'id'    => $this->id,
					'label' => $settings['fallbackMethodTitle'],
					'cost'  => $orderTotalCost < $settings['freeShippingUpperLimit'] ? $settings['fallbackMethodCost'] : 0
				);
				$this->add_rate( $rate );

				return;
			}

			if (
				( ! isset( $response['quotes'] )
				  || ! count( $response['quotes'] )
				)
				&& $settings['fallbackMethodEnabled'] == 'yes'
			) {
				$rate = array(
					'id'    => $this->id,
					'label' => $settings['fallbackMethodTitle'],
					'cost'  => $orderTotalCost < $settings['freeShippingUpperLimit'] ? $settings['fallbackMethodCost'] : 0
				);
				$this->add_rate( $rate );

				return;
			}

			foreach ( $response['quotes'] as $rateKey => $rateData ) {
				if ( isset( $rateData['costInCents'] ) && $rateData['costInCents'] > 0 ) {
					$rate = array(
						'id'        => $this->id . '-' . $rateKey,
						'label'     => $rateData['carrierName'],
						'cost'      => $orderTotalCost < $settings['freeShippingUpperLimit'] ? $rateData['costInCents'] / 100 : 0,
						'meta_data' => array(
							'carrier' => $rateKey,
						)
					);
					$this->add_rate( $rate );
				}
			}
		}
	}
} );

add_filter( 'woocommerce_shipping_methods', function ( $methods ) {
	$methods[] = 'Aftersalespro_ShippingMethod';

	return $methods;
} );
