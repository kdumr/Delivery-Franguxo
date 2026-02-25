<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to mapa and convert old values to shipping and date to new format.
 * TODO: refactor.
 */
class Myd_Legacy {
    /**
     * Old stores
     *
     * @var array
     *
     * @since 1.9.4
     */
    private static $stores = [];

    /**
     * Construct the Class
     *
     * @since 1.9.4
     */
    public function __construct() {

        $this->set_store();
    }

    /**
     * Set Store
     *
     * @since 1.9.4
     */
    public function set_store() {

        $args = [
            'post_type' => 'mydelivery-stores',
            'no_found_rows' => true,
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ];

        $store = new \WP_Query( $args );
        self::$stores = $store->posts;
    }

    /**
     * Get old store times
     *
     * Get old store delivery times on old stores CPT.
     *
     * @return array
     *
     * @since 1.9.4
     */
    static function get_old_delivery_clock() {

        $store = self::$stores;
        if( ! empty( $store ) ) {
            return $old_repeater = [
                'monday' => [
                    [
                        'start' => get_post_meta( $store[0], 'store_monday_time_store_monday_start_time', true ) ?? '',
                        'end' => get_post_meta( $store[0], 'store_monday_time_store_monday_start_time', true ) ?? '',
                    ]
                ],
                'tuesday' => [
                    [
                        'start' => get_post_meta( $store[0], 'store_tuesday_time_store_tuesday_start_time', true ) ?? '',
                        'end' => get_post_meta( $store[0], 'store_tuesday_time_store_tuesday_end_time', true ) ?? '',
                    ]
                ],
                'wednesday' => [
                    [
                        'start' => get_post_meta( $store[0], 'store_wednesday_time_store_wednesday_start_time', true ) ?? '',
                        'end' => get_post_meta( $store[0], 'store_tuesday_time_store_tuesday_end_time', true ) ?? '',
                    ]
                ],
                'thursday' => [
                    [
                        'start' => get_post_meta( $store[0], 'store_thursday_time_store_thursday_start_time', true ) ?? '',
                        'end' => get_post_meta( $store[0], 'store_thursday_time_store_thursday_end_time', true ) ?? '',
                    ]
                ],
                'friday' => [
                    [
                        'start' => get_post_meta( $store[0], 'store_friday_time_store_friday_start_time', true ) ?? '',
                        'end' => get_post_meta( $store[0], 'store_friday_time_store_friday_end_time', true ) ?? '',
                    ]
                ],
                'saturday' => [
                    [
                        'start' => get_post_meta( $store[0], 'store_saturday_time_store_saturday_start_time', true ) ?? '',
                        'end' => get_post_meta( $store[0], 'store_saturday_time_store_saturday_end_time', true ) ?? '',
                    ]
                ],
                'sunday' => [
                    [
                        'start' => get_post_meta( $store[0], 'store_sunday_time_store_sunday_start_time', true ) ?? '',
                        'end' => get_post_meta( $store[0], 'store_sunday_time_store_sunday_end_time', true ) ?? '',
                    ]
                ]
            ];
        }
        else {
            return array();
        }
    }

	/**
	 * Get old WhatsApp
	 *
	 * Get old WhatsApp phone on old stores CPT.
	 *
	 * @since 1.9.4
	 */
	public static function get_old_whatsapp() {
		$store = self::$stores;
		if ( ! empty( $store ) ) {
			return get_post_meta( $store[0], 'store_whatsapp_number', true );
		} else {
			return;
		}
	}

	/**
	 * Get old delivery type
	 *
	 * @since 1.9.4
	 */
	public static function get_old_delivery_type() {
		$store = self::$stores;
		if ( ! empty( $store ) ) {
			return get_post_meta( $store[0], 'store_shipping_tax_type', true );
		} else {
			return;
		}
	}

