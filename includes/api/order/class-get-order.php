<?php

namespace MydPro\Includes\Api\Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API endpoint to get order data.
 */
class Get_Order {
	/**
	 * Construct the class.
	 */
	public function __construct () {
		add_action( 'rest_api_init', [ $this, 'register_order_routes' ] );
	}

	/**
	 * Register plugin routes
	 */
	public function register_order_routes() {
		\register_rest_route(
			'myd-delivery/v1',
			'/order',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_order_data' ],
					'permission_callback' => '__return_true',
					'args' => $this->get_parameters(),
				),
			)
		);

		// simple existence check by order id
		\register_rest_route(
			'myd-delivery/v1',
			'/order/exists',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'order_exists' ],
					'permission_callback' => '__return_true',
				),
			)
		);

		// count orders for current user with specified statuses
		register_rest_route(
			'myd-delivery/v1',
			'/order/count',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'order_count_for_current_user' ],
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	public function permission_is_logged() {
		return is_user_logged_in();
	}

	/**
	 * Return count of orders for current user with specific statuses
	 */
	public function order_count_for_current_user( $request ) {
		$user_id = get_current_user_id();
		// allow passing myd_customer_id as fallback (from frontend sessionStorage)
		if ( ! $user_id ) {
			$maybe = $request->get_param('myd_customer_id');
			if ( $maybe && is_scalar( $maybe ) ) {
				$user_id = intval( $maybe );
			}
		}
		if ( ! $user_id ) {
			return new \WP_REST_Response( array( 'count' => 0 ), 200 );
		}

		$desired_statuses = array( 'new', 'confirmed', 'waiting', 'in-delivery' );

		$meta_or = array( 'relation' => 'OR' );
		foreach ( $desired_statuses as $s ) {
			$meta_or[] = array(
				'key' => 'order_status',
				'value' => $s,
				'compare' => '=',
			);
		}

		$args = array(
			'post_type' => 'mydelivery-orders',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => 'myd_customer_id',
					'value' => $user_id,
					'compare' => '=',
				),
				$meta_or,
			),
			'fields' => 'ids',
			'no_found_rows' => true,
		);

		$query = new \WP_Query( $args );
		$count = is_array( $query->posts ) ? count( $query->posts ) : 0;

		return new \WP_REST_Response( array( 'count' => (int) $count ), 200 );
	}

	public function order_exists( $request ) {
		$order_id = $request->get_param('order_id');
		$order_id = is_scalar($order_id) ? intval($order_id) : 0;
		if ( $order_id <= 0 ) {
			return new \WP_REST_Response(['error' => 'invalid_order_id'], 400);
		}
		$post = get_post( $order_id );
		if ( ! $post || $post->post_type !== 'mydelivery-orders' ) {
			return new \WP_REST_Response(['exists' => false], 404);
		}
		return new \WP_REST_Response(['exists' => true], 200);
	}

	/**
	 * Check orders and retrive status
	 */
	public function get_order_data( $request ) {
		$order_id = base64_decode( $request['hash'] );
		$data_response = array(
			'status' => get_post_meta( $order_id, 'order_status', true ),
		);
		return \wp_send_json_success( $data_response, 200 );
	}

	/**
	 * Define parameters
	 */
	public function get_parameters() {
		$args = array();

		$args['fields'] = array(
			'description' => esc_html__( 'The order fields to retrive', 'myd-delivery-pro' ),
			'type'        => 'string',
			'required' => true,
			'validate_callback' => array( $this, 'validate_parameter' ),
		);

		$args['hash'] = array(
			'description' => esc_html__( 'The order hash', 'myd-delivery-pro' ),
			'type'        => 'string',
			'required' => true,
			// 'validate_callback' => array( $this, 'validate_parameter' ),
		);

		return $args;
	}

	/**
	 * Validate parametes
	 */
	public function validate_parameter( $value, $request, $param ) {
		$allowed_parameters = array(
			'status',
		);

		if ( ! in_array( $value, $allowed_parameters ) ) {
			$error = array(
				'error_message' => esc_html__( 'Invalid or not allowed parameter', 'myd-delivery-pro' ),
			);
			return \wp_send_json_error( $error, 400 );
		}
	}
}

new Get_Order();
