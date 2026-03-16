<?php

namespace MydPro\Includes\Ajax;

use MydPro\Includes\Create_Draft_Order as Temp_Create_Draft;
use MydPro\Includes\Repositories\Coupon_Repository;
use MydPro\Includes\Cart;
use MydPro\Includes\Myd_Currency;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create Draft Order (AJAX)
 */
class Create_Draft_Order {
	public function __construct() {
		add_action( 'wp_ajax_myd_create_draft_order', array( $this, 'create_draft_order' ) );
		add_action( 'wp_ajax_nopriv_myd_create_draft_order', array( $this, 'create_draft_order' ) );
	}

	public function create_draft_order() {
		$nonce = $_POST['sec'] ?? null;
		if ( ! $nonce || ! \wp_verify_nonce( $nonce, 'myd-create-order' ) ) {
			die( \esc_html__( 'Ops! Security check failed.', 'my-delivey-wordpress' ) );
		}

		if ( function_exists('myd_check_ip_rate_limit') && myd_check_ip_rate_limit( 'create_draft_order', 30, 60 ) ) {
			wp_send_json_error( array(
				'success' => false,
				'error'   => true,
				'message' => __( 'Muitas requisições. Tente novamente em alguns segundos.', 'myd-delivery-pro' )
			) );
			wp_die();
		}

		$data = json_decode( stripslashes( $_POST['data'] ), true );

		$cart = new Cart( $data['cart']['items'] ?? array() );

		// Validate coupon if provided
		$coupon = null;
		if ( ! empty( $data['coupon']['code'] ) ) {
			$coupon_validation = Coupon_Repository::validate_coupon( $data['coupon']['code'] );
			if ( ! $coupon_validation['valid'] ) {
				$response = array(
					'success' => false,
					'error' => true,
					'message' => __( 'Cupom inválido', 'myd-delivery-pro' ),
					'details' => $coupon_validation['errors'],
				);
				echo json_encode( $response );
				wp_die();
			}
			$coupon = $coupon_validation['coupon'];
		}

		$order = new Temp_Create_Draft( $data );

		// lightweight signature for idempotency
		$signature_source = array(
			'items' => $data['cart']['items'] ?? array(),
			'total' => $data['total'] ?? 0,
			'customer_phone' => $data['customer']['phone'] ?? '',
			'customer_email' => $data['customer']['email'] ?? '',
		);
		$order_signature = md5( wp_json_encode( $signature_source ) );

		// find recent identical draft (10 minutes)
		$found_order_id = null;
		$existing = get_posts( array(
			'post_type' => 'mydelivery-orders',
			'post_status' => 'draft',
			'posts_per_page' => 1,
			'meta_key' => 'order_signature',
			'meta_value' => $order_signature,
			'orderby' => 'date',
			'order' => 'DESC',
			'fields' => 'ids',
		) );
		if ( ! empty( $existing ) && isset( $existing[0] ) ) {
			$pid = intval( $existing[0] );
			$post = get_post( $pid );
			if ( $post ) {
				$post_time = strtotime( $post->post_date_gmt );
				if ( $post_time !== false && ( time() - $post_time ) <= 600 ) {
					$found_order_id = $pid;
				}
			}
		}

		// Determine provided id (frontend may send 'order_id' or 'id')
		$provided_order_id = null;
		if ( isset( $data['order_id'] ) && intval( $data['order_id'] ) ) {
			$provided_order_id = intval( $data['order_id'] );
		} elseif ( isset( $data['id'] ) && intval( $data['id'] ) ) {
			$provided_order_id = intval( $data['id'] );
		}

		if ( $provided_order_id ) {
			$post = get_post( $provided_order_id );
			if ( $post && $post->post_type === 'mydelivery-orders' ) {
				$order->id = $provided_order_id;
			} else {
				// provided id invalid: try signature reuse or create
				$lock_key = 'myd_order_sig_lock_' . $order_signature;
				$got_lock = false;
				if ( ! empty( $order_signature ) && add_option( $lock_key, time(), '', 'no' ) ) {
					$got_lock = true;
				}
				if ( ! $got_lock ) {
					$tries = 0;
					while ( $tries < 10 ) {
						$check = get_posts( array(
							'post_type' => 'mydelivery-orders',
							'post_status' => 'draft',
							'posts_per_page' => 1,
							'meta_key' => 'order_signature',
							'meta_value' => $order_signature,
							'orderby' => 'date',
							'order' => 'DESC',
							'fields' => 'ids',
						) );
						if ( ! empty( $check ) && isset( $check[0] ) ) {
							$order->id = intval( $check[0] );
							break;
						}
						usleep(200000);
						$tries++;
					}
				}
				if ( empty( $order->id ) ) {
					$order->create();
				}
				if ( ! empty( $got_lock ) ) {
					delete_option( $lock_key );
				}
			}
		} else {
			// No id provided: always create a new draft for this request
			// Keep only signature-based reuse for identical recent submissions
			if ( $found_order_id ) {
				$order->id = $found_order_id;
			} else {
				$lock_key = 'myd_order_sig_lock_' . $order_signature;
				$got_lock = false;
				if ( ! empty( $order_signature ) && add_option( $lock_key, time(), '', 'no' ) ) {
					$got_lock = true;
				}
				if ( ! $got_lock ) {
					$tries = 0;
					while ( $tries < 10 ) {
						$check = get_posts( array(
							'post_type' => 'mydelivery-orders',
							'post_status' => 'draft',
							'posts_per_page' => 1,
							'meta_key' => 'order_signature',
							'meta_value' => $order_signature,
							'orderby' => 'date',
							'order' => 'DESC',
							'fields' => 'ids',
						) );
						if ( ! empty( $check ) && isset( $check[0] ) ) {
							$order->id = intval( $check[0] );
							break;
						}
						usleep(200000);
						$tries++;
					}
				}
				if ( empty( $order->id ) ) {
					$order->create();
				}
				if ( ! empty( $got_lock ) ) {
					delete_option( $lock_key );
				}
			}
		}

		$order->set_type( $data['type'] ?? '' );
		$order->set_cart( $cart );
		$order->set_shipping( $data['shipping'] ?? array() );
		$order->set_customer( $data['customer'] ?? array() );
		$order->set_coupon( $coupon );
		$order->save();

		// Persist signature for future idempotency checks
		if ( ! empty( $order_signature ) && ! empty( $order->id ) ) {
			update_post_meta( $order->id, 'order_signature', sanitize_text_field( $order_signature ) );
			update_post_meta( $order->id, 'order_signature_created_at', time() );
		}

		foreach ( $order->cart->items as &$item ) {
			$item['extras'] = $order->get_filtered_extras( $item['extras'] ?? array() );
		}

		\do_action(
			'myd-delivery/order/after-create',
			array(
				'id' => $order->id,
				'data' => $order,
				'currency_code' => Myd_Currency::get_currency_code(),
			)
		);

		$response = \apply_filters( 'myd-delivery/order/after-create/ajax-response',
			array(
				'order_id' => $order->id,
				'data' => $order,
				'template' => $order->get_total_summary_template(),
			)
		);

		echo json_encode( $response, true );
		wp_die();
	}
}
