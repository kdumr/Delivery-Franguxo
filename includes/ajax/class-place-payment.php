<?php

namespace MydPro\Includes\Ajax;

use MydPro\Includes\Custom_Message_Whatsapp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Place Payment
 */
class Place_Payment {
	/**
	 * Construct
	 */
	public function __construct() {
		\add_action( 'wp_ajax_myd_order_place_payment', array( $this, 'place_payment' ) );
		\add_action( 'wp_ajax_nopriv_myd_order_place_payment', array( $this, 'place_payment' ) );
	}

	/**
	 * Place Payment function
	 *
	 * @return void
	 */
	public function place_payment() {
		$nonce = $_POST['sec'] ?? null;
		if ( ! $nonce || ! \wp_verify_nonce( $nonce, 'myd-create-order' ) ) {
			die( \esc_html__( 'Ops! Security check failed.', 'my-delivey-wordpress' ) );
		}

		$data = json_decode( stripslashes( $_POST['data'] ), true );
		$order_id = (int) $data['id'];
		$payment = $data['payment'];

		// Verifica e aplica desconto de fidelidade se elegível antes de publicar o pedido
		try {
			$loyalty_discount = 0.0;
			$apply_loyalty = false;
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
				$loyalty_active = get_option( 'myd_fidelidade_ativo', 'off' );
				if ( $loyalty_active === 'on' ) {
					$ltipo = get_option( 'myd_fidelidade_tipo', 'loyalty_value' );
					$lpontos = intval( get_option( 'myd_fidelidade_pontos_necessarios', 0 ) );
					$lvalor_raw = get_option( 'myd_fidelidade_valor', '' );
					$lquant = intval( get_option( 'myd_fidelidade_quantidade', 0 ) );
					$lpremio_tipo = get_option( 'myd_fidelidade_premio_tipo', 'percent' );
					$lpremio_percent = get_option( 'myd_fidelidade_premio_percent', '' );
					$lpremio_fixo = get_option( 'myd_fidelidade_premio_fixo', '' );

					$parse_currency = function( $v ) {
						$v = str_replace( array( 'R$', ' ' ), '', $v );
						$v = str_replace( ',', '.', str_replace( '.', '', $v ) );
						return floatval( $v );
					};

					if ( $ltipo === 'loyalty_value' ) {
						// busca pedidos do usuário para computar pontos/total
						$orders = get_posts( array(
							'post_type' => 'mydelivery-orders',
							'posts_per_page' => -1,
							'post_status' => 'publish',
							'meta_query' => array(
								array('key' => 'myd_customer_id','value' => $user_id,'compare' => '='),
								array('key' => 'order_status','value' => 'draft','compare' => '!=')
							)
						) );
						$sum = 0.0;
						// pontos agora persistidos no usermeta
						$points_count = intval( get_user_meta( $user_id, 'myd_loyalty_points', true ) );
						$target = 0.0;
						if ( ! empty( $lvalor_raw ) ) {
							$target = $parse_currency( $lvalor_raw );
						}
						$eligible = false;
						if ( $target > 0 ) {
							if ( $lpontos > 0 ) {
								// Usa o valor persistido em usermeta
								if ( $points_count >= $lpontos ) $eligible = true;
							} else {
								if ( $sum >= $target ) $eligible = true;
							}
						}
					}
					if ( ! empty( $eligible ) ) {
						// compute discount amount
						if ( $lpremio_tipo === 'percent' ) {
							$pct = rtrim( trim( $lpremio_percent ), '%' );
							$pctv = floatval( $pct );
							if ( $pctv > 0 ) {
								$base = floatval( get_post_meta( $order_id, 'order_subtotal', true ) ?: 0 );
								$loyalty_discount = round( ( $pctv / 100.0 ) * $base, 2 );
							}
						} else {
							$fixed = $parse_currency( $lpremio_fixo );
							$loyalty_discount = round( floatval( $fixed ), 2 );
						}
						if ( $loyalty_discount > 0 ) {
							// cap to subtotal
							$sub = floatval( get_post_meta( $order_id, 'order_subtotal', true ) ?: 0 );
							if ( $loyalty_discount > $sub ) $loyalty_discount = $sub;
							$apply_loyalty = true;
						}
					}
				}
			}
		} catch ( \Throwable $e ) {
			// não bloquear fluxo de pedido se algo falhar aqui
		}

		\update_post_meta( $order_id, 'order_change', \sanitize_text_field( $payment['change'] ?? '' ) );

		// If frontend provided a payment method or payment details, mark as integration
		// We also accept payment.type === 'payment-integration', but many frontends don't set it
		$methodProvided = ! empty( $payment['method'] );
		$detailsProvided = ! empty( $payment['details'] ) && is_array( $payment['details'] );
		$typeIsIntegration = isset( $payment['type'] ) && $payment['type'] === 'payment-integration';
		if ( $typeIsIntegration || $methodProvided || $detailsProvided ) {
			$method = sanitize_text_field( $payment['method'] ?? 'payment-integration' );

			// If payment details include an external payment id, persist it as well
			if ( $detailsProvided ) {
				$possible = array( 'id', 'paymentId', 'payment_id' );
					foreach ( $possible as $k ) {
						if ( ! empty( $payment['details'][ $k ] ) ) {
							\MydPro\Includes\Order_Meta::set_payment_dataid( $order_id, $payment['details'][ $k ] );
							break;
						}
					}
			}

			// Persist selected payment method and type for upon-delivery flows or integrations
			// If frontend explicitly provided a type, use it; otherwise infer 'upon-delivery' when not integration
			$payment_type_to_store = 'upon-delivery';
			if ( isset( $payment['type'] ) && $payment['type'] === 'payment-integration' ) {
				$payment_type_to_store = 'payment-integration';
			} elseif ( isset( $payment['type'] ) && $payment['type'] === 'upon-delivery' ) {
				$payment_type_to_store = 'upon-delivery';
			} elseif ( $typeIsIntegration ) {
				$payment_type_to_store = 'payment-integration';
			}

			update_post_meta( $order_id, 'order_payment_method', $method );
			update_post_meta( $order_id, 'order_payment_type', $payment_type_to_store );
		}

