<?php
// Handler AJAX para alteração de senha do cliente logado
add_action('wp_ajax_myd_update_customer_password', 'myd_update_customer_password');

function myd_update_customer_password() {
    // Verifica nonce
    if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'myd_customer_auth')) {
        wp_send_json_error(['message' => 'Falha de segurança. Recarregue a página.']);
    }
    // Verifica login
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Você precisa estar logado para alterar a senha.']);
    }
    $user_id = get_current_user_id();
    $new_password = isset($_POST['password']) ? trim($_POST['password']) : '';
    if (strlen($new_password) < 6) {
        wp_send_json_error(['message' => 'A senha deve ter pelo menos 6 caracteres.']);
    }
    // Atualiza senha
    wp_set_password($new_password, $user_id);
    // Faz login automático após troca de senha
    wp_set_auth_cookie($user_id);
    wp_send_json_success(['message' => 'Senha alterada com sucesso!']);
}
