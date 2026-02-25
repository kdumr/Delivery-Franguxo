/**
 * MYD Autocomplete Disabler
 * Força a desabilitação de autopreenchimento nos campos myd-cart__checkout-input
 */
(function() {
    'use strict';
    
    // Função para desabilitar autocomplete em um campo
    function disableAutocomplete(input) {
        if (!input) return;
        
        // Força atributos
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('data-lpignore', 'true');
        input.setAttribute('data-form-type', 'other');
        
        // Remove autofill em Chrome
        if (input.style) {
            input.style.webkitBoxShadow = '0 0 0 1000px white inset';
            input.style.boxShadow = '0 0 0 1000px white inset';
        }
    }
    
    // Aplicar desabilitação nos campos existentes
    function applyToExistingFields() {
        const inputs = document.querySelectorAll('.myd-cart__checkout-input');
        inputs.forEach(disableAutocomplete);
    }
    
    // Observer para campos adicionados dinamicamente
    function setupMutationObserver() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Verificar se o próprio node é um input
                        if (node.classList && node.classList.contains('myd-cart__checkout-input')) {
                            disableAutocomplete(node);
                        }
                        // Verificar inputs filhos
                        const childInputs = node.querySelectorAll && node.querySelectorAll('.myd-cart__checkout-input');
                        if (childInputs) {
                            childInputs.forEach(disableAutocomplete);
                        }
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Executar quando DOM estiver pronto
    function init() {
        applyToExistingFields();
        setupMutationObserver();
    }
    
    // Garantir execução
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Backup: executar após 1 segundo para garantir
    setTimeout(applyToExistingFields, 1000);
    
})();