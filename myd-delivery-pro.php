<?php
/**
 * Plugin Name: Sistema delivery Franguxo
 * Plugin URI: https://github.com/kdumr/gestordepedidos
 * Description: Sistema raiz do delivery.
 * Author: kdumr
 * Author URI: https://github.com/kdumr
 * Version: 3.0.0
 * Requires PHP: 7.4
 * Requires at least: 5.5
 * Text Domain: myd-delivery-pro-edit
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package Myd_Delivery_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MYD_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MYD_PLUGN_URL', plugin_dir_url( __FILE__ ) );
define( 'MYD_PLUGIN_MAIN_FILE', __FILE__ );
define( 'MYD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'MYD_PLUGIN_DIRNAME', plugin_basename( __DIR__ ) );
define( 'MYD_CURRENT_VERSION', '3.0.1' );
define( 'MYD_MINIMUM_PHP_VERSION', '7.4' );
define( 'MYD_MINIMUM_WP_VERSION', '5.5' );
define( 'MYD_PLUGIN_NAME', 'MyD Delivery Pro' );

/**
 * Check PHP and WP version before include plugin main class
 *
 * @since 1.9.6
 */
if ( ! version_compare( PHP_VERSION, MYD_MINIMUM_PHP_VERSION, '>=' ) ) {

	add_action( 'admin_notices', 'mydp_admin_notice_php_version_fail' );
	return;
}

if ( ! version_compare( get_bloginfo( 'version' ), MYD_MINIMUM_WP_VERSION, '>=' ) ) {

	add_action( 'admin_notices', 'mydp_admin_notice_wp_version_fail' );
	return;
}

include_once MYD_PLUGIN_PATH . 'includes/class-plugin.php';
include_once MYD_PLUGIN_PATH . 'includes/shortcode-store-status.php';
$delivery_time_handler = MYD_PLUGIN_PATH . 'includes/myd-save-delivery-time-handler.php';
if ( file_exists( $delivery_time_handler ) ) {
	include_once $delivery_time_handler;
} else {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[MYD] includes/myd-save-delivery-time-handler.php missing: ' . $delivery_time_handler );
	}
}
$perf_file = MYD_PLUGIN_PATH . 'includes/performance.php';
if ( file_exists( $perf_file ) ) {
	include_once $perf_file;
} else {
	// optional: log missing file in debug log
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[MYD] includes/performance.php missing: ' . $perf_file );
	}
}
MydPro\Includes\Plugin::instance();

// Disable WordPress heartbeat to reduce admin-ajax polling
add_action( 'init', function() {
    // Disable heartbeat entirely for non-admin pages
    if ( ! is_admin() ) {
        wp_deregister_script( 'heartbeat' );
    } else {
        // For admin pages, reduce frequency to 60 seconds
        add_filter( 'heartbeat_settings', function( $settings ) {
            $settings['interval'] = 60; // 60 seconds instead of default 15-60
            return $settings;
        } );
    }
} );

/**
 * Admin notice PHP version fail
 *
 * @since 1.9.6
 * @return void
 */
function mydp_admin_notice_php_version_fail() {

	$message = sprintf(
		esc_html__( '%1$s requires PHP version %2$s or greater.', 'myd-delivery-pro' ),
		'<strong>MyD Delivery Pro</strong>',
		MYD_MINIMUM_PHP_VERSION
	);

	$html_message = sprintf( '<div class="notice notice-error"><p>%1$s</p></div>', $message );

	echo wp_kses_post( $html_message );
}
//raz0r
update_option('fdm-license', ['set_status' => 'active']); set_transient('myd_license_data', ['key' => '*************', 'status' => 'active', 'site_url' => get_site_url()], 30 * DAY_IN_SECONDS);
/**
 * Admin notice WP version fail
 *
 * @since 1.9.6
 * @return void
 */
function mydp_admin_notice_wp_version_fail() {

	$message = sprintf(
		esc_html__( '%1$s requires WordPress version %2$s or greater.', 'myd-delivery-pro' ),
		'<strong>MyD Delivery Pro</strong>',
		MYD_MINIMUM_WP_VERSION
	);

	$html_message = sprintf( '<div class="notice notice-error"><p>%1$s</p></div>', $message );

	echo wp_kses_post( $html_message );
}
