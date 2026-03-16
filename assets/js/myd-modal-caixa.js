/**
 * Caixa — Abertura e Fechamento Controller
 * Estado no Banco de Dados (WP REST API)
 */
(function () {
    'use strict';

    var NONCE = (typeof MYD_REST_NONCE !== 'undefined' && MYD_REST_NONCE)
        ? MYD_REST_NONCE
        : (typeof wpApiSettings !== 'undefined' && wpApiSettings.nonce ? wpApiSettings.nonce : '');

    var currentState = null;
    var lastReportData = null;

    document.addEventListener('DOMContentLoaded', function () {
        // Sidebar button - Abre como Popup Centralizado
        var btnCaixa = document.getElementById('myd-cashier-btn');
        if (btnCaixa) {
            btnCaixa.addEventListener('click', function (e) {
                e.preventDefault();
                showCaixaPanel();
            });
        }

        var btnClosePanel = document.getElementById('myd-caixa-x-btn');
        if (btnClosePanel) {
            btnClosePanel.addEventListener('click', function () {
                var panel = document.getElementById('myd-caixa-panel');
                if (panel) panel.style.display = 'none';
            });
        }

        // Botões
        var openBtn = document.getElementById('myd-caixa-open-btn');
        var closeBtn = document.getElementById('myd-caixa-close-btn');
        if (openBtn) openBtn.addEventListener('click', showOpenScreen);
        if (closeBtn) closeBtn.addEventListener('click', showCloseScreen);

        // Confirmar abertura
        var confirmOpen = document.getElementById('myd-caixa-confirm-open');
        if (confirmOpen) confirmOpen.addEventListener('click', confirmOpenCaixa);

        // Confirmar fechamento
        var confirmClose = document.getElementById('myd-caixa-confirm-close');
        if (confirmClose) confirmClose.addEventListener('click', doCloseCaixa);

        // Imprimir
        var printBtn = document.getElementById('myd-cashier-print');
        if (printBtn) printBtn.addEventListener('click', printCashierToLocalServer);

        // Movimentações (Retirada / Suprimento)
        var retiradaBtn = document.getElementById('myd-caixa-retirada-btn');
        var suprimentoBtn = document.getElementById('myd-caixa-suprimento-btn');
        if (retiradaBtn) retiradaBtn.addEventListener('click', function () { showMovScreen('retirada'); });
        if (suprimentoBtn) suprimentoBtn.addEventListener('click', function () { showMovScreen('suprimento'); });
        var movConfirm = document.getElementById('myd-mov-confirm');
        if (movConfirm) movConfirm.addEventListener('click', confirmMov);

        // Busca de caixa
        var searchBtn = document.getElementById('myd-caixa-search-btn');
        if (searchBtn) searchBtn.addEventListener('click', function () { showScreen('myd-caixa-search-screen'); });
        var searchConfirm = document.getElementById('myd-search-confirm');
        if (searchConfirm) searchConfirm.addEventListener('click', doSearchCaixa);

        // Fechar caixa retroativo
        var retroBtn = document.getElementById('myd-caixa-retro-btn');
        if (retroBtn) retroBtn.addEventListener('click', showRetroScreen);
        var retroConfirm = document.getElementById('myd-retro-confirm');
        if (retroConfirm) retroConfirm.addEventListener('click', doRetroClose);

        // Máscara de moeda no input de dinheiro inicial
        var cashInput = document.getElementById('myd-caixa-initial-cash');
        if (cashInput) {
            cashInput.addEventListener('input', function () {
                var raw = this.value.replace(/\D/g, '');
                var num = parseInt(raw, 10) || 0;
                var formatted = (num / 100).toFixed(2).replace('.', ',');
                formatted = formatted.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                this.value = 'R$ ' + formatted;
            });
            cashInput.addEventListener('focus', function () {
                var len = this.value.length;
                this.setSelectionRange(len, len);
            });
        }

        // Máscara de moeda nos inputs do fechamento
        var closeMaskedInputs = ['myd-caixa-final-cash', 'myd-caixa-ifood-liquid', 'myd-caixa-motoboy-fee',
            'myd-retro-initial', 'myd-retro-final', 'myd-retro-ifood', 'myd-retro-motoboy'];
        closeMaskedInputs.forEach(function (inputId) {
            var el = document.getElementById(inputId);
            if (el) {
                el.addEventListener('input', function () {
                    var raw = this.value.replace(/\D/g, '');
                    var num = parseInt(raw, 10) || 0;
                    var formatted = (num / 100).toFixed(2).replace('.', ',');
                    formatted = formatted.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    this.value = 'R$ ' + formatted;
                });
                el.addEventListener('focus', function () {
                    var len = this.value.length;
                    this.setSelectionRange(len, len);
                });
            }
        });

        fetchStateFromDB();
    });

    // ─── API ─────────────────────────────────────────
    function fetchStateFromDB() {
        var topBtnStatus = document.getElementById('myd-cashier-btn-status');
        if (topBtnStatus) topBtnStatus.textContent = '...';

        fetch('/wp-json/myd-delivery/v1/caixa/status', {
            method: 'GET',
            headers: { 'X-WP-Nonce': NONCE }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                currentState = data;
                updateHomeState();
            })
            .catch(function (err) {
                console.error('Erro ao buscar status do caixa', err);
            });
    }

    // ─── Navegação ──────────────────────────────────
    function showCaixaPanel() {
        var panel = document.getElementById('myd-caixa-panel');
        if (!panel) return;
        panel.style.display = 'flex';
        showScreen('myd-caixa-home');
        // Re-fetch para garantir dados atualizados ao abrir o popup
        fetchStateFromDB();
    }

    function showScreen(id) {
        var screens = document.querySelectorAll('.myd-caixa-screen');
        for (var i = 0; i < screens.length; i++) {
            screens[i].style.display = 'none';
            screens[i].classList.add('myd-hidden');
        }
        var target = document.getElementById(id);
        if (target) {
            target.classList.remove('myd-hidden');
            target.style.display = 'block';
        }
    }

    function showOpenScreen() {
        showScreen('myd-caixa-open-screen');
        var input = document.getElementById('myd-caixa-initial-cash');
        if (input) { input.value = 'R$ 0,00'; input.focus(); }
    }

    function showCloseScreen() {
        showScreen('myd-caixa-close-screen');
        var result = document.getElementById('myd-cashier-result');
        var loading = document.getElementById('myd-cashier-loading');
        if (result) result.classList.add('myd-hidden');
        if (loading) loading.classList.add('myd-hidden');
        lastReportData = null;
        var diffEl = document.getElementById('myd-caixa-diff-display');
        if (diffEl) diffEl.style.display = 'none';
        var finalInput = document.getElementById('myd-caixa-final-cash');
        if (finalInput) { finalInput.value = 'R$ 0,00'; finalInput.focus(); }
        var ifoodInput = document.getElementById('myd-caixa-ifood-liquid');
        if (ifoodInput) ifoodInput.value = 'R$ 0,00';
        var motoboyInput = document.getElementById('myd-caixa-motoboy-fee');
        if (motoboyInput) motoboyInput.value = 'R$ 0,00';
        var form = document.querySelector('#myd-caixa-close-screen .myd-cashier-form');
        if (form) form.style.display = 'block';
        var confirmBtn = document.getElementById('myd-caixa-confirm-close');
        if (confirmBtn) { confirmBtn.disabled = false; confirmBtn.style.opacity = '1'; confirmBtn.textContent = 'Confirmar Fechamento'; }
    }

    // ─── Estado da Interface ────────────────────────
    function updateHomeState() {
        var statusEl = document.getElementById('myd-caixa-status');
        var openBtn = document.getElementById('myd-caixa-open-btn');
        var closeBtn = document.getElementById('myd-caixa-close-btn');
        var movBtns = document.getElementById('myd-caixa-mov-buttons');
        var movHistory = document.getElementById('myd-caixa-mov-history');
        var topBtnStatus = document.getElementById('myd-cashier-btn-status');

        if (currentState && currentState.status === 'open') {
            if (topBtnStatus) { topBtnStatus.textContent = 'Aberto'; topBtnStatus.style.color = '#4caf50'; }

            var d = new Date(currentState.openingTime * 1000);
            var dateFormatted = ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear();
            var timeFormatted = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);

            var movTotal = calcMovTotal(currentState);
            var saldoAtual = (currentState.initialValue || 0) + movTotal;

            if (statusEl) {
                statusEl.className = 'myd-caixa-status myd-caixa-status-open';
                statusEl.innerHTML = '<span class="myd-caixa-status-dot myd-caixa-dot-open"></span>'
                    + 'Caixa aberto desde <strong>' + dateFormatted + ' às ' + timeFormatted + '</strong>'
                    + ' — Inicial: <strong>R$ ' + formatMoney(currentState.initialValue || 0) + '</strong>'
                    + (movTotal !== 0 ? ' — Saldo atual: <strong>R$ ' + formatMoney(saldoAtual) + '</strong>' : '');
            }
            if (openBtn) { openBtn.disabled = true; openBtn.style.opacity = '0.4'; }
            if (closeBtn) { closeBtn.disabled = false; closeBtn.style.opacity = '1'; }
            if (movBtns) movBtns.classList.remove('myd-hidden');
            renderMovHistory(currentState, movHistory);
        } else {
            if (topBtnStatus) { topBtnStatus.textContent = 'Fechado'; topBtnStatus.style.color = '#888'; }
            if (statusEl) {
                statusEl.className = 'myd-caixa-status myd-caixa-status-closed';
                statusEl.innerHTML = '<span class="myd-caixa-status-dot myd-caixa-dot-closed"></span>Caixa fechado';
            }
            if (openBtn) { openBtn.disabled = false; openBtn.style.opacity = '1'; }
            if (closeBtn) { closeBtn.disabled = true; closeBtn.style.opacity = '0.4'; }
            if (movBtns) movBtns.classList.add('myd-hidden');
            if (movHistory) movHistory.innerHTML = '';
        }

        var recentHistoryEl = document.getElementById('myd-caixa-recent-history');
        if (recentHistoryEl) {
            renderRecentHistory(currentState, recentHistoryEl);
        }
    }

    function calcMovTotal(state) {
        var movs = (state && state.movements) ? state.movements : [];
        var t = 0;
        for (var i = 0; i < movs.length; i++) {
            t += movs[i].type === 'suprimento' ? parseFloat(movs[i].amount) : -parseFloat(movs[i].amount);
        }
        return t;
    }

    function renderMovHistory(state, el) {
        var movs = (state && state.movements) ? state.movements : [];
        if (!el) return;
        if (movs.length === 0) { el.classList.add('myd-hidden'); return; }
        el.classList.remove('myd-hidden');
        var html = '<h3 class="myd-cashier-section-title" style="margin-top:16px;">Movimentações</h3>';
        html += '<div class="myd-caixa-mov-list">';
        // Mostrar os mais recentes primeiro
        for (var i = movs.length - 1; i >= 0; i--) {
            var m = movs[i];
            var isRet = m.type === 'retirada';
            var d = new Date(m.timestamp * 1000);
            var tStr = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
            html += '<div class="myd-caixa-mov-item ' + (isRet ? 'myd-mov-ret' : 'myd-mov-sup') + '">'
                + '<span>' + (isRet ? '📤' : '📥') + ' ' + (isRet ? 'Retirada' : 'Suprimento') + '</span>'
                + '<span>' + (m.description || '') + '</span>'
                + '<span class="myd-mov-time">' + tStr + '</span>'
                + '<span class="myd-mov-val ' + (isRet ? 'myd-mov-neg' : 'myd-mov-pos') + '">' + (isRet ? '- ' : '+ ') + 'R$ ' + formatMoney(m.amount) + '</span>'
                + '</div>';
        }
        html += '</div>';
        el.innerHTML = html;
    }

    function renderRecentHistory(state, el) {
        var hist = (state && state.history) ? state.history : [];
        if (!el) return;
        if (hist.length === 0) {
            el.classList.add('myd-hidden');
            return;
        }

        el.classList.remove('myd-hidden');
        var html = '<h3 class="myd-cashier-section-title">Últimos Caixas Fechados</h3>';
        html += '<div class="myd-caixa-recent-list">';

        for (var i = 0; i < hist.length; i++) {
            var h = hist[i];

            var dOpen = new Date(h.openingTime * 1000);
            var dateStr = ('0' + dOpen.getDate()).slice(-2) + '/' + ('0' + (dOpen.getMonth() + 1)).slice(-2);
            var timeOpenStr = ('0' + dOpen.getHours()).slice(-2) + ':' + ('0' + dOpen.getMinutes()).slice(-2);

            var timeCloseStr = '...';
            if (h.closingTime) {
                var dClose = new Date(h.closingTime * 1000);
                timeCloseStr = ('0' + dClose.getHours()).slice(-2) + ':' + ('0' + dClose.getMinutes()).slice(-2);
            }

            var diffVal = 0;
            if (h.closureData && h.closureData.finalCash !== undefined) {
                diffVal = parseFloat(h.closureData.finalCash);
            }

            var diffClass = diffVal > 0 ? 'myd-recent-pos' : 'myd-recent-zero';
            var diffSign = '';

            // Guardar o H no dataset como string pra restaurar no clique!
            var hJson = encodeURIComponent(JSON.stringify(h));

            html += '<div class="myd-caixa-recent-item" onclick="window.mydViewOldCaixa(decodeURIComponent(\'' + hJson + '\'))" style="cursor: pointer;" title="Clique para Visualizar Extrato deste Caixa">';
            html += '  <div class="myd-caixa-recent-icon">📦</div>';
            html += '  <div class="myd-caixa-recent-info">';
            html += '    <strong>' + dateStr + '</strong> (' + timeOpenStr + ' - ' + timeCloseStr + ')';
            html += '    <span>Caixa #' + h.id + '</span>';
            html += '  </div>';
            html += '  <div class="myd-caixa-recent-diff ' + diffClass + '">Em caixa: R$ ' + formatMoney(diffVal) + '</div>';
            html += '</div>';

        }

        html += '</div>';
        el.innerHTML = html;
    }

    // Função Exposta Globalmente (para poder ser chamada do onClick no HTML)
    window.mydViewOldCaixa = function (hStr) {
        var h = JSON.parse(hStr);
        if (!h || !h.closureData) {
            if (window.MydGlobalNotify) window.MydGlobalNotify('warning', 'Aviso', 'Este caixa antigo não salvou relatório de fechamento para visualização.');
            return;
        }

        var dOpen = new Date(h.openingTime * 1000);
        var openDate = dOpen.getFullYear() + '-' + ('0' + (dOpen.getMonth() + 1)).slice(-2) + '-' + ('0' + dOpen.getDate()).slice(-2);
        var openTime = ('0' + dOpen.getHours()).slice(-2) + ':' + ('0' + dOpen.getMinutes()).slice(-2);

        var dClose = new Date(h.closingTime * 1000);
        var closeTime = ('0' + dClose.getHours()).slice(-2) + ':' + ('0' + dClose.getMinutes()).slice(-2);

        // Limpar o DOM do Extrato Resultante anterior (se tiver algo sujo)
        var resEl = document.getElementById('myd-cashier-result');
        if (resEl) {
            resEl.classList.remove('myd-hidden');
            resEl.style.display = 'block';
        }

        // Esconder o form de fechar (pois isso é só uma leitura do passado)
        var form = document.querySelector('#myd-caixa-close-screen .myd-cashier-form');
        if (form) form.style.display = 'none';

        var rData = h.closureData.report || {};
        rData.ifoodLiquid = parseFloat(h.closureData.ifoodLiquid) || 0;
        rData.motoboyFee = parseFloat(h.closureData.motoboyFee) || 0;
        var initCash = parseFloat(h.closureData.initialCash) || 0;
        var finCash = parseFloat(h.closureData.finalCash) || 0;

        // Pulas pra tela de Fechamento mas só joga o preview pronto nele
        showScreen('myd-caixa-close-screen');

        // Restaura as listas da nota final (mesma lógica do fechamento real)
        renderReport(rData, openDate, openTime, closeTime);
        renderDiffResult(initCash, finCash, rData, h.movements || []);
    };

    // ─── Abrir Caixa ────────────────────────────────
    function confirmOpenCaixa() {
        var input = document.getElementById('myd-caixa-initial-cash');
        var val = input ? parseMaskedMoney(input.value) : 0;
        var btn = document.getElementById('myd-caixa-confirm-open');

        if (btn) { btn.disabled = true; btn.textContent = 'Abertura...'; }

        fetch('/wp-json/myd-delivery/v1/caixa/open', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': NONCE,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ initialValue: val })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    if (window.MydGlobalNotify) window.MydGlobalNotify('success', 'Caixa Aberto', 'Caixa aberto com R$ ' + formatMoney(val));
                    fetchStateFromDB();
                    showScreen('myd-caixa-home');
                } else {
                    if (window.MydGlobalNotify) window.MydGlobalNotify('error', 'Erro', data.error || 'Erro ao abrir caixa');
                }
            })
            .catch(function (err) {
                if (window.MydGlobalNotify) window.MydGlobalNotify('error', 'Erro', 'Falha na conexão.');
            })
            .finally(function () {
                if (btn) { btn.disabled = false; btn.textContent = 'Confirmar Abertura'; }
            });
    }

    // ─── Movimentações (Retirada / Suprimento) ──────
    var _currentMovType = 'retirada';

    function showMovScreen(type) {
        _currentMovType = type;
        showScreen('myd-caixa-mov-screen');
        var title = document.getElementById('myd-mov-title');
        if (title) title.textContent = type === 'retirada' ? '📤 Retirada' : '📥 Suprimento';
        var valInput = document.getElementById('myd-mov-value');
        var reasonInput = document.getElementById('myd-mov-reason');
        if (valInput) { valInput.value = ''; valInput.focus(); }
        if (reasonInput) reasonInput.value = '';
    }

    function confirmMov() {
        if (!currentState || currentState.status !== 'open') return;

        var val = parseFloat(document.getElementById('myd-mov-value').value) || 0;
        if (val <= 0) return;
        var reason = (document.getElementById('myd-mov-reason').value || '').trim();

        var btn = document.getElementById('myd-mov-confirm');
        if (btn) { btn.disabled = true; btn.textContent = 'Registrando...'; }

        fetch('/wp-json/myd-delivery/v1/caixa/movement', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': NONCE,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                caixa_id: currentState.caixa_id,
                type: _currentMovType,
                amount: val,
                description: reason
            })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    currentState.movements = data.movements;
                    showScreen('myd-caixa-home');
                    updateHomeState();
                    var label = _currentMovType === 'retirada' ? 'Retirada' : 'Suprimento';
                    if (window.MydGlobalNotify) window.MydGlobalNotify('success', label, label + ' de R$ ' + formatMoney(val) + ' registrada');
                } else {
                    if (window.MydGlobalNotify) window.MydGlobalNotify('error', 'Erro', data.error || 'Erro ao registrar movimento');
                }
            })
            .catch(function (err) {
                if (window.MydGlobalNotify) window.MydGlobalNotify('error', 'Erro', 'Falha na conexão.');
            })
            .finally(function () {
                if (btn) { btn.disabled = false; btn.textContent = 'Confirmar'; }
            });
    }

    // ─── Fechar Caixa ───────────────────────────────
    function doCloseCaixa() {
        if (!currentState || currentState.status !== 'open') return;

        var confirmBtn = document.getElementById('myd-caixa-confirm-close');
        var loadingEl = document.getElementById('myd-cashier-loading');
        var resultEl = document.getElementById('myd-cashier-result');
        var finalCash = parseMaskedMoney(document.getElementById('myd-caixa-final-cash').value);
        var initialCash = currentState.initialValue || 0;

        var ifoodInput = document.getElementById('myd-caixa-ifood-liquid');
        var ifoodLiquid = ifoodInput ? parseMaskedMoney(ifoodInput.value) : 0;

        var motoboyInput = document.getElementById('myd-caixa-motoboy-fee');
        var motoboyFee = motoboyInput ? parseMaskedMoney(motoboyInput.value) : 0;

        var d = new Date(currentState.openingTime * 1000);
        var openDate = d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
        var openTime = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);

        var now = new Date();
        var closeTime = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2);


        if (resultEl) resultEl.classList.add('myd-hidden');
        if (loadingEl) loadingEl.classList.remove('myd-hidden');
        if (confirmBtn) { confirmBtn.disabled = true; confirmBtn.style.opacity = '0.6'; confirmBtn.textContent = 'Processando...'; }

        // Mantenha cópia em memória pois o currentState será limpo após fechar
        var closingMov = (currentState.movements || []).slice();

        // 1. Buscar pedidos do período para bater os valores
        var url = '/wp-json/myd-delivery/v1/cashier/report?open_time=' + encodeURIComponent(openTime)
            + '&close_time=' + encodeURIComponent(closeTime)
            + '&delivery_cost=0'
            + '&date=' + encodeURIComponent(openDate);

        fetch(url, {
            method: 'GET',
            headers: { 'X-WP-Nonce': NONCE }
        })
            .then(function (r) { return r.json(); })
            .then(function (reportData) {
                lastReportData = reportData;
                reportData.ifoodLiquid = ifoodLiquid;
                reportData.motoboyFee = motoboyFee;

                // 2. Enviar POST definitivo para fechar no BD
                return fetch('/wp-json/myd-delivery/v1/caixa/close', {
                    method: 'POST',
                    headers: {
                        'X-WP-Nonce': NONCE,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        caixa_id: currentState.caixa_id,
                        closureData: {
                            report: reportData,
                            finalCash: finalCash,
                            initialCash: initialCash,
                            ifoodLiquid: ifoodLiquid,
                            motoboyFee: motoboyFee
                        }
                    })
                }).then(function (resClose) { return resClose.json(); }).then(function (closeData) {
                    if (closeData.success) {
                        renderReport(reportData, openDate, openTime, closeTime);
                        renderDiffResult(initialCash, finalCash, reportData, closingMov);

                        var form = document.querySelector('#myd-caixa-close-screen .myd-cashier-form');
                        if (form) form.style.display = 'none';

                        // Caixa fechado
                        currentState = { status: 'closed' };
                        updateHomeState();
                    } else {
                        throw new Error(closeData.error || 'Falha ao registrar fechamento no banco');
                    }
                });
            })
            .catch(function (err) {
                console.error('Erro ao fechar caixa:', err);
                if (window.MydGlobalNotify) {
                    window.MydGlobalNotify('error', 'Erro', err.message || 'Não foi possível fechar o caixa.');
                }
            })
            .finally(function () {
                if (loadingEl) loadingEl.classList.add('myd-hidden');
                if (confirmBtn) { confirmBtn.disabled = true; confirmBtn.textContent = 'Caixa Fechado'; }
            });
    }

    // ─── Buscar Caixa (por ID ou Data) ──────────────
    function doSearchCaixa() {
        var idInput = document.getElementById('myd-search-id');
        var dateInput = document.getElementById('myd-search-date');
        var statusEl = document.getElementById('myd-search-status');
        var resultsEl = document.getElementById('myd-search-results');

        var id = idInput ? (idInput.value || '').trim() : '';
        var date = dateInput ? (dateInput.value || '').trim() : '';

        if (!id && !date) {
            if (statusEl) { statusEl.style.color = '#c00'; statusEl.textContent = 'Informe um ID ou uma data.'; }
            return;
        }
        if (statusEl) { statusEl.style.color = '#666'; statusEl.textContent = 'Buscando...'; }
        if (resultsEl) resultsEl.innerHTML = '';

        var url = '/wp-json/myd-delivery/v1/caixa/search?';
        if (id) url += 'id=' + encodeURIComponent(id);
        else url += 'date=' + encodeURIComponent(date);

        fetch(url, { method: 'GET', headers: { 'X-WP-Nonce': NONCE } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) { if (statusEl) { statusEl.style.color = '#c00'; statusEl.textContent = data.error; } return; }
                var results = data.results || [];
                if (statusEl) statusEl.textContent = results.length + ' caixa(s) encontrado(s).';
                if (!resultsEl) return;
                var html = '<div class="myd-caixa-recent-list">';
                for (var i = 0; i < results.length; i++) {
                    var h = results[i];
                    var dOpen = new Date(h.openingTime * 1000);
                    var dateStr = ('0' + dOpen.getDate()).slice(-2) + '/' + ('0' + (dOpen.getMonth() + 1)).slice(-2) + '/' + dOpen.getFullYear();
                    var timeOpen = ('0' + dOpen.getHours()).slice(-2) + ':' + ('0' + dOpen.getMinutes()).slice(-2);
                    var timeClose = '...';
                    if (h.closingTime) {
                        var dClose = new Date(h.closingTime * 1000);
                        timeClose = ('0' + dClose.getHours()).slice(-2) + ':' + ('0' + dClose.getMinutes()).slice(-2);
                    }
                    var finalCash = (h.closureData && h.closureData.finalCash !== undefined) ? parseFloat(h.closureData.finalCash) : null;
                    var hJson = encodeURIComponent(JSON.stringify(h));
                    html += '<div class="myd-caixa-recent-item" onclick="window.mydViewOldCaixa(decodeURIComponent(\'' + hJson + '\'))" style="cursor:pointer;" title="Ver extrato">';
                    html += '  <div class="myd-caixa-recent-icon">📦</div>';
                    html += '  <div class="myd-caixa-recent-info">';
                    html += '    <strong>' + dateStr + '</strong> (' + timeOpen + ' - ' + timeClose + ')';
                    html += '    <span>Caixa #' + h.id + ' — ' + (h.status === 'open' ? '🟢 Aberto' : '🔒 Fechado') + '</span>';
                    html += '  </div>';
                    if (finalCash !== null) {
                        html += '  <div class="myd-caixa-recent-diff myd-recent-pos">R$ ' + formatMoney(finalCash) + '</div>';
                    }
                    html += '</div>';
                }
                html += '</div>';
                resultsEl.innerHTML = html;
            })
            .catch(function () {
                if (statusEl) { statusEl.style.color = '#c00'; statusEl.textContent = 'Erro de conexão.'; }
            });
    }

    // ─── Fechar Caixa Retroativo ────────────────────
    function showRetroScreen() {
        showScreen('myd-caixa-retro-screen');
        var statusEl = document.getElementById('myd-retro-status');
        if (statusEl) statusEl.textContent = '';
        var btn = document.getElementById('myd-retro-confirm');
        if (btn) { btn.disabled = false; btn.textContent = '🔒 Fechar Caixa Retroativo'; }
    }

    function doRetroClose() {
        var dateEl = document.getElementById('myd-retro-date');
        var openEl = document.getElementById('myd-retro-open');
        var closeEl = document.getElementById('myd-retro-close');
        var initEl = document.getElementById('myd-retro-initial');
        var finalEl = document.getElementById('myd-retro-final');
        var ifoodEl = document.getElementById('myd-retro-ifood');
        var motoboyEl = document.getElementById('myd-retro-motoboy');
        var statusEl = document.getElementById('myd-retro-status');
        var btn = document.getElementById('myd-retro-confirm');

        var date = dateEl ? dateEl.value.trim() : '';
        var openTime = openEl ? openEl.value.trim() : '';
        var closeTime = closeEl ? closeEl.value.trim() : '';

        if (!date) {
            if (statusEl) { statusEl.style.color = '#c00'; statusEl.textContent = 'Informe a data.'; } return;
        }
        if (!openTime || !closeTime) {
            if (statusEl) { statusEl.style.color = '#c00'; statusEl.textContent = 'Informe os horários.'; } return;
        }

        // Validação frontend: máx 7 dias
        var today = new Date(); today.setHours(0, 0, 0, 0);
        var chosen = new Date(date + 'T00:00:00');
        var maxPast = new Date(today); maxPast.setDate(today.getDate() - 7);
        if (chosen < maxPast) {
            if (statusEl) { statusEl.style.color = '#c00'; statusEl.textContent = 'Data muito antiga (máx 7 dias).'; } return;
        }
        if (chosen > today) {
            if (statusEl) { statusEl.style.color = '#c00'; statusEl.textContent = 'A data não pode ser no futuro.'; } return;
        }

        var initial = parseMaskedMoney(initEl ? initEl.value : '0');
        var finalCash = parseMaskedMoney(finalEl ? finalEl.value : '0');
        var ifoodLiquid = parseMaskedMoney(ifoodEl ? ifoodEl.value : '0');
        var motoboyFee = parseMaskedMoney(motoboyEl ? motoboyEl.value : '0');

        if (btn) { btn.disabled = true; btn.textContent = 'Processando...'; }
        if (statusEl) { statusEl.style.color = '#666'; statusEl.textContent = 'Aguarde...'; }

        fetch('/wp-json/myd-delivery/v1/caixa/retro-close', {
            method: 'POST',
            headers: { 'X-WP-Nonce': NONCE, 'Content-Type': 'application/json' },
            body: JSON.stringify({
                date: date, open_time: openTime, close_time: closeTime,
                initialValue: initial, finalCash: finalCash,
                ifoodLiquid: ifoodLiquid, motoboyFee: motoboyFee
            })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    if (statusEl) { statusEl.style.color = '#c00'; statusEl.textContent = data.error || 'Erro.'; }
                    if (btn) { btn.disabled = false; btn.textContent = '🔒 Fechar Caixa Retroativo'; }
                    return;
                }
                if (statusEl) { statusEl.style.color = 'green'; statusEl.textContent = '✅ Caixa retroativo #' + data.caixa_id + ' fechado!'; }
                // Exibe relatório usando tela de fechamento
                var rData = data.reportData || {};
                rData.ifoodLiquid = ifoodLiquid;
                rData.motoboyFee = motoboyFee;
                // Navega pra tela de fechamento mostrando o relatório
                showScreen('myd-caixa-close-screen');
                var form = document.querySelector('#myd-caixa-close-screen .myd-cashier-form');
                if (form) form.style.display = 'none';
                renderReport(rData, date, openTime, closeTime);
                renderDiffResult(initial, finalCash, rData, []);
                // Atualiza histórico
                fetchStateFromDB();
            })
            .catch(function () {
                if (statusEl) { statusEl.style.color = '#c00'; statusEl.textContent = 'Erro de conexão.'; }
                if (btn) { btn.disabled = false; btn.textContent = '🔒 Fechar Caixa Retroativo'; }
            });
    }

    // ─── Renderizar diferença no resultado ──────────
    function renderDiffResult(initialCash, finalCash, data, movements) {
        var diffResultEl = document.getElementById('myd-caixa-diff-result');
        if (!diffResultEl) return;

        var cashSales = data && data.payment_totals ? parseMoney(data.payment_totals['Dinheiro'] || '0') : 0;
        var movs = movements || [];
        var totalRetiradas = 0, totalSuprimentos = 0;
        for (var i = 0; i < movs.length; i++) {
            if (movs[i].type === 'retirada') totalRetiradas += parseFloat(movs[i].amount);
            else totalSuprimentos += parseFloat(movs[i].amount);
        }
        var movNet = totalSuprimentos - totalRetiradas;
        var expected = initialCash + cashSales + movNet;
        var diff = finalCash - expected;

        var html = '<div class="myd-caixa-diff-summary">';
        html += '<div>Dinheiro inicial: <strong>R$ ' + formatMoney(initialCash) + '</strong></div>';
        html += '<div>Vendas em dinheiro: <strong>R$ ' + formatMoney(cashSales) + '</strong></div>';
        if (totalSuprimentos > 0) html += '<div>Suprimentos: <strong class="myd-mov-pos">+ R$ ' + formatMoney(totalSuprimentos) + '</strong></div>';
        if (totalRetiradas > 0) html += '<div>Retiradas: <strong class="myd-mov-neg">- R$ ' + formatMoney(totalRetiradas) + '</strong></div>';
        html += '<div>Esperado: <strong>R$ ' + formatMoney(expected) + '</strong></div>';
        html += '<div>Em caixa: <strong>R$ ' + formatMoney(finalCash) + '</strong></div>';
        html += '</div>';

        if (movs.length > 0) {
            html += '<div class="myd-caixa-mov-list" style="margin:8px 0;">';
            for (var j = 0; j < movs.length; j++) {
                var m = movs[j];
                var isRet = m.type === 'retirada';
                var d = new Date(m.timestamp * 1000);
                var tStr = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);

                html += '<div class="myd-caixa-mov-item ' + (isRet ? 'myd-mov-ret' : 'myd-mov-sup') + '">'
                    + '<span>' + (isRet ? '📤' : '📥') + ' ' + (isRet ? 'Retirada' : 'Suprimento') + '</span>'
                    + '<span>' + (m.description || '') + '</span>'
                    + '<span class="myd-mov-time">' + tStr + '</span>'
                    + '<span class="myd-mov-val ' + (isRet ? 'myd-mov-neg' : 'myd-mov-pos') + '">' + (isRet ? '- ' : '+ ') + 'R$ ' + formatMoney(m.amount) + '</span>'
                    + '</div>';
            }
            html += '</div>';
        }

        if (Math.abs(diff) < 0.01) {
            html += '<div class="myd-caixa-diff myd-caixa-diff-ok">Caixa fechou sem diferença</div>';
        } else if (diff > 0) {
            html += '<div class="myd-caixa-diff myd-caixa-diff-more">Caixa fechou com dinheiro a mais (R$ ' + formatMoney(diff) + ')</div>';
        } else {
            html += '<div class="myd-caixa-diff myd-caixa-diff-less">Caixa fechou com dinheiro a menos (R$ ' + formatMoney(Math.abs(diff)) + ')</div>';
        }

        diffResultEl.innerHTML = html;
    }

    // ─── Renderizar Relatório ───────────────────────
    function renderReport(data, openDate, openTime, closeTime) {
        var resultEl = document.getElementById('myd-cashier-result');
        if (!resultEl) return;

        var periodEl = document.getElementById('myd-cashier-period');
        if (periodEl) {
            var dateParts = (openDate || '').split('-');
            var dateFormatted = dateParts.length === 3 ? dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0] : openDate;
            var now = new Date();
            var todayFormatted = ('0' + now.getDate()).slice(-2) + '/' + ('0' + (now.getMonth() + 1)).slice(-2) + '/' + now.getFullYear();
            periodEl.innerHTML =
                '<span class="myd-cashier-period-open">Abertura: <strong>' + dateFormatted + ' - ' + openTime + 'h</strong></span>' +
                '<span class="myd-cashier-period-sep">|</span>' +
                '<span class="myd-cashier-period-close">Fechamento: <strong>' + todayFormatted + ' - ' + closeTime + 'h</strong></span>';
        }

        var countEl = document.getElementById('myd-cashier-order-count');
        var totalEl = document.getElementById('myd-cashier-total');
        var delEl = document.getElementById('myd-cashier-delivery-val');

        var ifoodLiqTop = parseFloat(data.ifoodLiquid) || 0;
        var brutoRawTop = parseFloat(data.total_raw || 0);

        if (countEl) countEl.textContent = data.order_count || 0;
        if (totalEl) totalEl.textContent = 'R$ ' + formatMoney(brutoRawTop + ifoodLiqTop);
        if (delEl) delEl.textContent = 'R$ ' + (data.delivery_cost || '0,00');

        var paymentsEl = document.getElementById('myd-cashier-payments');
        if (paymentsEl && data.payment_totals) {
            var icons = { 'PIX (Online)': '💠', 'Crédito': '💳', 'Débito': '💲', 'VR': '🍽️', 'Dinheiro': '💵', 'Cartão (Online)': '🌐' };
            var html = '';
            for (var key in data.payment_totals) {
                if (!data.payment_totals.hasOwnProperty(key)) continue;
                html += '<div class="myd-cashier-payment-item"><span class="myd-cashier-payment-label">' + (icons[key] || '💰') + ' ' + key + '</span><span class="myd-cashier-payment-value">R$ ' + data.payment_totals[key] + '</span></div>';
            }
            paymentsEl.innerHTML = html;
        }

        var tbody = document.getElementById('myd-cashier-products-body');
        if (tbody && data.products) {
            var rows = '';
            if (data.products.length === 0) {
                rows = '<tr><td colspan="4" class="myd-td-empty">Nenhum produto encontrado</td></tr>';
            } else {
                var sumProducts = 0;
                for (var i = 0; i < data.products.length; i++) {
                    var p = data.products[i];
                    sumProducts += parseMoney(p.subtotal);
                    rows += '<tr><td class="myd-th-left">' + escapeHtml(p.name) + '</td><td>' + p.qty + '</td><td>R$ ' + p.unit_price + '</td><td>R$ ' + p.subtotal + '</td></tr>';
                }
                rows += '<tr class="myd-cashier-total-row"><td colspan="3" class="myd-td-total-label">Total dos Produtos</td><td class="myd-td-total-val">R$ ' + formatMoney(sumProducts) + '</td></tr>';
            }
            tbody.innerHTML = rows;
        }

        // --- Injecão das Taxas e Extras Visualmente ao Fim ---
        var tableWrap = document.querySelector('.myd-cashier-table-wrap');
        var feesWrap = document.getElementById('myd-cashier-fees-wrap');
        var liquidoWrap = document.getElementById('myd-cashier-liquido-wrap');
        // Limpa se já existir pra não duplicar em re-renderizações
        if (feesWrap) feesWrap.remove();
        if (liquidoWrap) liquidoWrap.remove();

        // ── Tabela 1: VALOR BRUTO ──────────────────────────────────────────
        var feesHtml = '<div id="myd-cashier-fees-wrap" class="myd-fees-wrap">';
        feesHtml += '<div class="myd-fees-title">Valor Bruto</div>';
        feesHtml += '<table class="myd-cashier-table">';
        feesHtml += '<tbody>';

        var _sumProdStr = typeof sumProducts !== 'undefined' ? sumProducts : 0;
        feesHtml += '<tr><td colspan="3" class="myd-td-label">Produtos Vendidos</td><td id="myd-cashier-products-val" class="myd-td-val">R$ ' + formatMoney(_sumProdStr) + '</td></tr>';
        feesHtml += '<tr><td colspan="3" class="myd-td-label">Taxas Entregas</td><td id="myd-cashier-delivery-val" class="myd-td-val">R$ ' + (data.total_delivery_fee || '0,00') + '</td></tr>';
        feesHtml += '<tr><td colspan="3" class="myd-td-label">Produtos Adicionais</td><td id="myd-cashier-extras-val" class="myd-td-val">R$ ' + (data.total_extras_fee || '0,00') + '</td></tr>';

        var ifoodLiquid = parseFloat(data.ifoodLiquid) || 0;
        var motoboyFee = parseFloat(data.motoboyFee) || 0;

        feesHtml += '<tr><td colspan="3" class="myd-td-label">Valor líquido iFood</td><td id="myd-cashier-ifood-val" class="myd-td-val">R$ ' + formatMoney(ifoodLiquid) + '</td></tr>';

        // Descontos
        var cpnRaw = parseFloat(data.total_coupon_discount_raw || 0);
        var fdlRaw = parseFloat(data.total_fidelity_discount_raw || 0);

        feesHtml += '<tr><td colspan="3" class="myd-td-label">Descontos de Cupom</td><td id="myd-cashier-coupon-val" class="myd-td-val ' + (cpnRaw > 0 ? 'myd-td-val-red' : '') + '">' + (cpnRaw > 0 ? '- ' : '') + 'R$ ' + (data.total_coupon_discount || '0,00') + '</td></tr>';
        feesHtml += '<tr><td colspan="3" class="myd-td-label">Descontos de Fidelidade</td><td id="myd-cashier-fidelity-val" class="myd-td-val ' + (fdlRaw > 0 ? 'myd-td-val-red' : '') + '">' + (fdlRaw > 0 ? '- ' : '') + 'R$ ' + (data.total_fidelity_discount || '0,00') + '</td></tr>';

        var brutoRaw = parseFloat(data.total_raw || 0);
        var brutoBruto = brutoRaw + ifoodLiquid;

        feesHtml += '<tr class="myd-cashier-total-row"><td colspan="3" class="myd-td-total-label">Total Bruto</td><td id="myd-cashier-total-bruto" class="myd-td-total-val">R$ ' + formatMoney(brutoBruto) + '</td></tr>';

        feesHtml += '</tbody></table></div>';

        // ── Tabela 2: VALOR LÍQUIDO ──────────────────────────────────────
        var mpFee = parseFloat(data.total_mercadopago_fee_raw || 0);
        var totalLiquido = brutoBruto - motoboyFee;

        feesHtml += '<div id="myd-cashier-liquido-wrap" class="myd-fees-wrap myd-fees-wrap-mt">';
        feesHtml += '<div class="myd-fees-title myd-fees-title-liquido">Valor Líquido</div>';
        feesHtml += '<table class="myd-cashier-table">';
        feesHtml += '<tbody>';

        feesHtml += '<tr><td colspan="3" class="myd-td-label">Valor Bruto</td><td class="myd-td-val">R$ ' + formatMoney(brutoBruto) + '</td></tr>';
        feesHtml += '<tr><td colspan="3" class="myd-td-label">Taxa de Motoboy</td><td id="myd-cashier-motoboy-val" class="myd-td-val ' + (motoboyFee > 0 ? 'myd-td-val-red' : '') + '">' + (motoboyFee > 0 ? '- ' : '') + 'R$ ' + formatMoney(motoboyFee) + '</td></tr>';
        feesHtml += '<tr><td colspan="3" class="myd-td-label">Taxas de transações</td><td id="myd-cashier-mp-fees-val" class="myd-td-val myd-td-val-gray">R$ ' + (data.total_mercadopago_fee || '0,00') + '</td></tr>';

        feesHtml += '<tr class="myd-cashier-total-row"><td colspan="3" class="myd-td-total-label">Total Líquido</td><td id="myd-cashier-total-liquido" class="myd-td-total-val myd-td-total-green">R$ ' + formatMoney(totalLiquido) + '</td></tr>';

        feesHtml += '</tbody></table></div>';

        if (tableWrap) {
            tableWrap.insertAdjacentHTML('afterend', feesHtml);
        }

        resultEl.classList.remove('myd-hidden');
    }

    // ─── Utilitários ────────────────────────────────
    function formatMoney(v) {
        return parseFloat(v).toFixed(2).replace('.', ',');
    }

    function parseMoney(str) {
        if (typeof str === 'number') return str;
        return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0;
    }

    function parseMaskedMoney(str) {
        if (typeof str === 'number') return str;
        var clean = String(str).replace(/[R$\s]/g, '').replace(/\./g, '').replace(',', '.');
        return parseFloat(clean) || 0;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
    function printCashierToLocalServer() {
        var lines = [];
        function push(l) { lines.push(String(l || '')); }
        function pushSep() { push('--------------------------------'); }
        function centerText(t, width) {
            width = width || 32;
            var text = String(t || '');
            var pad = Math.max(0, width - text.length);
            var left = Math.floor(pad / 2);
            var right = pad - left;
            return ' '.repeat(left) + text + ' '.repeat(right);
        }
        function formatItemLines(name, priceOrValue, width) {
            width = width || 32;
            var p = String(priceOrValue || '');
            name = String(name || '');

            var linesArray = [];
            // O valor sempre terá 3 espaços antes
            var valueSpace = p.length + 3;
            var maxNameLen = width - valueSpace;
            if (maxNameLen < 1) maxNameLen = 1; // Sanity check

            if (name.length <= maxNameLen) {
                // Coube na primeira linha sem problemas
                var space = Math.max(3, width - name.length - p.length);
                linesArray.push(name + ' '.repeat(space) + p);
            } else {
                // Nome muito longo: corta e empurra o valor pro final da primeira linha
                var firstPart = name.substring(0, maxNameLen);
                var space = width - firstPart.length - p.length;
                linesArray.push(firstPart + ' '.repeat(space) + p);

                // Quebrando o restante do texto sem atingir os limites
                var remaining = name.substring(maxNameLen).trim();
                var indent = name.startsWith(' ') ? name.match(/^ +/)[0] : '';
                if (name.startsWith('(')) indent = '  ';

                while (remaining.length > 0) {
                    var partLen = width - indent.length;
                    var part = remaining.substring(0, partLen);
                    linesArray.push(indent + part.trim());
                    remaining = remaining.substring(part.length).trim();
                }
            }
            return linesArray;
        }


        function formatProductLines(name, qty, price, width) {
            width = width || 32;
            var p = String(price || '');
            var q = String(qty || '');
            name = String(name || '');

            var priceColWidth = Math.max(9, p.length + 1);
            var qtyColWidth = Math.max(4, q.length + 2);
            var maxNameLen = width - priceColWidth - qtyColWidth;
            if (maxNameLen < 1) maxNameLen = 1;

            var linesArray = [];
            var firstPart = name.length > maxNameLen ? name.substring(0, maxNameLen) : name;

            var space1 = maxNameLen - firstPart.length + qtyColWidth - q.length;
            var space2 = priceColWidth - p.length;

            var line1 = firstPart + '.'.repeat(Math.max(1, space1)) + q + ' '.repeat(Math.max(1, space2)) + p;
            linesArray.push(line1);

            if (name.length > maxNameLen) {
                var remaining = name.substring(maxNameLen).trim();
                while (remaining.length > 0) {
                    var partLen = Math.min(maxNameLen, remaining.length);
                    var chunk = remaining.substring(0, partLen);
                    linesArray.push(chunk.trim());
                    remaining = remaining.substring(partLen).trim();
                }
            }
            return linesArray;
        }

        push('')
        push(centerText('* FECHAMENTO DE CAIXA *'));
        pushSep();

        var periodEl = document.getElementById('myd-cashier-period');
        if (periodEl) {
            // Pode haver HTML (strong, span, etc). Pegar o innerText e quebrar por tubos ou hífens extras
            var pText = periodEl.innerText.replace(/\|/g, '\n').split('\n');
            for (var i = 0; i < pText.length; i++) {
                if (pText[i].trim()) push(pText[i].trim());
            }
        }
        pushSep();

        var diffEl = document.getElementById('myd-caixa-diff-result');
        if (diffEl) {
            var summary = diffEl.querySelector('.myd-caixa-diff-summary');
            if (summary) {
                var divs = summary.querySelectorAll('div');
                for (var i = 0; i < divs.length; i++) {
                    var text = divs[i].innerText.trim();
                    if (text) {
                        var rsIndex = text.search(/R\$/i);
                        if (rsIndex !== -1) {
                            var n = text.substring(0, rsIndex).trim();
                            var v = text.substring(rsIndex).trim();
                            formatItemLines(n, v).forEach(push);
                        } else {
                            var parts = text.split(':');
                            if (parts.length >= 2 && text.match(/\d/)) {
                                var v = parts.pop().trim();
                                var n = parts.join(':').trim() + ':';
                                formatItemLines(n, v).forEach(push);
                            } else {
                                push(text);
                            }
                        }
                    }
                }
            }
            var movs = diffEl.querySelectorAll('.myd-caixa-mov-item');
            if (movs.length > 0) {
                push('');
                push('Movimentacoes:');
                var retiradas = [];
                var suprimentos = [];
                for (var i = 0; i < movs.length; i++) {
                    var spans = movs[i].querySelectorAll('span');
                    if (spans.length >= 4) {
                        var tType = spans[0].innerText.replace(/[\uD83D\uDCE4\uD83D\uDCE5]/g, '').trim().toLowerCase();
                        var tMotivo = spans[1].innerText.trim();
                        var tHora = spans[2].innerText.trim();
                        var tValor = spans[3].innerText.replace(/[\+\-\s]/g, '').replace('R$', '').trim();
                        var lineName = tMotivo ? ' ' + tMotivo + ' ' + tHora : ' ' + tHora;

                        if (tType === 'retirada') {
                            retiradas.push({ name: lineName, val: '-R$ ' + tValor });
                        } else {
                            suprimentos.push({ name: lineName, val: '+R$ ' + tValor });
                        }
                    } else {
                        push(' ' + movs[i].innerText.replace(/\n/g, ' ').trim());
                    }
                }
                if (retiradas.length > 0) {
                    push(' Retiradas:');
                    for (var r = 0; r < retiradas.length; r++) {
                        formatItemLines(retiradas[r].name, retiradas[r].val).forEach(push);
                    }
                }
                if (retiradas.length > 0 && suprimentos.length > 0) {
                    push('................................');
                }
                if (suprimentos.length > 0) {
                    push(' Suprimentos:');
                    for (var s = 0; s < suprimentos.length; s++) {
                        formatItemLines(suprimentos[s].name, suprimentos[s].val).forEach(push);
                    }
                }
            }
            var result = diffEl.querySelector('.myd-caixa-diff');
            if (result) {
                push('');
                push(result.innerText.trim());
            }
        }
        push('');


        var prodsTable = document.getElementById('myd-cashier-products-body');
        if (prodsTable) {
            push(centerText('PRODUTOS VENDIDOS'));
            pushSep();
            push('DESCRICAO           QTD    TOTAL');
            pushSep();
            var trs = prodsTable.querySelectorAll('tr');
            for (var i = 0; i < trs.length; i++) {
                var isTotalRow = trs[i].classList.contains('myd-cashier-total-row');
                var tds = trs[i].querySelectorAll('td');
                if (tds.length === 4) {
                    var nomeProd = tds[0].innerText.trim();
                    var qtyProd = tds[1].innerText.trim();
                    var sub = tds[3].innerText.trim();
                    if (sub.indexOf('R$') === -1) sub = 'R$ ' + sub;
                    formatProductLines(nomeProd, qtyProd, sub).forEach(push);
                    push('')
                } else if (tds.length === 1) {
                    push(centerText(tds[0].innerText.trim()));
                }
            }
        }

        var countEl = document.getElementById('myd-cashier-order-count');
        if (countEl) formatItemLines('Total de Pedidos:', countEl.innerText).forEach(push);

        pushSep();
        push(centerText('TOTAIS (FORMAS PAGAMENTO)'));
        var payments = document.querySelectorAll('.myd-cashier-payment-item');
        for (var i = 0; i < payments.length; i++) {
            var label = payments[i].querySelector('.myd-cashier-payment-label').innerText.replace(/[\uD83D\uDCB0\uD83D\uDCA0\uD83D\uDCB3\uD83D\uDCB2\uD83C\uDF7D\uFE0F\uD83D\uDCB5\uD83C\uDF10]/g, '').trim();
            var val = payments[i].querySelector('.myd-cashier-payment-value').innerText.trim();
            formatItemLines(label, val).forEach(push);
        }

        var totalEl = document.getElementById('myd-cashier-total');
        if (totalEl) {
            push('');
            formatItemLines('TOTAL ARRECADADO:', totalEl.innerText).forEach(push);
        }
        push('');
        pushSep();
        push(centerText('VALOR BRUTO'));
        pushSep();
        var delEl = document.getElementById('myd-cashier-delivery-val');
        formatItemLines('Taxas Entregas:', delEl ? delEl.innerText : 'R$ 0,00').forEach(push);

        var extEl = document.getElementById('myd-cashier-extras-val');
        formatItemLines('Produtos Adicionais:', extEl ? extEl.innerText : 'R$ 0,00').forEach(push);

        var ifoodEl = document.getElementById('myd-cashier-ifood-val');
        formatItemLines('Liquido iFood:', ifoodEl ? ifoodEl.innerText : 'R$ 0,00').forEach(push);

        var discEl = document.getElementById('myd-cashier-discounts-val');
        formatItemLines('Descontos:', discEl ? discEl.innerText : 'R$ 0,00').forEach(push);

        var brutoEl = document.getElementById('myd-cashier-total-bruto');
        if (brutoEl) {
            push('');
            formatItemLines('TOTAL BRUTO:', brutoEl.innerText).forEach(push);
        }

        push('');
        pushSep();
        push(centerText('VALOR LIQUIDO'));
        pushSep();

        if (brutoEl) formatItemLines('Valor Bruto:', brutoEl.innerText).forEach(push);

        var motoboyEl = document.getElementById('myd-cashier-motoboy-val');
        formatItemLines('Taxa de Motoboy:', motoboyEl ? motoboyEl.innerText : 'R$ 0,00').forEach(push);

        var mpFeeEl = document.getElementById('myd-cashier-mp-fees-val');
        formatItemLines('Taxas de transacoes:', mpFeeEl ? mpFeeEl.innerText : 'R$ 0,00').forEach(push);

        var liquidoEl = document.getElementById('myd-cashier-total-liquido');
        if (liquidoEl) {
            push('');
            formatItemLines('TOTAL LIQUIDO:', liquidoEl.innerText).forEach(push);
        }

        pushSep();
        push('');
        push('Confirmo que as informacoes digitadas acima foram digitadas por mim de acordo com a soma feita dos comprovantes, recibos e dinheiro recebidos por mim.');
        push('');
        push('')
        push(centerText('________________________'));
        push(centerText('Assinatura'));
        push('');
        push('');

        var textToPrint = lines.join('\n') + '\n';
        var printBtn = document.getElementById('myd-cashier-print');
        var originalText = printBtn ? printBtn.innerText : '';
        if (printBtn) { printBtn.innerText = 'Imprimindo...'; printBtn.disabled = true; }

        // Tentar carregar a imagem do cabeçalho como Base64
        function loadImageBase64(url) {
            return fetch(url)
                .then(function (r) {
                    if (!r.ok) throw new Error('Imagem não disponível');
                    return r.blob();
                })
                .then(function (blob) {
                    return new Promise(function (resolve) {
                        var reader = new FileReader();
                        reader.onloadend = function () { resolve(reader.result); };
                        reader.onerror = function () { resolve(null); };
                        reader.readAsDataURL(blob);
                    });
                })
                .catch(function () { return null; });
        }

        var imgUrl = (typeof MYD_LETTER_IMG_URL !== 'undefined' && MYD_LETTER_IMG_URL)
            ? MYD_LETTER_IMG_URL
            : null;

        var imagePromise = imgUrl ? loadImageBase64(imgUrl) : Promise.resolve(null);

        imagePromise.then(function (imageBase64) {
            var payload = { text: textToPrint, escpos: true, imagePrintWidth: 280 };
            if (imageBase64) payload.imageBase64 = imageBase64;

            return fetch('http://127.0.0.1:3420/print', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (window.MydGlobalNotify) {
                    if (data.ok) window.MydGlobalNotify('success', 'Impressão', 'Comando enviado para a impressora.');
                    else {
                        window.MydGlobalNotify('error', 'Impressão', 'Erro: ' + (data.error || 'Desconhecido'));
                        window.print(); // fallback
                    }
                }
            })
            .catch(function (err) {
                console.error('Print erro:', err);
                // Fallback
                window.print();
            })
            .finally(function () {
                if (printBtn) { printBtn.innerText = originalText; printBtn.disabled = false; }
            });
    }

})();
