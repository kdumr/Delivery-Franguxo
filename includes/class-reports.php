<?php

namespace MydPro\Includes;

use MydPro\Includes\Legacy\Legacy_Repeater;
use MydPro\Includes\Custom_Fields\Register_Custom_Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once MYD_PLUGIN_PATH . 'includes/legacy/class-legacy-repeater.php';

/**
 * Class to manage all reports
 *
 * @since 1.9.5
 */
class Myd_Reports {
	/**
	 * Order object - default last 7 days
	 *
	 * @since 1.9.5
	 */
	protected $orders = array();

	/**
	 * Count total orders
	 *
	 * @since 1.9.5
	 */
	protected $count_orders;

	/**
	 * Total orders
	 *
	 * @since 1.9.5
	 */
	protected $total_orders;

	/**
	 * Average sales per day
	 *
	 * @since 1.9.5
	 */
	protected $average_sales;

	/**
	 * Purchased items
	 *
	 * @since 1.9.5
	 */
	protected $purchased_items;

	/**
	 * Period
	 *
	 * @since 1.9.5
	 */
	protected $from;

	/**
	 * Period
	 *
	 * @since 1.9.5
	 */
	protected $to;

	/**
	 * Period in days
	 *
	 * @since 1.9.17
	 */
	protected $period_in_days;

	/**
	 * Orders by period
	 *
	 * @since 1.9.21
	 */
	protected $orders_by_period = array();

	/**
	 * Orders per day
	 *
	 * @since 1.9.21
	 */
	protected $orders_per_day = array();

	/**
	 * Contruct the class
	 *
	 * @since 1.9.5
	 * @param array $orders
	 * @param string $period
	 */
	public function __construct( $orders, $from, $to ) {
		$this->orders = $orders;
		$this->from = $from;
		$this->to = $to;
		$this->make_calcs();
		$this->count_orders = $this->set_count_orders();
		$this->period_in_days = $this->convert_period_to_days();
		$this->average_sales = $this->set_average_sales();
		$this->orders_by_period = $this->set_orders_by_period();
	}

	/**
	 * Make report calcs.
	 *
	 * @return void
	 */
	public function make_calcs() {
		$total_orders = 0;
		$purchased_items = array();
		foreach ( $this->orders as $key => $order ) {
			$order_status = get_post_meta( $order->ID, 'order_status', true );
			if ( $order_status === 'finished' ) {
				$current_order_total = get_post_meta( $order->ID, 'order_total', true );
				$current_order_total = str_replace( array( ',', '.' ), '', $current_order_total );
				$current_order_total = substr_replace( $current_order_total, '.', - Store_Data::get_store_data( 'number_decimals' ), 0 );
				$total_orders += (float) $current_order_total;

				/**
				 * TODO: check if is necessary migrate from old type of repeater data
				 */
				$order_items = \MydPro\Includes\Myd_Orders_Front_Panel::parse_order_items( get_post_meta( $order->ID, 'myd_order_items', true ) );
				$order_items_legacy = get_post_meta( $order->ID, 'order_items', true );
				$args = Register_Custom_Fields::get_registered_fields();
				$args = $args['myd_order_details']['fields']['myd_order_details'] ?? array();
				$update_db = Legacy_Repeater::need_update_db( $order_items_legacy, $order_items );
				if ( $update_db && ! empty( $args ) ) {
					$order_items = Legacy_Repeater::update_repeater_database( $order_items_legacy, $args, $order->ID );
				}

				$purchased_items =  array_merge( $purchased_items, $order_items );
				$post_date = date( 'Y-m-d',  strtotime( $order->post_date ) );
				$post_date = strtotime( $post_date );
				$this->orders_per_day[ $post_date ][] = $order->ID;
			}

			if ( $order_status !== 'finished' ) {
				unset( $this->orders[ $key ] );
			}
		}

		$this->total_orders = $total_orders;
		$this->purchased_items = $this->set_purchased_items( $purchased_items );
	}

