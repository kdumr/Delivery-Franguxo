<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Track order page
 * TODO: Refactor!!!
 */
class Fdm_Track_Order {
	/**
	 * Construct
	 */
	public function __construct() {
		add_shortcode( 'mydelivery-track-order', [ $this, 'output_content' ] );
	}

	/**
	 * Output content for shortcode
	 *
	 * @return string
	 */
	public function output_content() {
		if ( empty( $this->get_order_id() ) ) {
			?>
				<div class="fdm-not-logged"><?php esc_html_e( 'Sorry, you don\'t have orders to show.', 'myd-delivery-pro' ); ?></div>
			<?php
		} else {
			?>
			<?php \wp_enqueue_style( 'myd-track-order-frontend' ); ?>
			<?php \wp_enqueue_style( 'myd-order-panel-frontend' ); ?>
			<?php \wp_enqueue_script( 'myd-tracking-session-recovery' ); ?>

			<?php $postid = $this->get_order_id(); ?>
			<?php $currency_simbol = Store_Data::get_store_data( 'currency_simbol' ); ?>
			<?php $date = get_post_meta( $postid, 'order_date', true ); ?>
			<?php $date = date( 'd/m - H:i', strtotime( $date ) ); ?>
			<?php $order_status = $this->get_order_info( 'order_status' ); ?>
			<?php $status_color = $this->get_status_color( $order_status ); ?>
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

			<div class="fdm-track-order-wrap">
				<div class="fdm-track-order-content">
					<div id="myd-track-order-status-bar" class="fdm-track-order-content-status <?php echo esc_attr( $status_color ); ?>">
						<?php echo esc_html( $this->convert_status_name( $order_status ) ); ?>
					</div>

					<div class="myd-track-order-update-wrapper">
						<span class="myd-pulsating-circle"></span>
						<span>
							<?php \esc_html_e( 'Live updates', 'myd-delivery-pro' ); ?>
						</span>
					</div>

					<div class="fdm-track-order-content-customer">
					<div class="fdm-order-list-items-type"><?php echo esc_html( $this->get_order_type() ); ?></div>
					<div class="fdm-order-list-items-order-number"><?php echo __( 'Order', 'myd-delivery-pro' ); ?> <?php echo esc_html( $postid ); ?></div>
					<div class="fdm-order-list-items-date"><?php echo esc_html( $date ); ?></div>
					<hr class="fdm-divider">

					<?php echo $this->get_order_type_data(); ?>

					</div>
						<div class="fdm-track-order-content-products">
							<?php echo $this->get_order_items( $this->get_order_id() ); ?>

							<hr class="fdm-divider">

							<div class="fdm-order-list-items-customer">
								<?php echo esc_html__( 'Delivery', 'myd-delivery-pro' ); ?>:
								<?php echo esc_html( $currency_simbol ); ?> <?php echo $this->get_order_info( 'order_delivery_price' ); ?>
							</div>

							<?php
								// Exibe desconto de fidelidade se presente
								$fidelity_redeemed = get_post_meta( $postid, 'order_loyalty_redeemed', true );
								$fidelity_discount = get_post_meta( $postid, 'order_fidelity_discount', true );
								if ( ! empty( $fidelity_redeemed ) && (string) $fidelity_redeemed === '1' && ! empty( $fidelity_discount ) && floatval( $fidelity_discount ) > 0 ) :
							?>
								<div class="fdm-order-list-items-customer">
									<?php esc_html_e( 'Desconto aplicado', 'myd-delivery-pro' ); ?>: <span style="color: #28a745;">-<?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( $fidelity_discount ); ?></span>
								</div>
							<?php endif; ?>

							<?php // Coupon codes are intentionally not displayed on public order tracking pages to avoid exposure. ?>

							<div class="fdm-order-list-items-customer-name">
								<?php echo esc_html__( 'Total', 'myd-delivery-pro' ); ?>:
								<?php echo esc_html( $currency_simbol ); ?> <?php echo $this->get_order_info('order_total'); ?>
							</div>

							<div class="fdm-order-list-items-customer">
								<?php esc_html_e( 'Payment Type', 'myd-delivery-pro' ); ?>:
								<?php echo esc_html( $payment_type ); ?>
							</div>

							<div class="fdm-order-list-items-customer">
								<?php echo esc_html__( 'Payment Method', 'myd-delivery-pro' ); ?>:
								<?php
								$pm_raw = $this->get_order_info( 'order_payment_method' );
								$payment_method_labels = array(
									'CRD' => __( 'Crédito', 'myd-delivery-pro' ),
									'DEB' => __( 'Débito', 'myd-delivery-pro' ),
									'VRF' => __( 'Vale-refeição', 'myd-delivery-pro' ),
									'DIN' => __( 'Dinheiro', 'myd-delivery-pro' ),
									'PIX' => 'Pix',
									'pix' => 'Pix',
								);
								$pm_label = isset( $payment_method_labels[ $pm_raw ] ) ? $payment_method_labels[ $pm_raw ] : $pm_raw;
								echo esc_html( $pm_label );
								?>
							</div>

							<div class="fdm-order-list-items-customer">
								<?php esc_html_e( 'Payment Status', 'myd-delivery-pro' ); ?>:
								<?php echo esc_html( $payment_status ); ?>
							</div>

							<?php if ( ! empty( $change ) ) : ?>
								<div class="fdm-order-list-items-customer">
									<?php esc_html_e( 'Change for', 'myd-delivery-pro' ); ?>:
									<?php echo esc_html( $change ); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<script>
					const eventUrl = window.location.origin + '/wp-json/myd-delivery/v1/order/' + window.location.search + '&fields=status';
					const fetchOrderStatus = async () => {
						try {
							const response = await fetch(eventUrl, { cache: 'no-store' });
							if (!response.ok) {
								throw new Error('Network response was not ok');
							}
							const data = await response.json();
							const responseStatus = data?.data?.status || '';
							setOrderStatus(responseStatus);
						} catch (error) {
							console.error('Polling failed:', error);
						}
					};
					const pollingInterval = setInterval(fetchOrderStatus, 5000);

					function setOrderStatus(status) {
						const statusBar = document.getElementById('myd-track-order-status-bar');
						const statusMap = {
							new: {
								'class': 'myd-track-order-status--new',
								'name': '<?php echo \esc_html__( 'New', 'myd-delivery-pro' ); ?>',
							},
							confirmed: {
								'class': 'myd-track-order-status--confirmed',
								'name': '<?php echo \esc_html__( 'Confirmed', 'myd-delivery-pro' ); ?>',
							},
							'in-delivery': {
								'class': 'myd-track-order-status--indelivery',
								'name': '<?php echo \esc_html__( 'In Delivery', 'myd-delivery-pro' ); ?>',
							},
							finished:  {
								'class': 'myd-track-order-status--finished',
								'name': '<?php echo \esc_html__( 'Finished', 'myd-delivery-pro' ); ?>',
							},
							canceled:  {
								'class':  'myd-track-order-status--canceled',
								'name': '<?php echo \esc_html__( 'Canceled', 'myd-delivery-pro' ); ?>',
							},
							done: {
								'class': 'myd-track-order-status--done',
								'name': '<?php echo \esc_html__( 'Done', 'myd-delivery-pro' ); ?>',
							},
							waiting: {
								'class': 'myd-track-order-status--waiting',
								'name': '<?php echo \esc_html__( 'Waiting', 'myd-delivery-pro' ); ?>',
							},
						};

						if(statusBar && status) {
							const statusClass = statusMap[status].class;
							statusBar.className = 'fdm-track-order-content-status ' + statusMap[status].class;
							statusBar.innerText = statusMap[status].name;
						}
					}
				</script>
			<?php
		}
	}

