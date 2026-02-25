/**
 * MYD Checkout Loading Overlay
 * Mostra overlay de "Carregando" durante requisições AJAX do checkout
 */
(function() {
    'use strict';

    // CSS para o overlay de loading
    const overlayCSS = `
        <style id="myd-checkout-loading-overlay-css">
            .myd-checkout-overlay-parent {
                position: relative;
            }
            .myd-checkout-loading-overlay {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.45);
                backdrop-filter: blur(2px);
                z-index: 99999;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                pointer-events: none;
            }
            body > .myd-checkout-loading-overlay {
                position: fixed;
            }
            .myd-checkout-loading-overlay.active {
                opacity: 1;
                visibility: visible;
                pointer-events: all;
            }
            .myd-checkout-loading-content {
                background: white;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                text-align: center;
                max-width: 300px;
                width: 90%;
            }
            .myd-checkout-loading-text {
                font-size: 18px;
                font-weight: 600;
                color: #333;
                margin-bottom: 20px;
            }
            .myd-checkout-loading-spinner {
                display: inline-block;
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #0073aa;
                border-radius: 50%;
                animation: myd-spin 1s linear infinite;
            }
            @keyframes myd-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    `;

    // HTML do overlay
    const overlayHTML = `
        <div id="myd-checkout-loading-overlay" class="myd-checkout-loading-overlay">
            <div class="myd-checkout-loading-content">
                <div class="myd-checkout-loading-text">Carregando...</div>
                <div class="myd-checkout-loading-spinner"></div>
            </div>
        </div>
    `;

    let overlayElement = null;
    let isLoading = false;
    let bodyWasLocked = false;

    function attachOverlayToCartIfPossible() {
        if (!overlayElement) return;
        const cartContainer = document.querySelector('.myd-cart');
        if (!cartContainer) {
            return;
        }
        if (overlayElement.parentElement !== cartContainer) {
            if (overlayElement.parentElement && overlayElement.parentElement !== document.body) {
                overlayElement.parentElement.classList.remove('myd-checkout-overlay-parent');
            }
            overlayElement.remove();
            cartContainer.appendChild(overlayElement);
        }
        cartContainer.classList.add('myd-checkout-overlay-parent');
    }

    // Função para mostrar o overlay
    function showLoadingOverlay() {
        if (isLoading) return;
        isLoading = true;

        if (!overlayElement) {
            createOverlay();
        }

        attachOverlayToCartIfPossible();

        overlayElement.classList.add('active');

        if (overlayElement.parentElement === document.body) {
            document.body.style.overflow = 'hidden'; // Previne scroll global
            bodyWasLocked = true;
        } else {
            bodyWasLocked = false;
        }
    }

    // Função para esconder o overlay
    function hideLoadingOverlay() {
        if (!isLoading) return;
        isLoading = false;

        if (overlayElement) {
            overlayElement.classList.remove('active');
        }
        if (bodyWasLocked) {
            document.body.style.overflow = '';
            bodyWasLocked = false;
        }
    }

    // Função para criar o overlay
    function createOverlay() {
        // Adicionar CSS se não existir
        if (!document.getElementById('myd-checkout-loading-overlay-css')) {
            document.head.insertAdjacentHTML('beforeend', overlayCSS);
        }

        // Adicionar HTML se não existir
        let existingOverlay = document.getElementById('myd-checkout-loading-overlay');
        if (!existingOverlay) {
            const cartContainer = document.querySelector('.myd-cart');
            if (cartContainer) {
                cartContainer.classList.add('myd-checkout-overlay-parent');
                cartContainer.insertAdjacentHTML('beforeend', overlayHTML);
            } else {
                document.body.insertAdjacentHTML('beforeend', overlayHTML);
            }
            existingOverlay = document.getElementById('myd-checkout-loading-overlay');
        }

        overlayElement = existingOverlay;
        attachOverlayToCartIfPossible();
    }

    // Interceptar requisições AJAX do checkout
    function interceptAjaxRequests() {
        // Extrair valor da ação do body enviado pela requisição
        function extractActionFromBody(body) {
            if (!body) {
                return '';
            }

            if (typeof body === 'string') {
                const trimmed = body.trim();
                if (!trimmed) {
                    return '';
                }

                // Tentar interpretar como query string (application/x-www-form-urlencoded)
                try {
                    const params = new URLSearchParams(trimmed);
                    const actionValue = params.get('action');
                    if (actionValue) {
                        return actionValue;
                    }
                } catch (e) { /* noop */ }

                // Tentar interpretar como JSON
                if (trimmed[0] === '{') {
                    try {
                        const parsed = JSON.parse(trimmed);
                        if (parsed && typeof parsed.action === 'string') {
                            return parsed.action;
                        }
                    } catch (e) { /* noop */ }
                }

                // Fallback simples com split
                const actionMatch = trimmed.match(/action=([^&]+)/);
                if (actionMatch && actionMatch[1]) {
                    return decodeURIComponent(actionMatch[1]);
                }

                return '';
            }

            if (typeof URLSearchParams !== 'undefined' && body instanceof URLSearchParams) {
                const actionValue = body.get('action');
                return actionValue ? actionValue : '';
            }

            if (typeof FormData !== 'undefined' && body instanceof FormData) {
                const actionValue = body.get('action');
                return typeof actionValue === 'string' ? actionValue : '';
            }

            if (typeof body === 'object') {
                if (typeof body.action === 'string') {
                    return body.action;
                }

                if (body.action && typeof body.action.toString === 'function') {
                    return body.action.toString();
                }
            }

            return '';
        }

        function extractActionFromUrl(url) {
            if (!url || typeof url !== 'string') {
                return '';
            }

            const index = url.indexOf('?');
            if (index === -1) {
                return '';
            }

            try {
                const params = new URLSearchParams(url.slice(index + 1));
                const actionValue = params.get('action');
                return actionValue ? actionValue : '';
            } catch (e) {
                return '';
            }
        }

        function isCheckoutAction(url, body) {
            const actionValue = extractActionFromBody(body) || extractActionFromUrl(url);
            return actionValue === 'myd_create_draft_order' || actionValue === 'myd_place_payment';
        }

        // Interceptar fetch requests
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const requestInfo = args[0];
            const url = typeof requestInfo === 'string' ? requestInfo : requestInfo?.url;

            // Verificar se é a requisição do checkout (admin-ajax.php com action=myd_create_draft_order)
            if (typeof url === 'string' && url.includes('admin-ajax.php')) {
                const body = args[1]?.body;
                const requestBody = body || requestInfo?.body;
                if (isCheckoutAction(url, requestBody)) {
                    showLoadingOverlay();

                    return originalFetch.apply(this, args)
                        .then(response => {
                            // Aguardar um pouco para garantir que a UI foi atualizada
                            setTimeout(() => {
                                hideLoadingOverlay();
                            }, 500);
                            return response;
                        })
                        .catch(error => {
                            hideLoadingOverlay();
                            throw error;
                        });
                }
            }

            return originalFetch.apply(this, args);
        };

        // Interceptar XMLHttpRequest (fallback)
        const originalOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function(method, url) {
            this._url = url;
            return originalOpen.apply(this, arguments);
        };

        const originalSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.send = function(body) {
            if (this._url && this._url.includes('admin-ajax.php') &&
                isCheckoutAction(this._url, body)) {
                showLoadingOverlay();

                this.addEventListener('loadend', function() {
                    setTimeout(() => {
                        hideLoadingOverlay();
                    }, 500);
                });
            }

            return originalSend.apply(this, arguments);
        };
    }

    // Inicializar
    function init() {
        // Aguardar DOM pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                interceptAjaxRequests();
            });
        } else {
            interceptAjaxRequests();
        }

        console.log('MYD: Checkout loading overlay initialized');
    }

    // Executar inicialização
    init();

    // Expor funções globalmente para debug
    window.MydCheckoutLoading = {
        show: showLoadingOverlay,
        hide: hideLoadingOverlay
    };

})();