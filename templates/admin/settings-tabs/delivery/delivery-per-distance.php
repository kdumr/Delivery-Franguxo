<?php

use MydPro\Includes\l10n\Myd_Country;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active = $delivery_mode === 'per-distance' ? 'myd-tabs-content--active' : '';
if ( isset( $delivery_mode_options['per-distance']['options'] ) ) {
	$delivery_mode_per_distance_options = $delivery_mode_options['per-distance']['options'];
}

$country_option = get_option( 'fdm-business-country' ) !== '' && get_option( 'fdm-business-country' ) !== false ? get_option( 'fdm-business-country' ) : 'United States';
$country = new Myd_Country( $country_option );
wp_add_inline_script( 'myd-admin-scritps', 'const mydSelectedCountryCode = "' . $country->get_country_code() . '"', 'before' );

?>
<div class="card">
	<h3 class="title">
		<?php esc_html_e( 'Important', 'myd-delivery-pro' ); ?>
	</h3>
	<p>
		<?php esc_html_e( 'This feature depends on Google Maps API and costs can be applied by Google. The Google offer a limit of free requests and after this limit costs will be applied. You can check more details here: www.googlemaps.com.br/pricing', 'myd-delivery-pro' ); ?>
	</p>
</div>

<div class="myd-delivery-type-content <?php echo esc_attr( $active ); ?>" id="myd-delivery-per-distance">
	<h2>
		<?php esc_html_e( 'Price per Distance', 'myd-delivery-pro' ); ?>
	</h2>

	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="myd-shipping-distance-google-api-key"><?php esc_html_e( 'Google API Key', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input
						name="myd-shipping-distance-google-api-key"
						type="text"
						id="myd-shipping-distance-google-api-key"
						value="<?php echo esc_attr( get_option( 'myd-shipping-distance-google-api-key' ) ); ?>"
						class="regular-text"
						onblur="window.MydAdmin.initPriceByDistance()"
					>

					<label for="myd-shipping-distance-address-radius" style="display:block;margin-top:8px;font-size:13px;"><?php esc_html_e( 'Autocomplete bias radius (meters)', 'myd-delivery-pro' ); ?></label>
					<input
						name="myd-shipping-distance-address-radius"
						type="number"
						id="myd-shipping-distance-address-radius"
						value="<?php echo esc_attr( get_option( 'myd-shipping-distance-address-radius', 5000 ) ); ?>"
						class="regular-text"
						min="0"
					>

					<input
						name="myd-shipping-distance-address-latitude"
						type="hidden"
						value="<?php echo esc_attr( get_option( 'myd-shipping-distance-address-latitude' ) ); ?>"
					>

					<input
						name="myd-shipping-distance-address-longitude"
						type="hidden"
						value="<?php echo esc_attr( get_option( 'myd-shipping-distance-address-longitude' ) ); ?>"
					>

					<input
						name="myd-shipping-distance-formated-address"
						type="hidden"
						value="<?php echo esc_attr( get_option( 'myd-shipping-distance-formated-address' ) ); ?>"
					>
					<p class="description">
						<?php esc_html_e( 'Get/create your API key here:', 'myd-delivery-pro' ); ?>
						<a href="https://console.cloud.google.com/google/maps-apis/credentials" target="_blank">
							<?php esc_html_e( 'Google Maps API', 'myd-delivery-pro' ); ?>
						</a>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<h4>
		<?php esc_html_e( 'Your Store address', 'myd-delivery-pro' ); ?>
	</h4>

	<div class="myd-search-address" id="myd-search-address">
		<input
			class="myd-search-address__autocomplete-input"
			id="myd-search-address-autocomplete-input"
			type="text"
			placeholder="<?php esc_html_e( 'Enter an Address with Number', 'myd-delivery-pro' ); ?>"
			disabled
			autocomplete="off"
			value="<?php echo esc_attr( get_option( 'myd-shipping-distance-formated-address' ) ); ?>"
		/>
	</div>

	<div id="myd-map"></div>

	<h4>
		<?php esc_html_e( 'Price by distance range', 'myd-delivery-pro' ); ?>
	</h4>

	<table class="wp-list-table widefat fixed striped myd-options-table">
		<thead>
			<tr>
				<th>
					<?php esc_html_e( 'From (km)', 'myd-delivery-pro' ); ?>
				</th>
				<th>
					<?php esc_html_e( 'To (km)', 'myd-delivery-pro' ); ?>
				</th>
				<th>
					<?php esc_html_e( 'Price', 'myd-delivery-pro' ); ?>
				</th>
				<th class="myd-options-table__action">
					<?php esc_html_e( 'Action', 'myd-delivery-pro' ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( isset( $delivery_mode_per_distance_options ) && ! empty( $delivery_mode_per_distance_options ) ) : ?>
				<?php foreach ( $delivery_mode_per_distance_options as $k => $v ) : ?>
					<tr
						class="myd-options-table__row-content"
						data-row-index='<?php echo esc_attr( $k ); ?>'
						data-row-field-base="myd-delivery-mode-options[per-distance][options]"
					>
						<td>
							<input
								name="myd-delivery-mode-options[per-distance][options][<?php echo esc_attr( $k ); ?>][from]"
								data-data-index="from"
								type="number"
								step="0.001"
								id="myd-delivery-mode-options[per-distance][options][<?php echo esc_attr( $k ); ?>][from]"
								value="<?php echo esc_attr( $v['from'] ); ?>"
								class="regular-text myd-input-full"
							>
						</td>

						<td>
							<input
								name="myd-delivery-mode-options[per-distance][options][<?php echo esc_attr( $k ); ?>][to]"
								data-data-index="to"
								type="number"
								step="0.001"
								id="myd-delivery-mode-options[per-distance][options][<?php echo esc_attr( $k ); ?>][from]"
								value="<?php echo esc_attr( $v['to'] ); ?>"
								class="regular-text myd-input-full"
							>
						</td>
						<td>
							<input
								name="myd-delivery-mode-options[per-distance][options][<?php echo esc_attr( $k ); ?>][price]"
								data-data-index="price"
								type="number"
								step="0.001"
								id="myd-delivery-mode-options[per-distance][options][<?php echo esc_attr( $k ); ?>][price]"
								value="<?php echo esc_attr( $v['price'] ); ?>"
								class="regular-text myd-input-full"
							>
						</td>

						<td>
							<span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterTableRemoveRow(this)">
								<?php echo esc_html_e( 'remove', 'myd-delivery-pro' ); ?>
							</span>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr
					class="myd-options-table__row-content"
					data-row-index='0'
					data-row-field-base="myd-delivery-mode-options[per-distance][options]"
				>
					<td>
						<input
							name="myd-delivery-mode-options[per-distance][options][0][from]"
							data-data-index="from"
							type="number"
							step="0.001"
							id="myd-delivery-mode-options[per-distance][options][0][from]"
							value=""
							class="regular-text myd-input-full"
						>
					</td>

					<td>
						<input
							name="myd-delivery-mode-options[per-distance][options][0][to]"
							data-data-index="to"
							type="number"
							step="0.001"
							id="myd-delivery-mode-options[per-distance][options][0][to]"
							value=""
							class="regular-text myd-input-full"
						>
					</td>

					<td>
						<input
							name="myd-delivery-mode-options[per-distance][options][0][price]"
							data-data-index="price"
							type="number"
							step="0.001"
							id="myd-delivery-mode-options[per-distance][options][0][price]"
							value=""
							class="regular-text myd-input-full"
						>
					</td>

					<td>
						<span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterTableRemoveRow(this)">
							<?php echo esc_html_e( 'remove', 'myd-delivery-pro' ); ?>
						</span>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<a
		href="#"
		class="button button-small button-secondary myd-repeater-table__button"
		onclick="window.MydAdmin.mydRepeaterTableAddRow(event)"
	>
		<?php esc_html_e( 'Add more', 'myd-delivery-pro' ); ?>
	</a>
</div>
