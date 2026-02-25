<?php

namespace MydPro\Includes\Plugin_Update;

use MydPro\Includes\License\License_Manage_Data;
use MydPro\Includes\Plugin;
use const \DAY_IN_SECONDS;
use function \add_query_arg;
use function \apply_filters;
use function \delete_transient;
use function \get_transient;
use function \is_wp_error;
use function \sanitize_text_field;
use function \set_transient;
use function \wp_remote_get;
use function \wp_remote_retrieve_body;
use function \wp_remote_retrieve_response_code;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin update class.
 */
class Plugin_Update {
	const URL = 'https://raw.githubusercontent.com/kdumr/Sistema-Delivery-Franguxo/main/metadata.json';

	/**
	 * License Key
	 */
	private $license_key;

	/**
	 * Website url
	 */
	private $site_url;

	/**
	 * Already forced property.
	 */
	private $already_forced;

	public function __construct() {
		$this->already_forced = false;

		/**
		 * teste
		 */
		$license = Plugin::instance()->license;
		$this->license_key = $license->get_key();

		$this->site_url = \site_url();

		add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
		add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
		add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );
	}

	/**
	 * Get info to plugin details
	 *
	 * @param [type] $result
	 * @param [type] $action
	 * @param [type] $args
	 * @return mixed
	 */
	public function info( $result, $action = null, $args = null ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		if ( MYD_PLUGIN_DIRNAME !== $args->slug ) {
			return $result;
		}

		$plugin_data_from_server = $this->request();

		if ( ! is_array( $plugin_data_from_server ) ) {
			return $result;
		}

		$plugin_data_from_server = (object) $plugin_data_from_server;

		$result = new \stdClass();
		$result->name = isset( $plugin_data_from_server->name ) ? $plugin_data_from_server->name : '';
		$result->slug = isset( $plugin_data_from_server->slug ) ? $plugin_data_from_server->slug : '';
		$result->version = isset( $plugin_data_from_server->version ) ? $plugin_data_from_server->version : '';
		$result->tested = isset( $plugin_data_from_server->tested ) ? $plugin_data_from_server->tested : '';
		$result->requires = isset( $plugin_data_from_server->requires ) ? $plugin_data_from_server->requires : '';
		$result->author = isset( $plugin_data_from_server->author ) ? $plugin_data_from_server->author : '';
		$result->author_profile = isset( $plugin_data_from_server->author_profile ) ? $plugin_data_from_server->author_profile : '';
		$result->download_link = isset( $plugin_data_from_server->download_url ) ? $plugin_data_from_server->download_url : '';
		$result->trunk = isset( $plugin_data_from_server->download_url ) ? $plugin_data_from_server->download_url : '';
		$result->requires_php = isset( $plugin_data_from_server->requires_php ) ? $plugin_data_from_server->requires_php : '';
		$result->last_updated = isset( $plugin_data_from_server->last_updated ) ? $plugin_data_from_server->last_updated : '';

		$sections = array(
			'description' => '',
			'installation' => '',
			'changelog' => '',
			'upgrade_notice' => '',
		);

		foreach ( $sections as $key => $value ) {
			$sections[ $key ] = isset( $plugin_data_from_server->sections[ $key ] ) ? $plugin_data_from_server->sections[ $key ] : '';
		}

		/**
		 * Check if #result is object. create and object to add them.
		 * add all items with array and for each.
		 * unset license data to use in another feature.
		 */
		$result->sections = $sections;
		return $result;
	}

	/**
	 * Request
	 */
	public function request() {
		$data_from_server = get_transient( 'mydpro-update-data' );

		$force_update = isset( $_GET['force-check'] ) ? sanitize_text_field( $_GET['force-check'] ) : null;
		if ( $force_update === '1' && $this->already_forced === false ) {
			$data_from_server = false;
			$this->already_forced = true;
		}

		if ( $data_from_server === false ) {
			$request_url = apply_filters(
				'myd_delivery_update_metadata_url',
				add_query_arg(
					array(
						'license' => rawurlencode( (string) $this->license_key ),
						'domain'  => rawurlencode( (string) $this->site_url ),
					),
					self::URL
				),
				$this->license_key,
				$this->site_url
			);
			$data_from_server = wp_remote_get(
				$request_url,
				array(
					'timeout' => 20,
					'headers' => array(
						'Accept' => 'application/json',
					),
				)
			);

			if (
				is_wp_error( $data_from_server )
				|| 200 !== wp_remote_retrieve_response_code( $data_from_server )
				|| empty( wp_remote_retrieve_body( $data_from_server ) )
			) {
				return false;
			}

			set_transient( 'mydpro-update-data', $data_from_server, DAY_IN_SECONDS );
		}

		return json_decode( wp_remote_retrieve_body( $data_from_server ), true );
	}

	/**
	 * Real update place. Add info to WP get plugin and etc.
	 *
	 * @param [type] $transient
	 * @return void
	 */
	public function update( $transient ) {
		if ( ! isset( $transient->response ) ) {
			return $transient;
		}

		$plugin_update_data = $this->request();

		if ( ! is_array( $plugin_update_data ) ) {
			return $transient;
		}

		if ( version_compare( MYD_CURRENT_VERSION, $plugin_update_data['version'], '<' ) ) {
			$res = $this->get_plugin_data( $plugin_update_data );
			$transient->response[ MYD_PLUGIN_BASENAME ] = $res;
		} else {
			if ( isset( $transient->no_update ) ) {
				$res = $this->get_plugin_data( $plugin_update_data );
				$transient->no_update[ MYD_PLUGIN_BASENAME ] = $res;
			}
		}

		if ( isset( $plugin_update_data['license_status'] ) ) {
			$this->update_license_status( $plugin_update_data['license_status'] );
		}

		return $transient;
	}

	/**
	 * Get plugin data
	 *
	 * @param [array] $plugin_update_data
	 * @return \stdClass
	 */
	public function get_plugin_data( $plugin_update_data ) {
		$res = new \stdClass();
		$res->slug = MYD_PLUGIN_DIRNAME;
		$res->plugin = MYD_PLUGIN_BASENAME;
		$res->new_version = isset( $plugin_update_data['version'] ) ? $plugin_update_data['version'] : '';
		$res->tested = isset( $plugin_update_data['tested'] ) ? $plugin_update_data['tested'] : '';
		$res->package = isset( $plugin_update_data['download_url'] ) ? $plugin_update_data['download_url'] : '';
		return $res;
	}

	/**
	 * Update license status
	 *
	 * @param string $status
	 */
	protected function update_license_status( $status ) {
		$current_transient = License_Manage_Data::get_transient();

		$license = Plugin::instance()->license;
		if ( $current_transient !== false ) {
			License_Manage_Data::set_transient( $license->get_key(), $license->get_site_url(), $status );
		}
	}

	/**
	 * Clean after update
	 *
	 * @return void
	 */
	public function purge( $upgrader, $options ) {
		if ( $options['action'] === 'update' && $options['type'] === 'plugin' && isset( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				if( $plugin === MYD_PLUGIN_BASENAME ) {
					self::delete_plugin_update_transient();
				}
			}
		}
	}

	/**
	 * Delete update transient
	 *
	 * @return void
	 */
	public static function delete_plugin_update_transient() {
		delete_transient( 'mydpro-update-data' );
	}
}
