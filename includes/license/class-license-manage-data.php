<?php

namespace MydPro\Includes\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage license data class.
 */
class License_Manage_Data {

	/**
	 * Delete lincese transient and license option
	 *
	 * @since 1.9.7
	 */
	public static function delete_data() {
		delete_transient( 'myd_license_data' );
	}

	/**
	 * Set lincese transient
	 *
	 * @since 1.9.7
	 */
	public static function set_transient( $key = null, $site_url = '', $status = 'active' ) {
		$license_data = [
			'key' => $key,
			'status' => $status,
			'site_url' => $site_url,
		];

		set_transient( 'myd_license_data', $license_data, 30 * DAY_IN_SECONDS );
	}

	/**
	 * Get license transient
	 *
	 * @since 1.9.7
	 */
	public static function get_transient() {

		if ( get_transient( 'myd_license_data' ) !== false ) {

			return self::normalize_legacy_transient( get_transient( 'myd_license_data' ) );
		}

		return false;
	}

	/**
	 * Normalize legacy transient
	 *
	 * @since 1.9.7
	 */
	public static function normalize_legacy_transient( $transient ) {

		if ( isset( $transient['website'] ) ) {

			$transient['site_url'] = $transient['website'];
			unset( $transient['website'] );
		}

		if ( ! isset( $transient['key'] ) ) {

			$transient['key'] = self::get_key_option();
		}

		return $transient;
	}

	/**
	 * Update license key options
	 *
	 * @since 1.9.7
	 *
	 * TODO: add parameter. i cant has only the $_POST to update the option
	 */
	public static function update_key_option( $lincese_key ) {

		if ( empty( $lincese_key ) ) {
			return;
		}

		update_option( 'fdm-license', sanitize_text_field( $lincese_key ) );
	}

	/**
	 * Get license key option
	 *
	 * @since 1.9.7
	 */
	public static function get_key_option() {

		return empty( get_option( 'fdm-license' ) ) ? null : get_option( 'fdm-license' );
	}
}
