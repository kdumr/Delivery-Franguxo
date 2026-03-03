<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
	<h2><?php esc_html_e( 'Agente IA API (Google Gemini)', 'myd-delivery-pro' ); ?></h2>
	<p><?php esc_html_e( 'Configure e ative o motor de Inteligência Artificial para responder automaticamente às mensagens recebidas via Evolution API.', 'myd-delivery-pro' ); ?></p>
	
	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'fmd-settings-group' ); ?>
		
		<div class="myd-admin-addons" style="margin-top: 20px;">
			<div class="card" style="max-width: 800px; padding: 20px;">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="gemini_enabled"><strong><?php esc_html_e( 'Ligar IA no Atendimento?', 'myd-delivery-pro' ); ?></strong></label>
							</th>
							<td>
								<input name="gemini_enabled" type="checkbox" id="gemini_enabled" value="yes" <?php checked('yes', get_option('gemini_enabled', 'no')); ?>>
								<span class="description"><?php esc_html_e( 'Marcar para que a IA assuma as respostas dos clientes.', 'myd-delivery-pro' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="gemini_api_key"><strong><?php esc_html_e( 'API Key do Google Gemini', 'myd-delivery-pro' ); ?></strong></label>
							</th>
							<td>
								<input name="gemini_api_key" type="password" id="gemini_api_key" value="<?php echo esc_attr( get_option( 'gemini_api_key' ) ); ?>" class="regular-text">
								<p class="description"><a href="https://aistudio.google.com/app/apikey" target="_blank"><?php esc_html_e( 'Obter chaves da API do Google AI Studio', 'myd-delivery-pro' ); ?></a></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="gemini_system_prompt"><strong><?php esc_html_e( 'Instruções da IA (Prompt de Sistema)', 'myd-delivery-pro' ); ?></strong></label>
							</th>
							<td>
								<textarea name="gemini_system_prompt" id="gemini_system_prompt" class="large-text" rows="8" placeholder="Ex: Olá! Você é um atendente simpático de uma pizzaria..."><?php echo esc_textarea( get_option( 'gemini_system_prompt' ) ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Escreva todas as regras de negócio, o cardápio ou como o robô deve responder a pedidos.', 'myd-delivery-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<td colspan="2"><hr><h3 style="margin-bottom:0;"><?php esc_html_e( 'Conexão Evolution API (Saída)', 'myd-delivery-pro' ); ?></h3>
							<p class="description"><?php esc_html_e( 'Para a IA conseguir enviar as respostas geradas de volta para o cliente, informe os dados da sua instância do WhatsApp abaixo.', 'myd-delivery-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="evolution_api_url"><strong><?php esc_html_e( 'URL da Evolution API', 'myd-delivery-pro' ); ?></strong></label>
							</th>
							<td>
								<input name="evolution_api_url" type="text" id="evolution_api_url" value="<?php echo esc_attr( get_option( 'evolution_api_url' ) ); ?>" class="regular-text" placeholder="https://bot.exemplo.com">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="evolution_api_key"><strong><?php esc_html_e( 'Global API Key (Evolution)', 'myd-delivery-pro' ); ?></strong></label>
							</th>
							<td>
								<input name="evolution_api_key" type="password" id="evolution_api_key" value="<?php echo esc_attr( get_option( 'evolution_api_key' ) ); ?>" class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="evolution_instance_name"><strong><?php esc_html_e( 'Nome da Instância', 'myd-delivery-pro' ); ?></strong></label>
							</th>
							<td>
								<input name="evolution_instance_name" type="text" id="evolution_instance_name" value="<?php echo esc_attr( get_option( 'evolution_instance_name' ) ); ?>" class="regular-text" placeholder="Ex: franguxo">
							</td>
						</tr>
					</tbody>
				</table>
				
				<p class="submit">
					<?php submit_button( 'Salvar Configurações da IA', 'primary', 'submit', false ); ?>
				</p>
			</div>
		</div>
	</form>
</div>
