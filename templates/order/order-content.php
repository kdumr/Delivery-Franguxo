<?php

use MydPro\Includes\Store_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- panel-overrides.css moved to assets and is enqueued by the plugin bootstrap -->
<?php if ( $orders->have_posts() ) : ?>
	<?php $currency_simbol = Store_Data::get_store_data( 'currency_simbol' ); ?>
	<?php $today = new DateTimeImmutable('now', wp_timezone()); ?>
	<?php while ( $orders->have_posts() ) : ?>
		<?php $orders->the_post(); ?>
		<?php $postid = get_the_ID(); ?>
		<?php
			// Mostrar apenas pedidos do dia atual (mesma lógica do order-list.php)
			$order_date_raw = get_post_meta( $postid, 'order_date', true );
			if ( empty( $order_date_raw ) ) { continue; }
			try {
				$od = new DateTimeImmutable( $order_date_raw, wp_timezone() );
			} catch ( Exception $e ) {
				$od = false;
			}
			if ( ! $od || $od->format('Y-m-d') !== $today->format('Y-m-d') ) { continue; }

			// Data formatada para exibição
			$date = gmdate( 'd/m - H:i', strtotime( $order_date_raw ) );
		?>
		<?php $status = get_post_meta( $postid, 'order_status', true ); ?>
		<?php $coupon = get_post_meta( $postid, 'order_coupon', true ); ?>
		<?php $change = get_post_meta( $postid, 'order_change', true ); ?>
		<?php $raw_payment_type = get_post_meta( $postid, 'order_payment_type', true ); ?>
		<?php 
		$payment_method = get_post_meta( $postid, 'order_payment_method', true );
		// Mapeamento centralizado dos métodos de pagamento
		$payment_method_labels = array(
			'CRD' => __( 'Crédito', 'myd-delivery-pro' ),
			'DEB' => __( 'Débito', 'myd-delivery-pro' ),
			'VRF' => __( 'Vale-refeição', 'myd-delivery-pro' ),
			'DIN' => __( 'Dinheiro', 'myd-delivery-pro' ),
			'pix' => 'Pix',
		);
		$payment_method_label = isset($payment_method_labels[$payment_method]) ? $payment_method_labels[$payment_method] : $payment_method;
		?>
		<?php $payment_type = $raw_payment_type === 'upon-delivery' ? __( 'Upon Delivery', 'myd-delivery-pro' ) : __( 'Payment Integration', 'myd-delivery-pro' ); ?>
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

		<div class="fdm-orders-full-items" id="content-<?php echo esc_attr( $postid ); ?>">
			<div class="myd-detail-header">
			<?php
				$table   = get_post_meta( $postid, 'order_table', true );
				$address = get_post_meta( $postid, 'order_address', true );
				$customer_name = get_post_meta( $postid, 'order_customer_name', true );
				$customer_phone = get_post_meta( $postid, 'customer_phone', true );
				$addr = get_post_meta( $postid, 'order_address', true );
				$num  = get_post_meta( $postid, 'order_address_number', true );
				$comp = get_post_meta( $postid, 'order_address_comp', true );
				$ref  = get_post_meta( $postid, 'order_address_reference', true );
				$num_label = $num !== '' ? $num : 'S/n°';
				$neigh = get_post_meta( $postid, 'order_neighborhood', true );
				$zip   = get_post_meta( $postid, 'order_zipcode', true );
				$city_early = get_post_meta( $postid, 'order_city', true );

				// Prepara dados do Google Maps e texto de cópia (necessários nos botões do header)
				$gmaps_parts_early = array();
				if (!empty($addr)) $gmaps_parts_early[] = $addr;
				if (!empty($num)) $gmaps_parts_early[] = $num;
				if (!empty($comp)) $gmaps_parts_early[] = $comp;
				if (!empty($neigh)) $gmaps_parts_early[] = $neigh;
				if (!empty($city_early)) $gmaps_parts_early[] = $city_early;
				if (!empty($zip)) $gmaps_parts_early[] = $zip;
				$gmaps_address_early = implode(', ', array_filter($gmaps_parts_early));
				$gmaps_url_early = '';
				$copy_text_early = '';
				if (!empty($gmaps_address_early)) {
					$gmaps_url_early = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $gmaps_address_early );
					$order_number_early = get_the_title($postid);
					$order_locator_early = get_post_meta( $postid, 'order_locator', true );
					$site_confirm_url_early = home_url( '/confirmacao-de-entrega' );
					$copy_lines_early = array();
					$copy_lines_early[] = "Pedido #" . ( $order_number_early ?: '' );
					$copy_lines_early[] = "Nome do cliente: " . ( $customer_name ?: '' );
					$copy_lines_early[] = "Telefone: " . ( $customer_phone ?: '' );
					$copy_lines_early[] = "Endereço: " . $gmaps_address_early;
					if ( ! empty( $ref ) ) {
						$copy_lines_early[] = "Ponto de referência: " . $ref;
					}
					$copy_lines_early[] = "";
					$copy_lines_early[] = "Localização: " . $gmaps_url_early;
					$copy_lines_early[] = "";
					$copy_lines_early[] = "---------------------------";
					$copy_lines_early[] = "";
					$copy_lines_early[] = "Confirmação:";
					$copy_lines_early[] = "LOCALIZADOR: " . ( $order_locator_early ?: '' );
					$copy_lines_early[] = $site_confirm_url_early;
					$copy_text_early = implode("\n", $copy_lines_early);
				}
			?>

			<?php
				$order_channel = get_post_meta( $postid, 'order_channel', true );
			?>
			<div class="myd-customer-card" data-order-id="<?php echo esc_attr( $postid ); ?>">
				<div class="fdm-order-list-items">
					<div class="fdm-order-list-items myd-order-header-row">
						<div class="myd-order-header-info">
							<div class="fdm-order-list-items-order-number">
								<?php esc_html_e( 'Order', 'myd-delivery-pro' ); ?> <?php echo esc_html( get_the_title( $postid ) ); ?>
							</div>
							<div class="fdm-order-list-items-date"><?php echo esc_html( $date ); ?></div>
						</div>
						<div class="myd-order-header-actions">
							<?php if ( ! empty( $gmaps_url_early ) ) : ?>
								<button type="button" class="myd-header-action-btn myd-gmaps-link" data-postid="<?php echo esc_attr( $postid ); ?>" data-gmaps="<?php echo esc_attr( $gmaps_url_early ); ?>" title="Ver no Google Maps">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.5"><path d="M3 8.70938V16.8377C3 17.8813 3 18.4031 3.28314 18.7959C3.56627 19.1888 4.06129 19.3538 5.05132 19.6838L6.21609 20.072C7.58318 20.5277 8.26674 20.7556 8.95493 20.6634C8.96999 20.6614 8.98501 20.6593 9 20.6569V6.65705C8.88712 6.67391 8.77331 6.68433 8.6591 6.68823C8.11989 6.70664 7.58626 6.52877 6.51901 6.17302C5.12109 5.70705 4.42213 5.47406 3.89029 5.71066C3.70147 5.79466 3.53204 5.91678 3.39264 6.06935C3 6.49907 3 7.23584 3 8.70938Z" fill="#666"/><path d="M21 15.2907V7.16229C21 6.11872 21 5.59692 20.7169 5.20409C20.4337 4.81126 19.9387 4.64625 18.9487 4.31624L17.7839 3.92799C16.4168 3.47229 15.7333 3.24444 15.0451 3.3366C15.03 3.33861 15.015 3.34078 15 3.34309V17.343C15.1129 17.3261 15.2267 17.3157 15.3409 17.3118C15.8801 17.2934 16.4137 17.4713 17.481 17.827C18.8789 18.293 19.5779 18.526 20.1097 18.2894C20.2985 18.2054 20.468 18.0833 20.6074 17.9307C21 17.501 21 16.7642 21 15.2907Z" fill="#666"/></g><path d="M9.24685 6.60921C9.16522 6.6285 9.08286 6.64435 9 6.65673V20.6566C9.66964 20.5533 10.2689 20.1538 11.4416 19.3719L12.824 18.4503C13.7601 17.8263 14.2281 17.5143 14.7532 17.3902C14.8348 17.3709 14.9171 17.355 15 17.3427V3.34277C14.3304 3.44613 13.7311 3.84561 12.5583 4.62747L11.176 5.54905C10.2399 6.17308 9.77191 6.48509 9.24685 6.60921Z" fill="#666"/></svg>
								</button>
								<button type="button" class="myd-header-action-btn myd-copy-delivery-info" data-postid="<?php echo esc_attr( $postid ); ?>" title="Copiar informações de entrega">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.59961 11.3974C6.59961 8.67119 6.59961 7.3081 7.44314 6.46118C8.28667 5.61426 9.64432 5.61426 12.3596 5.61426H15.2396C17.9549 5.61426 19.3125 5.61426 20.1561 6.46118C20.9996 7.3081 20.9996 8.6712 20.9996 11.3974V16.2167C20.9996 18.9429 20.9996 20.306 20.1561 21.1529C19.3125 21.9998 17.9549 21.9998 15.2396 21.9998H12.3596C9.64432 21.9998 8.28667 21.9998 7.44314 21.1529C6.59961 20.306 6.59961 18.9429 6.59961 16.2167V11.3974Z" fill="#666"/><path opacity="0.5" d="M4.17157 3.17157C3 4.34315 3 6.22876 3 10V12C3 15.7712 3 17.6569 4.17157 18.8284C4.78913 19.446 5.6051 19.738 6.79105 19.8761C6.59961 19.0353 6.59961 17.8796 6.59961 16.2167V11.3974C6.59961 8.6712 6.59961 7.3081 7.44314 6.46118C8.28667 5.61426 9.64432 5.61426 12.3596 5.61426H15.2396C16.8915 5.61426 18.0409 5.61426 18.8777 5.80494C18.7403 4.61146 18.4484 3.79154 17.8284 3.17157C16.6569 2 14.7712 2 11 2C7.22876 2 5.34315 2 4.17157 3.17157Z" fill="#666"/></svg>
								</button>
								<textarea id="myd-delivery-info-<?php echo esc_attr( $postid ); ?>" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true"><?php echo esc_textarea( $copy_text_early ); ?></textarea>
							<?php endif; ?>
							<button type="button" class="myd-edit-order-btn" 
								data-order-id="<?php echo esc_attr( $postid ); ?>"
								data-customer-name="<?php echo esc_attr( $customer_name ); ?>"
								data-customer-phone="<?php echo esc_attr( $customer_phone ); ?>"
								data-address="<?php echo esc_attr( $addr ); ?>"
								data-address-number="<?php echo esc_attr( $num ); ?>"
								data-address-comp="<?php echo esc_attr( $comp ); ?>"
								data-neighborhood="<?php echo esc_attr( $neigh ); ?>"
								data-reference="<?php echo esc_attr( $ref ); ?>"
								title="Editar pedido">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
									<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
									<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
								</svg>
							</button>
						</div>
					</div>

					
							<?php if ( ! empty( $customer_name ) ) : ?>
								<?php
								// Conta pedidos finalizados/publicados para o mesmo nome de cliente
								$orders_count = 0;
								if ( ! empty( $customer_name ) ) {
											$orders_count_query = get_posts( array(
												'post_type' => 'mydelivery-orders',
												'post_status' => 'publish',
												'posts_per_page' => -1,
												'meta_query' => array(
													array(
														'key' => 'order_customer_name',
														'value' => $customer_name,
														'compare' => '='
													),
													array(
														'key' => 'order_status',
														'value' => array('canceled', 'draft'),
														'compare' => 'NOT IN'
													)
												),
												'fields' => 'ids'
											) );
									$orders_count = is_array($orders_count_query) ? count($orders_count_query) : 0;
								}
								?>
								<div class="fdm-order-list-items-customer-name">
									<?php echo esc_html( $customer_name ); ?>
									<?php if ( $orders_count > 0 ) : ?>
										<span style="font-size:12px; color:#888; font-weight:normal; margin-left:6px;">(<?php echo $orders_count; ?> pedidos)</span>
									<?php endif; ?>
									<?php if ( ! empty( $order_channel ) ) : ?>
										<span class="myd-channel-badge"><?php
											if ( $order_channel === 'SYS' ) {
												echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 197.92 458.7"><path d="M193.05 18.99 7.16.03C3.86-.3.99 1.95.99 4.84v449.03c0 2.89 2.87 5.1 6.12 4.81l101.94-7.44c2.82-.25 4.96-2.34 4.96-4.85l-1.16-154.37c0-2.59 2.29-4.72 5.21-4.85l52.91-3.69c2.92-.17 5.21-2.26 5.21-4.85v-92.31c0-2.55-2.24-4.64-5.16-4.85l-55.29-4.64c-2.96-.17-5.25-2.38-5.16-5.02l3.25-40.26c.1-2.72 2.72-4.81 5.78-4.68l72.52 3.77c3.16.13 5.78-2.09 5.78-4.85v-102c0-2.45-2.07-4.48-4.87-4.8Zm-13.61 87.38c0 2.56-2.13 4.62-4.7 4.5l-75.16-3.5a4.49 4.49 0 0 0-4.7 4.35l-2.64 74.53c-.08 2.45 1.78 4.5 4.19 4.66l61.16 4.31c2.37.2 4.19 2.13 4.19 4.5v67.15c0 2.41-1.86 4.35-4.23 4.5l-59.22 3.43c-2.37.12-4.23 2.09-4.23 4.5l.94 152.65c0 2.33-1.74 4.27-4.03 4.5l-66.57 6.91c-2.64.27-4.97-1.78-4.97-4.46V21.88c0-2.68 2.33-4.78 5.01-4.46l151.01 17.6c2.27.3 3.95 2.19 3.95 4.46v66.88Z"/><path d="M175.49 35.03 24.48 17.42a4.488 4.488 0 0 0-5.01 4.46V438.9c0 2.68 2.33 4.74 4.97 4.46l66.57-6.91c2.29-.23 4.03-2.17 4.03-4.5L94.1 279.3c0-2.41 1.86-4.39 4.23-4.5l59.22-3.43c2.37-.16 4.23-2.09 4.23-4.5v-67.15c0-2.37-1.82-4.31-4.19-4.5l-61.16-4.31c-2.41-.16-4.27-2.21-4.19-4.66l2.64-74.53a4.495 4.495 0 0 1 4.7-4.35l75.16 3.5c2.56.12 4.7-1.94 4.7-4.5V39.49c0-2.28-1.68-4.16-3.95-4.46" style="fill:#fbb80b"/></svg>';
												echo '<span>Cardápio</span>';
											} elseif ( $order_channel === 'IFD' ) {
												echo '<svg fill="#eb0033" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M8.428 1.67c-4.65 0-7.184 4.149-7.184 6.998 0 2.294 2.2 3.299 4.25 3.299l-.006-.006c4.244 0 7.184-3.854 7.184-6.998 0-2.29-2.175-3.293-4.244-3.293m11.328 0c-4.65 0-7.184 4.149-7.184 6.998 0 2.294 2.2 3.299 4.25 3.299l-.006-.006C21.061 11.96 24 8.107 24 4.963c0-2.29-2.18-3.293-4.244-3.293m-5.584 12.85 2.435 1.834c-2.17 2.07-6.124 3.525-9.353 3.17A8.91 8.91 0 0 1 .23 14.541H0a9.6 9.6 0 0 0 8.828 7.758c3.814.24 7.323-.905 9.947-3.13l-.004.007 1.08 2.988 1.555-7.623-7.234-.02z"/></svg>';
												echo '<span>iFood</span>';
											} elseif ( $order_channel === 'WPP' ) {
												echo '<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><path d="M23.993 0C10.763 0 0 10.765 0 24a23.82 23.82 0 0 0 4.57 14.067l-2.99 8.917 9.224-2.948A23.8 23.8 0 0 0 24.007 48C37.237 48 48 37.234 48 24S37.238 0 24.007 0zm-6.7 12.19c-.466-1.114-.818-1.156-1.523-1.185a14 14 0 0 0-.804-.027c-.918 0-1.877.268-2.455.86-.705.72-2.454 2.398-2.454 5.841s2.51 6.773 2.849 7.239c.353.465 4.895 7.632 11.947 10.553 5.515 2.286 7.152 2.074 8.407 1.806 1.834-.395 4.133-1.75 4.711-3.386s.579-3.034.41-3.33c-.17-.296-.636-.465-1.34-.818-.706-.353-4.134-2.046-4.783-2.272-.634-.24-1.24-.155-1.72.522-.677.946-1.34 1.905-1.876 2.483-.423.452-1.115.509-1.693.268-.776-.324-2.948-1.086-5.628-3.47-2.074-1.849-3.484-4.148-3.893-4.84-.41-.705-.042-1.114.282-1.495.353-.438.691-.748 1.044-1.157.352-.41.55-.621.776-1.1.24-.466.07-.946-.1-1.3-.168-.352-1.579-3.795-2.157-5.191" fill="#67c15e" fill-rule="evenodd"/></svg>';
												echo '<span>WhatsApp</span>';
											} else {
												echo '<span>' . esc_html( $order_channel ) . '</span>';
											}
										?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
					<?php if ( ! empty( $table ) ) : ?>
						<div class="fdm-order-list-items-customer"><?php echo esc_html__( 'Table', 'myd-delivery-pro' ) . ' ' . esc_html( $table ); ?></div>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $address ) ) : ?>
					<?php
					$city = get_post_meta( $postid, 'order_city', true );
					$line_left_parts = array( (string) $addr, (string) $num_label );
					$line_left = trim( implode( ', ', array_filter( $line_left_parts, function( $v ){ return $v !== '' && $v !== null; } ) ) );
					$line_mid_parts = array();
					if ( ! empty( $neigh ) ) { $line_mid_parts[] = (string) $neigh; }
					if ( ! empty( $city ) ) { $line_mid_parts[] = (string) $city; }
					$line_mid = trim( implode( ' - ', $line_mid_parts ) );
					$line_zip = ! empty( $zip ) ? (string) $zip : '';

					$final_chunks = array();
					if ( $line_left !== '' ) { $final_chunks[] = $line_left; }
					if ( $line_mid !== '' ) { $final_chunks[] = $line_mid; }
					$final_address = '';
					if ( ! empty( $final_chunks ) ) {
						$final_address = implode( ' - ', $final_chunks );
					}
					if ( $final_address !== '' && $line_zip !== '' ) {
						$final_address .= ' • ' . $line_zip;
					} elseif ( $final_address === '' && $line_zip !== '' ) {
						$final_address = $line_zip;
					}
					?>
					
					<div class="myd-card-section">
						<!-- Styles moved to assets/css/panel-overrides.css -->
						<div class="myd-section-title myd-address-title" style="display: flex; align-items: center; gap: 8px;">
							<div class="myd-address-icon-ellipse" aria-hidden="true" style="align-self: center;">
								<svg viewBox="-3 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000" stroke="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>pin_fill_sharp_circle [#717171]</title> <desc>Created with Sketch.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-183.000000, -5399.000000)" fill="#717171"> <g id="icons" transform="translate(56.000000, 160.000000)"> <path d="M137,5246.635 C137,5244.978 135.657,5243.635 134,5243.635 C132.343,5243.635 131,5244.978 131,5246.635 C131,5248.292 132.343,5249.635 134,5249.635 C135.657,5249.635 137,5248.292 137,5246.635 M141,5246 C141,5249.866 134,5259 134,5259 C134,5259 127,5249.866 127,5246 C127,5242.134 130.134,5239 134,5239 C137.866,5239 141,5242.134 141,5246 M135,5246.635 C135,5247.186 134.551,5247.635 134,5247.635 C133.449,5247.635 133,5247.186 133,5246.635 C133,5246.084 133.449,5245.635 134,5245.635 C134.551,5245.635 135,5246.084 135,5246.635" id="pin_fill_sharp_circle-[#717171]"> </path> </g> </g> </g> </g></svg>
							</div>
							<div style="display: flex; flex-direction: column; justify-content: center;">
								<div class="myd-address-title-text"><?php echo esc_html( $final_address ); ?></div>
							<?php
								$real_neigh = get_post_meta( $postid, 'order_real_neighborhood', true );
								if ( ! empty( $real_neigh ) ) :
							?>
								<div class="myd-address-real-neighborhood-text">Bairro Real: <?php echo esc_html( $real_neigh ); ?></div>
							<?php endif; ?>
								<?php if ( ! empty( $comp ) ) : ?>
									<div class="myd-address-title-text myd-address-complement">
										<?php echo esc_html( $comp ); ?>
									</div>
								<?php endif; ?>
								<?php if ( ! empty( $ref ) ) : ?>
									<div class="fdm-order-list-items-customer"><?php echo esc_html__( 'Ponto de referência', 'myd-delivery-pro' ) . ': ' . esc_html( $ref ); ?></div>
								<?php endif; ?>
							</div>
						</div>
					</div>
					<?php
				$status_text = '';
				switch ( strtolower( (string) $status ) ) {
					case 'new': $status_text = __( 'Novo pedido', 'myd-delivery-pro' ); break;
					case 'confirmed': $status_text = __( 'Pedido em preparo', 'myd-delivery-pro' ); break;
					case 'in-delivery': $status_text = __( 'Em entrega', 'myd-delivery-pro' ); break;
					case 'finished': $status_text = __( 'Concluído', 'myd-delivery-pro' ); break;
					case 'canceled': $status_text = __( 'Cancelado', 'myd-delivery-pro' ); break;
				}
				?>
				<?php if ( $status_text ) : ?>
					<?php if ( strtolower( (string) $status ) === 'new' ) : ?>
						<div class="myd-status-banner myd-status-new" style="display:flex;gap:8px;">
							<div class="myd-new-icon" aria-hidden="true" style="align-self:center;">
								<!-- New SVG (color #F3AC2E) -->
								<svg viewBox="0 0 512 512" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000" stroke="#000000" style="width:20px;height:20px;">
									<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
									<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
									<g id="SVGRepo_iconCarrier"> <title>new-indicator-filled</title> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="scheduler" fill="#F3AC2E" transform="translate(85.333333, 85.333333)"> <path d="M170.666667,1.42108547e-14 C264.923264,-3.10380131e-15 341.333333,76.4100694 341.333333,170.666667 C341.333333,264.923264 264.923264,341.333333 170.666667,341.333333 C76.4100694,341.333333 2.57539587e-14,264.923264 1.42108547e-14,170.666667 C2.6677507e-15,76.4100694 76.4100694,3.15255107e-14 170.666667,1.42108547e-14 Z M192,85.3333333 L149.333333,85.3333333 L149.333333,149.333333 L85.3333333,149.333333 L85.3333333,192 L149.333333,191.999333 L149.333333,256 L192,256 L191.999333,191.999333 L256,192 L256,149.333333 L191.999333,149.333333 L192,85.3333333 Z" id="Combined-Shape"> </path> </g> </g> </g></svg>
							</div>
							<div class="myd-status-text"><?php echo esc_html( $status_text ); ?></div>
						</div>
					<?php elseif ( strtolower( (string) $status ) === 'finished' ) : ?>
						<div class="myd-status-banner myd-status-finished" style="display:flex;align-items:center;gap:8px;">
							<div class="myd-address-icon-ellipse" aria-hidden="true" style="align-self: center;">
								<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M8.25014 6.01489C8.25005 6.00994 8.25 6.00498 8.25 6V5C8.25 2.92893 9.92893 1.25 12 1.25C14.0711 1.25 15.75 2.92893 15.75 5V6C15.75 6.00498 15.75 6.00994 15.7499 6.0149C17.0371 6.05353 17.8248 6.1924 18.4261 6.69147C19.2593 7.38295 19.4787 8.55339 19.9177 10.8943L20.6677 14.8943C21.2849 18.186 21.5934 19.8318 20.6937 20.9159C19.794 22 18.1195 22 14.7704 22H9.22954C5.88048 22 4.20595 22 3.30624 20.9159C2.40652 19.8318 2.71512 18.186 3.33231 14.8943L4.08231 10.8943C4.52122 8.55339 4.74068 7.38295 5.57386 6.69147C6.17521 6.19239 6.96288 6.05353 8.25014 6.01489ZM9.75 5C9.75 3.75736 10.7574 2.75 12 2.75C13.2426 2.75 14.25 3.75736 14.25 5V6C14.25 5.99999 14.25 6.00001 14.25 6C14.1747 5.99998 14.0982 6 14.0204 6H9.97954C9.90177 6 9.82526 6 9.75 6.00002C9.75 6.00002 9.75 6.00003 9.75 6.00002V5ZM15.4685 10.9144C15.792 11.1731 15.8444 11.6451 15.5856 11.9685L11.5856 16.9685C11.4524 17.1351 11.2545 17.2371 11.0415 17.2489C10.8285 17.2607 10.6205 17.1812 10.4697 17.0304L8.46965 15.0304C8.17676 14.7375 8.17676 14.2626 8.46965 13.9697C8.76255 13.6768 9.23742 13.6768 9.53031 13.9697L10.9375 15.3769L14.4143 11.0315C14.6731 10.7081 15.1451 10.6556 15.4685 10.9144Z" fill="#0f7a3a"></path> </g></svg>
							</div>
							<div class="myd-status-text"><?php echo esc_html( $status_text ); ?></div>
						</div>
					<?php elseif ( strtolower( (string) $status ) === 'confirmed' ) : ?>
						<div class="myd-status-banner myd-status-confirmed" style="display:flex;align-items:center;gap:8px;">
							<div class="myd-confirmed-icon" aria-hidden="true" style="align-self:center;">
								<!-- Confirmed SVG (color #0b72c1) -->
								<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;">
									<style type="text/css"> .st0{fill:#0b72c1;} </style>
									<g>
										<path class="st0" d="M493.409,370.168c0-109.597-75.965-214.36-176.965-244.446c2.23-6.615,3.358-13.404,3.358-20.213 c0-35.183-28.622-63.805-63.802-63.805s-63.802,28.622-63.802,63.805c0,6.809,1.128,13.598,3.358,20.213 c-101,30.086-176.963,134.849-176.963,244.446v1.592h474.816V370.168z M158.362,188.059c21.042,0,38.162,17.116,38.162,38.16 c0,21.04-17.12,38.16-38.162,38.16s-38.162-17.12-38.162-38.16C120.2,205.175,137.32,188.059,158.362,188.059z M256,76.689 c15.893,0,28.822,12.927,28.822,28.82c0,4.433-1.089,8.792-3.24,12.97c-8.702-1.075-17.302-1.619-25.582-1.619 c-8.28,0-16.881,0.544-25.582,1.619c-2.15-4.177-3.24-8.536-3.24-12.97C227.178,89.616,240.107,76.689,256,76.689z"></path>
										<path class="st0" d="M484.488,403.51H27.514C12.343,403.51,0,415.851,0,431.022v30.276c0,4.961,4.035,8.998,8.996,8.998h494.009 c4.959,0,8.994-4.037,8.994-8.998v-30.276C512,415.851,499.659,403.51,484.488,403.51z"></path>
									</g>
								</svg>
							</div>
							<div class="myd-status-text"><?php echo esc_html( $status_text ); ?></div>
						</div>
					
					<?php elseif ( strtolower( (string) $status ) === 'in-delivery' ) : ?>
						<div class="myd-status-banner myd-status-in-delivery" style="display:flex;align-items:center;gap:8px;">
							<div class="myd-in-delivery-icon" aria-hidden="true" style="align-self:center;flex:0 0:20px;">
								<!-- In-delivery SVG (color #ff9000) -->
								<svg fill="#ff9000" viewBox="0 0 460.427 460.427" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;overflow:visible;">
									<g><g><g><g>
									<circle cx="225.108" cy="41.302" r="38.373"></circle>
									<path d="M397.111,330.868c-7.377,0-14.458,1.281-21.047,3.611l-3.12-9.271c6.069-1.88,12.506-2.914,19.175-2.957 c8.102-0.052,15.106-6.153,15.609-14.239c0.549-8.84-6.458-16.18-15.179-16.18c-10.213,0-20.059,1.595-29.309,4.541l-20.71-61.54 h9.315c3.709,7.964,10.934,13.96,19.685,15.978c3.329,0.768,6.52-1.762,6.52-5.191v-45.936c0-3.406-3.164-5.96-6.484-5.199 c-8.767,2.01-16.007,8.012-19.721,15.987h-4.463c-2.762,3.551-6.192,6.541-10.09,8.794c-9.936,5.744-18.371,4.656-24.252,4.314 c1.41,4.189,9.775,29.046,11.571,34.383c-1.71,1.595,3.555-6.344-68.423,106.855h-0.684c-2.564,8.595-6.261,15.549-14.333,21.473 c-1.468,1.077-3.017,2.038-4.623,2.888h19.837c8.186,0,15.805-4.183,20.2-11.09l57.851-90.93l6.585,19.567 c-27.031,17.072-45.069,47.145-45.247,81.37c-0.043,8.292,6.381,15.424,14.668,15.71c8.646,0.299,15.749-6.621,15.749-15.2 c0-20.938,9.758-39.629,24.953-51.8l3.515,10.444c-13.426,12.156-21.633,29.974-20.806,49.648 c1.368,32.53,27.712,59.008,60.235,60.529c36.281,1.697,66.339-27.33,66.339-63.245 C460.427,359.272,432.024,330.868,397.111,330.868z M397.111,416.942c-12.549,0-22.758-10.209-22.758-22.758 s10.209-22.758,22.758-22.758s22.758,10.209,22.758,22.758C419.869,406.733,409.66,416.942,397.111,416.942z"></path>
									<path d="M269.437,259.727c3.117-10.448-2.336-21.534-12.515-25.441l-23.595-9.057l1.407-6.988 c-7.085-2.401-12.47-8.33-14.186-15.588l-13.618-57.806l28.632,49.039c2.935,5.028,8.193,8.252,14.005,8.59l69.342,4.03 c9.601,0.554,17.808-6.774,18.365-16.348c0.557-9.585-6.762-17.807-16.348-18.365l-60.037-3.489l-23.933-40.989l13.567,12.518 l1.624-8.065c2.827-14.035-6.26-27.703-20.294-30.53l-45.317-9.127c-14.035-2.826-27.703,6.26-30.53,20.294l-14.561,72.305 v-69.587c0-4.846-3.929-8.775-8.775-8.775H32.603c-4.846,0-8.775,3.929-8.775,8.775v127.689h-6.084 c-9.8,0-17.744,7.944-17.744,17.744c0,9.8,7.944,17.744,17.744,17.744h73.004v27.041c-29.827,11.281-52.235,37.663-57.884,69.823 c-1.275,7.26,4.317,13.919,11.7,13.919h15.524c-0.135,1.684-0.223,3.381-0.223,5.099c0,34.912,28.403,63.316,63.316,63.316 c34.912,0,63.316-28.403,63.316-63.316c0-1.686-0.086-3.351-0.216-5.004h15.02c-15.51-8.246-23.512-26.573-18.347-43.889 l19.983-66.989h17.417l-21.406,71.76c-3.294,11.041,2.987,22.662,14.028,25.956c11.042,3.294,22.663-2.988,25.956-14.028 L269.437,259.727z M123.18,416.942c-12.549,0-22.758-10.209-22.758-22.758c0-1.753,0.206-3.458,0.583-5.099h44.35 c0.377,1.64,0.583,3.345,0.583,5.099C145.938,406.733,135.728,416.942,123.18,416.942z M141.446,242.814v-17.198 c2.926,6.698,7.462,12.621,13.223,17.198H141.446z"></path>
									</g></g></g></g>
								</svg>
							</div>
							<div class="myd-status-text"><?php echo esc_html( $status_text ); ?></div>
						</div>
					<?php else : ?>
						<div class="myd-status-banner"><?php echo esc_html( $status_text ); ?></div>
					<?php endif; ?>
				<?php endif; ?>
				<?php endif; ?>
				</div>

						<?php
						// Get estimated delivery time from saved meta, or calculate if not available
						$estimated_delivery = get_post_meta($postid, 'order_estimated_delivery', true);
						if ($estimated_delivery) {
							$eta_ts = strtotime($estimated_delivery);
						} else {
							// Fallback calculation: order time + preparation time + delivery time
							$avg_prep_time = (int) get_option('myd-average-preparation-time', 30);
							$avg_delivery_time_str = get_option('fdm-estimate-time-delivery', '30');
							preg_match('/(\d+)/', $avg_delivery_time_str, $matches);
							$avg_delivery_time = isset($matches[1]) ? (int) $matches[1] : 30;
							$total_minutes = $avg_prep_time + $avg_delivery_time;
							
							$order_date_raw = get_post_meta($postid, 'order_date', true);
							$eta_ts = $order_date_raw ? (strtotime($order_date_raw) + ($total_minutes * 60)) : (time() + ($total_minutes * 60));
						}
						$eta_text = gmdate('H:i', $eta_ts);


						
						// Dados
						$customer_phone = isset($customer_phone) ? $customer_phone : get_post_meta($postid, 'customer_phone', true);
						$order_number = get_the_title($postid);
						?>

						<div class="myd-info-chips">
											<div class="myd-chip">
												<div class="myd-chip-label">
													<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="myd-chip-icon"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M16.5562 12.9062L16.1007 13.359C16.1007 13.359 15.0181 14.4355 12.0631 11.4972C9.10812 8.55901 10.1907 7.48257 10.1907 7.48257L10.4775 7.19738C11.1841 6.49484 11.2507 5.36691 10.6342 4.54348L9.37326 2.85908C8.61028 1.83992 7.13596 1.70529 6.26145 2.57483L4.69185 4.13552C4.25823 4.56668 3.96765 5.12559 4.00289 5.74561C4.09304 7.33182 4.81071 10.7447 8.81536 14.7266C13.0621 18.9492 17.0468 19.117 18.6763 18.9651C19.1917 18.9171 19.6399 18.6546 20.0011 18.2954L21.4217 16.883C22.3806 15.9295 22.1102 14.2949 20.8833 13.628L18.9728 12.5894C18.1672 12.1515 17.1858 12.2801 16.5562 12.9062Z" fill="#bdbaba"></path> </g></svg>
													<span class="myd-chip-text">Telefone</span>
												</div>
												<div class="myd-chip-value"><?php echo esc_html($customer_phone ?: '-'); ?></div>
											</div>
											<div class="myd-chip">
												<div class="myd-chip-label">
													<svg viewBox="0 0 19 19" xmlns="http://www.w3.org/2000/svg" fill="#000000" aria-hidden="true" class="myd-chip-icon"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill="#bdbaba" fill-rule="evenodd" d="M207.960546,159.843246 L210.399107,161.251151 C210.637153,161.388586 210.71416,161.70086 210.580127,161.933013 C210.442056,162.172159 210.144067,162.258604 209.899107,162.117176 L207.419233,160.68542 C207.165323,160.8826 206.846372,161 206.5,161 C205.671573,161 205,160.328427 205,159.5 C205,158.846891 205.417404,158.291271 206,158.085353 L206,153.503423 C206,153.22539 206.231934,153 206.5,153 C206.776142,153 207,153.232903 207,153.503423 L207,158.085353 C207.582596,158.291271 208,158.846891 208,159.5 C208,159.6181 207.986351,159.733013 207.960546,159.843246 Z M206.5,169 C211.746705,169 216,164.746705 216,159.5 C216,154.253295 211.746705,150 206.5,150 C201.253295,150 197,154.253295 197,159.5 C197,164.746705 201.253295,169 206.5,169 Z" transform="translate(-197 -150)"></path> </g></svg>
													<span class="myd-chip-text">Entrega prevista</span>
												</div>
												<div class="myd-chip-value"><?php echo esc_html($eta_text); ?></div>
											</div>
											<?php $order_locator = get_post_meta( $postid, 'order_locator', true ); ?>
											<div class="myd-chip">
												<div class="myd-chip-label">
													<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="myd-chip-icon"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M5.58579 4.58579C5 5.17157 5 6.11438 5 8V17C5 18.8856 5 19.8284 5.58579 20.4142C6.17157 21 7.11438 21 9 21H15C16.8856 21 17.8284 21 18.4142 20.4142C19 19.8284 19 18.8856 19 17V8C19 6.11438 19 5.17157 18.4142 4.58579C17.8284 4 16.8856 4 15 4H9C7.11438 4 6.17157 4 5.58579 4.58579ZM9 8C8.44772 8 8 8.44772 8 9C8 9.55228 8.44772 10 9 10H15C15.5523 10 16 9.55228 16 9C16 8.44772 15.5523 8 15 8H9ZM9 12C8.44772 12 8 12.4477 8 13C8 13.5523 8.44772 14 9 14H15C15.5523 14 16 13.5523 16 13C16 12.4477 15.5523 12 15 12H9ZM9 16C8.44772 16 8 16.4477 8 17C8 17.5523 8.44772 18 9 18H13C13.5523 18 14 17.5523 14 17C14 16.4477 13.5523 16 13 16H9Z" fill="#bdbaba"></path> </g></svg>
													<span class="myd-chip-text"><?php echo esc_html__( 'Localizador', 'myd-delivery-pro' ); ?></span>
												</div>
												<?php
												// Format the order locator into groups of 4 characters (e.g. 0000 0000)
												if ( ! empty( $order_locator ) ) {
													// remove any existing whitespace
													$clean = preg_replace( '/\s+/', '', $order_locator );
													// split in chunks of 4 with a space, chunk_split adds a trailing space
													$display_value = rtrim( chunk_split( $clean, 4, ' ' ) );
												} else {
													// Exibe placeholder aguardando localizador real
													$display_value = __( 'Aguardando...', 'myd-delivery-pro' );
												}
												?>
												<div class="myd-chip-value"><?php echo esc_html( $display_value ); ?></div>
											</div>
						</div>

			</div><!-- /.myd-detail-header -->

			<div class="myd-detail-scroll">

				<!-- .myd-detail-scroll styles moved to assets/css/panel-overrides.css -->
			<script>
			(function(){
				if (window.__MYD_SCROLL_RESIZER_INIT__) return; // one-time guard
				window.__MYD_SCROLL_RESIZER_INIT__ = true;

				function px(n){ return (typeof n === 'number' && isFinite(n)) ? n : 0; }

				function resizeAll(){
					try {
						var containers = document.querySelectorAll('.fdm-orders-full-items');
						var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
						containers.forEach(function(wrap){
							var scroll = wrap.querySelector('.myd-detail-scroll');
							if (!scroll) return;

							var actions = wrap.querySelector('.myd-quick-actions');
							var rect = scroll.getBoundingClientRect();
							var topOffset = px(rect.top);
							var actionsHeight = actions ? px(actions.getBoundingClientRect().height) : 0;
							var safePadding = 5; // prevents overlap with quick actions

						var available = Math.max(120, viewportHeight - topOffset - actionsHeight - safePadding);
							scroll.style.height = available + 'px';
							scroll.style.overflowY = 'auto';

						});
					} catch(_e){}
				}


				function rafResizeChain(){
					resizeAll();
					if (window.requestAnimationFrame){
						requestAnimationFrame(function(){
							resizeAll();
							requestAnimationFrame(function(){ resizeAll(); });
						});
					}
				}

				function debounce(fn, wait){ var t; return function(){ clearTimeout(t); var args = arguments; t = setTimeout(function(){ fn.apply(null, args); }, wait); }; }
				var debouncedResize = debounce(resizeAll, 60);

				var ro;
				if (window.ResizeObserver){
					ro = new ResizeObserver(function(){ debouncedResize(); });
				}

				var mo;
				if (window.MutationObserver){
					mo = new MutationObserver(function(){ debouncedResize(); });
				}

				function init(){
					rafResizeChain();
					window.addEventListener('resize', resizeAll);
					window.addEventListener('orientationchange', resizeAll);
					window.addEventListener('load', resizeAll);
					try {
						if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
							document.fonts.ready.then(function(){ setTimeout(resizeAll, 0); setTimeout(resizeAll, 200); });
						}
					} catch(_err){}
					// Recalc when user clicks an order or action button
					document.addEventListener('click', function(e){
						if (e.target.closest('.fdm-orders-items') || e.target.closest('[data-manage-order-id]')){
							setTimeout(rafResizeChain, 0);
							setTimeout(resizeAll, 250);
						}
					}, true);
					// Recalc after CSS transitions that may affect layout
					document.addEventListener('transitionend', function(e){
						if (e.target.closest && (e.target.closest('.fdm-orders-full-items') || e.target.closest('.myd-detail-header') || e.target.closest('.myd-quick-actions'))){
							debouncedResize();
						}
					}, true);
					// Observe changes that might alter actions/header height
					if (ro){
						document.querySelectorAll('.myd-detail-header, .myd-quick-actions').forEach(function(el){ ro.observe(el); });
					}
					// Observe DOM updates (when switching orders or injecting content)
					if (mo){
						var roots = document.querySelectorAll('.fdm-orders-full-items, .fdm-orders-loop, body');
						roots.forEach(function(r){ try { mo.observe(r, { childList:true, subtree:true }); } catch(_e){} });
					}
					// In case late content loads
					setTimeout(resizeAll, 100);
					setTimeout(resizeAll, 300);
					setTimeout(resizeAll, 800);
					setTimeout(resizeAll, 1500);
				}

				if (document.readyState === 'loading'){
					document.addEventListener('DOMContentLoaded', init);
				} else {
					init();
				}
			})();
			</script>

				<!-- Color override moved to assets/css/panel-overrides.css -->

			<div class="myd-order-card">
				


				<div class="myd-card-section">
					<!-- .myd-title-with-icon styles moved to assets/css/panel-overrides.css -->
					<div class="myd-section-title">
						<span class="myd-title-with-icon">
							<span aria-hidden="true">
								<svg width="5" height="5" class="myd-billing-icon" viewBox="0 -0.5 25 25" fill="none" xmlns="http://www.w3.org/2000/svg" stroke=""><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M9.5852 17.982L7.8182 18.882C7.74952 18.9162 7.66799 18.9121 7.60305 18.8713C7.53811 18.8304 7.49914 18.7587 7.5002 18.682V5.955C7.4896 5.4384 7.89961 5.01093 8.4162 5H17.5832C18.0998 5.01093 18.5098 5.4384 18.4992 5.955V18.682C18.5003 18.7587 18.4613 18.8304 18.3963 18.8713C18.3314 18.9121 18.2499 18.9162 18.1812 18.882L16.4142 17.982C16.3438 17.9461 16.2595 17.9514 16.1942 17.996L14.7722 18.962C14.6986 19.0121 14.6018 19.0121 14.5282 18.962L13.1222 18.007C13.0486 17.9569 12.9518 17.9569 12.8782 18.007L11.4722 18.962C11.3986 19.0121 11.3018 19.0121 11.2282 18.962L9.8052 18C9.7407 17.9542 9.6563 17.9472 9.5852 17.982Z" stroke="#717171" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M9.9992 11.25C9.58499 11.25 9.2492 11.5858 9.2492 12C9.2492 12.4142 9.58499 12.75 9.9992 12.75V11.25ZM15.9992 12.75C16.4134 12.75 16.7492 12.4142 16.7492 12C16.7492 11.5858 16.4134 11.25 15.9992 11.25V12.75ZM10.9992 13.25C10.585 13.25 10.2492 13.5858 10.2492 14C10.2492 14.4142 10.585 14.75 10.9992 14.75V13.25ZM14.9992 14.75C15.4134 14.75 15.7492 14.4142 15.7492 14C15.7492 13.5858 15.4134 13.25 14.9992 13.25V14.75ZM10.9992 9.25C10.585 9.25 10.2492 9.58579 10.2492 10C10.2492 10.4142 10.585 10.75 10.9992 10.75V9.25ZM14.9992 10.75C15.4134 10.75 15.7492 10.4142 15.7492 10C15.7492 9.58579 15.4134 9.25 14.9992 9.25V10.75ZM9.9992 12.75H15.9992V11.25H9.9992V12.75ZM10.9992 14.75H14.9992V13.25H10.9992V14.75ZM10.9992 10.75H14.9992V9.25H10.9992V10.75Z" fill="#717171"></path> </g></svg>
						</span>
						<span><?php esc_html_e( 'Itens no pedido', 'myd-delivery-pro' ); ?></span>
					</div>
					<div class="fdm-orders-items-products">
						<div class="fdm-order-list-items">
							<?php $items = \MydPro\Includes\Myd_Orders_Front_Panel::parse_order_items( get_post_meta( $postid, 'myd_order_items', true ) ); ?>
							<?php if ( ! empty( $items ) ) : ?>
								<?php foreach ( $items as $value ) : ?>
									<div class="fdm-products-order-loop">
										<?php
												$image_url = '';
												// Primeiro, tentar imagem armazenada dentro dos itens do pedido (product_image salvo ao criar o pedido)
												if ( ! empty( $value['product_image'] ) ) {
													$image_id = intval( $value['product_image'] );
													if ( $image_id ) {
														$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
													}
												}
												// Se não houver, tentar obter via post meta do produto (compatibilidade antiga)
												if ( empty( $image_url ) && ! empty( $value['id'] ) ) {
													$image_id = get_post_meta( (int) $value['id'], 'product_image', true );
													if ( $image_id ) {
														$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
													}
												}
												$thumb_url = $image_url ? $image_url : 'https://franguxo.app.br/wp-content/uploads/2025/07/FRANGUXO-LOGO-FUNDO-BRANCO-1024x1024.webp';
												$thumb_style = 'background-image:url(' . esc_url( $thumb_url ) . '); background-size:cover; background-position:center; background-repeat:no-repeat;';
										?>
										<div class="myd-item-thumb" style="<?php echo esc_attr( $thumb_style ); ?>"></div>
										<div class="myd-item-body">
											<div class="myd-item-row">
												<div class="fdm-order-list-items-product myd-item-title"><?php echo esc_html( $value['product_name'] ); ?></div>
												<div class="myd-item-price"><?php echo esc_html( Store_Data::get_store_data( 'currency_simbol' ) . ' ' . $value['product_price'] ); ?></div>
											</div>
											<?php
											$extras_text = '';
											if ( ! empty( $value['extras'] ) && is_array( $value['extras'] ) && ! empty( $value['extras']['groups'] ) ) {
												$groups_out = array();
												foreach ( $value['extras']['groups'] as $group ) {
													$items_out = array();
													if ( isset( $group['items'] ) && is_array( $group['items'] ) ) {
														foreach ( $group['items'] as $item ) {
															$qty = isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
															$name = isset( $item['name'] ) ? (string) $item['name'] : '';
															if ( $qty > 0 && $name !== '' ) {
																$extra_price = isset( $item['price'] ) ? floatval( $item['price'] ) : 0;
														$items_out[] = $name . ( $qty > 1 ? ' (' . $qty . 'x)' : '' ) . '||R$ ' . number_format( $extra_price * $qty, 2, ',', '.' );
															}
														}
													}
													if ( ! empty( $items_out ) ) {
														$group_name = isset( $group['group'] ) ? (string) $group['group'] : '';
														$groups_out[] = $group_name . ':' . PHP_EOL . implode( PHP_EOL, $items_out ) . PHP_EOL;
													}
												}
												$extras_text = implode( PHP_EOL, $groups_out );
											} elseif ( ! empty( $value['product_extras'] ) ) {
												$extras_text = (string) $value['product_extras'];
											}
											if ( $extras_text !== '' ) {
										$lines = explode( PHP_EOL, $extras_text );
										echo '<div class="myd-extra-group">';
										foreach ( $lines as $line ) {
											$line = trim( $line );
											if ( $line === '' ) continue;
											if ( strpos( $line, '||' ) !== false ) {
												$parts = explode( '||', $line, 2 );
												echo '<div class="myd-extra-item" style="display:flex;justify-content:space-between;align-items:center;">';
												echo '<span>' . esc_html( trim( $parts[0] ) ) . '</span>';
												echo '<span class="myd-item-price">' . esc_html( trim( $parts[1] ) ) . '</span>';
												echo '</div>';
											} elseif ( substr( $line, -1 ) === ':' ) {
												echo '<div class="myd-extra-title" style="font-weight:bold;">' . esc_html( $line ) . '</div>';
											} else {
												echo '<div>' . esc_html( $line ) . '</div>';
											}
										}
										echo '</div>';
									}
											?>

											<?php if ( ! empty( $value['product_note'] ) ) : ?>
												<div class="myd-note"><?php echo esc_html__( 'Note', 'myd-delivery-pro' ) . ': ' . esc_html( $value['product_note'] ); ?></div>
											<?php endif; ?>
										</div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>

							<div class="myd-card-section">
								<div class="myd-section-title"><?php esc_html_e( 'Resumo do pedido', 'myd-delivery-pro' ); ?></div>
								<div class="myd-summary-list">
									<div class="myd-summary-label"><?php esc_html_e( 'Subtotal', 'myd-delivery-pro' ); ?></div>
									<div class="myd-summary-value"><?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( get_post_meta( $postid, 'order_subtotal', true ) ); ?></div>
									<div class="myd-summary-label"><?php esc_html_e( 'Delivery', 'myd-delivery-pro' ); ?></div>
									<div class="myd-summary-value"><?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( get_post_meta( $postid, 'order_delivery_price', true ) ); ?></div>

									<?php
										// Se o pedido teve desconto por fidelidade, exibe linha específica abaixo do delivery
										$loyalty_redeemed = get_post_meta( $postid, 'order_loyalty_redeemed', true );
										$loyalty_discount = get_post_meta( $postid, 'order_fidelity_discount', true );
										if ( ! empty( $loyalty_redeemed ) && (string) $loyalty_redeemed === '1' && ! empty( $loyalty_discount ) && floatval( $loyalty_discount ) > 0 ) :
									?>
										<div class="myd-summary-label"><?php esc_html_e( 'Desconto aplicado (FIDELIDADE)', 'myd-delivery-pro' ); ?></div>
										<div class="myd-summary-value myd-summary-value--coupon">-<?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( $loyalty_discount ); ?></div>
									<?php endif; ?>

									<?php if ( ! empty( $coupon ) && current_user_can( 'manage_options' ) ) : ?>

										<?php $coupon_discount = get_post_meta( $postid, 'order_coupon_discount', true ); ?>
										<?php if ( ! empty( $coupon_discount ) && floatval( $coupon_discount ) > 0 ) : ?>
											<div class="myd-summary-label"><?php echo sprintf( esc_html__( 'Desconto Aplicado (Cupom: %s)', 'myd-delivery-pro' ), esc_html( $coupon ) ); ?></div>
											<div class="myd-summary-value myd-summary-value--coupon">-<?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( $coupon_discount ); ?></div>
										<?php endif; ?>
									<?php endif; ?>

									<?php if ( ! empty( $change ) ) : ?>
										<?php
											// Calcular apenas para exibição no painel: troco informado menos total do pedido
											$raw_total_display = get_post_meta( $postid, 'order_total', true );
											$dec_sep = \MydPro\Includes\Store_Data::get_store_data( 'decimal_separator' ) ?: ',';
											$normalized_total = str_replace( '.', '', (string) $raw_total_display );
											if ( $dec_sep !== '.' ) {
												$normalized_total = str_replace( $dec_sep, '.', $normalized_total );
											}
											$total_value = (float) $normalized_total;

											// $change pode vir no formato '1234,56' (vírgula decimal). Normalizar para float
											$normalized_change = str_replace( '.', '', (string) $change );
											if ( $dec_sep !== '.' ) {
												$normalized_change = str_replace( $dec_sep, '.', $normalized_change );
											}
											$change_value = (float) $normalized_change;

											$diff = $change_value - $total_value;
										?>
									<?php endif; ?>

								</div>
							</div>

							<?php
							$pmethod = (string) get_post_meta( $postid, 'order_payment_method', true );
							$pmethod_label = isset($payment_method_labels[$pmethod]) ? $payment_method_labels[$pmethod] : $pmethod;
							$pstatus = (string) get_post_meta( $postid, 'order_payment_status', true );
							// Seção separada para o card de cobrança
							if ( strtolower( $pstatus ) === 'waiting' ) :
								if ( $pmethod !== '' ) :
							?>
							<div class="myd-card-section myd-billing-card">
								<span aria-hidden="true" class="myd-icon-slot">
								<?php if (strtoupper($pmethod) === 'DIN') : ?>
									<!-- SVG Dinheiro -->
									<svg width="24" height="24" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" fill="#6FA945" style="display:inline-block;vertical-align:middle;"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path style="fill:#6FA945;" d="M512,372.87c0,9.22-7.475,16.696-16.696,16.696H16.696C7.475,389.565,0,382.09,0,372.87V139.13 c0-9.22,7.475-16.696,16.696-16.696h478.609c9.22,0,16.696,7.475,16.696,16.696V372.87z"></path> <path style="fill:#91DC5A;" d="M395.13,356.174H116.87c-9.223,0-16.696-7.536-16.696-16.76c0-13.304-5.228-25.896-14.728-35.396 c-9.489-9.494-22.049-14.739-35.359-14.739c-9.223,0-16.696-7.489-16.696-16.711v-33.391c0-9.223,7.473-16.696,16.696-16.696 c13.31,0,25.869-5.228,35.364-14.723s14.723-21.99,14.723-35.3c0-9.223,7.473-16.631,16.696-16.631H395.13 c9.223,0,16.696,7.408,16.696,16.631c0,13.31,5.228,25.837,14.723,35.332c9.5,9.494,22.059,14.707,35.364,14.707 c9.223,0,16.696,7.456,16.696,16.68v33.391c0,9.223-7.473,16.696-16.696,16.696c-13.304,0-25.864,5.228-35.364,14.728 c-9.494,9.494-14.723,22.118-14.723,35.423C411.826,348.637,404.353,356.174,395.13,356.174z"></path> <path style="fill:#6FA945;" d="M495.304,122.435H256v267.13h239.304c9.223,0,16.696-7.601,16.696-16.824V139.002 C512,129.78,504.527,122.435,495.304,122.435z"></path> <path style="fill:#91DC5A;" d="M461.913,222.481c-13.304,0-25.864-5.228-35.364-14.723c-9.495-9.494-14.723-21.99-14.723-35.3 c0-9.223-7.473-16.631-16.696-16.631H256v200.348h139.13c9.223,0,16.696-7.536,16.696-16.76c0-13.304,5.228-25.896,14.723-35.39 c9.5-9.5,22.059-14.744,35.364-14.744c9.223,0,16.696-7.489,16.696-16.711v-33.391C478.609,229.954,471.136,222.481,461.913,222.481 z"></path> <path style="fill:#6FA945;" d="M256,322.655c-36.826,0-66.783-29.956-66.783-66.783s29.956-66.783,66.783-66.783 s66.783,29.956,66.783,66.783S292.826,322.655,256,322.655z M256,222.481c-18.413,0-33.391,14.978-33.391,33.391 s14.978,33.391,33.391,33.391s33.391-14.978,33.391-33.391S274.413,222.481,256,222.481z"></path> <path style="fill:#6FA945;" d="M289.391,255.872c0,18.413-14.978,33.391-33.391,33.391v33.391c36.826,0,66.783-29.956,66.783-66.783 S292.826,189.089,256,189.089v33.391C274.413,222.481,289.391,237.459,289.391,255.872z"></path> </g></svg>
								<?php elseif (strtolower($pmethod) === 'pix') : ?>
									<!-- Pix SVG -->
									<svg width="24" height="24" class="myd-billing-icon" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M11.917 11.71a2.046 2.046 0 0 1-1.454-.602l-2.1-2.1a.4.4 0 0 0-.551 0l-2.108 2.108a2.044 2.044 0 0 1-1.454.602h-.414l2.66 2.66c.83.83 2.177.83 3.007 0l2.667-2.668h-.253zM4.25 4.282c.55 0 1.066.214 1.454.602l2.108 2.108a.39.39 0 0 0 .552 0l2.1-2.1a2.044 2.044 0 0 1 1.453-.602h.253L9.503 1.623a2.127 2.127 0 0 0-3.007 0l-2.66 2.66h.414z"></path><path d="m14.377 6.496-1.612-1.612a.307.307 0 0 1-.114.023h-.733c-.379 0-.75.154-1.017.422l-2.1 2.1a1.005 1.005 0 0 1-1.425 0L5.268 5.32a1.448 1.448 0 0 0-1.018-.422h-.9a.306.306 0 0 1-.109-.021L1.623 6.496c-.83.83-.83 2.177 0 3.008l1.618 1.618a.305.305 0 0 1 .108-.022h.901c.38 0 .75-.153 1.018-.421L7.375 8.57a1.034 1.034 0 0 1 1.426 0l2.1 2.1c.267.268.638.421 1.017.421h.733c.04 0 .079.01.114.024l1.612-1.612c.83-.83.83-2.178 0-3.008z"></path></g></svg>
								<?php else : ?>
									<!-- Non-Pix SVG -->
									<svg width="24" height="24" style="width:24px;height:24px;display:block;flex:0 0 24px;color:inherit;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M14 4H10C6.22876 4 4.34315 4 3.17157 5.17157C2.32803 6.01511 2.09185 7.22882 2.02572 9.25H21.9743C21.9082 7.22882 21.672 6.01511 20.8284 5.17157C19.6569 4 17.7712 4 14 4Z" fill="#000000"></path> <path d="M10 20H14C17.7712 20 19.6569 20 20.8284 18.8284C22 17.6569 22 15.7712 22 12C22 11.5581 22 11.142 21.9981 10.75H2.00189C2 11.142 2 11.5581 2 12C2 15.7712 2 17.6569 3.17157 18.8284C4.34315 20 6.22876 20 10 20Z" fill="#000000"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M5.25 16C5.25 15.5858 5.58579 15.25 6 15.25H10C10.4142 15.25 10.75 15.5858 10.75 16C10.75 16.4142 10.4142 16.75 10 16.75H6C5.58579 16.75 5.25 16.4142 5.25 16Z" fill="white"></path> <path fill-rule="evenodd" clip-rule="evenodd" d="M11.75 16C11.75 15.5858 12.0858 15.25 12.5 15.25H14C14.4142 15.25 14.75 15.5858 14.75 16C14.75 16.4142 14.4142 16.75 14 16.75H12.5C12.0858 16.75 11.75 16.4142 11.75 16Z" fill="white"></path> </g></svg>
								<?php endif; ?>
								</span>
								<div class="myd-strong">
									<?php esc_html_e( 'Cobrar do cliente', 'myd-delivery-pro' ); ?> - (<?php echo esc_html( $pmethod_label ); ?>)
								</div>
								<?php
									$raw_total = get_post_meta( $postid, 'order_total', true );
									// Normalize stored formatted price (e.g. "59,99") to float safely
									$dec_sep = \MydPro\Includes\Store_Data::get_store_data( 'decimal_separator' ) ?: ',';
									// Stored prices are formatted with thousands sep '.' by format_price(), remove it
									$normalized = str_replace('.', '', (string) $raw_total);
									// Replace decimal separator with dot for float casting
									if ( $dec_sep !== '.' ) {
										$normalized = str_replace( $dec_sep, '.', $normalized );
									}
									$total_value = (float) $normalized;
								?>
								<div class="myd-billing-amount"><?php echo esc_html( $currency_simbol ); ?> <?php echo esc_html( number_format( $total_value, 2, ',', '.' ) ); ?></div>
							</div>

							<?php
							// Exibir card separado com o troco calculado (order_change - order_total)
							$card_change = get_post_meta( $postid, 'order_change', true );
							if ( ! empty( $card_change ) && strtoupper( (string) $pmethod ) === 'DIN' ) :
								// Normalizar separadores decimais
								$dec_sep_panel = \MydPro\Includes\Store_Data::get_store_data( 'decimal_separator' ) ?: ',';
								$raw_total_panel = get_post_meta( $postid, 'order_total', true );
								$norm_total_panel = str_replace( '.', '', (string) $raw_total_panel );
								if ( $dec_sep_panel !== '.' ) {
									$norm_total_panel = str_replace( $dec_sep_panel, '.', $norm_total_panel );
								}
								$total_panel_value = (float) $norm_total_panel;

								$norm_change_panel = str_replace( '.', '', (string) $card_change );
								if ( $dec_sep_panel !== '.' ) {
									$norm_change_panel = str_replace( $dec_sep_panel, '.', $norm_change_panel );
								}
								$change_panel_value = (float) $norm_change_panel;
								$troco_diff = $change_panel_value - $total_panel_value;
								if ( $troco_diff > 0 ) : ?>
									<div class="myd-card-section myd-troco-card" style="margin-top:12px;">
										<div class="myd-summary-list">
											<div class="myd-summary-label"><?php esc_html_e( 'Valor a receber em dinheiro', 'myd-delivery-pro' ); ?></div>
											<div class="myd-summary-value"><?php echo esc_html( $currency_simbol . ' ' . number_format( $change_panel_value, 2, ',', '.' ) ); ?></div>
											<div class="myd-summary-label"><?php esc_html_e( 'Valor para levar de troco', 'myd-delivery-pro' ); ?></div>
											<div class="myd-summary-value"><?php echo esc_html( $currency_simbol . ' ' . number_format( $troco_diff, 2, ',', '.' ) ); ?></div>
										</div>
									</div>
								<?php endif; ?>
							<?php endif; ?>
							<?php
								endif;
							endif;
							if ( strtolower( $pstatus ) === 'paid' ) :
							?>
							<div class="myd-card-section myd-billing-card">
								<span aria-hidden="true" class="myd-icon-slot">
									<!-- Paid SVG (check) -->
									<svg viewBox="0 0 24 24" class="myd-billing-icon" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M4 12.6111L8.92308 17.5L20 6.5" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
								</span>
								<div class="myd-strong">Pago online, não cobrar na entrega.</div>
							</div>
							<?php
							endif;
							?>

				   <?php /* Removido bloco myd-quick-actions de dentro do scroll */ ?>
			   </div>
			   </div><!-- /.myd-detail-scroll -->

				<!-- Layout & quick-actions styles moved to assets/css/panel-overrides.css -->
			   <?php if ( strtolower( (string) $status ) === 'new' ) : ?>
			   <div class="myd-quick-actions">
				   <div>
				   <div class="fdm-btn-order-action myd-print-btn" id="myd-print-<?php echo esc_attr( $postid ); ?>" data-manage-order-id="<?php echo esc_attr( $postid ); ?>" aria-label="<?php esc_attr_e( 'Imprimir pedido', 'myd-delivery-pro' ); ?>" style="margin-right:12px;width:40px;height:40px;padding:6px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;border:none;background:transparent;">
						   <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 18H6.2C5.0799 18 4.51984 18 4.09202 17.782C3.71569 17.5903 3.40973 17.2843 3.21799 16.908C3 16.4802 3 15.9201 3 14.8V10.2C3 9.0799 3 8.51984 3.21799 8.09202C3.40973 7.71569 3.71569 7.40973 4.09202 7.21799C4.51984 7 5.0799 7 6.2 7H7M17 18H17.8C18.9201 18 19.4802 18 19.908 17.782C20.2843 17.5903 20.5903 17.2843 20.782 16.908C21 16.4802 21 15.9201 21 14.8V10.2C21 9.07989 21 8.51984 20.782 8.09202C20.5903 7.71569 20.2843 7.40973 19.908 7.21799C19.4802 7 18.9201 7 17.8 7H17M7 11H7.01M17 7V5.4V4.6C17 4.03995 17 3.75992 16.891 3.54601C16.7951 3.35785 16.6422 3.20487 16.454 3.10899C16.2401 3 15.9601 3 15.4 3H8.6C8.03995 3 7.75992 3 7.54601 3.10899C7.35785 3.20487 7.20487 3.35785 7.10899 3.54601C7 3.75992 7 4.03995 7 4.6V5.4V7M17 7H7M8.6 21H15.4C15.9601 21 16.2401 21 16.454 20.891C16.6422 20.7951 16.7951 20.6422 16.891 20.454C17 20.2401 17 19.9601 17 19.4V16.6C17 16.0399 17 15.7599 16.891 15.546C16.7951 15.3578 16.6422 15.2049 16.454 15.109C16.2401 15 15.9601 15 15.4 15H8.6C8.03995 15 7.75992 15 7.54601 15.109C7.35785 15.2049 7.20487 15.3578 7.10899 15.546C7 15.7599 7 16.0399 7 16.6V19.4C7 19.9601 7 20.2401 7.10899 20.454C7.20487 20.6422 7.35785 20.7951 7.54601 20.891C7.75992 21 8.03995 21 8.6 21Z" stroke="#ffae00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
					   </div>
				   </div>
				   <div class="myd-quick-actions-group">
					   <div class="fdm-btn-order-action myd-cancel-btn" id="myd-cancel-<?php echo esc_attr( $postid ); ?>"
						   data-manage-order-id="<?php echo esc_attr( $postid ); ?>"
						   data-order-payment-type="<?php echo esc_attr( $raw_payment_type ); ?>"
						   data-order-payment-method="<?php echo esc_attr( $payment_method ); ?>"
						   data-manage-order-action="canceled">
						   <?php esc_html_e( 'Cancel', 'myd-delivery-pro' ); ?>
					   </div>
					   <div class="fdm-btn-order-action myd-preparar-btn" id="myd-preparar-<?php echo esc_attr( $postid ); ?>"
							data-manage-order-id="<?php echo esc_attr( $postid ); ?>"
							data-manage-order-action="confirmed">
						   Preparar
					   </div>
				   </div>
			   </div>
			   <?php elseif ( strtolower( (string) $status ) === 'confirmed' ) : ?>
			   <div class="myd-quick-actions">
				   <div>
					   <div class="fdm-btn-order-action myd-print-btn" id="myd-print-<?php echo esc_attr( $postid ); ?>" data-manage-order-id="<?php echo esc_attr( $postid ); ?>" aria-label="<?php esc_attr_e( 'Imprimir pedido', 'myd-delivery-pro' ); ?>" style="margin-right:12px;width:40px;height:40px;padding:6px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;border:none;background:transparent;">
						   <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 18H6.2C5.0799 18 4.51984 18 4.09202 17.782C3.71569 17.5903 3.40973 17.2843 3.21799 16.908C3 16.4802 3 15.9201 3 14.8V10.2C3 9.0799 3 8.51984 3.21799 8.09202C3.40973 7.71569 3.71569 7.40973 4.09202 7.21799C4.51984 7 5.0799 7 6.2 7H7M17 18H17.8C18.9201 18 19.4802 18 19.908 17.782C20.2843 17.5903 20.5903 17.2843 20.782 16.908C21 16.4802 21 15.9201 21 14.8V10.2C21 9.07989 21 8.51984 20.782 8.09202C20.5903 7.71569 20.2843 7.40973 19.908 7.21799C19.4802 7 18.9201 7 17.8 7H17M7 11H7.01M17 7V5.4V4.6C17 4.03995 17 3.75992 16.891 3.54601C16.7951 3.35785 16.6422 3.20487 16.454 3.10899C16.2401 3 15.9601 3 15.4 3H8.6C8.03995 3 7.75992 3 7.54601 3.10899C7.35785 3.20487 7.20487 3.35785 7.10899 3.54601C7 3.75992 7 4.03995 7 4.6V5.4V7M17 7H7M8.6 21H15.4C15.9601 21 16.2401 21 16.454 20.891C16.6422 20.7951 16.7951 20.6422 16.891 20.454C17 20.2401 17 19.9601 17 19.4V16.6C17 16.0399 17 15.7599 16.891 15.546C16.7951 15.3578 16.6422 15.2049 16.454 15.109C16.2401 15 15.9601 15 15.4 15H8.6C8.03995 15 7.75992 15 7.54601 15.109C7.35785 15.2049 7.20487 15.3578 7.10899 15.546C7 15.7599 7 16.0399 7 16.6V19.4C7 19.9601 7 20.2401 7.10899 20.454C7.20487 20.6422 7.35785 20.7951 7.54601 20.891C7.75992 21 8.03995 21 8.6 21Z" stroke="#ffae00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
					   </div>
				   </div>
				   <div class="myd-quick-actions-group">
					   <div class="fdm-btn-order-action myd-cancel-btn" id="myd-cancel-<?php echo esc_attr( $postid ); ?>"
						    data-manage-order-id="<?php echo esc_attr( $postid ); ?>"
						    data-order-payment-type="<?php echo esc_attr( $raw_payment_type ); ?>"
						    data-order-payment-method="<?php echo esc_attr( $payment_method ); ?>"
						    data-manage-order-action="canceled">
						   <?php esc_html_e( 'Cancel', 'myd-delivery-pro' ); ?>
					   </div>
					   <div class="fdm-btn-order-action myd-despachar-btn" id="myd-despachar-<?php echo esc_attr( $postid ); ?>"
							data-manage-order-id="<?php echo esc_attr( $postid ); ?>"
							data-manage-order-action="in-delivery">
						   Despachar pedido
					   </div>
				   </div>
			   </div>
			   <?php elseif ( strtolower( (string) $status ) === 'in-delivery' ) : ?>
			   <div class="myd-quick-actions">
					<div class="fdm-btn-order-action myd-print-btn" id="myd-print-<?php echo esc_attr( $postid ); ?>" data-manage-order-id="<?php echo esc_attr( $postid ); ?>" aria-label="<?php esc_attr_e( 'Imprimir pedido', 'myd-delivery-pro' ); ?>" style="margin-right:12px;width:40px;height:40px;padding:6px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;border:none;background:transparent;">
						<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 18H6.2C5.0799 18 4.51984 18 4.09202 17.782C3.71569 17.5903 3.40973 17.2843 3.21799 16.908C3 16.4802 3 15.9201 3 14.8V10.2C3 9.0799 3 8.51984 3.21799 8.09202C3.40973 7.71569 3.71569 7.40973 4.09202 7.21799C4.51984 7 5.0799 7 6.2 7H7M17 18H17.8C18.9201 18 19.4802 18 19.908 17.782C20.2843 17.5903 20.5903 17.2843 20.782 16.908C21 16.4802 21 15.9201 21 14.8V10.2C21 9.07989 21 8.51984 20.782 8.09202C20.5903 7.71569 20.2843 7.40973 19.908 7.21799C19.4802 7 18.9201 7 17.8 7H17M7 11H7.01M17 7V5.4V4.6C17 4.03995 17 3.75992 16.891 3.54601C16.7951 3.35785 16.6422 3.20487 16.454 3.10899C16.2401 3 15.9601 3 15.4 3H8.6C8.03995 3 7.75992 3 7.54601 3.10899C7.35785 3.20487 7.20487 3.35785 7.10899 3.54601C7 3.75992 7 4.03995 7 4.6V5.4V7M17 7H7M8.6 21H15.4C15.9601 21 16.2401 21 16.454 20.891C16.6422 20.7951 16.7951 20.6422 16.891 20.454C17 20.2401 17 19.9601 17 19.4V16.6C17 16.0399 17 15.7599 16.891 15.546C16.7951 15.3578 16.6422 15.2049 16.454 15.109C16.2401 15 15.9601 15 15.4 15H8.6C8.03995 15 7.75992 15 7.54601 15.109C7.35785 15.2049 7.20487 15.3578 7.10899 15.546C7 15.7599 7 16.0399 7 16.6V19.4C7 19.9601 7 20.2401 7.10899 20.454C7.20487 20.6422 7.35785 20.7951 7.54601 20.891C7.75992 21 8.03995 21 8.6 21Z" stroke="#ffae00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
					</div>
                	<div class="myd-quick-actions-group">
			   		<div class="fdm-btn-order-action myd-cancel-btn" id="myd-cancel-<?php echo esc_attr( $postid ); ?>"
							data-manage-order-id="<?php echo esc_attr( $postid ); ?>"
							data-order-payment-type="<?php echo esc_attr( $raw_payment_type ); ?>"
							data-order-payment-method="<?php echo esc_attr( $payment_method ); ?>"
							data-manage-order-action="canceled">
			   			<?php esc_html_e( 'Cancel', 'myd-delivery-pro' ); ?>
			   		</div>

				</div>
			   </div>
			   
			   <?php endif; ?>
		</div>
	<?php endwhile; ?>
	<?php \wp_reset_postdata(); ?>
<?php endif; ?>
