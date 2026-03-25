<?php
/**
 * NOVO
 */
namespace MydPro\Includes\Custom_Fields;

use MydPro\Includes\Custom_Fields\Label;
use MydPro\Includes\Legacy\Legacy_Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Myd_Custom_Fiedls
 *
 * Implement custom fields to plugin.
 *
 * TODO: implement field->before and field->after para fazer vários tipos de inputs (table e outros).
 *
 * @since 1.9.5
 */
class Myd_Custom_Fields {
	/**
	 * Array with all data to create meta boxes and custom fields
	 */
	protected $fields = [];

	/**
	 * List all custom fields name
	 */
	protected $list_fields = [];

	/**
	 * List all screens used
	 */
	protected $screens = [];

	/**
	 * Construct class
	 *
	 * @since 1.9.5
	 * @param $fields
	 */
	public function __construct( array $fields ) {
		$this->fields = $fields;
		$this->list_fields = $this->get_list_fields();
		$this->screens = array_unique( array_column( $this->fields, 'screens' ) );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_fields' ], 10, 2 );
		add_action( 'admin_footer', [ $this, 'add_admin_js_validation' ] );
	}

	/**
	 * Add fields
	 *
	 * Add custom meta boxes and fileds.
	 *
	 * @since 1.9.5
	 */
	public function add_meta_box() {
		if( ! empty( $this->fields ) ) {
			foreach ( $this->fields as $arg ) {
				$context = isset( $arg['context'] ) ? $arg['context'] : 'normal';
				$priority = isset( $arg['priority'] ) ? $arg['priority'] : 'high';

				add_meta_box(
					$arg['id'],
					$arg['name'],
					array( $this, 'output_fields' ),
					$arg['screens'],
					$context,
					$priority
				);
			}
		}
	}

