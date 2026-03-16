<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Performs the actual authentication request to iFood and updates options.
 * 
 * @return array|WP_Error Array with token data on success, WP_Error on failure.
 */
function myd_ifood_perform_token_refresh() {
    $client_id     = get_option('ifood_client_id', '');
    $client_secret = get_option('ifood_client_secret', '');

    if ( empty($client_id) || empty($client_secret) ) {
        return new WP_Error('missing_credentials', 'Client ID ou Client Secret ausentes nas configurações.');
    }

    $response = wp_remote_post('https://merchant-api.ifood.com.br/authentication/v1.0/oauth/token', array(
        'method'      => 'POST',
        'timeout'     => 30,
        'headers'     => array(
            'accept'       => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ),
        'body'        => array(
            'grantType'                => 'client_credentials',
            'clientId'                 => $client_id,
            'clientSecret'             => $client_secret,
            'authorizationCode'        => '',
            'authorizationCodeVerifier' => '',
            'refreshToken'             => '',
        ),
    ));

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );
    $code = wp_remote_retrieve_response_code( $response );

    if ( $code !== 200 ) {
        $error_msg = isset($data['error']['message']) ? $data['error']['message'] : (isset($data['message']) ? $data['message'] : 'Erro desconhecido (' . $code . ')');
        return new WP_Error('ifood_api_error', $error_msg);
    }

    if ( ! empty($data['accessToken']) ) {
        // Update options
        update_option('ifood_access_token', $data['accessToken']);
        
        $expires_in = isset($data['expiresIn']) ? intval($data['expiresIn']) : 3600;
        $expiry_timestamp = time() + $expires_in;
        $formatted_expiry = date_i18n('d/m/Y H:i:s', $expiry_timestamp);

        update_option('ifood_token_expiry', $formatted_expiry);
        update_option('ifood_token_expiry_timestamp', $expiry_timestamp);
        
        // Clean up retry flag if exists
        delete_option('ifood_refresh_retry_30');

        return $data;
    }

    return new WP_Error('empty_token', 'Token não recebido da API.');
}

/**
 * AJAX handler for manual authentication.
 */
add_action('wp_ajax_myd_ifood_authenticate', function() {
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error( array( 'message' => 'Permissão insuficiente.' ) );
    }

    // Temporarily update options with provided inputs if they are different
    $client_id     = isset($_POST['clientId']) ? sanitize_text_field($_POST['clientId']) : '';
    $client_secret = isset($_POST['clientSecret']) ? sanitize_text_field($_POST['clientSecret']) : '';

    if ( ! empty($client_id) ) update_option('ifood_client_id', $client_id);
    if ( ! empty($client_secret) ) update_option('ifood_client_secret', $client_secret);

    $result = myd_ifood_perform_token_refresh();

    if ( is_wp_error($result) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    wp_send_json_success( $result );
});

/**
 * Cron handler to check and refresh token.
 */
add_action('myd_ifood_token_refresh_check', function() {
    $expiry_ts = (int) get_option('ifood_token_expiry_timestamp', 0);
    if ( ! $expiry_ts ) return;

    $now = time();
    $diff = $expiry_ts - $now;

    // Cases:
    // 1. Less than 1 hour remaining (3600 seconds)
    // 2. Failure occurred at 1 hour, retry at 30 minutes (1800 seconds)

    $should_refresh = false;
    $is_30_min_retry = false;

    if ( $diff <= 3600 && $diff > 1800 ) {
        // Faltando entre 1h e 30min
        $already_tried = get_option('ifood_last_refresh_attempt_1h', 0);
        if ( $now - $already_tried > 300 ) { // Prevent multiple tries in same minute if cron runs fast
            $should_refresh = true;
            update_option('ifood_last_refresh_attempt_1h', $now);
        }
    } elseif ( $diff <= 1800 && $diff > 0 ) {
        // Faltando menos de 30min
        $already_tried = get_option('ifood_last_refresh_attempt_30m', 0);
        if ( $now - $already_tried > 300 ) {
            $should_refresh = true;
            update_option('ifood_last_refresh_attempt_30m', $now);
        }
    }

    if ( $should_refresh ) {
        $result = myd_ifood_perform_token_refresh();
        if ( is_wp_error( $result ) ) {
            error_log('MyD iFood Auto-Refresh Error: ' . $result->get_error_message());
        } else {
            // Success: clear attempt markers for next cycle
            delete_option('ifood_last_refresh_attempt_1h');
            delete_option('ifood_last_refresh_attempt_30m');
        }
    }
});
