<?php

use MydPro\Includes\Fdm_svg;
use MydPro\Includes\Store_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_category = isset( $args['product_category'] ) && $args['product_category'] !== 'all' ? array( $args['product_category'] ) : array();
$currency_simbol = Store_Data::get_store_data( 'currency_simbol' );
?>
<?php
$user = is_user_logged_in() ? wp_get_current_user() : null;
$roles = $user ? (array) $user->roles : array();
$is_marketing = in_array('marketing', $roles, true);
?>
<?php if ( $is_marketing ) : ?>
<div class="myd-marketing-warning-wrap">
	<div class="myd-marketing-warning" id="myd-marketing-warning-btn" style="cursor:pointer;">
		<svg class="myd-marketing-warning-svg" fill="#ffffff" height="217px" width="217px" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="-2.07 -2.07 33.68 33.68" xml:space="preserve" stroke="#ffffff" stroke-width="0.00029536000000000005"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path d="M14.768,0C6.611,0,0,6.609,0,14.768c0,8.155,6.611,14.767,14.768,14.767s14.768-6.612,14.768-14.767 C29.535,6.609,22.924,0,14.768,0z M14.768,27.126c-6.828,0-12.361-5.532-12.361-12.359c0-6.828,5.533-12.362,12.361-12.362 c6.826,0,12.359,5.535,12.359,12.362C27.127,21.594,21.594,27.126,14.768,27.126z"></path> <path d="M14.385,19.337c-1.338,0-2.289,0.951-2.289,2.34c0,1.336,0.926,2.339,2.289,2.339c1.414,0,2.314-1.003,2.314-2.339 C16.672,20.288,15.771,19.337,14.385,19.337z"></path> <path d="M14.742,6.092c-1.824,0-3.34,0.513-4.293,1.053l0.875,2.804c0.668-0.462,1.697-0.772,2.545-0.772 c1.285,0.027,1.879,0.644,1.879,1.543c0,0.85-0.67,1.697-1.494,2.701c-1.156,1.364-1.594,2.701-1.516,4.012l0.025,0.669h3.42 v-0.463c-0.025-1.158,0.387-2.162,1.311-3.215c0.979-1.08,2.211-2.366,2.211-4.321C19.705,7.968,18.139,6.092,14.742,6.092z"></path> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> <g> </g> </g> </g></svg>
		<div>
		<span class="myd-marketing-warning-text">Você não pode fazer pedidos!</span>
		</div>
	</div>
</div>
<div class="myd-marketing-warning-spacer"></div>
<style>
#myd-marketing-modal {
	display: none;
	position: fixed;
	top: 0; left: 0;
	width: 100vw; height: 100vh;
	z-index: 3000;
	background: rgba(0,0,0,0.4);
	align-items: center;
	justify-content: center;
	transition: opacity 0.3s;
	opacity: 0;
}
#myd-marketing-modal.myd-modal-open {
	display: flex;
	opacity: 1;
}
#myd-marketing-modal .myd-modal-content {
	background: #fff;
	padding: 2rem 2.5rem;
	border-radius: 16px;
	max-width: 90vw;
	box-shadow: 0 2px 16px #0002;
	text-align: center;
	transform: scale(0.92);
	opacity: 0;
	transition: transform 0.3s, opacity 0.3s;
}
#myd-marketing-modal.myd-modal-open .myd-modal-content {
	transform: scale(1);
	opacity: 1;
}
</style>
<div id="myd-marketing-modal">
	<div class="myd-modal-content">
		<h2 style="color:#d32f2f;margin-bottom:1rem;">Atenção</h2>
		<p style="margin-bottom:1.5rem;">Você está usando uma conta com o cargo <b>Marketing</b>.<br>Para fazer pedidos, utilize uma conta de cliente.</p>
		<a href="<?php echo esc_url( wp_logout_url(home_url('/')) ); ?>" style="background:#d32f2f;color:#fff;padding:0.7em 2em;border:none;border-radius:8px;font-size:1.1em;cursor:pointer;text-decoration:none;">Deslogar</a>
		<br><br>
		<button id="myd-marketing-modal-close" class="myd-marketing-modal-close">Fechar</button>
	</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
	var btn = document.getElementById('myd-marketing-warning-btn');
	var modal = document.getElementById('myd-marketing-modal');
	var close = document.getElementById('myd-marketing-modal-close');
	if(btn && modal) {
		btn.addEventListener('click', function(){ 
			modal.style.display = 'flex';
			// Força reflow para garantir transição
			void modal.offsetWidth;
			modal.classList.add('myd-modal-open');
		});
	}
	if(close && modal) {
		close.addEventListener('click', function(){ 
			modal.classList.remove('myd-modal-open');
			setTimeout(function(){ modal.style.display = 'none'; }, 300);
		});
	}
	// Garante que display flex é aplicado ao abrir
	if(modal) {
		modal.addEventListener('transitionend', function(e){
			if(!modal.classList.contains('myd-modal-open')){
				modal.style.display = 'none';
			}
		});
	}
});
</script>
<?php endif; ?>
<?php if ( Store_Data::$template_dependencies_loaded === false ) : ?>
<script>
// Patch EventSource early to block redundant MercadoPago PIX SSE from other scripts.
(function(){
	try {
		var _ES = window.EventSource;
		if (!_ES) return;
		function PatchedEventSource(url) {
			try {
				if (typeof url === 'string' && url.indexOf('/mercadopago/pix/sse') !== -1) {
					console.info('[MYD] Bloqueado EventSource para', url);
					// return minimal compatible no-op implementation
					var noop = function(){};
					return {
						addEventListener: noop,
						removeEventListener: noop,
						close: noop,
						onopen: null,
						onmessage: null,
						onerror: null
					};
				}
			} catch(e) { /* ignore and fallback */ }
			return new _ES(url);
		}
		PatchedEventSource.prototype = _ES.prototype;
		window.EventSource = PatchedEventSource;
	} catch(e) { console.warn('[MYD] Patch EventSource falhou', e); }
})();

</script>
<!-- MercadoPago.JS V2 SDK -->
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script src="assets/mercadopago.js"></script>
	<script src="https://www.mercadopago.com/v2/security.js" view="checkout" output="deviceId"></script>

