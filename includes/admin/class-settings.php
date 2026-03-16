<?php
/**
 * NOVO
 */

namespace MydPro\Includes\Admin;

use MydPro\Includes\Admin\Admin_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to register plugin admin settings
 *
 * @since 1.9.6
 */
class Settings extends Admin_Settings {
		// Adiciona registro dos checkboxes de formas de pagamento
		// Garantindo que sejam salvos corretamente mesmo se desmarcados
		private static function sanitize_payment_checkbox($input, $expected) {
			return ($input === $expected) ? $expected : '';
		}
	/**
	 * Config group
	 *
	 * @since 1.9.6
	 */
	private const CONFIG_GROUP = 'fmd-settings-group';

	/**
	 * License group
	 *
	 * @since 1.9.6
	 */
	private const LICENSE_GROUP = 'fmd-license-group';

	/**
	 * Construct the class
	 *
	 * @since 1.9.6
	 */
	public function __construct() {
		$this->settings = [
					   // Bandeiras de cartão de crédito
					   [ 'name' => 'credit_card_visa', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'credit_card_master', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'credit_card_elo', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'credit_card_cabal', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'credit_card_hipercard', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'credit_card_amex', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'credit_card_diners', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'credit_card_hiper', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],

					   // Bandeiras de cartão de débito
					   [ 'name' => 'debit_card_visa', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'debit_card_master', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'debit_card_elo', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'debit_card_cabal', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],

					   // Pagamentos digitais
					   [ 'name' => 'digital_pix', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'digital_googlepay', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'digital_applepay', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'digital_samsungpay', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],

					   // Vouchers (Alimentação/Refeição)
					   [ 'name' => 'voucher_pluxee', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'voucher_alelo', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'voucher_vr', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
					   [ 'name' => 'voucher_ticket', 'option_group' => self::CONFIG_GROUP, 'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] ],
			[
				'name' => 'fdm-payment-credit',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => function($input) { return self::sanitize_payment_checkbox($input, 'CRD'); },
					'default' => '',
				],
			],
			[
				'name' => 'fdm-payment-debit',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => function($input) { return self::sanitize_payment_checkbox($input, 'DEB'); },
					'default' => '',
				],
			],
			[
				'name' => 'fdm-payment-vr',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => function($input) { return self::sanitize_payment_checkbox($input, 'VRF'); },
					'default' => '',
				],
			],
			[
				'name' => 'fdm-payment-cash',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => function($input) { return self::sanitize_payment_checkbox($input, 'DIN'); },
					'default' => '',
				],
			],
	[
	   'name' => 'myd-smtp-host',
	   'option_group' => self::CONFIG_GROUP,
	   'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
   ],
   [
	   'name' => 'credit_cards',
	   'option_group' => self::CONFIG_GROUP,
	   'args' => [
		   'sanitize_callback' => function($input) {
			   return is_array($input) ? array_map('sanitize_text_field', $input) : [];
		   },
		   'default' => [],
	   ],
   ],
   [
	   'name' => 'debit_cards',
	   'option_group' => self::CONFIG_GROUP,
	   'args' => [
		   'sanitize_callback' => function($input) {
			   return is_array($input) ? array_map('sanitize_text_field', $input) : [];
		   },
		   'default' => [],
	   ],
   ],
   [
	   'name' => 'vale_refeicao',
	   'option_group' => self::CONFIG_GROUP,
	   'args' => [
		   'sanitize_callback' => function($input) {
			   return is_array($input) ? array_map('sanitize_text_field', $input) : [];
		   },
		   'default' => [],
	   ],
   ],
   [
	   'name' => 'outros',
	   'option_group' => self::CONFIG_GROUP,
	   'args' => [
		   'sanitize_callback' => function($input) {
			   return is_array($input) ? array_map('sanitize_text_field', $input) : [];
		   },
		   'default' => [],
	   ],
   ],
		   [
			   'name' => 'myd-smtp-port',
			   'option_group' => self::CONFIG_GROUP,
			   'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
		   ],
		   [
			   'name' => 'myd-smtp-username',
			   'option_group' => self::CONFIG_GROUP,
			   'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
		   ],
		   [
			   'name' => 'myd-smtp-password',
			   'option_group' => self::CONFIG_GROUP,
			   'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
		   ],
		   [
			   'name' => 'myd-smtp-secure',
			   'option_group' => self::CONFIG_GROUP,
			   'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
		   ],
			[
				'name' => 'myd-currency',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
			[
				'name' => 'fdm-payment-type',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-business-name',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-business-country',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-mask-phone',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-estimate-time-delivery',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-list-menu-categories',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-payment-in-cash',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-principal-color',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => '#ea1d2b',
				]
			],
			[
				'name' => 'myd-price-color',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-number-decimal',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'intval',
					'default' => '2'
				]
			],
			[
				'name' => 'fdm-decimal-separator',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => ','
				]
			],
			[
				'name' => 'fdm-page-order-track',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-print-size',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'fdm-print-font-size',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-operation-mode-delivery',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => 'delivery',
				]
			],
			[
				'name' => 'myd-operation-mode-take-away',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-operation-mode-in-store',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-products-list-columns',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => 'myd-product-list--2columns'
				]
			],
			[
				'name' => 'myd-products-list-boxshadow',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
					'default' => 'myd-product-item--boxshadow'
				]
			],
			[
				'name' => 'myd-form-hide-zipcode',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-form-hide-address-number',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-option-minimum-price',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
            [
                'name' => 'myd-average-preparation-time',
                'option_group' => self::CONFIG_GROUP,
                'args' => [
                    'sanitize_callback' => 'intval',
                    'default' => 0
                ]
            ],
			[
				'name' => 'myd-option-redirect-whatsapp',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-delivery-time',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => ['initial'] //TODO: sanitize custom array
				]
			],
			[
				'name' => 'myd-delivery-mode',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-delivery-mode-options',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => ['initial'] //TODO: sanitize custom array
				]
			],
			[
				'name' => 'myd-business-mail',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				]
			],
			[
				'name' => 'myd-business-whatsapp',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
			[
				'name' => 'fdm-license',
				'option_group' => self::LICENSE_GROUP,
				'args' => []
			],
			[
				'name' => 'myd-delivery-force-open-close-store',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
                    'name' => 'myd-store-status-manual',
                    'option_group' => self::CONFIG_GROUP,
                    'args' => [
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => 'auto'
                    ]
                ],
			],
			[
				'name' => 'myd-shipping-distance-google-api-key',
				'option_group' => self::CONFIG_GROUP,
				'args' => [],
			],
			[
				'name' => 'myd-shipping-distance-address-radius',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'intval',
					'default' => 5000,
				],
			],
			[
				'name' => 'myd-shipping-distance-address-latitude',
				'option_group' => self::CONFIG_GROUP,
				'args' => [],
			],
			[
				'name' => 'myd-shipping-distance-address-longitude',
				'option_group' => self::CONFIG_GROUP,
				'args' => [],
			],
			[
				'name' => 'myd-shipping-distance-formated-address',
				'option_group' => self::CONFIG_GROUP,
				'args' => [],
			],
			[
				'name' => 'myd-template-order-custom-message-list-products',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '{product-qty} {product-name}' . PHP_EOL .
					'{product-extras}' . PHP_EOL .
					esc_html__( 'Note', 'myd-delivery-pro' ) . ': {product-note}',
				],
			],
			[
				'name' => 'myd-template-order-custom-message-delivery',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '===== ' . esc_html__( 'Order', 'myd-delivery-pro' ) . ' {order-number} ====='
					. PHP_EOL . PHP_EOL .
					'{order-products}' . PHP_EOL .
					esc_html__( 'Delivery', 'myd-delivery-pro' ) . ': {shipping-price}' . PHP_EOL .
					esc_html__( 'Order Total', 'myd-delivery-pro' ) . ': {order-total}' . PHP_EOL .
					esc_html__( 'Payment Method', 'myd-delivery-pro' ) . ': {payment-method}' . PHP_EOL .
					esc_html__( 'Change', 'myd-delivery-pro' ) . ': {payment-change}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Customer', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					'{customer-name}' . PHP_EOL .
					'{customer-phone}' . PHP_EOL .
					'{customer-address}, {customer-address-number}' . PHP_EOL .
					'{customer-address-complement}' . PHP_EOL .
					'{customer-address-neighborhood}' . PHP_EOL .
					'{customer-address-zipcode}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Track Order', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					'{order-track-page}',
				],
			],
			[
				'name' => 'myd-template-order-custom-message-take-away',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '===== ' . esc_html__( 'Order', 'myd-delivery-pro' ) . ' {order-number} ====='
					. PHP_EOL . PHP_EOL .
					'{order-products}' . PHP_EOL .
					esc_html__( 'Order Total', 'myd-delivery-pro' ) . ': {order-total}' . PHP_EOL .
					esc_html__( 'Payment Method', 'myd-delivery-pro' ) . ': {payment-method}' . PHP_EOL .
					esc_html__( 'Change', 'myd-delivery-pro' ) . ': {payment-change}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Customer', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					'{customer-name}' . PHP_EOL .
					'{customer-phone}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Track Order', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					'{order-track-page}',
				],
			],
			[
				'name' => 'myd-template-order-custom-message-digital-menu',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'default' => '===== ' . esc_html__( 'Order', 'myd-delivery-pro' ) . ' {order-number} ====='
					. PHP_EOL . PHP_EOL .
					'{order-products}' . PHP_EOL .
					esc_html__( 'Order Total', 'myd-delivery-pro' ) . ': {order-total}' . PHP_EOL .
					esc_html__( 'Payment Method', 'myd-delivery-pro' ) . ': {payment-method}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Customer', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					esc_html__( 'Table', 'myd-delivery-pro' ) . ': {order-table}' . PHP_EOL .
					'{customer-name}' . PHP_EOL . PHP_EOL .
					'===== ' . esc_html__( 'Track Order', 'myd-delivery-pro' ) . ' ====='
					. PHP_EOL . PHP_EOL .
					'{order-track-page}',
				],
			],
			[
				'name' => 'evolution_api_url',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'evolution_api_key',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'evolution_webhook_key',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'evolution_instance_name',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'evolution_ddi',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '55' ],
			],
			[
				'name' => 'evolution_msg_confirmed',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_textarea_field' ],
			],
			[
				'name' => 'evolution_msg_delivery',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_textarea_field' ],
			],
			[
				'name' => 'evolution_msg_confirmed_title',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_textarea_field' ],
			],
			// Button message options
			[
				'name' => 'evolution_btn_enabled',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'evolution_btn_title',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'evolution_btn_description',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_textarea_field' ],
			],
			[
				'name' => 'evolution_btn_footer',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'evolution_btn_delay',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'intval', 'default' => 0 ],
			],
			[
				'name' => 'evolution_btn_display_text',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			// Facebook Pixel / Conversions API
			[
				'name' => 'myd_facebook_pixel_id',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'myd_facebook_capi_token',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			// Mercado Pago (igual ao Dev Delivery OLD)
			[
				'name' => 'mercadopago_public_key',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
			[
				'name' => 'mercadopago_access_token',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
			[
				'name' => 'mercadopago_webhook_secret',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
			[
				'name' => 'myd-business-address',
				'option_group' => self::CONFIG_GROUP,
				'args' => [
					'sanitize_callback' => [self::class, 'validate_required_address'],
				],
			],
			// iFood settings
			[
				'name' => 'ifood_client_id',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'ifood_client_secret',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'ifood_access_token',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'ifood_token_expiry',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'ifood_token_expiry_timestamp',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'ifood_wp_api_secret',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'ifood_backend_url',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
			[
				'name' => 'ifood_backend_secret',
				'option_group' => self::CONFIG_GROUP,
				'args' => [ 'sanitize_callback' => 'sanitize_text_field' ],
			],
		];
	}
	/**
	 * Validação obrigatória do endereço ao salvar configurações
	 */
	public static function validate_required_address($input) {
		if (empty($input)) {
			add_settings_error('myd-business-address', 'address_required', __('O endereço da loja é obrigatório.', 'myd-delivery-pro'));
			return '';
		}
		return $input;
	}
}
