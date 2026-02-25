<?php
// SSE endpoint para notificar pedidos confirmados
// Salve como sse-order-status.php na raiz do seu projeto ou em /includes

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

// Permite CORS para Electron local
header('Access-Control-Allow-Origin: *');

// Caminho para o arquivo de controle de status (pode ser um cache, banco, etc)
$status_file = __DIR__ . '/sse-order-status-cache.json';

// Função para obter o último status salvo
function get_last_status() {
    global $status_file;
    if (!file_exists($status_file)) return [];
    $json = file_get_contents($status_file);
    return json_decode($json, true) ?: [];
}

// Função para salvar novo status
function set_last_status($data) {
    global $status_file;
    file_put_contents($status_file, json_encode($data));
}

// Loop SSE
$last_status = get_last_status();
while (true) {
    // Aqui você pode buscar do banco, cache, etc. Exemplo: checar arquivo atualizado por outro processo
    clearstatcache();
    $current_status = get_last_status();
    if ($current_status !== $last_status) {
        // Envia evento SSE para mudança de status geral
        echo "event: status_changed\n";
        echo 'data: ' . json_encode($current_status) . "\n\n";
        // Se for confirmado, também envia evento específico
        if ($current_status['status'] === 'confirmed') {
            echo "event: confirmed\n";
            echo 'data: ' . json_encode($current_status) . "\n\n";
        }
        if ( ob_get_level() > 0 ) {
            ob_flush();
        }
        flush();
        $last_status = $current_status;
    }
    sleep(2); // Checa a cada 2 segundos
}
?>
