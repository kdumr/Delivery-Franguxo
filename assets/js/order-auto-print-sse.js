// Listener SSE para impressão automática ao status 'confirmed'
(function(){
  if (!(window.electronAPI && typeof window.electronAPI.printOrderReceipt === "function")) return;
  // Altere para o domínio do seu servidor
  var sseUrl = 'https://dev.franguxo.app.br/includes/sse-order-status.php';
  var lastPrinted = {};
  try {
    console.log('[SSE] Conectando a', sseUrl);
    var es = new EventSource(sseUrl);
    es.addEventListener('confirmed', function(e) {
      console.log('[SSE] Evento recebido:', e);
      var data = {};
      try { data = JSON.parse(e.data); } catch(err) { console.warn('[SSE] Erro ao parsear data:', err, e.data); return; }
      console.log('[SSE] Dados do evento:', data);
      if (!data.order_id || lastPrinted[data.order_id] === data.timestamp) {
        console.log('[SSE] Ignorando evento duplicado ou sem order_id');
        return;
      }
      lastPrinted[data.order_id] = data.timestamp;
      // Busca dados completos do pedido e imprime
      console.log('[SSE] Buscando dados do pedido para impressão:', data.order_id);
      fetchOrderDataFromAPI(data.order_id).then(function(orderData) {
        console.log('[SSE] Dados do pedido recebidos, enviando para impressão:', orderData);
        window.electronAPI.printOrderReceipt(orderData);
      }).catch(function(err){
        console.warn('[SSE] Erro ao buscar dados do pedido:', err);
      });
    });
    es.onerror = function(err) {
      console.warn('[SSE] Erro de conexão/evento:', err);
    };
  } catch(e) { console.warn('[SSE] não suportado:', e); }
})();
