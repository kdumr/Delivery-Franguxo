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
 * @return array
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

/**
 * Make products columns sortable
 * 
 * @param array $columns
 * @return array
 */
function myd_products_sortable_columns( $columns ) {
	$columns['product_categorie'] = 'product_type';
	$columns['price']             = 'product_price';
	return $columns;
}
add_filter( 'manage_edit-mydelivery-produtos_sortable_columns', 'MydPro\Includes\myd_products_sortable_columns' );

/**
 * Handle custom sort order and filtering
 * 
 * @param \WP_Query $query
 */
function myd_products_custom_query_vars( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'mydelivery-produtos' === $query->get( 'post_type' ) ) {
		// Ordering
		$orderby = $query->get( 'orderby' );
		if ( 'product_type' === $orderby ) {
			$query->set( 'meta_key', 'product_type' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'product_price' === $orderby ) {
			$query->set( 'meta_key', 'product_price' );
			$query->set( 'orderby', 'meta_value_num' );
		}

		// Grouping/Sorting by category by default if no orderby is set
		if ( ! $orderby ) {
			$query->set( 'meta_key', 'product_type' );
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'order', 'ASC' );
		}

		// Filtering
		if ( isset( $_GET['myd_filter_category'] ) && ! empty( $_GET['myd_filter_category'] ) ) {
			$category = sanitize_text_field( $_GET['myd_filter_category'] );
			$meta_query = $query->get( 'meta_query' );
			if ( ! is_array( $meta_query ) ) {
				$meta_query = [];
			}
			$meta_query[] = [
				'key'     => 'product_type',
				'value'   => '"' . $category . '"', // Search inside serialized array
				'compare' => 'LIKE'
			];
			$query->set( 'meta_query', $meta_query );
		}
	}
}
add_action( 'pre_get_posts', 'MydPro\Includes\myd_products_custom_query_vars' );

/**
 * Add Category filter dropdown to products list
 * 
 * @param string $post_type
 */
function myd_products_add_category_filter( $post_type ) {
	if ( 'mydelivery-produtos' !== $post_type ) {
		return;
	}

	$categories_option = get_option( 'fdm-list-menu-categories' );
	if ( ! $categories_option ) {
		return;
	}

	$categories = explode( ',', $categories_option );
	$categories = array_map( 'trim', $categories );

	$selected = isset( $_GET['myd_filter_category'] ) ? sanitize_text_field( $_GET['myd_filter_category'] ) : '';

	echo '<select name="myd_filter_category">';
	echo '<option value="">' . __( 'All categories', 'myd-delivery-pro' ) . '</option>';
	foreach ( $categories as $category ) {
		if ( empty( $category ) ) continue;
		$selected_attr = ( $selected === $category ) ? ' selected="selected"' : '';
		echo '<option value="' . esc_attr( $category ) . '"' . $selected_attr . '>' . esc_html( $category ) . '</option>';
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'MydPro\Includes\myd_products_add_category_filter' );

/**
 * Add JavaScript to visually group the products by category
 */
function myd_products_group_by_category_js() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-mydelivery-produtos' !== $screen->id ) {
		return;
	}

	$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : '';
	// Only apply visual grouping if sorting by category (or default sort)
	if ( '' !== $orderby && 'product_type' !== $orderby ) {
		return;
	}
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		// Wait slightly to ensure table is fully rendered (in case of plugins tweaking it)
		setTimeout(function() {
			var $mainTable = $('table.wp-list-table.posts');
			if ( ! $mainTable.length || ! $mainTable.hasClass('type-mydelivery-produtos') && ! $mainTable.find('tr.type-mydelivery-produtos').length ) {
				return;
			}

			// Capture rows
			var $rows = $mainTable.find('tbody tr').filter(function() {
				return $(this).hasClass('type-mydelivery-produtos');
			});

			if ( $rows.length === 0 ) {
				return; // Empty list
			}

			// Group rows by category
			var categories = {};
			$rows.each(function() {
				var $row = $(this);
				var catTitle = '<?php _e( "Sem Categoria", "myd-delivery-pro" ); ?>';

				var $catCell = $row.find('td.column-product_categorie');
				if ( $catCell.length ) {
					var rawCat = $.trim( $catCell.text() );
					if ( rawCat ) {
						catTitle = rawCat;
					}
				}

				if ( ! categories[catTitle] ) {
					categories[catTitle] = [];
				}
				categories[catTitle].push($row);
			});

			// Build new structure
			var $tableWrapper = $('<div class="myd-categories-wrapper"></div>');
			var $thead = $mainTable.find('thead').clone();
			var $tfoot = $mainTable.find('tfoot').clone();

			// For each category, create a new heading and table
			$.each(categories, function(catName, rows) {
				var $catSection = $('<div class="myd-category-section" style="margin-top: 30px; margin-bottom: 20px;"></div>');
				$catSection.append('<h2 style="font-size: 1.5em; margin-bottom: 10px; padding: 0;">' + catName + '</h2>');

				var $newTable = $('<table class="wp-list-table widefat fixed striped posts"></table>');
				$newTable.append( $thead.clone() );

				var $newTbody = $('<tbody></tbody>');
				$.each(rows, function(i, $row) {
					// Add alternate row colors correctly for the new table
					$row.removeClass('alternate');
					if ( i % 2 !== 0 ) {
						$row.addClass('alternate');
					}
					$newTbody.append($row);
				});

				$newTable.append($newTbody);
				$newTable.append( $tfoot.clone() );

				$catSection.append($newTable);
				$tableWrapper.append($catSection);
			});

			// Replace original table with our new wrapper
			$mainTable.replaceWith($tableWrapper);
			
		}, 100);
	});
	</script>
	<?php
}
add_action( 'admin_footer', 'MydPro\Includes\myd_products_group_by_category_js' );
