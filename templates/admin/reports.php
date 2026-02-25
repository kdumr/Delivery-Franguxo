<?php

use MydPro\Includes\Store_Data;
use MydPro\Includes\Myd_Store_Orders;
use MydPro\Includes\Myd_Reports;
use MydPro\Includes\Myd_Store_Formatting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TODO: add user permission verification as we did on verion 1.9.42.
 */

/**
 * Store data and attrs
 */
$currency_simbol = Store_Data::get_store_data( 'currency_simbol' );
$url = esc_url( home_url( '/wp-admin/admin.php?page=myd-delivery-reports' ) );
$today = current_time( 'Y-m-d' );
$latest_7_days = date( 'Y-m-d', strtotime( "$today -7 days" ) );
$latest_30_days = date( 'Y-m-d', strtotime( "$today -30 days" ) );

/**
 * Define period
 */
$to = isset( $_GET['to'] ) ? sanitize_text_field( $_GET['to'] ) : $today;
$from = isset( $_GET['from'] ) ? sanitize_text_field( $_GET['from'] ) : $today;
$filter_type = isset( $_GET['filter_type'] ) ? sanitize_text_field( $_GET['filter_type'] ) : '';

/**
 * Query orders by period
 */
$args = [
	'post_type' => 'mydelivery-orders',
	'no_found_rows' => true,
	'update_post_term_cache' => false,
	'posts_per_page' => -1,
	'post_status' => 'publish',
	'date_query' => [
		[
			'after' => $from,
			'before' => $to,
			'inclusive' => true,
		]
	]
];
$orders = new Myd_Store_Orders( $args );
$orders = $orders->get_orders();

/**
 * Reports
 */
$report = new Myd_Reports( $orders, $from, $to );

