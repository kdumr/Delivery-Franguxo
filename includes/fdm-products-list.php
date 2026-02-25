<?php

namespace MydPro\Includes;

use MydPro\Includes\Legacy\Legacy_Repeater;
use MydPro\Includes\Custom_Fields\Register_Custom_Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once dirname(__FILE__) . '/fdm-custom-svg.php';
include_once dirname(__FILE__) . '/class-myd-product-extra.php';
include_once dirname(__FILE__) . '/class-myd-product.php';
include_once MYD_PLUGIN_PATH . 'includes/legacy/class-legacy-repeater.php';

/**
 * Class to show products
 *
 * TODO: Refactor!
 */
class Fdm_products_show {
	/**
	 * Register shortcode with template.
	 *
	 * @return void
	 * @since 1.9.15
	 */
	public function register_shortcode() {
		add_shortcode( 'mydelivery-products', array( $this, 'fdm_list_products' ) );
	}

	/*
	*
	* Return functions to shortcode
	*
	*/
	public function fdm_list_products () {
		return $this->fdm_list_products_html();
	}

	/**
	 * Get product extra
	 *
	 * Loads extras from centralized CPT (linked via myd_linked_extras),
	 * falling back to inline extras (myd_product_extras / product_extras).
	 *
	 * @param int $id
	 * @return void|array
	 * @since 1.6
	 */
	public function get_product_extra( $id ) {
		/**
		 * 1. Try loading from centralized extras (new system).
		 */
		$linked_extras = get_post_meta( $id, 'myd_linked_extras', true );

		if ( ! empty( $linked_extras ) && is_array( $linked_extras ) ) {
			$formated_extras = array();

			foreach ( $linked_extras as $extra_id ) {
				$extra_id = intval( $extra_id );
				$extra_post = get_post( $extra_id );

				if ( ! $extra_post || $extra_post->post_status !== 'publish' ) {
					continue;
				}

				$extra_available = get_post_meta( $extra_id, 'extra_available', true );
				$extra_max_limit = get_post_meta( $extra_id, 'extra_max_limit', true );
				$extra_min_limit = get_post_meta( $extra_id, 'extra_min_limit', true );
				// If min limit > 0, it's automatically required
				$extra_required = ( intval( $extra_min_limit ) > 0 ) ? '1' : '';
				$extra_options   = get_post_meta( $extra_id, 'myd_extra_options', true );

				$formated_extras[] = array(
					'extra_available' => $extra_available ?: 'show',
					'extra_limit'     => $extra_max_limit,
					'extra_min_limit' => $extra_min_limit ?? '',
					'extra_required'  => $extra_required,
					'extra_title'     => $extra_post->post_title,
					'extra_options'   => ! empty( $extra_options ) ? $extra_options : array(),
				);
			}

			if ( ! empty( $formated_extras ) ) {
				return $formated_extras;
			}
		}

		// If no linked extras were found or processed, return an empty array
		// Note: The legacy fallback system has been intentionally removed
		// to force products to use the new centralized extras system.
		return array();
	}

	/**
	 * Formar product extra
	 *
	 * @param int $id
	 * @return void
	 * @since 1.6
	 */
	public function format_product_extra( $id ) {
		$extras = $this->get_product_extra( $id );
		$product_extra = new Myd_product_extra();
		$product = new Myd_product();

		ob_start();
		include MYD_PLUGIN_PATH . '/templates/products/product-extra.php';
		return ob_get_clean();
	}

