<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class product extra
 */
class Myd_product_extra {
	/**
	 * Get extra title
	 */
	public function get_title( $extras ) {
		if ( ! empty( $extras['extra_title'] ) ) {
			$title = $extras['extra_title'];
		} else {
			$title = '';
		}

		return $title;
	}

	/**
	 * Get extra limit
	 */
	public function get_extra_limit( $extras ) {
		if ( ! empty( $extras['extra_limit'] ) ) {
			$limit = $extras['extra_limit'];
		} else {
			$limit = '';
		}

		return $limit;
	}

	/**
	 * Get extra limit tag
	 */
	public function get_extra_limit_tag( $extras ) {
		if( ! empty( $extras['extra_limit'] ) ) {
			$limit = '(Max: ' . $extras['extra_limit'] . ')';
		} else {
			$limit = '';
		}

		return $limit;
	}

	/**
	 * Get extra required
	 */
	public function get_extra_required ( $extras ) {
		if ( ! empty( $extras['extra_required'] ) ) {
			if ( $extras['extra_required'] == '1' ) {
				$required = 'required';
			}
		} else {
			$required = '';
		}

		return $required;
	}

	/**
	 * Get extra required tag
	 */
	public function get_extra_required_tag( $extras ) {
		if ( ! empty( $extras['extra_required'] ) && $extras['extra_required'] == '1' ) {
			$required_tag = __( 'Obrigatório', 'myd-delivery-pro' );
		} else {
			$required_tag = __( 'Opcional', 'myd-delivery-pro' );
		}

		return $required_tag;
	}

	/**
	 * Formar extra price
	 */
	public function format_extra_price( $currency, $price ) {
		if ( empty( $price ) ) {
			return;
		}

		return '' . $currency . ' ' . $price . '';
	}

	/**
	 * Gonvert cat to tag
	 */
	public function cat_to_class( $categorie ) {
		$converted_cat = str_replace( ' ', '-', $categorie );
		return $converted_cat;
	}
}
