<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$api_url = get_option('evolution_api_url', '');
$api_key = get_option('evolution_api_key', '');
$instance_name = get_option('evolution_instance_name', '');
$msg_confirmed = get_option('evolution_msg_confirmed', 'Olá {customer_name}, seu pedido foi confirmado!\n\nInformações do pedido:\n===== Pedido {order_number} =====\n\n{order_products}\nEntrega: {shipping_price}\nTotal do pedido: {order_total}\nMétodo de pagamento: {payment_method}\nTroco: {payment_change}\n\n===== Cliente =====\n{customer_name}\n{customer_phone}\n{customer_address}, {customer_address_number}\n{customer_address_complement}\n{customer_address_neighborhood}\n{customer_address_zipcode}\n\n===== Acompanhar Pedido =====\n{order_track_page}');
$msg_confirmed_title = get_option('evolution_msg_confirmed_title', '');
$msg_delivery = get_option('evolution_msg_delivery', 'Seu pedido saiu para entrega!');
$ddi = get_option('evolution_ddi', '55');
$test_number = '';
$connection_status = '';

// Button message options
$btn_enabled       = get_option('evolution_btn_enabled', '');
$btn_title         = get_option('evolution_btn_title', '');
$btn_description   = get_option('evolution_btn_description', '');
$btn_footer        = get_option('evolution_btn_footer', '');
$btn_delay         = get_option('evolution_btn_delay', '0');
$btn_display_text  = get_option('evolution_btn_display_text', '');

$logo_url = plugins_url( 'assets/img/logo-evo-ai.svg', MYD_PLUGIN_MAIN_FILE );
?>

