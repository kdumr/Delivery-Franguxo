
<?php
use MydPro\Includes\Store_Data;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$orders = $this->orders_object;
// Garante que o handler AJAX está disponível
require_once dirname(__DIR__, 2) . '/includes/myd-save-delivery-time-handler.php';
?>

<div id="hide-prints">
		<?php require MYD_PLUGIN_PATH . 'templates/order/print.php'; ?>
</div>

<!-- panel-inline.css moved to assets and is enqueued by the plugin bootstrap -->

<div class="myd-orders-shell">

	<!-- Barra lateral vertical esquerda (colocada abaixo do top-bar) -->
	<div class="myd-sidebar-left">
		<div class="myd-sidebar-header">
			<div class="myd-sidebar-logo">
				<?php // Exibe imagem de perfil na sidebar; usa URL do plugin se disponível ?>
				<?php if ( defined( 'MYD_PLUGIN_URL' ) ) : ?>
					<img src="<?php echo esc_url( rtrim( MYD_PLUGIN_URL, '/' ) . '/assets/img/franguxoperfil.png' ); ?>" alt="Franguxo" class="myd-sidebar-avatar" />
				<?php else: ?>
					<img src="<?php echo esc_url( plugins_url( 'assets/img/franguxoperfil.png', dirname( __FILE__ ) . '/../../myd-delivery-pro.php' ) ); ?>" alt="Franguxo" class="myd-sidebar-avatar" />
				<?php endif; ?>
			</div>
		</div>
		
		<nav class="myd-sidebar-nav">
			<a href="#myd-section-menu" class="myd-sidebar-item active" data-section="done" title="Menu">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M11.3861 1.21065C11.7472 0.929784 12.2528 0.929784 12.6139 1.21065L21.6139 8.21065C21.8575 8.4001 22 8.69141 22 9V20.5C22 21.3284 21.3284 22 20.5 22H15V14C15 13.4477 14.5523 13 14 13H10C9.44772 13 9 13.4477 9 14V22H3.5C2.67157 22 2 21.3284 2 20.5V9C2 8.69141 2.14247 8.4001 2.38606 8.21065L11.3861 1.21065Z" fill="#333333"></path> </g></svg>
				</svg>
			</a>

			<!-- Botão Pedidos: mostra a lista de pedidos -->
			<a href="#myd-section-orders" class="myd-sidebar-item" id="myd-sidebar-orders-btn" title="Pedidos">
				<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
					<path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15" stroke="#333333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5C15 6.10457 14.1046 7 13 7H11C9.89543 7 9 6.10457 9 5Z" stroke="#333333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					<path d="M9 12H15" stroke="#333333" stroke-width="2" stroke-linecap="round"/>
					<path d="M9 16H13" stroke="#333333" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</a>

			<!-- Botão WhatsApp Web: visível apenas no Electron -->
			<a href="#myd-section-whatsapp" class="myd-sidebar-item" id="myd-sidebar-whatsapp-btn" title="WhatsApp Web" style="display:none;">
				<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
					<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" fill="#25D366"/>
				</svg>
			</a>
			<script>
			// Mostrar botão WhatsApp apenas no Electron
			if (navigator.userAgent.includes('Electron')) {
				var wpBtn = document.getElementById('myd-sidebar-whatsapp-btn');
				if (wpBtn) wpBtn.style.display = '';
			}
			</script>

			<!-- Link adicional: Relatórios -->
			<a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>" class="myd-sidebar-item" title="Relatórios" target="_blank" rel="noopener noreferrer">
				<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
					<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
					<g id="SVGRepo_iconCarrier">
						<path d="M4 5V19C4 19.5523 4.44772 20 5 20H19" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
						<path d="M18 9L13 13.9999L10.5 11.4998L7 14.9998" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
					</g>
				</svg>
			</a>

			<!-- Botão Notas de Atualização -->
			<button type="button" id="myd-update-notes-btn" class="myd-sidebar-item" title="Notas de Atualização" style="background:none;border:none;padding:0;margin:0;cursor:pointer;">
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#000000" width="24" height="24">
					<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
					<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
					<g id="SVGRepo_iconCarrier">
						<path d="m9.47368421 4.15789474v2.10526315h-7.36842105v12.86315791h12.63157894v-7.6h2.1052632v7.368421c0 1.1578948-.9473685 2.1052632-2.1052632 2.1052632h-12.63157894c-1.15789474 0-2.10526316-.9473684-2.10526316-2.1052632v-12.63157891c0-1.15789473.94736842-2.10526315 2.10526316-2.10526315zm0 10.52631576v2.1052632h-5.26315789v-2.1052632zm3.15789469-3.1578947v2.1052631h-8.42105258v-2.1052631zm0-3.15789475v2.10526315h-8.42105258v-2.10526315zm3.1578948-5.26315789.9868421 2.17105263 2.1710526.9868421-2.1710526.98684211-.9868421 2.17105263-.9868421-2.17105263-2.1710527-.98684211 2.1710527-.9868421zm-3.1578948-2.10526316.6578948 1.44736842 1.4473684.65789474-1.4473684.65789473-.6578948 1.44736843-.6578947-1.44736843-1.4473684-.65789473 1.4473684-.65789474z" fill="#000000" transform="translate(3 1)"></path>
					</g>
				</svg>
			</button>

			<!-- Botão Criar Pedido Manual -->
			<a href="<?php echo esc_url( add_query_arg( 'myd_create_order', '1', home_url( '/' ) ) ); ?>" target="_blank" id="myd-manual-order-btn" class="myd-sidebar-item" title="Criar Pedido" style="background:none;border:none;padding:0;margin:0;cursor:pointer;text-decoration:none;">
				<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
					<path d="M12 5V19M5 12H19" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</a>


		</nav>
		
		<div class="myd-sidebar-footer">
			<button type="button" class="myd-sidebar-item" id="myd-sidebar-settings" title="Configurações" style="color:#fff;">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M19.14 12.94C19.18 12.64 19.2 12.33 19.2 12C19.2 11.68 19.18 11.36 19.13 11.06L21.16 9.48C21.34 9.34 21.39 9.07 21.28 8.87L19.36 5.55C19.24 5.33 18.99 5.26 18.77 5.33L16.38 6.29C15.88 5.91 15.35 5.59 14.76 5.35L14.4 2.81C14.36 2.57 14.16 2.4 13.92 2.4H10.08C9.84 2.4 9.65 2.57 9.61 2.81L9.25 5.35C8.66 5.59 8.12 5.92 7.63 6.29L5.24 5.33C5.02 5.25 4.77 5.33 4.65 5.55L2.74 8.87C2.62 9.08 2.66 9.34 2.86 9.48L4.89 11.06C4.84 11.36 4.8 11.69 4.8 12C4.8 12.31 4.82 12.64 4.87 12.94L2.84 14.52C2.66 14.66 2.61 14.93 2.72 15.13L4.64 18.45C4.76 18.67 5.01 18.74 5.23 18.67L7.62 17.71C8.12 18.09 8.65 18.41 9.24 18.65L9.6 21.19C9.65 21.43 9.84 21.6 10.08 21.6H13.92C14.16 21.6 14.36 21.43 14.39 21.19L14.75 18.65C15.34 18.41 15.88 18.09 16.37 17.71L18.76 18.67C18.98 18.75 19.23 18.67 19.35 18.45L21.27 15.13C21.39 14.91 21.34 14.66 21.15 14.52L19.14 12.94ZM12 15.6C10.02 15.6 8.4 13.98 8.4 12C8.4 10.02 10.02 8.4 12 8.4C13.98 8.4 15.6 10.02 15.6 12C15.6 13.98 13.98 15.6 12 15.6Z" fill="#333333"/>
				</svg>
			</button>
		</div>
	</div>

	<div class="fdm-ordres-wrap" id="myd-delivery-orders-full-screen">

		<div class="top-bar">
		<div class="myd-row-between">

			<div style="display:flex; gap:20px; align-items:center;">
				<!-- Botão de status-card-button -->
						<div class="status-card-button skeleton" id="myd-status-card-btn">
							<div class="status-card-content" style="display: flex; align-items: center; gap: 6px;">
								<span class="status-ellipse">
									<svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none" class="hds-flight-icon--animation-loading" style="width:10px;height:10px;display:block;">
										<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
										<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
										<g id="SVGRepo_iconCarrier">
											<g fill="#888888" fill-rule="evenodd" clip-rule="evenodd">
												<path d="M8 1.5a6.5 6.5 0 100 13 6.5 6.5 0 000-13zM0 8a8 8 0 1116 0A8 8 0 010 8z" opacity=".2"></path>
												<path d="M7.25.75A.75.75 0 018 0a8 8 0 018 8 .75.75 0 01-1.5 0A6.5 6.5 0 008 1.5a.75.75 0 01-.75-.75z"></path>
											</g>
										</g>
									</svg>
								</span>
								<span class="status-text">Carregando</span>
							</div>
							<span class="status-arrow" style="display:flex;align-items:center;">
								<svg viewBox="-4.5 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="#888888" style="width:10px;height:10px;display:block;">
									<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
									<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
									<g id="SVGRepo_iconCarrier">
										<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
											<g id="Dribbble-Light-Preview" transform="translate(-305.000000, -6679.000000)" fill="#888888">
												<g id="icons" transform="translate(56.000000, 160.000000)">
													<path d="M249.365851,6538.70769 L249.365851,6538.70769 C249.770764,6539.09744 250.426289,6539.09744 250.830166,6538.70769 L259.393407,6530.44413 C260.202198,6529.66364 260.202198,6528.39747 259.393407,6527.61699 L250.768031,6519.29246 C250.367261,6518.90671 249.720021,6518.90172 249.314072,6519.28247 L249.314072,6519.28247 C248.899839,6519.67121 248.894661,6520.31179 249.302681,6520.70653 L257.196934,6528.32352 C257.601847,6528.71426 257.601847,6529.34685 257.196934,6529.73759 L249.365851,6537.29462 C248.960938,6537.68437 248.960938,6538.31795 249.365851,6538.70769" id="arrow_right-[]"> </path>
												</g>
											</g>
										</g>
									</g>
								</svg>
							</span>
						</div>

				<!-- Botão de Caixa -->
				<div class="myd-top-cashier-btn" id="myd-cashier-btn">
					<span class="myd-top-cashier-icon">
						<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
							<path d="M4 6h16v2H4V6zm0 4h16v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8zm2 3a1 1 0 100 2h2a1 1 0 100-2H6zm5 0a1 1 0 100 2h2a1 1 0 100-2h-2zm5 0a1 1 0 100 2h2a1 1 0 100-2h-2zM7 2h10a1 1 0 011 1v2H6V3a1 1 0 011-1z" fill="#888888"/>
						</svg>
					</span>
					<div class="myd-top-cashier-texts">
						<span class="myd-top-cashier-title">Caixa</span>
						<span class="myd-top-cashier-status" id="myd-cashier-btn-status">Carregando...</span>
					</div>
				</div>
			</div>
<script>
const MYD_REST_NONCE = "<?php echo wp_create_nonce( 'wp_rest' ); ?>";
const MYD_LETTER_IMG_URL = "<?php echo esc_url( defined('MYD_PLUGN_URL') ? MYD_PLUGN_URL . 'assets/img/franguxoletter.png' : plugins_url('assets/img/franguxoletter.png', dirname(__FILE__, 3) . '/myd-delivery-pro.php') ); ?>";
// Polling para status da loja e atualização do botão status-card-button
document.addEventListener('DOMContentLoaded', function() {
	const btn = document.getElementById('myd-status-card-btn');
	const modalStatus = document.getElementById('myd-modal-status');
	const btnToggle = document.getElementById('btn-toggle-status');
	if (!btn) return;
	const ellipse = btn.querySelector('.status-ellipse');
	const loadingSVG = `<svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none" class="hds-flight-icon--animation-loading" style="width:16px;height:16px;display:block;"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><g fill="#888888" fill-rule="evenodd" clip-rule="evenodd"><path d="M8 1.5a6.5 6.5 0 100 13 6.5 6.5 0 000-13zM0 8a8 8 0 1116 0A8 8 0 010 8z" opacity=".2"></path><path d="M7.25.75A.75.75 0 018 0a8 8 0 018 8 .75.75 0 01-1.5 0A6.5 6.5 0 008 1.5a.75.75 0 01-.75-.75z"></path></g></g></svg>`;
	const text = btn.querySelector('.status-text');

	const MODAL_ICONS = {
		open: '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12ZM16.0303 8.96967C16.3232 9.26256 16.3232 9.73744 16.0303 10.0303L11.0303 15.0303C10.7374 15.3232 10.2626 15.3232 9.96967 15.0303L7.96967 13.0303C7.67678 12.7374 7.67678 12.2626 7.96967 11.9697C8.26256 11.6768 8.73744 11.6768 9.03033 11.9697L10.5 13.4393L12.7348 11.2045L14.9697 8.96967C15.2626 8.67678 15.7374 8.67678 16.0303 8.96967Z" fill="#50a773"></path> </g></svg>',
		close: '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zm-1.5-5.009c0-.867.659-1.491 1.491-1.491.85 0 1.509.624 1.509 1.491 0 .867-.659 1.509-1.509 1.509-.832 0-1.491-.642-1.491-1.509zM11.172 6a.5.5 0 0 0-.499.522l.306 7a.5.5 0 0 0 .5.478h1.043a.5.5 0 0 0 .5-.478l.305-7a.5.5 0 0 0-.5-.522h-1.655z" fill="#BD7E27"></path></g></svg>',
		ignore_open: '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12ZM16.0303 8.96967C16.3232 9.26256 16.3232 9.73744 16.0303 10.0303L11.0303 15.0303C10.7374 15.3232 10.2626 15.3232 9.96967 15.0303L7.96967 13.0303C7.67678 12.7374 7.67678 12.2626 7.96967 11.9697C8.26256 11.6768 8.73744 11.6768 9.03033 11.9697L10.5 13.4393L12.7348 11.2045L14.9697 8.96967C15.2626 8.67678 15.7374 8.67678 16.0303 8.96967Z" fill="#50a773"></path> </g></svg>',
		ignore_close: '<svg fill="#a6a6a6" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>time</title> <path d="M0 16q0-3.232 1.28-6.208t3.392-5.12 5.12-3.392 6.208-1.28q3.264 0 6.24 1.28t5.088 3.392 3.392 5.12 1.28 6.208q0 3.264-1.28 6.208t-3.392 5.12-5.12 3.424-6.208 1.248-6.208-1.248-5.12-3.424-3.392-5.12-1.28-6.208zM4 16q0 3.264 1.6 6.048t4.384 4.352 6.016 1.6 6.016-1.6 4.384-4.352 1.6-6.048-1.6-6.016-4.384-4.352-6.016-1.632-6.016 1.632-4.384 4.352-1.6 6.016zM14.016 16v-5.984q0-0.832 0.576-1.408t1.408-0.608 1.408 0.608 0.608 1.408v4h4q0.8 0 1.408 0.576t0.576 1.408-0.576 1.44-1.408 0.576h-6.016q-0.832 0-1.408-0.576t-0.576-1.44z"></path> </g></svg>'
	};

	function updateModalStatus(type, hoursInfo) {
		if (!modalStatus) return;
		let html = '';
		let mainText = '';
		let bgClass = '';
		let showHours = (type === 'ignore-open' || type === 'ignore-close');
		
		switch(type) {
			case 'open':
				mainText = 'Loja aberta definitivamente';
				bgClass = 'bg-open';
				break;
			case 'close':
				mainText = 'Operação da loja pausada';
				bgClass = 'bg-close';
				break;
			case 'ignore-open':
				mainText = 'Dentro do horário de funcionamento';
				bgClass = 'bg-ignore-open';
				break;
			case 'ignore-close':
				mainText = 'Fora do horário de funcionamento';
				bgClass = 'bg-ignore-close';
				break;
		}

		html = (MODAL_ICONS[type.replace('-','_')] || MODAL_ICONS[type] || '');
		html += '<div class="status-content-wrapper">';
		html += '<span class="status-text">' + mainText + '</span>';
		
		if (showHours && hoursInfo) {
			html += '<span class="status-subtext">' + hoursInfo + '</span>';
		}
		html += '</div>';

		modalStatus.innerHTML = html;
		modalStatus.className = 'status-card ' + bgClass;
	}

	function updateToggleButton(type) {
		if (!btnToggle) return;
		// Se status for 'close' (manter fechado), botão vira "Respeitar horários definidos"
		// Se status for qq outra coisa (open, ignore-open, ignore-close), botão vira "Fechar loja"
		
		// Reset styles
		btnToggle.style.background = '';
		btnToggle.style.backgroundColor = ''; 
		btnToggle.style.color = '';
		btnToggle.disabled = false;
		btnToggle.style.opacity = '1';
		btnToggle.style.cursor = 'pointer';
		
		btnToggle.classList.remove('is-open', 'is-close');
		
		// Guardar o estado atual para o click handler
		btnToggle.setAttribute('data-current-type', type);

		if (type === 'close') {
			btnToggle.textContent = 'Respeitar horários definidos';
			btnToggle.classList.add('is-close'); // Verde (definido no CSS)
		} else {
			// Pode ser 'open', 'ignore-open' ou 'ignore-close'
			btnToggle.textContent = 'Fechar loja';
			btnToggle.classList.add('is-open'); // Vermelho (definido no CSS)
		}
	}

	if (btnToggle) {
		btnToggle.addEventListener('click', async function() {
			const currentType = btnToggle.getAttribute('data-current-type');
			if (!currentType) return;
			
			// Estado de loading
			btnToggle.disabled = true;
			let newForce = 'close';
			if (currentType === 'close') {
				newForce = 'ignore'; // Volta para automático
			} else {
				newForce = 'close'; // Fecha
			}

			// Visual feedback
			btnToggle.textContent = 'Atualizando...';
			btnToggle.disabled = true;
			btnToggle.style.opacity = '0.5';
			btnToggle.style.cursor = 'wait';

			if (window.mydShowModalLoading) {
				window.mydShowModalLoading('Atualizando...');
			}

			try {
				const resp = await fetch('/wp-json/myd-delivery/v1/store/status', {
					method: 'POST',
					headers: { 
						'Content-Type': 'application/json',
						'X-WP-Nonce': MYD_REST_NONCE
					},
					body: JSON.stringify({ force: newForce }),
					credentials: 'same-origin'
				});

				if (resp.ok) {
					// Fetch status again to update UI
					if (typeof fetchStatus === 'function') {
						fetchStatus();
					}
					if (window.mydShowNotification) {
						window.mydShowNotification('Status atualizado com sucesso!', 3000, 'success');
					}
				} else {
					if (window.mydShowNotification) {
						window.mydShowNotification('Erro ao atualizar status', 4000, 'error');
					}
				}
			} catch (err) {
				console.error('Error toggling status:', err);
				if (window.mydShowNotification) {
					window.mydShowNotification('Erro de conexão', 4000, 'error');
				}
			} finally {
				if (window.mydHideModalLoading) {
					window.mydHideModalLoading();
				}
				// The button state will be refreshed by fetchStatus -> setBtnState -> updateToggleButton
			}
		});
	}

	function setBtnState(type, hoursInfo) {
		// type: open, close, ignore-open, ignore-close
		btn.classList.remove('status-open','status-close','status-ignore-open','status-ignore-close','skeleton');
		// Remove SVG se existir
		if (ellipse.querySelector('svg')) ellipse.innerHTML = '';
		ellipse.style.background = '';
		ellipse.style.boxShadow = '';

		// Atualiza o modal e o botão de toggle
		updateModalStatus(type, hoursInfo);
		updateToggleButton(type);

		switch(type) {
			case 'open':
				btn.classList.add('status-open');
				ellipse.style.background = '#2ecc40';
				ellipse.style.boxShadow = '0 0 6px #2ecc40aa';
				text.textContent = 'Manter aberto';
				break;
			case 'close':
				btn.classList.add('status-close');
				ellipse.style.background = '#e74c3c';
				ellipse.style.boxShadow = '0 0 6px #e74c3caa';
				text.textContent = 'Manter fechado';
				break;
			case 'ignore-open':
				btn.classList.add('status-ignore-open');
				ellipse.style.background = '#2ecc40';
				ellipse.style.boxShadow = '0 0 6px #2ecc40aa';
				text.textContent = 'Loja aberta';
				break;
			case 'ignore-close':
				btn.classList.add('status-ignore-close');
				ellipse.style.background = '#888';
				ellipse.style.boxShadow = '0 0 6px #8888';
				text.textContent = 'Loja fechada';
				break;
		}
	}

	async function fetchStatus() {
		try {
			const resp = await fetch('/wp-json/myd-delivery/v1/store/status', { method: 'GET', cache: 'no-store', credentials: 'same-origin' });
			if (!resp.ok) return;
			const data = await resp.json();
			if (!data || !data.force) return;
			// Remove skeleton ao receber resposta
			btn.classList.remove('skeleton');
			// Remove SVG e volta elipse normal
			if (ellipse.querySelector('svg')) ellipse.innerHTML = '';
			if (data.force === 'open') setBtnState('open', data.hours_info);
			else if (data.force === 'close') setBtnState('close', data.hours_info);
			else if (data.force === 'ignore') {
				if (data.open) setBtnState('ignore-open', data.hours_info);
				else setBtnState('ignore-close', data.hours_info);
			}
		} catch(e) {}
	}
	fetchStatus();
	setInterval(fetchStatus, 30000);
});
</script>

<!-- Modal Horários -->
<?php include __DIR__ . '/modal-horarios.php'; ?>
<link rel="stylesheet" href="<?php echo plugins_url('assets/css/myd-modal-horarios.css', dirname(__FILE__, 3) . '/myd-delivery-pro.php'); ?>">

<!-- Modal Editar Pedido -->
<div id="myd-edit-order-modal" class="myd-modal myd-hidden" aria-hidden="true" role="dialog" aria-modal="true">
	<div class="myd-modal-backdrop"></div>
	<div class="myd-modal-dialog">
		<button type="button" id="myd-edit-order-close" class="myd-modal-close-btn">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
		</button>
		<div class="myd-modal-title">Editar Pedido</div>
		<form id="myd-edit-order-form">
			<input type="hidden" id="myd-edit-order-id" name="order_id" value="">
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'myd_edit_order_customer' ); ?>">
			
			<div class="myd-form-group">
				<label for="myd-edit-customer-name">Nome do cliente</label>
				<input type="text" id="myd-edit-customer-name" name="customer_name" placeholder="Nome do cliente" disabled>
			</div>
			
			<div class="myd-form-group">
				<label for="myd-edit-customer-phone">Telefone</label>
				<input type="text" id="myd-edit-customer-phone" name="customer_phone" placeholder="(00) 00000-0000">
			</div>
			
			<div class="myd-address-section">
				<div class="myd-address-section-title">Endereço completo</div>
				
				<div class="myd-address-row">
					<label for="myd-edit-address">Rua/Avenida</label>
					<input type="text" id="myd-edit-address" name="address" placeholder="Nome da rua">
				</div>
				
				<div class="myd-address-row-inline">
					<div class="myd-field-small">
						<label for="myd-edit-address-number" class="myd-edit-address-number">Número</label>
						<input type="text" id="myd-edit-address-number" name="address_number" placeholder="Nº">
					</div>
					<div class="myd-field-large">
						<label for="myd-edit-neighborhood" class="myd-edit-neighborhood">Bairro</label>
						<input type="text" id="myd-edit-neighborhood" name="neighborhood" placeholder="Bairro">
					</div>
				</div>
				
				<div class="myd-address-row">
					<label for="myd-edit-address-comp">Complemento</label>
					<input type="text" id="myd-edit-address-comp" name="address_comp" placeholder="Apartamento, bloco, etc.">
				</div>
				
				<div class="myd-address-row">
					<label for="myd-edit-reference">Ponto de referência</label>
					<input type="text" id="myd-edit-reference" name="reference" placeholder="Próximo a...">
				</div>
			</div>
			
			<div class="myd-form-actions">
				<button type="button" id="myd-edit-order-cancel" class="myd-btn-cancel">Cancelar</button>
				<button type="submit" id="myd-edit-order-save" class="myd-btn-save">Salvar</button>
			</div>
		</form>
	</div>
