<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pages = get_pages();
$options = array();
foreach ( $pages as $key => $value ) {
	$options[] = '<option value="' . intval( $value->ID ) . '" ' . selected( get_option(' fdm-page-order-track' ), intval( $value->ID ), false ) . '>' . esc_html( $value->post_title ) . '</option>';
}

/**
 * To legacy type of input mask.
 * TODO: remove soon.
 */
$map_legacy_mask_option = array(
	'fdm-tel-8dig' => '####-####',
	'myd-tel-9' => '#####-####',
	'myd-tel-8-ddd' => '(##)####-####',
	'myd-tel-9-ddd' => '(##)#####-####',
	'myd-tel-us' => '(###)###-####',
	'myd-tel-ven' => '(####)###-####',
);
$mask_option = \get_option( 'fdm-mask-phone' );
if ( isset( $map_legacy_mask_option[ $mask_option ] ) ) {
	\update_option( 'fdm-mask-phone', $map_legacy_mask_option[ $mask_option ] );
	$mask_option = \get_option( 'fdm-mask-phone' );
}

?>

<div id="tab-advanced-content" class="myd-tabs-content">
	<h2>
		<?php esc_html_e( 'Advanced Settings', 'myd-delivery-pro' ); ?>
	</h2>
	<p>
		<?php esc_html_e( 'In this section you can configure some advanced settings.', 'myd-delivery-pro' ); ?>
	</p>

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="fdm-page-order-track"><?php esc_html_e( 'Track Order Page', 'myd-delivery-pro' );?></label>
					</th>
					<td>
						<select name="fdm-page-order-track" id="fdm-page-order-track">
							<option value=""><?php esc_html_e( 'Select', 'myd-delivery-pro' );?></option>
							<?php echo implode( $options ); ?>
						</select>
						<p class="description"><?php esc_html_e( 'Select the page for show order track for customers. After that, get the shortcode and paste in selected page.', 'myd-delivery-pro' );?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="fdm-mask-phone"><?php esc_html_e( 'Phone Mask', 'myd-delivery-pro' );?></label>
					</th>
					<td>
						<select name="fdm-mask-phone" id="fdm-mask-phone">
							<option value="">
								<?php esc_html_e( 'Select', 'myd-delivery-pro' ); ?>
							</option>
							<option
								value="####-####"
								<?php selected( $mask_option, '####-####' ); ?>
							>
								0000-0000 8 <?php esc_html_e( 'digitis', 'myd-delivery-pro' ); ?>
							</option>
							<option
								value="#####-####"
								<?php selected( $mask_option, '#####-####' ); ?>
							>
								00000-0000 9 <?php esc_html_e( 'digitis', 'myd-delivery-pro' ); ?>
							</option>
							<option
								value="(##)####-####"
								<?php selected( $mask_option, '(##)####-####' ); ?>
							>
								(00)0000-0000 DDD + 8 <?php esc_html_e( 'digitis', 'myd-delivery-pro' ); ?>
							</option>
							<option
								value="(##)#####-####"
								<?php selected( $mask_option, '(##)#####-####' ); ?>
							>
								(00)00000-0000 DDD + 9 <?php esc_html_e( 'digitis', 'myd-delivery-pro' ); ?>
							</option>
							<option
								value="(###)###-####"
								<?php selected( $mask_option, '(###)###-####' ); ?>
							>
								(000)000-0000 USA 10 <?php esc_html_e( 'digitis', 'myd-delivery-pro' ); ?>
							</option>
							<option
								value="(####)###-####"
								<?php selected( $mask_option, '(####)###-####' ); ?>
							>
								(0000)000-0000 11 <?php esc_html_e( 'digitis', 'myd-delivery-pro' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Select the mask for forms on plugin.', 'myd-delivery-pro' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Remove Zipcode', 'myd-delivery-pro') ;?></label>
					</th>
					<td>
						<input type="checkbox" name="myd-form-hide-zipcode" id="myd-form-hide-zipcode" value="yes" <?php checked( get_option( 'myd-form-hide-zipcode' ), 'yes' ); ?>>
						<label for="myd-form-hide-zipcode"><?php esc_html_e( 'Yes, remove Zipcode input', 'myd-delivery-pro' );?></label>
						<p class="description"><?php esc_html_e('Remove input and verification from Zipcode field.', 'myd-delivery-pro' );?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Remove Address Number', 'myd-delivery-pro' );?></label>
					</th>
					<td>
						<input type="checkbox" name="myd-form-hide-address-number" id="myd-form-hide-address-number" value="yes" <?php checked( get_option( 'myd-form-hide-address-number' ), 'yes' ); ?>>
						<label for="myd-form-hide-address-number"><?php esc_html_e( 'Yes, remove Address Number input', 'myd-delivery-pro' );?></label>
						<p class="description"><?php esc_html_e( 'Remove input and verification from address number field.', 'myd-delivery-pro' );?></p>
					</td>
				</tr>

			</tbody>
		</table>
	</div>