<?php
// Barra de Fidelidade: exibe acima do my-delivery-wrap quando ativado
$myd_fidelidade_ativo = get_option( 'myd_fidelidade_ativo', 'off' );
if ( $myd_fidelidade_ativo === 'on' ) :
	$myd_fidelidade_tipo = get_option( 'myd_fidelidade_tipo', 'loyalty_value' );
	$myd_fidelidade_valor = get_option( 'myd_fidelidade_valor', '' );
	$myd_fidelidade_quantidade = intval( get_option( 'myd_fidelidade_quantidade', 0 ) );
	$myd_fidelidade_premio_tipo = get_option( 'myd_fidelidade_premio_tipo', 'percent' );
	$myd_fidelidade_premio_percent = get_option( 'myd_fidelidade_premio_percent', '' );
	$myd_fidelidade_premio_fixo = get_option( 'myd_fidelidade_premio_fixo', '' );
	$myd_fidelidade_pontos_necessarios = intval( get_option( 'myd_fidelidade_pontos_necessarios', 0 ) );

	// Helper para formatar moeda
	if ( ! function_exists( 'myd_format_currency' ) ) {
		function myd_format_currency( $v ) {
			$v = str_replace( array( 'R$', ' ' ), '', $v );
			$v = str_replace( ',', '.', str_replace( '.', '', $v ) );
			$num = floatval( $v );
			return number_format( $num, 2, ',', '.' );
		}
	}

	$is_logged = is_user_logged_in();
	$progress_percent = 0;
	$amount_left = 0;
	$orders_count = 0;
	$sum = 0.0;

	if ( $is_logged ) {
		$user_id = get_current_user_id();
			$last_reset = get_user_meta( $user_id, 'myd_loyalty_reset_at', true );
			// Pontos expirados: se houver um expires_at e ele já passou, considerar pontos como 0
			$expires_at = get_user_meta( $user_id, 'myd_loyalty_expires_at', true );
		if ( $myd_fidelidade_tipo === 'loyalty_value' ) {
			$orders = get_posts( array(
				'post_type' => 'mydelivery-orders',
				'posts_per_page' => -1,
				'post_status' => 'publish',
				'meta_query' => array(
					array('key' => 'myd_customer_id','value' => $user_id,'compare' => '='),
					array('key' => 'order_status','value' => 'draft','compare' => '!=')
				)
			) );
			foreach ( $orders as $o ) {
				// Ignorar pedidos cancelados e anteriores ao último resgate
				$order_status_meta = get_post_meta( $o->ID, 'order_status', true );
				if ( $order_status_meta === 'canceled' ) continue;
				if ( ! empty( $last_reset ) ) {
					$ot = strtotime( $o->post_date );
					$rt = strtotime( $last_reset );
					if ( $ot <= $rt ) continue;
				}
				// Prefer subtotal meta when summing progress (exclude delivery fee)
				$total = get_post_meta( $o->ID, 'order_subtotal', true );
				if ( empty( $total ) ) $total = get_post_meta( $o->ID, 'myd_order_subtotal', true );
				if ( empty( $total ) ) {
					// fallback: use order_total minus delivery price
					$total_val = get_post_meta( $o->ID, 'order_total', true );
					if ( empty( $total_val ) ) $total_val = get_post_meta( $o->ID, 'myd_order_total', true );
					$delivery_val = get_post_meta( $o->ID, 'order_delivery_price', true );
					$delivery_num = floatval( str_replace( ',', '.', str_replace( '.', '', (string) $delivery_val ) ) );
					$total_num = floatval( str_replace( ',', '.', str_replace( '.', '', (string) $total_val ) ) );
					$total = number_format( max(0, $total_num - $delivery_num), 2, '.', '' );
				}
				$total = str_replace( array( 'R$', ' ' ), '', $total );
				$total = str_replace( ',', '.', str_replace( '.', '', $total ) );
				$sum += floatval( $total );
			}
			$target_raw = $myd_fidelidade_valor;
			$target = 0.0;
			if ( ! empty( $target_raw ) ) {
				$t = str_replace( array( 'R$', ' ' ), '', $target_raw );
				$t = str_replace( ',', '.', str_replace( '.', '', $t ) );
				$target = floatval( $t );
			}
			if ( $target > 0 ) {
				// se pontos configurados, calcule pontos por pedidos >= target
					if ( $myd_fidelidade_pontos_necessarios > 0 ) {
						// pontos agora persistidos no banco (usermeta)
						$points_count = intval( get_user_meta( $user_id, 'myd_loyalty_points', true ) );
						if ( ! empty( $expires_at ) ) {
							$exp_ts = strtotime( $expires_at );
							if ( $exp_ts !== false && $exp_ts <= (int) current_time( 'timestamp' ) ) {
								$points_count = 0;
							}
						}
					// percentuele não é usado para pontos, mas mantemos amount_left para compatibilidade
					$progress_percent = min( 100, (int) round( ( $sum / $target ) * 100 ) );
					$amount_left = max( 0, $target - $sum );
				} else {
					$progress_percent = min( 100, (int) round( ( $sum / $target ) * 100 ) );
					$amount_left = max( 0, $target - $sum );
				}
			}
		} else {
			// quantidade de pontos (modo por quantidade) também é mantida em usermeta
			$orders_count = intval( get_user_meta( $user_id, 'myd_loyalty_points', true ) );
		}
	}
	?>
	<link rel="stylesheet" href="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/css/panel-inline-extra.css' ); ?>" />
	

	<div class="myd-loyalty-bar" id="myd-loyalty-bar">
		<?php if ( $is_logged ) : ?>
			<?php
				// Apenas loyalty_value: exibir slots/pontos ou progresso baseado em valor configurado
				if ( $myd_fidelidade_pontos_necessarios > 0 ) {
					$points_needed = $myd_fidelidade_pontos_necessarios;
					$points_count = isset( $points_count ) ? intval( $points_count ) : 0;
					// cabeçalho: título + contador
					echo '<div class="myd-loyalty-header" style="display:flex;justify-content:space-between;align-items:center;">';
					echo '<div class="myd-loyalty-title" style="font-weight:700;">Cartão de fidelidade</div>';
					echo '<div class="myd-loyalty-count" style="white-space:nowrap;font-size:13px;color:#666;">' . $points_count . ' de ' . $points_needed . ' pontos ';
					echo '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle; width:14px; height:14px;"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g clip-path="url(#666clip0_429_11254)"> <path d="M10 17L15 12" stroke="#666" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M15 12L10 7" stroke="#666" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path> </g> <defs> <clipPath id="clip0_429_11254"> <rect width="24" height="24" fill="white"></rect> </clipPath> </defs> </g></svg>';
					echo '</div>';
					echo '</div>';
					// slots
					echo '<div class="myd-loyalty-quantity-wrapper" style="display:flex;align-items:center;gap:12px;margin-top:8px;">';
					echo '<div class="myd-loyalty-slots" style="flex:1;display:flex;gap:6px;align-items:center;">';
					for ( $i = 1; $i <= $points_needed; $i++ ) {
						$filled = ( $i <= $points_count ) ? ' myd-loyalty-slot--filled' : '';
						echo '<div class="myd-loyalty-slot' . $filled . '" aria-hidden="true"></div>';
					}
					$gift_filled = ( $points_count >= $points_needed ) ? ' myd-loyalty-gift--filled' : '';
					echo '</div>'; // slots
					echo '<div class="myd-loyalty-gift-wrap" style="margin-left:10px;">';
					echo '<div class="myd-loyalty-gift' . $gift_filled . '" aria-hidden="true">🎁</div>';
					echo '</div>';
					echo '</div>';
					if ( $points_count >= $points_needed ) {
						echo '<div style="margin-top:8px"><strong>Parabéns! Você já pode resgatar o prêmio.</strong></div>';
					}
				} else {
					$target_display = $myd_fidelidade_valor ? 'R$ ' . myd_format_currency( $myd_fidelidade_valor ) : '';
					echo '<div>Cartão de fidelidade: você acumulou <strong>R$ ' . number_format( $sum, 2, ',', '.' ) . '</strong> de <strong>' . $target_display . '</strong>.</div>';
					echo '<div class="myd-loyalty-progress"><div class="myd-loyalty-progress__fill" style="width:' . esc_attr( $progress_percent ) . '%"></div></div>';
					if ( $amount_left <= 0 ) {
						echo '<div><strong>Parabéns! Você já atingiu o valor para receber o prêmio.</strong></div>';
					} else {
						echo '<div>Faltam <strong>R$ ' . number_format( $amount_left, 2, ',', '.' ) . '</strong> para receber o prêmio.</div>';
					}
				}
			?>
		<?php else : ?>
			<?php
				$prize_percent_clean = rtrim( trim( $myd_fidelidade_premio_percent ), '%' );
				$prize_text = $myd_fidelidade_premio_tipo === 'percent' ? ( $prize_percent_clean . '%' ) : ( 'R$ ' . myd_format_currency( $myd_fidelidade_premio_fixo ) );
				if ( intval( $myd_fidelidade_pontos_necessarios ) > 0 ) {
					echo '<div><strong>Cartão de fidelidade:</strong> Acumule <strong>' . intval( $myd_fidelidade_pontos_necessarios ) . ' pontos</strong> e ganhe um vale compra de <strong>' . esc_html( $prize_text ) . '</strong> de desconto.</div>';
				} else {
					echo '<div><strong>Cartão de fidelidade:</strong> Acumule <strong>R$ ' . myd_format_currency( $myd_fidelidade_valor ) . '</strong> em pedidos e ganhe um vale compra de <strong>' . esc_html( $prize_text ) . '</strong> de desconto.</div>';
				}
			?>
		<?php endif; ?>
	</div>

	<!-- Loyalty Modal (hidden by default) -->
	<div id="myd-loyalty-modal" class="myd-loyalty-modal" aria-hidden="true" role="dialog" aria-modal="true" style="display:none;">
		<div class="myd-loyalty-modal__overlay" id="myd-loyalty-modal-overlay"></div>
		<div class="myd-loyalty-modal__content" role="document" aria-labelledby="myd-loyalty-modal-title">
			<div class="myd-loyalty-modal__header">
				<span id="myd-loyalty-modal-title" class="myd-loyalty-modal__title">Programa de fidelidade</span>
				<button type="button" class="myd-loyalty-modal__close" id="myd-loyalty-modal-close" aria-label="Fechar modal">
					<svg fill="currentColor" viewBox="-3.5 0 19 19" xmlns="http://www.w3.org/2000/svg" class="cf-icon-svg" aria-hidden="true"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M11.383 13.644A1.03 1.03 0 0 1 9.928 15.1L6 11.172 2.072 15.1a1.03 1.03 0 1 1-1.455-1.456l3.928-3.928L.617 5.79a1.03 1.03 0 1 1 1.455-1.456L6 8.261l3.928-3.928a1.03 1.03 0 0 1 1.455 1.456L7.455 9.716z"></path></g></svg>
				</button>
			</div>

			<div id="myd-loyalty-modal-body" style="margin-top:12px; pointer-events:none;"></div>

			<?php
				// Mensagem explicativa abaixo do cabeçalho do modal
				$needed_points = 0;
				if ( intval( $myd_fidelidade_pontos_necessarios ) > 0 ) {
					$needed_points = intval( $myd_fidelidade_pontos_necessarios );
				} else {
					$needed_points = max(1, intval( $myd_fidelidade_quantidade ) );
				}
				if ( $myd_fidelidade_premio_tipo === 'percent' ) {
					$prize_percent_clean = rtrim( trim( $myd_fidelidade_premio_percent ), '%' );
					$prize_display = $prize_percent_clean . '%';
				} else {
					$prize_display = $myd_fidelidade_premio_fixo ? 'R$ ' . myd_format_currency( $myd_fidelidade_premio_fixo ) : '';
				}
				$per_point_display = $myd_fidelidade_valor ? 'R$ ' . myd_format_currency( $myd_fidelidade_valor ) : '';
			?>
	
			<?php
				// Mensagem explanatória abaixo da barra (fora do modal)
				$needed_points = 0;
				if ( intval( $myd_fidelidade_pontos_necessarios ) > 0 ) {
					$needed_points = intval( $myd_fidelidade_pontos_necessarios );
				} else {
					$needed_points = max(1, intval( $myd_fidelidade_quantidade ) );
				}
				if ( $myd_fidelidade_premio_tipo === 'percent' ) {
					$prize_percent_clean = rtrim( trim( $myd_fidelidade_premio_percent ), '%' );
					$prize_display = $prize_percent_clean . '%';
				} else {
					$prize_display = $myd_fidelidade_premio_fixo ? 'R$ ' . myd_format_currency( $myd_fidelidade_premio_fixo ) : '';
				}
				$per_point_display = $myd_fidelidade_valor ? 'R$ ' . myd_format_currency( $myd_fidelidade_valor ) : '';
			?>

			<div class="myd-loyalty-desc">
				<?php
					if ( $is_logged ) {
						$expires_meta = get_user_meta( $user_id, 'myd_loyalty_expires_at', true );
						if ( ! empty( $expires_meta ) ) {
							$exp_ts = strtotime( $expires_meta );
							$now_ts = (int) current_time( 'timestamp' );
							if ( $exp_ts !== false && $exp_ts > $now_ts ) {
								$exp_date = date_i18n( get_option( 'date_format' ), $exp_ts );
								$exp_time = date_i18n( get_option( 'time_format' ), $exp_ts );
								printf( 'Seus pontos expiram em <strong>%s</strong> às <strong>%s</strong>.', esc_html( $exp_date ), esc_html( $exp_time ) );
							}
						}
					}
				?>
				<br/>
				Acumule <strong><?php echo esc_html( $needed_points ); ?></strong> pontos para ganhar <strong><?php echo esc_html( $prize_display ); ?></strong> de desconto no próximo pedido.
				<br/>
				Você recebe 1 ponto a cada <strong><?php echo esc_html( $per_point_display ); ?></strong> em compras na loja.
			</div>
			<h4 class="label-large-medium txt-fidelidade">Regras</h4>
			<ul class="myd-loyalty-rules">
                <li>
                    <span class="body-medium-regular">O vale compra é adicionado automaticamente ao seu pedido na página de pagamento.</span>
                </li>
                <li>
                    <span class="body-medium-regular">Se o valor do vale compra ultrapassar o valor do pedido o excedente será perdido.</span>
                </li>
                <li>
                    <span class="body-medium-regular">Válido somente pelos pedidos efetuados pelo site.</span>
                </li>
                <li>
                    <span class="body-medium-regular">A taxa de entrega não é considerada no valor do pedido.</span>
                </li>
				<li>
					<?php
						$f_exp = get_option( 'myd_fidelidade_expiracao', 'never' );
						if ( $f_exp === 'never' ) {
							$exp_text = __( 'Nunca', 'myd-delivery-pro' );
						} else {
							$exp_text = intval( $f_exp ) . ' dias';
						}
					?>
					<span class="body-medium-regular">Os pontos de fidelidade expiram após <?php echo esc_html( $exp_text ); ?>.</span>
				</li>
				<li>
					<span class="body-medium-regular">Pedidos cancelados não contabilizam pontos de fidelidade.</span>
				</li>
				<li>
					<span class="body-medium-regular">A administração da loja pode alterar ou encerrar o programa de fidelidade a qualquer momento.</span>
				</li>
				<li>
					<span class="body-medium-regular">Os pontos de fidelidade não são transferíveis entre usuários.</span>
				</li>
				<li>
					<span class="body-medium-regular">Os pontos de fidelidade não podem ser convertidos em dinheiro.</span>
				</li>
				<li>
					<span class="body-medium-regular">O tempo de expiração dos pontos de fidelidade é resetado a cada novo pedido confirmado.</span>
				</li>
            </ul>

		
		</div>
	</div>
	<!-- modal CSS moved to assets/css/panel-inline-extra.css -->

	<script>
	(function(){
		function openModal(){
			var modal = document.getElementById('myd-loyalty-modal');
			var body = document.getElementById('myd-loyalty-modal-body');
			var bar = document.getElementById('myd-loyalty-bar');
			if(!modal || !body || !bar) return;
			// Clone bar content into modal body for full details
			body.innerHTML = '';
			var clone = bar.cloneNode(true);
			// remove id to avoid duplicates
			clone.removeAttribute('id');
			body.appendChild(clone);
			modal.style.display = 'flex';
			modal.setAttribute('aria-hidden','false');
			// trap focus briefly
			document.body.style.overflow = 'hidden';
		}
		function closeModal(){
			var modal = document.getElementById('myd-loyalty-modal');
			if(!modal) return;
			modal.style.display = 'none';
			modal.setAttribute('aria-hidden','true');
			document.body.style.overflow = ''; 
		}
		document.addEventListener('DOMContentLoaded', function(){
			var bar = document.getElementById('myd-loyalty-bar');
			if(!bar) return;
			bar.addEventListener('click', function(e){
				openModal();
			});
			var overlay = document.getElementById('myd-loyalty-modal-overlay');
			var closeBtn = document.getElementById('myd-loyalty-modal-close');
			if(overlay) overlay.addEventListener('click', closeModal);
			if(closeBtn) closeBtn.addEventListener('click', closeModal);
			document.addEventListener('keydown', function(ev){ if(ev.key==='Escape') closeModal(); });
		});
	})();
	</script>
