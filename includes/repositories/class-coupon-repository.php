<?php

namespace MydPro\Includes\Repositories;

use MydPro\Includes\Coupon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupon Class
 */
class Coupon_Repository {
	/**
	 * Get coupon by name
	 *
	 * @param string $name
	 * @return Coupon|null
	 */
	public static function get_by_name( string $name = '' ) : ?Coupon {
		if ( empty( $name ) ) {
			return null; // handle error
		}

		$args = array(
			'post_type' => 'mydelivery-coupons',
			'fields' => 'ids',
			'title' => $name,
			'no_found_rows' => true,
		);

		$coupons = new \WP_Query( $args );
		$coupons = $coupons->posts;

		if ( empty( $coupons ) ) {
			return null;
		}

		return new Coupon( $coupons[0] );
	}

	/**
	 * Validate coupon by name with usage and expiry checks
	 *
	 * @param string $name
	 * @return array
	 */
	public static function validate_coupon( string $name = '' ) : array {
		if ( empty( $name ) ) {
			return array(
				'valid' => false,
				'coupon' => null,
				'errors' => array( __( 'Coupon code is required.', 'myd-delivery-pro' ) )
			);
		}

		$coupon = self::get_by_name( $name );
		
		if ( ! $coupon ) {
			return array(
				'valid' => false,
				'coupon' => null,
				'errors' => array( __( 'Invalid coupon code.', 'myd-delivery-pro' ) )
			);
		}

		$validation = $coupon->is_valid();
		
		return array(
			'valid' => $validation['valid'],
			'coupon' => $coupon,
			'errors' => $validation['errors']
		);
	}

	/**
	 * Get all coupons with their status information
	 *
	 * @return array
	 */
	public static function get_all_with_status() : array {
		$args = array(
			'post_type' => 'mydelivery-coupons',
			'posts_per_page' => -1,
			'post_status' => 'publish'
		);

		$coupon_posts = get_posts( $args );
		$coupons_with_status = array();

		foreach ( $coupon_posts as $coupon_post ) {
			$coupon = new Coupon( $coupon_post->ID );
			$status = $coupon->get_status_info();
			
			$coupons_with_status[] = array(
				'coupon' => $coupon,
				'status' => $status,
				'post' => $coupon_post
			);
		}

		return $coupons_with_status;
	}
}
