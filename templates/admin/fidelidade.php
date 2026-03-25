<?php
// Template para página de Fidelidade
// Salva e carrega configurações do sistema de fidelidade
if (is_admin() && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['myd_loyalty_form_submitted'])) {
    if ( ! isset($_POST['loyalty_nonce_field']) || ! wp_verify_nonce( $_POST['loyalty_nonce_field'], 'myd_loyalty_nonce' ) || ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Acesso negado. Falha na verificação de segurança.' );
    }

    // Salvar opções
    $toggle_val = isset($_POST['fidelidade_toggle']) ? $_POST['fidelidade_toggle'] : (isset($_POST['fidelidade_toggle_hidden']) ? $_POST['fidelidade_toggle_hidden'] : 'off');
    update_option('myd_fidelidade_ativo', $toggle_val === 'on' ? 'on' : 'off');
	// apenas loyalty_value suportado atualmente
	update_option('myd_fidelidade_tipo', 'loyalty_value');
    update_option('myd_fidelidade_valor', sanitize_text_field($_POST['input_loyalty_value'] ?? ''));
	// quantidade de pedidos (loyalty_quantity) não é mais suportada; manter opção vazia
	update_option('myd_fidelidade_quantidade', '');
    update_option('myd_fidelidade_premio_tipo', sanitize_text_field($_POST['reward_type'] ?? 'percent'));
    update_option('myd_fidelidade_premio_percent', sanitize_text_field($_POST['input_reward_percent'] ?? ''));
    update_option('myd_fidelidade_premio_fixo', sanitize_text_field($_POST['input_reward_fixed'] ?? ''));
    update_option('myd_fidelidade_expiracao', sanitize_text_field($_POST['loyalty_expiration'] ?? 'never'));
	$pontos_post = isset($_POST['input_loyalty_points_needed']) ? intval($_POST['input_loyalty_points_needed']) : 0;
	$pontos_post = max(1, min(20, $pontos_post));
	update_option('myd_fidelidade_pontos_necessarios', $pontos_post);
    echo '<div class="updated notice"><p>Configurações de fidelidade salvas com sucesso!</p></div>';
}
// Carregar opções
$ativo = get_option('myd_fidelidade_ativo', 'off');
$tipo = get_option('myd_fidelidade_tipo', 'loyalty_value');
$valor = get_option('myd_fidelidade_valor', '');
$quantidade = get_option('myd_fidelidade_quantidade', '');
$premio_tipo = get_option('myd_fidelidade_premio_tipo', 'percent');
$premio_percent = get_option('myd_fidelidade_premio_percent', '');
$premio_fixo = get_option('myd_fidelidade_premio_fixo', '');
$expiracao = get_option('myd_fidelidade_expiracao', 'never');
$pontos_necessarios = intval( get_option('myd_fidelidade_pontos_necessarios', 0) );
?>
<div class="wrap">
	<h1>Fidelidade</h1>
	<p>Configure o programa de fidelidade para seus clientes. Acumule pontos com base no valor total das compras.</p>
    

</div>

<!-- Slider fora do form -->
<div style="margin:18px 0 24px;">
	<label class="myd-switch">
		<input type="checkbox" id="fidelidade_toggle_ui" <?php if ($ativo === 'on') echo 'checked'; ?> />
		<span class="myd-slider"></span>
	</label>
	<span style="margin-left:12px;font-weight:bold;vertical-align:middle;">Ativar sistema de fidelidade</span>
	</div>

