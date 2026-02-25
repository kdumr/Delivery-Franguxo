<?php

namespace MydPro\Includes;

use MydPro\Includes\Store_Data;
use MydPro\Includes\Myd_Store_Formatting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart Class
 */
class Cart {
	/**
	 * Undocumented variable
	 *
	 * @var array
	 */
	public array $items;

	/**
	 * Undocumented variable
	 *
	 * @var integer
	 */
	public int $items_quantity;

	/**
	 * Undocumented variable
	 *
	 * @var float
	 */
	public float $total;

	/**
	 * Undocumented variable
	 *
	 * @var float
	 */
	public string $formated_price;

	/**
	 * Undocumented function
	 *
	 * @return boolean
	 */
	public function __construct( array $items ) {
		if ( empty( $items ) ) {
			return false; // handle error
		}

		$this->items_quantity = 0;
		$this->total = 0;
		$this->formated_price = '';
		$this->items = $items;
		$this->proccess_card();
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	private function proccess_card() : void {
		foreach ( $this->items as $key => $product ) {
			$this->items[ $key ]['extras'] = $this->calculate_extras( $product['extras'] );
			$extras_price = $this->items[ $key ]['extras']['total'];
			$this->items[ $key ]['price'] = (float) get_post_meta( $product['id'], 'product_price', true );
			$this->items[ $key ]['total'] = ( $this->items[ $key ]['price'] + $extras_price ) * $product['quantity'];
			$this->items[ $key ]['formated_price'] = $this->get_formated_price( $this->items[ $key ]['total'] );
			$this->items_quantity += (int) $product['quantity'];
			$this->total += $this->items[ $key ]['total'];
		}

		$this->formated_price = $this->get_formated_price( $this->total );
	}

	/**
	 * Calculate product extra
	 *
	 * @return array
	 */
	private function calculate_extras( array $extras ) : array {
		if ( empty( $extras ) ) {
			return $extras;
		}

		$extras['total'] = 0;
		foreach ( $extras['groups'] as $group_key => $group ) {
			$group['total'] = 0;
			foreach ( $group['items'] as $item_key => $item ) {
				$item['total'] = (int) $item['quantity'] * (float) $item['price'];
				$group['total'] += $item['total'];
				$extras['groups'][ $group_key ]['items'][ $item_key ] = $item;
			}

			$extras['total'] += $group['total'];
			$extras['groups'][ $group_key ] = $group;
		}

		return $extras;
	}

	/**
	 * Get formated price
	 */
	private function get_formated_price( $price ) : string {
		return Store_Data::get_store_data( 'currency_simbol' ) . ' ' . Myd_Store_Formatting::format_price( $price );
	}

	/**
	 * Get cart list template
	 */
	public function get_cart_list_template() {
		ob_start();
		require_once MYD_PLUGIN_PATH . 'templates/cart/cart-product-item.php';
		return ob_get_clean();
	}
}