</div>

<script>
/* ── Global Toast Notification ── */
window.MydGlobalNotify = function(type, title, message, svgHtml) {
	if (!document.getElementById('myd-global-toast-styles')) {
		var css = '#myd-toast-container{position:fixed;right:16px;bottom:16px;z-index:9999999;display:flex;flex-direction:column;gap:10px}' +
				  '.myd-toast{display:flex;align-items:center;gap:12px;background:#def1e1;border-left:4px solid #1dad00;padding:12px 14px;border-radius:4px;box-shadow:0 6px 18px rgba(0,0,0,0.15);min-width:240px;max-width:360px;opacity:0;transform:translateY(8px);transition:opacity .28s ease, transform .28s ease}' +
				  '.myd-toast.negative{background:#fde6e6;border-left-color:#d32f2f}' +
				  '.myd-toast.show{opacity:1;transform:translateY(0)}' +
				  '.myd-toast-icon{width:40px;height:40px;flex:0 0 40px;display:flex;align-items:center;justify-content:center}' +
				  '.myd-toast.negative .myd-toast-icon svg path{stroke:#d32f2f; fill:#d32f2f}' +
				  '.myd-toast-body{flex:1;display:flex;flex-direction:column}' +
				  '.myd-toast-title{font-weight:700;color:#111;margin-bottom:2px}' +
				  '.myd-toast-text{color:#444;font-size:13px}';
		var s = document.createElement('style');
		s.id = 'myd-global-toast-styles';
		s.type = 'text/css';
		if (s.styleSheet) s.styleSheet.cssText = css; else s.appendChild(document.createTextNode(css));
		document.head.appendChild(s);
	}
	var container = document.getElementById('myd-toast-container');
	if (!container) { container = document.createElement('div'); container.id = 'myd-toast-container'; document.body.appendChild(container); }
	var toast = document.createElement('div');
	toast.className = 'myd-toast' + (type === 'error' ? ' negative' : '');
	var icon = document.createElement('div');
	icon.className = 'myd-toast-icon';
	if (svgHtml) { icon.innerHTML = svgHtml; }
	else if (type === 'error') { icon.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="#d32f2f" stroke-width="2"/><path d="M15 9L9 15" stroke="#d32f2f" stroke-width="2" stroke-linecap="round"/><path d="M9 9L15 15" stroke="#d32f2f" stroke-width="2" stroke-linecap="round"/></svg>'; }
	else { icon.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 12.6111L8.92308 17.5L20 6.5" stroke="#1dad00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>'; }
	var body = document.createElement('div');
	body.className = 'myd-toast-body';
	body.innerHTML = '<div class="myd-toast-title">' + (title || '') + '</div><div class="myd-toast-text">' + (message || '') + '</div>';
	toast.appendChild(icon);
	toast.appendChild(body);
	container.appendChild(toast);
	requestAnimationFrame(function(){ toast.classList.add('show'); });
	setTimeout(function(){ toast.classList.remove('show'); setTimeout(function(){ if(toast.parentNode) toast.parentNode.removeChild(toast); }, 300); }, 3000);
};

/* ── Delegated handlers: Copy delivery info & Google Maps ── */
(function(){
	var copySvg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.59961 11.3974C6.59961 8.67119 6.59961 7.3081 7.44314 6.46118C8.28667 5.61426 9.64432 5.61426 12.3596 5.61426H15.2396C17.9549 5.61426 19.3125 5.61426 20.1561 6.46118C20.9996 7.3081 20.9996 8.6712 20.9996 11.3974V16.2167C20.9996 18.9429 20.9996 20.306 20.1561 21.1529C19.3125 21.9998 17.9549 21.9998 15.2396 21.9998H12.3596C9.64432 21.9998 8.28667 21.9998 7.44314 21.1529C6.59961 20.306 6.59961 18.9429 6.59961 16.2167V11.3974Z" fill="#1dad00"></path><path opacity="0.5" d="M4.17157 3.17157C3 4.34315 3 6.22876 3 10V12C3 15.7712 3 17.6569 4.17157 18.8284C4.78913 19.446 5.6051 19.738 6.79105 19.8761C6.59961 19.0353 6.59961 17.8796 6.59961 16.2167V11.3974C6.59961 8.6712 6.59961 7.3081 7.44314 6.46118C8.28667 5.61426 9.64432 5.61426 12.3596 5.61426H15.2396C16.8915 5.61426 18.0409 5.61426 18.8777 5.80494C18.7403 4.61146 18.4484 3.79154 17.8284 3.17157C16.6569 2 14.7712 2 11 2C7.22876 2 5.34315 2 4.17157 3.17157Z" fill="#1dad00"></path></svg>';

	function fallbackCopy(text) {
		try {
			var ta = document.createElement('textarea');
			ta.value = text;
			document.body.appendChild(ta);
			ta.select();
			document.execCommand('copy');
			document.body.removeChild(ta);
			window.MydGlobalNotify('ok', 'Copiado!', 'Informações de entrega copiadas.', copySvg);
		} catch (e) {
			window.MydGlobalNotify('error', 'Erro', 'Não foi possível copiar.', null);
		}
	}

	document.addEventListener('click', function(e){
		/* ── Copy delivery info ── */
		var copyBtn = e.target.closest ? e.target.closest('.myd-copy-delivery-info') : null;
		if (copyBtn) {
			var postid = copyBtn.getAttribute('data-postid');
			var textarea = document.getElementById('myd-delivery-info-' + postid);
			if (textarea) {
				var text = textarea.value || textarea.innerText || '';
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(function(){
						window.MydGlobalNotify('ok', 'Copiado!', 'Informações de entrega copiadas.', copySvg);
					}).catch(function(){ fallbackCopy(text); });
				} else {
					fallbackCopy(text);
				}
			}
			return;
		}

		/* ── Google Maps ── */
		var gmapsBtn = e.target.closest ? e.target.closest('.myd-gmaps-link') : null;
		if (gmapsBtn) {
			var url = gmapsBtn.getAttribute('data-gmaps') || '';
			if (url) {
				try { var w = window.open(url, '_blank'); try { if (w) w.opener = null; } catch(_){} } catch(_e){}
			}
			return;
		}
	});
})();
</script>

<script>
// Editar Pedido Modal Handler
document.addEventListener('DOMContentLoaded', function(){
	var modal = document.getElementById('myd-edit-order-modal');
	var closeBtn = document.getElementById('myd-edit-order-close');
	var cancelBtn = document.getElementById('myd-edit-order-cancel');
	var form = document.getElementById('myd-edit-order-form');
	
	if (!modal || !form) return;
	
	function openModal(btn){
		var orderId = btn.getAttribute('data-order-id');
		document.getElementById('myd-edit-order-id').value = orderId;
		document.getElementById('myd-edit-customer-name').value = btn.getAttribute('data-customer-name') || '';
		document.getElementById('myd-edit-customer-phone').value = btn.getAttribute('data-customer-phone') || '';
		document.getElementById('myd-edit-address').value = btn.getAttribute('data-address') || '';
		document.getElementById('myd-edit-address-number').value = btn.getAttribute('data-address-number') || '';
		document.getElementById('myd-edit-address-comp').value = btn.getAttribute('data-address-comp') || '';
		document.getElementById('myd-edit-neighborhood').value = btn.getAttribute('data-neighborhood') || '';
		document.getElementById('myd-edit-reference').value = btn.getAttribute('data-reference') || '';
		
		modal.style.display = 'flex';
		modal.classList.remove('myd-hidden');
		modal.setAttribute('aria-hidden', 'false');
	}
	
	function closeModal(){
		modal.style.display = 'none';
		modal.classList.add('myd-hidden');
		modal.setAttribute('aria-hidden', 'true');
	}
	
	// Delegated click handler for edit buttons
	document.addEventListener('click', function(e){
		var btn = e.target.closest('.myd-edit-order-btn');
		if (btn) {
			e.preventDefault();
			e.stopPropagation();
			openModal(btn);
		}
	});
	
	if (closeBtn) closeBtn.addEventListener('click', closeModal);
	if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
	modal.querySelector('.myd-modal-backdrop').addEventListener('click', closeModal);
	
	form.addEventListener('submit', function(e){
		e.preventDefault();
		var saveBtn = document.getElementById('myd-edit-order-save');
		var originalText = saveBtn.textContent;
		saveBtn.textContent = 'Salvando...';
		saveBtn.disabled = true;
		
		var formData = new FormData(form);
		formData.append('action', 'myd_edit_order_customer');
		
		fetch('/wp-admin/admin-ajax.php', {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
		.then(function(r){ return r.json(); })
		.then(function(resp){
			if (resp.success) {
				var orderId = document.getElementById('myd-edit-order-id').value;
				var data = resp.data.data;
				
				// Atualizar o botão de edição com os novos dados
				var editBtn = document.querySelector('.myd-edit-order-btn[data-order-id="' + orderId + '"]');
				if (editBtn) {
					editBtn.setAttribute('data-customer-name', data.customer_name);
					editBtn.setAttribute('data-customer-phone', data.customer_phone);
					editBtn.setAttribute('data-address', data.address);
					editBtn.setAttribute('data-address-number', data.address_number);
					editBtn.setAttribute('data-address-comp', data.address_comp);
					editBtn.setAttribute('data-neighborhood', data.neighborhood);
					editBtn.setAttribute('data-reference', data.reference);
				}
				
				// Atualizar o myd-customer-card no frontend
				var customerCard = document.querySelector('.myd-customer-card[data-order-id="' + orderId + '"]');
				if (customerCard) {
					// Atualizar nome do cliente
					var nameEl = customerCard.querySelector('.fdm-order-list-items-customer-name');
					if (nameEl) {
						var ordersCountSpan = nameEl.querySelector('span');
						nameEl.textContent = data.customer_name + ' ';
						if (ordersCountSpan) nameEl.appendChild(ordersCountSpan);
					}
					
					// Atualizar endereço
					var addressEl = customerCard.querySelector('.myd-address-title-text');
					if (addressEl) {
						var numLabel = data.address_number || 'S/n°';
						var addrParts = [data.address, numLabel].filter(function(v){ return v; });
						var addrLeft = addrParts.join(', ');
						var midParts = [];
						if (data.neighborhood) midParts.push(data.neighborhood);
						var addrMid = midParts.join(' - ');
						var finalAddr = [addrLeft, addrMid].filter(function(v){ return v; }).join(' - ');
						addressEl.textContent = finalAddr;
					}
					
					// Atualizar complemento
					var compEl = customerCard.querySelector('.myd-address-complement');
					if (compEl && data.address_comp) {
						compEl.textContent = data.address_comp;
						compEl.style.display = '';
					} else if (compEl && !data.address_comp) {
						compEl.style.display = 'none';
					}
					
					// Atualizar ponto de referência
					var refEl = customerCard.querySelector('.fdm-order-list-items-customer');
					if (refEl && data.reference) {
						refEl.textContent = 'Ponto de referência: ' + data.reference;
						refEl.style.display = '';
					} else if (refEl && !data.reference) {
						refEl.style.display = 'none';
					}
				}
				
				closeModal();
			} else {
				alert(resp.data && resp.data.message ? resp.data.message : 'Erro ao salvar');
			}
		})
		.catch(function(err){
			console.error('Erro ao salvar pedido:', err);
			alert('Erro de conexão');
		})
		.finally(function(){
			saveBtn.textContent = originalText;
			saveBtn.disabled = false;
		});
	});
});
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"></script>
<script src="<?php echo plugins_url('assets/js/myd-modal-horarios.js', dirname(__FILE__, 3) . '/myd-delivery-pro.php'); ?>?v=<?php echo time(); ?>"></script>

<!-- Painel Fechamento de Caixa -->
<?php include __DIR__ . '/modal-caixa.php'; ?>
<link rel="stylesheet" href="<?php echo plugins_url('assets/css/myd-modal-caixa.css', dirname(__FILE__, 3) . '/myd-delivery-pro.php'); ?>?v=<?php echo time(); ?>">
<script src="<?php echo plugins_url('assets/js/myd-modal-caixa.js', dirname(__FILE__, 3) . '/myd-delivery-pro.php'); ?>?v=<?php echo time(); ?>"></script>

			<!-- Status WhatsApp -->
			<div id="myd-whatsapp-widget" role="button" tabindex="0" aria-pressed="false" style="margin-left:auto;padding:8px 12px;border:1px solid #eef0f2;border-radius:6px;background:#F5F5F5;display:flex;align-items:center;gap:8px;">
				<div style="flex:0 0 auto;font-weight:600;font-size:14px;color:#333333;">Robô WhatsApp:</div>
				<div id="myd-whatsapp-status" style="flex:1;color:#666;font-size:14px;">Carregando...</div>
				<div id="myd-whatsapp-indicator" aria-hidden="true" style="width:12px;height:12px;border-radius:50%;background:#ccc;flex:0 0 auto;border:1px solid #ddd"></div>
			</div>

		</div>
	</div>

	<div class="fdm-orders-content">
		<!-- Painel de informações do dashboard (Resumo) -->
		<div class="dashboard-panel" id="dashboard-panel">
			<script>
			// Alterna a exibição do dashboard-panel ao clicar nos botões de Menu e Pedidos
			document.addEventListener('DOMContentLoaded', function() {
				var pedidosBtn = document.querySelector('a.myd-sidebar-item[href="#myd-section-orders"]');
				var menuBtn = document.querySelector('a.myd-sidebar-item[href="#myd-section-menu"]');
				var dashboardPanel = document.getElementById('dashboard-panel');
				if (pedidosBtn && dashboardPanel) {
					pedidosBtn.addEventListener('click', function() {
						dashboardPanel.style.display = 'none';
					});
				}
				if (menuBtn && dashboardPanel) {
					menuBtn.addEventListener('click', function() {
						dashboardPanel.style.display = '';
					});
				}
			});
			</script>
			<?php
				$myd_current_user = function_exists('wp_get_current_user') ? wp_get_current_user() : null;
				$myd_user_name = ($myd_current_user && ! empty( $myd_current_user->display_name ))
					? $myd_current_user->display_name
					: ( $myd_current_user && ! empty( $myd_current_user->user_login ) ? $myd_current_user->user_login : __('Usuário', 'myd-delivery-pro') );
			?>
			<h2 class="dashboard-title">👋 Olá, <?php echo esc_html( $myd_user_name ); ?></h2>
			<div class="dashboard-info">
				<div class="dashboard-info-block">
					<strong>Horário de funcionamento</strong>
					<div class="dashboard-info-sub">
						<span class="dashboard-day-link active" id="dashboard-day-today" role="button" tabindex="0">Hoje</span>
						<span class="dashboard-day-link" id="dashboard-day-tomorrow" role="button" tabindex="0">Amanhã</span>
					</div>
					<?php
						$myd_opening = get_option( 'myd-delivery-time', array() );
						$today_key = strtolower( date( 'l' ) ); // monday, tuesday, ...
						$tomorrow_key = strtolower( date( 'l', strtotime('+1 day') ) );

						$format_intervals = function( $arr ) {
							$out = array();
							if ( ! is_array( $arr ) ) return $out;
							foreach ( $arr as $slot ) {
								$start = isset( $slot['start'] ) ? $slot['start'] : '';
								$end = isset( $slot['end'] ) ? $slot['end'] : '';
								if ( $start !== '' && $end !== '' ) $out[] = $start . ' - ' . $end;
							}
							return $out;
						};

						$today_intervals = isset( $myd_opening[ $today_key ] ) ? $format_intervals( $myd_opening[ $today_key ] ) : array();
						$tomorrow_intervals = isset( $myd_opening[ $tomorrow_key ] ) ? $format_intervals( $myd_opening[ $tomorrow_key ] ) : array();

						$hours_display = count( $today_intervals ) ? implode( ', ', $today_intervals ) : 'Fechado';
						$tomorrow_display = count( $tomorrow_intervals ) ? implode( ', ', $tomorrow_intervals ) : 'Fechado';
					?>
					<div id="dashboard-hours" class="dashboard-hours" data-today="<?php echo esc_attr( $hours_display ); ?>" data-tomorrow="<?php echo esc_attr( $tomorrow_display ); ?>"><?php echo esc_html( $hours_display ); ?></div>
				</div>
				<div class="dashboard-info-block">
					<strong>Itens pausados no cardápio</strong>
					<?php
					// Count products where product_available is not 'show' or meta does not exist
					$paused_count = 0;
					$args = array(
						'post_type'      => 'mydelivery-produtos',
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'meta_query'     => array(
							'relation' => 'OR',
							array(
								'key'     => 'product_available',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => 'product_available',
								'value'   => 'show',
								'compare' => '!=',
							),
						),
					);
					$q = new WP_Query( $args );
					if ( $q && ! is_wp_error( $q ) ) {
						$paused_count = is_array( $q->posts ) ? count( $q->posts ) : 0;
					}
					wp_reset_postdata();
					?>
					<div class="dashboard-info-value"><?php echo esc_html( (int) $paused_count ); ?></div>
				</div>
			</div>
			<?php
			// Precompute tooltip text for orders stats: from 1st of current month 00:00 to yesterday 23:59:59
			$now_ts = isset( $now_ts ) ? (int) $now_ts : (int) current_time( 'timestamp' );
			$month_start_ts = isset( $month_start_ts ) ? (int) $month_start_ts : strtotime( date( 'Y-m-01 00:00:00', $now_ts ) );
			$today_midnight = isset( $today_midnight ) ? (int) $today_midnight : strtotime( date( 'Y-m-d 00:00:00', $now_ts ) );
			$yesterday_end_ts = isset( $yesterday_end_ts ) ? (int) $yesterday_end_ts : ( $today_midnight - 1 );
			$orders_tooltip = sprintf(
				'%s',
				sprintf(
					'Dados de vendas concluídas entre o dia %s às %s e %s às %s.',
					date_i18n( 'd/m', $month_start_ts ),
					date_i18n( 'H:i', $month_start_ts ),
					date_i18n( 'd/m', $yesterday_end_ts ),
					date_i18n( 'H:i', $yesterday_end_ts )
				)
			);
			?>
			<div class="dashboard-orders">
				<div class="dashboard-orders-title">
					<strong>Pedidos concluídos do mês <span class="dashboard-orders-info" data-tooltip="<?php echo esc_attr( $orders_tooltip ); ?>">&#9432;</span></strong>
				</div>
				<div class="dashboard-stats">
					<?php
					// Compute time window: from 1st of current month 00:00 to yesterday 23:59:59 (local WP time)
					$now_ts = (int) current_time( 'timestamp' );
					$month_start_ts = strtotime( date( 'Y-m-01 00:00:00', $now_ts ) );
					$today_midnight = strtotime( date( 'Y-m-d 00:00:00', $now_ts ) );
					$yesterday_end_ts = $today_midnight - 1; // yesterday 23:59:59

					// Previous period: same length, starting at first day of previous month
					$period_length = $yesterday_end_ts - $month_start_ts;
					$prev_month_start_ts = strtotime( date( 'Y-m-01 00:00:00', strtotime( '-1 month', $now_ts ) ) );
					$prev_period_end_ts = $prev_month_start_ts + $period_length;

					function myd_count_finished_orders_in_range( $start_ts, $end_ts ) {
						$ids = array();

						// 1) Orders with explicit status-change timestamp inside range
						$q1 = new WP_Query( array(
							'post_type'      => 'mydelivery-orders',
							'post_status'    => 'publish',
							'posts_per_page' => -1,
							'fields'         => 'ids',
							'meta_query'     => array(
								array( 'key' => 'order_status', 'value' => 'finished' ),
								array( 'key' => 'order_status_changed_ts', 'value' => array( (int) $start_ts, (int) $end_ts ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' ),
							),
						) );
						if ( $q1 && ! is_wp_error( $q1 ) && ! empty( $q1->posts ) ) {
							$ids = array_merge( $ids, $q1->posts );
						}
						wp_reset_postdata();

						// 2) Orders without order_status_changed_ts: fall back to post_date in the same range
						$q2 = new WP_Query( array(
							'post_type'      => 'mydelivery-orders',
							'post_status'    => 'publish',
							'posts_per_page' => -1,
							'fields'         => 'ids',
							'meta_query'     => array(
								array( 'key' => 'order_status', 'value' => 'finished' ),
								array( 'key' => 'order_status_changed_ts', 'compare' => 'NOT EXISTS' ),
							),
							'date_query'     => array(
								array( 'after' => date( 'Y-m-d H:i:s', $start_ts ), 'before' => date( 'Y-m-d H:i:s', $end_ts ), 'inclusive' => true ),
							),
						) );
						if ( $q2 && ! is_wp_error( $q2 ) && ! empty( $q2->posts ) ) {
							$ids = array_merge( $ids, $q2->posts );
						}
						wp_reset_postdata();

						$ids = array_unique( $ids );
						return count( $ids );
					}

					$finished_current = myd_count_finished_orders_in_range( $month_start_ts, $yesterday_end_ts );
					$finished_prev = myd_count_finished_orders_in_range( $prev_month_start_ts, $prev_period_end_ts );
					?>

					<div class="dashboard-stat">
						<div class="dashboard-stat-value"><?php echo esc_html( (int) $finished_current ); ?></div>
						<div class="dashboard-stat-label">Mês atual</div>
					</div>
					<div class="dashboard-stat">
						<div class="dashboard-stat-value"><?php echo esc_html( (int) $finished_prev ); ?></div>
						<div class="dashboard-stat-label">Mesmo período do mês anterior</div>
					</div>
				</div>
			</div>
		</div>
		<!-- Styles migrated to assets/css/panel-inline.css (override, welcome cover, modals, greeting, action bar) -->

		<!-- Cancel confirmation modal -->
	<div id="myd-cancel-modal" class="myd-modal myd-hidden" aria-hidden="true" role="dialog" aria-modal="true">
			<div class="myd-modal-backdrop"></div>
			<div class="myd-modal-dialog">
				<div class="myd-modal-title">Motivos do cancelamento:</div>
				<fieldset style="margin:8px 0 12px;border:0;padding:0;">
					<label style="display:block;margin:6px 0;"><input type="radio" name="myd-cancel-reason" value="cliente_desistiu"> Cliente desistiu</label>
					<label style="display:block;margin:6px 0;"><input type="radio" name="myd-cancel-reason" value="tempo_espera"> Tempo de espera muito longo</label>
					<label style="display:block;margin:6px 0;"><input type="radio" name="myd-cancel-reason" value="item_indisponivel"> Item indisponível</label>
					<label style="display:block;margin:6px 0;"><input type="radio" name="myd-cancel-reason" value="cliente_pediu_errado"> Cliente pediu item errado</label>
					<label style="display:block;margin:6px 0;"><input type="radio" name="myd-cancel-reason" value="endereco_incorreto"> Endereço incorreto</label>
					<label style="display:block;margin:6px 0;"><input type="radio" name="myd-cancel-reason" value="outro"> Outro</label>
					<div id="myd-cancel-reason-other-wrap" style="display:none;margin-top:8px;">
						<textarea id="myd-cancel-reason-other" name="myd-cancel-reason-other" maxlength="120" rows="1" placeholder="Descreva o motivo (máx. 120 caracteres)" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;resize:none;overflow:hidden;box-sizing:border-box;height:40px;"></textarea>
					</div>
				</fieldset>
				<!-- Payment info for cancel (filled dynamically) -->
				<div id="myd-cancel-payment-info" style="display:none;margin-top:12px;margin-bottom:12px;padding:8px;border-left:3px solid #ffe6e6;background:#fff6f6;border-radius:4px;">
					<div id="myd-cancel-payment-text" style="margin-bottom:8px;color:#333;font-size:17px;font-weight:500;"></div>
					<label style="display:flex;align-items:center;gap:8px;">
						<input type="checkbox" id="myd-cancel-refund-checkbox" name="myd-cancel-refund" value="1">
						<span>Desejo reembolsar o valor TOTAL do pedido.</span>
					</label>
				</div>
				<script>
				(function(){
					function toggleOther(){
						var radios = document.getElementsByName('myd-cancel-reason');
						var wrap = document.getElementById('myd-cancel-reason-other-wrap');
						var found = false;
						for (var i=0;i<radios.length;i++){
							if (radios[i].checked && radios[i].value === 'outro'){
								found = true;
								break;
							}
						}
						if (found) {
							if (wrap) { wrap.style.display = 'block'; var inp = document.getElementById('myd-cancel-reason-other'); try{ inp.focus(); }catch(e){} }
						} else {
							if (wrap) { wrap.style.display = 'none'; var inp = document.getElementById('myd-cancel-reason-other'); if (inp) inp.value = ''; }
						}
						updateCancelConfirmState();
					}
					// attach handlers
					document.addEventListener('change', function(ev){ if (ev.target && ev.target.name === 'myd-cancel-reason') toggleOther(); });
					// auto-resize textarea
					var otherTa = null;
					function autosizeOther(){
						if (!otherTa) otherTa = document.getElementById('myd-cancel-reason-other');
						if (!otherTa) return;
						otherTa.style.height = 'auto';
						var h = Math.min(otherTa.scrollHeight, 300);
						if (h < 40) h = 40;
						otherTa.style.height = h + 'px';
					}
					document.addEventListener('input', function(ev){ if (ev.target && ev.target.id === 'myd-cancel-reason-other') { autosizeOther(); updateCancelConfirmState(); } });
					// update confirm button state
					function updateCancelConfirmState(){
						var confirmBtn = document.getElementById('myd-cancel-modal-confirm');
						if (!confirmBtn) return;
						var radios = document.getElementsByName('myd-cancel-reason');
						var checked = null;
						for (var i=0;i<radios.length;i++){
							if (radios[i].checked) { checked = radios[i].value; break; }
						}
						if (!checked) { confirmBtn.disabled = true; return; }
						if (checked === 'outro'){
							var other = document.getElementById('myd-cancel-reason-other');
							var v = other ? (other.value || '').trim() : '';
							if (v.length < 5) { confirmBtn.disabled = true; return; }
						}
						confirmBtn.disabled = false;
					}
					// also run on load in case modal is prefilled
					setTimeout(function(){ toggleOther(); autosizeOther(); updateCancelConfirmState(); }, 50);
				})();
				</script>
				<div class="myd-modal-actions">
					<button type="button" class="myd-btn myd-btn-neutral" id="myd-cancel-modal-close">Não cancelar</button>
					<button type="button" class="myd-btn myd-btn-danger" id="myd-cancel-modal-confirm">Sim, cancelar pedido</button>
				</div>
				<style>
					/* Visual style for disabled modal action buttons */
					#myd-cancel-modal .myd-modal-actions .myd-btn[disabled],
					#myd-cancel-modal .myd-modal-actions .myd-btn:disabled {
						background: #d1d1d1 !important;
						border-color: #cfcfcf !important;
						color: #6b6b6b !important;
						opacity: 1 !important;
						cursor: not-allowed !important;
						box-shadow: none !important;
					}
				</style>
			</div>
		</div>

		<script>
		// Consolidated panel helpers: height calc, empty state, printing, counts, dedupe and detail loading
		document.addEventListener('DOMContentLoaded', function(){
			// 1) Adjust heights to account for admin bar and plugin top-bar
			try {
				var adminBar = document.getElementById('wpadminbar');
				var topBarEl = document.querySelector('.top-bar');

				function adjustSidebarOffset(){
					var adminOffset = adminBar ? adminBar.offsetHeight : 0;
					var topBarOffset = topBarEl ? topBarEl.offsetHeight : (document.querySelector('.top-bar') ? document.querySelector('.top-bar').offsetHeight : 0);
					var totalOffset = adminOffset + topBarOffset;
					var h = 'calc(100vh - ' + totalOffset + 'px)';
					var content = document.querySelector('.fdm-orders-content');
					if (content) content.style.setProperty('height', h, 'important');
					// Ajustar altura das colunas kanban
					var list = document.querySelector('.fdm-orders-list');
					var loop = document.querySelector('.fdm-orders-loop');
					if (list) list.style.setProperty('height', h, 'important');
					if (loop) loop.style.setProperty('height', h, 'important');
				}

				// initial adjust
				adjustSidebarOffset();

				// recompute on resize
				window.addEventListener('resize', adjustSidebarOffset);

				// observe topBar changes (dynamic content)
				if (topBarEl && window.MutationObserver) {
					var topObs = new MutationObserver(function(){ setTimeout(adjustSidebarOffset, 30); });
					topObs.observe(topBarEl, { childList:true, subtree:true, attributes:true, attributeFilter:['style','class'] });
				}

				// observe adminBar changes
				if (adminBar && window.MutationObserver) {
					var adminObs = new MutationObserver(function(){ setTimeout(adjustSidebarOffset, 30); });
					adminObs.observe(adminBar, { childList:true, subtree:true, attributes:true, attributeFilter:['style','class'] });
				}
			} catch(e) { /* noop */ }



			// 3) Printing via local print server (adds print buttons dynamically)
			(function(){
				function sendOrderToLocalPrinter(orderData){
					return fetch('http://localhost:3420/print', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(orderData) });
				}
				function ensurePrintButtons(){
					try{
						var actions = document.querySelectorAll('.fdm-orders-list-action-full');
						actions.forEach(function(bar){
							if (bar.querySelector('.myd-print-btn')) return;
							var btn = document.createElement('button');
							btn.type = 'button';
							btn.className = 'myd-btn myd-print-btn';
							// round icon-only button
							btn.setAttribute('aria-label', 'Imprimir pedido');
							// debug attribute to easily locate inserted buttons
							btn.setAttribute('data-myd-icon-btn', '1');
							// debug log to help confirm this script ran in the browser
							try{ console.log('[MYD] ensurePrintButtons: creating icon print button'); }catch(e){}
							btn.style.marginRight = '12px';
							btn.style.width = '40px';
							btn.style.height = '40px';
							btn.style.padding = '6px';
							btn.style.borderRadius = '50%';
							btn.style.display = 'inline-flex';
							btn.style.alignItems = 'center';
							btn.style.justifyContent = 'center';
							btn.style.border = 'none';
							btn.style.background = 'transparent';
							// SVG icon (printer) provided
							btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 18H6.2C5.0799 18 4.51984 18 4.09202 17.782C3.71569 17.5903 3.40973 17.2843 3.21799 16.908C3 16.4802 3 15.9201 3 14.8V10.2C3 9.0799 3 8.51984 3.21799 8.09202C3.40973 7.71569 3.71569 7.40973 4.09202 7.21799C4.51984 7 5.0799 7 6.2 7H7M17 18H17.8C18.9201 18 19.4802 18 19.908 17.782C20.2843 17.5903 20.5903 17.2843 20.782 16.908C21 16.4802 21 15.9201 21 14.8V10.2C21 9.07989 21 8.51984 20.782 8.09202C20.5903 7.71569 20.2843 7.40973 19.908 7.21799C19.4802 7 18.9201 7 17.8 7H17M7 11H7.01M17 7V5.4V4.6C17 4.03995 17 3.75992 16.891 3.54601C16.7951 3.35785 16.6422 3.20487 16.454 3.10899C16.2401 3 15.9601 3 15.4 3H8.6C8.03995 3 7.75992 3 7.54601 3.10899C7.35785 3.20487 7.20487 3.35785 7.10899 3.54601C7 3.75992 7 4.03995 7 4.6V5.4V7M17 7H7M8.6 21H15.4C15.9601 21 16.2401 21 16.454 20.891C16.6422 20.7951 16.7951 20.6422 16.891 20.454C17 20.2401 17 19.9601 17 19.4V16.6C17 16.0399 17 15.7599 16.891 15.546C16.7951 15.3578 16.6422 15.2049 16.454 15.109C16.2401 15 15.9601 15 15.4 15H8.6C8.03995 15 7.75992 15 7.54601 15.109C7.35785 15.2049 7.20487 15.3578 7.10899 15.546C7 15.7599 7 16.0399 7 16.6V19.4C7 19.9601 7 20.2401 7.10899 20.454C7.20487 20.6422 7.35785 20.7951 7.54601 20.891C7.75992 21 8.03995 21 8.6 21Z" stroke="#ffae00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
							btn.onclick = async function(ev){
								try{
									return;
								}catch(err){ console.error('Erro no clique do botão de impressão', err); }
							};
							var parent = bar.querySelector('.myd-quick-actions') || bar; parent.insertBefore(btn, parent.firstChild);
						});
					}catch(e){}
				}
				var obsPrint = new MutationObserver(function(){ ensurePrintButtons(); }); obsPrint.observe(document.body, { childList:true, subtree:true }); ensurePrintButtons();
			})();

			// 4) Live counts refresher
			(function(){
				function refreshOrderSectionCounts(){
					try{
						var secNew = document.querySelector('#myd-section-new .myd-orders-accordion-body');
						var secProd = document.querySelector('#myd-section-production .myd-orders-accordion-body');
						var secDone = document.querySelector('#myd-section-done .myd-orders-accordion-body');
						var cntNew = secNew ? secNew.querySelectorAll('.fdm-orders-items').length : 0;
						var cntProd = secProd ? secProd.querySelectorAll('.fdm-orders-items').length : 0;
						var cntDone = secDone ? secDone.querySelectorAll('.fdm-orders-items').length : 0;
						var elNew = document.querySelector('#myd-section-new .myd-orders-section-count');
						var elProd = document.querySelector('#myd-section-production .myd-orders-section-count');
						var elDone = document.querySelector('#myd-section-done .myd-orders-section-count');
						if(elNew && elNew.textContent != cntNew) elNew.textContent = cntNew;
						if(elProd && elProd.textContent != cntProd) elProd.textContent = cntProd;
						if(elDone && elDone.textContent != cntDone) elDone.textContent = cntDone;
						
						// Atualizar badges da sidebar
						var badgeNew = document.getElementById('myd-sidebar-badge-new');
						var badgeProd = document.getElementById('myd-sidebar-badge-production');
						var badgeDone = document.getElementById('myd-sidebar-badge-done');
						if(badgeNew) badgeNew.textContent = cntNew;
						if(badgeProd) badgeProd.textContent = cntProd;
						if(badgeDone) badgeDone.textContent = cntDone;
					}catch(e){}
				}
				if(!window.MydRefreshOrderCounts) window.MydRefreshOrderCounts = refreshOrderSectionCounts;
				if(document.readyState==='complete' || document.readyState==='interactive') setTimeout(refreshOrderSectionCounts,50); else document.addEventListener('DOMContentLoaded', function(){ setTimeout(refreshOrderSectionCounts,50); });
				try{
					var loop = document.querySelector('.fdm-orders-loop');
					if(loop && window.MutationObserver){
						var obs = new MutationObserver(function(m){ var changed = false; for(var i=0;i<m.length;i++){ var rec = m[i]; if(rec.addedNodes && rec.addedNodes.length) { changed = true; break; } if(rec.removedNodes && rec.removedNodes.length) { changed = true; break; } } if(changed) setTimeout(refreshOrderSectionCounts, 30); });
						obs.observe(loop, { childList:true, subtree:true });
					}
				}catch(e){}
			})();

			// 5) Sidebar navigation
			(function(){
				try{
					var sidebarItems = document.querySelectorAll('.myd-sidebar-item[data-section]');
					sidebarItems.forEach(function(item){
						item.addEventListener('click', function(e){
							e.preventDefault();
							var section = this.getAttribute('data-section');
							var targetId = '#myd-section-' + section;
							var targetEl = document.querySelector(targetId);
							
							// Remover active de todos e adicionar no clicado
							sidebarItems.forEach(function(i){ i.classList.remove('active'); });
							this.classList.add('active');
							
							// Scroll suave até a seção
							if(targetEl){
								targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
								// Expandir accordion se estiver fechado
								var accordionBtn = targetEl.querySelector('.myd-orders-accordion-btn');
								var accordionBody = targetEl.querySelector('.myd-orders-accordion-body');
								if(accordionBtn && accordionBody){
									if(accordionBody.style.display === 'none' || !accordionBody.style.display){
										accordionBody.style.display = 'block';
										accordionBtn.classList.add('active');
									}
								}
							}
						});
					});
					
					// Detectar seção visível e atualizar sidebar active
					function updateActiveSection(){
						try{
							var sections = [
								{id: '#myd-section-new', section: 'new'},
								{id: '#myd-section-production', section: 'production'},
								{id: '#myd-section-done', section: 'done'}
							];
							var scrollTop = document.querySelector('.fdm-orders-loop') ? document.querySelector('.fdm-orders-loop').scrollTop : 0;
							
							sections.forEach(function(s){
								var el = document.querySelector(s.id);
								if(el){
									var rect = el.getBoundingClientRect();
									if(rect.top >= 0 && rect.top < window.innerHeight / 2){
										var sidebarItem = document.querySelector('.myd-sidebar-item[data-section="' + s.section + '"]');
										if(sidebarItem && !sidebarItem.classList.contains('active')){
											sidebarItems.forEach(function(i){ i.classList.remove('active'); });
											sidebarItem.classList.add('active');
										}
									}
								}
							});
						}catch(e){}
					}
					
					var loopEl = document.querySelector('.fdm-orders-loop');
					if(loopEl){
						loopEl.addEventListener('scroll', function(){ setTimeout(updateActiveSection, 100); });
					}
				}catch(e){ console.error('Erro ao inicializar sidebar navigation', e); }
			})();

			// 6) Merge duplicate sections helper (runs once + observer)
			(function(){
				function ensureMerge(){
					try{
						var ids = [{id:'#myd-section-new', title:'Novos Pedidos'},{id:'#myd-section-production', title:'Em produção'},{id:'#myd-section-done', title:'Concluídos'}];
						ids.forEach(function(def){
							var nodes = document.querySelectorAll(def.id);
							if (!nodes || nodes.length <= 1) return;
							var keeper = nodes[0];
							var keeperBody = keeper.querySelector('.myd-orders-accordion-body') || keeper;
							for (var i = 1; i < nodes.length; i++){
								var n = nodes[i];
								var items = n.querySelectorAll ? n.querySelectorAll('.fdm-orders-items') : [];
								Array.prototype.forEach.call(items, function(it){ try { keeperBody.appendChild(it); } catch(e){ try { keeperBody.insertAdjacentElement('beforeend', it); } catch(_){} } });
								try { n.parentNode && n.parentNode.removeChild(n); } catch(e){}
							}
							try{ var cntEl = keeper.querySelector('.myd-orders-section-count'); if (cntEl) cntEl.textContent = keeperBody.querySelectorAll('.fdm-orders-items').length; } catch(e){}
						});
						if (window.MydDedupeOrders && typeof window.MydDedupeOrders === 'function') { try { window.MydDedupeOrders(); } catch(e){} }
					}catch(e){}
				}
				if (document.readyState === 'complete' || document.readyState === 'interactive') setTimeout(ensureMerge, 50); else document.addEventListener('DOMContentLoaded', function(){ setTimeout(ensureMerge, 50); });
				try{ var loop = document.querySelector('.fdm-orders-loop'); if (loop && window.MutationObserver) { var obs = new MutationObserver(function(){ setTimeout(ensureMerge, 50); }); obs.observe(loop, { childList: true, subtree: true }); } }catch(e){}
			})();

			// 6) Detail loader: fetch and inject full details when needed
			(function(){
				var loopEl = document.querySelector('.fdm-orders-loop');
				if (!loopEl) return;

				function smoothScrollIntoView(el){
					if (!el) return;
					try {
						var adminBar = document.getElementById('wpadminbar');
						var offset = adminBar ? adminBar.offsetHeight : 0;
						var rect = el.getBoundingClientRect();
						var top = rect.top + window.pageYOffset - (offset + 8);
						window.scrollTo({ top: top, behavior: 'smooth' });
					} catch(_) { try { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch(__) {} }
				}

				function fetchAndAttachFull(orderId){
					function attach(fullHtml){
						if (!fullHtml) return false;
						var host = document.querySelector('.fdm-orders-full');
						if (!host) return false;
						var parsed = null;
						if (window.DOMParser && typeof DOMParser === 'function') {
							try { parsed = new DOMParser().parseFromString(fullHtml, 'text/html'); } catch(e){ parsed = document.createElement('div'); parsed.innerHTML = fullHtml; }
						} else { parsed = document.createElement('div'); parsed.innerHTML = fullHtml; }
						var sel = '#content-' + CSS.escape(String(orderId));
						var fullEl = null;
						try { fullEl = parsed.querySelector(sel); } catch(_){ fullEl = null; }
						if (!fullEl) {
							var candidates = parsed.querySelectorAll ? parsed.querySelectorAll('.fdm-orders-full-items') : [];
							for (var i=0;i<candidates.length;i++){ if (String(candidates[i].id) === ('content-' + String(orderId))) { fullEl = candidates[i]; break; } }
						}
						if (!fullEl) return false;
						try { var existing = host.querySelector(sel); if (existing && existing.parentNode) existing.parentNode.removeChild(existing); } catch(_){ }
						try { fullEl.style.display = 'none'; } catch(_){ }
						try { host.appendChild(fullEl); } catch(e){ try { host.insertAdjacentElement('beforeend', fullEl); } catch(_){ } }
						return true;
					}

					// Try admin-ajax first
					try {
						if (window.order_ajax_object && window.order_ajax_object.ajax_url && window.order_ajax_object.nonce){
							fetch(window.order_ajax_object.ajax_url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Cache-Control': 'no-cache' }, body: 'action=update_orders&nonce=' + encodeURIComponent(window.order_ajax_object.nonce) }).then(function(r){ return r.json(); }).then(function(resp){ if (resp && resp.full) attach(resp.full); }).catch(function(){});
							return;
						}
					} catch(_){ }

					// Fallback REST
					var restUrl = (window.location && window.location.origin ? window.location.origin : '') + '/wp-json/my-delivery/v1/orders?oid=' + encodeURIComponent(String(orderId));
					fetch(restUrl, { credentials: 'same-origin' }).then(function(r){ if(!r.ok) throw 0; return r.json(); }).then(function(resp){ if (resp && resp.full) attach(resp.full); }).catch(function(){});
				}

				loopEl.addEventListener('click', function(e){
					var item = e.target && e.target.closest ? e.target.closest('.fdm-orders-items') : null;
					if (!item) return;
					try { item.setAttribute('data-order-clicked','true'); } catch(_){ }
					setTimeout(function(){
						var id = item.getAttribute('id'); if (!id) return;
						var sel = '#content-' + CSS.escape(String(id)); var fullEl = document.querySelector(sel);
						if (!fullEl) { fetchAndAttachFull(id, true); } else { try { document.querySelectorAll('.fdm-orders-full-items').forEach(function(n){ n.style.display = 'none'; }); fullEl.style.display = 'flex'; var cont = document.querySelector('.fdm-orders-full-details'); if (cont) { cont.style.display = 'flex'; cont.classList.add('myd-detail-open'); } smoothScrollIntoView(fullEl); } catch(_) {} }
					}, 30);
				});

				// show injected details when inserted after click
				var fullHost = document.querySelector('.fdm-orders-full');
				if (fullHost && window.MutationObserver) {
					var obs = new MutationObserver(function(muts){
						muts.forEach(function(m){
							if (!m.addedNodes || !m.addedNodes.length) return;
							Array.prototype.forEach.call(m.addedNodes, function(node){
								if (!(node && node.nodeType === 1)) return;
								if (!node.classList || !node.classList.contains('fdm-orders-full-items')) return;
								var id = (node.id || '').replace('content-',''); if (!id) return;
								var item = document.getElementById(id);
								if (item && (item.getAttribute('data-order-clicked') === 'true' || item.classList.contains('fdm-active'))) {
									try { document.querySelectorAll('.fdm-orders-full-items').forEach(function(n){ if(n!==node) n.style.display = 'none'; }); node.style.display = 'flex'; var cont = document.querySelector('.fdm-orders-full-details'); if (cont) { cont.style.display = 'flex'; cont.classList.add('myd-detail-open'); } try { smoothScrollIntoView(node); } catch(_) {} } catch(_) {}
								}
							});
						});
					});
					obs.observe(fullHost, { childList: true });
				}
			})();
		});
		</script>

		<style>
			/* Esconde a lista de pedidos quando a tela principal (welcome) está ativa */
			.fdm-orders-list.myd-list-hidden.myd-list-hidden {
				display: none !important;
			}
			/* Border right no item ativo da sidebar */
			.myd-sidebar-item.active {
				border-left: none !important;
				border-right: 3px solid #ffbb00 !important;
			}
		</style>



		<div class="fdm-orders-list myd-list-hidden" id="myd-orders-list-panel">
			

			<div class="fdm-orders-loop">
				<?php require MYD_PLUGIN_PATH . 'templates/order/order-list.php'; ?>
			</div>

			<script>
			// Guard: merge duplicate myd sections and move items to canonical containers
			(function(){
				function ensureMerge(){
					try{
						var ids = [{id:'#myd-section-new', title:'Novos Pedidos'},{id:'#myd-section-production', title:'Em produção'},{id:'#myd-section-done', title:'Concluídos'}];
						ids.forEach(function(def){
							var nodes = document.querySelectorAll(def.id);
							if (!nodes || nodes.length <= 1) return;
							var keeper = nodes[0];
							var keeperBody = keeper.querySelector('.myd-orders-accordion-body') || keeper;
							for (var i = 1; i < nodes.length; i++){
								var n = nodes[i];
								// move any order items into keeperBody
								var items = n.querySelectorAll ? n.querySelectorAll('.fdm-orders-items') : [];
								Array.prototype.forEach.call(items, function(it){ try { keeperBody.appendChild(it); } catch(e){ try { keeperBody.insertAdjacentElement('beforeend', it); } catch(_){} } });
								// remove the duplicate container
								try { n.parentNode && n.parentNode.removeChild(n); } catch(e){}
							}
							// update count if present
							try{
								var cntEl = keeper.querySelector('.myd-orders-section-count');
								if (cntEl) cntEl.textContent = keeperBody.querySelectorAll('.fdm-orders-items').length;
							} catch(e){}
						});
						// run global dedupe if present
						if (window.MydDedupeOrders && typeof window.MydDedupeOrders === 'function') {
							try { window.MydDedupeOrders(); } catch(e){}
						}
					}catch(e){/* silent */}
				}
				if (document.readyState === 'complete' || document.readyState === 'interactive') setTimeout(ensureMerge, 50); else document.addEventListener('DOMContentLoaded', function(){ setTimeout(ensureMerge, 50); });
				// also observe future additions and merge on-the-fly
				try{
					var loop = document.querySelector('.fdm-orders-loop');
					if (loop && window.MutationObserver) {
						var obs = new MutationObserver(function(){ setTimeout(ensureMerge, 50); });
						obs.observe(loop, { childList: true, subtree: true });
					}
				}catch(e){}
			})();
			</script>
		</div>

		<div class="fdm-orders-full-details">
			<button type="button" class="fdm-orders-full-close" aria-label="Fechar painel" title="Fechar painel" onclick="try{ window.MydCloseDetailOverlay && window.MydCloseDetailOverlay(); }catch(e){}">
				<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M18 6L6 18M6 6L18 18" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<!-- Botão fechar overlay -->
			<script>
			(function(){
				function closeDetailOverlay(){
					var cont = document.querySelector('.fdm-orders-full-details');
					if (cont) { cont.style.display = 'none'; cont.classList.remove('myd-detail-open'); }
					document.querySelectorAll('.fdm-orders-full-items').forEach(function(n){ n.style.display = 'none'; });
					// Remover active do card na lista
					document.querySelectorAll('.fdm-orders-items.fdm-active').forEach(function(n){ n.classList.remove('fdm-active'); });
				}
				window.MydCloseDetailOverlay = closeDetailOverlay;
				// Fechar ao clicar no backdrop (fora do painel)
				document.addEventListener('click', function(e){
					var overlay = e.target.closest('.fdm-orders-full-details');
					if (!overlay) return;
					// Se clicou diretamente no overlay (backdrop), fechar
					if (e.target === overlay) closeDetailOverlay();
				});
				// Fechar com ESC
				document.addEventListener('keydown', function(e){
					if (e.key === 'Escape') {
						var cont = document.querySelector('.fdm-orders-full-details');
						if (cont && cont.classList.contains('myd-detail-open')) closeDetailOverlay();
					}
				});
			})();
			</script>
			<?php 
				$current_user = function_exists('wp_get_current_user') ? wp_get_current_user() : null; 
				$__myd_user_name = ($current_user && isset($current_user->ID) && $current_user->ID)
					? ( $current_user->display_name ?: $current_user->user_login )
					: __('Usuário', 'myd-delivery-pro');
			?>

			<div class="fdm-orders-full">
				<div id="myd-order-detail-container"></div>

                    
				</div>
				<script>
				(function(){
					var ajaxUrl = '<?php echo esc_js( admin_url( "admin-ajax.php" ) ); ?>';
					var nonce = '<?php echo wp_create_nonce( "myd-order-notification" ); ?>';

					function renderOrderDetail(data){
						try{
							var container = document.getElementById('myd-order-detail-container');
							if(!container) return;
							// Remove placeholder
							var ph = document.getElementById('myd-detail-placeholder'); if(ph) ph.style.display = 'none';

							// Create detail wrapper (id content-<order_id>)
							var id = String(data.id || '');
							var wrapperId = 'content-' + id;
							var existing = document.getElementById(wrapperId);
							if(existing) { existing.parentNode.removeChild(existing); }
							var wrap = document.createElement('div');
							wrap.className = 'fdm-orders-full-items';
							wrap.id = wrapperId;

							// Header
							var header = document.createElement('div'); header.className = 'myd-detail-header';
							var title = document.createElement('div'); title.className = 'myd-detail-title';
							title.innerHTML = '<div style="font-weight:700;font-size:18px;">Pedido #' + (data.id || '') + ' • ' + (data.customer_name || '') + '</div>';
							header.appendChild(title);
							// Close button (placed inside header, top-right)
							try {
								var closeBtn = document.createElement('button');
								closeBtn.type = 'button';
								closeBtn.className = 'myd-detail-close-btn';
								closeBtn.title = 'Fechar';
								closeBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6L18 18" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
								closeBtn.addEventListener('click', function(){ try{ window.MydCloseDetailOverlay && window.MydCloseDetailOverlay(); } catch(_){}});
								header.appendChild(closeBtn);
							} catch(e) {}

							wrap.appendChild(header);

							// Address / basic info
							var info = document.createElement('div'); info.className = 'myd-card-section';
							// Mapeia código para rótulo legível
							var paymentLabels = { 'CRD': 'Crédito', 'DEB': 'Débito', 'VRF': 'Vale-refeição', 'DIN': 'Dinheiro' };
							var paymentLabel = paymentLabels[data.payment_method] || data.payment_method || '';
							info.innerHTML = '<div style="margin-top:8px;color:#333;">' + (data.address ? (data.address + (data.address_number ? ' • ' + data.address_number : '')) : '') + '</div>' +
											 '<div style="margin-top:6px;color:#666;">Pagamento: ' + (data.payment_status || '') + ' / Método: ' + paymentLabel + '</div>' +
											 '<div style="margin-top:6px;color:#666;">Total: ' + (data.total || '') + '</div>';
							wrap.appendChild(info);

							// Items list (lightweight)
							var items = data.items || [];
							var itemsWrap = document.createElement('div'); itemsWrap.className = 'myd-card-section';
							var listHtml = '<div style="margin-top:8px;font-weight:700;color:#333;">Itens</div><ul style="margin:8px 0 0 18px;color:#444;">';
							try{ for(var i=0;i<items.length;i++){ var it = items[i]; var qty = it.quantity || it.qty || 1; var name = it.name || it.title || it.product_name || JSON.stringify(it); listHtml += '<li>' + qty + '× ' + name + '</li>'; } }catch(e){}
							listHtml += '</ul>';
							itemsWrap.innerHTML = listHtml;
							wrap.appendChild(itemsWrap);

							// Actions stub: print / confirm (buttons will be hooked by existing handlers)
							var actions = document.createElement('div'); actions.className = 'myd-delivery-actions';
							actions.innerHTML = '<button class="fdm-btn-order-action" data-manage-order-id="'+id+'" id="fdm-confirm-order">Confirmar</button> <button class="fdm-btn-order-action" data-manage-order-id="'+id+'" id="fdm-indelivery-order">Em entrega</button>';
							wrap.appendChild(actions);



							container.appendChild(wrap);

							// ensure action visibility logic runs
							try{ setTimeout(function(){ document.querySelectorAll('.fdm-orders-items').forEach(function(it){ it.classList.remove('fdm-active'); }); var el = document.getElementById(id); if(el) el.classList.add('fdm-active'); } , 40); }catch(_){ }

						}catch(e){ console.error('[Orders Panel] renderOrderDetail error', e); }
					}

					function showLoading(){ try{ if(typeof showAjaxLoading==='function') showAjaxLoading(); }catch(_){} }
					function hideLoading(){ try{ if(typeof hideAjaxLoading==='function') hideAjaxLoading(); }catch(_){} }

					// Click handler: delegate for order items
					document.addEventListener('click', function(ev){
						try{
							var target = ev.target;
							var item = target.closest && target.closest('.fdm-orders-items');
							if(!item) return;
							var orderId = item.id || item.getAttribute('id');
							if(!orderId) return;
							// If detail already loaded and visible, toggle collapse via existing accordion behavior
							var existing = document.getElementById('content-' + orderId);
							if(existing){
								// let existing accordion code handle visibility; but ensure it's scrolled into view
								setTimeout(function(){ try{ existing.scrollIntoView({behavior:'smooth', block:'center'}); }catch(_){} }, 50);
								return;
							}

							// fetch details via AJAX
							var fd = new FormData(); fd.append('action','get_order_details'); fd.append('order_id', orderId); fd.append('nonce', nonce);
							showLoading();
							fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function(r){ return r.json(); }).then(function(json){ hideLoading(); if(json && json.success && json.data){ renderOrderDetail(json.data); } else { var msg = (json && json.message) ? json.message : 'Não foi possível carregar o pedido'; showErrorCard(msg); } }).catch(function(err){ hideLoading(); console.error('[Orders Panel] fetch order details failed', err); showErrorCard('Erro ao carregar pedido'); });
						}catch(e){ /* ignore */ }
					}, true);

				})();
				</script>
			</div>

			<!-- Tela inicial: bloco retangular quando nenhum detalhe estiver visível -->
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const actionFull = document.querySelector('.fdm-orders-list-action-full');
	const ordersLoop = document.querySelector('.fdm-orders-loop');
	if (!actionFull || !ordersLoop) return;

	function updateActionVisibility() {
		const active = document.querySelector('.fdm-orders-items.fdm-active');
		let visibleDetail = null;
		try {
			const details = document.querySelectorAll('.fdm-orders-full-items');
			for (const n of details) {
				const cs = window.getComputedStyle ? window.getComputedStyle(n) : null;
				const shown = cs ? cs.display !== 'none' : (n.style.display !== 'none');
				if (shown) { visibleDetail = n; break; }
			}
		} catch(_) {}

		if (active || visibleDetail) {
			// ensure visible
			actionFull.style.display = 'flex';
			let id = '';
			if (active) {
				id = active.getAttribute('id') || '';
			} else if (visibleDetail) {
				id = ((visibleDetail.id || '').replace('content-','')) || '';
			}
			document.querySelectorAll('.fdm-btn-order-action').forEach(function(btn){
				btn.setAttribute('data-manage-order-id', id);
			});

			// Move bar inside the visible detail pane
			try {
				var hostDetail = visibleDetail;
				if (!hostDetail && id) {
					hostDetail = document.getElementById('content-' + String(id));
				}
				if (hostDetail && actionFull.parentElement !== hostDetail) {
					// append as last child so it sits at bottom
					hostDetail.appendChild(actionFull);
				}
			} catch(_){ }

			// Show/hide specific buttons according to order status
			try {
				var btnConfirm = document.getElementById('fdm-confirm-order');
				var btnInDelivery = document.getElementById('fdm-indelivery-order');
				var btnFinished = document.getElementById('fdm-finished-order');
				var btnCancel = document.getElementById('fdm-cancel-order');
				// default: show all
				[btnConfirm, btnInDelivery, btnFinished, btnCancel].forEach(function(b){ if(b){ b.style.display = ''; }});
				var statusEl = id ? document.getElementById(id) : null;
				var st = statusEl ? (statusEl.getAttribute('data-order-status') || '').toLowerCase() : '';
				if (st === 'new') {
					if (btnConfirm) btnConfirm.style.display = 'flex';
					if (btnCancel) btnCancel.style.display = 'flex';
					if (btnInDelivery) btnInDelivery.style.display = 'none';
					if (btnFinished) btnFinished.style.display = 'none';
				} else if (st === 'in-delivery') {
					if (btnFinished) btnFinished.style.display = 'flex';
					if (btnConfirm) btnConfirm.style.display = 'none';
					if (btnInDelivery) btnInDelivery.style.display = 'none';
					if (btnCancel) btnCancel.style.display = 'none';
				}
			} catch(_){ /* ignore */ }
		} else {
			actionFull.style.display = 'none';
			document.querySelectorAll('.fdm-btn-order-action').forEach(function(btn){
				btn.setAttribute('data-manage-order-id', '');
			});
		}
	}

	// Initial state
	try { window.updateActionVisibility = updateActionVisibility; } catch(_){ }
	updateActionVisibility();

	// When an order item is clicked, existing handlers will toggle .fdm-active — call update after a short delay
	ordersLoop.addEventListener('click', function(){ setTimeout(updateActionVisibility, 50); });

	// Also observe class/style changes (in case other scripts toggle active or show details)
	try {
		const obs = new MutationObserver(function(mutations){
			let changed = false;
			for (const m of mutations) {
				if (m.type === 'attributes' && (m.attributeName === 'class' || m.attributeName === 'style')) { changed = true; break; }
				if (m.type === 'childList') { changed = true; break; }
			}
			if (changed) updateActionVisibility();
		});
		// observe all order items
		document.querySelectorAll('.fdm-orders-items').forEach(function(it){
			obs.observe(it, { attributes: true, attributeFilter: ['class'] });
		});
		// observe details host for style/class changes
		const fullHost = document.querySelector('.fdm-orders-full');
		if (fullHost) {
			const obs2 = new MutationObserver(function(){ setTimeout(updateActionVisibility, 20); });
			obs2.observe(fullHost, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });
		}
	} catch(e) { /* ignore in older browsers */ }
});
</script>

<script>
// Impressão: imprimir somente o conteúdo de #hide-prints referente ao pedido clicado
document.addEventListener('click', function(e){
	var btn = e.target.closest('.myd-print-btn');
	if (!btn) return;
	e.preventDefault();
	try {
		var id = btn.getAttribute('data-manage-order-id') || '';
		if (!id) {
			var m = (btn.id || '').match(/myd-print-(\d+)/);
			if (m) id = m[1];
		}
		if (!id) return;
		// On manual click: always perform a single print regardless of configured copies
		if (typeof window.printOrderSingle === 'function') {
			window.printOrderSingle(id).then(function(){
				console.log('[Orders Panel] printOrderSingle completed for order', id);
			}).catch(function(err){
				console.error('[Orders Panel] printOrderSingle failed for order ' + id, err);
			});
		} else if (typeof triggerAutoPrint === 'function') {
			// As a fallback, call triggerAutoPrint but ensure only a single copy is sent by temporarily overriding localStorage
			try {
				var prev = null;
				try { prev = localStorage.getItem('myd-print-copies'); } catch(_){ prev = null; }
				try { localStorage.setItem('myd-print-copies', '1'); } catch(_){ }
				try { triggerAutoPrint(id); console.log('[Orders Panel] triggerAutoPrint (forced single) called for order', id); } catch(err){ console.error(err); }
				// restore previous setting shortly after
				setTimeout(function(){ try { if (prev === null) localStorage.removeItem('myd-print-copies'); else localStorage.setItem('myd-print-copies', prev); } catch(_){} }, 500);
			} catch(err){ console.error('[Orders Panel] fallback triggerAutoPrint failed for order ' + id, err); }
		} else if (typeof window._myd_manualPrint === 'function') {
			// fallback to manual single-print flow
			window._myd_manualPrint(id).then(function(){
				console.log('[Orders Panel] manual print (single) completed for order', id);
			}).catch(function(err){ console.error('[Orders Panel] manual print failed for order ' + id, err); });
		} else {
			console.error('[Orders Panel] no available print function for order', id);
		}
	} catch(_) { /* noop */ }
}, true);
</script>

<script>
// Intercept cancel actions to require confirmation
document.addEventListener('DOMContentLoaded', function(){
	try {
		var modal = document.getElementById('myd-cancel-modal');
		if (!modal) return;
		var btnClose = document.getElementById('myd-cancel-modal-close');
		var btnConfirm = document.getElementById('myd-cancel-modal-confirm');
		var pendingTarget = null; // element to trigger after confirm

		function openModal(target){
			pendingTarget = target || null;
			// clear previous selections/inputs so modal always opens clean
			try {
				var radios = document.getElementsByName('myd-cancel-reason');
				for (var i=0;i<radios.length;i++){ radios[i].checked = false; }
				var wrap = document.getElementById('myd-cancel-reason-other-wrap');
				var otherTa = document.getElementById('myd-cancel-reason-other');
				if (wrap) wrap.style.display = 'none';
				if (otherTa) { otherTa.value = ''; otherTa.style.height = '40px'; }
				// Payment info / refund checkbox
				var paymentInfo = document.getElementById('myd-cancel-payment-info');
				var paymentText = document.getElementById('myd-cancel-payment-text');
				var refundCb = document.getElementById('myd-cancel-refund-checkbox');
				var paymentType = '';
				var paymentMethod = '';
				try {
					if (target && target.getAttribute) {
						paymentType = target.getAttribute('data-order-payment-type') || '';
						paymentMethod = target.getAttribute('data-order-payment-method') || '';
					}
				} catch(e){}
				if (paymentType === 'payment-integration') {
					if (paymentText) paymentText.textContent = 'Este pedido foi pago online via ' + (paymentMethod || '') + '!';
					if (paymentInfo) paymentInfo.style.display = 'block';
					if (refundCb) refundCb.checked = false;
				} else {
					if (paymentInfo) paymentInfo.style.display = 'none';
					if (refundCb) refundCb.checked = false;
				}
					// ensure confirm button reflects cleared state
					try { if (btnConfirm) { btnConfirm.disabled = true; } if (typeof updateCancelConfirmState === 'function') updateCancelConfirmState(); } catch(_e){}
			} catch(e){}
			modal.classList.remove('myd-hidden');
			modal.classList.add('show');
			modal.style.display = 'flex';
			modal.setAttribute('aria-hidden','false');
		}
		function closeModal(){
			modal.classList.remove('show');
			modal.classList.add('myd-hidden');
			modal.style.display = 'none';
			modal.setAttribute('aria-hidden','true');
			pendingTarget = null;
		}
		function proceed(){
			try {
				if (pendingTarget) {
					// temporarily disable interception and click original target
					pendingTarget.setAttribute('data-bypass-cancel-confirm','1');
					pendingTarget.click();
					pendingTarget.removeAttribute('data-bypass-cancel-confirm');
				}
			} catch(_) {}
		}

			// Do not close modal immediately; it will be closed when the AJAX request completes.


		// close events
		if (btnClose) btnClose.addEventListener('click', closeModal);
		try { modal.querySelector('.myd-modal-backdrop').addEventListener('click', closeModal); } catch(_){}
		if (btnConfirm) btnConfirm.addEventListener('click', proceed);

		// allow other scripts to close the cancel modal when async operations finish
		window.MydCloseCancelModal = function(){ try { closeModal(); } catch(_){} };

		// delegate clicks on cancel buttons
		document.addEventListener('click', function(e){
			var el = e.target && e.target.closest ? e.target.closest('.fdm-btn-order-action.myd-cancel-btn') : null;
			if (!el) return;
			if (el.getAttribute('data-bypass-cancel-confirm') === '1') return; // let it pass
			e.preventDefault(); e.stopImmediatePropagation();
			openModal(el);
		}, true);
	} catch(e) { /* noop */ }
});
</script>

<script>
// Back button: hide all order cards and full details, returning to the initial welcome screen
document.addEventListener('click', function(e){
	try {
		var clicked = null;
		if (e.target && e.target.closest) {
			clicked = e.target.closest('.fdm-btn-order-action-back') || e.target.closest('#fdm-back') || e.target.closest('.myd-progress-back') || e.target.closest('.myd-item-hide');
		}
		if (!clicked) return;

		// hide compact order items and full detail items
		document.querySelectorAll('.fdm-orders-full-items').forEach(function(n){ try { n.style.display = 'none'; } catch(_){} });
		// Fechar overlay de detalhe
		document.querySelectorAll('.fdm-orders-full-details').forEach(function(n){ try { n.style.display = 'none'; n.classList.remove('myd-detail-open'); } catch(_){} });
		// Remover active do card na lista
		document.querySelectorAll('.fdm-orders-items.fdm-active').forEach(function(n){ n.classList.remove('fdm-active'); });

		// Esconder painel WhatsApp se estiver visível
		var whatsappPanel = document.getElementById('myd-whatsapp-panel');
		if (whatsappPanel) whatsappPanel.style.display = 'none';

		// Voltar para a welcome cover e esconder a lista de pedidos
		var ordersList = document.getElementById('myd-orders-list-panel');
		if (ordersList) ordersList.classList.add('myd-list-hidden');

		// Atualizar sidebar active
		var menuBtn = document.querySelector('a[href="#myd-section-menu"]');
		var ordersBtn = document.getElementById('myd-sidebar-orders-btn');
		var waBtn = document.getElementById('myd-sidebar-whatsapp-btn');
		if(menuBtn) menuBtn.classList.add('active');
		if(ordersBtn) ordersBtn.classList.remove('active');
		if(waBtn) waBtn.classList.remove('active');

		// Try to trigger any existing empty-state toggle present in the page
		try { if (typeof toggleEmptyState === 'function') setTimeout(toggleEmptyState, 20); } catch(_){}
		try { if (typeof MydRefreshOrderCounts === 'function') setTimeout(MydRefreshOrderCounts, 20); } catch(_){}
	} catch(e) { /* silent */ }
}, true);
</script>

<script>
// Menu button: when user clicks the Menu (Home) in sidebar, show welcome and hide orders list
document.addEventListener('click', function(e){
	try {
		var clicked = null;
		if (e.target && e.target.closest) {
			clicked = e.target.closest('a[href="#myd-section-menu"], .myd-sidebar-item[title="Menu"], .myd-sidebar-item[data-section="done"]');
		}
		if (!clicked) return;

		// Esconder painel WhatsApp se estiver visível
		var whatsappPanel = document.getElementById('myd-whatsapp-panel');
		if (whatsappPanel) whatsappPanel.style.display = 'none';

		// Mostrar a welcome cover e esconder a lista de pedidos
		var ordersList = document.getElementById('myd-orders-list-panel');
		if (ordersList) ordersList.classList.add('myd-list-hidden');

		// Atualizar sidebar active
		var menuBtn = document.querySelector('a[href="#myd-section-menu"]');
		var ordersBtn = document.getElementById('myd-sidebar-orders-btn');
		var waBtn = document.getElementById('myd-sidebar-whatsapp-btn');
		if(menuBtn) menuBtn.classList.add('active');
		if(ordersBtn) ordersBtn.classList.remove('active');
		if(waBtn) waBtn.classList.remove('active');

		// hide compact order cards and injected full-details
		document.querySelectorAll('.fdm-orders-full-items').forEach(function(n){ try { n.style.display = 'none'; } catch(_){} });
		// Fechar overlay de detalhe
		document.querySelectorAll('.fdm-orders-full-details').forEach(function(n){ try { n.style.display = 'none'; n.classList.remove('myd-detail-open'); } catch(_){} });
		// Remover active do card na lista
		document.querySelectorAll('.fdm-orders-items.fdm-active').forEach(function(n){ n.classList.remove('fdm-active'); });
		// refresh empty state / counts if helpers exist
		try { if (typeof toggleEmptyState === 'function') setTimeout(toggleEmptyState, 20); } catch(_){}
		try { if (typeof MydRefreshOrderCounts === 'function') setTimeout(MydRefreshOrderCounts, 20); } catch(_){}
	} catch(e) { /* silent */ }
}, true);
</script>

<script>
// Orders button: when user clicks the Pedidos button in sidebar, show orders list and hide welcome
document.addEventListener('click', function(e){
	try {
		var clicked = null;
		if (e.target && e.target.closest) {
			clicked = e.target.closest('#myd-sidebar-orders-btn, a[href="#myd-section-orders"]');
		}
		if (!clicked) return;
		e.preventDefault();

		// Esconder painel WhatsApp se estiver visível
		var whatsappPanel = document.getElementById('myd-whatsapp-panel');
		if (whatsappPanel) whatsappPanel.style.display = 'none';

		// Mostrar a lista de pedidos e esconder a welcome cover
		var ordersList = document.getElementById('myd-orders-list-panel');
		if (ordersList) ordersList.classList.remove('myd-list-hidden');

		// Atualizar sidebar active
		var menuBtn = document.querySelector('a[href="#myd-section-menu"]');
		var ordersBtn = document.getElementById('myd-sidebar-orders-btn');
		var waBtn = document.getElementById('myd-sidebar-whatsapp-btn');
		if(menuBtn) menuBtn.classList.remove('active');
		if(ordersBtn) ordersBtn.classList.add('active');
		if(waBtn) waBtn.classList.remove('active');

		// hide compact order cards and injected full-details
		document.querySelectorAll('.fdm-orders-full-items').forEach(function(n){ try { n.style.display = 'none'; } catch(_){} });
		// refresh empty state / counts if helpers exist
		try { if (typeof toggleEmptyState === 'function') setTimeout(toggleEmptyState, 20); } catch(_){}
		try { if (typeof MydRefreshOrderCounts === 'function') setTimeout(MydRefreshOrderCounts, 20); } catch(_){}
	} catch(e) { /* silent */ }
}, true);
</script>

<script>
// WhatsApp button: when user clicks the WhatsApp button in sidebar, show WhatsApp Web and hide orders/dashboard
if (navigator.userAgent.includes('Electron')) {
	document.addEventListener('click', function(e){
		try {
			var clicked = null;
			if (e.target && e.target.closest) {
				clicked = e.target.closest('#myd-sidebar-whatsapp-btn, a[href="#myd-section-whatsapp"]');
			}
			if (!clicked) return;
			e.preventDefault();

			// Esconder dashboard e lista de pedidos
			var dashboardPanel = document.getElementById('dashboard-panel');
			var ordersList = document.getElementById('myd-orders-list-panel');
			if (dashboardPanel) dashboardPanel.style.display = 'none';
			if (ordersList) ordersList.classList.add('myd-list-hidden');

			// Fechar overlays de detalhe
			document.querySelectorAll('.fdm-orders-full-items').forEach(function(n){ try { n.style.display = 'none'; } catch(_){} });
			document.querySelectorAll('.fdm-orders-full-details').forEach(function(n){ try { n.style.display = 'none'; n.classList.remove('myd-detail-open'); } catch(_){} });
			document.querySelectorAll('.fdm-orders-items.fdm-active').forEach(function(n){ n.classList.remove('fdm-active'); });

			// Mostrar painel WhatsApp e lazy-load webview
			var whatsappPanel = document.getElementById('myd-whatsapp-panel');
			var webview = document.getElementById('myd-whatsapp-webview');
			if (whatsappPanel) {
				whatsappPanel.style.display = 'block';
				// Lazy-load: só carrega na primeira vez
				if (webview && (!webview.getAttribute('src') || webview.getAttribute('src') === 'about:blank')) {
					webview.src = 'https://web.whatsapp.com';
				}
			}

			// Atualizar sidebar active
			var menuBtn = document.querySelector('a[href="#myd-section-menu"]');
			var ordersBtn = document.getElementById('myd-sidebar-orders-btn');
			var waBtn = document.getElementById('myd-sidebar-whatsapp-btn');
			if(menuBtn) menuBtn.classList.remove('active');
			if(ordersBtn) ordersBtn.classList.remove('active');
			if(waBtn) waBtn.classList.add('active');
		} catch(e) { /* silent */ }
	}, true);
}
</script>

<script>
// Carregamento sob demanda do detalhe: se faltar o #content-<id>, busca e injeta
document.addEventListener('DOMContentLoaded', function(){
	try {
		var loopEl = document.querySelector('.fdm-orders-loop');
		if (!loopEl) return;

			// helper para rolar sem "puxar" a tela para baixo na primeira abertura
			function smoothScrollIntoView(el){
				if (!el) return;
				try {
					var adminBar = document.getElementById('wpadminbar');
					var offset = adminBar ? adminBar.offsetHeight : 0;
					var rect = el.getBoundingClientRect();
					var top = rect.top + window.pageYOffset - (offset + 8);
					window.scrollTo({ top: top, behavior: 'smooth' });
				} catch(_) {
					try { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch(__) {}
				}
			}

			function fetchAndAttachFull(orderId, thenShow){
			function attach(fullHtml){
				if (!fullHtml) return false;
				var host = document.querySelector('.fdm-orders-full');
				if (!host) return false;
				var parsed = null;
				if (window.DOMParser && typeof DOMParser === 'function') {
					try { parsed = new DOMParser().parseFromString(fullHtml, 'text/html'); }
					catch(e){ parsed = document.createElement('div'); parsed.innerHTML = fullHtml; }
				} else { parsed = document.createElement('div'); parsed.innerHTML = fullHtml; }
				var sel = '#content-' + CSS.escape(String(orderId));
				var fullEl = null;
				try { fullEl = parsed.querySelector(sel); } catch(_){ fullEl = null; }
				if (!fullEl) {
					var candidates = parsed.querySelectorAll ? parsed.querySelectorAll('.fdm-orders-full-items') : [];
					for (var i=0;i<candidates.length;i++){ if (String(candidates[i].id) === ('content-' + String(orderId))) { fullEl = candidates[i]; break; } }
				}
				if (!fullEl) return false;
				try { var existing = host.querySelector(sel); if (existing && existing.parentNode) existing.parentNode.removeChild(existing); } catch(_){ }
				try { fullEl.style.display = 'none'; } catch(_){ }
				try { host.appendChild(fullEl); } catch(e){ try { host.insertAdjacentElement('beforeend', fullEl); } catch(_){ } }
						if (thenShow) {
							try {
								// esconder outros e garantir exibição do painel overlay
								document.querySelectorAll('.fdm-orders-full-items').forEach(function(n){ n.style.display = 'none'; });
								fullEl.style.display = 'flex';
								var cont = document.querySelector('.fdm-orders-full-details');
								if (cont) { cont.style.display = 'flex'; cont.classList.add('myd-detail-open'); }
								smoothScrollIntoView(fullEl);
							} catch(_) { }
						}
				return true;
			}

			// Tenta admin-ajax primeiro
			try {
				if (window.order_ajax_object && window.order_ajax_object.ajax_url && window.order_ajax_object.nonce){
					fetch(window.order_ajax_object.ajax_url, {
						method: 'POST', credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Cache-Control': 'no-cache' },
						body: 'action=update_orders&nonce=' + encodeURIComponent(window.order_ajax_object.nonce)
					}).then(function(r){ return r.json(); }).then(function(resp){
						if (resp && resp.full) attach(resp.full);
					}).catch(function(){});
					return;
				}
			} catch(_){ }

			// Fallback REST
			var restUrl = (window.location && window.location.origin ? window.location.origin : '') + '/wp-json/my-delivery/v1/orders?oid=' + encodeURIComponent(String(orderId));
			fetch(restUrl, { credentials: 'same-origin' }).then(function(r){ if(!r.ok) throw 0; return r.json(); }).then(function(resp){
				if (resp && resp.full) attach(resp.full);
			}).catch(function(){});
		}

		loopEl.addEventListener('click', function(e){
			var item = e.target && e.target.closest ? e.target.closest('.fdm-orders-items') : null;
			if (!item) return;
				try { item.setAttribute('data-order-clicked','true'); } catch(_){}
			// esperar o handler padrão marcar ativo e tentar mostrar
			setTimeout(function(){
				var id = item.getAttribute('id');
				if (!id) return;
				var sel = '#content-' + CSS.escape(String(id));
					var fullEl = document.querySelector(sel);
					if (!fullEl) {
						// não existe: buscar, injetar e mostrar
						fetchAndAttachFull(id, true);
					} else {
						// existe: garantir exibição
						try {
							document.querySelectorAll('.fdm-orders-full-items').forEach(function(n){ n.style.display = 'none'; });
							fullEl.style.display = 'flex';
							var cont = document.querySelector('.fdm-orders-full-details');
							if (cont) { cont.style.display = 'flex'; cont.classList.add('myd-detail-open'); }
							smoothScrollIntoView(fullEl);
						} catch(_) {}
					}
			}, 30);
		});

			// Se o detalhe for inserido após o clique, exibir automaticamente
			var fullHost = document.querySelector('.fdm-orders-full');
			if (fullHost && window.MutationObserver) {
				var obs = new MutationObserver(function(muts){
					muts.forEach(function(m){
						if (!m.addedNodes || !m.addedNodes.length) return;
						Array.prototype.forEach.call(m.addedNodes, function(node){
							if (!(node && node.nodeType === 1)) return;
							if (!node.classList || !node.classList.contains('fdm-orders-full-items')) return;
							var id = (node.id || '').replace('content-','');
							if (!id) return;
							var item = document.getElementById(id);
							if (item && (item.getAttribute('data-order-clicked') === 'true' || item.classList.contains('fdm-active'))) {
								try {
									document.querySelectorAll('.fdm-orders-full-items').forEach(function(n){ if(n!==node) n.style.display = 'none'; });
									node.style.display = 'flex';
									var cont = document.querySelector('.fdm-orders-full-details');
									if (cont) { cont.style.display = 'flex'; cont.classList.add('myd-detail-open'); }
									try { smoothScrollIntoView(node); } catch(_) {}
								} catch(_) {}
							}
						});
					});
				});
				obs.observe(fullHost, { childList: true });
			}
	} catch(e) { /* noop */ }
});
</script>



<script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
	window.__PANEL_SCRIPT_LOADED = true;
	var pushUrl = '<?php echo esc_js( get_option( 'myd_push_server_url', '' ) ); ?>';
	console.log('[Orders Panel][DEBUG] pushUrl value:', pushUrl || '(empty)');
	if (!pushUrl) { console.warn('[Orders Panel][DEBUG] pushUrl vazio - configurar em settings'); return; }
	if (typeof io === 'undefined') { console.error('[Orders Panel][DEBUG] socket.io (io) não definido - script CDN carregou?'); return; }

	// Audio: implementação controlada — toca `trim.mp3` em loop quando há pedidos pendentes
	// e só para quando todos os pedidos pendentes forem removidos via eventos do websocket.
	var MydAudio = (function(){
		var audio = null;
		var isLooping = false;
		var STORAGE_KEY = 'myd_audio_unlocked_v1';

		function init(){
			if(!audio){
				try { audio = new Audio('/wp-content/plugins/sistema-delivery-franguxo/assets/songs/trim.mp3'); }
				catch(e){ console.warn('[Orders Panel] falha ao criar Audio', e); audio = null; }
				if (audio){ audio.preload = 'auto'; audio.volume = 1.0; audio.loop = true; }
			}
		}

		function startLoop(){
			try{
				init(); if(!audio) return;
				if (isLooping) return;
				audio.loop = true;
				isLooping = true;
				try { audio.currentTime = 0; } catch(_){ }
				var p = null;
				try { p = audio.play(); } catch(err) { console.warn('[Orders Panel] audio.play() threw', err); }
				if (p && p.catch) {
					p.catch(function(err){ console.warn('[Orders Panel] áudio bloqueado ou falhou ao tocar', err); });
				}
			} catch(e){ console.warn('[Orders Panel] erro ao iniciar loop de áudio', e); }
		}

		function stop(){
			try{
				if(!audio) return;
				audio.loop = false;
				isLooping = false;
				audio.pause();
				try { audio.currentTime = 0; } catch(_){ }
			} catch(e){ /* noop */ }
		}

		function markUnlocked(){ try { localStorage.setItem(STORAGE_KEY, String(Date.now())); } catch(_){ } }

		function isUnlocked(){ try { return !!localStorage.getItem(STORAGE_KEY); } catch(_){ return false; } }

		function tryUnlockViaAudioContext(done){
			try {
				var AudioCtx = window.AudioContext || window.webkitAudioContext;
				if (AudioCtx) {
					var ctx = window.__MYD_UNLOCK_AUDIO_CTX;
					if (!ctx) {
						try { ctx = new AudioCtx(); window.__MYD_UNLOCK_AUDIO_CTX = ctx; }
						catch(e){ ctx = null; }
					}
					if (ctx && typeof ctx.resume === 'function') {
						ctx.resume().then(function(){ markUnlocked(); if (done) done(true); }).catch(function(){ if (done) done(false); });
						return;
					}
				}
			} catch(e) { /* ignore */ }
			if (done) done(false);
		}

		function tryUnlockViaPlayPause(done){
			try {
				init(); if(!audio) { if(done) done(true); return; }
				var p = null;
				try { p = audio.play(); } catch(e){ p = null; }
				if (p && p.then) {
					p.then(function(){ try { audio.pause(); audio.currentTime = 0; } catch(_){ } markUnlocked(); if(done) done(true); })
					.catch(function(){ markUnlocked(); if(done) done(false); });
				} else {
					try { audio.pause(); audio.currentTime = 0; } catch(_){ }
					markUnlocked(); if(done) done(true);
				}
			} catch(e){ markUnlocked(); if(done) done(false); }
		}

		function unlock(){
			if (isUnlocked()) return; // already
			// First try AudioContext, then fallback to play/pause
			tryUnlockViaAudioContext(function(ok){ if (ok) return; tryUnlockViaPlayPause(function(){ /* noop */ }); });
			// ensure we attempt again on first user gesture if blocked
			function onGesture(){ try { tryUnlockViaPlayPause(function(){ /* noop */ }); } catch(_){} finally { removeListeners(); } }
			function addListeners(){ try { ['click','keydown','touchstart'].forEach(function(ev){ document.addEventListener(ev, onGesture, true); }); } catch(e){} }
			function removeListeners(){ try { ['click','keydown','touchstart'].forEach(function(ev){ document.removeEventListener(ev, onGesture, true); }); } catch(e){} }
			addListeners();
		}

		// auto-init unlock attempt once on load
		try { if (!isUnlocked()) { unlock(); } } catch(_){ }

		return { startLoop: startLoop, stop: stop, unlock: unlock };
	})();

// Fallback global manual print function (used if triggerAutoPrint isn't available yet)
window._myd_manualPrint = function(orderId){
	if (!orderId) {
		console.warn('[Orders Panel] manual print skipped: missing order id');
		return Promise.reject(new Error('missing_order_id'));
	}
	if (typeof fetch !== 'function') {
		console.error('[Orders Panel] manual print requires fetch API');
		return Promise.reject(new Error('fetch_not_available'));
	}
	try {
		var encodedBody = 'action=get_order_print_data&order_id=' + encodeURIComponent(orderId);
		return fetch('/wp-admin/admin-ajax.php', {
			method: 'POST',
			body: encodedBody,
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			credentials: 'same-origin'
		}).then(function(r){
			if(!r.ok) throw new Error('print_data_fetch_failed_status_' + r.status);
			return r.json();
		}).then(function(resp){
			if(resp && resp.success && resp.data){
				var orderData = resp.data;
				var payload = { orderData: orderData, escpos: true };
				try { var storedPrinter = localStorage.getItem('myd-default-printer'); if (storedPrinter) payload.printer = storedPrinter; } catch(_){ }
				try { var storedCopies = localStorage.getItem('myd-print-copies'); if (storedCopies) payload.copies = storedCopies; } catch(_){ }
				var body = null;
				try { body = JSON.stringify(payload); } catch(err){ return Promise.reject(err); }
				// Determine copies
				var copies = 1;
				try { var sc = localStorage.getItem('myd-print-copies'); if (sc) copies = parseInt(String(sc), 10) || 1; } catch(_) {}
				if (copies < 1) copies = 1;
				var MAX_COPIES = 10; if (copies > MAX_COPIES) copies = MAX_COPIES;

				function sendLocalCopy(n){
					if (n > copies) return Promise.resolve();
					return fetch('http://127.0.0.1:3420/print', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body })
						.catch(function(){
							return fetch('http://localhost:3420/print', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body });
						}).then(function(res){
							if (!res || !res.ok) throw new Error('local_print_failed');
							console.log('[Orders Panel] manual local print acknowledged for order ' + orderId + ' (copy ' + n + ')');
							return new Promise(function(resolve){ setTimeout(resolve, 180); }).then(function(){ return sendLocalCopy(n+1); });
						}).catch(function(err){
							console.error('[Orders Panel] manual local print failed for copy ' + n + ' for order ' + orderId, err);
							// continue with next copies
							return sendLocalCopy(n+1);
						});
				}

				return sendLocalCopy(1);
			}
			throw new Error('missing_order_data_for_print');
		}).catch(function(err){
			console.error('[Orders Panel] manual print flow failed for order ' + orderId, err);
			throw err;
		});
	} catch(err) {
		console.error('[Orders Panel] unexpected manual print error for order ' + orderId, err);
		return Promise.reject(err);
	}
};

