/**
 * Sistema de Persistência de Sessão de Pedidos
 * MyD Delivery Pro
 * 
 * Resolve o problema de perda de sessão quando o usuário fecha a aba
 * e abre novamente, especialmente em dispositivos móveis.
 */

(function() {
    'use strict';

    class OrderSessionPersistence {
        constructor() {
            this.sessionKey = 'myd_order_session';
            this.draftKey = 'myd_draft_order';
            this.userDataKey = 'mydUserData';
            this.cartKey = 'mydCart';
            this.sessionTimeout = 24 * 60 * 60 * 1000; // 24 horas
            
            this.init();
        }

        init() {
            // Aguarda carregamento completo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
            } else {
                this.setupEventListeners();
            }

            // Restaura sessão ao carregar a página
            // Primeiro tenta restaurar sessão local
            const restored = this.restoreOrderSession();
            // Se não encontrou sessão local e usuário está logado, tenta obter rascunho no servidor
            if ( ! restored && window.mydStoreInfo && window.mydStoreInfo.auth && window.mydStoreInfo.auth.isLoggedIn ) {
                this.fetchServerDraftIfNone();
            }
        }

        setupEventListeners() {
            // Salva dados do pedido quando draft é criado
            window.addEventListener('MydDraftOrderCreated', (event) => {
                this.saveOrderSession(event.detail);
            });

            // Salva dados quando checkout é atualizado
            window.addEventListener('MydCheckoutPlacePayment', () => {
                this.updateOrderSession();
            });

            // Limpa sessão quando pedido é finalizado
            window.addEventListener('MydOrderComplete', () => {
                this.clearOrderSession();
            });

            // Salva dados em tempo real durante o checkout
            this.setupRealTimeSaving();

            // Monitora mudanças de visibilidade da página (quando usuário sai/volta)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.saveCurrentState();
                } else if (document.visibilityState === 'visible') {
                    this.restoreOrderSession();
                }
            });

            // Salva antes de sair da página
            window.addEventListener('beforeunload', () => {
                this.saveCurrentState();
            });

            // Adiciona botão de recuperação na interface
            this.addRecoveryButton();
        }

        saveOrderSession(orderData) {
            try {
                const sessionData = {
                    timestamp: Date.now(),
                    orderId: orderData?.data?.id || orderData?.order_id,
                    orderData: orderData,
                    cart: this.getCartData(),
                    userData: this.getUserData(),
                    checkoutStep: this.getCurrentCheckoutStep(),
                    paymentData: this.getPaymentData(),
                    url: window.location.href
                };

                // Salva tanto no localStorage quanto no sessionStorage
                localStorage.setItem(this.sessionKey, JSON.stringify(sessionData));
                sessionStorage.setItem(this.sessionKey, JSON.stringify(sessionData));

                console.log('✅ Sessão do pedido salva:', sessionData.orderId);
            } catch (error) {
                console.warn('Erro ao salvar sessão do pedido:', error);
            }
        }

        updateOrderSession() {
            try {
                const existingSession = this.getOrderSession();
                if (existingSession) {
                    existingSession.timestamp = Date.now();
                    existingSession.cart = this.getCartData();
                    existingSession.userData = this.getUserData();
                    existingSession.checkoutStep = this.getCurrentCheckoutStep();
                    existingSession.paymentData = this.getPaymentData();

                    localStorage.setItem(this.sessionKey, JSON.stringify(existingSession));
                    sessionStorage.setItem(this.sessionKey, JSON.stringify(existingSession));
                }
            } catch (error) {
                console.warn('Erro ao atualizar sessão do pedido:', error);
            }
        }

        restoreOrderSession() {
            try {
                const sessionData = this.getOrderSession();
                const forceDialog = sessionStorage.getItem('myd_force_recovery_dialog');
                
                if (!sessionData) {
                    return false;
                }

                // Verifica se a sessão não expirou
                const now = Date.now();
                const sessionAge = now - sessionData.timestamp;
                
                if (sessionAge > this.sessionTimeout) {
                    this.clearOrderSession();
                    return false;
                }

                // Se o pedido for um rascunho, recupera automaticamente para que o cliente
                // volte exatamente para a etapa em que estava após um reload.
                if (this.isOrderDraft(sessionData)) {
                    // pequena espera para garantir que outros módulos (MydCart/MydCheckout) inicializem
                    setTimeout(() => {
                        this.recoverSession(sessionData);
                    }, 80);
                    return true;
                }

                // Verifica se há um pedido em andamento ou se foi forçado
                if ((sessionData.orderId && !this.isOrderCompleted(sessionData.orderId)) || forceDialog) {
                    // Remove a flag de força
                    if (forceDialog) {
                        sessionStorage.removeItem('myd_force_recovery_dialog');
                    }
                    
                    this.showSessionRecoveryDialog(sessionData);
                    return true;
                }

                return false;
            } catch (error) {
                console.warn('Erro ao restaurar sessão do pedido:', error);
                return false;
            }
        }

        getOrderSession() {
            try {
                // Tenta primeiro do sessionStorage, depois localStorage
                let sessionData = sessionStorage.getItem(this.sessionKey);
                if (!sessionData) {
                    sessionData = localStorage.getItem(this.sessionKey);
                }
                
                return sessionData ? JSON.parse(sessionData) : null;
            } catch (error) {
                console.warn('Erro ao ler sessão do pedido:', error);
                return null;
            }
        }

        clearOrderSession() {
            try {
                localStorage.removeItem(this.sessionKey);
                sessionStorage.removeItem(this.sessionKey);
                console.log('🗑️ Sessão do pedido limpa');
            } catch (error) {
                console.warn('Erro ao limpar sessão do pedido:', error);
            }
        }

        saveCurrentState() {
            try {
                // Força salvamento do estado atual
                if (window.MydCart) {
                    window.MydCart.saveStoredCart();
                }
                if (window.MydCheckout) {
                    window.MydCheckout.saveOnLocalStorage();
                }
                
                this.updateOrderSession();
            } catch (error) {
                console.warn('Erro ao salvar estado atual:', error);
            }
        }

        getCartData() {
            try {
                if (window.MydCart) {
                    return {
                        items: window.MydCart.items || [],
                        total: window.MydCart.total || 0,
                        formatedPrice: window.MydCart.formatedPrice || '',
                        itemsQuantity: window.MydCart.itemsQuantity || 0
                    };
                }
                return null;
            } catch (error) {
                return null;
            }
        }

        getUserData() {
            try {
                return JSON.parse(localStorage.getItem(this.userDataKey) || '{}');
            } catch (error) {
                return {};
            }
        }

        getCurrentCheckoutStep() {
            try {
                const activeNav = document.querySelector('.myd-cart__nav--active');
                const activeContent = document.querySelector('.myd-cart__content--active');
                
                return {
                    nav: activeNav ? activeNav.classList.toString() : '',
                    content: activeContent ? activeContent.id : '',
                    isCheckoutOpen: document.body.classList.contains('myd-cart-open')
                };
            } catch (error) {
                return {};
            }
        }

        getPaymentData() {
            try {
                if (window.MydOrder && window.MydOrder.payment) {
                    return window.MydOrder.payment.get();
                }
                return {};
            } catch (error) {
                return {};
            }
        }

        isOrderCompleted(orderId) {
            // Verifica se o pedido já foi finalizado
            // Isso pode ser feito via AJAX ou verificando elementos na página
            return false; // Por enquanto, assume que não foi finalizado
        }

        isOrderDraft(sessionData) {
            try {
                if (!sessionData || !sessionData.orderData) return false;

                const od = sessionData.orderData;
                const data = od.data || od;

                // Campos possíveis que indicam status/estado do pedido
                const status = (data && (data.status || data.post_status)) || od.status || od.post_status || '';
                const st = String(status).toLowerCase();

                // Considera rascunho os status mais comuns usados por WP/integrações
                if (!st) return false;
                return st === 'draft' || st === 'auto-draft' || st === 'rascunho';
            } catch (e) {
                return false;
            }
        }

        showSessionRecoveryDialog(sessionData) {
            // Remove diálogo existente se houver
            const existingDialog = document.getElementById('myd-session-recovery-dialog');
            if (existingDialog) {
                existingDialog.remove();
            }

            const dialog = document.createElement('div');
            dialog.id = 'myd-session-recovery-dialog';
            dialog.className = 'myd-session-recovery-dialog';
            
            dialog.innerHTML = `
                <div class="myd-session-recovery-overlay">
                    <div class="myd-session-recovery-modal">
                        <div class="myd-session-recovery-header">
                            <h3>🛒 Pedido em Andamento Encontrado</h3>
                        </div>
                        <div class="myd-session-recovery-content">
                            <p>Detectamos que você tinha um pedido em andamento. Deseja continuar de onde parou?</p>
                            <div class="myd-session-recovery-details">
                                <strong>Pedido ID:</strong> #${sessionData.orderId}<br>
                                <strong>Valor:</strong> ${sessionData.cart?.formatedPrice || 'N/A'}<br>
                                <strong>Itens:</strong> ${sessionData.cart?.itemsQuantity || 0}
                            </div>
                            <div class="myd-session-recovery-warning">
                                ⚠️ <strong>Importante:</strong> Se você já efetuou o pagamento via Pix ou outro método, 
                                é essencial continuar este pedido para não perder sua compra.
                            </div>
                        </div>
                        <div class="myd-session-recovery-actions">
                            <button id="myd-recover-session" class="myd-btn-primary">
                                ✅ Continuar Pedido
                            </button>
                            <button id="myd-start-new-order" class="myd-btn-secondary">
                                🆕 Novo Pedido
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(dialog);

            // Event listeners para os botões
            document.getElementById('myd-recover-session').addEventListener('click', () => {
                this.recoverSession(sessionData);
                dialog.remove();
            });

            document.getElementById('myd-start-new-order').addEventListener('click', () => {
                this.clearOrderSession();
                dialog.remove();
            });

            // Fecha ao clicar no overlay
            dialog.querySelector('.myd-session-recovery-overlay').addEventListener('click', (e) => {
                if (e.target === e.currentTarget) {
                    dialog.remove();
                }
            });
        }

        recoverSession(sessionData) {
            try {
                console.log('🔄 Recuperando sessão do pedido:', sessionData.orderId);

                // Restaura dados do carrinho
                if (sessionData.cart && window.MydCart) {
                    window.MydCart.items = sessionData.cart.items || [];
                    window.MydCart.total = sessionData.cart.total || 0;
                    window.MydCart.formatedPrice = sessionData.cart.formatedPrice || '';
                    window.MydCart.itemsQuantity = sessionData.cart.itemsQuantity || 0;
                    window.MydCart.saveStoredCart();
                    window.MydCart.setFLoatCart();
                    window.MydCart.setItemsToCheckout();
                }

                // Restaura dados do usuário
                if (sessionData.userData) {
                    localStorage.setItem(this.userDataKey, JSON.stringify(sessionData.userData));
                    if (window.MydCheckout) {
                        window.MydCheckout.fillCheckoutFromLocalStorage();
                    }
                }

                // Restaura dados do pedido
                if (sessionData.orderData && window.MydOrder) {
                    window.MydOrder.id = sessionData.orderId;
                    if (sessionData.orderData.data) {
                        window.MydOrder.updateProprieties(sessionData.orderData.data);
                    }
                }

                // Restaura step do checkout
                if (sessionData.checkoutStep && sessionData.checkoutStep.isCheckoutOpen) {
                    setTimeout(() => {
                        if (window.MydCheckout) {
                            window.MydCheckout.open();
                            
                            // Restaura step específico se necessário
                            if (sessionData.checkoutStep.content === 'myd-cart-payment') {
                                window.MydCheckout.goTo('orderComplete');
                            }
                        }
                    }, 100);
                }

                // Dispara eventos para atualizar a interface
                window.dispatchEvent(new CustomEvent('MydSessionRecovered', {
                    detail: sessionData
                }));

                this.showSuccessMessage('Pedido recuperado com sucesso! Continue de onde parou.');

            } catch (error) {
                console.error('Erro ao recuperar sessão:', error);
                this.showErrorMessage('Erro ao recuperar pedido. Tente novamente.');
            }
        }

        setupRealTimeSaving() {
            // Salva dados a cada mudança nos inputs do checkout
            const checkoutContainer = document.querySelector('.myd-checkout');
            if (checkoutContainer) {
                checkoutContainer.addEventListener('input', this.debounce(() => {
                    this.updateOrderSession();
                }, 1000));

                checkoutContainer.addEventListener('change', () => {
                    this.updateOrderSession();
                });
            }
        }

        addRecoveryButton() {
            // Adiciona botão para recuperar sessão manualmente (se houver)
            const sessionData = this.getOrderSession();
            if (sessionData && sessionData.orderId) {
                const recoveryButton = document.createElement('button');
                recoveryButton.className = 'myd-recovery-button';
                recoveryButton.innerHTML = '🔄 Recuperar Pedido';
                recoveryButton.title = `Recuperar pedido #${sessionData.orderId}`;
                
                recoveryButton.addEventListener('click', () => {
                    this.showSessionRecoveryDialog(sessionData);
                });

                // Adiciona o botão em local visível
                const header = document.querySelector('.myd-delivery-header, .site-header, header');
                if (header) {
                    header.appendChild(recoveryButton);
                }
            }
        }

        async fetchServerDraftIfNone() {
            try {
                // se já existe sessão local, não fazer nada
                const sessionData = this.getOrderSession();
                if (sessionData) return;

                // chama AJAX para buscar rascunho do usuário
                const resp = await fetch(ajax_object.ajax_url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=myd_get_customer_draft&sec=${ajax_object.order_nonce}`
                });

                if (!resp.ok) return;
                const json = await resp.json();
                if (!json || !json.success || !json.data || !json.data.id) return;

                // monta estrutura compatível com saveOrderSession
                const data = {
                    timestamp: Date.now(),
                    orderId: json.data.id,
                    orderData: json.data,
                    cart: json.data.cart || null,
                    userData: this.getUserData(),
                    checkoutStep: this.getCurrentCheckoutStep(),
                    paymentData: this.getPaymentData(),
                    url: window.location.href
                };

                // garante que MydOrder tenha o id para evitar criar novo rascunho
                if (window.MydOrder) {
                    window.MydOrder.id = json.data.id;
                    if (json.data.cart && window.MydCart) {
                        window.MydCart.items = json.data.cart.items || [];
                        window.MydCart.total = json.data.cart.total || 0;
                        window.MydCart.itemsQuantity = json.data.cart.itemsQuantity || 0;
                        window.MydCart.saveStoredCart && window.MydCart.saveStoredCart();
                    }
                }

                // salva sessão local para evitar criação de novo rascunho
                localStorage.setItem(this.sessionKey, JSON.stringify(data));
                sessionStorage.setItem(this.sessionKey, JSON.stringify(data));

                // dispara evento para que front atualize
                window.dispatchEvent(new CustomEvent('MydDraftOrderCreated', { detail: json }));
            } catch (e) {
                // fail silently
            }
        }

        showSuccessMessage(message) {
            if (window.Myd && window.Myd.notificationBar) {
                window.Myd.notificationBar('success', message);
            } else {
                alert(message);
            }
        }

        showErrorMessage(message) {
            if (window.Myd && window.Myd.notificationBar) {
                window.Myd.notificationBar('error', message);
            } else {
                alert(message);
            }
        }

        // Utility function
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // Inicializa o sistema
    window.addEventListener('load', () => {
        window.MydOrderSessionPersistence = new OrderSessionPersistence();
    });

    // Também inicializa se DOM já estiver pronto
    if (document.readyState !== 'loading') {
        window.MydOrderSessionPersistence = new OrderSessionPersistence();
    }

})();
