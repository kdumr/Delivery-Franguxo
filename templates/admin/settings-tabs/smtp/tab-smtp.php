<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="tab-smtp-content" class="myd-tabs-content">
	<h2><?php esc_html_e( 'Configurações de SMTP', 'myd-delivery-pro' ); ?></h2>
	<p><?php esc_html_e( 'Configure o servidor SMTP para enviar e-mails da sua loja.', 'myd-delivery-pro' ); ?></p>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					   <label for="myd-smtp-host"><?php esc_html_e( 'Servidor SMTP', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input name="myd-smtp-host" type="text" id="myd-smtp-host" value="<?php echo esc_attr( get_option( 'myd-smtp-host' ) ); ?>" class="regular-text">
					   <p class="description"><?php esc_html_e( 'Endereço do servidor SMTP.', 'myd-delivery-pro' );?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					   <label for="myd-smtp-port"><?php esc_html_e( 'Porta SMTP', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input name="myd-smtp-port" type="number" id="myd-smtp-port" value="<?php echo esc_attr( get_option( 'myd-smtp-port' ) ); ?>" class="small-text">
					   <p class="description"><?php esc_html_e( 'Normalmente 587, 465 ou 25.', 'myd-delivery-pro' );?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					   <label for="myd-smtp-username"><?php esc_html_e( 'Usuário SMTP', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input name="myd-smtp-username" type="text" id="myd-smtp-username" value="<?php echo esc_attr( get_option( 'myd-smtp-username' ) ); ?>" class="regular-text">
					   <p class="description"><?php esc_html_e( 'Normalmente o seu endereço de e-mail.', 'myd-delivery-pro' );?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					   <label for="myd-smtp-password"><?php esc_html_e( 'Senha SMTP', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<input name="myd-smtp-password" type="password" id="myd-smtp-password" value="<?php echo esc_attr( get_option( 'myd-smtp-password' ) ); ?>" class="regular-text">
					   <p class="description"><?php esc_html_e( 'Senha da sua conta SMTP.', 'myd-delivery-pro' );?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					   <label for="myd-smtp-secure"><?php esc_html_e( 'Criptografia', 'myd-delivery-pro' );?></label>
				</th>
				<td>
					<select name="myd-smtp-secure" id="myd-smtp-secure">
						   <option value="none" <?php selected( get_option('myd-smtp-secure'), 'none' ); ?> ><?php esc_html_e( 'Nenhuma', 'myd-delivery-pro' );?></option>
						   <option value="ssl" <?php selected( get_option('myd-smtp-secure'), 'ssl' ); ?> >SSL</option>
						   <option value="tls" <?php selected( get_option('myd-smtp-secure'), 'tls' ); ?> >TLS</option>
					</select>
					   <p class="description"><?php esc_html_e( 'Escolha o método de criptografia do seu servidor SMTP.', 'myd-delivery-pro' );?></p>
				</td>
			</tr>
		</tbody>
			</table>
			<hr>
			   <h3><?php esc_html_e('Testar envio de e-mail', 'myd-delivery-pro'); ?></h3>
			   <p>
				   <input type="email" id="myd-test-smtp-email" placeholder="Digite um e-mail para testar" class="regular-text" style="max-width:300px;">
				   <button type="button" class="button button-secondary" id="myd-test-smtp-btn"><?php esc_html_e('Testar envio', 'myd-delivery-pro'); ?></button>
				   <span id="myd-test-smtp-result" style="margin-left:10px;"></span>
			   </p>
			<script>
			jQuery(document).ready(function($){
				$('#myd-test-smtp-btn').on('click', function(){
					var email = $('#myd-test-smtp-email').val();
					   $('#myd-test-smtp-result').text('Enviando...');
					   $.post(ajaxurl, {
						   action: 'myd_test_smtp',
						   test_email: email
					   }, function(resp){
						   if(resp.success){
							   $('#myd-test-smtp-result').css('color','green').text(resp.data);
						   }else{
							   $('#myd-test-smtp-result').css('color','red').text(resp.data);
						   }
					   });
				});
			});
			</script>
	</div>
