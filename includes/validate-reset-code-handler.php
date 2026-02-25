<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_nopriv_myd_validate_reset_code', 'myd_validate_reset_code_handler');
add_action('wp_ajax_myd_validate_reset_code', 'myd_validate_reset_code_handler');

function myd_validate_reset_code_handler() {
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $only_validate = isset($_POST['only_validate']) ? intval($_POST['only_validate']) : 0;
    if (!$email || !$code) {
        wp_send_json_error(['message' => 'Dados incompletos.']);
    }
    $user = get_user_by('email', $email);
    if (!$user) {
        wp_send_json_error(['message' => 'Usuário não encontrado.']);
    }
    $saved_code = get_user_meta($user->ID, 'myd_forgot_code', true);
    $expires = get_user_meta($user->ID, 'myd_forgot_code_expires', true);
    if (!$saved_code || !$expires || time() > $expires) {
        wp_send_json_error(['message' => 'Código expirado ou inválido.']);
    }
    if ($code !== $saved_code) {
        wp_send_json_error(['message' => 'Código incorreto.']);
    }
    if ($only_validate) {
        // Só valida o código, não redefine senha ainda
        wp_send_json_success(['message' => 'Código válido.']);
        wp_die();
    }
    if (!$new_password) {
        wp_send_json_error(['message' => 'Dados incompletos.']);
    }
    // Atualiza a senha
    wp_set_password($new_password, $user->ID);
    // Limpa o código
    delete_user_meta($user->ID, 'myd_forgot_code');
    delete_user_meta($user->ID, 'myd_forgot_code_expires');
    wp_send_json_success(['message' => 'Senha redefinida com sucesso!']);
    wp_die();
}
