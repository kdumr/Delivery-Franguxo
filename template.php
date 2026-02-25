<?php

use MydPro\Includes\Fdm_svg;
use MydPro\Includes\Store_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_category = isset( $args['product_category'] ) && $args['product_category'] !== 'all' ? array( $args['product_category'] ) : array();
$currency_simbol = Store_Data::get_store_data( 'currency_simbol' );

<?php
use MydPro\Includes\Fdm_svg;
use MydPro\Includes\Store_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_category = isset( $args['product_category'] ) && $args['product_category'] !== 'all' ? array( $args['product_category'] ) : array();
$currency_simbol = Store_Data::get_store_data( 'currency_simbol' );

?>
<!-- Overlay vermelho para carregamento -->
<div id="myd-red-overlay" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:#ea1d2b;color:#fff;display:flex;align-items:center;justify-content:center;z-index:99999;font-size:2rem;font-family:sans-serif;">Carregando...</div>
<script>
// Remove overlay quando o CSS principal estiver carregado
function removeRedOverlay() {
	var overlay = document.getElementById('myd-red-overlay');
	if (overlay) overlay.style.display = 'none';
}
var cssHref = '/assets/css/delivery-frontend.min.css';
var links = document.querySelectorAll('link[rel="stylesheet"]');
var found = false;
links.forEach(function(link) {
	if (link.href && link.href.indexOf(cssHref) !== -1) {
		if (link.sheet) {
			removeRedOverlay();
		} else {
			link.addEventListener('load', removeRedOverlay);
		}
		found = true;
	}
});
if (!found) {
	window.addEventListener('load', removeRedOverlay);
}
</script>
		<div class="myd-popup-notification" id="myd-popup-notification">
			<div class="myd-popup-notification__message" id="myd-popup-notification__message"></div>
		</div>

		<div class="myd-content">
			<?php if ( ! isset( $args['filter_type'] ) || isset( $args['filter_type'] ) && $args['filter_type'] !== 'hide' ) : ?>
				<div class="myd-content-filter">
					<?php if ( ! isset( $args['filter_type'] ) || isset( $args['filter_type'] ) && $args['filter_type'] !== 'hide_filter' ) : ?>
						<div class="myd-content-filter__categories">
							<?php foreach( $this->get_categories() as $v ) : ?>
								<div class="myd-content-filter__tag" data-anchor="<?php echo str_replace( ' ', '-', esc_attr( $v ) ); ?>"><?php echo esc_html( $v ); ?></div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php if ( ! isset( $args['filter_type'] ) || isset( $args['filter_type'] ) && $args['filter_type'] !== 'hide_search' ) : ?>
						<div class="myd-content-filter__search-icon" id="myd-content-filter__search-icon">
							<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="20px"; heigth="20px"; xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 511.999 511.999" style="enable-background:new 0 0 511.999 511.999;" xml:space="preserve"><g><g><path d="M225.773,0.616C101.283,0.616,0,101.622,0,225.773s101.284,225.157,225.773,225.157s225.774-101.006,225.774-225.157S350.263,0.616,225.773,0.616z M225.773,413.917c-104.084,0-188.761-84.406-188.761-188.145c0-103.745,84.677-188.145,188.761-188.145s188.761,84.4,188.761,188.145C414.535,329.511,329.858,413.917,225.773,413.917z"/></g></g><g><g><path d="M506.547,479.756L385.024,358.85c-7.248-7.205-18.963-7.174-26.174,0.068c-7.205,7.248-7.174,18.962,0.068,26.174l121.523,120.906c3.615,3.59,8.328,5.385,13.053,5.385c4.756,0,9.506-1.82,13.121-5.453C513.82,498.681,513.789,486.967,506.547,479.756z"/></g></g></svg>
						</div>
						<div class="myd-content-filter__search-input" id="myd-content-filter__search-input">
							<input type="text" class="myd-search-products" name="myd-search-products" id="myd-search-products" placeholder="<?php esc_attr_e( 'Type to search', 'myd-delivery-pro' ); ?>">
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<section class="myd-products__wrapper">
	<?php echo $this->fdm_loop_products_per_categorie( $product_category ); ?>