// Manual single-print function: always send exactly one request to local print server
window.printOrderSingle = function(orderId){
	
	if (!orderId) {
		console.warn('[Orders Panel] printOrderSingle skipped: missing order id');
		return Promise.reject(new Error('missing_order_id'));
	}
	if (typeof fetch !== 'function') {
		console.error('[Orders Panel] printOrderSingle requires fetch API');
		return Promise.reject(new Error('fetch_not_available'));
	}
	try {
		var encodedBody = 'action=get_order_print_data&order_id=' + encodeURIComponent(orderId);
		return fetch('/wp-admin/admin-ajax.php', {
			method: 'POST',
			body: encodedBody,
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			credentials: 'same-origin'
		}).then(function(r){
			if(!r.ok) throw new Error('print_data_fetch_failed_status_' + r.status);
			return r.json();
		}).then(function(resp){
			
			if(resp && resp.success && resp.data){
				var orderData = resp.data;
				var payload = { orderData: orderData, escpos: true };
				try { var storedPrinter = localStorage.getItem('myd-default-printer'); if (storedPrinter) payload.printer = storedPrinter; } catch(_){ }
				// single copy
				payload.copies = 1;
				var body = null;
				try { body = JSON.stringify(payload); } catch(err){ return Promise.reject(err); }
				return fetch('http://127.0.0.1:3420/print', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body })
					.catch(function(){
						return fetch('http://localhost:3420/print', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body });
					}).then(function(res){
						if (!res || !res.ok) throw new Error('local_print_failed');
						console.log('[Orders Panel] printOrderSingle acknowledged for order', orderId);
						return res;
					});
			}
			throw new Error('missing_order_data_for_print');
		}).catch(function(err){
			console.error('[Orders Panel] printOrderSingle flow failed for order ' + orderId, err);
			throw err;
		});
	} catch(err) {
		console.error('[Orders Panel] unexpected printOrderSingle error for order ' + orderId, err);
		return Promise.reject(err);
	}
};

		// Áudio de evento único: som de confirmação (check)
		var MydSingleSound = (function(){
			var checkAudio = null;
			try { checkAudio = new Audio('<?php echo defined('MYD_PLUGN_URL') ? esc_url(MYD_PLUGN_URL . 'assets/songs/check.mp3') : plugins_url('assets/songs/check.mp3', dirname(__FILE__, 3) . '/myd-delivery-pro.php'); ?>'); } catch(e){}
			function playFinished(){ 
				try { if (checkAudio) { checkAudio.currentTime = 0; checkAudio.play().catch(function(){}); } } catch(e){ console.warn('[MydSingleSound] erro ao tocar check.mp3', e); }
			}
			return { playFinished: playFinished };
		})();
		window.MydSingleSound = MydSingleSound; // expõe globalmente para o socket handler

	// Gerencia pedidos pendentes para tocar/pausar som conforme houver novos pedidos
	// Nota: separar remoção disparada por websocket (remover e possivelmente parar som)
	// de remoção local (por exemplo ações do UI). Assim, somente eventos vindos
	// do socket irão interromper a reprodução.
	var MydAlert = (function(){
		var pending = new Set();
		function add(id){ try { pending.add(String(id)); MydAudio.startLoop(); } catch(_){ } }
		// removeLocal: apenas remove do conjunto de pendentes mas NÃO para o som
		function removeLocal(id){ try { pending.delete(String(id)); } catch(_){ } }
		// removeFromSocket: chamada quando o websocket confirma que o pedido mudou
		// para um estado terminal (confirmed/canceled/done) — então podemos
		// remover e, se não houver mais pendentes, parar o áudio.
		function removeFromSocket(id){ try { pending.delete(String(id)); if (pending.size === 0) MydAudio.stop(); } catch(_){ } }
		function count(){ try { return pending.size; } catch(_){ return 0; } }
		return { add: add, removeLocal: removeLocal, removeFromSocket: removeFromSocket, count: count };
	})();

	function connectSocketWithToken(token){
		try {
			// Guard: desconectar socket anterior para evitar listeners duplicados
			try {
				if (window.mydPushSocket && typeof window.mydPushSocket.disconnect === 'function') {
					window.mydPushSocket.disconnect();
					window.mydPushSocket = null;
					console.log('[Orders Panel][DEBUG] socket anterior desconectado antes de reconectar');
				}
			} catch(_) { }
			var socket = io(pushUrl, { auth: { token: token } });
			// expose socket so other code can emit/listen if needed
			try { window.mydPushSocket = socket; } catch(_){ }
			// Sinaliza ao frontend.min.js (fallback) que o panel.php assumiu o socket
			// assim o fallback não cria uma segunda conexão paralela com listeners duplicados
			try { window._mydPanelSocketRegistered = true; } catch(_){ }
			console.log('[Orders Panel][DEBUG] tentando conectar socket para', pushUrl);

			// helpers to place orders in correct section and update counts
			function extractStatusFromPayload(p){
				try {
					if (!p) return '';
					// common direct fields
					if (typeof p.status === 'string' && p.status.trim() !== '') return p.status;
					if (typeof p.order_status === 'string' && p.order_status.trim() !== '') return p.order_status;
					if (p.order && typeof p.order.status === 'string' && p.order.status.trim() !== '') return p.order.status;
					if (p.data && typeof p.data.status === 'string' && p.data.status.trim() !== '') return p.data.status;
					// sometimes payload is nested: payload.data.order.status
					if (p.data && p.data.order && typeof p.data.order.status === 'string' && p.data.order.status.trim() !== '') return p.data.order.status;
					// fallback to any string-like field
					for (var k in p) {
						if (!p.hasOwnProperty(k)) continue;
						try { if (typeof p[k] === 'string' && /status/i.test(k)) return p[k]; } catch(_){}
					}
				} catch(e) { /* noop */ }
				return '';
			}

			function normalizeStatus(s){
				if(!s) return 'new';
				s = String(s).toLowerCase().trim();
				// Normalize separators: underscores and spaces -> dash
				s = s.replace(/[_\s]+/g, '-');
				// handle some incoming variants
				if (['created','received','pending'].indexOf(s) !== -1) return 'new';
				if (['processing','preparing','accepted','accept','accepted-order','preparando','confirmado','ready-for-preparation','ready'].indexOf(s) !== -1) return 'confirmed';
				// Normalize different ways of expressing in-delivery
				if (['in-delivery','indelivery','in-delivery','in-delivery','out-for-delivery','out-for-shipping','despachado'].indexOf(s) !== -1 || s === 'in-delivery') return 'in-delivery';
				// Map 'finished' or 'completed' to 'done'
				if (s === 'finished' || s === 'completed') return 'done';
				return s;
			}

			function sendToLocalPrintServer(payload){
				if (!payload) return Promise.reject(new Error('missing_payload'));
				if (typeof fetch !== 'function') {
					return Promise.reject(new Error('fetch_not_available'));
				}
				var body = null;
				try { body = JSON.stringify(payload); } catch(err){ return Promise.reject(err); }
				var candidates = [];
				try {
					if (typeof window !== 'undefined' && window.MYD_LOCAL_PRINT_ENDPOINT) {
						candidates.push(String(window.MYD_LOCAL_PRINT_ENDPOINT));
					}
				} catch(_){ }
				candidates.push('http://127.0.0.1:3420/print');
				candidates.push('http://localhost:3420/print');
				var index = 0;
				function attempt(){
					if (index >= candidates.length) {
						return Promise.reject(new Error('all_local_print_endpoints_failed'));
					}
					var url = candidates[index++];
					return fetch(url, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: body
					}).catch(function(err){
						console.warn('[Orders Panel] failed sending to local print server via ' + url, err);
						return attempt();
					});
				}
				return attempt();
			}

			function triggerAutoPrint(orderId){
				if (!orderId) {
					console.warn('[Orders Panel] auto-print skipped: missing order id');
					return;
				}

				// Expor globalmente para que handlers fora deste escopo possam chamar
				try { window.triggerAutoPrint = triggerAutoPrint; } catch(_) { /* noop */ }
				if (typeof fetch !== 'function') {
					console.error('[Orders Panel] auto-print requires fetch API');
					return;
				}
				console.log('[Orders Panel] auto-print triggered for order', orderId);
				try {
					var encodedBody = 'action=get_order_print_data&order_id=' + encodeURIComponent(orderId);
					fetch('/wp-admin/admin-ajax.php', {
						method: 'POST',
						body: encodedBody,
						headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
						credentials: 'same-origin'
					}).then(function(r){
						if(!r.ok){ throw new Error('print_data_fetch_failed_status_' + r.status); }
						return r.json();
					}).then(function(resp){
						if(resp && resp.success && resp.data){
							var orderData = resp.data;
							console.log('[Orders Panel] orderData fetched for auto-print:', orderData);
							var payload = { orderData: orderData, escpos: true };
							try {
								var storedPrinter = localStorage.getItem('myd-default-printer');
								if (storedPrinter) payload.printer = storedPrinter;
							} catch(_){ }
							try {
								var storedCopies = localStorage.getItem('myd-print-copies');
								if (storedCopies) payload.copies = storedCopies;
							} catch(_){ }
							// Determine number of copies (ensure integer >=1)
							var copies = 1;
							try {
								var sc = localStorage.getItem('myd-print-copies');
								if (sc) { copies = parseInt(String(sc), 10) || 1; }
							} catch(_) { }
							if (copies < 1) copies = 1;
							// Cap copies to avoid accidental huge loops
							var MAX_COPIES = 10;
							if (copies > MAX_COPIES) copies = MAX_COPIES;

							function sendCopyAttempt(n){
								if (n > copies) return Promise.resolve();
								return sendToLocalPrintServer(payload).then(function(res){
									console.log('[Orders Panel] local print server acknowledged copy ' + n + ' for order', orderId);
									// small delay between copies
									return new Promise(function(resolve){ setTimeout(resolve, 180); }).then(function(){ return sendCopyAttempt(n+1); });
								}).catch(function(err){
									console.error('[Orders Panel] failed sending copy ' + n + ' to local print server', err);
									// continue attempting next copies to maximize chance
									return sendCopyAttempt(n+1);
								});
							}

							return sendCopyAttempt(1).then(function(){
								console.log('[Orders Panel] completed print attempts for order', orderId);
							}).catch(function(err){
								console.error('[Orders Panel] error during multi-copy print attempts for order ' + orderId, err);
							});
						}
						throw new Error('missing_order_data_for_print');
					}).catch(function(err){
						console.error('[Orders Panel] auto-print flow failed for order ' + orderId, err);
					});
				} catch(err) {
					console.error('[Orders Panel] unexpected auto-print error for order ' + orderId, err);
				}
			}

			function getTargetContainerByStatus(status){
				status = normalizeStatus(status);
				var container = null;
				// map individual statuses to their proper accordion bodies
				if (status === 'new') {
					container = document.querySelector('#myd-section-new .myd-orders-accordion-body');
				} else if (status === 'in-delivery') {
					// 'Em entrega' has its own section
					container = document.querySelector('#myd-section-in-delivery .myd-orders-accordion-body');
				} else if (['confirmed','waiting'].indexOf(status) !== -1) {
					container = document.querySelector('#myd-section-production .myd-orders-accordion-body');
				} else {
					container = document.querySelector('#myd-section-done .myd-orders-accordion-body');
				}
				if (!container) container = document.querySelector('.fdm-orders-loop');
				return container;
			}

			function placeOrder(container, el, toTop){
				try {
					if (toTop) {
						// find first order item to insert before; otherwise prepend normally
						var firstOrder = container.querySelector ? container.querySelector('.fdm-orders-items') : null;
						if (firstOrder && firstOrder.parentNode === container) {
							container.insertBefore(el, firstOrder);
						} else if (container.firstElementChild) {
							container.insertBefore(el, container.firstElementChild);
						} else {
							container.appendChild(el);
						}
					} else {
						container.appendChild(el);
					}
				} catch(e) {
					try { container.insertAdjacentElement(toTop ? 'afterbegin' : 'beforeend', el); } catch(_){}
				}
				if (window.MydRefreshOrderCounts && typeof window.MydRefreshOrderCounts === 'function') { try { window.MydRefreshOrderCounts(); } catch(_){} }
			}

			function highlightJustInserted(id){
				try { var inserted = document.querySelector('.fdm-orders-items#' + CSS.escape(String(id))); if(inserted){ inserted.classList.add('fdm-new-arrival'); setTimeout(function(){ try{ inserted.classList.remove('fdm-new-arrival'); }catch(_){ } }, 7000); } } catch(_){ }
			}

			function fetchAndInsertOrderCard(orderId){
				function insertOrReplaceFullDetails(orderId, fullHtml){
					try{
						if (!fullHtml) return;
						var host = document.querySelector('.fdm-orders-full');
						if (!host) return;
						var parsed = null;
						if (window.DOMParser && typeof DOMParser === 'function') {
							try { parsed = new DOMParser().parseFromString(fullHtml, 'text/html'); }
							catch(e){ parsed = document.createElement('div'); parsed.innerHTML = fullHtml; }
						} else {
							parsed = document.createElement('div'); parsed.innerHTML = fullHtml;
						}
						var sel = '#content-' + CSS.escape(String(orderId));
						var fullEl = null;
						try { fullEl = parsed.querySelector(sel); } catch(_){ fullEl = null; }
						if (!fullEl) {
							var candidates = parsed.querySelectorAll ? parsed.querySelectorAll('.fdm-orders-full-items') : [];
							for (var i=0;i<candidates.length;i++){ if (String(candidates[i].id) === ('content-' + String(orderId))) { fullEl = candidates[i]; break; } }
						}
						if (!fullEl) return;
						// remove any existing details for this order
						try{
							var existing = host.querySelector(sel);
							if (existing && existing.parentNode) existing.parentNode.removeChild(existing);
						}catch(_){ }
						// keep it hidden until user clicks the list item
						try { fullEl.style.display = 'none'; } catch(_){ }
						try { host.appendChild(fullEl); } catch(e){ try { host.insertAdjacentElement('beforeend', fullEl); } catch(_){ } }
					} catch(e){ /* noop */ }
				}
				// Prefer using localized admin-ajax endpoint when available to get full HTML loops
				try {
					if (window.order_ajax_object && window.order_ajax_object.ajax_url && window.order_ajax_object.nonce){
						return fetch(window.order_ajax_object.ajax_url, {
							method: 'POST', credentials: 'same-origin',
							headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Cache-Control': 'no-cache' },
							body: 'action=update_orders&nonce=' + encodeURIComponent(window.order_ajax_object.nonce)
						}).then(function(r){ return r.json(); }).then(function(resp){
							var loopHtml = resp && (resp.loop || resp.t) ? (resp.loop || resp.t) : '';
							if (!loopHtml) return;
							var dp = null;
							if (window.DOMParser && typeof DOMParser === 'function') { try { dp = new DOMParser().parseFromString(loopHtml, 'text/html'); } catch(e) { dp = document.createElement('div'); dp.innerHTML = loopHtml; } }
							else { dp = document.createElement('div'); dp.innerHTML = loopHtml; }
							var found = null;
							try { found = dp.querySelector('.fdm-orders-items#' + CSS.escape(String(orderId))); } catch(e){ found = null; }
							if (!found) {
								var all = dp.querySelectorAll ? dp.querySelectorAll('.fdm-orders-items') : [];
								for (var i=0;i<all.length;i++){ if (String(all[i].id) === String(orderId)) { found = all[i]; break; } }
							}
							if (found){
								var st = found.getAttribute ? found.getAttribute('data-order-status') : null;
								var container2 = getTargetContainerByStatus(st);
								try {
									// Remove any existing list item with same id to avoid duplicates
									var existingItem = document.querySelector('.fdm-orders-items#' + CSS.escape(String(orderId)));
									if (existingItem && existingItem.parentNode) {
										existingItem.parentNode.removeChild(existingItem);
									}
								} catch(_){ }
								placeOrder(container2, found, true);
								highlightJustInserted(orderId);
								// also inject/replace full details if present in the response
								try { insertOrReplaceFullDetails(orderId, resp && resp.full ? resp.full : ''); } catch(_){ }
							}
						});
					}
				} catch(e) { console.warn('[Orders Panel] admin-ajax update_orders failed path', e); }

				// Fallback: try REST endpoint (may not include HTML in all setups)
				var restUrl = (window.location && window.location.origin ? window.location.origin : '') + '/wp-json/my-delivery/v1/orders?oid=' + encodeURIComponent(String(orderId));
				return fetch(restUrl, { credentials: 'same-origin' }).then(function(r){ if(!r.ok) throw new Error('fetch_failed'); return r.json(); }).then(function(resp){
					var loopHtml = resp && (resp.loop || resp.t) ? (resp.loop || resp.t) : '';
					if(!loopHtml && !(resp && resp.full)) return; // some REST impls only return status; nothing to insert
					var parser = null;
					if (window.DOMParser && typeof DOMParser === 'function') {
						try { parser = new DOMParser().parseFromString(loopHtml || resp.full || '', 'text/html'); } catch(e) { parser = document.createElement('div'); parser.innerHTML = (loopHtml || resp.full || ''); }
					} else { parser = document.createElement('div'); parser.innerHTML = loopHtml; }
					var newItem = null;
					try { newItem = parser.querySelector ? parser.querySelector('.fdm-orders-items#' + CSS.escape(String(orderId))) : null; } catch(e){ newItem = null; }
					if(!newItem) {
						var nodes = parser.querySelectorAll ? parser.querySelectorAll('.fdm-orders-items') : [];
						for(var i2=0;i2<nodes.length;i2++){ if(String(nodes[i2].id) === String(orderId)){ newItem = nodes[i2]; break; } }
					}
					if(newItem){
						var st = newItem.getAttribute ? newItem.getAttribute('data-order-status') : null;
						var container2 = getTargetContainerByStatus(st);
						try {
							// Remove any existing list item with same id to avoid duplicates
							var existingItem = document.querySelector('.fdm-orders-items#' + CSS.escape(String(orderId)));
							if (existingItem && existingItem.parentNode) {
								existingItem.parentNode.removeChild(existingItem);
							}
						} catch(_){ }
						placeOrder(container2, newItem, true);
						highlightJustInserted(orderId);
						try { if (String(st || '') === 'new') { MydAlert.add(orderId); } } catch(_){ }
					}
					// try to inject full details if REST provided it
					try { insertOrReplaceFullDetails(orderId, resp && resp.full ? resp.full : ''); } catch(_){ }
				}).catch(function(e){ console.warn('[Orders Panel] failed to fetch order HTML for id', orderId, e); });
			}
			socket.on('connect', function(){ 
				console.log('[Orders Panel] socket connected, id:', socket.id); 
				// Log rooms after connection
				setTimeout(function(){ console.log('[Orders Panel] socket rooms:', Array.from(socket.rooms || [])); }, 100);
			});
			socket.on('disconnect', function(reason){ console.log('[Orders Panel] socket disconnected:', reason); });
			socket.on('connect_error', function(err){ console.error('[Orders Panel] socket connect error:', err); });
			socket.on('order.status', function(payload){
				console.log('[Orders Panel] received order.status raw payload:', payload);
				try {
					if (!payload || !payload.order_id) return console.warn('[Orders Panel] order.status missing order_id', payload);
					var id = String(payload.order_id);
					// Extract status from various payload shapes
					var extracted = extractStatusFromPayload(payload);
					console.log('[Orders Panel] extracted status from payload:', extracted);
					var rawStatus = extracted || (payload.status || '');
					var status = normalizeStatus(rawStatus);
					console.log('[Orders Panel] normalized status:', status, ' (raw:', rawStatus, ') for order', id);
					try { if (status === 'new') { MydAlert.add(id); } } catch(_){ }
					// Se o status mudou para confirmado ou cancelado, interrompe o som
					// (usar remoção específica para eventos vindos do websocket)
					try { if (status === 'confirmed' || status === 'canceled') { MydAlert.removeFromSocket(id); } } catch(_){ }
					var item = document.querySelector('.fdm-orders-items#' + CSS.escape(id));
					if (!item) {
						// Try to fetch the card and insert it without a full reload
						fetchAndInsertOrderCard(id);
						// Disparar auto-print se o pedido chegou como confirmed e não existia no DOM
						if (status === 'confirmed') {
							// Aguardar inserção do card antes de imprimir (dedup feita internamente pelo triggerAutoPrint)
							setTimeout(function(){ try { triggerAutoPrint(id); } catch(e){ console.error('[Orders Panel] auto-print after insert failed', e); } }, 1500);
						}
						return;
					}
					// map status to label and color (same mapping as PHP)
					var map = {
						'new': { text: '<?php echo esc_js( __( 'New', 'myd-delivery-pro' ) ); ?>', color: '#d0ad02' },
						'confirmed': { text: '<?php echo esc_js( __( 'Confirmed', 'myd-delivery-pro' ) ); ?>', color: '#208e2a' },
						'in-delivery': { text: '<?php echo esc_js( __( 'In Delivery', 'myd-delivery-pro' ) ); ?>', color: '#d8800d' },
						'done': { text: '<?php echo esc_js( __( 'Done', 'myd-delivery-pro' ) ); ?>', color: '#037d91' },
						'waiting': { text: '<?php echo esc_js( __( 'Waiting', 'myd-delivery-pro' ) ); ?>', color: '#4e6585' },
						'canceled': { text: 'Cancelado', color: '#dc3545' }
					};
					var data = map[status] || map[normalizeStatus(status)] || { text: status, color: '#6c757d' };
					var badge = item.querySelector('.fdm-order-list-items-status');
					if (badge) {
						// Hide badge for terminal statuses
						if (['done', 'finished', 'canceled', 'refunded'].indexOf(status) !== -1) {
							badge.style.display = 'none';
						} else {
							badge.textContent = data.text;
							badge.style.background = data.color;
							badge.style.display = ''; // Ensure it's visible for other statuses
						}
					}
					// keep status attribute up to date
					item.setAttribute('data-order-status', status);
					// Apply canceled visual treatment (strikethrough + badge)
					if (status === 'canceled') {
						item.classList.add('myd-order-canceled');
						var orderNumEl = item.querySelector('.fdm-order-list-items-order-number');
						if (orderNumEl && !orderNumEl.querySelector('.myd-canceled-badge')) {
							var badge = document.createElement('span');
							badge.className = 'myd-canceled-badge';
							badge.textContent = 'Cancelado';
							orderNumEl.appendChild(badge);
						}
					} else {
						item.classList.remove('myd-order-canceled');
						try { var cb = item.querySelector('.myd-canceled-badge'); if(cb) cb.remove(); } catch(_){}
					}
					// If order moved to a terminal status, remove "new" highlight which may override colors
					if (['done', 'finished', 'canceled', 'refunded'].indexOf(status) !== -1) {
						try {
							item.classList.remove('fdm-new-unclicked');
							item.removeAttribute('data-order-clicked');
							// remove inline style overrides so attribute-selectors can apply
							item.style.removeProperty('background');
							item.style.removeProperty('border');
						} catch (e) { /* ignore */ }
					}
					// toggle quick actions (Cancelar + Preparar) in the corresponding detail
					try {
						var detail = document.getElementById('content-' + String(id));
						if (detail) {
							var qa = detail.querySelector('.myd-quick-actions');
							function ensureQA(){ if(!qa){ qa = document.createElement('div'); qa.className = 'myd-quick-actions'; detail.appendChild(qa);} }
							function renderNew(){
								ensureQA(); qa.innerHTML = '';
								var cancelBtn = document.createElement('div');
								cancelBtn.className = 'fdm-btn-order-action myd-cancel-btn';
								cancelBtn.id = 'myd-cancel-' + String(id);
								cancelBtn.setAttribute('data-manage-order-id', String(id));
								cancelBtn.setAttribute('data-manage-order-action', 'canceled');
								cancelBtn.textContent = '<?php echo esc_js( __( 'Cancel', 'myd-delivery-pro' ) ); ?>';
								var prepBtn = document.createElement('div');
								prepBtn.className = 'fdm-btn-order-action myd-preparar-btn';
								prepBtn.id = 'myd-preparar-' + String(id);
								prepBtn.setAttribute('data-manage-order-id', String(id));
								prepBtn.setAttribute('data-manage-order-action', 'confirmed');
								prepBtn.textContent = 'Preparar';
								qa.appendChild(cancelBtn); qa.appendChild(prepBtn); qa.style.display = 'flex';
							}
							function renderConfirmed(){
								ensureQA(); qa.innerHTML = '';
								var cancelBtn = document.createElement('div');
								cancelBtn.className = 'fdm-btn-order-action myd-cancel-btn';
								cancelBtn.id = 'myd-cancel-' + String(id);
								cancelBtn.setAttribute('data-manage-order-id', String(id));
								cancelBtn.setAttribute('data-manage-order-action', 'canceled');
								cancelBtn.textContent = '<?php echo esc_js( __( 'Cancel', 'myd-delivery-pro' ) ); ?>';
								var shipBtn = document.createElement('div');
								shipBtn.className = 'fdm-btn-order-action myd-despachar-btn';
								shipBtn.id = 'myd-despachar-' + String(id);
								shipBtn.setAttribute('data-manage-order-id', String(id));
								shipBtn.setAttribute('data-manage-order-action', 'in-delivery');
								shipBtn.textContent = 'Despachar pedido';
								qa.appendChild(cancelBtn); qa.appendChild(shipBtn); qa.style.display = 'flex';
							}
							if (status === 'new') {
								renderNew();
							} else if (status === 'confirmed') {
								renderConfirmed();
							} else if (qa) {
								qa.style.display = 'none';
							}
						}
					} catch(_){ }
					// move item to the correct section if needed
					try {
						var target = getTargetContainerByStatus(status);
						if (target && item.parentElement !== target) {
							if (item.parentElement) item.parentElement.removeChild(item);
							target.insertBefore(item, target.firstElementChild || null);
							if (typeof window.updateActionVisibility === 'function') window.updateActionVisibility();
						}
					} catch(e){ console.error(e); }

					try {
						   if (badge) {
							   // Hide badge for terminal statuses
							   if (["done", "finished", "canceled", "refunded"].indexOf(status) !== -1) {
								   badge.style.display = 'none';
							   } else {
								   // NÃO sobrescreve badge, apenas atualiza cor e acessibilidade
								   badge.style.background = data.color;
								   badge.style.display = '';
								   // Atualiza atributos para acessibilidade
								   badge.setAttribute('data-status-label', data.text);
								   badge.setAttribute('title', data.text);
							   }
						   }
						   if (status === 'confirmed') {
						   triggerAutoPrint(id);
					   }
						} catch(err) {
							console.error('[Orders Panel] badge/auto-print update failed for order ' + id, err);
						}

					// If order became done (finished/completed), play check sound and ensure it is removed from pending alerts
					// Note: normalizeStatus() converts 'finished' -> 'done', so we check both
					if (status === 'done' || status === 'finished') {
						try {
							MydSingleSound.playFinished();
						} catch(e) { console.warn('[Orders Panel] failed to play finished sound', e); }
						try { MydAlert.removeFromSocket(id); } catch(_) {}
					}
				} catch(e) { console.error(e); }
			});
			// Real-time store status listener (UI updates for select-btn suppressed)
			socket.on('store.status', function(payload){
				// Keep for debugging only; do not manipulate `.myd-select-btn` from socket events.
				console.log('[Orders Panel] received store.status (select-btn updates disabled):', payload);
			});
		} catch(e) { console.error('[Orders Panel] socket error', e); }
	}

	// Sinalizar ANTES do fetch (síncronamente) para que o frontend.min.js
	// (fallback) não crie uma segunda conexão de socket paralela
	try { window._mydPanelSocketRegistered = true; } catch(_){ }

	// request anonymous token (customer_id 0) from WP
	fetch('/wp-json/myd-delivery/v1/push/auth', {
		method: 'POST',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify({ myd_customer_id: 0 }),
		credentials: 'same-origin' // Include cookies for authentication
	}).then(function(r){ 
		console.log('[Orders Panel] token fetch response:', r.status, r.headers.get('content-type'));
		return r.json(); 
	}).then(function(data){
		console.log('[Orders Panel] token data:', data);
		if (data && data.token) {
			// Decode JWT to check role
			try {
				var payload = data.token.split('.')[1];
				var decoded = JSON.parse(atob(payload.replace(/-/g, '+').replace(/_/g, '/')));
				console.log('[Orders Panel] decoded token:', decoded);
			} catch(e) {
				console.error('[Orders Panel] failed to decode token:', e);
			}
			connectSocketWithToken(data.token);
		} else {
			console.error('[Orders Panel] no token in response');
		}
	}).catch(function(e){ 
		console.error('[Orders Panel] token fetch error:', e); 
	});
});
</script>

