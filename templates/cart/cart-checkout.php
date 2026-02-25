<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shipping method
 */
$shipping_type = get_option( 'myd-delivery-mode' );
$shipping_options = get_option( 'myd-delivery-mode-options' );
$shipping_options = isset( $shipping_options[ $shipping_type ] ) ? $shipping_options[ $shipping_type ] : '';

// get payment methods options
$payments = get_option( 'fdm-payment-type' );
$payments = explode( ",", $payments );
$payments = array_map( 'trim', $payments );

// coupons
$coupons_args = [
	'post_type' => 'mydelivery-coupons',
	'no_found_rows' => true,
	'post_status' => 'publish',
];

$coupons_list = new \WP_Query( $coupons_args );
$coupons_list = $coupons_list->posts;

if ( ! empty( $coupons_list ) ) {
	foreach ( $coupons_list as $k => $v ) {
		$coupons[ $k ] = [ 'name' => $v->post_title ];
		$coupons[ $k ] = $coupons[ $k ] + [ 'type' => get_post_meta( $v->ID, 'myd_coupon_type', true ) ];
		$coupons[ $k ] = $coupons[ $k ] + [ 'format' => get_post_meta( $v->ID, 'myd_discount_format', true ) ];
		$coupons[ $k ] = $coupons[ $k ] + [ 'value' => get_post_meta( $v->ID, 'myd_discount_value', true ) ];
	}
}

$enable_autocomplete_address = get_option( 'fdm-business-country' ) === 'Brazil' ? 'true' : 'false';

/**
 * To legacy type of input mask.
 * TODO: remove soon.
 */
$map_legacy_mask_option = array(
	'fdm-tel-8dig' => '####-####',
	'myd-tel-9' => '#####-####',
	'myd-tel-8-ddd' => '(##)####-####',
	'myd-tel-9-ddd' => '(##)#####-####',
	'myd-tel-us' => '(###)###-####',
	'myd-tel-ven' => '(####)###-####',
);
$mask_option = \get_option( 'fdm-mask-phone' );
if ( isset( $map_legacy_mask_option[ $mask_option ] ) ) {
	\update_option( 'fdm-mask-phone', $map_legacy_mask_option[ $mask_option ] );
	$mask_option = \get_option( 'fdm-mask-phone' );
}