</section>

<?php if ( Store_Data::$template_dependencies_loaded === false ) : ?>
	<section class="myd-float">
		<div class="myd-float__button-subtotal">
			<span
				id="myd-float__price"
				data-currency="<?php echo \esc_attr( $currency_simbol ); ?>">
				<?php echo \esc_html( $currency_simbol ); ?>
			</span>
			<span id="myd_float__separator">&bull;</span>
			<span id="myd-float__qty">0</span>
			<span id="myd-float__qty-text">
				<?php esc_html_e( 'items', 'myd-delivery-pro' ); ?>
			</span>
		</div>

		<div class="myd-float__title">
			<?php esc_html_e( 'View Bag', 'myd-delivery-pro' ); ?>
			<svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 2.75C10.7574 2.75 9.75006 3.75736 9.75006 5V5.25447C10.1676 5.24999 10.6183 5.25 11.1053 5.25H12.8948C13.3819 5.25 13.8326 5.24999 14.2501 5.25447V5C14.2501 3.75736 13.2427 2.75 12.0001 2.75ZM15.7501 5.30694V5C15.7501 2.92893 14.0711 1.25 12.0001 1.25C9.929 1.25 8.25006 2.92893 8.25006 5V5.30694C8.11506 5.31679 7.98479 5.32834 7.85904 5.34189C6.98068 5.43657 6.24614 5.63489 5.59385 6.08197C5.3695 6.23574 5.15877 6.40849 4.96399 6.59833C4.39766 7.15027 4.05914 7.83166 3.79405 8.67439C3.53667 9.49258 3.32867 10.5327 3.06729 11.8396L3.04822 11.935C2.67158 13.8181 2.37478 15.302 2.28954 16.484C2.20244 17.6916 2.32415 18.7075 2.89619 19.588C3.08705 19.8817 3.30982 20.1534 3.56044 20.3982C4.31157 21.1318 5.28392 21.4504 6.48518 21.6018C7.66087 21.75 9.17418 21.75 11.0946 21.75H12.9055C14.826 21.75 16.3393 21.75 17.5149 21.6018C18.7162 21.4504 19.6886 21.1318 20.4397 20.3982C20.6903 20.1534 20.9131 19.8817 21.1039 19.588C21.676 18.7075 21.7977 17.6916 21.7106 16.484C21.6254 15.3021 21.3286 13.8182 20.9519 11.9351L20.9328 11.8396C20.6715 10.5327 20.4635 9.49259 20.2061 8.67439C19.941 7.83166 19.6025 7.15027 19.0361 6.59833C18.8414 6.40849 18.6306 6.23574 18.4063 6.08197C17.754 5.63489 17.0194 5.43657 16.1411 5.34189C16.0153 5.32834 15.8851 5.31679 15.7501 5.30694ZM8.01978 6.83326C7.27307 6.91374 6.81176 7.06572 6.44188 7.31924C6.28838 7.42445 6.1442 7.54265 6.01093 7.67254C5.68979 7.98552 5.45028 8.40807 5.22492 9.12449C4.99463 9.85661 4.80147 10.8172 4.52967 12.1762C4.14013 14.1239 3.8633 15.5153 3.78565 16.5919C3.70906 17.6538 3.83838 18.2849 4.15401 18.7707C4.2846 18.9717 4.43702 19.1576 4.60849 19.3251C5.02293 19.7298 5.61646 19.9804 6.67278 20.1136C7.74368 20.2486 9.1623 20.25 11.1486 20.25H12.8515C14.8378 20.25 16.2564 20.2486 17.3273 20.1136C18.3837 19.9804 18.9772 19.7298 19.3916 19.3251C19.5631 19.1576 19.7155 18.9717 19.8461 18.7707C20.1617 18.2849 20.2911 17.6538 20.2145 16.5919C20.1368 15.5153 19.86 14.1239 19.4705 12.1762C19.1987 10.8173 19.0055 9.85661 18.7752 9.12449C18.5498 8.40807 18.3103 7.98552 17.9892 7.67254C17.8559 7.54265 17.7118 7.42445 17.5582 7.31924C17.1884 7.06572 16.7271 6.91374 15.9803 6.83326C15.2173 6.75101 14.2374 6.75 12.8515 6.75H11.1486C9.76271 6.75 8.78285 6.75101 8.01978 6.83326ZM8.92103 14.2929C9.31156 14.1548 9.74006 14.3595 9.87809 14.7501C10.1873 15.625 11.0218 16.25 12.0003 16.25C12.9787 16.25 13.8132 15.625 14.1224 14.7501C14.2605 14.3595 14.6889 14.1548 15.0795 14.2929C15.47 14.4309 15.6747 14.8594 15.5367 15.2499C15.0222 16.7054 13.6342 17.75 12.0003 17.75C10.3663 17.75 8.97827 16.7054 8.46383 15.2499C8.3258 14.8594 8.53049 14.4309 8.92103 14.2929Z" fill="#fff"/>
			</svg>
		</div>
	</section>
	<!-- Barra de perfil fixa abaixo do myd-float -->
	<style>
		.myd-profile-bar {
			width: 100vw;
			max-width: 100%;
			position: fixed;
			left: 0;
			z-index: 9999;
			bottom: 0;
			background: #222;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 8px 0 4px 0;
			box-shadow: 0 -2px 8px rgba(0,0,0,0.08);
		}
		.myd-profile-bar svg {
			display: block;
			margin: 0 auto;
		}
		.myd-profile-bar span {
			display: block;
			color: #fff;
			font-size: 13px;
			margin-top: 2px;
			text-align: center;
			letter-spacing: 1px;
		}
	</style>
	<div class="myd-profile-bar">
		<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff" width="32" height="32"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
		<span>Perfil</span>
	</div>

	<section class="myd-checkout" id="myd-checkout">
		<div class="myd-cart" id="myd-cart">
			<div class="myd-cart__nav">
				<div class="myd-cart__nav-back">
					<?php echo Fdm_svg::nav_arrow_left(); ?>
				</div>

				<div
					class="myd-cart__nav-bag myd-cart__nav--active"
					data-tab-content="myd-cart__products"
					data-back="none"
					data-next="myd-cart__nav-shipping"
				>
					<svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M12.0001 2.75C10.7574 2.75 9.75006 3.75736 9.75006 5V5.25447C10.1676 5.24999 10.6183 5.25 11.1053 5.25H12.8948C13.3819 5.25 13.8326 5.24999 14.2501 5.25447V5C14.2501 3.75736 13.2427 2.75 12.0001 2.75ZM15.7501 5.30694V5C15.7501 2.92893 14.0711 1.25 12.0001 1.25C9.929 1.25 8.25006 2.92893 8.25006 5V5.30694C8.11506 5.31679 7.98479 5.32834 7.85904 5.34189C6.98068 5.43657 6.24614 5.63489 5.59385 6.08197C5.3695 6.23574 5.15877 6.40849 4.96399 6.59833C4.39766 7.15027 4.05914 7.83166 3.79405 8.67439C3.53667 9.49258 3.32867 10.5327 3.06729 11.8396L3.04822 11.935C2.67158 13.8181 2.37478 15.302 2.28954 16.484C2.20244 17.6916 2.32415 18.7075 2.89619 19.588C3.08705 19.8817 3.30982 20.1534 3.56044 20.3982C4.31157 21.1318 5.28392 21.4504 6.48518 21.6018C7.66087 21.75 9.17418 21.75 11.0946 21.75H12.9055C14.826 21.75 16.3393 21.75 17.5149 21.6018C18.7162 21.4504 19.6886 21.1318 20.4397 20.3982C20.6903 20.1534 20.9131 19.8817 21.1039 19.588C21.676 18.7075 21.7977 17.6916 21.7106 16.484C21.6254 15.3021 21.3286 13.8182 20.9519 11.9351L20.9328 11.8396C20.6715 10.5327 20.4635 9.49259 20.2061 8.67439C19.941 7.83166 19.6025 7.15027 19.0361 6.59833C18.8414 6.40849 18.6306 6.23574 18.4063 6.08197C17.754 5.63489 17.0194 5.43657 16.1411 5.34189C16.0153 5.32834 15.8851 5.31679 15.7501 5.30694ZM8.01978 6.83326C7.27307 6.91374 6.81176 7.06572 6.44188 7.31924C6.28838 7.42445 6.1442 7.54265 6.01093 7.67254C5.68979 7.98552 5.45028 8.40807 5.22492 9.12449C4.99463 9.85661 4.80147 10.8172 4.52967 12.1762C4.14013 14.1239 3.8633 15.5153 3.78565 16.5919C3.70906 17.6538 3.83838 18.2849 4.15401 18.7707C4.2846 18.9717 4.43702 19.1576 4.60849 19.3251C5.02293 19.7298 5.61646 19.9804 6.67278 20.1136C7.74368 20.2486 9.1623 20.25 11.1486 20.25H12.8515C14.8378 20.25 16.2564 20.2486 17.3273 20.1136C18.3837 19.9804 18.9772 19.7298 19.3916 19.3251C19.5631 19.1576 19.7155 18.9717 19.8461 18.7707C20.1617 18.2849 20.2911 17.6538 20.2145 16.5919C20.1368 15.5153 19.86 14.1239 19.4705 12.1762C19.1987 10.8173 19.0055 9.85661 18.7752 9.12449C18.5498 8.40807 18.3103 7.98552 17.9892 7.67254C17.8559 7.54265 17.7118 7.42445 17.5582 7.31924C17.1884 7.06572 16.7271 6.91374 15.9803 6.83326C15.2173 6.75101 14.2374 6.75 12.8515 6.75H11.1486C9.76271 6.75 8.78285 6.75101 8.01978 6.83326ZM8.92103 14.2929C9.31156 14.1548 9.74006 14.3595 9.87809 14.7501C10.1873 15.625 11.0218 16.25 12.0003 16.25C12.9787 16.25 13.8132 15.625 14.1224 14.7501C14.2605 14.3595 14.6889 14.1548 15.0795 14.2929C15.47 14.4309 15.6747 14.8594 15.5367 15.2499C15.0222 16.7054 13.6342 17.75 12.0003 17.75C10.3663 17.75 8.97827 16.7054 8.46383 15.2499C8.3258 14.8594 8.53049 14.4309 8.92103 14.2929Z"/>
					</svg>

					<div class="myd-cart__nav-desc">
						<?php esc_html_e( 'Bag', 'myd-delivery-pro' ); ?>
					</div>
				</div>

				<div
					class="myd-cart__nav-shipping"
					data-tab-content="myd-cart__checkout"
					data-back="myd-cart__nav-bag"
					data-next="myd-cart__nav-payment"
				>
					<svg width="24px" height="24px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
						<path d="M22 6H2a1.001 1.001 0 0 0-1 1v3a1.001 1.001 0 0 0 1 1h20a1.001 1.001 0 0 0 1-1V7a1.001 1.001 0 0 0-1-1zm0 4H2V7h20v3h.001M22 17H2a1.001 1.001 0 0 0-1 1v3a1.001 1.001 0 0 0 1 1h20a1.001 1.001 0 0 0 1-1v-3a1.001 1.001 0 0 0-1-1zm0 4H2v-3h20v3h.001M10 14v1H2v-1zM2 3h8v1H2z"/><path fill="none" d="M0 0h24v24H0z"/>
					</svg>

					<div class="myd-cart__nav-desc">
						<?php esc_html_e( 'Checkout', 'myd-delivery-pro' ); ?>
					</div>
				</div>

					<div
						class="myd-cart__nav-payment"
						data-tab-content="myd-cart__payment"
						data-back="myd-cart__nav-shipping"
						data-next="myd-cart__finished"
					>
						<svg width="24px" height="24px" viewBox="0 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg">
							<g id="layer1">
							<path d="M 12.964844 0.095703125 C 12.805889 0.093483625 12.654204 0.10575226 12.503906 0.12890625 C 11.902715 0.22152215 11.399294 0.43880053 10.416016 0.60742188 C 9.5128573 0.76230303 8.9000581 0.53804687 8.1347656 0.34375 C 7.3694731 0.14945313 6.4403485 0.025497315 5.2929688 0.54492188 C 5.0125471 0.67254789 4.9131349 1.0209548 5.0839844 1.2773438 L 6.8574219 3.9355469 C 6.4799034 4.2948616 5.386098 5.3589005 4.0996094 7.0742188 C 2.5695621 9.1142816 1 11.799685 1 14.5 C 1 17.150236 2.3087845 18.664286 4.0703125 19.341797 C 5.8318405 20.019308 8 20 10 20 C 12 20 14.168159 20.01931 15.929688 19.341797 C 17.691216 18.664286 19 17.150236 19 14.5 C 19 11.799685 17.430438 9.1142814 15.900391 7.0742188 C 14.613901 5.3589005 13.520096 4.2948616 13.142578 3.9355469 L 14.916016 1.2773438 C 15.088927 1.0174273 14.984039 0.66436818 14.697266 0.54101562 C 13.978672 0.23310127 13.441708 0.10236154 12.964844 0.095703125 z M 12.65625 1.1171875 C 12.922777 1.0761275 13.330981 1.236312 13.679688 1.3300781 L 12.232422 3.5 L 7.7675781 3.5 L 6.3046875 1.3046875 C 6.8796693 1.1670037 7.3639663 1.1812379 7.8886719 1.3144531 C 8.5922201 1.493074 9.440416 1.7898596 10.583984 1.59375 C 11.647433 1.4113805 12.227503 1.1832377 12.65625 1.1171875 z M 7.7070312 4.5 L 12.292969 4.5 C 12.480348 4.6748327 13.734431 5.8555424 15.099609 7.6757812 C 16.569562 9.6357185 18 12.200315 18 14.5 C 18 16.849764 17.058785 17.835714 15.570312 18.408203 C 14.081843 18.980692 12 19 10 19 C 8 19 5.9181595 18.980692 4.4296875 18.408203 C 2.9412155 17.835714 2 16.849764 2 14.5 C 2 12.200315 3.4304379 9.6357186 4.9003906 7.6757812 C 6.2655702 5.855542 7.5196519 4.6748327 7.7070312 4.5 z M 9.5 9 L 9.5 10 C 8.6774954 10 8 10.677495 8 11.5 C 8 12.322505 8.6774954 13 9.5 13 L 10.5 13 C 10.782065 13 11 13.217935 11 13.5 C 11 13.782065 10.782065 14 10.5 14 L 9.5 14 L 8 14 L 8 15 L 9.5 15 L 9.5 16 L 10.5 16 L 10.5 15 C 11.322504 15 12 14.322505 12 13.5 C 12 12.677495 11.322504 12 10.5 12 L 9.5 12 C 9.2179352 12 9 11.782065 9 11.5 C 9 11.217935 9.2179352 11 9.5 11 L 10.5 11 L 12 11 L 12 10 L 10.5 10 L 10.5 9 L 9.5 9 z " style="fill-opacity:1; stroke:none; stroke-width:0px;"/>
							</g>
						</svg>

						<div class="myd-cart__nav-desc">
							<?php esc_html_e( 'Payment', 'myd-delivery-pro' ); ?>
						</div>
				</div>

				<div class="myd-cart__nav-close"><?php echo Fdm_svg::svg_close(); ?></div>
			</div>

			<div class="myd-cart__content">
				<?php include_once MYD_PLUGIN_PATH . '/templates/cart/cart-empty.php'; ?>
				<div class="myd-cart__products"></div>
				<?php include_once MYD_PLUGIN_PATH . '/templates/cart/cart-checkout.php'; ?>
				<?php include_once MYD_PLUGIN_PATH . '/templates/cart/cart-payment.php'; ?>
				<?php include_once MYD_PLUGIN_PATH . '/templates/cart/cart-finished-order.php'; ?>
			</div>

			<div class="myd-cart__button">
				<div
					class="myd-cart__button-text"
					data-text="<?php esc_attr_e( 'Next', 'myd-delivery-pro' ) ?>"
				>
					<?php esc_html_e( 'Next', 'myd-delivery-pro' ) ?>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<script type="text/template" id="myd-template-loading">
	<div class="myd-loader"></div>
</script>

<?php Store_Data::$template_dependencies_loaded = true; ?>
