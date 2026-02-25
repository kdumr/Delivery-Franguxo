<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to manage currency
 */
class Myd_Currency {
	/**
	 * Currency list
	 */
	protected static $currency_list = array();

	/**
	 * Currency code
	 *
	 * @since 1.9.35
	 */
	protected static $currency_code;

	/**
	 * Currency symbol
	 *
	 * @since 1.9.35
	 */
	protected static $currency_symbol;

	/**
	 * Get currency list
	 *
	 * @since 1.9.35
	 */
	public static function get_currency_list() {
		if ( ! empty( self::$currency_list ) ) {
			return self::$currency_list;
		}

		self::$currency_list = array(
			'AFA' => array(
				'name' => __( 'Afghan Afghani', 'myd-delivery-pro' ),
				'symbol' => '؋',
			),
			"ALL" => array(
				'name' => __( 'Albanian Lek', 'myd-delivery-pro' ),
				'symbol' => 'Lek',
			),
			"DZD" => array(
				'name' => __( 'Algerian Dinar', 'myd-delivery-pro' ),
				'symbol' => 'دج',
			),
			"AOA" => array(
				'name' => __( 'Angolan Kwanza', 'myd-delivery-pro' ),
				'symbol' => 'Kz',
			),
			"ARS" => array(
				'name' => __( 'Argentine Peso', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"AMD" => array(
				'name' => __( 'Armenian Dram', 'myd-delivery-pro' ),
				'symbol' => '֏',
			),
			"AWG" => array(
				'name' => __( 'Aruban Florin', 'myd-delivery-pro' ),
				'symbol' => 'ƒ',
			),
			"AUD" => array(
				'name' => __( 'Australian Dollar', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"AZN" => array(
				'name' => __( 'Azerbaijani Manat', 'myd-delivery-pro' ),
				'symbol' => 'm',
			),
			"BSD" => array(
				'name' => __( 'Bahamian Dollar', 'myd-delivery-pro' ),
				'symbol' => 'B$',
			),
			"BHD" => array(
				'name' => __( 'BahamiBahraini Dinar', 'myd-delivery-pro' ),
				'symbol' => '.د.ب',
			),
			"BDT" => array(
				'name' => __( 'Bangladeshi Taka', 'myd-delivery-pro' ),
				'symbol' => '৳',
			),
			"BBD" => array(
				'name' => __( 'Barbadian Dollar', 'myd-delivery-pro' ),
				'symbol' => 'Bds$',
			),
			"BYR" => array(
				'name' => __( 'Belarusian Ruble', 'myd-delivery-pro' ),
				'symbol' => 'Br',
			),
			"BEF" => array(
				'name' => __( 'Belgian Franc', 'myd-delivery-pro' ),
				'symbol' => 'fr',
			),
			"BZD" => array(
				'name' => __( 'Belize Dollar', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"BMD" => array(
				'name' => __( 'Bermudan Dollar', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"BTN" => array(
				'name' => __( 'Bhutanese Ngultrum', 'myd-delivery-pro' ),
				'symbol' => 'Nu.',
			),
			"BTC" => array(
				'name' => __( 'Bitcoin', 'myd-delivery-pro' ),
				'symbol' => '฿',
			),
			"BOB" => array(
				'name' => __( 'Bolivian Boliviano', 'myd-delivery-pro' ),
				'symbol' => 'Bs.'
			),
			"BAM" => array(
				'name' => __( 'Bosnia-Herzegovina Convertible Mark', 'myd-delivery-pro' ),
				'symbol' => 'KM',
			),
			"BWP" => array(
				'name' => __( 'Botswanan Pula', 'myd-delivery-pro' ),
				'symbol' => 'P',
			),
			"BRL" => array(
				'name' => __( 'Brazilian Real', 'myd-delivery-pro' ),
				'symbol' => 'R$',
			),
			"GBP" => array(
				'name' => __( 'British Pound Sterling', 'myd-delivery-pro' ),
				'symbol' => '£',
			),
			"BND" => array(
				'name' => __( 'Brunei Dollar', 'myd-delivery-pro' ),
				'symbol' => 'B$',
			),
			"BGN" => array(
				'name' => __( 'Bulgarian Lev', 'myd-delivery-pro' ),
				'symbol' => 'Лв.',
			),
			"BIF" => array(
				'name' => __( 'Burundian Franc', 'myd-delivery-pro' ),
				'symbol' => 'FBu',
			),
			"KHR" => array(
				'name' => __( 'Cambodian Riel', 'myd-delivery-pro' ),
				'symbol' => 'KHR',
			),
			"CAD" => array(
				'name' => __( 'Canadian Dollar', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"CVE" => array(
				'name' => __( 'Cape Verdean Escudo', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"KYD" => array(
				'name' => __( 'Cayman Islands Dollar', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"XOF" => array(
				'name' => __( 'CFA Franc BCEAO', 'myd-delivery-pro' ),
				'symbol' => 'CFA',
			),
			"XAF" => array(
				'name' => __( 'CFA Franc BEAC', 'myd-delivery-pro' ),
				'symbol' => 'FCFA',
			),
			"XPF" => array(
				'name' => __( 'CFP Franc', 'myd-delivery-pro' ),
				'symbol' => '₣',
			),
			"CLP" => array(
				'name' => __( 'Chilean Peso', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"CLF" => array(
				'name' => __( 'Chilean Unit of Account', 'myd-delivery-pro' ),
				'symbol' => 'CLF',
			),
			"CNY" => array(
				'name' => __( 'Chinese Yuan', 'myd-delivery-pro' ),
				'symbol' => '¥',
			),
			"COP" => array(
				'name' => __( 'Colombian Peso', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"KMF" => array(
				'name' => __( 'Comorian Franc', 'myd-delivery-pro' ),
				'symbol' => 'CF',
			),
			"CDF" => array(
				'name' => __( 'Congolese Franc', 'myd-delivery-pro' ),
				'symbol' => 'FC',
			),
			"CRC" => array(
				'name' => __( 'Costa Rican Colón', 'myd-delivery-pro' ),
				'symbol' => '₡',
			),
			"HRK" => array(
				'name' => __( 'Croatian Kuna', 'myd-delivery-pro' ),
				'symbol' => 'kn',
			),
			"CUC" => array(
				'name' => __( 'Cuban Convertible Peso', 'myd-delivery-pro' ),
				'symbol' => '$, CUC'
			),
			"CZK" => array(
				'name' => __( 'Czech Republic Koruna', 'myd-delivery-pro' ),
				'symbol' => 'Kč',
			),
			"DKK" => array(
				'name' => __( 'Danish Krone', 'myd-delivery-pro' ),
				'symbol' => 'Kr.',
			),
			"DJF" => array(
				'name' => __( 'Djiboutian Franc', 'myd-delivery-pro' ),
				'symbol' => 'Fdj',
			),
			"DOP" => array(
				'name' => __( 'Dominican Peso', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"XCD" => array(
				'name' => __( 'East Caribbean Dollar', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"EGP" => array(
				'name' => __( 'Egyptian Pound', 'myd-delivery-pro' ),
				'symbol' => 'ج.م',
			),
			"ERN" => array(
				'name' => __( 'Eritrean Nakfa', 'myd-delivery-pro' ),
				'symbol' => 'Nfk',
			),
			"EEK" => array(
				'name' => __( 'Estonian Kroon', 'myd-delivery-pro' ),
				'symbol' => 'kr',
			),
			"ETB" => array(
				'name' => __( 'Ethiopian Birr', 'myd-delivery-pro' ),
				'symbol' => 'Nkf',
			),
			"EUR" => array(
				'name' => __( 'Euro', 'myd-delivery-pro' ),
				'symbol' => '€',
			),
			"FKP" => array(
				'name' => __( 'Falkland Islands Pound', 'myd-delivery-pro' ),
				'symbol' => '£',
			),
			"FJD" => array(
				'name' => __( 'Fijian Dollar', 'myd-delivery-pro' ),
				'symbol' => 'FJ$',
			),
			"GMD" => array(
				'name' => __( 'Gambian Dalasi', 'myd-delivery-pro' ),
				'symbol' => 'D',
			),
			"GEL" => array(
				'name' => __( 'Georgian Lari', 'myd-delivery-pro' ),
				'symbol' => 'ლ'
			),
			"DEM" => array(
				'name' => __( 'German Mark', 'myd-delivery-pro' ),
				'symbol' => 'DM',
			),
			"GHS" => array(
				'name' => __( 'Ghanaian Cedi', 'myd-delivery-pro' ),
				'symbol' => 'GH₵',
			),
			"GIP" => array(
				'name' => __( 'Gibraltar Pound', 'myd-delivery-pro' ),
				'symbol' => '£',
			),
			"GRD" => array(
				'name' => __( 'Greek Drachma', 'myd-delivery-pro' ),
				'symbol' => '₯, Δρχ, Δρ',
			),
			"GTQ" => array(
				'name' => __( 'Guatemalan Quetzal', 'myd-delivery-pro' ),
				'symbol' => 'Q',
			),
			"GNF" => array(
				'name' => __( 'Guinean Franc', 'myd-delivery-pro' ),
				'symbol' => 'FG',
			),
			"GYD" => array(
				'name' => __( 'Guyanaese Dollar', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"HTG" => array(
				'name' => __( 'Haitian Gourde', 'myd-delivery-pro' ),
				'symbol' => 'G',
			),
			"HNL" => array(
				'name' => __( 'Honduran Lempira', 'myd-delivery-pro' ),
				'symbol' => 'L',
			),
			"HKD" => array(
				'name' => __( 'Hong Kong Dollar', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"HUF" => array(
				'name' => __( 'Hungarian Forint', 'myd-delivery-pro' ),
				'symbol' => 'Ft',
			),
			"ISK" => array(
				'name' => __( 'Icelandic Króna', 'myd-delivery-pro' ),
				'symbol' => 'kr',
			),
			"INR" => array(
				'name' => __( 'Indian Rupee', 'myd-delivery-pro' ),
				'symbol' => '₹',
			),
			"IDR" => array(
				'name' => __( 'Indonesian Rupiah', 'myd-delivery-pro' ),
				'symbol' => 'Rp',
			),
			"IRR" => array(
				'name' => __( 'Iranian Rial', 'myd-delivery-pro' ),
				'symbol' => '﷼',
			),
			"IQD" => array(
				'name' => __( 'Iraqi Dinar', 'myd-delivery-pro' ),
				'symbol' => 'د.ع',
			),
			"ILS" => array(
				'name' => __( 'Israeli New Sheqel', 'myd-delivery-pro' ),
				'symbol' => '₪',
			),
			"ITL" => array(
				'name' => __( 'Italian Lira', 'myd-delivery-pro' ),
				'symbol' => 'L,£',
			),
			"JMD" => array(
				'name' => __( 'Jamaican Dollar', 'myd-delivery-pro' ),
				'symbol' => 'J$',
			),
			"JPY" => array(
				'name' => __( 'Japanese Yen', 'myd-delivery-pro' ),
				'symbol' => '¥',
			),
			"JOD" => array(
				'name' => __( 'Jordanian Dinar', 'myd-delivery-pro' ),
				'symbol' => 'ا.د',
			),
			"KZT" => array(
				'name' => __( 'Kazakhstani Tenge', 'myd-delivery-pro' ),
				'symbol' => 'лв'
			),
			"KES" => array(
				'name' => "Kenyan Shilling",
				'symbol' => "KSh"
			),
			"KWD" => array(
				'name' => "Kuwaiti Dinar",
				'symbol' => "ك.د"
			),
			"KGS" => array(
				'name' => "Kyrgystani Som",
				'symbol' => "лв"
			),
			"LAK" => array(
				'name' => "Laotian Kip",
				'symbol' => "₭"
			),
			"LVL" => array(
				'name' => "Latvian Lats",
				'symbol' => "Ls"
			),
			"LBP" => array(
				'name' => "Lebanese Pound",
				'symbol' => "£"
			),
			"LSL" => array(
				'name' => "Lesotho Loti",
				'symbol' => "L"
			),
			"LRD" => array(
				'name' => "Liberian Dollar",
				'symbol' => "$"
			),
			"LYD" => array(
				'name' => "Libyan Dinar",
				'symbol' => "د.ل"
			),
			"LTC" => array(
				'name' => "Litecoin",
				'symbol' => "Ł"
			),
			"LTL" => array(
				'name' => "Lithuanian Litas",
				'symbol' => "Lt"
			),
			"MOP" => array(
				'name' => "Macanese Pataca",
				'symbol' => "$"
			),
			"MKD" => array(
				'name' => "Macedonian Denar",
				'symbol' => "ден"
			),
			"MGA" => array(
				'name' => "Malagasy Ariary",
				'symbol' => "Ar"
			),
			"MWK" => array(
				'name' => "Malawian Kwacha",
				'symbol' => "MK"
			),
			"MYR" => array(
				'name' => "Malaysian Ringgit",
				'symbol' => "RM"
			),
			"MVR" => array(
				'name' => "Maldivian Rufiyaa",
				'symbol' => "Rf"
			),
			"MRO" => array(
				'name' => "Mauritanian Ouguiya",
				'symbol' => "MRU"
			),
			"MUR" => array(
				'name' => "Mauritian Rupee",
				'symbol' => "₨"
			),
			"MXN" => array(
				'name' => "Mexican Peso",
				'symbol' => "$"
			),
			"MDL" => array(
				'name' => "Moldovan Leu",
				'symbol' => "L"
			),
			"MNT" => array(
				'name' => "Mongolian Tugrik",
				'symbol' => "₮"
			),
			"MAD" => array(
				'name' => "Moroccan Dirham",
				'symbol' => "MAD"
			),
			"MZM" => array(
				'name' => "Mozambican Metical",
				'symbol' => "MT"
			),
			"MMK" => array(
				'name' => "Myanmar Kyat",
				'symbol' => "K"
			),
			"NAD" => array(
				'name' => "Namibian Dollar",
				'symbol' => "$"
			),
			"NPR" => array(
				'name' => "Nepalese Rupee",
				'symbol' => "₨"
			),
			"ANG" => array(
				'name' => "Netherlands Antillean Guilder",
				'symbol' => "ƒ"
			),
			"TWD" => array(
				'name' => "New Taiwan Dollar",
				'symbol' => "$"
			),
			"NZD" => array(
				'name' => "New Zealand Dollar",
				'symbol' => "$"
			),
			"NIO" => array(
				'name' => "Nicaraguan Córdoba",
				'symbol' => "C$"
			),
			"NGN" => array(
				'name' => "Nigerian Naira",
				'symbol' => "₦"
			),
			"KPW" => array(
				'name' => "North Korean Won",
				'symbol' => "₩"
			),
			"NOK" => array(
				'name' => "Norwegian Krone",
				'symbol' => "kr"
			),
			"OMR" => array(
				'name' => "Omani Rial",
				'symbol' => ".ع.ر"
			),
			"PKR" => array(
				'name' => "Pakistani Rupee",
				'symbol' => "₨"
			),
			"PAB" => array(
				'name' => "Panamanian Balboa",
				'symbol' => "B/."
			),
			"PGK" => array(
				'name' => "Papua New Guinean Kina",
				'symbol' => "K"
			),
			"PYG" => array(
				'name' => "Paraguayan Guarani",
				'symbol' => "₲"
			),
			"PEN" => array(
				'name' => "Peruvian Nuevo Sol",
				'symbol' => "S/."
			),
			"PHP" => array(
				'name' => "Philippine Peso",
				'symbol' => "₱"
			),
			"PLN" => array(
				'name' => "Polish Zloty",
				'symbol' => "zł"
			),
			"QAR" => array(
				'name' => "Qatari Rial",
				'symbol' => "ق.ر"
			),
			"RON" => array(
				'name' => "Romanian Leu",
				'symbol' => "lei"
			),
			"RUB" => array(
				'name' => "Russian Ruble",
				'symbol' => "₽"
			),
			"RWF" => array(
				'name' => "Rwandan Franc",
				'symbol' => "FRw"
			),
			"SVC" => array(
				'name' => "Salvadoran Colón",
				'symbol' => "₡"
			),
			"WST" => array(
				'name' => "Samoan Tala",
				'symbol' => "SAT"
			),
			"STD" => array(
				'name' => "São Tomé and Príncipe Dobra",
				'symbol' => "Db"
			),
			"SAR" => array(
				'name' => "Saudi Riyal",
				'symbol' => "﷼"
			),
			"RSD" => array(
				'name' => "Serbian Dinar",
				'symbol' => "din"
			),
			"SCR" => array(
				'name' => "Seychellois Rupee",
				'symbol' => "SRe"
			),
			"SLL" => array(
				'name' => "Sierra Leonean Leone",
				'symbol' => "Le"
			),
			"SGD" => array(
				'name' => "Singapore Dollar",
				'symbol' => "$"
			),
			"SKK" => array(
				'name' => "Slovak Koruna",
				'symbol' => "Sk"
			),
			"SBD" => array(
				'name' => "Solomon Islands Dollar",
				'symbol' => "Si$"
			),
			"SOS" => array(
				'name' => "Somali Shilling",
				'symbol' => "Sh.so."
			),
			"ZAR" => array(
				'name' => "South African Rand",
				'symbol' => "R"
			),
			"KRW" => array(
				'name' => "South Korean Won",
				'symbol' => "₩"
			),
			"SSP" => array(
				'name' => "South Sudanese Pound",
				'symbol' => "£"
			),
			"XDR" => array(
				'name' => "Special Drawing Rights",
				'symbol' => "SDR"
			),
			"LKR" => array(
				'name' => "Sri Lankan Rupee",
				'symbol' => "Rs"
			),
			"SHP" => array(
				'name' => "St. Helena Pound",
				'symbol' => "£"
			),
			"SDG" => array(
				'name' => "Sudanese Pound",
				'symbol' => ".س.ج"
			),
			"SRD" => array(
				'name' => "Surinamese Dollar",
				'symbol' => "$"
			),
			"SZL" => array(
				'name' => "Swazi Lilangeni",
				'symbol' => "E"
			),
			"SEK" => array(
				'name' => "Swedish Krona",
				'symbol' => "kr"
			),
			"CHF" => array(
				'name' => "Swiss Franc",
				'symbol' => "CHf"
			),
			"SYP" => array(
				'name' => "Syrian Pound",
				'symbol' => "LS"
			),
			"TJS" => array(
				'name' => "Tajikistani Somoni",
				'symbol' => "SM"
			),
			"TZS" => array(
				'name' => "Tanzanian Shilling",
				'symbol' => "TSh"
			),
			"THB" => array(
				'name' => "Thai Baht",
				'symbol' => "฿"
			),
			"TOP" => array(
				'name' => "Tongan Pa'anga",
				'symbol' => "$"
			),
			"TTD" => array(
				'name' => "Trinidad & Tobago Dollar",
				'symbol' => "$"
			),
			"TND" => array(
				'name' => "Tunisian Dinar",
				'symbol' => "ت.د"
			),
			"TRY" => array(
				'name' => "Turkish Lira",
				'symbol' => "₺"
			),
			"TMT" => array(
				'name' => "Turkmenistani Manat",
				'symbol' => "T"
			),
			"UGX" => array(
				'name' => "Ugandan Shilling",
				'symbol' => "USh"
			),
			"UAH" => array(
				'name' => "Ukrainian Hryvnia",
				'symbol' => "₴"
			),
			"AED" => array(
				'name' => "United Arab Emirates Dirham",
				'symbol' => "إ.د"
			),
			"UYU" => array(
				'name' => "Uruguayan Peso",
				'symbol' => "$"
			),
			"USD" => array(
				'name' => __( 'US Dollar', 'myd-delivery-pro' ),
				'symbol' => '$',
			),
			"UZS" => array(
				'name' => "Uzbekistan Som",
				'symbol' => "лв"
			),
			"VUV" => array(
				'name' => "Vanuatu Vatu",
				'symbol' => "VT"
			),
			"VEF" => array(
				'name' => "Venezuelan BolÃvar",
				'symbol' => "Bs"
			),
			"VND" => array(
				'name' => "Vietnamese Dong",
				'symbol' => "₫"
			),
			"YER" => array(
				'name' => "Yemeni Rial",
				'symbol' => "﷼"
			),
			"ZMK" => array(
				'name' => "Zambian Kwacha",
				'symbol' => "ZK"
			),
			"ZWL" => array(
				'name' => "Zimbabwean dollar",
				'symbol' => "$"
			),
		);

		return self::$currency_list;
	}

	/**
	 * Get selected currency
	 *
	 * @since 1.9.35
	 */
	public static function get_currency_code() {
		if ( ! empty( self::$currency_code ) ) {
			return self::$currency_code;
		}

		if ( self::need_update_legacy_option() ) {
			self::update_legacy_option();
			return self::$currency_code;
		}

		self::$currency_code = get_option( 'myd-currency' );
		return self::$currency_code;
	}

	/**
	 * Get currency symbol
	 *
	 * @since 1.9.35
	 */
	public static function get_currency_symbol() {
		if ( ! empty( self::$currency_symbol ) ) {
			return self::$currency_symbol;
		}

		$currency_list = self::get_currency_list();
		$currency_code = self::get_currency_code();
		return $currency_list[ $currency_code ]['symbol'] ?? '$';
	}

	/**
	 * Check if needs update legacy option with currency symbol
	 *
	 * @return boolean
	 */
	private static function need_update_legacy_option() {
		$currency_code = get_option( 'myd-currency' );
		$legacy_currency = get_option( 'fdm-currency' );
		if ( empty( $currency_code ) && ! empty( $legacy_currency ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if needs update legacy option with currency symbol
	 *
	 * @return string
	 */
	private static function update_legacy_option() {
		$legacy_currency = get_option( 'fdm-currency' );
		if ( $legacy_currency === 'R$' ) {
			self::$currency_code = 'BRL';
			update_option( 'myd-currency', 'BRL' );
			return;
		}

		if ( $legacy_currency === '$' ) {
			self::$currency_code = 'USD';
			update_option( 'myd-currency', 'USD' );
			return;
		}

		if ( $legacy_currency === '€' ) {
			self::$currency_code = 'EUR';
			update_option( 'myd-currency', 'EUR' );
			return;
		}

		$currency_list = self::get_currency_list();
		$search_list = array_combine( array_keys( $currency_list ), array_column( $currency_list, 'symbol' ) );
		$found_currency_code = array_search( $legacy_currency, $search_list );
		if ( is_string( $found_currency_code ) ) {
			update_option( 'myd-currency', $found_currency_code );
			self::$currency_code = $found_currency_code;
			return;
		}
	}
}
