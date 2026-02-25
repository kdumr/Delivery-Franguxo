<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active = $delivery_mode === 'per-cep-range' ? 'myd-tabs-content--active' : '' ;
/**
 * TODO: check this later
 */
if ( isset( $delivery_mode_options['per-cep-range']['options'] ) ) {
	$delivery_mode_per_cep_range_options = $delivery_mode_options['per-cep-range']['options'];
}
?>
<div class="myd-delivery-type-content <?php echo esc_attr( $active );?>" id="myd-delivery-per-cep-range">
	<h2>
		<?php esc_html_e( 'Price per Zipcode range', 'myd-delivery-pro' ); ?>
	</h2>
	<p>
		<?php esc_html_e( 'Soon we will have this option to calculate shipping using the Google Maps API.', 'myd-delivery-pro' ); ?>
	</p>

	<table class="wp-list-table widefat fixed striped myd-options-table">
        <thead>
            <tr>
                <th style="width:160px"><?php esc_html_e( 'Bairro', 'myd-delivery-pro' );?></th>
                <th><?php esc_html_e( 'From Zipcode', 'myd-delivery-pro' );?></th>
                <th><?php esc_html_e( 'To Zipcode', 'myd-delivery-pro' );?></th>
                <th><?php esc_html_e( 'Price', 'myd-delivery-pro' );?></th>
                <th class="myd-options-table__action"><?php esc_html_e( 'Action', 'myd-delivery-pro' );?></th>
            </tr>
        </thead>
        <tbody>
            <?php if( isset( $delivery_mode_per_cep_range_options ) && !empty( $delivery_mode_per_cep_range_options ) ): ?>

                <?php foreach( $delivery_mode_per_cep_range_options as $k => $v ): ?>
                    <tr class="myd-options-table__row-content" data-row-index='<?php echo esc_attr( $k );?>' data-row-field-base="myd-delivery-mode-options[per-cep-range][options]">
                        <td>
                            <span class="myd-cep-bairro" style="font-size:12px;color:#555;font-style:italic;">—</span>
                        </td>
                        <td>
                            <input name="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][from]" data-data-index="from" type="number" id="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][from]" value="<?php echo esc_attr( $v['from'] );?>" class="regular-text myd-input-full">
                        </td>
                        <td>
                            <input name="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][to]" data-data-index="to" type="number" id="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][from]" value="<?php echo esc_attr( $v['to'] );?>" class="regular-text myd-input-full">
                        </td>
                        <td>
                            <input name="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][price]" data-data-index="price" type="number" step="0.001" id="myd-delivery-mode-options[per-cep-range][options][<?php echo esc_attr( $k );?>][price]" value="<?php echo esc_attr( $v['price'] );?>" class="regular-text myd-input-full">
                        </td>
                        <td>
                            <span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterTableRemoveRow(this)"><?php echo esc_html_e( 'remove', 'myd-delivery-pro' );?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>

                <tr class="myd-options-table__row-content" data-row-index='0' data-row-field-base="myd-delivery-mode-options[per-cep-range][options]">
                    <td>
                        <span class="myd-cep-bairro" style="font-size:12px;color:#555;font-style:italic;">—</span>
                    </td>
                    <td>
                        <input name="myd-delivery-mode-options[per-cep-range][options][0][from]" data-data-index="from" type="number" id="myd-delivery-mode-options[per-cep-range][options][0][from]" value="" class="regular-text myd-input-full">
                    </td>
                    <td>
                        <input name="myd-delivery-mode-options[per-cep-range][options][0][to]" data-data-index="to" type="number" id="myd-delivery-mode-options[per-cep-range][options][0][to]" value="" class="regular-text myd-input-full">
                    </td>
                    <td>
                        <input name="myd-delivery-mode-options[per-cep-range][options][0][price]" data-data-index="price" type="number" step="0.001" id="myd-delivery-mode-options[per-cep-range][options][0][price]" value="" class="regular-text myd-input-full">
                    </td>
                    <td>
                        <span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterTableRemoveRow(this)"><?php echo esc_html_e( 'remove', 'myd-delivery-pro' );?></span>
                    </td>
                </tr>

            <?php endif;?>
        </tbody>
    </table>
    <a href="#" class="button button-small button-secondary myd-repeater-table__button" onclick="window.MydAdmin.mydRepeaterTableAddRow(event)"><?php esc_html_e( 'Add more', 'myd-delivery-pro' );?></a>
</div>

<script>
(function () {
    'use strict';

    var cache = {};

    /**
     * Busca bairro via ViaCEP para um CEP de 8 dígitos
     */
    function fetchBairro(cep, callback) {
        cep = String(cep).replace(/\D/g, '');
        if (cep.length !== 8) { callback(null); return; }

        if (cache[cep]) { callback(cache[cep]); return; }

        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && !data.erro && data.bairro) {
                    var label = data.bairro;
                    if (data.localidade) label += ' - ' + data.localidade;
                    cache[cep] = label;
                    callback(label);
                } else {
                    callback(null);
                }
            })
            .catch(function () { callback(null); });
    }

    /**
     * Atualiza o span de bairro de uma linha da tabela
     */
    function updateRowBairro(row) {
        var fromInput = row.querySelector('input[data-data-index="from"]');
        var span = row.querySelector('.myd-cep-bairro');
        if (!fromInput || !span) return;

        var cep = String(fromInput.value).replace(/\D/g, '');
        if (cep.length < 8) { span.textContent = '—'; return; }

        span.textContent = 'Buscando...';
        fetchBairro(cep, function (bairro) {
            span.textContent = bairro || 'Não encontrado';
        });
    }

    /**
     * Inicializa eventos e busca bairros das linhas existentes
     */
    function init() {
        var container = document.getElementById('myd-delivery-per-cep-range');
        if (!container) return;

        // Buscar bairro de todas as linhas existentes ao carregar (escalonado para respeitar rate limit do ViaCEP)
        var rows = container.querySelectorAll('.myd-options-table__row-content');
        rows.forEach(function (row, index) {
            setTimeout(function () { updateRowBairro(row); }, index * 1500); // 1.5s entre cada request (~40/min, dentro do limite de 50/min)
        });

        // Delegar evento blur nos inputs "from" para buscar bairro
        container.addEventListener('focusout', function (e) {
            var input = e.target;
            if (!input || input.getAttribute('data-data-index') !== 'from') return;
            var row = input.closest('.myd-options-table__row-content');
            if (row) updateRowBairro(row);
        });

        // Observar novas linhas adicionadas via "Add more"
        var tbody = container.querySelector('tbody');
        if (tbody) {
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    m.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1 && node.classList.contains('myd-options-table__row-content')) {
                            // Garantir que a nova linha tem o span de bairro
                            var firstTd = node.querySelector('td:first-child');
                            if (firstTd && !firstTd.querySelector('.myd-cep-bairro')) {
                                var span = document.createElement('span');
                                span.className = 'myd-cep-bairro';
                                span.style.cssText = 'font-size:12px;color:#555;font-style:italic;';
                                span.textContent = '—';
                                firstTd.innerHTML = '';
                                firstTd.appendChild(span);
                            }
                        }
                    });
                });
            });
            observer.observe(tbody, { childList: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
