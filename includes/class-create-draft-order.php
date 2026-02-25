<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Myd Create Order
 *
 */
class Create_Draft_Order {
	/**
	 * Request data
	 *
	 */
	protected array $request_data;

	/**
	 * Id
	 *
	 */
	public int $id;

	/**
	 * Type
	 *
	 * @var string
	 */
	public string $type;

	/**
	 * Subtotal
	 *
	 * @var float
	 */
	public float $subtotal;

	/**
	 * Total
	 *
	 * @var float
	 */
	public float $total;

	/**
	 * Cart
	 *
	 * @var Cart
	 */
	public Cart $cart;

	/**
	 * Payment
	 *
	 * @var array
	 */
	public array $payment;

	/**
	 * Customer
	 *
	 * @var array
	 */
	public array $customer;

	/**
	 * Shipping
	 *
	 * @var array
	 */
	public array $shipping;

	/**
	 * Coupon
	 *
	 * @var ?Coupon
	 */
	public ?Coupon $coupon;

	/**
	 * Discount amount applied
	 *
	 * @var float
	 */
	public float $discount_amount = 0;

	/**
	 * Construct class.
	 */
	public function __construct( array $request_data ) {
		$this->request_data = $request_data;
		$this->subtotal = $request_data['subtotal'] ?? 0;
		$this->total = $request_data['total'] ?? 0;
		$this->payment = $request_data['payment'] ?? array();
	}

	/**
	 * Set type
	 *
	 * @param string $type
	 * @return void
	 */
	public function set_type( string $type ) : void {
		$this->type = $type;
	}

	/**
	 * Set cart
	 *
	 * @param Cart $cart
	 * @return void
	 */
	public function set_cart( Cart $cart ) : void {
		$this->cart = $cart;
	}

	/**
	 * Set customer
	 *
	 * @param [type] $customer
	 * @return void
	 */
	public function set_customer( $customer ) : void {
		$this->customer = $customer;
	}

	/**
	 * Set shipping
	 *
	 * @param [type] $shipping
	 * @return void
	 */
	public function set_shipping( $shipping ) : void {
		$this->shipping = $shipping;
	}

	/**
	 * Set coupon
	 *
	 * @param Coupon|null $coupon
	 * @return void
	 */
	public function set_coupon( ?Coupon $coupon ) : void {
		$this->coupon = $coupon;
	}
	/**
	 * Create Order
	 *
	 * @return void
	 */
	public function create() : void {
		$data = array(
			'post_title' => '#',
			'post_status' => 'draft',
			'post_type' => 'mydelivery-orders',
		);

		$this->id = wp_insert_post( $data );
		wp_update_post(
			array(
				'ID' => $this->id,
				'post_title' => $this->id,
			)
		);

		// Salva o canal de venda como SYS (site)
		\update_post_meta( $this->id, 'order_channel', 'SYS' );

		// Schedule automatic deletion of this draft after 24 hours
		if ( function_exists( 'wp_schedule_single_event' ) ) {
			$timestamp = time() + 86400; // 24 hours
			// Avoid duplicate scheduled events for same post
			if ( ! wp_next_scheduled( 'myd_delete_draft_order', array( $this->id ) ) ) {
				wp_schedule_single_event( $timestamp, 'myd_delete_draft_order', array( $this->id ) );
			}
		}
	}

