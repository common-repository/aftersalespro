<?php

if ( ! function_exists( 'aftersalesprogr_map_order_data' ) ) {
	function aftersalesprogr_map_order_data( $order ):array {
		$order_data                = $order->get_data();
		$order_shipping_first_name = $order_data['shipping']['first_name'];
		$order_shipping_last_name  = $order_data['shipping']['last_name'];
		$cod_total                 = $order_data['payment_method'] == 'cod' ? $order_data['total'] : 0;

		$is_archived  = $order_data['status'] == 'wc-on-hold';
		$is_cancelled = in_array( $order_data['status'], [ 'wc-cancelled', 'wc-refunded', 'wc-failed' ] );
		$is_shipped   = $order_data['status'] == 'wc-completed';

		$shipping_method = $order->get_shipping_method();

		$information = [];
		$isBoxNow    = strpos( strtoupper( $shipping_method ), 'BOX' ) !== false;
		$boxNowField = $order->get_meta( '_boxnow_locker_id' );
		if ( $boxNowField != '' && $isBoxNow ) {
			$information = [
				'information' => [
					'agency'      => 'boxnowgr',
					'locker_id'   => $order->get_meta( '_boxnow_locker_id' ),
					'boxNowField' => $boxNowField,
				]
			];
		}

		$acsCourierField = $order->get_meta( 'acs_pp_point' );
		if ( $acsCourierField != '' ) {
			$acsCourierFieldData = json_decode( $acsCourierField, true );
			$information         = [
				'information' => [
					'agency'          => 'acs_courier',
					'locker_id'       => $acsCourierFieldData['id'],
					'acsCourierField' => $acsCourierField,
				],
			];
		}

		$itemData = [];
		$items = [];
		foreach ( $order->get_items() as $item ) {
			$item_data  = $item->get_data();
			$product = wc_get_product($item_data['product_id']);
			if ($product) {
				$productData = $product->get_data();
			$items[] = $item_data + [
					'sku' => $productData['sku'],
					'product' => $productData,
			];
			}
			$itemData[] = [
				'id'           => $item_data['product_id'],
				'name'         => $item_data['name'],
				'quantity'     => $item_data['quantity'],
				'price'        => $item_data['subtotal'],
				'total'        => $item_data['total'],
				'variation_id' => $item_data['variation_id'],
			];
		}

		$meta = [
			'meta' => [
				'product_str' => $itemData ?? null,
			]
		];

		return [
			       'primary_id'            => $order->get_order_number(),
			       'secondary_id'          => get_the_ID(),
			       'status'                => $order_data['status'],
			       'weight'                => aftersalesprogr_get_order_weight( get_the_ID() ),
			       'items'                 => null,
			       'payment_method'        => $order_data['payment_method'],
			       'payment_label'         => $order_data['payment_method_title'],
			       'shipping_method'       => $shipping_method,
			       'shipping_label'        => $shipping_method,
			       'cod_total'             => $cod_total,
			       'order_total'           => $order_data['total'],
			       'notes'                 => $order->get_customer_note(),
			       'recipient_name'        => implode(' ', [
						$order_shipping_last_name,
						$order_shipping_first_name,
						$order_data['shipping']['company'],
			       ]),
			       'recipient_address_str' => implode(' ', [
				       $order_data['shipping']['address_1'],
				       $order_data['shipping']['address_2'],
			       ]),
			       'recipient_zipcode'     => $order_data['shipping']['postcode'],
			       'recipient_area'        => $order_data['shipping']['city'],
			       'recipient_phone'       => $order_data['shipping']['phone'],
			       'recipient_mobile'      => $order_data['billing']['phone'],
			       'recipient_email'       => $order_data['billing']['email'],
			       'recipient_country'     => $order_data['shipping']['country']
			                                  ?? $order_data['billing']['country'] ?? 'GR',
			       'is_archived'           => $is_archived,
			       'is_cancelled'          => $is_cancelled,
			       'is_shipped'            => $is_shipped,
			       'needs_update'          => false,
			       'platform_created_at'   => isset($order_data['date_created'])
				       ? $order_data['date_created']->date( 'Y-m-d H:i:s' ) : null,
			       'platform_updated_at'   => isset($order_data['date_modified'])
				       ? $order_data['date_modified']->date( 'Y-m-d H:i:s' ) : null,
			       'raw'                   => [
					   'order' => $order_data,
				       'items' => $items,
			       ],
		       ] + $information + $meta;
	}
}
if ( ! function_exists( 'aftersalesprogr_get_order_weight' ) ) {
	function aftersalesprogr_get_order_weight( $order_id ) :float {
		$order        = wc_get_order( $order_id );
		$order_items  = $order->get_items();
		$total_weight = 0;

		foreach ( $order_items as $product_item ) {
			$product = $product_item->get_product();
			if ( ! $product ) {
				continue;
			}
			$product_weight = (int) $product->get_weight();
			$quantity       = (int) $product_item->get_quantity();

			$total_weight += floatval( $product_weight * $quantity );
		}

		return $total_weight;
	}
}

if ( ! function_exists( 'aftersalesprogr_map_product' ) ) {
	function aftersalesprogr_map_product( $product ):array {
		$productData = $product->get_data();

		return [
			'sku'               => $productData['sku'],
			'name'              => $productData['name'],
			'price'             => $productData['price'],
			'special_price'     => intval( $productData['sale_price'] ),
			'status'            => $productData['stock_status'] === "instock",
			'weight'            => intval( $productData['weight'] ) ?? null,
			'categories'        => array_values( $productData['category_ids'] ),
			'link'              => get_permalink( $productData['id'] ),
			'images'            => wp_get_attachment_url( $productData['image_id'] ) ?? null,
			'qty'               => intval( $productData['stock_quantity'] ),
			'source_id'         => $productData['id'],
			'source_created_at' => $productData['date_created'] ?? null,
			'source_updated_at' => $productData['date_modified'] ?? null
		];
	}
}