<style>
.evo-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    margin-bottom: 16px;
    overflow: hidden;
}
.evo-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: #f6f7f7;
    border-bottom: 1px solid #c3c4c7;
    cursor: pointer;
    user-select: none;
    transition: background .15s;
}
.evo-section-header:hover { background: #eef0f1; }
.evo-section-header h3 { margin: 0; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.evo-section-header .dashicons { transition: transform .2s; color: #787c82; }
.evo-section-header.collapsed .dashicons { transform: rotate(-90deg); }
.evo-section-body { padding: 18px; }
.evo-section-body.hidden { display: none; }
.evo-field { margin-bottom: 14px; }
.evo-field:last-child { margin-bottom: 0; }
.evo-field label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 13px; }
.evo-field .description { color: #646970; font-size: 12px; margin-top: 3px; }
.evo-field input[type="text"],
.evo-field input[type="number"],
.evo-field input[type="password"],
.evo-field select,
.evo-field textarea { width: 100%; max-width: 500px; }
.evo-field textarea { min-height: 120px; }
.evo-row { display: flex; gap: 14px; flex-wrap: wrap; }
.evo-row > .evo-field { flex: 1; min-width: 200px; }
.evo-variables { background: #f0f6fc; border: 1px solid #c5d9ed; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px; font-size: 12px; color: #2c3338; }
.evo-variables code { background: #fff; padding: 1px 5px; border-radius: 3px; font-size: 11.5px; }
.evo-btn-card {
    background: #f9f9f9;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    padding: 14px;
    margin-bottom: 12px;
}
.evo-btn-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.evo-btn-card-header strong { font-size: 13px; }
.evo-btn-type-fields { margin-top: 8px; }
.evo-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    padding: 6px 0;
}
.evo-badge {
    display: inline-block;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 600;
    text-transform: uppercase;
}
.evo-badge-green { background: #dcfce7; color: #166534; }
.evo-badge-gray { background: #f3f4f6; color: #6b7280; }
</style>

<div id="tab-evolution-content" class="myd-tabs-content">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
        <img src="<?php echo esc_url($logo_url); ?>" alt="Evolution API" style="height:36px;" />
    </div>

    <!-- ═══════ SEÇÃO: CONEXÃO ═══════ -->
    <div class="evo-section">
        <div class="evo-section-header" onclick="evoToggleSection(this)">
            <h3><span class="dashicons dashicons-admin-links"></span> Conexão</h3>
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </div>
        <div class="evo-section-body">
            <div class="evo-row">
                <div class="evo-field">
                    <label for="evolution_api_url">URL da API</label>
                    <input type="text" name="evolution_api_url" id="evolution_api_url" value="<?php echo esc_attr($api_url); ?>" placeholder="https://api.example.com" />
                </div>
                <div class="evo-field">
                    <label for="evolution_api_key">API Key</label>
                    <input type="text" name="evolution_api_key" id="evolution_api_key" value="<?php echo esc_attr($api_key); ?>" />
                </div>
            </div>
            <div class="evo-row">
                <div class="evo-field">
                    <label for="evolution_instance_name">Nome da Instância</label>
                    <input type="text" name="evolution_instance_name" id="evolution_instance_name" value="<?php echo esc_attr($instance_name); ?>" />
                </div>
                <div class="evo-field">
                    <label for="evolution_ddi">DDI <span style="font-weight:normal">(Ex: Brasil: +55)</span></label>
                    <input type="number" name="evolution_ddi" id="evolution_ddi" value="<?php echo esc_attr($ddi); ?>" style="max-width:100px;" min="1" max="999" />
                </div>
            </div>
            <div class="evo-field">
                <label for="evolution_webhook_key">Webhook Key</label>
                <div style="display:flex;align-items:center;gap:5px;max-width:500px;">
                    <input type="password" name="evolution_webhook_key" id="evolution_webhook_key" value="<?php echo esc_attr( get_option('evolution_webhook_key','') ); ?>" style="flex:1;" />
                    <button type="button" class="button" id="toggle_webhook_key" title="Mostrar/Ocultar Chave"><span class="dashicons dashicons-visibility" style="margin-top:4px;"></span></button>
                </div>
                <p class="description">Chave usada para validar webhooks recebidos da Evolution (opcional).</p>
            </div>

            <hr style="margin:16px 0;border:none;border-top:1px solid #dcdcde;" />

            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
                <button type="button" class="button button-primary" id="evolution_test_connection">Testar Conexão</button>
                <span id="evolution_connection_status" style="font-weight:500;"></span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:12px;">
                <input type="number" id="evolution_test_number" placeholder="Número WhatsApp" value="<?php echo esc_attr($test_number); ?>" style="max-width:200px;" />
                <button type="button" class="button" id="evolution_test_send">Enviar Teste</button>
                <span id="evolution_test_message" style="font-weight:500;"></span>
            </div>
        </div>
    </div>

    <!-- ═══════ SEÇÃO: MENSAGEM PEDIDO FEITO ═══════ -->
    <div class="evo-section">
        <div class="evo-section-header" onclick="evoToggleSection(this)">
            <h3><span class="dashicons dashicons-format-status"></span> Mensagem Pedido Feito</h3>
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </div>
        <div class="evo-section-body">
            <div class="evo-variables">
                <strong>Variáveis disponíveis:</strong><br>
                <code>{customer_name}</code> <code>{first_customer_name}</code> <code>{order_number}</code> <code>{order_code}</code> <code>{order_products}</code> <code>{shipping_price}</code> <code>{order_total}</code> <code>{payment_method}</code> <code>{payment_change}</code> <code>{customer_phone}</code> <code>{customer_address}</code> <code>{customer_address_number}</code> <code>{customer_address_complement}</code> <code>{customer_address_neighborhood}</code> <code>{customer_address_zipcode}</code> <code>{order_track_page}</code>
            </div>
            <div class="evo-field" id="evo_msg_confirmed_title_wrapper" style="<?php echo $btn_enabled === 'on' ? 'display:none;' : ''; ?>">
                <label for="evolution_msg_confirmed_title">Corpo da Mensagem</label>
                <textarea name="evolution_msg_confirmed_title" id="evolution_msg_confirmed_title" style="max-width:100%;"><?php echo esc_textarea( $msg_confirmed_title ); ?></textarea>
            </div>

            <!-- Botão interativo -->
            <div style="margin-top:18px;padding-top:16px;border-top:1px solid #dcdcde;">
                <label class="evo-checkbox-label">
                    <input type="checkbox" name="evolution_btn_enabled" id="evolution_btn_enabled" value="on" <?php checked( $btn_enabled, 'on' ); ?> />
                    Enviar como botão interativo
                    <span class="evo-badge <?php echo $btn_enabled === 'on' ? 'evo-badge-green' : 'evo-badge-gray'; ?>" id="evo_btn_badge"><?php echo $btn_enabled === 'on' ? 'ATIVO' : 'INATIVO'; ?></span>
                </label>
                <p class="description" style="margin-top:2px;">Quando ativado, a mensagem será enviada com botões clicáveis usando a API <code>sendButtons</code>.</p>
            </div>

            <div id="evo_btn_panel" style="margin-top:14px;<?php echo $btn_enabled !== 'on' ? 'display:none;' : ''; ?>">
                <div class="evo-row">
                    <div class="evo-field">
                        <label for="evolution_btn_title">Título</label>
                        <input type="text" name="evolution_btn_title" id="evolution_btn_title" value="<?php echo esc_attr($btn_title); ?>" placeholder="Ex: Pedido Recebido! 🎉" />
                        <p class="description">Título exibido acima da mensagem. Suporta variáveis.</p>
                    </div>
                    <div class="evo-field">
                        <label for="evolution_btn_footer">Rodapé</label>
                        <input type="text" name="evolution_btn_footer" id="evolution_btn_footer" value="<?php echo esc_attr($btn_footer); ?>" placeholder="Ex: Obrigado pela preferência!" />
                        <p class="description">Texto pequeno no final da mensagem.</p>
                    </div>
                </div>

                <div class="evo-field">
                    <label for="evolution_btn_description">Descrição (corpo)</label>
                    <textarea name="evolution_btn_description" id="evolution_btn_description" style="max-width:100%;min-height:80px;"><?php echo esc_textarea($btn_description); ?></textarea>
                    <p class="description">Texto principal da mensagem de botão. Se vazio, usa o "Corpo da Mensagem" acima. Suporta variáveis.</p>
                </div>

                <div class="evo-field">
                    <label for="evolution_btn_delay">Delay (ms)</label>
                    <input type="number" name="evolution_btn_delay" id="evolution_btn_delay" value="<?php echo esc_attr($btn_delay); ?>" min="0" max="30000" style="max-width:120px;" />
                    <p class="description">Tempo de presença antes de enviar (em milissegundos). 0 = sem delay.</p>
                </div>

                <h4 style="margin:18px 0 10px;font-size:13px;">Botão</h4>

                <div class="evo-btn-card">
                    <div class="evo-field">
                        <label for="evolution_btn_display_text">Texto do Botão</label>
                        <input type="text" name="evolution_btn_display_text" id="evolution_btn_display_text" value="<?php echo esc_attr($btn_display_text); ?>" placeholder="Ex: 📦 Acompanhar Pedido" />
                        <p class="description">Texto exibido no botão. O link será a página de rastreio do pedido (<code>{order_track_page}</code>).</p>
                    </div>
                </div>
            </div>

            <hr style="margin:16px 0;border:none;border-top:1px solid #dcdcde;" />
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="number" id="evo_test_num_pedido_feito" placeholder="Número WhatsApp" style="max-width:200px;" />
                <button type="button" class="button" onclick="evoTestMsg('pedido_feito')">📩 Testar mensagem</button>
                <span id="evo_test_status_pedido_feito" style="font-weight:500;"></span>
            </div>
        </div>
    </div>

    <!-- ═══════ SEÇÃO: MENSAGEM PEDIDO CONFIRMADO ═══════ -->
    <div class="evo-section">
        <div class="evo-section-header" onclick="evoToggleSection(this)">
            <h3><span class="dashicons dashicons-yes-alt"></span> Mensagem Pedido Confirmado</h3>
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </div>
        <div class="evo-section-body">
            <div class="evo-field">
                <textarea name="evolution_msg_confirmed" id="evolution_msg_confirmed" style="max-width:100%;"><?php echo esc_textarea($msg_confirmed); ?></textarea>
            </div>
            <hr style="margin:16px 0;border:none;border-top:1px solid #dcdcde;" />
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="number" id="evo_test_num_confirmado" placeholder="Número WhatsApp" style="max-width:200px;" />
                <button type="button" class="button" onclick="evoTestMsg('confirmado')">📩 Testar mensagem</button>
                <span id="evo_test_status_confirmado" style="font-weight:500;"></span>
            </div>
        </div>
    </div>

    <!-- ═══════ SEÇÃO: MENSAGEM SAIU PARA ENTREGA ═══════ -->
    <div class="evo-section">
        <div class="evo-section-header" onclick="evoToggleSection(this)">
            <h3><span class="dashicons dashicons-car"></span> Mensagem Saiu para Entrega</h3>
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </div>
        <div class="evo-section-body">
            <div class="evo-variables">
                <strong>Variáveis disponíveis:</strong><br>
                <code>{customer_name}</code> <code>{first_customer_name}</code> <code>{order_number}</code> <code>{order_code}</code> <code>{order_products}</code> <code>{shipping_price}</code> <code>{order_total}</code> <code>{payment_method}</code> <code>{payment_change}</code> <code>{customer_phone}</code> <code>{customer_address}</code> <code>{customer_address_number}</code> <code>{customer_address_complement}</code> <code>{customer_address_neighborhood}</code> <code>{customer_address_zipcode}</code> <code>{order_track_page}</code>
            </div>
            <div class="evo-field">
                <textarea name="evolution_msg_delivery" id="evolution_msg_delivery" style="max-width:100%;"><?php echo esc_textarea($msg_delivery); ?></textarea>
            </div>
            <hr style="margin:16px 0;border:none;border-top:1px solid #dcdcde;" />
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <input type="number" id="evo_test_num_entrega" placeholder="Número WhatsApp" style="max-width:200px;" />
                <button type="button" class="button" onclick="evoTestMsg('entrega')">📩 Testar mensagem</button>
                <span id="evo_test_status_entrega" style="font-weight:500;"></span>
            </div>
        </div>
    </div>
</div>

<script>
/* ═══════ Toggle sections ═══════ */
function evoToggleSection(header) {
    header.classList.toggle('collapsed');
    var body = header.nextElementSibling;
    body.classList.toggle('hidden');
}

/* ═══════ Toggle button panel + badge ═══════ */
document.getElementById('evolution_btn_enabled').onchange = function() {
    var panel = document.getElementById('evo_btn_panel');
    var badge = document.getElementById('evo_btn_badge');
    var msgWrapper = document.getElementById('evo_msg_confirmed_title_wrapper');
    panel.style.display = this.checked ? '' : 'none';
    if (msgWrapper) msgWrapper.style.display = this.checked ? 'none' : '';
    badge.textContent = this.checked ? 'ATIVO' : 'INATIVO';
    badge.className = 'evo-badge ' + (this.checked ? 'evo-badge-green' : 'evo-badge-gray');
};

/* ═══════ Connection test ═══════ */
document.getElementById('evolution_test_connection').onclick = function() {
    var url = document.getElementById('evolution_api_url').value;
    var key = document.getElementById('evolution_api_key').value;
    var instance = document.getElementById('evolution_instance_name').value;
    var statusSpan = document.getElementById('evolution_connection_status');
    var instanceFull = 'dwp-' + instance;
    statusSpan.innerText = 'Testando...';
    fetch(url + '/instance/fetchInstances?instanceName=' + instanceFull, {
        method: 'GET',
        headers: { 'apikey': key }
    }).then(function(resp) {
        if (resp.status === 404) {
            criarInstancia();
            return;
        }
        resp.json().then(function(data) {
            var notFoundObj = Array.isArray(data) ? data.find(function(item) { return item.status === 404; }) : null;
            if (notFoundObj) {
                criarInstancia();
            } else {
                var instanceObj = Array.isArray(data) ? data.find(function(item) { return item.name === instanceFull; }) : null;
                if (instanceObj && instanceObj.connectionStatus === 'open') {
                    statusSpan.innerText = 'Instância conectada';
                } else if (instanceObj && (instanceObj.connectionStatus === 'connecting' || instanceObj.connectionStatus === 'close')) {
                    statusSpan.innerText = 'Instância conectando...';
                    fetch(url + '/instance/connect/' + instanceFull, {
                        method: 'GET',
                        headers: { 'apikey': key }
                    }).then(function(connectResp) {
                        if (connectResp.status === 200) {
                            connectResp.json().then(function(connectData) {
                                if (connectData && connectData.base64) {
                                    let modal = document.getElementById('evolution_qr_modal');
                                    if (!modal) {
                                        modal = document.createElement('div');
                                        modal.id = 'evolution_qr_modal';
                                        modal.style.position = 'fixed';
                                        modal.style.top = '0';
                                        modal.style.left = '0';
                                        modal.style.width = '100vw';
                                        modal.style.height = '100vh';
                                        modal.style.background = 'rgba(0,0,0,0.7)';
                                        modal.style.display = 'flex';
                                        modal.style.alignItems = 'center';
                                        modal.style.justifyContent = 'center';
                                        modal.style.zIndex = '9999';
                                        modal.innerHTML = '<div style="background:#fff;padding:20px;border-radius:8px;position:relative;text-align:center;max-width:90vw;max-height:90vh;"><span id="close_qr_modal" style="position:absolute;top:10px;right:15px;cursor:pointer;font-size:22px;font-weight:bold;">&times;</span><img src="' + connectData.base64 + '" alt="QR Code para conexão" style="margin-top:10px;max-width:300px;"></div>';
                                        document.body.appendChild(modal);
                                        document.getElementById('close_qr_modal').onclick = function() {
                                            modal.remove();
                                            statusSpan.innerText = 'Verificando conexão...';
                                            fetch(url + '/instance/fetchInstances?instanceName=' + instanceFull, {
                                                method: 'GET',
                                                headers: { 'apikey': key }
                                            }).then(function(resp) {
                                                if (resp.status === 200) {
                                                    resp.json().then(function(data) {
                                                        var instanceObj = Array.isArray(data) ? data.find(function(item) { return item.name === instanceFull; }) : null;
                                                        if (instanceObj && instanceObj.connectionStatus === 'open') {
                                                            statusSpan.innerText = 'Instância conectada!';
                                                        } else {
                                                            statusSpan.innerText = 'Falhou ao conectar. Por favor, tente conectar novamente.';
                                                        }
                                                    });
                                                } else {
                                                    statusSpan.innerText = 'Erro ao verificar conexão.';
                                                }
                                            }).catch(function() {
                                                statusSpan.innerText = 'Erro ao verificar conexão.';
                                            });
                                        };
                                    }
                                    statusSpan.innerText = 'Escaneie o QR Code para conectar.';
                                } else {
                                    statusSpan.innerText = 'Instância conectando, mas não foi possível obter o QR Code.';
                                }
                            });
                        } else {
                            statusSpan.innerText = 'Erro ao conectar instância: ' + connectResp.status;
                        }
                    });
                } else if (instanceObj) {
                    statusSpan.innerText = 'Instância encontrada, mas não conectada (' + instanceObj.connectionStatus + ')';
                } else {
                    statusSpan.innerText = 'Instância não encontrada!';
                }
            }
        });
    }).catch(function() { statusSpan.innerText = 'Erro de conexão.'; });

    function criarInstancia() {
        statusSpan.innerText = 'Instância não encontrada! Criando nova instância...';
        fetch(url + '/instance/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'apikey': key },
            body: JSON.stringify({
                instanceName: instanceFull,
                qrcode: true,
                integration: 'WHATSAPP-BAILEYS',
                webhook: {
                    enabled: true,
                    url: "<?php echo esc_url_raw( rest_url( 'myd-delivery/v1/evolution/webhook' ) ); ?>",
                    webhook_by_events: false,
                    events: ["MESSAGES_UPSERT", "CONNECTION_UPDATE", "QRCODE_UPDATED"]
                }
            })
        }).then(function(createResp) {
            if (createResp.status === 201 || createResp.status === 200 || createResp.status === 400) {
                if (createResp.status === 400) { console.warn('Evolution retornou 400 na criação, tentando prosseguir...'); }
                statusSpan.innerText = 'Instância processada! Configurando webhook e QR Code...';
                
                fetch(url + '/instance/fetchInstances', {
                    headers: { 'apikey': key }
                }).then(r => r.json()).then(data => {
                    const list = Array.isArray(data) ? data : (data.instances || []);
                    const found = list.find(i => i.instanceName === instanceFull || i.name === instanceFull);
                    const finalToken = found ? (found.token || (found.instance && found.instance.token)) : null;
                    if (finalToken) {
                        fetch("<?php echo esc_url_raw( rest_url( 'myd-delivery/v1/evolution/save-token' ) ); ?>", {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>'
                            },
                            body: JSON.stringify({ token: finalToken, instance: instanceFull })
                        }).then(r => r.json()).then(res => {
                            if (res.status === 'ok') {
                                const inputKey = document.getElementById('evolution_webhook_key');
                                if (inputKey) inputKey.value = finalToken;
                            }
                        });

                        fetch(url + '/webhook/instance/' + instanceFull, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'apikey': key },
                            body: JSON.stringify({
                                url: "<?php echo esc_url_raw( rest_url( 'myd-delivery/v1/evolution/webhook' ) ); ?>",
                                enabled: true,
                                webhook_by_events: false,
                                events: ["MESSAGES_UPSERT", "CONNECTION_UPDATE", "QRCODE_UPDATED"]
                            })
                        }).then(r => r.json()).then(wRes => {
                            console.log('Evolution Webhook Response:', wRes);
                        }).catch(err => console.error('Erro ao setar webhook:', err));
                    }
                }).catch(e => console.error('Erro ao sincronizar token:', e));
                fetch(url + '/instance/connect/' + instanceFull, {
                    method: 'GET',
                    headers: { 'apikey': key }
                }).then(function(connectResp) {
                    if (connectResp.status === 200) {
                        connectResp.json().then(function(connectData) {
                            if (connectData && connectData.base64) {
                                let modal = document.getElementById('evolution_qr_modal');
                                if (!modal) {
                                    modal = document.createElement('div');
                                    modal.id = 'evolution_qr_modal';
                                    modal.style.position = 'fixed';
                                    modal.style.top = '0';
                                    modal.style.left = '0';
                                    modal.style.width = '100vw';
                                    modal.style.height = '100vh';
                                    modal.style.background = 'rgba(0,0,0,0.7)';
                                    modal.style.display = 'flex';
                                    modal.style.alignItems = 'center';
                                    modal.style.justifyContent = 'center';
                                    modal.style.zIndex = '9999';
                                    modal.innerHTML = '<div style="background:#fff;padding:20px;border-radius:8px;position:relative;text-align:center;max-width:90vw;max-height:90vh;"><span id="close_qr_modal" style="position:absolute;top:10px;right:15px;cursor:pointer;font-size:22px;font-weight:bold;">&times;</span><img src="' + connectData.base64 + '" alt="QR Code para conexão" style="margin-top:10px;max-width:300px;"></div>';
                                    document.body.appendChild(modal);
                                    document.getElementById('close_qr_modal').onclick = function() {
                                        modal.remove();
                                        statusSpan.innerText = 'Verificando conexão...';
                                        fetch(url + '/instance/fetchInstances?instanceName=' + instanceFull, {
                                            method: 'GET',
                                            headers: { 'apikey': key }
                                        }).then(function(resp) {
                                            if (resp.status === 200) {
                                                resp.json().then(function(data) {
                                                    var instanceObj = Array.isArray(data) ? data.find(function(item) { return item.name === instanceFull; }) : null;
                                                    if (instanceObj && instanceObj.connectionStatus === 'open') {
                                                        statusSpan.innerText = 'Instância conectada!';
                                                    } else {
                                                        statusSpan.innerText = 'Falhou ao conectar. Por favor, tente conectar novamente.';
                                                    }
                                                });
                                            } else {
                                                statusSpan.innerText = 'Erro ao verificar conexão.';
                                            }
                                        }).catch(function() {
                                            statusSpan.innerText = 'Erro ao verificar conexão.';
                                        });
                                    };
                                }
                                statusSpan.innerText = 'Escaneie o QR Code para conectar.';
                            } else {
                                statusSpan.innerText = 'Instância conectando, mas não foi possível obter o QR Code.';
                            }
                        });
                    } else {
                        statusSpan.innerText = 'Erro ao conectar instância: ' + connectResp.status;
                    }
                });
            } else {
                statusSpan.innerText = 'Erro ao criar instância: ' + createResp.status;
            }
        });
    }
};

/* ═══════ Send test ═══════ */
document.getElementById('evolution_test_send').onclick = function() {
    var url = document.getElementById('evolution_api_url').value;
    var key = document.getElementById('evolution_api_key').value;
    var instance = document.getElementById('evolution_instance_name').value;
    var ddi = document.getElementById('evolution_ddi').value;
    var number = document.getElementById('evolution_test_number').value.replace(/\D/g, '');
    var msg = document.getElementById('evolution_msg_confirmed').value;
    var statusSpan = document.getElementById('evolution_test_message');
    statusSpan.innerText = 'Enviando...';
    var fullNumber = '+' + ddi + number;
    fetch(url + '/message/sendText/dwp-' + instance, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'apikey': key },
        body: JSON.stringify({ number: fullNumber, text: "Teste api, ok!" })
    }).then(function(resp) {
        if (resp.status === 201) statusSpan.innerText = 'Mensagem enviada!';
        else statusSpan.innerText = 'Erro: ' + resp.status;
    }).catch(function() { statusSpan.innerText = 'Erro de conexão.'; });
};