	/**
	 * Get order type data
	 */
	public function get_order_type_data() {
		$postid = $this->get_order_id();
		$table = get_post_meta( $postid, 'order_table', true );
		$address = get_post_meta( $postid, 'order_address', true );

		if ( ! empty( $table ) ) {
			return '<div class="fdm-order-list-items-customer-name">'.get_post_meta( $postid, 'order_customer_name', true ).'</div>
                    <div class="fdm-order-list-items-customer">'.get_post_meta( $postid, 'customer_phone', true ).'</div>
                    <div class="fdm-order-list-items-customer">'.esc_html__('Table','myd-delivery-pro').' '.get_post_meta( $postid, 'order_table', true ).'</div>';
		}

		if ( ! empty( $address ) ) {
			$addr = get_post_meta( $postid, 'order_address', true );
			$num  = get_post_meta( $postid, 'order_address_number', true );
			$comp = get_post_meta( $postid, 'order_address_comp', true );
			$ref  = get_post_meta( $postid, 'order_address_reference', true );
			$num_label = $num !== '' ? $num : 'S/n°';

			$html  = '<div class="fdm-order-list-items-customer-name">'.get_post_meta( $postid, 'order_customer_name', true ).'</div>';
			$html .= '<div class="fdm-order-list-items-customer">'.get_post_meta( $postid, 'customer_phone', true ).'</div>';
			$html .= '<div class="fdm-order-list-items-customer">'.esc_html( $addr ).', '.esc_html( $num_label ).' | '.esc_html( $comp ).'</div>';
			if ( ! empty( $ref ) ) {
				$html .= '<div class="fdm-order-list-items-customer">'.esc_html__( 'Ponto de referência','myd-delivery-pro' ).': '.esc_html( $ref ).'</div>';
			}
			$html .= '<div class="fdm-order-list-items-customer">'.get_post_meta( $postid, 'order_neighborhood', true ).' | '.get_post_meta( $postid, 'order_zipcode', true ).'</div>';
			return $html;
		}

		if ( empty( $address ) and empty( $table ) ) {
			return '<div class="fdm-order-list-items-customer-name">'.get_post_meta( $postid, 'order_customer_name', true ).'</div>
                    <div class="fdm-order-list-items-customer">'.get_post_meta( $postid, 'customer_phone', true ).'</div>';
		}
	}

