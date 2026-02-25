Registro de alterações — Trabalhos executados em 19/01/2026
=========================================================

Este arquivo documenta todas as alterações realizadas hoje no repositório "Sistema Delivery Franguxo". O objetivo é registrar passo-a-passo o que foi modificado, por quê, onde e como testar — para que uma IA (ou desenvolvedor) possa reproduzir essas mudanças no futuro.

Resumo rápido
-------------
- Corrigi concorrência de status ao setar `order_status` via webhook do MercadoPago
- Implementei verificação para só trocar `order_status` para `new` quando o status atual for vazio ou `started` (evita sobrescrever alterações feitas pelo polling do navegador)
- Tornei `ensure_initial_status` mais robusto (tratamento de arrays, fallback e opção de sobrescrever `started`)
- Adicionei logs de debug no handler do MercadoPago para diagnosticar por que o status não mudava
- Garanti que o badge do painel mostre sempre o tempo (minutos) — removi substituição por texto de status
- Ajustei o polling visual para atualizar a cada 30s e criar elementos `.myd-order-minutes` caso faltem
- Passei a ordenar pedidos por timestamp de alteração de status (mais recente primeiro) tanto no frontend (JS) quanto no backend (meta `order_status_changed_ts`)
- Registrei mudancas de status no meta `order_notes` e gravei também `order_status_changed_ts` para ordenação server-side

Arquivos alterados (principais)
--------------------------------
- includes/class-mercadopago-webhook-handler.php
- includes/class-order-meta.php
- templates/order/order-list.php
- templates/order/list-item.php
- templates/order/panel.php

Detalhes por arquivo
---------------------

1) includes/class-mercadopago-webhook-handler.php
- Objetivo: evitar que o webhook sobrescreva um status que já tenha sido alterado pelo navegador/polling.
- Alterações principais:
  - Substituí chamadas diretas a:

    ```php
    update_post_meta( $order_id, 'order_status', 'new' );
    ```

    por:

    ```php
    Order_Meta::ensure_initial_status( $order_id, 'new', array( '', 'started' ) );
    ```

    Isto seta `new` apenas se o status atual estiver vazio ou for `started`.

  - Adicionei logs de debug em torno da chamada a `ensure_initial_status` para registrar o valor antes/depois e o resultado:

    ```php
    error_log('[MYD][MP Webhook Handler] Pre-ensure order_status for ' . $order_id . ' is: ' . print_r($pre_status, true));
    $set_result = Order_Meta::ensure_initial_status(...);
    error_log('[MYD][MP Webhook Handler] ensure_initial_status returned: ' . ($set_result ? 'true' : 'false'));
    ```

2) includes/class-order-meta.php
- Objetivo: tornar o gerenciamento de status robusto e registrar notas/timestamps quando o status muda.
- Alterações principais:
  - Tratamento de `get_post_meta` retornando array: se `order_status` for um array, usamos o primeiro elemento para normalização.
  - `ensure_initial_status`:
    - Agora aceita um array `only_if_current_in` e, quando o status atual é permitido (ex.: `started`) sobrescreve explicitamente (antes apenas tentava `add_post_meta` atomico que falhava quando a meta já existia).
    - Comportamento: quando `only_if_current_in` é array e o status atual está presente, atualiza com `update_post_meta` em vez de falhar.
  - Hooks e persistência de nota:
    - Adicionei `init_hooks()` que registra `added_post_meta` e `updated_post_meta` e chama `record_status_change_note()` quando a meta alterada é `order_status`.
    - `record_status_change_note()` acrescenta um item em `order_notes` com tipo `status`, texto e data legível.
    - Ao gravar a nota, também salvo `order_status_changed_ts` (timestamp numérico via `current_time('timestamp')`) para permitir ordenação server-side.

  - Trecho chave inserido:

    ```php
    update_post_meta( $order_id, 'order_notes', $notes );
    update_post_meta( $order_id, 'order_status_changed_ts', (int) current_time( 'timestamp' ) );
    ```

3) templates/order/order-list.php
- Objetivo: ordenar os pedidos nas seções do painel pelo timestamp de alteração de status (mais recente primeiro) e ajustar renderização.
- Alterações principais:
  - Adicionei função PHP `myd_get_order_status_changed_ts($postid)` que retorna `order_status_changed_ts` (fallback para `order_date` se não existir).
  - Antes de renderizar os accordions, faço `usort` em cada seção usando o timestamp retornado, ordenando descendentemente (mais recente primeiro).
  - Garante que, ao recarregar a página, os pedidos que mudaram de status mais recentemente venham primeiro.

4) templates/order/list-item.php
- Objetivo: garantir que o badge exiba sempre o tempo (minutos) e não seja sobrescrito por textos de status.
- Alterações principais:
  - Substituí a saída que imprimia `status_data['text']` pelo markup de minutos:

    ```php
    <div class="myd-order-minutes"><?php echo intval( $minutes ); ?></div>
    <div class="myd-order-minutes-unit">min</div>
    ```

  - Calculo dos minutos e seleção de cor (verde/laranja/vermelho) permanecem, mas agora sempre renderizados aqui no HTML inicial.