	/**
	 * Set purchased items
	 *
	 * @since 1.9.21
	 */
	protected function set_purchased_items( $purchased_items ) {
		if ( empty( $purchased_items ) ) {
			return array();
		}

		$items = array_column( $purchased_items, 'product_name' );
		$new_items = array();

		foreach ( $items as $item ) {
			$name = preg_replace( '/.*\d\sx\s/', '', $item );
			$quantity = (int) preg_replace( '/\sx.*/', '', $item );
			$key_existent_item = array_search( $name, array_column( $new_items, 'name' ) );

			if ( $key_existent_item !== false ) {
				$new_items[ $key_existent_item ]['quantity'] += $quantity;
			} else {
				$new_items[] = array(
					'name' => $name,
					'quantity' => $quantity,
				);
			}
		}

		return $new_items;
	}

	/**
	 * Set count orders
	 *
	 * @since 1.9.5
	 */
	public function set_count_orders() {
		return count( $this->orders );
	}

	/**
	 * Set average sales
	 *
	 * @since 1.9.5
	 */
	public function set_average_sales() {
		if ( $this->period_in_days <= 0 ) {
			return $this->total_orders;
		}

		return (float) $this->total_orders / (int) $this->period_in_days;
	}

	/**
	 * Convert date (period) to number of the days.
	 *
	 * @return string
	 */
	public function convert_period_to_days() {
		$interval = date_diff( new \DateTime( $this->to ), new \DateTime( $this->from ) );
		return (int) $interval->format( '%a' );
	}

	/**
	 * Get count orders
	 *
	 * @since 1.9.5
	 */
	public function get_count_orders() {
		return $this->count_orders;
	}

	/**
	 * Get total orders
	 *
	 * @since 1.9.5
	 */
	public function get_total_orders() {
		return $this->total_orders;
	}

	/**
	 * Get average sales
	 *
	 * @since 1.9.5
	 */
	public function get_average_sales() {
		return $this->average_sales;
	}

	/**
	 * Get purchased items
	 *
	 * @since 1.9.5
	 */
	public function get_purchased_items() {
		return $this->purchased_items;
	}

	/**
	 * Get quantity of purchased items
	 *
	 * @since 1.9.21
	 */
	public function get_purchased_items_quantity() {
		return array_sum( array_column( $this->purchased_items, 'quantity' ) );
	}

	/**
	 * Get orders by period
	 *
	 * @since 1.9.21
	 */
	public function get_orders_by_period() {
		return $this->orders_by_period;
	}

	/**
	 * Set orders by period
	 *
	 * @since 1.9.21
	 */
	protected function set_orders_by_period() {
		if ( $this->period_in_days === 0 ) {
			$this->orders_by_period[] = array(
				'period' => date( 'M j', strtotime( $this->to ) ),
				'total' => $this->total_orders,
				'number_orders' => $this->count_orders,
			);

			return $this->orders_by_period;
		}

		for ( $limit = 1; $limit <= $this->period_in_days; $limit++ ) {
			$period = ! isset( $period ) ? $this->to : date( 'Y-m-d', strtotime( '-1 day', strtotime( $period ) ) );
			$date_timestamp = strtotime( $period );
			$orders = $this->orders_per_day[ $date_timestamp ] ?? array();
			$total = 0;

			foreach ( $orders as $order ) {
				$order_total = get_post_meta( $order, 'order_total', true );
				if ( ! empty( $order_total ) ) {
					$order_total = str_replace( array( ',', '.' ), '', $order_total );
					$order_total = substr_replace( $order_total, '.', - Store_Data::get_store_data( 'number_decimals' ), 0 );
					$total += (float) $order_total;
				}
			}

			$this->orders_by_period[] = array(
				'period' => date( 'M j', strtotime( $period ) ),
				'total' => $total,
				'number_orders' => count( $orders ),
				'orders' => $orders,
			);
		}

		if( $this->period_in_days >= 14 ) {
			$new_period = array();
			$divider = round( $this->period_in_days / 7 );
			$chunk_orders = array_chunk( $this->orders_by_period, $divider, true );

			foreach ( $chunk_orders as $period ) {
				$firs_key = array_key_first( $period );
				$last_key = array_key_last( $period );
				$period_name = date( 'M j', strtotime( $period[ $last_key ]['period'] ) ) . ' - ' . date( 'M j', strtotime( $period[ $firs_key ]['period'] ) );
				$new_period[] = array(
					'period' => $period_name,
					'total' => array_sum( array_column( $period, 'total' ) ),
					'number_orders' => array_sum( array_column( $period, 'number_orders' ) ),
					'orders' => array_merge( ...array_column( $period, 'orders' ) ),
				);
			}

			$this->orders_by_period = $new_period;
		}

		return $this->orders_by_period;
	}

