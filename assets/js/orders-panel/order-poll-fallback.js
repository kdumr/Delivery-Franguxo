/**
 * Polling para Novos Pedidos
 *
 * Verifica novos pedidos a cada 30 segundos via AJAX.
 * Funciona continuamente, independente do estado do Socket.IO.
 *
 * @since 4.2.0
 */
(function () {
    'use strict';

    var POLL_INTERVAL = 30000; // 30 segundos
    var INITIAL_DELAY = 5000;  // 5 segundos de delay inicial para dar tempo ao Socket.IO
    var LOG_PREFIX = '[OrderPollFallback]';

    var pollTimer = null;
    var isPolling = false;
    var lastKnownIds = null; // Set de IDs conhecidos no DOM

    // ───────────────────────────── Helpers ─────────────────────────────

    /**
     * Verifica se o Socket.IO push está conectado
     */
    function isSocketConnected() {
        try {
            return window.mydPushSocket && window.mydPushSocket.connected === true;
        } catch (_) {
            return false;
        }
    }

    /**
     * Coleta todos os IDs de pedidos atualmente no DOM
     */
    function getDomOrderIds() {
        var ids = new Set();
        try {
            var items = document.querySelectorAll('.fdm-orders-items');
            items.forEach(function (el) {
                if (el.id) ids.add(String(el.id));
            });
        } catch (_) { }
        return ids;
    }

    /**
     * Normaliza status de pedido (mesma lógica de frontend.min.js)
     */
    function normalizeStatus(s) {
        if (!s) return 'new';
        s = String(s).toLowerCase().trim().replace(/[_\s]+/g, '-');
        if (['created', 'received', 'pending'].indexOf(s) !== -1) return 'new';
        if (['processing', 'preparing', 'accepted', 'accept', 'accepted-order', 'preparando', 'confirmado', 'ready-for-preparation', 'ready'].indexOf(s) !== -1) return 'confirmed';
        if (['in-delivery', 'indelivery', 'out-for-delivery', 'out-for-shipping', 'despachado'].indexOf(s) !== -1) return 'in-delivery';
        if (s === 'finished' || s === 'completed') return 'done';
        return s;
    }

    /**
     * Retorna o container alvo pelo status
     */
    function getTargetContainer(status) {
        var s = normalizeStatus(status);
        var container = null;
        if (s === 'new') container = document.querySelector('#myd-section-new .myd-orders-accordion-body');
        else if (s === 'in-delivery') container = document.querySelector('#myd-section-in-delivery .myd-orders-accordion-body');
        else if (['confirmed', 'waiting'].indexOf(s) !== -1) container = document.querySelector('#myd-section-production .myd-orders-accordion-body');
        else container = document.querySelector('#myd-section-done .myd-orders-accordion-body');
        if (!container) container = document.querySelector('.fdm-orders-loop');
        return container;
    }

    // ──────────────────────────── Polling ────────────────────────────

    /**
     * Faz a verificação leve (check_new_orders) e, se houver novos,
     * busca o HTML completo via update_orders.
     */
    function poll() {


        var ajaxObj = window.order_ajax_object;
        if (!ajaxObj || !ajaxObj.ajax_url || !ajaxObj.nonce) {
            console.warn(LOG_PREFIX, 'order_ajax_object não disponível');
            return;
        }

        // Primeira chamada: check leve
        fetch(ajaxObj.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Cache-Control': 'no-cache' },
            body: 'action=check_new_orders&nonce=' + encodeURIComponent(ajaxObj.nonce)
        }).then(function (r) { return r.json(); }).then(function (resp) {
            if (!resp || !resp.success || !resp.data || !resp.data.order_ids) {
                console.warn(LOG_PREFIX, 'Resposta inesperada do check_new_orders', resp);
                return;
            }

            var serverIds = resp.data.order_ids; // Array de strings
            var domIds = getDomOrderIds();

            // Encontrar IDs que existem no servidor mas não no DOM
            var newIds = [];
            for (var i = 0; i < serverIds.length; i++) {
                if (!domIds.has(String(serverIds[i]))) {
                    newIds.push(serverIds[i]);
                }
            }

            if (newIds.length === 0) {
                console.log(LOG_PREFIX, 'Nenhum pedido novo detectado (' + domIds.size + ' no DOM, ' + serverIds.length + ' no servidor)');
                return;
            }

            console.log(LOG_PREFIX, newIds.length + ' pedido(s) novo(s) detectado(s):', newIds);

            // Buscar HTML completo
            return fetch(ajaxObj.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Cache-Control': 'no-cache' },
                body: 'action=update_orders&nonce=' + encodeURIComponent(ajaxObj.nonce)
            }).then(function (r) { return r.json(); }).then(function (fullResp) {
                if (!fullResp || !fullResp.loop) {
                    console.warn(LOG_PREFIX, 'Resposta vazia do update_orders');
                    return;
                }

                var parsed = null;
                try {
                    parsed = new DOMParser().parseFromString(fullResp.loop, 'text/html');
                } catch (_) {
                    var tmp = document.createElement('div');
                    tmp.innerHTML = fullResp.loop;
                    parsed = tmp;
                }

                // Inserir apenas os pedidos novos
                newIds.forEach(function (id) {
                    // Guard: evita inserção se o card já existe no DOM (pode ter sido inserido pelo Socket.IO)
                    if (document.getElementById(String(id))) {
                        console.log(LOG_PREFIX, 'Card #' + id + ' já existe no DOM — pulando inserção (dedup)');
                        return;
                    }

                    // Guard: evita inserção concorrente com Socket.IO usando Set global
                    if (!window.__mydInsertingIds) window.__mydInsertingIds = new Set();
                    if (window.__mydInsertingIds.has(String(id))) {
                        console.log(LOG_PREFIX, 'Card #' + id + ' já está sendo inserido por outro processo — pulando (dedup)');
                        return;
                    }
                    window.__mydInsertingIds.add(String(id));

                    var found = null;
                    try { found = parsed.querySelector('.fdm-orders-items#' + CSS.escape(id)); } catch (_) { found = null; }
                    if (!found) {
                        // Tentativa alternativa
                        var all = parsed.querySelectorAll('.fdm-orders-items');
                        for (var j = 0; j < all.length; j++) {
                            if (String(all[j].id) === String(id)) { found = all[j]; break; }
                        }
                    }

                    if (!found) {
                        window.__mydInsertingIds.delete(String(id));
                        console.warn(LOG_PREFIX, 'Card #' + id + ' não encontrado no HTML retornado');
                        return;
                    }

                    // Guard final: verifica novamente no DOM antes de inserir (janela de tempo)
                    if (document.getElementById(String(id))) {
                        window.__mydInsertingIds.delete(String(id));
                        console.log(LOG_PREFIX, 'Card #' + id + ' apareceu no DOM durante busca — pulando (dedup)');
                        return;
                    }

                    var st = found.getAttribute('data-order-status');
                    var ns = normalizeStatus(st);
                    var container = getTargetContainer(st);

                    if (container) {
                        try {
                            container.insertBefore(found, container.firstElementChild);
                        } catch (_) {
                            try { container.appendChild(found); } catch (__) { }
                        }
                        window.__mydInsertingIds.delete(String(id));

                        // Animação de destaque
                        try {
                            found.classList.add('fdm-new-arrival');
                            setTimeout(function () {
                                try { found.classList.remove('fdm-new-arrival'); } catch (_) { }
                            }, 7000);
                        } catch (_) { }

                        // Atualizar contadores
                        try {
                            if (window.MydRefreshOrderCounts && typeof window.MydRefreshOrderCounts === 'function') {
                                window.MydRefreshOrderCounts();
                            }
                        } catch (_) { }

                        // Alerta sonoro para pedidos novos
                        try {
                            if (ns === 'new' && window.MydAlert && typeof window.MydAlert.add === 'function') {
                                window.MydAlert.add(id);
                            }
                        } catch (_) { }

                        console.log(LOG_PREFIX, 'Pedido #' + id + ' inserido com sucesso (status: ' + ns + ')');
                    } else {
                        window.__mydInsertingIds.delete(String(id));
                    }
                });

                // Inserir detalhes completos (full)
                try {
                    if (fullResp.full) {
                        var host = document.querySelector('.fdm-orders-full');
                        if (host) {
                            var parsedFull = new DOMParser().parseFromString(fullResp.full, 'text/html');
                            newIds.forEach(function (id) {
                                try {
                                    var fullEl = parsedFull.querySelector('#content-' + CSS.escape(id));
                                    if (fullEl && !document.getElementById('content-' + id)) {
                                        fullEl.style.display = 'none';
                                        host.appendChild(fullEl);
                                    }
                                } catch (_) { }
                            });
                        }
                    }
                } catch (_) { }

            }).catch(function (e) {
                console.warn(LOG_PREFIX, 'Erro ao buscar update_orders:', e);
            });

        }).catch(function (e) {
            console.warn(LOG_PREFIX, 'Erro ao buscar check_new_orders:', e);
        });
    }

    /**
     * Inicia o polling
     */
    function startPolling() {
        if (isPolling) return;
        isPolling = true;
        console.log(LOG_PREFIX, 'Polling iniciado — intervalo: ' + (POLL_INTERVAL / 1000) + 's');
        poll(); // primeira execução imediata
        pollTimer = setInterval(poll, POLL_INTERVAL);
    }

    /**
     * Para o polling
     */
    function stopPolling() {
        if (!isPolling) return;
        isPolling = false;
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        console.log(LOG_PREFIX, 'Polling parado');
    }

    // ──────────────────────────── Init ────────────────────────────

    function init() {
        // Verificar se estamos na página de pedidos
        if (!document.querySelector('.fdm-orders-loop') && !document.querySelector('mydelivery-orders')) {
            console.log(LOG_PREFIX, 'Não está na página de pedidos — desativado');
            return;
        }

        console.log(LOG_PREFIX, 'Inicializando... (delay inicial: ' + (INITIAL_DELAY / 1000) + 's)');

        setTimeout(function () {
            console.log(LOG_PREFIX, 'Ativando polling contínuo');
            startPolling();
        }, INITIAL_DELAY);
    }

    // ──────────────────────────── Deduplicação ────────────────────────────

    /**
     * Remove cards duplicados do DOM.
     * Um card é considerado duplicado quando há mais de um .fdm-orders-items com o mesmo id no DOM.
     * Mantém apenas o primeiro; remove os demais.
     */
    function dedupeOrderCards() {
        try {
            var seen = {};
            var all = document.querySelectorAll('.fdm-orders-items');
            var removed = 0;
            all.forEach(function (el) {
                var id = el.id;
                if (!id) return;
                if (seen[id]) {
                    // Duplicata encontrada — remover
                    try { el.parentNode && el.parentNode.removeChild(el); removed++; } catch (_) { }
                } else {
                    seen[id] = true;
                }
            });

            // Deduplicar detalhes (.fdm-orders-full > #content-XXXX)
            var seenContent = {};
            var allContent = document.querySelectorAll('.fdm-orders-full [id^="content-"]');
            allContent.forEach(function (el) {
                var id = el.id;
                if (!id) return;
                if (seenContent[id]) {
                    try { el.parentNode && el.parentNode.removeChild(el); removed++; } catch (_) { }
                } else {
                    seenContent[id] = true;
                }
            });

            if (removed > 0) {
                console.warn(LOG_PREFIX, 'MydDedupeOrders: ' + removed + ' card(s) duplicado(s) removido(s) do DOM');
                // Atualizar contadores após limpeza
                try {
                    if (window.MydRefreshOrderCounts && typeof window.MydRefreshOrderCounts === 'function') {
                        window.MydRefreshOrderCounts();
                    }
                } catch (_) { }
            }
            return removed;
        } catch (e) {
            console.warn(LOG_PREFIX, 'MydDedupeOrders: erro ao deduplicar', e);
            return 0;
        }
    }

    // Inicializa quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Rodar deduplicação automaticamente 1s após a carga (captura duplicatas pré-existentes)
    setTimeout(dedupeOrderCards, 1000);
    // Rodar novamente após 5s para cobrir chegadas tardias do Socket.IO
    setTimeout(dedupeOrderCards, 5000);

    // Expor globalmente para debug e chamada manual
    window.MydDedupeOrders = dedupeOrderCards;
    window.MydOrderPollFallback = {
        start: startPolling,
        stop: stopPolling,
        poll: poll,
        isPolling: function () { return isPolling; },
        isSocketConnected: isSocketConnected,
        dedupe: dedupeOrderCards
    };

})();
