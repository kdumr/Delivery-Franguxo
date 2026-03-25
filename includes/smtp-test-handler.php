<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_myd_test_smtp', function() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'myd_admin_nonce' ) ) {
        wp_send_json_error('Acesso negado. Falha na verificação de segurança.');
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error('Você não tem permissão para realizar esta ação.');
    }

    $email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';
    if (!$email || !is_email($email)) {
        wp_send_json_error('E-mail inválido.');
    }
    $subject = 'Teste SMTP - MyDelivery Pro';
    $message = 'Este é um e-mail de teste do sistema SMTP.';
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $sent = wp_mail($email, $subject, $message, $headers);
    if ($sent) {
        wp_send_json_success('E-mail enviado com sucesso para ' . esc_html($email));
    } else {
        wp_send_json_error('Falha ao enviar o e-mail. Verifique as configurações SMTP.');
    }
    wp_die();
});
