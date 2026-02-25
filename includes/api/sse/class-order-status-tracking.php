<?php

namespace MydPro\Includes\Api\Sse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API endpoint to track order status (SSE)
 */
class Order_Status_Tracking {
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
			'/order-status-tracking',
			array(
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => [ $this, 'order_status_tracking' ],
					'permission_callback' => '__return_true',
					'args' => $this->get_parameters(),
				),
			)
		);
	}

	/**
	 * Check orders and retrive status
	 */
	public function order_status_tracking( $request ) {
		header( 'Cache-Control: no-store' );
		header( 'Content-Type: text/event-stream' );
		header( 'X-Accel-Buffering: no' );
		header( 'Connection: keep-alive' );

		while ( true ) {
			$url_request = \add_query_arg(
				array(
					'hash' => $request['hash'],
					'fields' => 'status',
				),
				\home_url() . '/wp-json/myd-delivery/v1/order'
			);

			$response = \wp_remote_get(
				$url_request,
				array(
					'timeout' => 40,
					'sslverify' => false,
				)
			);

			$response_body = isset( $response['body'] ) ? json_decode( $response['body'], true ) : array();

			if ( isset( $response_body['success'] ) && $response_body['success'] === true ) {
				$event_response = array(
					'status' => isset( $response_body['data']['status'] ) ? $response_body['data']['status'] : '',
				);

				echo "event: order-status-update\n";
				echo 'data: ' . json_encode( $event_response );
				echo "\n\n";
			}

			if ( ob_get_level() > 0 ) {
				ob_end_flush();
			}
			flush();

			if ( connection_aborted() ) {
				break;
			}

			sleep( 5 );
		}
	}

	/**
	 * Define parameters
	 */
	public function get_parameters() {
		$args = array();

		$args['hash'] = array(
			'description' => esc_html__( 'The order hash', 'myd-delivery-pro' ),
			'type' => 'string',
			'required' => true,
		);

		return $args;
	}
}

new Order_Status_Tracking();
