<?php
/**
 * NOVO
 */

namespace MydPro\Includes;

use MydPro\Includes\Store_Data;
use MydPro\Includes\Admin\Settings;
use MydPro\Includes\Admin\Custom_Posts;
use MydPro\Includes\Admin\Admin_Page;
use MydPro\Includes\License\License;
use MydPro\Includes\Plugin_Update\Plugin_Update;
use MydPro\Includes\Custom_Fields\Myd_Custom_Fields;
use MydPro\Includes\Custom_Fields\Register_Custom_Fields;
use MydPro\Includes\Ajax\Update_Cart;
use MydPro\Includes\Ajax\Create_Draft_Order;
use MydPro\Includes\Ajax\Place_Payment;
use MydPro\Includes\Custom_Login;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'myd_user_is_allowed_admin' ) ) {
	/**
	 * Central permission helper.
	 * Returns true if the provided user (or current user) is considered an admin for MyD operations.
	 * Default allowed roles: administrator, contributor, gestor_de_trafego. Can be filtered via 'myd_allowed_roles'.
	 *
	 * @param null|WP_User|int $user
	 * @return bool
	 */
	function myd_user_is_allowed_admin( $user = null ) {
		if ( is_null( $user ) ) {
			if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) return false;
			$user = wp_get_current_user();
		} elseif ( is_numeric( $user ) ) {
			$user = get_user_by( 'id', intval( $user ) );
		}
		if ( ! $user || empty( $user->roles ) ) return false;
		$roles = (array) $user->roles;
		$allowed = apply_filters( 'myd_allowed_roles', array( 'administrator' ) );
		foreach ( (array) $allowed as $r ) {
			if ( in_array( $r, $roles, true ) ) return true;
		}
		return false;
	}
}

if ( ! function_exists( 'myd_check_ip_rate_limit' ) ) {
	/**
	 * Rate limiter simples baseado em IP usando transient config do WordPress.
	 *
	 * @param string $action_name  Name of the action to rate limit.
	 * @param int    $max_requests Maximum requests allowed.
	 * @param int    $time_window  Time window in seconds.
	 * @return bool True if rate limit is exceeded (should block), False otherwise.
	 */
	function myd_check_ip_rate_limit( $action_name, $max_requests = 10, $time_window = 60 ) {
		$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		if ( $ip === 'unknown' ) {
			return false;
		}
		$transient_name = 'myd_rl_' . md5( $action_name . '_' . $ip );
		$current_requests = get_transient( $transient_name );

		if ( false === $current_requests ) {
			set_transient( $transient_name, 1, $time_window );
			return false;
		}

		if ( (int) $current_requests >= $max_requests ) {
			return true; // Blocked
		}

		// Hack: Transient expiration is not extended on update, and update_option clears cache differently.
		// For simplicity, we just increase the counter. Note: This resets the TTL if not careful, 
		// but WP's set_transient will overwrite the existing TTL. Let's just track it simply.
		$timeout = get_option( '_transient_timeout_' . $transient_name );
		$time_remaining = $timeout ? (int) $timeout - time() : $time_window;
		$time_remaining = max( 1, $time_remaining ); // at least 1 second

		set_transient( $transient_name, (int) $current_requests + 1, $time_remaining );
		return false;
	}
}


/**
 * Plugin main class
 *
 * @since 1.9.6
 */
final class Plugin {

	/**
	 * Store data
	 *
	 * @since 1.9.6
	 *
	 * TODO: change to protected and create method to get
	 */
	public $store_data;

	/**
	 * License
	 *
	 * @since 1.9.6
	 *
	 * TODO: change to protected and create method to get
	 */
	public $license;

	/**
	 * License
	 *
	 * @since 1.9.6
	 */
	protected $admin_settings;

	/**
	 * Custom Posts
	 *
	 * @since 1.9.6
	 */
	protected $custom_posts;

	/**
	 * Admin menu pages
	 */
	protected $admin_menu_pages;

	/**
	 * Instance
	 *
	 * @since 1.9.4
	 *
	 * @access private
	 * @static
	 */
	private static $_instance = null;

