<?php
/**
 * Plugin Name: MyD Delivery integration with SumUp
 * Plugin URI: https://myddelivery.com/
 * Description: MyD Delivery integration with SumUp enable SumUp payment gateway options to MyD Delivery Pro.
 * Author: EduardoVillao.me
 * Author URI: https://eduardovillao.me/
 * Version: 1.2.4
 * Requires PHP: 7.4
 * Requires at least: 5.5
 * Text Domain: myd-delivery-integration-sumup
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package MyD_Delivery_integration_with_SumUp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SUMUPMYD_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SUMUPMYD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SUMUPMYD_PLUGIN_MAIN_FILE', __FILE__ );
define( 'SUMUPMYD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SUMUPMYD_PLUGIN_DIRNAME', plugin_basename( __DIR__ ) );
define( 'SUMUPMYD_CURRENT_VERSION', '1.2.4' );
define( 'SUMUPMYD_MINIMUM_PHP_VERSION', '7.4' );
define( 'SUMUPMYD_MINIMUM_WP_VERSION', '5.5' );
define( 'SUMUPMYD_PLUGIN_NAME', 'MyD Delivery integration with SumUp' );

/**
 * Check PHP and WP version before include plugin main class
 *
 * @since 1.9.6
 */
if ( ! version_compare( PHP_VERSION, SUMUPMYD_MINIMUM_PHP_VERSION, '>=' ) ) {
	add_action( 'admin_notices', 'sumupmyd_admin_notice_php_version_fail' );
	return;
}

if ( ! version_compare( get_bloginfo( 'version' ), SUMUPMYD_MINIMUM_WP_VERSION, '>=' ) ) {
	add_action( 'admin_notices', 'sumupmyd_admin_notice_wp_version_fail' );
	return;
}

include_once SUMUPMYD_PLUGIN_PATH . 'includes/class-plugin.php';
SumupMyd\Includes\Plugin::instance();

/**
 * Admin notice PHP version fail
 *
 * @since 1.0
 */
function sumupmyd_admin_notice_php_version_fail() {
	$message = sprintf(
		esc_html__( '%1$s requires PHP version %2$s or greater.', 'myd-delivery-integration-sumup' ),
		'<strong>' . esc_html( SUMUPMYD_PLUGIN_NAME ) . '</strong>',
		SUMUPMYD_MINIMUM_PHP_VERSION
	);

	$html_message = sprintf( '<div class="notice notice-error"><p>%1$s</p></div>', $message );
	echo wp_kses_post( $html_message );
}

/**
 * Admin notice WP version fail
 *
 * @since 1.0
 */
function sumupmyd_admin_notice_wp_version_fail() {
	$message = sprintf(
		esc_html__( '%1$s requires WordPress version %2$s or greater.', 'myd-delivery-integration-sumup' ),
		'<strong>' . esc_html( SUMUPMYD_PLUGIN_NAME ) . '</strong>',
		SUMUPMYD_MINIMUM_WP_VERSION
	);

	$html_message = sprintf( '<div class="notice notice-error"><p>%1$s</p></div>', $message );
	echo wp_kses_post( $html_message );
}
