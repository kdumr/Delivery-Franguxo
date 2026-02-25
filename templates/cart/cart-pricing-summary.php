<?php

use MydPro\Includes\Store_Data;
use MydPro\Includes\Myd_Store_Formatting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="myd-card__flex-row">
	<h4 id="myd-cart-payment-subtotal-label" class="myd-cart__title-inline">
		<?php esc_html_e( 'Subtotal', 'myd-delivery-pro' ); ?>
	</h4>
	<span id="myd-cart-payment-subtotal-value">
		<?php echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) . ' ' . Myd_Store_Formatting::format_price( $this->subtotal ) ); ?>
	</span>
</div>

<?php if ( isset( $this->shipping['price'] ) ) : ?>
	<div class="myd-card__flex-row">
		<h4 id="myd-cart-payment-delivery-fee-label" class="myd-cart__title-inline">
			<?php esc_html_e( 'Delivery Fee', 'myd-delivery-pro' ); ?>
		</h4>
		<span id="myd-cart-payment-delivery-fee-value">
			<?php echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) . ' ' . Myd_Store_Formatting::format_price( $this->shipping['price'] ?? 0 ) ); ?>
		</span>
	</div>
<?php endif; ?>

<?php if ( isset( $this->coupon->code ) ) : ?>
	<?php
		$coupon_value = 0.0;
		if ( isset( $this->discount_amount ) ) {
			$coupon_value = floatval( $this->discount_amount );
		}
		if ( $coupon_value <= 0 && isset( $this->coupon->amount ) ) {
			$parse_number = function( $v ) {
				$v = str_replace( array( 'R$', ' ' ), '', (string) $v );
				$v = str_replace( ',', '.', str_replace( '.', '', $v ) );
				return floatval( $v );
			};
			$shipping_price = $parse_number( $this->shipping['price'] ?? 0 );
			$base_total = floatval( $this->subtotal ) + $shipping_price;
			if ( isset( $this->coupon->type ) && $this->coupon->type === 'discount-delivery' ) {
				if ( isset( $this->coupon->discount_format ) && $this->coupon->discount_format === 'amount' ) {
					$coupon_value = min( floatval( $this->coupon->amount ), $shipping_price );
				} else {
					$coupon_value = ( floatval( $this->coupon->amount ) * $shipping_price ) / 100.0;
				}
			} else {
				if ( isset( $this->coupon->discount_format ) && $this->coupon->discount_format === 'amount' ) {
					$coupon_value = min( floatval( $this->coupon->amount ), $base_total );
				} else {
					$coupon_value = ( floatval( $this->coupon->amount ) * $base_total ) / 100.0;
				}
			}
		}
	?>
	<?php if ( $coupon_value > 0 ) : ?>
		<div class="myd-card__flex-row">
			<h4 id="myd-cart-payment-coupon-label" class="myd-cart__title-inline">
				<?php esc_html_e( 'Coupon', 'myd-delivery-pro' ); ?> <?php echo esc_html( $this->coupon->code ); ?>
			</h4>
			<span id="myd-cart-payment-coupon-value" style="color:#28a745;">
				-<?php echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) . ' ' . Myd_Store_Formatting::format_price( $coupon_value ) ); ?>
			</span>
		</div>
	<?php else : ?>
		<div class="myd-card__flex-row">
			<h4 id="myd-cart-payment-coupon-label" class="myd-cart__title-inline">
				<?php esc_html_e( 'Coupon', 'myd-delivery-pro' ); ?>
			</h4>
			<span id="myd-cart-payment-coupon-value">
				<?php echo esc_html( $this->coupon->code ); ?>
			</span>
		</div>
	<?php endif; ?>
<?php endif; ?>

