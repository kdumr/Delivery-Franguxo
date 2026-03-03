<?php
/**
 * Novo
 */

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Tracing util (opt-in). Provides lightweight spans when option `myd_tracing_enabled` is true.
@include_once __DIR__ . '/tracing.php';

/**
 * API endpoint to check new orders.
 */
class Myd_Api {
		/**
		 * Expor public key do MercadoPago para o frontend (apenas GET, sem dados sensíveis)
		 */
		public function register_public_key_route() {
			\register_rest_route('myd-delivery/v1', '/public-key', [
				'methods' => 'GET',
				'callback' => function() {
					$public_key = get_option('mercadopago_public_key', '');
					if (!$public_key) {
						return new \WP_REST_Response(['error' => 'Chave pública não configurada'], 404);
					}
					return new \WP_REST_Response(['public_key' => $public_key], 200);
				},
				'permission_callback' => '__return_true',
			]);
		}
	/**
	 * Construct the class.
	 */
	public function __construct () {
		add_action( 'rest_api_init', [ $this, 'register_order_routes' ] );
		// Register Mercado Pago endpoints
		add_action( 'rest_api_init', [ $this, 'register_mercadopago_routes' ] );
		// Expor public key do MercadoPago
		add_action( 'rest_api_init', [ $this, 'register_public_key_route' ] );
		// Register push endpoints
		add_action( 'rest_api_init', [ $this, 'register_push_routes' ] );
		// Register store status endpoint
		add_action( 'rest_api_init', [ $this, 'register_store_routes' ] );
		// Register misc endpoints
		add_action( 'rest_api_init', [ $this, 'register_misc_routes' ] );
		// Register manual order endpoints
		add_action( 'rest_api_init', [ $this, 'register_manual_order_routes' ] );
		// Register create order page route
		add_action( 'template_redirect', [ $this, 'handle_create_order_page' ] );
		// Register Gemini Config route
		add_action( 'rest_api_init', [ $this, 'register_gemini_routes' ] );

		// Ensure AJAX handlers for whatsapp status are always available (both logged-in and anonymous)
		add_action('wp_ajax_myd_get_whatsapp_status', function() {
			$state = get_option('myd_whatsapp_connection_state', '');
			wp_send_json(['state' => $state]);
		});
		add_action('wp_ajax_nopriv_myd_get_whatsapp_status', function() {
			$state = get_option('myd_whatsapp_connection_state', '');
			wp_send_json(['state' => $state]);
		});

		// AJAX: retornar HTML do popup de produto sob demanda
		add_action('wp_ajax_myd_get_product_popup', function() {
			$id = isset($_POST['product_id']) ? intval($_POST['product_id']) : (isset($_GET['product_id']) ? intval($_GET['product_id']) : 0);
			if ( ! $id ) {
				wp_send_json_error(['message' => 'invalid_product_id']);
			}
			$post = get_post( $id );
			if ( ! $post ) {
				wp_send_json_error(['message' => 'product_not_found']);
			}

			$image_id = get_post_meta( $id, 'product_image', true );
			$image_url = wp_get_attachment_image_url( $image_id, 'large' );
			$product_price = get_post_meta( $id, 'product_price', true );
			$product_price = empty( $product_price ) ? 0 : $product_price;
			$currency_simbol = Store_Data::get_store_data( 'currency_simbol' );
			// extras via helper class
			$products_show = new Fdm_products_show();
			$extras_html = $products_show->format_product_extra( $id );

			ob_start();
			?>
			<div class="myd-product-popup__wrapper">
				<div class="myd-product-popup__image-container">
					<span class=fdm-popup-close-btn>
						<svg width="22px" height="22px" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path fill="#000000" d="M104.704 338.752a64 64 0 0 1 90.496 0l316.8 316.8 316.8-316.8a64 64 0 0 1 90.496 90.496L557.248 791.296a64 64 0 0 1-90.496 0L104.704 429.248a64 64 0 0 1 0-90.496z"/></svg>
					</span>

					<div class="myd-product-popup__img" data-image="<?php echo esc_attr( $image_url ); ?>">
						<?php echo wp_get_attachment_image( $image_id, 'medium', false, [ 'class' => 'myd-product-popup-img attachment-medium size-medium', 'alt' => 'MyD Delivery Product Image' ] ); ?>
					</div>
				</div>

				<div class="fdm-popup-product-content">
					<h3 class="myd-product-popup__title"><?php echo esc_html( get_the_title( $id ) ); ?></h3>
					<p class="myd-product-popup__description"><?php echo esc_html( get_post_meta( $id, 'product_description', true ) ); ?></p>

					<p class="myd-product-popup__price"><?php echo esc_html( $currency_simbol . ' ' . Myd_Store_Formatting::format_price( $product_price ) ); ?></p>

					<div class="myd-product-popup-extras">
						<div class="fdm-product-add-extras"><?php echo $extras_html; ?></div>
						<input type="text" id="myd-product-note-<?php echo esc_attr( $id ); ?>" placeholder="<?php echo esc_html__( 'any special requests?', 'myd-delivery-pro' ); ?>" class="myd-product-popup__note">
					</div>
				</div>

				<?php
				$user = wp_get_current_user();
				if ( !in_array( 'marketing', (array) $user->roles ) ) : ?>
				<div class="fdm-popup-product-action">
					<div class="fdm-popup-product-content-qty">
						<div class="fdm-click-minus">-</div>
						<input type="number" class="fdm-popup-input-text fmd-item-qty" value="1" min="1" max="10" pattern="\d*" readonly="readonly">
						<div class="fdm-click-plus">+</div>
					</div>

					<div class="fdm-popup-product-content-add-cart">
						<a
							class="fdm-add-to-cart-popup"
							id="<?php echo esc_attr( $id ); ?>"
							data-name="<?php echo esc_attr( get_the_title( $id ) ); ?>"
							data-price="<?php echo Myd_Store_Formatting::format_price( $product_price ); ?>"
							data-image="<?php echo esc_attr( $image_url ); ?>"
							data-product-id="<?php echo esc_attr( get_post_meta( $id, 'product_id', true ) ); ?>"
							data-text="<?php esc_attr_e( 'Add to bag', 'myd-delivery-pro' ); ?>">
							<span class="myd-add-to-cart-button__text"><?php esc_html_e( 'Add to bag', 'myd-delivery-pro' ); ?></span>
						</a>
					</div>
				</div>
				<?php endif; ?>
			</div>
			<?php
			$html = ob_get_clean();
			wp_send_json_success(['html' => $html]);
		});
		add_action('wp_ajax_nopriv_myd_get_product_popup', function() {
			// simply reuse the logged-in handler to avoid duplication
			// forward to same logic via POST
			$_POST = $_POST ? $_POST : $_GET;
			return do_action('wp_ajax_myd_get_product_popup');
		});

		// When MercadoPago reports an approved payment, update linked order if possible
		add_action('myd_mercadopago_payment_approved', [ $this, 'handle_mp_approved' ], 10, 2);
	}

	/**
	 * Register Gemini Server config route
	 */
	public function register_gemini_routes() {
		\register_rest_route('myd-delivery/v1', '/gemini/config', [
			'methods' => 'GET',
			'callback' => [$this, 'get_gemini_config'],
			'permission_callback' => '__return_true', // Em produção, ideal ter uma chave/token entre Node e WP
		]);

		\register_rest_route('myd-delivery/v1', '/gemini/orders/active/(?P<phone>[\d]+)', [
			'methods' => 'GET',
			'callback' => [$this, 'get_gemini_active_orders'],
			'permission_callback' => '__return_true', // Permitir acesso da IA
		]);
	}

	/**
	 * Output config block for Node.js Gemini API Server
	 */
	public function get_gemini_config( $request ) {
		$config = [
			'gemini_enabled'       => get_option('gemini_enabled', 'no') === 'yes',
			'gemini_api_key'       => get_option('gemini_api_key', ''),
			'gemini_system_prompt' => get_option('gemini_system_prompt', ''),
			'evolution_api_url'    => get_option('evolution_api_url', ''),
			'evolution_api_key'    => get_option('evolution_api_key', ''),
			'evolution_instance'   => get_option('evolution_instance_name', '')
		];

		return rest_ensure_response( $config );
	}

	/**
	 * Output active orders for Gemini based on phone number
	 */
	public function get_gemini_active_orders( $request ) {
		$phone = $request->get_param('phone');
		// Clean phone number (remove DDI if it's 55 to match local DB format, usually it's saved raw)
		// For safety, let's search with the exact phone, and also fallback to stripping '55' if it's BR
		$phone_variants = [ $phone ];
		if ( strlen($phone) > 11 && strpos($phone, '55') === 0 ) {
			$phone_variants[] = substr($phone, 2); // sem 55
		}

		$orders = [];
		$active_statuses = ['waiting', 'paid', 'in-production', 'sent'];

		foreach ( $phone_variants as $p ) {
			$args = [
				'post_type' => 'mydelivery-orders',
				'post_status' => 'publish', // Pedidos salvos
				'posts_per_page' => 10,
				'meta_query' => [
					'relation' => 'AND',
					[
						'key' => 'customer_phone',
						'value' => $p,
						'compare' => 'LIKE' // Use LIKE in case of formatting like (11) 9...
					],
					[
						'key' => 'order_status',
						'value' => $active_statuses,
						'compare' => 'IN'
					]
				]
			];

			$query = new \WP_Query( $args );
			if ( $query->have_posts() ) {
				foreach ( $query->posts as $post ) {
					$order_id = $post->ID;
					// Parse the order details to make it short and context-friendly for AI
					$status = get_post_meta( $order_id, 'order_status', true );
					// Translate status for AI
					$status_br = [
						'waiting' => 'Aguardando Pagamento/Aprovação',
						'paid' => 'Pago e Aguardando Produção',
						'in-production' => 'Sendo Preparado / Na Cozinha',
						'sent' => 'Saiu para Entrega / A Caminho'
					];
					
					$items_raw = get_post_meta($order_id, 'myd_order_items', true) ?: [];
					$items_summary = [];
					foreach($items_raw as $it) {
						$items_summary[] = $it['product_name'] ?? 'Item';
					}

					$orders[] = [
						'numero_pedido' => $order_id,
						'status_atual' => $status_br[$status] ?? $status,
						'total' => get_post_meta( $order_id, 'order_total', true ),
						'metodo_pagamento' => get_post_meta( $order_id, 'order_payment_method', true ),
						'itens_resumo' => implode(', ', $items_summary)
					];
				}
				break; // Found orders for this variant, stop searching
			}
		}

		return rest_ensure_response( $orders );
	}

