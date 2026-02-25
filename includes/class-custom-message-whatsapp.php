<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Message to be used on WhatsApp redirect message
 */
class Custom_Message_Whatsapp {
	/**
	 * Order ID
	 *
	 * @var integer
	 */
	protected int $order_id;

	/**
	 * Order data
	 *
	 * @var array
	 */
	protected array $order_data;

	/**
	 * Construct
	 */
	public function __construct( int $order_id ) {
		if ( empty( $order_id ) ) {
			return; // handle error
		}

		$this->order_id = $order_id;
		$this->get_order_data();
	}

	/**
	 * Get order data
	 *
	 * @return void
	 */
	protected function get_order_data() : void {
		$this->order_data['type'] = \get_post_meta( $this->order_id, 'order_ship_method', true );
		$this->order_data['date'] = \get_post_meta( $this->order_id, 'order_ship_method', true );
		$this->order_data['items'] = \MydPro\Includes\Myd_Orders_Front_Panel::parse_order_items( \get_post_meta( $this->order_id, 'myd_order_items', true ) );
		// Do NOT include coupon codes in outbound messages or external redirects.
		// Always blank the coupon code to avoid exposing it outside admin interfaces.
		$this->order_data['coupon_code'] = '';
		$this->order_data['total'] = \get_post_meta( $this->order_id, 'order_total', true );
		$this->order_data['subtotal'] = \get_post_meta( $this->order_id, 'order_subtotal', true );
		$this->order_data['payment_type'] = \get_post_meta( $this->order_id, 'order_payment_type', true );
		$this->order_data['payment_status'] = \get_post_meta( $this->order_id, 'order_payment_status', true );
		$this->order_data['payment_method'] = \get_post_meta( $this->order_id, 'order_payment_method', true );
		$this->order_data['payment_change'] = \get_post_meta( $this->order_id, 'order_change', true );
		$this->order_data['customer_name'] = \get_post_meta( $this->order_id, 'order_customer_name', true );
		$this->order_data['first_customer_name'] = explode(' ', trim($this->order_data['customer_name']))[0] ?? $this->order_data['customer_name'];
		$this->order_data['customer_phone'] = \get_post_meta( $this->order_id, 'customer_phone', true );
		$this->order_data['address_street'] = \get_post_meta( $this->order_id, 'order_address', true );
		$this->order_data['address_number'] = \get_post_meta( $this->order_id, 'order_address_number', true );
		$this->order_data['address_complement'] = \get_post_meta( $this->order_id, 'order_address_comp', true );
		$this->order_data['address_neighborhood'] = \get_post_meta( $this->order_id, 'order_neighborhood', true );
		$this->order_data['address_zipcode'] = \get_post_meta( $this->order_id, 'order_zipcode', true );
		$this->order_data['shipping_price'] = \get_post_meta( $this->order_id, 'order_delivery_price', true );
		$this->order_data['shipping_table'] = \get_post_meta( $this->order_id, 'order_table', true );
	}

	/**
	 * Get formated extras - legacy and temp. function to be removed when this class is formated
	 *
	 * @param array $extras
	 * @return string
	 */
	private function get_formated_extras( array $extras ) : string {
		if ( empty( $extras['groups'] ) ) {
			return '';
		}

		$formated_extras = array();
		foreach ( $extras['groups'] as $group ) {
			$formated_extras[] = $group['group'] . ':' . PHP_EOL . implode( PHP_EOL, array_column( $group['items'], 'name' ) ) . PHP_EOL;
		}

		return implode( PHP_EOL, $formated_extras );
	}

	/**
	 * Get whatsApp redirect link
	 *
	 * @return string
	 */
	public function get_whatsapp_redirect_link() : string {
		$whatsapp_number = get_option( 'myd-business-whatsapp' );
		$whatsapp_number = str_replace( array( '+', ' ' ), '', $whatsapp_number );
		$products = array();
		$product_extras = array();

		foreach ( $this->order_data['items'] as $key => $v ) {
			if ( ! empty( $v['extras'] ) ) {
				$product_extras[] = $this->get_formated_extras( $v['extras'] );
			}

			$maybe_break_line = $key !== 0 ? PHP_EOL . PHP_EOL : '';

			$products[] = '' . $maybe_break_line . $this->convert_order_product_list( $v['quantity'], \get_the_title( $v['id'] ), $v['total'], $product_extras, $v['note'] ) . '';
			$product_extras = array();
		}

		$message = $this->convert_custom_message( $products );
		$message = urlencode( html_entity_decode( $message, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

		return 'https://wa.me/' . $whatsapp_number . '?text=' . $message . '';
	}

	/**
	 * Convert Custom Message
	 *
	 * @return string
	 */
	protected function convert_custom_message( $products ) : string {
		$available_tokens = array(
			'{order-number}' => $this->order_id,
			'{order-date-time}' => $this->order_data['date'],
			'{order-coupon-code}' => $this->order_data['coupon_code'],
			'{order-total}' => Store_Data::get_store_data( 'currency_simbol' ) . ' ' . $this->order_data['total'],
			'{order-subtotal}' => Store_Data::get_store_data( 'currency_simbol' ) . ' ' . $this->order_data['subtotal'],
			'{order-products}' => implode( $products ),
			'{order-table}' => $this->order_data['shipping_table'],
			'{order-track-page}' => \get_permalink( \get_option( 'fdm-page-order-track' ) ) . '?hash=' . base64_encode( $this->order_id ),
			'{payment-type}' => $this->order_data['payment_type'],
			'{payment-status}' => $this->order_data['payment_status'],
			'{payment-method}' => $this->order_data['payment_method'],
			'{payment-change}' => Store_Data::get_store_data( 'currency_simbol' ) . ' ' . $this->order_data['payment_change'],
			'{customer-name}' => $this->order_data['customer_name'],
			'{first-customer-name}' => $this->order_data['first_customer_name'],
			'{customer-phone}' => $this->order_data['customer_phone'],
			'{customer-address}' => $this->order_data['address_street'],
			'{customer-address-number}' => $this->order_data['address_number'],
			'{customer-address-complement}' => $this->order_data['address_complement'],
			'{customer-address-neighborhood}' => $this->order_data['address_neighborhood'],
			'{customer-address-zipcode}' => $this->order_data['address_zipcode'],
			'{shipping-price}' => Store_Data::get_store_data( 'currency_simbol' ) . ' ' . $this->order_data['shipping_price'],
		);

		$order_type = $this->order_data['type'];

		$messages_by_type = array(
			'delivery' => 'myd-template-order-custom-message-delivery',
			'take-away' => 'myd-template-order-custom-message-take-away',
			'order-in-store' => 'myd-template-order-custom-message-digital-menu',
		);

		$message = get_option( $messages_by_type[ $order_type ] );

		foreach ( $available_tokens as $token => $order_data ) {
			$message = str_replace( $token, $order_data, $message );
		}

		return $message;
	}

	/**
	 * Convert Custom Message
	 *
	 * @return string
	 */
	protected function convert_order_product_list( $product_quantity, $product_name, $product_price, $product_extras, $product_note ) : string {
		$available_tokens = array(
			'{product-qty}' => $product_quantity,
			'{product-name}' => $product_name,
			'{product-price}' => $product_price,
			'{product-extras}' => is_array( $product_extras ) ? implode( $product_extras ) : '',
			'{product-note}' => $product_note,
		);

		$message = \get_option( 'myd-template-order-custom-message-list-products' );

		foreach ( $available_tokens as $token => $order_data ) {
			$message = str_replace( $token, $order_data, $message );
		}

		return $message;
	}
}