	/**
	 * Products by categorie
	 *
	 * @since 1.9.8
	 */
	public function fdm_loop_products( $products, $categorie ) {
		if ( ! $products->have_posts() ) {
			return null;
		}

		ob_start();
		while ( $products->have_posts() ) :
			$products->the_post();
			$product_category = get_post_meta( get_the_ID(), 'product_type', true );
			$is_available = get_post_meta( get_the_ID(), 'product_available', true );
			$match = false;
			if ( is_array( $product_category ) ) {
				$match = in_array( $categorie, $product_category );
			} else {
				$match = $product_category === $categorie;
			}
			if ( $match && $is_available !== 'hide' ) {
				/**
				 * Loop products
				 */
				include MYD_PLUGIN_PATH . '/templates/products/loop-products.php';
			}
		endwhile;
		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Query products
	 *
	 * @since 1.9.8
	 * @return array
	 */
	public function get_products() {
		$args = [
			'post_type' => 'mydelivery-produtos',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'no_found_rows' => true
		];

		return new \WP_Query( $args );
	}

	/**
	 * Get categories
	 *
	 * @return arrray
	 * @since 1.9.8
	 */
	public function get_categories() {

		$categories = get_option( 'fdm-list-menu-categories' );

		if ( empty( $categories ) ) {
			return NULL;
		}

		$categories = explode( ",", $categories );
		$categories = array_map( 'trim', $categories );
		return $categories;
	}

	/**
	 * Loop products
	 *
	 * @return void
	 */
	public function fdm_loop_products_per_categorie( $categories = array() ) {
		$categories = ! empty( $categories ) ? $categories : $this->get_categories();

		if ( $categories === null ) {
			return esc_html__( 'For show correct produts, create categories on plugin settings and add in produtcs.', 'myd-delivery-pro' );
		}

		$grid_columns = get_option( 'myd-products-list-columns' );
		$products_object = $this->get_products();
		$products = '';
		foreach ( $categories as $categorie ) {
			$categorie_tag = str_replace( ' ', '-', $categorie );
			$product_by_categorie = $this->fdm_loop_products( $products_object, $categorie );

			if ( $product_by_categorie !== NULL && ! empty( $product_by_categorie ) ) {
				$products .= '<h2 class="myd-product-list__title" id="fdm-' . $categorie_tag . '">' . $categorie . '</h2><div class="myd-product-list myd-' . $categorie_tag . ' ' . $grid_columns . '">' . $product_by_categorie . '</div>';
			}
		}

		return $products;
	}

	/*
	*
	* Get categories options
	*
	*/
	public function fdm_list_categories () {

		$categories = get_option('fdm-list-menu-categories');

		if( !empty($categories) ) {

			$categories = get_option('fdm-list-menu-categories');
			$categories = explode(",", $categories);
			$categories = array_map('trim', $categories);

			return $categories;
		}
	}

	/**
	 * TEMP. code add JS dependencies to footer
	 *
	 * @return string
	 */
	public function add_js_dependencies() {
		/**
		 * Delivery time to move to Class
		 *
		 * TODO: Remove to class/method
		 *
		 * @return JSON
		 */
		$date = current_time( 'Y-m-d' );
		$current_week_day = strtolower( date( 'l', strtotime( $date ) ) );
		$delivery_time = get_option( 'myd-delivery-time' );
		if( isset( $delivery_time[$current_week_day] ) ) {
			$current_delivery_time = $delivery_time[ $current_week_day ];
		} else {
			$current_delivery_time = 'false';
		}

		/**
		 * Delivery mode and options
		 *
		 * TODO: move to class/method
		 *
		 * @since 1.9.4
		 */
		$shipping_type = get_option( 'myd-delivery-mode' );
		$shipping_options = get_option( 'myd-delivery-mode-options' );
		if ( isset( $shipping_options[ $shipping_type ] ) ) {
			if ( $shipping_type === 'per-distance' ) {
				$shipping_options[ $shipping_type ]['originAddress'] = array(
					'latitude' => get_option( 'myd-shipping-distance-address-latitude' ),
					'longitude' => get_option( 'myd-shipping-distance-address-longitude' ),
				);
				$shipping_options[ $shipping_type ]['googleApi'] = array(
					'key' => get_option( 'myd-shipping-distance-google-api-key' ),
				);
			}

			$shipping_options = $shipping_options[ $shipping_type ];
		} else {
			$shipping_options = 'false';
		}

		$store_data = array(
			'auth' => array(
				'isLoggedIn' => is_user_logged_in(),
				'loginUrl'   => wp_login_url(),
			),
			'currency' => array(
				'symbol' => Store_Data::get_store_data( 'currency_simbol' ),
				'decimalSeparator' => get_option( 'fdm-decimal-separator' ),
				'decimalNumbers' => intval( get_option( 'fdm-number-decimal' ) ),
			),
			'countryCode' => Store_Data::get_store_data( 'country_code' ),
			'forceStore' => get_option( 'myd-delivery-force-open-close-store' ),
			'deliveryTime' => $current_delivery_time,
			'deliveryShipping' => array(
				'method' => \esc_attr( $shipping_type ),
				'options' => $shipping_options,
			),
			'minimumPurchase' => get_option( 'myd-option-minimum-price' ),
			'autoRedirect' => get_option( 'myd-option-redirect-whatsapp' ),
			'messages' => array(
				'storeClosed' => esc_html__( 'The store is closed', 'myd-delivery-pro' ),
				'cartEmpty' => esc_html__( 'Cart empty', 'myd-delivery-pro' ),
				'addToCard' => esc_html__( 'Added to cart', 'myd-delivery-pro' ),
				'deliveryAreaError' => esc_html__( 'Desculpe, o delivery não atende essa região.', 'myd-delivery-pro' ),
				'invalidCoupon' => esc_html__( 'Invalid coupon', 'myd-delivery-pro' ),
				'removedFromCart' => esc_html__( 'Removed from cart', 'myd-delivery-pro' ),
				'extraRequired' => esc_html__( 'Select required extra', 'myd-delivery-pro' ),
				'extraMin' => esc_html__( 'Select the minimum required for the extra', 'myd-delivery-pro' ),
				'inputRequired' => esc_html__( 'Required inputs empty', 'myd-delivery-pro' ),
				'loginRequired' => esc_html__( 'Faça login para continuar', 'myd-delivery-pro' ),
				'minimumPrice' => esc_html__( 'The minimum order is', 'myd-delivery-pro' ),
				'template' => false,
				'shipping' => array(
					'mapApiError' => esc_html__( 'Sorry, error on request to calculate delivery distance', 'myd-delivery-pro' ),
					'outOfArea' => esc_html__( 'Sorry, your address is out of our delivery area', 'myd-delivery-pro' ),
				),
			),
		);

		$store_data = \wp_json_encode( $store_data, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE );
		return 'const mydStoreInfo = ' . $store_data . ';';
	}

	/**
	 * Creat front end template
	 *
	 * @since 1.8
	 * @access public
	 */
	public function fdm_list_products_html( $args = array() ) {
		if ( Store_Data::$template_dependencies_loaded === false ) {
			wp_add_inline_script( 'myd-create-order', $this->add_js_dependencies(), 'before' );
		}

		\wp_enqueue_script( 'myd-create-order' );
		\wp_enqueue_style( 'myd-delivery-frontend' );
		// Ensure the delivery-frontend dependency stub is enqueued so scripts depending on it
		// will be printed after the CSS (ordering helper)
		if (\function_exists('wp_enqueue_script')) {
			\wp_enqueue_script( 'myd-delivery-frontend-dep' );
		}

		ob_start();
	?>
	<!-- Splash screen: cobre tela antes do CSS carregar -->
	<div id="myd-splash-screen" style="position:fixed;inset:0;z-index:999999;background:#ea1d2b;display:flex;align-items:center;justify-content:center;flex-direction:column;transition:opacity 0.4s ease-in-out;">
		<svg width="160" height="150" viewBox="0 0 489.33 461.2" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<g><path fill="#010101" d="M15.67,299.92c-3.86,0-7.61-1.43-10.5-4.03-3.29-2.96-5.17-7.2-5.17-11.62v-107.31c0-4.46,1.91-8.73,5.24-11.69,3.33-2.97,7.79-4.37,12.22-3.86,0,0,25.51,1.62,37.95,4.42s13.74,10.08,13.74,10.08c.66,1.74,1.02,3.61,1.02,5.57v17.21c0,4.26-1.76,8.38-4.84,11.32-1.05,1.01-2.24,1.85-3.51,2.52,2.37,2.76,3.81,6.33,3.81,10.19v17.28c0,8.22-6.43,15.09-14.64,15.63l-2.7.16.16,26.61c0,8.15-6.04,14.85-14.06,15.67l-17.11,1.78c-.54.05-1.07.08-1.61.08ZM31.41,269.21s-.02,0-.03,0h.03ZM35.24,205.97h.16c-.05,0-.11,0-.16,0ZM48.4,200.51v.12s0-.08,0-.12Z"/><path fill="#010101" d="M102.36,292.48l-7.6.41c-2.5.14-4.93-.31-7.11-1.26-2.08,1.33-4.48,2.18-7.07,2.4l-16.41,1.33c-4.43.34-8.74-1.18-11.92-4.13-3.18-2.95-5.01-7.14-5.01-11.48v-92.6c0-8.5,6.91-15.52,15.39-15.66,7.72-.14,27.24.11,40.91,5.62,20.13,8.12,21.05,27.3,21.44,35.49.73,15.45-4.35,25.13-9.78,31l9.92,27.46c1.68,4.71,1.03,9.93-1.71,14.02-2.44,3.66-6.35,6.12-10.67,6.78l-10.4.63ZM78.77,215.85h0ZM79.07,212h0ZM63.21,200.48s-.04,0-.06,0c.02,0,.04,0,.06,0Z"/><path fill="#010101" d="M114.24,291.88c-4.54,0-8.83-1.94-11.81-5.38-3.08-3.54-4.41-8.22-3.64-12.85l13.9-85.16c1.37-8.04,8.45-13.64,16.51-13.1h.11s26.35,1.98,26.35,1.98c7.12.58,12.94,5.89,14.15,12.94l14.05,81.48c.77,4.56-.47,9.16-3.4,12.68-2.93,3.52-7.23,5.57-11.8,5.64l-16.79.25c-4.14,0-7.97-1.64-10.8-4.33-2.78,3.2-6.81,5.26-11.27,5.42l-15.13.41c-.14,0-.28,0-.43,0ZM127.41,278.3v.07s0-.05,0-.07ZM165.04,272.69l.02.13s-.01-.09-.02-.13Z"/><path fill="#010101" d="M176.18,289.28c-3.96,0-7.87-1.56-10.78-4.34-3.08-2.94-4.84-7.07-4.84-11.33v-79.86c0-8.66,7.02-15.68,15.65-15.68h16.12c3.59,0,6.96,1.21,9.65,3.31,2.66-2.07,5.99-3.31,9.59-3.31h14.75c8.64,0,15.66,7.02,15.66,15.66v78.93c0,8.49-6.91,15.51-15.39,15.66l-15.79.34c-3.55.07-6.9-1.05-9.62-3.05-2.39,1.74-5.3,2.83-8.47,2.98h-.1s-15.74.66-15.74.66c-.23.01-.46.02-.69.02ZM222.9,268.2l.05.13s-.03-.08-.05-.13ZM175.7,260.29s-.07,0-.1,0h.1ZM226.02,259.33s-.08,0-.11,0h.11Z"/><path fill="#010101" d="M264.07,289.14c-8.09,0-16.97-1.8-24.92-7.44-14.28-10.12-20.65-28.89-18.95-55.79,1.4-22.03,8.94-37.24,22.43-45.21,19.47-11.5,42.85-2.08,49.55,1.08,3.18,1.51,5.64,3.97,7.17,6.9l4.12,4.22-7.61,19.85c1.41.72,2.71,1.65,3.85,2.77,2.97,2.92,4.67,6.99,4.67,11.16v37.53c0,6.87-3.6,11.22-6.3,13.89-2.66,2.63-5.89,4.45-8.21,5.55l-.7.32c-2.92,1.24-13.14,5.16-25.09,5.16ZM273.05,255.79h-.09s.06,0,.09,0ZM268.49,244.62h0ZM266.54,208.2s-.01.03-.02.05l.02-.05ZM273.05,191.15s-.02.04-.03.07l.03-.07Z"/><path fill="#010101" d="M323.53,291.53c-1.3,0-2.59-.05-3.88-.16-21.84-1.84-37.25-18.11-36.73-38.72v-59.69c0-8.51,6.66-15.38,15.16-15.65h0s16.06-.5,16.06-.5c3.04-.08,6.05.72,8.62,2.28,2.35-1.69,5.19-2.75,8.28-2.92l16.63-.93c4.22-.26,8.47,1.28,11.6,4.23,3.13,2.95,4.93,7.11,4.93,11.41v66.28s-.15.98-.15.98c-3.08,22.42-22.09,33.39-40.54,33.4ZM364.16,257.16v.09s0-.06,0-.09ZM299,206.29h0s.02,0,.03,0h-.03ZM349.37,204.18h-.12s.08,0,.12,0Z"/><path fill="#010101" d="M415.04,292.92h-17.53c-4.88,0-9.42-2.3-12.34-6.03-3.27,3.26-7.91,4.98-12.77,4.44l-18.05-2.1c-4.85-.55-9.22-3.38-11.71-7.59-2.51-4.25-2.86-9.51-.94-14.05l16.01-37.8-14.87-37.01v-1.84c-.08-.94-.08-1.88,0-2.81v-12.66h8.72c2-.98,4.2-1.53,6.49-1.59l17.84-.5c3.2-.06,6.2.83,8.75,2.46,2.43-2.05,5.48-3.35,8.81-3.63h.13s17.64-1.33,17.64-1.33c5.48-.39,10.62,2,13.82,6.39,3.2,4.38,3.9,10.01,1.89,15.05l-14.92,37.46,17.47,41.41c2.04,4.86,1.52,10.37-1.39,14.75-2.91,4.38-7.79,6.99-13.04,6.99ZM361.55,271.31s0,.01,0,.02v-.02ZM409.83,224.63l.06.15-.06-.15ZM399.99,181.55s-.01.03-.02.04l.02-.04Z"/><path fill="#010101" d="M446.84,297.4c-14.21,0-25.33-5.4-33.05-16.06-7.89-10.89-12.03-27.44-12.64-50.6-.63-23.65,4.65-41.38,15.69-52.72,7.92-8.13,18.3-12.43,30-12.43l1.71.04c13.92.06,24.9,6.42,31.76,18.39,6.16,10.74,9.02,25.83,9.02,47.48,0,23.49-3.26,39.35-10.27,49.91-7.05,10.62-17.89,16-32.22,16Z"/></g>
			<g><path fill="#fbb80b" d="M54.65,180.33l-38.86-4.53c-.69-.08-1.29.46-1.29,1.15v107.31c0,.69.6,1.22,1.28,1.15l17.13-1.78c.59-.06,1.04-.56,1.04-1.16l-.24-39.28c0-.62.48-1.13,1.09-1.16l15.24-.88c.61-.04,1.09-.54,1.09-1.16v-17.28c0-.61-.47-1.11-1.08-1.16l-15.74-1.11c-.62-.04-1.1-.57-1.08-1.2l.68-19.18c.02-.65.57-1.15,1.21-1.12l19.34.9c.66.03,1.21-.5,1.21-1.16v-17.21c0-.59-.44-1.08-1.03-1.15h.01Z"/><path fill="#fbb80b" d="M98.26,239.38c-.21-.57.06-1.22.62-1.46,3.13-1.37,12.47-6.95,11.63-24.63-.45-9.37-1.84-18.49-12.38-22.74-11.56-4.66-30.45-4.66-35.25-4.57-.63,0-1.13.53-1.13,1.16v92.6c0,.68.58,1.21,1.25,1.16l16.38-1.33c.6-.05,1.07-.55,1.07-1.16v-33.9c0-.64.52-1.16,1.16-1.16h0c.51,0,.96.33,1.11.82l10.1,33.42c.15.51.64.85,1.17.82l16.48-.88c.78-.04,1.29-.82,1.03-1.55l-13.23-36.61h-.01ZM85.39,226.97c-2.25.36-3.7.22-4.54.03-.53-.12-.89-.61-.87-1.15l.58-22.58c.02-.66.57-1.17,1.23-1.13l4.99.31s6.95,0,6.82,10.48-4.29,13.42-8.21,14.05h0Z"/><path fill="#fbb80b" d="M154.45,191.81l-26.22-1.94c-.6-.04-1.13.38-1.23.97l-13.91,85.2c-.12.72.45,1.37,1.18,1.35l14.99-.41c.55-.02,1.02-.42,1.11-.96l2.95-16.77c.1-.57.6-.98,1.18-.96l12.93.44c.56.02,1.03.44,1.11,1l2.16,15.15c.08.58.58,1,1.17,1l16.57-.25c.71-.01,1.25-.65,1.13-1.36l-14.05-81.48c-.09-.52-.53-.92-1.06-.96v-.02h-.01ZM135.58,241.84l4.56-24.48c.24-1.27,2.06-1.26,2.28.02l4.22,24.48c.12.71-.42,1.36-1.14,1.36h-8.78c-.73,0-1.27-.66-1.14-1.37h0Z"/><path fill="#fbb80b" d="M175.05,193.76v79.86c0,.66.55,1.19,1.21,1.16l15.74-.66c.62-.03,1.11-.54,1.11-1.16v-36.46c0-1.29,1.79-1.62,2.25-.41l14.01,37.33c.17.46.62.76,1.11.75l15.85-.34c.63-.01,1.14-.53,1.14-1.16v-78.93c0-.64-.52-1.16-1.16-1.16h-14.75c-.63,0-1.15.51-1.16,1.14l-.53,34.87c-.02,1.27-1.77,1.59-2.24.41l-14.23-35.69c-.18-.44-.6-.73-1.08-.73h-16.12c-.64,0-1.16.52-1.16,1.16v.02h0Z"/><path fill="#fbb80b" d="M286.58,196.37c.22-.57-.04-1.21-.59-1.47-6.87-3.24-48-20.41-51.32,31.93-4.15,65.32,45.41,45.24,48.83,43.79.06-.03,2.76-1.23,4.38-2.83,1.53-1.51,1.99-2.38,1.99-3.57v-37.53c0-.65-.53-1.17-1.18-1.16l-24.03.52c-.63,0-1.13.52-1.13,1.15l-.16,13.69c0,.69.59,1.24,1.28,1.17l6.91-.69c.68-.07,1.28.47,1.28,1.15v13.03c0,.42-.22.81-.59,1.01-2.17,1.2-9.93,4.6-15.89-3.95-7.01-10.04-9.51-49.06,11.88-44.8,5.2,1.03,8.31,3.96,9.84,5.89.56.7,1.67.55,1.99-.29l6.53-17.02-.02-.02Z"/><path fill="#fbb80b" d="M298.54,191.8c-.63.02-1.12.53-1.12,1.16v59.89c-.95,30.28,48.2,32.96,52.28,3.31,0-.06.01-.12.01-.18v-65.11c0-.67-.56-1.2-1.22-1.16l-16.63.93c-.61.03-1.09.54-1.09,1.16v59.73c0,.12-.02.24-.05.36-2.75,9.3-14.54,9.24-14.92-.56v-58.87c0-.65-.54-1.18-1.2-1.16l-16.06.5h0Z"/><path fill="#fbb80b" d="M357.34,189.97l15.87,39.51c.11.28.11.6,0,.88l-18.16,42.87c-.3.71.17,1.52.93,1.6l18.01,2.09c.54.06,1.05-.26,1.23-.77l9.04-25.59c.36-1.02,1.81-1.03,2.18,0l9.98,27.11c.17.46.6.76,1.09.76h17.53c.83,0,1.39-.85,1.07-1.61l-19.6-46.46c-.12-.28-.12-.6,0-.88l16.95-42.55c.32-.8-.31-1.65-1.16-1.59l-17.64,1.32c-.47.04-.88.35-1.02.81l-6.95,21.88c-.34,1.06-1.84,1.08-2.2.03l-7.2-20.72c-.17-.48-.62-.79-1.13-.78l-17.76.5c-.81.02-1.34.84-1.04,1.59h-.02Z"/><path fill="#fbb80b" d="M446.84,180.08c-16.38,0-32.2,12.53-31.2,50.27,1.14,43.2,14.82,52.55,31.2,52.55s27.99-8.25,27.99-51.41c0-38.89-11.61-51.41-27.99-51.41h0ZM445.84,263.93c-6.34,0-11.64-5.9-12.08-33.15-.39-23.81,5.74-31.71,12.08-31.71s10.84,7.9,10.84,32.43c0,27.23-4.5,32.43-10.84,32.43Z"/></g>
		</svg>
		<style>@keyframes myd-spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}</style>
		<div style="width:48px;height:48px;border:4px solid rgba(255,255,255,0.2);border-top-color:rgba(255,255,255,0.7);border-radius:50%;animation:myd-spin 0.8s linear infinite;will-change:transform;"></div>
	</div>
	<script>
	(function(){
		var splash = document.getElementById('myd-splash-screen');
		if(!splash) return;
		function hide(){
			splash.style.opacity = '0';
			splash.style.pointerEvents = 'none';
			setTimeout(function(){ if(splash.parentNode) splash.parentNode.removeChild(splash); }, 500);
		}
		if(document.readyState === 'complete'){ hide(); }
		else { window.addEventListener('load', hide); }
	})();
	</script>
	<?php

		/**
		 * Include templates
		 *
		 * @since 1.9
		 */
		include MYD_PLUGIN_PATH . '/templates/template.php';

		return ob_get_clean();
	}
}

$delivery_page_shortcode = new Fdm_products_show();
$delivery_page_shortcode->register_shortcode();