5) templates/order/panel.php (JS de painel)
- Objetivo: garantir atualização visual consistente via websocket e polling, preservar o contador de minutos e ordenamento por alterações de status em tempo real.
- Alterações principais (JS):
  - Garante criação de `.myd-order-minutes` e `.myd-order-minutes-unit` quando o socket/DOM tenta atualizar o badge.
  - Mudei o handler do evento `socket.on('order.status')` para NÃO sobrescrever o conteúdo do badge com `data.text`. Em vez disso atualiza apenas a cor (background) e define `data-status-label`/`title` para acessibilidade.
  - Adicionei helpers JS:
    - `getPayloadTs(p)` — extrai timestamp do payload (várias formas) para usar como `data-order-status-ts` (ms).
    - `reorderSection(container)` — reordena filhos `.fdm-orders-items` dentro de um container por `data-order-status-ts` (desc).
  - Ao receber `order.status`, o script agora define `item.setAttribute('data-order-status-ts', ts)` (usando `getPayloadTs`) e após mover o item para a seção correta chama `reorderSection(target)`.
  - Ao conectar o socket, inicializo `data-order-status-ts` para itens existentes (a partir de `data-order-ts` se houver) e reordeno cada seção uma vez.
  - Alterei o polling que atualiza minutos para rodar a cada 30s (antes 60s) e criei lógica que insere os elementos `.myd-order-minutes` caso o badge seja substituído por texto.

Trechos JS importantes:

```js
// run every 30 seconds
setInterval(updateBadges, 30 * 1000);

// on socket order.status
item.setAttribute('data-order-status', status);
item.setAttribute('data-order-status-ts', String(getPayloadTs(payload)));
placeOrder(target, item, true);
reorderSection(target);
```

Como testar (passo-a-passo)
-------------------------------
1) Habilitar logs no WordPress

Edite `wp-config.php` e certifique-se destas linhas:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2) Abrir log em tempo real (PowerShell) — na pasta do site:

```powershell
Get-Content -Path "e:\Meus Documentos\Desktop\Sistema Delivery Franguxo\wp-content\debug.log" -Wait -Tail 50
```

Os `error_log()` adicionados ao handler do MercadoPago irão aparecer nesse arquivo para diagnosticar pre/post `order_status`.

3) Testar fluxo webhook
- Reenviar webhook de pagamento do MercadoPago (ou simular via endpoint de webhook do seu ambiente). Observe as linhas de debug que escrevi no handler e verifique se `order_status` mudou para `new` quando apropriado.

4) Testar painel de pedidos
- Recarregue o painel de pedidos no browser:
  - Verifique que badges mostram minutos imediatamente.
  - Mude status de um pedido (via UI, ou via evento websocket simulado) e confirme que: 1) `data-order-status-ts` é atualizado no elemento `.fdm-orders-items`, 2) o item é movido para a seção correta e 3) fica no topo (mais recente) da seção.
  - Recarregue a página e confirme a ordenação server-side (PHP) por `order_status_changed_ts` — pedidos com notas de status mais recentes deverão aparecer primeiro.

Observações e próximos passos recomendados
----------------------------------------
- Autor da mudança: hoje registamos apenas a origem genérica "meta" no note. Podemos enriquecer `record_status_change_note()` para receber e armazenar uma origem mais precisa (ex.: `webhook`, `socket`, `admin-ui`, `api`) — isso exige propagar essa informação ao atualizar `order_status` em pontos específicos (PHP handlers e JS).
- Persistência no servidor: atualmente gravamos `order_status_changed_ts` quando a nota é registrada no servidor. Se atualizações de status ocorrerem apenas no JS (ex.: cliente sem permissão para meta update), garanta que o servidor também receba e persista essa mudança (ou confie somente no JS timestamp para ordenação até uma confirmação server-side).
- Animação visual: se desejar, podemos adicionar uma transição suave quando um item é movido para o topo para facilitar a percepção do operador.

Lista completa de arquivos modificados hoje
-----------------------------------------
- includes/class-mercadopago-webhook-handler.php  (substituição por ensure_initial_status + logs)
- includes/class-order-meta.php                   (melhorias em ensure_initial_status, hooks, gravação de notes e timestamp)
- templates/order/order-list.php                  (ordenamento server-side por `order_status_changed_ts`, polling 30s)
- templates/order/list-item.php                   (badge sempre mostra minutos)
- templates/order/panel.php                       (JS: preservar badge, getPayloadTs, reorderSection, data-order-status-ts, inicialização)


Fim do registro.

Arquivo gerado automaticamente por alterações realizadas em 19/01/2026.
