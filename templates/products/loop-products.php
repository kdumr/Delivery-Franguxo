<?php

use MydPro\Includes\Store_Data;
use MydPro\Includes\Myd_Store_Formatting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$postid = get_the_ID();
$image_id = get_post_meta( $postid, 'product_image', true );
$image_url = wp_get_attachment_image_url( $image_id, 'large' );
$box_shadow = get_option( 'myd-products-list-boxshadow' );
$product_price = get_post_meta( $postid, 'product_price', true );
$product_price = empty( $product_price ) ? 0 : $product_price;
$button_text = apply_filters( 'myd-product-loop-button-text', '+' );
$currency_simbol = Store_Data::get_store_data( 'currency_simbol' );
$is_available = get_post_meta( $postid, 'product_available', true );
$disabled_class = $is_available === 'not-available' ? 'myd-product-disabled' : '';
$price_label = get_post_meta( $postid, 'product_price_label', true );
$hide_price_class = $price_label === 'hide' ? 'myd-product-item__price--hide' : '';

?>
<article class="myd-product-item <?php echo esc_attr( $box_shadow ); ?> <?php echo esc_attr( $disabled_class ); ?>" itemscope itemtype="http://schema.org/Product" data-id="<?php echo esc_attr( $postid ); ?>">
	<?php if ( $is_available === 'not-available' ) : ?>
		<span class="myd-product-item__not-available"><?php esc_html_e( 'Not available', 'myd-delivery-pro' ); ?></span>
		<div class="myd-product-item__not-available-overlay"></div>
	<?php endif; ?>
	<div class="myd-product-item__content">
		<?php
		
		?>
		<h3 class="myd-product-item__title" itemprop="name"><?php echo esc_html( get_the_title() ); ?></h3>
		<p class="myd-product-item__desc" itemprop="description"><?php echo esc_html( get_post_meta( $postid, 'product_description', true ) ); ?></p>

		<div class="myd-product-item__actions">
			<span class="myd-product-item__price <?php echo esc_attr( $hide_price_class ); ?>" itemprop="price">
				<?php if ( $price_label === 'show' || $price_label === '' ) : ?>
					<?php echo esc_html( $currency_simbol . ' ' . Myd_Store_Formatting::format_price( get_post_meta( $postid, 'product_price', true ) ) ); ?>
				<?php endif; ?>

				<?php if ( $price_label === 'from' ) : ?>
					<?php echo esc_html__( 'From', 'myd-delivery-pro' ); ?> <?php echo esc_html( $currency_simbol . ' ' . Myd_Store_Formatting::format_price( get_post_meta( $postid, 'product_price', true ) ) ); ?>
				<?php endif; ?>

				<?php if ( $price_label === 'consult' ) : ?>
					<?php echo esc_html__( 'By Consult', 'myd-delivery-pro' ); ?>
				<?php endif; ?>
			</span>
		</div>
	</div>


	
	<div class="myd-product-item__img" data-image="<?php echo esc_attr( $image_url ); ?>" style="position:relative;">
		<?php
		// Badges / selos (render inside image container)
		$seals = get_post_meta( $postid, 'product_seals', true );
		if ( ! empty( $seals ) ) {
			if ( ! is_array( $seals ) ) {
				$seals = array( $seals );
			}
			foreach ( $seals as $seal ) {
				if ( $seal === 'mais-vendido' ) {
					echo '<div class="myd-product-badge myd-product-badge--best-seller">' . esc_html__( 'Mais vendido', 'myd-delivery-pro' ) . '</div>';
				}
				if ( $seal === 'custo-beneficio' ) {
					echo '<div class="myd-product-badge myd-product-badge--value">' . esc_html__( 'Custo benefício', 'myd-delivery-pro' ) . '</div>';
				}
			}
		}
		?>
		<?php echo wp_get_attachment_image( $image_id, 'medium', false, [ 'class' => 'myd-product-item-img attachment-medium size-medium', 'alt' => 'MyD Delivery Product Image', 'loading' => 'lazy', 'decoding' => 'async' ] ); ?>
	</div>
</article>

<hr class="myd-product-item__divider">

<?php if ( $is_available !== 'not-available' ) : ?>
	<div
		class="fdm-popup-product-init myd-hide-element"
		id="popup-<?php echo \esc_attr( $postid ); ?>"
		data-product-id="<?php echo \esc_attr( $postid ); ?>"
		data-loaded="0"
	></div>
<?php endif; ?>
