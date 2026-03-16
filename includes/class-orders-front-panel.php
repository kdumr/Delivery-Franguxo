<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Ensure dashboard shortcode class is available
if ( file_exists( MYD_PLUGIN_PATH . 'includes/class-dashboard-shortcode.php' ) ) {
	require_once MYD_PLUGIN_PATH . 'includes/class-dashboard-shortcode.php';
}

/**
 * TODO: refactor the class
 */
class Myd_Orders_Front_Panel {
	/**
	 * Queried orders object
	 *
	 * @var object
	 */
	protected $orders_object;

	/**
	 * Default args
	 *
	 * @var array
	 */
	protected $default_args = [
		'post_type' => 'mydelivery-orders',
		'posts_per_page' => 30,
		'no_found_rows' => true,
		'meta_query' => [
			'relation' => 'OR',
			[
				'key'     => 'order_status',
				'value'   => 'new',
				'compare' => '=',
			],
			[
				'key'     => 'order_status',
				'value'   => 'confirmed',
				'compare' => '=',
			],
			[
				'key'     => 'order_status',
				'value'   => 'in-delivery',
				'compare' => '=',
			],
			[
				'key'     => 'order_status',
				'value'   => 'done',
				'compare' => '=',
			],
			[
				'key'     => 'order_status',
				'value'   => 'finished',
				'compare' => '=',
			],
			[
				'key'     => 'order_status',
				'value'   => 'waiting',
				'compare' => '=',
			],
		]
	];

	/**
	 * Construct the class
	 */
	public function __construct () {
		add_shortcode( 'mydelivery-orders', [ $this, 'show_orders_list'] );
		// Simple dashboard shortcode for lightweight embeds (delegated to includes/class-dashboard-shortcode.php)
		add_shortcode( 'mydelivery-dashboard', [ '\\MydPro\\Includes\\Myd_Dashboard_Shortcode', 'render' ] );
		// Initialize dashboard AJAX handlers
		\MydPro\Includes\Myd_Dashboard_Shortcode::init();
		// Shortcode for confirmation choices (two stacked buttons)
		add_shortcode( 'myd-confirmation', [ $this, 'shortcode_confirmation_buttons'] );

		// Redirecionar para login quando página privada com shortcode [mydelivery-orders] é acessada sem autenticação
		add_action( 'template_redirect', [ $this, 'maybe_redirect_orders_to_login' ] );
		add_action( 'wp_ajax_reload_orders', [ $this, 'ajax_reload_orders'] );
		add_action( 'wp_ajax_update_orders', [ $this, 'update_orders'] );
		add_action( 'wp_ajax_print_orders', [ $this, 'ajax_print_order'] );
		add_action( 'wp_ajax_get_order_details', [ $this, 'ajax_get_order_details'] );
		// AJAX: retornar URLs de imagem dos itens de um pedido
		add_action( 'wp_ajax_get_order_images', [ $this, 'ajax_get_order_images'] );
		// Debug: retornar postmeta completo para um post_id (requer nonce)
		add_action( 'wp_ajax_debug_post_meta', [ $this, 'ajax_debug_post_meta'] );
		add_action( 'wp_ajax_sse_order_status', [ $this, 'ajax_sse_order_status'] );
		add_action( 'wp_ajax_check_locator', [ $this, 'ajax_check_locator'] );
		add_action( 'wp_ajax_nopriv_check_locator', [ $this, 'ajax_check_locator'] );
		// Confirmation code check (step3)
		add_action( 'wp_ajax_check_confirmation', [ $this, 'ajax_check_confirmation'] );
		add_action( 'wp_ajax_nopriv_check_confirmation', [ $this, 'ajax_check_confirmation'] );
		add_action( 'wp_ajax_get_order_print_data', [ $this, 'ajax_get_order_print_data'] );
		// AJAX: editar dados do cliente no pedido
		add_action( 'wp_ajax_myd_edit_order_customer', [ $this, 'ajax_edit_order_customer'] );
		// AJAX: verificação leve de IDs de pedidos (fallback polling)
		add_action( 'wp_ajax_check_new_orders', [ $this, 'ajax_check_new_orders'] );

		// Register REST route for confirmation (keeping AJAX for compatibility)
		add_action( 'rest_api_init', function() {
			register_rest_route( 'myd/v1', '/confirm', array(
				'methods' => 'POST',
				'callback' => [ $this, 'rest_check_confirmation' ],
				'permission_callback' => '__return_true',
			) );
		} );
	}


	/**
	 * Normaliza o valor de myd_order_items lido do post_meta.
	 * Suporta JSON (novo formato) e serialize PHP (formato legado).
	 *
	 * @param mixed $raw Valor bruto retornado por get_post_meta.
	 * @return array
	 */
	public static function parse_order_items( $raw ) : array {
		if ( is_array( $raw ) ) {
			return $raw; // já desserializado pelo WP (legado)
		}
		if ( is_string( $raw ) && strlen( $raw ) > 0 ) {
			// Tenta JSON primeiro (novo formato)
			$decoded = json_decode( $raw, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				return $decoded;
			}
			// Fallback: tenta unserialize PHP (pedidos antigos)
			$unserialized = @unserialize( $raw );
			if ( $unserialized !== false && is_array( $unserialized ) ) {
				return $unserialized;
			}
		}
		return array();
	}

	/**
	 * Ajax: get order data for printing
	 */
	public function ajax_get_order_print_data(){
		$this->ensure_clean_output_buffer();
		// Only allow administrators to request full order print data (this may contain sensitive coupon information).
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
			exit;
		}
		$order_id = isset( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0;
		if ( $order_id <= 0 ) {
			echo wp_json_encode( array( 'success' => false, 'message' => 'ID do pedido inválido' ) );
			exit;
		}

		// Get all the meta data
		$data = array(
			'id' => $order_id,
			'store_name' => get_option('blogname') ?: 'Loja',
			'date' => get_post_meta( $order_id, 'order_date', true ),
			'localizador' => get_post_meta( $order_id, 'myd_order_locator', true ) ?: get_post_meta( $order_id, 'order_locator', true ),
			'customer_name' => get_post_meta( $order_id, 'myd_order_customer_name', true ) ?: get_post_meta( $order_id, 'order_customer_name', true ),
			'customer_phone' => get_post_meta( $order_id, 'customer_phone', true ),
			'address' => get_post_meta( $order_id, 'order_address', true ),
			'address_number' => get_post_meta( $order_id, 'order_address_number', true ),
			'address_comp' => get_post_meta( $order_id, 'order_address_comp', true ),
			'neighborhood' => get_post_meta( $order_id, 'order_neighborhood', true ),
			'real_neighborhood' => get_post_meta( $order_id, 'order_real_neighborhood', true ),
			'reference' => get_post_meta( $order_id, 'order_address_reference', true ),
			'city' => get_post_meta( $order_id, 'order_city', true ),
			'state' => get_post_meta( $order_id, 'order_state', true ),
			'zipcode' => get_post_meta( $order_id, 'order_zipcode', true ),
			'items' => self::parse_order_items( get_post_meta( $order_id, 'myd_order_items', true ) ),
			'subtotal' => get_post_meta( $order_id, 'order_subtotal', true ),
			'delivery_price' => get_post_meta( $order_id, 'order_delivery_price', true ),
			'coupon_name' => get_post_meta( $order_id, 'order_coupon', true ),
			'coupon_discount' => get_post_meta( $order_id, 'order_coupon_discount', true ),
			'fidelity_discount' => get_post_meta( $order_id, 'order_fidelity_discount', true ),
			'loyalty_redeemed' => get_post_meta( $order_id, 'order_loyalty_redeemed', true ),
			'total' => get_post_meta( $order_id, 'order_total', true ),
			'payment_status' => get_post_meta( $order_id, 'order_payment_status', true ),
			'payment_method' => get_post_meta( $order_id, 'order_payment_method', true ),
			'payment_change' => get_post_meta( $order_id, 'order_change', true ),
			'customer_note' => get_post_meta( $order_id, 'order_customer_note', true ),
		);

		echo wp_json_encode( array( 'success' => true, 'data' => $data ) );
		exit;
	}

	/**
	 * Ajax: verifica localizador (action=check_locator)
	 * Pode ser chamado por usuários não autenticados (wp_ajax_nopriv_)
	 */
	public function ajax_check_locator() {
		$this->ensure_clean_output_buffer();
		$code = isset( $_REQUEST['code'] ) ? preg_replace( '/\D/', '', (string) $_REQUEST['code'] ) : '';
		if ( empty( $code ) ) {
			echo wp_json_encode( array( 'success' => false, 'message' => 'Código inválido' ) );
			exit;
		}

		$args = array(
			'post_type' => 'mydelivery-orders',
			'post_status' => 'publish',
			'fields' => 'ids',
			'posts_per_page' => 10,
			'meta_query' => array(
				'relation' => 'OR',
				array( 'key' => 'order_locator', 'value' => $code, 'compare' => '=' ),
				array( 'key' => 'myd_order_locator', 'value' => $code, 'compare' => '=' ),
			),
		);

		$found = get_posts( $args );
		if ( empty( $found ) ) {
			echo wp_json_encode( array( 'success' => false, 'message' => 'Código não localizado' ) );
			exit;
		}

		$ids = array_map( 'intval', (array) $found );
		$first = intval( $ids[0] );
		$customer_name = get_post_meta( $first, 'myd_order_customer_name', true ) ?: get_post_meta( $first, 'order_customer_name', true );

		echo wp_json_encode( array( 'success' => true, 'ids' => $ids, 'customer_name' => $customer_name ) );
		exit;
	}