	/**
	 * Register Mercado Pago routes
	 */
	public function register_mercadopago_routes() {
		// Helper to register the same route on two namespaces
		$ns = function($path){ return [ 'myd-delivery/v1', 'my-delivery/v1' ]; };

		// preference
		foreach ($ns('/mercadopago/preference') as $namespace) {
			\register_rest_route($namespace, '/mercadopago/preference', [
				'methods' => 'POST',
				'callback' => function($request) {
					$params = $request->get_json_params();
					$amount = isset($params['amount']) ? floatval($params['amount']) : 0;
					// Accept payer either at top-level or nested under detail->payer
					$payer = [];
					if (!empty($params['detail']) && is_array($params['detail']) && !empty($params['detail']['payer']) && is_array($params['detail']['payer'])) {
						$payer = $params['detail']['payer'];
					} elseif (!empty($params['payer']) && is_array($params['payer'])) {
						$payer = $params['payer'];
					}
					$access_token = get_option('mercadopago_access_token', '');
					if (!$access_token || $amount <= 0) {
						return new \WP_REST_Response(['error'=>'Dados insuficientes para criar preferência'], 400);
					}
					// Ensure we always provide a payer email (use test account as fallback)
					if (empty($payer['email'])) { $payer['email'] = 'cadudznmrpena@gmail.com'; }
					$body = [
						'items' => [
							[
								'title' => 'Pedido Online',
								'quantity' => 1,
								'unit_price' => $amount,
								'currency_id' => 'BRL',
                                
							],
                            
						],
						'payer' => $payer,
					];
					$response = wp_remote_post('https://api.mercadopago.com/checkout/preferences', [
						'headers' => [
							'Authorization' => 'Bearer ' . $access_token,
							'Content-Type' => 'application/json',
						],
						'body' => json_encode($body),
						'timeout' => 20,
					]);
					if ( is_wp_error($response) ) {
						return new \WP_REST_Response(['error'=>'Erro ao conectar MercadoPago'], 500);
					}
					$data = json_decode(wp_remote_retrieve_body($response), true);
					if ( isset($data['id']) ) {
						return new \WP_REST_Response(['preferenceId'=>$data['id']], 200);
					}
					return new \WP_REST_Response(['error'=>'Erro ao criar preferência','details'=>$data], 500);
				},
				'permission_callback' => '__return_true',
			]);
		}

		// webhook
		foreach ($ns('/mercadopago/webhook') as $namespace) {
			\register_rest_route($namespace, '/mercadopago/webhook', [
				'methods' => [ 'POST', 'GET' ],
				'callback' => function($request) {
					// Start tracing span for webhook handling (opt-in)
					$trace_span = null;
					if (class_exists('MydPro\\Includes\\Myd_Tracing')) {
						$trace_span = \MydPro\Includes\Myd_Tracing::start_span('mercadopago.webhook', ['method' => strtoupper($request->get_method())]);
					}
					if (strtoupper($request->get_method()) === 'GET') {
						if ($trace_span) { \MydPro\Includes\Myd_Tracing::add_event($trace_span, 'http_get_ping', ['message' => 'healthcheck']); \MydPro\Includes\Myd_Tracing::end_span($trace_span, ['status'=>'ok']); }
						return new \WP_REST_Response([
							'status' => 'online',
							'message' => 'Use POST (Mercado Pago Webhooks) para enviar notificações.',
						], 200);
					}
					$access_token = get_option('mercadopago_access_token', '');
					if (!$access_token) {
						if ($trace_span) { \MydPro\Includes\Myd_Tracing::add_event($trace_span, 'error', ['reason'=>'access_token_missing']); \MydPro\Includes\Myd_Tracing::end_span($trace_span, ['status'=>'error','code'=>'access_token_missing']); }
						return new \WP_REST_Response(['error' => 'Access Token ausente'], 400);
					}

					$raw_body = $request->get_body();
					$payload = json_decode($raw_body, true);
					$headers = function_exists('getallheaders') ? getallheaders() : [];

					// --- Validação x-signature Mercado Pago ---
					// Espera header x-signature no formato: ts=...,v1=hexsignature
					$xSignatureHeader = '';
					if (!empty($headers['x-signature'])) { $xSignatureHeader = $headers['x-signature']; }
					elseif (!empty($headers['X-Signature'])) { $xSignatureHeader = $headers['X-Signature']; }
					elseif (!empty($headers['x-signature'][0])) { $xSignatureHeader = $headers['x-signature'][0]; }

					$ts = null; $v1 = null;
					if ($xSignatureHeader) {
						$parts = explode(',', $xSignatureHeader);
						foreach ($parts as $p) {
							$kv = explode('=', trim($p), 2);
							if (count($kv) === 2) {
								if ($kv[0] === 'ts') $ts = $kv[1];
								if ($kv[0] === 'v1') $v1 = $kv[1];
							}
						}
					}

					// montar manifest: id:[data.id_url];request-id:[x-request-id];ts:[ts];
					$data_id_url = '';
					if (!empty($_GET['data.id'])) { $data_id_url = strtolower((string) $_GET['data.id']); }
					elseif (is_array($payload) && !empty($payload['data']['id'])) { $data_id_url = is_scalar($payload['data']['id']) ? strtolower((string)$payload['data']['id']) : ''; }
					$xRequestId = isset($_SERVER['HTTP_X_REQUEST_ID']) ? $_SERVER['HTTP_X_REQUEST_ID'] : '';
					$manifest = 'id:' . $data_id_url . ';request-id:' . $xRequestId . ';ts:' . ($ts ?? '') . ';';

					// Recupera o segredo salvo nas configurações do plugin
					$webhook_secret = get_option('mercadopago_webhook_secret', '');
					$assinatura_valida = false;
					if ($webhook_secret && $v1 && $manifest) {
						$sha = hash_hmac('sha256', $manifest, $webhook_secret);
						if (hash_equals($sha, $v1)) {
							$assinatura_valida = true;
						}
					}
					// Se a assinatura não for válida, retorna 401 e PARA TUDO imediatamente
					if (!$assinatura_valida) {
						if ($trace_span) { \MydPro\Includes\Myd_Tracing::add_event($trace_span, 'webhook_signature_invalid', ['manifest'=>$manifest, 'v1'=>$v1]); \MydPro\Includes\Myd_Tracing::end_span($trace_span, ['status'=>'invalid_signature']); }
						// Garantir que absolutamente nada abaixo seja executado
						return new \WP_REST_Response(['error'=>'Assinatura do webhook Mercado Pago inválida.'], 401);
					}
					// --- Fim validação x-signature ---
					if ($trace_span) {
						\MydPro\Includes\Myd_Tracing::add_event($trace_span, 'x_signature_info', [
							'has_x_signature' => (bool)$xSignatureHeader,
							'ts' => $ts,
							'v1_present' => (bool)$v1,
							'manifest' => $manifest,
						]);
					}
					if (defined('WP_DEBUG') && WP_DEBUG) {
						error_log('[MYD][MP Webhook] x-signature verificada. Manifest:' . $manifest);
					}
					if (defined('WP_DEBUG') && WP_DEBUG) {
						error_log('[MYD][MP Webhook] Headers: ' . wp_json_encode($headers));
						error_log('[MYD][MP Webhook] Body: ' . $raw_body);
					}

					$payment_id = '';
					if (is_array($payload)) {
						// Prefer the payment id sent in payload.data.id (this is the actual payment resource id)
						// Some MercadoPago webhook payloads include a top-level "id" which is the notification id
						// and not the payment id. Using data.id ensures we target the correct payment.
						if (!empty($payload['data']['id'])) {
							$payment_id = (string) $payload['data']['id'];
						} elseif (!empty($payload['id'])) {
							$payment_id = (string) $payload['id'];
						}
					}
					if (!$payment_id) {
						$q_id = $request->get_param('id');
						if (!$q_id) { $q_id = $request->get_param('data.id'); }
						if ($q_id) { $payment_id = (string) $q_id; }
					}

					if (!$payment_id) {
						if ($trace_span) { \MydPro\Includes\Myd_Tracing::add_event($trace_span, 'ignored', ['reason' => 'payment_id_not_found']); \MydPro\Includes\Myd_Tracing::end_span($trace_span, ['status'=>'ignored']); }
						return new \WP_REST_Response(['status' => 'ignored', 'reason' => 'payment_id_not_found'], 200);
					}

					$action = is_array($payload) && isset($payload['action']) ? (string) $payload['action'] : '';
					// If webhook action indicates update, mark for reporting but still query MercadoPago API
					$is_action_webhook = ($action === 'payment.updated');

					// Call MercadoPago API to fetch payment details - instrument as sub-span
					$mp_span = null;
					if ($trace_span) { $mp_span = \MydPro\Includes\Myd_Tracing::start_span('mercadopago.api_get_payment', ['payment_id' => $payment_id]); }
					$response = wp_remote_get('https://api.mercadopago.com/v1/payments/' . rawurlencode($payment_id), [
						'headers' => [
							'Authorization' => 'Bearer ' . $access_token,
							'Content-Type' => 'application/json',
							'X-meli-session-id' => $deviceId,
						],
						'timeout' => 20,
					]);
					if ( is_wp_error($response) ) {
						if ($mp_span) { \MydPro\Includes\Myd_Tracing::add_event($mp_span, 'http_error', ['message' => $response->get_error_message()]); \MydPro\Includes\Myd_Tracing::end_span($mp_span, ['status'=>'error']); }
						if ($trace_span) { \MydPro\Includes\Myd_Tracing::end_span($trace_span, ['status'=>'error','code'=>'mp_api_error']); }
						return new \WP_REST_Response(['error' => 'Erro ao consultar pagamento'], 500);
					}
					$data = json_decode(wp_remote_retrieve_body($response), true);
					if ($mp_span) { \MydPro\Includes\Myd_Tracing::add_event($mp_span, 'http_response', ['http_code' => wp_remote_retrieve_response_code($response), 'body_preview' => substr((string)wp_remote_retrieve_body($response), 0, 512)]); \MydPro\Includes\Myd_Tracing::end_span($mp_span, ['http_code' => wp_remote_retrieve_response_code($response)]); }
					$status = $data['status'] ?? null;
					$status_detail = $data['status_detail'] ?? null;
					$method = $data['payment_method_id'] ?? null;

					if ($status === 'approved') {
						set_transient('myd_mp_approved_' . $payment_id, [
							'status' => $status,
							'status_detail' => $status_detail,
							'payment_method' => $method,
							'updated_at' => time(),
						], DAY_IN_SECONDS);
						do_action('myd_mercadopago_payment_approved', $payment_id, $data);
					}

					return new \WP_REST_Response([
						'status' => 'ok',
						'payment_id' => $payment_id,
						'payment_status' => $status,
						'status_detail' => $status_detail ?? null,
						'via' => $is_action_webhook ? 'webhook_action' : 'api_poll',
					], 200);
				},
				'permission_callback' => '__return_true',
			]);
		}

		// payment_status
		foreach ($ns('/mercadopago/payment_status') as $namespace) {
			\register_rest_route($namespace, '/mercadopago/payment_status', [
				'methods' => 'GET',
				'callback' => function($request) {
					$payment_id = $request->get_param('id');
					if (!$payment_id) { $payment_id = $request->get_param('payment_id'); }
					$payment_id = is_scalar($payment_id) ? (string) $payment_id : '';
					$access_token = get_option('mercadopago_access_token', '');
					if (!$access_token || $payment_id === '') {
						return new \WP_REST_Response(['error' => 'Parâmetros inválidos'], 400);
					}
					$response = wp_remote_get('https://api.mercadopago.com/v1/payments/' . rawurlencode($payment_id), [
						'headers' => [
							'Authorization' => 'Bearer ' . $access_token,
							'Content-Type' => 'application/json',
						],
						'timeout' => 20,
					]);
					if ( is_wp_error($response) ) {
						return new \WP_REST_Response(['error' => 'Erro ao consultar MercadoPago'], 500);
					}
					$data = json_decode(wp_remote_retrieve_body($response), true);
					if ( isset($data['status']) ) {
						// If payment is approved, trigger the same action as webhook so orders get updated
						try {
							if ( isset($data['status']) && $data['status'] === 'approved' ) {
								set_transient('myd_mp_approved_' . $payment_id, [
									'status' => $data['status'],
									'status_detail' => $data['status_detail'] ?? null,
									'payment_method' => $data['payment_method_id'] ?? null,
									'updated_at' => time(),
								], DAY_IN_SECONDS);
								// Fire action to let other handlers (e.g. order updater) process the approved payment
								do_action('myd_mercadopago_payment_approved', $payment_id, $data);
							}
						} catch ( \Throwable $e ) {
							// ignore errors here to avoid breaking the API response
						}
						return new \WP_REST_Response([
							'id' => $data['id'] ?? $payment_id,
							'status' => $data['status'],
							'status_detail' => $data['status_detail'] ?? null,
							'point_of_interaction' => $data['point_of_interaction'] ?? null,
						], 200);
					}
					return new \WP_REST_Response(['error' => 'Resposta inválida do MercadoPago', 'details' => $data], 500);
				},
				'permission_callback' => '__return_true',
			]);
		}

		// process_payment
		foreach ($ns('/mercadopago/process_payment') as $namespace) {
			\register_rest_route($namespace, '/mercadopago/process_payment', [
				'methods' => 'POST',
				'callback' => function($request) {
					$params = $request->get_json_params();
					$access_token = get_option('mercadopago_access_token', '');
					if (!$access_token) {
						return new \WP_REST_Response(['error'=>'Chave de acesso MercadoPago ausente'], 400);
					}

					// Find order_id to get product title
					$order_id = null;
					$product_title = $params['description'] ?? 'Pedido Online';
					$description = $product_title; // Initialize description
					
					// 1) explicit param from frontend
					if (!empty($params['order_id'])) {
						$order_id = intval($params['order_id']);
					} elseif (!empty($params['orderId'])) {
						$order_id = intval($params['orderId']);
					}
					
					// 3) fallback: if user logged in, get latest draft linked to user
					if (empty($order_id) && is_user_logged_in()) {
						$user_id = get_current_user_id();
						$existing = get_posts(array(
							'post_type' => 'mydelivery-orders',
							'posts_per_page' => 1,
							'post_status' => 'draft',
							'meta_key' => 'myd_customer_id',
							'meta_value' => $user_id,
							'orderby' => 'date',
							'order' => 'DESC'
						));
						if (!empty($existing) && isset($existing[0]->ID)) { 
							$order_id = intval($existing[0]->ID); 
						}
					}
					
					// Get product title, quantity and unit price from order metadata if available
					if (!empty($order_id) && get_post($order_id) && get_post($order_id)->post_type === 'mydelivery-orders') {
						$order_items = \MydPro\Includes\Myd_Orders_Front_Panel::parse_order_items( get_post_meta($order_id, 'myd_order_items', true) );
						if (!empty($order_items) && isset($order_items[0]['product_name'])) {
							$product_title = $order_items[0]['product_name'];
							// Extract quantity from title (e.g., "1 x Teste" -> 1)
							if (preg_match('/^(\d+)\s*x\s*/', $product_title, $matches)) {
								$product_quantity = intval($matches[1]);
							} else {
								$product_quantity = 1; // Default quantity
							}
							// Get unit price from order metadata
							$product_unit_price_raw = isset($order_items[0]['product_price']) ? $order_items[0]['product_price'] : '0';
							// Convert Brazilian format (comma) to American format (dot) for Mercado Pago
							$product_unit_price = floatval(str_replace(',', '.', $product_unit_price_raw));
							// Get product ID from order metadata
							$product_id = isset($order_items[0]['product_id']) ? $order_items[0]['product_id'] : $order_id;
							// Get product extras from order metadata
							$product_extras = isset($order_items[0]['product_extras']) ? $order_items[0]['product_extras'] : '';
							// Build description: "{nome do pedido} | {extras do produto}"
							$description = $product_title . ' | ' . $product_extras;
						} else {
							// Fallback to post title if order items not found
							$order_post = get_post($order_id);
							if ($order_post && !empty($order_post->post_title)) {
								$product_title = $order_post->post_title;
								$product_quantity = 1; // Default quantity
								$product_unit_price = 0; // Default price
								$product_extras = '';
								$description = $product_title . ' | Extras: ' . $product_extras;
							}
						}
					}

					$payment_data = [
						'transaction_amount' => isset($params['transaction_amount']) ? floatval($params['transaction_amount']) : 0,
						'token' => $params['token'] ?? '',
						'description' => $description,
						'external_reference' => $order_id,
						'installments' => $params['installments'] ?? 1,
						'payment_method_id' => $params['payment_method_id'] ?? '',
						'issuer_id' => $params['issuer_id'] ?? '',
						'statement_descriptor' => 'FRANGUXO', // Nome da loja na fatura (máx. 13 caracteres)
						// prefer detail->payer, then top-level payer; fallback to test email
						'payer' => [
							'email' => $params['detail']['payer']['email'] ?? ($params['payer']['email'] ?? 'cadudznmrpena@gmail.com'),
							'identification' => $params['detail']['payer']['identification'] ?? ($params['payer']['identification'] ?? []),
						],
						'additional_info' => [
							'items' => [
								[
									'id' => $product_id,
									'title' => $product_title,
									'description' => $description,
									'quantity' => $product_quantity,
									'unit_price' => $product_unit_price,
									'category_id' => 'Food'
								]
							]
						]
					];
					// Só adiciona o campo device se o session_id estiver preenchido
					if (!empty($params['device_id'])) {
						$payment_data['device'] = [
							'session_id' => $params['device_id']
						];
					}
					$payment_data = array_filter($payment_data, function($v) { return $v !== '' && $v !== null; });
					$idempotency_key = uniqid('mp_', true) . '-' . bin2hex(random_bytes(8));
					// Temporary debug logs: record request/response when WP_DEBUG is enabled.
					// Remove these logs after debugging to avoid leaking sensitive data.
					if ( defined('WP_DEBUG') && WP_DEBUG ) {
						try {
							error_log('[MYD][MP][PAYMENT_REQUEST] ' . wp_json_encode($payment_data));
						} catch ( \Throwable $_ ) {}
					}

					   $headers = [
						   'Authorization' => 'Bearer ' . $access_token,
						   'Content-Type' => 'application/json',
						   'X-Idempotency-Key' => $idempotency_key,
					   ];
					   if (!empty($params['device_id'])) {
						   $headers['X-meli-session-id'] = $params['device_id'];
					   }
					   $response = wp_remote_post('https://api.mercadopago.com/v1/payments', [
						   'headers' => $headers,
						   'body' => json_encode($payment_data),
						   'timeout' => 20,
					   ]);
					if ( defined('WP_DEBUG') && WP_DEBUG ) {
						try {
							$code = is_wp_error($response) ? 'WP_ERROR' : wp_remote_retrieve_response_code($response);
							error_log('[MYD][MP][PAYMENT_RESPONSE_CODE] ' . $code);
							$body = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
							// Limit size to avoid huge logs
							error_log('[MYD][MP][PAYMENT_RESPONSE_BODY] ' . substr((string)$body, 0, 4000));
						} catch ( \Throwable $_ ) {}
					}
					if ( is_wp_error($response) ) {
						return new \WP_REST_Response(['error'=>'Erro ao conectar MercadoPago'], 500);
					}
					$data = json_decode(wp_remote_retrieve_body($response), true);
					if ( isset($data['status']) ) {
						// Persist payment info into linked order post if possible
						$mp_status = $data['status'];
						$mp_id = isset($data['id']) ? $data['id'] : null;
						$mp_payment_method = $data['payment_method_id'] ?? ($data['payment_method'] ?? null);
						// Attempt to find order id from incoming params or MP response
						$order_id = null;
						// 1) explicit param from frontend
						if (!empty($params['order_id'])) {
							$order_id = intval($params['order_id']);
						} elseif (!empty($params['orderId'])) {
							$order_id = intval($params['orderId']);
						}
						// 2) metadata or external_reference from MP request (either request params or response)
						if (empty($order_id) && !empty($params['metadata']['order_id'])) {
							$order_id = intval($params['metadata']['order_id']);
						}
						if (empty($order_id) && !empty($data['metadata']['order_id'])) {
							$order_id = intval($data['metadata']['order_id']);
						}
						if (empty($order_id) && !empty($data['external_reference'])) {
							// external_reference sometimes contains order id
							$order_id_candidate = filter_var($data['external_reference'], FILTER_SANITIZE_NUMBER_INT);
							if ($order_id_candidate !== '') { $order_id = intval($order_id_candidate); }
						}
						// 3) fallback: if user logged in, get latest draft linked to user
						if (empty($order_id) && is_user_logged_in()) {
							$user_id = get_current_user_id();
							$existing = get_posts(array(
								'post_type' => 'mydelivery-orders',
								'posts_per_page' => 1,
								'post_status' => 'draft',
								'meta_key' => 'myd_customer_id',
								'meta_value' => $user_id,
								'orderby' => 'date',
								'order' => 'DESC'
							));
							if (!empty($existing) && isset($existing[0]->ID)) { $order_id = intval($existing[0]->ID); }
						}
						// 4) If we have an order, update post meta accordingly
						if (!empty($order_id) && get_post($order_id) && get_post($order_id)->post_type === 'mydelivery-orders') {
							if ($mp_id) {
								Order_Meta::set_payment_dataid( $order_id, $mp_id );
							}
							// Map MP status to our admin status: pending -> waiting, approved -> paid
							$mapped = '';
							if ($mp_status === 'pending') { $mapped = 'waiting'; }
							elseif ($mp_status === 'approved') { $mapped = 'paid'; }
							if ($mapped !== '') {
								update_post_meta($order_id, 'order_payment_status', sanitize_text_field($mapped));
							}
							if ($mp_payment_method) {
								$mp_payment_method = sanitize_text_field($mp_payment_method);
								if (strtolower($mp_payment_method) === 'pix') {
									$mp_payment_method = 'PIX';
								}
								update_post_meta($order_id, 'order_payment_method', $mp_payment_method);
							}
							// Mark the order as payment integration type when processed via MercadoPago
							update_post_meta($order_id, 'order_payment_type', 'payment-integration');
							// Notify push server (if configured)
							try {
								if ( class_exists('MydPro\\Includes\\Push\\Push_Notifier') ) {
									\MydPro\Includes\Push\Push_Notifier::notify( get_post_meta( $order_id, 'myd_customer_id', true ), $order_id, get_post_meta($order_id, 'order_status', true) );
								}
							} catch(\Exception $e) {}
						}
						// If PIX payment, store creation time for auto-cancel after 10 seconds
						if ($mp_payment_method === 'pix' && $mp_id) {
							set_transient('myd_pix_created_' . $mp_id, time(), 60); // expire in 1 minute
						}
						return new \WP_REST_Response(['status'=>$data['status'], 'id'=>$data['id'], 'detail'=>$data], 200);
					}
					return new \WP_REST_Response(['error'=>'Erro ao processar pagamento','details'=>$data], 500);
				},
				'permission_callback' => '__return_true',
			]);
		}

		// pix await_approval
		foreach ($ns('/mercadopago/pix/await_approval') as $namespace) {
			\register_rest_route($namespace, '/mercadopago/pix/await_approval', [
				'methods' => 'GET',
				'callback' => function($request) {
					$payment_id = $request->get_param('payment_id');
					$payment_id = is_scalar($payment_id) ? (string) $payment_id : '';
					if (!$payment_id) {
						return new \WP_REST_Response(['error' => 'payment_id ausente'], 400);
					}
					$started = time();
					$timeout = 25;
					while ((time() - $started) < $timeout) {
						$flag = get_transient('myd_mp_approved_' . $payment_id);
						if ($flag && is_array($flag)) {
							return new \WP_REST_Response(['status' => 'approved', 'info' => $flag], 200);
						}
						usleep(500000);
					}
					return new \WP_REST_Response(['status' => 'waiting'], 200);
				},
				'permission_callback' => '__return_true',
			]);
		}

		// pix sse
		foreach ($ns('/mercadopago/pix/sse') as $namespace) {
			\register_rest_route($namespace, '/mercadopago/pix/sse', [
				'methods' => 'GET',
				'callback' => function($request) {
					$payment_id = $request->get_param('payment_id');
					$payment_id = is_scalar($payment_id) ? (string) $payment_id : '';
					if (!$payment_id) {
						return new \WP_REST_Response(['error' => 'payment_id ausente'], 400);
					}
					nocache_headers();
					header('Content-Type: text/event-stream');
					header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
					header('Pragma: no-cache');
					header('Connection: keep-alive');
					header('X-Accel-Buffering: no');
					if (function_exists('ignore_user_abort')) { @ignore_user_abort(true); }
					@set_time_limit(0);
					while (ob_get_level() > 0) { @ob_end_flush(); }
					@ob_implicit_flush(true);

					$start = time();
					$timeout = 300;
					$cancelled = false;
					echo "event: open\n";
					echo "data: ok\n\n";
					flush();

					while ((time() - $start) < $timeout) {
						$flag = get_transient('myd_mp_approved_' . $payment_id);
						if ($flag && is_array($flag)) {
							echo "event: approved\n";
							echo 'data: ' . json_encode(['payment_id' => $payment_id, 'info' => $flag]) . "\n\n";
							flush();
							exit;
						}
						// Check if 10 seconds passed and PIX not approved, cancel it
						if (!$cancelled && (time() - $start) >= 10) {
							$created_time = get_transient('myd_pix_created_' . $payment_id);
							if ($created_time && (time() - $created_time) >= 10) {
								// Cancel the PIX payment
								$access_token = get_option('mercadopago_access_token', '');
								if ($access_token) {
									$cancel_response = wp_remote_request('https://api.mercadopago.com/v1/payments/' . rawurlencode($payment_id), [
										'method' => 'PUT',
										'headers' => [
											'Authorization' => 'Bearer ' . $access_token,
											'Content-Type' => 'application/json',
										],
										'body' => json_encode(['status' => 'cancelled']),
										'timeout' => 10,
									]);
									if (!is_wp_error($cancel_response)) {
										$cancelled = true;
										echo "event: cancelled\n";
										echo 'data: ' . json_encode(['payment_id' => $payment_id, 'reason' => 'timeout_10s']) . "\n\n";
										flush();
										exit;
									}
								}
							}
						}
						static $i = 0; $i++;
						if ($i % 5 === 0) {
							echo "event: keepalive\n";
							echo "data: ping\n\n";
							flush();
						}
						sleep(1);
					}
					echo "event: timeout\n";
					echo "data: done\n\n";
					flush();
					exit;
				},
				'permission_callback' => '__return_true',
			]);
		}

		// diagnostics ping
		foreach ($ns('/mercadopago/ping') as $namespace) {
			\register_rest_route($namespace, '/mercadopago/ping', [
				'methods' => 'GET',
				'callback' => function() { return new \WP_REST_Response(['status' => 'ok'], 200); },
				'permission_callback' => '__return_true',
			]);
		}
	}

