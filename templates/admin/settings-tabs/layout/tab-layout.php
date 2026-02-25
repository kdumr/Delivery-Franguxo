<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="tab-layout-content" class="myd-tabs-content">
	<h2><?php esc_html_e( 'Layout Settings', 'myd-delivery-pro' ); ?></h2>
	<p><?php esc_html_e( 'In this section you can configure some layout options.', 'myd-delivery-pro' ); ?></p>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="fdm-principal-color"><?php esc_html_e( 'Main color', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<input name="fdm-principal-color" type="color" id="fdm-principal-color" value="<?php echo esc_attr( get_option( 'fdm-principal-color' ) ); ?>">
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="myd-price-color"><?php esc_html_e( 'Price color', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<input name="myd-price-color" type="color" id="myd-price-color" value="<?php echo esc_attr( get_option( 'myd-price-color' ) ); ?>">
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="myd-products-list-columns"><?php esc_html_e( 'Product grid columns', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<select name="myd-products-list-columns" id="myd-products-list-columns">
						<option value=""><?php esc_html_e( 'Select', 'myd-delivery-pro' ); ?></option>
						<option value="myd-product-list--1column" <?php selected( get_option( 'myd-products-list-columns'), 'myd-product-list--1column' ); ?>>1 <?php esc_html_e( 'column', 'myd-delivery-pro' ); ?></option>
						<option value="myd-product-list--2columns" <?php selected( get_option( 'myd-products-list-columns'), 'myd-product-list--2columns' ); ?>>2 <?php esc_html_e( 'columns', 'myd-delivery-pro' ); ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Box shadow', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<input type="checkbox" name="myd-products-list-boxshadow" id="myd-p roducts-list-boxshadow" value="myd-product-item--boxshadow" <?php checked( get_option( 'myd-products-list-boxshadow'), 'myd-product-item--boxshadow' ); ?>>
					<label for="myd-products-list-boxshadow"><?php esc_html_e( 'Yes, add box shadow on products card', 'myd-delivery-pro' ); ?></label>
				</td>
			</tr>
		</tbody>
	</table>
	<div class="card">
		<h2 class="title"><?php esc_html_e( 'More flexibility with MyD Delivery Widgets', 'myd-delivery-pro' ); ?></h2>
		<p><?php echo wp_kses_post( __( 'We have a <b>free plugin with widgets for Elementor</b> where you can customize the layout in many other ways. Get on WordPress directory.', 'myd-delivery-pro' ) ); ?></p>
		<p><a class="button-primary thickbox open-plugin-details-modal" href="/wp-admin/plugin-install.php?tab=plugin-information&plugin=myd-delivery-widgets&TB_iframe=true&width=772&height=1174"><?php esc_html_e( 'Install MyD Delivery Widgets', 'myd-delivery-pro' ); ?></a></p>
		<p><?php echo wp_kses_post( __( "<b><i>If you dont use Elementor don't worry</b>, will be still improve layout configurations here.</i>", 'myd-delivery-pro' ) ); ?></p>
	</div>
</div>
