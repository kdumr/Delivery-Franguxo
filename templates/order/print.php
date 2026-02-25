<?php
/** Novo */

use MydPro\Includes\Store_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<?php if ( $orders->have_posts() ) : ?>
	<?php $currency_simbol = Store_Data::get_store_data( 'currency_simbol' ); ?>
	<?php $today = new DateTimeImmutable('now', wp_timezone()); ?>
	<?php while ( $orders->have_posts() ) : ?>
		<?php $orders->the_post(); ?>
		<?php $postid = get_the_ID(); ?>
		<?php
			// Exibir somente pedidos do dia atual, igual à lista e aos detalhes
			$order_date_raw = get_post_meta( $postid, 'order_date', true );
			if ( empty( $order_date_raw ) ) { continue; }
			try {
				$od = new DateTimeImmutable( $order_date_raw, wp_timezone() );
			} catch ( Exception $e ) {
				$od = false;
			}
			if ( ! $od || $od->format('Y-m-d') !== $today->format('Y-m-d') ) { continue; }

			$date = gmdate( 'd/m - H:i', strtotime( $order_date_raw ) );
		?>
		<?php $coupon = get_post_meta( $postid, 'order_coupon', true ); ?>
		<?php $change = get_post_meta( $postid, 'order_change', true ); ?>
		<?php $payment_type = get_post_meta( $postid, 'order_payment_type', true ); ?>
		<?php $payment_type = $payment_type === 'upon-delivery' ? __( 'Upon Delivery', 'myd-delivery-pro' ) : __( 'Payment Integration', 'myd-delivery-pro' ); ?>
		<?php $payment_status = get_post_meta( $postid, 'order_payment_status', true ); ?>
		<?php $payment_status_mapped = array(
			'waiting' => __( 'Waiting', 'myd-delivery-pro' ),
			'paid' => __( 'Pago', 'myd-delivery-pro' ),
			'failed' => __( 'Falhou', 'myd-delivery-pro' ),
		); ?>
		<?php $payment_status = $payment_status_mapped[ $payment_status ] ?? ''; ?>

		<?php
			$order_type = \get_post_meta( $postid, 'order_ship_method', true );

			$map_type = array(
				'delivery' => __( 'Delivery', 'myd-delivery-pro' ),
				'take-away' => __( 'Take Away', 'myd-delivery-pro' ),
				'order-in-store' => __( 'Delivery', 'myd-delivery-pro' ),
			);

			$order_type = $map_type[ $order_type ] ?? '';
		?>

		<div class="order-print" id="print-<?php echo esc_attr( $postid ); ?>">
			<div style="border-top: 1px dashed #000; margin: 5px 0;"></div>

			<div class="order-header">
				<?php echo esc_html( $postid ); ?> | <?php echo esc_html( $order_type ); ?>
			</div>

			<div style="border-top: 1px dashed #000; margin: 5px 0 10px 0;"></div>

			<div>
				<?php echo esc_html( $date ); ?>
			</div>

			<?php if ( ! empty( get_post_meta( $postid, 'order_ship_method', true ) ) ) : ?>
				<?php $table = get_post_meta( $postid, 'order_table', true ); ?>
				<?php $address = get_post_meta( $postid, 'order_address', true ); ?>

				<?php if ( ! empty( $table ) ) : ?>
					<div><?php echo esc_html( get_post_meta( $postid, 'order_customer_name', true ) ); ?></div>
					<div><?php echo esc_html( get_post_meta( $postid, 'customer_phone', true ) ); ?></div>
					<div><?php echo esc_html__( 'Table', 'myd-delivery-pro' ) . ' ' . esc_html( get_post_meta( $postid, 'order_table', true ) ); ?></div>';
				<?php endif; ?>

				<?php if ( ! empty( $address ) ) : ?>
					<?php
						$addr = get_post_meta( $postid, 'order_address', true );
						$num  = get_post_meta( $postid, 'order_address_number', true );
						$comp = get_post_meta( $postid, 'order_address_comp', true );
						$ref  = get_post_meta( $postid, 'order_address_reference', true );
						$num_label = $num !== '' ? $num : 'S/n°';
					?>
					<div><?php echo esc_html( get_post_meta( $postid, 'order_customer_name', true ) ); ?></div>
					<div><?php echo esc_html( get_post_meta( $postid, 'customer_phone', true ) ); ?></div>
					<div><?php echo esc_html( $addr ) . ', ' . esc_html( $num_label ) . ' | ' . esc_html( $comp ); ?></div>
					<?php if ( ! empty( $ref ) ) : ?>
					<div><?php echo esc_html__( 'Ponto de referência', 'myd-delivery-pro' ) . ': ' . esc_html( $ref ); ?></div>
					<?php endif; ?>
					<?php
						$print_neigh = get_post_meta( $postid, 'order_neighborhood', true );
						$print_real_neigh = get_post_meta( $postid, 'order_real_neighborhood', true );
						$neigh_display = esc_html( $print_neigh );
						if ( ! empty( $print_real_neigh ) ) {
							$neigh_display .= '*';
						}
					?>
					<div><?php echo $neigh_display . ' | ' . esc_html( get_post_meta( $postid, 'order_zipcode', true ) ); ?></div>
				<?php endif; ?>

				<?php if ( empty( $address ) && empty( $table ) ) : ?>
					<div><?php echo esc_html( get_post_meta( $postid, 'order_customer_name', true ) ); ?></div>
					<div><?php echo esc_html( get_post_meta( $postid, 'customer_phone', true ) ); ?></div>;
				<?php endif; ?>
			<?php endif; ?>

			<div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

			<?php $items = get_post_meta( $postid, 'myd_order_items', true ); ?>
			<?php if ( ! empty( $items ) ) : ?>
				<?php foreach ( $items as $value ) : ?>
					<div>
						<div><?php echo esc_html( $value['product_name'] ); ?></div>

						<?php if ( $value['product_extras'] !== '' ) : ?>
							<div style="white-space: pre;"><?php echo esc_html( $value['product_extras'] ); ?></div>
						<?php endif; ?>

						<?php
						// Mostrar valor de cada extra separado
						if ( ! empty( $value['extras'] ) && ! empty( $value['extras']['groups'] ) ) :
							foreach ( $value['extras']['groups'] as $group ) :
								if ( ! empty( $group['items'] ) ) :
									foreach ( $group['items'] as $extra_item ) :
										if ( (int) $extra_item['quantity'] > 0 ) :
											$extra_total = (float) $extra_item['price'] * (int) $extra_item['quantity'];
											?>
											<div><?php echo esc_html( $extra_item['name'] ); ?> (<?php echo esc_html( $extra_item['quantity'] ); ?>x) - <?php echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) ) . ' ' . number_format( $extra_total, 2, ',', '.' ); ?></div>
											<?php
										endif;
									endforeach;
								endif;
							endforeach;
						endif;
						?>

						<?php if ( ! empty( $value['product_note'] ) ) : ?>
							<div><?php echo esc_html__( 'Note', 'myd-delivery-pro' ) . ' ' . esc_html( $value['product_note'] ); ?></div>
						<?php endif; ?>

						<div><?php echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) ) . ' ' . esc_html( $value['product_price'] ); ?></div>
					</div>
					<div style="margin: 10px 0;"></div>
				<?php endforeach; ?>
			<?php endif; ?>

			<div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

			<div>
				<?php esc_html_e( 'Delivery','myd-delivery-pro'); ?>: <?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( get_post_meta( $postid, 'order_delivery_price', true ) ); ?>
			</div>

			<?php
				// Se houver desconto por fidelidade, exibe primeiro
				$fidelity_redeemed = get_post_meta( $postid, 'order_loyalty_redeemed', true );
				$fidelity_discount = get_post_meta( $postid, 'order_fidelity_discount', true );
				if ( ! empty( $fidelity_redeemed ) && (string) $fidelity_redeemed === '1' && ! empty( $fidelity_discount ) && floatval( $fidelity_discount ) > 0 ) :
			?>
				<div>
					<?php esc_html_e( 'Desconto aplicado (FIDELIDADE)', 'myd-delivery-pro' ); ?>: <span style="color: #000000ff;">-<?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( $fidelity_discount ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $coupon ) && current_user_can( 'manage_options' ) ) : ?>
				<div class="fdm-order-list-items-customer">
					<?php esc_html_e( 'Coupon code', 'myd-delivery-pro' ); ?>: <?php echo esc_html( $coupon ); ?>
				</div>
				<?php $coupon_discount = get_post_meta( $postid, 'order_coupon_discount', true ); ?>
				<?php if ( ! empty( $coupon_discount ) && floatval( $coupon_discount ) > 0 ) : ?>
					<div>
						<?php esc_html_e( 'Desconto', 'myd-delivery-pro' ); ?>: <span style="color: #000000ff;">-<?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( $coupon_discount ); ?></span>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<div>
				<?php esc_html_e( 'Total', 'myd-delivery-pro' ); ?>: <?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( get_post_meta( $postid, 'order_total', true ) ); ?>
			</div>

			<div>
				<?php esc_html_e( 'Payment Type', 'myd-delivery-pro' ); ?>: <?php echo esc_html( $payment_type ); ?>
			</div>

			<div>
				<?php esc_html_e( 'Payment Method', 'myd-delivery-pro' ); ?>: <?php echo esc_html( get_post_meta( $postid, 'order_payment_method', true ) ); ?>
			</div>

			<div class="fdm-order-list-items-customer">
				<?php esc_html_e( 'Payment Status', 'myd-delivery-pro' ); ?>:
				<?php echo esc_html( $payment_status ); ?>
			</div>

			<?php if ( ! empty( $change ) ) : ?>
				<div class="fdm-order-list-items-customer">
					<?php esc_html_e( 'Change for', 'myd-delivery-pro' ); ?>: <?php echo esc_html( $change ); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endwhile; ?>
	<?php wp_reset_postdata(); ?>
<?php endif; ?>
