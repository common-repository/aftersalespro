<?php

class AfterSalesProGrApi {
	/**
	 * Get header Authorization.
	 * */
	private function getAuthorizationHeader(): string {
		if ( isset( $_SERVER['HTTP_X_ASP_TOKEN'] ) ) {
			return trim( sanitize_text_field( $_SERVER['HTTP_X_ASP_TOKEN'] ) );
		}

		if ( isset( $_SERVER['Authorization'] ) ) {
			return trim( sanitize_text_field( $_SERVER['Authorization'] ) );
		}

		if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) { //Nginx or fast CGI
			return trim( sanitize_text_field( $_SERVER['HTTP_AUTHORIZATION'] ) );
		}

		if ( function_exists( 'apache_request_headers' ) ) {
			$requestHeaders = apache_request_headers();
			// Server-side fix for bug in old Android versions (a nice side effect of this fix means we don't care
			// about capitalization for Authorization)
			$requestHeaders = array_combine( array_map( 'ucwords', array_keys( $requestHeaders ) ),
				array_values( $requestHeaders ) );
			if ( isset( $requestHeaders['Authorization'] ) ) {
				return trim( $requestHeaders['Authorization'] );
			}
		}

		return '';
	}

	/**
	 * get access token from header.
	 * */
	private function getBearerToken(): string {
		$headers = $this->getAuthorizationHeader();
		if ( $headers != '' ) {
			if ( preg_match( '/Bearer\s(\S+)/', $headers, $matches ) ) {
				return $matches[1];
			}
		}

		return '';
	}

	/**
	 * Validate Sent Bearer with saved.
	 */
	private function validateRequest() {
		$token = get_option( 'aftersalesprogr_api_token', '' );
		if ( $token == '' ) {
			echo json_encode( [
				'meta' => [
					'is_successful' => false,
					'message'       => 'Token is not set.',
					'version'       => AFTERSALESPROGR_VERSION,
				],
			], JSON_PRETTY_PRINT );
			die();
		}

		$bearer = $this->getBearerToken();
		if ( $bearer == '' ) {
			echo json_encode( [
				'meta' => [
					'is_successful' => false,
					'message'       => 'Bearer could not be read.',
					'version'       => AFTERSALESPROGR_VERSION,
				],
			], JSON_PRETTY_PRINT );
			die();
		}

		if ( $bearer != $token ) {
			echo json_encode( [
				'meta' => [
					'is_successful' => false,
					'message'       => 'Authorization failed.',
					'version'       => AFTERSALESPROGR_VERSION,
				],
			], JSON_PRETTY_PRINT );
			die();
		}
	}

	private function getOrders(): WP_Query {
		$args                   = [];
		$args['posts_per_page'] = - 1;
		$args['post_type']      = [ 'shop_order', 'shop_order_placehold' ];

		$args['date_query'] = [];

		/*
		 * support deprecated use of after
		 */
		$updated_at_from = $_REQUEST['updated_at_from'] ?? $_REQUEST['after'] ?? null;
		if ( isset( $updated_at_from ) ) {
			$args['date_query'][] = [
				'after'  => sanitize_text_field( $updated_at_from ),
				'column' => 'post_modified',
			];
		}

		if ( isset( $_REQUEST['updated_at_to'] ) ) {
			$args['date_query'][] = [
				'before'  => sanitize_text_field( $_REQUEST['updated_at_to'] ),
				'column' => 'post_modified',
			];
		}

		if ( isset( $_REQUEST['created_at_from'] ) ) {
			$args['date_query'][] = [
				'after'  => sanitize_text_field( $_REQUEST['created_at_from'] ),
				'column' => 'post_date',
			];
		}

		if ( isset( $_REQUEST['created_at_to'] ) ) {
			$args['date_query'][] = [
				'before'  => sanitize_text_field( $_REQUEST['created_at_to'] ),
				'column' => 'post_date',
			];
		}

		if ( isset( $_REQUEST['ids'] ) ) {
			$ids = explode( ',', sanitize_text_field( $_REQUEST['ids'] ) );

			if ( function_exists( 'wc_sequential_order_numbers' ) ) {
				$wpIds = $ids;
				$ids   = [];
				foreach ( $wpIds as $id ) {
					$ids[] = wc_sequential_order_numbers()->find_order_by_order_number( $id );
				}
			}

			$args['post__in'] = $ids;
		}

		$args['post_status'] = 'any';
		if ( isset( $_REQUEST['status'] ) ) {
			$args['post_status'] = explode( ',', sanitize_text_field( $_REQUEST['status'] ) );
		}

		return new WP_Query( $args );
	}

	public function list_orders() {
		$this->validateRequest();

		/*
		 * Get Orders
		 */
		$orders = $this->getOrders();

		if ( $orders->found_posts == 0 ) {
			echo json_encode( [
				'meta' => [
					'is_successful' => true,
					'message'       => 'No orders found.',
				],
				'data' => [
					'orders' => [],
				],
			], JSON_PRETTY_PRINT );
			die();
		}

		$responseOrders = [];
		while ( $orders->have_posts() ) {
			$orders->the_post();
			$order                = wc_get_order( get_the_ID() );
			$orderDataMapFunction = get_option( 'aftersalesprogr_order_data_mapper' );
			if ( ! function_exists( $orderDataMapFunction ) ) {
				$orderDataMapFunction = 'aftersalesprogr_map_order_data';
			}
			$responseOrders[] = ( $orderDataMapFunction )( $order );
		}

		echo json_encode( [
			'meta' => [
				'is_successful' => true,
				'message'       => 'No orders found.',
			],
			'data' => [
				'orders' => $responseOrders,
			],
		], JSON_PRETTY_PRINT );
	}

	public function complete_order( $order_id = null ) {
		$this->validateRequest();

		$envelope = [
			'meta' => [
				'is_successful' => false,
			],
			'data' => [],
		];

		$order_id = (int) $order_id;
		if ( $order_id <= 0 ) {
			$envelope['meta']['message'] = 'Wrong order_id.';
		} else {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				$envelope['meta']['message'] = 'Order not found.';
			} else {
				$envelope['meta']['is_successful'] = true;
				if ( isset( $_REQUEST['comment'] ) && $_REQUEST['comment'] != '' ) {
					$is_customer = isset( $_REQUEST['is_customer'] ) && $_REQUEST['is_customer'] == 1;
					$order->add_order_note( sanitize_text_field( $_REQUEST['comment'] ), $is_customer );
				}
				$status = sanitize_text_field( $_REQUEST['order_status'] );
				$order->update_status( $status );
			}
		}

		echo json_encode( $envelope, JSON_PRETTY_PRINT );
	}

	public function check_active_plugins( $name ): bool {
		$active_plugins = get_option( 'active_plugins' );
		foreach ( $active_plugins as $active_plugin ) {
			if ( strpos( $active_plugin, $name ) !== false ) {
				return true;
			}
		}

		return false;
	}

	public function get_order_statuses() {
		$this->validateRequest();

		$envelope = [
			'meta' => [
				'is_successful' => false,
			],
			'data' => [],
		];

		$statuses = wc_get_order_statuses();
		if ( isset( $statuses ) ) {
			$envelope['meta']['is_successful'] = true;
			$envelope['data']['statuses']      = $statuses;
		}

		echo json_encode( $envelope, JSON_PRETTY_PRINT );
	}

	public function getProductArgs() {
		$args                   = [];
		$args['posts_per_page'] = - 1;

		$args['date_query'] = [];

		/*
		 * support deprecated use of after
		 */
		$updated_at_from = $_REQUEST['updated_at_from'] ?? $_REQUEST['after'] ?? null;
		if ( isset( $updated_at_from ) ) {
			$args['date_query'][] = [
				'after'  => sanitize_text_field( $updated_at_from ),
				'column' => 'post_modified',
			];
		}

		if ( isset( $_REQUEST['updated_at_to'] ) ) {
			$args['date_query'][] = [
				'before'  => sanitize_text_field( $_REQUEST['updated_at_to'] ),
				'column' => 'post_modified',
			];
		}

		if ( isset( $_REQUEST['created_at_from'] ) ) {
			$args['date_query'][] = [
				'after'  => sanitize_text_field( $_REQUEST['created_at_from'] ),
				'column' => 'post_date',
			];
		}

		if ( isset( $_REQUEST['created_at_to'] ) ) {
			$args['date_query'][] = [
				'before'  => sanitize_text_field( $_REQUEST['created_at_to'] ),
				'column' => 'post_date',
			];
		}

		$args['post_status'] = 'any';
		if ( isset( $_REQUEST['status'] ) ) {
			$args['post_status'] = explode( ',', sanitize_text_field( $_REQUEST['status'] ) );
		}

		return $args;
	}

	public function get_products() {
		$this->validateRequest();
		$args = $this->getProductArgs();

		$envelope = [
			'meta' => [
				'is_successful' => false,
			],
			'data' => [],
		];

		$args['orderby'] = 'ID';
		$products = wc_get_products( $args );

		if (empty($products)) {
			echo json_encode( [
				'meta' => [
					'is_successful' => true,
					'message'       => 'No products found.',
				],
				'data' => [
					'products' => [],
				],
			], JSON_PRETTY_PRINT );
			die();
		}

		$responseProducts = array();

		$mapProductFunction = get_option( 'aftersalesprogr_product_mapper' );
		if ( ! function_exists( $mapProductFunction ) ) {
			$mapProductFunction = 'aftersalesprogr_map_product';
		}

		foreach ( $products as $product ) {
			$responseProducts[] = ( $mapProductFunction )( $product );
		}

		if ( ! empty( $responseProducts ) ) {
			$envelope['meta']['is_successful'] = true;
			$envelope['data']['products']      = $responseProducts;
		}

		echo json_encode( $envelope, JSON_PRETTY_PRINT );
	}

	public function get_categories() {
		$this->validateRequest();

		$envelope = [
			'meta' => [
				'is_successful' => false,
			],
			'data' => [],
		];

		$taxonomy     = 'product_cat';
		$orderby      = 'name';
		$show_count   = 0;      // 1 for yes, 0 for no
		$pad_counts   = 0;      // 1 for yes, 0 for no
		$hierarchical = 1;      // 1 for yes, 0 for no
		$title        = '';
		$empty        = 0;

		$args = array(
			'taxonomy'     => $taxonomy,
			'orderby'      => $orderby,
			'show_count'   => $show_count,
			'pad_counts'   => $pad_counts,
			'hierarchical' => $hierarchical,
			'title_li'     => $title,
			'hide_empty'   => $empty
		);

		$all_categories = get_categories( $args );

		if (empty($all_categories)) {
			echo json_encode( [
				'meta' => [
					'is_successful' => true,
					'message'       => 'No categories found.',
				],
				'data' => [
					'categories' => [],
				],
			], JSON_PRETTY_PRINT );
			die();
		}

		$envelope['meta']['is_successful'] = true;
		$envelope['data']['categories']      = $all_categories;


		echo json_encode( $envelope, JSON_PRETTY_PRINT );
	}

	public function get_version() {
		$this->validateRequest();

		$envelope = [
			'meta' => [
				'is_successful' => true,
			],
			'data' => [
				'plugin_version' => AFTERSALESPROGR_VERSION,
				'wp_version'     => get_bloginfo( 'version' ),
				'php_version'    => phpversion(),
				'wc_version'     => get_option( 'woocommerce_version' ),
				'url'            => get_bloginfo( 'wpurl' ),
				'plugins'        => get_option( 'active_plugins' ),
				'configuration'  => [
					'trackingwidget_status' => get_option('aftersalesprogr_trackingwidget_status'),
					'trackingwidget_uuid' => get_option('aftersalesprogr_trackingwidget_uuid'),
					'order_data_mapper' => get_option('aftersalesprogr_order_data_mapper'),
					'product_mapper' => get_option('aftersalesprogr_product_mapper'),
					'woocommerce_settings' => get_option('woocommerce_Aftersalespro_ShippingMethod_settings'),
				],
			],

		];
		echo json_encode( $envelope, JSON_PRETTY_PRINT );
	}
}

header( 'Content-Type: application/json' );
$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : null;
switch ( $action ):
	case 'complete_order':
		$orderId = isset( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : null;
		( new AfterSalesProGrApi() )->complete_order( $orderId );
		break;
	case 'get_orders_statuses':
		( new AfterSalesProGrApi() )->get_order_statuses();
		break;
	case 'get_products':
		( new AfterSalesProGrApi() )->get_products();
		break;
	case 'version':
		( new AfterSalesProGrApi() )->get_version();
		break;
	case 'get_orders':
		( new AfterSalesProGrApi() )->list_orders();
		break;
	case 'get_categories':
		( new AfterSalesProGrApi() )->get_categories();
		break;
	default:
		echo json_encode( [
			'meta' => [
				'is_successful' => true,
				'message'       => 'OK',
				'version'       => AFTERSALESPROGR_VERSION,
			],
		], JSON_PRETTY_PRINT );
		break;
endswitch;
