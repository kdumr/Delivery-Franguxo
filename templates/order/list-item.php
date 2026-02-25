<?php 
/**
 * Template part for displaying order list items
 *
 * @package FDM
 */

$status_data = fdm_get_order_status( $order->get_status() );
$status      = $order->get_status();
?>
<div class="fdm-order-list-item">
	<div class="fdm-order-list-item-inner">
		<?php
		// compute minutes since order creation (fallback robust)
		$order_ts = 0;
		$order_date_meta = get_post_meta( $order->get_id(), 'order_date', true );
		if ( ! empty( $order_date_meta ) ) {
			try {
				$dt = new DateTimeImmutable( $order_date_meta, wp_timezone() );
				$order_ts = (int) $dt->getTimestamp();
			} catch ( Exception $e ) {
				$order_ts = (int) strtotime( $order_date_meta );
			}
		}
		if ( $order_ts <= 0 ) $order_ts = time();
		$now_ts = time();
		$minutes = (int) floor( max(0, $now_ts - $order_ts) / 60 );
		$avg_prep = (int) get_option( 'myd-average-preparation-time', 30 );
		if ( $avg_prep <= 0 ) $avg_prep = 30;
		$part = (int) floor( $avg_prep / 3 ); if ( $part < 1 ) $part = 1;
		if ( $minutes >= $avg_prep ) {
			$time_background = '#ea1d2b';
		} elseif ( $minutes > $part ) {
			$time_background = '#d8800d';
		} else {
			$time_background = '#2c9b2c';
		}
		?>
		<div class="fdm-order-list-items-status" style="background: <?php echo esc_attr( $time_background ); ?>; <?php if ( in_array( $status, array( 'done', 'finished', 'canceled', 'refunded' ) ) ) { echo 'display: none;'; } ?>">
			<div class="myd-order-minutes"><?php echo intval( $minutes ); ?></div>
			<div class="myd-order-minutes-unit">min</div>
		</div>
		<div class="fdm-order-list-items-details">
			<div class="fdm-order-list-items-title">
				<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
					<?php echo esc_html( $order->get_order_number() ); ?>
				</a>
			</div>
			<div class="fdm-order-list-items-meta">
				<?php
				$items      = $order->get_items();
				$item_count = count( $items );
				if ( $item_count > 1 ) {
					echo esc_html( $item_count ) . ' ' . esc_html__( 'items', 'fdm' );
				} else {
					echo esc_html__( '1 item', 'fdm' );
				}
				?>
			</div>
		</div>
	</div>
</div>