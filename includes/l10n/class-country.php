<?php

namespace MydPro\Includes\l10n;

use MydPro\Includes\l10n\Myd_Countries;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to define country and their settings details
 */
class Myd_Country {
	/**
	 * Country name
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * Country code
	 *
	 * @var string
	 */
	protected $code;

	/**
	 * Country calling code (DDI)
	 *
	 * @var number
	 */
	protected $calling_code;

	/**
	 * Country currency
	 *
	 * @var string
	 */
	protected string $currency;

	/**
	 * Country currency symbol
	 *
	 * @var string
	 */
	protected string $currency_symbol;

	/**
	 * Class contruct
	 */
	public function __construct( string $country_name = 'Brazil' ) {
		if ( empty( $country_name ) ) {
			$country_name = 'Brazil';
		}

		$countries = Myd_Countries::get_countries();
		if ( ! isset( $countries[ $country_name ] ) ) {
			throw new \Exception( 'Invalid country name' );
			return;
		}

		$this->name = $countries[ $country_name ]['name'];
		$this->code = $countries[ $country_name ]['code'];
		$this->calling_code = $countries[ $country_name ]['calling_code'];
		$this->currency = $countries[ $country_name ]['currency'];
		$this->currency_symbol = $countries[ $country_name ]['currency_symbol'];
	}

	/**
	 * Get country code
	 *
	 * @return string
	 */
	public function get_country_code() {
		return $this->code;
	}
}
