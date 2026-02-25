// Script para tabs de métodos de pagamento

document.addEventListener('DOMContentLoaded', function() {
  var tabButtons = document.querySelectorAll('.myd-payment-tabs li');
  var tabContents = document.querySelectorAll('.myd-payment-tab-content');

  tabButtons.forEach(function(btn) {
    btn.addEventListener('click', function() {
      // Remove active de todos
      tabButtons.forEach(function(b) {
        b.classList.remove('active');
        b.style.color = '#888';
        b.style.fontWeight = '';
        b.style.borderBottom = '';
      });
      tabContents.forEach(function(tc) { tc.style.display = 'none'; });

      // Ativa o clicado
      btn.classList.add('active');
      btn.style.color = '#e53935';
      btn.style.fontWeight = 'bold';
      btn.style.borderBottom = '2px solid #e53935';
      var tab = btn.getAttribute('data-tab');
      var content = document.getElementById('tab-' + tab);
      if(content) content.style.display = '';
    });
  });
});