	/**
	 * Instance
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.9.4
	 *
	 * @access public
	 * @static
	 *
	 * @return Plugin An instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Envia mensagem Evolution quando um pedido muda de rascunho para publicado
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param WP_Post|int $post
	 * @return void
	 */
	public function maybe_send_evolution_on_publish_transition( $new_status, $old_status, $post ) {
		if ( empty( $old_status ) || empty( $new_status ) ) return;
		// only when transitioning draft -> publish
		if ( $old_status !== 'draft' || $new_status !== 'publish' ) return;
		$post_id = is_object( $post ) ? intval( $post->ID ) : intval( $post );
		if ( ! $post_id ) return;
		if ( get_post_type( $post_id ) !== 'mydelivery-orders' ) return;
		try {
			$this->maybe_send_evolution_on_front_create( array( 'id' => $post_id ) );
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Evolution API (publish transition): ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Send Evolution WhatsApp message when frontend order is created
	 *
	 * @param array $payload
	 * @return void
	 */
	public function maybe_send_evolution_on_front_create( $payload ) {
		if ( ! is_array( $payload ) || empty( $payload['id'] ) ) {
			return;
		}
		$order_id = intval( $payload['id'] );
		$msg = get_option( 'evolution_msg_confirmed_title', '' );
		// Support admin saving messages with literal "\n" sequences — convert to real newlines
		if ( strpos( $msg, '\\n' ) !== false ) {
			$msg = str_replace('\\n', "\n", $msg);
		}
		if ( empty( $msg ) ) return;

		$api_url = get_option( 'evolution_api_url' );
		$api_key = get_option( 'evolution_api_key' );
		$instance = get_option( 'evolution_instance_name' );
		$ddi = get_option( 'evolution_ddi', '55' );

		$phone = get_post_meta( $order_id, 'customer_phone', true );
		// fallback to other possible meta keys
		if ( empty( $phone ) ) {
			$phone = get_post_meta( $order_id, 'order_customer_phone', true );
		}

		$customer_name = get_post_meta( $order_id, 'order_customer_name', true );
		$first_customer_name = explode(' ', trim($customer_name))[0] ?? $customer_name;
		$order_number = $order_id;
		$order_products = get_post_meta( $order_id, 'order_products', true );
		// Fallback: if legacy 'order_products' is empty, try formatted 'myd_order_items'
		if ( empty( $order_products ) ) {
			$items = \MydPro\Includes\Myd_Orders_Front_Panel::parse_order_items( get_post_meta( $order_id, 'myd_order_items', true ) );
			if ( ! empty( $items ) ) {
				$lines = array();
				foreach ( $items as $it ) {
					$qty = isset( $it['quantity'] ) ? intval( $it['quantity'] ) : 0;
					// Prefer explicit 'name' field; otherwise strip leading quantity from 'product_name'
					if ( ! empty( $it['name'] ) ) {
						$name_raw = $it['name'];
					} else {
						$pn = isset( $it['product_name'] ) ? $it['product_name'] : '';
						$name_raw = preg_replace( '/^\s*\d+\s*x\s*/i', '', (string) $pn );
					}
					// Decode HTML entities (ex: &#8211;) and strip tags
					$name = html_entity_decode( strip_tags( (string) $name_raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					$name = trim( $name );
					$extras = isset( $it['product_extras'] ) ? $it['product_extras'] : '';
					$line = trim( ( $qty ? $qty . 'x ' : '' ) . $name );
					if ( $extras ) {
						$extras_trim = trim( (string) $extras );
						// Decode entities and strip tags in extras
						$extras_trim = html_entity_decode( strip_tags( $extras_trim ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
						// indent extras lines with 3 spaces
						$extras_indented = preg_replace( '/^/m', '   ', $extras_trim );
						$line .= "\n" . $extras_indented;
					}
					$lines[] = $line;
				}
				$order_products = implode("\n", $lines);
			}
		}
		$shipping_price = get_post_meta( $order_id, 'order_delivery_price', true );
		$order_total = get_post_meta( $order_id, 'order_total', true );
		$payment_method = get_post_meta( $order_id, 'order_payment_method', true );
		// Map common payment method codes to human-readable labels for evolution messages
		$payment_method_labels = array(
			'CRD' => __( 'Crédito', 'myd-delivery-pro' ),
			'DEB' => __( 'Débito', 'myd-delivery-pro' ),
			'VRF' => __( 'Vale-refeição', 'myd-delivery-pro' ),
			'DIN' => __( 'Dinheiro', 'myd-delivery-pro' ),
			'PIX' => 'Pix',
			'pix' => 'Pix',
		);
		$payment_method_label = isset( $payment_method_labels[ $payment_method ] ) ? $payment_method_labels[ $payment_method ] : $payment_method;
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
			'{payment_method}' => $payment_method_label,
			'{payment_change}' => $payment_change,
			'{customer_phone}' => $customer_phone,
			'{customer_address}' => $customer_address,
			'{customer_address_number}' => $customer_address_number,
			'{customer_address_complement}' => $customer_address_complement,
			'{customer_address_neighborhood}' => $customer_address_neighborhood,
			'{customer_address_zipcode}' => $customer_address_zipcode,
			'{order_track_page}' => $order_track_page,
			'{space}' => "\n",
		);

		$msg = strtr( $msg, $replace );

		if ( $api_url && $api_key && $instance && $phone && $msg ) {
			$phone_digits = preg_replace( '/\D/', '', $phone );
			$full_number = '+' . $ddi . $phone_digits;

			$btn_enabled = get_option( 'evolution_btn_enabled', '' );

			if ( $btn_enabled === 'on' ) {
				// Build sendButtons payload
				$btn_title = strtr( get_option( 'evolution_btn_title', '' ), $replace );
				$btn_footer = strtr( get_option( 'evolution_btn_footer', '' ), $replace );
				$btn_desc = get_option( 'evolution_btn_description', '' );
				$btn_desc = ! empty( $btn_desc ) ? strtr( $btn_desc, $replace ) : $msg;
				$btn_delay = intval( get_option( 'evolution_btn_delay', 0 ) );

				// Single URL button with order tracking page
				$btn_text = get_option( 'evolution_btn_display_text', '' );
				$btn_text = ! empty( $btn_text ) ? strtr( $btn_text, $replace ) : '📦 Acompanhar Pedido';
				$buttons = array(
					array(
						'type'        => 'url',
						'displayText' => $btn_text,
						'url'         => $order_track_page,
					),
				);

				$body_data = array(
					'number'      => $full_number,
					'title'       => $btn_title,
					'description' => $btn_desc,
					'footer'      => $btn_footer,
					'buttons'     => $buttons,
				);
				if ( $btn_delay > 0 ) {
					$body_data['delay'] = $btn_delay;
				}

				$args = array(
					'headers' => array( 'Content-Type' => 'application/json', 'apikey' => $api_key ),
					'body'    => json_encode( $body_data ),
					'timeout' => 15,
				);
				$response = wp_remote_post( trailingslashit( $api_url ) . 'message/sendButtons/dwp-' . $instance, $args );
			} else {
				// Plain text fallback
				$args = array(
					'headers' => array( 'Content-Type' => 'application/json', 'apikey' => $api_key ),
					'body'    => json_encode( array( 'number' => $full_number, 'text' => $msg ) ),
					'timeout' => 10,
				);
				$response = wp_remote_post( trailingslashit( $api_url ) . 'message/sendText/dwp-' . $instance, $args );
			}

			if ( is_wp_error( $response ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Evolution API (order create): Erro WP: ' . $response->get_error_message() );
				}
			} else {
				$code = wp_remote_retrieve_response_code( $response );
				if ( $code !== 201 && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Evolution API (order create): Código HTTP inesperado: ' . $code . ' | Resposta: ' . print_r( $response, true ) );
				}
			}
		}
	}

	/**
	 * Processa redirects SSO quando o link efêmero é acessado no navegador externo.
	 * URL esperado: ?myd_sso_code=CODE
	 */
	public function maybe_handle_sso_redirect() {
		if ( empty( $_GET['myd_sso_code'] ) ) return;
		$code = call_user_func('sanitize_text_field', call_user_func('wp_unslash', $_GET['myd_sso_code']));
		$transient_key = 'myd_sso_code_' . $code;
		// Recuperar user_id salvo no transient
		$user_id = call_user_func('get_transient', $transient_key);
		if ( ! $user_id ) {
			// Não encontrado ou expirado — redirecionar para a página de login
			call_user_func('wp_safe_redirect', call_user_func('wp_login_url'));
			exit;
		}
		// Criar cookie de autenticação do WordPress para o usuário e remover o transient
		call_user_func('wp_set_auth_cookie', intval($user_id));
		call_user_func('delete_transient', $transient_key);
		// Redirecionar para área administrativa /wp-admin ou root dependendo do contexto
		call_user_func('wp_safe_redirect', call_user_func('admin_url'));
		exit;
	}

	/**
	 * Disable class cloning and throw an error on object clone.
	 *
	 * @access public
	 * @since 1.9.6
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'myd-delivery-pro' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @access public
	 * @since 1.9.6
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'myd-delivery-pro' ), '1.0' );
	}

	/**
	 * Construct class
	 *
	 * @since 1.2
	 * @return void
	 */
	private function __construct() {
		\do_action( 'myd_delivery_pro_init' );
		\add_action( 'init', [ $this, 'init' ] );
		\register_activation_hook( MYD_PLUGIN_MAIN_FILE, [ $this, 'activation' ] );
		\register_deactivation_hook( MYD_PLUGIN_MAIN_FILE, [ $this, 'deactivation' ] );
	}

	/**
	 * Init plugin
	 *
	 * @since 1.9.4
	 */
	public function init() {
		/**
		 * Check and solve plugin path name
		 */
		$this->check_plugin_path();

		/**
		 * Check if old version of plugin is active
		 */
		if ( $this->plugin_is_active( 'my-delivey-wordpress/my-delivey-wordpress.php' ) || $this->plugin_is_active( 'my-delivery-wordpress/my-delivery-wordpress.php' ) ) {

			$error_message = sprintf(
				esc_html__( '%1$s requires MyDelivery WordPress (our old version) to be deactivated.', 'myd-delivery-pro' ),
				'<strong>MyD Delivery Pro</strong>'
			);

			add_action( 'admin_notices', function( $message ) use ( $error_message ) {
					printf( '<div class="notice notice-error"><p>%1$s</p></div>', $error_message );
				}
			);
			return;
		}

		/**
		 * Required files (load classes)
		 */
		$this->set_required_files();

		// Garantir que a capability específica exista para os papéis administrativos
		// Executa de forma idempotente para evitar que o usuário perca acesso sem reativar o plugin
		if ( function_exists( 'get_role' ) ) {
			$roles = array( 'administrator', 'shop_manager' );
			foreach ( $roles as $r ) {
				$role = get_role( $r );
				if ( $role && ! $role->has_cap( 'myd_view_reports' ) ) {
					$role->add_cap( 'myd_view_reports' );
				}
			}
		}

			// Handler para processar SSO via código efêmero (visitado pelo navegador externo)
			call_user_func('add_action', 'template_redirect', array($this, 'maybe_handle_sso_redirect'), 1);
		\load_plugin_textdomain( 'myd-delivery-pro', false, MYD_PLUGIN_DIRNAME . '/languages' );

		// Add custom cron schedule for every minute checks
		add_filter('cron_schedules', function($schedules){
			if (!isset($schedules['every_minute'])) {
				$schedules['every_minute'] = array('interval' => 60, 'display' => 'Every Minute');
			}
			return $schedules;
		});

		// Hook to perform periodic store open/close checks
		add_action('myd_check_store_open', [ $this, 'check_store_open_schedule' ]);

		// Schedule loyalty expiration cleanup (daily) and register handler
		if ( ! call_user_func('wp_next_scheduled', 'myd_loyalty_expire_cleanup') ) {
			call_user_func('wp_schedule_event', time(), 'every_minute', 'myd_loyalty_expire_cleanup');
		}
		add_action( 'myd_loyalty_expire_cleanup', [ $this, 'cleanup_expired_loyalty_points' ] );

		// Schedule iFood token refresh check (every minute)
		if ( ! call_user_func('wp_next_scheduled', 'myd_ifood_token_refresh_check') ) {
			call_user_func('wp_schedule_event', time(), 'every_minute', 'myd_ifood_token_refresh_check');
		}

		new Update_Cart();
		new Create_Draft_Order();
		new Place_Payment();
		new Mercadopago_Webhook_Handler();
		
		// Initialize Customer Authentication System
		new Customer_Authentication();

		// Customiza a tela de login nativa do WP
		new Custom_Login();

			// Hook para restaurar/ajustar pontos quando o status do pedido muda (ex: cancelado)
			add_action( 'updated_post_meta', array( $this, 'maybe_restore_loyalty_on_status_change' ), 10, 4 );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frondend_scripts' ] );

		$this->license = new License();

		   if ( is_admin() ) {
			   $this->admin_settings = new Settings();
			   add_action( 'admin_init', [ $this->admin_settings, 'register_settings' ] );
			   // Inclui handler de teste SMTP
			   require_once(MYD_PLUGIN_PATH . '/includes/smtp-test-handler.php');
			   // Inclui handler de recuperação de senha
			   require_once(MYD_PLUGIN_PATH . '/includes/forgot-password-handler.php');
			   // Inclui handler de alteração de senha do perfil
			   require_once(MYD_PLUGIN_PATH . '/includes/myd-update-customer-password-handler.php');
			   // Inclui handler de validação de código de redefinição
			   require_once(MYD_PLUGIN_PATH . '/includes/validate-reset-code-handler.php');
			   // Inclui handler de autenticação iFood
			   require_once(MYD_PLUGIN_PATH . '/includes/admin/ifood-auth-handler.php');

			   $this->admin_menu_pages = new Admin_Page();
			
			// Initialize Customers Manager for admin
			new \MydPro\Includes\Admin\Customers_Manager();
			add_action( 'admin_menu', [ $this->admin_menu_pages, 'add_admin_pages' ] );
		}

		$this->custom_posts = new Custom_Posts();
		$this->custom_posts->register_custom_posts();

		Store_Data::set_store_data();
		$this->store_data = Store_Data::get_store_data();

		// Send Evolution WhatsApp message when an order post is published (draft -> publish)
		add_action( 'transition_post_status', [ $this, 'maybe_send_evolution_on_publish_transition' ], 20, 3 );

		/**
		 * Plugin update checker (temporarily disabled; re-enable by uncommenting below).
		 */
		// $plugin_update = new Plugin_Update();
		// add_filter( 'plugins_api', array( $plugin_update, 'info' ), 20, 3 );
		// add_filter( 'pre_set_site_transient_update_plugins', array( $plugin_update, 'update' ) );

		/**
		 * TODO: Move to license class
		 */
		add_action( 'in_plugin_update_message-myd-delivery-pro/myd-delivery-pro.php', [ $this, 'update_notice_invalid_license' ], 10, 2 );

		if ( is_admin() ) {
			new Myd_Custom_Fields( Register_Custom_Fields::get_registered_fields() );
		}

		// Auto-generate unique 8-digit order locator when saving orders
		add_action( 'save_post_mydelivery-orders', function( $post_id, $post, $update ) {
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
			if ( wp_is_post_revision( $post_id ) ) return;
			// Only run on publish to avoid generating random codes on drafts
			if ( empty( $post ) || ! isset( $post->post_status ) || $post->post_status !== 'publish' ) return;
			$locator = get_post_meta( $post_id, 'order_locator', true );
			if ( ! empty( $locator ) ) return;
			$attempts = 0; $max = 30; $candidate = null;
			while ( $attempts < $max ) {
				try { $num = random_int(0, 99999999); } catch ( \Throwable $e ) { $num = mt_rand(0, 99999999); }
				$candidate = str_pad( (string) $num, 8, '0', STR_PAD_LEFT );
				$exists = get_posts( array(
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
				) );
				if ( empty( $exists ) ) break;
				$attempts++;
			}
			if ( $candidate ) {
				update_post_meta( $post_id, 'order_locator', $candidate );
			}
		}, 20, 3 );

		// Salva código de confirmação do usuário no pedido, se existir; senão gera aleatório
		add_action( 'save_post_mydelivery-orders', function( $post_id, $post, $update ) {
			if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
			if ( wp_is_post_revision( $post_id ) ) return;
			$code = \get_post_meta( $post_id, 'order_confirmation_code', true );
			if ( empty( $code ) ) {
				// Tenta obter o código do usuário vinculado ao pedido
				$user_id = \get_post_meta( $post_id, 'myd_customer_id', true );
				if ( $user_id ) {
					$user_code = \get_user_meta( $user_id, 'myd_delivery_confirm_code', true );
					if ( ! empty( $user_code ) ) {
						// Salva em ambos os meta_keys para máxima compatibilidade
						\update_post_meta( $post_id, 'order_confirmation_code', $user_code );
						\update_post_meta( $post_id, 'myd_order_confirmation_code', $user_code );
						return;
					}
				}
				// Fallback: gera aleatório
				try { $num = random_int(0, 9999); } catch ( \Throwable $e ) { $num = mt_rand(0, 9999); }
				$code = str_pad( (string) $num, 4, '0', STR_PAD_LEFT );
				\update_post_meta( $post_id, 'order_confirmation_code', $code );
				\update_post_meta( $post_id, 'myd_order_confirmation_code', $code );
			}
		}, 25, 3 );

		// Agendar hook para limpeza de tokens
		add_action('myd_cleanup_expired_tokens', array($this, 'cleanup_expired_tokens'));
			// notify push server when order_status meta changes
			// Deduplicação: evita múltiplas notificações para o mesmo (order_id, status) na mesma requisição.
			// O webhook do MercadoPago chama update_post_meta('order_status') várias vezes seguidas,
			// o que dispararia o hook múltiplas vezes para o mesmo pedido.
			add_action( 'updated_post_meta', function($meta_id, $object_id, $meta_key, $_meta_value) {
				if ($meta_key !== 'order_status') return;
				if ( get_post_type($object_id) !== 'mydelivery-orders' ) return;
				$customer = get_post_meta($object_id, 'myd_customer_id', true);
				if (!$customer) return;

				// Guard de deduplicação: só notifica uma vez por (order_id + status) por requisição
				static $notified = array();
				$dedup_key = $object_id . '_' . $_meta_value;
				if ( isset( $notified[ $dedup_key ] ) ) return;
				$notified[ $dedup_key ] = true;

				if ( class_exists('MydPro\\Includes\\Push\\Push_Notifier') ) {
					try { \MydPro\Includes\Push\Push_Notifier::notify( $customer, $object_id, $_meta_value ); } catch(\Exception $e) {}
				}

				// Se o pedido foi cancelado e havia resgate de fidelidade, restaura estado anterior
				if ( $_meta_value === 'canceled' ) {
					$redeemed = get_post_meta( $object_id, 'order_loyalty_redeemed', true );
					if ( ! empty( $redeemed ) && (string) $redeemed === '1' ) {
						$cid = $customer;
						$prev = get_post_meta( $object_id, 'order_loyalty_reset_prev', true );
						if ( ! empty( $cid ) ) {
							if ( ! empty( $prev ) ) {
								update_user_meta( (int) $cid, 'myd_loyalty_reset_at', $prev );
							} else {
								delete_user_meta( (int) $cid, 'myd_loyalty_reset_at' );
							}
							// marcar que este pedido não mais resgatou
							update_post_meta( $object_id, 'order_loyalty_redeemed', '0' );
						}
					}
				}

				// Se o status foi alterado para 'confirmed' e é pedido iFood, notifica o backend
				if ( $_meta_value === 'confirmed' ) {
					$channel = get_post_meta( $object_id, 'order_channel', true );
					if ( $channel === 'IFD' ) {
						$ifood_order_id  = get_post_meta( $object_id, 'ifood_order_id', true );
						$backend_url     = get_option( 'ifood_backend_url', '' );
						$backend_secret  = get_option( 'ifood_backend_secret', '' );
						$wp_api_secret   = get_option( 'ifood_wp_api_secret', '' );

						if ( ! empty( $ifood_order_id ) && ! empty( $backend_url ) ) {
							$url = rtrim( $backend_url, '/' ) . '/ifood/confirm';
							$response = wp_remote_post( $url, [
								'headers' => [ 'Content-Type' => 'application/json' ],
								'body'    => wp_json_encode([
									'ifood_order_id' => $ifood_order_id,
									'backend_secret' => $backend_secret,
									'wp_api_secret'  => $wp_api_secret,
								]),
								'timeout' => 15,
							]);
							if ( is_wp_error( $response ) ) {
								error_log( '[MYD][iFood] Failed to send confirm to backend: ' . $response->get_error_message() );
							} else {
								error_log( '[MYD][iFood] Confirm sent to backend for order ' . $object_id . ' (iFood: ' . $ifood_order_id . ')' );
							}
						}
					}
				}

			}, 10, 4 );

			// Notify push server when store open/close settings change
			add_action( 'updated_option', function( $option_name, $old_value, $value ) {
				if ( ! class_exists('MydPro\\Includes\\Push\\Push_Notifier') ) return;

				// Force open/close option changed
				if ( $option_name === 'myd-delivery-force-open-close-store' ) {
					$open = ($value === 'open');
					try { \MydPro\Includes\Push\Push_Notifier::notify_store( $open ); } catch(\Exception $e) {}
					return;
				}

				// Opening hours changed
				if ( $option_name === 'myd-delivery-time' ) {
					// Recompute store open state and notify
					$open = \MydPro\Includes\Store_Data::is_store_open();
					try { \MydPro\Includes\Push\Push_Notifier::notify_store( $open ); } catch(\Exception $e) {}
					return;
				}
			}, 10, 3 );

		// Register JWT session endpoint
		add_action('rest_api_init', function () {
			// Endpoint de geração de token JWT (substitui o plugin JWT Authentication)
			register_rest_route('jwt-auth/v1', '/token', array(
				'methods' => 'POST',
				'callback' => function($request) {
					$username = $request->get_param('username');
					$password = $request->get_param('password');

					if (empty($username) || empty($password)) {
						return new \WP_Error('missing_credentials', 'Username and password are required', array('status' => 400));
					}

					$user = \wp_authenticate($username, $password);
					if (\is_wp_error($user)) {
						return new \WP_Error('invalid_credentials', 'Invalid credentials', array('status' => 403));
					}

					// Usar a mesma chave secreta do plugin JWT ou nossa própria
					$secret_key = \get_option('jwt_auth_secret_key');
					if ( ! $secret_key ) {
						$secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : '';
					}
					if ( ! $secret_key ) {
						// Gera e persiste um secret robusto — nunca usa fallback previsível
						// wp_generate_password($length, $special_chars, $extra_special_chars)
						$secret_key = \wp_generate_password( 64, true, true );
						\update_option( 'jwt_auth_secret_key', $secret_key );
					}

					$issuedAt = time();
					$notBefore = $issuedAt;
					$expire = $issuedAt + (86400 * 7); // 7 dias padrão

					$token_data = array(
						'iss' => \get_bloginfo('url'),
						'iat' => $issuedAt,
						'nbf' => $notBefore,
						'exp' => $expire,
						'data' => array(
							'user' => array(
								'id' => $user->ID,
							)
						)
					);

					// Gerar JWT
					if (class_exists('JWT')) {
						$jwt = \JWT::encode($token_data, $secret_key, 'HS256');
					} else {
						// Codificação manual
						$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
						$payload = json_encode($token_data);
						$headerEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
						$payloadEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
						$signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $secret_key, true);
						$signatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
						$jwt = $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
					}

					return array(
						'token' => $jwt,
						'user_email' => $user->user_email,
						'user_nicename' => $user->user_nicename,
						'user_display_name' => $user->display_name,
					);
				},
				'permission_callback' => '__return_true',
			));

			// Endpoint para listar tokens ativos (para admin)
			register_rest_route('myd-delivery/v1', '/tokens', array(
				'methods' => 'GET',
				'callback' => function($request) {
					global $wpdb;

					if (! myd_user_is_allowed_admin()) {
						return new \WP_Error('insufficient_permissions', 'Permissões insuficientes', array('status' => 403));
					}

					$tokens = $wpdb->get_results("
						SELECT rt.*, u.user_login, u.display_name
						FROM {$wpdb->prefix}myd_refresh_tokens rt
						LEFT JOIN {$wpdb->users} u ON rt.user_id = u.ID
						WHERE rt.expires_at > NOW()
						ORDER BY rt.created_at DESC
					");

					return array('tokens' => $tokens);
				},
				'permission_callback' => '__return_true',
			));

			// Endpoint para revogar token específico (para admin)
			register_rest_route('myd-delivery/v1', '/tokens', array(
				'methods' => 'DELETE',
				'callback' => function($request) {
					global $wpdb;

					if (! myd_user_is_allowed_admin()) {
						return new \WP_Error('insufficient_permissions', 'Permissões insuficientes', array('status' => 403));
					}

					$token_id = $request->get_param('token_id');
					if (!$token_id) {
						return new \WP_Error('missing_token_id', 'ID do token é obrigatório', array('status' => 400));
					}

					$result = $wpdb->delete(
						$wpdb->prefix . 'myd_refresh_tokens',
						array('id' => $token_id),
						array('%d')
					);

					if ($result) {
						return array('success' => true, 'message' => 'Token revogado com sucesso');
					} else {
						return new \WP_Error('revoke_failed', 'Falha ao revogar token', array('status' => 500));
					}
				},
				'permission_callback' => '__return_true',
			));

			// Endpoint de login
			register_rest_route('custom-auth/v1', '/login', array(
				'methods' => 'POST',
				'callback' => function($request) {
					global $wpdb;

					$token = $request->get_header('authorization');
					if (!$token || !preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
						return new \WP_Error('invalid_token', 'Token inválido', array('status' => 401));
					}

					$access_token = $matches[1];

					// Verificar se token foi revogado
					$token_hash = hash('sha256', $access_token);
					$revoked = $wpdb->get_var($wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}myd_revoked_tokens WHERE token_hash = %s",
						$token_hash
					));
					if ($revoked) {
						return new \WP_Error('token_revoked', 'Token foi revogado', array('status' => 401));
					}

					// Decodificar o token JWT manualmente
					$parts = explode('.', $access_token);
					if (count($parts) !== 3) {
						return new \WP_Error('invalid_token', 'Token inválido', array('status' => 401));
					}
					$payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
					if (!$payload || !isset($payload['data']['user']['id'])) {
						return new \WP_Error('invalid_token', 'Token inválido', array('status' => 401));
					}
					$user_id = $payload['data']['user']['id'];

					// Gerar refresh token
					$refresh_token = call_user_func('wp_generate_password', 64, false);
					$refresh_expires = date('Y-m-d H:i:s', time() + (30 * 86400)); // 30 dias em segundos

					// Salvar refresh token no banco
					$wpdb->insert(
						$wpdb->prefix . 'myd_refresh_tokens',
						array(
							'user_id' => $user_id,
							'refresh_token' => hash('sha256', $refresh_token), // Salvar hash
							'access_token_hash' => $token_hash,
							'expires_at' => $refresh_expires,
							'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
							'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
						),
						array('%d', '%s', '%s', '%s', '%s', '%s')
					);

					// Gerar cookie de autenticação
					$expiration = time() + 604800; // 7 dias de sessão para o Electron
					$cookiehash = md5(call_user_func('get_option', 'siteurl'));
					$cookie_name = 'wordpress_logged_in_' . $cookiehash;
					$cookie_value = call_user_func('wp_generate_auth_cookie', $user_id, $expiration, 'logged_in');

					return array(
						'cookieName' => $cookie_name,
						'cookieValue' => $cookie_value,
						'refresh_token' => $refresh_token,
						'access_token' => $access_token
					);
				},
				'permission_callback' => '__return_true',
			));

			// Endpoint de refresh
			register_rest_route('custom-auth/v1', '/refresh', array(
				'methods' => 'POST',
				'callback' => function($request) {
					global $wpdb;

					$refresh_token = $request->get_param('refresh_token');
					if (!$refresh_token) {
						return new \WP_Error('missing_refresh_token', 'Refresh token é obrigatório', array('status' => 400));
					}

					// Buscar refresh token no banco
					$stored_token = $wpdb->get_row($wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}myd_refresh_tokens 
						 WHERE refresh_token = %s AND expires_at > NOW()",
						hash('sha256', $refresh_token)
					));

					if (!$stored_token) {
						return new \WP_Error('invalid_refresh_token', 'Refresh token inválido ou expirado', array('status' => 401));
					}

					$user_id = $stored_token->user_id;

					// Gerar novo access token usando o plugin JWT
					$secret_key = call_user_func('get_option', 'jwt_auth_secret_key');
					if (!$secret_key) {
						$secret_key = \JWT_AUTH_SECRET_KEY;
					}

					$issuedAt = time();
					$notBefore = $issuedAt;
					$expire = $issuedAt + (86400 * 7); // 7 dias (igual ao login inicial)

					$token_data = array(
						'iss' => call_user_func('get_bloginfo', 'url'),
						'iat' => $issuedAt,
						'nbf' => $notBefore,
						'exp' => $expire,
						'data' => array(
							'user' => array(
								'id' => $user_id,
							)
						)
					);

					// Usar a classe JWT se disponível, senão manual
					if (class_exists('JWT')) {
						$jwt = \JWT::encode($token_data, $secret_key, 'HS256');
					} else {
						// Codificação manual
						$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
						$payload = json_encode($token_data);
						$headerEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
						$payloadEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
						$signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $secret_key, true);
						$signatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
						$jwt = $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
					}

					// Atualizar refresh token (rotate)
					$new_refresh_token = call_user_func('wp_generate_password', 64, false);
					$wpdb->update(
						$wpdb->prefix . 'myd_refresh_tokens',
						array(
							'refresh_token' => hash('sha256', $new_refresh_token),
							'expires_at' => date('Y-m-d H:i:s', time() + (30 * 86400))
						),
						array('id' => $stored_token->id),
						array('%s', '%s'),
						array('%d')
					);

					// Gerar novo cookie de autenticação
					$expiration = time() + 604800; // 7 dias de sessão para o Electron
					$cookiehash = md5(call_user_func('get_option', 'siteurl'));
					$cookie_name = 'wordpress_logged_in_' . $cookiehash;
					$cookie_value = call_user_func('wp_generate_auth_cookie', $user_id, $expiration, 'logged_in');

					return array(
						'access_token' => $jwt,
						'refresh_token' => $new_refresh_token,
						'cookieName' => $cookie_name,
						'cookieValue' => $cookie_value,
						'expires_in' => 86400
					);
				},
				'permission_callback' => '__return_true',
			));
			// Endpoint de revogação
			register_rest_route('custom-auth/v1', '/revoke', array(
				'methods' => 'POST',
				'callback' => function($request) {
					global $wpdb;

					$token_to_revoke = $request->get_param('token');
					$revoke_all = $request->get_param('revoke_all'); // Revogar todos os tokens do usuário
					$user_id = $request->get_param('user_id'); // Para admin revogar tokens de outros usuários

					// Permitir revogação direta por token (quando o cliente fornece o refresh token em texto)
					if ($token_to_revoke) {
						global $wpdb;
						$token_hash = hash('sha256', $token_to_revoke);

						// Verificar se o token existe na tabela de refresh tokens
						$stored = $wpdb->get_row($wpdb->prepare(
							"SELECT * FROM {$wpdb->prefix}myd_refresh_tokens WHERE refresh_token = %s",
							$token_hash
						));

						if ($stored) {
							// LOG: rota de revogação chamada com token (apenas hash salvo nos logs)
							error_log('[myd-delivery-pro] revoke called with token param; token_hash=' . $token_hash);

							// Adicionar à blacklist
							$wpdb->insert(
								$wpdb->prefix . 'myd_revoked_tokens',
								array(
									'token_hash' => $token_hash,
									'reason' => 'Revogado via endpoint por posse do refresh token'
								),
								array('%s', '%s')
							);

							// Remover o refresh token da tabela (garante que não apareça mais como ativo)
							$deleted = $wpdb->delete(
								$wpdb->prefix . 'myd_refresh_tokens',
								array('id' => $stored->id),
								array('%d')
							);

							if ($deleted) {
								error_log('[myd-delivery-pro] refresh token id ' . $stored->id . ' deleted from myd_refresh_tokens');
								
								// Destruir todas as sessões nativas desse usuário no WP para deslogar em qualquer navegador web
								if ( $stored->user_id ) {
									call_user_func('wp_destroy_all_sessions', $stored->user_id);
									error_log('[myd-delivery-pro] wp_destroy_all_sessions called for user ' . $stored->user_id);
								}
							} else {
								error_log('[myd-delivery-pro] failed to delete refresh token id ' . $stored->id);
							}

							return array(
								'success' => true,
								'message' => 'Token revogado e removido do banco com sucesso',
								'revoked_count' => ($deleted ? 1 : 0)
							);
						} else {
							return new \WP_Error('token_not_found', 'Token não encontrado', array('status' => 404));
						}
					}

					// Se não enviaram token específico, então exigimos autenticação para revogar por usuário
					$current_user_id = call_user_func('get_current_user_id');
					if (!$current_user_id) {
						return new \WP_Error('not_authenticated', 'Usuário não autenticado', array('status' => 401));
					}

					// Verificar permissões - só admin pode revogar tokens de outros
					if ($user_id && $user_id != $current_user_id) {
						if (! myd_user_is_allowed_admin()) {
							return new \WP_Error('insufficient_permissions', 'Permissões insuficientes', array('status' => 403));
						}
						$target_user_id = $user_id;
					} else {
						$target_user_id = $current_user_id;
					}

					$revoked_count = 0;

					if ($revoke_all) {
						// Remover todos os refresh tokens do usuário (opção agressiva) e registrar quantos foram removidos
						$result = $wpdb->query($wpdb->prepare(
							"DELETE FROM {$wpdb->prefix}myd_refresh_tokens WHERE user_id = %d",
							$target_user_id
						));
						// $result retorna número de linhas afetadas ou false
						$revoked_count = $result === false ? 0 : intval($result);
						
						// Destruir sessões globais
						call_user_func('wp_destroy_all_sessions', $target_user_id);
						
						error_log('[myd-delivery-pro] revoke_all removed ' . $revoked_count . ' tokens and destroyed sessions for user ' . $target_user_id);
					}

					return array(
						'success' => true,
						'message' => "Token(s) revogado(s) com sucesso",
						'revoked_count' => $revoked_count
					);
				},
				'permission_callback' => '__return_true',
			));

			// Endpoint para gerar link SSO efêmero (usado pelo Electron para logar o navegador externo)
			register_rest_route('custom-auth/v1', '/sso/generate', array(
				'methods' => 'POST',
				'callback' => function($request) {
					global $wpdb;

					$token = $request->get_header('authorization');
					if (!$token || !preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
						return new \WP_Error('invalid_token', 'Token inválido', array('status' => 401));
					}

					$access_token = $matches[1];

					// Decodificar token para obter user_id (mesma lógica do login)
					$parts = explode('.', $access_token);
					if (count($parts) !== 3) {
						return new \WP_Error('invalid_token', 'Token inválido', array('status' => 401));
					}
					$payload = json_decode(base64_decode(str_replace(array('-', '_'), array('+', '/'), $parts[1])), true);
					if (!$payload || !isset($payload['data']['user']['id'])) {
						return new \WP_Error('invalid_token', 'Token inválido', array('status' => 401));
					}
					$user_id = intval($payload['data']['user']['id']);

					// Gerar código efêmero e salvar como transient (válido por 2 minutos)
					$code = call_user_func('wp_generate_password', 32, false);
					$transient_key = 'myd_sso_code_' . $code;
					call_user_func('set_transient', $transient_key, $user_id, 120);

					// Retornar URL público que, quando visitado, cria a sessão e redireciona
					$sso_url = call_user_func('home_url', '?myd_sso_code=' . rawurlencode($code));
					return array('sso_url' => $sso_url, 'expires_in' => 120);
				},
				'permission_callback' => '__return_true'
			));
		});

		// Register frontend shortcode for a simple dashboard
		add_action( 'init', function() {
			add_shortcode( 'myd_dashboard', [ $this, 'render_dashboard_shortcode' ] );
		} );

		// Reset expired points for current user at init (so DB reflects expirations when user visits)
		$this->maybe_reset_expired_points_for_current_user();
		// Fallback: run global loyalty cleanup on init if last run was more than 60s ago
		$this->maybe_run_loyalty_cleanup_on_init();

	}