<script>
// Select-menu behaviour: toggle, option clicks, update WP and notify push server
document.addEventListener('DOMContentLoaded', function(){
	try {
		var wrap = document.getElementById('myd-store-status-wrap');

				// Poll server periodically to detect schedule-based open/close changes
				(function(){
					var POLL_INTERVAL = 30000; // 30s
					function pollOnce(){
						try{
							if(!wrap) return;
							fetch('/wp-json/myd-delivery/v1/store/status', { method: 'GET', cache: 'no-store', credentials: 'same-origin' })
							.then(function(r){ if(!r.ok) throw new Error('bad'); return r.json(); })
							.then(function(j){
								if(!j || typeof j.open === 'undefined') return;
								var serverOpen = !!j.open;
								var prev = wrap.getAttribute('data-open') === '1';
								if(serverOpen !== prev){
									wrap.setAttribute('data-open', serverOpen ? '1':'0');
									var curForce = wrap.getAttribute('data-current') || 'ignore';
									try{ applyStatus(curForce, serverOpen); } catch(e){ console.warn('[StoreStatus] applyStatus failed on poll', e); }
									// Notify push server about schedule-based change so other clients receive the update
									try {
										ensureToken(function(tdata){
											if (!tdata || !tdata.token) return;
											var notifyPayload = { force: curForce, open: !!serverOpen };
											fetch(pushUrlLocal + '/notify/store', {
												method: 'POST',
												headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + tdata.token },
												body: JSON.stringify(notifyPayload)
											}).then(function(r){ if (!r.ok) console.warn('Push notify/store failed (poll)'); }).catch(function(e){ console.error('[StoreStatus] push notify failed (poll)', e); });
										});
									} catch(e) { console.error('[StoreStatus] error notifying push on poll', e); }
								}
							}).catch(function(){ /* ignore */ });
						} catch(e){}
					}
					setTimeout(pollOnce, 2000);
					setInterval(pollOnce, POLL_INTERVAL);
				})();
		if (!wrap) return;
		var btn = wrap.querySelector('.select-btn');
		var opts = wrap.querySelectorAll('.option');
		var optBox = wrap.querySelector('.options');

		var movedOptionsEl = null;
		// Close and cleanup any moved/cloned options element. Keep the original optBox display state in sync
		function closeAll() {
			// remove any cloned moved element
			if (movedOptionsEl) {
				try { movedOptionsEl.parentNode.removeChild(movedOptionsEl); } catch(_){ }
				movedOptionsEl = null;
			}
			// hide all original .options instances to avoid duplicates showing behind
			try {
				Array.prototype.slice.call(document.querySelectorAll('.select-menu .options')).forEach(function(o){
					try { o.style.display = 'none'; } catch(_){ }
				});
			} catch(_){ }
		}

		function openBox() {
			if (!optBox) return;
			// if already moved/open, do nothing
			if (movedOptionsEl) return;
			// move a clone to body to avoid stacking context issues
			try {
				movedOptionsEl = optBox.cloneNode(true);
				movedOptionsEl.classList.add('myd-select-options');
				movedOptionsEl.setAttribute('data-myd-select', '1');
				movedOptionsEl.style.position = 'absolute';
				movedOptionsEl.style.display = 'block';
				movedOptionsEl.style.zIndex = '120000';
				// position under the button
				var rect = btn.getBoundingClientRect();
				movedOptionsEl.style.left = (rect.left + window.scrollX) + 'px';
				movedOptionsEl.style.top = (rect.bottom + window.scrollY + 8) + 'px';
				document.body.appendChild(movedOptionsEl);
				// hide the original options element while the clone is visible
				// this prevents the original box (positioned inside the top-bar) from showing behind the clone
				try { optBox.style.display = 'none'; } catch(_){ }
				// explicitly hide any other original options so they don't remain visible under the clone
				try {
					Array.prototype.slice.call(document.querySelectorAll('.select-menu .options')).forEach(function(o){
						try { o.style.display = 'none'; } catch(_){ }
					});
				} catch(_){ }
				// re-bind option click listeners on the moved element
				Array.prototype.slice.call(movedOptionsEl.querySelectorAll('.option')).forEach(function(optMoved){
					optMoved.addEventListener('click', function(e){
						// find original option by status and trigger click on it
						var status = optMoved.getAttribute('data-status');
						// find matching original option and dispatch
						var original = Array.prototype.slice.call(opts).find(function(o){ return o.getAttribute('data-status') === status; });
						if (original) original.click();
					});
				});
			} catch(e) { console.error(e); optBox.style.display = 'block'; }
		}

		btn.addEventListener('click', function(e){
			e.stopPropagation();
			if (!optBox) return;
			// Use movedOptionsEl as the authoritative "open" state when cloning to body.
			if (movedOptionsEl) closeAll(); else openBox();
		});

		// click outside closes
		document.addEventListener('click', function(){ closeAll(); });

		// SVG templates (injected from PHP)
		var svgOpen = <?php echo json_encode( myd_get_status_icon_svg_inline(true) ); ?>;
		var svgClose = <?php echo json_encode( myd_get_status_icon_svg_inline(false) ); ?>;

		// helper to update UI and icon (disabled)
		function applyStatus(status, storeOpen){
			// Disabled: avoid any DOM mutation of `.myd-select-btn`.
			try { console.log('[StoreStatus] applyStatus called (updates disabled):', status, storeOpen); } catch(e){}
		}

		var adminPushToken = null;
		var pushUrlLocal = '<?php echo esc_js( get_option( 'myd_push_server_url', '' ) ); ?>';

		function ensureToken(cb){
			if (adminPushToken) return cb({ token: adminPushToken });
			fetch('/wp-json/myd-delivery/v1/push/auth', {
				method: 'POST', headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ myd_customer_id: 0 })
			}).then(r=>r.json()).then(function(d){ adminPushToken = d && d.token ? d.token : null; cb(d); }).catch(function(e){ console.error('Token fetch error', e); cb(null); });
		}

		opts.forEach(function(opt){
			opt.addEventListener('click', function(e){
				e.stopPropagation();
				var status = opt.getAttribute('data-status');
				closeAll();
				// submit to WP via hidden form (nonce present)
				var form = document.getElementById('myd-store-status-form');
				var nonceInput = form ? form.querySelector('input[name="myd_store_status_box_nonce"]') : null;
				var nonce = nonceInput ? nonceInput.value : '';
				var fd = new FormData(); fd.append('myd_store_status', status); fd.append('myd_store_status_box_nonce', nonce);
				fetch(window.location.href, { method: 'POST', body: fd }).then(function(resp){
					if (resp.ok) {
						// on success, notify push server and let socket update the UI
						try {
							if (pushUrlLocal) {
								ensureToken(function(tdata){
									if (!tdata || !tdata.token) return;
										// helper: try to get authoritative 'open' from server via REST endpoints (best-effort)
										function fetchServerOpen(cb) {
											var endpoints = [
												'/wp-json/myd-delivery/v1/store/open',
												'/wp-json/myd-delivery/v1/store/status',
												'/wp-json/myd-delivery/v1/store',
												'/wp-json/myd-delivery/v1/push/store-open'
											];
											var tried = 0;
											function tryNext() {
												if (tried >= endpoints.length) return cb(undefined);
												var url = endpoints[tried++];
												fetch(url, { method: 'GET', credentials: 'same-origin' }).then(function(r){
													if (!r.ok) return tryNext();
													return r.json().then(function(j){
														if (j && typeof j.open !== 'undefined') return cb(!!j.open);
														// some endpoints may return { status: 'open' } or similar
														if (j && typeof j.status !== 'undefined') return cb(String(j.status).toLowerCase() === 'open');
														return tryNext();
													}).catch(function(){ tryNext(); });
												}).catch(function(){ tryNext(); });
											}
											tryNext();
										}

										var runtimeOpenAttr = wrap ? wrap.getAttribute('data-open') : null;
										var initialOpenAttr = wrap ? wrap.getAttribute('data-initial-open') : null;
										var initialOpen = initialOpenAttr === '1';

										function doNotify(openValue) {
											var notifyPayload = { force: status };
											if (status === 'open' || status === 'close') {
												notifyPayload.open = (status === 'open');
											} else {
												if (typeof openValue !== 'undefined') notifyPayload.open = !!openValue;
												else if (runtimeOpenAttr === '1') notifyPayload.open = true;
												else if (runtimeOpenAttr === '0') notifyPayload.open = false;
												else notifyPayload.open = initialOpen;
											}
											fetch(pushUrlLocal + '/notify/store', {
												method: 'POST', headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + tdata.token },
												body: JSON.stringify(notifyPayload)
											}).then(function(r){ if (!r.ok) console.warn('Push notify/store failed'); }).catch(function(e){ console.error(e); });
										}

										if (status === 'ignore') {
											// try to obtain authoritative server open before notifying push server
											fetchServerOpen(function(serverOpen){
												if (typeof serverOpen === 'undefined') {
													// couldn't verify server state — do not change frontend (wait for socket)
													console.warn('[StoreStatus] unable to verify server open state; UI will wait for socket update');
													doNotify();
												} else {
													// Apply authoritative state to frontend now (server-verified)
													try { applyStatus('ignore', !!serverOpen); } catch(e) { console.error(e); }
													doNotify(serverOpen);
												}
											});
										} else {
											doNotify();
										}
								});
							}
						} catch(e){ console.error(e); }
					} else {
						alert('Ocorreu um erro ao salvar o status.');
					}
				}).catch(function(e){ console.error(e); alert('Ocorreu um erro de rede.'); });
			});
		});
	} catch(e) { console.error(e); }
});
</script>