<?php endif; ?>
	<section class="my-delivery-wrap">
		<div class="fdm-lightbox-image myd-hide-element" id="myd-image-preview-popup">
			<div class="fdm-lightbox-image-close" id="myd-image-preview-popup-close">
				<?php echo Fdm_svg::svg_close(); ?>
			</div>
			<div class="fdm-lightbox-image-link" id="myd-image-preview-wrapper">
				<img id="myd-image-preview-image" src="">
			</div>
		</div>

		<div class="myd-popup-notification" id="myd-popup-notification">
			<div class="myd-popup-notification__message" id="myd-popup-notification__message"></div>
		</div>

<script>
// Global left notification helper (single implementation)
(function(){
	if (!window.Myd) window.Myd = {};
	if (window.Myd.showLeftNotification) return; // already defined
	window.Myd.showLeftNotification = function(msg, svgHtml, type){
		try{
			var wrap = document.querySelector('.myd-left-toast-wrapper');
			if (!wrap){ wrap = document.createElement('div'); wrap.className = 'myd-left-toast-wrapper'; document.body.appendChild(wrap); }
			var el = document.createElement('div'); el.className = 'myd-left-toast ' + (type==='error'?'error':'success');
			if (svgHtml){
				var iconWrap = document.createElement('span'); iconWrap.className = 'myd-left-toast__icon';
				try { iconWrap.innerHTML = svgHtml; } catch(_){ iconWrap.textContent = ''; }
				el.appendChild(iconWrap);
			}
			var textWrap = document.createElement('span'); textWrap.className = 'myd-left-toast__text'; textWrap.textContent = msg || '';
			el.appendChild(textWrap);
			wrap.appendChild(el);
			requestAnimationFrame(function(){ el.classList.add('show'); });
			setTimeout(function(){ try{ el.classList.remove('show'); setTimeout(function(){ try{ if (el && el.parentNode) el.parentNode.removeChild(el); }catch(_){ } }, 240); }catch(_){ } }, 2400);
		}catch(_e){}
	};
})();
</script>


		<div class="myd-content">
			<?php if ( ! isset( $args['filter_type'] ) || isset( $args['filter_type'] ) && $args['filter_type'] !== 'hide' ) : ?>
				<div class="myd-content-filter">
					<?php if ( ! isset( $args['filter_type'] ) || isset( $args['filter_type'] ) && $args['filter_type'] !== 'hide_filter' ) : ?>
						<div class="myd-content-filter__categories">
							<?php foreach( $this->get_categories() as $v ) : ?>
								<div class="myd-content-filter__tag" data-anchor="<?php echo str_replace( ' ', '-', esc_attr( $v ) ); ?>"><?php echo esc_html( $v ); ?></div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<section class="myd-products__wrapper">
	<?php echo $this->fdm_loop_products_per_categorie( $product_category ); ?>