	/**
	 * When post meta 'order_status' is updated, handle loyalty restoration on cancellations
	 */
	public function maybe_restore_loyalty_on_status_change( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( $meta_key !== 'order_status' ) return;
		if ( empty( $post_id ) ) return;
		// if new status is 'canceled', try to restore points
		if ( $meta_value !== 'canceled' ) return;
		// only for our orders
		if ( get_post_type( $post_id ) !== 'mydelivery-orders' ) return;
		$cid = get_post_meta( $post_id, 'myd_customer_id', true );
		if ( empty( $cid ) ) return;
		$cid = (int) $cid;
		// if this order had added a point previously, restore previous points or decrement
		$point_added = get_post_meta( $post_id, 'order_loyalty_point_added', true );
		$redeemed = get_post_meta( $post_id, 'order_loyalty_redeemed', true );
		$prev_points = get_post_meta( $post_id, 'order_loyalty_points_prev', true );
		$current = intval( get_user_meta( $cid, 'myd_loyalty_points', true ) );
		if ( ! empty( $point_added ) && (string) $point_added === '1' ) {
			if ( $prev_points !== '' && is_numeric( $prev_points ) ) {
				update_user_meta( $cid, 'myd_loyalty_points', intval( $prev_points ) );
			} else {
				// decrement safely
				$newv = max( 0, $current - 1 );
				update_user_meta( $cid, 'myd_loyalty_points', $newv );
			}
			update_post_meta( $post_id, 'order_loyalty_point_added', '0' );
		}
		// if this order was a redeemed order, restore previous points snapshot
		if ( ! empty( $redeemed ) && (string) $redeemed === '1' ) {
			if ( $prev_points !== '' && is_numeric( $prev_points ) ) {
				update_user_meta( $cid, 'myd_loyalty_points', intval( $prev_points ) );
			}
			update_post_meta( $post_id, 'order_loyalty_redeemed', '0' );
		}
	}

