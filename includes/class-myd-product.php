<?php

namespace MydPro\Includes;

use MydPro\Includes\Myd_Store_Formatting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product class
 */
class Myd_product {
	/**
	 * Format price
	 */
	public function get_price( $price ) {
		if ( ! empty( $price ) ) {
			return Myd_Store_Formatting::format_price( $price );
		} else {
			return;
		}
	}

	/**
	 * Get currency
	 */
	public function get_currency() {
		return Store_Data::get_store_data( 'currency_simbol' );
	}
}
