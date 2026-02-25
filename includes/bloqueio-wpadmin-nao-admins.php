<?php
/**
 * Plugin Name: Bloqueio WP-Admin para Não-Admins (Página Personalizada)
 * Description: Bloqueia acesso ao painel para usuários que não sejam administradores e exibe mensagem personalizada.
 * Version: 1.0
 * Author: Você
 */

// Função para exibir página de bloqueio
function bwna_pagina_bloqueio() {
    $logout_url = wp_logout_url( home_url() );
    // Garante que o nonce está presente para logout imediato
    if ( strpos( $logout_url, '_wpnonce' ) === false ) {
        $logout_url = wp_nonce_url( $logout_url, 'log-out' );
    }
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?> >
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Acesso Negado</title>
        <?php wp_head(); ?>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f8f8f8;
                color: #333;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
            }
            .acesso-negado {
                background: #fff;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 400px;
                width: 100%;
            }
            .acesso-negado h1 {
                color: #c0392b;
                margin-bottom: 15px;
            }
            .acesso-negado p {
                margin-bottom: 20px;
            }
            .btn-logout {
                display: inline-block;
                padding: 10px 20px;
                background: #c0392b;
                color: #fff;
                text-decoration: none;
                border-radius: 4px;
                font-weight: bold;
            }
            .btn-logout:hover {
                background: #e74c3c;
            }
        </style>
    </head>
    <body>
        <div class="acesso-negado">
            <h1>🚫 Acesso Negado</h1>
            <p>Você não tem permissão para acessar esta página.<br>
            Por favor, faça login com uma conta de administrador.</p>
            <a class="btn-logout" href="<?php echo esc_url( $logout_url ); ?>">Sair da Conta</a>
        </div>
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
    exit;
}

// Bloqueio no painel
add_action('admin_init', function () {
    if ( is_user_logged_in()
        && is_admin()
        && ! ( current_user_can('administrator') || current_user_can('myd_view_reports') )
        && ! (defined('DOING_AJAX') && DOING_AJAX)
    ) {
        // Permite logout normalmente
        if (
            (isset($_GET['action']) && $_GET['action'] === 'logout') ||
            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'action=logout') !== false)
        ) {
            return;
        }
        bwna_pagina_bloqueio();
    }
});

// Bloqueio no wp-login.php já logado
/*
add_action('login_init', function () {
    if ( is_user_logged_in() && ! ( current_user_can('administrator') || current_user_can('myd_view_reports') ) ) {
        // Permite logout normalmente
        if (
            (isset($_GET['action']) && $_GET['action'] === 'logout') ||
            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'action=logout') !== false)
        ) {
            return;
        }
        bwna_pagina_bloqueio();
    }
});
*/

// Redirecionamento pós-login
add_filter('login_redirect', function ($redirect_to, $requested, $user) {
    // Se não for um usuário válido, retorna o redirect_to original
    if ( ! $user instanceof WP_User ) {
        return $redirect_to;
    }
    
    // Verifica se o usuário tem a role 'marketing'
    $has_marketing_role = in_array( 'marketing', (array) $user->roles, true );
    
    // Se for admin, tiver myd_view_reports ou tiver a role marketing, respeita o redirect_to
    if ( user_can($user, 'administrator') || user_can($user, 'myd_view_reports') || $has_marketing_role ) {
        return $redirect_to;
    }
    
    // Para outros usuários, se o redirect_to contiver /dashboard, permite o redirecionamento
    if ( strpos( $redirect_to, '/dashboard' ) !== false ) {
        return $redirect_to;
    }
    
    // Caso contrário, redireciona para home
    return home_url('/');
}, 999, 3);

// Remove barra de admin para todos
add_filter('show_admin_bar', '__return_false');
