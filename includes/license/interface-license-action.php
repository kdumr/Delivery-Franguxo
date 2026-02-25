<?php

namespace MydPro\Includes\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface License_Action {
	/**
	 * Run function
	 */
	public function run();

	/**
	 * Get respose status
	 */
	public function get_response_status();

	/**
	 * Get response
	 */
	public function get_response();
}