	/**
	 * Load required files
	 *
	 * @since 1.2
	 * @return void
	 */
	public function set_required_files() {
		// Electron user cookie expiration fix (7 days)
		add_filter( 'auth_cookie_expiration', function( $length, $user_id, $remember ) {
			if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && strpos( $_SERVER['HTTP_USER_AGENT'], 'Electron' ) !== false ) {
				return 604800; // 7 days in seconds
			}
			return $length;
		}, 99, 3 );

	// Bloqueio WP-Admin para não-admins
	include_once MYD_PLUGIN_PATH . 'includes/bloqueio-wpadmin-nao-admins.php';
		if ( is_admin() ) {
			include_once MYD_PLUGIN_PATH . 'includes/admin/class-admin-page.php';
			include_once MYD_PLUGIN_PATH . 'includes/admin/abstract-class-admin-settings.php';
			include_once MYD_PLUGIN_PATH . 'includes/admin/class-settings.php';
			include_once MYD_PLUGIN_PATH . 'includes/class-reports.php';
		}

		include_once MYD_PLUGIN_PATH . 'includes/legacy/class-legacy-repeater.php';
		include_once MYD_PLUGIN_PATH . 'includes/custom-fields/class-register-custom-fields.php';
		include_once MYD_PLUGIN_PATH . 'includes/custom-fields/class-label.php';
		include_once MYD_PLUGIN_PATH . 'includes/custom-fields/class-custom-fields.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-store-data.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-order-meta.php';
		include_once MYD_PLUGIN_PATH . 'includes/admin/class-custom-posts.php';
		include_once MYD_PLUGIN_PATH . 'includes/fdm-products-list.php';
		include_once MYD_PLUGIN_PATH . 'includes/myd-manage-cpt-columns.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-orders-front-panel.php';
		include_once MYD_PLUGIN_PATH . 'includes/fdm-track-order.php';
		include_once MYD_PLUGIN_PATH . 'includes/api.php';
		include_once MYD_PLUGIN_PATH . 'includes/push/class-push-notifier.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-settings.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/sse/class-order-status-tracking.php';
		include_once MYD_PLUGIN_PATH . 'includes/api/order/class-get-order.php';
		// Access monitor (SSE + shortcode)
		include_once MYD_PLUGIN_PATH . 'includes/access-monitor.php';
		include_once MYD_PLUGIN_PATH . 'includes/set-custom-styles.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-legacy.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-store-orders.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-store-formatting.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/abstract-class-license-api.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/interface-license-action.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/class-license-manage-data.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/class-license.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/class-license-activate.php';
		include_once MYD_PLUGIN_PATH . 'includes/license/class-license-deactivate.php';
		include_once MYD_PLUGIN_PATH . 'includes/plugin-update/class-plugin-update.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-currency.php';
		include_once MYD_PLUGIN_PATH . 'includes/l10n/class-countries.php';
		include_once MYD_PLUGIN_PATH . 'includes/l10n/class-country.php';
		include_once MYD_PLUGIN_PATH . 'includes/ajax/class-update-cart.php';
		include_once MYD_PLUGIN_PATH . 'includes/ajax/class-create-draft-order.php';
		include_once MYD_PLUGIN_PATH . 'includes/ajax/class-place-payment.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-mercadopago-webhook-handler.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-cart.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-create-draft-order.php';
		include_once MYD_PLUGIN_PATH . 'includes/repositories/class-coupon-repository.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-coupon.php';
		include_once MYD_PLUGIN_PATH . '/includes/class-create-draft-order.php';
		include_once MYD_PLUGIN_PATH . '/includes/class-custom-message-whatsapp.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-customer-authentication.php';
		include_once MYD_PLUGIN_PATH . 'includes/admin/class-customers-manager.php';
		include_once MYD_PLUGIN_PATH . 'includes/class-custom-login.php';
		// Provide backwards-compatible global helpers for legacy files
		include_once MYD_PLUGIN_PATH . 'includes/compat-global.php';
		require_once __DIR__ . '/shortcode-store-status-set.php';
		// Facebook Conversions API helper (server-side endpoint)
		if ( file_exists( MYD_PLUGIN_PATH . 'includes/class-facebook-capi.php' ) ) {
			include_once MYD_PLUGIN_PATH . 'includes/class-facebook-capi.php';
			if ( class_exists('\MydPro\\Includes\\Facebook_CAPI') && method_exists('\MydPro\\Includes\\Facebook_CAPI', 'register_routes') ) {
				\MydPro\Includes\Facebook_CAPI::register_routes();
			}
		}
	}

	/**
	 * Enqueu admin styles/scripts
	 *
	 * @since 1.2
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		wp_register_script( 'myd-admin-scritps', MYD_PLUGN_URL . 'assets/js/admin/admin-scripts.min.js', [], MYD_CURRENT_VERSION, true );
		wp_enqueue_script( 'myd-admin-scritps' );

		wp_register_script( 'myd-admin-cf-media-library', MYD_PLUGN_URL . 'assets/js/admin/custom-fields/media-library.min.js', [], MYD_CURRENT_VERSION, true );
		wp_register_script( 'myd-admin-cf-repeater', MYD_PLUGN_URL . 'assets/js/admin/custom-fields/repeater.min.js', [], MYD_CURRENT_VERSION, true );

		wp_register_style( 'myd-admin-style', MYD_PLUGN_URL . 'assets/css/admin/admin-style.min.css', [], MYD_CURRENT_VERSION );
		wp_enqueue_style( 'myd-admin-style' );
		// Switch customizado para métodos de pagamento
		wp_register_style( 'myd-payment-switch', MYD_PLUGN_URL . 'assets/css/myd-payment-switch.css', [], MYD_CURRENT_VERSION );
		wp_enqueue_style( 'myd-payment-switch' );
		// Script para tabs de métodos de pagamento
		wp_register_script( 'myd-payment-tabs', MYD_PLUGN_URL . 'assets/js/myd-payment-tabs.js', [], MYD_CURRENT_VERSION, true );
		wp_enqueue_script( 'myd-payment-tabs' );

		wp_register_script( 'myd-chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), MYD_CURRENT_VERSION, true );
	}

	/**
	 * Enqueue front end styles/scripts
	 *
	 * @since 1.2
	 * @return void
	 */
	public function enqueue_frondend_scripts() {
		// Socket.IO Client
		wp_register_script(
			'socket-io',
			'https://cdn.socket.io/4.7.2/socket.io.min.js',
			array(),
			'4.7.2',
			true
		);

		// Small stub script used as a dependency to ensure ordering after delivery CSS
		wp_register_script(
			'myd-delivery-frontend-dep',
			MYD_PLUGN_URL . 'assets/js/delivery-frontend-dep.js',
			array(),
			MYD_CURRENT_VERSION,
			true
		);

		// Profile Bar Script (depend on the delivery-frontend stub so it loads after the CSS)
		wp_register_script(
			'myd-profile-bar',
			MYD_PLUGN_URL . 'assets/js/profile-bar.js',
			array('myd-delivery-frontend-dep'),
			MYD_CURRENT_VERSION,
			true
		);
		wp_enqueue_script('myd-profile-bar');

		// Enfileira Socket.IO após o script do profile-bar para reduzir bloqueio
		wp_enqueue_script('socket-io');

		// Passa dados do usuário e logout seguro para o JS
		$current_user = wp_get_current_user();
		$user_payload = null;
		if ($current_user && $current_user->exists()) {
			$user_payload = [
				'display_name' => $current_user->display_name,
				'user_email'   => $current_user->user_email,
				'id'           => $current_user->ID,
				'phone'        => get_user_meta( $current_user->ID, 'myd_customer_phone', true ),
			];
		}
		wp_localize_script('myd-profile-bar', 'MYD_DATA', [
			'logoutUrl'   => wp_logout_url(home_url('/')),
			'currentUser' => $user_payload,
			'isLoggedIn'  => is_user_logged_in(),
			'pushUrl'     => get_option( 'myd_push_server_url', '' ),
			// Provide plugin base URL so frontend JS can build asset paths reliably
			'pluginUrl'   => MYD_PLUGN_URL,
		]);
		wp_register_script( 'plugin_pdf', 'https://printjs-4de6.kxcdn.com/print.min.js', array(), MYD_CURRENT_VERSION, true );
		wp_register_style( 'plugin_pdf_css', 'https://printjs-4de6.kxcdn.com/print.min.css', array(), MYD_CURRENT_VERSION, true );

		wp_register_script( 'myd-create-order', MYD_PLUGN_URL . 'assets/js/order.min.js', array(), MYD_CURRENT_VERSION, true );
		wp_localize_script(
			'myd-create-order',
			'ajax_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'order_nonce' => wp_create_nonce( 'myd-create-order' ),
			)
		);

		// Use file modification time as version to bust cache automatically when files change
		$css_base_path = MYD_PLUGIN_PATH . 'assets/css/';
		$delivery_frontend_ver = @file_exists( $css_base_path . 'delivery-frontend.min.css' ) ? filemtime( $css_base_path . 'delivery-frontend.min.css' ) : MYD_CURRENT_VERSION;
		$order_panel_ver = @file_exists( $css_base_path . 'order-panel-frontend.min.css' ) ? filemtime( $css_base_path . 'order-panel-frontend.min.css' ) : MYD_CURRENT_VERSION;
		$panel_inline_ver = @file_exists( $css_base_path . 'panel-inline.css' ) ? filemtime( $css_base_path . 'panel-inline.css' ) : MYD_CURRENT_VERSION;
		$panel_overrides_ver = @file_exists( $css_base_path . 'panel-overrides.css' ) ? filemtime( $css_base_path . 'panel-overrides.css' ) : MYD_CURRENT_VERSION;

		wp_register_style( 'myd-delivery-frontend', MYD_PLUGN_URL . 'assets/css/delivery-frontend.min.css', array(), $delivery_frontend_ver );
		wp_register_style( 'myd-order-panel-frontend', MYD_PLUGN_URL . 'assets/css/order-panel-frontend.min.css', array(), $order_panel_ver );
		// Panel specific consolidated CSS migrated from templates/order/panel.php
		wp_register_style( 'myd-panel-inline', MYD_PLUGN_URL . 'assets/css/panel-inline.css', array( 'myd-order-panel-frontend' ), $panel_inline_ver );
		wp_register_style( 'myd-panel-overrides', MYD_PLUGN_URL . 'assets/css/panel-overrides.css', array( 'myd-order-panel-frontend' ), $panel_overrides_ver );
		// Disabled inputs styling
		wp_register_style( 'myd-disabled-inputs', MYD_PLUGN_URL . 'assets/css/disabled-inputs.css', array(), MYD_CURRENT_VERSION );
		wp_enqueue_style( 'myd-disabled-inputs' );
		// Autocomplete disable styling
		wp_register_style( 'myd-autocomplete-disable', MYD_PLUGN_URL . 'assets/css/autocomplete-disable.css', array(), MYD_CURRENT_VERSION );
		wp_enqueue_style( 'myd-autocomplete-disable' );
		// Autocomplete disable script
		wp_register_script( 'myd-autocomplete-disabler', MYD_PLUGN_URL . 'assets/js/autocomplete-disabler.js', array(), MYD_CURRENT_VERSION, true );
		wp_enqueue_script( 'myd-autocomplete-disabler' );
		// Manual number input restrictor
		wp_register_script( 'myd-manual-number-restrictor', MYD_PLUGN_URL . 'assets/js/manual-number-restrictor.js', array(), MYD_CURRENT_VERSION, true );
		wp_enqueue_script( 'myd-manual-number-restrictor' );
		// Checkout loading overlay
		wp_register_script( 'myd-checkout-loading-overlay', MYD_PLUGN_URL . 'assets/js/checkout-loading-overlay.js', array(), MYD_CURRENT_VERSION, true );
		wp_enqueue_script( 'myd-checkout-loading-overlay' );
		wp_register_style( 'myd-track-order-frontend', MYD_PLUGN_URL . 'assets/css/track-order-frontend.min.css', array(), MYD_CURRENT_VERSION );

		/**
		 * Orders Panel
		 * TODO: refactor Jquery and merge scripts
		 */
		$js_panel_path = defined('MYD_PLUGIN_PATH') ? MYD_PLUGIN_PATH . 'assets/js/orders-panel/frontend.min.js' : '';
		$js_panel_ver = ($js_panel_path && @file_exists($js_panel_path)) ? filemtime($js_panel_path) : MYD_CURRENT_VERSION;
		wp_register_script( 'myd-orders-panel', MYD_PLUGN_URL . 'assets/js/orders-panel/frontend.min.js', array(), $js_panel_ver, true );
		// Expose push server URL and REST base to the frontend orders-panel script
		wp_localize_script(
			'myd-orders-panel',
			'MYD_ORDERS_PANEL',
			array(
				'push_url' => get_option( 'myd_push_server_url', '' ),
				'rest_base' => rest_url( 'myd-delivery/v1/' ),
			)
		);
		wp_localize_script(
			'myd-orders-panel',
			'order_ajax_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'myd-order-notification' ),
				'domain' => esc_attr( home_url() ),
			)
		);

		wp_register_script( 'myd-order-list-ajax', MYD_PLUGN_URL . 'assets/js/order-list-ajax.min.js', array( 'jquery' ), MYD_CURRENT_VERSION, true );
		
		/**
		 * New Order Highlight Script
		 */
		wp_register_script( 'myd-new-order-highlight', MYD_PLUGN_URL . 'assets/js/orders-panel/new-order-highlight.js', array(), MYD_CURRENT_VERSION, true );

		/**
		 * Order Poll Fallback – verifica novos pedidos a cada 30s quando Socket.IO não está conectado
		 */
		wp_register_script( 'myd-order-poll-fallback', MYD_PLUGN_URL . 'assets/js/orders-panel/order-poll-fallback.js', array( 'myd-orders-panel' ), MYD_CURRENT_VERSION, true );
		
        /**
         * Print & Audio Script
         */
        wp_register_script( 'myd-print-audio', MYD_PLUGN_URL . 'assets/js/orders-panel/print-audio.js', array( 'jquery' ), MYD_CURRENT_VERSION, true );
		
		/**
		 * Order Session Persistence Script
		 */
		wp_register_script( 'myd-order-session-persistence', MYD_PLUGN_URL . 'assets/js/order-session-persistence.js', array( 'myd-create-order' ), MYD_CURRENT_VERSION, true );
		wp_register_style( 'myd-order-session-persistence', MYD_PLUGN_URL . 'assets/css/order-session-persistence.css', array(), MYD_CURRENT_VERSION );

		// Facebook / Meta Pixel client-side tracking (fires Purchase on order complete)
		wp_register_script( 'myd-fb-pixel', MYD_PLUGN_URL . 'assets/js/fb-pixel.js', array( 'myd-create-order' ), MYD_CURRENT_VERSION, true );
		wp_localize_script( 'myd-fb-pixel', 'MYD_FB', array(
			'pixelId' => get_option( 'myd_facebook_pixel_id', '' ),
			'currency' => get_option( 'myd_facebook_currency', 'BRL' ),
			'fbCapiUrl' => rest_url('myd/v1/fb-capi'),
			'fbCapiNonce' => wp_create_nonce('wp_rest'),
		) );
		wp_enqueue_script( 'myd-fb-pixel' );
		
		/**
		 * Tracking Session Recovery Script
		 */
		wp_register_script( 'myd-tracking-session-recovery', MYD_PLUGN_URL . 'assets/js/tracking-session-recovery.js', array(), MYD_CURRENT_VERSION, true );
		
		/**
		 * END Orders Panel
		 */

		// Ensure delivery frontend dependency stub is enqueued whenever the delivery CSS is queued
		add_action( 'wp_print_styles', array( $this, 'maybe_enqueue_delivery_dep' ) );
	}

	/**
	 * Shortcode renderer for a simple dashboard.
	 * Usage: [myd_dashboard limit="5"]
	 *
	 * @param array $atts
	 * @return string
	 */
	public function render_dashboard_shortcode( $atts = array() ) {
		   // Redireciona para login se não estiver logado
		   if ( ! is_user_logged_in() ) {
			   // Tenta usar o caminho configurado em app-config.json
			   $login_path = '/wp-admin.php';
			   if ( function_exists('get_option') ) {
				   $config_path = dirname(__FILE__, 2) . '/gestordepedidos/app-config.json';
				   if ( file_exists( $config_path ) ) {
					   $config = json_decode( file_get_contents( $config_path ), true );
					   if ( isset($config['wordpress']['loginPath']) ) {
						   $login_path = $config['wordpress']['loginPath'];
					   }
				   }
			   }
			   $login_url = site_url( $login_path );
			   if ( ! headers_sent() ) {
				   wp_redirect( $login_url );
				   exit;
			   } else {
				   echo '<script>window.location.href = "' . esc_url( $login_url ) . '";</script>';
				   return '';
			   }
		   }

		   $atts = shortcode_atts( array(
			   'limit' => 5,
		   ), $atts, 'myd_dashboard' );

		   $limit = intval( $atts['limit'] );
		   // Count orders created today
		   $today = getdate();
		   $today_start = sprintf( '%04d-%02d-%02d 00:00:00', $today['year'], $today['mon'], $today['mday'] );

		   $today_query = new \WP_Query( array(
			   'post_type' => 'mydelivery-orders',
			   'post_status' => 'any',
			   'date_query' => array(
				   array( 'after' => $today_start, 'inclusive' => true ),
			   ),
			   'posts_per_page' => -1,
			   'fields' => 'ids',
		   ) );

		   $today_count = is_wp_error( $today_query ) ? 0 : count( $today_query->posts );

		   // Recent orders
		   $recent_q = new \WP_Query( array(
			   'post_type' => 'mydelivery-orders',
			   'post_status' => 'any',
			   'posts_per_page' => $limit,
			   'orderby' => 'date',
			   'order' => 'DESC'
		   ) );

		   ob_start();
		   ?>
		   <div class="myd-dashboard">
			   <h3><?php esc_html_e( 'MyD Dashboard', 'myd-delivery-pro' ); ?></h3>
			   <p><strong><?php esc_html_e( 'Pedidos hoje:', 'myd-delivery-pro' ); ?></strong> <?php echo intval( $today_count ); ?></p>
			   <h4><?php esc_html_e( 'Pedidos recentes', 'myd-delivery-pro' ); ?></h4>
			   <ul>
			   <?php if ( $recent_q->have_posts() ) : while ( $recent_q->have_posts() ) : $recent_q->the_post();
				   $post_id = get_the_ID();
				   $status = get_post_meta( $post_id, 'order_status', true );
				   $total = get_post_meta( $post_id, 'order_total', true );
				   $customer = get_post_meta( $post_id, 'order_customer_name', true );
				   ?>
				   <li>
					   <a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" target="_blank"><?php echo esc_html( '#' . $post_id ); ?></a>
					   - <?php echo esc_html( $customer ); ?>
					   (<?php echo esc_html( $status ); ?>) - <?php echo esc_html( $total ); ?>
				   </li>
			   <?php endwhile; wp_reset_postdata(); else: ?>
				   <li><?php esc_html_e( 'Nenhum pedido recente', 'myd-delivery-pro' ); ?></li>
			   <?php endif; ?>
			   </ul>
		   </div>
		   <?php
		   return ob_get_clean();
	}

	/**
	 * Enqueue the delivery-frontend dependency stub when the delivery CSS is enqueued.
	 * This ensures scripts that depend on the stub are printed after the CSS.
	 */
	public function maybe_enqueue_delivery_dep() {
		if ( function_exists( 'wp_style_is' ) && wp_style_is( 'myd-delivery-frontend', 'enqueued' ) ) {
			wp_enqueue_script( 'myd-delivery-frontend-dep' );
		}
	}

	/**
	 * Fix plugin path name error
	 *
	 * Solve problem caused in old version ipdate
	 *
	 * @since 1.9.4
	 */
	public function check_plugin_path() {
		if ( is_admin() ) {

			$current_path = MYD_PLUGIN_PATH;

			if ( strpos( $current_path, 'my-delivey-wordpress' ) !== false ) {

				if ( ! function_exists( 'deactivate_plugins' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}

				$deactive = deactivate_plugins( 'my-delivey-wordpress/myd-delivery-pro.php' );
				if ( is_wp_error( $deactive ) ) {
					esc_html_e( 'Error to deactive, contato MyD Delivery support.', 'myd-delivery-pro' );
					return;
				}

				$new_path = str_replace( 'my-delivey-wordpress', 'myd-delivery-pro', $current_path );
				rename( $current_path, $new_path );

				wp_safe_redirect( site_url( '/wp-admin/plugins.php' ) );
				exit;
			}
		}
	}

	/**
	 * Update notice
	 *
	 * @since 1.9.4
	 * @return void
	 */
	public function update_notice_invalid_license( $plugin_data, $new_data ) {

		if ( empty( $new_data->package ) ) {
			printf(
				'<br><span><strong>%1s</strong> %2s.</span>',
				esc_html__( 'Important:', 'myd-delivery-pro' ),
				esc_html__( 'Update is not available because your license is invalid', 'myd-delivery-pro' )
			);
		}
	}

	/**
	 * Check if plugin is activated
	 *
	 * @since 1.9.4
	 * @return boolean
	 * @param string $plugin
	 */
	public function plugin_is_active( $plugin ) {
		return function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin ) : in_array( $plugin, (array) get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Activation hook
	 *
	 * @since 1.9.6
	 * @return void
	 */
	public function activation() {
		\flush_rewrite_rules();

		// Criar tabelas para sistema de refresh tokens
		$this->create_token_tables();

		// Schedule periodic store open check (every minute) if push notifier available
		if ( ! call_user_func('wp_next_scheduled', 'myd_check_store_open') ) {
			call_user_func('wp_schedule_event', time(), 'every_minute', 'myd_check_store_open');
		}

		// Add plugin-specific capability for viewing reports
		if ( function_exists( 'get_role' ) ) {
			$roles = array( 'administrator', 'shop_manager' );
			foreach ( $roles as $r ) {
				$role = get_role( $r );
				if ( $role && ! $role->has_cap( 'myd_view_reports' ) ) {
					$role->add_cap( 'myd_view_reports' );
				}
			}
		}
	}

    

	/**
	 * Criar tabelas para refresh tokens e revogação
	 */
	private function create_token_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Tabela para refresh tokens
		$table_refresh = $wpdb->prefix . 'myd_refresh_tokens';
		$sql_refresh = "CREATE TABLE $table_refresh (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			refresh_token varchar(255) NOT NULL,
			access_token_hash varchar(128) NOT NULL,
			expires_at datetime NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			ip_address varchar(45),
			user_agent text,
			PRIMARY KEY (id),
			UNIQUE KEY refresh_token (refresh_token),
			KEY user_id (user_id),
			KEY expires_at (expires_at)
		) $charset_collate;";

		// Tabela para tokens revogados
		$table_revoked = $wpdb->prefix . 'myd_revoked_tokens';
		$sql_revoked = "CREATE TABLE $table_revoked (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			token_hash varchar(128) NOT NULL,
			revoked_at datetime DEFAULT CURRENT_TIMESTAMP,
			reason varchar(255),
			PRIMARY KEY (id),
			UNIQUE KEY token_hash (token_hash),
			KEY revoked_at (revoked_at)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		call_user_func('dbDelta', $sql_refresh);
		call_user_func('dbDelta', $sql_revoked);

		// Agendar limpeza de tokens expirados
		if (!call_user_func('wp_next_scheduled', 'myd_cleanup_expired_tokens')) {
			call_user_func('wp_schedule_event', time(), 'daily', 'myd_cleanup_expired_tokens');
		}
	}

	/**
	 * Limpar tokens expirados (executado diariamente)
	 */
	public function cleanup_expired_tokens() {
		global $wpdb;

		// Limpar refresh tokens expirados
		$wpdb->query("DELETE FROM {$wpdb->prefix}myd_refresh_tokens WHERE expires_at < NOW()");

		// Limpar tokens revogados antigos (manter por 30 dias para auditoria)
		$wpdb->query("DELETE FROM {$wpdb->prefix}myd_revoked_tokens WHERE revoked_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
	}

	/**
	 * Limpar pontos de fidelidade expirados (executado diariamente pelo cron)
	 */
	public function cleanup_expired_loyalty_points() {
		global $wpdb;
		$umeta = $wpdb->usermeta;
		$meta_key = 'myd_loyalty_expires_at';
		$now = current_time( 'mysql' );
		// Selecionar usuários cuja meta expires_at <= agora
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, meta_value FROM {$umeta} WHERE meta_key = %s AND meta_value <= %s", $meta_key, $now ) );
		if ( empty( $rows ) ) return;
		foreach ( $rows as $r ) {
			$uid = intval( $r->user_id );
			// Setar pontos para zero e registrar reset timestamp
			update_user_meta( $uid, 'myd_loyalty_points', 0 );
			update_user_meta( $uid, 'myd_loyalty_reset_at', $now );
			// Remover expires_at (já expirou)
			delete_user_meta( $uid, 'myd_loyalty_expires_at' );
		}
	}

	/**
	 * Reset expired points for current logged-in user (run at init)
	 * Ensures DB is updated when the user visits after expiration.
	 */
	protected function maybe_reset_expired_points_for_current_user() {
		if ( ! is_user_logged_in() ) return;
		$uid = get_current_user_id();
		if ( ! $uid ) return;
		$exp_opt = get_option( 'myd_fidelidade_expiracao', 'never' );
		if ( $exp_opt === 'never' ) return;
		$expires_at = get_user_meta( $uid, 'myd_loyalty_expires_at', true );
		if ( empty( $expires_at ) ) return;
		$now_ts = (int) current_time( 'timestamp' );
		$expires_ts = strtotime( $expires_at );
		if ( $expires_ts !== false && $expires_ts <= $now_ts ) {
			$now = current_time( 'mysql' );
			update_user_meta( $uid, 'myd_loyalty_points', 0 );
			update_user_meta( $uid, 'myd_loyalty_reset_at', $now );
			delete_user_meta( $uid, 'myd_loyalty_expires_at' );
		}
	}

	/**
	 * Fallback to run global cleanup on any init, throttled to avoid heavy runs.
	 * This ensures expirations are applied even if only anonymous visitors access the site.
	 */
	protected function maybe_run_loyalty_cleanup_on_init() {
		$last = get_option( 'myd_loyalty_last_cleanup', 0 );
		$now = (int) current_time( 'timestamp' );
		// Throttle: only run if more than 60 seconds passed since last run
		if ( $last && ( $now - intval( $last ) ) < 60 ) {
			return;
		}
		// Run cleanup (safe to call repeatedly)
		try {
			$this->cleanup_expired_loyalty_points();
			update_option( 'myd_loyalty_last_cleanup', $now );
		} catch ( \Throwable $e ) {
			// swallow errors to avoid breaking front requests
		}
	}

	/**
	 * Deactivation hook
	 *
	 * @since 1.9.6
	 * @return void
	 */
	public function deactivation() {
		\flush_rewrite_rules();

		// Clear scheduled store check
		if ( function_exists('wp_clear_scheduled_hook') ) {
			call_user_func('wp_clear_scheduled_hook', 'myd_check_store_open');
		}

		// Clear loyalty expiration cleanup schedule
		if ( function_exists('wp_clear_scheduled_hook') ) {
			call_user_func('wp_clear_scheduled_hook', 'myd_loyalty_expire_cleanup');
		}

		// Remove plugin-specific capability from roles
		if ( function_exists( 'get_role' ) ) {
			$roles = array( 'administrator', 'shop_manager' );
			foreach ( $roles as $r ) {
				$role = get_role( $r );
				if ( $role && $role->has_cap( 'myd_view_reports' ) ) {
					$role->remove_cap( 'myd_view_reports' );
				}
			}
		}
	}

	/**
	 * Cron job to check store open state and notify push server on transitions
	 */
	public function check_store_open_schedule() {
		// Only proceed if Push_Notifier exists and Store_Data available
		if ( ! class_exists('MydPro\\Includes\\Push\\Push_Notifier') || ! class_exists('\MydPro\\Includes\\Store_Data') ) {
			return;
		}
		try {
			$open = \MydPro\Includes\Store_Data::is_store_open();
			$last = get_option('myd-last-store-open', null);
			if ($last === null) {
				update_option('myd-last-store-open', $open ? '1' : '0');
				return;
			}
			$last_bool = ($last === '1');
			if ($last_bool !== $open) {
				// state changed -> notify push server
				try { \MydPro\Includes\Push\Push_Notifier::notify_store( $open ); } catch ( \Throwable $e ) {}
				update_option('myd-last-store-open', $open ? '1' : '0');
			}
		} catch ( \Throwable $e ) {
			// swallow errors
		}
	}
}