	/**
	 * Get coupon usage data for reports
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_coupon_usage_data() {
		$coupon_data = array();
		$coupon_stats = array();

		foreach ( $this->orders as $order ) {
			$order_id = $order->ID;
			$coupon_code = get_post_meta( $order_id, 'order_coupon', true );
			
			if ( ! empty( $coupon_code ) ) {
				// Buscar dados do cupom
				$coupon_post = get_posts( array(
					'post_type' => 'mydelivery-coupons',
					'meta_query' => array(
						array(
							'key' => 'code',
							'value' => $coupon_code,
							'compare' => '='
						)
					),
					'posts_per_page' => 1
				) );

				$coupon_type = 'N/A';
				$coupon_id = null;
				$usage_limit = 0;
				$expiry_date = '';
				
				if ( ! empty( $coupon_post ) ) {
					$coupon_id = $coupon_post[0]->ID;
					$coupon_type = get_post_meta( $coupon_id, 'myd_coupon_type', true );
					$coupon_type = $coupon_type ?: 'discount-total';
					$usage_limit = intval( get_post_meta( $coupon_id, 'myd_coupon_usage_limit', true ) );
					$expiry_date = get_post_meta( $coupon_id, 'myd_coupon_expiry_date', true );
				}

				// Formatar tipo de cupom
				$formatted_type = '';
				switch ( $coupon_type ) {
					case 'discount-total':
						$formatted_type = __( 'Total Discount', 'myd-delivery-pro' );
						break;
					case 'discount-delivery':
						$formatted_type = __( 'Delivery Discount', 'myd-delivery-pro' );
						break;
					default:
						$formatted_type = ucfirst( str_replace( '-', ' ', $coupon_type ) );
						break;
				}

				$coupon_data[] = array(
					'order_id' => $order_id,
					'order_date' => get_the_date( 'Y-m-d H:i', $order_id ),
					'coupon_code' => $coupon_code,
					'coupon_type' => $coupon_type,
					'coupon_type_formatted' => $formatted_type,
					'order_total' => get_post_meta( $order_id, 'order_cart_total', true ) ?: 0
				);

				// Estatísticas por cupom
				if ( ! isset( $coupon_stats[ $coupon_code ] ) ) {
					$coupon_stats[ $coupon_code ] = array(
						'code' => $coupon_code,
						'type' => $formatted_type,
						'usage_count' => 0,
						'total_orders_value' => 0,
						'usage_limit' => $usage_limit,
						'expiry_date' => $expiry_date,
						'coupon_id' => $coupon_id
					);
				}

				$coupon_stats[ $coupon_code ]['usage_count']++;
				$coupon_stats[ $coupon_code ]['total_orders_value'] += floatval( get_post_meta( $order_id, 'order_cart_total', true ) ?: 0 );
			}
		}

		return array(
			'orders_with_coupons' => $coupon_data,
			'coupon_statistics' => array_values( $coupon_stats )
		);
	}
}