/* ═══════ Toggle webhook key visibility ═══════ */
document.getElementById('toggle_webhook_key').onclick = function() {
    var input = document.getElementById('evolution_webhook_key');
    var icon = this.querySelector('.dashicons');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('dashicons-visibility');
        icon.classList.add('dashicons-hidden');
    } else {
        input.type = 'password';
        icon.classList.remove('dashicons-hidden');
        icon.classList.add('dashicons-visibility');
    }
};

/* ═══════ Random test data generator ═══════ */
function evoRandomData() {
    var names = ['João Silva', 'Maria Santos', 'Carlos Oliveira', 'Ana Costa', 'Pedro Lima'];
    var streets = ['Rua das Flores', 'Av. Brasil', 'Rua São Paulo', 'Av. Paulista', 'Rua XV de Novembro'];
    var bairros = ['Centro', 'Jardim América', 'Vila Nova', 'Boa Vista', 'Santa Cruz'];
    var payments = ['Pix', 'Crédito', 'Débito', 'Dinheiro'];
    var products = ['2x X-Burguer\n1x Batata Frita G\n1x Coca-Cola 600ml', '1x Pizza Calabresa G\n1x Guaraná 2L', '3x Coxinha\n2x Pastel de Carne\n1x Suco Natural'];
    var pick = function(arr) { return arr[Math.floor(Math.random() * arr.length)]; };
    var name = pick(names);
    var orderNum = Math.floor(1000 + Math.random() * 9000);
    var orderCode = String(Math.floor(1000 + Math.random() * 9000));
    return {
        '{customer_name}': name,
        '{first_customer_name}': name.split(' ')[0],
        '{order_number}': String(orderNum),
        '{order_code}': orderCode,
        '{order_products}': pick(products),
        '{shipping_price}': 'R$ ' + (3 + Math.floor(Math.random() * 8)) + ',00',
        '{order_total}': 'R$ ' + (25 + Math.floor(Math.random() * 75)) + ',90',
        '{payment_method}': pick(payments),
        '{payment_change}': 'R$ 0,00',
        '{customer_phone}': '(11) 9' + Math.floor(10000000 + Math.random() * 90000000),
        '{customer_address}': pick(streets),
        '{customer_address_number}': String(Math.floor(100 + Math.random() * 900)),
        '{customer_address_complement}': 'Apto ' + Math.floor(1 + Math.random() * 50),
        '{customer_address_neighborhood}': pick(bairros),
        '{customer_address_zipcode}': String(Math.floor(10000 + Math.random() * 90000)) + '-' + String(Math.floor(100 + Math.random() * 900)),
        '{order_track_page}': window.location.origin + '/acompanhar-pedido/?hash=' + btoa(String(orderNum))
    };
}

