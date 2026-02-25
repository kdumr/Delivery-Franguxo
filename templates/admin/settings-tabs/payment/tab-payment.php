<?php

use MydPro\Includes\Myd_Currency;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$currency_list = Myd_Currency::get_currency_list();
$saved_currency_code = Myd_Currency::get_currency_code();

?>
<div id="tab-payment-content" class="myd-tabs-content">
	<h2><?php esc_html_e( 'Payment Settings', 'myd-delivery-pro' ); ?></h2>
	<p><?php esc_html_e( 'In this section you can configure the payment methods and others settings.', 'myd-delivery-pro' ); ?></p>

	<table class="form-table bandeiras-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="myd-currency"><?php esc_html_e( 'Currency', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<select name="myd-currency" id="myd-currency">
						<option value=""><?php esc_html_e( 'Select', 'myd-delivery-pro' ); ?></option>
						<?php foreach ( $currency_list as $currency_code => $currency_value ) : ?>
							<?php $currency_name = $currency_value['name'] . ' (' . $currency_value['symbol'] . ')'; ?>
							<option
								value="<?php echo esc_attr( $currency_code ); ?>"
								<?php selected( $saved_currency_code, $currency_code ); ?>
								>
								<?php echo esc_html( $currency_name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fdm-number-decimal"><?php esc_html_e( 'Number of decimals', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input name="fdm-number-decimal" type="number" id="fdm-number-decimal" value="<?php echo esc_attr( get_option( 'fdm-number-decimal' ) ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'This sets the number of decimal points show in displayed price.', 'myd-delivery-pro' );?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fdm-decimal-separator"><?php esc_html_e( 'Decimal separator', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<input name="fdm-decimal-separator" type="text" id="fdm-decimal-separator" value="<?php echo esc_attr( get_option( 'fdm-decimal-separator' ) ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'This sets the decimal separator of displayed prices ', 'myd-delivery-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fdm-payment-in-cash">
						<?php esc_html_e( 'Accept payment in cash?', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<select name="fdm-payment-in-cash" id="fdm-payment-in-cash">
						<option value="">
							<?php esc_html_e( 'Select', 'myd-delivery-pro' ); ?>
						</option>
						<option
							value="yes"
							<?php selected( get_option( 'fdm-payment-in-cash' ), 'yes' ); ?>
						>
							<?php esc_html_e( 'Yes, my store accept cash payments on delivery', 'myd-delivery-pro' ); ?>
						</option>
						<option
							value="no"
							<?php selected( get_option( 'fdm-payment-in-cash' ), 'no' ); ?>
						>
							<?php esc_html_e( 'No, my store does not accept cash payments on delivery', 'myd-delivery-pro' ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fdm-payment-type">
						<?php esc_html_e( 'Payment upon delivery', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<textarea
						placeholder="<?php esc_html_e( 'Cash, Credit Card, Debit Card...', 'myd-delivery-pro' ); ?>"
						id="fdm-payment-type"
						name="fdm-payment-type"
						cols="50"
						rows="5"
						class="large-text"
					><?php esc_html_e( get_option( 'fdm-payment-type' ) ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'These are payment options to be used directly upon delivery and not while placing the order in the system.', 'myd-delivery-pro' ); ?>
					</p>

					<p class="description">
						<?php esc_html_e( 'List all payment methods separated by comma (,). Like: Credit card, Debit Card, Voucher(...).', 'myd-delivery-pro' ); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>

	<hr style="margin:32px 0;">
	<h3><?php esc_html_e('Formas de pagamento', 'myd-delivery-pro'); ?></h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">Crédito</th>
				<td>
					<label class="switch">
						<input type="checkbox" name="fdm-payment-credit" id="fdm-payment-credit" value="CRD" <?php checked(get_option('fdm-payment-credit'), 'CRD'); ?> />
						<span class="slider round"></span>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">Débito</th>
				<td>
					<label class="switch">
						<input type="checkbox" name="fdm-payment-debit" id="fdm-payment-debit" value="DEB" <?php checked(get_option('fdm-payment-debit'), 'DEB'); ?> />
						<span class="slider round"></span>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">Vale-refeição</th>
				<td>
					<label class="switch">
						<input type="checkbox" name="fdm-payment-vr" id="fdm-payment-vr" value="VRF" <?php checked(get_option('fdm-payment-vr'), 'VRF'); ?> />
						<span class="slider round"></span>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">Dinheiro</th>
				<td>
					<label class="switch">
						<input type="checkbox" name="fdm-payment-cash" id="fdm-payment-cash" value="DIN" <?php checked(get_option('fdm-payment-cash'), 'DIN'); ?> />
						<span class="slider round"></span>
					</label>
				</td>
			</tr>
		</tbody>
	</table>

	   <style>
	   .bandeiras-table td.bandeira-label {
		   width: 180px;
		   min-width: 140px;
		   max-width: 220px;
		   padding-right: 16px;
		   text-align: left;
	   }
	   .bandeiras-table td.bandeira-switch {
		   width: 80px;
		   min-width: 60px;
		   text-align: left;
	   }
	.switch {
	  position: relative;
	  display: inline-block;
	  width: 46px;
	  height: 24px;
	}
	.switch input {display:none;}
	.slider {
	  position: absolute;
	  cursor: pointer;
	  top: 0; left: 0; right: 0; bottom: 0;
	  background-color: #ccc;
	  transition: .4s;
	  border-radius: 24px;
	}
	.slider:before {
	  position: absolute;
	  content: "";
	  height: 18px;
	  width: 18px;
	  left: 3px;
	  bottom: 3px;
	  background-color: white;
	  transition: .4s;
	  border-radius: 50%;
	}
	input:checked + .slider {
	  background-color: #2196F3;
	}
	input:checked + .slider:before {
	  transform: translateX(22px);
	}
	</style>

	<h3>Bandeiras aceitas - Crédito</h3>
	<table class="form-table">
		<tbody>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/visa.png'); ?>" alt="Visa" title="Visa" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_visa" value="1" <?php checked(get_option('credit_card_visa'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/master.png'); ?>" alt="Master" title="Master" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_master" value="1" <?php checked(get_option('credit_card_master'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/elo.png'); ?>" alt="Elo" title="Elo" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_elo" value="1" <?php checked(get_option('credit_card_elo'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/cabal.png'); ?>" alt="Cabal" title="Cabal" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_cabal" value="1" <?php checked(get_option('credit_card_cabal'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/hipercard.png'); ?>" alt="Hiper" title="Hiper" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_hipercard" value="1" <?php checked(get_option('credit_card_hipercard'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/amex.png'); ?>" alt="American Express" title="American Express" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_amex" value="1" <?php checked(get_option('credit_card_amex'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/diners.png'); ?>" alt="Diners" title="Diners" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_diners" value="1" <?php checked(get_option('credit_card_diners'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/hiper.png'); ?>" alt="Hiper" title="Hiper" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_hiper" value="1" <?php checked(get_option('credit_card_hiper'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/master.png'); ?>" alt="Master" title="Master" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_master" value="1" <?php checked(get_option('credit_card_master'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/elo.png'); ?>" alt="Elo" title="Elo" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_elo" value="1" <?php checked(get_option('credit_card_elo'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/cabal.png'); ?>" alt="Cabal" title="Cabal" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_cabal" value="1" <?php checked(get_option('credit_card_cabal'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/hipercard.png'); ?>" alt="Hiper" title="Hiper" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_hipercard" value="1" <?php checked(get_option('credit_card_hipercard'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/amex.png'); ?>" alt="American Express" title="American Express" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_amex" value="1" <?php checked(get_option('credit_card_amex'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/diners.png'); ?>" alt="Diners" title="Diners" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_diners" value="1" <?php checked(get_option('credit_card_diners'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/hiper.png'); ?>" alt="Hiper" title="Hiper" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="credit_card_hiper" value="1" <?php checked(get_option('credit_card_hiper'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
		</tbody>
	</table>

	<h3>Bandeiras aceitas - Débito</h3>
	<table class="form-table">
		<tbody>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/visa.png'); ?>" alt="Visa" title="Visa" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="debit_card_visa" value="1" <?php checked(get_option('debit_card_visa'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/master.png'); ?>" alt="Master" title="Master" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="debit_card_master" value="1" <?php checked(get_option('debit_card_master'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/elo.png'); ?>" alt="Elo" title="Elo" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="debit_card_elo" value="1" <?php checked(get_option('debit_card_elo'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/cabal.png'); ?>" alt="Cabal" title="Cabal" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="debit_card_cabal" value="1" <?php checked(get_option('debit_card_cabal'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
		</tbody>
	</table>

	   <h3>Bandeiras aceitas - Vouchers</h3>
	   <table class="form-table">
		   <tbody>
				  <tr>
					  <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/pluxee.png'); ?>" alt="Pluxee" title="Pluxee" style="height:22px;vertical-align:middle;" /></th>
					  <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="voucher_pluxee" value="1" <?php checked(get_option('voucher_pluxee'), '1'); ?> /><span class="slider round"></span></label></td>
				  </tr>
				  <tr>
					  <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/ticket.png'); ?>" alt="Ticket" title="Ticket" style="height:22px;vertical-align:middle;" /></th>
					  <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="voucher_ticket" value="1" <?php checked(get_option('voucher_ticket'), '1'); ?> /><span class="slider round"></span></label></td>
				  </tr>
				  <tr>
					  <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/alelo.png'); ?>" alt="Alelo" title="Alelo" style="height:22px;vertical-align:middle;" /></th>
					  <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="voucher_alelo" value="1" <?php checked(get_option('voucher_alelo'), '1'); ?> /><span class="slider round"></span></label></td>
				  </tr>
				  <tr>
					  <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/vrbeneficios.png'); ?>" alt="VR Benefícios" title="VR Benefícios" style="height:22px;vertical-align:middle;" /></th>
					  <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="voucher_vr" value="1" <?php checked(get_option('voucher_vr'), '1'); ?> /><span class="slider round"></span></label></td>
				  </tr>
		   </tbody>
	   </table>

	<h3>Pagamentos Digitais</h3>
	<table class="form-table">
		<tbody>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/pix.png'); ?>" alt="Pix" title="Pix (QR Code)" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="digital_pix" value="1" <?php checked(get_option('digital_pix'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/googlepay.png'); ?>" alt="Google Pay" title="Google Pay" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="digital_googlepay" value="1" <?php checked(get_option('digital_googlepay'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/applepay.png'); ?>" alt="Apple Pay" title="Apple Pay" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="digital_applepay" value="1" <?php checked(get_option('digital_applepay'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
			   <tr>
				   <th class="bandeira-label" scope="row"><img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/samsungpay.png'); ?>" alt="Samsung Pay" title="Samsung Pay" style="height:22px;vertical-align:middle;" /></th>
				   <td class="bandeira-switch"><label class="switch"><input type="checkbox" name="digital_samsungpay" value="1" <?php checked(get_option('digital_samsungpay'), '1'); ?> /><span class="slider round"></span></label></td>
			   </tr>
		</tbody>
	</table>

	<hr style="margin:32px 0;">
	<h3>Integração de pagamento MercadoPago</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="mercadopago_public_key">Public Key</label>
				</th>
				<td>
					<input type="text" name="mercadopago_public_key" id="mercadopago_public_key" value="<?php echo esc_attr( get_option('mercadopago_public_key', '') ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for=" mercadopago_access_token">Access Token</label>
				</th>
				<td>
					<input type="password" name="mercadopago_access_token" id="mercadopago_access_token" value="<?php echo esc_attr( get_option('mercadopago_access_token', '') ); ?>" class="regular-text" autocomplete="off" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="mercadopago_webhook_secret">Webhook Secret</label>
				</th>
				<td>
					<input input type="password" name="mercadopago_webhook_secret" id="mercadopago_webhook_secret" value="<?php echo esc_attr( get_option('mercadopago_webhook_secret', '') ); ?>" class="regular-text" autocomplete="off" />
					<p class="description">Configure este segredo em Suas integrações &gt; Webhooks para validar as notificações (x-signature).</p>
				</td>
			</tr>
			<tr>
				<th scope="row">URL do Webhook</th>
				<td>
					<?php $webhook_url = home_url('/wp-json/myd-delivery/v1/mercadopago/webhook'); ?>
					<code><?php echo esc_html($webhook_url); ?></code>
					<p class="description">Cole a URL acima no painel do Mercado Pago em Webhooks &gt; Pagamentos (payment).</p>
				</td>
			</tr>
		</tbody>
	</table>
	<hr style="margin:32px 0;">

	<?php do_action( 'myd-delivery/settings/payment/after-fields' ); ?>
</div>