</section>

<?php if ( Store_Data::$template_dependencies_loaded === false ) : ?>
	<?php if ( ! $is_marketing ) : ?>
	<section class="myd-float">
		<div class="myd-float__button-subtotal">
			<span
				id="myd-float__price"
				data-currency="<?php echo \esc_attr( $currency_simbol ); ?>">
				<?php echo \esc_html( $currency_simbol ); ?>
			</span>
			<span id="myd_float__separator">&bull;</span>
			<span id="myd-float__qty">0</span>
			<span id="myd-float__qty-text">
				<?php esc_html_e( 'items', 'myd-delivery-pro' ); ?>
			</span>
		</div>

		<div class="myd-float__title">
			<?php esc_html_e( 'View Bag', 'myd-delivery-pro' ); ?>
			<svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 2.75C10.7574 2.75 9.75006 3.75736 9.75006 5V5.25447C10.1676 5.24999 10.6183 5.25 11.1053 5.25H12.8948C13.3819 5.25 13.8326 5.24999 14.2501 5.25447V5C14.2501 3.75736 13.2427 2.75 12.0001 2.75ZM15.7501 5.30694V5C15.7501 2.92893 14.0711 1.25 12.0001 1.25C9.929 1.25 8.25006 2.92893 8.25006 5V5.30694C8.11506 5.31679 7.98479 5.32834 7.85904 5.34189C6.98068 5.43657 6.24614 5.63489 5.59385 6.08197C5.3695 6.23574 5.15877 6.40849 4.96399 6.59833C4.39766 7.15027 4.05914 7.83166 3.79405 8.67439C3.53667 9.49258 3.32867 10.5327 3.06729 11.8396L3.04822 11.935C2.67158 13.8181 2.37478 15.302 2.28954 16.484C2.20244 17.6916 2.32415 18.7075 2.89619 19.588C3.08705 19.8817 3.30982 20.1534 3.56044 20.3982C4.31157 21.1318 5.28392 21.4504 6.48518 21.6018C7.66087 21.75 9.17418 21.75 11.0946 21.75H12.9055C14.826 21.75 16.3393 21.75 17.5149 21.6018C18.7162 21.4504 19.6886 21.1318 20.4397 20.3982C20.6903 20.1534 20.9131 19.8817 21.1039 19.588C21.676 18.7075 21.7977 17.6916 21.7106 16.484C21.6254 15.3021 21.3286 13.8182 20.9519 11.9351L20.9328 11.8396C20.6715 10.5327 20.4635 9.49259 20.2061 8.67439C19.941 7.83166 19.6025 7.15027 19.0361 6.59833C18.8414 6.40849 18.6306 6.23574 18.4063 6.08197C17.754 5.63489 17.0194 5.43657 16.1411 5.34189C16.0153 5.32834 15.8851 5.31679 15.7501 5.30694ZM8.01978 6.83326C7.27307 6.91374 6.81176 7.06572 6.44188 7.31924C6.28838 7.42445 6.1442 7.54265 6.01093 7.67254C5.68979 7.98552 5.45028 8.40807 5.22492 9.12449C4.99463 9.85661 4.80147 10.8172 4.52967 12.1762C4.14013 14.1239 3.8633 15.5153 3.78565 16.5919C3.70906 17.6538 3.83838 18.2849 4.15401 18.7707C4.2846 18.9717 4.43702 19.1576 4.60849 19.3251C5.02293 19.7298 5.61646 19.9804 6.67278 20.1136C7.74368 20.2486 9.1623 20.25 11.1486 20.25H12.8515C14.8378 20.25 16.2564 20.2486 17.3273 20.1136C18.3837 19.9804 18.9772 19.7298 19.3916 19.3251C19.5631 19.1576 19.7155 18.9717 19.8461 18.7707C20.1617 18.2849 20.2911 17.6538 20.2145 16.5919C20.1368 15.5153 19.86 14.1239 19.4705 12.1762C19.1987 10.8173 19.0055 9.85661 18.7752 9.12449C18.5498 8.40807 18.3103 7.98552 17.9892 7.67254C17.8559 7.54265 17.7118 7.42445 17.5582 7.31924C17.1884 7.06572 16.7271 6.91374 15.9803 6.83326C15.2173 6.75101 14.2374 6.75 12.8515 6.75H11.1486C9.76271 6.75 8.78285 6.75101 8.01978 6.83326ZM8.92103 14.2929C9.31156 14.1548 9.74006 14.3595 9.87809 14.7501C10.1873 15.625 11.0218 16.25 12.0003 16.25C12.9787 16.25 13.8132 15.625 14.1224 14.7501C14.2605 14.3595 14.6889 14.1548 15.0795 14.2929C15.47 14.4309 15.6747 14.8594 15.5367 15.2499C15.0222 16.7054 13.6342 17.75 12.0003 17.75C10.3663 17.75 8.97827 16.7054 8.46383 15.2499C8.3258 14.8594 8.53049 14.4309 8.92103 14.2929Z" fill="#fff"/>
			</svg>
		</div>
	</section>
	<?php endif; // !is_marketing ?>
	<!-- Barra de perfil fixa abaixo do myd-float -->
	<?php if ( ! $is_marketing ) : ?>
		<link rel="stylesheet" href="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/css/profile-bar-modal.css' ); ?>" />
	   <div class="myd-profile-bar">
		   <div class="myd-profile-bar__button" id="myd-profile-bar__button" style="display: flex; flex-direction: column; align-items: center; cursor: pointer;">
			   <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff" width="20" height="20"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
			   <span>Perfil</span>
		 </div>
	 <?php endif; // !is_marketing ?>

		   <!-- Botão Meus Pedidos ao lado do Perfil -->
		   <div class="myd-profile-bar__button" id="myd-profile-bar__orders-button" style="display: flex; flex-direction: column; align-items: center; cursor: pointer; margin-left:10px;">
			  <!-- reuse similar svg as in modal menu -->
			  <svg height="20px" width="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.58579 4.58579C5 5.17157 5 6.11438 5 8V17C5 18.8856 5 19.8284 5.58579 20.4142C6.17157 21 7.11438 21 9 21H15C16.8856 21 17.8284 21 18.4142 20.4142C19 19.8284 19 18.8856 19 17V8C19 6.11438 19 5.17157 18.4142 4.58579C17.8284 4 16.8856 4 15 4H9C7.11438 4 6.17157 4 5.58579 4.58579ZM9 8C8.44772 8 8 8.44772 8 9C8 9.55228 8.44772 10 9 10H15C15.5523 10 16 9.55228 16 9C16 8.44772 15.5523 8 15 8H9ZM9 12C8.44772 12 8 12.4477 8 13C8 13.5523 8.44772 14 9 14H15C15.5523 14 16 13.55228 16 13C16 12.4477 15.5523 12 15 12H9ZM9 16C8.44772 16 8 16.4477 8 17C8 17.5523 8.44772 18 9 18H13C13.5523 18 14 17.5523 14 17C14 16.4477 13.5523 16 13 16H9Z" fill="#ffffff"></path>
			  </svg>
			  <span style="font-size:12px;">Pedidos</span>
			  <span class="myd-profile-badge" aria-hidden="true">1</span>
		   </div>
	   </div>

	<section class="myd-checkout" id="myd-checkout">
		<div class="myd-cart" id="myd-cart">
			<div class="myd-cart__nav">
				<div class="myd-cart__nav-back">
					<?php echo Fdm_svg::nav_arrow_left(); ?>
				</div>

				<div
					class="myd-cart__nav-bag myd-cart__nav--active"
					data-tab-content="myd-cart__products"
					data-back="none"
					data-next="myd-cart__nav-shipping"
				>
					<svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 2.75C10.7574 2.75 9.75006 3.75736 9.75006 5V5.25447C10.1676 5.24999 10.6183 5.25 11.1053 5.25H12.8948C13.3819 5.25 13.8326 5.24999 14.2501 5.25447V5C14.2501 3.75736 13.2427 2.75 12.0001 2.75ZM15.7501 5.30694V5C15.7501 2.92893 14.0711 1.25 12.0001 1.25C9.929 1.25 8.25006 2.92893 8.25006 5V5.30694C8.11506 5.31679 7.98479 5.32834 7.85904 5.34189C6.98068 5.43657 6.24614 5.63489 5.59385 6.08197C5.3695 6.23574 5.15877 6.40849 4.96399 6.59833C4.39766 7.15027 4.05914 7.83166 3.79405 8.67439C3.53667 9.49258 3.32867 10.5327 3.06729 11.8396L3.04822 11.935C2.67158 13.8181 2.37478 15.302 2.28954 16.484C2.20244 17.6916 2.32415 18.7075 2.89619 19.588C3.08705 19.8817 3.30982 20.1534 3.56044 20.3982C4.31157 21.1318 5.28392 21.4504 6.48518 21.6018C7.66087 21.75 9.17418 21.75 11.0946 21.75H12.9055C14.826 21.75 16.3393 21.75 17.5149 21.6018C18.7162 21.4504 19.6886 21.1318 20.4397 20.3982C20.6903 20.1534 20.9131 19.8817 21.1039 19.588C21.676 18.7075 21.7977 17.6916 21.7106 16.484C21.6254 15.3021 21.3286 13.8182 20.9519 11.9351L20.9328 11.8396C20.6715 10.5327 20.4635 9.49259 20.2061 8.67439C19.941 7.83166 19.6025 7.15027 19.0361 6.59833C18.8414 6.40849 18.6306 6.23574 18.4063 6.08197C17.754 5.63489 17.0194 5.43657 16.1411 5.34189C16.0153 5.32834 15.8851 5.31679 15.7501 5.30694ZM8.01978 6.83326C7.27307 6.91374 6.81176 7.06572 6.44188 7.31924C6.28838 7.42445 6.1442 7.54265 6.01093 7.67254C5.68979 7.98552 5.45028 8.40807 5.22492 9.12449C4.99463 9.85661 4.80147 10.8172 4.52967 12.1762C4.14013 14.1239 3.8633 15.5153 3.78565 16.5919C3.70906 17.6538 3.83838 18.2849 4.15401 18.7707C4.2846 18.9717 4.43702 19.1576 4.60849 19.3251C5.02293 19.7298 5.61646 19.9804 6.67278 20.1136C7.74368 20.2486 9.1623 20.25 11.1486 20.25H12.8515C14.8378 20.25 16.2564 20.2486 17.3273 20.1136C18.3837 19.9804 18.9772 19.7298 19.3916 19.3251C19.5631 19.1576 19.7155 18.9717 19.8461 18.7707C20.1617 18.2849 20.2911 17.6538 20.2145 16.5919C20.1368 15.5153 19.86 14.1239 19.4705 12.1762C19.1987 10.8173 19.0055 9.85661 18.7752 9.12449C18.5498 8.40807 18.3103 7.98552 17.9892 7.67254C17.8559 7.54265 17.7118 7.42445 17.5582 7.31924C17.1884 7.06572 16.7271 6.91374 15.9803 6.83326C15.2173 6.75101 14.2374 6.75 12.8515 6.75H11.1486C9.76271 6.75 8.78285 6.75101 8.01978 6.83326ZM8.92103 14.2929C9.31156 14.1548 9.74006 14.3595 9.87809 14.7501C10.1873 15.625 11.0218 16.25 12.0003 16.25C12.9787 16.25 13.8132 15.625 14.1224 14.7501C14.2605 14.3595 14.6889 14.1548 15.0795 14.2929C15.47 14.4309 15.6747 14.8594 15.5367 15.2499C15.0222 16.7054 13.6342 17.75 12.0003 17.75C10.3663 17.75 8.97827 16.7054 8.46383 15.2499C8.3258 14.8594 8.53049 14.4309 8.92103 14.2929Z"/>
					</svg>

					<div class="myd-cart__nav-desc">
						<?php esc_html_e( 'Bag', 'myd-delivery-pro' ); ?>
					</div>
				</div>

				<div
					class="myd-cart__nav-shipping"
					data-tab-content="myd-cart__checkout"
					data-back="myd-cart__nav-bag"
					data-next="myd-cart__nav-payment"
				>
					<svg width="24px" height="24px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
						<path d="M22 6H2a1.001 1.001 0 0 0-1 1v3a1.001 1.001 0 0 0 1 1h20a1.001 1.001 0 0 0 1-1V7a1.001 1.001 0 0 0-1-1zm0 4H2V7h20v3h.001M22 17H2a1.001 1.001 0 0 0-1 1v3a1.001 1.001 0 0 0 1 1h20a1.001 1.001 0 0 0 1-1v-3a1.001 1.001 0 0 0-1-1zm0 4H2v-3h20v3h.001M10 14v1H2v-1zM2 3h8v1H2z"/><path fill="none" d="M0 0h24v24H0z"/>
					</svg>

					<div class="myd-cart__nav-desc">
						<?php esc_html_e( 'Checkout', 'myd-delivery-pro' ); ?>
					</div>
				</div>

					<div
						class="myd-cart__nav-payment"
						data-tab-content="myd-cart__payment"
						data-back="myd-cart__nav-shipping"
						data-next="myd-cart__finished"
					>
						<svg width="24px" height="24px" viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg">
							<g id="layer1">
							<path d="M 12.964844 0.095703125 C 12.805889 0.093483625 12.654204 0.10575226 12.503906 0.12890625 C 11.902715 0.22152215 11.399294 0.43880053 10.416016 0.60742188 C 9.5128573 0.76230303 8.9000581 0.53804687 8.1347656 0.34375 C 7.3694731 0.14945313 6.4403485 0.025497315 5.2929688 0.54492188 C 5.0125471 0.67254789 4.9131349 1.0209548 5.0839844 1.2773438 L 6.8574219 3.9355469 C 6.4799034 4.2948616 5.386098 5.3589005 4.0996094 7.0742188 C 2.5695621 9.1142816 1 11.799685 1 14.5 C 1 17.150236 2.3087845 18.664286 4.0703125 19.341797 C 5.8318405 20.019308 8 20 10 20 C 12 20 14.168159 20.01931 15.929688 19.341797 C 17.691216 18.664286 19 17.150236 19 14.5 C 19 11.799685 17.430438 9.1142814 15.900391 7.0742188 C 14.613901 5.3589005 13.520096 4.2948616 13.142578 3.9355469 L 14.916016 1.2773438 C 15.088927 1.0174273 14.984039 0.66436818 14.697266 0.54101562 C 13.978672 0.23310127 13.441708 0.10236154 12.964844 0.095703125 z M 12.65625 1.1171875 C 12.922777 1.0761275 13.330981 1.236312 13.679688 1.3300781 L 12.232422 3.5 L 7.7675781 3.5 L 6.3046875 1.3046875 C 6.8796693 1.1670037 7.3639663 1.1812379 7.8886719 1.3144531 C 8.5922201 1.493074 9.440416 1.7898596 10.583984 1.59375 C 11.647433 1.4113805 12.227503 1.1832377 12.65625 1.1171875 z M 7.7070312 4.5 L 12.292969 4.5 C 12.480348 4.6748327 13.734431 5.8555424 15.099609 7.6757812 C 16.569562 9.6357185 18 12.200315 18 14.5 C 18 16.849764 17.058785 17.835714 15.570312 18.408203 C 14.081843 18.980692 12 19 10 19 C 8 19 5.9181595 18.980692 4.4296875 18.408203 C 2.9412155 17.835714 2 16.849764 2 14.5 C 2 12.200315 3.4304379 9.6357186 4.9003906 7.6757812 C 6.2655702 5.855542 7.5196519 4.6748327 7.7070312 4.5 z M 9.5 9 L 9.5 10 C 8.6774954 10 8 10.677495 8 11.5 C 8 12.322505 8.6774954 13 9.5 13 L 10.5 13 C 10.782065 13 11 13.217935 11 13.5 C 11 13.782065 10.782065 14 10.5 14 L 9.5 14 L 8 14 L 8 15 L 9.5 15 L 9.5 16 L 10.5 16 L 10.5 15 C 11.322504 15 12 14.322505 12 13.5 C 12 12.677495 11.322504 12 10.5 12 L 9.5 12 C 9.2179352 12 9 11.782065 9 11.5 C 9 11.217935 9.2179352 11 9.5 11 L 10.5 11 L 12 11 L 12 10 L 10.5 10 L 10.5 9 L 9.5 9 z " style="fill-opacity:1; stroke:none; stroke-width:0px;"/>
							</g>
						</svg>

						<div class="myd-cart__nav-desc">
							<?php esc_html_e( 'Payment', 'myd-delivery-pro' ); ?>
						</div>
				</div>

				<div class="myd-cart__nav-close"><?php echo Fdm_svg::svg_close(); ?></div>
			</div>

			<div class="myd-cart__content">
				<?php include_once MYD_PLUGIN_PATH . '/templates/cart/cart-empty.php'; ?>
				<div class="myd-cart__products"></div>
				<?php include_once MYD_PLUGIN_PATH . '/templates/cart/cart-checkout.php'; ?>
				<?php include_once MYD_PLUGIN_PATH . '/templates/cart/cart-payment.php'; ?>
				<?php include_once MYD_PLUGIN_PATH . '/templates/cart/cart-finished-order.php'; ?>
			</div>

	<div class="myd-cart__footer-area" style="display: flex; flex-direction: column; flex-shrink: 0; background: #fff; border-top: 1px solid #eaeaea; z-index: 10;">
				<div class="myd-cart__subtotal-display" id="myd-cart__subtotal-display" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; font-weight: 700; font-size: 16px; color: #1a1a1a;">
					<span>Subtotal</span>
					<span id="myd-cart__button-price"><?php echo \esc_html( $currency_simbol ); ?> 0,00</span>
				</div>
				<style>
					/* Oculta o subtotal se a tela ativa não for myd-cart__products */
					.myd-cart:not(:has(.myd-cart__products.myd-cart__content--active)) .myd-cart__subtotal-display {
						display: none !important;
					}
				</style>
				<div class="myd-cart__button">
					<div
						class="myd-cart__button-text"
						data-text="<?php esc_attr_e( 'Next', 'myd-delivery-pro' ) ?>"
					>
						<?php esc_html_e( 'Next', 'myd-delivery-pro' ) ?>
					</div>
				</div>
			</div>
		</div>

		   <?php
		   // Modal de perfil MOVIDO PARA FORA DO MYD-CHECKOUT
		   ?>
	</section>