wp_enqueue_script( 'myd-chart-js' );

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Reports', 'myd-delivery-pro' ); ?></h1>

	<section class="myd-custom-content-page">
		<div class="myd-admin-filter">
			<a class="myd-admin-filter__item <?php echo esc_attr( $filter_type === 'today' ? 'myd-admin-filter--active' : '' ); ?>" href="<?php echo esc_attr( $url . '&filter_type=today&from=' . $today ); ?>"><?php esc_html_e( 'Today', 'myd-delivery-pro' ); ?></a>

			<a class="myd-admin-filter__item <?php echo esc_attr( $filter_type === '7' ? 'myd-admin-filter--active' : '' ); ?>" href="<?php echo esc_attr( $url . '&filter_type=7&from=' . $latest_7_days ); ?>"><?php esc_html_e( 'Latest 7 days', 'myd-delivery-pro' ); ?></a>

			<a class="myd-admin-filter__item <?php echo esc_attr( $filter_type === '30' ? 'myd-admin-filter--active' : '' ); ?>" href="<?php echo esc_attr( $url . '&filter_type=30&from=' . $latest_30_days ); ?>"><?php esc_html_e( 'Latest 30 days', 'myd-delivery-pro' ); ?></a>

			<div class="myd-admin-filter__range">
				<span>De:</span>
				<input type="date" name="report-range-from" id="report-range-from" max="<?php echo esc_attr( $today ); ?>" value="<?php echo esc_attr( $filter_type === 'range' ? esc_attr( $from ) : '' ); ?>">

				<span>Até:</span>
				<input type="date" name="report-range-to" id="report-range-to" max="<?php echo esc_attr( $today ); ?>" value="<?php echo esc_attr( $filter_type === 'range' ? esc_attr( $to ) : '' ); ?>">

				<a class="button-primary" id="report-range-submit" data-from="<?php echo esc_attr( $filter_type === 'range' ? esc_attr( $from ) : '' ); ?>" data-to="<?php echo esc_attr( $filter_type === 'range' ? esc_attr( $to ) : '' ); ?>" data-url="<?php echo esc_attr( $url . '&filter_type=range&from={from}&to={to}'); ?>" href="#"><?php esc_html_e( 'Filter', 'myd-delivery-pro' ); ?></a>
			</div>

		</div>
		<div class="myd-admin-cards myd-card-4columns">
			<div class="myd-admin-cards__item myd-cards--price">
				<span class="myd-admin-cards__amount"><?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( Myd_Store_Formatting::format_price( $report->get_total_orders() ) ); ?></span>
				<p class="myd-admin-cards__description"><?php esc_html_e( 'Total sales in this period', 'myd-delivery-pro' ); ?></p>
			</div>

			<div class="myd-admin-cards__item myd-cards--orders">
				<span class="myd-admin-cards__amount"><?php echo esc_html( $report->get_count_orders() ); ?></span>
				<p class="myd-admin-cards__description"><?php esc_html_e( 'Total orders in this period', 'myd-delivery-pro' ); ?></p>
			</div>

			<div class="myd-admin-cards__item myd-cards--purchased">
				<span class="myd-admin-cards__amount"><?php echo esc_html( $report->get_purchased_items_quantity() ); ?></span>
				<p class="myd-admin-cards__description"><?php esc_html_e( 'Quantity of products sold', 'myd-delivery-pro' ); ?></p>
			</div>

			<div class="myd-admin-cards__item myd-cards--average">
				<span class="myd-admin-cards__amount"><?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( Myd_Store_Formatting::format_price( $report->get_average_sales() ) ); ?></span>
				<p class="myd-admin-cards__description"><?php esc_html_e( 'Average amount sales per day', 'myd-delivery-pro' ); ?></p>
			</div>
		</div>

		<div class="myd-reports-charts">
			<div class="myd-reports__chart-wrapper myd-chart-30">
				<h3 style="text-align:center;"><?php echo __( 'Top 3 products', 'myd-delivery-pro' ); ?></h3>
				<canvas id="myd-reports-charts-1" class="myd-reports__charts"></canvas>

				<?php
				$purchased_items = $report->get_purchased_items();
				$labels = array();
				$colors = array();
				$data = array();
				$vailable_colors = array(
					'rgb(255, 99, 132)',
					'rgb(54, 162, 235)',
					'rgb(255, 205, 86)',
				);

				$chart_purchased_items = array();

				if ( empty( $purchased_items ) ) {
					$chart_purchased_items[] = array(
						'label' => 'No product',
						'color' => 'rgb(255, 99, 132)',
						'qty' => 1,
					);
				} else {
					foreach ( $purchased_items as $key => $item ) {
						if ( $key <= 2 ) {
							$chart_purchased_items[] = array(
								'label' => $item['name'] ?? 'Product',
								'color' => $vailable_colors[ $key ] ?? 'rgb(255, 99, 132)',
								'qty' => $item['quantity'] ?? 1,
							);
						}
					}
				}
				?>

				<script>
					window.addEventListener('DOMContentLoaded', (e) => {
						const chartWrapper = document.getElementById('myd-reports-charts-1');

						new Chart(chartWrapper, {
							type: 'doughnut',
							data: {
							labels: <?php echo wp_json_encode( array_column( $chart_purchased_items, 'label' ) ); ?>,
							datasets: [{
								data: <?php echo wp_json_encode( array_column( $chart_purchased_items, 'qty' ) ); ?>,
								backgroundColor: <?php echo wp_json_encode( array_column( $chart_purchased_items, 'color' ) ); ?>,
								hoverOffset: 4
							}]
							},
						});
					});
				</script>
			</div>

			<div class="myd-reports__chart-wrapper myd-chart-70">
				<canvas id="myd-reports-charts" class="myd-reports__charts"></canvas>

				<?php
				$orders = $report->get_orders_by_period();
				$orders = array_reverse( $orders, true );
				$labels = array();
				$data = array();

				$chart_orders = array();

				if ( empty( $orders ) ) {
					$chart_orders[] = array(
						'label' => 'No orders',
						'qty' => 0,
					);
				} else {
					foreach ( $orders as $key => $item ) {
						$chart_orders[] = array(
							'label' => $item['period'] ?? 'Product',
							'qty' => $item['number_orders'] ?? 1,
						);
					}
				}
				?>

				<script>
					window.addEventListener('DOMContentLoaded', (e) => {
						const chartWrapper = document.getElementById('myd-reports-charts');

						new Chart(chartWrapper, {
							type: 'bar',
							data: {
							labels: <?php echo wp_json_encode( array_column( $chart_orders, 'label' ) ); ?>,
							datasets: [{
								label: 'Pedidos no período',
								data: <?php echo wp_json_encode( array_column( $chart_orders, 'qty' ) ); ?>,
								borderWidth: 1,
								backgroundColor: 'rgb(54, 162, 235)'
							}]
							},
							options: {
							scales: {
								y: {
								beginAtZero: true
								}
							}
							}
						});
					});
				</script>
			</div>

			<div class="myd-reports__chart-wrapper">
				<canvas id="myd-reports-charts-2" class="myd-reports__charts"></canvas>

				<?php
				$orders = $report->get_orders_by_period();
				$orders = array_reverse( $orders, true );
				$labels = array();
				$data = array();

				$chart_orders = array();

				if ( empty( $orders ) ) {
					$chart_orders[] = array(
						'label' => 'No orders',
						'qty' => 0,
					);
				} else {
					foreach ( $orders as $key => $item ) {
						$chart_orders[] = array(
							'label' => $item['period'] ?? 'Date',
							'qty' => $item['total'] ?? 0,
						);
					}
				}
				?>

				<script>
					window.addEventListener('DOMContentLoaded', (e) => {
						const chartWrapper = document.getElementById('myd-reports-charts-2');

						new Chart(chartWrapper, {
							type: 'bar',
							data: {
							labels: <?php echo wp_json_encode( array_column( $chart_orders, 'label' ) ); ?>,
							datasets: [{
								label: 'Quantidade de vendas no período',
								data: <?php echo wp_json_encode( array_column( $chart_orders, 'qty' ) ); ?>,
								borderWidth: 1,
								backgroundColor: 'rgb(54, 162, 235)'
							}]
							},
							options: {
							scales: {
								y: {
								beginAtZero: true
								}
							}
							}
						});
					});
				</script>
			</div>
		</div>

		<!-- Seção de Relatório de Cupons -->
		<div class="myd-coupons-report-section" style="margin-top: 40px;">
			<h2><?php esc_html_e( 'Relatório de cupons', 'myd-delivery-pro' ); ?></h2>
			
			<?php
			$coupon_data = $report->get_coupon_usage_data();
			$orders_with_coupons = $coupon_data['orders_with_coupons'];
			$coupon_statistics = $coupon_data['coupon_statistics'];
			?>

			<!-- Estatísticas Resumidas de Cupons -->
			<div class="myd-admin-cards myd-card-3columns" style="margin-bottom: 30px;">
				<div class="myd-admin-cards__item myd-cards--coupon">
					<span class="myd-admin-cards__amount"><?php echo esc_html( count( $orders_with_coupons ) ); ?></span>
					<p class="myd-admin-cards__description"><?php esc_html_e( 'Pedidos com cupons', 'myd-delivery-pro' ); ?></p>
				</div>

				<div class="myd-admin-cards__item myd-cards--coupon">
					<span class="myd-admin-cards__amount"><?php echo esc_html( count( $coupon_statistics ) ); ?></span>
					<p class="myd-admin-cards__description"><?php esc_html_e( 'Cupons diferentes utilizados', 'myd-delivery-pro' ); ?></p>
				</div>


			</div>

			<!-- Tabela de Estatísticas por Cupom -->
			<?php if ( ! empty( $coupon_statistics ) ) : ?>
				<div class="myd-coupon-statistics-table" style="margin-bottom: 30px;">
					<h3><?php esc_html_e( 'Estatísticas de Cupons', 'myd-delivery-pro' ); ?></h3>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Código do Cupom', 'myd-delivery-pro' ); ?></th>
								<th><?php esc_html_e( 'Tipo', 'myd-delivery-pro' ); ?></th>
								<th><?php esc_html_e( 'Uso Atual', 'myd-delivery-pro' ); ?></th>
								<th><?php esc_html_e( 'Limite de Uso', 'myd-delivery-pro' ); ?></th>
								<th><?php esc_html_e( 'Validade', 'myd-delivery-pro' ); ?></th>
								<th><?php esc_html_e( 'Status', 'myd-delivery-pro' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $coupon_statistics as $coupon_stat ) : ?>
								<?php
								// Calcular status
								$is_expired = false;
								$is_limit_reached = false;
								$expiry_info = '';
								
								if ( ! empty( $coupon_stat['expiry_date'] ) ) {
									$expiry_timestamp = strtotime( $coupon_stat['expiry_date'] );
									$current_timestamp = current_time( 'timestamp' );
									
									if ( $current_timestamp > $expiry_timestamp ) {
										$is_expired = true;
										$expiry_info = '<span style="color: #d63638; font-weight: bold;">' . __( 'Expirado', 'myd-delivery-pro' ) . '</span><br>';
										$expiry_info .= '<small>' . date_i18n( 'd/m/Y H:i', $expiry_timestamp ) . '</small>';
									} else {
										$expiry_info = '<span style="color: #00a32a; font-weight: bold;">' . __( 'Válido', 'myd-delivery-pro' ) . '</span><br>';
										$expiry_info .= '<small>' . date_i18n( 'd/m/Y H:i', $expiry_timestamp ) . '</small>';
									}
								} else {
									$expiry_info = '<span style="color: #646970; font-style: italic;">' . __( 'Sem data de expiração', 'myd-delivery-pro' ) . '</span>';
								}
								
								if ( $coupon_stat['usage_limit'] > 0 && $coupon_stat['usage_count'] >= $coupon_stat['usage_limit'] ) {
									$is_limit_reached = true;
								}
								
								$is_active = !$is_expired && !$is_limit_reached;
								?>
								<tr>
									<td>
										<strong><?php echo esc_html( $coupon_stat['code'] ); ?></strong>
										<?php if ( $coupon_stat['coupon_id'] ) : ?>
											<br><small><a href="<?php echo esc_url( admin_url( 'post.php?post=' . $coupon_stat['coupon_id'] . '&action=edit' ) ); ?>"><?php _e( 'Editar cupom', 'myd-delivery-pro' ); ?></a></small>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $coupon_stat['type'] ); ?></td>
									<td>
										<strong><?php echo esc_html( $coupon_stat['usage_count'] ); ?></strong>
										<?php if ( $coupon_stat['usage_limit'] > 0 ) : ?>
											<?php 
											$percentage = ( $coupon_stat['usage_count'] / $coupon_stat['usage_limit'] ) * 100;
											$color = $percentage >= 100 ? '#d63638' : ( $percentage >= 80 ? '#ffb900' : '#00a32a' );
											?>
											<div style="width: 100px; height: 8px; background: #f0f0f1; border-radius: 4px; margin-top: 4px; overflow: hidden;">
												<div style="height: 100%; background: <?php echo $color; ?>; width: <?php echo min( $percentage, 100 ); ?>%;"></div>
											</div>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( $coupon_stat['usage_limit'] > 0 ) : ?>
											<?php echo esc_html( $coupon_stat['usage_limit'] ); ?>
											<?php if ( $is_limit_reached ) : ?>
												<br><small style="color: #d63638; font-weight: bold;"><?php _e( 'Limite atingido', 'myd-delivery-pro' ); ?></small>
											<?php endif; ?>
										<?php else : ?>
											<span style="color: #646970; font-style: italic;"><?php _e( 'Ilimitado', 'myd-delivery-pro' ); ?></span>
										<?php endif; ?>
									</td>
									<td><?php echo $expiry_info; ?></td>
									<td>
										<?php if ( $is_active ) : ?>
											<span style="color: #00a32a; font-weight: bold;"><?php _e( 'Ativo', 'myd-delivery-pro' ); ?></span>
										<?php else : ?>
											<span style="color: #d63638; font-weight: bold;"><?php _e( 'Inativo', 'myd-delivery-pro' ); ?></span>
											<?php if ( $is_expired ) : ?>
												<br><small><?php _e( 'Motivo: Expirado', 'myd-delivery-pro' ); ?></small>
											<?php elseif ( $is_limit_reached ) : ?>
												<br><small><?php _e( 'Motivo: Limite atingido', 'myd-delivery-pro' ); ?></small>
											<?php endif; ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<!-- Tabela Detalhada de Pedidos com Cupons -->
			<?php if ( ! empty( $orders_with_coupons ) ) : ?>
				<div class="myd-coupon-orders-table">
					<h3><?php esc_html_e( 'Pedidos com Cupons', 'myd-delivery-pro' ); ?></h3>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'ID do Pedido', 'myd-delivery-pro' ); ?></th>
								<th><?php esc_html_e( 'Data', 'myd-delivery-pro' ); ?></th>
								<th><?php esc_html_e( 'Código do Cupom', 'myd-delivery-pro' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $orders_with_coupons as $order_coupon ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order_coupon['order_id'] . '&action=edit' ) ); ?>">
											#<?php echo esc_html( $order_coupon['order_id'] ); ?>
										</a>
									</td>
									<td><?php echo esc_html( date( 'd/m/Y H:i', strtotime( $order_coupon['order_date'] ) ) ); ?></td>
									<td><strong><?php echo esc_html( $order_coupon['coupon_code'] ); ?></strong></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<div class="myd-no-coupons-message" style="text-align: center; padding: 40px; background: #000000ff; border-radius: 8px;">
					<h3><?php esc_html_e( 'Nenhum pedido com cupons encontrado neste período', 'myd-delivery-pro' ); ?></h3>
					<p><?php esc_html_e( 'Tente selecionar um intervalo de datas diferente ou verifique se os cupons estão sendo usados.', 'myd-delivery-pro' ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<style>
			/* Estilos para a seção de relatórios de cupons */
			.myd-coupons-report-section {
				background: #fff;
				padding: 20px;
				border-radius: 8px;
				border: 1px solid #c3c4c7;
			}

			.myd-coupons-report-section h2 {
				margin-top: 0;
				margin-bottom: 20px;
				color: #1d2327;
				border-bottom: 2px solid #2271b1;
				padding-bottom: 10px;
			}

			.myd-coupons-report-section h3 {
				margin-bottom: 15px;
				color: #1d2327;
			}

			.myd-card-3columns {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
				gap: 20px;
			}

			.myd-cards--coupon {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: white;
			}

			.myd-coupon-statistics-table table,
			.myd-coupon-orders-table table {
				margin-top: 10px;
				border-radius: 8px;
				overflow: hidden;
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			}

			.myd-coupon-statistics-table th,
			.myd-coupon-orders-table th {
				background: #f0f0f1;
				font-weight: 600;
			}

			.myd-coupon-statistics-table td,
			.myd-coupon-orders-table td {
				vertical-align: middle;
			}

			.myd-coupon-statistics-table tr:hover,
			.myd-coupon-orders-table tr:hover {
				background-color: #f8f9fa;
			}

			.myd-no-coupons-message {
				background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
				color: white;
				border-radius: 12px;
				padding: 40px;
				text-align: center;
			}

			.myd-no-coupons-message h3 {
				color: white;
				margin-bottom: 10px;
			}

			.myd-no-coupons-message p {
				color: rgba(255,255,255,0.9);
				margin: 0;
			}

			/* Responsividade */
			@media (max-width: 768px) {
				.myd-card-3columns {
					grid-template-columns: 1fr;
				}
				
				.myd-coupon-statistics-table,
				.myd-coupon-orders-table {
					overflow-x: auto;
				}
			}
		</style>
	</section>
</div>
