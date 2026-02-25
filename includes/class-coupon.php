<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupon Class
 */
class Coupon {
	/**
	 * Id
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * Allow Types
	 *
	 * @var array
	 */
	public array $allow_types = array(
		'discount-total',
		'discount-delivery',
	);

	/**
	 * Type
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * Code
	 *
	 * @var string
	 */
	public string $code;

	/**
	 * Discount format ($ or %)
	 */
	protected array $allow_discount_formats = array(
		'amount',
		'percent',
	);

	/**
	 * Discount format ($ or %)
	 */
	public string $discount_format;

	/**
	 * Amount of discount
	 */
	public int $amount;

	/**
	 * Description
	 *
	 * @var string
	 */
	public string $description;

	/**
	 * Usage limit
	 *
	 * @var int
	 */
	public int $usage_limit;

	/**
	 * Expiry date
	 *
	 * @var string
	 */
	public string $expiry_date;

	/**
	 * Current usage count
	 *
	 * @var int
	 */
	public int $usage_count;

	/**
	 * Construct
	 */
	public function __construct( int $id = 0 ) {
		if ( empty( $id ) || $id === 0 ) {
			return; // TODO: handle error
		}

		$this->id = $id;
		$this->type = get_post_meta( $this->id, 'myd_coupon_type', true );
		$this->code = get_the_title( $id );
		$this->discount_format = get_post_meta( $this->id, 'myd_discount_format', true );
		$this->amount = get_post_meta( $this->id, 'myd_discount_value', true );
		$this->description = get_post_meta( $this->id, 'myd_coupon_description', true );
		$this->usage_limit = intval( get_post_meta( $this->id, 'myd_coupon_usage_limit', true ) );
		$this->expiry_date = get_post_meta( $this->id, 'myd_coupon_expiry_date', true );
		$this->usage_count = $this->get_current_usage_count();
	}

	/**
	 * Get current usage count for this coupon
	 *
	 * @return int
	 */
	public function get_current_usage_count() {
				global $wpdb;
				// Conta apenas cupons usados em pedidos publicados
				$count = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(pm.meta_id)
						FROM {$wpdb->postmeta} pm
						INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
						WHERE pm.meta_key = 'order_coupon'
							AND pm.meta_value = %s
							AND p.post_status = 'publish'",
						$this->code
				) );
				return intval( $count );
	}

	/**
	 * Check if coupon is valid (not expired and within usage limit)
	 *
	 * @return array
	 */
	public function is_valid() {
		$errors = array();

		// Verificar se o cupom expirou
		if ( ! empty( $this->expiry_date ) ) {
			$expiry_timestamp = strtotime( $this->expiry_date );
			$current_timestamp = current_time( 'timestamp' );
			
			if ( $current_timestamp > $expiry_timestamp ) {
				$errors[] = __( 'Cupom expirado', 'myd-delivery-pro' );
			}
		}

		// Verificar limite de uso
		if ( $this->usage_limit > 0 && $this->usage_count >= $this->usage_limit ) {
			$errors[] = __( 'Limite de uso atingido', 'myd-delivery-pro' );
		}

		return array(
			'valid' => empty( $errors ),
			'errors' => $errors
		);
	}

	/**
	 * Get coupon status information
	 *
	 * @return array
	 */
	public function get_status_info() {
		$status = array(
			'usage_info' => '',
			'expiry_info' => '',
			'is_active' => true
		);

		// Informações de uso
		if ( $this->usage_limit > 0 ) {
			$remaining = $this->usage_limit - $this->usage_count;
			$status['usage_info'] = sprintf(
				__( 'Usado %d de %d vezes (%d restantes)', 'myd-delivery-pro' ),
				$this->usage_count,
				$this->usage_limit,
				$remaining
			);
			
			if ( $remaining <= 0 ) {
				$status['is_active'] = false;
			}
		} else {
			$status['usage_info'] = sprintf(
				__( 'Usado %d vezes (ilimitado)', 'myd-delivery-pro' ),
				$this->usage_count
			);
		}

		// Informações de validade
		if ( ! empty( $this->expiry_date ) ) {
			$expiry_timestamp = strtotime( $this->expiry_date );
			$current_timestamp = current_time( 'timestamp' );
			
			if ( $current_timestamp > $expiry_timestamp ) {
				$status['expiry_info'] = sprintf(
					__( 'Expirou em %s', 'myd-delivery-pro' ),
					date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $expiry_timestamp )
				);
				$status['is_active'] = false;
			} else {
				$status['expiry_info'] = sprintf(
					__( 'Expira em %s', 'myd-delivery-pro' ),
					date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $expiry_timestamp )
				);
			}
		} else {
			$status['expiry_info'] = __( 'Sem data de expiração', 'myd-delivery-pro' );
		}

		return $status;
	}
}
