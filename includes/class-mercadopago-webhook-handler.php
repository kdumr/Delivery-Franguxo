<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Mercadopago_Webhook_Handler {

	public function __construct() {
		add_action( 'myd_mercadopago_payment_approved', [ $this, 'handle_approved_payment' ], 10, 2 );
	}

	public function handle_approved_payment( $payment_id, $action_data ) {
		// This is where you would add logic to process the approved Mercado Pago payment.
		// For example, updating order status, sending notifications, etc.
		// The $payment_id is the ID of the payment from Mercado Pago.
		// The $action_data can be the original webhook payload or the 'action' string ('payment.updated' or 'payment.created').

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[MYD][MP Webhook Handler] Payment approved: ' . $payment_id );
			error_log( '[MYD][MP Webhook Handler] Action data: ' . print_r( $action_data, true ) );
		}

		// Try to find a linked order by meta 'order_payment_dataid' or 'order_payment_dataid' == $payment_id
		$order_id = null;
		// Search by meta key which we set when creating/processing payment
		$posts = \get_posts(array(
			'post_type' => 'mydelivery-orders',
			'post_status' => array('draft','pending','publish'),
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'order_payment_dataid',
					'value' => sanitize_text_field( (string) $payment_id ),
					'compare' => '=',
				),
				array(
					'key' => 'order_payment_dataid',
					'value' => sanitize_text_field( (string) $payment_id ),
					'compare' => '=',
				),
			),
			'posts_per_page' => 1,
			'fields' => 'ids',
		));
		if ( ! empty( $posts ) && isset( $posts[0] ) ) {
			$order_id = intval( $posts[0] );
		}

		// If not found, try to match by external_reference or metadata in $action_data
		if ( empty( $order_id ) && is_array( $action_data ) ) {
			// some MP responses include metadata.order_id or external_reference
			if ( ! empty( $action_data['metadata']['order_id'] ) ) {
				$order_id = intval( $action_data['metadata']['order_id'] );
			} elseif ( ! empty( $action_data['external_reference'] ) ) {
				$candidate = filter_var( $action_data['external_reference'], FILTER_SANITIZE_NUMBER_INT );
				if ( $candidate !== '' ) { $order_id = intval( $candidate ); }
			}
		}

		if ( empty( $order_id ) ) {
			// nothing to do if we can't find an order
			if ( \defined( 'WP_DEBUG' ) && \WP_DEBUG ) {
				\error_log( '[MYD][MP Webhook Handler] No linked order found for payment ' . $payment_id );
			}
			return;
		}

		// Load post and ensure correct post type
		$post = \get_post( $order_id );
		if ( ! $post || $post->post_type !== 'mydelivery-orders' ) {
			if ( \defined( 'WP_DEBUG' ) && \WP_DEBUG ) {
				\error_log( '[MYD][MP Webhook Handler] Linked post is not a mydelivery-orders: ' . print_r( $post, true ) );
			}
			return;
		}

		// Ensure the payment_status_detail is 'accredited' (MP has finalized the payment)
		$mp_status_detail = '';
		if ( is_array( $action_data ) ) {
			$mp_status_detail = isset( $action_data['status_detail'] ) ? (string) $action_data['status_detail'] : (isset($action_data['mp_status_detail']) ? (string) $action_data['mp_status_detail'] : '');
		}
		$mp_status_detail = strtolower( trim( $mp_status_detail ) );
		if ( $mp_status_detail !== 'accredited' ) {
			if ( \defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			\error_log( '[MYD][MP Webhook Handler] Payment ' . $payment_id . ' ignored because status_detail is not accredited: ' . $mp_status_detail );
			}
			return;
		}

		// Mirror Place_Payment behavior: set payment meta and publish the order
		// If the order is already marked as paid, skip re-processing
		$current_payment_status = \get_post_meta( $order_id, 'order_payment_status', true );
		if ( ! empty( $current_payment_status ) && strtolower( $current_payment_status ) === 'paid' ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				\error_log( '[MYD][MP Webhook Handler] Order already paid, skipping: ' . $order_id );
			}
			return;
		}
		try {
			// update payment id meta (centralized helper)
			Order_Meta::set_payment_dataid( $order_id, $payment_id );
			\update_post_meta( $order_id, 'order_payment_status', 'paid' );
			// Also set order_status to 'new' at the same moment we mark payment as paid
			// Use Order_Meta::ensure_initial_status to avoid overwriting changes made by the client polling
			try {
				$pre_status = \get_post_meta( $order_id, 'order_status', true );
				error_log('[MYD][MP Webhook Handler] Pre-ensure order_status for ' . $order_id . ' is: ' . print_r($pre_status, true));
				$set_result = Order_Meta::ensure_initial_status( $order_id, 'new', array( '', 'started' ) );
				error_log('[MYD][MP Webhook Handler] ensure_initial_status returned: ' . ($set_result ? 'true' : 'false'));
			} catch ( \Throwable $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log('[MYD][MP Webhook Handler] ensure_initial_status error for ' . $order_id . ': ' . $e->getMessage());
				}
			}
			\update_post_meta( $order_id, 'order_payment_type', 'payment-integration' );

			// Publish order
			\wp_update_post( array( 'ID' => $order_id, 'post_status' => 'publish' ) );

			// Ensure an 8-digit order locator exists (compat with auto-generation in Plugin)
			$locator = \get_post_meta( $order_id, 'order_locator', true );
			if ( empty( $locator ) ) {
				$attempts = 0; $max = 30; $candidate = null;
				while ( $attempts < $max ) {
					try { $num = \random_int(0, 99999999); } catch ( \Throwable $e ) { $num = \mt_rand(0, 99999999); }
					$candidate = str_pad( (string) $num, 8, '0', STR_PAD_LEFT );
					$exists = \get_posts( array(
						'post_type' => 'mydelivery-orders',
						'post_status' => 'any',
						'fields' => 'ids',
						'posts_per_page' => 1,
						'meta_query' => array(
							array(
								'key' => 'order_locator',
								'value' => $candidate,
								'compare' => '='
							)
						)
					) );
					if ( empty( $exists ) ) break;
					$attempts++;
				}
				if ( $candidate ) {
					\update_post_meta( $order_id, 'order_locator', $candidate );
					\update_post_meta( $order_id, 'myd_order_locator', $candidate );
				}
			}

			// Clear scheduled draft deletion if any
			if ( \function_exists( 'wp_unschedule_event' ) ) {
				$timestamp = \wp_next_scheduled( 'myd_delete_draft_order', array( $order_id ) );
				if ( $timestamp ) {
					\wp_unschedule_event( $timestamp, 'myd_delete_draft_order', array( $order_id ) );
				}
			}

			// final attempt to set order_status to 'new' (guarded via ensure_initial_status)
			try {
				$pre_status = \get_post_meta( $order_id, 'order_status', true );
				error_log('[MYD][MP Webhook Handler] Pre-ensure (final) order_status for ' . $order_id . ' is: ' . print_r($pre_status, true));
				$set_result = Order_Meta::ensure_initial_status( $order_id, 'new', array( '', 'started' ) );
				error_log('[MYD][MP Webhook Handler] ensure_initial_status (final) returned: ' . ($set_result ? 'true' : 'false'));
			} catch ( \Throwable $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log('[MYD][MP Webhook Handler] ensure_initial_status (final) error for ' . $order_id . ': ' . $e->getMessage());
				}
			}
			// Ensure payment status remains 'paid' after publishing and status updates
			\update_post_meta( $order_id, 'order_payment_status', \sanitize_text_field( 'paid' ) );

			// Ensure confirmation code exists (4 digits)
			$confirmation_code = \get_post_meta( $order_id, 'order_confirmation_code', true );
			$myd_customer_id = \get_post_meta( $order_id, 'myd_customer_id', true );
			$user_code = '';
			if ( ! empty( $myd_customer_id ) ) {
				$user_code = \get_user_meta( (int) $myd_customer_id, 'myd_delivery_confirm_code', true );
			}
			if ( empty( $user_code ) && \is_user_logged_in() ) {
				$user_code = \get_user_meta( \get_current_user_id(), 'myd_delivery_confirm_code', true );
			}
			if ( ! empty( $user_code ) ) {
				$confirmation_code = $user_code;
				update_post_meta( $order_id, 'order_confirmation_code', $confirmation_code );
				update_post_meta( $order_id, 'myd_order_confirmation_code', $confirmation_code );
			} else {
				if ( empty( $confirmation_code ) ) {
					try { $num = \random_int(0, 9999); } catch ( \Throwable $e ) { $num = \mt_rand(0, 9999); }
					$confirmation_code = str_pad( (string) $num, 4, '0', STR_PAD_LEFT );
					update_post_meta( $order_id, 'order_confirmation_code', $confirmation_code );
					update_post_meta( $order_id, 'myd_order_confirmation_code', $confirmation_code );
				}
			}

				// Notify push server (if configured)
			try {
				if ( \class_exists('MydPro\\Includes\\Push\\Push_Notifier') ) {
					\MydPro\Includes\Push\Push_Notifier::notify( \get_post_meta( $order_id, 'myd_customer_id', true ), $order_id, \get_post_meta($order_id, 'order_status', true) );
				}
			} catch(\Exception $e) {}

			if ( \defined( 'WP_DEBUG' ) && \WP_DEBUG ) {
				\error_log( '[MYD][MP Webhook Handler] Order ' . $order_id . ' published due to MP payment ' . $payment_id );
			}
		} catch ( \Throwable $e ) {
			if ( \defined( 'WP_DEBUG' ) && \WP_DEBUG ) {
				\error_log( '[MYD][MP Webhook Handler] Error publishing order: ' . $e->getMessage() );
			}
		}
	}
}