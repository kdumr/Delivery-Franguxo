<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orders custom columns
 *
 * @param [type] $columns
 * @return void
 */
function myd_order_custom_columns ( $columns ) {
	$columns["status"] = "Status";
	$columns["customer"] = '' . __( 'Customer', 'myd-delivery-pro' ) . '';
	$columns["phone"] = '' . __( 'Phone', 'myd-delivery-pro' ) . '';
	$columns["order_date"] = '' . __( 'Order Date', 'myd-delivery-pro' ) . '';

	unset($columns['date']);
	return $columns;
}

add_filter( 'manage_edit-mydelivery-orders_columns', 'MydPro\Includes\myd_order_custom_columns' );

/**
 * Orders custom columns content
 *
 * @param string $colname, int $cptid
 * @return void
 * @since 1.9.5
 */
function myd_order_custom_column_content ( $colname, $cptid ) {
	if ( $colname == 'status') {
		echo get_post_meta( $cptid, 'order_status', true );
	}

	if ( $colname == 'customer') {
		echo get_post_meta( $cptid, 'order_customer_name', true );
	}

	if ( $colname == 'phone') {
		echo get_post_meta( $cptid, 'customer_phone', true );
	}

	if ( $colname == 'order_date') {
		echo get_post_meta( $cptid, 'order_date', true );
	}
}

add_action( 'manage_mydelivery-orders_posts_custom_column', 'MydPro\Includes\myd_order_custom_column_content', 10, 2 );

/**
 * Products custom columns
 *
 * @param array $columns
 * @return void
 * @since 1.9.5
 */
function myd_products_custom_columns ( $columns ) {
	$columns["price"] = '' . __( 'Price', 'myd-delivery-pro' ) . '';
	$columns["product_categorie"] = '' . __( 'Category', 'myd-delivery-pro' ) . '';
	$columns["product_description"] = '' . __( 'Product Description', 'myd-delivery-pro' ) . '';

	unset( $columns['date'] );
	return $columns;
}

add_filter( 'manage_edit-mydelivery-produtos_columns', 'MydPro\Includes\myd_products_custom_columns' );

/**
 * Products custom colmns content
 *
 * @param string $colname
 * @param int $cptid
 * @return void
 */
function myd_products_custom_column_content ( $colname, $cptid ) {
	if ( $colname == 'price') {
		echo get_post_meta( $cptid, 'product_price', true );
	}

	if ( $colname == 'product_categorie') {
		$pt = get_post_meta( $cptid, 'product_type', true );
		if ( is_array( $pt ) ) {
			echo implode( ', ', $pt );
		} else {
			echo $pt;
		}
	}

	if ( $colname == 'product_description') {
		echo get_post_meta( $cptid, 'product_description', true );
	}
}

add_action( 'manage_mydelivery-produtos_posts_custom_column', 'MydPro\Includes\myd_products_custom_column_content', 10, 2 );