<?php
// Calcular desconto de fidelidade quando aplicável
$loyalty_discount = 0.0;
$loyalty_active = get_option( 'myd_fidelidade_ativo', 'off' );
if ( $loyalty_active === 'on' && is_user_logged_in() ) {
	$user_id = get_current_user_id();
	$last_reset = get_user_meta( $user_id, 'myd_loyalty_reset_at', true );
	$ltipo = get_option( 'myd_fidelidade_tipo', 'loyalty_value' );
	$lpontos = intval( get_option( 'myd_fidelidade_pontos_necessarios', 0 ) );
	$lvalor_raw = get_option( 'myd_fidelidade_valor', '' );
	$lquant = intval( get_option( 'myd_fidelidade_quantidade', 0 ) );
	$lpremio_tipo = get_option( 'myd_fidelidade_premio_tipo', 'percent' );
	$lpremio_percent = get_option( 'myd_fidelidade_premio_percent', '' );
	$lpremio_fixo = get_option( 'myd_fidelidade_premio_fixo', '' );

	// helper local: parse formatted currency string to float
	$parse_currency = function( $v ) {
		$v = str_replace( array( 'R$', ' ' ), '', $v );
		$v = str_replace( ',', '.', str_replace( '.', '', $v ) );
		return floatval( $v );
	};

	// obter pedidos do usuário para verificar elegibilidade
	if ( $ltipo === 'loyalty_value' ) {
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
		$points_count = 0;
		foreach ( $orders as $o ) {
			// Ignorar pedidos anteriores ao último resgate
			if ( ! empty( $last_reset ) ) {
				$ot = strtotime( $o->post_date );
				$rt = strtotime( $last_reset );
				if ( $ot <= $rt ) continue;
			}
			$total = get_post_meta( $o->ID, 'myd_order_total', true );
			if ( empty( $total ) || $total == '0' ) $total = get_post_meta( $o->ID, 'order_total', true );
			if ( empty( $total ) || $total == '0' ) $total = get_post_meta( $o->ID, 'total', true );
			if ( empty( $total ) || $total == '0' ) $total = get_post_meta( $o->ID, 'myd_total', true );
			if ( empty( $total ) || $total == '0' ) $total = get_post_meta( $o->ID, 'fdm_order_total', true );
			$num = $parse_currency( $total );
			$sum += $num;
		}
		$target = 0.0;
		if ( ! empty( $lvalor_raw ) ) {
			$target = $parse_currency( $lvalor_raw );
		}
		$eligible = false;
		if ( $target > 0 ) {
			if ( $lpontos > 0 ) {
				// Usa o valor persistido em usermeta `myd_loyalty_points`
				$points_count = intval( get_user_meta( $user_id, 'myd_loyalty_points', true ) );
				// if expires_at exists and passed, consider 0
				$expires_at = get_user_meta( $user_id, 'myd_loyalty_expires_at', true );
				if ( ! empty( $expires_at ) ) {
					$exp_ts = strtotime( $expires_at );
					if ( $exp_ts !== false && $exp_ts <= (int) current_time( 'timestamp' ) ) {
						$points_count = 0;
					}
				}
				if ( $points_count >= $lpontos ) $eligible = true;
			} else {
				if ( $sum >= $target ) $eligible = true;
			}
		}
	}

	if ( ! empty( $eligible ) ) {
		// calcular valor do prêmio
		if ( $lpremio_tipo === 'percent' ) {
			$pct = rtrim( trim( $lpremio_percent ), '%' );
			$pctv = floatval( $pct );
			if ( $pctv > 0 ) {
				// aplicar sobre subtotal (evita recalcular impostos/shipping incorretamente)
				$base = floatval( $this->subtotal );
				$loyalty_discount = round( ( $pctv / 100.0 ) * $base, 2 );
			}
		} else {
			$fixed = $parse_currency( $lpremio_fixo );
			$loyalty_discount = round( floatval( $fixed ), 2 );
		}
		// Não pode exceder o subtotal
		if ( $loyalty_discount > floatval( $this->subtotal ) ) {
			$loyalty_discount = floatval( $this->subtotal );
		}
	}
}
?>

<?php if ( $loyalty_discount > 0 ) : ?>
	<div class="myd-card__flex-row">
		<h4 class="myd-cart__title-inline">Desconto de fidelidade</h4>
		<span id="myd-cart-payment-fidelity-discount" style="color:#28a745;">-<?php echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) . ' ' . Myd_Store_Formatting::format_price( $loyalty_discount ) ); ?></span>
	</div>
<?php endif; ?>

<div class="myd-card__flex-row">
	<h4 id="myd-cart-payment-total-label" class="myd-cart__title-inline">
		<?php esc_html_e( 'Total', 'myd-delivery-pro' ); ?>
	</h4>
	<span id="myd-cart-payment-total-value">
		<?php
			$display_total = floatval( $this->total ) - floatval( $loyalty_discount );
			if ( $display_total < 0 ) $display_total = 0;
			echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) . ' ' . Myd_Store_Formatting::format_price( $display_total ) );
		?>
	</span>
</div>
