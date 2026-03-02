<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gemini_API {

	/**
	 * Envia mensagem para o Google Gemini e retorna a resposta gerada.
	 *
	 * @param string $user_message A mensagem recebida do cliente no WhatsApp.
	 * @return string|false A resposta em texto do Gemini ou falso em caso de erro.
	 */
	public static function generate_response( $user_message ) {
		$enabled = get_option( 'gemini_enabled', 'no' );
		if ( 'yes' !== $enabled ) {
			return false;
		}

		$api_key = get_option( 'gemini_api_key' );
		if ( empty( $api_key ) ) {
			return false;
		}

		$system_prompt = get_option( 'gemini_system_prompt' );
		
		// Utilizamos o modelo recomendado pelo Google atualmente: gemini-2.0-flash
		$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key;

		$contents = [];
		
		// Se existir System Prompt configurado, enviamos como papel do "user",
		// mas como context/systemInstruction (suportado pela v1beta).
		$system_instruction = null;
		if ( ! empty( $system_prompt ) ) {
			$system_instruction = [
				'parts' => [
					[ 'text' => $system_prompt ]
				]
			];
		}

		// A mensagem atual do usuário
		$contents[] = [
			'role' => 'user',
			'parts' => [
				[ 'text' => $user_message ]
			]
		];

		$body = [
			'contents' => $contents,
			'generationConfig' => [
				'temperature' => 0.7,
				'maxOutputTokens' => 800,
			]
		];

		if ( $system_instruction ) {
			$body['systemInstruction'] = $system_instruction;
		}

		$args = [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 15,
		];

		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Gemini API Erro WP: ' . $response->get_error_message() );
			}
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body_response = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Gemini API Erro HTTP ' . $code . ': ' . print_r( $body_response, true ) );
			}
			return false;
		}

		$data = json_decode( $body_response, true );

		if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return $data['candidates'][0]['content']['parts'][0]['text'];
		}

		return false;
	}
}
