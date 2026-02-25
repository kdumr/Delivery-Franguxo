<?php
/**
 * Real-time Access Monitor (logs + SSE + shortcode)
 */

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Access_Monitor {
    const OPT_LOG = 'myd_access_log';        // array of entries (ring buffer)
    const OPT_SEQ = 'myd_access_seq';        // monotonic sequence id
    const MAX_ENTRIES = 300;                 // keep last N

    public function __construct() {
        // Hooks to capture accesses
        \add_action( 'template_redirect', [ $this, 'log_front_request' ], 1 );
        \add_filter( 'rest_request_after_callbacks', [ $this, 'log_rest_request' ], 10, 3 );

        // REST route for SSE stream
        \add_action( 'rest_api_init', [ $this, 'register_routes' ] );

        // Shortcode for monitor page
        \add_shortcode( 'myd_access_monitor', [ $this, 'shortcode_monitor' ] );

        // Admin page (Ferramentas > Monitor de Acessos)
        \add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
    }

    /** Get client IP (best-effort) */
    private function get_client_ip(): string {
    foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ] as $k ) {
            if ( ! empty( $_SERVER[ $k ] ) ) {
                $raw = sanitize_text_field( wp_unslash( $_SERVER[ $k ] ) );
                // If list, take first
                $parts = explode( ',', $raw );
                return trim( $parts[0] );
            }
        }
        return '';
    }

    /**
     * Normalize values for storing in log: sanitize strings and recursively
     * process arrays. Truncate long values to avoid huge option entries.
     */
    private function normalize_for_log( $val, int $max_length = 1000 ) {
        if ( is_array( $val ) ) {
            $out = [];
            $count = 0;
            foreach ( $val as $k => $v ) {
                // avoid insanely large arrays
                if ( $count++ > 200 ) { break; }
                if ( is_array( $v ) ) {
                    $out[ $k ] = $this->normalize_for_log( $v, $max_length );
                } elseif ( is_scalar( $v ) ) {
                    // cast and sanitize
                    $s = (string) $v;
                    $s = \sanitize_text_field( \wp_unslash( $s ) );
                    $out[ $k ] = ( \strlen( $s ) > $max_length ) ? \substr( $s, 0, $max_length ) : $s;
                } else {
                    $s = \wp_json_encode( $v );
                    $out[ $k ] = ( \strlen( $s ) > $max_length ) ? \substr( $s, 0, $max_length ) : $s;
                }
            }
            return $out;
        }
        // scalar or object
        if ( is_scalar( $val ) ) {
            $s = (string) $val;
            $s = \sanitize_text_field( \wp_unslash( $s ) );
            return ( \strlen( $s ) > $max_length ) ? \substr( $s, 0, $max_length ) : $s;
        }
        $s = \wp_json_encode( $val );
        return ( \strlen( $s ) > $max_length ) ? \substr( $s, 0, $max_length ) : $s;
    }

    /** Append log entry to ring buffer */
    private function append_log( array $entry ): void {
    $seq = (int) \get_option( self::OPT_SEQ, 0 );
        $seq++;
        $entry['id'] = $seq;
        $entry['ts'] = time();

    $log = \get_option( self::OPT_LOG, [] );
        if ( ! is_array( $log ) ) { $log = []; }
        $log[] = $entry;
        if ( count( $log ) > self::MAX_ENTRIES ) {
            $log = array_slice( $log, - self::MAX_ENTRIES );
        }
    \update_option( self::OPT_LOG, $log, false );
    \update_option( self::OPT_SEQ, $seq, false );
        // Bust option caches so long-running SSE requests can see updates
        if ( function_exists('wp_cache_delete') ) {
            \wp_cache_delete( self::OPT_LOG, 'options' );
            \wp_cache_delete( self::OPT_SEQ, 'options' );
            \wp_cache_delete( 'alloptions', 'options' );
        }
    }

    /** Log a regular front-end request */
    public function log_front_request(): void {
    if ( \is_admin() ) { return; }
        // Skip REST requests and our own stream
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        if ( strpos( $uri, '/wp-json/' ) === 0 || strpos( $uri, '/wp-json/myd-delivery/v1/access-stream' ) !== false ) {
            return;
        }
        // Avoid logging preview loads of the monitor itself repeatedly (optional flag)
    if ( isset( $_GET['myd_monitor'] ) ) { return; }

    $this->append_log( [
            'type'   => 'web',
            'ip'     => $this->get_client_ip(),
            'method' => isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '',
            'path'   => $uri,
            'ua'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 300 ) : '',
        ] );
    }

    /** Log REST API requests (after handlers run) */
    public function log_rest_request( $response, $handler, $request ) {
        try {
            $route = method_exists( $request, 'get_route' ) ? $request->get_route() : '';
            // Skip our own SSE stream to prevent noise
            if ( strpos( $route, '/access-stream' ) !== false ) { return $response; }
            // Gather richer request data (headers, query, body params, raw body preview)
            $headers = [];
            if ( method_exists( $request, 'get_headers' ) ) {
                $raw_headers = $request->get_headers();
                if ( is_array( $raw_headers ) ) {
                    foreach ( $raw_headers as $hk => $hv ) {
                        if ( is_array( $hv ) ) {
                            $val = isset( $hv[0] ) ? $hv[0] : implode(', ', $hv);
                        } else {
                            $val = $hv;
                        }
                        $headers[ $hk ] = $this->normalize_for_log( $val, 600 );
                    }
                }
            } else {
                // fallback to common server headers
                $fallback = [ 'HTTP_AUTHORIZATION', 'CONTENT_TYPE', 'CONTENT_LENGTH', 'HTTP_USER_AGENT', 'HTTP_X_FORWARDED_FOR' ];
                foreach ( $fallback as $k ) {
                    if ( isset( $_SERVER[ $k ] ) ) {
                        $headers[ $k ] = $this->normalize_for_log( $_SERVER[ $k ], 600 );
                    }
                }
            }

            // Query and body params
            $query = method_exists( $request, 'get_query_params' ) ? $request->get_query_params() : [];
            $body_params = method_exists( $request, 'get_body_params' ) ? $request->get_body_params() : [];
            // Raw body preview (truncated) if available
            $body_raw = '';
            if ( method_exists( $request, 'get_body' ) ) {
                try { $body_raw = (string) $request->get_body(); } catch ( \Throwable $e ) { $body_raw = ''; }
            } elseif ( isset( $GLOBALS['HTTP_RAW_POST_DATA'] ) ) {
                $body_raw = (string) $GLOBALS['HTTP_RAW_POST_DATA'];
            } else {
                // last resort: php://input
                $input = @file_get_contents('php://input');
                if ( $input !== false ) { $body_raw = (string) $input; }
            }

            $entry = [
                'type'   => 'api',
                'ip'     => $this->get_client_ip(),
                'method' => isset( $_SERVER['REQUEST_METHOD'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '',
                'path'   => '/wp-json' . $route,
                'ua'     => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( \sanitize_text_field( \wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 300 ) : '',
                'code'   => is_wp_error( $response ) ? $response->get_error_code() : ( method_exists( $response, 'get_status' ) ? $response->get_status() : 200 ),
                // extra details
                'headers' => $this->normalize_for_log( $headers, 600 ),
                'query'   => $this->normalize_for_log( $query, 800 ),
                    'body'    => $this->normalize_for_log( $body_params, 1200 ),
                    'body_raw_preview' => ( \strlen( $body_raw ) > 1000 ) ? \substr( $body_raw, 0, 1000 ) : $body_raw,
                'content_length' => isset( $_SERVER['CONTENT_LENGTH'] ) ? (int) $_SERVER['CONTENT_LENGTH'] : null,
            ];

                // If this is a MercadoPago webhook, attempt to enrich the log entry with the payment status
                try {
                    if ( strpos( $route, '/mercadopago/webhook' ) !== false || strpos( $entry['path'], '/mercadopago/webhook' ) !== false ) {
                        $mp_payment_id = '';
                        // try structured body params first
                        if ( is_array( $body_params ) && ! empty( $body_params['data']['id'] ) ) {
                            $mp_payment_id = (string) $body_params['data']['id'];
                        } elseif ( is_array( $body_params ) && ! empty( $body_params['id'] ) ) {
                            $mp_payment_id = (string) $body_params['id'];
                        } else {
                            // fallback to raw preview
                            if ( is_string( $body_raw ) && preg_match('/"data"\s*:\s*\{[^}]*"id"\s*:\s*"?(\d+)"?/i', $body_raw, $m) ) {
                                $mp_payment_id = $m[1];
                            } elseif ( is_string( $body_raw ) && preg_match('/"id"\s*:\s*"?(\d+)"?/i', $body_raw, $m2) ) {
                                $mp_payment_id = $m2[1];
                            }
                        }
                        if ( $mp_payment_id ) {
                            $access_token = get_option('mercadopago_access_token', '');
                            if ( $access_token ) {
                                $resp = wp_remote_get('https://api.mercadopago.com/v1/payments/' . rawurlencode($mp_payment_id), [
                                    'headers' => [ 'Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json' ],
                                    'timeout' => 8,
                                ]);
                                if ( ! is_wp_error( $resp ) ) {
                                    $mp_data = json_decode( wp_remote_retrieve_body( $resp ), true );
                                    if ( is_array( $mp_data ) && isset( $mp_data['status'] ) ) {
                                        $entry['mp_status'] = $mp_data['status'];
                                        $entry['mp_status_detail'] = $mp_data['status_detail'] ?? null;
                                        $entry['mp_payment_method'] = $mp_data['payment_method_id'] ?? null;
                                    }
                                }
                            }
                        }
                    }
                } catch ( \Throwable $e ) {
                    // ignore enrichment failures
                }

            $this->append_log( $entry );
        } catch ( \Throwable $e ) {
            // ignore
        }
        return $response;
    }

    public function register_routes() : void {
        \register_rest_route( 'myd-delivery/v1', '/access-stream', [
            'methods'  => \WP_REST_Server::READABLE,
            'permission_callback' => function( $request ) {
                // Accept admin users OR a valid REST nonce from admin page
                if ( myd_user_is_allowed_admin() ) return true;
                $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field( wp_unslash($_GET['_wpnonce']) ) : '';
                return \wp_verify_nonce( $nonce, 'wp_rest' ) ? true : false;
            },
            'callback' => [ $this, 'sse_stream' ],
            'args'     => [
                'since' => [ 'type' => 'integer', 'required' => false ],
            ],
        ] );

        // Clear log endpoint
        \register_rest_route( 'myd-delivery/v1', '/access-clear', [
            'methods'  => \WP_REST_Server::CREATABLE,
            'permission_callback' => function() {
                return myd_user_is_allowed_admin();
            },
            'callback' => function(){
                \update_option( self::OPT_LOG, [], false );
                if ( function_exists('wp_cache_delete') ) {
                    \wp_cache_delete( self::OPT_LOG, 'options' );
                    \wp_cache_delete( 'alloptions', 'options' );
                }
                return new \WP_REST_Response( ['status'=>'ok'], 200 );
            }
        ] );
    }

    public function sse_stream( $request ) {
        @set_time_limit( 0 );
    // Headers for SSE
    header( 'Content-Type: text/event-stream; charset=utf-8' );
    // no-transform helps proxies (ex.: Cloudflare) not buffer/modify stream
    header( 'Cache-Control: no-cache, no-transform' );
        header( 'X-Accel-Buffering: no' );
        header( 'Connection: keep-alive' );
    if ( ! headers_sent() ) { @ini_set('output_buffering','off'); @ini_set('zlib.output_compression', '0'); }

    // Send padding to nudge proxies to start streaming immediately (~2KB)
    echo str_repeat(": \n", 2048);
    if ( function_exists( 'ob_get_level' ) && ob_get_level() > 0 ) { @ob_end_flush(); }
    @flush();

        $last = (int) ( $request['since'] ?? 0 );

        // Send environment meta (Cloudflare detection)
        $cf = ( isset($_SERVER['HTTP_CF_RAY']) || isset($_SERVER['HTTP_CF_CONNECTING_IP']) || isset($_SERVER['HTTP_CF_VISITOR']) );
        $meta = [
            'cf' => $cf,
            'headers' => array_filter([
                'CF-RAY' => isset($_SERVER['HTTP_CF_RAY']) ? $_SERVER['HTTP_CF_RAY'] : null,
                'CF-Connecting-IP' => isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : null,
                'CF-Visitor' => isset($_SERVER['HTTP_CF_VISITOR']) ? $_SERVER['HTTP_CF_VISITOR'] : null,
                'Via' => isset($_SERVER['HTTP_VIA']) ? $_SERVER['HTTP_VIA'] : null,
                'X-Forwarded-For' => isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null,
            ])
        ];
    echo "event: meta\n";
    echo 'data: ' . \wp_json_encode( $meta ) . "\n\n";

        // Send a snapshot of recent entries first
    $snapshot = \get_option( self::OPT_LOG, [] );
        if ( is_array( $snapshot ) && ! empty( $snapshot ) ) {
            $payload = [ 'entries' => array_slice( $snapshot, -50 ) ];
            echo "event: snapshot\n";
            echo 'data: ' . \wp_json_encode( $payload ) . "\n\n";
            if ( ! empty( $payload['entries'] ) ) {
                $last = max( array_column( $payload['entries'], 'id' ) );
            }
        }
        if ( function_exists( 'ob_get_level' ) && ob_get_level() > 0 ) { @ob_end_flush(); }
        @flush();

        // Stream new entries
    $start = time();
    $lastPing = 0;
    $lastWhatsappState = \get_option('myd_whatsapp_connection_state', '');
        while ( ! connection_aborted() && ( time() - $start ) < 600 ) { // up to 10 minutes per connection
            // Bust caches before each poll so we read fresh values from DB
            if ( function_exists('wp_cache_delete') ) {
                \wp_cache_delete( self::OPT_SEQ, 'options' );
                \wp_cache_delete( self::OPT_LOG, 'options' );
                \wp_cache_delete( 'alloptions', 'options' );
                \wp_cache_delete( 'myd_whatsapp_connection_state', 'options' );
            }
            $seq = (int) \get_option( self::OPT_SEQ, 0 );
            if ( $seq > $last ) {
                $log = \get_option( self::OPT_LOG, [] );
                if ( is_array( $log ) ) {
                    foreach ( $log as $row ) {
                        if ( isset( $row['id'] ) && (int) $row['id'] > $last ) {
                            echo "event: access\n";
                            echo 'data: ' . \wp_json_encode( $row ) . "\n\n";
                            $last = (int) $row['id'];
                        }
                    }
                    if ( function_exists( 'ob_get_level' ) && ob_get_level() > 0 ) { @ob_end_flush(); }
                    @flush();
                }
            }
            // Check for WhatsApp status changes
            $currentWhatsappState = \get_option('myd_whatsapp_connection_state', '');
            if ( $currentWhatsappState !== $lastWhatsappState ) {
                echo "event: whatsapp-status\n";
                echo 'data: ' . \wp_json_encode( [ 'state' => $currentWhatsappState ] ) . "\n\n";
                $lastWhatsappState = $currentWhatsappState;
                if ( function_exists( 'ob_get_level' ) && ob_get_level() > 0 ) { @ob_end_flush(); }
                @flush();
            }
            // Heartbeat ping every 10s to keep proxies from idling the stream
            if ( time() - $lastPing >= 10 ) {
                echo "event: ping\n";
                echo 'data: ' . time() . "\n\n";
                if ( function_exists( 'ob_get_level' ) && ob_get_level() > 0 ) { @ob_end_flush(); }
                @flush();
                $lastPing = time();
            }
            sleep( 1 );
        }
        // End the stream
        echo "event: end\n";
        echo 'data: "bye"' . "\n\n";
        if ( function_exists( 'ob_get_level' ) && ob_get_level() > 0 ) { @ob_end_flush(); }
        @flush();
        exit;
    }

    /** Shortcode UI */
    public function shortcode_monitor( $admin_context = false ): string {
        if ( ! $admin_context && ! myd_user_is_allowed_admin() ) {
            return '<div style="padding:12px;color:#a00">Acesso restrito.</div>';
        }
        // Simple UI
    ob_start();
    $nonce = \wp_create_nonce('wp_rest');
    ?>
        <div id="myd-access-monitor" style="font-family:Arial, sans-serif; padding:16px;">
            <h2 style="margin-top:0;">Monitor de Acessos (tempo real)</h2>
            <div style="margin:8px 0 12px; color:#555;">Mostrando Web + API. Mantém os últimos <?php echo (int) self::MAX_ENTRIES; ?> itens.</div>
            <div id="myd-access-status" style="margin-bottom:8px; color:#007cba;">Conectando…</div>
            <div id="myd-access-meta" style="margin-bottom:8px; color:#666; font-size:12px;"></div>
            <button id="myd-access-clear" type="button" class="button" style="margin-bottom:12px;">Limpar log</button>
            <div style="overflow:auto; max-height:60vh; border:1px solid #e5e5e5; border-radius:6px;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead style="position:sticky; top:0; background:#fafafa;">
                        <tr>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Quando</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Tipo</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">IP</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Método</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Caminho</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Status</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Motivo</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">User Agent</th>
                        </tr>
                    </thead>
                    <tbody id="myd-access-rows"></tbody>
                </table>
            </div>

            <hr style="margin:40px 0 20px;">

            <h2 style="margin-top:0;">Gerenciamento de Tokens JWT</h2>
            <div style="margin:8px 0 12px; color:#555;">Gerencie tokens de acesso e refresh ativos. Tokens expirados são limpos automaticamente.</div>
            <button id="myd-tokens-refresh" type="button" class="button" style="margin-bottom:12px;">Atualizar Lista</button>
            <button id="myd-tokens-revoke-all" type="button" class="button button-primary" style="margin-bottom:12px; margin-left:8px;">Revogar Todos os Meus Tokens</button>
            <div style="overflow:auto; max-height:50vh; border:1px solid #e5e5e5; border-radius:6px;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead style="position:sticky; top:0; background:#fafafa;">
                        <tr>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Usuário</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Criado em</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Expira em</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">IP</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">User Agent</th>
                            <th style="text-align:left; padding:8px; border-bottom:1px solid #eee;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="myd-tokens-rows">
                        <tr><td colspan="6" style="padding:20px; text-align:center; color:#666;">Carregando tokens...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <script>
        (function(){
            const rows = document.getElementById('myd-access-rows');
            const status = document.getElementById('myd-access-status');
            const metaEl = document.getElementById('myd-access-meta');
            function fmt(ts){ try{ const d=new Date(ts*1000); return d.toLocaleString(); }catch(e){ return ts; } }
            function addRow(r){
                const tr = document.createElement('tr');
                // detect Mercado Pago webhook payment.updated
                let isMpWebhook = false;
                try{
                    const path = (r.path||'').toString();
                    if(path.indexOf('/mercadopago/webhook') !== -1 || path.indexOf('mercadopago/webhook') !== -1) {
                        // prefer structured body.action when available
                        if(r.body && typeof r.body === 'object' && (r.body.action === 'payment.updated' || r.body.action === 'payment.created')) {
                            if(r.body.action === 'payment.updated') isMpWebhook = true;
                        } else if(r.body && typeof r.body === 'string' && r.body.indexOf('payment.updated') !== -1) {
                            isMpWebhook = true;
                        } else if(r.body_raw_preview && r.body_raw_preview.toString().indexOf('payment.updated') !== -1) {
                            isMpWebhook = true;
                        }
                    }
                }catch(e){}

                tr.innerHTML = `
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; white-space:nowrap;">${fmt(r.ts||Date.now()/1000)}</td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;">${(r.type||'').toUpperCase()}</td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;">${r.ip||''}</td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;">${r.method||''}</td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;">${r.path||''}</td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;">${r.mp_status? (r.mp_status + (r.mp_status_detail? ' ('+r.mp_status_detail+')':'')) : ''}</td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; color:#a33;">${r.reason? (r.reason) : ''}</td>
                    <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;">${r.ua||''}</td>`;
                // visual highlight for payment.updated
                if(isMpWebhook) {
                    tr.style.background = '#fff8e1'; // light yellow for webhook rows
                    tr.style.borderLeft = '4px solid #f39c12';
                    tr.title = 'Webhook MercadoPago (payment.updated) — clique para ver detalhes';
                }
                // If we have mp_status and it's approved, make the row green-ish to indicate payment done
                if(r.mp_status && r.mp_status.toLowerCase() === 'approved') {
                    tr.style.background = '#e9f7ef'; // light green
                    tr.style.borderLeft = '4px solid #2ecc71';
                }

                rows.insertBefore(tr, rows.firstChild);
                // attach full JSON as data attribute (truncated to avoid massive DOM strings)
                try{ tr.dataset.details = JSON.stringify(r); }catch(e){}
                tr.style.cursor = 'pointer';
                if(!isMpWebhook) tr.title = tr.title || 'Clique para ver detalhes';
                tr.addEventListener('click', function(){
                    try{ const d = JSON.parse(this.dataset.details||'{}'); const s = JSON.stringify(d, null, 2); const w = window.open('', '_blank', 'width=800,height=600,scrollbars=yes'); w.document.title = 'Acesso detalhado'; w.document.body.style.whiteSpace='pre-wrap'; w.document.body.style.fontFamily='monospace'; w.document.body.textContent = s; }catch(e){ alert('Detalhes indisponíveis'); }
                });
                // limit to ~500 display rows for DOM perf
                while(rows.children.length > 500){ rows.removeChild(rows.lastChild); }
            }
            function addMany(list){ (list||[]).forEach(addRow); }

            let es;
            function connect(){
                try{ if(es) { es.close(); } }catch(e){}
                status.textContent = 'Conectando…';
                es = new EventSource('<?php echo \esc_url_raw( \rest_url('myd-delivery/v1/access-stream') ); ?>?_wpnonce=<?php echo \esc_attr( $nonce ); ?>');
                es.addEventListener('open', ()=>{ status.textContent = 'Conectado'; });
                es.addEventListener('error', ()=>{ status.textContent = 'Desconectado, tentando novamente…'; setTimeout(connect, 3000); });
                es.addEventListener('meta', (e)=>{
                    try{
                        const d = JSON.parse(e.data);
                        if(d && d.cf){ metaEl.textContent = 'Proxy detectado (Cloudflare). Se o stream não atualizar, desative cache/buffering para /wp-json/myd-delivery/v1/access-stream.'; }
                        else { metaEl.textContent = ''; }
                    }catch(err){}
                });
                es.addEventListener('snapshot', (e)=>{ try{ const d = JSON.parse(e.data); addMany(d.entries); }catch(err){} });
                es.addEventListener('access', (e)=>{ try{ const r = JSON.parse(e.data); addRow(r); }catch(err){} });
                es.addEventListener('end', ()=>{ status.textContent = 'Encerrado'; });
            }
            connect();

            // Clear button
            document.getElementById('myd-access-clear').addEventListener('click', async ()=>{
                rows.innerHTML='';
                try{
                    await fetch('<?php echo \esc_url_raw( \rest_url('myd-delivery/v1/access-clear') ); ?>', {method:'POST', headers:{'X-WP-Nonce':'<?php echo \esc_attr( $nonce ); ?>'}});
                }catch(e){}
            });

            // Token management
            async function loadTokens() {
                const tbody = document.getElementById('myd-tokens-rows');
                tbody.innerHTML = '<tr><td colspan="6" style="padding:20px; text-align:center; color:#666;">Carregando...</td></tr>';
                try {
                    const res = await fetch('<?php echo \esc_url_raw( \rest_url('myd-delivery/v1/tokens') ); ?>?_wpnonce=<?php echo \esc_attr( $nonce ); ?>');
                    const data = await res.json();
                    tbody.innerHTML = '';
                    if (data.tokens && data.tokens.length > 0) {
                        data.tokens.forEach(token => {
                            const tr = document.createElement('tr');
                            const created = new Date(token.created_at).toLocaleString();
                            const expires = new Date(token.expires_at).toLocaleString();
                            const isExpired = new Date(token.expires_at) < new Date();
                            tr.innerHTML = `
                                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;">${token.user_login || token.user_id}</td>
                                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;">${created}</td>
                                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0; ${isExpired ? 'color:#a00;' : ''}">${expires} ${isExpired ? '(Expirado)' : ''}</td>
                                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;">${token.ip_address || ''}</td>
                                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;">${(token.user_agent || '').substring(0, 50)}${(token.user_agent || '').length > 50 ? '...' : ''}</td>
                                <td style="padding:6px 8px; border-bottom:1px solid #f0f0f0;">
                                    <button class="button button-small" onclick="revokeToken('${token.id}')">Revogar</button>
                                </td>
                            `;
                            tbody.appendChild(tr);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" style="padding:20px; text-align:center; color:#666;">Nenhum token ativo encontrado.</td></tr>';
                    }
                } catch (e) {
                    tbody.innerHTML = '<tr><td colspan="6" style="padding:20px; text-align:center; color:#a00;">Erro ao carregar tokens.</td></tr>';
                    console.error('Error loading tokens:', e);
                }
            }

            window.revokeToken = async function(tokenId) {
                if (!confirm('Tem certeza que deseja revogar este token?')) return;
                try {
                    const res = await fetch('<?php echo \esc_url_raw( \rest_url('myd-delivery/v1/tokens') ); ?>?_wpnonce=<?php echo \esc_attr( $nonce ); ?>', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ token_id: tokenId })
                    });
                    const data = await res.json();
                    alert(data.message || 'Token revogado');
                    loadTokens();
                } catch (e) {
                    alert('Erro ao revogar token');
                    console.error(e);
                }
            };

            // Load tokens on page load
            loadTokens();

            // Refresh button
            document.getElementById('myd-tokens-refresh').addEventListener('click', loadTokens);

            // Revoke all button
            document.getElementById('myd-tokens-revoke-all').addEventListener('click', async () => {
                if (!confirm('Tem certeza que deseja revogar TODOS os seus tokens? Você será desconectado de todos os dispositivos.')) return;
                try {
                    const res = await fetch('<?php echo \esc_url_raw( \rest_url('custom-auth/v1/revoke') ); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'same-origin', // Inclui cookies de autenticação
                        body: JSON.stringify({ revoke_all: true })
                    });
                    const data = await res.json();
                    alert(data.message || 'Tokens revogados');
                    loadTokens();
                } catch (e) {
                    alert('Erro ao revogar tokens');
                    console.error(e);
                }
            });
        })();
        </script>
        <?php
        return (string) ob_get_clean();
    }

    /** Admin page renderer */
    public function register_admin_page() : void {
        \add_management_page(
            'Monitor de Acessos (MyD)',
            'Monitor de Acessos (MyD)',
            'manage_options',
            'myd-access-monitor',
            function(){ echo $this->shortcode_monitor( true ); }
        );
    }
}

// Initialize immediately
new Access_Monitor();