<!-- detail card CSS consolidated into assets/css/panel-inline.css -->

</div><!-- /.fdm-orders-content -->

</div><!-- /.fdm-ordres-wrap -->

</div><!-- /.myd-orders-shell -->

<!-- Settings modal -->
<style>
.myd-menu-item.active { background: #e6e6e6 !important; }
</style>
<div id="myd-settings-modal" style="display:none;position:fixed;left:0;top:0;width:100%;height:100%;z-index:130000;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
	<div style="background:#fff;max-width:900px;width:94%;margin:0 auto;border-radius:8px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.3);display:flex;height:80vh;position:relative;padding-bottom:56px;">
	<!-- Save button positioned at the bottom-right of the modal -->
	<button id="myd-save-printer" class="myd-btn myd-btn-primary" type="button" style="position:absolute;right:20px;bottom:16px;background:#ffae00;color:#fff;border:0;padding:12px 18px;border-radius:8px;cursor:pointer;font-size:15px;min-width:110px;display:none">Salvar</button>
        <div style="width:200px;border-right:1px solid #eee;padding:16px;background:#f9f9f9;">
            <h4 style="margin:0 0 12px 0;font-size:14px;">Menu</h4>
            <ul id="myd-settings-menu" style="list-style:none;padding:0;margin:0;">
                <li class="myd-menu-item active" data-section="printer" style="padding:8px;cursor:pointer;border-radius:4px;margin-bottom:4px;">Impressão</li>
                <li class="myd-menu-item" data-section="store" style="padding:8px;cursor:pointer;border-radius:4px;margin-bottom:4px;">Gestão de Loja</li>
                <li class="myd-menu-item" data-section="account" style="padding:8px;cursor:pointer;border-radius:4px;margin-bottom:4px;">Minha Conta</li>
            </ul>
        </div>
        <div style="flex:1;padding:16px;overflow-y:auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <strong style="font-size:16px" id="myd-settings-title">Impressão</strong>
                <button id="myd-settings-close" style="background:none;border:0;font-size:18px;cursor:pointer;">✕</button>
            </div>
            <div id="myd-settings-content">
                <!-- Configurar Impressora -->
                <section id="myd-section-printer" class="myd-settings-section">

                    <!-- Mensagem para usuários não-Electron -->
                    <div id="myd-electron-message" style="display:none;background:#fff3cd;border:1px solid #ffeaa7;border-radius:4px;padding:16px;margin-bottom:16px;">
                        <h5 style="margin:0 0 8px 0;color:#856404;">Funcionalidade exclusiva do aplicativo</h5>
                        <p style="margin:0;color:#856404;font-size:14px;">
                            A configuração de impressoras locais está disponível apenas quando você utiliza o aplicativo "Franguxo Gestor de Pedidos" instalado no computador.
                        </p>
                    </div>

                    <!-- Controles de impressora (visíveis apenas no Electron) -->
                    <div id="myd-printer-controls">
						<div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
							<span id="myd-printer-loading" style="display:none;color:#666">Buscando...</span>
						</div>
						<div id="myd-copies-card" style="margin-bottom:12px;padding:12px;border-radius:6px;border:1px solid #eef0f2;display:flex;align-items:center;justify-content:space-between;background:#fff">
							<div style="flex:1;min-width:0;">
								<label style="display:block;margin-bottom:6px;font-weight:600">Quantidade de vias</label>
								<div style="color:#666;font-size:13px;line-height:1.2">Defina a quantidade de vias que serão impressas automaticamente</div>
							</div>
							<div style="margin-left:12px;">
								<div id="myd-copies-buttons" style="display:flex;gap:8px;">
									<button type="button" class="myd-copy-btn" data-copies="1">1</button>
									<button type="button" class="myd-copy-btn" data-copies="2">2</button>
									<button type="button" class="myd-copy-btn" data-copies="3">3</button>
									<button type="button" class="myd-copy-btn" data-copies="4">4</button>
								</div>
							</div>
						</div>
						<div id="myd-printers-list" style="min-height:40px;">
							<!-- printers will be injected here -->
						</div>
						<div style="margin-top:16px;padding-top:16px;border-top:1px solid #eef0f2;">
							<button type="button" id="myd-test-print-btn" style="display:flex;align-items:center;gap:8px;background:#f5f5f5;border:1px solid #ddd;border-radius:6px;padding:10px 16px;cursor:pointer;font-size:14px;color:#333;transition:background .15s;"
								onmouseover="this.style.background='#eee'" onmouseout="this.style.background='#f5f5f5'">
								🖨️ <span id="myd-test-print-label">Testar Impressão</span>
							</button>
							<div id="myd-test-print-status" style="margin-top:8px;font-size:13px;color:#666;display:none;"></div>
						</div>
						<div style="margin-top:12px;">
							<span id="myd-printer-status" style="margin-left:8px;color:green;display:none"></span>
						</div>
                    </div>
                </section>

                <!-- Gestão de Loja -->
                <section id="myd-section-store" class="myd-settings-section" style="display:none;">
                    <h4 style="margin:0 0 8px 0">Gestão de Loja</h4>
                    <p style="margin:0 0 12px 0;color:#666">Gerencie horários de funcionamento, status da loja e outras configurações operacionais.</p>
                    <div style="margin-bottom:12px;">
                        <label style="display:block;margin-bottom:4px;">Horário de Funcionamento</label>
                        <p style="color:#666;font-size:14px;">Configure os horários em Configurações > Horários de Abertura.</p>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label style="display:block;margin-bottom:4px;">Status da Loja</label>
                        <p style="color:#666;font-size:14px;">Use o seletor na barra superior para alterar o status (Aberto/Fechado).</p>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:4px;">Outras Configurações</label>
                        <p style="color:#666;font-size:14px;">Acesse Configurações > Geral para mais opções.</p>
                    </div>
                </section>

                <!-- Minha Conta -->
                <section id="myd-section-account" class="myd-settings-section" style="display:none;">
                    <h4 style="margin:0 0 8px 0">Minha Conta</h4>
                    <p style="margin:0 0 12px 0;color:#666">Visualize e edite suas informações pessoais.</p>
                    <div style="margin-bottom:12px;">
                        <label style="display:block;margin-bottom:4px;">Nome</label>
                        <input type="text" id="myd-account-name" readonly style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                    </div>
                    <div style="margin-bottom:12px;">
                        <label style="display:block;margin-bottom:4px;">Email</label>
                        <input type="email" id="myd-account-email" readonly style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:4px;">Função</label>
                        <input type="text" id="myd-account-role" readonly style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
                    </div>
                </section>
            </div>
        </div>
		<script>
		// Today/Tomorrow buttons: set active class and update hours
		document.addEventListener('DOMContentLoaded', function(){
			var btnToday = document.getElementById('dashboard-day-today');
			var btnTomorrow = document.getElementById('dashboard-day-tomorrow');
			var hoursEl = document.getElementById('dashboard-hours');
			if (!btnToday || !btnTomorrow || !hoursEl) return;

			function setActive(button){
				btnToday.classList.remove('active');
				btnTomorrow.classList.remove('active');
				button.classList.add('active');
				if (button === btnToday) {
					hoursEl.textContent = hoursEl.getAttribute('data-today') || '';
				} else {
					hoursEl.textContent = hoursEl.getAttribute('data-tomorrow') || '';
				}
			}

			btnToday.addEventListener('click', function(e){ e.preventDefault(); setActive(btnToday); });
			btnTomorrow.addEventListener('click', function(e){ e.preventDefault(); setActive(btnTomorrow); });
			btnToday.addEventListener('keydown', function(e){ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setActive(btnToday); } });
			btnTomorrow.addEventListener('keydown', function(e){ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setActive(btnTomorrow); } });

			// initialize showing today
			setActive(btnToday);
		});
		</script>
    </div>
