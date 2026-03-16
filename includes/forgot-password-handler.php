<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_nopriv_myd_forgot_password', 'myd_forgot_password_handler');
add_action('wp_ajax_myd_forgot_password', 'myd_forgot_password_handler');

function myd_forgot_password_handler() {
    if ( function_exists('myd_check_ip_rate_limit') && myd_check_ip_rate_limit( 'forgot_password', 5, 3600 ) ) {
        wp_send_json_error(['message' => 'Muitas solicitações. Tente novamente mais tarde.']);
    }

    // Accept either `email` or `identifier` (CPF digits). Backwards-compatible with existing email param.
    $identifier = '';
    if (isset($_POST['identifier'])) {
        $identifier = sanitize_text_field($_POST['identifier']);
    } elseif (isset($_POST['email'])) {
        $identifier = sanitize_text_field($_POST['email']);
    }

    if (empty($identifier)) {
        wp_send_json_error(['message' => 'Digite o email ou CPF usado no cadastro para recuperar a senha.']);
    }

    $user = null;
    $email = '';

    // If it's a valid email, search by email
    if (is_email($identifier)) {
        $email = sanitize_email($identifier);
        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_error(['message' => 'E-mail não encontrado.']);
        }
    } else {
        // Try CPF (digits only)
        $digits = preg_replace('/\D/', '', $identifier);
        if (!preg_match('/^\d{11}$/', $digits)) {
            wp_send_json_error(['message' => 'E-mail ou CPF inválido.']);
        }

        $users_with_cpf = get_users(array(
            'meta_key' => 'myd_customer_cpf',
            'meta_value' => $digits,
            'number' => 1,
        ));

        if (empty($users_with_cpf)) {
            wp_send_json_error(['message' => 'CPF não encontrado.']);
        }

        $user = $users_with_cpf[0];
        $email = $user->user_email;
    }

    // Limite de tentativas por hora (rate limit)
    $limit = 5; // máximo de 5 solicitações por hora
    $window = 300; // 5 minutos em segundos
    $now = time();
    $history = get_user_meta($user->ID, 'myd_forgot_code_history', true);
    if (!is_array($history)) $history = [];
    // Remove registros antigos
    $history = array_filter($history, function($ts) use ($now, $window) { return ($now - $ts) < $window; });
    if (count($history) >= $limit) {
        wp_send_json_error(['message' => 'Você fez muitas solicitações. Tente novamente em 1 hora.']);
    }
    // Adiciona tentativa atual
    $history[] = $now;
    update_user_meta($user->ID, 'myd_forgot_code_history', $history);

    // Gera código de 6 dígitos criptograficamente seguro
    try {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    } catch (\Exception $e) {
        $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    $expires = $now + 600; // 10 minutos
    update_user_meta($user->ID, 'myd_forgot_code', $code);
    update_user_meta($user->ID, 'myd_forgot_code_expires', $expires);

        // Envia e-mail em HTML com logo do WordPress
        $store_name = get_option('fdm-business-name');
        $subject = 'Código de recuperação - ' . ($store_name ? $store_name : 'Delivery');
        // Pega logo do WordPress
        $logo_id = get_theme_mod('custom_logo');
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
        // Formata código com espaço
        $code_fmt = substr($code,0,3).'&nbsp;'.substr($code,3,3);
        $html = '<!DOCTYPE html>
<html lang="pt-BR" style="margin:0;padding:0;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta name="x-apple-disable-message-reformatting">
    <title>Código de confirmação</title>
</head>
<body class="email-bg" style="margin:0;padding:0;background:#f3f5f7;">
    <div style="display:none;overflow:hidden;line-height:1px;opacity:0;max-height:0;max-width:0;">
        Seu código para recuperar a senha. Válido por 8 minutos.
    </div>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <tr>
            <td align="center" style="padding:24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;border-collapse:collapse;">
                    <tr>
                        <td align="center" style="padding:12px 24px;">
                            <img src="'.esc_url($logo_url).'" width="140" height="auto" alt="Logo" style="display:block;max-width:140px;border:0;outline:none;text-decoration:none;">
                        </td>
                    </tr>
                    <tr>
                        <td class="card" style="background:#ffffff;border:1px solid #e6e9ee;border-radius:12px;padding:28px 24px;">
                            <h1 class="text" style="margin:0 0 12px 0;font-family:Arial,Helvetica,sans-serif;font-size:24px;line-height:1.3;color:#111827;font-weight:700;text-align:left;">
                                Olá
                            </h1>
                            <p class="text" style="margin:0 0 16px 0;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#111827;">
                                Este é o código para recuperar sua senha:
                            </p>
                            <div class="code" style="margin:0 0 16px 0;padding:16px 20px;border:1px dashed #d2d6dd;border-radius:10px;background:#f7f9fc;text-align:center;">
                                <span style="font-family:Consolas,\'Courier New\',Courier,monospace;font-size:28px;letter-spacing:2px;font-weight:700;color:#111827;display:inline-block;">
                                    '.$code_fmt.'
                                </span>
                            </div>
                            <p class="muted" style="margin:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#6b7280;">
                                Este código é válido por 8 minutos.
                            </p>
                            <p class="muted" style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.6;color:#6b7280;">
                                Este é um e-mail automático.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:16px 24px;">
                            <p class="muted" style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#8a93a0;">
                                Se você não solicitou este código, ignore este e-mail.
                            </p>
                        </td>
                    </tr>
                </table>
                <div style="height:24px;line-height:24px;">&nbsp;</div>
            </td>
        </tr>
    </table>
</body>
</html>';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($email, $subject, $html, $headers);
        if ($sent) {
            wp_send_json_success(['message' => 'Enviamos um código de recuperação para seu e-mail.', 'email' => $email]);
        } else {
            wp_send_json_error(['message' => 'Falha ao enviar o e-mail.']);
        }
        wp_die();
}
