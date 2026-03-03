<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="post" action="options.php">
<?php settings_fields('myd-delivery-pro-settings-group'); ?>
<div id="tab-evolution-api-content" class="myd-tabs-content">
	<h2><?php esc_html_e( 'Evolution API', 'myd-delivery-pro' ); ?></h2>
	<p><?php esc_html_e( 'Configure a integração com a Evolution API para envio automático de mensagens no WhatsApp.', 'myd-delivery-pro' ); ?></p>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row">
					<label for="evolution_api_url"><?php esc_html_e( 'URL da API', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<input name="evolution_api_url" type="text" id="evolution_api_url" value="<?php echo esc_attr( get_option( 'evolution_api_url' ) ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="evolution_api_key"><?php esc_html_e( 'API Key', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<input name="evolution_api_key" type="text" id="evolution_api_key" value="<?php echo esc_attr( get_option( 'evolution_api_key' ) ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="evolution_webhook_key"><?php esc_html_e( 'Webhook Key', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<input name="evolution_webhook_key" type="password" id="evolution_webhook_key" value="<?php echo esc_attr( get_option( 'evolution_webhook_key' ) ); ?>" class="regular-text">
					<p class="description"><?php esc_html_e( 'Chave usada para validar webhooks recebidos da Evolution (opcional).', 'myd-delivery-pro' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="evolution_instance_name"><?php esc_html_e( 'Nome da Instância', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<input name="evolution_instance_name" type="text" id="evolution_instance_name" value="<?php echo esc_attr( get_option( 'evolution_instance_name' ) ); ?>" class="regular-text">
				</td>
			</tr>
			
			<tr>
				<th scope="row">
					<label for="evolution_message_confirmed"><?php esc_html_e( 'Mensagem de Pedido Aceito', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<textarea name="evolution_message_confirmed" id="evolution_message_confirmed" class="large-text" rows="3"><?php echo esc_textarea( get_option( 'evolution_message_confirmed' ) ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="evolution_message_delivery"><?php esc_html_e( 'Mensagem de Pedido em Entrega', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<textarea name="evolution_message_delivery" id="evolution_message_delivery" class="large-text" rows="3"><?php echo esc_textarea( get_option( 'evolution_message_delivery' ) ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="evolution_test_phone"><?php esc_html_e( 'Número para Teste', 'myd-delivery-pro' ); ?></label>
				</th>
				<td>
					<input name="evolution_test_phone" type="text" id="evolution_test_phone" value="" class="regular-text">
					<button type="button" class="button" id="evolution-api-test-btn">Teste API</button>
					<span id="evolution-api-test-result"></span>
				</td>
			</tr>
		</tbody>
	</table>
	<?php submit_button(); ?>
	<div id="evolution-api-status"></div>
</div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function() {
	document.getElementById('evolution-api-test-btn').addEventListener('click', function() {
		const phone = document.getElementById('evolution_test_phone').value;
		const url = document.getElementById('evolution_api_url').value;
		const key = document.getElementById('evolution_api_key').value;
		const instance = document.getElementById('evolution_instance_name').value;
		const resultSpan = document.getElementById('evolution-api-test-result');
		resultSpan.textContent = 'Testando...';
		fetch(ajaxurl, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			body: `action=evolution_api_test&phone=${encodeURIComponent(phone)}&url=${encodeURIComponent(url)}&key=${encodeURIComponent(key)}&instance=${encodeURIComponent(instance)}`
		})
		.then(r => r.json())
		.then(data => {
			resultSpan.textContent = data.message;
		})
		.catch(() => {
			resultSpan.textContent = 'Erro ao testar API.';
		});
	});
});
</script>