	/**
	 * Get order id
	 */
	public function get_order_id() {
		if ( ! empty( $_GET['hash'] ) ) {
			$parameter = sanitize_text_field( $_GET['hash'] );
			return base64_decode( $parameter );
		} else {
			return;
		}
	}

	/**
	 * Get order info
	 */
	public function get_order_info( $meta ) {
		$order_meta = get_post_meta( $this->get_order_id(), $meta, true );

		if ( ! empty( $order_meta ) ) {
			return $order_meta;
		} else {
			return;
		}
	}

	protected function get_order_type() {
		$order_type = \get_post_meta( $this->get_order_id(), 'order_ship_method', true );

		$map_type = array(
			'delivery' => __( 'Delivery', 'myd-delivery-pro' ),
			'take-away' => __( 'Take Away', 'myd-delivery-pro' ),
			'order-in-store' => __( 'Delivery', 'myd-delivery-pro' ),
		);

		return $map_type[ $order_type ] ?? '';
	}

	/**
	 * Prepare items
	 */
	public function get_order_items( $postid ) {
		/**
		 * TODO: check this with new model.
		 */
		$items = get_post_meta( $postid, 'myd_order_items', true );
		$currency_simbol = Store_Data::get_store_data( 'currency_simbol' );
		$list = '';

		if ( ! empty( $items ) ) {
			foreach ( $items as $value ) {
				$list .= '<div class="fdm-products-order-loop">';
				$list .= '<div class="fdm-order-list-items-product">' . esc_html( $value['product_name'] ) . '</div>';
				$extras_html = '';
				if ( isset( $value['extras'] ) && is_array( $value['extras'] ) && ! empty( $value['extras']['groups'] ) ) {
					foreach ( $value['extras']['groups'] as $group ) {
						if ( ! isset( $group['items'] ) || ! is_array( $group['items'] ) ) continue;
						$group_name = isset( $group['group'] ) ? trim( $group['group'] ) : '';
						$group_items_html = '';
						foreach ( $group['items'] as $item ) {
							$qty  = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
							$name = isset( $item['name'] ) ? trim( $item['name'] ) : '';
							if ( $qty > 0 && $name !== '' ) {
								$group_items_html .= '<div class="fdm-order-list-items-product-extra">' . esc_html( $qty . 'x ' . $name ) . '</div>';
							}
						}
						if ( $group_items_html !== '' ) {
							if ( $group_name !== '' ) {
								$extras_html .= '<div class="fdm-order-list-items-product-extra" style="font-weight:600;color:#444;">' . esc_html( $group_name ) . ':</div>';
							}
							$extras_html .= $group_items_html;
						}
					}
				}
				$list .= $extras_html;

				if ( ! empty( $value['product_note'] ) ) {
					$list .= '<div class="fdm-order-list-items-customer">' . __( 'Note', 'myd-delivery-pro' ) . ' ' . esc_html( $value['product_note'] ) . '</div>';
				}


				$list .= '</div>';
			}
		}

		return $list;
	}

