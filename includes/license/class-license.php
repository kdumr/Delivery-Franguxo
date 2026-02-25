<?php

namespace MydPro\Includes\License;

use MydPro\Includes\License\License_Action;
use MydPro\Includes\License\License_Activate;
use MydPro\Includes\License\License_Deactivate;
use MydPro\Includes\License\License_Manage_Data;
use MydPro\Includes\Plugin_Update\Plugin_Update;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Myd License
 *
 * @since 1.9
 */
final class License {

	/**
	 * License key
	 *
	 * @since 1.9.7
	 *
	 * TODO: change to protected and create method to get
	 */
	protected $key;

	/**
	 * License data
	 *
	 * @since 1.9.7
	 *
	 * TODO: change to protected and create method to get
	 */
	protected $status;

	/**
	 * Site url
	 *
	 * @since 1.9.7
	 */
	protected $site_url;

	/**
	 * Error
	 *
	 * @since 1.9.7
	 */
	protected $error;

	/**
	 * Error
	 *
	 * @since 1.9.11
	 */
	protected $license_transient;

	/**
	 * Construct Class
	 *
	 * @since 1.9
	 */
	public function __construct() {
		if ( $this->is_active_request() && $this->has_license_post() ) {
			License_Manage_Data::update_key_option( $_POST['fdm-license'] );
		}

		$this->key = License_Manage_Data::get_key_option();
		$this->site_url = site_url();

		if ( ! is_admin() ) {
			$this->set_status( 'active' );
			return;
		}

		$this->get_license();
	}

	/**
	 * Get license
	 *
	 * @since 1.9.7
	 */
	public function get_license() {
		if ( $this->is_null_license() ) {
			return;
		}

		if ( $this->has_action_request() ) {
			$this->run_requested_action();
			return;
		}

		if ( $this->has_license_transient() ) {
			$this->process_transient();
			return;
		}

		$this->set_status( 'deactivated' );
	}

	/**
	 * Check if license ky is null
	 *
	 * @return boolean
	 */
	private function is_null_license() {
		if ( null !== $this->key ) {
			return false;
		}

		$error = [
			'error_type' => 'Empty license key',
			'message' => 'Add your license key to continue.'
		];
		$this->register_error( $error, 'deactivated' );

		return true;
	}

	/**
	 * Has license transient
	 *
	 * @return boolean
	 */
	private function has_license_transient() {
		$license_transient = License_Manage_Data::get_transient();
		if ( false !== $license_transient ) {
			$this->license_transient = $license_transient;
			return true;
		}

		return false;
	}

    /**
     * Has action requested
     *
     * @since 1.9.7
     */
    private function has_action_request() {

        if( isset( $_POST['myd-active-license'] ) || isset( $_POST['myd-deactivate-license'] ) ) {
            return true;
        }

        return false;
    }

    /**
     * Has license posted
     *
     * @since 1.9.8
     */
    private function has_license_post() {

        if( isset( $_POST['fdm-license'] ) && ! empty( $_POST['fdm-license'] )  ) {
            return true;
        }

        return false;
    }

	/**
	 * Has activate request action
	 *
	 * @since 1.9.7
	 *
	 * TODO: change the if. remove check post license or method name.
	 */
	private function is_active_request() {
	if ( isset( $_POST['myd-active-license'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Process transient data
	 *
	 * @since 1.9.7
	 */
	private function process_transient() {
		$license_transient = $this->license_transient;
		if ( is_array( $license_transient ) ) {
			$this->key = isset( $license_transient['key'] ) ? $license_transient['key'] : '';
			$this->status = isset( $license_transient['status'] ) ? $license_transient['status'] : 'Not checked';
			$this->site_url = isset( $license_transient['site_url'] ) ? $license_transient['site_url'] : '';
		}
	}

	/**
	 * Check site url change
	 *
	 * @since 1.9.6
	 * @return boolean|void
	 */
	private function site_url_changed() {
		$license_transient = $this->license_transient;
		if ( is_array( $license_transient ) && isset( $license_transient['site_url'] ) ) {

			if ( $this->remove_url_protocol( $license_transient['site_url'] ) !== $this->remove_url_protocol( $this->site_url ) ) {
				return true;
			}
		}
	}

	/**
	 * Activate license
	 *
	 * @since 1.9.6
	 * @return void
	 */
	private function activate() {
		$activate_response = $this->run_api_action( new License_Activate( $this->key ) );
		if ( $activate_response === false ) {
			return;
		}

		if ( get_transient( 'mydpro-update-data' ) !== false ) {
			Plugin_Update::delete_plugin_update_transient();
		}
		$this->set_status( 'active' );
	}

	/**
	 * Deactive license
	 *
	 * @since 1.9.6
	 * @return void
	 */
	private function deactivate() {
		$deactivate_response = $this->run_api_action( new License_Deactivate( $this->key ) );
		if ( false === $deactivate_response ) {
			return;
		}

		$this->key = '';
		$this->set_status( 'deactivated' );
	}

	/**
	 * Run api action
	 *
	 * @param License_Action $api_action
	 * @return boolean|array
	 * @since 1.9.7
	 */
	private function run_api_action( License_Action $api_action ) {
		$api_action->run();
		$api_action_status = $api_action->get_response_status();
		if ( $api_action_status === 'error' ) {
			$this->register_error( $api_action->get_response() );
			return false;
		}

		return $api_action->get_response();
	}

	/**
	 * Run action
	 *
	 * @since 1.9.7
	 */
	private function run_requested_action() {
		if ( $this->is_active_request() ) {
			$this->activate();
		}
		else {
			$this->deactivate();
		}
	}

    /**
     * Remove url protocol
     *
     * @since 1.9.7
     */
    private function remove_url_protocol( $url ) {
        return str_replace( [ 'http://','https://' ], '', $url );
    }

    /**
     * Register error
     *
     * @since 1.9.7
     */
    private function register_error( $error = [], $status = 'error' ) {

        $this->error = $error;
        $this->set_status( $status );
    }

    /**
     * Set status
     *
     * @since 1.9.7
     */
    private function set_status( $status ) {

        $this->status = $status;
    }

    /**
     * Get status
     *
     * @since 1.9.7
     */
    public function get_status() {

        return $this->status;
    }

	/**
	 * Get key
	 *
	 * @since 1.9.7
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Get site_url
	 *
	 * @since 1.9.7
	 */
	public function get_site_url() {
		return $this->site_url;
	}

    /**
     * Get error
     *
     * @since 1.9.8
     */
    public function get_error() {
        return $this->error;
    }
}
