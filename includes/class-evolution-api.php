<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Evolution_API {
	public static function send_message( $number, $message ) {
		$api_url = get_option( 'evolution_api_url' );
		$api_key = get_option( 'evolution_api_key' );
		$instance = get_option( 'evolution_instance_name' );
		if ( empty( $api_url ) || empty( $api_key ) || empty( $instance ) ) {
			return false;
		}
		$number = preg_replace('/\D/', '', $number); // Remove caracteres não numéricos
		if (substr($number, 0, 2) !== '55') {
			$number = '55' . $number;
		}
		if (strlen($number) < 12) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('Evolution API: Número de telefone inválido: ' . $number);
			}
			return false;
		}
		$args = [
			'headers' => [
				'Content-Type' => 'application/json',
				'apikey' => $api_key,
			],
			'body' => json_encode([
				'number' => $number,
				'text' => $message,
			]),
			'timeout' => 5,
		];
		$response = wp_remote_post( trailingslashit( $api_url ) . 'message/sendText/' . $instance, $args );
		if ( is_wp_error( $response ) ) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('Evolution API: Erro WP: ' . $response->get_error_message());
			}
			return false;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ($code !== 201) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('Evolution API: Código HTTP inesperado: ' . $code . ' | Resposta: ' . print_r($response, true));
			}
			return false;
		}
		return true;
	}
}