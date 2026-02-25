<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class with static methods to format Store
 */
class Myd_Store_Formatting {
	/**
	 * Formatitng price
	 *
	 * @param float $price
	 * @return float
	 * @since 1.9.5
	 */
	public static function format_price( $price ) {
		$price = floatval( $price );

		return number_format(
			$price,
			Store_Data::get_store_data( 'number_decimals' ),
			Store_Data::get_store_data( 'decimal_separator' ),
			'.'
		);
	}
}
