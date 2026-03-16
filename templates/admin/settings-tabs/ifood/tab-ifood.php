<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$ifood_client_id     = get_option('ifood_client_id', '');
$ifood_client_secret = get_option('ifood_client_secret', '');
$ifood_access_token  = get_option('ifood_access_token', '');
$ifood_token_expiry  = get_option('ifood_token_expiry', '');
?>

<style>
.ifood-section {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    margin-bottom: 16px;
    overflow: hidden;
}
.ifood-section-header {
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
.ifood-section-header:hover { background: #eef0f1; }
.ifood-section-header h3 { margin: 0; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.ifood-section-header .dashicons { transition: transform .2s; color: #787c82; }
.ifood-section-header.collapsed .dashicons { transform: rotate(-90deg); }
.ifood-section-body { padding: 18px; }
.ifood-field { margin-bottom: 14px; }
.ifood-field label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 13px; }
.ifood-field input[type="text"],
.ifood-field input[type="password"] { width: 100%; max-width: 500px; }
.ifood-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    margin-top: 8px;
}
.ifood-status-active { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.ifood-status-expired { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<div id="tab-ifood-content" class="myd-tabs-content">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
        <img src="https://logospng.org/download/ifood/logo-ifood-1024.png" alt="iFood" style="height:36px;" />
        <h2 style="margin:0;">Integração iFood</h2>
    </div>

    <div class="ifood-section">
        <div class="ifood-section-header" onclick="ifoodToggleSection(this)">
            <h3><span class="dashicons dashicons-lock"></span> Autenticação</h3>
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </div>
        <div class="ifood-section-body">
            <div class="ifood-field">
                <label for="ifood_client_id">Client ID</label>
                <input type="text" name="ifood_client_id" id="ifood_client_id" value="<?php echo esc_attr($ifood_client_id); ?>" />
            </div>
            <div class="ifood-field">
                <label for="ifood_client_secret">Client Secret</label>
                <input type="password" name="ifood_client_secret" id="ifood_client_secret" value="<?php echo esc_attr($ifood_client_secret); ?>" />
            </div>
            
            <hr style="margin:20px 0; border:0; border-top:1px solid #eee;">

            <div class="ifood-field">
                <label for="ifood_access_token">Access Token</label>
                <input type="text" name="ifood_access_token" id="ifood_access_token" value="<?php echo esc_attr($ifood_access_token); ?>" readonly style="background:#f0f0f1;" />
            </div>

            <div id="ifood_token_expiry_info" style="margin-bottom: 15px;">
                <label>Expiração do Token:</label>
                <span id="ifood_token_expiry_label" style="font-weight:bold;"><?php echo $ifood_token_expiry ? esc_html($ifood_token_expiry) : 'Nenhum token gerado'; ?></span>
                <input type="hidden" name="ifood_token_expiry" id="ifood_token_expiry" value="<?php echo esc_attr($ifood_token_expiry); ?>" />
            </div>

            <button type="button" class="button button-primary" id="ifood_authenticate_btn">Autenticar com iFood</button>
            <span id="ifood_auth_status" style="margin-left: 10px; font-weight: 500;"></span>
        </div>
    </div>
</div>

<script>
function ifoodToggleSection(header) {
    header.classList.toggle('collapsed');
    var body = header.nextElementSibling;
    body.style.display = (body.style.display === 'none') ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const authBtn = document.getElementById('ifood_authenticate_btn');
    const statusSpan = document.getElementById('ifood_auth_status');
    const clientIdInput = document.getElementById('ifood_client_id');
    const clientSecretInput = document.getElementById('ifood_client_secret');
    const accessTokenInput = document.getElementById('ifood_access_token');
    const expiryInput = document.getElementById('ifood_token_expiry');
    const expiryLabel = document.getElementById('ifood_token_expiry_label');

    if (authBtn) {
        authBtn.addEventListener('click', function() {
            const clientId = clientIdInput.value.trim();
            const clientSecret = clientSecretInput.value.trim();

            if (!clientId || !clientSecret) {
                statusSpan.innerHTML = '<span style="color:red;">Preencha Client ID e Client Secret.</span>';
                return;
            }

            statusSpan.innerHTML = 'Autenticando...';
            authBtn.disabled = true;

            jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'myd_ifood_authenticate',
                    clientId: clientId,
                    clientSecret: clientSecret
                },
                success: function(response) {
                    if (response.success && response.data.accessToken) {
                        var data = response.data;
                        accessTokenInput.value = data.accessToken;
                        
                        // Cálculo de expiração
                        const now = new Date();
                        const expiryDate = new Date(now.getTime() + (data.expiresIn * 1000));
                        const formattedExpiry = expiryDate.toLocaleString('pt-BR');
                        
                        expiryInput.value = formattedExpiry;
                        expiryLabel.innerText = formattedExpiry;
                        
                        statusSpan.innerHTML = '<span style="color:green;">Autenticado com sucesso! Salve as alterações.</span>';
                    } else {
                        var msg = response.data && response.data.message ? response.data.message : 'Erro desconhecido.';
                        statusSpan.innerHTML = '<span style="color:red;">' + msg + '</span>';
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro iFood Auth:', error);
                    statusSpan.innerHTML = '<span style="color:red;">Erro na requisição ao servidor.</span>';
                },
                complete: function() {
                    authBtn.disabled = false;
                }
            });
        });
    }
});
</script>
