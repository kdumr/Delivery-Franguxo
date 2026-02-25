<?php

namespace MydPro\Includes\License;

use MydPro\Includes\License\License_Api;
use MydPro\Includes\License\License_Action;
use MydPro\Includes\License\License_Manage_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License Activate class.
 */
class License_Activate extends License_Api implements License_Action {

	public function __construct( $license_key ) {
		$this->action = 'activate';
		$this->key = $license_key;
	}

	/**
	 * Activate
	 *
	 * @since 1.9.7
	 */
	public function run() {
		$this->fetch_api();
		$response_status = $this->get_response_status();
		if ( $response_status === 'error' ) {
			License_Manage_Data::delete_data();
			return;
		}

		License_Manage_Data::set_transient( $this->key, $this->site_url );
	}
}