<?php endif; ?>

<?php
// Modal de perfil (renderizado aqui para não sofrer display:none do checkout)
include_once MYD_PLUGIN_PATH . '/templates/myd-profile-modal.php';
?>

<script type="text/template" id="myd-template-loading">
	<div class="myd-loader"></div>
</script>


<script>
// Script SSE com botão de teste para debug
(function(){
  console.log('[SSE DEBUG] Script iniciado');

  // Verificar se jQuery existe
  if (typeof jQuery === 'undefined') {
    console.error('[SSE DEBUG] jQuery não encontrado!');
    return;
  }
  console.log('[SSE DEBUG] jQuery encontrado');

  // Verificar se electronAPI existe
  if (typeof window.electronAPI === 'undefined') {
    console.log('[SSE DEBUG] electronAPI não encontrado (normal se não for Electron)');
  } else {
    console.log('[SSE DEBUG] electronAPI encontrado');
  }

  // Definir order_ajax_object se não existir
  if (typeof order_ajax_object === 'undefined') {
    window.order_ajax_object = {
      ajax_url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
      nonce: '<?php echo wp_create_nonce( 'myd-order-notification' ); ?>',
      domain: '<?php echo esc_attr( home_url() ); ?>'
    };
    console.log('[SSE DEBUG] order_ajax_object definido:', order_ajax_object);
  }

  // Função simples para testar recarga
  function testReload() {
    console.log('[SSE DEBUG] Testando recarga de pedidos...');
    return new Promise(function(resolve, reject) {
      jQuery.ajax({
        method: "post",
        url: order_ajax_object.ajax_url,
        data: {
          action: "update_orders",
          nonce: order_ajax_object.nonce
        }
      }).done(function(response) {
        // console.log('[SSE DEBUG] Resposta da recarga:', response);
        if (response.success && response.data) {
          console.log('[SSE DEBUG] Recarga bem-sucedida');
          resolve(response.data);
        } else {
          // console.warn('[SSE DEBUG] Resposta inválida:', response);
          reject(new Error('Resposta inválida'));
        }
      }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('[SSE DEBUG] Erro na recarga:', textStatus, errorThrown);
        reject(new Error('Erro na requisição: ' + textStatus + ' ' + errorThrown));
      });
    });
  }

	// Testar recarga imediatamente — somente para administradores/usuários autorizados
	if (<?php echo is_admin() || current_user_can('manage_options') ? 'true' : 'false'; ?>) {
		console.log('[SSE DEBUG] Testando recarga inicial para usuário autorizado...');
		testReload().then(function() {
			console.log('[SSE DEBUG] Recarga inicial OK');
		}).catch(function(err) {
			console.error('[SSE DEBUG] Erro na recarga inicial:', err);
		});
	} else {
		console.log('[SSE DEBUG] Recarga inicial omitida para usuário não autorizado');
	}

  // Agora tentar SSE - só para admins
  if (<?php echo is_admin() || current_user_can('manage_options') ? 'true' : 'false'; ?>) {
    try {
      var sseUrl = order_ajax_object.ajax_url + '?action=sse_order_status';
      console.log('[SSE DEBUG] Tentando conectar SSE em:', sseUrl);

      var es = new EventSource(sseUrl);
      console.log('[SSE DEBUG] EventSource criado');

			// Helper: permitir impressão local apenas quando explicitamente habilitado
			window.MYD_LOCAL_PRINT_ENABLED = window.MYD_LOCAL_PRINT_ENABLED || false;
			function mydCanUseLocalPrint() {
				try {
					if (window.MYD_LOCAL_PRINT_ENABLED) return true;
					if (window && window.electronAPI) return true;
					if (navigator && navigator.userAgent && navigator.userAgent.indexOf('Electron') !== -1) return true;
					return false;
				} catch (e) {
					return false;
				}
			}

      es.addEventListener('status_changed', function(e) {
        console.log('[SSE DEBUG] Evento status_changed recebido:', e);
        var data = {};
        try { data = JSON.parse(e.data); } catch(err) {
          console.warn('[SSE DEBUG] Erro ao parsear data:', err, e.data);
          return;
        }
        console.log('[SSE DEBUG] Dados do evento:', data);

        // Recarregar pedidos
        testReload().then(function() {
          console.log('[SSE DEBUG] Pedidos recarregados após evento');
        }).catch(function(err) {
          console.warn('[SSE DEBUG] Erro ao recarregar:', err);
        });
      });

      es.addEventListener('confirmed', function(e) {
        console.log('[SSE DEBUG] Evento confirmed recebido:', e);
        var data = {};
        try { data = JSON.parse(e.data); } catch(err) {
          console.warn('[SSE DEBUG] Erro ao parsear data confirmed:', err, e.data);
          return;
        }
        console.log('[SSE DEBUG] Dados do evento confirmed:', data);

				// Aqui você pode adicionar lógica específica para impressão automática
				if (window.electronAPI && window.electronAPI.printOrder) {
					console.log('[SSE DEBUG] Chamando impressão automática via Electron');
					window.electronAPI.printOrder(data.order_id);
				} else {
					console.log('[SSE DEBUG] Impressão automática via Electron não disponível, tentando fallback para servidor local');
					// Fallback: buscar dados de impressão via AJAX e enviar ao servidor de impressão local
					try {
						var formData = new FormData();
						formData.append('action', 'get_order_print_data');
						formData.append('order_id', data.order_id);
						fetch('/wp-admin/admin-ajax.php', {
							method: 'POST',
							body: formData,
							credentials: 'same-origin'
						}).then(function(r){ if(!r.ok) throw new Error('fetch_failed'); return r.json(); }).then(function(resp){
							if (resp && resp.success && resp.data) {
								var orderData = resp.data;
												console.log('[SSE DEBUG] orderData para impressão (fallback):', orderData);
												// Apenas tentar imprimir localmente quando permitido (app desktop ou flag explícita)
												if (typeof mydCanUseLocalPrint === 'function' ? mydCanUseLocalPrint() : (window.MYD_LOCAL_PRINT_ENABLED || false)) {
													fetch('http://127.0.0.1:3420/print', {
														method: 'POST',
														headers: { 'Content-Type': 'application/json' },
														body: JSON.stringify({ orderData: orderData, escpos: true })
													}).then(function(r){ if(!r.ok) throw new Error('local_print_failed_status_'+r.status); }).catch(function(e){ console.warn('[SSE DEBUG] Falha ao enviar para servidor de impressão local', e); });
												} else {
													console.log('[SSE DEBUG] Impressão local suprimida (frontend do cliente).');
												}
							} else {
								console.warn('[SSE DEBUG] Falha ao obter dados de impressão via admin-ajax (fallback)', resp);
							}
						}).catch(function(e){
							console.warn('[SSE DEBUG] Erro ao buscar dados de impressão via admin-ajax (fallback)', e);
						});
					} catch(e) {
						console.warn('[SSE DEBUG] Erro no fallback de impressão SSE:', e);
					}
				}
      });

  		// Ouvir eventos 'finished' e recarregar pedidos imediatamente
  		es.addEventListener('finished', function(e) {
  			console.log('[SSE DEBUG] Evento finished recebido:', e);
  			var data = {};
  			try { data = JSON.parse(e.data); } catch(err) {
  				console.warn('[SSE DEBUG] Erro ao parsear data finished:', err, e.data);
  				return;
  			}
  			console.log('[SSE DEBUG] Dados do evento finished:', data);
  			// Recarregar pedidos para refletir movimentação para a seção "done"
  			testReload().then(function() {
  				console.log('[SSE DEBUG] Pedidos recarregados após finished');
  			}).catch(function(err) {
  				console.warn('[SSE DEBUG] Erro ao recarregar após finished:', err);
  			});
  		});

      es.addEventListener('open', function(e) {
        console.log('[SSE DEBUG] Conexão SSE aberta com sucesso');
      });

      es.addEventListener('error', function(e) {
        console.error('[SSE DEBUG] Erro na conexão SSE:', e);
      });

    } catch(e) {
      console.error('[SSE DEBUG] Erro ao criar EventSource:', e);
    }
  } else {
    console.log('[SSE DEBUG] SSE desabilitado para usuários não-admin');
  }

  console.log('[SSE DEBUG] Script finalizado');
})();
</script>
<!-- Carrega script que evita cliques duplicados no checkout e mostra overlay -->
<script src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/js/checkout-blocker.js' ); ?>"></script>
<?php Store_Data::$template_dependencies_loaded = true; ?>


