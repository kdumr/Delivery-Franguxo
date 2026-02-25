<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h2>
	<?php esc_html_e( 'SumUp Payment Gateway', 'myd-delivery-integration-sumup' ); ?>
</h2>
<p>
	<?php esc_html_e( 'Set your SumUp account credentials to start. If you need help ', 'myd-delivery-integration-sumup' ); ?>
	<a href="https://myddelivery.com/docs/how-to-configure-sumup-payment-integration/" target="_blank"><?php esc_html_e( 'check this tutorial', 'myd-delivery-integration-sumup' ); ?></a>
</p>

<p>
	<strong><?php esc_html_e( 'Don\'t have a SumUp account yet?', 'myd-delivery-integration-sumup' ); ?>
	<a href="https://buy.sumup.com/pt-br/signup/create-account/?skip_shop=yes&fcam_rc=BRECOMPRC85V2PZVMOBLPIX" target="_blank"><?php esc_html_e( 'Create a new account here.', 'myd-delivery-integration-sumup' ); ?></a></strong>
</p>

<table class="form-table">
	<tbody>
		<tr>
			<th scope="row">
				<label for="sumupmyd-merchant-id">
					<?php esc_html_e( 'Merchant ID', 'myd-delivery-integration-sumup' ); ?>
				</label>
			</th>
			<td>
				<input
					name="sumupmyd-merchant-id"
					type="text"
					id="sumupmyd-merchant-id"
					value="<?php echo esc_attr( get_option( 'sumupmyd-merchant-id' ) ); ?>"
					class="regular-text"
				>

				<p class="description">
					<?php esc_html_e( 'Get your Merchant ID direct on your SumUp account.', 'myd-delivery-integration-sumup' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="sumupmyd-api-key">
					<?php esc_html_e( 'API Key', 'myd-delivery-integration-sumup' ); ?>
				</label>
			</th>
			<td>
				<input
					name="sumupmyd-api-key"
					type="password"
					id="sumupmyd-api-key"
					value="<?php echo esc_attr( get_option( 'sumupmyd-api-key' ) ); ?>"
					class="regular-text"
				>

				<p class="description">
					<?php esc_html_e( 'Create/get your API Key here: ', 'myd-delivery-integration-sumup' ); ?>
					<a href="https://developer.sumup.com/api-keys" target="_blank">SumUp API Keys</a>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="sumupmyd-email">
					<?php esc_html_e( 'SumUp profile email', 'myd-delivery-integration-sumup' ); ?>
				</label>
			</th>
			<td>
				<input
					name="sumupmyd-email"
					type="email"
					id="sumupmyd-email"
					value="<?php echo esc_attr( get_option( 'sumupmyd-email' ) ); ?>"
					class="regular-text"
				>

				<p class="description">
					<?php esc_html_e( 'Email used on your SumUp account.', 'myd-delivery-integration-sumup' ); ?>
				</p>
			</td>
		</tr>
	</tbody>
</table>
