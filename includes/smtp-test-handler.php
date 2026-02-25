<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_myd_test_smtp', function() {
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
