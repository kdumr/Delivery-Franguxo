<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$track_page = get_the_permalink( get_option( 'fdm-page-order-track' ) );

?>
<div id="myd-cart-finished" class="myd-cart__finished">
	<div class="myd-cart__finished-content">
		<div class="myd-cart__finished-order-number">
			<?php esc_html_e( 'Your order number is: ', 'myd-delivery-pro' ); ?>
			<div class="" id="finished-order-number">#4432</div>
		</div>

		<div class="myd-cart__finished-time">
			<?php esc_html_e( 'Delivery time:', 'myd-delivery-pro' ); ?>
			<?php echo esc_html( get_option( 'fdm-estimate-time-delivery' ) ); ?>
		</div>

		<div class="myd-cart__finished-message">
			<?php esc_html_e( 'Thanks! Your order has been finished. Now just wait.', 'myd-delivery-pro' ); ?>
		</div>

		<!-- Confirmation Code block: shown below the message -->
		<div class="myd-cart__finished-confirmation" aria-live="polite">
			<style>
				.myd-cart__finished-confirmation { margin: 12px 0 18px; padding: 10px 12px; border: 1px dashed #bbb; border-radius: 6px; background: #f8f8f8; text-align:center; }
				.myd-cart__finished-confirmation .myd-confirm-title { font-weight: 700; color:#222; margin-bottom:4px; }
				.myd-cart__finished-confirmation .myd-confirm-subtitle { font-size: 12px; color:#666; margin-bottom: 8px; line-height:1.3; }
				.myd-cart__finished-confirmation .myd-confirm-code { display:inline-flex; align-items:center; gap:8px; }
				.myd-cart__finished-confirmation .digit-box { display:inline-flex; align-items:center; justify-content:center; width:36px; height:48px; border-radius:6px; background:#e9e9e9; border:1px solid #cfcfcf; box-shadow: inset 0 1px 0 rgba(255,255,255,.6); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size:28px; font-weight:700; color:#111; }
				.myd-cart__finished-confirmation .digit-sep { font-weight:700; font-size:22px; color:#444; padding:0 2px; }
			</style>
			<div class="myd-confirm-title"><?php echo esc_html__('Código de confirmação', 'myd-delivery-pro'); ?></div>
			<div class="myd-confirm-subtitle"><?php echo esc_html__('Este código deverá ser informado ao entregador para que seu pedido seja finalizado', 'myd-delivery-pro'); ?></div>
			<div class="myd-confirm-code" id="finished-confirmation-code">
				<span class="digit-box">0</span>
				<span class="digit-box">0</span>
				<span class="digit-sep">--</span>
				<span class="digit-box">0</span>
				<span class="digit-box">0</span>
			</div>
		</div>

		<div class="myd-cart__finished-whatsapp">
			<a href="" target="_blank">
				<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:svgjs="http://svgjs.com/svgjs" version="1.1" width="18" height="18" x="0" y="0" viewBox="0 0 682 682.66669" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path xmlns="http://www.w3.org/2000/svg" d="m544.386719 93.007812c-59.875-59.945312-139.503907-92.9726558-224.335938-93.007812-174.804687 0-317.070312 142.261719-317.140625 317.113281-.023437 55.894531 14.578125 110.457031 42.332032 158.550781l-44.992188 164.335938 168.121094-44.101562c46.324218 25.269531 98.476562 38.585937 151.550781 38.601562h.132813c174.785156 0 317.066406-142.273438 317.132812-317.132812.035156-84.742188-32.921875-164.417969-92.800781-224.359376zm-224.335938 487.933594h-.109375c-47.296875-.019531-93.683594-12.730468-134.160156-36.742187l-9.621094-5.714844-99.765625 26.171875 26.628907-97.269531-6.269532-9.972657c-26.386718-41.96875-40.320312-90.476562-40.296875-140.28125.054688-145.332031 118.304688-263.570312 263.699219-263.570312 70.40625.023438 136.589844 27.476562 186.355469 77.300781s77.15625 116.050781 77.132812 186.484375c-.0625 145.34375-118.304687 263.59375-263.59375 263.59375zm144.585938-197.417968c-7.921875-3.96875-46.882813-23.132813-54.148438-25.78125-7.257812-2.644532-12.546875-3.960938-17.824219 3.96875-5.285156 7.929687-20.46875 25.78125-25.09375 31.066406-4.625 5.289062-9.242187 5.953125-17.167968 1.984375-7.925782-3.964844-33.457032-12.335938-63.726563-39.332031-23.554687-21.011719-39.457031-46.960938-44.082031-54.890626-4.617188-7.9375-.039062-11.8125 3.476562-16.171874 8.578126-10.652344 17.167969-21.820313 19.808594-27.105469 2.644532-5.289063 1.320313-9.917969-.664062-13.882813-1.976563-3.964844-17.824219-42.96875-24.425782-58.839844-6.4375-15.445312-12.964843-13.359374-17.832031-13.601562-4.617187-.230469-9.902343-.277344-15.1875-.277344-5.28125 0-13.867187 1.980469-21.132812 9.917969-7.261719 7.933594-27.730469 27.101563-27.730469 66.105469s28.394531 76.683594 32.355469 81.972656c3.960937 5.289062 55.878906 85.328125 135.367187 119.648438 18.90625 8.171874 33.664063 13.042968 45.175782 16.695312 18.984374 6.03125 36.253906 5.179688 49.910156 3.140625 15.226562-2.277344 46.878906-19.171875 53.488281-37.679687 6.601563-18.511719 6.601563-34.375 4.617187-37.683594-1.976562-3.304688-7.261718-5.285156-15.183593-9.253906zm0 0" fill-rule="evenodd" fill="#ffffff" data-original="#000000" style="" class=""/></g></svg>
				<?php esc_html_e( 'Send order on WhatsApp', 'myd-delivery-pro' ); ?>
			</a>
		</div>

		<div class="myd-cart__finished-track-order">
			<a href="<?php echo esc_attr( $track_page ); ?>" target="_blank"><?php esc_html_e( 'Track Order', 'myd-delivery-pro' ); ?></a>
		</div>
	</div>
</div>
