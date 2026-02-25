<?php
namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Customiza a tela de login (wp-login.php)
 * para ter o aspecto do Gestor de Pedidos no desktop.
 */
class Custom_Login {

    public function __construct() {
        // Enfileira os estilos na página de login
        add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_styles' ) );
        
        // Altera o link do logo para ir para a home do site
        add_filter( 'login_headerurl', array( $this, 'custom_login_header_url' ) );
        
        // Adiciona a estrutura HTML extra do lado esquerdo no footer (mas posicionado via CSS)
        add_action( 'login_footer', array( $this, 'add_left_side_html' ) );

        // Troca os textos do botão e placeholders
        add_filter( 'gettext', array( $this, 'change_default_texts' ), 20, 3 );
        
        // Customiza a view introduzindo título antes do form
        add_filter( 'login_message', array( $this, 'custom_login_message' ) );
    }

    public function enqueue_login_styles() {
        wp_enqueue_style( 'myd-custom-login', MYD_PLUGN_URL . 'assets/css/myd-login.css', array( 'login' ), MYD_CURRENT_VERSION );
    }

    public function custom_login_header_url() {
        return home_url();
    }

    public function add_left_side_html() {
        // Usa a imagem de fundo da cozinha
        $bg_image = MYD_PLUGN_URL . 'assets/img/kitchen.jpg';
        // Usa a imagem de frango flutuante
        $frango_img = MYD_PLUGN_URL . 'assets/img/frango1.png';
        // Letreiro / Logo principal
        $letter_img = MYD_PLUGN_URL . 'assets/img/franguxoletter.png';
        
        echo '<div class="myd-login-left-panel" style="background-image: url(\'' . esc_url($bg_image) . '\');">';
        echo '  <img src="' . esc_url($frango_img) . '" class="myd-floating-frango myd-frango-1" alt="" aria-hidden="true" />';
        echo '  <img src="' . esc_url($frango_img) . '" class="myd-floating-frango myd-frango-2" alt="" aria-hidden="true" />';
        echo '  <img src="' . esc_url($frango_img) . '" class="myd-floating-frango myd-frango-3" alt="" aria-hidden="true" />';
        echo '  <img src="' . esc_url($frango_img) . '" class="myd-floating-frango myd-frango-4" alt="" aria-hidden="true" />';

        echo '  <div class="myd-login-left-content">';
        echo '      <img src="' . esc_url($letter_img) . '" alt="Franguxo" class="myd-login-letter-img" />';
        echo '  </div>';
        echo '</div>';

    }

    public function change_default_texts( $translated_text, $text, $domain ) {
        if ( in_array( $GLOBALS['pagenow'], array( 'wp-login.php' ) ) ) {
            if ( 'Acessar' === $translated_text || 'Log In' === $text || 'Log in' === $text ) {
                return 'Continuar';
            }
            if ( 'Nome de usuário ou endereço de e-mail' === $translated_text || 'Username or Email Address' === $text ) {
                return 'E-mail';
            }
        }
        return $translated_text;
    }

    public function custom_login_message( $message ) {
        $header  = '<div class="myd-login-form-header">';
        $header .= '  <h2>Franguxo - Gestor de Pedidos</h2>';
        $header .= '</div>';
        
        return $header . $message;
    }
}
