<?php

namespace MydPro\Includes\Admin;

use MydPro\Includes\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Posts class.
 */
class Custom_Posts {
	/**
	 * Custom posts
	 *
	 * @since 1.9.6
	 */
	protected $custom_posts;

	/**
	 * License status
	 *
	 * @since 1.9.6
	 */
	protected $license;

	/**
	 * Construct the class
	 *
	 * @since 1.9.6
	 */
	public function __construct() {
		$this->license = Plugin::instance()->license;

		$this->custom_posts = [
			'mydelivery-produtos' => [
				'condition' => true,
				'args' => [
					'label' => __('MyDelivery Products', 'myd-delivery-pro'),
					'labels' => [
						'name' => __('Products', 'myd-delivery-pro'),
						'singular_name' => __('Products', 'myd-delivery-pro'),
						'menu_name' => __('Products', 'myd-delivery-pro'),
						'all_items' => __('Products', 'myd-delivery-pro'),
						'add_new' => __('Add product', 'myd-delivery-pro'),
						'add_new_item' => __('Add product', 'myd-delivery-pro'),
						'edit_item' => __('Edit product', 'myd-delivery-pro'),
						'new_item' => __('New product', 'myd-delivery-pro'),
						'view_item' => __('View product', 'myd-delivery-pro'),
						'view_items' => __('View products', 'myd-delivery-pro'),
						'search_items' => __('Search products', 'myd-delivery-pro')
					],
					'description' => 'Plugin MyD Delivery products menu.',
					'public' => true,
					'publicly_queryable' => false,
					'show_ui' => true,
					'delete_with_user' => false,
					'show_in_rest' => true,
					'rest_base' => '',
					'rest_controller_class' => 'WP_REST_Posts_Controller',
					'has_archive' => false,
					'show_in_menu' => 'myd-delivery-dashoboard',
					'show_in_nav_menus' => true,
					'exclude_from_search' => false,
					'capability_type' => 'post',
					'map_meta_cap' => true,
					'hierarchical' => false,
					'rewrite' => [
						'slug' => 'mydelivery-produtos',
						'with_front' => true
					],
					'query_var' => true,
					'supports' => [
						'title'
					]
				]
			],
			'mydelivery-orders' => [
				'condition' => true,
				'args' => [
					'label' => __('MyDelivery Orders', 'myd-delivery-pro'),
					'labels' => [
						'name' => __('Orders', 'myd-delivery-pro'),
						'singular_name' => __('Order', 'myd-delivery-pro'),
						'menu_name' => __('Orders', 'myd-delivery-pro'),
						'all_items' => __('Orders', 'myd-delivery-pro'),
						'add_new' => __('Add order', 'myd-delivery-pro'),
						'add_new_item' => __('Add order', 'myd-delivery-pro'),
						'edit_item' => __('Edit order', 'myd-delivery-pro'),
						'new_item' => __('New order', 'myd-delivery-pro'),
						'view_item' => __('View order', 'myd-delivery-pro'),
						'view_items' => __('View orders', 'myd-delivery-pro'),
						'search_items' => __('Search orders', 'myd-delivery-pro'),
					],
					'description' => 'Plugin MyDelivery orders menu.',
					'public' => true,
					'publicly_queryable' => false,
					'show_ui' => true,
					'delete_with_user' => false,
					'show_in_rest' => true,
					'rest_base' => '',
					'rest_controller_class' => 'WP_REST_Posts_Controller',
					'has_archive' => false,
					'show_in_menu' => 'myd-delivery-dashoboard',
					'show_in_nav_menus' => true,
					'exclude_from_search' => false,
					'capability_type' => 'post',
					'map_meta_cap' => true,
					'hierarchical' => false,
					'rewrite' => [
						'slug' => 'mydelivery-orders',
						'with_front' => true
					],
					'query_var' => true,
					'supports' => [
						'title'
					]
				]
			],
			'mydelivery-coupons' => [
				'condition' => true,
				'args' => [
					'label' => __('MyDelivery Coupons', 'myd-delivery-pro'),
					'labels' => [
						'name' => __('Coupons', 'myd-delivery-pro'),
						'singular_name' => __('Coupons', 'myd-delivery-pro'),
						'menu_name' => __('Coupons', 'myd-delivery-pro'),
						'all_items' => __('Coupons', 'myd-delivery-pro'),
						'add_new' => __('Add coupon', 'myd-delivery-pro'),
						'add_new_item' => __('Add coupon', 'myd-delivery-pro'),
						'edit_item' => __('Edit coupon', 'myd-delivery-pro'),
						'new_item' => __('New coupon', 'myd-delivery-pro'),
						'view_item' => __('View coupon', 'myd-delivery-pro'),
						'view_items' => __('View coupons', 'myd-delivery-pro'),
						'search_items' => __('Search coupons', 'myd-delivery-pro'),
					],
					'description' => 'Coupons for MyD Delivery',
					'public' => true,
					'publicly_queryable' => false,
					'show_ui' => true,
					'delete_with_user' => false,
					'show_in_rest' => true,
					'rest_base' => '',
					'rest_controller_class' => 'WP_REST_Posts_Controller',
					'has_archive' => false,
					'show_in_menu' => 'myd-delivery-dashoboard',
					'show_in_nav_menus' => true,
					'exclude_from_search' => false,
					'capability_type' => 'post',
					'map_meta_cap' => true,
					'hierarchical' => false,
					'rewrite' => [
						'slug' => 'mydelivery-coupons',
						'with_front' => true
					],
					'query_var' => true,
					'supports' => [
						'title'
					]
				]
			],
			'mydelivery-extras' => [
				'condition' => true,
				'args' => [
					'label' => __('MyDelivery Extras', 'myd-delivery-pro'),
					'labels' => [
						'name' => __('Extras', 'myd-delivery-pro'),
						'singular_name' => __('Extra', 'myd-delivery-pro'),
						'menu_name' => __('Extras', 'myd-delivery-pro'),
						'all_items' => __('Extras', 'myd-delivery-pro'),
						'add_new' => __('Adicionar extra', 'myd-delivery-pro'),
						'add_new_item' => __('Adicionar extra', 'myd-delivery-pro'),
						'edit_item' => __('Editar extra', 'myd-delivery-pro'),
						'new_item' => __('Novo extra', 'myd-delivery-pro'),
						'view_item' => __('Ver extra', 'myd-delivery-pro'),
						'view_items' => __('Ver extras', 'myd-delivery-pro'),
						'search_items' => __('Buscar extras', 'myd-delivery-pro'),
					],
					'description' => 'Extras centralizados para produtos MyD Delivery',
					'public' => true,
					'publicly_queryable' => false,
					'show_ui' => true,
					'delete_with_user' => false,
					'show_in_rest' => true,
					'rest_base' => '',
					'rest_controller_class' => 'WP_REST_Posts_Controller',
					'has_archive' => false,
					'show_in_menu' => 'myd-delivery-dashoboard',
					'show_in_nav_menus' => true,
					'exclude_from_search' => false,
					'capability_type' => 'post',
					'map_meta_cap' => true,
					'hierarchical' => false,
					'rewrite' => [
						'slug' => 'mydelivery-extras',
						'with_front' => true
					],
					'query_var' => true,
					'supports' => [
						'title'
					]
				]
			],
			'myd_caixa' => [
				'condition' => true,
				'args' => [
					'label' => __('Caixa MyDelivery', 'myd-delivery-pro'),
					'labels' => [
						'name' => __('Histórico de Caixas', 'myd-delivery-pro'),
						'singular_name' => __('Caixa', 'myd-delivery-pro'),
						'menu_name' => __('Caixa', 'myd-delivery-pro'),
						'all_items' => __('Histórico de Caixas', 'myd-delivery-pro'),
						'add_new' => __('Novo Turno', 'myd-delivery-pro'),
						'add_new_item' => __('Novo Turno de Caixa', 'myd-delivery-pro'),
						'edit_item' => __('Detalhes do Caixa', 'myd-delivery-pro'),
						'new_item' => __('Novo Caixa', 'myd-delivery-pro'),
						'view_item' => __('Ver Caixa', 'myd-delivery-pro'),
						'view_items' => __('Ver Fechamentos', 'myd-delivery-pro'),
						'search_items' => __('Buscar Fechamentos', 'myd-delivery-pro'),
					],
					'description' => 'Histórico e registro de turnos do Caixa',
					'public' => false,
					'publicly_queryable' => false,
					'show_ui' => true,
					'delete_with_user' => false,
					'show_in_rest' => true,
					'rest_base' => 'myd_caixa',
					'rest_controller_class' => 'WP_REST_Posts_Controller',
					'has_archive' => false,
					'show_in_menu' => 'myd-delivery-dashoboard',
					'show_in_nav_menus' => false,
					'exclude_from_search' => true,
					'capability_type' => 'post',
					'map_meta_cap' => true,
					'hierarchical' => false,
					'rewrite' => false,
					'query_var' => false,
					'supports' => [
						'title'
					]
				]
			]
		];
	}

