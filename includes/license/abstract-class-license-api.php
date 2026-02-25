<?php

namespace MydPro\Includes\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class License API
 *
 * @since 1.9.7
 */
abstract class License_Api {
	/**
	 * Const SSK
	 *
	 * @since 1.9.7
	 */
	private const SSK = '5ea0bcedc059a9.97186140';

	/**
	 * Const lsu
	 *
	 * @since 1.9.7
	 */
	private const LSU = 'https://eduardovillao.me/';

	/**
	 * Action
	 *
	 * @since 1.9.7
	 */
	protected $action;

	/**
	 * License key
	 *
	 * @since 1.9.7
	 */
	protected $key;

	/**
	 * Site url
	 *
	 * @since 1.9.7
	 */
	protected $site_url;

	/**
	 * Response
	 *
	 * @since 1.9.7
	 */
	protected $response;

	/**
	 * Response status
	 *
	 * @since 1.9.7
	 */
	protected $response_status;

	/**
	 * Responde HTTP code
	 *
	 * @since 1.9.7
	 */
	protected $response_code;

	/**
	 * Prepare args
	 *
	 * @since 1.9.7
	 */
	public function prepare_args() {
		$this->site_url = site_url();

		$license_args = [
			'slm_action' => 'slm_' . $this->action,
			'secret_key' => self::SSK,
			'license_key' => $this->key,
			'item_reference' => urlencode( MYD_PLUGIN_NAME ),
		];

		if ( $this->action === 'activate' || $this->action === 'deactivate' ) {

			$license_args['registered_domain'] = $this->site_url;
		}

		return $license_args;
	}

	/**
	 * Fetch API
	 *
	 * @since 1.9.7
	 */
	public function fetch_api() {
		if ( empty( $this->key ) || $this->key === null ) {
			$this->response_status = 'error';
			$this->response = [
				'error' => [
					'error_type' => 'License empty',
					'message' => 'Please insert your license to continue.',
				],
			];
			return;
		}

		$license_args = $this->prepare_args();
		$query_api = esc_url_raw( add_query_arg( $license_args, self::LSU ) );
		$response = wp_remote_get( $query_api, [ 'timeout' => 40, 'sslverify' => true ] );
		$this->validate_response( $response );
	}

	/**
	 * Validate response
	 *
	 * @since 1.9.7
	 */
	public function validate_response( $response ) {
		if ( is_wp_error( $response ) ) {

			$this->response_status = 'error';
			$this->response = [
				'error' => [
					'error_type' => 'WP Error',
					'message' => 'WordPress Error: ' . $response->get_error_message(),
				],
			];

			return;
		}

		$this->response_code = $this->get_http_response_code( $response );

		if ( $this->is_http_error( $this->response_code ) ) {

			$this->response_status = 'error';
			$this->response = [
				'error' => [
					'error_type' => 'HTTP Error',
					'message' => 'Fetch API has an HTTP Error. Message: ' . $this->get_http_error_message( $this->response_code ) . '.',
				],
			];

			return;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $this->api_result_is_error( $response_body ) ) {

			$this->response_status = 'error';
			$this->response = [
				'error_type' => 'API result: Error',
				'message' => 'API result message: ' . $response_body['message'],
			];

			/**
			 * Temp code to check condition if license is already activated for this domains on plugin API.
			 */
			if ( preg_match( '/(.*License key already in use on)/', $response_body['message'] ) ) {
				$this->response_status = 'success';
				$this->response = $response_body;
			}

			return;
		}

		$this->response_status = 'success';
		$this->response = $response_body;
	}

	/**
	 * Check if API result is error
	 *
	 * @since 1.9.7
	 */
	public function api_result_is_error( $response ) {
		if ( isset( $response['result'] ) && $response['result'] !== 'success' ) {
			return true;
		}
	}

	/**
	 * Get response
	 *
	 * @since 1.9.7
	 */
	public function get_response() {
		return $this->response;
	}

	/**
	 * Get response
	 *
	 * @since 1.9.7
	 */
	public function get_response_status() {
		return $this->response_status;
	}

	/**
	 * Get response HTTP code
	 *
	 * @since 1.9.7
	 */
	public function get_http_response_code( $response ) {
		return wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Check HTTP error
	 *
	 * @param int $response_code
	 * @return boolean
	 * @since 1.9.7
	 *
	 * TODO: check intval in response_code
	 */
	public function is_http_error( $response_code ) {
		if ( $response_code !== 200 ) {
			return true;
		}
	}

	/**
	 * Get HTTP error message
	 *
	 * TODO: add error message reference
	 *
	 * @param $error_code
	 * @since 1.9.7
	 */
	public function get_http_error_message( $error_code ) {
		$http_errors = [
			'404' => 'Not found',
		];

		if ( isset( $http_errors[ $error_code ] ) ) {
			return $http_errors[ $error_code ];
		}
	}
}