	/**
	 * Admin JS Validation
	 * Prevents original price from being equal to or less than promotional price
	 */
	public function add_admin_js_validation() {
		global $post_type;
		if ( $post_type !== 'mydelivery-produtos' ) {
			return;
		}
		?>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var form = document.getElementById('post');
			if (!form) return;
			
			form.addEventListener('submit', function(e) {
				var originalPriceInput = document.getElementById('myd_product_original_price');
				var promoPriceInput = document.getElementById('myd_product_price');
				
				if (originalPriceInput && promoPriceInput) {
					var originalPrice = parseFloat(originalPriceInput.value);
					var promoPrice = parseFloat(promoPriceInput.value);
					
					// Só valida se os dois forem preenchidos numericamente
					if (!isNaN(originalPrice) && !isNaN(promoPrice)) {
						if (originalPrice <= promoPrice) {
							e.preventDefault();
							alert('O Preço Real (valor sem desconto) deve ser estritamente maior que o Preço atual com desconto. Verifique os valores.');
							originalPriceInput.focus();
							return false;
						}
					}
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Save field
	 *
	 * Action to check and save filed after $_POST.
	 *
	 * @since 1.9.5
	 */
	public function save_fields( int $post_id, $post ) {
		/**
		 * Check if is a valid nonce
		 */
		if ( ! isset( $_POST['myd_inner_meta_box_nonce'] ) ) {
			return;
		}

		$nonce = $_POST['myd_inner_meta_box_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'myd_inner_meta_box' ) ) {
			return;
		}

		/**
		 * Do not save if is auto save action
		 */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		/**
		 * Check user permission
		 */
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		/**
		 * Check is current screens is used
		 */
		if ( ! in_array( $post->post_type, $this->screens ) ) {
			return;
		}

		/**
		 * Generate unique product ID for new products
		 */
		if ( $post->post_type === 'mydelivery-produtos' && ( ! isset( $_POST['product_id'] ) || empty( $_POST['product_id'] ) ) ) {
			$unique_id = $this->generate_unique_product_id();
			$_POST['product_id'] = $unique_id;
		}

		/**
		 * Check $_POST exist and update post meta
		 * invert to make foreach on $_POST and verify for scecific list_fields or some pattern to try get repeater values
		 */
		foreach ( $this->list_fields as $field_name ) {
			if ( array_key_exists( $field_name, $_POST ) ) {
				$value = wp_unslash( $_POST[ $field_name ] );
				if ( ! is_array( $value ) ) {
					$value = sanitize_text_field( $value );
				} else {
					foreach ( $value as $key => $item_value ) {
						if ( is_array( $item_value ) ) {
							$it = new \RecursiveIteratorIterator( new \RecursiveArrayIterator( $item_value ) );
							$filtered_values = array();
							foreach ( $it as $v ) {
								if ( ! empty( $v ) ) {
									$filtered_values[] = $v;
								}
							}
							if ( empty( $filtered_values ) && isset( $value[ $key ] ) ) {
								unset( $value[ $key ] );
							}
						} else {
							if ( ! isset( $value[ $key ] ) || trim( (string) $item_value ) === '' ) {
								unset( $value[ $key ] );
							}
						}
					}
				}
				// Validação: impede salvar se product_id já existe em outro produto
				if ($field_name === 'product_id' && !empty($value)) {
					global $post;
					$args = [
						'post_type' => 'mydelivery-produtos',
						'meta_key' => 'product_id',
						'meta_value' => $value,
						'posts_per_page' => 1,
						'fields' => 'ids',
						'exclude' => [$post_id],
					];
					$exists = get_posts($args);
					if (!empty($exists)) {
						// Exibe alertbox e impede salvar via JS
						echo '<script>alert("Já existe um produto com este código. Gere outro código.");window.history.back();</script>';
						exit;
					}
				}

				// Preservar a chave 'extras' em pedidos que são editados pelo wp-admin
				// O metabox não submete o array complexo 'extras', então nós recuperamos do banco de dados antes da sobrescrita.
				if ( $field_name === 'myd_order_items' && is_array( $value ) ) {
					$old_value = get_post_meta( $post_id, 'myd_order_items', true );
					if ( is_string( $old_value ) && ! empty( $old_value ) ) {
						$_decoded = json_decode( $old_value, true );
						if ( is_array( $_decoded ) ) $old_value = $_decoded;
					}

					if ( is_array( $old_value ) && ! empty( $old_value ) ) {
						foreach ( $value as $index => &$new_item ) {
							// Se o item antigo existir no mesmo índice e tiver a chave 'extras', nós copiamos ela de volta
							if ( isset( $old_value[ $index ] ) && is_array( $old_value[ $index ] ) ) {
								if ( isset( $old_value[ $index ]['extras'] ) ) {
									$new_item['extras'] = $old_value[ $index ]['extras'];
								}
							}
						}
						unset($new_item);
					}
				}

				update_post_meta( $post_id, $field_name, $value );
			}
		}
	}

	/**
	 * Template field
	 *
	 * Implement template filed to custom post.
	 *
	 * @since 1.9.5
	 */
	public function output_fields( $post, $metabox ) {
		$fields = $this->fields[ $metabox['id'] ]['fields'];
		/**
		 * Render inputs by type
		 */
		$rendered_fields = array();
		$skip_fields     = array();

		// Mapeamento de campos que devem ser exibidos "ao lado" de outros
		// Mantém vazio para exibir campos em linhas separadas (cupom acima do desconto)
		$group_next = array();

		foreach ( $fields as $field ) {
			if ( in_array( $field['name'], $skip_fields ) ) {
				continue;
			}

			$rendered = $this->render_inputs( $field, $post->ID );

			// Se este campo deve ter outro ao lado
			if ( isset( $group_next[ $field['name'] ] ) ) {
				$next_field_name = $group_next[ $field['name'] ];
				// Procura o próximo campo na lista original para renderizá-lo aqui
				foreach ( $fields as $f ) {
					if ( $f['name'] === $next_field_name ) {
						$next_rendered = $this->render_inputs( $f, $post->ID );
						
						// Estilização inline simples para manter um ao lado do outro
						$rendered['input'] = '<div style="display:flex; align-items:center; gap:15px;">' . 
							'<div>' . $rendered['input'] . '</div>' . 
							'<div style="display:flex; align-items:center; gap:5px;">' . 
								'<span style="font-weight:bold;">' . $next_rendered['label'] . ':</span> ' . 
								'<span>' . $next_rendered['input'] . '</span>' . 
							'</div>' . 
						'</div>';

						$skip_fields[] = $next_field_name;
						break;
					}
				}
			}

			$rendered_fields[] = $rendered;
		}

		/**
		 * Add nonce to security check later.
		 */
		wp_nonce_field( 'myd_inner_meta_box', 'myd_inner_meta_box_nonce' );

		$metabox_wrapper = $this->fields[ $metabox['id'] ]['wrapper'] ?? '';
		if ( $metabox_wrapper === 'wide' ) {
			echo $rendered_fields[0]['input'];
			return;
		}

		?>
		<table class="form-table">
			<tbody>
				<?php foreach ( $rendered_fields as $field ) : ?>
					<tr>
						<th scope="row"><?php echo $field['label']; ?></th>
						<td><?php echo $field['input']; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render inputs
	 *
	 * Render inputs by type
	 *
	 * @since 1.9.5
	 * @return array
	 */
	public function render_inputs( array $args, int $post_id ) {
		$field = $args;
		$storaged_value = $value = get_post_meta( $post_id, $field['name'], true );

		// Garantia: se estivermos renderizando o campo de desconto de fidelidade,
		// force o preenchimento a partir do postmeta `order_fidelity_discount` quando existir.
		if ( $field['name'] === 'order_fidelity_discount' ) {
			$discount_meta = get_post_meta( $post_id, 'order_fidelity_discount', true );
			if ( ! empty( $discount_meta ) ) {
				$value = $discount_meta;
			}
		}
		if ( empty( $storaged_value ) && isset( $field['value'] ) && ! empty( $field['value'] ) ) {
			$value = $field['value'];
		} else {
			$value = $storaged_value;
		}
		// Se for o campo order_channel, renderiza como select fixo
		if ($field['name'] === 'order_channel') {
			$field['type'] = 'select';
			$field['select_options'] = [
				'SYS' => 'SYS',
				'WPP' => 'WPP',
				'IFD' => 'IFD',
			];
			// Valor padrão SYS se não houver
			if (empty($value)) {
				$value = 'SYS';
			}
		}
		switch ( $field['type'] ) {
			case 'text':
				$input = $this->render_input_text( $field, $post_id, $value );
				break;
			case 'number':
				$input = $this->render_input_number( $field, $post_id, $value );
				break;
			case 'select':
				$input = $this->render_input_select( $field, $post_id, $value );
				break;
			case 'textarea':
				$input = $this->render_input_textarea( $field, $post_id, $value );
				break;
			case 'image':
				$input = $this->render_input_image( $field, $post_id, $value );
				break;
			case 'repeater':
				$input = $this->render_input_repeater( $field, $post_id, $value );
				break;
			case 'checkbox':
				$input = $this->render_input_checkbox( $field, $post_id, $value );
				break;
			case 'checkbox_group':
				$input = $this->render_input_checkbox_group( $field, $post_id, $value );
				break;
			case 'radio_group':
				$input = $this->render_input_radio_group( $field, $post_id, $value );
				break;
			case 'datetime-local':
				$input = $this->render_input_datetime_local( $field, $post_id, $value );
				break;
			case 'order-note':
				$input = $this->render_order_note( $field, $post_id, $value );
				break;
			case 'linked_extras':
				$input = $this->render_input_linked_extras( $field, $post_id, $value );
				break;
		}

		$label = new Label( $field );
		$rendered_fields = [
			'label' => $label->output(),
			'input' => $input
		];

		return $rendered_fields;
	}

	/**
	 * Build data-attr.
	 * Move it to abstract class and reuse it after.
	 */
	public function build_data_attr( array $data_attr ) {
		if ( ! is_array( $data_attr ) || empty( $data_attr ) ) {
			return '';
		}

		$output_data_attr = array();
		foreach ( $data_attr as $data_key => $data_value ) {
			$output_data_attr[] = $data_key . '="' . $data_value . '"';
		}

		return implode( $output_data_attr );
	}

	/**
	 * Render input type Text
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_text( array $args, int $post_id, string $value = '' ) {
		// Ensure scalar value
			$val = is_array( $value ) ? ( $value[0] ?? '' ) : $value;
			if ( empty( $val ) && isset( $args['default_value'] ) ) {
				$val = $args['default_value'];
			}

		$required = isset( $args['required'] ) && $args['required'] === true ? 'required' : '';
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] : '';
		$data_attr = isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : '';
		// Torna o campo order_channel readonly sempre
		if ( $args['name'] === 'order_channel' ) {
			$readonly = 'readonly';
		} else {
			$readonly = isset( $args['readonly'] ) && $args['readonly'] === true ? 'readonly' : '';
		}
		$description = isset( $args['description'] ) ? '<p class="description">' . esc_html( $args['description'] ) . '</p>' : '';

		// Adiciona botão de gerar ao lado do campo myd_product_id
		$input = sprintf(
			'<input name="%s" type="text" id="%s" value="%s" class="%s" %s %s %s style="width:140px;">',
			esc_attr( $args['name'] ),
			esc_attr( $args['id'] ),
			esc_attr( $val ),
			esc_attr( $class ),
			$required,
			$readonly,
			$data_attr
		);
		if ( $args['id'] === 'myd_product_id' ) {
			$input .= ' <button type="button" class="button" id="myd-generate-product-id" style="margin-left:5px;">Gerar</button>';
			$input .= "<script>\n            document.addEventListener('DOMContentLoaded', function() {\n                var btn = document.getElementById('myd-generate-product-id');\n                if(btn) {\n                    btn.addEventListener('click', function() {\n                        btn.disabled = true;\n                        btn.textContent = 'Gerando...';\n                        fetch(ajaxurl + '?action=myd_generate_product_id', { credentials: 'same-origin' })\n                            .then(r => r.json())\n                            .then(data => {\n                                if(data.success && data.data && data.data.id) {\n                                    document.getElementById('myd_product_id').value = data.data.id;\n                                } else {\n                                    alert('Erro ao gerar código.');\n                                }\n                                btn.disabled = false;\n                                btn.textContent = 'Gerar';\n                            })\n                            .catch(() => {\n                                alert('Erro ao gerar código.');\n                                btn.disabled = false;\n                                btn.textContent = 'Gerar';\n                            });\n                    });\n                }\n            });\n            </script>";
		}
		return $input . $description;
	}

	/**
	 * Render input type Number
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_number( array $args, int $post_id, $value = '' ) {
		if (
			empty( $value ) &&
			isset( $args['default_value'] ) &&
			! empty( $args['default_value'] )
		) {
			$value = $args['default_value'];
		}

		$required = isset( $args['required'] ) && $args['required'] === true ? 'required' : '';
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] : '';
		$min = isset( $args['min'] ) && $args['min'] !== '' ? 'min="' . esc_attr( $args['min'] ) . '"' : '';
		$max = isset( $args['max'] ) && $args['max'] !== '' ? 'max="' . esc_attr( $args['max'] ) . '"' : '';

		$description = isset( $args['description'] ) ? '<p class="description">' . esc_html( $args['description'] ) . '</p>' : '';

		return sprintf(
			'<input name="%s" type="number" step="any" id="%s" value="%s" class="%s" %s %s %s %s>%s',
			esc_attr( $args['name'] ),
			esc_attr( $args['id'] ),
			esc_attr( $value ),
			esc_attr( $class ),
			$required,
			$min,
			$max,
			isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : '',
			$description
		);
	}

	/**
	 * Render input type Textarea
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_textarea( array $args, int $post_id, string $value = '' ) {
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] . ' large-text' : 'large-text';

		return sprintf(
			'<textarea name="%s" id="%s" cols="50" rows="5" class="%s" %s %s>%s</textarea>',
			esc_attr( $args['name'] ),
			esc_attr( $args['id'] ),
			esc_attr( $class ),
			isset( $args['required'] ) && $args['required'] === true ? 'required' : '',
			isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : '',
			esc_attr( $value )
		);
	}

	/**
	 * Render input type datetime-local
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_datetime_local( array $args, int $post_id, string $value = '' ) {
		if (
			empty( $value ) &&
			isset( $args['default_value'] ) &&
			! empty( $args['default_value'] )
		) {
			$value = $args['default_value'];
		}

		// Converter valor do banco para formato datetime-local se necessário
		if ( ! empty( $value ) && strtotime( $value ) ) {
			$value = date( 'Y-m-d\TH:i', strtotime( $value ) );
		}

		$required = isset( $args['required'] ) && $args['required'] === true ? 'required' : '';
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] : '';
		$data_attr = isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : '';
		$description = isset( $args['description'] ) ? '<p class="description">' . esc_html( $args['description'] ) . '</p>' : '';

		return sprintf(
			'<input name="%s" type="datetime-local" id="%s" value="%s" class="%s" %s %s>%s',
			esc_attr( $args['name'] ),
			esc_attr( $args['id'] ),
			esc_attr( $value ),
			esc_attr( $class ),
			$required,
			$data_attr,
			$description
		);
	}

	/**
	 * Render input type Order Note
	 *
	 * @since 1.9.5
	 */
	public function render_order_note( array $args, int $post_id, array $value = array() ) {
		$output = array();
		foreach ( $value as $note ) {
			$output[] = sprintf(
				'<div class="order-note order-note--%s"><span class="order-note__text">%s</span><span class="order-note__date">%s</span></div>',
				esc_attr( $note['type'] ?? '' ),
				esc_html( $note['note'] ?? '' ),
				esc_html( $note['date'] ?? '' ),
			);
		}

		return implode( $output );
	}

	/**
	 * Render input type Image
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_image( array $args, int $post_id, string $value = '' ) {
		$img_url = '';
		// Accept either attachment ID or direct URL stored previously
		if ( is_numeric( $value ) && intval( $value ) > 0 ) {
			$img_url = wp_get_attachment_image_url( intval( $value ), 'medium' );
		} elseif ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$img_url = esc_url( $value );
		}
		$hidden_image_class = ! empty( $img_url ) ? '' : 'myd-admin-hidden';

		if( current_user_can('upload_files') ) {
			wp_enqueue_media();
			wp_enqueue_script( 'myd-admin-cf-media-library' );
		}

		$image_preview = sprintf(
			'<div class="myd-custom-field-image-wrapper"><img class="myd-custom-field__image-preview %s" src="%s"></div>',
			esc_attr( $hidden_image_class ),
			esc_url( $img_url )
		);

		$image_input = sprintf(
			'<input type="hidden" id="myd-custom-field-image-id" name="%s" value="%s">',
			esc_attr( isset( $args['name'] ) ? $args['name'] : '' ),
			esc_attr( $value )
		);

		// Detect if this field is rendered inside a repeater by checking for array-style name
		$inside_repeater = false;
		if ( isset( $args['name'] ) && strpos( $args['name'], '[' ) !== false ) {
			$inside_repeater = true;
		}

		if ( $inside_repeater ) {
			// For repeater fields return only preview + hidden input (no buttons)
			return sprintf('%s %s', $image_preview, $image_input );
		}

		// Not in repeater: include Choose/Remove buttons (original behavior)
		return sprintf(
			'%s %s <button href="#" class="button button-primary" id="myd-cf-chose-media">%s</button> <button href="#" class="button" id="myd-cf-remove-media">%s</button>',
			$image_preview,
			$image_input,
			esc_html__( 'Choose', 'myd-delivery-pro' ),
			esc_html__( 'Remove', 'myd-delivery-pro' )
		);
	}

	/**
	 * Render input type Text
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_checkbox( array $args, int $post_id, string $value = '' ) {
		$required = isset( $args['required'] ) ? $args['required'] : '';
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] : '';

		return sprintf(
			'<input name="%1$s" type="hidden" data-id="%2$s" value="0" class="%3$s" %4$s %5$s>
			<input name="%1$s" type="checkbox" id="%2$s" value="1" class="%3$s" %4$s %5$s %6$s>',
			esc_attr( $args['name'] ),
			esc_attr( $args['id'] ),
			esc_attr( $class ),
			$required === true ? 'required' : '',
			isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : '',
			checked( $value, '1', false )
		);
	}

	/**
	 * Render input type Checkbox Group (multiple values)
	 *
	 * @param array $args
	 */
	public function render_input_checkbox_group( array $args, int $post_id, $value = '' ) {
		$values = is_array( $value ) ? $value : ( $value !== '' ? array( $value ) : array() );
		$options = isset( $args['select_options'] ) ? $args['select_options'] : array();
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] : '';
		$data_attr = isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : '';

		$wrapper_id = esc_attr( $args['id'] ) . '_select_checkbox_wrapper';
		$header_id = esc_attr( $args['id'] ) . '_select_checkbox_header';
		$options_id = esc_attr( $args['id'] ) . '_select_checkbox_options';
		$label_id = esc_attr( $args['id'] ) . '_select_checkbox_label';

		// Build options markup
		$parts = array();
		foreach ( $options as $opt_value => $opt_label ) {
			$checkbox_id = $args['id'] . '_' . preg_replace('/[^a-z0-9_\-]/i', '-', (string) $opt_value );
			$checked = in_array( (string) $opt_value, array_map('strval', $values), true ) ? 'checked' : '';
			$parts[] = sprintf(
				'<label style="display:block; margin:4px 0;"><input type="checkbox" name="%1$s[]" id="%2$s" value="%3$s" class="%4$s myd-select-checkbox-input" %5$s %6$s> %7$s</label>',
				esc_attr( $args['name'] ),
				esc_attr( $checkbox_id ),
				esc_attr( $opt_value ),
				esc_attr( $class ),
				$checked,
				$data_attr,
				esc_html( $opt_label )
			);
		}

		$select_placeholder = esc_html__('Selecione as categorias do item');

		$html = '';
		$html .= '<div class="myd-select-checkbox" id="' . $wrapper_id . '" style="position:relative; max-width:420px;">';
		$html .= '<div class="myd-select-checkbox__header" id="' . $header_id . '" tabindex="0" style="border:1px solid #ccc; padding:8px; background:#fff; cursor:pointer;">' . $select_placeholder . '</div>';
		$html .= '<div class="myd-select-checkbox__options" id="' . $options_id . '" style="display:none; position:absolute; z-index:9999; background:#fff; border:1px solid #ccc; padding:8px; max-height:240px; overflow:auto; width:100%; box-sizing:border-box;">' . implode($parts ) . '</div>';
		// selected label below
		$selected_text = '';
		if ( ! empty( $values ) ) {
			$selected_items = array();
			foreach ( $values as $v ) {
				if ( isset( $options[ $v ] ) ) {
					$selected_items[] = $options[ $v ];
				}
			}
			$selected_text = implode( ', ', $selected_items );
		}
		$items_id = $label_id . '_items';
		$html .= '<div class="myd-select-checkbox__selected" id="' . $label_id . '" style="margin-top:6px; font-style:italic; color:#333;">';
		$html .= '<span class="myd-select-checkbox__prefix" style="font-weight:600;">' . esc_html__( 'Categorias:', 'myd-delivery-pro' ) . '</span> ';
		$html .= '<span id="' . $items_id . '">' . esc_html( $selected_text ) . '</span>';
		$html .= '</div>';
		$html .= '</div>';

		// Inline script to handle toggle and update
		$script = "<script>(function(){var wrap=document.getElementById('" . $wrapper_id . "');if(!wrap)return;var header=document.getElementById('" . $header_id . "');var options=document.getElementById('" . $options_id . "');var items=document.getElementById('" . $items_id . "');header.addEventListener('click',function(e){options.style.display=options.style.display==='none'?'block':'none';});document.addEventListener('click',function(e){if(!wrap.contains(e.target)){options.style.display='none';}});var inputs=wrap.querySelectorAll('.myd-select-checkbox-input');function updateItems(){var selected=[];for(var i=0;i<inputs.length;i++){if(inputs[i].checked){var lab=inputs[i].parentNode.textContent.trim();selected.push(lab);}}items.textContent=selected.join(', ');}for(var i=0;i<inputs.length;i++){inputs[i].addEventListener('change',function(){updateItems();});}updateItems();})();</script>";

		return $html . $script;
	}

	/**
	 * Render input type Radio Group (single value)
	 *
	 * @param array $args
	 */
	public function render_input_radio_group( array $args, int $post_id, $value = '' ) {
		// stored value may be scalar
		$selected = is_array( $value ) ? ( $value[0] ?? '' ) : $value;
		$options = isset( $args['select_options'] ) ? $args['select_options'] : array();
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] : '';
		$data_attr = isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : '';

		$wrapper_id = esc_attr( $args['id'] ) . '_select_radio_wrapper';
		$header_id = esc_attr( $args['id'] ) . '_select_radio_header';
		$options_id = esc_attr( $args['id'] ) . '_select_radio_options';
		$label_id = esc_attr( $args['id'] ) . '_select_radio_label';
		$items_id = $label_id . '_item';

		$parts = array();
		foreach ( $options as $opt_value => $opt_label ) {
			$radio_id = $args['id'] . '_' . preg_replace('/[^a-z0-9_\-]/i', '-', (string) $opt_value );
			$checked = (string) $opt_value === (string) $selected ? 'checked' : '';
			$parts[] = sprintf(
				'<label style="display:inline-block; margin-right:12px; vertical-align:middle;"><input type="radio" name="%1$s" id="%2$s" value="%3$s" class="%4$s myd-select-radio-input" %5$s %6$s> %7$s</label>',
				esc_attr( $args['name'] ),
				esc_attr( $radio_id ),
				esc_attr( $opt_value ),
				esc_attr( $class ),
				$checked,
				$data_attr,
				esc_html( $opt_label )
			);
		}

		// Render radios always visible, side by side
		$html = '';
		$html .= '<div class="myd-radio-group" id="' . $wrapper_id . '">';
		$html .= '<span class="myd-select-radio__prefix" style="font-weight:600; margin-right:8px;">' . esc_html__( 'Selos:', 'myd-delivery-pro' ) . '</span>';
		$html .= '<div class="myd-select-radio__options" id="' . $options_id . '" style="display:inline-block;">' . implode( '', $parts ) . '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get repeater values from db (new implementation).
	 */
	public function build_repeater_object( $fields, $fields_value ) {
		if ( ! is_array( $fields_value ) || empty( $fields_value ) ) {
			return;
		}

		$builded_object = array();
		$size_of_array = count( $fields_value );
		$size_of_array = (int) $size_of_array - 1;

		for ( $limit = 0; $limit <= $size_of_array; $limit++ ) {
			foreach ( $fields as $field ) {
				if ( isset( $field['fields'] ) ) {
					$builded_object[ $limit ][ $field['name'] ] = $this->build_repeater_object( $field['fields'], $fields_value[ $limit ][ $field['name'] ] );
				} else {
					$builded_object[ $limit ][ $field['name'] ] = $field;
					$builded_object[ $limit ][ $field['name'] ]['value'] = $fields_value[ $limit ][ $field['name'] ] ?? '';
				}
			}
		}

		return $builded_object;
	}

	/**
	 * Render input type Repeater
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_repeater( array $args, int $post_id, $value = '' ) {
		$repeater_main_field = $args['name'];
		$repeater_legacy_main_field = $args['legacy'] ?? '';
		$repeater_main_field_value = get_post_meta( $post_id, $repeater_main_field, true );
		// Se o valor for uma string JSON (novo formato salvo como wp_json_encode), decodificar para array
		if ( is_string( $repeater_main_field_value ) && ! empty( $repeater_main_field_value ) ) {
			$decoded = json_decode( $repeater_main_field_value, true );
			if ( is_array( $decoded ) ) {
				$repeater_main_field_value = $decoded;
			}
		}
		$repeater_legacy_main_field_value = get_post_meta( $post_id, $repeater_legacy_main_field, true );
		$to_render = array();

		/**
		 * TODO: move to other method.
		 * Build array of value when the value is empty to maintain the compatibility with other builds/checks of repeater method.
		 */
		if ( empty( $repeater_main_field_value ) && empty( $repeater_legacy_main_field_value ) ) {
			$new_value = array();
			foreach ( $args['fields'] as $value ) {
				if ( $value['type'] !== 'repeater' ) {
					$new_value[ $value['name'] ] = '';
				} else {
					$internal_new_value =  array();
					foreach ( $value['fields'] as $internal_value ) {
						$internal_new_value[ $internal_value['name'] ] = '';
					}
					$new_value[ $value['name'] ][] = $internal_new_value;
				}
			}

			$value = array( $new_value );
		}

		$update_db = Legacy_Repeater::need_update_db( $repeater_legacy_main_field_value, $repeater_main_field_value );
		// Quando o campo já contém dados válidos (não-legacy), usar diretamente
		if ( ! $update_db && is_array( $repeater_main_field_value ) && ! empty( $repeater_main_field_value ) ) {
			$value = $repeater_main_field_value;
		}
		if ( $update_db ) {
			$value = Legacy_Repeater::update_repeater_database( $repeater_legacy_main_field_value, $args, $post_id );
		}

		$args['fields'] = $this->build_repeater_object( $args['fields'], $value );
		$to_render = $args['fields'];

		if ( empty( $to_render ) ) {
			return;
		}

		$item_id = $args['id'] ?? '';

		wp_enqueue_script( 'myd-admin-cf-repeater' );

		ob_start();

		?>
			<div class="myd-repeater-wrapper" id="<?php echo esc_attr( $item_id ); ?>">
				<?php foreach ( $to_render as $key => $arg ) : ?>
					<?php $this->repeater_template( $arg, $post_id, $item_id, $key ); ?>
				<?php endforeach; ?>

				<button href="#" class="button button-primary myd-repeater-add-extra" id="myd-repeater-add-extra" data-row="<?php echo esc_attr( $item_id ); ?>"><?php esc_html_e( 'Add product extra', 'myd-delivery-pro' ); ?></button>
			</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Template to render repeater
	 *
	 * @param array $fields
	 * @param int $post_id
	 * @return void
	 */
	public function repeater_template( $fields, $post_id, $item_id = '', $loop_key = null ) {
		?>
			<div class="myd-repeater-container myd-repeater-container--top-level">
				<div class="myd-repeater-container__remove">X</div>
				<?php foreach ( $fields as $key => $field ) : ?>
					<?php if ( isset( $field['type'] ) ) : ?>
						<?php
							$class = ! empty( $field['custom_class'] ) ? 'myd-repeater-row' . ' ' . $field['custom_class'] : 'myd-repeater-row';
							$field['custom_class'] = 'myd-repeater-input';

							$field['data'] = array(
								'data-main-index' => $loop_key,
								'data-name' => $item_id . '[{{main-index}}]' . '[' . $field['name'] . ']',
							);

							$field['name'] = $item_id . '[' . $loop_key . ']' . '[' . $field['name'] . ']';
						?>
						<div class="<?php echo esc_attr( $class ); ?>">
							<?php echo implode( $this->render_inputs( $field, $post_id ) ); ?>
						</div>
					<?php else : ?>
							<?php foreach ( $field as $internal_key => $internal_field ) : ?>
								<details class="myd-repeater-container myd-repeater-container--internal" data-index="<?php echo esc_attr( $key ); ?>">
									<summary class="myd-repeater-summary">
										<span class="myd-repeater-summary__title"><?php echo esc_html( $internal_field['extra_option_name']['value'] ); ?></span>
										<span class="myd-repeater-summary__action--remove" data-row="<?php echo esc_attr( $item_id ); ?>"><?php esc_html_e( 'remove', 'myd-delivery-pro' ); ?></span>
									</summary>

									<div class="myd-repeater-container">
										<?php foreach ( $internal_field as $internal_field2 ) : ?>
											<?php
												$class = ! empty( $internal_field2['custom_class'] ) ? 'myd-repeater-row' . ' ' . $internal_field2['custom_class'] : 'myd-repeater-row';
												$internal_field2['custom_class'] = 'myd-repeater-input';

												$internal_field2['data'] = array(
													'data-main-index' => $loop_key,
													'data-name' => $item_id . '[{{main-index}}]' . '[' . $key . ']' . '[' . $internal_field2['name'] . ']',
													'data-internal-index' => $internal_key,
												);

												$internal_field2['name'] = $item_id . '[' . $loop_key . ']' . '[' . $key . ']' . '[' . $internal_key . ']' . '[' . $internal_field2['name'] . ']';
											?>
											<div class="<?php echo esc_attr( $class ); ?>">
												<?php echo implode( $this->render_inputs( $internal_field2, $post_id ) ); ?>
											</div>
										<?php endforeach; ?>
									</div>
								</details>
							<?php endforeach; ?>

							<button href="#" class="button button-secondary myd-extra-option-button myd-repeater-add-option" id="myd-repeater-add-option"><?php _e( 'Add option', 'myd-delivery-pro' ); ?></button>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php
	}

	/**
	 * Render input type Select
	 *
	 * @since 1.9.5
	 * @param array $args
	 */
	public function render_input_select( array $args, int $post_id, string $value = '' ) {
		if (
			empty( $value ) &&
			isset( $args['default_value'] ) &&
			! empty( $args['default_value'] )
		) {
			$value = $args['default_value'];
		}


		// Se for order_channel, exibe nomes amigáveis
		if ($args['name'] === 'order_channel') {
			$friendly = [
				'SYS' => 'Cardápio',
				'WPP' => 'Whatsapp',
				'IFD' => 'Ifood',
			];
			$options = array();
			foreach ($args['select_options'] as $key => $option) {
				$label = isset($friendly[$key]) ? $friendly[$key] : $option;
				$options[] = '<option value="' . esc_attr($key) . '" ' . selected($key, $value, false) . '>' . esc_html($label) . '</option>';
			}
		} else {
			$options = array();
			foreach ( $args['select_options'] as $key => $option ) {
				$options[] = '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $value, false ) . '>' . esc_html( $option ) . '</option>';
			}
		}

		$required = isset( $args['required'] ) && $args['required'] === true ? 'required' : '';
		$class = ! empty( $args['custom_class'] ) ? $args['custom_class'] : '';

		$disabled = '';
		if ($args['name'] === 'order_channel') {
			$disabled = 'disabled="disabled"';
		}
		return sprintf(
			'<select name="%s" id="%s" class="%s" %s %s %s><option value="">%s</option>%s</select>',
			esc_attr( $args['name'] ),
			esc_attr( $args['id'] ),
			esc_attr( $class ),
			esc_attr( $required ),
			isset( $args['data'] ) ? $this->build_data_attr( $args['data'] ) : '',
			$disabled,
			esc_html__( 'Select', 'myd-delivery-pro' ),
			implode( $options )
		);
	}

	/**
	 * Get list fields
	 *
	 * @since 1.9.5
	 * @return array
	 */
	public function get_list_fields() {
		$fields = array_column( $this->fields, 'fields' );
		$list_fields = [];

		foreach ( $fields as $v ) {
			$list_fields = array_merge( $list_fields, array_column( $v, 'name' ) );
		}

		return $list_fields;
	}

	/**
	 * Generate a unique 8-digit product ID
	 *
	 * @since 1.9.5
	 * @return string
	 */
	private function generate_unique_product_id() {
		do {
			$id = str_pad( mt_rand( 0, 99999999 ), 8, '0', STR_PAD_LEFT );
			$exists = get_posts( array(
				'post_type' => 'mydelivery-produtos',
				'meta_key' => 'product_id',
				'meta_value' => $id,
				'posts_per_page' => 1,
				'fields' => 'ids',
			) );
		} while ( ! empty( $exists ) );

		return $id;
	}

	/**
	 * Render input type Linked Extras
	 *
	 * Queries all published mydelivery-extras posts and renders checkboxes
	 * so the user can link centralized extras to a product.
	 *
	 * @param array  $args    Field arguments.
	 * @param int    $post_id Current post ID.
	 * @param mixed  $value   Stored value (array of extra IDs).
	 * @return string
	 */
	public function render_input_linked_extras( array $args, int $post_id, $value = '' ) {
		$selected_ids = is_array( $value ) ? array_map( 'intval', $value ) : array();

		$extras = get_posts( array(
			'post_type'      => 'mydelivery-extras',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		if ( empty( $extras ) ) {
			return '<p class="description">' . esc_html__( 'Nenhum extra cadastrado. Crie extras no menu "Extras" antes de vincular.', 'myd-delivery-pro' ) . '</p>';
		}

		$wrapper_id = esc_attr( $args['id'] ) . '_linked_extras_wrapper';

		$html  = '<div class="myd-linked-extras-wrapper" id="' . $wrapper_id . '" style="max-width:500px;">';
		$html .= '<p class="description" style="margin-bottom:8px;">' . esc_html__( 'Marque os extras que deseja disponibilizar neste produto (arraste para reordenar):', 'myd-delivery-pro' ) . '</p>';
		$html .= '<div class="myd-linked-extras-sortable" style="min-height:20px;">';

		// Organize extras into selected (in saved order) and unselected (alphabetical)
		$extras_indexed = array();
		foreach ( $extras as $extra_post ) {
			$extras_indexed[ $extra_post->ID ] = $extra_post;
		}

		$ordered_extras = array();

		// 1. Add selected extras first, maintaining their saved order
		foreach ( $selected_ids as $sid ) {
			if ( isset( $extras_indexed[ $sid ] ) ) {
				$ordered_extras[] = $extras_indexed[ $sid ];
				unset( $extras_indexed[ $sid ] ); // Remove so it's not added again
			}
		}

		// 2. Add remaining (unselected) extras at the end
		foreach ( $extras_indexed as $extra_post ) {
			$ordered_extras[] = $extra_post;
		}

		// Render the checkboxes
		foreach ( $ordered_extras as $extra_post ) {
			$extra_id = $extra_post->ID;
			$checked  = in_array( $extra_id, $selected_ids, true ) ? 'checked' : '';
			
			// Highlight selected ones slightly differently or give them a grab cursor
			$cursor = 'grab';
			
			$html    .= sprintf(
				'<label class="myd-linked-extra-item" style="display:flex; align-items:center; margin:4px 0; padding:8px; background:#fff; border:1px solid #ddd; border-radius:4px; cursor:%5$s; user-select:none;">
					<span class="dashicons dashicons-menu" style="color:#aaa; margin-right:8px; cursor:grab;"></span>
					<input type="checkbox" name="%1$s[]" value="%2$d" %3$s style="margin-right:8px;"> 
					<span>%4$s</span>
				</label>',
				esc_attr( $args['name'] ),
				$extra_id,
				$checked,
				esc_html( $extra_post->post_title ),
				$cursor
			);
		}

		$html .= '</div>'; // End sortable container
		$html .= '</div>'; // End wrapper box
		
		// Enqueue the native WordPress jQuery UI Sortable and add the init script
		wp_enqueue_script( 'jquery-ui-sortable' );
		
		$html .= '<script>
			jQuery(document).ready(function($) {
				$("#' . $wrapper_id . ' .myd-linked-extras-sortable").sortable({
					handle: ".dashicons-menu",
					cursor: "grabbing",
					placeholder: "ui-state-highlight",
					forcePlaceholderSize: true,
					update: function(event, ui) {
						// The DOM order dictates the $_POST array order automatically
					}
				});
				
				// Optional: add a dashed style for the placeholder
				$("<style type=\'text/css\'> .ui-state-highlight { height: 1.5em; line-height: 1.2em; background: #fdfdfd; border: 1px dashed #ccc; margin: 4px 0; border-radius: 4px; } </style>").appendTo("head");
			});
		</script>';

		return $html;
	}
}
