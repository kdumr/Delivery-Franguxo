<?php

namespace SumupMyd\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to create Check on SumUp
 */
class Checkout {
	/**
	 * SumUp API Key
	 *
	 * @var string
	 */
	private static $api_key;

	/**
	 * SumUp Merchant mail
	 *
	 * @var string
	 */
	private static $merchant_mail;

	/**
	 * SumUp Merchant ID
	 *
	 * @var string
	 */
	private static $merchant_id;

	/**
	 * SumUp Checkout ID
	 *
	 * @var string
	 */
	public static $id = false;

	/**
	 * Create check on SumUp
	 *
	 * @param array $order_data
	 *
	 * @return array
	 */
	public static function create( $order_data ) {
		if ( empty( self::get_merchant_id() ) ||
			empty( self::get_merchant_mail() ) ||
			empty( self::get_api_key() ) ) {
				return array(
					'error' => 'Missing configuration',
					'error_message' => 'Check SumUp payment settings.',
				);
		}

		$checkout_data = array(
			'checkout_reference' => 'MYD_DELIVERY_' . $order_data['data']->id . '/' . time(),
			'amount' => $order_data['data']->total,
			'currency' => $order_data['currency_code'] ?? 'BRL',
			'merchant_code' => self::get_merchant_id(),
			'pay_to_email' => self::get_merchant_mail(),
			'description' => 'MyD Delivery #' . $order_data['data']->id . '/' . time(),
			'redirect_url' => \site_url() . '?cartvalidation=true&order_id=' . $order_data['data']->id,
			'return_url' => \site_url(),
		);

		$response = wp_remote_post( 'https://api.sumup.com/v0.1/checkouts',
			array(
				'body'    => json_encode( $checkout_data ),
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept' => 'application/json',
					'Authorization' => 'Bearer ' . self::get_api_key(),
				),
			)
		);

		return ! is_wp_error( $response ) && isset( $response['body'] ) ? json_decode( $response['body'], true ) : array();
	}

	/**
	 * Get checkout based on ID
	 *
	 * @param string $checkout_id
	 *
	 * @return array
	 */
	public static function get( $checkout_id = '' ) {
		if ( empty( self::get_api_key() ) ) {
				return array(
					'error' => 'Missing configuration',
					'error_message' => 'SumUp API Key is empty or wrong. Check the settings.',
				);
		}

		$response = wp_remote_get( 'https://api.sumup.com/v0.1/checkouts/' . $checkout_id,
			array(
				'headers' => array(
					'Accept' => 'application/json',
					'Authorization' => 'Bearer ' . self::get_api_key(),
				),
			)
		);

		return ! is_wp_error( $response ) && isset( $response['body'] ) ? json_decode( $response['body'], true ) : array();
	}

	/**
	 * Get API Key
	 *
	 * @return string
	 */
	private static function get_api_key() {
		if ( empty( self::$api_key ) ) {
			self::$api_key = \get_option( 'sumupmyd-api-key' );
		}

		return self::$api_key;
	}

	/**
	 * Get API Key
	 *
	 * @return string
	 */
	private static function get_merchant_id() {
		if ( empty( self::$merchant_id ) ) {
			self::$merchant_id = \get_option( 'sumupmyd-merchant-id' );
		}

		return self::$merchant_id;
	}

	/**
	 * Get API Key
	 *
	 * @return string
	 */
	private static function get_merchant_mail() {
		if ( empty( self::$merchant_mail ) ) {
			self::$merchant_mail = \get_option( 'sumupmyd-email' );
		}

		return self::$merchant_mail;
	}
}
