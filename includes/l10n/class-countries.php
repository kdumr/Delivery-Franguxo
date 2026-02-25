<?php

namespace MydPro\Includes\l10n;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to define country and their settings details
 */
class Myd_Countries {
	/**
	 * List of available countries and their data
	 *
	 * @var array
	 */
	public static $countries = array();

	/**
	 * Static function to get all available countries and their data.
	 *
	 * @return array
	 */
	public static function get_countries() {
		if ( empty( self::$countries ) ) {
			include MYD_PLUGIN_PATH . 'includes/l10n/countries-list.php';
			self::$countries = $countries_list;
		}

		return self::$countries;
	}
}
