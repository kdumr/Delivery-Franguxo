<?php

namespace MydPro\Includes\Push;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Push_Notifier {
	public static function notify( $myd_customer_id, $order_id = null, $status = null ) {
		$push_url = get_option( 'myd_push_server_url', '' );
		$secret = get_option( 'myd_push_secret', '' );
		if ( empty( $push_url ) || empty( $secret ) ) {
			return false;
		}

		// Gerar JWT token
		$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
		$payload = json_encode(['myd_customer_id' => $myd_customer_id, 'iat' => time(), 'exp' => time() + 86400]); // 24h
		$header_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
		$payload_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
		$signature = hash_hmac('sha256', $header_encoded . "." . $payload_encoded, $secret, true);
		$signature_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
		$token = $header_encoded . "." . $payload_encoded . "." . $signature_encoded;

		$body = array(
			'myd_customer_id' => $myd_customer_id,
			'order_id' => $order_id,
			'status' => $status,
		);
		$args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $token
			),
			'body' => wp_json_encode($body),
			'timeout' => 5,
		);
		$response = wp_remote_post( rtrim($push_url, '/') . '/notify', $args );
		if ( is_wp_error( $response ) ) { return false; }
		$code = wp_remote_retrieve_response_code( $response );
		return ($code >= 200 && $code < 300);
	}

	public static function notify_store( $open ) {
		$push_url = get_option( 'myd_push_server_url', '' );
		$secret = get_option( 'myd_push_secret', '' );
		if ( empty( $push_url ) || empty( $secret ) ) {
			return false;
		}

		// Gerar JWT token (sem myd_customer_id)
		$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
		$payload = json_encode(['iat' => time(), 'exp' => time() + 86400]); // 24h
		$header_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
		$payload_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
		$signature = hash_hmac('sha256', $header_encoded . "." . $payload_encoded, $secret, true);
		$signature_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
		$token = $header_encoded . "." . $payload_encoded . "." . $signature_encoded;

		$body = array(
			'open' => $open ? true : false,
		);
		$args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $token
			),
			'body' => wp_json_encode($body),
			'timeout' => 5,
		);
		$response = wp_remote_post( rtrim($push_url, '/') . '/notify/store', $args );
		if ( is_wp_error( $response ) ) { return false; }
		$code = wp_remote_retrieve_response_code( $response );
		return ($code >= 200 && $code < 300);
	}
}
