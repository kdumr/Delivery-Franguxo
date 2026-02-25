<?php

namespace MydPro\Includes;

use MydPro\Includes\Myd_Currency;
use MydPro\Includes\l10n\Myd_Country;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store data static class
 *
 * @since 1.9.5
 */
class Store_Data {
	/**
	 * Store data definitions
	 *
	 * @since 1.9.5
	 */
	protected static $store_data = [];

	/**
	 * TEMP. VARIABLE TO START A FEATURE AND CONTROLE THE RENDER ELEMENTS
	 *
	 * @since 1.9.41
	 */
	public static $template_dependencies_loaded = false;

	/**
	 * Set store data
	 *
	 * @since 1.9.5
	 */
	public static function set_store_data() {
		$country_option = get_option( 'fdm-business-country' );
		$country = new Myd_Country( $country_option );
		$country_code = $country->get_country_code();

		$store_data = [
			'name' => get_option( 'fdm-business-name' ),
			'whatasapp' => get_option( 'myd-business-whatsapp' ),
			'email' => get_option( 'myd-business-mail' ),
			'country' => $country_option,
			'country_code' => $country_code,
			'operation_mode' => '',
			'delivery_time' => get_option( 'fdm-estimate-time-delivery' ),
			'delivery_mode' => get_option( 'myd-delivery-mode' ),
			'delivery_options' => get_option( 'myd-delivery-mode-options' ),
			'delivery_hours' => get_option( 'myd-delivery-time' ),
			'force_open_close_store' => get_option( 'myd-delivery-force-open-close-store' ),
			'minimum_order' => get_option( 'myd-option-minimum-price' ),
			'auto_redirect' => get_option( 'myd-option-redirect-whatsapp' ),
			'currency_simbol' => Myd_Currency::get_currency_symbol(),
			'number_decimals' => get_option( 'fdm-number-decimal' ),
			'decimal_separator' => get_option( 'fdm-decimal-separator' ),
			'cash_payment' => get_option( 'fdm-payment-in-cash' ),
			'print_size' => get_option( 'fdm-print-size' ),
			'print_font_size' => get_option( 'fdm-print-font-size' ),
			'product_categories' => get_option( 'fdm-list-menu-categories' ),
		];

		self::$store_data = $store_data;
	}

	/**
	 * Get store data
	 *
	 * @since 1.9.5
	 * @param string $data
	 */
	public static function get_store_data( $data = '' ) {
		if ( empty( $data ) ) {
			return self::$store_data;
		}

		if ( array_key_exists( $data, self::$store_data ) ) {
			return self::$store_data[ $data ];
		}
	}

	/**
	 * Verifica se a loja está aberta considerando o forçado e o horário configurado
	 */
	public static function is_store_open() {
		$force = get_option('myd-delivery-force-open-close-store');
		if ($force === 'open') return true;
		if ($force === 'close') return false;
		$hours = get_option('myd-delivery-time');
		if (!is_array($hours)) return false;
		// Usa o timezone do WordPress
		if (function_exists('wp_timezone')) {
			$timezone = wp_timezone();
			$dt = new \DateTime('now', $timezone);
		} else {
			$timezone_string = get_option('timezone_string');
			$dt = new \DateTime('now', $timezone_string ? new \DateTimeZone($timezone_string) : null);
		}
		$day = strtolower($dt->format('l'));
		if (!isset($hours[$day])) return false;
		$current = $dt->format('H:i');
		foreach ($hours[$day] as $interval) {
			if (empty($interval['start']) || empty($interval['end'])) continue;
			if ($current >= $interval['start'] && $current <= $interval['end']) return true;
		}
		return false;
	}

	/**
	 * Retorna o horário de funcionamento de hoje formatado
	 */
	public static function get_today_hours() {
		$hours = get_option('myd-delivery-time');
		if (!is_array($hours)) return '';

		if (function_exists('wp_timezone')) {
			$timezone = wp_timezone();
			$dt = new \DateTime('now', $timezone);
		} else {
			$timezone_string = get_option('timezone_string');
			$dt = new \DateTime('now', $timezone_string ? new \DateTimeZone($timezone_string) : null);
		}

		$day_key = strtolower($dt->format('l'));
		
		$days_map = [
			'monday'    => 'segunda-feira',
			'tuesday'   => 'terça-feira',
			'wednesday' => 'quarta-feira',
			'thursday'  => 'quinta-feira',
			'friday'    => 'sexta-feira',
			'saturday'  => 'sábado',
			'sunday'    => 'domingo'
		];

		$day_name = $days_map[$day_key] ?? $day_key;
		
		if (!isset($hours[$day_key]) || empty($hours[$day_key])) {
			return $day_name . ', hoje estamos fechados';
		}

		$intervals = [];
		foreach ($hours[$day_key] as $interval) {
			if (empty($interval['start']) || empty($interval['end'])) continue;
			$intervals[] = 'das ' . $interval['start'] . ' às ' . $interval['end'];
		}

		if (empty($intervals)) {
			return $day_name . ', hoje estamos fechados';
		}

		return $day_name . ', ' . implode(' e ', $intervals);
	}
}
