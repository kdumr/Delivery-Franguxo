<?php

namespace MydPro\Includes\Admin;

use MydPro\Includes\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin page
 *
 * TODO: improve this doc
 */
class Admin_Page {
	/**
	 * Menu page
	 *
	 * @since 1.9.6
	 */
	protected $menu_page;

	/**
	 * Submenu pages
	 *
	 * @since 1.9.6
	 */
	protected $submenu_pages;

	/**
	 * Page templates
	 *
	 * @since 1.9.6
	 */
	protected $page_templates;

	/**
	 * License
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

		$this->menu_page = [
			'page_title' => 'MyD Delivery',
			'menu_title' => 'MyD Delivery',
			'capability' => 'publish_posts',
			'slug' => 'myd-delivery-dashoboard',
			'call_template' => '',
			'icon' => MYD_PLUGN_URL . 'assets/img/fdm-icon.png',
			'position' => 56,
		];

		$this->submenu_pages = [
			[
				'parent_slug' => 'myd-delivery-dashoboard',
				'page_title' => 'MyD Delivery Dashboard',
				'menu_title' => esc_html__( 'Dashboard', 'myd-delivery-pro' ),
				'capability' => 'publish_posts',
				'slug' => 'myd-delivery-dashoboard',
				'call_template' => [ $this, 'get_template_dashboard' ],
				'position' => 0,
				'condition' => false,
			],
			[
				'parent_slug' => 'myd-delivery-dashoboard',
				'page_title' => 'MyD Delivery Customers',
				'menu_title' => esc_html__( 'Customers', 'myd-delivery-pro' ),
				'capability' => 'publish_posts',
				'slug' => 'myd-delivery-customers',
				'call_template' => [ $this, 'get_template_customers' ],
				'position' => 4,
				'condition' => true,
			],
			[
				'parent_slug' => 'myd-delivery-dashoboard',
				'page_title' => 'Programa de Fidelidade',
				'menu_title' => esc_html__( 'Fidelidade', 'myd-delivery-pro' ),
				'capability' => 'publish_posts',
				'slug' => 'myd-delivery-fidelidade',
				'call_template' => [ $this, 'get_template_fidelidade' ],
				'position' => 3.5, // logo após Pedidos
				'condition' => true,
			],
			[
				'parent_slug' => 'myd-delivery-dashoboard',
				'page_title' => 'MyD Delivery Reports',
				'menu_title' => esc_html__( 'Reports', 'myd-delivery-pro' ),
				'capability' => 'myd_view_reports',
				'slug' => 'myd-delivery-reports',
				'call_template' => [ $this, 'get_template_reports' ],
				'position' => 5,
				'condition' => true,
			],
			[
				'parent_slug' => 'myd-delivery-dashoboard',
				'page_title' => 'MyD Delivery Settings',
				'menu_title' => esc_html__( 'Settings', 'myd-delivery-pro' ),
				'capability' => 'manage_options',
				'slug' => 'myd-delivery-settings',
				'call_template' => [ $this, 'get_template_settings' ],
				'position' => 6,
				'condition' => true,
			],
			[
				'parent_slug' => 'myd-delivery-dashoboard',
				'page_title' => 'MyD Delivery Add-ons',
				'menu_title' => esc_html__( 'Add-ons', 'myd-delivery-pro' ),
				'capability' => 'read',
				'slug' => 'myd-delivery-addons',
				'call_template' => [ $this, 'get_template_addons' ],
				'position' => 7,
				'condition' => false,
			],
		];

		$this->page_templates = [
			'dashboard' => MYD_PLUGIN_PATH . 'templates/admin/dashboard.php',
			'settings' => MYD_PLUGIN_PATH . 'templates/admin/settings.php',
			'reports' => MYD_PLUGIN_PATH . 'templates/admin/reports.php',
			'customers' => MYD_PLUGIN_PATH . 'templates/admin/customers.php',
			'addons' => MYD_PLUGIN_PATH . 'templates/admin/addons.php',
			'fidelidade' => MYD_PLUGIN_PATH . 'templates/admin/fidelidade.php',
		];
	}

	/**
	 * Get template page Fidelidade
	 *
	 * @since 1.9.99
	 */
	public function get_template_fidelidade() {
		include_once $this->page_templates['fidelidade'];
	}
	/**
	 * Add admin page
	 *
	 * @since 1.9.6
	 */
	public function add_admin_pages() {
		$this->add_menu_page();
		$this->add_submenu_page();
	}

	/**
	 * Add menu page
	 *
	 * @since 1.9.6
	 */
	public function add_menu_page() {
		add_menu_page(
			$this->menu_page['page_title'],
			$this->menu_page['menu_title'],
			$this->menu_page['capability'],
			$this->menu_page['slug'],
			$this->menu_page['call_template'],
			$this->menu_page['icon'],
			$this->menu_page['position']
		);
	}

	/**
	 * Add submenu pages
	 *
	 * @since 1.9.6
	 */
	public function add_submenu_page() {
		$submenu_pages = apply_filters( 'myd-delivery/admin/before-register-submenu-pages', $this->submenu_pages );

		foreach ( $submenu_pages as $submenu ) {
			if ( $submenu['condition'] === false || $submenu['condition'] === true && $this->license->get_status() === 'active' || $this->license->get_status() === 'expired' || $this->license->get_status() === 'mismatch' ) {

				add_submenu_page(
					$submenu['parent_slug'],
					$submenu['page_title'],
					$submenu['menu_title'],
					$submenu['capability'],
					$submenu['slug'],
					$submenu['call_template'],
					$submenu['position']
				);
			}
		}
	}

	/**
	 * Get template page License
	 *
	 * @since 1.9.6
	 */
	public function get_template_dashboard() {
		include_once $this->page_templates['dashboard'];
	}

	/**
	 * Get template page Settings
	 *
	 * @since 1.9.6
	 */
	public function get_template_settings() {
		include_once $this->page_templates['settings'];
	}

	/**
	 * Get template page Reports
	 *
	 * @since 1.9.6
	 */
	public function get_template_reports() {
		include_once $this->page_templates['reports'];
	}

	/**
	 * Get template page Customer
	 *
	 * @since 1.9.6
	 */
	public function get_template_customers() {
		include_once $this->page_templates['customers'];
	}

	/**
	 * Get template page Add-ons
	 *
	 * @since 1.9.32
	 */
	public function get_template_addons() {
		include_once $this->page_templates['addons'];
	}
}
