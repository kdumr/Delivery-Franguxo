<?php
/**
 * Painel: Caixa — Abertura e Fechamento (inline no fdm-orders-content)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$myd_delivery_time = get_option( 'myd-delivery-time', array() );
$today_day_key = strtolower( date( 'l' ) );
$default_open = '00:00';
if ( is_array( $myd_delivery_time ) && isset( $myd_delivery_time[ $today_day_key ] ) && is_array( $myd_delivery_time[ $today_day_key ] ) ) {
	$first_slot = reset( $myd_delivery_time[ $today_day_key ] );
	if ( isset( $first_slot['start'] ) && $first_slot['start'] !== '' ) {
		$default_open = $first_slot['start'];
	}
}
$default_close = current_time( 'H:i' );
$default_date  = current_time( 'Y-m-d' );
?>

<div id="myd-caixa-panel" class="myd-caixa-panel">
<div class="myd-caixa-container">

	<div class="myd-caixa-header-bar">
		<span class="myd-caixa-header-title">Caixa</span>
		<button type="button" id="myd-caixa-x-btn" class="myd-caixa-x-btn">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="#999" stroke-width="2" stroke-linecap="round"/></svg>
		</button>
	</div>

	<div class="myd-caixa-content-scroll">

	<!-- ===== TELA 1: HOME — STATUS + BOTÕES ===== -->
	<div id="myd-caixa-home" class="myd-caixa-screen">
		<div class="myd-cashier-header">
			<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v2H4V6zm0 4h16v8a2 2 0 01-2 2H6a2 2 0 01-2-2v-8zm2 3a1 1 0 100 2h2a1 1 0 100-2H6zm5 0a1 1 0 100 2h2a1 1 0 100-2h-2zm5 0a1 1 0 100 2h2a1 1 0 100-2h-2zM7 2h10a1 1 0 011 1v2H6V3a1 1 0 011-1z" fill="#333"/></svg>
			<h2 class="myd-cashier-title">Caixa</h2>
		</div>

		<div id="myd-caixa-status" class="myd-caixa-status"></div>

		<div class="myd-caixa-buttons">
			<button type="button" id="myd-caixa-open-btn" class="myd-caixa-btn myd-caixa-btn-open">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M19 11H5m7-7v14" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/></svg>
				Abrir Caixa
			</button>
			<button type="button" id="myd-caixa-close-btn" class="myd-caixa-btn myd-caixa-btn-close" disabled>
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/></svg>
				Fechar Caixa
			</button>
		</div>

		<!-- Botões Retirada / Suprimento (só quando caixa aberto) -->
		<div id="myd-caixa-mov-buttons" class="myd-caixa-mov-buttons myd-hidden">
			<button type="button" id="myd-caixa-retirada-btn" class="myd-caixa-mov-btn myd-caixa-mov-retirada">
				📤 Retirada
			</button>
			<button type="button" id="myd-caixa-suprimento-btn" class="myd-caixa-mov-btn myd-caixa-mov-suprimento">
				📥 Suprimento
			</button>
		</div>

		<!-- Histórico de movimentações -->
		<div id="myd-caixa-mov-history" class="myd-hidden"></div>

		<!-- Histórico de fechamentos (API) -->
		<div id="myd-caixa-recent-history" class="myd-caixa-recent-history myd-hidden"></div>

		<!-- Botões extras: Buscar e Retroativo -->
		<div class="myd-caixa-extra-btns">
			<button type="button" id="myd-caixa-search-btn" class="myd-caixa-mov-btn myd-btn-search">
				🔍 Buscar Caixa
			</button>
			<button type="button" id="myd-caixa-retro-btn" class="myd-caixa-mov-btn myd-btn-retro">
				📅 Fechar Caixa Retroativo
			</button>
		</div>
	</div>

	<!-- ===== TELA 4: MOVIMENTAÇÃO (Retirada/Suprimento) ===== -->
	<div id="myd-caixa-mov-screen" class="myd-caixa-screen myd-hidden">
		<div class="myd-cashier-header">
			<h2 class="myd-cashier-title" id="myd-mov-title">📤 Retirada</h2>
		</div>
		<div class="myd-cashier-form">
			<div class="myd-cashier-field myd-field-sm">
				<label for="myd-mov-value">Valor (R$)</label>
				<input type="number" id="myd-mov-value" value="" min="0.01" step="0.01" placeholder="0,00" autofocus>
			</div>
			<div class="myd-cashier-field myd-field-md">
				<label for="myd-mov-reason">Motivo (opcional)</label>
				<input type="text" id="myd-mov-reason" value="" placeholder="Ex: Troco, pagamento fornecedor...">
			</div>
			<button type="button" id="myd-mov-confirm" class="myd-cashier-btn-generate myd-btn-mt">
				✅ Confirmar
			</button>
		</div>
	</div>

	<!-- ===== TELA 5: BUSCAR CAIXA (por ID ou Data) ===== -->
	<div id="myd-caixa-search-screen" class="myd-caixa-screen myd-hidden">
		<div class="myd-cashier-header">
			<h2 class="myd-cashier-title">🔍 Buscar Caixa</h2>
		</div>
		<div class="myd-cashier-form">
			<div class="myd-search-row">
				<div class="myd-cashier-field myd-field-flex">
					<label for="myd-search-id">Buscar por ID</label>
					<input type="number" id="myd-search-id" placeholder="Ex: 2430" min="1">
				</div>
				<div class="myd-search-or">ou</div>
				<div class="myd-cashier-field myd-field-flex">
					<label for="myd-search-date">Buscar por Data</label>
					<input type="date" id="myd-search-date">
				</div>
			</div>
			<button type="button" id="myd-search-confirm" class="myd-cashier-btn-generate">
				🔍 Buscar
			</button>
			<div id="myd-search-status" class="myd-status-msg"></div>
			<div id="myd-search-results" class="myd-search-results"></div>
		</div>
	</div>

	<!-- ===== TELA 6: FECHAR CAIXA RETROATIVO ===== -->
	<div id="myd-caixa-retro-screen" class="myd-caixa-screen myd-hidden">
		<div class="myd-cashier-header">
			<h2 class="myd-cashier-title">📅 Fechar Caixa Retroativo</h2>
		</div>
		<div class="myd-cashier-form">
			<p class="myd-retro-hint">Permite fechar um caixa de até 7 dias atrás. Preencha o período e os valores.</p>

			<div class="myd-flex-row">
				<div class="myd-cashier-field myd-field-flex">
					<label for="myd-retro-date">Data</label>
					<input type="date" id="myd-retro-date"
						max="<?php echo esc_attr($default_date); ?>"
						min="<?php echo esc_attr(date('Y-m-d', strtotime('-7 days'))); ?>">
				</div>
				<div class="myd-cashier-field myd-field-flex">
					<label for="myd-retro-open">Hora Abertura</label>
					<input type="time" id="myd-retro-open" value="<?php echo esc_attr($default_open); ?>">
				</div>
				<div class="myd-cashier-field myd-field-flex">
					<label for="myd-retro-close">Hora Fechamento</label>
					<input type="time" id="myd-retro-close" value="<?php echo esc_attr($default_close); ?>">
				</div>
			</div>

			<div class="myd-flex-row myd-flex-row-mt">
				<div class="myd-cashier-field myd-field-flex">
					<label for="myd-retro-initial">Dinheiro Inicial</label>
					<input type="text" id="myd-retro-initial" value="R$ 0,00" class="myd-input-money">
				</div>
				<div class="myd-cashier-field myd-field-flex">
					<label for="myd-retro-final">Dinheiro em Caixa</label>
					<input type="text" id="myd-retro-final" value="R$ 0,00" class="myd-input-money">
				</div>
			</div>

			<div class="myd-flex-row myd-flex-row-mt">
				<div class="myd-cashier-field myd-field-flex">
					<label for="myd-retro-ifood">Líquido iFood</label>
					<input type="text" id="myd-retro-ifood" value="R$ 0,00" class="myd-input-money">
				</div>
				<div class="myd-cashier-field myd-field-flex">
					<label for="myd-retro-motoboy">Taxa Motoboy</label>
					<input type="text" id="myd-retro-motoboy" value="R$ 0,00" class="myd-input-money">
				</div>
			</div>

			<button type="button" id="myd-retro-confirm" class="myd-caixa-btn-close-confirm myd-btn-mt">
				🔒 Fechar Caixa Retroativo
			</button>
			<div id="myd-retro-status" class="myd-status-msg"></div>
		</div>
	</div>

	<!-- ===== TELA 2: ABRIR CAIXA ===== -->
	<div id="myd-caixa-open-screen" class="myd-caixa-screen myd-hidden">
		<div class="myd-cashier-header">
			<h2 class="myd-cashier-title">💰 Abrir Caixa</h2>
		</div>
		<div class="myd-cashier-form">
			<div class="myd-cashier-field myd-field-open-cash">
				<label for="myd-caixa-initial-cash">Dinheiro em espécie no caixa</label>
				<input type="text" id="myd-caixa-initial-cash" value="R$ 0,00" placeholder="R$ 0,00" autofocus class="myd-input-open-cash">
			</div>
			<button type="button" id="myd-caixa-confirm-open" class="myd-cashier-btn-generate myd-btn-mt">
				✅ Confirmar Abertura
			</button>
		</div>
	</div>

	<!-- ===== TELA 3: FECHAR CAIXA ===== -->
	<div id="myd-caixa-close-screen" class="myd-caixa-screen myd-hidden">
		<div class="myd-cashier-header">
			<h2 class="myd-cashier-title">📋 Fechar Caixa</h2>
		</div>

		<div class="myd-cashier-form">
			<div class="myd-cashier-field myd-field-sm">
				<label for="myd-caixa-final-cash">Dinheiro em espécie no caixa agora</label>
				<input type="text" id="myd-caixa-final-cash" value="R$ 0,00" placeholder="R$ 0,00" autofocus class="myd-input-money">
			</div>
			<div class="myd-cashier-field myd-field-sm myd-field-mt">
				<label for="myd-caixa-ifood-liquid">Valor líquido do iFood</label>
				<input type="text" id="myd-caixa-ifood-liquid" value="R$ 0,00" placeholder="R$ 0,00" class="myd-input-money">
			</div>
			<div class="myd-cashier-field myd-field-sm myd-field-mt">
				<label for="myd-caixa-motoboy-fee">Taxas pagas para motoboy</label>
				<input type="text" id="myd-caixa-motoboy-fee" value="R$ 0,00" placeholder="R$ 0,00" class="myd-input-money">
			</div>
			<!-- Diferença em tempo real (aparece após gerar) -->
			<div id="myd-caixa-diff-display" class="myd-caixa-diff myd-hidden myd-field-mt"></div>

			<button type="button" id="myd-caixa-confirm-close" class="myd-caixa-btn-close-confirm myd-btn-mt">
				🔒 Fechar Caixa e Gerar Relatório
			</button>
		</div>

		<!-- Resultado -->
		<div id="myd-cashier-result" class="myd-cashier-result myd-hidden">
			<div class="myd-cashier-period" id="myd-cashier-period"></div>

			<!-- Diferença de caixa final -->
			<div id="myd-caixa-diff-result" class="myd-caixa-diff-result"></div>

			<h3 class="myd-cashier-section-title">Totais por Forma de Pagamento</h3>
			<div class="myd-cashier-payment-grid" id="myd-cashier-payments"></div>

			<h3 class="myd-cashier-section-title">Produtos Vendidos</h3>
			<div class="myd-cashier-table-wrap">
				<table class="myd-cashier-table">
					<thead>
						<tr><th class="myd-th-left">Produto</th><th>Qtd</th><th>Valor Unit.</th><th>Subtotal</th></tr>
					</thead>
					<tbody id="myd-cashier-products-body"></tbody>
				</table>
			</div>

			<div class="myd-cashier-actions">
				<button type="button" id="myd-cashier-print" class="myd-cashier-btn-print">🖨️ Imprimir</button>
			</div>
		</div>
	</div>

	</div> <!-- fechar .myd-caixa-content-scroll -->
</div>
</div>