	/**
	 * Get old delivery area
	 *
	 * @since 1.9.4
	 */
	public static function get_old_delivery_area() {
		$store = self::$stores;
		if ( ! empty( $store ) ) {
			/**
			 * Fixed per CEP
			 */
			$repeater_fixed_cep = get_post_meta( $store[0], 'store_list_zipcodes', true );
			$new_options_fixed_cep = array();
			if ( ! empty( $repeater_fixed_cep ) ) {
				$f_ceps = array();
				$number_ceps = (int) $repeater_fixed_cep - 1;

				for ( $limit = 0; $limit <= $number_ceps; $limit++ ) {
					$f_ceps[ $limit ] = array(
						'store_list_zipcodes-from' => get_post_meta( $store[0], 'store_list_zipcodes_' . $limit . '_store_list_zipcodes-from', true ),
						'store_list_zipcodes-to' => get_post_meta( $store[0], 'store_list_zipcodes_' . $limit . '_store_list_zipcodes-to', true ),
					);
				}

				foreach( $f_ceps as $v ) {
					$new_options_fixed_cep[] = [
						'from' => $v['store_list_zipcodes-from'],
						'to' => $v['store_list_zipcodes-to']
					];
				}
			}

			/**
			 * Fixed per Neighborhood
			 */
			$repeater_fixed_neig = get_post_meta( $store[0], 'store_list_neighborhoods', true );
			$repeater_fixed_neig = explode( ',', $repeater_fixed_neig );
			$new_options_fixed_neig = array();
			if( ! empty( $repeater_fixed_neig ) ) {
				foreach( $repeater_fixed_neig as $v ) {
					$new_options_fixed_neig[] = [
						'from' => trim( $v )
					];
				}
			}

			/**
			 * Per CEP range
			 */
			$repeater_cep_range = get_post_meta( $store[0], 'store_price_per_zipcode', true );
			$new_options_cep_range = array();
			if ( ! empty( $repeater_cep_range ) ) {
				$ceps = array();
				$number_ceps = (int) $repeater_cep_range - 1;

				for ( $limit = 0; $limit <= $number_ceps; $limit++ ) {
					$ceps[ $limit ] = array(
						'store_from_zipcode' => get_post_meta( $store[0], 'store_price_per_zipcode_' . $limit . '_store_from_zipcode', true ),
						'store_to_zipcode' => get_post_meta( $store[0], 'store_price_per_zipcode_' . $limit . '_store_to_zipcode', true ),
						'store_zipcode_price' => get_post_meta( $store[0], 'store_price_per_zipcode_' . $limit . '_store_zipcode_price', true ),
					);
				}

				foreach( $ceps as $v ) {
					$new_options_cep_range[] = [
						'from' => $v['store_from_zipcode'],
						'to' => $v['store_to_zipcode'],
						'price' => $v['store_zipcode_price']
					];
				}
			}

			/**
			 * Per neihghborhood
			 */
			$repeater_per_neigh = get_post_meta( $store[0], 'store_price_per_neighborhood', true );
			$new_options_per_neig = array();
			if ( ! empty( $repeater_per_neigh ) ) {
				$neigborhoods = array();
				$number_neig_to_interate = (int) $repeater_per_neigh - 1;

				for ( $limit = 0; $limit <= $number_neig_to_interate; $limit++ ) {
					$neigborhoods[ $limit ] = array(
						'store_neighborhood_name' => get_post_meta( $store[0], 'store_price_per_neighborhood_' . $limit . '_store_neighborhood_name', true ),
						'store_neighborhood_price' => get_post_meta( $store[0], 'store_price_per_neighborhood_' . $limit . '_store_neighborhood_price', true ),
					);
				}

				foreach( $neigborhoods as $v ) {
					$new_options_per_neig[] = [
						'from' => $v['store_neighborhood_name'],
						'price' => $v['store_neighborhood_price']
					];
				}
			}

			return [
				'fixed-per-cep' => [
					'price' => get_post_meta( $store[0], 'store_tax_price', true ),
				'options' => $new_options_fixed_cep,
				],
				'fixed-per-neighborhood' => [
					'price' => get_post_meta( $store[0], 'store_tax_price', true ),
					'options' => $new_options_fixed_neig,
				],
				'per-cep-range' => [
					'options' => $new_options_cep_range,
				],
				'per-neighborhood' => [
					'options' => $new_options_per_neig,
				],
				'per-distance' => [],
			];
		} else {
			return [
				'fixed-per-cep' => [
					'price' => '',
					'options' => [],
				],
				'fixed-per-neighborhood' => [
					'price' => '',
					'options' => [],
				],
				'per-cep-range' => [
					'options' => [],
				],
				'per-neighborhood' => [
					'options' => [],
				],
				'per-distance' => [],
			];
		}
	}
}

new Myd_Legacy();
