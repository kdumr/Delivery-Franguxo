// Diagnóstico para preencher o campo de nome do cliente no checkout
(function() {
    function tryFillName() {
        var input = document.getElementById('input-customer-name');
        var user = window.SimpleAuth && window.SimpleAuth.currentUser;
        if (input && user && user.name) {
            input.value = user.name;
            input.readOnly = true;
            input.style.background = '#e6ffe6'; // visual para teste
            console.log('[DIAG] Preenchido input-customer-name:', user.name);
        } else {
            console.log('[DIAG] input-customer-name não preenchido:', {input, user});
        }
    }
    window.addEventListener('DOMContentLoaded', tryFillName);
    document.addEventListener('click', function() {
        setTimeout(tryFillName, 400);
    });
    setInterval(tryFillName, 1500);
})();