function evoReplaceVars(text, data) {
    if (!text) return text;
    for (var key in data) { text = text.split(key).join(data[key]); }
    return text;
}

/* ═══════ Test message sender ═══════ */
function evoTestMsg(section) {
    var url = document.getElementById('evolution_api_url').value;
    var key = document.getElementById('evolution_api_key').value;
    var instance = document.getElementById('evolution_instance_name').value;
    var ddi = document.getElementById('evolution_ddi').value;
    var numInput = document.getElementById('evo_test_num_' + section);
    var statusSpan = document.getElementById('evo_test_status_' + section);
    var number = numInput.value.replace(/\D/g, '');
    if (!number) { statusSpan.innerText = '⚠️ Informe um número'; return; }
    var fullNumber = '+' + ddi + number;
    var data = evoRandomData();
    statusSpan.innerText = 'Enviando...';

    if (section === 'pedido_feito') {
        var btnEnabled = document.getElementById('evolution_btn_enabled').checked;
        if (btnEnabled) {
            var title = evoReplaceVars(document.getElementById('evolution_btn_title').value, data);
            var descEl = document.getElementById('evolution_btn_description');
            var desc = descEl.value.trim() ? evoReplaceVars(descEl.value, data) : evoReplaceVars(document.getElementById('evolution_msg_confirmed_title').value, data);
            var footer = evoReplaceVars(document.getElementById('evolution_btn_footer').value, data);
            var delay = parseInt(document.getElementById('evolution_btn_delay').value) || 0;
            var btnText = document.getElementById('evolution_btn_display_text').value || '📦 Acompanhar Pedido';
            var buttons = [{ type: 'url', displayText: evoReplaceVars(btnText, data), url: data['{order_track_page}'] }];
            var body = { number: fullNumber, title: title, description: desc, footer: footer, buttons: buttons };
            if (delay > 0) body.delay = delay;
            fetch(url + '/message/sendButtons/dwp-' + instance, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'apikey': key },
                body: JSON.stringify(body)
            }).then(function(r) {
                statusSpan.innerText = r.status === 201 ? '✅ Mensagem com botão enviada!' : '❌ Erro: ' + r.status;
            }).catch(function() { statusSpan.innerText = '❌ Erro de conexão.'; });
        } else {
            var msg = evoReplaceVars(document.getElementById('evolution_msg_confirmed_title').value, data);
            fetch(url + '/message/sendText/dwp-' + instance, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'apikey': key },
                body: JSON.stringify({ number: fullNumber, text: msg })
            }).then(function(r) {
                statusSpan.innerText = r.status === 201 ? '✅ Mensagem enviada!' : '❌ Erro: ' + r.status;
            }).catch(function() { statusSpan.innerText = '❌ Erro de conexão.'; });
        }
    } else if (section === 'confirmado') {
        var msg = evoReplaceVars(document.getElementById('evolution_msg_confirmed').value, data);
        fetch(url + '/message/sendText/dwp-' + instance, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'apikey': key },
            body: JSON.stringify({ number: fullNumber, text: msg })
        }).then(function(r) {
            statusSpan.innerText = r.status === 201 ? '✅ Mensagem enviada!' : '❌ Erro: ' + r.status;
        }).catch(function() { statusSpan.innerText = '❌ Erro de conexão.'; });
    } else if (section === 'entrega') {
        var msg = evoReplaceVars(document.getElementById('evolution_msg_delivery').value, data);
        fetch(url + '/message/sendText/dwp-' + instance, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'apikey': key },
            body: JSON.stringify({ number: fullNumber, text: msg })
        }).then(function(r) {
            statusSpan.innerText = r.status === 201 ? '✅ Mensagem enviada!' : '❌ Erro: ' + r.status;
        }).catch(function() { statusSpan.innerText = '❌ Erro de conexão.'; });
    }
}
</script>
