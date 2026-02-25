/**
 * Funcionalidade para destacar pedidos novos até serem clicados
 * MyD Delivery Pro
 */

(function() {
    'use strict';

    // Classe para gerenciar destacamento de pedidos novos
    class NewOrderHighlight {
        constructor() {
            this.clickedOrdersKey = 'myd_clicked_orders';
            this.clickedOrders = this.getClickedOrders();
            this.cleanupOldOrders();
            this.cleanupOldOrdersByDate();
            this.init();
        }

        init() {
            // Aguarda o carregamento completo da página
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
            } else {
                this.setupEventListeners();
            }

            // Observer para detectar novos pedidos adicionados dinamicamente
            this.observeOrderChanges();
            
            // Escuta evento customizado para quando novos pedidos chegam via AJAX
            window.addEventListener('MydOrdersUpdated', () => {
                this.setupEventListeners();
            });
        }

        setupEventListeners() {
            // Remove destaque de pedidos já clicados
            this.removeHighlightFromClickedOrders();

            // Adiciona listener para cliques em pedidos
            this.addOrderClickListeners();
        }

        getClickedOrders() {
            try {
                const stored = localStorage.getItem(this.clickedOrdersKey);
                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                console.warn('Erro ao carregar pedidos clicados do localStorage:', e);
                return [];
            }
        }

        saveClickedOrders() {
            try {
                localStorage.setItem(this.clickedOrdersKey, JSON.stringify(this.clickedOrders));
            } catch (e) {
                console.warn('Erro ao salvar pedidos clicados no localStorage:', e);
            }
        }

        cleanupOldOrders() {
            // Remove pedidos clicados que não existem mais na página
            const currentOrderIds = Array.from(document.querySelectorAll('.fdm-orders-items')).map(item => item.id);
            const filteredOrders = this.clickedOrders.filter(orderId => currentOrderIds.includes(orderId));
            
            if (filteredOrders.length !== this.clickedOrders.length) {
                this.clickedOrders = filteredOrders;
                this.saveClickedOrders();
            }
        }

        addClickedOrder(orderId) {
            if (!this.clickedOrders.includes(orderId)) {
                this.clickedOrders.push(orderId);
                this.saveClickedOrders();
            }
        }

        removeHighlightFromClickedOrders() {
            const orderItems = document.querySelectorAll('.fdm-orders-items');
            
            orderItems.forEach(item => {
                const orderId = item.id;
                const status = item.getAttribute('data-order-status');
                
                if (this.clickedOrders.includes(orderId)) {
                    this.removeHighlight(item);
                } else if (status === 'new' && !item.hasAttribute('data-order-clicked')) {
                    if (!item.classList.contains('fdm-new-unclicked')) {
                        item.classList.add('fdm-new-unclicked');
                    }
                }
            });
        }

        removeHighlight(orderItem) {
            // Remove a classe CSS
            orderItem.classList.remove('fdm-new-unclicked');
            
            // Remove o CSS inline que pode estar aplicado
            orderItem.removeAttribute('style');
            
            // Marca como clicado alterando o data-order-status para debug
            orderItem.setAttribute('data-order-clicked', 'true');
        }

        addOrderClickListeners() {
            // Adiciona listener no container pai para capturar cliques em todos os pedidos
            const ordersContainer = document.querySelector('.fdm-orders-loop');
            if (ordersContainer) {
                ordersContainer.addEventListener('click', (e) => {
                    const orderItem = e.target.closest('.fdm-orders-items');
                    if (orderItem) {
                        this.handleOrderClick(orderItem);
                    }
                });
            }
        }

        handleOrderClick(orderItem) {
            const orderId = orderItem.id;
            
            // Remove o destaque visual
            this.removeHighlight(orderItem);
            
            // Adiciona à lista de pedidos clicados
            this.addClickedOrder(orderId);
        }

        observeOrderChanges() {
            // Observer para detectar quando novos pedidos são adicionados
            const ordersContainer = document.querySelector('.fdm-orders-loop');
            if (ordersContainer) {
                const observer = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'childList') {
                            // Processa novos nós adicionados
                            mutation.addedNodes.forEach((node) => {
                                if (node.nodeType === Node.ELEMENT_NODE) {
                                    // Se é um novo pedido ou contém novos pedidos
                                    const newOrders = node.classList && node.classList.contains('fdm-orders-items') 
                                        ? [node] 
                                        : node.querySelectorAll ? node.querySelectorAll('.fdm-orders-items') : [];
                                    
                                    newOrders.forEach(orderItem => {
                                        const orderId = orderItem.id;
                                        
                                        // Se já foi clicado, remove destaque
                                        if (this.clickedOrders.includes(orderId)) {
                                            this.removeHighlight(orderItem);
                                        }
                                    });
                                }
                            });
                        }
                    });
                });

                observer.observe(ordersContainer, {
                    childList: true,
                    subtree: true
                });
                
                // Monitora mudanças via AJAX também
                const originalRemove = Element.prototype.remove;
                const self = this;
                Element.prototype.remove = function() {
                    if (this.classList && this.classList.contains('fdm-orders-items')) {
                        // Dispara evento quando pedidos são removidos (indica atualização)
                        setTimeout(() => {
                            window.dispatchEvent(new Event('MydOrdersUpdated'));
                        }, 100);
                    }
                    return originalRemove.apply(this, arguments);
                };
            }
        }

        // Limpa automaticamente pedidos antigos baseado em data (opcional)
        cleanupOldOrdersByDate() {
            try {
                const oneWeekAgo = Date.now() - (7 * 24 * 60 * 60 * 1000); // 7 dias atrás
                const lastCleanup = localStorage.getItem('myd_last_cleanup');
                
                // Se não fez limpeza nos últimos 7 dias
                if (!lastCleanup || parseInt(lastCleanup) < oneWeekAgo) {
                    // Aqui você poderia implementar lógica para verificar data dos pedidos
                    // Por simplicidade, vamos apenas marcar que a limpeza foi feita
                    localStorage.setItem('myd_last_cleanup', Date.now().toString());
                }
            } catch (e) {
                console.warn('Erro na limpeza automática:', e);
            }
        }

        // Método público para limpar pedidos clicados (útil para reset manual)
        clearClickedOrders() {
            this.clickedOrders = [];
            this.saveClickedOrders();
            
            // Remove atributos de clicado e restaura destaque para todos os pedidos novos
            const allOrders = document.querySelectorAll('.fdm-orders-items[data-order-status="new"]');
            allOrders.forEach(orderItem => {
                orderItem.removeAttribute('data-order-clicked');
                if (!orderItem.classList.contains('fdm-new-unclicked')) {
                    orderItem.classList.add('fdm-new-unclicked');
                }
            });
        }
    }

    // Inicializa quando a página carrega
    window.addEventListener('load', () => {
        window.MydNewOrderHighlight = new NewOrderHighlight();
    });

    // Também inicializa se o DOM já estiver pronto
    if (document.readyState !== 'loading') {
        window.MydNewOrderHighlight = new NewOrderHighlight();
    }

})();
