/**
 * MYD Manual Number Input Restrictor
 * Garante que o campo de número manual aceite apenas números e máximo 5 dígitos
 */
(function() {
    'use strict';
    
    function restrictManualNumberInput() {
        const manualNumberInput = document.getElementById('input-delivery-manual-number');
        if (!manualNumberInput) return;
        
        // Função para permitir apenas números
        function onlyNumbers(e) {
            // Permitir teclas especiais: backspace, delete, tab, escape, enter
            if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                // Permitir Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true) ||
                // Permitir home, end, left, right
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
            }
            // Garantir que é um número (0-9)
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        }
        
        // Função para limitar a 5 dígitos
        function limitLength(e) {
            const value = e.target.value;
            if (value.length > 5) {
                e.target.value = value.slice(0, 5);
            }
            
            // Remover caracteres não numéricos
            const numericValue = value.replace(/[^0-9]/g, '');
            if (numericValue !== value) {
                e.target.value = numericValue.slice(0, 5);
            }
        }
        
        // Função para validar na colagem
        function onPaste(e) {
            setTimeout(function() {
                const value = e.target.value;
                const numericValue = value.replace(/[^0-9]/g, '').slice(0, 5);
                e.target.value = numericValue;
            }, 10);
        }
        
        // Adicionar event listeners
        manualNumberInput.addEventListener('keydown', onlyNumbers);
        manualNumberInput.addEventListener('input', limitLength);
        manualNumberInput.addEventListener('paste', onPaste);
        
        // Garantir atributos corretos
        manualNumberInput.setAttribute('inputmode', 'numeric');
        manualNumberInput.setAttribute('pattern', '[0-9]{1,5}');
        
        console.log('MYD: Manual number input restrictions applied');
    }
    
    // Executar quando DOM estiver pronto
    function init() {
        restrictManualNumberInput();
        
        // Re-aplicar se o campo for adicionado dinamicamente
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        if (node.id === 'input-delivery-manual-number' || 
                            (node.querySelector && node.querySelector('#input-delivery-manual-number'))) {
                            setTimeout(restrictManualNumberInput, 100);
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
    
    // Garantir execução
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();