	/**
	 * Register custom posts
	 *
	 * @since 1.9.6
	 */
	public function register_custom_posts() {
		$custom_posts = apply_filters( 'mydp_before_regigster_custom_posts', $this->custom_posts );

		foreach ( $custom_posts as $custom_post => $options ) {
			if ( $options['condition'] === false || $options['condition'] === true && $this->license->get_status() === 'active' || $this->license->get_status() === 'expired' || $this->license->get_status() === 'mismatch' ) {
				register_post_type( $custom_post, $options['args'] );
			}
		}

		// Adicionar colunas personalizadas para cupons
		add_filter( 'manage_mydelivery-coupons_posts_columns', [ $this, 'add_coupon_columns' ] );
		add_action( 'manage_mydelivery-coupons_posts_custom_column', [ $this, 'fill_coupon_columns' ], 10, 2 );
		add_filter( 'manage_edit-mydelivery-coupons_sortable_columns', [ $this, 'make_coupon_columns_sortable' ] );
		add_action( 'admin_head', [ $this, 'add_coupon_admin_styles' ] );
		// Adiciona ação de linha para copiar o cupom na lista
		add_filter( 'post_row_actions', [ $this, 'add_coupon_row_actions' ], 10, 2 );
	}

	/**
	 * Add custom styles for coupon admin columns
	 */
	public function add_coupon_admin_styles() {
		$screen = get_current_screen();
		if ( $screen && $screen->post_type === 'mydelivery-coupons' ) {
			echo '<style>
				/* Coupon Type Badges */
				.coupon-type-badge {
					display: inline-block;
					padding: 4px 8px;
					border-radius: 12px;
					font-size: 11px;
					font-weight: bold;
					text-transform: uppercase;
					color: white;
					background: #666;
				}
				.coupon-type-total { background: #0ea500ff; }
				.coupon-type-delivery { background: #fda500ff; }

				/* Usage Info */
				.coupon-usage-info {
					min-width: 100px;
				}
				.usage-bar {
					width: 100%;
					height: 8px;
					background: #f0f0f1;
					border-radius: 4px;
					overflow: hidden;
					margin-bottom: 4px;
				}
				.usage-fill {
					height: 100%;
					background: linear-gradient(90deg, #00a32a 0%, #ffb900 70%, #d63638 100%);
					transition: width 0.3s ease;
				}
				.usage-text {
					font-size: 11px;
					color: #646970;
				}
				.usage-unlimited {
					font-size: 11px;
					color: #00a32a;
					font-weight: bold;
				}

				/* Expiry Status */
				.coupon-expired {
					color: #d63638;
					font-weight: bold;
				}
				.coupon-expiring-soon {
					color: #ffb900;
					font-weight: bold;
				}
				.coupon-valid {
					color: #00a32a;
					font-weight: bold;
				}
				.no-expiry {
					color: #646970;
					font-style: italic;
				}

				/* Status */
				.coupon-status-active {
					color: #00a32a;
					font-weight: bold;
				}
				.coupon-status-inactive {
					color: #d63638;
					font-weight: bold;
				}

				/* Column widths */
				.column-coupon_type { width: 120px; }
				.column-coupon_usage { width: 150px; }
				.column-coupon_expiry { width: 120px; }
				.column-coupon_status { width: 80px; }
			</style>';
		}
	}

	/**
	 * Add custom columns to coupon list
	 *
	 * @param array $columns
	 * @return array
	 */
	public function add_coupon_columns( $columns ) {
		$new_columns = array();
		
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			
			// Adicionar colunas após o título
			if ( $key === 'title' ) {
				$new_columns['coupon_type'] = __( 'Tipo', 'myd-delivery-pro' );
				$new_columns['coupon_usage'] = __( 'Uso', 'myd-delivery-pro' );
				$new_columns['coupon_expiry'] = __( 'Expiração', 'myd-delivery-pro' );
				$new_columns['coupon_status'] = __( 'Status', 'myd-delivery-pro' );
			}
		}
		
		return $new_columns;
	}

	/**
	 * Fill custom columns with data
	 *
	 * @param string $column
	 * @param int $post_id
	 */
	public function fill_coupon_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'coupon_type':
				$type = get_post_meta( $post_id, 'myd_coupon_type', true );
				switch ( $type ) {
					case 'discount-total':
						echo '<span class="coupon-type-badge coupon-type-total">' . __( 'Desconto Total', 'myd-delivery-pro' ) . '</span>';
						break;
					case 'discount-delivery':
						echo '<span class="coupon-type-badge coupon-type-delivery">' . __( 'Desconto na Entrega', 'myd-delivery-pro' ) . '</span>';
						break;
					default:
						echo '<span class="coupon-type-badge">' . ucfirst( str_replace( '-', ' ', $type ) ) . '</span>';
						break;
				}
				break;

			case 'coupon_usage':
				$usage_limit = intval( get_post_meta( $post_id, 'myd_coupon_usage_limit', true ) );
				$coupon = new \MydPro\Includes\Coupon( $post_id );
				$usage_count = $coupon->get_current_usage_count();
				
				if ( $usage_limit > 0 ) {
					$percentage = ( $usage_count / $usage_limit ) * 100;
					$remaining = $usage_limit - $usage_count;
					
					echo '<div class="coupon-usage-info">';
					echo '<div class="usage-bar">';
					echo '<div class="usage-fill" style="width: ' . min( $percentage, 100 ) . '%"></div>';
					echo '</div>';
					echo '<span class="usage-text">' . sprintf( __( '%d/%d usos', 'myd-delivery-pro' ), $usage_count, $usage_limit ) . '</span>';
					echo '</div>';
				} else {
					echo '<span class="usage-unlimited">' . sprintf( __( '%d usos (ilimitado)', 'myd-delivery-pro' ), $usage_count ) . '</span>';
				}
				break;

			case 'coupon_expiry':
				$expiry_date = get_post_meta( $post_id, 'myd_coupon_expiry_date', true );
				
				if ( ! empty( $expiry_date ) ) {
					$expiry_timestamp = strtotime( $expiry_date );
					$current_timestamp = current_time( 'timestamp' );
					
					if ( $current_timestamp > $expiry_timestamp ) {
						echo '<span class="coupon-expired">' . __( 'Expirado', 'myd-delivery-pro' ) . '</span><br>';
						echo '<small>' . date_i18n( get_option( 'date_format' ), $expiry_timestamp ) . '</small>';
					} else {
						$time_diff = $expiry_timestamp - $current_timestamp;
						$days_remaining = floor( $time_diff / ( 60 * 60 * 24 ) );
						
						if ( $days_remaining <= 7 ) {
							echo '<span class="coupon-expiring-soon">' . sprintf( __( '%d dias restantes', 'myd-delivery-pro' ), $days_remaining ) . '</span><br>';
						} else {
							echo '<span class="coupon-valid">' . sprintf( __( '%d dias restantes', 'myd-delivery-pro' ), $days_remaining ) . '</span><br>';
						}
						echo '<small>' . date_i18n( get_option( 'date_format' ), $expiry_timestamp ) . '</small>';
					}
				} else {
					echo '<span class="no-expiry">' . __( 'Sem expiração', 'myd-delivery-pro' ) . '</span>';
				}
				break;

			case 'coupon_status':
				$coupon = new \MydPro\Includes\Coupon( $post_id );
				$status = $coupon->get_status_info();
				
				if ( $status['is_active'] ) {
					echo '<span class="coupon-status-active">' . __( 'Ativo', 'myd-delivery-pro' ) . '</span>';
				} else {
					echo '<span class="coupon-status-inactive">' . __( 'Inativo', 'myd-delivery-pro' ) . '</span>';
				}
				break;
		}
	}