	/**
	 * Static handler for scheduled deletion of drafts.
	 * Deletes the post if it still exists and is in draft status.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public static function delete_scheduled_draft( int $post_id ) : void {
		if ( empty( $post_id ) ) { return; }
		$post = get_post( $post_id );
		if ( ! $post ) { return; }
		if ( $post->post_type !== 'mydelivery-orders' ) { return; }
		// Force permanent deletion regardless of current status
		wp_delete_post( $post_id, true );
	}

	/**
	 * Get formated extras - legacy and temp. function to be removed when this class is formated
	 *
	 * @param array $extras
	 * @return string
	 */
	private function get_formated_extras( array $extras ) : string {
		if ( empty( $extras['groups'] ) ) {
			return '';
		}

		$formated_extras = array();
		foreach ( $extras['groups'] as $group ) {
			$selected_items = array();
			foreach ( $group['items'] as $item ) {
				if ( (int) $item['quantity'] > 0 ) {
					$selected_items[] = $item['name'] . ( $item['quantity'] > 1 ? ' (' . $item['quantity'] . 'x)' : '' );
				}
			}
			if ( ! empty( $selected_items ) ) {
				$formated_extras[] = $group['group'] . ':' . PHP_EOL . implode( PHP_EOL, $selected_items ) . PHP_EOL;
			}
		}

		return implode( PHP_EOL, $formated_extras );
	}

	private function trace( string $event, array $context = array() ) : void {
		$payload = array_merge(
			array(
				'event' => $event,
				'order_id' => isset( $this->id ) ? (int) $this->id : 0,
				'ts' => microtime( true ),
			),
			$context
		);
		\error_log( 'MYD_SAVE_TRACE ' . \wp_json_encode( $payload ) );
	}

