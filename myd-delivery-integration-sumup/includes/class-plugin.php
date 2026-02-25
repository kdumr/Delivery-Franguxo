<?php

namespace SumupMyd\Includes;

use SumupMyd\Includes\Checkout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin main class
 *
 * @since 1.9.6
 */
final class Plugin {
	/**
	 * Instance
	 *
	 * @since 1.9.4
	 *
	 * @access private
	 * @static
	 */
	private static $instance = null;

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
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Disable class cloning and throw an error on object clone.
	 *
	 * @access public
	 * @since 1.9.6
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'myd-delivery-integration-sumup' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @access public
	 * @since 1.9.6
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'myd-delivery-integration-sumup' ), '1.0' );
	}

	/**
	 * Construct class
	 *
	 * @since 1.2
	 * @return void
	 */
	private function __construct() {
		do_action( 'myd-delivery-integration-with-sumup/init' );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Init plugin
	 *
	 * @since 1.9.4
	 */
	public function init() {
		if ( ! did_action( 'myd_delivery_pro_init' ) ) {
			$this->require_myd_plugin_message();
			return;
		}

		if( defined( 'MYD_CURRENT_VERSION' ) && version_compare( \MYD_CURRENT_VERSION, '2.2.12', '<' ) ) {
			$this->require_myd_plugin_version_message();
			return;
		}

		$this->set_required_files();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frondend_scripts' ) );
		add_action( 'myd-delivery/settings/payment/after-fields', array( $this, 'add_settings' ) );
		add_filter( 'myd-delivery/admin/before-register-settings', array( $this, 'register_settings' ) );
		add_action( 'myd-delivery/order/after-create', array( $this, 'create_checkout' ) );
		add_filter( 'myd-delivery/order/validate-payment-integration', array( $this, 'validate_payment_integration' ), 2, 10 );
		add_action( 'update_option_sumupmyd-merchant-id', array( $this, 'validate_settings' ), 3, 10 );
		add_action( 'update_option_sumupmyd-api-key', array( $this, 'validate_settings' ), 3, 10 );
		add_action( 'update_option_sumupmyd-email', array( $this, 'validate_settings' ), 3, 10 );
	}

	/**
	 * Validate settings when update someone
	 *
	 * @param [type] $old_value
	 * @param [type] $value
	 * @param [type] $option
	 *
	 * @return void
	 */
	public function validate_settings( $old_value, $value, $option ) {
		$order_data = array(
			'id' => 'SETTING_VALIDATION',
			'currency_code' => class_exists( '\MydPro\Includes\Store_Data' ) ? \MydPro\Includes\Store_Data::get_store_data( 'currency_simbol' ) : 'BRL',
			'data' => array(
				'orderTotal' => array(
					'finalPrice' => 10,
				),
			),
		);

		$checkout_validation = Checkout::create( $order_data );

		if ( empty( $checkout_validation ) ) {

		}

		if ( isset( $checkout_validation['error'] ) ) {

		}

		if ( isset( $checkout_validation['error_code'] ) ) {
			// {"error_code":"INVALID","message":"Validation error","param":"currency"}
		}
	}

	/**
	 * Create SumUp checkout
	 */
	public function create_checkout( $order_data ) {
		if ( Checkout::$id !== false ) {
			return Checkout::$id;
		}

		$checkout = Checkout::create( $order_data );

		\update_post_meta( $order_data['id'], 'checkout_data', $checkout );

		\add_filter(
			'myd-delivery/order/after-create/ajax-response',
			function( $response ) use ( $checkout ) {
				$response['checkout_data'] = ! empty( $checkout ) ? $checkout : false;
				return $response;
			}
		);
	}

	/**
	 * Validate payment integration
	 */
	public function validate_payment_integration( $value, $order_id ) {
		$checkout_data = \get_post_meta( $order_id, 'checkout_data', true );
		$checkout_id = $checkout_data['id'] ?? null;

		if ( ! $checkout_id ) {
			// handle error when checkout_id is missed.
			return;
		}

		$payment = Checkout::get( $checkout_id );
		if ( empty( $payment ) ) {
			// handle error when checkout_id is missed.
			return;
		}

		$payment_status = strtolower( $payment['status'] );
		\update_post_meta( $order_id, 'order_payment_status', $payment_status );

		if ( $payment_status === 'paid' ) {
			\update_post_meta( $order_id, 'checkout_data', $payment );
		}

		$error_object = array(
			'error_message' => esc_html__( 'Complete the payment to place the order', 'myd-delivery-integration-sumup' ),
		);

		return $payment_status !== 'paid' ? $error_object : $value;
	}

	/**
	 * Add settings to options
	 *
	 * @return void
	 */
	public function add_settings() {
		include_once SUMUPMYD_PLUGIN_PATH . 'templates/settings.php';
	}

	/**
	 * Register settings
	 *
	 * @return array
	 */
	public function register_settings( $settings ) {
		$settings[] = array(
			'name' => 'sumupmyd-merchant-id',
			'option_group' => 'fmd-settings-group',
			'args' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

		$settings[] = array(
			'name' => 'sumupmyd-api-key',
			'option_group' => 'fmd-settings-group',
			'args' => array(),
		);

		$settings[] = array(
			'name' => 'sumupmyd-email',
			'option_group' => 'fmd-settings-group',
			'args' => array(),
		);

		return $settings;
	}

	/**
	 * Load required files
	 *
	 * @since 1.2
	 * @return void
	 */
	public function set_required_files() {
		include_once SUMUPMYD_PLUGIN_PATH . 'includes/class-checkout.php';
	}

	/**
	 * Enqueu admin styles/scripts
	 *
	 * @since 1.2
	 * @return void
	 */
	public function enqueue_admin_scripts() {}

	/**
	 * Enqueue front end styles/scripts
	 *
	 * @since 1.2
	 * @return void
	 */
	public function enqueue_frondend_scripts() {
		\wp_register_script( 'sumup_gateway_sdk', 'https://gateway.sumup.com/gateway/ecom/card/v2/sdk.js', array(), SUMUPMYD_CURRENT_VERSION, false );
		\wp_enqueue_script( 'sumup_gateway_sdk' );

		\wp_register_script( 'sumup_myd_paymnet', SUMUPMYD_PLUGIN_URL . 'assets/js/frontend.min.js', array(), SUMUPMYD_CURRENT_VERSION, false );
		\wp_enqueue_script( 'sumup_myd_paymnet' );

		\wp_register_style( 'sumup_myd_style', SUMUPMYD_PLUGIN_URL . 'assets/css/style.min.css', array(), SUMUPMYD_CURRENT_VERSION );
		\wp_enqueue_style( 'sumup_myd_style' );
	}

	/**
	 * Admin notice PHP version fail
	 *
	 * @since 1.0
	 */
	public function require_myd_plugin_message() {
		$message = sprintf(
			esc_html__( '%1$s requires MyD Delivery Pro plugin installed and activated.', 'myd-delivery-integration-sumup' ),
			'<strong>' . esc_html( SUMUPMYD_PLUGIN_NAME ) . '</strong>'
		);

		$html_message = sprintf( '<div class="notice notice-error"><p>%1$s</p></div>', $message );
		echo wp_kses_post( $html_message );
	}

	/**
	 * Admin notice PHP version fail
	 *
	 * @since 1.0
	 */
	public function require_myd_plugin_version_message() {
		$message = sprintf(
			esc_html__( '%1$s requires MyD Delivery Pro plugin in the version 2.2.12 or greater. If the update is not available to you, contact the %2$s.', 'myd-delivery-integration-sumup' ),
			'<strong>' . esc_html( SUMUPMYD_PLUGIN_NAME ) . '</strong>',
			'<a href="https://myddelivery.com/support/premium-support/" target="_blank">' . esc_html__( 'plugin support here' ) . '</a>'
		);

		$html_message = sprintf( '<div class="notice notice-error"><p>%1$s</p></div>', $message );
		echo wp_kses_post( $html_message );
	}

	/**
	 * Settings generic error message
	 *
	 * @since 1.0
	 */
	public function settings_generic_error_message() {
		$message = esc_html__( 'Check SumUp payment settings. Something went wrong while trying validate it.', 'myd-delivery-integration-sumup' );

		$html_message = sprintf( '<div class="notice notice-error"><p>%1$s</p></div>', $message );
		echo wp_kses_post( $html_message );
	}
}