<!-- Reabre container/form para manter layout abaixo -->
<div class="wrap">
	<form id="myd-loyalty-form" method="post" action="">
	<?php wp_nonce_field( 'myd_loyalty_nonce', 'loyalty_nonce_field' ); ?>
	<input type="hidden" name="myd_loyalty_form_submitted" value="1" />
	<!-- hidden input que será enviado com o estado do slider -->
	<input type="hidden" name="fidelidade_toggle" id="fidelidade_toggle_input" value="<?php echo $ativo === 'on' ? 'on' : 'off'; ?>" />
		<table class="form-table">
			<tr>
				<th><label for="input_loyalty_value">Valor mínimo para pontuação (R$):</label></th>
				<td>
					<input type="text" inputmode="decimal" id="input_loyalty_value" name="input_loyalty_value" class="input-monetary" autocomplete="off" value="<?php echo esc_attr($valor); ?>" />
					<div id="err_input_loyalty_value" class="myd-error" style="display:none">Campo obrigatório</div>
				</td>
			</tr>
			<tr id="loyalty_value_row">
								<th><label for="input_loyalty_value">Insira o valor total (R$):</label></th>
								<td>
									<input type="text" inputmode="decimal" id="input_loyalty_value" name="input_loyalty_value" class="input-monetary" autocomplete="off" value="<?php echo esc_attr($valor); ?>" />
									<div id="err_input_loyalty_value" class="myd-error" style="display:none">Campo obrigatório</div>
								</td>
						</tr>
			<tr id="loyalty_points_row">
								<th><label for="input_loyalty_points_needed">Quantidade de pontos necessária:</label></th>
								<td>
									<input type="number" min="1" max="20" id="input_loyalty_points_needed" name="input_loyalty_points_needed" value="<?php echo esc_attr($pontos_necessarios); ?>" />
									<p class="description">A cada pedido com valor igual ou maior que o valor definido acima o cliente ganha 1 ponto. Informe quantos pontos são necessários para ganhar o prêmio.</p>
									<div id="err_input_loyalty_points_needed" class="myd-error" style="display:none">Campo obrigatório</div>
								</td>
							</tr>
						<!-- loyalty_quantity removido: apenas loyalty_value é suportado -->
			<tr>
                <th><label for="reward_type">Tipo de prêmio:</label></th>
                <td>
                    <select id="reward_type" name="reward_type">
                        <option value="percent" <?php if ($premio_tipo === 'percent') echo 'selected'; ?>>Desconto em % (porcentagem) total do pedido</option>
                        <option value="fixed" <?php if ($premio_tipo === 'fixed') echo 'selected'; ?>>Valor fixo</option>
                    </select>
                </td>
            </tr>
            <tr id="reward_percent_row" style="<?php echo $premio_tipo === 'percent' ? '' : 'display:none;'; ?>">
                <th><label for="input_reward_percent">Porcentagem de desconto (%):</label></th>
								<td>
									<input type="text" inputmode="decimal" id="input_reward_percent" name="input_reward_percent" class="input-percent" autocomplete="off" value="<?php echo esc_attr($premio_percent); ?>" />
									<div id="err_input_reward_percent" class="myd-error" style="display:none">Campo obrigatório</div>
								</td>
            </tr>
            <tr id="reward_fixed_row" style="<?php echo $premio_tipo === 'fixed' ? '' : 'display:none;'; ?>">
                <th><label for="input_reward_fixed">Valor fixo do prêmio (R$):</label></th>
								<td>
									<input type="text" inputmode="decimal" id="input_reward_fixed" name="input_reward_fixed" class="input-monetary" autocomplete="off" value="<?php echo esc_attr($premio_fixo); ?>" />
									<div id="err_input_reward_fixed" class="myd-error" style="display:none">Campo obrigatório</div>
								</td>
            </tr>
			<tr>
				<th><label for="loyalty_expiration">Tempo de expiração da fidelidade:</label></th>
				<td>
					<select id="loyalty_expiration" name="loyalty_expiration">
						<option value="30" <?php if ($expiracao === '30') echo 'selected'; ?>>30 dias</option>
						<option value="60" <?php if ($expiracao === '60') echo 'selected'; ?>>60 dias</option>
						<option value="120" <?php if ($expiracao === '120') echo 'selected'; ?>>120 dias</option>
						<option value="240" <?php if ($expiracao === '240') echo 'selected'; ?>>240 dias</option>
						<option value="480" <?php if ($expiracao === '480') echo 'selected'; ?>>480 dias</option>
						<option value="960" <?php if ($expiracao === '960') echo 'selected'; ?>>960 dias</option>
						<option value="never" <?php if ($expiracao === 'never') echo 'selected'; ?>>Nunca (∞)</option>
					</select>
				</td>
			</tr>
		</table>
		<p><input type="submit" class="button button-primary" value="Salvar Configuração" /></p>
	</form>
</div>

<style>
.myd-switch {
  position: relative;
  display: inline-block;
  width: 52px;
  height: 28px;
  vertical-align: middle;
}
.myd-switch input {display:none;}
.myd-slider {
	position: absolute;
	cursor: pointer;
	top: 0; left: 0; right: 0; bottom: 0;
	/* Off = vermelho */
	background-color: #e74c3c;
	transition: background-color 0.28s ease;
	border-radius: 28px;
}
.myd-slider:before {
	position: absolute;
	content: "";
	height: 22px;
	width: 22px;
	left: 3px;
	bottom: 3px;
	background-color: white;
	transition: transform 0.28s ease, background-color 0.28s ease;
	border-radius: 50%;
}
.myd-switch input:checked + .myd-slider {
	/* On = verde */
	background-color: #27ae60;
}