	/**
	 * Make coupon columns sortable
	 *
	 * @param array $columns
	 * @return array
	 */
	public function make_coupon_columns_sortable( $columns ) {
		$columns['coupon_type'] = 'coupon_type';
		$columns['coupon_expiry'] = 'coupon_expiry';
		$columns['coupon_status'] = 'coupon_status';
		
		return $columns;
	}

	/**
	 * Add a "Copy coupon" row action for coupons list.
	 *
	 * @param array   $actions Existing row actions.
	 * @param WP_Post $post    Post object.
	 * @return array Modified actions.
	 */
	public function add_coupon_row_actions( $actions, $post ) {
		if ( ! is_object( $post ) || $post->post_type !== 'mydelivery-coupons' ) {
			return $actions;
		}

		$copy_label = __( 'Copiar cupom', 'myd-delivery-pro' );
		$copied_msg = esc_js( __( 'Cupom copiado para a área de transferência', 'myd-delivery-pro' ) );
		$fail_msg = esc_js( __( 'Não foi possível copiar o cupom', 'myd-delivery-pro' ) );

		// Build full message according to coupon properties
		$coupon = new \MydPro\Includes\Coupon( $post->ID );
		$code = $coupon->code;
		$type = $coupon->type; // discount-total | discount-delivery
		$format = $coupon->discount_format; // percent | amount
		$amount = $coupon->amount;
		$expiry = $coupon->expiry_date;
		$site = home_url();

		if ( ! empty( $expiry ) ) {
			$expiry_ts = strtotime( $expiry );
			$expiry_text = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $expiry_ts );
		} else {
			$expiry_text = '';
		}

