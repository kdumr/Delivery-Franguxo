<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Facebook_CAPI {
	public static function register_routes() {
		add_action('rest_api_init', function() {
			register_rest_route('myd/v1', '/fb-capi', array(
				'methods' => 'POST',
				'callback' => array(__CLASS__, 'handle_request'),
				'permission_callback' => '__return_true',
			));
		});
	}

	public static function handle_request(\WP_REST_Request $request) {
		$pixel_id = get_option('myd_facebook_pixel_id', '');
		$access_token = get_option('myd_facebook_capi_token', '');
		if ( empty($pixel_id) || empty($access_token) ) {
			return new \WP_Error('fb_capi_not_configured', 'Facebook CAPI not configured', array('status' => 400));
		}

		$body = $request->get_json_params();
		if ( ! is_array($body) ) $body = array();

		// Build event data
		$event_name = isset($body['event_name']) ? sanitize_text_field($body['event_name']) : (isset($body['event']) ? sanitize_text_field($body['event']) : 'Purchase');
		$event_time = isset($body['event_time']) ? intval($body['event_time']) : time();
		$event_id = isset($body['event_id']) ? sanitize_text_field($body['event_id']) : wp_generate_password(16, false, false);

		$event_source_url = isset($body['event_source_url']) ? esc_url_raw($body['event_source_url']) : (isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : home_url());

		// user_data passed from client (hashed values) or empty
		$user_data = isset($body['user_data']) && is_array($body['user_data']) ? $body['user_data'] : array();

		// prefer fbp/fbc provided by client, otherwise try cookies
		$fbp = isset($body['fbp']) ? sanitize_text_field($body['fbp']) : (isset($_COOKIE['_fbp']) ? sanitize_text_field($_COOKIE['_fbp']) : '');
		$fbc = isset($body['fbc']) ? sanitize_text_field($body['fbc']) : (isset($_COOKIE['_fbc']) ? sanitize_text_field($_COOKIE['_fbc']) : '');

		// Add any client-provided contents/custom_data
		$custom_data = isset($body['custom_data']) && is_array($body['custom_data']) ? $body['custom_data'] : array();
		$value = isset($body['value']) ? floatval($body['value']) : (isset($custom_data['value']) ? floatval($custom_data['value']) : 0.0);
		$currency = isset($body['currency']) ? sanitize_text_field($body['currency']) : (isset($custom_data['currency']) ? sanitize_text_field($custom_data['currency']) : get_option('myd_facebook_currency', 'BRL'));

		$event = array(
			'event_name' => $event_name,
			'event_time' => $event_time,
			'event_id' => $event_id,
			'event_source_url' => $event_source_url,
			'action_source' => 'website',
			'user_data' => array(),
			'custom_data' => array(),
		);

		// Merge hashed user_data (client sends hashed values for email, phone, fn, ln, etc.)
		foreach ($user_data as $k => $v) {
			// keep as-is (assumed hashed already)
			$event['user_data'][ $k ] = $v;
		}

		// Attach fbp/fbc if available (these are not hashed)
		if ( ! empty($fbp) ) $event['user_data']['fbp'] = $fbp;
		if ( ! empty($fbc) ) $event['user_data']['fbc'] = $fbc;

		// Server can add IP and user agent
		$client_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		$client_ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		if ( ! empty($client_ip) ) $event['user_data']['client_ip_address'] = $client_ip;
		if ( ! empty($client_ua) ) $event['user_data']['client_user_agent'] = $client_ua;

		// Add custom_data
		if ( isset($body['contents']) ) {
			$event['custom_data']['contents'] = $body['contents'];
		}
		if ( isset($body['content_type']) ) $event['custom_data']['content_type'] = sanitize_text_field($body['content_type']);
		if ( isset($body['currency']) ) $event['custom_data']['currency'] = sanitize_text_field($body['currency']);
		if ( isset($body['value']) ) $event['custom_data']['value'] = floatval($body['value']);
		if ( isset($custom_data) && is_array($custom_data) ) {
			// merge other keys
			foreach ($custom_data as $k => $v) {
				$event['custom_data'][ $k ] = $v;
			}
		}

		$payload = array('data' => array( $event ));

		// Allow an optional test_event_code (useful for Events Manager test tool)
		$test_event_code = '';
		if ( isset($body['test_event_code']) && ! empty($body['test_event_code']) ) {
			$test_event_code = sanitize_text_field( $body['test_event_code'] );
		} else {
			// optional global option (if you want to persist a test code)
			$test_event_code = get_option('myd_facebook_capi_test_event_code', '');
		}

		// Send to Facebook
		$endpoint = sprintf('https://graph.facebook.com/v16.0/%s/events?access_token=%s', rawurlencode($pixel_id), rawurlencode($access_token));
		if ( $test_event_code ) {
			$endpoint .= '&test_event_code=' . rawurlencode( $test_event_code );
		}
		$args = array(
			'headers' => array('Content-Type' => 'application/json'),
			'body' => wp_json_encode($payload),
			'timeout' => 15,
		);

		// Optionally log request payload when WP_DEBUG is enabled (avoid logging raw PII in production)
		if ( defined('WP_DEBUG') && WP_DEBUG ) {
			error_log('fb-capi: endpoint=' . $endpoint . ' payload=' . wp_json_encode($payload));
		}

		$response = wp_remote_post( $endpoint, $args );
		if ( is_wp_error( $response ) ) {
			if ( defined('WP_DEBUG') && WP_DEBUG ) {
				error_log('fb-capi: request failed: ' . $response->get_error_message());
			}
			return new \WP_Error('fb_capi_request_failed', $response->get_error_message(), array('status' => 500));
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( defined('WP_DEBUG') && WP_DEBUG ) {
			error_log('fb-capi: response_code=' . intval($code) . ' body=' . $body);
		}

		return rest_ensure_response( array( 'code' => $code, 'body' => json_decode($body, true) ) );
	}
}