	/**
	 * Handle approved MercadoPago payment (triggered by webhook)
	 * Attempt to find linked `mydelivery-orders` post and mark it paid.
	 */
	public function handle_mp_approved( $payment_id, $data ) {
		try {
			$payment_id = (string) $payment_id;
			// Try to find order id from payload: external_reference or metadata
			$order_id = null;
			if ( is_array($data) ) {
				if (!empty($data['external_reference'])) {
					$candidate = filter_var($data['external_reference'], FILTER_SANITIZE_NUMBER_INT);
					if ($candidate !== '') $order_id = intval($candidate);
				}
				if (!$order_id && !empty($data['metadata']) && is_array($data['metadata']) && !empty($data['metadata']['order_id'])) {
					$order_id = intval($data['metadata']['order_id']);
				}
			}
			// If not found, try to locate by post meta order_payment_dataid == payment_id
			if (empty($order_id)) {
				$posts = get_posts([ 'post_type'=>'mydelivery-orders', 'meta_key'=>'order_payment_dataid', 'meta_value'=>$payment_id, 'posts_per_page'=>1 ]);
				if (!empty($posts) && isset($posts[0]->ID)) $order_id = intval($posts[0]->ID);
			}
			// If still not found, try by external_reference stored as meta 'order_external_reference'
			if (empty($order_id) && is_array($data) && !empty($data['external_reference'])) {
				$cand = sanitize_text_field((string)$data['external_reference']);
				$posts = get_posts([ 'post_type'=>'mydelivery-orders', 'meta_key'=>'order_external_reference', 'meta_value'=>$cand, 'posts_per_page'=>1 ]);
				if (!empty($posts) && isset($posts[0]->ID)) $order_id = intval($posts[0]->ID);
			}

			if (empty($order_id)) {
				// Nothing to update
				return;
			}
			// Ensure post exists and has correct post type
			$post = get_post($order_id);
			if (!$post || $post->post_type !== 'mydelivery-orders') return;
			// Map status
			$mp_status = is_array($data) && isset($data['status']) ? $data['status'] : 'approved';
			$mapped = '';
			if ($mp_status === 'pending') $mapped = 'waiting';
			elseif ($mp_status === 'approved') $mapped = 'paid';
			// Update metadata
			if ($payment_id) Order_Meta::set_payment_dataid( $order_id, $payment_id );
			if ($mapped !== '') {
				update_post_meta($order_id, 'order_payment_status', sanitize_text_field($mapped));
				// If payment mapped to 'paid', ensure order_status is set to 'new'
				   if ($mapped === 'paid') {
					   // Só seta 'new' se status atual for vazio ou 'started'
					   \MydPro\Includes\Order_Meta::ensure_initial_status($order_id, 'new', array('', 'started'));
					   // Publish order
					   wp_update_post(array('ID' => $order_id, 'post_status' => 'publish'));
					// Ensure an 8-digit order locator exists (compat with Plugin behavior)
					$locator = get_post_meta($order_id, 'order_locator', true);
					if (empty($locator)) {
						$attempts = 0; $max = 30; $candidate = null;
						while ($attempts < $max) {
							try { $num = random_int(0, 99999999); } catch (\Throwable $e) { $num = mt_rand(0, 99999999); }
							$candidate = str_pad((string) $num, 8, '0', STR_PAD_LEFT);
							$exists = get_posts(array(
								'post_type' => 'mydelivery-orders',
								'post_status' => 'any',
								'fields' => 'ids',
								'posts_per_page' => 1,
								'meta_query' => array(
									array(
										'key' => 'order_locator',
										'value' => $candidate,
										'compare' => '='
									)
								)
							));
							if (empty($exists)) break;
							$attempts++;
						}
						if ($candidate) {
							update_post_meta($order_id, 'order_locator', $candidate);
							update_post_meta($order_id, 'myd_order_locator', $candidate);
						}
					}
					// Clear scheduled draft deletion if any
					if (function_exists('wp_unschedule_event')) {
						$timestamp = wp_next_scheduled('myd_delete_draft_order', array( $order_id ));
						if ($timestamp) {
							wp_unschedule_event($timestamp, 'myd_delete_draft_order', array( $order_id ));
						}
					}
					// Optionally notify push server
					try {
						if ( class_exists('MydPro\\Includes\\Push\\Push_Notifier') ) {
							\MydPro\Includes\Push\Push_Notifier::notify( get_post_meta( $order_id, 'myd_customer_id', true ), $order_id, get_post_meta($order_id, 'order_status', true) );
						}
					} catch (\Exception $_) {}
				}
			}
			if (is_array($data) && !empty($data['payment_method_id'])) {
				$pm = sanitize_text_field($data['payment_method_id']);
				if (strtolower($pm) === 'pix') { $pm = 'PIX'; }
				update_post_meta($order_id, 'order_payment_method', $pm);
			}
			update_post_meta($order_id, 'order_payment_type', 'payment-integration');
			// Optionally notify push server
			try {
				if ( class_exists('MydPro\\Includes\\Push\\Push_Notifier') ) {
					\MydPro\Includes\Push\Push_Notifier::notify( get_post_meta( $order_id, 'myd_customer_id', true ), $order_id, get_post_meta($order_id, 'order_status', true) );
				}
			} catch (\Exception $_) {}
		} catch (\Throwable $_) {
			// ignore errors
		}
	}
	