	/**
	 * Update order
	 *
	 * @return void
	 */
	public function save() : void {
		$this->trace( 'save_start', array( 'cart_items_count' => isset( $this->cart->items ) && is_array( $this->cart->items ) ? count( $this->cart->items ) : 0 ) );
		$this->calculate_total();

		// Descontos
		// Manter $this->discount_amount como desconto de CUPOM apenas (já calculado em calculate_total()).
		$coupon_discount = floatval( $this->discount_amount );
		$fidelity_discount = 0.0;
		// Se o frontend enviou um valor de cupom, use para persistência (sem re-subtrair do total)
		if ( isset( $this->request_data['coupon_discount'] ) ) {
			$clean = preg_replace( '/[^0-9\.,-]/', '', (string) $this->request_data['coupon_discount'] );
			$clean = str_replace( ',', '.', $clean );
			$req_coupon = floatval( $clean );
			if ( $req_coupon > 0 ) $coupon_discount = $req_coupon;
		} elseif ( isset( $this->request_data['order_coupon_discount'] ) ) {
			$clean = preg_replace( '/[^0-9\.,-]/', '', (string) $this->request_data['order_coupon_discount'] );
			$clean = str_replace( ',', '.', $clean );
			$req_coupon = floatval( $clean );
			if ( $req_coupon > 0 ) $coupon_discount = $req_coupon;
		}
		// Fidelidade (aplicada somente ao total)
		if ( isset( $this->request_data['fidelity_discount'] ) ) {
			$clean = preg_replace( '/[^0-9\.,-]/', '', (string) $this->request_data['fidelity_discount'] );
			$clean = str_replace( ',', '.', $clean );
			$fidelity_discount = floatval( $clean );
		} elseif ( isset( $this->request_data['order_fidelity_discount'] ) ) {
			$clean = preg_replace( '/[^0-9\.,-]/', '', (string) $this->request_data['order_fidelity_discount'] );
			$clean = str_replace( ',', '.', $clean );
			$fidelity_discount = floatval( $clean );
		}
		if ( $fidelity_discount > 0 ) {
			$this->total = max( 0, $this->total - $fidelity_discount );
		}
		// Garanta que a propriedade permaneça representando apenas o cupom
		$this->discount_amount = $coupon_discount;

		$order_items = array();
		foreach ( $this->cart->items as $item ) {
				// Get product image url (store as product_image) so it's available in the order post meta
				$image_id = \get_post_meta( $item['id'], 'product_image', true );
				// store attachment ID so admin can render the image field
				// Build product_extras string from extras groups/items
				$filtered_extras = $this->get_filtered_extras( $item['extras'] ?? array() );
				$product_extras_lines = array();
				if ( ! empty( $filtered_extras['groups'] ) ) {
					foreach ( $filtered_extras['groups'] as $extra_group ) {
						$group_name  = isset( $extra_group['group'] ) ? $extra_group['group'] : '';
						$item_lines  = array();
						foreach ( $extra_group['items'] as $extra_item ) {
						$qty   = isset( $extra_item['quantity'] ) ? intval( $extra_item['quantity'] ) : 1;
						$name  = isset( $extra_item['name'] ) ? $extra_item['name'] : '';
						$price = isset( $extra_item['price'] ) ? floatval( $extra_item['price'] ) : 0;
						$line  = ( $qty > 1 ? $qty . 'x ' : '' ) . $name;
						$item_lines[] = $line;
					}
						$product_extras_lines[] = $group_name . ':' . PHP_EOL . implode( PHP_EOL, $item_lines );
					}
				}
				$product_extras_str = implode( PHP_EOL . PHP_EOL, $product_extras_lines );

				$order_items[] = array(
				'product_image' => intval( $image_id ),
				'product_id' => \get_post_meta( $item['id'], 'product_id', true ),
				'product_name' => '' . $item['quantity'] . ' x ' . \get_the_title( $item['id'] ),
				'product_extras' => $product_extras_str,
				'product_price' => Myd_Store_Formatting::format_price( $item['price'] ?? 0 ),
				'product_total' => Myd_Store_Formatting::format_price( $item['total'] ?? 0 ),
				'product_note' => $item['note'],
				// TODO: create a function to custom fields show the data based on array keys. !IMPORTANT
				'id' => $item['id'] ?? 0,
				'name' => \get_post_meta( $item['id'], 'product_name', true ),
				'quantity' => $item['quantity'] ?? 0,
				'extras' => $filtered_extras,
				'price' => $item['price'] ?? 0,
				'total' => $item['total'] ?? 0,
				'note' => $item['note'] ?? '',
			);
		}

		$new_hash = md5( \wp_json_encode( $order_items ) );
		$prev_hash = \get_post_meta( $this->id, 'myd_order_items_hash', true );
		$prev_last = intval( \get_post_meta( $this->id, 'order_last_saved_at', true ) );
		$now_ts = time();
		$rapid_repeat = ( $prev_last > 0 ) && ( ( $now_ts - $prev_last ) < 3 );
		$should_update_items = ( $prev_hash !== $new_hash ) || ! $rapid_repeat;
		$this->trace( 'save_items_decision', array( 'prev_hash' => (string) $prev_hash, 'new_hash' => (string) $new_hash, 'rapid_repeat' => $rapid_repeat, 'should_update' => $should_update_items ) );
		if ( $should_update_items ) {
			\update_post_meta( $this->id, 'myd_order_items', $order_items );
			\update_post_meta( $this->id, 'myd_order_items_hash', $new_hash );
			\update_post_meta( $this->id, 'order_last_saved_at', $now_ts );
			$this->trace( 'items_persisted', array( 'count' => count( $order_items ) ) );
		} else {
			$this->trace( 'items_skipped', array() );
		}
		$this->add_order_note( __( 'Order status changed to: started', 'myd-delivery-pro' ), );

		\update_post_meta( $this->id, 'order_status', 'started' );
		\update_post_meta( $this->id, 'order_date', current_time( 'd-m-Y H:i' ) );

		// Calculate estimated delivery time: order time + average preparation time + average delivery time
		$order_datetime = current_time( 'Y-m-d H:i:s' ); // Get current datetime in MySQL format
		$avg_prep_time = (int) get_option( 'myd-average-preparation-time', 30 ); // in minutes
		$avg_delivery_time_str = get_option( 'fdm-estimate-time-delivery', '30' ); // e.g., "30 min" or "30"
		
		// Parse delivery time - extract numeric value
		preg_match( '/(\d+)/', $avg_delivery_time_str, $matches );
		$avg_delivery_time = isset( $matches[1] ) ? (int) $matches[1] : 30; // default to 30 minutes
		
		$total_minutes = $avg_prep_time + $avg_delivery_time;
		
		// Add minutes to current time
		$estimated_datetime = date( 'Y-m-d H:i:s', strtotime( $order_datetime . " + {$total_minutes} minutes" ) );
		\update_post_meta( $this->id, 'order_estimated_delivery', $estimated_datetime );

		\update_post_meta( $this->id, 'order_customer_name', sanitize_text_field( $this->customer['name'] ?? '' ) );
		\update_post_meta( $this->id, 'customer_phone', sanitize_text_field( $this->customer['phone'] ?? '' ) );

		// Persist linked customer id if provided or fallback to current logged user
		$provided_customer_id = null;
		if ( ! empty( $this->customer['myd_customer_id'] ) ) {
			$provided_customer_id = (int) $this->customer['myd_customer_id'];
		} elseif ( ! empty( $this->customer['id'] ) ) {
			$provided_customer_id = (int) $this->customer['id'];
		} elseif ( ! empty( $this->request_data['myd_customer_id'] ) ) {
			$provided_customer_id = (int) $this->request_data['myd_customer_id'];
		}
		if ( empty( $provided_customer_id ) && function_exists( '\is_user_logged_in' ) && \is_user_logged_in() ) {
			$provided_customer_id = (int) \get_current_user_id();
		}
		if ( ! empty( $provided_customer_id ) ) {
			\update_post_meta( $this->id, 'myd_customer_id', $provided_customer_id );
			// If the user has a confirmation code, persist it now to avoid random fallback
			$user_code = \get_user_meta( $provided_customer_id, 'myd_delivery_confirm_code', true );
			if ( ! empty( $user_code ) ) {
				// ensure stored as 4-digit string
				$code = str_pad( (string) $user_code, 4, '0', STR_PAD_LEFT );
				\update_post_meta( $this->id, 'myd_order_confirmation_code', $code );
				\update_post_meta( $this->id, 'order_confirmation_code', $code );
			}
		}
		\update_post_meta( $this->id, 'order_address', sanitize_text_field( $this->customer['address']['street'] ?? '' ) );
		\update_post_meta( $this->id, 'order_address_number', sanitize_text_field( $this->customer['address']['number'] ?? '' ) );
		\update_post_meta( $this->id, 'order_address_comp', sanitize_text_field( $this->customer['address']['complement'] ?? '' ) );
		// New: save reference point (shown when number is missing or for delivery reference)
		\update_post_meta( $this->id, 'order_address_reference', sanitize_text_field( $this->customer['address']['reference'] ?? '' ) );
		$neighborhood = sanitize_text_field( $this->customer['address']['neighborhood'] ?? '' );
		$real_neighborhood = sanitize_text_field( $this->customer['address']['real_neighborhood'] ?? '' );
		\update_post_meta( $this->id, 'order_neighborhood', $neighborhood );
		// Only save real_neighborhood when the user manually changed the bairro
		\update_post_meta( $this->id, 'order_real_neighborhood', ( $real_neighborhood !== '' && $real_neighborhood !== $neighborhood ) ? $real_neighborhood : '' );
		\update_post_meta( $this->id, 'order_zipcode', sanitize_text_field( $this->customer['address']['zipcode'] ?? '' ) );
		\update_post_meta( $this->id, 'order_ship_method', sanitize_text_field( $this->type ?? '' ) );
		\update_post_meta( $this->id, 'order_delivery_price', sanitize_text_field( Myd_Store_Formatting::format_price( $this->shipping['price'] ?? '' ) ) );
		\update_post_meta( $this->id, 'order_coupon', sanitize_text_field( $this->coupon->code ?? '' ) );
		\update_post_meta( $this->id, 'order_table', sanitize_text_field( $this->shipping['table'] ?? '' ) );
		\update_post_meta( $this->id, 'order_payment_status', 'waiting' );
		\update_post_meta( $this->id, 'order_coupon_discount', sanitize_text_field( Myd_Store_Formatting::format_price( $coupon_discount ) ) );
		\update_post_meta( $this->id, 'order_fidelity_discount', sanitize_text_field( Myd_Store_Formatting::format_price( $fidelity_discount ) ) );
		\update_post_meta( $this->id, 'order_subtotal', sanitize_text_field( Myd_Store_Formatting::format_price( $this->subtotal ) ) );
		\update_post_meta( $this->id, 'order_total', sanitize_text_field( Myd_Store_Formatting::format_price( $this->total ) ) );
		$this->trace( 'save_end', array() );
	}