<script>
(function(){
	if (typeof order_ajax_object === 'undefined') return;

	function initPopupHandlers(popup){
		if(!popup) return;
		// close buttons
		popup.querySelectorAll('.fdm-popup-close-btn').forEach(function(btn){
			btn.addEventListener('click', function(){ popup.classList.toggle('myd-hide-element'); });
		});

		// add to cart inside popup
		popup.querySelectorAll('.fdm-add-to-cart-popup').forEach(function(el){
			el.addEventListener('click', function(){
				var t = el.querySelector('.myd-add-to-cart-button__text');
				if(t && window.Myd && typeof window.Myd.getLoadingAnimation === 'function') t.innerHTML = window.Myd.getLoadingAnimation();
				try { if(window.MydCart && typeof window.MydCart.addItem === 'function') window.MydCart.addItem(el.id); } catch(e){}
				if(t) t.innerHTML = el.dataset.text || '<?php echo esc_js( __( 'Add to bag', 'myd-delivery-pro' ) ); ?>';
			});
		});

		// image preview - DISABLED as per user request
		/*
		popup.querySelectorAll('.myd-product-popup__img').forEach(function(img){
			img.addEventListener('click', function(){
				var w = document.getElementById('myd-image-preview-image');
				var box = document.getElementById('myd-image-preview-popup');
				if(w && box){ w.src = img.dataset.image; box.classList.toggle('myd-hide-element'); }
			});
		});
		*/

		// quantity handlers (use existing Myd helper if available)
		if(window.Myd && typeof window.Myd.setProductChangeQuantity === 'function'){
			try { window.Myd.setProductChangeQuantity(); } catch(e){}
		}
	}

	// capture clicks on product item and lazily load popup
	document.addEventListener('click', function(e){
		var item = e.target.closest && e.target.closest('.myd-product-item');
		if(!item) return;
		var id = item.dataset.id;
		if(!id) return;
		var popup = document.getElementById('popup-' + id);
		if(!popup) return;

		// If already loaded, toggle visibility and stop propagation
		if(popup.dataset.loaded === '1'){
			e.stopImmediatePropagation();
			popup.classList.toggle('myd-hide-element');
			return;
		}

		// prevent default handlers from running (they would toggle empty popup)
		e.stopImmediatePropagation();

		// show lightweight loader
		popup.innerHTML = '<div class="myd-loader" style="min-height:120px"></div>';
		popup.classList.remove('myd-hide-element');

		var body = 'action=myd_get_product_popup&product_id=' + encodeURIComponent(id);
		if(order_ajax_object && order_ajax_object.nonce) body += '&nonce=' + encodeURIComponent(order_ajax_object.nonce);

		fetch(order_ajax_object.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body
		}).then(function(r){ return r.json(); }).then(function(resp){
			if(resp && resp.success && resp.data && resp.data.html){
				popup.innerHTML = resp.data.html;
				popup.dataset.loaded = '1';
				initPopupHandlers(popup);
			} else {
				popup.innerHTML = '<div class="fdm-popup-product-content"><p><?php echo esc_js( __( 'Erro ao carregar produto', 'myd-delivery-pro' ) ); ?></p></div>';
			}
		}).catch(function(){
			popup.innerHTML = '<div class="fdm-popup-product-content"><p><?php echo esc_js( __( 'Erro ao carregar produto', 'myd-delivery-pro' ) ); ?></p></div>';
		});

	}, true);

})();
</script>

