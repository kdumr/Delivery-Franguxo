<?php
namespace MydPro\Includes;
if (!defined('ABSPATH')) exit;
class Printing_Service {
    private static $instance;
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('woocommerce_order_status_completed', [$this, 'send_order_to_api'], 10, 1);
    }
    public function register_routes() {
        register_rest_route('myd-delivery/v1', '/print-orders', [
            'methods' => 'POST',
            'callback' => [$this, 'receive_order'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
        register_rest_route('myd-delivery/v1', '/pending-orders', [
            'methods' => 'GET',
            'callback' => [$this, 'get_pending_orders'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }
    public function check_permissions($request) {
        $token = $request->get_header('Authorization');
        $saved_token = get_option('myd_printing_api_token');
        if (empty($token) || empty($saved_token) || 'Bearer ' . $saved_token !== $token) {
            return new \WP_Error('rest_forbidden', 'Token inválido.', ['status' => 403]);
        }
        return true;
    }
    public function receive_order($request) {
        $orders = get_transient('myd_pending_orders') ?: [];
        $orders[] = $request->get_json_params();
        set_transient('myd_pending_orders', $orders, DAY_IN_SECONDS);
        return new \WP_REST_Response(['status' => 'sucesso'], 200);
    }
    public function get_pending_orders() {
        $orders = get_transient('myd_pending_orders');
        delete_transient('myd_pending_orders');
        return new \WP_REST_Response($orders ?: [], 200);
    }
    public function send_order_to_api($order_id) {
        $api_url = get_option('myd_printing_api_url');
        $token = get_option('myd_printing_api_token');
        if (empty($api_url) || empty($token)) return;
        $order = wc_get_order($order_id);
        if (!$order) return;
        $products = [];
        foreach ($order->get_items() as $item) {
            $products[] = ['name' => $item->get_name(), 'quantity' => $item->get_quantity()];
        }
        $data = ['order_id' => $order_id, 'total' => $order->get_total(), 'products' => $products];
        wp_remote_post($api_url, [
            'method' => 'POST',
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json; charset=utf-8'],
            'body' => json_encode($data),
            'data_format' => 'body',
        ]);
    }
}