.myd-switch input:checked + .myd-slider:before {
	transform: translateX(24px);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// Simplificado: apenas loyalty_value é suportado
	var rowValor = document.getElementById('loyalty_value_row');
	var rewardType = document.getElementById('reward_type');
	var rowPercent = document.getElementById('reward_percent_row');
	var rowFixed = document.getElementById('reward_fixed_row');
	// visible UI toggle (outside form)
	var toggle = document.getElementById('fidelidade_toggle_ui');
	var toggleInput = document.getElementById('fidelidade_toggle_input');
	var form = document.getElementById('myd-loyalty-form');
	var allInputs = form.querySelectorAll('input, select, textarea, button');
	var allLabels = form.querySelectorAll('label, th, td, p, h1, h2, h3, h4, h5, h6');

	function toggleFields() {
		// always show loyalty value rows
		if (rowValor) rowValor.style.display = '';

		if (rewardType && rewardType.value === 'percent') {
			if (rowPercent) rowPercent.style.display = '';
			if (rowFixed) rowFixed.style.display = 'none';
		} else {
			if (rowPercent) rowPercent.style.display = 'none';
			if (rowFixed) rowFixed.style.display = '';
		}
	}
	if (rewardType) rewardType.addEventListener('change', toggleFields);
	toggleFields();
	function setFormEnabled(enabled) {
		allInputs.forEach(function(el) {
			// nunca desabilitar o input hidden que transmite o estado do slider
			if (el === toggle) return;
			if (el.type === 'hidden') return;
			if (el.type === 'submit' && el.classList.contains('button-primary')) return;
			el.disabled = !enabled;
		});
		allLabels.forEach(function(el) {
			if (!enabled) {
				el.style.opacity = 0.5;
			} else {
				el.style.opacity = '';
			}
		});
		// O botão de submit deve ficar sempre ativo e opaco normal
		var submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
		if (submitBtn) {
			submitBtn.disabled = false;
			submitBtn.style.opacity = '1';
			submitBtn.style.pointerEvents = 'auto';
			submitBtn.style.filter = 'none';
			// Corrige opacidade herdada do <td> ou <p> pai
			if (submitBtn.parentElement) {
				submitBtn.parentElement.style.opacity = '1';
			}
		}
	}
	// Inicializa conforme estado do checkbox (carregado pelo PHP)
	if (toggle) {
		// set hidden input value to reflect UI toggle
		if (toggleInput) toggleInput.value = toggle.checked ? 'on' : 'off';
		setFormEnabled(toggle.checked);
		toggle.addEventListener('change', function() {
			if (toggleInput) toggleInput.value = toggle.checked ? 'on' : 'off';
			setFormEnabled(toggle.checked);
		});
	} else {
		setFormEnabled(false);
	}

	// Formatação visual dos inputs
	function formatMonetaryInput(input) {
		input.addEventListener('input', function(e) {
			let val = input.value.replace(/\D/g, '');
			if (val.length > 0) {
				while (val.length < 3) val = '0' + val;
				let intPart = val.slice(0, val.length - 2);
				let decPart = val.slice(-2);
				let formatted = intPart + ',' + decPart;
				formatted = formatted.replace(/^0+(\d)/, '$1');
				input.classList.add('formatted');
				input.value = formatted;
			} else {
				input.classList.remove('formatted');
				input.value = '';
			}
		});
		input.addEventListener('focus', function() { input.select(); });
	}
	function formatPercentInput(input) {
		input.addEventListener('input', function(e) {
			let val = input.value.replace(/\D/g, '');
			if (val.length > 0) {
				let percent = parseInt(val, 10);
				if (percent > 100) percent = 100;
				input.value = percent + '%';
				input.classList.add('formatted');
			} else {
				input.classList.remove('formatted');
				input.value = '';
			}
		});
		input.addEventListener('focus', function() { input.select(); });
	}
	document.querySelectorAll('.input-monetary').forEach(formatMonetaryInput);
	document.querySelectorAll('.input-percent').forEach(formatPercentInput);
});
</script>
<style>
/* Erros e destaque de inputs */
.myd-error { color: #c00; font-size: 12px; margin-top: 6px; }
.input-error { border-color: #c00 !important; box-shadow: 0 0 0 2px rgba(204,0,0,0.08) !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	var toggleUi = document.getElementById('fidelidade_toggle_ui');
	var form = document.getElementById('myd-loyalty-form');
	var inputValue = document.getElementById('input_loyalty_value');
	var inputQty = document.getElementById('input_loyalty_quantity');

	function showError(errId, input, msg) {
		var el = document.getElementById(errId);
		if (el) { el.textContent = msg; el.style.display = 'inline-block'; }
		if (input) input.classList.add('input-error');
	}

	function clearError(errId, input) {
		var el = document.getElementById(errId);
		if (el) { el.style.display = 'none'; }
		if (input) input.classList.remove('input-error');
	}

	if (inputValue) {
		inputValue.addEventListener('focus', function() { clearError('err_input_loyalty_value', inputValue); });
	}
	var inputPoints = document.getElementById('input_loyalty_points_needed');
	if (inputPoints) {
		inputPoints.addEventListener('focus', function() { clearError('err_input_loyalty_points_needed', inputPoints); });
	}
	// inputQty removed (loyalty_quantity not supported)
	var inputRewardPercent = document.getElementById('input_reward_percent');
	var inputRewardFixed = document.getElementById('input_reward_fixed');
	if (inputRewardPercent) {
		inputRewardPercent.addEventListener('focus', function() { clearError('err_input_reward_percent', inputRewardPercent); });
	}
	if (inputRewardFixed) {
		inputRewardFixed.addEventListener('focus', function() { clearError('err_input_reward_fixed', inputRewardFixed); });
	}

	form.addEventListener('submit', function(e) {
		// Se sistema desligado, permite salvar (configuração de ligar/desligar ainda pode ser salva)
		if (toggleUi && !toggleUi.checked) return;

		var valid = true;

		// Valida valor mínimo > 0 (loyalty_value)
		var digits = inputValue ? inputValue.value.replace(/\D/g, '') : '';
		var cents = digits ? parseInt(digits, 10) : 0; // em centavos
		if (!digits) {
			showError('err_input_loyalty_value', inputValue, 'Campo obrigatório');
			valid = false;
		} else if (cents <= 0) {
			showError('err_input_loyalty_value', inputValue, 'Insira um valor maior que 0');
			valid = false;
		}

		// Se definido, também validar pontos necessários maior que 0
		var pointsNeeded = inputPoints ? inputPoints.value.trim() : '';
		var pnum = pointsNeeded ? parseInt(pointsNeeded, 10) : 0;
		if (!pointsNeeded) {
			showError('err_input_loyalty_points_needed', inputPoints, 'Campo obrigatório');
			valid = false;
		} else if (isNaN(pnum) || pnum <= 0) {
			showError('err_input_loyalty_points_needed', inputPoints, 'Insira um valor maior que 0');
			valid = false;
		} else if (pnum > 20) {
			showError('err_input_loyalty_points_needed', inputPoints, 'Máximo 20 pontos');
			valid = false;
		}

		// Validação dos campos de prêmio dependendo do tipo selecionado
		var rtype = document.getElementById('reward_type').value;
		if (rtype === 'percent') {
			var pdigits = inputRewardPercent ? inputRewardPercent.value.replace(/\D/g, '') : '';
			var pnum = pdigits ? parseInt(pdigits, 10) : 0;
			if (!pdigits) {
				showError('err_input_reward_percent', inputRewardPercent, 'Campo obrigatório');
				valid = false;
			} else if (pnum <= 0) {
				showError('err_input_reward_percent', inputRewardPercent, 'Insira um valor maior que 0');
				valid = false;
			}
		} else if (rtype === 'fixed') {
			var fdigits = inputRewardFixed ? inputRewardFixed.value.replace(/\D/g, '') : '';
			var fnum = fdigits ? parseInt(fdigits, 10) : 0; // centavos
			if (!fdigits) {
				showError('err_input_reward_fixed', inputRewardFixed, 'Campo obrigatório');
				valid = false;
			} else if (fnum <= 0) {
				showError('err_input_reward_fixed', inputRewardFixed, 'Insira um valor maior que 0');
				valid = false;
			}
		}

		if (!valid) {
			e.preventDefault();
			return false;
		}
	});
});
</script>