	/**
	 * Ajax: verifica código de confirmação (action=check_confirmation)
	 */
	public function ajax_check_confirmation() {
		$this->ensure_clean_output_buffer();
		$order_id = isset( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0;
		$code = isset( $_REQUEST['code'] ) ? preg_replace( '/\D/', '', (string) $_REQUEST['code'] ) : '';
		if ( $order_id <= 0 || empty( $code ) ) {
			echo wp_json_encode( array( 'success' => false, 'message' => 'Parâmetros inválidos' ) );
			exit;
		}

		// Valida que o pedido está em status 'in-delivery' antes de confirmar
		$current_status = get_post_meta( $order_id, 'order_status', true );
		if ( strtolower( (string) $current_status ) !== 'in-delivery' ) {
			echo wp_json_encode( array( 'success' => false, 'message' => 'Este pedido não está em rota de entrega. Confirmação não permitida.' ) );
			exit;
		}

		$stored = get_post_meta( $order_id, 'order_confirmation_code', true );
		if ( empty( $stored ) ) $stored = get_post_meta( $order_id, 'myd_order_confirmation_code', true );
		if ( (string) $stored === (string) $code ) {
			// Marca como confirmado se ainda não estiver
			$current = get_post_meta( $order_id, 'order_status', true );
			if ( empty( $current ) || strtolower( (string) $current ) !== 'finished' ) {
					update_post_meta( $order_id, 'order_status', 'finished' );
					// Salva hora da confirmação (hora local) em formato HH:MM
					try {
						// Save full date and time in WP local timezone (MySQL format)
						$delivery_time = \date_i18n( 'd-m-Y H:i', \current_time( 'timestamp' ) );
						update_post_meta( $order_id, 'order_delivery_time', $delivery_time );
					} catch ( \Throwable $e ) {
						// silencioso: não interromper fluxo por erro de formatação de data
					}
			}
				$resp = array( 'success' => true );
				if ( isset( $delivery_time ) ) $resp['delivery_time'] = $delivery_time;
				echo wp_json_encode( $resp );
			exit;
		} else {
			echo wp_json_encode( array( 'success' => false, 'message' => 'Código incorreto' ) );
			exit;
		}
	}

	/**
	 * REST: endpoint de confirmação (POST /myd/v1/confirm)
	 */
	public function rest_check_confirmation( \WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$order_id = isset( $params['order_id'] ) ? intval( $params['order_id'] ) : 0;
		$code = isset( $params['code'] ) ? preg_replace( '/\D/', '', (string) $params['code'] ) : '';
		if ( $order_id <= 0 || empty( $code ) ) {
			return new \WP_REST_Response( array( 'success' => false, 'message' => 'Parâmetros inválidos' ), 400 );
		}
		$stored = get_post_meta( $order_id, 'order_confirmation_code', true );
		if ( empty( $stored ) ) $stored = get_post_meta( $order_id, 'myd_order_confirmation_code', true );
		if ( (string) $stored === (string) $code ) {
			update_post_meta( $order_id, 'order_status', 'confirmed' );
			// Save full delivery datetime for REST-confirmation as well
			try {
				$dt = \date_i18n( 'd-m-Y H:i', \current_time( 'timestamp' ) );
				update_post_meta( $order_id, 'order_delivery_time', $dt );
			} catch ( \Throwable $e ) {
				// noop
			}
			return new \WP_REST_Response( array( 'success' => true ), 200 );
		}
		return new \WP_REST_Response( array( 'success' => false, 'message' => 'Código incorreto' ), 403 );
	}

	/**
	 * Ensure output buffers are clean and zlib compression doesn't cause ob_end_flush notices
	 */
	protected function ensure_clean_output_buffer(){
		// attempt to disable zlib.output_compression for this request
		if ( function_exists('ini_set') ) {
			@ini_set('zlib.output_compression', '0');
		}
		// clear any active output buffers to avoid ob_end_flush notices
		while ( @ob_get_level() ) {
			@ob_end_clean();
		}
		// prevent the server from trying to flush after script ends
		@ignore_user_abort(true);
	}

	/**
	 * Shortcode: [myd-confirmation]
	 * Renders two stacked buttons: iFood and Cardápio próprio.
	 * Usage example:
	 *   [myd-confirmation ifood_url="https://ifood.com/sualoja" menu_url="/cardapio" ifood_label="iFood" menu_label="Cardápio próprio"]
	 */
	public function shortcode_confirmation_buttons( $atts = [] ) {
		$atts = shortcode_atts([
			'ifood_url'   => get_option('myd_ifood_url') ?: 'https://confirmacao-entrega-propria.ifood.com.br/',
			'menu_url'    => get_option('myd_menu_url') ?: '#',
			'ifood_label' => __( 'iFood', 'myd-delivery-pro' ),
			'menu_label'  => __( 'Cardápio próprio', 'myd-delivery-pro' ),
		], $atts, 'myd-confirmation');

		$primary = get_option( 'fdm-principal-color' );
		if ( ! $primary ) { $primary = '#f1a100'; }

		ob_start();
		?>
		<div class="myd-progress" aria-hidden="false" style="position:fixed;top:10px;left:12px;right:12px;width:calc(100% - 24px);box-sizing:border-box;padding:0;z-index:9999;">
				<div class="myd-progress-bar" style="display:flex;gap:8px;align-items:center;height:4px;">
					<div class="myd-progress-segment" data-step="1" style="flex:1;background:#e73535;border-radius:4px;height:100%;transition:background .18s ease;"></div>
					<div class="myd-progress-segment" data-step="2" style="flex:1;background:#e6e6e6;border-radius:4px;height:100%;transition:background .18s ease;"></div>
					<div class="myd-progress-segment" data-step="3" style="flex:1;background:#e6e6e6;border-radius:4px;height:100%;transition:background .18s ease;"></div>
					<div class="myd-progress-segment" data-step="4" style="flex:1;background:#e6e6e6;border-radius:4px;height:100%;transition:background .18s ease;"></div>
				</div>
				<button id="mydProgressBack" class="myd-progress-back" title="Voltar" aria-label="Voltar" style="font-size:18px;color:#fff;margin:6px 0 8px 0;border:0;background:<?php echo esc_attr( $primary ); ?>;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:24px;box-shadow:0 4px 10px rgba(0,0,0,.22);">
					<svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" width="20" height="20" aria-hidden="true" focusable="false" style="display:block;">
						<path d="M8 10L8 14L6 14L0 8L6 2L8 2L8 6L16 6L16 10L8 10Z" fill="currentColor"></path>
					</svg>
				</button>
		</div>
		<div class="myd-confirmation-shortcode" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 100%; max-width: 560px; padding: 20px 0 80px 0;">
			<style>
				.myd-confirmation-shortcode .myd-conf-stack{display:flex;flex-direction:column;gap:10px;max-width:560px;margin:0 auto;padding:0 14px}
				.myd-confirmation-shortcode .myd-conf-btn{display:flex;align-items:center;justify-content:center;width:100%;padding:12px 16px;border-radius:8px;font-weight:700;color:#fff;text-decoration:none;box-shadow:0 2px 6px rgba(0,0,0,.12); border: none; cursor: pointer;}
				.myd-confirmation-shortcode .myd-conf-btn:focus{outline:2px solid rgba(0,0,0,.15);outline-offset:2px}
				.myd-confirmation-shortcode .myd-conf-btn--ifood{background:#ea1d2b}
				.myd-confirmation-shortcode .myd-conf-btn--menu{background: <?php echo esc_attr( $primary ); ?>}
				.myd-confirmation-shortcode .step{display:none;}
				.myd-confirmation-shortcode .step.active{display:block;}
					 .myd-confirmation-shortcode .input-group{display:flex; gap:5px; justify-content:center; margin:10px 0;}
					 .myd-confirmation-shortcode .myd-conf-intro{ text-align:center;margin-bottom:6px; padding: 5%}
					 .myd-confirmation-shortcode .myd-conf-intro img{ width:40%;height:auto;display:block;margin:0 auto 8px; }
					 .myd-confirmation-shortcode .myd-conf-intro-text{ font-weight:700; font-size:1.125rem; padding: 25px;}
					.myd-confirmation-shortcode input[type="text"]{width:40px; height:40px; padding:0; box-sizing:border-box; text-align:center; font-size:18px; line-height:40px; border:1px solid #ccc; border-radius:4px;}
					.myd-confirmation-shortcode #step2 > p.step2-title{ text-align:center; font-weight:700; margin:0 0 8px 0; font-size:1.25rem; }
					.myd-confirmation-shortcode #step2 > p.step2-help{ text-align:center; font-weight:400; margin:6px 0 10px 0; font-size:.875rem; color:#888; padding: 25px}
					.myd-confirmation-shortcode .step3-title{ text-align:center; font-weight:700; margin:0 0 8px 0; font-size:1.25rem; }
					.myd-confirmation-shortcode .step3-title strong{ font-weight:700; }
					.myd-confirmation-shortcode .step3-help{ text-align:center; color:#888; font-size:.875rem; margin:6px 0 10px; }
					 /* hide internal continue buttons (we use a global footer button) */
					 .myd-confirmation-shortcode .continue-btn{display:none !important;}
				.myd-confirmation-shortcode .success-message{text-align:center; font-size:20px; color:green;}
			</style>
			<!-- Order card: displays order number and locator (shown in step3 and step4) -->
			<div class="step3-order-card" id="step3OrderCard" style="display:none;">
				<span id="step3OrderNumber" style="font-weight:700;">&nbsp;</span>
				<span id="step3OrderDot" style="color:#bbb; margin:0 8px;">&bull;</span>
				<span id="step3OrderLocator" style="color:#666;">&nbsp;</span>
			</div>
			<div id="step1" class="step active">
				<div class="myd-conf-stack" role="group" aria-label="Confirmação">
					<div class="myd-conf-intro">
						<img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/motoboy.png' ); ?>" alt="Motoboy" />
						<div class="myd-conf-intro-text">Escolha o botão correspondente ao tipo de<br>entrega que você está realizando</div>
					</div>
					<a class="myd-conf-btn myd-conf-btn--ifood" href="<?php echo esc_url( $atts['ifood_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $atts['ifood_label'] ); ?></a>
					<button class="myd-conf-btn myd-conf-btn--menu" onclick="clearConfirmationInputs(); nextStep(2)"><?php echo esc_html( $atts['menu_label'] ); ?></button>
				</div>
			</div>
			<div id="step2" class="step">
				<div class="myd-step2-intro" style="text-align:center;margin-bottom:8px;">
					<img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/note.png' ); ?>" alt="Order" style="width:60%;height:auto;display:block;margin:0 auto; padding: 25px;" />
				</div>
				<p class="step2-title">Informe o código localizador</p>
				<p class="step2-help">Você pode encontrar o código<br>localizador do pedido na comanda entregue pela<br>loja. O código é formado por 8 dígitos.</p>
				<div class="input-group">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="code1" oninput="autoAdvance(this,'code2'); checkStep2();" onkeydown="navBack(event,'')">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="code2" oninput="autoAdvance(this,'code3'); checkStep2();" onkeydown="navBack(event,'code1')">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="code3" oninput="autoAdvance(this,'code4'); checkStep2();" onkeydown="navBack(event,'code2')">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="code4" oninput="autoAdvance(this,'code5'); checkStep2();" onkeydown="navBack(event,'code3')">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="code5" oninput="autoAdvance(this,'code6'); checkStep2();" onkeydown="navBack(event,'code4')">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="code6" oninput="autoAdvance(this,'code7'); checkStep2();" onkeydown="navBack(event,'code5')">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="code7" oninput="autoAdvance(this,'code8'); checkStep2();" onkeydown="navBack(event,'code6')">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="code8" oninput="autoAdvance(this,''); checkStep2();" onkeydown="navBack(event,'code7')">
				</div>
				<button class="continue-btn" id="continueBtn2" onclick="submitCode1()" disabled>Continuar</button>
			</div>
			<div id="step3" class="step">
				<!-- Motoboy image above the question, matching Step 1 -->
				<div class="myd-conf-intro" style="text-align:center;margin-bottom:6px;">
					<img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/motoboy.png' ); ?>" alt="Motoboy" />
				</div>
				<p class="step3-title">Qual o código de entrega de <strong id="step3CustomerNameTitle"></strong>?</p>
				<p class="step3-help">Por gentileza, entregue o pedido para <strong id="step3CustomerNameHelp"></strong><br>somente após confirmar o código</p>
				<div class="input-group">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="codeA" oninput="autoAdvance(this,'codeB'); checkStep3();" onkeydown="navBack(event,'')">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="codeB" oninput="autoAdvance(this,'codeC'); checkStep3();" onkeydown="navBack(event,'codeA')">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="codeC" oninput="autoAdvance(this,'codeD'); checkStep3();" onkeydown="navBack(event,'codeB')">
						<input value="" inputmode="numeric" type="text" maxlength="1" pattern="[0-9]" id="codeD" oninput="autoAdvance(this,''); checkStep3();" onkeydown="navBack(event,'codeC')">
				</div>
			</div>
			<style>
				/* Step3 order card styling (matches screenshot) */
				.step3-order-card{box-sizing:border-box;margin:0 auto 6px auto;padding:12px 16px;border-radius:12px;background:#fff;border:1px solid #f0f0f0;max-width:920px;width:calc(100% - 48px);text-align:center;color:#333}
				.step3-order-card span{display:inline-block;vertical-align:middle}
			</style>
			<div id="step4" class="step">
				<!-- Lottie dotlottie success animation + message -->
				<div style="text-align:center;padding:10px 0;">
					<script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>
					<dotlottie-wc src="https://lottie.host/0fe2a6f2-060e-451b-acc8-40f55b7b8cf8/woeTT2oAfp.lottie" style="width:auto;height:auto;max-width:300px;max-height:300px;margin:0 auto;display:block;" autoplay loop></dotlottie-wc>
					<div class="success-message" style="font-size:20px;font-weight:700;margin-top:12px;">Entrega confirmada com sucesso!</div>
				</div>
			</div>
			</div>
			<style>
			.myd-confirmation-continue{position:fixed;left:0;bottom:0;width:100vw;transform:none;max-width:100%;z-index:9999;display:none;box-sizing:border-box;padding:1rem}
			.myd-confirmation-continue button{display:block;width:100%;padding:12px 0;border:0;background:<?php echo esc_attr($primary); ?>;color:#fff;font-size:16px;border-radius:6px}
			/* disabled state: less saturated and not-allowed cursor */
			.myd-confirmation-continue button:disabled{opacity:0.6;background:<?php echo esc_attr($primary); ?>;filter:saturate(.6);cursor:not-allowed}
			.myd-confirmation-deliver-another{position:fixed;left:0;bottom:0;width:100vw;transform:none;max-width:100%;z-index:9999;display:none;box-sizing:border-box;padding:1rem}
			.myd-confirmation-deliver-another button{display:block;width:100%;padding:12px 0;border:0;background:<?php echo esc_attr($primary); ?>;color:#fff;font-size:16px;border-radius:6px;font-weight:700}
			.myd-confirmation-shortcode .continue-btn:disabled{opacity:0.6;filter:saturate(.6);cursor:not-allowed}
			body { padding-bottom: calc(64px + 1rem); }
			/* Error card styles: full-screen dim overlay with bottom sheet filling horizontal */
			.myd-error-card{position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:10001;}
			/* place the white card above the bottom with an offset so it appears where the arrow points */
			.myd-error-card__inner{position:absolute;left:50%;transform:translateX(-50%);bottom:0;width:min(980px,calc(100% - 48px));background:#fff;border-radius:8px;padding:18px 24px 20px;box-shadow:0 8px 24px rgba(0,0,0,0.18);text-align:center}
			.myd-error-card__illustration{margin:0 auto 18px; width:96px;height:96px;display:flex;justify-content:center}
			.myd-error-card__title{margin:6px 0 6px;font-size:18px;font-weight:700;color:#222}
			.myd-error-card__msg{margin:0 0 16px;color:#666;}
			.myd-error-card__retry{display:block;width:100%;background:#e73535;color:#fff;border:0;padding:14px;border-radius:6px;font-weight:700;margin-top:12px}
			.myd-error-card__inner{position:absolute;left:50%;transform:translateX(-50%) translateY(12px);bottom:0;width:min(980px,calc(100% - 48px));background:#fff;border-radius:8px;padding:18px 24px 20px;box-shadow:0 8px 24px rgba(0,0,0,0.18);text-align:center;opacity:0;transition:transform .28s cubic-bezier(.2,.9,.2,1),opacity .28s cubic-bezier(.2,.9,.2,1)}
			/* visible state triggers slide-up */
			.myd-error-card--visible .myd-error-card__inner{transform:translateX(-50%) translateY(0);opacity:1}
			/* constrain large inline SVG so it doesn't overlap content */
			/* SVG sizing: smaller to avoid overlap */
			.myd-error-card__illustration svg{width:88px;height:auto;display:block;margin:0 auto}
			.myd-error-card__inner{width:100%;max-width:100%;background:#fff;border-radius:8px 8px 6px 6px;padding:18px 24px 20px;box-shadow:0 8px 24px rgba(0,0,0,0.12);text-align:center;margin-top:auto}
			</style>
			<div class="myd-confirmation-continue">
				<button id="continueBtnGlobal" disabled>Continuar</button>
			</div>
			<div class="myd-confirmation-deliver-another">
				<button id="deliverAnotherBtn">Entregar outro pedido</button>
			</div>
			<script>
			// Ensure confirmation inputs always start empty and disable browser autofill
			function clearConfirmationInputs(){
				try{
					var ids = ['code1','code2','code3','code4','code5','code6','code7','code8','codeA','codeB','codeC','codeD'];
					ids.forEach(function(id){
						var el = document.getElementById(id);
						if(!el) return;
						try{ el.value = ''; }catch(_){}
						try{ el.autocomplete = 'off'; el.setAttribute('autocomplete','off'); }catch(_){}
						try{ el.spellcheck = false; el.setAttribute('aria-autocomplete','none'); }catch(_){}
						// remove name attribute to reduce autofill heuristics
						try{ el.removeAttribute('name'); }catch(_){}
					});
					// Blur any focused input to avoid leaving a prefilled caret
					try{ if(document.activeElement && ids.indexOf(document.activeElement.id) !== -1) document.activeElement.blur(); }catch(_){}
					// also clear the visual order card if present
					try{
						const card = document.getElementById('step3OrderCard'); if(card) card.style.display = 'none';
						const numEl = document.getElementById('step3OrderNumber'); if(numEl) numEl.textContent = '';
						const locEl = document.getElementById('step3OrderLocator'); if(locEl) locEl.textContent = '';
					} catch(_){ }
				} catch(e) { console.warn('clearConfirmationInputs failed', e); }
			}
			// Run once when shortcode is rendered
			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', clearConfirmationInputs); else clearConfirmationInputs();
			</script>
			<!-- Error card (hidden by default) -->
			<div class="myd-error-card" id="mydErrorCard" role="alert" aria-hidden="true" style="display:none;">
				<div class="myd-error-card__inner">
					<div class="myd-error-card__illustration" aria-hidden="true">
						<svg height="150" width="150" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path style="fill:#FEA921;" d="M318.873,23.442C314.314,9.461,301.277,0,286.571,0h-61.143c-14.705,0-27.742,9.461-32.301,23.442 l-8.559,26.248l-9.77,29.958L53.132,452.734h405.736L318.873,23.442z"></path> <path style="fill:#FEA921;" d="M498.628,509.741H13.371c-6.268,0-11.35-5.082-11.35-11.35v-34.307c0-6.268,5.082-11.35,11.35-11.35 h485.258c6.268,0,11.35,5.082,11.35,11.35v34.307C509.978,504.66,504.897,509.741,498.628,509.741z"></path> </g> <path style="fill:#E58200;" d="M498.629,483.497H13.371c-6.268,0-11.35-5.082-11.35-11.35v28.504c0,6.268,5.081,11.35,11.35,11.35 h485.258c6.268,0,11.35-5.082,11.35-11.35v-28.504C509.978,478.416,504.897,483.497,498.629,483.497z"></path> <g> <path style="fill:#F2F2F2;" d="M111.795,272.845L329.281,55.359l-10.408-31.917c-3.341-10.243-11.24-18.042-21.005-21.489 L152.84,146.981L111.795,272.845z"></path> <polygon style="fill:#F2F2F2;" points="375.45,196.935 354.592,132.973 61.988,425.577 53.132,452.734 119.65,452.734 "></polygon> <polygon style="fill:#F2F2F2;" points="421.618,338.512 400.76,274.55 222.576,452.734 307.395,452.734 "></polygon> </g> <path style="fill:#E58200;" d="M318.873,23.442C314.314,9.461,301.277,0,286.571,0h-31.259c14.705,0,27.742,9.461,32.301,23.442 l122.272,374.944c3.714,11.392-4.775,23.091-16.757,23.091H63.325l-10.193,31.259h374.477h31.259L318.873,23.442z"></path> <g> <path style="fill:#CCCCCC;" d="M318.02,21.145c-0.091-0.225-0.189-0.445-0.285-0.667c-0.239-0.552-0.492-1.097-0.759-1.632 c-0.105-0.211-0.207-0.426-0.316-0.635c-0.36-0.688-0.739-1.363-1.142-2.019c-0.11-0.178-0.229-0.348-0.342-0.524 c-0.309-0.482-0.628-0.957-0.96-1.422c-0.186-0.261-0.377-0.518-0.571-0.775c-0.285-0.376-0.579-0.742-0.879-1.104 c-0.209-0.253-0.412-0.51-0.629-0.757c-0.449-0.514-0.915-1.012-1.394-1.496c-0.29-0.294-0.593-0.574-0.893-0.855 c-0.242-0.227-0.484-0.453-0.733-0.673c-0.34-0.3-0.683-0.597-1.034-0.884c-0.194-0.159-0.394-0.311-0.591-0.465 c-1.307-1.02-2.683-1.947-4.126-2.768c-0.137-0.078-0.274-0.156-0.412-0.232c-1.625-0.894-3.325-1.664-5.088-2.286l-13.856,13.856 c1.487,2.35,2.71,4.901,3.601,7.633l18.095,55.489l23.572-23.572l-6.442-19.754l-3.966-12.163 C318.618,22.66,318.327,21.898,318.02,21.145z"></path> <polygon style="fill:#CCCCCC;" points="150.909,421.476 66.09,421.476 61.988,425.577 53.132,452.734 119.65,452.734 "></polygon> <polygon style="fill:#CCCCCC;" points="331.02,156.546 351.878,220.508 375.45,196.935 354.592,132.973 "></polygon> <polygon style="fill:#CCCCCC;" points="338.653,421.476 253.834,421.476 222.576,452.734 307.395,452.734 "></polygon> <polygon style="fill:#CCCCCC;" points="377.189,298.122 398.047,362.083 421.618,338.512 400.76,274.55 "></polygon> </g> </g></svg>
					</div>
					<h3 class="myd-error-card__title">Não foi possível confirmar a entrega.</h3>
					<p class="myd-error-card__msg" id="mydErrorMsg">Verifique o número do código localizador na comanda e tente novamente.</p>
					<button id="mydErrorRetry" class="myd-error-card__retry">Tentar novamente</button>
				</div>
			</div>
			<!-- Loading overlay -->
			<div id="mydLoading" aria-live="polite" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:10002; align-items:center; justify-content:center;">
				<div id="mydLoadingInner" style="background:transparent; padding:12px; border-radius:8px; text-align:center; display:flex; flex-direction:column; align-items:center; gap:8px;">
					<!-- Animated SVG spinner (transparent background) -->
					<svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none" class="hds-flight-icon--animation-loading" width="56" height="56" aria-hidden="true">
						<g fill="#ff5900" fill-rule="evenodd" clip-rule="evenodd">
							<path d="M8 1.5a6.5 6.5 0 100 13 6.5 6.5 0 000-13zM0 8a8 8 0 1116 0A8 8 0 010 8z" opacity=".2"></path>
							<path d="M7.25.75A.75.75 0 018 0a8 8 0 018 8 .75.75 0 01-1.5 0A6.5 6.5 0 008 1.5a.75.75 0 01-.75-.75z"></path>
						</g>
					</svg>
					<span id="mydLoadingText" style="color:#fff; font-weight:600; font-size:14px; margin-top:4px;">Carregando...</span>
				</div>
			</div>
			<style>
				/* spinner rotation */
				@keyframes myd-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
				.hds-flight-icon--animation-loading { animation: myd-spin 1s linear infinite; background: transparent; }
				#mydLoading { display: none; }
				#mydLoadingInner { pointer-events: none; }
				#mydLoadingText { pointer-events: none; }
			</style>
			<script>
			// Error card helpers
			function showErrorCard(message){
				const card = document.getElementById('mydErrorCard');
				const msg = document.getElementById('mydErrorMsg');
				if(msg) msg.textContent = message || 'Ocorreu um erro';
				if(!card) return;
				// require explicit hide call to close this card
				card.style.display = 'block';
				card.setAttribute('aria-hidden','false');
				// trigger reflow then add visible class to animate
				void card.offsetWidth;
				card.classList.add('myd-error-card--visible');
				// keep global continue visible so user can retry without losing context
			}
			function hideErrorCard(force){
				// only hide when explicitly forced to avoid accidental closes
				if(!force) return;
				const card = document.getElementById('mydErrorCard');
				if(!card) return;
				// remove visible class to start hide animation
				card.classList.remove('myd-error-card--visible');
				card.setAttribute('aria-hidden','true');
				// after transition, hide the element
				const inner = card.querySelector('.myd-error-card__inner');
				if(inner){
					const onEnd = function(){ card.style.display = 'none'; inner.removeEventListener('transitionend', onEnd); };
					inner.addEventListener('transitionend', onEnd);
				} else {
					card.style.display = 'none';
				}
				// restore continue visibility based on active step
				const wrapper = document.querySelector('.myd-confirmation-continue');
				if(document.getElementById('step2').classList.contains('active') || document.getElementById('step3').classList.contains('active')){
					if(wrapper) wrapper.style.display = 'block';
				} else {
					if(wrapper) wrapper.style.display = 'none';
				}
			}
			// wire retry button
			document.addEventListener('click', function(e){
				if(e.target && e.target.id === 'mydErrorRetry'){
					// clear inputs and visual card
					if(typeof clearConfirmationInputs === 'function') clearConfirmationInputs();
					// re-run checks to disable continue buttons
					if(typeof checkStep2 === 'function') checkStep2();
					if(typeof checkStep3 === 'function') checkStep3();
					hideErrorCard(true);
					// focus first input of active step (prefer step2)
					if(document.getElementById('step2').classList.contains('active')){
						const first = document.getElementById('code1'); if(first) first.focus();
					} else if(document.getElementById('step3').classList.contains('active')){
						const first = document.getElementById('codeA'); if(first) first.focus();
					}
				}
			});
				function nextStep(step) {
					document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
					document.getElementById('step' + step).classList.add('active');
					// update progress header
					if(typeof updateProgress === 'function') updateProgress(step);
					// show global continue only on step2 and step3
					const wrapper = document.querySelector('.myd-confirmation-continue');
					if(step === 2 || step === 3){
						if(wrapper) wrapper.style.display = 'block';
						// when entering step3, ensure continue is disabled until inputs are filled
						if(step === 3){ const g = document.getElementById('continueBtnGlobal'); if(g) g.disabled = true; }
					} else {
						if(wrapper) wrapper.style.display = 'none';
					}
					// show deliver another only on step4
					const deliverWrapper = document.querySelector('.myd-confirmation-deliver-another');
					if(step === 4){
						if(deliverWrapper) deliverWrapper.style.display = 'block';
						// ensure order card is shown
						const card = document.getElementById('step3OrderCard');
						if(card) card.style.display = 'block';
					} else {
						if(deliverWrapper) deliverWrapper.style.display = 'none';
					}
				}

				// update progress UI: step is 1..4
				function updateProgress(step){
					try{
						const text = document.querySelector('.myd-progress-text');
						const fill = document.querySelector('.myd-progress-fill');
								// update segmented progress and back-arrow visibility
								const segments = document.querySelectorAll('.myd-progress-segment');
								if(segments && segments.length){
									segments.forEach(s => {
										const sStep = parseInt(s.getAttribute('data-step') || '0', 10);
										if(sStep <= step) s.style.background = '#ffae00';
										else s.style.background = '#e6e6e6';
									});
								}
								const back = document.getElementById('mydProgressBack');
								if(back) back.style.visibility = (step === 2 || step === 3) ? 'visible' : 'hidden';
					} catch(e){}

				}

				// handle deliver another button
				document.addEventListener('click', function(e){
					if(e.target && e.target.id === 'deliverAnotherBtn'){
						// reset to step1
						if(typeof clearConfirmationInputs === 'function') clearConfirmationInputs();
						nextStep(1);
						// hide deliver another footer
						const wrapper = document.querySelector('.myd-confirmation-deliver-another'); if(wrapper) wrapper.style.display = 'none';
					}
				});

				// back arrow handler for progress
				document.addEventListener('click', function(e){
					if(e.target && e.target.id === 'mydProgressBack'){
						goToPreviousStep();
					}
				});

				function goToPreviousStep(){
					try{
						const current = Array.from(document.querySelectorAll('.step')).findIndex(s => s.classList.contains('active')) + 1;
						const prev = Math.max(1, current - 1);
						nextStep(prev);
					} catch(e){}
				}
				function checkStep3(){
					let allFilled = true;
					['A','B','C','D'].forEach(l => {
						if(document.getElementById('code' + l).value === '') allFilled = false;
					});
					document.getElementById('continueBtnGlobal').disabled = !allFilled;
				}
				function checkStep2() {
					let allFilled = true;
					for(let i=1; i<=8; i++) {
						if(document.getElementById('code' + i).value === '') {
							allFilled = false;
							break;
						}
					}
					document.getElementById('continueBtn2').disabled = !allFilled;
					document.getElementById('continueBtnGlobal').disabled = !allFilled;
				}
				function showLoading(){
					const l = document.getElementById('mydLoading');
					if(l){ l.style.display = 'flex'; }
				}
				function hideLoading(){
					const l = document.getElementById('mydLoading');
					if(l){ l.style.display = 'none'; }
				}

				function submitCode1() {
					let code = '';
					for(let i=1; i<=8; i++) {
						code += document.getElementById('code' + i).value;
					}
					const ajaxUrl = '<?php echo esc_js( admin_url( "admin-ajax.php" ) ); ?>';
					const data = new FormData();
					data.append('action','check_locator');
					data.append('code', code);
					const controller = new AbortController();
					const timeout = setTimeout(() => controller.abort(), 20000);
					showLoading();
					fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin', signal: controller.signal })
						.then(r => r.json())
						.then(json => {
							clearTimeout(timeout);
							hideLoading();
							if(json && json.success){
								const cname = (json.customer_name && json.customer_name.trim()) ? json.customer_name.trim() : '';
								const titleEl = document.getElementById('step3CustomerNameTitle');
								const helpEl = document.getElementById('step3CustomerNameHelp');
								// show only the first name (e.g. 'Carlos' from 'Carlos Eduardo Martins')
								const firstName = (cname) ? (cname.split(/\s+/)[0] || cname) : '';
								const displayName = firstName || 'cliente';
								if(titleEl) titleEl.textContent = displayName;
								if(helpEl) helpEl.textContent = displayName;
								nextStep(3);
								hideErrorCard(true);
								const first = document.getElementById('codeA'); if(first) first.focus();
								if(json.ids && json.ids.length){
									window.__myd_matched_order_id = json.ids[0];
									// populate the order card
									try{
										const card = document.getElementById('step3OrderCard');
										const numEl = document.getElementById('step3OrderNumber');
										const locEl = document.getElementById('step3OrderLocator');
										if(numEl) numEl.textContent = 'Pedido #' + json.ids[0];
										if(locEl) locEl.textContent = 'Localizador ' + code;
										if(card) card.style.display = 'block';
									} catch(e){ /* ignore DOM errors */ }
								}
							} else {
								const msg = (json && json.message) ? json.message : 'Código não localizado';
								showErrorCard(msg);
							}
						})
						.catch(err => {
							clearTimeout(timeout);
							hideLoading();
							if(err && err.name === 'AbortError'){
								showErrorCard('Verifique sua conexão com internet e tente novamente.');
							} else {
								showErrorCard('Erro de rede ao verificar código');
							}
						});
				}
				function submitCode2() {
					let code = '';
					let letters = ['A','B','C','D'];
					for(let letter of letters) {
						code += document.getElementById('code' + letter).value;
					}
					const orderId = window.__myd_matched_order_id || 0;
					if(!orderId){ showErrorCard('Pedido não identificado. Reinicie o processo.'); return; }
					const ajaxUrl = '<?php echo esc_js( admin_url( "admin-ajax.php" ) ); ?>';
					const data = new FormData();
					data.append('action','check_confirmation');
					data.append('order_id', orderId);
					data.append('code', code);
					const controller = new AbortController();
					const timeout = setTimeout(() => controller.abort(), 8000);
					showLoading();
					fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin', signal: controller.signal })
						.then(r => r.json())
						.then(json => {
							clearTimeout(timeout);
							hideLoading();
							if(json && json.success){
								nextStep(4);
								hideErrorCard(true);
							} else {
								const msg = (json && json.message) ? json.message : 'Código incorreto';
								showErrorCard(msg);
							}
						})
						.catch(err => {
							clearTimeout(timeout);
							hideLoading();
							if(err && err.name === 'AbortError'){
								showErrorCard('A requisição demorou demais. Tente novamente.');
							} else {
								showErrorCard('Erro de rede ao verificar código de confirmação');
							}
						});
				}
				function autoAdvance(el, nextId){
					// allow only digits
					el.value = el.value.replace(/[^0-9]/g,'');
					if(el.value.length === 1 && nextId){
						const next = document.getElementById(nextId);
						if(next) next.focus();
					}
				}
				function navBack(e, prevId){
					if(e.key === 'Backspace' && e.target.value === '' && prevId){
						const prev = document.getElementById(prevId);
						if(prev) prev.focus();
					}
				}

				function pasteHandlerStep(el, expectedLen, prefix){
					return function(e){
						e.preventDefault();
						const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
						if(!text) return;
						for(let i=0;i<expectedLen;i++){
							const ch = text[i] || '';
							const input = document.getElementById(prefix + (i+1));
							if(input) input.value = ch;
						}
						checkStep2();
						// focus after paste
						const next = document.getElementById(prefix + expectedLen);
						if(next) next.focus();
					}
				}

				function pasteHandlerStep3(el){
					return function(e){
						e.preventDefault();
						const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
						if(!text) return;
						const letters = ['A','B','C','D'];
						for(let i=0;i<4;i++){
							const ch = text[i] || '';
							const input = document.getElementById('code' + letters[i]);
							if(input) input.value = ch;
						}
						checkStep3();
						const last = document.getElementById('codeD');
						if(last) last.focus();
					}
				}

				function attachPasteHandlers(){
					for(let i=1;i<=8;i++){
						const inp = document.getElementById('code' + i);
						if(inp) inp.addEventListener('paste', pasteHandlerStep(inp,8,'code'));
					}
					['codeA','codeB','codeC','codeD'].forEach(id => {
						const inp = document.getElementById(id);
						if(inp) inp.addEventListener('paste', pasteHandlerStep3(inp));
					});
				}

				// attach on load
				setTimeout(attachPasteHandlers, 100);

				// Protect the error card from accidental external hides.
				// If the card is visible (has visible class) but some other script sets
				// `style.display='none'` or `aria-hidden='true'`, restore it.
				(function(){
					function initErrorCardProtector(){
						const card = document.getElementById('mydErrorCard');
						if(!card) return;
						const observer = new MutationObserver(function(mutations){
							if(window.__myd_error_force_hiding) return; // allow intentional hides
							if(!card.classList.contains('myd-error-card--visible')) return;
							for(const m of mutations){
								if(m.type === 'attributes' && (m.attributeName === 'style' || m.attributeName === 'aria-hidden' || m.attributeName === 'class')){
									const isHidden = (card.style && card.style.display === 'none') || card.getAttribute('aria-hidden') === 'true';
									if(isHidden){
										card.style.display = 'block';
										card.setAttribute('aria-hidden','false');
										void card.offsetWidth; // reflow
										card.classList.add('myd-error-card--visible');
									}
								}
							}
						});
						observer.observe(card, { attributes: true, attributeFilter: ['style','class','aria-hidden'] });
					}
					setTimeout(initErrorCardProtector, 150);
				})();
				document.getElementById('continueBtnGlobal').addEventListener('click', function(){
					// Trigger the visible step's continue
					if(document.getElementById('step2').classList.contains('active')){
						submitCode1();
					} else if(document.getElementById('step3').classList.contains('active')){
						submitCode2();
					}
				});
				// initialize progress UI with step 1 active
				updateProgress(1);
			</script>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Redireciona para a página de login quando o usuário não autenticado tenta acessar
	 * uma página privada que contém o shortcode [mydelivery-orders].
	 * Isso resolve o problema do WordPress exibir "Desculpe, você não tem permissão..."
	 * antes mesmo de o shortcode ser processado.
	 */
	public function maybe_redirect_orders_to_login() {
		// Se tentar acessar o link de "Criar Pedido" e não estiver logado, redireciona para login e volta.
		if ( isset( $_GET['myd_create_order'] ) ) {
			if ( ! is_user_logged_in() ) {
				// Monta URL de retorno mantendo o parâmetro
				$redirect_to = home_url( '/?myd_create_order=1' );
				$login_url   = wp_login_url( $redirect_to );
				wp_redirect( $login_url );
				exit;
			}
		}

		if ( is_user_logged_in() || is_admin() ) {
			return;
		}

		// Verifica se é uma página singular (pode ser 404 por ser privada)
		$queried = get_queried_object();
		$post    = null;

		if ( $queried instanceof \WP_Post ) {
			$post = $queried;
		}

		// Fallback: tenta resolver pelo slug da URL quando o WordPress retorna 404 para páginas privadas
		if ( ! $post && is_404() ) {
			$request_path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
			$slug         = basename( $request_path );
			if ( $slug ) {
				$found = get_posts( [
					'name'        => $slug,
					'post_type'   => 'page',
					'post_status' => 'private',
					'numberposts' => 1,
				] );
				if ( ! empty( $found ) ) {
					$post = $found[0];
				}
			}
		}

		if ( ! $post ) {
			return;
		}

		// Verifica se a página contém o shortcode [mydelivery-orders]
		if ( has_shortcode( $post->post_content, 'mydelivery-orders' ) ) {
			$redirect_to = home_url( $_SERVER['REQUEST_URI'] );
			$login_url   = wp_login_url( $redirect_to );
			wp_redirect( $login_url );
			exit;
		}
	}

	/**
	 * Output template panel
	 *
	 * TODO: move to new class
	 *
	 * @return void
	 * @since 1.9.5
	 */
	public function show_orders_list () {
		// Redireciona para a página de login se o usuário não estiver logado
		if ( ! is_user_logged_in() ) {
			$redirect_to = get_permalink();
			$login_url   = wp_login_url( $redirect_to );
			if ( ! headers_sent() ) {
				wp_redirect( $login_url );
				exit;
			} else {
				echo '<script>window.location.href = "' . esc_url( $login_url ) . '";</script>';
				return '';
			}
		}

		// Permitir acesso para quem tem a role "marketing" também
		$user  = wp_get_current_user();
		$roles = (array) $user->roles;

		if ( current_user_can( 'edit_posts' ) || in_array( 'marketing', $roles, true ) ) {
			// Ensure Socket.IO client is available for realtime updates in orders panel
			\wp_enqueue_script( 'socket-io' );
			\wp_enqueue_script( 'myd-orders-panel' );

			// Provide order_ajax_object to JS so socket handlers can fetch/insert new order cards via AJAX
			\wp_localize_script( 'myd-orders-panel', 'order_ajax_object', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'myd-order-notification' ),
				'domain'   => home_url(),
			) );

				\wp_enqueue_script( 'myd-order-list-ajax' );
				\wp_enqueue_script( 'myd-new-order-highlight' );
				\wp_enqueue_script( 'myd-order-poll-fallback' );
				\wp_enqueue_script( 'myd-print-audio' );
				\wp_enqueue_script( 'myd-update-notes', plugins_url( 'assets/js/myd-update-notes.js', dirname( __FILE__ ) . '/../myd-delivery-pro.php' ), array('jquery'), MYD_CURRENT_VERSION, true );
				// Passa a URL absoluta do update-notes.html para o JS
				$update_notes_url = plugins_url( 'assets/update-notes.html', dirname( __FILE__ ) . '/../myd-delivery-pro.php' );
				// Carrega o conteúdo do arquivo de notas no servidor e embute apenas para usuários autorizados
				$update_notes_path = rtrim( MYD_PLUGIN_PATH, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'update-notes.html';
				$notes_html = '';
				if ( file_exists( $update_notes_path ) && is_readable( $update_notes_path ) ) {
					$notes_html = file_get_contents( $update_notes_path );
				}
				// Indica ao JS que o usuário atual tem permissão e fornece o HTML embutido
				wp_localize_script( 'myd-update-notes', 'mydUpdateNotesUrlObj', array(
					'html' => $notes_html,
					'can_view' => true,
				) );
			\wp_enqueue_style( 'myd-order-panel-frontend' );
			// Enqueue consolidated panel styles migrated from templates/order/panel.php
			// NOTE: panel-inline.css was consolidated into order-panel-frontend.min.css.
			// Avoid enqueuing the deprecated handle to prevent duplicate stylesheet loads.
			\wp_enqueue_style( 'myd-order-panel-frontend' );
			\wp_enqueue_style( 'myd-panel-overrides' );
			\wp_enqueue_script( 'plugin_pdf' );
			\wp_enqueue_style( 'plugin_pdf_css' );

			/**
			 * Query orders
			 */
			$orders = new Myd_Store_Orders( $this->default_args );
			$orders = $orders->get_orders_object();
			$this->orders_object = $orders;

			/**
			 * Include templates
			 */
			ob_start();
			include MYD_PLUGIN_PATH . 'templates/order/panel.php';
			return ob_get_clean();
		} else {
			return '<div class="fdm-not-logged">' . __( 'Desculpe, você não tem acesso a essa página.', 'myd-delivery-pro' ) . '</div>';
		}
	}

	/**
	 * Simple dashboard shortcode output — minimal Hello world placeholder.
	 *
	 * @return string
	 */
	public function show_dashboard() {
		// Keep same permission model as orders panel: require edit_posts capability
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			return '<div class="myd-dashboard-unauth">' . __( 'Sorry, you dont have access to this page.', 'myd-delivery-pro' ) . '</div>';
		}

		// Enqueue Chart.js from CDN (in footer)
		if ( function_exists( 'wp_enqueue_script' ) ) {
			\wp_enqueue_script( 'myd-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true );
		}

		// Default range: last 30 days (inclusive). Allow override via GET params `myd_dashboard_start` and `myd_dashboard_end` (YYYY-MM-DD).
		$end_ts = current_time( 'timestamp' );
		// Read GET params (safe fallback)
		$start_param = isset( $_GET['myd_dashboard_start'] ) ? sanitize_text_field( wp_unslash( $_GET['myd_dashboard_start'] ) ) : '';
		$end_param = isset( $_GET['myd_dashboard_end'] ) ? sanitize_text_field( wp_unslash( $_GET['myd_dashboard_end'] ) ) : '';
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_param ) ) {
			$end_ts = strtotime( $end_param . ' 23:59:59' );
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_param ) ) {
			$start_ts = strtotime( $start_param . ' 00:00:00' );
		} else {
			$start_ts = strtotime( '-29 days', $end_ts );
		}
		// Ensure start <= end
		if ( $start_ts > $end_ts ) {
			$tmp = $start_ts; $start_ts = $end_ts; $end_ts = $tmp;
		}
		// Limit maximum range to 2 years to avoid heavy queries
		$max_range = 365 * 2 * 24 * 3600;
		if ( ( $end_ts - $start_ts ) > $max_range ) {
			$start_ts = strtotime( '-365 days', $end_ts );
		}
		$days = array();
		for ( $ts = $start_ts; $ts <= $end_ts; $ts = strtotime( '+1 day', $ts ) ) {
			$days[] = date( 'Y-m-d', $ts );
		}

		$args = array(
			'post_type' => 'mydelivery-orders',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'date_query' => array(
				array(
					'after' => date( 'Y-m-d', $start_ts ),
					'before' => date( 'Y-m-d', $end_ts ),
					'inclusive' => true,
				),
			),
		);

		$query = new \WP_Query( $args );
		$total = 0.0;
		$count = 0;
		$economy = 0.0; // sum of discounts
		$daily = array_fill_keys( $days, 0.0 );

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $p ) {
				$post_id = is_object( $p ) ? $p->ID : (int) $p['ID'];

				// order total fallbacks
				$order_total = get_post_meta( $post_id, 'order_total', true );
				if ( $order_total === '' || $order_total === null ) {
					$order_total = get_post_meta( $post_id, 'myd_order_total', true );
				}
				if ( $order_total === '' || $order_total === null ) {
					$order_total = get_post_meta( $post_id, 'fdm_order_total', true );
				}
				$order_total = floatval( str_replace( ',', '.', (string) $order_total ) );
				$total += $order_total;
				$count++;

				// discounts (economia)
				$coupon = floatval( get_post_meta( $post_id, 'order_coupon_discount', true ) );
				$fidelity = floatval( get_post_meta( $post_id, 'order_fidelity_discount', true ) );
				$economy += ( $coupon + $fidelity );

				$day = date( 'Y-m-d', strtotime( $p->post_date ) );
				if ( isset( $daily[ $day ] ) ) {
					$daily[ $day ] += $order_total;
				}
			}
		}

		$avg = ( $count > 0 ) ? ( $total / $count ) : 0.0;
		$currency = \MydPro\Includes\Store_Data::get_store_data( 'currency_simbol' ) ?: 'R$';

		$labels = array_map( function( $d ) { return date( 'd/m', strtotime( $d ) ); }, $days );
		$data = array_values( $daily );

		ob_start();
		?>
		<style>
		.myd-dashboard-wrap{font-family:Arial,Helvetica,sans-serif;padding:18px;max-width:1100px;margin:0 auto}
		.myd-dashboard-header{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
		.myd-dashboard-cards{display:flex;gap:12px;margin:12px 0 22px 0;flex-wrap:wrap}
		.myd-dashboard-card{flex:1 1 220px;background:#fff;border:1px solid #eee;padding:14px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.04)}
		.myd-dashboard-card .title{font-size:13px;color:#666;margin-bottom:8px}
		.myd-dashboard-card .value{font-size:20px;font-weight:700;color:#222}
		#mydSalesChart{background:#fff;border-radius:8px;padding:10px}
		</style>

		<?php
		return ob_get_clean();
		}

	/**
	 * Loop orders list
	 *
	 * TODO: move to new class
	 *
	 * @return void
	 * @since 1.9.5
	 */
	public function loop_orders_list () {
		$orders = $this->orders_object;

		ob_start();
		include MYD_PLUGIN_PATH . 'templates/order/order-list.php';
		return ob_get_clean();
	}

	/**
	 * Orders content
	 *
	 * TODO: move to new class
	 *
	 * @return void
	 * @since 1.9.5
	 */
	public function loop_orders_full () {
		       $orders = $this->orders_object;

		       // Força recarregar o meta 'order_locator' para cada pedido antes de renderizar o HTML
		       if ( is_array($orders) || $orders instanceof \Traversable ) {
			       foreach ( $orders as $order ) {
				       $post_id = is_object($order) ? $order->ID : $order['ID'];
				       // Limpa cache do meta para garantir leitura do valor atualizado
				       delete_post_meta( $post_id, '_order_locator_cache_fix' );
				       // Releitura forçada
				       get_post_meta( $post_id, 'order_locator', true );
				       get_post_meta( $post_id, 'myd_order_locator', true );
			       }
		       }

		       ob_start();
		       include MYD_PLUGIN_PATH . 'templates/order/order-content.php';
		       return ob_get_clean();
	}

	/**
	 * Orders print
	 *
	 * TODO: move to new class
	 *
	 * @return void
	 * @since 1.9.5
	 */
	public function loop_print_order () {
		$orders = $this->orders_object;

		ob_start();
		include MYD_PLUGIN_PATH . 'templates/order/print.php';
		return ob_get_clean();
	}

	/**
	 * Count orders
	 *
	 * @return void
	 */
	public function count_orders() {
		$orders = $this->query_orders();
		$orders = $orders->get_posts();

		return count( $orders );
	}

	/**
	 * Ajax class items
	 *
	 * @return void
	 */
	public function ajax_reload_orders() {
        $this->ensure_clean_output_buffer();
		// Permission check: only allow logged in users with appropriate capability
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			echo wp_json_encode( array( 'error' => 'Unauthorized' ) );
			exit;
		}

		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'myd-order-notification' ) ) {
			echo wp_json_encode(
				array(
					'error' => 'Security validation failed.',
				)
			);
			exit;
		}

		$order_id = sanitize_text_field( $_REQUEST['id'] );
		$order_action = sanitize_text_field( $_REQUEST['order_action'] );

		// Read current status BEFORE updating to detect real transitions
		$prev_status = get_post_meta( $order_id, 'order_status', true );
		$has_status_changed = ( $prev_status !== $order_action );

		// Only update meta if status actually changed
		if ( $has_status_changed ) {
			update_post_meta( $order_id, 'order_status', $order_action );
			// If this update represents a cancellation, persist cancel reason (if provided)
			if ( in_array( strtolower( (string) $order_action ), array( 'canceled', 'cancelled' ), true ) ) {
				$cancel_reason = isset( $_REQUEST['cancel_reason'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['cancel_reason'] ) ) : '';
				$cancel_note = isset( $_REQUEST['cancel_reason_note'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['cancel_reason_note'] ) ) : '';
				if ( $cancel_reason !== '' ) update_post_meta( $order_id, 'order_cancel_reason', $cancel_reason );
				if ( $cancel_note !== '' ) update_post_meta( $order_id, 'order_cancel_reason_note', $cancel_note );

				// If refund requested via modal, attempt Mercado Pago refund for payment-integration orders
				$refund_requested = isset( $_REQUEST['refund_requested'] ) && in_array( (string) $_REQUEST['refund_requested'], array( '1', 'true', 'yes' ), true );
				if ( $refund_requested ) {
					$order_payment_type = get_post_meta( $order_id, 'order_payment_type', true );
					$payment_dataid = get_post_meta( $order_id, 'order_payment_dataid', true );
					if ( $order_payment_type === 'payment-integration' && $payment_dataid ) {
						$access_token = get_option( 'mercadopago_access_token', '' );
						if ( $access_token ) {
							$refund_url = 'https://api.mercadopago.com/v1/payments/' . rawurlencode( $payment_dataid ) . '/refunds';
							$args_refund = array(
								'headers' => array(
									'Authorization' => 'Bearer ' . $access_token,
									'Content-Type' => 'application/json',
									'X-Render-In-Process-Refunds' => 'true'
								),
								'body' => json_encode( (object) array() ),
								'timeout' => 20,
								'method' => 'POST'
							);
							$refund_resp = wp_remote_post( $refund_url, $args_refund );
							if ( is_wp_error( $refund_resp ) ) {
								$err = $refund_resp->get_error_message();
								if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
									error_log( 'MercadoPago refund WP error: ' . $err );
								}
								update_post_meta( $order_id, 'order_refund_error', $err );
								$refund_debug = array( 'error' => $err );
							} else {
								$http_code = wp_remote_retrieve_response_code( $refund_resp );
								$body = wp_remote_retrieve_body( $refund_resp );
								update_post_meta( $order_id, 'order_refund_response', $body );
								$refund_debug = array( 'http_code' => $http_code, 'body' => $body );
								if ( $http_code >= 200 && $http_code < 300 ) {
									$decoded = json_decode( $body, true );
									$refund_id = is_array( $decoded ) && isset( $decoded['id'] ) ? $decoded['id'] : ( is_array( $decoded ) && isset( $decoded['refund_id'] ) ? $decoded['refund_id'] : '' );
									$refund_status = is_array( $decoded ) && isset( $decoded['status'] ) ? $decoded['status'] : '';
									if ( $refund_id ) update_post_meta( $order_id, 'order_refund_id', sanitize_text_field( $refund_id ) );
									if ( $refund_status ) update_post_meta( $order_id, 'order_refund_status', sanitize_text_field( $refund_status ) );
									// If refund approved by provider, mark payment status as refunded
									try {
										if ( $refund_status && strtolower( (string) $refund_status ) === 'approved' ) {
											update_post_meta( $order_id, 'order_payment_status', 'refunded' );
										}
									} catch ( \Exception $e ) {
										// swallow exception to avoid breaking admin flow
									}
								} else {
									if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
										error_log( 'MercadoPago refund unexpected HTTP code: ' . $http_code . ' | Body: ' . $body );
									}
									update_post_meta( $order_id, 'order_refund_error', 'http_' . intval( $http_code ) );
								}
							}
						} else {
							update_post_meta( $order_id, 'order_refund_error', 'mercadopago_access_token_missing' );
						}
					} else {
						update_post_meta( $order_id, 'order_refund_error', 'order_not_eligible_for_refund' );
					}
				}

				// Send Evolution WhatsApp message to customer about cancellation (after refund processing)
				$api_url = get_option('evolution_api_url');
				$api_key = get_option('evolution_api_key');
				$instance = get_option('evolution_instance_name');
				$ddi = get_option('evolution_ddi', '55');
				$phone = get_post_meta( $order_id, 'customer_phone', true );
				if ( $api_url && $api_key && $instance && $phone ) {
					// Map reason codes to human labels
					$reason_map = array(
						'cliente_desistiu' => 'Cliente desistiu',
						'tempo_espera' => 'Tempo de espera muito longo',
						'item_indisponivel' => 'Item indisponível',
						'cliente_pediu_errado' => 'Cliente pediu item errado',
						'endereco_incorreto' => 'Endereço incorreto',
						'outro' => 'Outro',
					);
					$note = $cancel_note ?: '';
					// If the chosen reason is 'outro', use the provided note as the visible reason
					if ( strtolower( $cancel_reason ) === 'outro' ) {
						$reason_label = $note ?: ( $reason_map['outro'] ?? 'Outro' );
						$note = ''; // avoid duplicating as Observação
					} else {
						$reason_label = $reason_map[ $cancel_reason ] ?? $cancel_reason;
					}
					// build message
					$msg_text = '';
					$msg_text .= 'Pedido n° ' . $order_id . ' foi *CANCELADO* ❌' . "\n";
					$msg_text .= 'Motivo do cancelamento:' . "\n";
					$msg_text .= '> ' . $reason_label;
					if ( $note ) {
						$msg_text .= "\n" . 'Observação: ' . $note;
					}

					// Se o reembolso foi aprovado, adiciona mensagem informativa
					$order_refund_status = get_post_meta( $order_id, 'order_refund_status', true );
					$payment_method = get_post_meta( $order_id, 'order_payment_method', true );
					if ( $order_refund_status && strtolower( $order_refund_status ) === 'approved' ) {
						$msg_text .= "\n\n" . '💸 O reembolso do pedido foi realizado com sucesso. O valor será devolvido pelo mesmo método de pagamento.' . "\n" . "_Se o pagamento foi feito por cartão de crédito, o valor aparecerá na próxima fatura ou em até 2 faturas, conforme a data de fechamento do cartão._";
					}
					$phone_digits = preg_replace('/\D/', '', $phone);
					$full_number = '+' . $ddi . $phone_digits;
					$args = array(
						'headers' => array('Content-Type' => 'application/json', 'apikey' => $api_key),
						'body' => json_encode( array( 'number' => $full_number, 'text' => $msg_text ) ),
						'timeout' => 10,
					);
					$response = wp_remote_post( trailingslashit($api_url) . 'message/sendText/dwp-' . $instance, $args );
					if ( is_wp_error( $response ) ) {
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'Evolution API (cancel): Erro WP: ' . $response->get_error_message() );
						}
					} else {
						$code = wp_remote_retrieve_response_code( $response );
						if ( $code !== 201 && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'Evolution API (cancel): Código HTTP inesperado: ' . $code . ' | Resposta: ' . print_r( $response, true ) );
						}
					}
				}
			}
		}
		// SSE: salva info no cache SSE somente quando houver mudança real de status
		if ( $has_status_changed ) {
			$status_file = __DIR__ . '/sse-order-status-cache.json';
			$status_labels = [
				'new' => 'Novo',
				'confirmed' => 'Confirmado',
				'in-delivery' => 'Em Entrega',
				'finished' => 'Concluído',
				'done' => 'Concluído',
				'delivered' => 'Entregue',
				'cancelled' => 'Cancelado',
				'canceled' => 'Cancelado'
			];
			$status_colors = [
				'new' => '#d0ad02',
				'confirmed' => '#208e2a',
				'in-delivery' => '#037d91',
				'finished' => '#037d91',
				'done' => '#037d91',
				'delivered' => '#28a745',
				'cancelled' => '#dc3545',
				'canceled' => '#dc3545'
			];
			$order_data = [
				'order_id' => $order_id,
				'status' => $order_action,
				'status_label' => $status_labels[$order_action] ?? ucfirst($order_action),
				'status_color' => $status_colors[$order_action] ?? '#6c757d',
				'timestamp' => time()
			];
			file_put_contents($status_file, json_encode($order_data));

			// Quando status mudou para 'confirmed', creditar pontos de fidelidade quando aplicável
			if ( strtolower( (string) $order_action ) === 'confirmed' ) {
				$cid_for_points = get_post_meta( $order_id, 'myd_customer_id', true );
				if ( ! empty( $cid_for_points ) ) {
					$cid_for_points = (int) $cid_for_points;
					$already_added = get_post_meta( $order_id, 'order_loyalty_point_added', true );
					if ( empty( $already_added ) || (string) $already_added !== '1' ) {
						$ltipo_check = get_option( 'myd_fidelidade_tipo', 'loyalty_value' );
						$lpontos_check = intval( get_option( 'myd_fidelidade_pontos_necessarios', 0 ) );
						$lvalor_raw_check = get_option( 'myd_fidelidade_valor', '' );
						$parse_currency_local = function( $v ) {
							$v = str_replace( array( 'R$', ' ' ), '', $v );
							$v = str_replace( ',', '.', str_replace( '.', '', $v ) );
							return floatval( $v );
						};
						$target_check = 0.0;
						if ( ! empty( $lvalor_raw_check ) ) $target_check = $parse_currency_local( $lvalor_raw_check );
						$should_add = false;
						// obtém subtotal do pedido (prefer meta order_subtotal)
						$order_sub_raw = get_post_meta( $order_id, 'order_subtotal', true );
						if ( empty( $order_sub_raw ) ) $order_sub_raw = get_post_meta( $order_id, 'myd_order_subtotal', true );
						if ( ! empty( $order_sub_raw ) ) {
							$order_sub_num = floatval( str_replace( ',', '.', str_replace( '.', '', (string) $order_sub_raw ) ) );
						} else {
							$order_total_raw = get_post_meta( $order_id, 'order_total', true );
							if ( empty( $order_total_raw ) ) $order_total_raw = get_post_meta( $order_id, 'myd_order_total', true );
							$delivery_raw = get_post_meta( $order_id, 'order_delivery_price', true );
							$delivery_num = floatval( str_replace( ',', '.', str_replace( '.', '', (string) $delivery_raw ) ) );
							$order_total_num = floatval( str_replace( ',', '.', str_replace( '.', '', (string) $order_total_raw ) ) );
							$order_sub_num = max( 0, $order_total_num - $delivery_num );
						}
						if ( $ltipo_check === 'loyalty_value' ) {
							if ( $lpontos_check > 0 && $target_check > 0 ) {
								if ( $order_sub_num >= $target_check ) $should_add = true;
							}
						}
						if ( $should_add ) {
							// Expiration handling: if user had an expires_at and it's passed, reset points first
							$exp_opt = get_option( 'myd_fidelidade_expiracao', 'never' );
							$expires_at = get_user_meta( $cid_for_points, 'myd_loyalty_expires_at', true );
							$now_ts = (int) current_time( 'timestamp' );
							if ( $exp_opt !== 'never' && ! empty( $expires_at ) ) {
								$expires_ts = strtotime( $expires_at );
								if ( $expires_ts !== false && $expires_ts <= $now_ts ) {
									// points expired — reset to zero
									update_user_meta( $cid_for_points, 'myd_loyalty_points', 0 );
									$prev_points = 0;
								} else {
									$prev_points = intval( get_user_meta( $cid_for_points, 'myd_loyalty_points', true ) );
								}
							} else {
								$prev_points = intval( get_user_meta( $cid_for_points, 'myd_loyalty_points', true ) );
							}

							update_post_meta( $order_id, 'order_loyalty_points_prev', $prev_points );
							$new_points = $prev_points + 1;
							update_user_meta( $cid_for_points, 'myd_loyalty_points', $new_points );
							// update expires_at: every time a new point is registered, expiration window resets
							if ( $exp_opt !== 'never' ) {
								$days = intval( $exp_opt );
								if ( $days > 0 ) {
									$expires_ts = $now_ts + ( $days * DAY_IN_SECONDS );
									$expires_str = date( 'Y-m-d H:i:s', $expires_ts );
									update_user_meta( $cid_for_points, 'myd_loyalty_expires_at', $expires_str );
								}
							} else {
								// remove any expires_at if set
								delete_user_meta( $cid_for_points, 'myd_loyalty_expires_at' );
							}

							update_post_meta( $order_id, 'order_loyalty_point_added', '1' );
						}
					}
				}
			}
		}
		// Evolution API integration: enviar somente se houve mudança real para os status monitorados
		if ( $has_status_changed && in_array( $order_action, [ 'confirmed', 'in-delivery' ] ) ) {
			$api_url = get_option('evolution_api_url');
			$api_key = get_option('evolution_api_key');
			$instance = get_option('evolution_instance_name');
			$ddi = get_option('evolution_ddi', '55');
			$msg = $order_action === 'confirmed' ? get_option('evolution_msg_confirmed') : get_option('evolution_msg_delivery');
			$phone = get_post_meta( $order_id, 'customer_phone', true );
			// Preencher variáveis na mensagem
			$customer_name = get_post_meta( $order_id, 'order_customer_name', true );
			$first_customer_name = explode(' ', trim($customer_name))[0] ?? $customer_name;
			$order_number = $order_id;
			$order_products = get_post_meta( $order_id, 'order_products', true );
			$shipping_price = get_post_meta( $order_id, 'order_delivery_price', true );
			$order_total = get_post_meta( $order_id, 'order_total', true );
			$payment_method = get_post_meta( $order_id, 'order_payment_method', true );
			$payment_change = get_post_meta( $order_id, 'order_change', true );
			$customer_phone = $phone;
			$customer_address = get_post_meta( $order_id, 'order_address', true );
			$customer_address_number = get_post_meta( $order_id, 'order_address_number', true );
			$customer_address_complement = get_post_meta( $order_id, 'order_address_comp', true );
			$customer_address_neighborhood = get_post_meta( $order_id, 'order_neighborhood', true );
			$customer_address_zipcode = get_post_meta( $order_id, 'order_zipcode', true );
			$order_track_page = get_permalink( get_option( 'fdm-page-order-track' ) ) . '?hash=' . base64_encode( $order_id );
			$replace = array(
				'{customer_name}' => $customer_name,
				'{first_customer_name}' => $first_customer_name,
				'{order_number}' => $order_number,
				'{order_code}' => get_post_meta( $order_id, 'order_confirmation_code', true ),
				'{order_products}' => $order_products,
				'{shipping_price}' => $shipping_price,
				'{order_total}' => $order_total,
				'{payment_method}' => $payment_method,
				'{payment_change}' => $payment_change,
				'{customer_phone}' => $customer_phone,
				'{customer_address}' => $customer_address,
				'{customer_address_number}' => $customer_address_number,
				'{customer_address_complement}' => $customer_address_complement,
				'{customer_address_neighborhood}' => $customer_address_neighborhood,
				'{customer_address_zipcode}' => $customer_address_zipcode,
				'{order_track_page}' => $order_track_page,
				'{space}' => "\n"
			);
			$msg = strtr($msg, $replace);
			if ( $api_url && $api_key && $instance && $phone && $msg ) {
				// Remove tudo que não for número
				$phone_digits = preg_replace('/\D/', '', $phone);
				$full_number = '+' . $ddi . $phone_digits;
				$args = [
					'headers' => [
						'Content-Type' => 'application/json',
						'apikey' => $api_key
					],
					'body' => json_encode([
						'number' => $full_number,
						'text' => $msg
					]),
					'timeout' => 10
				];
				$response = wp_remote_post( trailingslashit($api_url) . 'message/sendText/dwp-' . $instance, $args );
				if ( is_wp_error( $response ) ) {
					if ( defined('WP_DEBUG') && WP_DEBUG ) {
						error_log( 'Evolution API: Erro WP: ' . $response->get_error_message() );
					}
				} else {
					$code = wp_remote_retrieve_response_code( $response );
					if ( $code !== 201 && defined('WP_DEBUG') && WP_DEBUG ) {
						error_log( 'Evolution API: Código HTTP inesperado: ' . $code . ' | Resposta: ' . print_r( $response, true ) );
					}
				}
			}
		}

		// Notificação WebSocket direta: garante envio ao servidor push para pedidos
		// SEM myd_customer_id (ex: pedidos manuais criados pelo painel).
		// O hook updated_post_meta em class-plugin.php já dispara para pedidos COM customer,
		// portanto aqui só chamamos quando customer está vazio (evita notificação duplicada).
		if ( $has_status_changed && class_exists('MydPro\\Includes\\Push\\Push_Notifier') ) {
			try {
				$customer_for_push = get_post_meta( $order_id, 'myd_customer_id', true );
				// Só notifica diretamente se NÃO houver customer — o hook do class-plugin.php
				// já cobre o caso com customer, evitando emissão duplicada do order.status
				if ( empty( $customer_for_push ) ) {
					\MydPro\Includes\Push\Push_Notifier::notify( '', $order_id, $order_action );
				}
			} catch ( \Exception $e ) {
				// silencia erros para não quebrar o fluxo
			}
		}

		if ( empty( $this->orders_object ) ) {
			/**
			 * Query orders
			 */
			$orders = new Myd_Store_Orders( $this->default_args );
			$orders = $orders->get_orders_object();
			$this->orders_object = $orders;
		}

		echo wp_json_encode( array(
			'loop' => $this->loop_orders_list(),
			'full' => $this->loop_orders_full(),
			'refund_debug' => $refund_debug,
		));

		exit;
	}

	/**
	 * Ajax to reload order after update (new order)
	 *
	 * @return void
	 */
	public function update_orders() {
		// Permission check: only allow logged in users with appropriate capability
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
			exit;
		}

		$nonce = $_REQUEST['nonce'] ?? null;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'myd-order-notification' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Ops! Security check failed.', 'my-delivey-wordpress' ) ) );
			exit;
		} else {
			if ( empty( $this->orders_object ) ) {
				/**
				 * Query orders
				 */
				$orders = new Myd_Store_Orders( $this->default_args );
				$orders = $orders->get_orders_object();
				$this->orders_object = $orders;
			}

			echo wp_json_encode( array(
				'loop' => $this->loop_orders_list(),
				'full' => $this->loop_orders_full(),
				'print' => $this->loop_print_order(),
			));

			exit;
		}
	}

	/**
	 * Ajax to get order details for printing
	 *
	 * @return void
	 */
	public function ajax_get_order_details() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
			return;
		}
		$nonce = $_REQUEST['nonce'] ?? null;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'myd-order-notification' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Ops! Security check failed.', 'my-delivey-wordpress' ) ) );
			return;
		}

		$order_id = sanitize_text_field( $_REQUEST['order_id'] ?? '' );
		if ( empty( $order_id ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Order ID is required.', 'my-delivey-wordpress' ) ) );
			return;
		}

		// Get order post
		$order = get_post( $order_id );
		if ( ! $order || $order->post_type !== 'mydelivery-orders' ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Order not found.', 'my-delivey-wordpress' ) ) );
			return;
		}

		// Get order metadata
		$order_data = array(
			'id' => $order->ID,
			'date' => get_post_meta( $order->ID, 'order_date', true ),
			'status' => get_post_meta( $order->ID, 'order_status', true ),
			'customer_name' => get_post_meta( $order->ID, 'order_customer_name', true ),
			'customer_note' => get_post_meta( $order->ID, 'order_customer_note', true ),
			'customer_phone' => get_post_meta( $order->ID, 'customer_phone', true ),
			'customer_email' => get_post_meta( $order->ID, 'customer_email', true ),
			'address' => get_post_meta( $order->ID, 'order_address', true ),
			'address_number' => get_post_meta( $order->ID, 'order_address_number', true ),
			'address_comp' => get_post_meta( $order->ID, 'order_address_comp', true ),
			'neighborhood' => get_post_meta( $order->ID, 'order_neighborhood', true ),
			'real_neighborhood' => get_post_meta( $order->ID, 'order_real_neighborhood', true ),
			'zipcode' => get_post_meta( $order->ID, 'order_zipcode', true ),
			'payment_method' => get_post_meta( $order->ID, 'order_payment_method', true ),
			'payment_change' => get_post_meta( $order->ID, 'order_change', true ),
			'payment_status' => get_post_meta( $order->ID, 'order_payment_status', true ),
			'delivery_price' => get_post_meta( $order->ID, 'order_delivery_price', true ),
			'subtotal' => get_post_meta( $order->ID, 'order_subtotal', true ),
			'total' => get_post_meta( $order->ID, 'order_total', true ),
			'notes' => get_post_meta( $order->ID, 'order_notes', true ),
			// Cupom e desconto do cupom (only include for administrators)
			'coupon_name' => current_user_can( 'manage_options' ) ? get_post_meta( $order->ID, 'order_coupon', true ) : '',
			'coupon_discount' => current_user_can( 'manage_options' ) ? get_post_meta( $order->ID, 'order_coupon_discount', true ) : '',
			'fidelity_discount' => get_post_meta( $order->ID, 'order_fidelity_discount', true ),
			'loyalty_redeemed' => get_post_meta( $order->ID, 'order_loyalty_redeemed', true ),
		);

		// Get order items (suporta JSON novo e serialize legado)
		$items = get_post_meta( $order->ID, 'myd_order_items', true );
		$items = self::parse_order_items( $items );
		$order_data['items'] = $items;
		// Adiciona o nome da loja
		$order_data['store_name'] = get_option('fdm-business-name');

		wp_send_json_success( $order_data );
	}

	/**
	 * Ajax: retorna URLs de imagem dos itens do pedido
	 * Espera 'order_id' e 'nonce' (myd-order-notification)
	 */
	public function ajax_get_order_images() {
		$this->ensure_clean_output_buffer();
		if ( ! is_user_logged_in() ) {
			echo wp_json_encode( array( 'success' => false, 'message' => 'Unauthorized' ) );
			exit;
		}
		$nonce = $_REQUEST['nonce'] ?? null;
		$order_id = isset( $_REQUEST['order_id'] ) ? intval( $_REQUEST['order_id'] ) : 0;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'myd-order-notification' ) ) {
			echo wp_json_encode( array( 'success' => false, 'message' => 'Security check failed' ) );
			exit;
		}
		if ( $order_id <= 0 ) {
			echo wp_json_encode( array( 'success' => false, 'message' => 'Invalid order id' ) );
			exit;
		}

