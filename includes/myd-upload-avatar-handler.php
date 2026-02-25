<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_myd_upload_avatar', 'myd_upload_avatar_handler');

function myd_upload_avatar_handler() {
    // verifica nonce e login
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if ( empty($nonce) || ! wp_verify_nonce( $nonce, 'myd_customer_auth' ) ) {
        wp_send_json_error( array( 'message' => 'Falha de segurança.' ), 403 );
    }
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Você precisa estar logado.' ), 403 );
    }

    if ( empty( $_FILES['avatar'] ) ) {
        wp_send_json_error( array( 'message' => 'Nenhum arquivo enviado.' ), 400 );
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $file = $_FILES['avatar'];

    $overrides = array( 'test_form' => false );
    $movefile = wp_handle_upload( $file, $overrides );
    if ( isset( $movefile['error'] ) ) {
        wp_send_json_error( array( 'message' => 'Erro ao enviar arquivo: ' . $movefile['error'] ), 500 );
    }

    $filename = $movefile['file'];
    $filetype = wp_check_filetype( basename( $filename ), null );

    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name( basename( $filename ) ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    $attach_id = wp_insert_attachment( $attachment, $movefile['file'] );
    if ( is_wp_error( $attach_id ) ) {
        wp_send_json_error( array( 'message' => 'Erro ao inserir attachment.' ), 500 );
    }

    $attach_data = wp_generate_attachment_metadata( $attach_id, $movefile['file'] );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    $url = wp_get_attachment_url( $attach_id );
    $user_id = get_current_user_id();
    // Salva ID e URL no meta do usuário
    update_user_meta( $user_id, 'myd_avatar_id', $attach_id );
    update_user_meta( $user_id, 'myd_avatar_url', $url );

    wp_send_json_success( array( 'message' => 'Avatar atualizado.', 'url' => $url, 'id' => $attach_id ) );
}