	private function format_extras_display( $extras ) {
		if ( empty( $extras ) || empty( $extras['groups'] ) ) {
			return '';
		}
		$result = array();
		foreach ( $extras['groups'] as $group ) {
			$selected_items = array();
			if ( isset( $group['items'] ) && is_array( $group['items'] ) ) {
				foreach ( $group['items'] as $item ) {
					$qty = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
					if ( $qty > 0 ) {
						$suffix = $qty > 1 ? ' (' . $qty . 'x)' : '';
						$name = isset( $item['name'] ) ? $item['name'] : '';
						if ( $name !== '' ) {
							$selected_items[] = $name . $suffix;
						}
					}
				}
			}
			if ( ! empty( $selected_items ) ) {
				$group_name = isset( $group['group'] ) ? $group['group'] : '';
				$result[] = $group_name . ':' . PHP_EOL . implode( PHP_EOL, $selected_items ) . PHP_EOL;
			}
		}
		return implode( PHP_EOL, $result );
	}

	/**
	 * Check status color
	 *
	 * @return string
	 */
	public function get_status_color( $status ) {
		switch ( $status ) {
			case 'new':
				return 'myd-track-order-status--new';
				break;
			case 'confirmed':
				return 'myd-track-order-status--confirmed';
				break;
			case 'in-delivery':
				return 'myd-track-order-status--indelivery';
				break;
			case 'finished':
				return 'myd-track-order-status--finished';
				break;
			case 'canceled':
				return 'myd-track-order-status--canceled';
				break;
			case 'done':
				return 'myd-track-order-status--done';
				break;
			case 'waiting':
				return 'myd-track-order-status--waiting';
				break;
		}
	}

	/**
	 * Convert order status
	 */
	public function convert_status_name( $status ) {
		switch ( $status ) {
			case 'new':
				return esc_html__( 'New', 'myd-delivery-pro' );
				break;
			case 'confirmed':
				return esc_html__( 'Confirmed', 'myd-delivery-pro' );
				break;
			case 'in-delivery':
				return esc_html__( 'In Delivery', 'myd-delivery-pro' );
				break;
			case 'finished':
				return esc_html__( 'Finished', 'myd-delivery-pro' );
				break;
			case 'canceled':
				return esc_html__( 'Canceled', 'myd-delivery-pro' );
				break;
			case 'done':
				return esc_html__( 'Done', 'myd-delivery-pro' );
				break;
			case 'waiting':
				return esc_html__( 'Waiting', 'myd-delivery-pro' );
				break;
		}
	}
}

new Fdm_Track_Order();
