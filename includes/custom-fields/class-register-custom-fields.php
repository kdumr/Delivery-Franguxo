<?php

namespace MydPro\Includes\Custom_Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Custom Fields Class.
 * TODO: Refactor.
 * @since 1.9.31
 */
class Register_Custom_Fields {
	/**
	 * Registered fields
	 *
	 * @var array
	 */
	public static $myd_fields = array();

	/**
	 * Get registered fields
	 *
	 * @return array
	 */
	public static function get_registered_fields() {
		if ( ! empty( self::$myd_fields ) ) {
			return self::$myd_fields;
		}

		self::set_custom_fields();
		return self::$myd_fields;
	}

	/**
	 * Set custom fields
	 *
	 * @return void
	 */
	public static function set_custom_fields() {
		/**
		 * Coupon fields
		 *
		 * @since 1.9.5
		 */
		self::$myd_fields['myd_coupons_options'] = [
			'id' => 'myd_coupons_options',
			'name' => __( 'Coupons info', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-coupons',
			'fields' => [
				'myd_coupon_type' => [
					'type' => 'select',
					'label' => __( 'What the coupon type?', 'myd-delivery-pro' ),
					'id' => 'myd_coupon_type',
					'name' => 'myd_coupon_type',
					'custom_class' => '',
					'required' => true,
					'select_options' => [
						'discount-total' => __( 'Total discount', 'myd-delivery-pro' ),
						'discount-delivery' => __( 'Delivery discount', 'myd-delivery-pro' )
					]
				],
				'myd_discount_format' => [
					'type' => 'select',
					'label' => __( 'Discount format' , 'myd-delivery-pro' ),
					'id' => 'myd_discount_format',
					'name' => 'myd_discount_format',
					'custom_class' => '',
					'required' => true,
					'select_options' => [
						'amount' => __( 'Amount discount ($)', 'myd-delivery-pro' ),
						'percent' => __( 'Percent discount (%)', 'myd-delivery-pro' )
					]
				],
				'myd_discount_value' => [
					'type' => 'number',
					'label' => __( 'Valor do desconto', 'myd-delivery-pro' ),
					'id' => 'myd_discount_value',
					'name' => 'myd_discount_value',
					'custom_class' => '',
					'min' => 0,
					'max' => '',
					'required' => true
				],
				'myd_coupont_description' => [
					'type' => 'textarea',
					'label' => __( 'Descrição do cupom', 'myd-delivery-pro' ),
					'id' => 'myd_coupon_description',
					'name' => 'myd_coupon_description',
					'custom_class' => '',
					'required' => false
				],
				'myd_coupon_usage_limit' => [
					'type' => 'number',
					'label' => __( 'Limite máximo de uso', 'myd-delivery-pro' ),
					'id' => 'myd_coupon_usage_limit',
					'name' => 'myd_coupon_usage_limit',
					'custom_class' => '',
					'min' => 0,
					'max' => '',
					'required' => false,
					'description' => __( 'Deixe vazio para uso ilimitado', 'myd-delivery-pro' )
				],
				'myd_coupon_expiry_date' => [
					'type' => 'datetime-local',
					'label' => __( 'Data e hora de expiração', 'myd-delivery-pro' ),
					'id' => 'myd_coupon_expiry_date',
					'name' => 'myd_coupon_expiry_date',
					'custom_class' => '',
					'required' => false,
					'description' => __( 'Deixe vazio para que o cupom nunca expire.', 'myd-delivery-pro' )
				]
			]
		];

		/**
		 * Products fields
		 *
		 * @since 1.9.5
		 */
		$category_options = array();
		$categories = \get_option( 'fdm-list-menu-categories' );
		$categories = explode( ',', $categories );
		$categories = array_map( 'trim', $categories );

		if ( is_array( $categories ) ) {
			foreach ( $categories as $category ) {
				$category_options[ $category ] = $category;
			}
		}

		self::$myd_fields['myd_product_options'] = [
			'id' => 'myd_product_options',
			'name' => __( 'Product Info', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-produtos',
			'fields' => [
				'myd_product_id' => [
					'type' => 'text',
					'label' => __( 'Product ID', 'myd-delivery-pro' ),
					'id' => 'myd_product_id',
					'name' => 'product_id',
					'custom_class' => '',
					'required' => true,
					'readonly' => true,
					'description' => __( 'Auto-generated unique 8-digit ID', 'myd-delivery-pro' ),
				],
				'myd_product_image' => [
					'type' => 'image',
					'label' => __( 'Image', 'myd-delivery-pro' ),
					'id' => 'myd_product_image',
					'name' => 'product_image',
					'custom_class' => '',
					'required' => true,
				],
				'myd_product_available' => [
					'type' => 'select',
					'label' => __( 'Available?', 'myd-delivery-pro' ),
					'id' => 'myd_product_available',
					'name' => 'product_available',
					'custom_class' => '',
					'required' => true,
					'value' => 'show',
					'select_options' => [
						'show' => __( 'Yes, show the product', 'myd-delivery-pro' ),
						'hide' => __( 'No, hide the product', 'myd-delivery-pro' ),
						'not-available' => __( 'Show as not available', 'myd-delivery-pro' ),
					],
				],
				'myd_product_category' => [
					'type' => 'checkbox_group',
					'label' => __( 'Category', 'myd-delivery-pro' ),
					'id' => 'myd_product_category',
					'name' => 'product_type',
					'custom_class' => '',
					'required' => true,
					'select_options' => $category_options,
				],
				'myd_product_featured' => [
					'type' => 'checkbox',
					'label' => __( 'Destacar produto', 'myd-delivery-pro' ),
					'id' => 'myd_product_featured',
					'name' => 'product_featured',
					'custom_class' => '',
					'required' => false,
				],
				'myd_discount_value' => [
					'type' => 'number',
					'label' => __( 'Price', 'myd-delivery-pro' ),
					'id' => 'myd_product_price',
					'name' => 'product_price',
					'custom_class' => '',
					'required' => true,
				],
				'myd_product_price_label' => [
					'type' => 'select',
					'label' => __( 'Price label', 'myd-delivery-pro' ),
					'id' => 'myd_product_price_label',
					'name' => 'product_price_label',
					'value' => 'show',
					'required' => true,
					'select_options' => [
						'show' => __( 'Show the price', 'myd-delivery-pro' ),
						'hide' => __( 'Hide the price', 'myd-delivery-pro' ),
						'from' => __( 'Show as "From {{product price}}"', 'myd-delivery-pro' ),
						'consult' => __( 'Show as "By Consult"', 'myd-delivery-pro' ),
					],
				],
				'myd_product_description' => [
					'type' => 'textarea',
					'label' => __( 'Description', 'myd-delivery-pro' ),
					'id' => 'myd_product_description',
					'name' => 'product_description',
					'custom_class' => '',
					'required' => false,
				],
				// Product seals (selos) — multiple checkboxes
				'myd_product_seals' => [
					'type' => 'radio_group',
					'label' => __( 'Selos', 'myd-delivery-pro' ),
					'id' => 'myd_product_seals',
					'name' => 'product_seals',
					'custom_class' => '',
					'required' => false,
					'select_options' => [
						'mais-vendido' => __( 'Mais vendido', 'myd-delivery-pro' ),
						'custo-beneficio' => __( 'Custo benefício', 'myd-delivery-pro' ),
					],
				],
			]
		];



		/**
		 * Extras CPT fields (centralized extras management)
		 *
		 * Each extra is a post in the mydelivery-extras CPT.
		 * These fields define the extra's properties.
		 */
		self::$myd_fields['myd_extra_group_options'] = [
			'id' => 'myd_extra_group_options',
			'name' => __( 'Configurações do Extra', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-extras',
			'fields' => [
				'myd_extra_available' => [
					'type' => 'select',
					'label' => __( 'Disponível?', 'myd-delivery-pro' ),
					'id' => 'myd_extra_available',
					'name' => 'extra_available',
					'custom_class' => '',
					'required' => true,
					'value' => 'show',
					'default_value' => 'show',
					'select_options' => [
						'show' => __( 'Sim, exibir o extra', 'myd-delivery-pro' ),
						'hide' => __( 'Não, ocultar o extra', 'myd-delivery-pro' ),
						'not-available' => __( 'Exibir como indisponível', 'myd-delivery-pro' ),
					],
				],
				'myd_extra_min_limit' => [
					'type' => 'number',
					'label' => __( 'Limite mín.', 'myd-delivery-pro' ),
					'id' => 'myd_extra_min_limit',
					'name' => 'extra_min_limit',
					'custom_class' => 'myd-input-size-10',
				],
				'myd_extra_max_limit' => [
					'type' => 'number',
					'label' => __( 'Limite máx.', 'myd-delivery-pro' ),
					'id' => 'myd_extra_max_limit',
					'name' => 'extra_max_limit',
					'custom_class' => 'myd-input-size-10',
				],
			]
		];

		self::$myd_fields['myd_extra_options_group'] = [
			'id' => 'myd_extra_options_group',
			'name' => __( 'Opções do Extra', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-extras',
			'wrapper' => 'wide',
			'fields' => [
				'myd_extra_options' => [
					'type' => 'repeater',
					'label' => __( 'Opções', 'myd-delivery-pro' ),
					'id' => 'myd_extra_options',
					'name' => 'myd_extra_options',
					'custom_class' => '',
					'fields' => [
						[
							'type' => 'select',
							'label' => __( 'Disponível?', 'myd-delivery-pro' ),
							'id' => 'myd_extra_option_available',
							'name' => 'extra_option_available',
							'custom_class' => '',
							'required' => true,
							'value' => 'show',
							'default_value' => 'show',
							'select_options' => [
								'show' => __( 'Sim, exibir a opção', 'myd-delivery-pro' ),
								'hide' => __( 'Não, ocultar a opção', 'myd-delivery-pro' ),
								'not-available' => __( 'Exibir como indisponível', 'myd-delivery-pro' ),
							],
						],
						[
							'type' => 'text',
							'label' => __( 'Nome da opção', 'myd-delivery-pro' ),
							'id' => 'myd_extra_option_name',
							'name' => 'extra_option_name',
							'custom_class' => ' myd-input-size-75',
						],
						[
							'type' => 'number',
							'label' => __( 'Preço da opção', 'myd-delivery-pro' ),
							'id' => 'myd_extra_option_price',
							'name' => 'extra_option_price',
							'custom_class' => 'myd-input-size-20',
						],
						[
							'type' => 'textarea',
							'label' => __( 'Descrição da opção', 'myd-delivery-pro' ),
							'id' => 'myd_extra_option_description',
							'name' => 'extra_option_description',
							'custom_class' => '',
						],
					]
				],
			]
		];

		/**
		 * Linked extras field for products
		 *
		 * Allows linking centralized extras to products via checkboxes.
		 */
		self::$myd_fields['myd_product_linked_extras'] = [
			'id' => 'myd_product_linked_extras',
			'name' => __( 'Extras Vinculados', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-produtos',
			'fields' => [
				'myd_linked_extras' => [
					'type' => 'linked_extras',
					'label' => __( 'Selecionar Extras', 'myd-delivery-pro' ),
					'id' => 'myd_linked_extras',
					'name' => 'myd_linked_extras',
					'custom_class' => '',
					'required' => false,
				],
			]
		];

		/**
		 * Order fields
		 *
		 * @since 1.9.5
		 */
		self::$myd_fields['myd_order_data'] = [
			'id' => 'myd_order_data',
			'name' => __( 'Order Data', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-orders',
			'fields' => [
				// Novo: Local onde o pedido foi feito
				'myd_order_channel' => [
					'type' => 'text',
					'label' => __( 'Local do pedido', 'myd-delivery-pro' ),
					'id' => 'myd_order_channel',
					'name' => 'order_channel',
					'custom_class' => '',
					'required' => false,
					'description' => __( 'Exibe o local/canal onde o pedido foi realizado (ex: WhatsApp, Site, Balcão, etc).', 'myd-delivery-pro' ),
				],
				// Novo: Localizador de pedido (preenchido automaticamente ao salvar)
				'myd_order_locator' => [
					'type' => 'text',
					'label' => __( 'Localizador de pedido', 'myd-delivery-pro' ),
					'id' => 'myd_order_locator',
					'name' => 'order_locator',
					'custom_class' => '',
					'required' => false,
					'description' => __( 'Preenchido automaticamente com 8 números únicos ao salvar.', 'myd-delivery-pro' ),
				],

				// Novo: Código de confirmação
				'myd_order_confirmation_code' => [
					'type' => 'text',
					'label' => __( 'Código de confirmação', 'myd-delivery-pro' ),
					'id' => 'myd_order_confirmation_code',
					'name' => 'order_confirmation_code',
					'custom_class' => '',
					'required' => false,
				],
				'myd_order_status' => [
					'type' => 'select',
					'label' => __( 'Status', 'myd-delivery-pro' ),
					'id' => 'myd_order_status',
					'name' => 'order_status',
					'custom_class' => '',
					'required' => true,
					'select_options' => array(
						'new' => __( 'New', 'myd-delivery-pro' ),
						'confirmed' => __( 'Confirmed', 'myd-delivery-pro' ),
						'done' => __( 'Done', 'myd-delivery-pro' ),
						'waiting' => __( 'Waiting', 'myd-delivery-pro' ),
						'in-delivery' => __( 'In Delivery', 'myd-delivery-pro' ),
						'finished' => __( 'Finished', 'myd-delivery-pro' ),
						'canceled' => __( 'Canceled', 'myd-delivery-pro' ),
					),
				],
				'myd_order_date' => [
					'type' => 'text',
					'label' => __( 'Date', 'myd-delivery-pro' ),
					'id' => 'myd_order_date',
					'name' => 'order_date',
					'custom_class' => '',
					'required' => false
				],
				'myd_order_eta' => [
					'type'         => 'text',
					'label'        => __( 'Entrega prevista', 'myd-delivery-pro' ),
					'id'           => 'myd_order_eta',
					'name'         => 'order_eta',
					'custom_class' => '',
					'required'     => false,
					'readonly'     => true,
					'description'  => __( 'Calculado automaticamente: Data + tempo estimado de entrega.', 'myd-delivery-pro' ),
				],
				'myd_order_ship_method' => [
					'type' => 'text',
					'label' => __( 'Type', 'myd-delivery-pro' ),
					'id' => 'myd_order_ship_method',
					'name' => 'order_ship_method',
					'custom_class' => '',
					'required' => true
				],
				'myd_order_delivery_time' => [
					'type' => 'text',
					'label' => __( 'Hora de entrega', 'myd-delivery-pro' ),
					'id' => 'myd_order_delivery_time',
					'name' => 'order_delivery_time',
					'custom_class' => '',
					'required' => false
				],
				'myd_custom_field_image_preview' => [
					'type' => 'image',
					'label' => __( 'Image Preview', 'myd-delivery-pro' ),
					'id' => 'myd_custom_field_image_preview',
					'name' => 'myd-custom-field__image-preview',
					'custom_class' => '',
					'required' => false
				],
			]
		];

		self::$myd_fields['myd_order_customer'] = [
			'id' => 'myd_order_customer',
			'name' => __( 'Customer', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-orders',
			'fields' => [
				'myd_order_customer_name' => [
					'type' => 'text',
					'label' => __( 'Full name', 'myd-delivery-pro' ),
					'id' => 'myd_order_customer_name',
					'name' => 'order_customer_name',
					'custom_class' => '',
					'required' => true
				],
				'myd_order_customer_phone' => [
					'type' => 'text',
					'label' => __( 'Phone', 'myd-delivery-pro' ),
					'id' => 'myd_order_customer_phone',
					'name' => 'customer_phone',
					'custom_class' => '',
					'required' => false
				],
				'myd_order_customer_address' => [
					'type' => 'text',
					'label' => __( 'Address', 'myd-delivery-pro' ),
					'id' => 'myd_order_customer_address',
					'name' => 'order_address',
					'custom_class' => '',
					'required' => false
				],
				'myd_order_customer_address_number' => [
					'type' => 'number',
					'label' => __( 'Number', 'myd-delivery-pro' ),
					'id' => 'myd_order_customer_address_number',
					'name' => 'order_address_number',
					'custom_class' => '',
					'required' => false
				],
				'myd_order_customer_address_reference' => [
					'type' => 'text',
					'label' => __( 'Reference Point', 'myd-delivery-pro' ),
					'id' => 'myd_order_customer_address_reference',
					'name' => 'order_address_reference',
					'custom_class' => '',
					'required' => false
				],
				'myd_order_customer_address_comp' => [
					'type' => 'text',
					'label' => __( 'Apartment, suite, etc.', 'myd-delivery-pro' ),
					'id' => 'myd_order_customer_address_comp',
					'name' => 'order_address_comp',
					'custom_class' => '',
					'required' => false
				],
				'myd_order_customer_neighborhood' => [
					'type' => 'text',
					'label' => __( 'Neighborhood', 'myd-delivery-pro' ),
					'id' => 'myd_order_customer_neighborhood',
					'name' => 'order_neighborhood',
					'custom_class' => '',
					'required' => false
				],
				'myd_order_customer_real_neighborhood' => [
					'type' => 'text',
					'label' => 'Bairro Real',
					'id' => 'myd_order_customer_real_neighborhood',
					'name' => 'order_real_neighborhood',
					'custom_class' => '',
					'required' => false,
					'readonly' => true,
				],
				'myd_order_customer_order_zipcode' => [
					'type' => 'text',
					'label' => __( 'Zipcode', 'myd-delivery-pro' ),
					'id' => 'myd_order_customer_order_zipcode',
					'name' => 'order_zipcode',
					'custom_class' => '',
					'required' => false
				],
			]
		];

		self::$myd_fields['myd_order_payment'] = [
			'id' => 'myd_order_payment',
			'name' => __( 'Payment', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-orders',
			'fields' => [
				'myd_order_payment_status' => [
					'type' => 'select',
					'label' => __( 'Status', 'myd-delivery-pro' ),
					'id' => 'myd_payment_status',
					'name' => 'order_payment_status',
					'custom_class' => '',
					'required' => true,
					'select_options' => array(
						'waiting' => __( 'Waiting', 'myd-delivery-pro' ),
						'paid' => __( 'Paid', 'myd-delivery-pro' ),
						'failed' => __( 'Failed', 'myd-delivery-pro' ),
						'refunded' => __( 'Reembolsado', 'myd-delivery-pro' ),
					),
				],
				'myd_order_payment_type' => [
					'type' => 'select',
					'label' => __( 'Type', 'myd-delivery-pro' ),
					'id' => 'myd_payment_type',
					'name' => 'order_payment_type',
					'custom_class' => '',
					'required' => true,
					'select_options' => array(
						'upon-delivery' => __( 'Upon Delivery', 'myd-delivery-pro' ),
						'payment-integration' => __( 'Payment Integration', 'myd-delivery-pro' ),
					),
				],

				// Dataid field (below payment type)
				'myd_order_payment_dataid' => [
					'type' => 'text',
					'label' => __( 'Dataid', 'myd-delivery-pro' ),
					'id' => 'myd_order_payment_dataid',
					'name' => 'order_payment_dataid',
					'custom_class' => '',
					'required' => false,
				],
				'myd_order_payment_method' => [
					'type' => 'text',
					'label' => __( 'Method', 'myd-delivery-pro' ),
					'id' => 'myd_order_payment_method',
					'name' => 'order_payment_method',
					'custom_class' => '',
					'required' => true,
				],
				'myd_order_delivery_price' => [
					'type' => 'text',
					'label' => __( 'Delivery Price', 'myd-delivery-pro' ),
					'id' => 'myd_order_delivery_price',
					'name' => 'order_delivery_price',
					'custom_class' => '',
					'required' => true,
				],
				'myd_order_coupon' => [
					'type' => 'text',
					'label' => __( 'Coupon code', 'myd-delivery-pro' ),
					'id' => 'myd_order_coupon',
					'name' => 'order_coupon',
					'custom_class' => '',
					'required' => false,
				],
				'myd_order_coupon_discount' => [
					'type' => 'text',
					'label' => __( 'Desconto de cupom', 'myd-delivery-pro' ),
					'id' => 'myd_order_coupon_discount',
					'name' => 'order_coupon_discount',
					'custom_class' => '',
					'required' => false,
				],
				// Novo campo: desconto proveniente do programa de fidelidade (se aplicável)
				'myd_order_fidelity_discount' => [
					'type' => 'text',
					'label' => __( 'Desconto de fidelidade', 'myd-delivery-pro' ),
					'id' => 'myd_order_fidelity_discount',
					'name' => 'order_fidelity_discount',
					'custom_class' => '',
					'required' => false,
				],
				'myd_order_subtotal' => [
					'type' => 'text',
					'label' => __( 'Subtotal', 'myd-delivery-pro' ),
					'id' => 'myd_order_subtotal',
					'name' => 'order_subtotal',
					'custom_class' => '',
					'required' => true,
				],
				'myd_order_total' => [
					'type' => 'text',
					'label' => __( 'Total', 'myd-delivery-pro' ),
					'id' => 'myd_order_total',
					'name' => 'order_total',
					'custom_class' => '',
					'required' => true,
				],
				'myd_order_change' => [
					'type' => 'text',
					'label' => __( 'Change for', 'myd-delivery-pro' ),
					'id' => 'myd_order_change',
					'name' => 'order_change',
					'custom_class' => '',
					'required' => false,
				],
			]
		];

		self::$myd_fields['myd_order_in_store'] = [
			'id' => 'myd_order_in_store',
			'name' => __( 'Order in Store', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-orders',
			'fields' => [
				'myd_order_table' => [
					'type' => 'number',
					'label' => __( 'Table', 'myd-delivery-pro' ),
					'id' => 'myd_order_table',
					'name' => 'order_table',
					'custom_class' => '',
					'required' => false
				]
			]
		];

		self::$myd_fields['myd_order_ifood'] = [
			'id' => 'myd_order_ifood',
			'name' => __( 'iFood', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-orders',
			'fields' => [
				'ifood_order_id' => [
					'type' => 'text',
					'label' => __( 'ID Original do iFood', 'myd-delivery-pro' ),
					'id' => 'ifood_order_id',
					'name' => 'ifood_order_id',
					'custom_class' => '',
					'required' => false,
					'description' => __( 'Identificador original (UUID) importado do iFood.', 'myd-delivery-pro' ),
				],
				'ifood_delivery_observations' => [
					'type'         => 'textarea',
					'label'        => __( 'Observação', 'myd-delivery-pro' ),
					'id'           => 'ifood_delivery_observations',
					'name'         => 'ifood_delivery_observations',
					'custom_class' => '',
					'required'     => false,
					'readonly'     => true,
					'description'  => __( 'Observação de entrega enviada pelo iFood.', 'myd-delivery-pro' ),
				],
			]
		];

		self::$myd_fields['myd_order_note'] = [
			'id' => 'myd_order_note',
			'name' => __( 'Notes', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-orders',
			'fields' => [
				'myd_order_notes' => [
					'type' => 'order-note',
					'label' => __( 'Notes', 'myd-delivery-pro' ),
					'id' => 'myd_order_notes',
					'name' => 'order_notes',
					'custom_class' => '',
					'required' => false
				]
			]
		];

		/**
		 * TODO: check problem with ID on item. Is important use name and not ID to construct the name of custom field. (main case)
		 */
		self::$myd_fields['myd_order_details'] = [
			'id' => 'myd_order_details',
			'name' => __( 'Order Details', 'myd-delivery-pro' ),
			'screens' => 'mydelivery-orders',
			'wrapper' => 'wide',
			'fields' => [
				'myd_order_details' => [
					'type' => 'repeater',
					'label' => __( 'Order Items', 'myd-delivery-pro' ),
					'id' => 'myd_order_items',
					'name' => 'myd_order_items',
					'legacy' => 'order_items',
					'custom_class' => '',
					'fields' => [
							[
								'type' => 'image',
								'label' => __( 'Product Image', 'myd-delivery-pro' ),
								'id' => 'myd_order_product_image',
								'name' => 'product_image',
								'custom_class' => '',
								'required' => false,
							],
							[
							'type' => 'text',
							'label' => __( 'Product ID', 'myd-delivery-pro' ),
							'id' => 'myd_order_product_id',
							'name' => 'product_id',
							'custom_class' => 'myd-input-size-100',
							'readonly' => true,
						],
						[
							'type' => 'text',
							'label' => __( 'Product Name', 'myd-delivery-pro' ),
							'id' => 'myd_order_product_name',
							'name' => 'product_name',
							'legacy' => 'order_product',
							'custom_class' => 'myd-input-size-100',
						],
						[
							'type' => 'textarea',
							'label' => __( 'Product extras', 'myd-delivery-pro' ),
							'id' => 'myd-order-product-extras',
							'name' => 'product_extras',
							'legacy' => 'order_item_extra',
							'custom_class' => '',
						],
						[
							'type' => 'text',
							'label' => __( 'Product Unit Price', 'myd-delivery-pro' ),
							'id' => 'myd-order-product-price',
							'name' => 'product_price',
							'legacy' => 'order_item_price',
							'custom_class' => '',
						],
						[
							'type' => 'text',
							'label' => __( 'Product Total', 'myd-delivery-pro' ),
							'id' => 'myd-order-product-total',
							'name' => 'product_total',
							'custom_class' => '',
							'readonly' => true,
						],
						[
							'type' => 'textarea',
							'label' => __( 'Item note', 'myd-delivery-pro' ),
							'id' => 'myd-order-product-note',
							'name' => 'product_note',
							'legacy' => 'order_item_note',
							'custom_class' => '',
						],
					],
				],
			],
		];

		/**
		 * Do action before insert custom fields
		 *
		 * @since 1.9.5
		 */
		\do_action( 'myd_before_insert_custom_fields', self::$myd_fields );
	}
}

// Adiciona endpoint AJAX para gerar código único de produto
add_action('wp_ajax_myd_generate_product_id', function() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Sem permissão']);
    }
    require_once __DIR__ . '/class-custom-fields.php';
    $fields = \MydPro\Includes\Custom_Fields\Register_Custom_Fields::get_registered_fields();
    $custom = new \MydPro\Includes\Custom_Fields\Myd_Custom_Fields($fields);
    $id = (new \ReflectionClass($custom))->getMethod('generate_unique_product_id');
    $id->setAccessible(true);
    $unique = $id->invoke($custom);
    wp_send_json_success(['id' => $unique]);
});
