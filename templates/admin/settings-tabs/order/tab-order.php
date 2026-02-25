<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="tab-order-content" class="myd-tabs-content">
	<h2>
		<?php esc_html_e( 'Order settings', 'myd-delivery-pro' ); ?>
	</h2>
	<p>
		<?php esc_html_e( 'In this section you can configure the order message used when the customer is redirected to WhatsApp.', 'myd-delivery-pro' ); ?>
	</p>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="myd-option-minimum-price">
						<?php esc_html_e( 'Minimum price', 'myd-delivery-pro' ); ?>
					</label>
				</th>

				<td>
					<input
						name="myd-option-minimum-price"
						type="number"
						id="myd-option-minimum-price"
						value="<?php echo esc_attr( get_option( 'myd-option-minimum-price' ) ); ?>"
						class="regular-text"
					>

					<p class="description">
						<?php esc_html_e( "If you don't want to set a minimum price for orders, just leave this input blank.", 'myd-delivery-pro' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="myd-average-preparation-time">
						<?php esc_html_e( 'Tempo médio de preparo (minutos)', 'myd-delivery-pro' ); ?>
					</label>
				</th>

				<td>
					<input
						name="myd-average-preparation-time"
						type="number"
						id="myd-average-preparation-time"
						value="<?php echo esc_attr( get_option( 'myd-average-preparation-time' ) ); ?>"
						class="regular-text"
						min="0"
					>
					<p class="description">
						<?php esc_html_e( 'Defina o tempo médio de preparo do pedido em minutos.', 'myd-delivery-pro' ); ?>
					</p>
				</td>
			</tr>


			<tr>
				<th scope="row">
					<label>
						<?php esc_html_e( 'Redirect to WhatsApp?', 'myd-delivery-pro' ); ?>
					</label>
				</th>

				<td>
					<input
						type="checkbox"
						name="myd-option-redirect-whatsapp"
						id="myd-option-redirect-whatsapp"
						value="yes"
						<?php checked( get_option( 'myd-option-redirect-whatsapp' ), 'yes' ); ?>
					>

					<label for="myd-option-redirect-whatsapp">
						<?php esc_html_e( 'Yes, redirect the customer to WhatsApp after checkout', 'myd-delivery-pro' ); ?>
					</label>

					<p class="description">
						<?php esc_html_e( "If you don't select it, the plugin will be show the button to send order on WhatsApp.", 'myd-delivery-pro' ) ;?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<h2>
		<?php esc_html_e( 'Custom Order Message', 'myd-delivery-pro' ); ?>
	</h2>

	<div class="card">
		<h3>
			<?php esc_html_e( 'How to use?', 'myd-delivery-pro' ); ?>
		</h3>

		<p>
			1. <?php esc_html_e( 'Define the template to list the order products in "Template to List the Order Products". This will be used to list the products on all types of your order message that\'s you will create in the next steps.', 'myd-delivery-pro' ); ?>
		</p>

		<p>
			2. <?php esc_html_e( 'Select the type of message you will customize. By default you can customize different messages for each type of order (Delivery, Take-away and Digital Menu).', 'myd-delivery-pro' ); ?>
		</p>

		<p>
			3. <?php esc_html_e( 'Create the full custom message in "Template to Order Message" to be used when customer is redirected to WhatsApp after order sucessfull.', 'myd-delivery-pro' ); ?>
		</p>
	</div>

	<h3>
		<?php esc_html_e( 'Template to List the Order Products', 'myd-delivery-pro' ); ?>
	</h3>

	<p class="description">
		<?php esc_html_e( 'Avaliable tokens:', 'myd-delivery-pro' ); ?>
	</p>

	<ul>
		<li>
			<code>{product-qty}</code> - <?php esc_html_e( 'Product Quantity', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{product-name}</code> - <?php esc_html_e( 'Product Name', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{product-price}</code> - <?php esc_html_e( 'Product Price with currency symbol', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{product-extras}</code> - <?php esc_html_e( 'Product Extras', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{product-note}</code> - <?php esc_html_e( 'Product Note', 'myd-delivery-pro' ); ?>.
		</li>
	</ul>

	<textarea
		name="myd-template-order-custom-message-list-products"
		class="large-text"
		rows="6"
	><?php echo get_option( 'myd-template-order-custom-message-list-products' ); ?></textarea>

	<h3>
		<?php esc_html_e( 'Template to Order Message', 'myd-delivery-pro' ); ?>
	</h3>

	<div class="card">
		<h3>
			<?php esc_html_e( 'Important:', 'myd-delivery-pro' ); ?>
		</h3>

		<p>
			<?php
			echo sprintf(
				esc_html__( '"Template to List the Order Products" is related to token %s so this token is required in the Order Message and will list the products based on what you are defined in the previous step.', 'myd-delivery-pro' ),
				'<code>{order-products}</code>'
			);
			?>
		</p>
	</div>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="myd-custom-message-type">
						<?php esc_html_e( 'Message Type', 'myd-delivery-pro' ); ?>
					</label>
				</th>

				<td>
					<select name="myd-custom-message-type" id="myd-custom-message-type">
						<option value="delivery">
							<?php esc_html_e( 'Delivery', 'myd-delivery-pro' ); ?>
						</option>
						<option value="take-away">
							<?php esc_html_e( 'Take-away', 'myd-delivery-pro' ); ?>
						</option>
						<option value="digital-menu">
							<?php esc_html_e( 'Digital Menu', 'myd-delivery-pro' ); ?>
						</option>
					</select>
				</td>
			</tr>
		</tbody>
	</table>

	<p class="description">
		<?php esc_html_e( 'Avaliable tokens:', 'myd-delivery-pro' ); ?>
	</p>

	<ul>
		<li>
			<code>{order-number}</code> - <?php esc_html_e( 'Order Number', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{order-date-time}</code> - <?php esc_html_e( 'Order Date and Time', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{order-coupon-code}</code> - <?php esc_html_e( 'Order Coupon Code', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{order-total}</code> - <?php esc_html_e( 'Order Total', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{order-products}</code> - <?php esc_html_e( "Order Products. This token is the result of the configuration above, don't forget to add it", 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{order-table}</code> - <?php esc_html_e( 'Number of the table', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{order-track-page}</code> - <?php esc_html_e( 'Link to order track page', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{payment-type}</code> - <?php esc_html_e( 'Payment Type', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{payment-status}</code> - <?php esc_html_e( 'Payment Status', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{payment-method}</code> - <?php esc_html_e( 'Payment Method', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{payment-change}</code> - <?php esc_html_e( 'Payment Change (applied for cash payments)', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{customer-name}</code> - <?php esc_html_e( 'Customer Name', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{customer-phone}</code> - <?php esc_html_e( 'Customer Phone', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{customer-address}</code> - <?php esc_html_e( 'Customer Address', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{customer-address-number}</code> - <?php esc_html_e( 'Customer Address Number', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{customer-address-complement}</code> - <?php esc_html_e( 'Customer Address Complement (Apartment, suite, etc.)', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{customer-address-neighborhood}</code> - <?php esc_html_e( 'Customer Address Neighborhood', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{customer-address-zipcode}</code> - <?php esc_html_e( 'Customer Address Zipcode', 'myd-delivery-pro' ); ?>.
		</li>
		<li>
			<code>{shipping-price}</code> - <?php esc_html_e( 'Shipping Price', 'myd-delivery-pro' ); ?>.
		</li>
	</ul>

	<textarea
		name="myd-template-order-custom-message-delivery"
		id="myd-template-order-custom-message-delivery"
		class="large-text myd-template-order-custom-message"
		rows="20"
	><?php echo get_option( 'myd-template-order-custom-message-delivery' ); ?></textarea>

	<textarea
		name="myd-template-order-custom-message-take-away"
		id="myd-template-order-custom-message-take-away"
		class="large-text myd-admin-hidden myd-template-order-custom-message"
		rows="20"
	><?php echo get_option( 'myd-template-order-custom-message-take-away' ); ?></textarea>

	<textarea
		name="myd-template-order-custom-message-digital-menu"
		id="myd-template-order-custom-message-digital-menu"
		class="large-text myd-admin-hidden myd-template-order-custom-message"
		rows="20"
	><?php echo get_option( 'myd-template-order-custom-message-digital-menu' ); ?></textarea>
</div>
