<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Settings', 'myd-delivery-pro' ); ?></h1>

	<?php settings_errors(); ?>

	<nav class="nav-tab-wrapper">
		<a href="#tab_company" id="tab-company" class="nav-tab myd-tab nav-tab-active" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'Company', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_facebook" id="tab-facebook" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'Facebook / Pixel', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_delivery" id="tab-delivery" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'Delivery', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_opening_hours" id="tab-opening-hours" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'Opening Hours', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_order" id="tab-order" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'Order', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_payment" id="tab-payment" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'Payment', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_print" id="tab-print" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'Print', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_layout" id="tab-layout" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'Layout', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_advanced" id="tab-advanced" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'Advanced', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_shortcodes" id="tab-shortcodes" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'Shortcodes', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_smtp" id="tab-smtp" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'SMTP', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_evolution" id="tab-evolution" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'Evolution', 'myd-delivery-pro' ); ?></a>
		<a href="#tab_ifood" id="tab-ifood" class="nav-tab myd-tab" onclick="window.MydAdmin.mydChangeTab(event)"><?php esc_html_e( 'iFood', 'myd-delivery-pro' ); ?></a>
	</nav>

	<form method="post" action="options.php">
		<?php settings_fields( 'fmd-settings-group' ); ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/company/tab-company.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/delivery/tab-delivery.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/opening-hours/tab-opening-hours.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/order/tab-order.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/payment/tab-payment.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/print/tab-print.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/facebook/tab-facebook.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/layout/tab-layout.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/advanced/tab-advanced.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/shortcodes/tab-shortcodes.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/smtp/tab-smtp.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/evolution/tab-evolution.php'; ?>
		<?php include_once MYD_PLUGIN_PATH . '/templates/admin/settings-tabs/ifood/tab-ifood.php'; ?>
		<?php submit_button(); ?>
	</form>

</div>