?>
<div class="myd-cart__checkout">
	<div class="myd-cart__checkout-type">

		<div class="myd-cart__checkout-title"><?php esc_html_e( 'Order Type', 'myd-delivery-pro' ); ?></div>

			<div class="myd-cart__checkout-option-wrap">

				<?php if( get_option( 'myd-operation-mode-delivery' ) === 'delivery' ) : ?>

					<div class="myd-cart__checkout-option myd-cart__checkout-option--active" data-type="delivery" data-content=".myd-cart__checkout-customer, .myd-cart__checkout-delivery">
						<div class="myd-cart__checkout-option-delivery" data-type="delivery"><?php esc_html_e( 'Delivery', 'myd-delivery-pro' ); ?></div>
					</div>

					

				<?php endif; ?>

				<?php if( get_option( 'myd-operation-mode-take-away' ) === 'take-away' ) : ?>

				<div class="myd-cart__checkout-option" data-type="take-away" data-content=".myd-cart__checkout-customer">
					<div class="myd-cart__checkout-option-order-in-store" data-type="take-away"><?php esc_html_e( 'Take Away', 'myd-delivery-pro' ); ?></div>
				</div>

				<?php endif; ?>

				<?php if( get_option( 'myd-operation-mode-in-store' ) === 'order-in-store' ) : ?>

					<div class="myd-cart__checkout-option" data-type="order-in-store" data-content=".myd-cart__checkout-customer, .myd-cart__checkout-in-store">
					<div class="myd-cart__checkout-option-order-in-store" data-type="order-in-store"><?php esc_html_e( 'Order in Store', 'myd-delivery-pro' ); ?></div>
				</div>

				<?php endif; ?>
			</div>
		</div>

	<div class="myd-cart__checkout-customer myd-cart__checkout-field-group--active">

		<div class="myd-cart__checkout-title"><?php esc_html_e( 'Customer Info', 'myd-delivery-pro' ); ?></div>

		<label class="myd-cart__checkout-label" for="input-customer-name"><?php esc_html_e( 'Name', 'myd-delivery-pro' ); ?></label>
		<?php
		$customer_name = '';
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( in_array( 'client', (array) $user->roles ) ) {
				$customer_name = esc_attr( $user->display_name );
			}
		}
		?>
		<input type="text" class="myd-cart__checkout-input" id="input-customer-name" name="input-customer-name" required value="<?php echo $customer_name; ?>"<?php echo $customer_name ? ' readonly' : ''; ?> autocomplete="off" data-lpignore="true" data-form-type="other">

	<label class="myd-cart__checkout-label" for="input-customer-phone"><?php esc_html_e( 'Phone', 'myd-delivery-pro' ); ?> (Whatsapp)</label>
		<?php
		$customer_phone = '';
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			   $customer_phone = get_user_meta( $user->ID, 'myd_customer_phone', true );
			   // Formatar telefone para (##) #####-####
			   if ( preg_match( '/^(\d{2})(\d{5})(\d{4})$/', $customer_phone, $m ) ) {
				   $customer_phone = "($m[1]) $m[2]-$m[3]";
			   }
		}
		?>
		<input
			type="text"
			class="myd-cart__checkout-input"
			id="input-customer-phone"
			name="input-customer-phone"
			required
			data-mask="<?php echo esc_attr( $mask_option ); ?>"
			inputmode="numeric"
			autocomplete="off"
			data-lpignore="true"
			data-form-type="other"
			value="<?php echo esc_attr( $customer_phone ); ?>"<?php echo $customer_phone ? ' readonly' : ''; ?>
		>
		<div>
			<button type="button" id="myd-change-phone-link" style="background:none;border:none;color:#ffae00;font-weight:600;cursor:pointer;padding:0;">Alterar telefone &gt;</button>
		</div>

		<!-- Modal para alterar telefone -->
		<div id="myd-change-phone-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:99999;align-items:center;justify-content:center;">
			<div style="background:#fff;border-radius:8px;padding:16px;max-width:420px;width:100%;box-sizing:border-box;margin:0 16px;">
				<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
					<strong style="font-size:16px;">Alterar telefone</strong>
				</div>
				<div style="margin-bottom:8px;">
					<input type="text" id="myd-change-phone-input" placeholder="Digite o novo telefone" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;" inputmode="numeric">
				</div>
				<div style="display:flex;gap:8px;justify-content:flex-end;">
					<button type="button" id="myd-change-phone-save" style="font-weight:600;border:none;padding:8px 12px;border-radius:4px;cursor:pointer;">Salvar</button>
					<button type="button" id="myd-change-phone-cancel" style="border:none;padding:8px 12px;border-radius:4px;cursor:pointer;font-weight:600;">Cancelar</button>
				</div>

					<style>
						#myd-change-phone-save{ color:#fff; background:#ffae00; transition: background 150ms ease; }
						#myd-change-phone-save:hover{ background:#ffae0094 !important; color:#fff !important; }
						#myd-change-phone-save:active{ transform:translateY(1px); }
						#myd-change-phone-cancel{ color:#000; background:#f0f0f0; transition: background 150ms ease; }
						#myd-change-phone-cancel:hover{ background:#000 !important; color:#fff !important; }
						#myd-change-phone-cancel:active{ transform:translateY(1px); }
						#myd-change-phone-link:hover{ text-decoration:underline; color:#ffae00; }
						#myd-change-phone-save:focus, #myd-change-phone-cancel:focus, #myd-change-phone-link:focus {
							outline: 2px solid rgba(255,174,0,0.25);
							outline-offset: 2px;
						}
					</style>
				<div id="myd-change-phone-msg" style="margin-top:8px;color:#cb2027;display:none;font-size:13px;"></div>
			</div>
		</div>

		<script>
		(function(){
			var link = document.getElementById('myd-change-phone-link');
			var modal = document.getElementById('myd-change-phone-modal');
			var closeBtn = document.getElementById('myd-change-phone-close');
			var cancelBtn = document.getElementById('myd-change-phone-cancel');
			var saveBtn = document.getElementById('myd-change-phone-save');
			var input = document.getElementById('myd-change-phone-input');
			var msg = document.getElementById('myd-change-phone-msg');
			var phoneField = document.getElementById('input-customer-phone');
			
			function formatBRPhone(digits) {
				if (!digits) return '';
				digits = digits.replace(/\D/g, '');
				// Remove leading country code if present (e.g., 55)
				if (digits.length > 11 && digits.indexOf('55') === 0) {
					digits = digits.substr(2);
				}
				if (digits.length <= 10) {
					// (XX) XXXX-XXXX
					return digits.replace(/(\d{2})(\d{0,4})(\d{0,4})/, function(m,a,b,c){
						var s = ''; if (a) s += '('+a+')'; if (b) s += ' '+b; if (c) s += (b.length?'-':'')+c; return s.trim();
					});
				}
				// 11 digits -> (XX) 9XXXX-XXXX
				return digits.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
			}

			// format input display while typing
			if (input) {
				input.addEventListener('input', function(ev){
					var raw = input.value.replace(/[^0-9]/g, '');
					// limit to max 11 digits
					if (raw.length > 11) raw = raw.substr(0,11);
					var formatted = formatBRPhone(raw);
					input.value = formatted;
				});
			}

			function showPopupNotification(message, type) {
				try {
					var tpl = document.getElementById('myd-popup-notification');
					var msgEl = document.getElementById('myd-popup-notification__message');
					if (tpl && msgEl) {
						msgEl.innerText = message;
						if (type === 'success') tpl.style.background = '#35a575';
						else tpl.style.background = '#cb2027';
						tpl.style.opacity = '1'; tpl.style.visibility = 'visible';
						setTimeout(function(){ try { tpl.style.opacity = '0'; tpl.style.visibility = 'hidden'; } catch(e){} }, 6000);
						return;
					}
				} catch(e) { /* ignore */ }
				// fallback: alert
				try { alert(message); } catch(e){}
			}

			function open() {
				if (modal) modal.style.display = 'flex';
				if (input) input.value = formatBRPhone(phoneField ? phoneField.value : '');
				if (input) try{ input.focus(); }catch(e){}
			}
			function close() {
				if (modal) modal.style.display = 'none';
			}
			if (link) link.addEventListener('click', open);
			if (closeBtn) closeBtn.addEventListener('click', close);
			if (cancelBtn) cancelBtn.addEventListener('click', close);
			// click outside modal content closes
			if (modal) modal.addEventListener('click', function(ev){ if (ev.target === modal) close(); });
			if (saveBtn) saveBtn.addEventListener('click', function(){
				var val = input ? input.value.trim() : '';
				// basic normalization: keep only digits
				val = val.replace(/\D/g, '');
				// limit to max 11 digits
				if (val.length > 11) val = val.substr(0,11);
				if (!val) {
					showPopupNotification('Informe um telefone válido.', 'error');
					return;
				}
				// require exactly 11 digits
				if (val.length !== 11) {
					showPopupNotification('Telefone deve conter 11 dígitos.', 'error');
					return;
				}
				// Prepare data for AJAX
				var name = '';
				try {
					if (typeof mydCustomerAuth !== 'undefined' && mydCustomerAuth.current_user && mydCustomerAuth.current_user.name) {
						name = mydCustomerAuth.current_user.name;
					} else if (document.getElementById('input-customer-name')) {
						name = document.getElementById('input-customer-name').value || '';
					}
				} catch(e) { name = ''; }
				if (!name) {
					showPopupNotification('Nome do cliente ausente. Atualize a página.', 'error');
					return;
				}
				if (typeof jQuery === 'undefined' || typeof mydCustomerAuth === 'undefined') {
					// Try fetch fallback
					fetch((window.mydCustomerAuth && mydCustomerAuth.ajax_url) ? mydCustomerAuth.ajax_url : '/wp-admin/admin-ajax.php', {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: 'action=myd_update_customer_profile&nonce=' + encodeURIComponent(mydCustomerAuth ? mydCustomerAuth.nonce : '') + '&name=' + encodeURIComponent(name) + '&phone=' + encodeURIComponent(val)
					}).then(r=>r.json()).then(function(res){
						if (res && res.success) {
							if (phoneField) { phoneField.value = formatBRPhone(val); phoneField.setAttribute('readonly','readonly'); }
							if (typeof mydCustomerAuth !== 'undefined' && mydCustomerAuth.current_user) { mydCustomerAuth.current_user.phone = val; }
							showPopupNotification(res && res.data && res.data.message ? res.data.message : 'Perfil atualizado com sucesso!', 'success');
							close();
						} else {
							showPopupNotification((res && res.data && res.data.message) ? res.data.message : 'Erro ao salvar.', 'error');
						}
					}).catch(function(){ showPopupNotification('Erro na rede.', 'error'); });
					return;
				}
				jQuery.post(mydCustomerAuth.ajax_url, {
					action: 'myd_update_customer_profile',
					nonce: mydCustomerAuth.nonce,
					name: name,
					phone: val
				}, function(res){
						if (res && res.success) {
							if (phoneField) { phoneField.value = formatBRPhone(val); phoneField.setAttribute('readonly','readonly'); }
							if (typeof mydCustomerAuth !== 'undefined' && mydCustomerAuth.current_user) { mydCustomerAuth.current_user.phone = val; }
							showPopupNotification(res && res.data && res.data.message ? res.data.message : 'Perfil atualizado com sucesso!', 'success');
							close();
						} else {
							showPopupNotification((res && res.data && res.data.message) ? res.data.message : 'Erro ao salvar.', 'error');
						}
				}, 'json').fail(function(){ showPopupNotification('Erro na requisição.', 'error'); });
			});
		})();
		</script>
	</div>

	<div class="myd-cart__checkout-delivery myd-cart__checkout-field-group--active myd-cart__checkout-delivery--styled">
		<div class="myd-cart__checkout-title">
			<?php esc_html_e( 'Delivery Info', 'myd-delivery-pro' ); ?>
		</div>

		<?php if ( $shipping_type === 'per-distance' ) : ?>
			<div id="myd-address-preview" class="myd-cart__address-preview myd-address-preview--hidden">
				<div class="myd-address-preview-row">
					<div class="myd-address-preview-left">
						<div class="myd-address-preview-icon">
							<svg width="30" height="30" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
								<path d="M12 2C8.686 2 6 4.686 6 8c0 4.5 6 12 6 12s6-7.5 6-12c0-3.314-2.686-6-6-6zm0 8.5A2.5 2.5 0 1 1 12 5.5a2.5 2.5 0 0 1 0 5z" fill="#000000ff"/>
							</svg>
						</div>
						<div class="myd-address-preview-text">
							<div id="myd-address-preview-line1" class="myd-cart__address-preview-line1"></div>
							<div id="myd-address-preview-line2" class="myd-cart__address-preview-line2"></div>
							<div id="myd-address-preview-line3" class="myd-cart__address-preview-line3 myd-cart__address-preview-line3--sub"></div>
						</div>
					</div>
					
				</div>
				<button type="button" id="myd-address-change" class="myd-cart__address-change">Mudar endereço</button>
			</div>

			<script>
			// Garante que ao clicar em "Trocar" o preview suma
			(function(){
				function hidePreview(){
					try {
						var preview = document.getElementById('myd-address-preview');
						if (preview) preview.style.display = 'none';
					} catch(e) { /* noop */ }
				}
				// Handler direto no botão, se já existir
				var changeBtn = document.getElementById('myd-address-change');
				if (changeBtn) {
					changeBtn.addEventListener('click', hidePreview);
				}
				// Delegado para elementos renderizados dinamicamente
				document.addEventListener('click', function(ev){
					var target = ev.target;
					if (target && (target.id === 'myd-address-change' || (target.closest && target.closest('#myd-address-change')))) {
						hidePreview();
					}
				});
			})();
			</script>
			<div id="myd-autocomplete-wrapper">
				<label
					class="myd-cart__checkout-label"
					for="input-delivery-autocomplete-address"
					>
						<?php esc_html_e( 'Enter your address with number', 'myd-delivery-pro' ); ?>
					</label>
				<input
					type="text"
					class="myd-cart__checkout-input"
					id="input-delivery-autocomplete-address"
					name="input-delivery-autocomplete-address"
					autocomplete="off"
					data-lpignore="true"
					data-form-type="other"
					value=""
				>
				<!-- Custom suggestions container for server-side/autocomplete endpoint rendering -->
				<div id="myd-autocomplete-suggestions" class="myd-autocomplete-suggestions myd-autocomplete-suggestions--custom"></div>
			</div>

			<!-- CEP search section -->
			<div id="myd-cep-search-wrapper">
				<div class="myd-cep-separator">ou</div>
				<label class="myd-cart__checkout-label" for="myd-cep-input">CEP</label>
				<div class="myd-cep-input-row">
					<div class="myd-cep-input-row__inner">
						<input
							type="text"
							class="myd-cart__checkout-input"
							id="myd-cep-input"
							name="myd-cep-input"
							placeholder="00000-000"
							maxlength="9"
							inputmode="numeric"
							autocomplete="off"
							data-lpignore="true"
							data-form-type="other"
						>
						<button
							type="button"
							id="myd-cep-search-btn"
							class="myd-cep-search-btn"
						>Buscar</button>
					</div>
				</div>
				<div id="myd-cep-error" class="myd-cep-error"></div>
			</div>



			<script>
			(function(){
				var cepInput = document.getElementById('myd-cep-input');
				var cepBtn   = document.getElementById('myd-cep-search-btn');
				var cepError = document.getElementById('myd-cep-error');

				if (!cepInput || !cepBtn) return;

				// ---- Máscara CEP: 00000-000 ----
				cepInput.addEventListener('input', function() {
					var raw = cepInput.value.replace(/\D/g, '');
					if (raw.length > 8) raw = raw.substring(0, 8);
					if (raw.length > 5) {
						cepInput.value = raw.substring(0, 5) + '-' + raw.substring(5);
					} else {
						cepInput.value = raw;
					}
				});

				// Bloquear teclas não-numéricas (permitir Backspace, Delete, Tab, Arrow, etc.)
				cepInput.addEventListener('keydown', function(e) {
					if (e.ctrlKey || e.metaKey || e.altKey) return;
					if (['Backspace','Delete','Tab','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'].indexOf(e.key) !== -1) return;
					if (e.key.length === 1 && !/[0-9]/.test(e.key)) {
						e.preventDefault();
					}
				});

				// Bloquear paste de não-numéricos
				cepInput.addEventListener('paste', function(e) {
					e.preventDefault();
					var pasted = (e.clipboardData || window.clipboardData).getData('text') || '';
					var digits = pasted.replace(/\D/g, '');
					if (digits.length > 8) digits = digits.substring(0, 8);
					if (digits.length > 5) {
						cepInput.value = digits.substring(0, 5) + '-' + digits.substring(5);
					} else {
						cepInput.value = digits;
					}
				});

				function showCepError(msg) {
					if (cepError) { cepError.textContent = msg; cepError.style.display = 'block'; }
				}
				function hideCepError() {
					if (cepError) { cepError.textContent = ''; cepError.style.display = 'none'; }
				}

				// ---- Buscar CEP via ViaCEP ----
				cepBtn.addEventListener('click', function() {
					hideCepError();
					var raw = cepInput.value.replace(/\D/g, '');
					if (raw.length !== 8) {
						showCepError('Digite um CEP válido com 8 dígitos.');
						return;
					}
					cepBtn.disabled = true;
					cepBtn.textContent = 'Buscando...';

					fetch('https://viacep.com.br/ws/' + raw + '/json/')
						.then(function(r) { return r.json(); })
						.then(function(data) {
							cepBtn.disabled = false;
							cepBtn.textContent = 'Buscar';
							if (!data || data.erro) {
								showCepError('CEP não encontrado. Verifique e tente novamente.');
								return;
							}
							// Montar objeto place compatível com showAddressDetails
							var placeData = {
								formatted_address: [data.logradouro, data.bairro, data.localidade + ' - ' + data.uf, raw].filter(Boolean).join(', '),
								address_components: [
									{ long_name: data.logradouro || '', short_name: data.logradouro || '', types: ['route'] },
									{ long_name: data.bairro || '', short_name: data.bairro || '', types: ['sublocality_level_1', 'sublocality'] },
									{ long_name: data.localidade || '', short_name: data.localidade || '', types: ['locality', 'administrative_area_level_2'] },
									{ long_name: data.uf || '', short_name: data.uf || '', types: ['administrative_area_level_1'] },
									{ long_name: 'Brasil', short_name: 'BR', types: ['country'] },
									{ long_name: raw.substring(0,5) + '-' + raw.substring(5), short_name: raw.substring(0,5) + '-' + raw.substring(5), types: ['postal_code'] }
								],
								geometry: { location: { lat: 0, lng: 0 } }
							};
							// Disparar evento para o listener existente no order.min.js
							document.dispatchEvent(new CustomEvent('MydCepSearch', { detail: placeData, bubbles: true }));
						})
						.catch(function() {
							cepBtn.disabled = false;
							cepBtn.textContent = 'Buscar';
							showCepError('Erro na conexão. Tente novamente.');
						});
				});

				// Enter no input de CEP dispara busca
				cepInput.addEventListener('keydown', function(e) {
					if (e.key === 'Enter') {
						e.preventDefault();
						cepBtn.click();
					}
				});
			})();
			</script>

			<div id="myd-per-distance-address-extra" class="myd-per-distance-address-extra">
				<!-- Hidden field to store the street number for backend processing -->
				<input
					type="hidden"
					class="myd-cart__checkout-input"
					id="input-delivery-street-number"
					name="input-delivery-street-number"
				>

				<!-- Visible number input - shown when Google doesn't provide street_number -->
				<div id="myd-manual-number-input" class="myd-manual-number-input">
					<label class="myd-cart__checkout-label" for="input-delivery-manual-number">
						<?php esc_html_e( 'Número do endereço', 'myd-delivery-pro' ); ?>
					</label>
					<input
						type="number"
						class="myd-cart__checkout-input"
						id="input-delivery-manual-number"
						name="input-delivery-manual-number"
						placeholder="Ex: 123"
						style="max-width: 150px;"
						autocomplete="off"
						data-lpignore="true"
						data-form-type="other"
						min="1"
						max="99999"
						maxlength="5"
						inputmode="numeric"
					>
				</div>



				<label
					class="myd-cart__checkout-label"
					for="input-delivery-comp"
					>
						<?php esc_html_e( 'Apartment, suite, etc.', 'myd-delivery-pro' ); ?>
				</label>
				<input
					type="text"
					class="myd-cart__checkout-input"
					id="input-delivery-comp"
					name="input-delivery-comp"
					autocomplete="off"
					data-lpignore="true"
					data-form-type="other"
				>

				<div id="myd-reference-wrapper" class="myd-reference-wrapper">
					<label
						class="myd-cart__checkout-label"
						for="input-delivery-reference"
					>
						<?php esc_html_e( 'Ponto de referência', 'myd-delivery-pro' ); ?>
					</label>
					<input
						type="text"
						class="myd-cart__checkout-input"
						id="input-delivery-reference"
						name="input-delivery-reference"
						maxlength="65"
					>
				</div>
			</div>

			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-latitude"
				name="input-delivery-latitude"
			>
			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-longitude"
				name="input-delivery-longitude"
			>
			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-formated-address"
				name="input-delivery-formated-address"
			>
			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-zipcode"
				name="input-delivery-zipcode"
			>
			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-street-name"
				name="input-delivery-street-name"
			>
			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-neighborhood"
				name="input-delivery-neighborhood"
			>
			<input
				type="hidden"
				class="myd-cart__checkout-input"
				id="input-delivery-real-neighborhood"
				name="input-delivery-real-neighborhood"
			>
				<input
					type="hidden"
					class="myd-cart__checkout-input"
					id="input-delivery-city"
					name="input-delivery-city"
				>
				<input
					type="hidden"
					class="myd-cart__checkout-input"
					id="input-delivery-state"
					name="input-delivery-state"
				>
				<input
					type="hidden"
					class="myd-cart__checkout-input"
					id="input-delivery-country"
					name="input-delivery-country"
				>
		<?php else : ?>
			<?php if ( get_option( 'myd-form-hide-zipcode' ) != 'yes' ) : ?>
				<label class="myd-cart__checkout-label" for="input-delivery-zipcode"><?php esc_html_e( 'Zipcode', 'myd-delivery-pro' ); ?></label>
				<input
					type="text"
					class="myd-cart__checkout-input"
					id="input-delivery-zipcode"
					name="input-delivery-zipcode"
					autocomplete="off"
					data-autocomplete="<?php echo \esc_attr( $enable_autocomplete_address ); ?>"
					inputmode="numeric"
					data-lpignore="true"
					data-form-type="other"
					required
				>
			<?php endif; ?>

			<label class="myd-cart__checkout-label" for="input-delivery-street-name"><?php esc_html_e( 'Street Name', 'myd-delivery-pro' ); ?></label>
			<input type="text" class="myd-cart__checkout-input" id="input-delivery-street-name" name="input-delivery-street-name" required autocomplete="off" data-lpignore="true" data-form-type="other">

			<?php if( get_option( 'myd-form-hide-address-number' ) != 'yes' ) : ?>
				<label class="myd-cart__checkout-label" for="input-delivery-address-number"><?php esc_html_e( 'Address Number', 'myd-delivery-pro' ); ?></label>
				<input type="number" class="myd-cart__checkout-input" id="input-delivery-address-number" name="input-delivery-address-number" required autocomplete="off" data-lpignore="true" data-form-type="other">
			<?php endif; ?>

			<label class="myd-cart__checkout-label" for="input-delivery-comp"><?php esc_html_e( 'Apartment, suite, etc.', 'myd-delivery-pro' ); ?></label>
			<input type="text" class="myd-cart__checkout-input" id="input-delivery-comp" name="input-delivery-comp">

			<?php if ( get_option( 'fdm-business-country' ) == 'Brazil' && $shipping_type == 'per-cep-range' || $shipping_type == 'fixed-per-cep' ) : ?>
				<label class="myd-cart__checkout-label" for="input-delivery-neighborhood"><?php esc_html_e( 'Neighborhood', 'myd-delivery-pro' ); ?></label>
				<input type="text" class="myd-cart__checkout-input" id="input-delivery-neighborhood" name="input-delivery-neighborhood" required autocomplete="off" data-lpignore="true" data-form-type="other">
			<?php endif; ?>

			<?php if ( $shipping_type == 'fixed-per-neighborhood' || $shipping_type == 'per-neighborhood' ) : ?>
				<label class="myd-cart__checkout-label" for="input-delivery-neighborhood"><?php esc_html_e( 'Neighborhood', 'myd-delivery-pro' ); ?></label>
				<select class="" id="input-delivery-neighborhood" name="input-delivery-neighborhood" required>
					<option value=""><?php esc_html_e( 'Select', 'myd-delivery-pro' ); ?></option>
					<?php if ( isset( $shipping_options['options'] ) ) :
						foreach( $shipping_options['options'] as $k => $v ) : ?>
							<option value="<?php echo esc_attr( $v['from'] ); ?>"><?php echo esc_html( $v['from'] ); ?></option>
						<?php endforeach;
					endif; ?>
				</select>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<div class="myd-cart__checkout-in-store">
		<div class="myd-cart__checkout-title"><?php esc_html_e( 'Store Info', 'myd-delivery-pro' ); ?></div>
		<label class="myd-cart__checkout-label" for="input-in-store-table"><?php esc_html_e( 'Table number', 'myd-delivery-pro' ); ?></label>
		<input type="text" class="myd-cart__checkout-input" id="input-in-store-table" name="input-in-store-table" autocomplete="off" data-lpignore="true" data-form-type="other">
	</div>

	<div class="myd-cart__checkout-coupon">
		<label class="myd-cart__checkout-label" for="input-checkout-coupon"><?php esc_html_e( 'Coupon', 'myd-delivery-pro' ); ?></label>
		<input type="text" class="myd-cart__checkout-input" id="input-coupon" name="input-checkout-coupon" autocomplete="off" data-lpignore="true" data-form-type="other">
		<p><?php esc_html_e( 'If you have a discount coupon, add it here.', 'myd-delivery-pro' ); ?></p>

		<?php if ( ! empty( $coupons ) && current_user_can( 'manage_options' ) ) : ?>
			<div class="myd-cart__coupons-obj" id="myd-cart__coupons-obj">
				<?php echo wp_json_encode( $coupons ); ?>
			</div>
		<?php endif; ?>
	</div>
</div>
