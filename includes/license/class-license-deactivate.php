<?php

namespace MydPro\Includes\License;

use MydPro\Includes\License\License_Api;
use MydPro\Includes\License\License_Action;
use MydPro\Includes\License\License_Manage_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License Deactive class.
 */
class License_Deactivate extends License_Api implements License_Action {

	public function __construct( $license_key ) {
		$this->action = 'deactivate';
		$this->key = $license_key;
	}

	/**
	 * Deactivate
	 *
	 * @since 1.9.7
	 */
	public function run() {
		$this->fetch_api();
		$response_status = $this->get_response_status();
		if ( $response_status === 'success' ) {
			License_Manage_Data::delete_data();
		}
	}
}