		// Debug: if neither type nor method/details were provided for integration flows, log payload for diagnosis
		if ( ! $typeIsIntegration && ! $methodProvided && ! $detailsProvided && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log('[MYD][PlacePayment] Integration payment called but no method/type/details present. Payload: ' . print_r( $payment, true ));
		}

		$payment_error = array();
		if ( $payment['type'] === 'payment-integration' ) {
			$payment_error = \apply_filters( 'myd-delivery/order/validate-payment-integration', array(), $order_id );
		}

		$order_track_link = \get_permalink( \get_option( 'fdm-page-order-track' ) ) . '?hash=' . base64_encode( $order_id );

		$whatsapp_link = new Custom_Message_Whatsapp( $order_id );
		$whatsapp_link = $whatsapp_link->get_whatsapp_redirect_link();

		\do_action(
			'myd-delivery/order/after-place-payment',
			array(
				'id' => $order_id,
			)
		);

		if ( ! empty( $payment_error ) ) {
			$response_object = array(
				'order_id' => $order_id,
				'error' => $payment_error,
			);
		} else {
			// Se aplicável, persiste desconto de fidelidade e ajusta total
			if ( ! empty( $apply_loyalty ) && $loyalty_discount > 0 ) {
				// salva pontos anteriores do usuário para possível restauração se pedido for cancelado
				$cid_for_reset = get_post_meta( $order_id, 'myd_customer_id', true );
				if ( ! empty( $cid_for_reset ) ) {
					$prev_points = intval( get_user_meta( (int) $cid_for_reset, 'myd_loyalty_points', true ) );
					update_post_meta( $order_id, 'order_loyalty_points_prev', $prev_points );
				}
				update_post_meta( $order_id, 'order_loyalty_redeemed', '1' );
				update_post_meta( $order_id, 'order_fidelity_discount', sanitize_text_field( \MydPro\Includes\Myd_Store_Formatting::format_price( $loyalty_discount ) ) );
				// ajustar order_total
				$orig_total = floatval( get_post_meta( $order_id, 'order_total', true ) ?: 0 );
				$new_total = round( max( 0, $orig_total - $loyalty_discount ), 2 );
				update_post_meta( $order_id, 'order_total', \MydPro\Includes\Myd_Store_Formatting::format_price( $new_total ) );
			}

			\wp_update_post(
				array(
					'ID' => $order_id,
					'post_status' => 'publish',
				)
			);
			// Clear scheduled draft deletion if any
			if ( function_exists( 'wp_unschedule_event' ) ) {
				$timestamp = wp_next_scheduled( 'myd_delete_draft_order', array( $order_id ) );
				if ( $timestamp ) {
					wp_unschedule_event( $timestamp, 'myd_delete_draft_order', array( $order_id ) );
				}
			}
			   // Só seta 'new' se status atual for vazio ou 'started'
			   \MydPro\Includes\Order_Meta::ensure_initial_status($order_id, 'new', array('', 'started'));

			// Pontos de fidelidade: o crédito é realizado apenas quando o pedido é confirmado.
			// Lógica de adição de pontos foi movida para o handler de mudança de status do pedido.

			// Ensure confirmation code exists (4 digits) for finished screen
			$confirmation_code = \get_post_meta( $order_id, 'order_confirmation_code', true );
			// Prefer user code (overwrite) if available
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
				\update_post_meta( $order_id, 'order_confirmation_code', $confirmation_code );
				\update_post_meta( $order_id, 'myd_order_confirmation_code', $confirmation_code );
			} else {
				// If still empty, ensure we have a 4-digit code
				if ( empty( $confirmation_code ) ) {
					try { $num = random_int(0, 9999); } catch ( \Throwable $e ) { $num = mt_rand(0, 9999); }
					$confirmation_code = str_pad( (string) $num, 4, '0', STR_PAD_LEFT );
					\update_post_meta( $order_id, 'order_confirmation_code', $confirmation_code );
					\update_post_meta( $order_id, 'myd_order_confirmation_code', $confirmation_code );
				}
			}

			$response_object = array(
				'id' => $order_id,
				'whatsappLink' => $whatsapp_link,
				'orderTrackLink' => $order_track_link,
				'confirmationCode' => $confirmation_code,
			);

			// Se aplicou resgate de fidelidade, registra timestamp de reset para o usuário
			if ( ! empty( $apply_loyalty ) && $loyalty_discount > 0 ) {
				$cid = get_post_meta( $order_id, 'myd_customer_id', true );
				if ( ! empty( $cid ) ) {
					// reduz pontos do usuário (consome pontos necessários)
					$needed = intval( get_option( 'myd_fidelidade_pontos_necessarios', 0 ) );
					$prev = intval( get_user_meta( (int) $cid, 'myd_loyalty_points', true ) );
					if ( $needed > 0 ) {
						$remaining = max( 0, $prev - $needed );
					} else {
						$remaining = 0; // if not using points, reset progress
					}
					update_user_meta( (int) $cid, 'myd_loyalty_points', $remaining );
					// manter compatibilidade: registra timestamp de reset também
					update_user_meta( (int) $cid, 'myd_loyalty_reset_at', current_time( 'mysql' ) );
				}
			}
		}

		$response = \apply_filters( 'myd-delivery/order/place-payment/ajax-response', $response_object );

		echo json_encode( $response, true );
		\wp_die();
	}
}
