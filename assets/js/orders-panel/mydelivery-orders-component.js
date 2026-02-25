/**
 * MyDelivery Orders Web Component
 * 
 * Encapsula o painel de pedidos em um Web Component customizado
 * para melhor organização e extensibilidade futura.
 * 
 * @since 1.0.0
 */

class MyDeliveryOrders extends HTMLElement {
    constructor() {
        super();

        // Usar Light DOM para manter compatibilidade com estilos e scripts existentes
        // Shadow DOM seria isolado demais para a estrutura atual
        this.orders = [];
        this.observers = [];
    }

    connectedCallback() {
        // Executado quando o elemento é adicionado ao DOM
        console.log('[MyDeliveryOrders] Component connected');

        // Adicionar classe para identificação
        this.classList.add('myd-orders-component');

        // Inicializar observadores para mudanças no DOM
        this.initObservers();

        // Emitir evento customizado indicando que o componente está pronto
        this.dispatchEvent(new CustomEvent('mydelivery-orders-ready', {
            bubbles: true,
            detail: { component: this }
        }));
    }

    disconnectedCallback() {
        // Executado quando o elemento é removido do DOM
        console.log('[MyDeliveryOrders] Component disconnected');

        // Limpar observadores
        this.cleanupObservers();
    }

    /**
     * Inicializa observadores para monitorar mudanças no painel
     */
    initObservers() {
        // Observar mudanças nos pedidos (adições/remoções)
        const ordersLoop = this.querySelector('.fdm-orders-loop');
        if (ordersLoop && window.MutationObserver) {
            const observer = new MutationObserver((mutations) => {
                this.handleOrdersChange(mutations);
            });

            observer.observe(ordersLoop, {
                childList: true,
                subtree: true
            });

            this.observers.push(observer);
        }
    }

    /**
     * Limpa todos os observadores
     */
    cleanupObservers() {
        this.observers.forEach(observer => observer.disconnect());
        this.observers = [];
    }

    /**
     * Manipula mudanças nos pedidos
     * @param {MutationRecord[]} mutations 
     */
    handleOrdersChange(mutations) {
        // Emitir evento quando pedidos mudarem
        this.dispatchEvent(new CustomEvent('orders-changed', {
            bubbles: true,
            detail: { mutations }
        }));
    }

    /**
     * Retorna todos os pedidos atualmente visíveis
     * @returns {NodeListOf<Element>}
     */
    getAllOrders() {
        return this.querySelectorAll('.fdm-orders-items');
    }

    /**
     * Retorna pedidos por status
     * @param {string} status - Status do pedido (new, confirmed, in-delivery, done, etc.)
     * @returns {NodeListOf<Element>}
     */
    getOrdersByStatus(status) {
        return this.querySelectorAll(`.fdm-orders-items[data-order-status="${status}"]`);
    }

    /**
     * Retorna um pedido específico por ID
     * @param {string|number} orderId 
     * @returns {Element|null}
     */
    getOrderById(orderId) {
        return this.querySelector(`#${orderId}`);
    }

    /**
     * Retorna contagem de pedidos por seção
     * @returns {Object}
     */
    getOrderCounts() {
        return {
            new: this.getOrdersByStatus('new').length,
            production: this.querySelectorAll('[data-order-status="confirmed"], [data-order-status="waiting"]').length,
            inDelivery: this.getOrdersByStatus('in-delivery').length,
            done: this.querySelectorAll('[data-order-status="done"], [data-order-status="finished"], [data-order-status="canceled"]').length,
            total: this.getAllOrders().length
        };
    }

    /**
     * Atualiza a interface do componente
     */
    refresh() {
        // Emitir evento de refresh
        this.dispatchEvent(new CustomEvent('orders-refresh', {
            bubbles: true
        }));
    }
}

// Registrar o Web Component
if (!customElements.get('mydelivery-orders')) {
    customElements.define('mydelivery-orders', MyDeliveryOrders);
    console.log('[MyDeliveryOrders] Web Component registered successfully');
} else {
    console.warn('[MyDeliveryOrders] Component already registered');
}

// Expor globalmente para compatibilidade
window.MyDeliveryOrders = MyDeliveryOrders;
