// checkout-blocker.js
// Bloqueia cliques duplicados no botão de avanço de checkout e exibe um overlay "Carregando..."
(function(){
  if (typeof window === 'undefined') return;

  var isRequestActive = false;
  var activeCount = 0; // número de requisições admin-ajax ativas

  // Overlay functions removed

  function hideOverlay(){
    // Função para remover qualquer overlay existente
    try{
      var overlays = document.querySelectorAll('.myd-checkout-overlay');
      overlays.forEach(function(overlay){
        if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
      });
    }catch(e){}
  }

  function setRequestState(state){
    // se state === 'inc' incrementa, se 'dec' decrementa, se boolean seta direto
    if (state === 'inc') {
      activeCount = Math.max(0, activeCount) + 1;
    } else if (state === 'dec') {
      activeCount = Math.max(0, activeCount - 1);
    } else {
      isRequestActive = !!state;
      activeCount = isRequestActive ? 1 : 0;
    }

    isRequestActive = activeCount > 0;
    var btn = document.querySelector('.myd-cart__button');
    if (btn) btn.setAttribute('aria-busy', isRequestActive ? 'true' : 'false');
    // Overlay removido - não mostra mais o overlay de carregamento
  }

  // Handler de clique que previne múltiplos eventos ao mesmo tempo
  function onNextClick(e){
    var btn = e.currentTarget;
    if (isRequestActive){
      e.preventDefault();
      e.stopPropagation();
      return false;
    }
    // Não bloquear por tamanho do telefone aqui — removido bloqueio de 10/11 dígitos.
    try {
      // Apenas normalize o valor bruto em dataset.raw para uso posterior (sem validar/impedir envio)
      var phoneInput = document.getElementById('input-customer-phone');
      if (phoneInput) phoneInput.dataset.raw = (phoneInput.value || '').replace(/\D/g, '');
    } catch(err) { /* não-fatal */ }

    // marca como ativo e deixa o fluxo original seguir
    // Não ativamos aqui diretamente; ativamos apenas se uma requisição para admin-ajax for feita.
    // Mantemos um fallback caso o fluxo não dispare a chamada AJAX.
    window.__myd_checkout_blocker_fallback = setTimeout(function(){
      // força reset caso nada tenha sido detectado
      activeCount = 0; setRequestState(false);
    }, 20000);
  }

  // Quando eventos MyD que indicam fim/avanço ocorrerem, desbloqueia
  function onMydDraftOrderCreated(){ setRequestState(false); }
  function onMydCheckoutPlacePayment(){ setRequestState(false); }

  // --- Bloqueio de voltar após pedido concluído ---
  var orderFinalized = false;
  var popstateHandler = null;

  function disableBackButtons(){
    var sels = ['.myd-cart__back', '.myd-cart-back', '.myd-back-btn', '.myd-cart__nav-back'];
    sels.forEach(function(s){
      var els = document.querySelectorAll(s);
      Array.prototype.forEach.call(els, function(el){
        try{ el.__myd_prev_onclick = el.onclick; el.onclick = function(e){ e.preventDefault(); return false; }; el.setAttribute('aria-disabled','true'); }catch(e){}
      });
    });
  }

  function enableBackPrevention(){
    if (orderFinalized) return;
    orderFinalized = true;
    // empurra um estado extra para evitar voltar direto
    try{ history.pushState({myd_order_final:true}, '', location.href); }catch(e){}

    // handler que reempurra o estado caso o usuário tente voltar
    popstateHandler = function(e){
      try{
        // se for tentativa de voltar do estado final, reempurra e mantém na página
        if (e && e.state && e.state.myd_order_final) {
          history.pushState({myd_order_final:true}, '', location.href);
        } else {
          // seja conservador: sempre reempurrar para evitar voltar à tela anterior
          history.pushState({myd_order_final:true}, '', location.href);
        }
      }catch(err){ /* swallow */ }
    };
    window.addEventListener('popstate', popstateHandler);
    disableBackButtons();
    // marca visual opcional no cart
    var cart = document.querySelector('.myd-cart'); if (cart) cart.classList.add('myd-order-finalized');
  }

  function disableBackPrevention(){
    if (!orderFinalized) return;
    orderFinalized = false;
    try{ window.removeEventListener('popstate', popstateHandler); popstateHandler = null; }catch(e){}
    var cart = document.querySelector('.myd-cart'); if (cart) cart.classList.remove('myd-order-finalized');
    // restore onclicks if stored
    var sels = ['.myd-cart__back', '.myd-cart-back', '.myd-back-btn', '.myd-cart__nav-back'];
    sels.forEach(function(s){
      var els = document.querySelectorAll(s);
      Array.prototype.forEach.call(els, function(el){
        try{ if (el.__myd_prev_onclick) el.onclick = el.__myd_prev_onclick; el.removeAttribute('aria-disabled'); }catch(e){}
      });
    });
  }

  // Delegated handler to catch dynamic nav-back elements
  document.addEventListener('click', function(e){
    try{
      if (!orderFinalized) return;
      var target = e.target;
      // percorre ancestors até document
      while(target && target !== document){
        if (target.matches && target.matches('.myd-cart__nav-back')){
          e.preventDefault(); e.stopPropagation(); return false;
        }
        target = target.parentNode;
      }
    }catch(err){}
  }, true);

  // Ao finalizar o pedido, ativamos a prevenção de voltar
  function onOrderFinalized(){
    setRequestState(false);
    try{ enableBackPrevention(); }catch(e){}
  }

  // Intercepta fetch para detectar fim de chamadas originadas pelo frontend
  if (window.fetch){
    var origFetch = window.fetch.bind(window);
    window.fetch = function(){
      try{
        var url = arguments[0];
        var inputUrl = '';
        try{ inputUrl = (typeof url === 'string') ? url : (url && url.url) || ''; }catch(e){}

        var opts = arguments[1] || {};
        var body = opts.body || '';

        // Detecta chamada de criação de rascunho de pedido específica
        var isCreateDraft = (typeof body === 'string' && body.indexOf('action=myd_create_draft_order') !== -1) || (inputUrl && inputUrl.indexOf('admin-ajax.php') !== -1 && typeof body === 'string' && body.indexOf('myd_create_draft_order') !== -1);

        // Se for a criação de rascunho, apenas normalize o telefone (não bloqueia)
        if (isCreateDraft) {
          try {
            var phoneInput = document.getElementById('input-customer-phone');
            if (phoneInput) phoneInput.dataset.raw = (phoneInput.value || '').replace(/\D/g, '');
          } catch(err) { /* swallow */ }
        }

        var isAdminAjax = inputUrl && inputUrl.indexOf('admin-ajax.php') !== -1;
        if (isAdminAjax) {
          // incrementa contador ao iniciar
          setRequestState('inc');
        }

        var prom = origFetch.apply(this, arguments);
        prom.then(function(){ if (isAdminAjax) setRequestState('dec'); }).catch(function(err){
          if (isAdminAjax) {
            // Overlay de erro removido - não mostra mais mensagem de erro de rede
            setRequestState('dec');
          }
        });
        return prom;
      }catch(e){
        return origFetch.apply(this, arguments);
      }
    };
  }

  // Intercepta XMLHttpRequest para capturar jQuery.ajax ou XHR diretas
  (function(){
    if (!window.XMLHttpRequest) return;
    var origOpen = XMLHttpRequest.prototype.open;
    var origSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function(method, url){
      try{ this.__myd_xhr_url = url ? String(url) : ''; }catch(e){ this.__myd_xhr_url = ''; }
      return origOpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function(){
      var isAdmin = this.__myd_xhr_url && this.__myd_xhr_url.indexOf('admin-ajax.php') !== -1;
      if (isAdmin) {
        setRequestState('inc');
        var self = this;
        var cleanup = function(){ try{ setRequestState('dec'); }catch(e){} };
        var errorHandler = function(){
          try{
            // Overlay de erro removido - não mostra mais mensagem de erro de rede
            setRequestState('dec');
          }catch(e){}
        };
        this.addEventListener('load', cleanup);
        this.addEventListener('error', errorHandler);
        this.addEventListener('abort', cleanup);
      }
      return origSend.apply(this, arguments);
    };
  })();

  // Observa DOM para garantir que o botão seja ligado mesmo após re-render
  function attach(){
    var btn = document.querySelector('.myd-cart__button');
    if (btn && !btn.__myd_blocker_attached){
      btn.addEventListener('click', onNextClick, true);
      btn.__myd_blocker_attached = true;
    }
  }

  window.addEventListener('DOMContentLoaded', function(){
    attach();
  });

  // Também tenta reaplicar quando o conteúdo muda (p.ex. SPA behavior)
  var mo = new MutationObserver(function(){ attach(); });
  mo.observe(document.body, { childList: true, subtree: true });

  // Eventos customizados do sistema
  window.addEventListener('MydDraftOrderCreated', function(){ onMydDraftOrderCreated(); onOrderFinalized(); });
  window.addEventListener('MydCheckoutPlacePayment', function(){ onMydCheckoutPlacePayment(); onOrderFinalized(); });

  // CSS do overlay removido

})();