		$items = self::parse_order_items( get_post_meta( $order_id, 'myd_order_items', true ) );
		$results = array();
		if ( is_array( $items ) ) {
			foreach ( $items as $it ) {
				$product_post_id = isset( $it['id'] ) ? (int) $it['id'] : 0;
				$image_url = '';
				if ( $product_post_id > 0 ) {
					$image_id = get_post_meta( $product_post_id, 'product_image', true );
					if ( $image_id ) {
						$image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
					}
				}
				$results[] = array(
					'product_id' => $product_post_id,
					'name' => $it['product_name'] ?? '',
					'image_url' => $image_url,
				);
			}
		}

		echo wp_json_encode( array( 'success' => true, 'items' => $results ) );
		exit;
	}

	/**
	 * Debug: retorna todos os post meta para um post_id (temporário)
	 * Uso: action=debug_post_meta, post_id, nonce=myd-order-notification
	 */
	public function ajax_debug_post_meta() {
		$this->ensure_clean_output_buffer();
		if ( ! is_user_logged_in() ) {
			echo wp_json_encode( array( 'success' => false, 'message' => 'Unauthorized' ) );
			exit;
		}
		$nonce = $_REQUEST['nonce'] ?? null;
		$post_id = isset( $_REQUEST['post_id'] ) ? intval( $_REQUEST['post_id'] ) : 0;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'myd-order-notification' ) ) {
			echo wp_json_encode( array( 'success' => false, 'message' => 'Security check failed' ) );
			exit;
		}
		if ( $post_id <= 0 ) {
			echo wp_json_encode( array( 'success' => false, 'message' => 'Invalid post_id' ) );
			exit;
		}

		$meta = get_post_meta( $post_id );
		// Post object
		$post_obj = get_post( $post_id );
		// featured image id
		$featured = get_post_thumbnail_id( $post_id );
		// attached media
		$attachments = get_attached_media( '', $post_id );
		// Sanitize meta for JSON
		$sanitized = array();
		foreach ( $meta as $k => $v ) {
			$sanitized[$k] = $v;
		}

		$attach_list = array();
		foreach ( $attachments as $a ) {
			$attach_list[] = array( 'ID' => $a->ID, 'post_mime_type' => $a->post_mime_type, 'guid' => $a->guid );
		}

		echo wp_json_encode( array( 'success' => true, 'post_id' => $post_id, 'post' => $post_obj, 'meta' => $sanitized, 'featured_id' => $featured, 'attachments' => $attach_list ) );
		exit;
	}

	/**
	 * SSE endpoint para notificações em tempo real
	 */
	public function ajax_sse_order_status() {
		if ( ! is_user_logged_in() ) {
			// For SSE, send a single error event and exit
			header('HTTP/1.1 403 Forbidden');
			echo "event: error\n";
			echo "data: unauthorized\n\n";
			exit;
		}
		// Headers para SSE
		header('Content-Type: text/event-stream');
		header('Cache-Control: no-cache');
		header('Connection: keep-alive');
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Headers: Cache-Control');

		// Desabilitar output buffering
		if (ob_get_level()) {
			ob_end_clean();
		}

		$status_file = __DIR__ . '/sse-order-status-cache.json';
		$last_status = [];

		while (true) {
			clearstatcache();

			if (file_exists($status_file)) {
				$json = file_get_contents($status_file);
				$current_status = json_decode($json, true);

				if ($current_status && $current_status !== $last_status) {
					// Envia evento SSE para mudança de status geral
					echo "event: status_changed\n";
					echo 'data: ' . json_encode($current_status) . "\n\n";

					// Se for confirmado, também envia evento específico
					if ($current_status['status'] === 'confirmed') {
						echo "event: confirmed\n";
						echo 'data: ' . json_encode($current_status) . "\n\n";
					}
					// Se for finalizado, envia evento específico
					if ($current_status['status'] === 'finished' || $current_status['status'] === 'done') {
						echo "event: finished\n";
						echo 'data: ' . json_encode($current_status) . "\n\n";
					}

					if ( ob_get_level() > 0 ) {
						ob_flush();
					}
					flush();
					$last_status = $current_status;
				}
			}

			sleep(2); // Checa a cada 2 segundos
		}
	}

	/**
	 * AJAX: verificação leve de IDs de pedidos para fallback polling
	 * Retorna apenas os IDs e status dos pedidos atuais, sem HTML.
	 */
	public function ajax_check_new_orders() {
		$this->ensure_clean_output_buffer();

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
			exit;
		}

		$nonce = $_REQUEST['nonce'] ?? null;
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'myd-order-notification' ) ) {
			wp_send_json_error( array( 'message' => 'Security validation failed.' ) );
			exit;
		}

		// Buscar apenas pedidos do dia atual
		$today = current_time( 'Y-m-d' );
		$query = new \WP_Query( array(
			'post_type'      => 'mydelivery-orders',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'date_query'     => array(
				array(
					'after'     => $today . ' 00:00:00',
					'inclusive' => true,
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		$order_ids = array();
		$order_statuses = array();

		// Status terminais: pedidos com esses status não aparecem no kanban ativo
		// e não devem ser retornados para o polling, pois causariam inserções duplicadas
		$terminal_statuses = array( 'done', 'finished', 'canceled', 'cancelled', 'delivered', 'refunded', 'completed' );

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $pid ) {
				$status = get_post_meta( $pid, 'order_status', true );
				// Excluir pedidos com status terminal — eles não estão no kanban ativo
				if ( in_array( strtolower( (string) $status ), $terminal_statuses, true ) ) {
					continue;
				}
				$order_ids[] = (string) $pid;
				$order_statuses[ (string) $pid ] = $status;
			}
		}

		wp_send_json_success( array(
			'order_ids'     => $order_ids,
			'order_statuses' => $order_statuses,
		) );
	}

	/**
	 * AJAX: Editar dados do cliente em um pedido existente
	 */
	public function ajax_edit_order_customer() {
		$this->ensure_clean_output_buffer();

		// Verificar permissões
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permissão negada' ), 403 );
		}

		// Verificar nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'myd_edit_order_customer' ) ) {
			wp_send_json_error( array( 'message' => 'Nonce inválido' ), 403 );
		}

		$order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
		if ( $order_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'ID do pedido inválido' ) );
		}

		// Verificar se o pedido existe
		$order = get_post( $order_id );
		if ( ! $order || $order->post_type !== 'mydelivery-orders' ) {
			wp_send_json_error( array( 'message' => 'Pedido não encontrado' ) );
		}

		// Sanitizar e atualizar os dados
		$customer_name = isset( $_POST['customer_name'] ) ? sanitize_text_field( $_POST['customer_name'] ) : '';
		$customer_phone = isset( $_POST['customer_phone'] ) ? sanitize_text_field( $_POST['customer_phone'] ) : '';
		$address = isset( $_POST['address'] ) ? sanitize_text_field( $_POST['address'] ) : '';
		$address_number = isset( $_POST['address_number'] ) ? sanitize_text_field( $_POST['address_number'] ) : '';
		$address_comp = isset( $_POST['address_comp'] ) ? sanitize_text_field( $_POST['address_comp'] ) : '';
		$neighborhood = isset( $_POST['neighborhood'] ) ? sanitize_text_field( $_POST['neighborhood'] ) : '';
		$reference = isset( $_POST['reference'] ) ? sanitize_text_field( $_POST['reference'] ) : '';

		// Atualizar meta dados
		if ( $customer_name !== '' ) {
			update_post_meta( $order_id, 'order_customer_name', $customer_name );
			update_post_meta( $order_id, 'myd_order_customer_name', $customer_name );
		}
		if ( $customer_phone !== '' ) {
			update_post_meta( $order_id, 'customer_phone', $customer_phone );
		}
		if ( $address !== '' ) {
			update_post_meta( $order_id, 'order_address', $address );
		}
		update_post_meta( $order_id, 'order_address_number', $address_number );
		update_post_meta( $order_id, 'order_address_comp', $address_comp );
		update_post_meta( $order_id, 'order_neighborhood', $neighborhood );
		update_post_meta( $order_id, 'order_address_reference', $reference );

		wp_send_json_success( array(
			'message' => 'Dados atualizados com sucesso',
			'data' => array(
				'customer_name' => $customer_name,
				'customer_phone' => $customer_phone,
				'address' => $address,
				'address_number' => $address_number,
				'address_comp' => $address_comp,
				'neighborhood' => $neighborhood,
				'reference' => $reference,
			)
		) );
	}
}

new Myd_Orders_Front_Panel();