</div><script>
document.addEventListener('DOMContentLoaded', function(){
    var settingsBtn = document.getElementById('myd-sidebar-settings');
    var modal = document.getElementById('myd-settings-modal');
    var closeBtn = document.getElementById('myd-settings-close');
    var menuItems = document.querySelectorAll('#myd-settings-menu .myd-menu-item');
    var sections = document.querySelectorAll('.myd-settings-section');
    var titleEl = document.getElementById('myd-settings-title');
    var printersList = document.getElementById('myd-printers-list');
	var loadingEl = document.getElementById('myd-printer-loading');
	var saveBtn = document.getElementById('myd-save-printer');
	var clearBtn = document.getElementById('myd-clear-printer');
	var statusEl = document.getElementById('myd-printer-status');
	// pending selection (only persisted when user clicks Salvar)
	var pendingPrinter = null;
	// pending copies and persisted copies
	var pendingCopies = null;
	var currentSavedCopies = null;
	// currently persisted selection (from localStorage) used to detect changes
	var currentSaved = null;

    if (!settingsBtn || !modal) return;

	var testPrintBtn = document.getElementById('myd-test-print-btn');
	var testPrintLabel = document.getElementById('myd-test-print-label');
	var testPrintStatus = document.getElementById('myd-test-print-status');

	if (testPrintBtn) {
		testPrintBtn.addEventListener('click', function() {
			var savedPrinter = (typeof window.electron !== 'undefined' && window.electron && window.electron.ipcRenderer)
				? null : null; // será lido pelo server via config

			testPrintLabel.textContent = 'Imprimindo...';
			testPrintBtn.disabled = true;
			testPrintStatus.style.display = 'none';

			var fakePrinter = pendingPrinter || currentSaved || undefined;

			var fakeOrder = {
				id: '1234',
				store_name: 'Franguxo',
				date: new Date().toLocaleDateString('pt-BR') + ' ' + new Date().toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'}),
				localizador: 'ABCD1234',
				customer_name: 'Cliente Teste',
				customer_phone: '(47) 99999-9999',
				address: 'Rua das Flores',
				address_number: '123',
				neighborhood: 'Centro',
				city: 'Jaraguá do Sul',
				state: 'SC',
				items: [
					{
						product_name: 'Frango Grelhado',
						quantity: '2',
						product_price: '25,90',
						total: 51.80,
						extras: { groups: [{ group: 'Acompanhamento', items: [{ name: 'Fritas', quantity: '1', price: '5.00' }] }] },
						product_note: 'Sem cebola'
					},
					{
						product_name: 'Refrigerante Lata',
						quantity: '1',
						product_price: '6,00',
						total: 6.00,
						extras: { groups: [] }
					}
				],
				subtotal: '57,80',
				delivery_price: '5,00',
				total: '62,80',
				payment_status: 'waiting',
				order_payment_method: 'DIN',
				order_change: '70.00'
			};

			fetch('http://127.0.0.1:3420/print', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ orderData: fakeOrder, escpos: true, printer: fakePrinter })
			})
			.then(function(r) { return r.json(); })
			.then(function(data) {
				testPrintStatus.style.display = 'block';
				if (data.ok) {
					testPrintStatus.style.color = 'green';
					testPrintStatus.textContent = '✅ Impressão enviada com sucesso!';
				} else {
					testPrintStatus.style.color = '#c00';
					testPrintStatus.textContent = '❌ Erro: ' + (data.error || 'Desconhecido');
				}
			})
			.catch(function() {
				testPrintStatus.style.display = 'block';
				testPrintStatus.style.color = '#c00';
				testPrintStatus.textContent = '❌ Servidor de impressão não encontrado (porta 3420)';
			})
			.finally(function() {
				testPrintLabel.textContent = 'Testar Impressão';
				testPrintBtn.disabled = false;
			});
		});
	}

	function openModal(){
		modal.style.display = 'flex';
		// Detecta se está rodando no Electron usando a mesma lógica aplicada no order-list
		var isElectron = false;
		try {
			if (typeof window !== 'undefined') {
				if (typeof window.isElectron !== 'undefined') {
					isElectron = !!window.isElectron;
				} else if (window.electronAPI && (typeof window.electronAPI.triggerPrint === 'function' || typeof window.electronAPI.printOrderReceipt === 'function')) {
					isElectron = true;
				} else if (
					(typeof navigator === 'object' && navigator.userAgent && navigator.userAgent.toLowerCase().indexOf('electron') !== -1) ||
					(window.process && window.process.type)
				) {
					isElectron = true;
				}
			}
		} catch(_){ }
		var printerSection = document.getElementById('myd-section-printer');
		var electronMessage = document.getElementById('myd-electron-message');
		var printerControls = document.getElementById('myd-printer-controls');

		if (!isElectron) {
			// Esconde controles e mostra mensagem para não-Electron
			if (printerControls) printerControls.style.display = 'none';
			if (electronMessage) electronMessage.style.display = 'block';
		} else {
			// Mostra controles e esconde mensagem para Electron
			if (printerControls) printerControls.style.display = 'block';
			if (electronMessage) electronMessage.style.display = 'none';
		}

		// load saved selection (no status text shown here)
		var saved = localStorage.getItem('myd-default-printer');
	currentSaved = saved === null ? null : saved;
	// load saved copies
	var savedCopies = localStorage.getItem('myd-print-copies');
	currentSavedCopies = savedCopies === null ? null : savedCopies;
	pendingPrinter = null;
	pendingCopies = null;
		// Buscar impressoras automaticamente ao abrir o modal
		try{
			// aciona a busca diretamente
			setTimeout(function(){ fetchPrinters(); }, 50);
		}catch(e){ console.warn('Auto-fetch printers failed', e); }
        // Load account data if section is account
        loadAccountData();
    }
    function closeModal(){ modal.style.display = 'none'; }

    settingsBtn.addEventListener('click', function(e){ e.preventDefault(); openModal(); });
    closeBtn.addEventListener('click', function(e){ e.preventDefault(); closeModal(); });
    modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });

    // Menu switching
    menuItems.forEach(function(item){
        item.addEventListener('click', function(){
            var section = item.getAttribute('data-section');
            // Update active menu item
            menuItems.forEach(function(mi){ mi.classList.remove('active'); });
            item.classList.add('active');
            // Show corresponding section
            sections.forEach(function(sec){ sec.style.display = 'none'; });
            var targetSec = document.getElementById('myd-section-' + section);
            if (targetSec) targetSec.style.display = 'block';
            // Update title
            var titles = { printer: 'Impressão', store: 'Gestão de Loja', account: 'Minha Conta' };
            titleEl.textContent = titles[section] || 'Configurações';
            // Load data if needed
            if (section === 'account') loadAccountData();
        });
    });

	// --- WhatsApp status management (consolidated polling + SSE) ---
	(function(){
		var lastState = undefined; // use undefined to force first update
		var evolutionApiUrl = <?php echo json_encode( trim( (string) get_option( 'evolution_api_url', '' ) ) ); ?>;
		var evolutionInstance = <?php echo json_encode( trim( (string) get_option( 'evolution_instance_name', '' ) ) ); ?>;
		var evolutionApiKey = <?php echo json_encode( trim( (string) get_option( 'evolution_api_key', '' ) ) ); ?>;
		var statusEl = document.getElementById('myd-whatsapp-status');
		var indicator = document.getElementById('myd-whatsapp-indicator');

		if (!statusEl || !indicator) return;

		function handleOfflineModal(isOffline) {
			var modalId = 'myd-offline-modal';
			var modal = document.getElementById(modalId);
			if (isOffline) {
				if (!modal) {
					modal = document.createElement('div');
					modal.id = modalId;
					modal.style.position = 'fixed';
					modal.style.top = '0';
					modal.style.left = '0';
					modal.style.width = '100vw';
					modal.style.height = '100vh';
					modal.style.backgroundColor = 'rgba(0,0,0,0.85)';
					modal.style.zIndex = '999999';
					modal.style.display = 'flex';
					modal.style.flexDirection = 'column';
					modal.style.alignItems = 'center';
					modal.style.justifyContent = 'center';
					modal.style.color = '#fff';
					modal.style.fontFamily = 'Inter, sans-serif';
					modal.innerHTML = `
						<div style="background: #2C2D3E; padding: 40px; border-radius: 12px; text-align: center; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
							<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 64px; height: 64px; margin-bottom: 20px;"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M3.96973 5.03039L18.9697 20.0304L20.0304 18.9697L5.03039 3.96973L3.96973 5.03039ZM2.92454 9.67478C3.71079 8.88852 4.57369 8.2256 5.48917 7.68602L6.58987 8.78672C5.86769 9.17925 5.17917 9.65606 4.53875 10.2172L11.9999 17.5283L13.6826 15.8795L14.7433 16.9402L11.9999 19.6284L2.38879 10.2105L2.92454 9.67478ZM19.4611 10.2172L15.8255 13.7797L16.8862 14.8404L21.611 10.2105L21.0753 9.67478C17.6588 6.25827 12.7953 5.17059 8.45752 6.41173L9.69662 7.65083C13.0757 6.95288 16.7117 7.80832 19.4611 10.2172Z" fill="#f39c12"></path> </g></svg>
							<h2 style="margin: 0 0 15px 0; font-size: 24px; font-weight: 600;">Sem Conexão</h2>
							<p style="margin: 0; font-size: 16px; color: #aaa; line-height: 1.5;">O painel perdeu a conexão com a internet. Aguardando reconexão...</p>
						</div>
					`;
					document.body.appendChild(modal);
				}
				modal.style.display = 'flex';
			} else {
				if (modal) {
					modal.style.display = 'none';
				}
			}
		}

		function updateUi(state){
			try {
				if (!navigator.onLine && (!state || state === 'disconnected')) {
					statusEl.textContent = 'Sem Internet';
					indicator.style.background = '#f39c12'; // Laranja
					handleOfflineModal(true);
					// Não dar return aqui, vamos limpar a última variável
					lastState = 'offline';
					return;
				} else {
					handleOfflineModal(false);
				}

				var st = state ? String(state).toLowerCase() : null;
				if (st === lastState) return;
				lastState = st;

				if (st === 'open'){
					statusEl.textContent = 'Conectado';
					indicator.style.background = '#2ecc71';
					// Close QR modals if open
					var m = document.getElementById('myd-whatsapp-modal');
					if (m) m.style.display = 'none';
					var evoModal = document.getElementById('evolution_qr_modal');
					if (evoModal) evoModal.remove();
				} else {
					statusEl.textContent = 'Desconectado';
					indicator.style.background = '#c0392b';
				}
			} catch(e) { console.error('[WhatsApp Status] updateUi error:', e); }
		}

		// Listener global para status de rede
		window.addEventListener('online', function() {
			handleOfflineModal(false);
			fetchStatusOnce();
		});
		window.addEventListener('offline', function(){ 
			updateUi(null); 
		});

		function fetchStatusOnce(){
			var restUrl = (window.location && window.location.origin ? window.location.origin : '') + '/wp-json/myd-delivery/v1/evolution/whatsapp_status';
			fetch(restUrl, { method: 'GET', credentials: 'same-origin' }).then(function(r){
				if (!r.ok) throw new Error('status_fetch_failed');
				return r.json();
			}).then(function(j){
				var st = null;
				if (j && j.body && j.body.instance && j.body.instance.state) st = j.body.instance.state;
				else if (j && j.instance && j.instance.state) st = j.instance.state;
				updateUi(st);
			}).catch(function(){
				// fallback: direct external URL
				if (!evolutionApiUrl || !evolutionInstance) { updateUi(null); return; }
				var base = evolutionApiUrl.replace(/\/+$/, '');
				var inst = String(evolutionInstance).replace(/^dwp-/, '');
				var url = base + '/instance/connectionState/dwp-' + encodeURIComponent(inst);
				fetch(url, { method: 'GET', headers: { 'apikey': evolutionApiKey } }).then(function(r){ 
					if(!r.ok) throw new Error('direct_fetch_failed'); 
					return r.json(); 
				}).then(function(j){ 
					var st = (j && j.instance && j.instance.state) ? j.instance.state : null; 
					updateUi(st); 
				}).catch(function(){ updateUi(null); });
			});
		}

		// Initial fetches
		fetchStatusOnce();
		// Polling fallback every 60 seconds (SSE is primary)
		setInterval(fetchStatusOnce, 60000);

		// SSE for real-time updates
		try {
			var nonce = '<?php echo wp_create_nonce("wp_rest"); ?>';
			var es = new EventSource('<?php echo esc_url_raw( rest_url("myd-delivery/v1/access-stream") ); ?>?_wpnonce=' + encodeURIComponent(nonce));
			
			es.addEventListener('whatsapp-status', function(e){
				try {
					var data = JSON.parse(e.data);
					if (data && data.state) updateUi(data.state);
				} catch(err){}
			});

			es.addEventListener('access', function(e){
				try {
					var row = JSON.parse(e.data);
					var modal = document.getElementById('myd-whatsapp-modal');
					if (!modal || modal.style.display === 'none') return;

					var evt = (row && row.event) ? row.event : (row && row.body && row.body.event ? row.body.event : '');
					if (!evt || String(evt).toLowerCase() !== 'qrcode.updated') return;

					var b64 = null;
					if (row.body && row.body.data && row.body.data.qrcode && row.body.data.qrcode.base64) b64 = row.body.data.qrcode.base64;
					if (!b64 && row.data && row.data.qrcode && row.data.qrcode.base64) b64 = row.data.qrcode.base64;
					
					if (b64) {
						var qrArea = document.getElementById('myd-whatsapp-qr-area');
						if (qrArea) qrArea.innerHTML = '<img src="' + b64 + '" alt="QR Code" style="max-width:320px;display:block;margin:6px auto;" />';
						var loading = document.getElementById('myd-whatsapp-qr-loading'); 
						if (loading) loading.style.display = 'none';
					}
				} catch(_) {}
			});
		} catch(e) {}

		// Re-fetch when store settings section is opened
		var obs = new MutationObserver(function(){
			var storeSec = document.getElementById('myd-section-store');
			if (storeSec && storeSec.style.display !== 'none') {
				fetchStatusOnce();
			}
		});
		var settingsContent = document.getElementById('myd-settings-content');
		if (settingsContent) obs.observe(settingsContent, { attributes: true, attributeFilter: ['style'] });
	})();

	// Click-to-open modal when status is Disconnected
	(function(){
		var widget = document.getElementById('myd-whatsapp-widget');
		var statusEl = document.getElementById('myd-whatsapp-status');
		if (!widget) return;

		var audioUrl = "<?php echo (defined('MYD_PLUGN_URL') ? MYD_PLUGN_URL : plugins_url('assets/songs/notify1.mp3', dirname(__FILE__, 3) . '/myd-delivery-pro.php')) . (defined('MYD_PLUGN_URL') ? 'assets/songs/notify1.mp3' : ''); ?>";
		var disconnectAudio = new Audio(audioUrl);
		disconnectAudio.loop = false;
		var alertActive = false;
		var audioStarted = false;
		var audioTimeoutId = null;

		function tryPlayAudio(){
			if (alertActive && disconnectAudio.paused) {
				disconnectAudio.play().then(function(){ audioStarted = true; }).catch(function(e){ 
					console.warn('Som bloqueado: aguardando interação do usuário.', e); 
				});
			}
		}

		// Adicionar intervalo de 1 segundo entre as repetições
		disconnectAudio.addEventListener('ended', function() {
			if (alertActive) {
				audioTimeoutId = setTimeout(tryPlayAudio, 2000);
			}
		});

		// Destravar áudio em qualquer clique se o alerta estiver ativo
		document.addEventListener('click', function(){ if (alertActive && !audioStarted) tryPlayAudio(); }, { once: false });

		function isDisconnected(){
			try{ return statusEl && statusEl.textContent && statusEl.textContent.toLowerCase().indexOf('desconect') !== -1; }catch(e){return false;}
		}

		function ensureModal(){
			var existing = document.getElementById('myd-whatsapp-modal');
			if (existing) return existing;
			var modal = document.createElement('div');
			modal.id = 'myd-whatsapp-modal';
			modal.style.cssText = 'display:none;position:fixed;z-index:100000;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,0.45);align-items:center;justify-content:center;';
			var inner = document.createElement('div');
			inner.style.cssText = 'background:#fff;padding:18px;border-radius:10px;max-width:520px;width:92%;box-shadow:0 10px 30px rgba(0,0,0,0.2);font-family:Arial, sans-serif;';
			inner.innerHTML = '<h3 style="margin-top:0;font-size:18px;">Robô WhatsApp desconectado</h3>' +
				'<p style="color:#444;">O robô do WhatsApp está desconectado. Você pode verificar as configurações da integração, revalidar o webhook ou consultar os logs para diagnosticar o problema.</p>' +
				'<div id="myd-whatsapp-qr-area" style="margin-top:12px;text-align:center;min-height:120px;">' +
					'<div id="myd-whatsapp-qr-loading" style="color:#666">Carregando QR Code...</div>' +
				'</div>' +
				'<div style="margin-top:16px;text-align:right;"><button id="myd-whatsapp-modal-close" style="margin-right:8px;padding:8px 12px;border-radius:6px;border:1px solid #ddd;background:#f5f5f5;">Fechar</button></div>';
			modal.appendChild(inner);
			modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
			document.body.appendChild(modal);

			document.getElementById('myd-whatsapp-modal-close').addEventListener('click', closeModal);

			// Quando o modal for criado, tente obter o QR da Evolution (reaproveita lógica do settings)
			(function(){
				var apiUrl = <?php echo json_encode( trim( (string) get_option( 'evolution_api_url', '' ) ) ); ?>;
				var instanceName = <?php echo json_encode( trim( (string) get_option( 'evolution_instance_name', '' ) ) ); ?>;
				var apiKey = <?php echo json_encode( trim( (string) get_option( 'evolution_api_key', '' ) ) ); ?>;
				var qrArea = document.getElementById('myd-whatsapp-qr-area');
				var qrLoading = document.getElementById('myd-whatsapp-qr-loading');
				if (!qrArea) return;

				function instanceFull(){ return 'dwp-' + (String(instanceName || '').replace(/^dwp-/, '')); }

				function showMessage(msg){ if (qrLoading) qrLoading.textContent = msg; }

				function renderImg(base64){
					if (!base64) return;
					qrArea.innerHTML = '<img src="' + base64 + '" alt="QR Code para conexão" style="max-width:320px;max-height:320px;display:block;margin:6px auto;" />';
				}

				function fetchConnect(){
					if (!apiUrl || !instanceName) { showMessage('Configuração Evolution ausente. Verifique as opções.'); return; }
					var base = apiUrl.replace(/\/+$/, '');
					var inst = instanceFull();
					var webhookUrl = <?php echo json_encode( esc_url_raw( rest_url( 'myd-delivery/v1/evolution/webhook' ) ) ); ?>;
					showMessage('Solicitando QR Code...');
					fetch(base + '/instance/connect/' + encodeURIComponent(inst), { method: 'GET', headers: { 'apikey': apiKey } }).then(function(resp){
						if (resp.status === 200) {
							resp.json().then(function(connectData){
								if (connectData && connectData.base64) {
									renderImg(connectData.base64);
									showMessage('Escaneie o QR Code para conectar.');
								} else {
									showMessage('Conectando, mas QR Code não disponível. Aguarde webhooks.');
								}
							}).catch(function(){ showMessage('Erro ao processar resposta do servidor Evolution.'); });
						} else if (resp.status === 404) {
							// Tentar criar instância e reconectar
							showMessage('Instância não encontrada. Criando instância...');
							fetch(base + '/instance/create', {
								method: 'POST',
								headers: { 'Content-Type': 'application/json', 'apikey': apiKey },
								body: JSON.stringify({ 
									instanceName: inst, 
									qrcode: true, 
									integration: 'WHATSAPP-BAILEYS',
									webhook: {
										enabled: true,
										url: webhookUrl,
										webhook_by_events: false,
										events: ["MESSAGES_UPSERT", "CONNECTION_UPDATE", "QRCODE_UPDATED"]
									}
								})
							}).then(function(createResp){
								// Se 201 (criado), 200 (sucesso) ou 400 (prisma error/já existe mas com erro de integration)
								if (createResp.status === 200 || createResp.status === 201 || createResp.status === 400) {
									if (createResp.status === 400) { console.warn('Evolution retornou 400 na criação, tentando prosseguir...'); }
									showMessage('Instância processada. Configurando webhook e QR Code...');
									
									// Capturar o token da instância e salvar no WordPress
									fetch(base + '/instance/fetchInstances', {
										headers: { 'apikey': apiKey }
									}).then(r => r.json()).then(data => {
										const list = Array.isArray(data) ? data : (data.instances || []);
										const found = list.find(i => i.instanceName === inst || i.name === inst);
										const finalToken = found ? (found.token || (found.instance && found.instance.token)) : null;
										if (finalToken) {
											// Salvar no WordPress
											fetch("<?php echo esc_url_raw( rest_url( 'myd-delivery/v1/evolution/save-token' ) ); ?>", {
												method: 'POST',
												headers: { 
													'Content-Type': 'application/json',
													'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>'
												},
												body: JSON.stringify({ token: finalToken, instance: inst })
											});

											// Configurar Webhook (usando endpoint /webhook/instance para compatibilidade com Manager)
											fetch(base + '/webhook/instance/' + inst, {
												method: 'POST',
												headers: { 'Content-Type': 'application/json', 'apikey': apiKey },
												body: JSON.stringify({
													url: webhookUrl,
													enabled: true,
													webhook_by_events: false,
													events: ["MESSAGES_UPSERT", "CONNECTION_UPDATE", "QRCODE_UPDATED"]
												})
											}).then(r => r.json()).then(wRes => {
												console.log('Evolution Webhook Response (Modal):', wRes);
											}).catch(err => console.error('Erro ao setar webhook:', err));
										}
									}).catch(e => console.error('Erro ao sincronizar token:', e));

									// tentar conectar novamente
									setTimeout(fetchConnect, 1500);
								} else {
									showMessage('Erro ao criar instância: ' + createResp.status);
								}
							}).catch(function(){ showMessage('Erro ao criar instância.'); });
						} else {
							showMessage('Erro ao solicitar QR Code: ' + resp.status);
						}
					}).catch(function(){ showMessage('Erro de conexão com Evolution.'); });
				}

				// Expor uma função para recarregar quando o modal for aberto
				qrArea.reloadQr = fetchConnect;
				// iniciar tentativa imediata
				fetchConnect();
			})();

			function closeModal(){ modal.style.display = 'none'; }
			return modal;
		}

		function ensureAlertPopup(){
			var existing = document.getElementById('myd-whatsapp-alert-popup');
			if (existing) return existing;
			var popup = document.createElement('div');
			popup.id = 'myd-whatsapp-alert-popup';
			popup.className = 'myd-alert-overlay';
			var inner = document.createElement('div');
			inner.className = 'myd-alert-modal';
			inner.innerHTML = 
				'<div class="myd-alert-icon-wrap">' +
					'<span>⚠️</span>' +
				'</div>' +
				'<h2 class="myd-alert-title">WhatsApp Desconectado!</h2>' +
				'<p class="myd-alert-desc">O robô de mensagens parou de funcionar. <br><b>Conecte novamente agora</b> para não perder nenhum pedido!</p>' +
				'<div class="myd-alert-actions">' +
					'<button id="myd-whatsapp-alert-ignore" class="myd-alert-btn myd-alert-btn-ignore">Ignorar</button>' +
					'<button id="myd-whatsapp-alert-connect" class="myd-alert-btn myd-alert-btn-connect">CONECTAR AGORA</button>' +
				'</div>';
			popup.appendChild(inner);
			document.body.appendChild(popup);

			var btnIgnore = document.getElementById('myd-whatsapp-alert-ignore');
			var btnConnect = document.getElementById('myd-whatsapp-alert-connect');

			btnIgnore.onclick = function(){
				popup.style.display = 'none';
				alertActive = false;
				clearTimeout(audioTimeoutId);
				disconnectAudio.pause();
				disconnectAudio.currentTime = 0;
			};
			btnConnect.onclick = function(){
				popup.style.display = 'none';
				alertActive = false;
				clearTimeout(audioTimeoutId);
				disconnectAudio.pause();
				disconnectAudio.currentTime = 0;
				openModal();
			};
			return popup;
		}

		function showAlertPopup(){
			if (alertActive) return;
			alertActive = true;
			var p = ensureAlertPopup();
			p.style.display = 'flex';
			tryPlayAudio();
		}

		function openModal(){
			var m = ensureModal();
			m.style.display = 'flex';
			try{
				var qrArea = document.getElementById('myd-whatsapp-qr-area');
				if (qrArea && typeof qrArea.reloadQr === 'function') qrArea.reloadQr();
			}catch(e){/* noop */}
		}

		widget.addEventListener('click', function(){ if (isDisconnected()) openModal(); });
		widget.addEventListener('keydown', function(e){ if ((e.key === 'Enter' || e.key === ' ') && isDisconnected()) { e.preventDefault(); openModal(); } });

		// update cursor when status changes
		function refreshStatusActions(){ 
			try{ 
				var disconnected = isDisconnected();
				if (disconnected) {
					widget.style.cursor = 'pointer'; 
					showAlertPopup();
				} else {
					widget.style.cursor = 'default'; 
					alertActive = false; // Reset flag if connected
					clearTimeout(audioTimeoutId);
					var p = document.getElementById('myd-whatsapp-alert-popup');
					if (p) {
						p.style.display = 'none';
						disconnectAudio.pause();
						disconnectAudio.currentTime = 0;
					}
				}
			} catch(e){} 
		}
		refreshStatusActions();
		if (statusEl){
			var mo = new MutationObserver(refreshStatusActions);
			mo.observe(statusEl, { childList:true, subtree:true, characterData:true });
		}
	})();

    function loadAccountData(){
        // Fetch user data via WP AJAX or assume it's available
        // For simplicity, use a placeholder or fetch from a REST endpoint
        fetch('/wp-json/wp/v2/users/me', { credentials: 'same-origin' }).then(function(r){
            if (!r.ok) return;
            return r.json();
        }).then(function(user){
            if (user) {
                document.getElementById('myd-account-name').value = user.name || '';
                document.getElementById('myd-account-email').value = user.email || '';
                document.getElementById('myd-account-role').value = user.roles ? user.roles.join(', ') : '';
            }
        }).catch(function(){ /* ignore */ });
    }


	// Alterado: exibir lista com switches (um por impressora), semelhante à imagem de referência
	printersList.innerHTML = '';

	// Inserir estilo para switch (apenas se ainda não existir)
	if (!document.getElementById('myd-printer-switch-style')){
		var style = document.createElement('style');
		style.id = 'myd-printer-switch-style';
			style.innerHTML = '\n.myprinter-list{list-style:none;padding:0;margin:0}\n.myprinter-item{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-bottom:1px solid #f0f0f0}\n.myprinter-name{font-size:15px;color:#222}\n.switch{position:relative;display:inline-block;width:44px;height:24px}\n.switch input{opacity:0;width:0;height:0}\n.slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:#ccc;transition:.2s;border-radius:24px}\n.slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;top:3px;background:white;transition:.2s;border-radius:50%}\n.switch input:checked + .slider{background:#ffae00}\n.switch input:checked + .slider:before{transform:translateX(20px)}\n.myd-copy-btn{padding:4px 8px;border-radius:4px;border:1px solid #ddd;background:#fff;cursor:pointer;font-size:13px;min-width:34px;text-align:center;color:#ccc}\n.myd-copy-btn:not(.active):hover{background:#f1f1f1; color: #ccc}\n.myd-copy-btn.active{background:#ffae00;color:#fff;}\n';
		document.head.appendChild(style);
	}

	function renderPrinters(printers){
		printersList.innerHTML = '';
		var ul = document.createElement('ul'); ul.className = 'myprinter-list';
		var saved = localStorage.getItem('myd-default-printer');
		printers.forEach(function(p){
			var name = p && p.name ? p.name : (typeof p === 'string' ? p : JSON.stringify(p));
			var li = document.createElement('li'); li.className = 'myprinter-item';
			var span = document.createElement('span'); span.className = 'myprinter-name'; span.textContent = name;
			var label = document.createElement('label'); label.className = 'switch';
			var input = document.createElement('input'); input.type = 'checkbox'; input.className = 'printer-toggle'; input.setAttribute('data-printer', name);
			var slider = document.createElement('span'); slider.className = 'slider';
			label.appendChild(input); label.appendChild(slider);
			li.appendChild(span); li.appendChild(label);
			ul.appendChild(li);

			// set initial state
			try{ if (saved && saved === name) input.checked = true; }catch(e){}

			input.addEventListener('change', function(){
				if (input.checked){
					// uncheck others
					Array.prototype.slice.call(document.querySelectorAll('.printer-toggle')).forEach(function(other){ if(other!==input) other.checked=false; });
					// mark as pending selection; do NOT persist until user clicks Salvar
					pendingPrinter = name;
					// update Save button visibility
					updateSaveButtonVisibility();
				} else {
					// if user unchecked the selection, clear pending if it was this
					if (pendingPrinter === name) pendingPrinter = null;
					// update Save button visibility
					updateSaveButtonVisibility();
				}
			});
		});
		printersList.appendChild(ul);
		// update save button visibility after rendering
		updateSaveButtonVisibility();
		// initialize copies UI according to saved value
		initCopiesUI();
	}

	function fetchPrinters(){
		loadingEl.style.display = 'inline';
		fetch('http://localhost:3420/printers', { method: 'GET' }).then(function(r){
			loadingEl.style.display = 'none';
			if (!r.ok) throw new Error('Não foi possível obter lista de impressoras');
			return r.json();
		}).then(function(j){
			if (!j || !j.printers) { printersList.textContent = 'Nenhuma impressora encontrada'; return; }
			renderPrinters(j.printers);
		}).catch(function(err){
			loadingEl.style.display = 'none';
			printersList.textContent = 'Erro: ' + (err && err.message ? err.message : 'falha na requisição');
		});
	}

	function updateSaveButtonVisibility(){
		if (!saveBtn) return;
		// determine current selection in DOM
		var checked = document.querySelector('.printer-toggle:checked');
		var sel = checked ? checked.getAttribute('data-printer') : null;
		// if selection differs from persisted value, show save
		var copiesSelected = null;
		var copyBtn = document.querySelector('.myd-copy-btn.active');
		if (copyBtn) copiesSelected = copyBtn.getAttribute('data-copies');
		// show save if either printer differs or copies differs
		if (sel !== currentSaved || (copiesSelected !== currentSavedCopies)) {
			saveBtn.style.display = 'inline-block';
		} else {
			saveBtn.style.display = 'none';
		}
	}

	function initCopiesUI(){
		var saved = currentSavedCopies;
		var buttons = document.querySelectorAll('.myd-copy-btn');
		buttons.forEach(function(b){ b.classList.remove('active'); });
		if (saved) {
			var btn = document.querySelector('.myd-copy-btn[data-copies="' + saved + '"]');
			if (btn) btn.classList.add('active');
		}
		// attach click handlers
		buttons.forEach(function(b){
			b.addEventListener('click', function(){
				buttons.forEach(function(x){ x.classList.remove('active'); });
				b.classList.add('active');
				pendingCopies = b.getAttribute('data-copies');
				updateSaveButtonVisibility();
			});
		});
	}

	// cria o popup de confirmação de salvamento
	function createSavePopup(){
		if (document.getElementById('myd-save-popup')) return;
		var wrap = document.createElement('div');
		wrap.id = 'myd-save-popup';
		wrap.style.cssText = 'display:none;position:fixed;left:0;top:0;right:0;bottom:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.4);z-index:150000;';
		var box = document.createElement('div');
		box.className = 'myd-save-popup-box';
		// position relative so we can place the button at the bottom-right inside the box
		box.style.cssText = 'position:relative;background:#fff;padding:20px 24px 54px;border-radius:8px;min-width:280px;max-width:90%;text-align:left;box-shadow:0 8px 24px rgba(0,0,0,0.2);transform:translateY(12px);opacity:0;transition:transform .25s ease, opacity .2s ease;';
		var title = document.createElement('div'); title.textContent = 'Configurações salvas!'; title.style.cssText = 'font-weight:600;margin-bottom:8px;font-size:16px;color:#222';
		var btn = document.createElement('button'); btn.textContent = 'Entendi'; btn.style.cssText = 'position:absolute;right:16px;bottom:12px;background:#ffae00;color:#fff;border:0;padding:8px 14px;border-radius:6px;cursor:pointer;font-size:14px';
		btn.addEventListener('click', function(e){ e.stopPropagation(); closeSavePopup(); });
		box.appendChild(title); box.appendChild(btn); wrap.appendChild(box);
		wrap.addEventListener('click', function(e){ if (e.target === wrap) closeSavePopup(); });
		document.body.appendChild(wrap);
	}

	function showSavePopup(){
		var el = document.getElementById('myd-save-popup');
		if (!el){ createSavePopup(); el = document.getElementById('myd-save-popup'); }
		el.style.display = 'flex';
		// force a reflow then animate box into place
		var box = el.querySelector('.myd-save-popup-box');
		if (box){
			requestAnimationFrame(function(){
				box.style.transform = 'translateY(0)';
				box.style.opacity = '1';
			});
		}
	}


	function closeSavePopup(){
		var el = document.getElementById('myd-save-popup');
		if (!el) return;
		var box = el.querySelector('.myd-save-popup-box');
		if (box){
			box.style.transform = 'translateY(12px)';
			box.style.opacity = '0';
		}
		// hide after transition
		setTimeout(function(){ if (el) el.style.display = 'none'; }, 260);
	}

	saveBtn.addEventListener('click', function(){
		// allow saving even when there is no printer selected
		var checked = document.querySelector('.printer-toggle:checked');
		var sel = null;
		if (pendingPrinter) sel = pendingPrinter;
		else if (checked) sel = checked.getAttribute('data-printer');
		try{
			if (sel) {
				localStorage.setItem('myd-default-printer', sel);
			} else {
				// explicit save of empty selection: remove stored value
				localStorage.removeItem('myd-default-printer');
			}
			// also persist copies
			var copiesToSave = pendingCopies !== null ? pendingCopies : currentSavedCopies;
			if (copiesToSave) {
				localStorage.setItem('myd-print-copies', copiesToSave);
			} else {
				localStorage.removeItem('myd-print-copies');
			}
			pendingPrinter = null;
			pendingCopies = null;
			// update persisted state and hide save button
			currentSaved = sel === null ? null : sel;
			currentSavedCopies = copiesToSave === null ? null : copiesToSave;
			updateSaveButtonVisibility();
			// mostrar popup de confirmação
			// Persistir também no config do Electron, se disponível
			if (window.electronAPI && typeof window.electronAPI.savePrinter === 'function') {
				try { window.electronAPI.savePrinter({ printer: sel || null, copies: copiesToSave || null }); } catch(e) { console.warn('electronAPI.savePrinter failed', e); }
			}
			showSavePopup();
		}catch(e){ alert('Erro ao salvar: '+(e&&e.message?e.message:e)); }
	});

	clearBtn.addEventListener('click', function(){
		try{ 
			localStorage.removeItem('myd-default-printer'); 
			localStorage.removeItem('myd-print-copies');
			Array.prototype.slice.call(document.querySelectorAll('.printer-toggle')).forEach(function(ch){ ch.checked=false; }); 
			Array.prototype.slice.call(document.querySelectorAll('.myd-copy-btn')).forEach(function(b){ b.classList.remove('active'); });
			pendingPrinter = null; currentSaved = null; pendingCopies = null; currentSavedCopies = null; updateSaveButtonVisibility(); 
			// Also clear Electron config if available
			if (window.electronAPI && typeof window.electronAPI.savePrinter === 'function') {
				try { window.electronAPI.savePrinter({ printer: null, copies: null }); } catch(e){ console.warn('electronAPI.savePrinter failed', e); }
			}
		}catch(e){ console.warn('Erro ao limpar impressora:', e); }
	});
});
</script>

<script>
// Health check de sessão do usuário (Polling 3s)
document.addEventListener('DOMContentLoaded', function() {
    // Evita loop contínuo caso já estejamos na tela de login
    if (document.body.classList.contains('login') || window.location.href.indexOf('wp-login.php') !== -1) return;
    
    setInterval(function() {
        var formData = new FormData();
        formData.append('action', 'myd_check_session');
        
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            // Se o request retornar falha ou indicar deslogado
            if (!resp.success && resp.data && resp.data.logged_in === false) {
                console.warn("Sessão expirada. Redirecionando para login...");
                if (window.MydGlobalNotify) {
                    window.MydGlobalNotify('error', 'Sessão Expirada', 'Você foi desconectado. Redirecionando...');
                }
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            }
        })
        .catch(function(e) {
            // Ignora falhas de rede pra não deslogar na primeira instabilidade
            console.warn("Falha no health check da sessão", e);
        });
    }, 60000);
});
</script>
