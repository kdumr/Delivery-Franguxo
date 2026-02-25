<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Obter valor total do carrinho no backend (WooCommerce)
if (function_exists('WC')) {
	$cart_total_float = WC()->cart ? WC()->cart->get_total('float') : 0.0;
} else {
	$cart_total_float = 0.0;
}
$online_payment_enabled = defined( 'SUMUPMYD_CURRENT_VERSION' );
?>
<div id="myd-cart-payment" class="myd-cart__payment">
	<div id="myd-cart-total-summary" class="myd-cart__payment-amount-details"></div>

	<div class="myd-cart__checkout-payment">
		<h4 class="myd-cart__checkout-title">
			<?php esc_html_e( 'Payment', 'myd-delivery-pro' ); ?>
		</h4>

	   <div class="myd-cart__payment-options-container">
		   <!-- Nova aba: Pagamento online -->
		   <details open data-type="online-payment">
			   <summary>
				   <svg class="myd-details-arrow" width="12" height="12" viewBox="-4.5 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M249.365851,6538.70769 C249.770764,6539.09744 250.426289,6539.09744 250.830166,6538.70769 L259.393407,6530.44413 C260.202198,6529.66364 260.202198,6528.39747 259.393407,6527.61699 L250.768031,6519.29246 C250.367261,6518.90671 249.720021,6518.90172 249.314072,6519.28247 C248.899839,6519.67121 248.894661,6520.31179 249.302681,6520.70653 L257.196934,6528.32352 C257.601847,6528.71426 257.601847,6529.34685 257.196934,6529.73759 L249.365851,6537.29462 C248.960938,6537.68437 248.960938,6538.31795 249.365851,6538.70769" fill="#333" transform="translate(-249,  -6519)"/></svg>
				   <?php esc_html_e( 'Pagamento online', 'myd-delivery-pro' ); ?>
			   </summary>
			   <div class="myd-cart__checkout-payment-method" id="myd-online-payment-method">
				   <div id="paymentBrick_container">
					   <div id="myd-brick-loader" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;gap:12px">
						   <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
							   <circle cx="20" cy="20" r="16" fill="none" stroke="#e8e8e8" stroke-width="3.5"/>
							   <circle cx="20" cy="20" r="16" fill="none" stroke="#ffae00" stroke-width="3.5" stroke-linecap="round" stroke-dasharray="80" stroke-dashoffset="60">
								   <animateTransform attributeName="transform" type="rotate" from="0 20 20" to="360 20 20" dur="1s" repeatCount="indefinite"/>
							   </circle>
						   </svg>
						   <span style="font-size:14px;color:#888;font-weight:500">Carregando...</span>
					   </div>
				   </div>
				   <script src="https://sdk.mercadopago.com/js/v2"></script>
				   <?php
					   $mp_public_key = get_option('mercadopago_public_key', '');
				   ?>
				   <script>
				   (function(){
				     console.log('[MYD] Iniciando script Mercado Pago...');
				     // Bloqueia EventSource para /mercadopago/pix/sse (evita SSE redundante no frontend)
				     (function(){
				       try {
				         var _ES = window.EventSource;
				         if (!_ES) return;
				         function PatchedEventSource(url) {
				           try {
				             if (typeof url === 'string' && url.indexOf('/mercadopago/pix/sse') !== -1) {
				               console.info('[MYD] Bloqueado EventSource para', url);
				               // devolve objeto mínimo compatível
				               var noop = function(){};
				               return {
				                 addEventListener: noop,
				                 removeEventListener: noop,
				                 close: noop,
				                 // propriedades comuns
				                 onopen: null,
				                 onmessage: null,
				                 onerror: null
				               };
				             }
				           } catch(e) { /* ignore */ }
				           return new _ES(url);
				         }
				         PatchedEventSource.prototype = _ES.prototype;
				         window.EventSource = PatchedEventSource;
				       } catch(e) { console.warn('[MYD] Patch EventSource falhou', e); }
				     })();
				     function initMercadoPagoBrick(){
				       console.log('[MYD] initMercadoPagoBrick chamado');
							// Helper: show notification using existing #myd-popup-notification markup
							function showErrorBar(message) {
								try {
									var tpl = document.getElementById('myd-popup-notification');
									var msg = document.getElementById('myd-popup-notification__message');
									if (tpl && msg) {
										msg.innerText = message;
										tpl.style.background = '#cb2027';
										tpl.style.opacity = '1';
										tpl.style.visibility = 'visible';
										// auto-hide after 6s
										setTimeout(function(){ try { tpl.style.opacity = '0'; tpl.style.visibility = 'hidden'; } catch(e){} }, 6000);
										return;
									}
									// fallback: previous implementation
									var container = document.querySelector('.myd-cart') || document.getElementById('myd-cart-payment');
									if (!container) container = document.body;
									var existing = document.getElementById('myd-error-bar');
									if (existing) existing.parentNode.removeChild(existing);
									var bar = document.createElement('div');
									bar.id = 'myd-error-bar';
									bar.style.background = '#c62828';
									bar.style.color = '#fff';
									bar.style.padding = '12px';
									bar.style.borderRadius = '4px';
									bar.style.marginBottom = '12px';
									bar.style.fontWeight = '600';
									bar.style.boxShadow = '0 2px 6px rgba(0,0,0,0.12)';
									bar.textContent = message;
									if (container.firstChild) container.insertBefore(bar, container.firstChild);
									setTimeout(function(){ try { if (bar && bar.parentNode) bar.parentNode.removeChild(bar); } catch(e){} }, 6000);
								} catch(e) { console.error(e); }
							}

							// Helper: simulate back button logic (click on .myd-cart__nav-back if present)
							function showProductsTab() {
								try {
									var back = document.querySelector('.myd-cart__nav-back');
									if (back) {
										try { back.click(); return; } catch(e){}
									}
									// fallback: activate products pane directly
									var contents = document.querySelectorAll('.myd-cart__content--active');
									Array.prototype.forEach.call(contents, function(el){ el.classList.remove('myd-cart__content--active'); });
									var products = document.querySelector('.myd-cart__products');
									if (products) products.classList.add('myd-cart__content--active');
									var tabButtons = document.querySelectorAll('[data-tab-content]');
									Array.prototype.forEach.call(tabButtons, function(btn){
										if (btn.getAttribute('data-tab-content') === 'myd-cart__products') {
											try { btn.click(); } catch(e){}
										}
									});
								} catch(e) { console.error(e); }
							}

							// Inicia um observador de pagamento: usa SSE com reconexão/backoff
							// e fallback para polling a cada 5s caso SSE não esteja disponível.
							function startPaymentWatcher(paymentId, opts) {
								if (!paymentId) return;
								opts = opts || {};
								var es = null;
								var stopped = false;
								var reconnectDelay = 1000; // 1s
								var maxDelay = 30000; // 30s
								var reconnectAttempts = 0;
								var pollInterval = null;
								var totalTimeout = opts.timeout || (15 * 60 * 1000); // 15 minutos por padrão

								function cleanup() {
									stopped = true;
									try { if (es) { es.close(); } } catch (e) {}
									es = null;
									if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
								}

								function finalizeFromApproved() {
									const box = document.getElementById('pix-status-box');
									if (box) {
										box.innerHTML = '<div style="text-align:center;padding:20px">\n<div style="font-size:18px;font-weight:700;color:#0a7a0a;margin-bottom:8px">Pagamento efetuado!</div>\n<div style="color:#333">Estamos finalizando seu pedido.</div>\n</div>';
									}
									// Dispatch event so other modules can finalize the order automatically
									try {
										if (window && window.Myd && typeof window.Myd.newEvent === 'function') {
											window.Myd.newEvent('MydCheckoutPlacePayment', { method: 'pix', paymentId: paymentId });
											console.info && console.info('[MYD] dispatched MydCheckoutPlacePayment (auto) for', paymentId);
										} else {
											console.info && console.info('[MYD] payment approved for', paymentId, '- Myd not available to auto-dispatch');
										}
									} catch (e) { console.warn('[MYD] error dispatching auto finalize event', e); }
									cleanup();
								}

								function startPolling() {
									if (pollInterval) return;
									pollInterval = setInterval(async function() {
										try {
											const res = await fetch('/wp-json/myd-delivery/v1/mercadopago/payment_status?id=' + encodeURIComponent(paymentId), { cache: 'no-store' });
											if (!res.ok) return; // keep polling
											const data = await res.json();
											if (data && data.status === 'approved') {
												finalizeFromApproved();
											}
										} catch (e) {
											// ignore and retry
										}
									}, 5000);
								}

								function startSSE() {
									if (stopped) return;
									try {
										const sseUrl = '/wp-json/myd-delivery/v1/mercadopago/pix/sse?payment_id=' + encodeURIComponent(paymentId);
										es = new EventSource(sseUrl);
										// generic listeners: approved + common aliases
										es.addEventListener('approved', handleSseEvent);
										es.addEventListener('confirmed', handleSseEvent);
										es.addEventListener('status_changed', handleSseEvent);
										// also listen to default message event (some servers emit without 'event:' label)
										es.addEventListener('message', function(ev) {
											try { handleSseEvent(ev); } catch(e) { /* ignore */ }
										});

										es.onopen = async function() {
											reconnectDelay = 1000; reconnectAttempts = 0;
											if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
											// immediate status check in case event arrived before or during connection
											try {
												const quick = await fetch('/wp-json/myd-delivery/v1/mercadopago/payment_status?id=' + encodeURIComponent(paymentId), { cache: 'no-store' });
												if (quick && quick.ok) {
													const d = await quick.json();
													if (d && d.status === 'approved') {
														handleSseEvent({ data: JSON.stringify(d) });
													}
												}
											} catch(e) { /* ignore */ }
										};

										es.onerror = function(err) {
											try { if (es) { es.close(); } } catch (e) {}
											es = null;
											reconnectAttempts++;
											if (reconnectAttempts >= 4) {
												// after several failed attempts, fallback to polling
												startPolling();
												return;
											}
											// attempt reconnect with exponential backoff
											setTimeout(function() { reconnectDelay = Math.min(maxDelay, reconnectDelay * 2); startSSE(); }, reconnectDelay);
										};

										function handleSseEvent(ev) {
											try {
												var payload = null;
												if (ev && ev.data) {
													try { payload = typeof ev.data === 'string' ? JSON.parse(ev.data) : ev.data; } catch(e) { payload = ev.data; }
												}
												// payload can be either { status: 'approved' } or nested
												if (payload && (payload.status === 'approved' || (payload.info && payload.info.status === 'approved'))) {
													finalizeFromApproved();
												}
											} catch(e) { console.error(e); }
										}
									} catch (e) {
										// SSE não disponível: iniciar polling imediatamente
										startPolling();
									}
								}

								// start
								startSSE();
								// safety timeout to stop watchers after long time
								setTimeout(function(){ if (!stopped) cleanup(); }, totalTimeout);
							}
				       const container = document.getElementById('paymentBrick_container');
				       const pubKey = '<?php echo esc_js($mp_public_key); ?>';
				       console.log('[MYD] Container encontrado:', !!container, 'PubKey:', pubKey ? 'definida' : 'vazia');
				       if (!container) { console.log('[MYD] Container não encontrado'); return; }
				       if (!pubKey) {
				         console.log('[MYD] Public Key não configurada');
				         container.innerHTML = '<div style="color:#c00;background:#fee;padding:10px;border-radius:6px">Mercado Pago: defina a Public Key nas configurações.</div>';
				         return;
				       }

				       // Intercept 'Próximo' button clicks when on payment pane to ensure order exists
				       (function attachNextButtonGuard(){
				         try {
				           var nextBtn = document.querySelector('.myd-cart__button');
				           if (!nextBtn) return;
				           nextBtn.addEventListener('click', async function (ev) {
				             try {
				               // check if payment pane is currently visible
				               var paymentPane = document.querySelector('.myd-cart__payment');
				               var isPaymentActive = paymentPane && paymentPane.classList.contains('myd-cart__content--active');
				               // also check via visibility of element inside
				               if (!isPaymentActive) return; // not on payment step
				               // if we have an order id, verify it exists
				               var orderId = (window.MydOrder && window.MydOrder.id) ? window.MydOrder.id : null;
				               if (orderId) {
				                 var res = await fetch('/wp-json/myd-delivery/v1/order/exists?order_id=' + encodeURIComponent(orderId), { method: 'GET', cache: 'no-store' });
				                 if (!res.ok) {
				                   ev.stopPropagation(); ev.preventDefault();
				                   showErrorBar('Ops! O tempo para concluir seu pedido expirou. Por favor, tente novamente.');
				                   setTimeout(function(){ showProductsTab(); }, 450);
				                   return false;
				                 }
				               }
				               // no order id: allow default behavior (other code may create draft)
				             } catch(e) {
				               // on network error, block to avoid progressing without order
				               ev.stopPropagation(); ev.preventDefault();
				               showErrorBar('Erro ao verificar pedido. Tente novamente.');
				               return false;
				             }
				           }, { capture: true });
				         } catch(e) { console.error(e); }
				       })();
				       console.log('[MYD] Inicializando MercadoPago com pubKey');
				       const mp = new MercadoPago(pubKey, { locale: 'pt-BR' });
				       let deviceId = '';
				       try {
				           // Implementar device ID conforme documentação Mercado Pago
				           const navigatorInfo = window.navigator;
				           const screenInfo = window.screen;
				           const deviceIdString = [
				               navigatorInfo.userAgent,
				               navigatorInfo.language,
				               screenInfo.width + 'x' + screenInfo.height,
				               new Date().getTimezoneOffset(),
				               !!window.sessionStorage,
				               !!window.localStorage,
				               !!window.indexedDB,
				               navigatorInfo.platform
				           ].join('|');
				           
				           // Criar hash simples do device ID
				           let hash = 0;
				           for (let i = 0; i < deviceIdString.length; i++) {
				               const char = deviceIdString.charCodeAt(i);
				               hash = ((hash << 5) - hash) + char;
				               hash = hash & hash; // Converter para 32 bits
				           }
				           deviceId = 'web-' + Math.abs(hash).toString(36);
				           console.log('[MYD] Device ID gerado:', deviceId);
				       } catch (e) {
				           console.warn('[MYD] Erro ao gerar device ID:', e);
				           deviceId = 'web-fallback-' + Date.now();
				       }
				       const bricksBuilder = mp.bricks();

					   // Calcula o total real (itens + entrega - descontos) priorizando o total do pedido
			   const backendCartTotal = <?php echo json_encode($cart_total_float); ?>; // fallback vindo do WooCommerce se existir

			   // Helper: ler desconto de fidelidade exibido no DOM
			   function getLoyaltyDiscount() {
				   try {
					   var el = document.getElementById('myd-cart-payment-fidelity-discount');
					   if (!el) return 0;
					   var txt = el.textContent || '';
					   // remove tudo que não é número, vírgula ou ponto: "-R$ 5,00" -> "5,00"
					   txt = txt.replace(/[^0-9,\.]/g, '');
					   // formato BR: "5,00" -> "5.00"
					   txt = txt.replace(/\./g, '').replace(',', '.');
					   var val = parseFloat(txt);
					   return isNaN(val) ? 0 : val;
				   } catch(e) { return 0; }
			   }

			   function computeAmount() {
				   var loyaltyDiscount = getLoyaltyDiscount();

				   // 1) Total do pedido já calculado (inclui frete/descontos)
				   if (window.MydOrder && typeof window.MydOrder.total === 'number' && window.MydOrder.total > 0) {
					   var total = window.MydOrder.total - loyaltyDiscount;
					   if (total < 0) total = 0;
					   return Number(total.toFixed(2));
				   }
				   // 2) Total salvo na sessão (ao criar draft, salvamos orderTotal)
				   if (window.MydOrderSessionPersistence && typeof window.MydOrderSessionPersistence.getOrderSession === 'function') {
					   const session = window.MydOrderSessionPersistence.getOrderSession();
					   const orderTotal = session && session.orderData && session.orderData.orderTotal;
					   if (typeof orderTotal === 'number' && orderTotal > 0) {
						   var total = orderTotal - loyaltyDiscount;
						   if (total < 0) total = 0;
						   return Number(total.toFixed(2));
					   }
					   if (session && session.cart && typeof session.cart.total === 'number' && session.cart.total > 0) {
						   var total = session.cart.total - loyaltyDiscount;
						   if (total < 0) total = 0;
						   return Number(total.toFixed(2));
					   }
				   }
				   // 3) Total do carrinho atual
				   if (window.MydCart && typeof window.MydCart.total === 'number' && window.MydCart.total > 0) {
					   var total = window.MydCart.total - loyaltyDiscount;
					   if (total < 0) total = 0;
					   return Number(total.toFixed(2));
				   }
				   // 4) Fallback vindo do backend (Woo)
				   if (typeof backendCartTotal === 'number' && backendCartTotal > 0) {
					   var total = backendCartTotal - loyaltyDiscount;
					   if (total < 0) total = 0;
					   return Number(total.toFixed(2));
				   }
				   // 5) Último recurso
				   return 100.00;
			   }

					   let amount = computeAmount();
			   console.log('[MP] Valor inicial do amount:', amount, '| Desconto fidelidade:', getLoyaltyDiscount());

					   // Obter dados reais do cliente autenticado
					   let payer = { firstName: 'Cliente', lastName: 'Anônimo', email: 'cliente@exemplo.com' };
					   if (window.mydCustomerAuth && window.mydCustomerAuth.current_user) {
						   const user = window.mydCustomerAuth.current_user;
						   if (user.name) {
							   const nameParts = user.name.trim().split(' ');
							   payer.firstName = nameParts[0] || 'Cliente';
							   payer.lastName = nameParts.slice(1).join(' ') || 'Anônimo';
						   }
						   if (user.email) payer.email = user.email;
					   }
					   console.log('[MYD] Payer definido:', payer);

					   // Flag para garantir que o Brick só seja renderizado uma vez
			   let brickRendered = false;

			   // Função para (re)renderizar o Brick sempre com o total atualizado
			   function refreshPaymentBrick() {
				   if (brickRendered) {
					   console.log('[MYD] Brick já renderizado, ignorando refresh duplicado');
					   return;
				   }
				   console.log('[MYD] refreshPaymentBrick executando');
				   amount = computeAmount();
				   console.log('[MP] Renderizando Brick com amount:', amount);
				   brickRendered = true;
				   // Criar uma preference simples para o brick
				   fetch('/wp-json/myd-delivery/v1/mercadopago/preference', {
					   method: 'POST',
					   headers: { 'Content-Type': 'application/json' },
					   body: JSON.stringify({ amount: amount, payer: payer })
				   })
				   .then(res => res.json())
				   .then(data => {
					   console.log('[MP] Preference criada:', data);
					   const prefId = data.preferenceId || null;
					   renderPaymentBrick(bricksBuilder, prefId, amount);
				   })
				   .catch(err => {
					   console.error('[MP] Erro ao criar preference:', err);
					   // Tentar renderizar sem preference
					   renderPaymentBrick(bricksBuilder, null, amount);
				   });
			   }

			   // Renderizar o Brick apenas quando o painel de pagamento estiver visível
			   function tryRenderWhenPaymentVisible() {
				   if (brickRendered) return;
				   var paymentPane = document.querySelector('.myd-cart__checkout-payment');
				   if (paymentPane) {
					   // Painel de pagamento existe — renderizar o Brick
					   console.log('[MYD] Painel de pagamento detectado, carregando Brick');
					   refreshPaymentBrick();
				   }
			   }

			   // NÃO renderiza na carga inicial — espera até o checkout chegar na aba de pagamento
			   console.log('[MYD] Brick será carregado quando a aba de pagamento for exibida');

			   // Renderiza quando o draft order é criado (momento em que o checkout avança para pagamento)
			   window.addEventListener('MydDraftOrderCreated', tryRenderWhenPaymentVisible);
			   window.addEventListener('MydCartUpdated', tryRenderWhenPaymentVisible);
			   window.addEventListener('MydSessionRecovered', tryRenderWhenPaymentVisible);

			   // Observar quando a aba de pagamento ficar ativa via MutationObserver
			   (function observePaymentPane() {
				   try {
					   var cart = document.querySelector('.myd-cart') || document.querySelector('.myd-checkout');
					   if (!cart) return;
					   var observer = new MutationObserver(function() {
						   if (brickRendered) { observer.disconnect(); return; }
						   var paymentActive = document.querySelector('.myd-cart__payment.myd-cart__content--active');
						   if (paymentActive) {
							   console.log('[MYD] Aba de pagamento ativa detectada via observer');
							   refreshPaymentBrick();
							   observer.disconnect();
						   }
					   });
					   observer.observe(cart, { attributes: true, subtree: true, attributeFilter: ['class'] });
				   } catch(e) { console.warn('[MYD] observer error', e); }
			   })();

					   async function renderPaymentBrick(bricksBuilder, preferenceId, currentAmount) {
						   console.log('[MYD] renderPaymentBrick chamado com amount:', currentAmount);
						   // Desmontar brick anterior se existir
					   if (window.paymentBrickController && typeof window.paymentBrickController.unmount === 'function') {
							   try { 
								   console.log('[MYD] Desmontando brick anterior');
								   window.paymentBrickController.unmount(); 
								   window.paymentBrickController = null; // Limpar referência
							   } catch (e) {
								   console.warn('[MYD] Erro ao desmontar brick:', e);
							   }
						   }
						   // Limpar container antes de renderizar
						   const container = document.getElementById('paymentBrick_container');
						   const initData = {
							   amount: currentAmount,
							   payer: payer,
						   };
						   if (preferenceId) {
							   initData.preferenceId = preferenceId;
						   }
						   const settings = {
							   initialization: initData,
							   customization: {
								   visual: {
									   hideFormTitle: true,
									   style: {
										   theme: 'flat',
										   baseColor: '#ffae00'
									   },
								   },
								   paymentMethods: {
									   creditCard: 'all',
									   debitCard: 'all',
									   // ticket: 'all', // boleto removido
									   bankTransfer: 'all',
									   maxInstallments: 3
								   },
							   },
							   callbacks: {
								   onReady: () => {
							   try { var loader = document.getElementById('myd-brick-loader'); if (loader) loader.remove(); } catch(e) {}
						   },
								   onSubmit: ({ selectedPaymentMethod, formData }) => {
									   return new Promise((resolve, reject) => {
										   // Attach current order id to the payload so server can link payment -> order
										   var mpPayload;
										   try {
											   if (formData instanceof FormData) {
												   mpPayload = {};
												   formData.forEach(function(v,k){ mpPayload[k] = v; });
											   } else {
												   mpPayload = Object.assign({}, formData);
											   }
										   } catch(e) {
											   mpPayload = formData;
										   }
										   try {
											   if (window && window.MydOrder && window.MydOrder.id) {
												   mpPayload.order_id = window.MydOrder.id;
											   }
										   } catch(e) {}
										   // Adicionar Device ID gerado pelo SDK MercadoPago.JS V2
										   mpPayload.device_id = window.getMercadoPagoDeviceId && window.getMercadoPagoDeviceId();

										   // Before hitting MercadoPago, ensure the linked order still exists
										   (async function(){
											   try {
												   if (mpPayload && mpPayload.order_id) {
													   const existsRes = await fetch('/wp-json/myd-delivery/v1/order/exists?order_id=' + encodeURIComponent(mpPayload.order_id), { method: 'GET', cache: 'no-store' });
													   if (!existsRes.ok) {
														   // show red notification and return to products tab
														   showErrorBar('Ops! O tempo para concluir seu pedido expirou. Por favor, tente novamente.');
														   // wait a bit so the user sees the notification before switching tabs
														   setTimeout(function(){ showProductsTab(); }, 450);
														   return reject(new Error('order_not_found'));
													   }
												   }
											   } catch(e) {
												   // network error: stop to avoid creating payment without order
												   showErrorBar('Erro ao verificar pedido. Tente novamente.');
												   return reject(e);
											   }

											   return fetch('/wp-json/myd-delivery/v1/mercadopago/process_payment', {
												   method: 'POST',
												   headers: {
													   'Content-Type': 'application/json',
												   },
												   body: JSON.stringify(mpPayload),
											   });
										   })()
											   .then((response) => response.json())
											   .then((response) => {
												   if (response.status === 'approved') {
													   // Pagamento aprovado, dispara evento para criar pedido automaticamente
													   try {
														   if (window && window.Myd && typeof window.Myd.newEvent === 'function') {
															   window.Myd.newEvent('MydCheckoutPlacePayment', { method: response.payment_method_id || 'unknown', paymentId: response.id || null });
															   console.info && console.info('[MYD] payment flow returned approved (auto dispatch)');
														   } else {
															   console.info && console.info('[MYD] payment approved but Myd not available to auto-dispatch');
														   }
													   } catch(e) { console.warn('[MYD] error dispatching auto event', e); }
													   resolve();
												   } else if (response.status === 'pending' && response.detail && response.detail.payment_method_id === 'pix') {
													   // Pagamento Pix pendente: exibe QR e copia e cola
												   const paymentId = response.id || (response.detail && response.detail.id);
												   let pixData = response.detail.point_of_interaction && response.detail.point_of_interaction.transaction_data;
													   if (pixData && (pixData.qr_code_base64 || pixData.qr_code)) {
											   	   let html = '<div id="pix-status-box" data-payment-id="' + (paymentId || '') + '" style="border:1px solid #e2e2e2;border-radius:8px;padding:12px">';
														   if (pixData.qr_code_base64) {
												   html += '<div style="text-align:center;margin-bottom:10px"><img src="data:image/jpeg;base64,' + pixData.qr_code_base64 + '" style="max-width:220px;max-height:220px;" /></div>';
														   }
														   if (pixData.qr_code || pixData.ticket_url) {
												   html += '<div style="margin-bottom:15px">';
												   
												   if (pixData.qr_code) {
													   html += '<label for="pix-copia-e-cola" style="display:block;margin-bottom:5px;font-weight:600;text-align:left">Pix Copia e Cola:</label>';
													   html += '<input type="text" id="pix-copia-e-cola" value="' + pixData.qr_code + '" readonly />';
												   }
												   
												   if (pixData.ticket_url) {
													   html += '<div style="margin:12px 0 8px 0;text-align:center"><a href="' + pixData.ticket_url + '" target="_blank" style="text-decoration:underline;color:#009ee3;font-weight:600">Abrir página do pagamento Pix no banco</a></div>';
												   }

												   if (pixData.qr_code) {
													   html += '<button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'pix-copia-e-cola\').value); this.innerText=\'Copiado!\'; setTimeout(()=>this.innerText=\'Copiar código\', 2000);" style="width:100%;margin-top:8px">Copiar código</button>';
												   }
												   
												   html += '</div>';
														   }
											   	   html += '<div id="pix-status-message" style="margin-top:8px;color:#666;font-size:12px;display:flex;align-items:center;gap:8px">'
											   	       + '<span class="myd-spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #ccc;border-top-color:#ffae00;border-radius:50%;animation:mydspin 1s linear infinite"></span>'
											   	       + '<span>Pagamento Pix pendente. Após pagar, clique em "Já paguei, verificar".</span>'
											   	       + '</div>'
											   	       + '<div style="margin-top:10px"><button id="pix-check-once" type="button" style="background:#ffae00;border:0;border-radius:6px;padding:8px 12px;color:#000;font-weight:600;cursor:pointer">Já paguei, verificar</button></div>'
											   	       + '<style>@keyframes mydspin{from{transform:rotate(0)}to{transform:rotate(360deg)}}</style>'
											   	       + '</div>';
												   document.getElementById('paymentBrick_container').innerHTML = html;
											   	   // Iniciar polling automático para verificar status do pagamento Pix a cada 3 segundos
											   	   let pixPollingInterval = setInterval(async function() {
											   	       try {
											   	           const res = await fetch('/wp-json/myd-delivery/v1/mercadopago/payment_status?id=' + encodeURIComponent(paymentId), { cache: 'no-store' });
											   	           const data = await res.json();
											   	           if (data && data.status === 'approved') {
											   	               // Pagamento aprovado: parar polling e finalizar pedido automaticamente
											   	               clearInterval(pixPollingInterval);
											   	               const box = document.getElementById('pix-status-box');
											   	               if (box) {
											   	                   box.innerHTML = '<div style="text-align:center;padding:20px">\n<div style="font-size:18px;font-weight:700;color:#0a7a0a;margin-bottom:8px">Pagamento efetuado!</div>\n<div style="color:#333">Estamos finalizando seu pedido.</div>\n</div>';
											   	               }
											   	               // Disparar evento para finalizar o pedido
											   	               try {
											   	                   if (window && window.Myd && typeof window.Myd.newEvent === 'function') {
											   	                       window.Myd.newEvent('MydCheckoutPlacePayment', { method: 'pix', paymentId: paymentId });
											   	                       console.info && console.info('[MYD] pix approved (auto-polling dispatch)', paymentId);
											   	                   } else {
											   	                       console.info && console.info('[MYD] pix approved but Myd not available');
											   	                   }
											   	               } catch (e) { console.warn('[MYD] error dispatching pix approved event', e); }
											   	           }
											   	       } catch (e) {
											   	           console.warn('[MYD] error polling pix status', e);
											   	       }
											   	   }, 3000); // 3 segundos
											   	   // Botão para verificar uma única vez o status do pagamento (sem polling contínuo)
											   	   try {
											   	     const btn = document.getElementById('pix-check-once');
																																	 if (btn && paymentId) {
																																			 btn.addEventListener('click', async function() {
																																				 btn.disabled = true; btn.textContent = 'Verificando...';
																																				 try {
																																					 const res = await fetch('/wp-json/myd-delivery/v1/mercadopago/payment_status?id=' + encodeURIComponent(paymentId), { cache: 'no-store' });
																																					 const data = await res.json();
																																					 if (data && data.status === 'approved') {
																																						 const box = document.getElementById('pix-status-box');
																																						 if (box) {
																																							 box.innerHTML = '<div style="text-align:center;padding:20px">\n<div style="font-size:18px;font-weight:700;color:#0a7a0a;margin-bottom:8px">Pagamento efetuado!</div>\n<div style="color:#333">Estamos finalizando seu pedido.</div>\n</div>';
																																						 }
																																						 // Disparar evento para finalizar o pedido (clicou e já está aprovado)
																																						 try {
																																							 if (window && window.Myd && typeof window.Myd.newEvent === 'function') {
																																								 window.Myd.newEvent('MydCheckoutPlacePayment', { method: 'pix', paymentId: paymentId });
																																								 console.info && console.info('[MYD] pix approved (manual-button dispatch)', paymentId);
																																							 } else {
																																								 console.info && console.info('[MYD] pix approved but Myd not available');
																																							 }
																																						 } catch (e) { console.warn('[MYD] error dispatching pix approved event', e); }
																																						 // keep resolve/flow
																																					 } else {
																																						 btn.disabled = false; btn.textContent = 'Já paguei, verificar';
																																						 const msg = document.getElementById('pix-status-message');
																																						 if (msg) { msg.innerHTML = '<span class="myd-spinner" style="display:inline-block;width:14px;height:14px;border:2px solid #ccc;border-top-color:#ffae00;border-radius:50%;animation:mydspin 1s linear infinite"></span><span>Ainda não aprovado. Tente novamente em alguns segundos.</span>'; }
																																					 }
																																				 } catch (e) {
																																					 btn.disabled = false; btn.textContent = 'Já paguei, verificar';
																																					 alert('Não foi possível verificar agora. Tente novamente.');
																																				 }
																																			 });
																																		 }
										    	     // Conectar no SSE (com reconexão e fallback polling) para atualizar automaticamente quando o webhook aprovar
		                                           	 // removido auto-criação de SSE aqui; manter apenas botão manual
											   	   } catch (e) { /* noop */ }
													   } else {
														   document.getElementById('paymentBrick_container').innerHTML = '<div style="color:red">Pagamento Pix pendente, mas não foi possível exibir o QR Code.</div>';
													   }
													   resolve();
												   } else if (response.status === 'in_process') {
													   // Pagamento em processamento (cartões que requerem autoriza\u00e7\u00e3o pelo emissor)
													   const inProcessMsg = 'Pagamento em processamento pelo emissor. Aguarde a confirma\u00e7\u00e3o.';
													   try {
														   if (window && window.Myd && typeof window.Myd.notificationBar === 'function') {
															   window.Myd.notificationBar('warning', inProcessMsg);
														   } else {
															   alert(inProcessMsg);
														   }
													   } catch (e) { try { alert(inProcessMsg); } catch(_){} }
													   // Resolve o fluxo para que o checkout possa prosseguir (pedido ficará em 'waiting' até webhook confirmar)
													   resolve();
												   } else {
													   // Pagamento não aprovado, exiba erro
													   const statusDetail = response && response.detail && response.detail.status_detail ? response.detail.status_detail : '';
													   const customMessages = {
														   'pending_contingency': 'Pagamento pendente',
														   'cc_rejected_other_reason': 'Pagamento rejeitado',
														   'cc_rejected_insufficient_amount': 'Cartão rejeitado por falta de saldo. Tente um método de pagamento diferente',
														   'cc_rejected_bad_filled_security_code': 'Cartão recusado. Verifique os dados do cartão e tente novamente.',
														   'cc_rejected_bad_filled_date': 'Cartão recusado. Verifique os dados do cartão e tente novamente.',
														   'cc_rejected_bad_filled_other': 'Cartão recusado. Verifique os dados do cartão e tente novamente.'
													   };
													   // Tratamento específico para requer autorização no app do banco
													   if (statusDetail === 'cc_rejected_call_for_authorize') {
														   const authMessage = 'Pagamento aguardando confirmação. Autorize o pagamento no aplicativo do seu banco.';
														   try {
															   // Preferir a barra de notificação do Myd se disponível
															   if (window && window.Myd && typeof window.Myd.notificationBar === 'function') {
																   window.Myd.notificationBar('warning', authMessage);
															   } else {
																   // Usar o helper existente showErrorBar (mostra em vermelho). Ajustar visual para aviso
																   try {
																	   var tpl = document.getElementById('myd-popup-notification');
																	   var msg = document.getElementById('myd-popup-notification__message');
																	   if (tpl && msg) {
																		   msg.innerText = authMessage;
																		   tpl.style.background = '#ffae00'; // amarelo
																		   tpl.style.opacity = '1'; tpl.style.visibility = 'visible';
																		   setTimeout(function(){ try { tpl.style.opacity = '0'; tpl.style.visibility = 'hidden'; } catch(e){} }, 8000);
																	   } else {
																		   // fallback leve: usar alert se nada estiver disponível
																		   alert(authMessage);
																	   }
																   } catch (e) {
																	   alert(authMessage);
																   }
															   }
														   } catch (e) {
															   try { alert(authMessage); } catch (er) { /* noop */ }
														   }
														   // rejeitar a promise / flow
														   return reject();
													   }
													   const customMessage = statusDetail && customMessages[statusDetail] ? customMessages[statusDetail] : '';
													   if (customMessage) {
														   try {
														       if (window && window.Myd && typeof window.Myd.notificationBar === 'function') {
														           window.Myd.notificationBar('error', customMessage);
														       }
														   } catch(e) {}
													   } else {
														   let msg = 'Pagamento não aprovado.';
														   if (response.detail && response.detail.status_detail) {
														       msg += ' Motivo: ' + response.detail.status_detail;
														   } else if (response.detail) {
														       msg += '\nDetalhes: ' + JSON.stringify(response.detail, null, 2);
														   } else if (response.error) {
														       msg += ' Erro: ' + response.error;
														   } else {
														       msg += ' Erro desconhecido.';
														   }
														   alert(msg);
													   }
													   reject();
												   }
											   })
											   .catch((error) => {
												   // show notification and return to products (after short delay so bar is visible)
												   showErrorBar('Ops! O tempo para concluir seu pedido expirou. Por favor, tente novamente.');
												   setTimeout(function(){ showProductsTab(); }, 450);
												   reject();
											   });
									   });
								   },
								   onError: (error) => {
									   console.error(error);
								   },
							   },
						   };
						   console.log('[MYD] Criando Payment Brick...');
						   try {
							   window.paymentBrickController = await bricksBuilder.create(
								   'payment',
								   'paymentBrick_container',
								   settings
							   );
							   console.log('[MYD] Payment Brick criado com sucesso');
						   } catch (error) {
							   console.error('[MYD] Erro ao criar Payment Brick:', error);
							   document.getElementById('paymentBrick_container').innerHTML = '<div style="color:red">Erro ao inicializar Mercado Pago</div>';
						   }
					   }
				     }
				     if (document.readyState === 'loading') {
				       document.addEventListener('DOMContentLoaded', function() {
				           waitForSDKAndInit();
				       });
				     } else {
				       // DOM já carregado (conteúdo pode ter sido injetado dinamicamente)
				       waitForSDKAndInit();
				     }

				     function waitForSDKAndInit() {
				       if (typeof MercadoPago === 'undefined') {
				         console.log('[MYD] Aguardando SDK MercadoPago carregar...');
				         setTimeout(waitForSDKAndInit, 100);
				         return;
				       }
				       console.log('[MYD] SDK MercadoPago carregado, iniciando...');
				       initMercadoPagoBrick();
				     }
				   })();
				   </script>
			   </div>
		   </details>


		   <?php
		   // Exibir menu "Pagamento na entrega" apenas se algum método estiver ativo
		   $active_methods = [];
		   $payment_labels = [
			   'CRD' => __('Crédito', 'myd-delivery-pro'),
			   'DEB' => __('Débito', 'myd-delivery-pro'),
			   'VRF' => __('Vale-refeição', 'myd-delivery-pro'),
			   'DIN' => __('Dinheiro', 'myd-delivery-pro'),
		   ];
		   foreach ([
			   'CRD' => 'fdm-payment-credit',
			   'DEB' => 'fdm-payment-debit',
			   'VRF' => 'fdm-payment-vr',
			   'DIN' => 'fdm-payment-cash',
		   ] as $code => $opt) {
			   if (get_option($opt) === $code) {
				   $active_methods[$code] = $payment_labels[$code];
			   }
		   }
		   if (!empty($active_methods)) : ?>
		   <details open data-type="delivery-payment">
			   <summary>
				   <svg class="myd-details-arrow" width="12" height="12" viewBox="-4.5 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M249.365851,6538.70769 C249.770764,6539.09744 250.426289,6539.09744 250.830166,6538.70769 L259.393407,6530.44413 C260.202198,6529.66364 260.202198,6528.39747 259.393407,6527.61699 L250.768031,6519.29246 C250.367261,6518.90671 249.720021,6518.90172 249.314072,6519.28247 C248.899839,6519.67121 248.894661,6520.31179 249.302681,6520.70653 L257.196934,6528.32352 C257.601847,6528.71426 257.601847,6529.34685 257.196934,6529.73759 L249.365851,6537.29462 C248.960938,6537.68437 248.960938,6538.31795 249.365851,6538.70769" fill="#333" transform="translate(-249, -6519)"/></svg>
				   <?php esc_html_e('Pagamento na entrega', 'myd-delivery-pro'); ?>
			   </summary>
			   <?php foreach ($active_methods as $code => $label) : ?>
				   <div class="myd-cart__payment-option-wrapper">
					   <input
						   type="radio"
						   class="myd-cart__payment-input-option"
						   id="payopt-<?php echo esc_attr($code); ?>"
						   name="myd-payment-option"
						   value="<?php echo esc_attr($code); ?>"
					   >
					   <div class="myd-cart__payment-main">
						   <label for="payopt-<?php echo esc_attr($code); ?>">
							   <?php echo esc_html($label); ?>
						   </label>
						   <svg class="myd-cart__payment-input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="32px" height="32px"><path fill="#c8e6c9" d="M44,24c0,11.045-8.955,20-20,20S4,35.045,4,24S12.955,4,24,4S44,12.955,44,24z"/><path fill="#4caf50" d="M34.586,14.586l-13.57,13.586l-5.602-5.586l-2.828,2.828l8.434,8.414l16.395-16.414L34.586,14.586z"/></svg>
					   </div>
				   </div>
				   <?php if ($code === 'DIN') : ?>
				   <div id="myd-troco-popup" class="myd-troco-popup">
					   <div class="myd-troco-popup__content">
						   <div class="myd-troco-popup__title">Vai precisar de troco?</div>
						   <div class="myd-troco-popup__desc">Digite o valor que você vai pagar em dinheiro, para que a pessoa que for entregar seu pedido leve o troco para você.</div>
						   <input id="myd-troco-input" type="text" inputmode="decimal" class="myd-troco-popup__input" placeholder="Ex: 100,00">
						   <button id="myd-troco-confirm" class="myd-troco-popup__confirm myd-troco-popup__confirm--disabled" disabled>Confirmar</button>
						   <div class="myd-troco-popup__nochange-wrapper">
							   <span id="myd-troco-nochange" class="myd-troco-popup__nochange">Não preciso de troco</span>
						   </div>
					   </div>
				   </div>
				   <script>
				   (function(){
					   var trocoPopup = document.getElementById('myd-troco-popup');
					   var trocoInput = document.getElementById('myd-troco-input');
					   var trocoConfirm = document.getElementById('myd-troco-confirm');
					   var trocoNoChange = document.getElementById('myd-troco-nochange');
					   var radioDin = document.getElementById('payopt-DIN');
					   var totalLabel = document.querySelector('.myd-cart-payment-total-label');
					   var totalSummary = document.getElementById('myd-cart-total-summary');
					   var radioDin = document.getElementById('payopt-DIN');
					   var radioDinWrapper = radioDin ? radioDin.closest('.myd-cart__payment-option-wrapper') : null;
					   var radioDinMain = radioDinWrapper ? radioDinWrapper.querySelector('.myd-cart__payment-main') : null;
					   var trocoDisplay = null;
					   var cartTotal = 0;
					   function getCartTotal() {
						   var el = document.getElementById('myd-cart-total-summary');
						   if (!el) return 0;
						   var txt = el.textContent || '';
						   var val = txt.replace(/[^0-9,\.]/g, '').replace(',', '.');
						   return parseFloat(val) || 0;
					   }
					   function showTrocoPopup() {
						   cartTotal = getCartTotal();
						   trocoInput.value = '';
						   trocoInput.disabled = false;
						   // garantir botão confirm desativado ao abrir
						   if (trocoConfirm) { trocoConfirm.disabled = true; trocoConfirm.classList.add('myd-troco-popup__confirm--disabled'); }
						   trocoPopup.style.display = 'flex';
						   trocoInput.focus();
					   }
					   function hideTrocoPopup() {
						   trocoPopup.style.display = 'none';
					   }
					   function updateTrocoDisplay(valor) {
						   if (!trocoDisplay) {
							   trocoDisplay = document.createElement('div');
							   trocoDisplay.id = 'myd-troco-display';
							   trocoDisplay.className = 'myd-troco-display';
							   // Inserir logo após a área principal (label+svg) do Dinheiro
							   if (radioDinMain && radioDinMain.parentNode) {
								   radioDinMain.insertAdjacentElement('afterend', trocoDisplay);
							   } else if (radioDinWrapper) {
								   radioDinWrapper.appendChild(trocoDisplay);
							   }
						   }
						   trocoDisplay.textContent = 'Troco para: R$ ' + valor.toLocaleString('pt-BR', {minimumFractionDigits:2});
						   trocoDisplay.style.display = '';
					   }
					   function removeTrocoDisplay() {
						   if (trocoDisplay && trocoDisplay.parentNode) {
							   trocoDisplay.parentNode.removeChild(trocoDisplay);
							   trocoDisplay = null;
						   }
					   }
					   // Formatação automática do input para moeda
					   function formatCurrencyInput(e) {
						   var v = trocoInput.value.replace(/\D/g, '');
						   if (!v) {
							   trocoInput.value = '';
							   return;
						   }
						   // Limite de 9 dígitos (até 9.999.999,99)
						   if (v.length > 9) v = v.slice(0,9);
						   while (v.length < 3) v = '0' + v;
						   var intPart = v.slice(0, v.length - 2);
						   var decPart = v.slice(-2);
						   intPart = intPart.replace(/^0+/, '') || '0';
						   var intFormatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
						   trocoInput.value = 'R$ ' + intFormatted + ',' + decPart;
					   }
					   // Retorna valor numérico do input formatado
					   function getInputValue() {
						   var val = trocoInput.value.replace(/[^\d,]/g, '').replace(',', '.');
						   return parseFloat(val) || 0;
					   }
					   if (radioDin) {
						   radioDin.addEventListener('change', function(){
							   if (radioDin.checked) {
								   showTrocoPopup();
							   }
						   });
					   }
					   if (trocoNoChange) trocoNoChange.onclick = function(){
						   hideTrocoPopup();
						   removeTrocoDisplay();
						   try {
							   var changeInput = document.getElementById('input-payment-change');
							   if (changeInput) {
								   changeInput.value = '';
								   try { changeInput.removeAttribute('required'); } catch (e) {}
							   }
							   if (window && window.MydOrder && window.MydOrder.payment) window.MydOrder.payment.change = null;
							   if (window && window.MydCheckout && typeof window.MydCheckout.saveOnLocalStorage === 'function') {
								   try { window.MydCheckout.saveOnLocalStorage(); } catch (e) { }
							   }
						   } catch (e) { }
					   };
					   if (trocoConfirm) trocoConfirm.onclick = function(){
						   var valor = getInputValue();
						   if (isNaN(valor) || valor < cartTotal) {
							   trocoInput.value = '';
							   trocoInput.disabled = true;
							   trocoInput.placeholder = 'Valor deve ser maior que o total';
							   setTimeout(function(){
								   trocoInput.disabled = false;
								   trocoInput.placeholder = 'Ex: 100,00';
							   }, 1200);
							   return;
						   }
						   updateTrocoDisplay(valor);
						   // Salvar valor no input padrão (raw: somente números e vírgula, ex: "1234,56")
						   try {
							   var formattedDisplay = 'R$ ' + valor.toLocaleString('pt-BR', {minimumFractionDigits:2});
							   var raw = (Number.isFinite(valor) ? valor.toFixed(2) : (parseFloat(valor) || 0).toFixed(2));
							   raw = raw.replace('.', ',');
							   var changeInput = document.getElementById('input-payment-change');
							   if (changeInput) {
								   changeInput.value = raw;
								   try { changeInput.setAttribute('required', 'required'); } catch (e) {}
							   }
							   if (window && window.MydOrder && window.MydOrder.payment) {
								   window.MydOrder.payment.change = raw;
							   }
							   // persistir localmente se disponível
							   if (window && window.MydCheckout && typeof window.MydCheckout.saveOnLocalStorage === 'function') {
								   try { window.MydCheckout.saveOnLocalStorage(); } catch (e) { }
							   }
						   } catch (e) { /* noop */ }
						   hideTrocoPopup();
					   };
					   if (trocoInput) {
						   trocoInput.addEventListener('input', function(e){
							   var before = trocoInput.value;
							   formatCurrencyInput(e);
							   var valor = getInputValue();
							   // Não mostrar o troco enquanto digita — apenas habilitar/desabilitar o botão
							   if (valor < cartTotal) {
								   trocoConfirm.disabled = true;
								   trocoConfirm.classList.add('myd-troco-popup__confirm--disabled');
							   } else {
								   trocoConfirm.disabled = false;
								   trocoConfirm.classList.remove('myd-troco-popup__confirm--disabled');
							   }
						   });
						   // Permitir colar valor já formatado
						   trocoInput.addEventListener('paste', function(e){
							   setTimeout(function(){ formatCurrencyInput(); }, 0);
						   });
						   // Selecionar tudo ao focar
						   trocoInput.addEventListener('focus', function(){
							   setTimeout(function(){ trocoInput.select(); }, 10);
						   });
					   }

					   // Remover troco quando outra forma de pagamento for selecionada
					   try {
						   var paymentRadios = document.querySelectorAll('.myd-cart__payment-input-option');
						   Array.prototype.forEach.call(paymentRadios, function(r){
							   r.addEventListener('change', function(){
								   if (!document.getElementById('payopt-DIN') || !document.getElementById('payopt-DIN').checked) {
									   removeTrocoDisplay();
								   }
							   });
						   });
					   } catch (e) { /* noop */ }
				   })();
				   </script>
				   <?php endif; ?>
			   <?php endforeach; ?>
			   <label
				   class="myd-cart__checkout-label"
				   id="label-payment-change"
				   for="input-payment-change"
			   >
				   <?php esc_html_e( 'Change for', 'myd-delivery-pro' ); ?>
			   </label>
			   <input
				   type="text"
				   class="myd-cart__checkout-input"
				   id="input-payment-change"
				   name="input-payment-change"
				   inputmode="numeric"
				   data-mask="###.###.###,##"
				   data-mask-reverse="true"
				   autocomplete="off"
				   data-lpignore="true"
				   data-form-type="other"
			   >
		   </details>
		   <?php endif; ?>
	   </div>
	</div>
</div>
