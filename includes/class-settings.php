<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function add_settings_page() {
		add_options_page(
			'MyDelivery Settings',
			'MyDelivery',
			'manage_options',
			'myd-settings',
			[ $this, 'settings_page' ]
		);
	}

	public function register_settings() {
		register_setting( 'myd_settings_group', 'myd_push_server_url' );
		register_setting( 'myd_settings_group', 'myd_push_secret' );

		// Facebook / Pixel settings
		register_setting( 'myd_settings_group', 'myd_facebook_pixel_id' );
		register_setting( 'myd_settings_group', 'myd_facebook_capi_token' );

		add_settings_section(
			'myd_push_section',
			'Push Server Configuration',
			[ $this, 'section_callback' ],
			'myd-settings'
		);

		add_settings_field(
			'myd_push_server_url',
			'Push Server URL',
			[ $this, 'url_field_callback' ],
			'myd-settings',
			'myd_push_section'
		);

		add_settings_field(
			'myd_push_secret',
			'Push Server Secret',
			[ $this, 'secret_field_callback' ],
			'myd-settings',
			'myd_push_section'
		);

		// Facebook Pixel section
		add_settings_section(
			'myd_fb_section',
			'Facebook / Pixel',
			[ $this, 'fb_section_callback' ],
			'myd-settings'
		);

		add_settings_field(
			'myd_facebook_pixel_id',
			'Facebook Pixel ID',
			[ $this, 'pixel_field_callback' ],
			'myd-settings',
			'myd_fb_section'
		);

		add_settings_field(
			'myd_facebook_capi_token',
			'Facebook CAPI Access Token',
			[ $this, 'capi_field_callback' ],
			'myd-settings',
			'myd_fb_section'
		);
	}

	public function fb_section_callback() {
		echo '<p>Configure o Facebook Pixel e (opcional) o token do Conversions API para envio server-side.</p>';
	}

	public function pixel_field_callback() {
		$value = get_option( 'myd_facebook_pixel_id', '' );
		echo '<input type="text" name="myd_facebook_pixel_id" value="' . esc_attr( $value ) . '" placeholder="123456789012345" style="width:100%;" />';
		echo '<p class="description">ID do Pixel (ex: 123456789012345). Usado para o tracking client-side.</p>';
	}

	public function capi_field_callback() {
		$value = get_option( 'myd_facebook_capi_token', '' );
		echo '<input type="text" name="myd_facebook_capi_token" value="' . esc_attr( $value ) . '" placeholder="EAAB..." style="width:100%;" />';
		echo '<p class="description">Access Token do Conversions API (opcional). Usado para enviar eventos server-side ao Facebook.</p>';
	}

	public function section_callback() {
		echo '<p>Configure the push server for real-time updates.</p>';
	}

	public function url_field_callback() {
		$value = get_option( 'myd_push_server_url', '' );
		echo '<input type="text" name="myd_push_server_url" value="' . esc_attr( $value ) . '" placeholder="https://nodes-siteapi.ojhhy6.easypanel.host" style="width: 100%;" />';
	}

	public function secret_field_callback() {
		$value = get_option( 'myd_push_secret', '' );
		echo '<input type="password" name="myd_push_secret" value="' . esc_attr( $value ) . '" placeholder="super-secret-key-2025-change-this-in-production-abcdef123456" style="width: 100%;" />';
	}

	public function settings_page() {
		?>
		<div class="wrap">
			<h1>MyDelivery Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'myd_settings_group' );
				do_settings_sections( 'myd-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}

new Settings();