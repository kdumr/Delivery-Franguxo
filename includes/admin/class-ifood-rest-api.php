<?php
namespace MydPro\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * iFood REST API Integration
 *
 * Registers WordPress REST endpoints for:
 * 1. Receiving orders from the Node.js backend (POST /create-order)
 * 2. Webhook fallback (POST /webhook) for direct iFood webhooks if needed
 *
 * Also hooks into settings save to push config to the backend.
 */
class Ifood_REST_API {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
        // Push config to backend whenever iFood settings change
        add_action( 'update_option_ifood_merchant_id',   [ $this, 'push_config_to_backend' ], 10, 2 );
        add_action( 'update_option_ifood_client_id',     [ $this, 'push_config_to_backend' ], 10, 2 );
        add_action( 'update_option_ifood_client_secret', [ $this, 'push_config_to_backend' ], 10, 2 );
        add_action( 'update_option_wp_ifood_api_secret', [ $this, 'push_config_to_backend' ], 10, 2 );
    }

    public function register_routes() {
        register_rest_route( 'myd-delivery/v1', '/ifood/create-order', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_create_order' ],
            'permission_callback' => [ $this, 'validate_backend_secret' ],
        ] );

        register_rest_route( 'myd-delivery/v1', '/ifood/status', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_status' ],
            'permission_callback' => function() { return current_user_can( 'manage_options' ); },
        ] );
    }

    /**
     * Validates the shared secret from the Node.js backend.
     */
    public function validate_backend_secret( $request ) {
        $stored_secret = get_option( 'wp_ifood_api_secret', '' );
        if ( empty( $stored_secret ) ) {
            return new \WP_Error( 'no_secret', 'WP API Secret not configured', [ 'status' => 503 ] );
        }
        $incoming = $request->get_header( 'X-MyD-Secret' );
        if ( $incoming !== $stored_secret ) {
            return new \WP_Error( 'unauthorized', 'Invalid secret', [ 'status' => 401 ] );
        }
        return true;
    }

    /**
     * Creates a WordPress order post from iFood order data.
     */
    public function handle_create_order( $request ) {
        $order = $request->get_json_params();

        if ( empty( $order['id'] ) ) {
            return new \WP_Error( 'invalid_order', 'Missing order id', [ 'status' => 400 ] );
        }

        $ifood_order_id = sanitize_text_field( $order['id'] );

        // Avoid duplicate creation
        $existing = get_posts( [
            'post_type'      => 'myd_order',
            'meta_key'       => '_ifood_order_id',
            'meta_value'     => $ifood_order_id,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ] );

        if ( ! empty( $existing ) ) {
            return rest_ensure_response( [
                'status'  => 'duplicate',
                'post_id' => $existing[0],
                'message' => 'Order already exists',
            ] );
        }

        // Build customer name
        $customer      = $order['customer'] ?? [];
        $customer_name = trim( ( $customer['name'] ?? '' ) . ' ' . ( $customer['lastName'] ?? '' ) );
        if ( empty( $customer_name ) ) $customer_name = 'Cliente iFood';

        // Build title
        $total        = $order['total']['orderAmount'] ?? 0;
        $total_fmt    = 'R$ ' . number_format( $total, 2, ',', '.' );
        $post_title   = sprintf( 'iFood #%s — %s — %s', substr($ifood_order_id, 0, 8), $customer_name, $total_fmt );

        // Create post
        $post_id = wp_insert_post( [
            'post_type'   => 'myd_order',
            'post_title'  => $post_title,
            'post_status' => 'publish',
            'meta_input'  => [
                '_ifood_order_id'      => $ifood_order_id,
                '_ifood_order_data'    => wp_json_encode( $order ),
                '_order_source'        => 'ifood',
                '_order_status'        => 'pending',
                '_customer_name'       => $customer_name,
                '_customer_phone'      => $customer['phone'] ?? '',
                '_customer_document'   => $customer['document'] ?? '',
                '_order_total'         => $total,
                '_order_type'          => $order['orderType'] ?? 'DELIVERY',
                '_order_created_at'    => $order['createdAt'] ?? current_time( 'mysql' ),
                '_delivery_address'    => wp_json_encode( $order['delivery']['deliveryAddress'] ?? [] ),
                '_order_items'         => wp_json_encode( $order['items'] ?? [] ),
                '_order_payments'      => wp_json_encode( $order['payments'] ?? [] ),
                '_ifood_merchant_id'   => $order['merchant']['id'] ?? '',
            ],
        ] );

        if ( is_wp_error( $post_id ) ) {
            return new \WP_Error( 'insert_failed', $post_id->get_error_message(), [ 'status' => 500 ] );
        }

        // Notify via Socket.io (if backend push server is running)
        $this->notify_socket( $post_id, $ifood_order_id );

        return rest_ensure_response( [
            'status'        => 'created',
            'post_id'       => $post_id,
            'ifood_order_id' => $ifood_order_id,
        ] );
    }

    /**
     * Returns iFood integration status info.
     */
    public function handle_status( $request ) {
        return rest_ensure_response( [
            'merchant_id_configured' => ! empty( get_option( 'ifood_merchant_id' ) ),
            'token_expiry'           => get_option( 'ifood_token_expiry', 'N/A' ),
            'wp_api_secret_set'      => ! empty( get_option( 'wp_ifood_api_secret' ) ),
        ] );
    }

    /**
     * Notify Node.js backend via Socket.io about a new iFood order.
     */
    private function notify_socket( $post_id, $ifood_order_id ) {
        $backend_url = get_option( 'myd_backend_url', '' );
        if ( empty( $backend_url ) ) return;

        $token = get_option( 'myd_backend_token', '' );
        if ( empty( $token ) ) return;

        wp_remote_post( trailingslashit( $backend_url ) . 'notify', [
            'timeout' => 5,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode( [
                'order_id' => $post_id,
                'status'   => 'pending',
                'source'   => 'ifood',
            ] ),
        ] );
    }

    /**
     * Push iFood configuration to Node.js backend when WP settings are saved.
     * Triggered by update_option hooks.
     */
    public function push_config_to_backend( $old_value = '', $new_value = '' ) {
        $backend_url    = get_option( 'myd_backend_url', '' );
        $backend_secret = get_option( 'myd_backend_push_secret', '' );

        if ( empty( $backend_url ) || empty( $backend_secret ) ) return;

        $payload = [
            'merchantId'   => get_option( 'ifood_merchant_id', '' ),
            'clientId'     => get_option( 'ifood_client_id', '' ),
            'clientSecret' => get_option( 'ifood_client_secret', '' ),
            'wpApiSecret'  => get_option( 'wp_ifood_api_secret', '' ),
            'wpBaseUrl'    => get_site_url(),
        ];

        $response = wp_remote_post( trailingslashit( $backend_url ) . 'config', [
            'timeout' => 10,
            'headers' => [
                'Content-Type'     => 'application/json',
                'X-Backend-Secret' => $backend_secret,
            ],
            'body' => wp_json_encode( $payload ),
        ] );

        if ( is_wp_error( $response ) ) {
            error_log( '[iFood] Failed to push config to backend: ' . $response->get_error_message() );
        } else {
            $code = wp_remote_retrieve_response_code( $response );
            error_log( "[iFood] Config pushed to backend — HTTP $code" );
        }
    }
}
