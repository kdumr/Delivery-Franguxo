<?php

namespace MydPro\Includes\Ajax;

use MydPro\Includes\Cart;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update Cart Class
 */
class Update_Cart {
	/**
	 * Construct
	 */
	public function __construct() {
		add_action( 'wp_ajax_myd_update_cart', array( $this, 'update_cart' ) );
		add_action( 'wp_ajax_nopriv_myd_update_cart', array( $this, 'update_cart' ) );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function update_cart() {
		$nonce = $_POST['sec'] ?? null;
		if ( ! $nonce || ! \wp_verify_nonce( $nonce, 'myd-create-order' ) ) {
			die( \esc_html__( 'Ops! Security check failed.', 'my-delivey-wordpress' ) );
		}

		$data = json_decode( stripslashes( $_POST['data'] ), true );
		$cart = new Cart( $data['items'] );
		$response = array(
			'cart' => $cart,
			'template' => $cart->get_cart_list_template(),
		);
		echo json_encode( $response, true );
		wp_die();
	}
}