		// If expiry is set, build a line to include in the message; otherwise leave empty
		$expiry_segment = '';
		if ( ! empty( $expiry_text ) ) {
			$expiry_segment = sprintf( "Cupom válido até %s\n", $expiry_text );
		}

		if ( $format === 'percent' ) {
			$discount_part = sprintf( '%s%%', $amount );
		} else {
			$discount_part = 'R$ ' . number_format( floatval( $amount ), 2, ',', '.' );
		}

		if ( $type === 'discount-total' ) {
			$message = sprintf(
				"🎟️ Utilize o cupom: %s\nE garanta %s de desconto no seu pedido.\n%s*Válido apenas para pedidos feitos no site %s",
				$code,
				$discount_part,
				$expiry_segment,
				$site
			);
		} else { // discount-delivery
			$message = sprintf(
				"🎟️ Utilize o cupom: %s\nE garanta %s de desconto no valor da ENTREGA do seu pedido.\n%s*Válido apenas para pedidos feitos no site %s",
				$code,
				$discount_part,
				$expiry_segment,
				$site
			);
		}

		// Encode message as JS string safely
		$message_json = wp_json_encode( $message );
		$onclick = "var s = {$message_json}; navigator.clipboard.writeText(s).then(function(){alert('{$copied_msg}');}).catch(function(){alert('{$fail_msg}');}); return false;";

		// Insert the copy action before the "edit" action if it exists, otherwise append
		$inserted = false;
		$new_actions = [];
		foreach ( $actions as $key => $action_html ) {
			if ( ! $inserted && $key === 'edit' ) {
				$new_actions['copy_coupon'] = '<a href="#" class="copy-coupon-action" onclick="' . esc_attr( $onclick ) . '">' . esc_html( $copy_label ) . '</a>';
				$inserted = true;
			}
			$new_actions[ $key ] = $action_html;
		}
		if ( ! $inserted ) {
			$new_actions['copy_coupon'] = '<a href="#" class="copy-coupon-action" onclick="' . esc_attr( $onclick ) . '">' . esc_html( $copy_label ) . '</a>';
		}

		return $new_actions;
	}
}