	/**
	 * Proxy to Google Places Autocomplete
	 */
	public function places_autocomplete( $request ) {
		$params = $request->get_query_params();
		$input = isset( $params['input'] ) ? sanitize_text_field( $params['input'] ) : '';
		$components = isset( $params['components'] ) ? sanitize_text_field( $params['components'] ) : '';
		// optional location bias params
		$location = isset( $params['location'] ) ? sanitize_text_field( $params['location'] ) : '';
		$radius = isset( $params['radius'] ) ? sanitize_text_field( $params['radius'] ) : '';
		if ( empty( $input ) ) {
			return new \WP_REST_Response( array( 'predictions' => array(), 'status' => 'INVALID_REQUEST' ), 200 );
		}
		// read api key from options (same option used in fdm-products-list)
		$api_key = get_option( 'myd-shipping-distance-google-api-key' );
		if ( empty( $api_key ) ) {
			return new \WP_REST_Response( array( 'error' => 'missing_api_key' ), 500 );
		}

		// prefer site locale for Google Places language (e.g., pt_BR -> pt-BR)
		$lang = str_replace( '_', '-', get_locale() );
		// Force autocomplete results to Brazil to restrict suggestions
		$url = 'https://maps.googleapis.com/maps/api/place/autocomplete/json?input=' . rawurlencode( $input ) . '&components=country:br';
		// append location bias if provided
		if ( ! empty( $location ) ) {
			// location expected as "lat,lng"
			$url .= '&location=' . rawurlencode( $location );
		}
		if ( ! empty( $radius ) ) {
			$url .= '&radius=' . rawurlencode( $radius );
		}
		// Removed types filter to include ALL location types: addresses, establishments, points of interest, etc.
		// This will show the maximum number of results, including squares, parks, buildings, etc.
		$url .= '&key=' . rawurlencode( $api_key ) . '&language=' . rawurlencode( $lang );

		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) ) {
			return new \WP_REST_Response( array( 'error' => 'request_failed' ), 500 );
		}
		$body = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );

		if ( isset( $decoded['predictions'] ) && is_array( $decoded['predictions'] ) ) {
			$allowed_types     = array( 'point_of_interest', 'geocode', 'route' );
			$filtered_predictions = array();
			foreach ( $decoded['predictions'] as $prediction ) {
				$prediction_types = isset( $prediction['types'] ) && is_array( $prediction['types'] ) ? $prediction['types'] : array();
				if ( empty( array_intersect( $allowed_types, $prediction_types ) ) ) {
					continue;
				}
				$place_id = isset( $prediction['place_id'] ) ? $prediction['place_id'] : '';
				if ( empty( $place_id ) ) {
					continue;
				}
				$components = $this->get_place_address_components( $place_id, $api_key, $lang );
				if ( empty( $components ) ) {
					continue;
				}
				if ( ! $this->has_neighborhood_component( $components ) ) {
					continue;
				}
				$filtered_predictions[] = $prediction;
			}
			$decoded['predictions'] = array_values( $filtered_predictions );
			if ( empty( $decoded['predictions'] ) ) {
				$decoded['status'] = 'ZERO_RESULTS';
			}
		}
		return new \WP_REST_Response( $decoded, 200 );
	}

	/**
	 * Proxy to Google Place Details
	 */
	public function places_details( $request ) {
		$params = $request->get_query_params();
		$place_id = isset( $params['place_id'] ) ? sanitize_text_field( $params['place_id'] ) : '';
		$fields = isset( $params['fields'] ) ? sanitize_text_field( $params['fields'] ) : 'formatted_address,geometry,address_component,address_components';
		if ( empty( $place_id ) ) {
			return new \WP_REST_Response( array( 'error' => 'missing_place_id' ), 400 );
		}
		$api_key = get_option( 'myd-shipping-distance-google-api-key' );
		if ( empty( $api_key ) ) {
			return new \WP_REST_Response( array( 'error' => 'missing_api_key' ), 500 );
		}
		// prefer site locale for language
		$lang = str_replace( '_', '-', get_locale() );
		$url = 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . rawurlencode( $place_id ) . '&fields=' . rawurlencode( $fields ) . '&key=' . rawurlencode( $api_key ) . '&language=' . rawurlencode( $lang );
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		if ( is_wp_error( $response ) ) {
			return new \WP_REST_Response( array( 'error' => 'request_failed' ), 500 );
		}
		$body = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );
		return new \WP_REST_Response( $decoded, 200 );
	}

	/**
	 * Retrieve cached address components for a place id.
	 */
	private function get_place_address_components( $place_id, $api_key, $lang ) {
		$cache_key = 'myd_place_components_' . md5( $place_id . '|' . $lang );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}
		$fields = 'address_component,address_components';
		$url    = 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . rawurlencode( $place_id ) . '&fields=' . rawurlencode( $fields ) . '&key=' . rawurlencode( $api_key ) . '&language=' . rawurlencode( $lang );
		$response = wp_remote_get( $url, array( 'timeout' => 5 ) );
		if ( is_wp_error( $response ) ) {
			return null;
		}
		$body    = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $body, true );
		if ( ! is_array( $decoded ) || ( $decoded['status'] ?? '' ) !== 'OK' ) {
			return null;
		}
		$components = isset( $decoded['result']['address_components'] ) && is_array( $decoded['result']['address_components'] )
			? $decoded['result']['address_components']
			: array();
		set_transient( $cache_key, $components, HOUR_IN_SECONDS );
		return $components;
	}

	/**
	 * Check if address components contain a neighborhood-level entry.
	 */
	private function has_neighborhood_component( $components ) {
		if ( empty( $components ) || ! is_array( $components ) ) {
			return false;
		}
		$target_types = array( 'neighborhood', 'sublocality', 'sublocality_level_1', 'sublocality_level_2', 'sublocality_level_3', 'sublocality_level_4' );
		foreach ( $components as $component ) {
			$types = isset( $component['types'] ) && is_array( $component['types'] ) ? $component['types'] : array();
			if ( empty( $types ) ) {
				continue;
			}
			if ( ! empty( array_intersect( $target_types, $types ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Register plugin routes
	 */
	public function register_order_routes() {
		\register_rest_route(
			'my-delivery/v1',
			'/orders',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'check_orders' ],
					'permission_callback' => [ $this, 'api_permissions_check' ],
					'args' => $this->get_parameters(),
				),
			)
		);
		\register_rest_route('myd-delivery/v1', '/whatsapp-connection', [
		    'methods' => 'POST',
		    'callback' => function($request) {
		        $params = $request->get_json_params();
		        if(isset($params['event']) && $params['event']==='connection.update') {
		            $state = isset($params['data']['state']) ? $params['data']['state'] : '';
		            update_option('myd_whatsapp_connection_state', $state);
		            return new \WP_REST_Response(['status'=>'ok'], 200);
		        }
		        return new \WP_REST_Response(['status'=>'ignored'], 200);
		    },
		    'permission_callback' => '__return_true',
		]);

		// Proxy endpoints for Google Places (autocomplete + details)
		\register_rest_route('my-delivery/v1', '/places/autocomplete', [
			'methods' => \WP_REST_Server::READABLE,
			'callback' => [ $this, 'places_autocomplete' ],
			'permission_callback' => '__return_true',
		]);

		\register_rest_route('my-delivery/v1', '/places/details', [
			'methods' => \WP_REST_Server::READABLE,
			'callback' => [ $this, 'places_details' ],
			'permission_callback' => '__return_true',
		]);

	}

	/**
	 * Check orders and retrive status
	 */
	public function check_orders( $request ) {
		$current_id = $request['oid'];

		$args = [
			'post_type' => 'mydelivery-orders',
			'posts_per_page' => 1,
			'no_found_rows' => true,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'     => 'order_status',
					'value'   => 'new',
					'compare' => '=',
				),
				array(
					'key'     => 'order_status',
					'value'   => 'confirmed',
					'compare' => '=',
				),
				array(
					'key'     => 'order_status',
					'value'   => 'in-delivery',
					'compare' => '=',
				),
			),
		];

		$orders = new \WP_Query( $args );
		$orders = $orders->get_posts();

		if ( $orders[0]->ID <= $current_id ) {

			$response = [ 'status' => 'atualizado' ];
			return rest_ensure_response( $response );
		} else {

			$response = [ 'status' => 'desatualizado' ];
			return rest_ensure_response( $response );
		}
	}

	/**
	 * Check API permissions
	 */
	public function api_permissions_check() {
		if ( \current_user_can( 'edit_posts' ) ) {
			return new \WP_Error( 'rest_forbidden', esc_html__( 'You can not permission for acess that.', 'myd-delivery-pro' ), array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Define parameters
	 */
	public function get_parameters() {
		$args = array();

		$args['oid'] = array(
			'description' => esc_html__( 'The filter parameter is used to filter number', 'myd-delivery-pro' ),
			'type'        => 'integer',
			'required' => true,
			'validate_callback' => [ $this, 'validate_parameter' ],
		);

		return $args;
	}

	/**
	 * Validate parameters
	 */
	public function validate_parameter( $value, $request, $param ) {
		if ( ! is_numeric( $value ) ) {
			return new \WP_Error( 'rest_invalid_param', esc_html__( 'Sorry this parameter its not valid or empty', 'myd-delivery-pro' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Register push routes
	 */
	public function register_push_routes() {
		\register_rest_route('myd-delivery/v1', '/push/auth', [
			'methods' => 'POST',
			'callback' => [ $this, 'push_auth' ],
			'permission_callback' => function() {
				// Temporarily allow all requests - we'll validate authentication inside the method
				return true;
			},
		]);
	}

	/**
	 * Register store routes
	 */
	public function register_store_routes() {
		// GET: retorna status
		\register_rest_route('myd-delivery/v1', '/store/status', [
			'methods' => 'GET',
			'callback' => [ $this, 'store_status' ],
			'permission_callback' => '__return_true',
		]);
		// POST: atualiza status (somente administradores)
		\register_rest_route('myd-delivery/v1', '/store/status', [
			'methods' => 'POST',
			'callback' => [ $this, 'store_status_update' ],
			'permission_callback' => function(){ return \is_user_logged_in() && myd_user_is_allowed_admin(); },
			'args' => [
				'force' => [
					'required' => true,
					'validate_callback' => function($param){ return in_array($param, ['ignore','open','close'], true); }
				]
			]
		]);
	}

	/**
	 * Register miscellaneous routes (simple-auth modal)
	 */
	public function register_misc_routes() {
	\register_rest_route('myd-delivery/v1', '/simple-auth/loginmodal', [
			'methods' => 'GET',
			'callback' => function($request) {
				// Render the modal partial and return as HTML
				$tpl = MYD_PLUGIN_PATH . 'templates/simple-auth/loginmodal.php';
				if (file_exists($tpl)) {
					ob_start();
					include $tpl;
					$html = ob_get_clean();
					return new \WP_REST_Response(['html' => $html], 200);
				}
				return new \WP_REST_Response(['error' => 'Template not found'], 404);
			},
			'permission_callback' => '__return_true',
		]);

		// Evolution WhatsApp status proxy: server-side request to avoid CORS/auth issues
	\register_rest_route('myd-delivery/v1', '/evolution/whatsapp_status', [
		'methods' => 'GET',
		'callback' => function($request) {
			// Temporary debug log: record incoming status requests (remove after debugging)
			try {
				$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
				$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
				if ( function_exists('getallheaders') ) {
					$h = getallheaders();
				} else {
					$h = [];
				}
				@error_log('[MYD][EvolutionStatus] request from ' . $ip . ' uri=' . $uri . ' headers=' . substr(json_encode($h),0,1000));
			} catch (\Throwable $_) { }
			$api_url = trim((string) get_option('evolution_api_url', ''));
			$instance = trim((string) get_option('evolution_instance_name', ''));
			$api_key = trim((string) get_option('evolution_api_key', ''));
			if (!$api_url || !$instance) {
				return new \WP_REST_Response(['error' => 'not_configured'], 400);
			}
			// ensure instance doesn't duplicate dwp- prefix
			$inst = preg_replace('#^dwp-#i', '', $instance);
			$target = rtrim($api_url, '/') . '/instance/connectionState/dwp-' . rawurlencode($inst);
			$headers = [ 'Accept' => 'application/json' ];
			// Evolution API expects the API key in the header named `apikey` (see docs)
			if ($api_key) {
				$headers['apikey'] = $api_key;
			}
			$response = wp_remote_get($target, [ 'headers' => $headers, 'timeout' => 10 ]);
			if (is_wp_error($response)) {
				return new \WP_REST_Response(['error' => 'request_failed', 'message' => $response->get_error_message()], 502);
			}
			$code = wp_remote_retrieve_response_code($response) ?: 500;
			$body = wp_remote_retrieve_body($response);
			$parsed = null;
			try { $parsed = json_decode($body, true); } catch (\Throwable $_) { $parsed = null; }
			return new \WP_REST_Response(['status_code' => $code, 'body' => $parsed, 'raw' => $body], $code === 200 ? 200 : 502);
		},
		'permission_callback' => '__return_true',
	]);

	// Also register same endpoint under old namespace for compatibility
	\register_rest_route('my-delivery/v1', '/evolution/whatsapp_status', [
		'methods' => 'GET',
		'callback' => function($request) {
			$api_url = trim((string) get_option('evolution_api_url', ''));
			$instance = trim((string) get_option('evolution_instance_name', ''));
			$api_key = trim((string) get_option('evolution_api_key', ''));
			if (!$api_url || !$instance) {
				return new \WP_REST_Response(['error' => 'not_configured'], 400);
			}
			$inst = preg_replace('#^dwp-#i', '', $instance);
			$target = rtrim($api_url, '/') . '/instance/connectionState/dwp-' . rawurlencode($inst);
			$headers = [ 'Accept' => 'application/json' ];
			if ($api_key) { $headers['apikey'] = $api_key; }
			$response = wp_remote_get($target, [ 'headers' => $headers, 'timeout' => 10 ]);
			if (is_wp_error($response)) {
				return new \WP_REST_Response(['error' => 'request_failed', 'message' => $response->get_error_message()], 502);
			}
			$code = wp_remote_retrieve_response_code($response) ?: 500;
			$body = wp_remote_retrieve_body($response);
			$parsed = null;
			try { $parsed = json_decode($body, true); } catch (\Throwable $_) { $parsed = null; }
			return new \WP_REST_Response(['status_code' => $code, 'body' => $parsed, 'raw' => $body], $code === 200 ? 200 : 502);
		},
		'permission_callback' => '__return_true',
	]);

		// Evolution webhook receiver: accepts POSTs from Evolution and updates stored connection state
		\register_rest_route('myd-delivery/v1', '/evolution/webhook', [
			'methods' => 'POST',
			'callback' => function($request) {
				// Temporary debug log: record incoming webhook raw body + headers (remove after debugging)
				try {
					$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
					$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
					$raw_preview = '';
					try { $raw_preview = substr((string) @file_get_contents('php://input'), 0, 2000); } catch (\Throwable $_) { $raw_preview = ''; }
					if ( function_exists('getallheaders') ) { $h = getallheaders(); } else { $h = []; }
					@error_log('[MYD][EvolutionWebhook] from=' . $ip . ' uri=' . $uri . ' headers=' . substr(json_encode($h),0,1000) . ' body_preview=' . $raw_preview);
				} catch (\Throwable $_) { }
				$raw = $request->get_body();
				$payload = json_decode($raw, true);
				if (!is_array($payload)) {
					return new \WP_REST_Response(['error' => 'invalid_json'], 400);
				}
				// Optional: verify apikey in payload matches stored option (if configured)
				$incoming_key = isset($payload['apikey']) ? trim((string)$payload['apikey']) : '';
				if (!$incoming_key && function_exists('getallheaders')) {
					$h = getallheaders();
					if (isset($h['apikey'])) $incoming_key = trim((string)$h['apikey']);
				}

				$stored_key = trim((string) get_option('evolution_webhook_key', ''));
				if (!$stored_key) { $stored_key = trim((string) get_option('evolution_api_key', '')); }
				
				$auth_ok = true;
				if ($stored_key && $incoming_key && $incoming_key !== $stored_key) {
					$auth_ok = false;
					if (defined('WP_DEBUG') && WP_DEBUG) { error_log('[MYD][Evolution Webhook] apikey mismatch'); }
				}

				// Optional: verify instance matches configured instance
				$incoming_instance = isset($payload['instance']) ? trim((string)$payload['instance']) : '';
				$stored_instance = trim((string) get_option('evolution_instance_name', ''));
				$instance_ok = true;
				if ($stored_instance && $incoming_instance) {
					$norm_in = strtolower(preg_replace('#^dwp-#i', '', $incoming_instance));
					$norm_st = strtolower(preg_replace('#^dwp-#i', '', $stored_instance));
					if ($norm_in !== $norm_st) {
						$instance_ok = false;
					}
				}

				$event = isset($payload['event']) ? strtolower((string) $payload['event']) : '';
				$state = null;
				if (isset($payload['data']) && is_array($payload['data']) && isset($payload['data']['state'])) {
					$state = strtolower(trim((string) $payload['data']['state']));
				} elseif (isset($payload['state'])) {
					$state = strtolower(trim((string) $payload['state']));
				}

				$reason = '';
				if (!$auth_ok) $reason = 'apikey_mismatch';
				elseif (!$instance_ok) $reason = 'instance_mismatch';

				if ($auth_ok && $instance_ok && ($event === 'connection.update' || $event === 'typebot.start') && $state) {
					// Normalize common variants
					if ($state === 'closed' || $state === 'disconnect' || $state === 'disconnected') $state = 'close';
					if ($state === 'connected') $state = 'open';
					update_option('myd_whatsapp_connection_state', $state);
					if (defined('WP_DEBUG') && WP_DEBUG) { error_log('[MYD][Evolution Webhook] connection.update -> ' . $state); }

					// Also append a concise entry to the access monitor log so it appears in the real-time monitor
					try {
						$ip = '';
						foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $k ) {
							if ( ! empty( $_SERVER[ $k ] ) ) {
								$rawip = sanitize_text_field( wp_unslash( $_SERVER[ $k ] ) );
								$parts = explode( ',', $rawip );
								$ip = trim( $parts[0] );
								break;
							}
						}
						$ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 300 ) : '';
						$seq = (int) get_option('myd_access_seq', 0);
						$seq++;
						$entry = [
							'id' => $seq,
							'ts' => time(),
							'type' => 'evolution-webhook',
							'ip' => $ip,
							'method' => 'POST',
							'path' => '/wp-json/myd-delivery/v1/evolution/webhook',
							'ua' => $ua,
							'event' => $event,
							'instance' => $incoming_instance,
							'state' => $state,
							'reason' => $reason,
							'body_raw_preview' => ( strlen($raw) > 1000 ? substr($raw,0,1000) : $raw ),
						];
						// Diagnostic: if auth failed, log the first characters of the received key vs expected
						if (!$auth_ok && $incoming_key) {
							$entry['diag_keys'] = substr($incoming_key, 0, 4) . '... vs ' . substr($stored_key, 0, 4) . '...';
						}
						$log = get_option('myd_access_log', []);
						if (!is_array($log)) $log = [];
						$log[] = $entry;
						if (count($log) > 300) { $log = array_slice($log, -300); }
						update_option('myd_access_log', $log, false);
						update_option('myd_access_seq', $seq, false);
						if (function_exists('wp_cache_delete')) { wp_cache_delete('myd_access_log', 'options'); wp_cache_delete('myd_access_seq', 'options'); wp_cache_delete('alloptions', 'options'); }
					} catch (\Throwable $_) { /* ignore logging failures */ }

					return new \WP_REST_Response(['status' => 'ok', 'state' => $state], 200);
				}

				return new \WP_REST_Response(['status' => 'ignored', 'reason' => $reason], 200);
			},
			'permission_callback' => '__return_true',
		]);

		// Endpoint to save evolution_webhook_key (called from JS after instance creation)
		\register_rest_route('myd-delivery/v1', '/evolution/save-token', [
			'methods' => 'POST',
			'callback' => function($request) {
				if (!myd_user_is_allowed_admin()) {
					return new \WP_REST_Response(['error' => 'unauthorized'], 403);
				}
				$token = trim((string)$request->get_param('token'));
				$instance = trim((string)$request->get_param('instance'));
				if (!$token) {
					return new \WP_REST_Response(['error' => 'empty_token'], 400);
				}
				update_option('evolution_webhook_key', $token);
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('[MYD][Evolution] Saved webhook key for instance ' . $instance);
				}
				return new \WP_REST_Response(['status' => 'ok', 'message' => 'Token salvo com sucesso'], 200);
			},
			'permission_callback' => '__return_true', // actual check inside callback via myd_user_is_allowed_admin
		]);

	// Compatibility: also accept webhook under legacy namespace
	\register_rest_route('my-delivery/v1', '/evolution/webhook', [
		'methods' => 'POST',
		'callback' => function($request) {
			// Reuse logic: parse body and forward to the primary handler path
			$raw = $request->get_body();
			$payload = json_decode($raw, true);
			if (!is_array($payload)) {
				return new \WP_REST_Response(['error' => 'invalid_json'], 400);
			}
			// Simple verification and same behavior as primary route
			$incoming_key = isset($payload['apikey']) ? trim((string)$payload['apikey']) : '';
			$stored_key = trim((string) get_option('evolution_api_key', ''));
			if ($stored_key && $incoming_key && $incoming_key !== $stored_key) {
				if (defined('WP_DEBUG') && WP_DEBUG) { error_log('[MYD][Evolution Webhook - legacy] apikey mismatch'); }
				return new \WP_REST_Response(['error' => 'invalid_apikey'], 401);
			}
			$incoming_instance = isset($payload['instance']) ? trim((string)$payload['instance']) : '';
			$stored_instance = trim((string) get_option('evolution_instance_name', ''));
			if ($stored_instance && $incoming_instance) {
				$norm_in = strtolower(preg_replace('#^dwp-#i', '', $incoming_instance));
				$norm_st = strtolower(preg_replace('#^dwp-#i', '', $stored_instance));
				if ($norm_in !== $norm_st) {
					if (defined('WP_DEBUG') && WP_DEBUG) { error_log('[MYD][Evolution Webhook - legacy] instance mismatch: ' . $incoming_instance); }
					return new \WP_REST_Response(['status' => 'ignored', 'reason' => 'instance_mismatch'], 200);
				}
			}
			$event = isset($payload['event']) ? (string) $payload['event'] : '';
			$state = null;
			if (isset($payload['data']) && is_array($payload['data']) && isset($payload['data']['state'])) {
				$state = strtolower(trim((string) $payload['data']['state']));
			} elseif (isset($payload['state'])) {
				$state = strtolower(trim((string) $payload['state']));
			}
			if ($event === 'connection.update' && $state) {
				if ($state === 'closed' || $state === 'disconnect' || $state === 'disconnected') $state = 'close';
				if ($state === 'connected') $state = 'open';
				update_option('myd_whatsapp_connection_state', $state);
				// append to access log as well
				try {
					$ip = '';
					foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $k ) {
						if ( ! empty( $_SERVER[ $k ] ) ) { $rawip = sanitize_text_field( wp_unslash( $_SERVER[ $k ] ) ); $parts = explode( ',', $rawip ); $ip = trim( $parts[0] ); break; }
					}
					$ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 300 ) : '';
					$seq = (int) get_option('myd_access_seq', 0); $seq++;
					$entry = [ 'id'=>$seq, 'ts'=>time(), 'type'=>'evolution-webhook', 'ip'=>$ip, 'method'=>'POST', 'path'=>'/wp-json/my-delivery/v1/evolution/webhook', 'ua'=>$ua, 'event'=>$event, 'instance'=>$incoming_instance, 'state'=>$state, 'body_raw_preview'=> ( strlen($raw) > 1000 ? substr($raw,0,1000) : $raw ) ];
					$log = get_option('myd_access_log', []); if (!is_array($log)) $log = []; $log[] = $entry; if (count($log) > 300) $log = array_slice($log, -300); update_option('myd_access_log', $log, false); update_option('myd_access_seq', $seq, false); if (function_exists('wp_cache_delete')) { wp_cache_delete('myd_access_log','options'); wp_cache_delete('myd_access_seq','options'); wp_cache_delete('alloptions','options'); }
				} catch (\Throwable $_) {}
				return new \WP_REST_Response(['status'=>'ok','state'=>$state], 200);
			}
			return new \WP_REST_Response(['status'=>'ignored'], 200);
		},
		'permission_callback' => '__return_true',
	]);

	// Diagnostic endpoint: accept POSTs to test whether external services can reach the server
	\register_rest_route('myd-delivery/v1', '/evolution/webhook-test', [
		'methods' => 'POST',
		'callback' => function($request) {
			try {
				$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
				$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
				$raw = $request->get_body();
				if ( function_exists('getallheaders') ) { $h = getallheaders(); } else { $h = []; }
				@error_log('[MYD][EvolutionWebhookTest] from=' . $ip . ' uri=' . $uri . ' headers=' . substr(json_encode($h),0,1000) . ' body_preview=' . substr((string)$raw,0,2000));
			} catch (\Throwable $_) { }
			return new \WP_REST_Response(['status' => 'ok', 'message' => 'webhook-test received'], 200);
		},
		'permission_callback' => '__return_true',
	]);

	\register_rest_route('my-delivery/v1', '/evolution/webhook-test', [
		'methods' => 'POST',
		'callback' => function($request) {
			try {
				$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
				$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
				$raw = $request->get_body();
				if ( function_exists('getallheaders') ) { $h = getallheaders(); } else { $h = []; }
				@error_log('[MYD][EvolutionWebhookTest - legacy] from=' . $ip . ' uri=' . $uri . ' headers=' . substr(json_encode($h),0,1000) . ' body_preview=' . substr((string)$raw,0,2000));
			} catch (\Throwable $_) { }
			return new \WP_REST_Response(['status' => 'ok', 'message' => 'webhook-test received (legacy)'], 200);
		},
		'permission_callback' => '__return_true',
	]);
	}

	/**
	 * Generate JWT token for push server
	 */
	public function push_auth( $request ) {
		// Force WordPress to load user session
		if (!session_id()) {
			session_start();
		}

		if ( defined('WP_DEBUG') && WP_DEBUG ) {
			\error_log('[Push Auth] COOKIEHASH: ' . (defined('COOKIEHASH') ? COOKIEHASH : 'not defined'));
			\error_log('[Push Auth] Available cookies: ' . print_r(array_keys($_COOKIE), true));
		}

		// Check authentication - require valid WordPress login cookie
		$authenticated = false;
		$cookie_hashes = [
			COOKIEHASH,
			md5('dev.franguxo.app.br'),
			'9b586bbbfda1b1a48aedc56eef8a3ccd'
		];

		foreach ($cookie_hashes as $hash) {
			$cookie_key = 'wordpress_logged_in_' . $hash;
			if (isset($_COOKIE[$cookie_key])) {
				$cookie_data = $_COOKIE[$cookie_key];
				$cookie_parts = explode('|', $cookie_data);
				if (count($cookie_parts) >= 4) {
					$username = $cookie_parts[0];
					$user = \get_user_by('login', $username);
					if ($user && $user->ID > 0) {
						$authenticated = true;
						\wp_set_current_user($user->ID);
						\error_log('[Push Auth] Authenticated user: ' . $username . ' (ID: ' . $user->ID . ') using hash: ' . $hash);
						break;
					}
				}
			}
		}

		if (!$authenticated) {
			\error_log('[Push Auth] Authentication failed - no valid login cookie found');
			return new \WP_REST_Response(['error' => 'Authentication required'], 401);
		}

		$params = $request->get_json_params();
		$myd_customer_id = isset($params['myd_customer_id']) ? intval($params['myd_customer_id']) : 0;

		$secret = get_option('myd_push_secret', '');
		if (!$secret) {
			return new \WP_REST_Response(['error' => 'Push secret not configured'], 500);
		}

		// Debug: log user status
		$is_logged_in = \is_user_logged_in();
		$current_user = \wp_get_current_user();
		$user_id = $current_user->ID;
		$user_roles = $current_user->roles;
		$can_manage_options = myd_user_is_allowed_admin();
		$can_manage_woocommerce = \current_user_can('manage_woocommerce');
		$can_edit_shop_orders = \current_user_can('edit_shop_orders');
		$is_administrator = \in_array('administrator', $user_roles);
		if ( defined('WP_DEBUG') && WP_DEBUG ) {
			error_log("[Push Auth] User logged in: $is_logged_in, ID: $user_id, Roles: " . implode(',', $user_roles) . ", is_administrator: " . ($is_administrator ? 'YES' : 'NO') . ", manage_options: $can_manage_options, manage_woocommerce: $can_manage_woocommerce, edit_shop_orders: $can_edit_shop_orders");
			error_log("[Push Auth] Current user object: " . print_r($current_user, true));
			error_log("[Push Auth] Session cookies: " . print_r($_COOKIE, true));
			error_log("[Push Auth] Request headers: " . print_r($request->get_headers(), true));
			error_log("[Push Auth] Request origin: " . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'none'));
			error_log("[Push Auth] Request referer: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'none'));
			error_log("[Push Auth] Site URL: " . get_site_url());
			error_log("[Push Auth] Home URL: " . get_home_url());
		}

		// Generate JWT - include myd_customer_id only when provided (>0)
		$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
		$payload_data = array('iat' => time(), 'exp' => time() + 86400);
		if ($myd_customer_id > 0) {
			$payload_data['myd_customer_id'] = $myd_customer_id;
		}
		// If current WP user is logged in AND has administrator role, mark role in token for room join
		if ( \is_user_logged_in() && \in_array('administrator', $user_roles) ) {
			$payload_data['role'] = 'admin';
			if ( defined('WP_DEBUG') && WP_DEBUG ) {
				\error_log('[Push Auth] User has administrator role - will join admins room');
				\error_log('[Push Auth] User roles array: ' . print_r($user_roles, true));
			}
		} else {
			$payload_data['role'] = 'user';
			if ( defined('WP_DEBUG') && WP_DEBUG ) {
				\error_log('[Push Auth] User does NOT have administrator role - will NOT join admins room');
				\error_log('[Push Auth] User roles array: ' . print_r($user_roles, true));
				\error_log('[Push Auth] Checking for administrator in roles: ' . (\in_array('administrator', $user_roles) ? 'FOUND' : 'NOT FOUND'));
			}
		}
		$payload = json_encode($payload_data);
		$header_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
		$payload_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
		$signature = hash_hmac('sha256', $header_encoded . "." . $payload_encoded, $secret, true);
		$signature_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
		$token = $header_encoded . "." . $payload_encoded . "." . $signature_encoded;

		return new \WP_REST_Response(['token' => $token], 200);
	}

	/**
	 * Return current store force setting and whether store is open
	 */
	public function store_status( $request ) {
		$force = get_option('myd-delivery-force-open-close-store', 'ignore');
		// Use Store_Data if available
		$open = false;
		$hours_info = '';
		if ( class_exists('\MydPro\Includes\Store_Data') ) {
			$open = \MydPro\Includes\Store_Data::is_store_open();
			$hours_info = \MydPro\Includes\Store_Data::get_today_hours();
		}
		return new \WP_REST_Response([
			'force'      => $force, 
			'open'       => (bool)$open,
			'hours_info' => $hours_info
		], 200);
	}

	public function store_status_update( $request ) {
		$param = $request->get_param('force');
		$allowed = ['ignore','open','close'];
		if ( ! in_array($param, $allowed, true) ) {
			return new \WP_REST_Response(['error' => 'Invalid force value'], 400);
		}
		update_option('myd-delivery-force-open-close-store', $param);
		$force = get_option('myd-delivery-force-open-close-store', 'ignore');
		$open = false;
		if ( class_exists('\\MydPro\\Includes\\Store_Data') ) {
			$open = \MydPro\Includes\Store_Data::is_store_open();
		}
		// Compute effective open according to forced override
		$effective_open = $open;
		if ($force === 'open') { $effective_open = true; }
		elseif ($force === 'close') { $effective_open = false; }

		// Push broadcast
		$push_sent = false; $push_error = null;
		$push_url = get_option('myd_push_server_url', '');
		$push_secret = get_option('myd_push_secret', '');
		if ($push_url && $push_secret) {
			try {
				$header = json_encode(['typ'=>'JWT','alg'=>'HS256']);
				$payload = json_encode(['iat'=>time(),'exp'=>time()+300,'role'=>'admin']);
				$h = str_replace(['+','/','='], ['-','_',''], base64_encode($header));
				$p = str_replace(['+','/','='], ['-','_',''], base64_encode($payload));
				$sig = hash_hmac('sha256', $h.'.'.$p, $push_secret, true);
				$s = str_replace(['+','/','='], ['-','_',''], base64_encode($sig));
				$jwt = $h.'.'.$p.'.'.$s;
				$response = wp_remote_post( rtrim($push_url,'/').'/notify/store', [
					'timeout' => 5,
					'headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$jwt ],
					'body' => wp_json_encode(['open' => $effective_open, 'force' => $force])
				]);
				if ( ! is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200 ) {
					$push_sent = true;
				} else {
					$push_error = is_wp_error($response) ? $response->get_error_message() : 'HTTP '.wp_remote_retrieve_response_code($response);
				}
			} catch ( \Throwable $e ) {
				$push_error = $e->getMessage();
			}
			if ($push_error) { error_log('[Store Status] Push broadcast failed: '.$push_error); }
		}

		return new \WP_REST_Response([
			'updated' => true,
			'force' => $force,
			'open' => (bool)$open,
			'effective_open' => (bool)$effective_open,
			'push_sent' => $push_sent
		], 200);
	}

	/**
	 * Handle create order page request
	 */
	public function handle_create_order_page() {
		if ( ! isset( $_GET['myd_create_order'] ) || $_GET['myd_create_order'] !== '1' ) {
			return;
		}

		// Verificar se usuário tem permissão
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Você não tem permissão para acessar esta página.', 'myd-delivery-pro' ) );
		}

		// Carregar o template
		$template_path = MYD_PLUGIN_PATH . 'templates/order/create-order.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
			exit;
		}
	}

	/**
	 * Register manual order creation routes
	 */
	public function register_manual_order_routes() {
		// Endpoint para buscar produtos
		\register_rest_route('myd-delivery/v1', '/products/search', [
			'methods' => 'GET',
			'callback' => [ $this, 'search_products' ],
			'permission_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
		]);

		// Endpoint para obter extras de um produto
		\register_rest_route('myd-delivery/v1', '/products/(?P<id>\d+)/extras', [
			'methods' => 'GET',
			'callback' => [ $this, 'get_product_extras' ],
			'permission_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
		]);

		// Endpoint para criar pedido manual
		\register_rest_route('myd-delivery/v1', '/orders/create-manual', [
			'methods' => 'POST',
			'callback' => [ $this, 'create_manual_order' ],
			'permission_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
		]);
	}

	/**
	 * Search products by name
	 */
	public function search_products( $request ) {
		$query = sanitize_text_field( $request->get_param( 'q' ) );
		
		if ( empty( $query ) || strlen( $query ) < 2 ) {
			return new \WP_REST_Response( [ 'products' => [] ], 200 );
		}

		$args = [
			'post_type'      => 'mydelivery-produtos',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			's'              => $query,
			'meta_query'     => [
				[
					'key'     => 'product_available',
					'value'   => 'show',
					'compare' => '=',
				],
			],
		];

		$products_query = new \WP_Query( $args );
		$products = [];

		if ( $products_query->have_posts() ) {
			while ( $products_query->have_posts() ) {
				$products_query->the_post();
				$post_id = get_the_ID();
				$price = get_post_meta( $post_id, 'product_price', true );
				
				$products[] = [
					'id'    => $post_id,
					'name'  => get_the_title(),
					'price' => floatval( $price ),
				];
			}
			wp_reset_postdata();
		}

		return new \WP_REST_Response( [ 'products' => $products ], 200 );
	}

	/**
	 * Get product extras/addons (retorna HTML renderizado pelo servidor)
	 */
	public function get_product_extras( $request ) {
		$product_id = absint( $request->get_param( 'id' ) );

		if ( ! $product_id || get_post_type( $product_id ) !== 'mydelivery-produtos' ) {
			return new \WP_REST_Response( [ 'has_extras' => false, 'html' => '' ], 200 );
		}

		$products_show = new \MydPro\Includes\Fdm_products_show();
		$raw_extras    = $products_show->get_product_extra( $product_id );

		if ( empty( $raw_extras ) ) {
			return new \WP_REST_Response( [ 'has_extras' => false, 'html' => '' ], 200 );
		}

		// Verificar se há pelo menos um grupo visível com opções
		$has_visible = false;
		foreach ( $raw_extras as $group ) {
			$available = $group['extra_available'] ?? '';
			if ( $available === 'hide' ) {
				continue;
			}
			if ( ! empty( $group['extra_options'] ) ) {
				$has_visible = true;
				break;
			}
		}

		if ( ! $has_visible ) {
			return new \WP_REST_Response( [ 'has_extras' => false, 'html' => '' ], 200 );
		}

		// Usar o mesmo método do frontend para renderizar o HTML
		$html = $products_show->format_product_extra( $product_id );

		return new \WP_REST_Response( [ 'has_extras' => true, 'html' => $html ], 200 );
	}

	/**
	 * Create manual order from panel
	 */
	public function create_manual_order( $request ) {
		$params = $request->get_json_params();
		
		// Validar nonce
		if ( empty( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'myd_create_manual_order' ) ) {
			return new \WP_REST_Response( [ 'success' => false, 'message' => 'Nonce inválido' ], 403 );
		}

		// Validações básicas
		if ( empty( $params['customer_name'] ) ) {
			return new \WP_REST_Response( [ 'success' => false, 'message' => 'Nome do cliente é obrigatório' ], 400 );
		}
		if ( empty( $params['customer_phone'] ) ) {
			return new \WP_REST_Response( [ 'success' => false, 'message' => 'Telefone do cliente é obrigatório' ], 400 );
		}
		if ( empty( $params['payment_method'] ) ) {
			return new \WP_REST_Response( [ 'success' => false, 'message' => 'Forma de pagamento é obrigatória' ], 400 );
		}
		if ( empty( $params['items'] ) || ! is_array( $params['items'] ) ) {
			return new \WP_REST_Response( [ 'success' => false, 'message' => 'Adicione pelo menos um produto' ], 400 );
		}

		$order_type = sanitize_text_field( $params['order_type'] ?? 'delivery' );

		// Criar o pedido
		$order_data = [
			'post_title'  => '#',
			'post_status' => 'publish',
			'post_type'   => 'mydelivery-orders',
		];

		$order_id = wp_insert_post( $order_data );

		if ( is_wp_error( $order_id ) ) {
			return new \WP_REST_Response( [ 'success' => false, 'message' => 'Erro ao criar pedido' ], 500 );
		}

		// Atualizar título com o ID
		wp_update_post( [
			'ID'         => $order_id,
			'post_title' => $order_id,
		] );

		// Calcular subtotal e total
		$subtotal = 0;
		$order_items = [];

		foreach ( $params['items'] as $item ) {
			$product_id = intval( $item['id'] );
			$quantity = intval( $item['quantity'] );
			$price = floatval( $item['price'] );
			$item_total = $price * $quantity;
			$subtotal += $item_total;

			$image_id = get_post_meta( $product_id, 'product_image', true );

			$item_note = ! empty( $item['note'] ) ? sanitize_textarea_field( $item['note'] ) : '';

			$order_items[] = [
				'product_image' => intval( $image_id ),
				'product_id'    => get_post_meta( $product_id, 'product_id', true ),
				'product_name'  => $quantity . ' x ' . esc_html( get_the_title( $product_id ) ),
				'product_extras' => '',
				'product_price' => Myd_Store_Formatting::format_price( $price ),
				'product_total' => Myd_Store_Formatting::format_price( $item_total ),
				'product_note'  => $item_note,
				'id'            => $product_id,
				'name'          => esc_html( get_post_meta( $product_id, 'product_name', true ) ),
				'quantity'      => $quantity,
				'extras'        => [],
				'price'         => $price,
				'total'         => $item_total,
				'note'          => $item_note,
			];
		}

		// Taxa de entrega
		$delivery_fee = 0;
		if ( $order_type === 'delivery' && ! empty( $params['delivery_fee'] ) ) {
			$fee_val = preg_replace( '/[^\d,.-]/', '', $params['delivery_fee'] );
			$fee_val = str_replace( ',', '.', $fee_val );
			$delivery_fee = floatval( $fee_val );
		}

		$total = $subtotal + $delivery_fee;

		// Salvar metas do pedido
		\error_log( 'MYD_SAVE_TRACE ' . \wp_json_encode( array( 'event' => 'api_persist_items', 'order_id' => intval( $order_id ), 'items_count' => count( $order_items ) ) ) );
		update_post_meta( $order_id, 'myd_order_items', $order_items );
		$order_channel = sanitize_text_field( $params['order_channel'] ?? 'MANUAL' );
		update_post_meta( $order_id, 'order_channel', $order_channel );
		update_post_meta( $order_id, 'order_status', 'new' );
		update_post_meta( $order_id, 'order_date', current_time( 'd-m-Y H:i' ) );
		update_post_meta( $order_id, 'order_customer_name', sanitize_text_field( $params['customer_name'] ) );
		update_post_meta( $order_id, 'customer_phone', sanitize_text_field( $params['customer_phone'] ) );
		update_post_meta( $order_id, 'order_ship_method', $order_type );
		update_post_meta( $order_id, 'order_payment_method', sanitize_text_field( $params['payment_method'] ) );
		update_post_meta( $order_id, 'order_payment_type', 'upon-delivery' );
		update_post_meta( $order_id, 'order_payment_status', 'waiting' );
		update_post_meta( $order_id, 'order_subtotal', Myd_Store_Formatting::format_price( $subtotal ) );
		update_post_meta( $order_id, 'order_delivery_price', Myd_Store_Formatting::format_price( $delivery_fee ) );
		update_post_meta( $order_id, 'order_total', Myd_Store_Formatting::format_price( $total ) );
		\error_log( 'MYD_SAVE_TRACE ' . \wp_json_encode( array( 'event' => 'api_save_end', 'order_id' => intval( $order_id ) ) ) );

		// Troco (se dinheiro)
		if ( $params['payment_method'] === 'DIN' && ! empty( $params['change_for'] ) ) {
			$change_val = preg_replace( '/[^\d,.-]/', '', $params['change_for'] );
			$change_val = str_replace( ',', '.', $change_val );
			update_post_meta( $order_id, 'order_change_for', Myd_Store_Formatting::format_price( floatval( $change_val ) ) );
		}

		// Observações
		if ( ! empty( $params['order_notes'] ) ) {
			update_post_meta( $order_id, 'order_customer_note', sanitize_textarea_field( $params['order_notes'] ) );
		}

		// Endereço (se delivery)
		if ( $order_type === 'delivery' ) {
			update_post_meta( $order_id, 'order_address', sanitize_text_field( $params['address'] ?? '' ) );
			update_post_meta( $order_id, 'order_address_number', sanitize_text_field( $params['address_number'] ?? '' ) );
			$api_neighborhood = sanitize_text_field( $params['neighborhood'] ?? '' );
			$api_real_neighborhood = sanitize_text_field( $params['real_neighborhood'] ?? '' );
			update_post_meta( $order_id, 'order_neighborhood', $api_neighborhood );
			// Only save real_neighborhood when the user manually changed the bairro
			update_post_meta( $order_id, 'order_real_neighborhood', ( $api_real_neighborhood !== '' && $api_real_neighborhood !== $api_neighborhood ) ? $api_real_neighborhood : '' );
			update_post_meta( $order_id, 'order_address_comp', sanitize_text_field( $params['address_comp'] ?? '' ) );
			update_post_meta( $order_id, 'order_address_reference', sanitize_text_field( $params['reference'] ?? '' ) );
		}

		// Calcular tempo estimado de entrega
		$order_datetime = current_time( 'Y-m-d H:i:s' );
		$avg_prep_time = (int) get_option( 'myd-average-preparation-time', 30 );
		$avg_delivery_time_str = get_option( 'fdm-estimate-time-delivery', '30' );
		preg_match( '/(\d+)/', $avg_delivery_time_str, $matches );
		$avg_delivery_time = isset( $matches[1] ) ? (int) $matches[1] : 30;
		$total_minutes = $avg_prep_time + ( $order_type === 'delivery' ? $avg_delivery_time : 0 );
		$estimated_datetime = date( 'Y-m-d H:i:s', strtotime( $order_datetime . " + {$total_minutes} minutes" ) );
		update_post_meta( $order_id, 'order_estimated_delivery', $estimated_datetime );

		// Adicionar nota do pedido
		$order_notes = [
			[
				'type' => 'success',
				'note' => esc_html__( 'Pedido criado manualmente pelo painel', 'myd-delivery-pro' ),
				'date' => wp_date( get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ) ),
			],
		];
		update_post_meta( $order_id, 'order_notes', $order_notes );

		// Enviar notificação push para o painel de pedidos
		\MydPro\Includes\Push\Push_Notifier::notify( '', $order_id, 'new' );

		return new \WP_REST_Response( [
			'success'  => true,
			'order_id' => $order_id,
			'message'  => 'Pedido criado com sucesso',
		], 200 );
	}
}

new Myd_Api();