	/**
	 * Calculate order total
	 *
	 * @return void
	 */
	public function calculate_total() : void {
		$this->subtotal = $this->cart->total;
		$this->total = $this->cart->total + floatval($this->shipping['price']);
		if ( $this->coupon ) {
			$this->calculate_discount();
		}
	}

	/**
	 * Calculate discount
	 *
	 * @return void
	 */
	private function calculate_discount() : void {
		$original_total = $this->cart->total + $this->shipping['price'];
		
		if ( $this->coupon->type === 'discount-total' ) {
			if ( $this->coupon->discount_format === 'amount' ) {
				$this->discount_amount = $this->coupon->amount;
				$this->total = $this->total - $this->coupon->amount;
			}

			if ( $this->coupon->discount_format === 'percent' ) {
				$this->discount_amount = ( $this->coupon->amount * $this->total ) / 100;
				$this->total = $this->total - $this->discount_amount;
			}
		}

		if ( $this->coupon->type === 'discount-delivery' ) {
			if ( $this->coupon->discount_format === 'amount' ) {
				$this->discount_amount = min( $this->coupon->amount, $this->shipping['price'] );
				$this->total = $this->cart->total + ( $this->shipping['price'] - $this->discount_amount );
			}

			if ( $this->coupon->discount_format === 'percent' ) {
				$this->discount_amount = ( $this->coupon->amount * $this->shipping['price'] ) / 100;
				$this->total = $this->cart->total + ( $this->shipping['price'] - $this->discount_amount );
			}
		}
	}

