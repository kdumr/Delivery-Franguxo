<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store orders class
 *
 * @since 1.9.5
 */
class Myd_Store_Orders {
	/**
	 * Store orders
	 *
	 * @since 1.9.5
	 */
	protected $orders = [];

	/**
	 * Store order object
	 *
	 * @since 1.9.5
	 */
	protected $orders_object;

	/**
	 * Default order args
	 *
	 * @since 1.9.5
	 */
	protected $default_order_args = [
		'post_type' => 'mydelivery-orders',
		'no_found_rows' => true,
		'update_post_term_cache' => false,
		'posts_per_page' => -1
	];

	/**
	 * Construct class
	 *
	 * @since 1.9.5
	 */
	public function __construct( array $args = [] ) {
		$this->set_orders( $args );
	}

	/**
	 * Set orders
	 *
	 * @since 1.9.5
	 * @return object
	 */
	public function set_orders( array $args = [] ) {
		if( empty( $args ) ) {

			$args = $this->default_order_args;
		}

		$query_orders = new \WP_Query( $args );
		$this->orders_object = $query_orders;
		$this->orders = $query_orders->posts;
	}

	/**
	 * Get orders
	 *
	 * @since 1.9.5
	 */
	public function get_orders() {

		return $this->orders;
	}

	/**
	 * Get orders
	 *
	 * @since 1.9.5
	 */
	public function get_orders_object() {

		return $this->orders_object;
	}
}
