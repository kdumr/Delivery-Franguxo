<?php

namespace MydPro\Includes\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Evolution_Api {
	public function __construct() {
		\add_action( 'wp_ajax_evolution_api_test', array( $this, 'test_api' ) );
	}

	public function test_api() {
		$url = isset($_POST['url']) ? sanitize_text_field($_POST['url']) : '';
		$key = isset($_POST['key']) ? sanitize_text_field($_POST['key']) : '';
		$instance = isset($_POST['instance']) ? sanitize_text_field($_POST['instance']) : '';
		$phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

		if (!$url || !$key || !$instance || !$phone) {
			echo json_encode(['message' => 'Preencha todos os campos obrigatórios.']);
			wp_die();
		}

		// Verifica conexão da instância
		$connect_url = trailingslashit($url) . 'instance/connect/' . $instance;
		$auth_header = $key;
		if (stripos($key, 'Bearer ') !== 0) {
			$auth_header = 'Bearer ' . $key;
		}
		$response = wp_remote_get($connect_url, [
			'headers' => [
				'Authorization' => $auth_header
			]
		]);
		$status = wp_remote_retrieve_response_code($response);
		if ($status === 404) {
			echo json_encode(['message' => 'Instância não encontrada.']);
			wp_die();
		}
		if ($status === 401) {
			echo json_encode(['message' => 'API Key inválida.']);
			wp_die();
		}
		if ($status !== 200) {
			echo json_encode(['message' => 'Erro ao conectar à API.']);
			wp_die();
		}

		// Envia mensagem de teste
		$send_url = trailingslashit($url) . 'message';
		$args = [
			'headers' => [
				'apikey' => $key,
				'Content-Type' => 'application/json',
			],
			'body' => json_encode([
				'number' => $phone,
				'text' => 'Teste API Evolution',
			]),
			'timeout' => 20
		];
		$send_response = wp_remote_post($send_url, $args);
		$send_status = wp_remote_retrieve_response_code($send_response);
		if ($send_status === 200) {
			echo json_encode(['message' => 'Mensagem enviada com sucesso!']);
		} else {
			echo json_encode(['message' => 'Falha ao enviar mensagem.']);
		}
		wp_die();
	}
}