	/**
	 * Add order note
	 */
	public function add_order_note( string $note, string $type = 'success' ) : void {
		$order_note = \get_post_meta( $this->id, 'order_notes', true );
		$order_note = is_array( $order_note ) ? $order_note : array();
		$order_note[] = array(
			'type' => \esc_html( $type ),
			'note' => \esc_html( $note ),
			'date' => wp_date( get_option( 'date_format' ) . ' - ' . get_option( 'time_format' ) ),
		);

		\update_post_meta( $this->id, 'order_notes', $order_note );
	}

	/**
	 * Undocumented function
	 *
	 */
	public function get_total_summary_template() {
		ob_start();
		require_once MYD_PLUGIN_PATH . 'templates/cart/cart-pricing-summary.php';
		return ob_get_clean();
	}

	/**
	 * Get filtered extras with only selected items (quantity > 0)
	 *
	 * @param array $extras
	 * @return array
	 */
	public function get_filtered_extras( array $extras ) : array {
		if ( empty( $extras['groups'] ) ) {
			return array();
		}

		$filtered_extras = array( 'groups' => array() );
		foreach ( $extras['groups'] as $group ) {
			$selected_items = array();
			foreach ( $group['items'] as $item ) {
				if ( (int) $item['quantity'] > 0 ) {
					$selected_items[] = $item;
				}
			}
			if ( ! empty( $selected_items ) ) {
				$filtered_group = $group;
				$filtered_group['items'] = $selected_items;
				$filtered_extras['groups'][] = $filtered_group;
			}
		}

		return $filtered_extras;
	}
}
