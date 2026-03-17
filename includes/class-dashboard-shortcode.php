<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Myd_Dashboard_Shortcode {
    public static function init() {
        // Register AJAX handlers
        add_action( 'wp_ajax_myd_dashboard_filter', array( __CLASS__, 'ajax_filter' ) );
        // Settings: password reset / avatar update
        add_action( 'wp_ajax_myd_send_reset_code', array( __CLASS__, 'ajax_send_reset_code' ) );
        add_action( 'wp_ajax_myd_verify_reset_code', array( __CLASS__, 'ajax_verify_reset_code' ) );
        add_action( 'wp_ajax_myd_update_password', array( __CLASS__, 'ajax_update_password' ) );
        add_action( 'wp_ajax_myd_update_avatar', array( __CLASS__, 'ajax_update_avatar' ) );
    }
    // Determine whether an order (post) should be considered "finished".
    // Tries WooCommerce status, several common meta keys and fallbacks.
    public static function is_order_finished( $post_id ) {
        if ( empty( $post_id ) ) {
            return false;
        }

        // Only consider orders finished if an explicit meta key/value indicates 'finished'.
        // Prefer the canonical 'order_status' meta, but check a few alternative keys used by integrations.
        $meta_keys = array( 'order_status', 'myd_order_status', 'status', '_order_status', 'order_state' );
        foreach ( $meta_keys as $k ) {
            $v = get_post_meta( $post_id, $k, true );
            if ( $v === '' || $v === null ) {
                continue;
            }
            $s = strtolower( trim( (string) $v ) );
            if ( $s === 'finished' ) {
                return true;
            }
        }

        // If a WC order object exists and its status string equals 'finished', accept it.
        if ( function_exists( 'wc_get_order' ) ) {
            try {
                $order = wc_get_order( $post_id );
                if ( $order && method_exists( $order, 'get_status' ) ) {
                    $s = strtolower( trim( (string) $order->get_status() ) );
                    if ( $s === 'finished' ) {
                        return true;
                    }
                }
            } catch ( \Exception $e ) {
                // ignore
            }
        }

        return false;
    }
    public static function get_dashboard_data( $start_date, $end_date ) {
        $start_ts = strtotime( $start_date );
        $end_ts = strtotime( $end_date );
        $days = array();
        for ( $ts = $start_ts; $ts <= $end_ts; $ts = strtotime( '+1 day', $ts ) ) {
            $days[] = date( 'Y-m-d', $ts );
        }

        $args = array(
            'post_type' => 'mydelivery-orders',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'date_query' => array(
                array(
                    'after' => date( 'Y-m-d', $start_ts ),
                    'before' => date( 'Y-m-d', $end_ts ),
                    'inclusive' => true,
                ),
            ),
        );

        $query = new \WP_Query( $args );
        $total = 0.0;
        $count = 0;
        $economy = 0.0;
        $daily = array_fill_keys( $days, 0.0 );
        $daily_counts = array_fill_keys( $days, 0 );
        $channel_counts = array(
            'SYS' => 0,
            'WPP' => 0,
            'IFD' => 0,
        );
        $payment_totals = array();
        // Tempo de entrega por dia da semana (0=Dom, 1=Seg, ..., 6=Sab)
        $delivery_times_by_weekday = array(
            0 => array(), // Dom
            1 => array(), // Seg
            2 => array(), // Ter
            3 => array(), // Qua
            4 => array(), // Qui
            5 => array(), // Sex
            6 => array(), // Sab
        );
        $delivery_total_minutes = 0;
        $delivery_total_count = 0;
        $delivery_ontime_total = 0;
        $delivery_ontime_count = 0;
        $avg_prep_time = (int) get_option( 'myd-average-preparation-time', 30 );

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $p ) {
                $post_id = is_object( $p ) ? $p->ID : (int) $p['ID'];
                // only count orders considered "finished"
                if ( ! self::is_order_finished( $post_id ) ) {
                    continue;
                }

                $order_total = get_post_meta( $post_id, 'order_total', true );
                if ( $order_total === '' || $order_total === null ) {
                    $order_total = get_post_meta( $post_id, 'myd_order_total', true );
                }
                if ( $order_total === '' || $order_total === null ) {
                    $order_total = get_post_meta( $post_id, 'fdm_order_total', true );
                }
                $order_total = floatval( str_replace( ',', '.', (string) $order_total ) );
                $total += $order_total;
                $count++;

                $coupon = floatval( get_post_meta( $post_id, 'order_coupon_discount', true ) );
                $fidelity = floatval( get_post_meta( $post_id, 'order_fidelity_discount', true ) );
                $economy += ( $coupon + $fidelity );

                $day = date( 'Y-m-d', strtotime( $p->post_date ) );
                if ( isset( $daily[ $day ] ) ) {
                    $daily[ $day ] += $order_total;
                    $daily_counts[ $day ] += 1;
                }
                // count order channel (normalize common meta keys)
                $order_channel = get_post_meta( $post_id, 'order_channel', true );
                if ( empty( $order_channel ) ) {
                    $order_channel = get_post_meta( $post_id, 'order_chanel', true );
                }
                $oc = strtoupper( trim( (string) $order_channel ) );
                if ( $oc === 'SYS' ) {
                    $channel_counts['SYS']++;
                } elseif ( strpos( $oc, 'WPP' ) !== false || strpos( $oc, 'WHATSAPP' ) !== false ) {
                    $channel_counts['WPP']++;
                } elseif ( $oc === 'IFD' || stripos( $oc, 'IFOOD' ) !== false ) {
                    $channel_counts['IFD']++;
                }

                // Agrupar por forma de pagamento (somando o valor total)
                $payment_method = get_post_meta( $post_id, 'order_payment_method', true );
                $pm = strtoupper( trim( (string) $payment_method ) );
                if ( $pm !== '' ) {
                    // Normalizar para categorias conhecidas ou ONLINE
                    $known_methods = array( 'DIN', 'CRD', 'DEB', 'VRF' );
                    if ( ! in_array( $pm, $known_methods, true ) ) {
                        $pm = 'ONLINE';
                    }
                    if ( ! isset( $payment_totals[ $pm ] ) ) {
                        $payment_totals[ $pm ] = 0;
                    }
                    $payment_totals[ $pm ] += $order_total;
                }

                // Coletar tempo de entrega por dia da semana
                $delivery_time = get_post_meta( $post_id, 'order_delivery_time', true );
                if ( ! empty( $delivery_time ) ) {
                    $minutes = 0;
                    $delivery_ts = 0;
                    // Se for uma data/hora (ex: 02-02-2026 10:36), calcula diferença para post_date
                    if ( preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})[\sT](\d{2}):(\d{2})/', $delivery_time, $dt_match ) ) {
                        $entrega_ts = strtotime(sprintf('%04d-%02d-%02d %02d:%02d:00', $dt_match[3], $dt_match[2], $dt_match[1], $dt_match[4], $dt_match[5]));
                        $pedido_ts = strtotime($p->post_date);
                        if ($entrega_ts && $pedido_ts && $entrega_ts > $pedido_ts) {
                            $minutes = round(($entrega_ts - $pedido_ts) / 60);
                            $delivery_ts = $entrega_ts;
                        }
                    } else {
                        // Tentar extrair minutos do formato (pode ser "45", "45 min", "1h 30min", etc.)
                        if ( preg_match( '/(\d+)\s*h/i', $delivery_time, $h_match ) ) {
                            $minutes += intval( $h_match[1] ) * 60;
                        }
                        if ( preg_match( '/(\d+)\s*min/i', $delivery_time, $m_match ) ) {
                            $minutes += intval( $m_match[1] );
                        }
                        // Se não encontrou padrão, tenta pegar apenas número
                        if ( $minutes === 0 && preg_match( '/(\d+)/', $delivery_time, $n_match ) ) {
                            $minutes = intval( $n_match[1] );
                        }
                        if ( $minutes > 0 ) {
                            $pedido_ts = strtotime($p->post_date);
                            if ( $pedido_ts ) {
                                $delivery_ts = $pedido_ts + ( $minutes * 60 );
                            }
                        }
                    }
                    if ( $minutes > 0 ) {
                        $weekday = (int) date( 'w', strtotime( $p->post_date ) );
                        $delivery_times_by_weekday[ $weekday ][] = $minutes;
                        $delivery_total_minutes += $minutes;
                        $delivery_total_count++;
                    }
                    if ( $delivery_ts ) {
                        $pedido_ts = strtotime($p->post_date);
                        if ( $pedido_ts ) {
                            $deadline_ts = $pedido_ts + ( $avg_prep_time * 60 );
                            $delivery_ontime_total++;
                            if ( $delivery_ts <= $deadline_ts ) {
                                $delivery_ontime_count++;
                            }
                        }
                    }
                }
            }
        }

        // Calcular média de tempo de entrega por dia da semana
        $delivery_avg_by_weekday = array();
        for ( $wd = 0; $wd <= 6; $wd++ ) {
            $times = $delivery_times_by_weekday[ $wd ];
            if ( count( $times ) > 0 ) {
                $delivery_avg_by_weekday[ $wd ] = round( array_sum( $times ) / count( $times ) );
            } else {
                $delivery_avg_by_weekday[ $wd ] = 0;
            }
        }
        $delivery_avg_overall = ( $delivery_total_count > 0 ) ? round( $delivery_total_minutes / $delivery_total_count ) : 0;
        $delivery_ontime_pct = ( $delivery_ontime_total > 0 ) ? round( ( $delivery_ontime_count / $delivery_ontime_total ) * 100, 2 ) : 0;

        $avg = ( $count > 0 ) ? ( $total / $count ) : 0.0;
        $currency = \MydPro\Includes\Store_Data::get_store_data( 'currency_simbol' ) ?: 'R$';

        $labels = array_map( function( $d ) { return date( 'd/m', strtotime( $d ) ); }, $days );
        $data = array_values( $daily );
        $counts = array_values( $daily_counts );

        return array(
            'total' => $total,
            'count' => $count,
            'avg' => $avg,
            'economy' => $economy,
            'currency' => $currency,
            'labels' => $labels,
            'data' => $data,
            'counts' => $counts,
            'days' => $days,
            'channels' => $channel_counts,
            'payments' => $payment_totals,
            'delivery_times' => $delivery_avg_by_weekday,
            'delivery_avg_overall' => $delivery_avg_overall,
            'delivery_ontime_pct' => $delivery_ontime_pct,
        );
    }
    public static function ajax_filter() {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        if ( ! is_user_logged_in() || ( ! current_user_can( 'edit_posts' ) && ! in_array( 'marketing', $roles, true ) ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }

        $start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
        $end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';

        if ( empty( $start_date ) || empty( $end_date ) ) {
            wp_send_json_error( array( 'message' => 'Invalid dates' ), 400 );
        }

        $data = self::get_dashboard_data( $start_date, $end_date );
        
        // Format values for display
        $data['total_formatted'] = $data['currency'] . ' ' . \MydPro\Includes\Myd_Store_Formatting::format_price( $data['total'] );
        $data['avg_formatted'] = $data['currency'] . ' ' . \MydPro\Includes\Myd_Store_Formatting::format_price( $data['avg'] );
        $data['economy_formatted'] = $data['currency'] . ' ' . \MydPro\Includes\Myd_Store_Formatting::format_price( $data['economy'] );
        $data['count_formatted'] = number_format_i18n( $data['count'] );

        // Also compute previous period totals to return percentual variation
        $start_ts = strtotime( $start_date );
        $end_ts = strtotime( $end_date );
        $num_days = ( (int) ( ( $end_ts - $start_ts ) / DAY_IN_SECONDS ) ) + 1;
        $prev_end_ts = strtotime( '-1 day', $start_ts );
        $prev_start_ts = strtotime( '-' . ( $num_days - 1 ) . ' days', $prev_end_ts );
        $prev_data = self::get_dashboard_data( date( 'Y-m-d', $prev_start_ts ), date( 'Y-m-d', $prev_end_ts ) );
        // total
        $prev_total = $prev_data['total'];
        $delta_value = $data['total'] - $prev_total;
        if ( $prev_total == 0 ) {
            $delta_total_pct = $data['total'] == 0 ? 0 : 100;
        } else {
            $delta_total_pct = ( $delta_value / $prev_total ) * 100;
        }
        $delta_total_direction = $delta_total_pct > 0 ? 'up' : ( $delta_total_pct < 0 ? 'down' : 'neutral' );
        $data['delta_total_formatted'] = ( $delta_total_pct > 0 ? '+' : '' ) . number_format_i18n( $delta_total_pct, 2 ) . '%';
        $data['delta_total_direction'] = $delta_total_direction;

        // avg
        $prev_avg = $prev_data['avg'];
        if ( $prev_avg == 0 ) {
            $delta_avg_pct = $data['avg'] == 0 ? 0 : 100;
        } else {
            $delta_avg_pct = ( ( $data['avg'] - $prev_avg ) / $prev_avg ) * 100;
        }
        $data['delta_avg_formatted'] = ( $delta_avg_pct > 0 ? '+' : '' ) . number_format_i18n( $delta_avg_pct, 2 ) . '%';
        $data['delta_avg_direction'] = $delta_avg_pct > 0 ? 'up' : ( $delta_avg_pct < 0 ? 'down' : 'neutral' );

        // count
        $prev_count = $prev_data['count'];
        if ( $prev_count == 0 ) {
            $delta_count_pct = $data['count'] == 0 ? 0 : 100;
        } else {
            $delta_count_pct = ( ( $data['count'] - $prev_count ) / $prev_count ) * 100;
        }
        $data['delta_count_formatted'] = ( $delta_count_pct > 0 ? '+' : '' ) . number_format_i18n( $delta_count_pct, 2 ) . '%';
        $data['delta_count_direction'] = $delta_count_pct > 0 ? 'up' : ( $delta_count_pct < 0 ? 'down' : 'neutral' );

        // economy
        $prev_economy = $prev_data['economy'];
        if ( $prev_economy == 0 ) {
            $delta_economy_pct = $data['economy'] == 0 ? 0 : 100;
        } else {
            $delta_economy_pct = ( ( $data['economy'] - $prev_economy ) / $prev_economy ) * 100;
        }
        $data['delta_economy_formatted'] = ( $delta_economy_pct > 0 ? '+' : '' ) . number_format_i18n( $delta_economy_pct, 2 ) . '%';
        $data['delta_economy_direction'] = $delta_economy_pct > 0 ? 'up' : ( $delta_economy_pct < 0 ? 'down' : 'neutral' );

        // include previous period values for chart comparison
            $data['prev_values'] = $prev_data['data'];
            // include raw dates arrays so client can re-aggregate (e.g. weekday grouping)
            $data['days'] = isset($data['days']) ? $data['days'] : array();
            $data['prev_days'] = isset($prev_data['days']) ? $prev_data['days'] : array();
            // include daily counts for tooltip (and previous period counts)
            $data['counts'] = isset($data['counts']) ? $data['counts'] : array();
            $data['prev_counts'] = isset($prev_data['counts']) ? $prev_data['counts'] : array();
        // include a localized label for previous period
        $data['prev_label'] = sprintf( __( 'comparação com os %d dias anteriores', 'myd-delivery-pro' ), $num_days );
        $data['num_days'] = $num_days;

        // If client requested hourly aggregation, compute hourly buckets for current and previous periods
        $view = isset( $_POST['view'] ) ? sanitize_text_field( wp_unslash( $_POST['view'] ) ) : '';
        if ( $view === 'hour_by_hour' ) {
            // prepare buckets
            $hours = array_fill( 0, 24, 0.0 );
            $prev_hours = array_fill( 0, 24, 0.0 );

            $args = array(
                'post_type' => 'mydelivery-orders',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'date_query' => array(
                    array(
                        'after' => date( 'Y-m-d', $start_ts ),
                        'before' => date( 'Y-m-d', $end_ts ),
                        'inclusive' => true,
                    ),
                ),
            );
            $q = new \WP_Query( $args );
            if ( $q->have_posts() ) {
                foreach ( $q->posts as $p ) {
                    $post_id = is_object( $p ) ? $p->ID : (int) $p['ID'];
                    // only include finished orders
                    if ( ! self::is_order_finished( $post_id ) ) { continue; }
                    $order_total = get_post_meta( $post_id, 'order_total', true );
                    if ( $order_total === '' || $order_total === null ) {
                        $order_total = get_post_meta( $post_id, 'myd_order_total', true );
                    }
                    if ( $order_total === '' || $order_total === null ) {
                        $order_total = get_post_meta( $post_id, 'fdm_order_total', true );
                    }
                    $order_total = floatval( str_replace( ',', '.', (string) $order_total ) );
                    $hour = (int) date( 'G', strtotime( is_object( $p ) ? $p->post_date : $p['post_date'] ) );
                    if ( $hour < 0 || $hour > 23 ) { $hour = 0; }
                    $hours[ $hour ] += $order_total;
                }
            }

            // previous period
            $args_prev = array(
                'post_type' => 'mydelivery-orders',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'date_query' => array(
                    array(
                        'after' => date( 'Y-m-d', $prev_start_ts ),
                        'before' => date( 'Y-m-d', $prev_end_ts ),
                        'inclusive' => true,
                    ),
                ),
            );
            $q2 = new \WP_Query( $args_prev );
            if ( $q2->have_posts() ) {
                foreach ( $q2->posts as $p ) {
                    $post_id = is_object( $p ) ? $p->ID : (int) $p['ID'];
                    // only include finished orders
                    if ( ! self::is_order_finished( $post_id ) ) { continue; }
                    $order_total = get_post_meta( $post_id, 'order_total', true );
                    if ( $order_total === '' || $order_total === null ) {
                        $order_total = get_post_meta( $post_id, 'myd_order_total', true );
                    }
                    if ( $order_total === '' || $order_total === null ) {
                        $order_total = get_post_meta( $post_id, 'fdm_order_total', true );
                    }
                    $order_total = floatval( str_replace( ',', '.', (string) $order_total ) );
                    $hour = (int) date( 'G', strtotime( is_object( $p ) ? $p->post_date : $p['post_date'] ) );
                    if ( $hour < 0 || $hour > 23 ) { $hour = 0; }
                    $prev_hours[ $hour ] += $order_total;
                }
            }

            $hour_labels = array();
            for ( $h = 0; $h < 24; $h++ ) {
                $hour_labels[] = sprintf( '%02dh', $h );
            }

            $data['labels'] = $hour_labels;
            $data['data'] = array_values( $hours );
            $data['prev_values'] = array_values( $prev_hours );
        }
        $data['num_days'] = $num_days;

        wp_send_json_success( $data );
    }

    /**
     * AJAX: envia código de verificação por e-mail para o usuário atual
     */
    public static function ajax_send_reset_code() {
        check_ajax_referer( 'myd-settings', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        $user = wp_get_current_user();
        $email = $user->user_email;
        if ( empty( $email ) ) {
            wp_send_json_error( array( 'message' => 'No email' ), 400 );
        }
        $code = wp_rand( 100000, 999999 ); // 6-digit
        update_user_meta( $user->ID, 'myd_reset_code', (string) $code );
        update_user_meta( $user->ID, 'myd_reset_code_expires', time() + 15 * MINUTE_IN_SECONDS );

        $store_name = get_option('fdm-business-name');
        $subject = 'Código de verificação - ' . ($store_name ? $store_name : 'Delivery');
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
    <title>Código de verificação</title>
</head>
<body class="email-bg" style="margin:0;padding:0;background:#f3f5f7;">
    <div style="display:none;overflow:hidden;line-height:1px;opacity:0;max-height:0;max-width:0;">
        Seu código de verificação. Válido por 15 minutos.
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
                                Este é o código de verificação para suas configurações:
                            </p>
                            <div class="code" style="margin:0 0 16px 0;padding:16px 20px;border:1px dashed #d2d6dd;border-radius:10px;background:#f7f9fc;text-align:center;">
                                <span style="font-family:Consolas,\'Courier New\',Courier,monospace;font-size:28px;letter-spacing:2px;font-weight:700;color:#111827;display:inline-block;">
                                    '.$code_fmt.'
                                </span>
                            </div>
                            <p class="muted" style="margin:0 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#6b7280;">
                                Este código é válido por 15 minutos.
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
            wp_send_json_success(['message' => 'Código enviado para seu e-mail.', 'email' => $email]);
        } else {
            wp_send_json_error(['message' => 'Falha ao enviar o e-mail.']);
        }
    }

    /**
     * AJAX: verifica código de verificação fornecido
     */
    public static function ajax_verify_reset_code() {
        check_ajax_referer( 'myd-settings', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        $user = wp_get_current_user();
        $code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
        $stored = get_user_meta( $user->ID, 'myd_reset_code', true );
        $expires = intval( get_user_meta( $user->ID, 'myd_reset_code_expires', true ) );
        if ( empty( $stored ) || $stored !== $code ) {
            wp_send_json_error( array( 'message' => 'Código inválido' ), 400 );
        }
        if ( $expires && time() > $expires ) {
            wp_send_json_error( array( 'message' => 'Código expirado' ), 400 );
        }
        // Mark as verified (short-lived)
        update_user_meta( $user->ID, 'myd_reset_code_verified', time() + 5 * MINUTE_IN_SECONDS );
        wp_send_json_success();
    }

    /**
     * AJAX: atualiza a senha do usuário após verificação do código
     */
    public static function ajax_update_password() {
        check_ajax_referer( 'myd-settings', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        $user = wp_get_current_user();
        $code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
        $new_pass = isset( $_POST['new_password'] ) ? $_POST['new_password'] : '';
        if ( ! $new_pass || strlen( $new_pass ) < 6 ) {
            wp_send_json_error( array( 'message' => 'Senha inválida (mínimo 6 caracteres)' ), 400 );
        }
        $stored = get_user_meta( $user->ID, 'myd_reset_code', true );
        $expires = intval( get_user_meta( $user->ID, 'myd_reset_code_expires', true ) );
        if ( empty( $stored ) || $stored !== $code ) {
            wp_send_json_error( array( 'message' => 'Código inválido' ), 400 );
        }
        if ( $expires && time() > $expires ) {
            wp_send_json_error( array( 'message' => 'Código expirado' ), 400 );
        }
        // All good: update password
        wp_set_password( $new_pass, $user->ID );
        // clear reset meta
        delete_user_meta( $user->ID, 'myd_reset_code' );
        delete_user_meta( $user->ID, 'myd_reset_code_expires' );
        delete_user_meta( $user->ID, 'myd_reset_code_verified' );
        wp_send_json_success();
    }

    /**
     * AJAX: atualiza avatar do usuário (upload de imagem)
     */
    public static function ajax_update_avatar() {
        check_ajax_referer( 'myd-settings', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        $user = wp_get_current_user();
        if ( empty( $_FILES['avatar'] ) || ! isset( $_FILES['avatar'] ) ) {
            wp_send_json_error( array( 'message' => 'Arquivo não informado' ), 400 );
        }
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $file = $_FILES['avatar'];
        $overrides = array( 'test_form' => false, 'mimes' => array( 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png' ) );
        $move = wp_handle_upload( $file, $overrides );
        if ( isset( $move['error'] ) ) {
            wp_send_json_error( array( 'message' => $move['error'] ), 500 );
        }
        $url = $move['url'];
        update_user_meta( $user->ID, 'myd_user_avatar', $url );
        wp_send_json_success( array( 'url' => $url ) );
    }

    public static function render() {
        if ( ! is_user_logged_in() ) {
            $redirect_to = home_url( '/dashboard/' );
            $login_url = wp_login_url( $redirect_to );
            if ( ! headers_sent() ) {
                wp_redirect( $login_url );
                exit;
            } else {
                echo '<script>window.location.href = "' . esc_url( $login_url ) . '";</script>';
                return '';
            }
        }
        // ...existing code...
        // Permitir redirecionamento para /dashboard ou /dashboard/ após login
        add_filter( 'login_redirect', function( $redirect_to, $request, $user ) {
            if ( strpos( $redirect_to, '/dashboard' ) !== false ) {
                return $redirect_to;
            }
            return $redirect_to;
        }, 10, 3 );
        // Permitir acesso para quem tem a role "marketing" também
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        if ( ! current_user_can( 'edit_posts' ) && ! in_array( 'marketing', $roles, true ) ) {
            return '<div class="myd-dashboard-unauth">' . __( 'Desculpe, você não tem acesso a essa página.', 'myd-delivery-pro' ) . '</div>';
        }

        if ( function_exists( 'wp_enqueue_script' ) ) {
            \wp_enqueue_script( 'myd-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true );
        }
        // Enqueue dashboard styles
        if ( function_exists( 'wp_enqueue_style' ) ) {
            \wp_enqueue_style( 'myd-dashboard-css', MYD_PLUGN_URL . 'assets/css/myd-dashboard.css', array(), null );
        }
        // flatpickr datepicker (for consistent calendar while inputs have no type)
        if ( function_exists( 'wp_enqueue_style' ) ) {
            \wp_enqueue_style( 'myd-flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), null );
        }
        if ( function_exists( 'wp_enqueue_script' ) ) {
            \wp_enqueue_script( 'myd-flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js', array(), null, true );
            // pt locale for flatpickr
            \wp_enqueue_script( 'myd-flatpickr-l10n', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js', array( 'myd-flatpickr' ), null, true );
        }

        $end_ts = strtotime( '-1 day', current_time( 'timestamp' ) ); // ontem (última data disponível)
        $start_ts = strtotime( '-6 days', $end_ts );
        
        // Get initial data using the shared method
        $dashboard_data = self::get_dashboard_data( date( 'Y-m-d', $start_ts ), date( 'Y-m-d', $end_ts ) );
        $total = $dashboard_data['total'];
        $count = $dashboard_data['count'];
        $avg = $dashboard_data['avg'];
        $economy = $dashboard_data['economy'];
        $currency = $dashboard_data['currency'];
        $labels = $dashboard_data['labels'];
        $data = $dashboard_data['data'];

        // Determine human-readable period label and date range
        $default_start_fmt = date( 'd/m/Y', $start_ts );
        $default_end_fmt = date( 'd/m/Y', $end_ts );
        $period_name = __( 'Últ. 7 dias', 'myd-delivery-pro' );
        $period_dates = $default_start_fmt . ' — ' . $default_end_fmt;
        $qs_start = isset( $_GET['myd_dashboard_start'] ) ? sanitize_text_field( wp_unslash( $_GET['myd_dashboard_start'] ) ) : '';
        $qs_end   = isset( $_GET['myd_dashboard_end'] ) ? sanitize_text_field( wp_unslash( $_GET['myd_dashboard_end'] ) ) : '';
        if ( $qs_start || $qs_end ) {
            $start_dt = $qs_start ? date_create_from_format( 'Y-m-d', $qs_start ) : false;
            $end_dt   = $qs_end ? date_create_from_format( 'Y-m-d', $qs_end ) : false;
            $period_name = ''; // custom period, no name
            if ( $start_dt && $end_dt ) {
                $period_dates = $start_dt->format( 'd/m/Y' ) . ' — ' . $end_dt->format( 'd/m/Y' );
            } elseif ( $start_dt ) {
                $period_dates = sprintf( __( 'Desde %s', 'myd-delivery-pro' ), $start_dt->format( 'd/m/Y' ) );
            } elseif ( $end_dt ) {
                $period_dates = sprintf( __( 'Até %s', 'myd-delivery-pro' ), $end_dt->format( 'd/m/Y' ) );
            }
        }

        // Calculate previous period to compute percentual variation
        $num_days = ( (int) ( ( $end_ts - $start_ts ) / DAY_IN_SECONDS ) ) + 1;
        $prev_end_ts = strtotime( '-1 day', $start_ts );
        $prev_start_ts = strtotime( '-' . ( $num_days - 1 ) . ' days', $prev_end_ts );
        $prev_data = self::get_dashboard_data( date( 'Y-m-d', $prev_start_ts ), date( 'Y-m-d', $prev_end_ts ) );
        // localized label for previous period (used for JS legend if needed)
        $prev_label = sprintf( __( 'comparação com os %d dias anteriores', 'myd-delivery-pro' ), $num_days );

        // Total delta
        $prev_total = $prev_data['total'];
        $delta_value = $total - $prev_total;
        if ( $prev_total == 0 ) {
            $delta_total_pct = $total == 0 ? 0 : 100;
        } else {
            $delta_total_pct = ( $delta_value / $prev_total ) * 100;
        }
        $delta_total_direction = $delta_total_pct > 0 ? 'up' : ( $delta_total_pct < 0 ? 'down' : 'neutral' );
        $delta_total_formatted = ( $delta_total_pct > 0 ? '+' : '' ) . number_format_i18n( $delta_total_pct, 2 ) . '%';

        // Avg delta
        $prev_avg = $prev_data['avg'];
        if ( $prev_avg == 0 ) {
            $delta_avg_pct = $avg == 0 ? 0 : 100;
        } else {
            $delta_avg_pct = ( ( $avg - $prev_avg ) / $prev_avg ) * 100;
        }
        $delta_avg_direction = $delta_avg_pct > 0 ? 'up' : ( $delta_avg_pct < 0 ? 'down' : 'neutral' );
        $delta_avg_formatted = ( $delta_avg_pct > 0 ? '+' : '' ) . number_format_i18n( $delta_avg_pct, 2 ) . '%';

        // Count delta
        $prev_count = $prev_data['count'];
        if ( $prev_count == 0 ) {
            $delta_count_pct = $count == 0 ? 0 : 100;
        } else {
            $delta_count_pct = ( ( $count - $prev_count ) / $prev_count ) * 100;
        }
        $delta_count_direction = $delta_count_pct > 0 ? 'up' : ( $delta_count_pct < 0 ? 'down' : 'neutral' );
        $delta_count_formatted = ( $delta_count_pct > 0 ? '+' : '' ) . number_format_i18n( $delta_count_pct, 2 ) . '%';

        // Economy delta
        $prev_economy = $prev_data['economy'];
        if ( $prev_economy == 0 ) {
            $delta_economy_pct = $economy == 0 ? 0 : 100;
        } else {
            $delta_economy_pct = ( ( $economy - $prev_economy ) / $prev_economy ) * 100;
        }
        $delta_economy_direction = $delta_economy_pct > 0 ? 'up' : ( $delta_economy_pct < 0 ? 'down' : 'neutral' );
        $delta_economy_formatted = ( $delta_economy_pct > 0 ? '+' : '' ) . number_format_i18n( $delta_economy_pct, 2 ) . '%';

        ob_start();
        
        // Get current user name
        $current_user = wp_get_current_user();
        $user_name = trim( $current_user->first_name . ' ' . $current_user->last_name );
        if ( empty( $user_name ) ) {
            $user_name = $current_user->display_name;
        }
        $user_avatar = get_avatar_url( $current_user->ID, array( 'size' => 80 ) );
        ?>
        <!-- styles moved to assets/css/myd-dashboard.css -->
        <div class="myd-dashboard-container" style="display: flex; width: 100%; min-height: 100vh;">
            <div class="myd-sidebar-left">
                <div class="myd-sidebar-header">
                    <div class="myd-sidebar-logo">
                        <img src="<?php echo esc_url( $user_avatar ); ?>" alt="<?php echo esc_attr( $user_name ); ?>" class="myd-sidebar-avatar" />
                    </div>
                </div>
                <nav class="myd-sidebar-nav">
                    <a href="#myd-dashboard" class="myd-sidebar-item active" title="Dashboard">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" fill="#333333"/></svg>
                        <span class="myd-sidebar-label">Dashboard</span>
                    </a>
                </nav>
                <div class="myd-sidebar-footer">
                    <button type="button" class="myd-sidebar-item" id="myd-sidebar-refresh" title="Atualizar dados"  onclick="window.location.reload();">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" fill="#333333"/></svg>
                        <span class="myd-sidebar-label">Atualizar</span>
                    </button>
                    <button type="button" class="myd-sidebar-item" id="myd-sidebar-settings" title="Configurações" style="color:#fff;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19.14 12.94C19.18 12.64 19.2 12.33 19.2 12C19.2 11.68 19.18 11.36 19.13 11.06L21.16 9.48C21.34 9.34 21.39 9.07 21.28 8.87L19.36 5.55C19.24 5.33 18.99 5.26 18.77 5.33L16.38 6.29C15.88 5.91 15.35 5.59 14.76 5.35L14.4 2.81C14.36 2.57 14.16 2.4 13.92 2.4H10.08C9.84 2.4 9.65 2.57 9.61 2.81L9.25 5.35C8.66 5.59 8.12 5.92 7.63 6.29L5.24 5.33C5.02 5.25 4.77 5.33 4.65 5.55L2.74 8.87C2.62 9.08 2.66 9.34 2.86 9.48L4.89 11.06C4.84 11.36 4.8 11.69 4.8 12C4.8 12.31 4.82 12.64 4.87 12.94L2.84 14.52C2.66 14.66 2.61 14.93 2.72 15.13L4.64 18.45C4.76 18.67 5.01 18.74 5.23 18.67L7.62 17.71C8.12 18.09 8.65 18.41 9.24 18.65L9.6 21.19C9.65 21.43 9.84 21.6 10.08 21.6H13.92C14.16 21.6 14.36 21.43 14.39 21.19L14.75 18.65C15.34 18.41 15.88 18.09 16.37 17.71L18.76 18.67C18.98 18.75 19.23 18.67 19.35 18.45L21.27 15.13C21.39 14.91 21.34 14.66 21.15 14.52L19.14 12.94ZM12 15.6C10.02 15.6 8.4 13.98 8.4 12C8.4 10.02 10.02 8.4 12 8.4C13.98 8.4 15.6 10.02 15.6 12C15.6 13.98 13.98 15.6 12 15.6Z" fill="#333333"/></svg>
                        <span class="myd-sidebar-label">Configurações</span>
                    </button>
                </div>
                <button type="button" class="myd-sidebar-toggle" id="myd-sidebar-toggle" title="Expandir/Recolher">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <script>
            (function(){
                var toggleBtn = document.getElementById('myd-sidebar-toggle');
                var sidebar = document.querySelector('.myd-sidebar-left');
                if(toggleBtn && sidebar){
                    toggleBtn.addEventListener('click', function(){
                        sidebar.classList.toggle('expanded');
                    });
                }
            })();
            </script>
            <!-- Conteúdo principal do dashboard -->
            <div class="myd-dashboard-wrap" style="flex: 1; min-width: 0;">
                <div class="myd-dashboard-header">
                    <span><?php echo esc_html( sprintf( __( 'Bem vindo, %s!', 'myd-delivery-pro' ), $user_name) ); ?></span>
                    <p><?php echo esc_html__( 'Veja as informações do seu negócio', 'myd-delivery-pro' ); ?></p>
                </div>
                <div class="myd-dashboard-filter-box">
                <form method="post" id="myd-dashboard-filter" class="myd-dashboard-filter">
                    <div class="myd-period-caption">
                        <label><?php echo esc_html__( 'Período:', 'myd-delivery-pro' ); ?></label>
                    </div>
                    <div id="myd-period-compact" class="myd-period-compact">
                        <span id="myd-period-name" class="myd-period-name"<?php echo empty( $period_name ) ? ' style="display:none;"' : ''; ?>><?php echo esc_html( $period_name ); ?></span>
                        <span id="myd-period-dates" class="myd-period-dates"><?php echo esc_html( $period_dates ); ?></span>
                        <button type="button" id="myd-period-toggle" class="myd-period-toggle" aria-expanded="false" title="Selecionar período">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                <path d="M7 11H9V13H7V11Z" fill="#666"/>
                                <path d="M11 11H13V13H11V11Z" fill="#666"/>
                                <path d="M15 11H17V13H15V11Z" fill="#666"/>
                                <path d="M19 4H18V2H16V4H8V2H6V4H5C3.9 4 3 4.9 3 6V20C3 21.1 3.9 22 5 22H19C20.1 22 21 21.1 21 20V6C21 4.9 20.1 4 19 4ZM19 20H5V9H19V20Z" fill="#666"/>
                            </svg>
                        </button>
                        <div class="myd-period-popup">
                            <div id="myd-period-inputs" class="myd-period-inputs">
                                <div class="myd-period-popup-top">
                                    <div class="myd-period-row">
                                        <div class="myd-date-column">
                                            <label class="myd-date-label"><?php echo esc_html__( 'Data inicial', 'myd-delivery-pro' ); ?></label>
                                            <div class="myd-date-input-wrap">
                                                <input name="myd_dashboard_start" value="<?php echo esc_attr( date( 'Y-m-d', $start_ts ) ); ?>" />
                                                <button type="button" class="myd-date-icon" onclick="this.previousElementSibling.focus();" aria-hidden="true">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                                        <path d="M7 11H9V13H7V11Z" fill="#666"/>
                                                        <path d="M11 11H13V13H11V11Z" fill="#666"/>
                                                        <path d="M15 11H17V13H15V11Z" fill="#666"/>
                                                        <path d="M19 4H18V2H16V4H8V2H6V4H5C3.9 4 3 4.9 3 6V20C3 21.1 3.9 22 5 22H19C20.1 22 21 21.1 21 20V6C21 4.9 20.1 4 19 4ZM19 20H5V9H19V20Z" fill="#666"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="myd-date-column">
                                            <label class="myd-date-label"><?php echo esc_html__( 'Data final', 'myd-delivery-pro' ); ?></label>
                                            <div class="myd-date-input-wrap">
                                                <input name="myd_dashboard_end" value="<?php echo esc_attr( date( 'Y-m-d', $end_ts ) ); ?>" />
                                                <button type="button" class="myd-date-icon" onclick="this.previousElementSibling.focus();" aria-hidden="true">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                                                        <path d="M7 11H9V13H7V11Z" fill="#666"/>
                                                        <path d="M11 11H13V13H11V11Z" fill="#666"/>
                                                        <path d="M15 11H17V13H15V11Z" fill="#666"/>
                                                        <path d="M19 4H18V2H16V4H8V2H6V4H5C3.9 4 3 4.9 3 6V20C3 21.1 3.9 22 5 22H19C20.1 22 21 21.1 21 20V6C21 4.9 20.1 4 19 4ZM19 20H5V9H19V20Z" fill="#666"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="myd-date-error" class="myd-date-error" style="display:none;">
                                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M24 12C24 18.6274 18.6274 24 12 24C5.37258 24 0 18.6274 0 12C0 5.37258 5.37258 0 12 0C18.6274 0 24 5.37258 24 12ZM10.5 7.5V12C10.5 12.8284 11.1716 13.5 12 13.5C12.8284 13.5 13.5 12.8284 13.5 12V7.5C13.5 6.67157 12.8284 6 12 6C11.1716 6 10.5 6.67157 10.5 7.5ZM12 18C12.8284 18 13.5 17.3284 13.5 16.5C13.5 15.6716 12.8284 15 12 15C11.1716 15 10.5 15.6716 10.5 16.5C10.5 17.3284 11.1716 18 12 18Z" fill="#dc3545"></path></svg>
                                        <span id="myd-date-error-text"></span>
                                    </div>
                                    <div class="myd-period-presets">
                                        <a href="#" id="myd-period-yesterday"><?php echo esc_html__( 'Ontem', 'myd-delivery-pro' ); ?></a>
                                        <a href="#" id="myd-period-7days"><?php echo esc_html__( 'Últ. 7 dias', 'myd-delivery-pro' ); ?></a>
                                        <a href="#" id="myd-reset-period"><?php echo esc_html__( 'Últ. 30 dias', 'myd-delivery-pro' ); ?></a>
                                    </div>
                                </div>
                            </div>
                            <div class="myd-period-actions">
                                <button type="submit" class="button apply"><?php echo esc_html__( 'Aplicar', 'myd-delivery-pro' ); ?></button>
                            </div>
                        </div>
                    </div>
                </form>
                </div>
                <!-- ...restante do dashboard... -->
            <script>
            (function(){
                try{
                    var toggle = document.getElementById('myd-period-toggle');
                    var inputs = document.getElementById('myd-period-inputs');
                    var popup = document.querySelector('.myd-period-popup');
                    var periodName = document.getElementById('myd-period-name');
                    var periodDates = document.getElementById('myd-period-dates');
                    // create backdrop element
                    var backdrop = document.createElement('div');
                    backdrop.className = 'myd-popup-backdrop';
                    document.body.appendChild(backdrop);

                    if ( toggle && popup ) {
                        toggle.addEventListener('click', function(e){
                            e.preventDefault();
                            var open = popup.style.display === 'flex' || popup.style.display === 'block';
                            popup.style.display = open ? 'none' : 'flex';
                            backdrop.style.display = open ? 'none' : 'block';
                            toggle.setAttribute('aria-expanded', (!open).toString());
                        });
                        // close when clicking outside (but not inside flatpickr calendar)
                        document.addEventListener('click', function(e){
                            if(!popup || !toggle) return;
                            if(popup.style.display === 'none') return;
                            // check if click is inside flatpickr calendar
                            var flatpickrEl = e.target.closest('.flatpickr-calendar');
                            if(flatpickrEl) return; // don't close if clicking inside flatpickr
                            if(!popup.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)){
                                popup.style.display = 'none';
                                backdrop.style.display = 'none';
                                toggle.setAttribute('aria-expanded','false');
                            }
                        });
                        // also close when clicking on backdrop
                        backdrop.addEventListener('click', function(){
                            popup.style.display = 'none';
                            backdrop.style.display = 'none';
                            toggle.setAttribute('aria-expanded','false');
                        });

                        // update label immediately when submitting the form (so UX feels responsive)
                        var form = document.querySelector('.myd-dashboard-filter');
                        function fmt(d){
                            if(!d) return '';
                            var m = d.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                            return m ? (m[3] + '/' + m[2] + '/' + m[1]) : d;
                        }
                        if(form){
                            form.addEventListener('submit', function(){
                                try{
                                    var s = form.querySelector('input[name="myd_dashboard_start"]') ? form.querySelector('input[name="myd_dashboard_start"]').value : '';
                                    var e = form.querySelector('input[name="myd_dashboard_end"]') ? form.querySelector('input[name="myd_dashboard_end"]').value : '';
                                    if(periodDates){
                                        if(s && e){ periodDates.textContent = fmt(s) + ' — ' + fmt(e); }
                                        else if(s){ periodDates.textContent = 'Desde ' + fmt(s); }
                                        else if(e){ periodDates.textContent = 'Até ' + fmt(e); }
                                    }
                                }catch(ex){}
                            });
                        }
                        // initialize flatpickr on inputs (retry until library loads) with Portuguese locale
                        (function initFlatpickr(){
                            try{
                                if (typeof flatpickr === 'undefined'){
                                    return setTimeout(initFlatpickr, 200);
                                }
                                var opts = {
                                    dateFormat: 'Y-m-d',
                                    altInput: true,
                                    altFormat: 'd/m/Y',
                                    allowInput: true,
                                    disableMobile: true,
                                    monthSelectorType: 'static'
                                };
                                // attempt to use pt locale and override weekday shorthand to single letters
                                try{
                                    var l10n = (typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.pt) ? flatpickr.l10ns.pt : {};
                                    // clone to avoid mutating global
                                    var locale = Object.assign({}, l10n);
                                    locale.weekdays = {
                                        shorthand: ['D','S','T','Q','Q','S','S'],
                                        longhand: ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado']
                                    };
                                    opts.locale = locale;
                                }catch(e){
                                    opts.locale = 'pt';
                                }
                                var startInput = document.querySelector('input[name="myd_dashboard_start"]');
                                var endInput = document.querySelector('input[name="myd_dashboard_end"]');
                                var errorEl = document.getElementById('myd-date-error');
                                var applyBtn = document.querySelector('.myd-period-actions button.apply');
                                var fpStart, fpEnd;

                                // parse date from dd/mm/yyyy format (altInput) or yyyy-mm-dd (hidden input)
                                function parseDate(str){
                                    if(!str) return null;
                                    // try dd/mm/yyyy
                                    var parts = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
                                    if(parts){
                                        return new Date(parts[3], parts[2]-1, parts[1]);
                                    }
                                    // try yyyy-mm-dd
                                    parts = str.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
                                    if(parts){
                                        return new Date(parts[1], parts[2]-1, parts[3]);
                                    }
                                    return null;
                                }

                                // error messages
                                var errorMessages = {
                                    emptyStart: '<?php echo esc_js( __( 'Preencha a data inicial para prosseguir', 'myd-delivery-pro' ) ); ?>',
                                    emptyEnd: '<?php echo esc_js( __( 'Preencha a data final para prosseguir', 'myd-delivery-pro' ) ); ?>',
                                    invalidRange: '<?php echo esc_js( __( 'A data final não pode ser anterior à data inicial', 'myd-delivery-pro' ) ); ?>'
                                };
                                var errorTextEl = document.getElementById('myd-date-error-text');

                                // show error with message
                                function showError(msg){
                                    if(errorTextEl) errorTextEl.textContent = msg;
                                    if(errorEl) errorEl.style.display = 'flex';
                                    if(applyBtn) applyBtn.disabled = true;
                                }

                                // hide error
                                function hideError(){
                                    if(errorEl) errorEl.style.display = 'none';
                                    if(applyBtn) applyBtn.disabled = false;
                                }

                                // validation function
                                function validateDateRange(){
                                    if(!errorEl) return true;
                                    // get values from altInput (visible) or hidden input
                                    var startAlt = startInput ? startInput._flatpickr ? startInput._flatpickr.altInput : null : null;
                                    var endAlt = endInput ? endInput._flatpickr ? endInput._flatpickr.altInput : null : null;
                                    
                                    var startStr = startAlt ? startAlt.value : (startInput ? startInput.value : '');
                                    var endStr = endAlt ? endAlt.value : (endInput ? endInput.value : '');
                                    
                                    // check empty start
                                    if(!startStr || !startStr.trim()){
                                        showError(errorMessages.emptyStart);
                                        return false;
                                    }
                                    // check empty end
                                    if(!endStr || !endStr.trim()){
                                        showError(errorMessages.emptyEnd);
                                        return false;
                                    }
                                    
                                    var startDate = parseDate(startStr);
                                    var endDate = parseDate(endStr);
                                    
                                    // check invalid range
                                    if(startDate && endDate && endDate < startDate){
                                        showError(errorMessages.invalidRange);
                                        return false;
                                    }
                                    hideError();
                                    return true;
                                }

                                if (startInput){
                                    var startOpts = Object.assign({}, opts, {
                                        onChange: function(selectedDates, dateStr){
                                            validateDateRange();
                                        }
                                    });
                                    fpStart = flatpickr(startInput, startOpts);
                                }
                                if (endInput){
                                    // data final não pode ser hoje nem futuro (máximo = ontem)
                                    var endOpts = Object.assign({}, opts);
                                    var yesterday = new Date();
                                    yesterday.setDate(yesterday.getDate() - 1);
                                    endOpts.maxDate = yesterday;
                                    endOpts.onChange = function(){
                                        validateDateRange();
                                    };
                                    fpEnd = flatpickr(endInput, endOpts);
                                }

                                // validate on form submit and do AJAX
                                var form = document.getElementById('myd-dashboard-filter');
                                var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
                                if(form){
                                    form.addEventListener('submit', function(e){
                                        e.preventDefault();
                                        if(!validateDateRange()){
                                            return;
                                        }
                                        
                                        var startVal = startInput ? startInput.value : '';
                                        var endVal = endInput ? endInput.value : '';
                                        
                                        // Show loading state
                                        // Close popup immediately
                                        var popup = document.querySelector('.myd-period-popup');
                                        var backdrop = document.querySelector('.myd-popup-backdrop');
                                        var toggle = document.getElementById('myd-period-toggle');
                                        if(popup) popup.style.display = 'none';
                                        if(backdrop) backdrop.style.display = 'none';
                                        if(toggle) toggle.setAttribute('aria-expanded', 'false');
                                        
                                        // Update period dates immediately
                                        var periodDatesEl = document.getElementById('myd-period-dates');
                                        var periodNameEl = document.getElementById('myd-period-name');
                                        if(periodDatesEl){
                                            var startFmt = startVal.replace(/(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1');
                                            var endFmt = endVal.replace(/(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1');
                                            periodDatesEl.textContent = startFmt + ' — ' + endFmt;
                                        }
                                        if(periodNameEl){
                                            periodNameEl.textContent = 'Personalizado';
                                        }
                                        
                                        // Show skeleton loading
                                        var cardTotal = document.getElementById('myd-card-total');
                                        var cardAvg = document.getElementById('myd-card-avg');
                                        var cardCount = document.getElementById('myd-card-count');
                                        var cardEconomy = document.getElementById('myd-card-economy');
                                        var chartWrap = document.querySelector('.myd-chart-wrap');
                                        if(cardTotal) cardTotal.classList.add('myd-skeleton');
                                        if(cardAvg) cardAvg.classList.add('myd-skeleton');
                                        if(cardCount) cardCount.classList.add('myd-skeleton');
                                        if(cardEconomy) cardEconomy.classList.add('myd-skeleton');
                                        if(chartWrap) chartWrap.classList.add('myd-skeleton');
                                        
                                        // Make AJAX request
                                        var savedActiveTab = document.querySelector('#myd-chart-tabs .myd-chart-tab--active');
                                        var savedActiveView = savedActiveTab ? savedActiveTab.getAttribute('data-view') : null;
                                        var formData = new FormData();
                                        formData.append('action', 'myd_dashboard_filter');
                                        formData.append('start_date', startVal);
                                        formData.append('end_date', endVal);

                                        fetch(ajaxUrl, {
                                            method: 'POST',
                                            body: formData,
                                            credentials: 'same-origin'
                                        })
                                        .then(function(response){ return response.json(); })
                                        .then(function(result){
                                            if(result.success && result.data){
                                                // Update dashboard
                                                if(typeof window.mydUpdateDashboard === 'function'){
                                                    window.mydUpdateDashboard(result.data);
                                                    // restore user's selected tab/view
                                                    try{
                                                        if(savedActiveView) {
                                                            // remove active class from all tabs
                                                            var tabs = document.querySelectorAll('#myd-chart-tabs .myd-chart-tab');
                                                            tabs.forEach(function(t){ t.classList.remove('myd-chart-tab--active'); });
                                                            // find matching tab and activate
                                                            var newTab = document.querySelector('#myd-chart-tabs .myd-chart-tab[data-view="' + savedActiveView + '"]');
                                                            if(newTab) {
                                                                newTab.classList.add('myd-chart-tab--active');
                                                            }
                                                            // re-render view
                                                            switchChartView(savedActiveView);
                                                        }
                                                    }catch(e){}
                                                }
                                            }
                                        })
                                        .catch(function(error){
                                            console.error('Dashboard filter error:', error);
                                        })
                                        .finally(function(){
                                            // Remove skeleton loading
                                            if(cardTotal) cardTotal.classList.remove('myd-skeleton');
                                            if(cardAvg) cardAvg.classList.remove('myd-skeleton');
                                            if(cardCount) cardCount.classList.remove('myd-skeleton');
                                            if(cardEconomy) cardEconomy.classList.remove('myd-skeleton');
                                            if(chartWrap) chartWrap.classList.remove('myd-skeleton');
                                        });
                                    });
                                }

                                // validate on typing (input event) - flatpickr creates altInput, so listen on both
                                var allInputs = document.querySelectorAll('.myd-date-column input');
                                allInputs.forEach(function(inp){
                                    inp.addEventListener('input', validateDateRange);
                                    inp.addEventListener('keyup', validateDateRange);
                                    inp.addEventListener('change', validateDateRange);
                                });

                                // Helper function to format date as YYYY-MM-DD
                                var formatDate = function(d){
                                    var y = d.getFullYear();
                                    var m = String(d.getMonth() + 1).padStart(2, '0');
                                    var day = String(d.getDate()).padStart(2, '0');
                                    return y + '-' + m + '-' + day;
                                };
                                
                                // Helper function to apply preset period
                                function applyPresetPeriod(startVal, endVal, periodLabel){
                                    // Update flatpickr inputs
                                    if(fpStart) fpStart.setDate(startVal, true);
                                    if(fpEnd) fpEnd.setDate(endVal, true);
                                    
                                    // Close popup immediately
                                    var popup = document.querySelector('.myd-period-popup');
                                    var backdrop = document.querySelector('.myd-popup-backdrop');
                                    var toggle = document.getElementById('myd-period-toggle');
                                    if(popup) popup.style.display = 'none';
                                    if(backdrop) backdrop.style.display = 'none';
                                    if(toggle) toggle.setAttribute('aria-expanded', 'false');
                                    
                                    // Update period labels immediately
                                    var periodNameEl = document.getElementById('myd-period-name');
                                    var periodDatesEl = document.getElementById('myd-period-dates');
                                    if(periodNameEl){
                                        periodNameEl.textContent = periodLabel;
                                    }
                                    if(periodDatesEl){
                                        var startFmt = startVal.replace(/(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1');
                                        var endFmt = endVal.replace(/(\d{4})-(\d{2})-(\d{2})/, '$3/$2/$1');
                                        periodDatesEl.textContent = startFmt + ' — ' + endFmt;
                                    }
                                    
                                    // Show skeleton loading
                                    var cardTotal = document.getElementById('myd-card-total');
                                    var cardAvg = document.getElementById('myd-card-avg');
                                    var cardCount = document.getElementById('myd-card-count');
                                    var cardEconomy = document.getElementById('myd-card-economy');
                                    var chartWrap = document.querySelector('.myd-chart-wrap');
                                    if(cardTotal) cardTotal.classList.add('myd-skeleton');
                                    if(cardAvg) cardAvg.classList.add('myd-skeleton');
                                    if(cardCount) cardCount.classList.add('myd-skeleton');
                                    if(cardEconomy) cardEconomy.classList.add('myd-skeleton');
                                    if(chartWrap) chartWrap.classList.add('myd-skeleton');
                                    
                                    // Make AJAX request
                                    var savedActiveTab = document.querySelector('#myd-chart-tabs .myd-chart-tab--active');
                                    var savedActiveView = savedActiveTab ? savedActiveTab.getAttribute('data-view') : null;
                                    var formData = new FormData();
                                    formData.append('action', 'myd_dashboard_filter');
                                    formData.append('start_date', startVal);
                                    formData.append('end_date', endVal);

                                    fetch(ajaxUrl, {
                                        method: 'POST',
                                        credentials: 'same-origin',
                                        body: formData
                                    })
                                    .then(function(response){ return response.json(); })
                                    .then(function(data){
                                        if(data.success){
                                            mydUpdateDashboard(data.data);
                                            try{
                                                if(savedActiveView) {
                                                    var tabs = document.querySelectorAll('#myd-chart-tabs .myd-chart-tab');
                                                    tabs.forEach(function(t){ t.classList.remove('myd-chart-tab--active'); });
                                                    var newTab = document.querySelector('#myd-chart-tabs .myd-chart-tab[data-view="' + savedActiveView + '"]');
                                                    if(newTab) newTab.classList.add('myd-chart-tab--active');
                                                    switchChartView(savedActiveView);
                                                }
                                            }catch(e){}
                                        }
                                        // Remove skeleton loading
                                        if(cardTotal) cardTotal.classList.remove('myd-skeleton');
                                        if(cardAvg) cardAvg.classList.remove('myd-skeleton');
                                        if(cardCount) cardCount.classList.remove('myd-skeleton');
                                        if(cardEconomy) cardEconomy.classList.remove('myd-skeleton');
                                        if(chartWrap) chartWrap.classList.remove('myd-skeleton');
                                    })
                                    .catch(function(){
                                        // Remove skeleton loading on error
                                        if(cardTotal) cardTotal.classList.remove('myd-skeleton');
                                        if(cardAvg) cardAvg.classList.remove('myd-skeleton');
                                        if(cardCount) cardCount.classList.remove('myd-skeleton');
                                        if(cardEconomy) cardEconomy.classList.remove('myd-skeleton');
                                        if(chartWrap) chartWrap.classList.remove('myd-skeleton');
                                    });
                                }
                                
                                // Yesterday preset
                                var yesterdayLink = document.getElementById('myd-period-yesterday');
                                if(yesterdayLink){
                                    yesterdayLink.addEventListener('click', function(e){
                                        e.preventDefault();
                                        var today = new Date();
                                        var yesterday = new Date(today);
                                        yesterday.setDate(yesterday.getDate() - 1);
                                        var dateVal = formatDate(yesterday);
                                        applyPresetPeriod(dateVal, dateVal, '<?php echo esc_js( __( 'Ontem', 'myd-delivery-pro' ) ); ?>');
                                    });
                                }
                                
                                // Last 7 days preset
                                var days7Link = document.getElementById('myd-period-7days');
                                if(days7Link){
                                    days7Link.addEventListener('click', function(e){
                                        e.preventDefault();
                                        var today = new Date();
                                        var yesterday = new Date(today);
                                        yesterday.setDate(yesterday.getDate() - 1);
                                        var startDate = new Date(yesterday);
                                        startDate.setDate(startDate.getDate() - 6);
                                        applyPresetPeriod(formatDate(startDate), formatDate(yesterday), '<?php echo esc_js( __( 'Últ. 7 dias', 'myd-delivery-pro' ) ); ?>');
                                    });
                                }
                                
                                // Last 30 days preset
                                var resetLink = document.getElementById('myd-reset-period');
                                if(resetLink){
                                    resetLink.addEventListener('click', function(e){
                                        e.preventDefault();
                                        var today = new Date();
                                        var yesterday = new Date(today);
                                        yesterday.setDate(yesterday.getDate() - 1);
                                        var startDate = new Date(yesterday);
                                        startDate.setDate(startDate.getDate() - 29);
                                        applyPresetPeriod(formatDate(startDate), formatDate(yesterday), '<?php echo esc_js( __( 'Últ. 30 dias', 'myd-delivery-pro' ) ); ?>');
                                    });
                                }
                            }catch(e){
                                setTimeout(initFlatpickr, 400);
                            }
                        })();
                    }
                }catch(e){}
            })();
            </script>

                                <!-- Nova seção: Análise de pedidos (quantidade) -->
                                <div class="myd-dashboard-orders">
                                    <div class="myd-section-header" style="display:flex;align-items:center;margin-bottom:12px;">
                                        <div class="myd-dashboard-title-icon" style="display:flex;align-items:center; font-size:20px;">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <rect x="5" y="4" width="14" height="17" rx="2" stroke="#33363F" stroke-width="2"></rect> <path d="M9 9H15" stroke="#33363F" stroke-width="2" stroke-linecap="round"></path> <path d="M9 13H15" stroke="#33363F" stroke-width="2" stroke-linecap="round"></path> <path d="M9 17H13" stroke="#33363F" stroke-width="2" stroke-linecap="round"></path> </g></svg>
                                        </div>
                                        <h2 class="myd-dashboard-title" style="margin:0;font-size:18px;font-weight:600;color:#111;">Análise de pedidos</h2>
                                    </div>
                                    <div class="myd-dashboard-cards">
                                        <div class="myd-dashboard-card myd-card-with-icon">
                                            <div class="myd-card-icon">
                                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 5L19 12H7.37671M20 16H8L6 3H3M11 6L13 8L17 4M9 20C9 20.5523 8.55228 21 8 21C7.44772 21 7 20.5523 7 20C7 19.4477 7.44772 19 8 19C8.55228 19 9 19.4477 9 20ZM20 20C20 20.5523 19.5523 21 19 21C18.4477 21 18 20.5523 18 20C18 19.4477 18.4477 19 19 19C19.5523 19 20 19.4477 20 20Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                            </div>
                                            <div class="myd-card-content">
                                                <div class="title"><?php echo esc_html__( 'Total de pedidos', 'myd-delivery-pro' ); ?></div>
                                                <div class="value" id="myd-orders-card-count">
                                                    <span class="myd-card-price" id="myd-orders-card-count-value"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
                                                    <span class="myd-card-delta myd-card-delta--<?php echo esc_attr( $delta_count_direction ); ?>" id="myd-orders-card-count-delta">
                                                        <?php if ( $delta_count_direction === 'up' ) : ?>
                                                            <span class="myd-delta-arrow">▲</span>
                                                        <?php elseif ( $delta_count_direction === 'down' ) : ?>
                                                            <span class="myd-delta-arrow">▼</span>
                                                        <?php else : ?>
                                                            <span class="myd-delta-arrow">—</span>
                                                        <?php endif; ?>
                                                        <span class="myd-delta-value"><?php echo esc_html( $delta_count_formatted ); ?></span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="myd-chart-wrap">
                                        <canvas id="mydOrdersChart" height="160"></canvas>
                                    </div>
                                    <div class="myd-chart-legend" id="myd-orders-chart-legend" aria-hidden="false"></div>
                                </div>

                            <script>
                            (function(){
                                // Dados para o gráfico de pedidos - expostos globalmente para atualização via AJAX
                                window.mydOrderLabels = <?php echo wp_json_encode( $labels ); ?>;
                                window.mydOrderCounts = <?php echo wp_json_encode( $dashboard_data['counts'] ); ?>;
                                window.mydPrevOrderCounts = <?php echo wp_json_encode( $prev_data['counts'] ); ?>;
                                window.mydOrderRawDates = <?php echo wp_json_encode( $dashboard_data['days'] ); ?>;
                                window.mydPrevOrderDates = <?php echo wp_json_encode( $prev_data['days'] ); ?>;
                                window.mydOrdersLegendNumDays = <?php echo wp_json_encode( $num_days ); ?>;
                                var mydOrdersChart = null;


                                function renderOrdersChart(chartLabels, chartCounts, prevCounts){
                                    var el = document.getElementById('mydOrdersChart');
                                    if(!el) return;
                                    var ctx = el.getContext('2d');
                                    if(typeof Chart === 'undefined'){
                                        return setTimeout(function(){ renderOrdersChart(chartLabels, chartCounts, prevCounts); }, 250);
                                    }
                                    var singlePointMode = false;
                                    var paddedLabels = chartLabels;
                                    var paddedCounts = chartCounts;
                                    var paddedPrev = prevCounts || [];
                                    if(Array.isArray(chartLabels) && chartLabels.length === 1){
                                        singlePointMode = true;
                                        paddedLabels = ['', chartLabels[0], ''];
                                        paddedCounts = [0, (chartCounts && chartCounts.length ? chartCounts[0] : 0), 0];
                                        if(prevCounts && prevCounts.length){
                                            paddedPrev = [0, prevCounts[0], 0];
                                        } else {
                                            paddedPrev = [0, 0, 0];
                                        }
                                    }
                                    if(mydOrdersChart){
                                        mydOrdersChart.destroy();
                                    }
                                    mydOrdersChart = new Chart(ctx, {
                                        type: 'line',
                                        data: {
                                            labels: paddedLabels,
                                            datasets: [
                                                {
                                                    label: '<?php echo esc_js( 'Quantidade de pedidos' ); ?>',
                                                    data: paddedCounts,
                                                    borderColor: '#ed972b',
                                                    backgroundColor: 'rgba(237,151,43,0.06)',
                                                    tension: 0,
                                                    pointRadius: 5,
                                                    radius: 3,
                                                    pointHoverRadius: 6,
                                                    hoverRadius: 6,
                                                    pointBackgroundColor: '#ed972b',
                                                    pointBorderColor: '#ffffff',
                                                    pointBorderWidth: 2,
                                                    fill: false,
                                                    borderWidth: 2,
                                                    order: 2
                                                },
                                                {
                                                    label: '<?php echo esc_js( __( 'Comparação período anterior', 'myd-delivery-pro' ) ); ?>',
                                                    data: paddedPrev || [],
                                                    borderColor: 'rgba(237,151,43,0.28)',
                                                    backgroundColor: 'rgba(237,151,43,0.06)',
                                                    tension: 0,
                                                    pointRadius: 3,
                                                    radius: 3,
                                                    pointHoverRadius: 0,
                                                    hoverRadius: 0,
                                                    pointBackgroundColor: 'rgba(237,151,43,0.28)',
                                                    fill: false,
                                                    borderWidth: 2,
                                                    order: 1
                                                }
                                            ]
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            interaction: {
                                                mode: 'nearest',
                                                axis: 'x',
                                                intersect: false
                                            },
                                            elements: {
                                                point: {
                                                    radius: 3,
                                                    hitRadius: 10,
                                                    hoverRadius: 0
                                                }
                                            },
                                            plugins: {
                                                legend: { display: false },
                                                tooltip: {
                                                    enabled: false,
                                                    external: function(context){
                                                        // --- Tooltip customizado igual ao de vendas ---
                                                        var tooltipEl = document.getElementById('myd-orders-chart-tooltip');
                                                        if(!tooltipEl){
                                                            tooltipEl = document.createElement('div');
                                                            tooltipEl.id = 'myd-orders-chart-tooltip';
                                                            tooltipEl.className = 'myd-chart-tooltip';
                                                            tooltipEl.style.pointerEvents = 'none';
                                                            tooltipEl.style.zIndex = 1000;
                                                            tooltipEl.style.opacity = 0;
                                                            tooltipEl.style.visibility = 'hidden';
                                                            tooltipEl.style.transition = 'opacity 120ms ease';
                                                            tooltipEl.style.position = 'absolute';
                                                            try{
                                                                var canvasParent = context.chart && context.chart.canvas ? context.chart.canvas.parentNode : null;
                                                                if(canvasParent){
                                                                    var cs = window.getComputedStyle(canvasParent);
                                                                    if(cs.position === 'static') canvasParent.style.position = 'relative';
                                                                    canvasParent.appendChild(tooltipEl);
                                                                } else {
                                                                    document.body.appendChild(tooltipEl);
                                                                }
                                                            }catch(e){
                                                                document.body.appendChild(tooltipEl);
                                                            }
                                                        }

                                                        var tooltipModel = context.tooltip;
                                                        if(tooltipModel.opacity === 0){
                                                            tooltipEl.style.opacity = 0;
                                                            tooltipEl.style.visibility = 'hidden';
                                                            return;
                                                        }

                                                        var chart = context.chart;
                                                        var point = null;
                                                        if(tooltipModel.dataPoints && tooltipModel.dataPoints.length){
                                                            for(var i=0;i<tooltipModel.dataPoints.length;i++){
                                                                if(tooltipModel.dataPoints[i].datasetIndex === 0){
                                                                    point = tooltipModel.dataPoints[i];
                                                                    break;
                                                                }
                                                            }
                                                            if(!point) point = tooltipModel.dataPoints[0];
                                                        }
                                                        if(!point){
                                                            tooltipEl.style.opacity = 0;
                                                            return;
                                                        }

                                                        var idx = point.dataIndex;
                                                        var mappedIdx = idx;
                                                        if(typeof window.mydOrderCounts !== 'undefined' && chart.data && chart.data.labels){
                                                            var labelsLen = chart.data.labels.length || 0;
                                                            var countsLen = window.mydOrderCounts.length || 0;
                                                            if(labelsLen > countsLen){
                                                                var offset = Math.round((labelsLen - countsLen) / 2);
                                                                mappedIdx = idx - offset;
                                                            }
                                                        }
                                                        var numericCurrent = (typeof window.mydOrderCounts !== 'undefined' && window.mydOrderCounts[mappedIdx] !== undefined) ? window.mydOrderCounts[mappedIdx] : 0;
                                                        var numericPrevious = (typeof window.mydPrevOrderCounts !== 'undefined' && window.mydPrevOrderCounts[mappedIdx] !== undefined) ? window.mydPrevOrderCounts[mappedIdx] : 0;
                                                        var pct = 0;
                                                        var dir = 'neutral';
                                                        if(numericPrevious == 0){
                                                            pct = numericCurrent == 0 ? 0 : 100;
                                                        } else {
                                                            pct = ((numericCurrent - numericPrevious)/numericPrevious) * 100;
                                                        }
                                                        if(pct > 0) dir = 'up'; else if(pct < 0) dir = 'down';

                                                        var currentCount = numericCurrent;
                                                        var previousCount = numericPrevious;

                                                        var label = chart.data.labels[idx] || '';
                                                        var prevLabelRaw = (typeof window.mydPrevOrderDates !== 'undefined' && window.mydPrevOrderDates[mappedIdx]) ? window.mydPrevOrderDates[mappedIdx] : '';
                                                        var prevLabel = prevLabelRaw;
                                                        if(prevLabelRaw){
                                                            var m = String(prevLabelRaw).match(/^(\d{4})-(\d{2})-(\d{2})/);
                                                            if(m){ prevLabel = m[3] + '/' + m[2] + '/' + m[1]; }
                                                        }

                                                        var html = '';
                                                        html += '<div class="myd-tooltip-top">Dia ' + label + '</div>';
                                                        html += '<div class="myd-tooltip-main">';
                                                        html += '<div class="myd-tooltip-count">' + currentCount + ' pedidos</div>';
                                                        var arrow = (dir === 'up') ? '▲ ' : (dir === 'down') ? '▼ ' : '— ';
                                                        html += '<div class="myd-tooltip-pct myd-pct--' + dir + '">' + arrow + (pct > 0 ? '+' : '') + pct.toFixed(2) + '%</div>';
                                                        html += '</div>';
                                                        html += '<div class="myd-tooltip-rel">em relação aos <strong>' + previousCount + ' pedidos</strong> do dia <strong>' + prevLabel + '</strong></div>';

                                                        tooltipEl.innerHTML = html;
                                                        tooltipEl.style.visibility = 'visible';
                                                        tooltipEl.style.opacity = 1;

                                                        var canvas = chart.canvas;
                                                        var canvasParent = canvas.parentNode || document.body;
                                                        var canvasRect = canvas.getBoundingClientRect();
                                                        var parentRect = canvasParent.getBoundingClientRect ? canvasParent.getBoundingClientRect() : { left: 0, top: 0 };
                                                        var caretXWithinCanvas = tooltipModel.caretX || 0;
                                                        var caretYWithinCanvas = tooltipModel.caretY || 0;
                                                        var canvasLeftInParent = canvasRect.left - parentRect.left;
                                                        var tooltipW = tooltipEl.offsetWidth || 160;
                                                        var sidePadding = 10;
                                                        var parentWidth = (canvasParent && canvasParent.clientWidth) ? canvasParent.clientWidth : window.innerWidth;
                                                        var minPadding = 8;
                                                        var maxLeft = parentWidth - tooltipW - minPadding;
                                                        var showRight = (caretXWithinCanvas < (canvasRect.width / 2));
                                                        var left;
                                                        if(showRight){
                                                            left = Math.round(canvasLeftInParent + caretXWithinCanvas + sidePadding);
                                                        } else {
                                                            left = Math.round(canvasLeftInParent + caretXWithinCanvas - tooltipW - sidePadding);
                                                        }
                                                        if(left < minPadding) left = minPadding;
                                                        if(left > maxLeft) left = maxLeft;
                                                        var canvasTopInParent = canvasRect.top - parentRect.top;
                                                        var desiredTop = Math.round(canvasTopInParent + Math.max(12, caretYWithinCanvas - (tooltipEl.offsetHeight / 2)));
                                                        var topFixed = desiredTop;
                                                        var parentHeight = (canvasParent && canvasParent.clientHeight) ? canvasParent.clientHeight : window.innerHeight;
                                                        var maxTop = parentHeight - tooltipEl.offsetHeight - minPadding;
                                                        if(topFixed < minPadding) topFixed = minPadding;
                                                        if(topFixed > maxTop) topFixed = maxTop;
                                                        tooltipEl.style.left = left + 'px';
                                                        tooltipEl.style.top = topFixed + 'px';
                                                        tooltipEl.style.transform = 'translateZ(0)';
                                                    }
                                                }
                                            },
                                            scales: {
                                                y: { beginAtZero: true },
                                                x: { ticks: { maxRotation: 0 } }
                                            }
                                        }
                                    });
                                    buildOrdersLegend(mydOrdersChart);

                                    // --- Custom hover handling: increase point size only for the main series (dataset 0) ---
                                    (function(){
                                        try{
                                            if(!mydOrdersChart) return;
                                            var canvasEl = el;
                                            var mainDs = mydOrdersChart.data.datasets[0];
                                            var baseRadii = Array.isArray(mainDs.pointRadius) ? mainDs.pointRadius.slice() : new Array(mydOrdersChart.data.datasets[0].data.length).fill(mainDs.pointRadius || mainDs.radius || 3);
                                            mydOrdersChart.data.datasets[0].pointRadiusArray = baseRadii;
                                            var hoverIdx = null;

                                            function resetHover(){
                                                if(hoverIdx === null) return;
                                                mydOrdersChart.data.datasets[0].pointRadius = mydOrdersChart.data.datasets[0].pointRadiusArray.slice();
                                                mydOrdersChart.update('none');
                                                hoverIdx = null;
                                            }

                                            canvasEl.addEventListener('mousemove', function(evt){
                                                try{
                                                    var points = mydOrdersChart.getElementsAtEventForMode(evt, 'nearest', {intersect:true}, false) || [];
                                                    var p = null;
                                                    for(var i=0;i<points.length;i++){
                                                        if(points[i].datasetIndex === 0){ p = points[i]; break; }
                                                    }
                                                    if(p){
                                                        try{ canvasEl.style.cursor = 'pointer'; }catch(e){}
                                                        var idx = p.index;
                                                        if(hoverIdx !== idx){
                                                            var arr = mydOrdersChart.data.datasets[0].pointRadiusArray.slice();
                                                            arr[idx] = 6;
                                                            mydOrdersChart.data.datasets[0].pointRadius = arr;
                                                            mydOrdersChart.update('none');
                                                            hoverIdx = idx;
                                                        }
                                                    } else {
                                                        try{ canvasEl.style.cursor = 'default'; }catch(e){}
                                                        resetHover();
                                                    }
                                                }catch(e){}
                                            });

                                            canvasEl.addEventListener('mouseleave', function(){ resetHover(); });
                                        }catch(e){}
                                    })();

                                    // --- Click handler: open side panel with orders for the clicked day ---
                                    (function(){
                                        try{
                                            var canvasEl = el;
                                            canvasEl.addEventListener('click', function(evt){
                                                try{
                                                    var pts = mydOrdersChart.getElementsAtEventForMode(evt, 'nearest', {intersect:true}, false) || [];
                                                    var p = null;
                                                    for(var i=0;i<pts.length;i++){
                                                        if(pts[i].datasetIndex === 0){ p = pts[i]; break; }
                                                    }
                                                    if(!p) return;
                                                    var idx = p.index;
                                                    var mappedIdx = idx;
                                                    if(mydOrdersChart.data && mydOrdersChart.data.labels){
                                                        var labelsLen = mydOrdersChart.data.labels.length || 0;
                                                        var countsLen = (window.mydOrderCounts && window.mydOrderCounts.length) ? window.mydOrderCounts.length : 0;
                                                        if(labelsLen > countsLen){
                                                            var offset = Math.round((labelsLen - countsLen) / 2);
                                                            mappedIdx = idx - offset;
                                                        }
                                                    }

                                                    var dayLabel = mydOrdersChart.data.labels[idx] || '';
                                                    var rawDate = (typeof window.mydOrderRawDates !== 'undefined' && window.mydOrderRawDates[mappedIdx]) ? window.mydOrderRawDates[mappedIdx] : '';
                                                    var displayDate = dayLabel || '';
                                                    if(rawDate){
                                                        var mm = String(rawDate).match(/^(\d{4})-(\d{2})-(\d{2})/);
                                                        if(mm) displayDate = mm[3] + '/' + mm[2] + '/' + mm[1];
                                                    }

                                                    // Use global openSidePanel function (defined in mydSalesChart script)
                                                    if(typeof window.mydOpenSidePanel === 'function'){
                                                        var loadingNode = document.createElement('div');
                                                        loadingNode.className = 'myd-side-panel-loading';
                                                        loadingNode.textContent = 'Carregando pedidos...';
                                                        window.mydOpenSidePanel('Pedidos concluídos', loadingNode);

                                                        // Store date for back navigation
                                                        if(typeof window.mydSidePanelDate !== 'undefined'){
                                                            window.mydSidePanelDate = rawDate || ('date:' + displayDate);
                                                        }

                                                        // AJAX request
                                                        var fd = new FormData();
                                                        fd.append('action', 'myd_get_orders_for_day');
                                                        fd.append('date', rawDate || ('date:' + displayDate));

                                                        fetch(window.mydAjaxUrl || '/wp-admin/admin-ajax.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                                                            .then(function(r){ return r.ok ? r.json() : Promise.reject(r); })
                                                            .then(function(res){
                                                                if(typeof window.mydRenderOrdersList === 'function'){
                                                                    window.mydRenderOrdersList(res);
                                                                }
                                                            })
                                                            .catch(function(e){
                                                                var body = document.getElementById('myd-side-panel-body');
                                                                if(body){
                                                                    var existing = body.querySelector('.myd-side-panel-loading');
                                                                    if(existing) existing.remove();
                                                                    var err = document.createElement('div');
                                                                    err.className = 'myd-no-orders';
                                                                    err.textContent = 'Erro ao carregar pedidos.';
                                                                    body.appendChild(err);
                                                                }
                                                            });
                                                    }
                                                }catch(e){
                                                    console.error('mydOrdersChart click error:', e);
                                                }
                                            });
                                        }catch(e){}
                                    })();
                                }

                                function buildOrdersLegend(chart){
                                    try{
                                        var container = document.getElementById('myd-orders-chart-legend');
                                        if(!container) return;
                                        container.innerHTML = '';
                                        if(!chart || !chart.data || !chart.data.datasets) return;
                                        chart.data.datasets.forEach(function(ds, idx){
                                            var item = document.createElement('div');
                                            item.className = 'myd-legend-item';
                                            var sw = document.createElement('span');
                                            sw.className = 'myd-legend-swatch';
                                            sw.style.background = ds.borderColor || ds.backgroundColor || '#ccc';
                                            var lbl = document.createElement('span');
                                            lbl.className = 'myd-legend-label';
                                            if(idx === 0) {
                                                lbl.innerHTML = 'Total de pedidos nos <strong>últimos ' + window.mydOrdersLegendNumDays + ' dias</strong>';
                                            } else if (idx === 1) {
                                                lbl.innerHTML = 'comparação com os <strong>' + window.mydOrdersLegendNumDays + ' dias anteriores</strong>';
                                            } else {
                                                lbl.textContent = ds.label || ('Série ' + (idx+1));
                                            }
                                            item.appendChild(sw);
                                            item.appendChild(lbl);
                                            container.appendChild(item);
                                        });
                                    }catch(e){}
                                }

                                // Expor funções globalmente para atualização via AJAX
                                window.mydRenderOrdersChart = renderOrdersChart;
                                window.mydBuildOrdersLegend = buildOrdersLegend;

                                document.addEventListener('DOMContentLoaded', function(){ renderOrdersChart(window.mydOrderLabels, window.mydOrderCounts, window.mydPrevOrderCounts); });
                                if ( document.readyState === 'complete' ) renderOrdersChart(window.mydOrderLabels, window.mydOrderCounts, window.mydPrevOrderCounts);
                            })();
                            </script>
                <div class="myd-dashboard-sales">
                    <div class="myd-section-header" style="display:flex;align-items:center;margin-bottom:12px;">
                        <div class="myd-dashboard-title-icon" style="display:flex;align-items:center; font-size:20px;">
                        <svg viewBox="0 0 24 24" role="img" xmlns="http://www.w3.org/2000/svg" aria-labelledby="dolarIconTitle" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" color="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title id="dolarIconTitle">Dolar</title> <path d="M12 4L12 6M12 18L12 20M15.5 8C15.1666667 6.66666667 14 6 12 6 9 6 8.5 7.95652174 8.5 9 8.5 13.140327 15.5 10.9649412 15.5 15 15.5 16.0434783 15 18 12 18 10 18 8.83333333 17.3333333 8.5 16"></path> </g></svg>
                    </div>
                    <h2 class="myd-dashboard-title" style="margin:0;font-size:18px;font-weight:600;color:#111;">Análise de vendas</h2>
                </div>
                <div class="myd-dashboard-cards">
                    <div class="myd-dashboard-card myd-card-with-icon">
                        <div class="myd-card-icon">
                            <svg viewBox="0 0 24 24" role="img" xmlns="http://www.w3.org/2000/svg" aria-labelledby="dolarIconTitle" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"><title id="dolarIconTitle">Dolar</title><path d="M12 4L12 6M12 18L12 20M15.5 8C15.1666667 6.66666667 14 6 12 6 9 6 8.5 7.95652174 8.5 9 8.5 13.140327 15.5 10.9649412 15.5 15 15.5 16.0434783 15 18 12 18 10 18 8.83333333 17.3333333 8.5 16"></path></svg>
                        </div>
                        <div class="myd-card-content">
                            <div class="title"><?php echo esc_html__( 'Valor das vendas', 'myd-delivery-pro' ); ?></div>
                            <div class="value" id="myd-card-total">
                                <span class="myd-card-price" id="myd-card-price-value"><?php echo esc_html( $currency . ' ' . \MydPro\Includes\Myd_Store_Formatting::format_price( $total ) ); ?></span>
                                <span class="myd-card-delta myd-card-delta--<?php echo esc_attr( $delta_total_direction ); ?>" id="myd-card-total-delta">
                                    <?php if ( $delta_total_direction === 'up' ) : ?>
                                        <span class="myd-delta-arrow">▲</span>
                                    <?php elseif ( $delta_total_direction === 'down' ) : ?>
                                        <span class="myd-delta-arrow">▼</span>
                                    <?php else : ?>
                                        <span class="myd-delta-arrow">—</span>
                                    <?php endif; ?>
                                    <span class="myd-delta-value"><?php echo esc_html( $delta_total_formatted ); ?></span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="myd-dashboard-card myd-card-with-icon">
                        <div class="myd-card-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 12.0002C5 10.694 4.16519 9.58273 3 9.1709V7.6C3 7.03995 3 6.75992 3.10899 6.54601C3.20487 6.35785 3.35785 6.20487 3.54601 6.10899C3.75992 6 4.03995 6 4.6 6H19.4C19.9601 6 20.2401 6 20.454 6.10899C20.6422 6.20487 20.7951 6.35785 20.891 6.54601C21 6.75992 21 7.03995 21 7.6V9.17071C19.8348 9.58254 19 10.694 19 12.0002C19 13.3064 19.8348 14.4175 21 14.8293V16.4C21 16.9601 21 17.2401 20.891 17.454C20.7951 17.6422 20.6422 17.7951 20.454 17.891C20.2401 18 19.9601 18 19.4 18H4.6C4.03995 18 3.75992 18 3.54601 17.891C3.35785 17.7951 3.20487 17.6422 3.10899 17.454C3 17.2401 3 16.9601 3 16.4V14.8295C4.16519 14.4177 5 13.3064 5 12.0002Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        </div>
                        <div class="myd-card-content">
                            <div class="title"><?php echo esc_html__( 'Ticket médio', 'myd-delivery-pro' ); ?></div>
                            <div class="value" id="myd-card-avg">
                                <span class="myd-card-price" id="myd-card-avg-value"><?php echo esc_html( $currency . ' ' . \MydPro\Includes\Myd_Store_Formatting::format_price( $avg ) ); ?></span>
                                <span class="myd-card-delta myd-card-delta--<?php echo esc_attr( $delta_avg_direction ); ?>" id="myd-card-avg-delta">
                                    <?php if ( $delta_avg_direction === 'up' ) : ?>
                                        <span class="myd-delta-arrow">▲</span>
                                    <?php elseif ( $delta_avg_direction === 'down' ) : ?>
                                        <span class="myd-delta-arrow">▼</span>
                                    <?php else : ?>
                                        <span class="myd-delta-arrow">—</span>
                                    <?php endif; ?>
                                    <span class="myd-delta-value"><?php echo esc_html( $delta_avg_formatted ); ?></span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="myd-dashboard-card myd-card-with-icon">
                        <div class="myd-card-icon">
                            <svg fill="currentColor" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve"><g> <g> <path d="M251.574,0c-37.454,0-67.925,30.471-67.925,67.926c-0.001,37.454,30.47,67.925,67.925,67.925 c37.454,0,67.926-30.471,67.926-67.925C319.5,30.471,289.028,0,251.574,0z M251.574,105.434 c-20.682,0-37.508-16.826-37.508-37.508c-0.001-20.683,16.825-37.509,37.508-37.509c20.682,0,37.509,16.826,37.509,37.509 C289.082,88.608,272.256,105.434,251.574,105.434z"></path> </g> </g> <g> <g> <rect x="190.525" y="204.932" width="122.106" height="30.417"></rect> </g> </g> <g> <g> <path d="M304.62,153.082H198.529c-2.383,0-4.774,0.056-7.168,0.166c-11.603-23.7-35.991-39.386-63.066-39.386h-15.209v64.928 c-24.427,16.201-43.836,39.075-55.821,65.747H13.751v126.705h43.494c9.037,20.098,22.384,38.212,39.019,52.855 c16.391,14.427,35.715,25.243,56.453,31.674V512h86.192v-49.304h25.329V512h86.192v-56.214 c29.054-9.021,55.177-26.645,74.494-50.468c19.047-23.49,30.761-52.231,33.74-82.22h39.585V292.68H458.68 C451.014,214.431,384.85,153.082,304.62,153.082z M401.298,386.159c-17.623,21.734-42.268,37.044-69.396,43.113l-11.889,2.659 v49.651h-25.357v-49.304h-86.164v49.304h-25.357v-49.668l-11.889-2.659c-20.247-4.53-39.226-14.209-54.884-27.991 c-15.552-13.689-27.528-31.143-34.63-50.476l-3.659-9.966H44.169v-65.87h33.92l3.661-9.962 c9.959-27.094,29.21-50.045,54.207-64.627l7.544-4.401v-48.647c11.055,4.599,19.786,14.077,23.114,26.093l3.501,12.64 l13.018-1.605c5.099-0.629,10.279-0.948,15.394-0.948h106.09c68.588,0,124.391,55.801,124.391,124.39 C429.01,336.737,419.426,363.802,401.298,386.159z"></path> </g> </g> <g> <g> <circle cx="129.315" cy="260.028" r="16.68"></circle> </g> </g> </g></svg>
                        </div>
                        <div class="myd-card-content">
                            <div class="title"><?php echo esc_html__( 'Economia', 'myd-delivery-pro' ); ?></div>
                            <div class="value" id="myd-card-economy">
                                <span class="myd-card-price" id="myd-card-economy-value"><?php echo esc_html( $currency . ' ' . \MydPro\Includes\Myd_Store_Formatting::format_price( $economy ) ); ?></span>
                                <span class="myd-card-delta myd-card-delta--<?php echo esc_attr( $delta_economy_direction ); ?>" id="myd-card-economy-delta">
                                    <?php if ( $delta_economy_direction === 'up' ) : ?>
                                        <span class="myd-delta-arrow">▲</span>
                                    <?php elseif ( $delta_economy_direction === 'down' ) : ?>
                                        <span class="myd-delta-arrow">▼</span>
                                    <?php else : ?>
                                        <span class="myd-delta-arrow">—</span>
                                    <?php endif; ?>
                                    <span class="myd-delta-value"><?php echo esc_html( $delta_economy_formatted ); ?></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="myd-chart-wrap">
                    <canvas id="mydSalesChart" height="160"></canvas>
                </div>
                <div class="myd-chart-legend" id="myd-chart-legend" aria-hidden="false"></div>
                </div>

                <!-- Seção de insights: Origem, Forma de pagamento e Tempo de entrega (dados de teste aleatórios) -->
                <div class="myd-dashboard-insights" style="display:flex;gap:12px;margin-top:18px;flex-wrap:wrap;">
                    <div class="myd-dashboard-card" style="flex:1 1 32%;min-width:220px;">
                        <div class="myd-insight-title-row">
                            <div class="title">Origem de pedidos</div>
                            <div class="myd-insight-subtitle">Visualize os canais que mais geram vendas</div>
                        </div>
                        <div class="myd-delivery-metrics">
                            <div class="myd-delivery-metric">
                                <div class="myd-delivery-metric-label">Mais usado</div>
                                <div class="myd-delivery-metric-value" id="mydOriginMostUsedValue">-</div>
                            </div>
                        </div>
                        <div>
                            <canvas id="mydOrdersOriginChart" height="200"></canvas>
                        </div>
                    </div>
                    <div class="myd-dashboard-card" style="flex:1 1 32%;min-width:220px;">
                        <div class="myd-insight-title-row">
                            <div class="title">Forma de pagamento</div>
                            <div class="myd-insight-subtitle">Formas de pagamento mais utilizadas pelos clientes</div>
                        </div>
                        <div class="myd-delivery-metrics">
                            <div class="myd-delivery-metric">
                                <div class="myd-delivery-metric-label">Mais usado</div>
                                <div class="myd-delivery-metric-value" id="mydPaymentMostUsedValue">-</div>
                            </div>
                        </div>
                        <div>
                            <canvas id="mydPaymentMethodChart" height="200"></canvas>
                        </div>
                    </div>
                    <div class="myd-dashboard-card" style="flex:1 1 32%;min-width:220px;">
                        <div class="myd-insight-title-row">
                            <div class="title">Desempenho de entrega</div>
                            <div class="myd-insight-subtitle">Acompanhe o desempenho do seu serviço de entregas</div>
                        </div>
                        <div class="myd-delivery-metrics">
                            <div class="myd-delivery-metric">
                                <div class="myd-delivery-metric-label">Tempo médio (OCT)</div>
                                <div class="myd-delivery-metric-value" id="mydDeliveryAvgValue"></div>
                            </div>
                            <div class="myd-delivery-metric">
                                <div class="myd-delivery-metric-label">Entregas no prazo (OTD)</div>
                                <div class="myd-delivery-metric-value" id="mydDeliveryOntimeValue"></div>
                            </div>
                        </div>
                        <div>
                            <canvas id="mydDeliveryTimeChart" height="200"></canvas>
                        </div>
                    </div>
                </div>

            </div>

        </div>
        <script>
        (function(){
            var labels = <?php echo wp_json_encode( $labels ); ?>;
            var values = <?php echo wp_json_encode( $data ); ?>;
            var prevValues = <?php echo wp_json_encode( $prev_data['data'] ); ?>;
            var rawDates = <?php echo wp_json_encode( $dashboard_data['days'] ); ?>;
            var prevDates = <?php echo wp_json_encode( $prev_data['days'] ); ?>;
            var counts = <?php echo wp_json_encode( $dashboard_data['counts'] ); ?>;
            var prevCounts = <?php echo wp_json_encode( $prev_data['counts'] ); ?>;
            var legendNumDays = <?php echo wp_json_encode( $num_days ); ?>;
            var mydChart = null;
            var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
            var adminUrl = '<?php echo esc_js( admin_url( '' ) ); ?>';
            var currencySymbol = '<?php echo esc_js( $currency ); ?>';
            var mydSidePanelDate = null; // store current date shown in side panel for back navigation
            var serverDefaultStart = '<?php echo date( 'Y-m-d', $start_ts ); ?>';
            var serverDefaultEnd = '<?php echo date( 'Y-m-d', $end_ts ); ?>';

            // keep originals so we can restore after aggregated views
            var _myd_orig = {
                labels: Array.isArray(labels) ? labels.slice() : [],
                values: Array.isArray(values) ? values.slice() : [],
                prevValues: Array.isArray(prevValues) ? prevValues.slice() : [],
                rawDates: Array.isArray(rawDates) ? rawDates.slice() : [],
                prevDates: Array.isArray(prevDates) ? prevDates.slice() : [],
                counts: Array.isArray(counts) ? counts.slice() : [],
                prevCounts: Array.isArray(prevCounts) ? prevCounts.slice() : []
            };

            function renderChart(chartLabels, chartValues, prevValues){
                var el = document.getElementById('mydSalesChart');
                if(!el) return;
                var ctx = el.getContext('2d');
                if(typeof Chart === 'undefined'){
                    // retry later if Chart.js not yet loaded
                    return setTimeout(function(){ renderChart(chartLabels, chartValues, prevValues); }, 250);
                }
                // If only one data point, pad labels and values so the single point appears centered
                var singlePointMode = false;
                var paddedLabels = chartLabels;
                var paddedValues = chartValues;
                var paddedPrev = prevValues || [];
                if(Array.isArray(chartLabels) && chartLabels.length === 1){
                    singlePointMode = true;
                    paddedLabels = ['', chartLabels[0], ''];
                    paddedValues = [0, (chartValues && chartValues.length ? chartValues[0] : 0), 0];
                    if(prevValues && prevValues.length){
                        paddedPrev = [0, prevValues[0], 0];
                    } else {
                        paddedPrev = [0, 0, 0];
                    }
                }
                // Destroy existing chart if exists
                if(mydChart){
                    mydChart.destroy();
                }
                mydChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: paddedLabels,
                        datasets: [
                            {
                                label: '<?php echo esc_js( 'Valor das vendas (R$)' ); ?>',
                                data: paddedValues,
                                borderColor: '#ed972b',
                                backgroundColor: 'rgba(237,151,43,0.06)',
                                tension: 0,
                                // ensure points are visible by default
                                pointRadius: 5,
                                radius: 3,
                                pointHoverRadius: 6,
                                hoverRadius: 6,
                                pointBackgroundColor: '#ed972b',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                fill: false,
                                borderWidth: 2,
                                order: 2
                            },
                            {
                                label: '<?php echo esc_js( __( 'Comparação período anterior', 'myd-delivery-pro' ) ); ?>',
                                data: paddedPrev || [],
                                borderColor: 'rgba(237,151,43,0.28)',
                                backgroundColor: 'rgba(237,151,43,0.06)',
                                tension: 0,
                                // visible but subtle points for comparison series
                                pointRadius: 3,
                                radius: 3,
                                pointHoverRadius: 0,
                                hoverRadius: 0,
                                pointBackgroundColor: 'rgba(237,151,43,0.28)',
                                fill: false,
                                borderWidth: 2,
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'nearest',
                            axis: 'x',
                            intersect: false
                        },
                        elements: {
                            point: {
                                radius: 3,
                                hitRadius: 10,
                                hoverRadius: 0
                            }
                        },
                        plugins: {
                            // disable built-in legend (we render an external one)
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: false,
                                external: function(context){
                                    // create tooltip element if not exists
                                    var tooltipEl = document.getElementById('myd-chart-tooltip');
                                    if(!tooltipEl){
                                        tooltipEl = document.createElement('div');
                                        tooltipEl.id = 'myd-chart-tooltip';
                                        tooltipEl.className = 'myd-chart-tooltip';
                                        // keep non-interactive so it never blocks pointer events
                                        tooltipEl.style.pointerEvents = 'none';
                                        tooltipEl.style.zIndex = 1000;
                                        tooltipEl.style.opacity = 0;
                                        tooltipEl.style.visibility = 'hidden';
                                        tooltipEl.style.transition = 'opacity 120ms ease';
                                        // position absolute inside the chart wrapper so it doesn't overlay other elements
                                        tooltipEl.style.position = 'absolute';
                                        // append to chart container (canvas parent) so coordinates are local
                                        try{
                                            var canvasParent = context.chart && context.chart.canvas ? context.chart.canvas.parentNode : null;
                                            if(canvasParent){
                                                // ensure the parent can contain absolutely positioned children
                                                var cs = window.getComputedStyle(canvasParent);
                                                if(cs.position === 'static') canvasParent.style.position = 'relative';
                                                canvasParent.appendChild(tooltipEl);
                                            } else {
                                                document.body.appendChild(tooltipEl);
                                            }
                                        }catch(e){
                                            document.body.appendChild(tooltipEl);
                                        }
                                    }

                                    var tooltipModel = context.tooltip;
                                    if(tooltipModel.opacity === 0){
                                        // hide tooltip using opacity/visibility so it doesn't block hover
                                        tooltipEl.style.opacity = 0;
                                        tooltipEl.style.visibility = 'hidden';
                                        return;
                                    }

                                    // Prefer the dataPoint from the current period (datasetIndex === 0)
                                    var chart = context.chart;
                                    var point = null;
                                    if(tooltipModel.dataPoints && tooltipModel.dataPoints.length){
                                        for(var i=0;i<tooltipModel.dataPoints.length;i++){
                                            if(tooltipModel.dataPoints[i].datasetIndex === 0){
                                                point = tooltipModel.dataPoints[i];
                                                break;
                                            }
                                        }
                                        // fallback to first point if none for dataset 0
                                        if(!point) point = tooltipModel.dataPoints[0];
                                    }
                                    if(!point){
                                        tooltipEl.style.opacity = 0;
                                        return;
                                    }

                                    var idx = point.dataIndex;
                                    // If chart labels were padded (single-point centering), map index back to original counts
                                    var mappedIdx = idx;
                                    if(typeof counts !== 'undefined' && chart.data && chart.data.labels){
                                        var labelsLen = chart.data.labels.length || 0;
                                        var countsLen = counts.length || 0;
                                        if(labelsLen > countsLen){
                                            var offset = Math.round((labelsLen - countsLen) / 2);
                                            mappedIdx = idx - offset;
                                        }
                                    }
                                    // determine if tooltip should show currency values (dataset label or presence of currencySymbol)
                                    var mainDsLabel = (chart.data && chart.data.datasets && chart.data.datasets[0] && chart.data.datasets[0].label) ? String(chart.data.datasets[0].label) : '';
                                    var isCurrencyTooltip = false;
                                    try{
                                        if(typeof currencySymbol !== 'undefined' && currencySymbol) isCurrencyTooltip = true;
                                        if(!isCurrencyTooltip && /valor|r\$/i.test(mainDsLabel)) isCurrencyTooltip = true;
                                    }catch(e){/* ignore */}

                                    // read source arrays: counts for counts view, values for currency
                                    var numericCurrent = 0;
                                    var numericPrevious = 0;
                                    if(isCurrencyTooltip){
                                        numericCurrent = (typeof values !== 'undefined' && values[mappedIdx] !== undefined) ? parseFloat(values[mappedIdx]) || 0 : 0;
                                        numericPrevious = (typeof prevValues !== 'undefined' && prevValues[mappedIdx] !== undefined) ? parseFloat(prevValues[mappedIdx]) || 0 : 0;
                                    } else {
                                        numericCurrent = (typeof counts !== 'undefined' && counts[mappedIdx] !== undefined) ? counts[mappedIdx] : 0;
                                        numericPrevious = (typeof prevCounts !== 'undefined' && prevCounts[mappedIdx] !== undefined) ? prevCounts[mappedIdx] : 0;
                                    }
                                    var pct = 0;
                                    var dir = 'neutral';
                                    if(numericPrevious == 0){
                                        pct = numericCurrent == 0 ? 0 : 100;
                                    } else {
                                        pct = ((numericCurrent - numericPrevious)/numericPrevious) * 100;
                                    }
                                    if(pct > 0) dir = 'up'; else if(pct < 0) dir = 'down';

                                    // expose variables used later for display
                                    var currentCount = numericCurrent;
                                    var previousCount = numericPrevious;

                                    // build HTML similar to provided design
                                    var label = chart.data.labels[idx] || '';
                                    var prevLabelRaw = (typeof prevDates !== 'undefined' && prevDates[mappedIdx]) ? prevDates[mappedIdx] : '';
                                    var prevLabel = prevLabelRaw;
                                    if(prevLabelRaw){
                                        // try to format ISO date (YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS) to dd/mm/yyyy
                                        var m = String(prevLabelRaw).match(/^(\d{4})-(\d{2})-(\d{2})/);
                                        if(m){ prevLabel = m[3] + '/' + m[2] + '/' + m[1]; }
                                    }

                                    var html = '';
                                    // determine if this chart represents monetary values (format as currency)
                                    var mainDsLabel = (chart.data && chart.data.datasets && chart.data.datasets[0] && chart.data.datasets[0].label) ? String(chart.data.datasets[0].label) : '';
                                    var isCurrencyTooltip = false;
                                    try{
                                        if(typeof currencySymbol !== 'undefined' && currencySymbol) isCurrencyTooltip = true;
                                        if(!isCurrencyTooltip && /valor|r\$/i.test(mainDsLabel)) isCurrencyTooltip = true;
                                    }catch(e){/* ignore */}

                                    function fmtCurrency(v){
                                        var n = parseFloat(v) || 0;
                                        var s = n.toFixed(2).replace('.', ',');
                                        if(typeof currencySymbol !== 'undefined' && currencySymbol){ return currencySymbol + ' ' + s; }
                                        return 'R$ ' + s;
                                    }

                                    html += '<div class="myd-tooltip-top">Dia ' + label + '</div>';
                                    html += '<div class="myd-tooltip-main">';
                                    if(isCurrencyTooltip){
                                        html += '<div class="myd-tooltip-count">' + fmtCurrency(currentCount) + '</div>';
                                    } else {
                                        html += '<div class="myd-tooltip-count">' + currentCount + ' pedidos</div>';
                                    }
                                    var arrow = (dir === 'up') ? '▲ ' : (dir === 'down') ? '▼ ' : '— ';
                                    html += '<div class="myd-tooltip-pct myd-pct--' + dir + '">' + arrow + (pct > 0 ? '+' : '') + pct.toFixed(2) + '%</div>';
                                    html += '</div>';
                                    if(isCurrencyTooltip){
                                        html += '<div class="myd-tooltip-rel">em relação a <strong>' + fmtCurrency(previousCount) + '</strong> do dia <strong>' + prevLabel + '</strong></div>';
                                    } else {
                                        html += '<div class="myd-tooltip-rel">em relação aos <strong>' + previousCount + ' pedidos</strong> do dia <strong>' + prevLabel + '</strong></div>';
                                    }

                                    tooltipEl.innerHTML = html;
                                    // show tooltip visually but keep it non-interactive to avoid mouse capture
                                    tooltipEl.style.visibility = 'visible';
                                    tooltipEl.style.opacity = 1;

                                    // position tooltip relative to the canvas parent (use bounding rects for stability)
                                    var canvas = chart.canvas;
                                    var canvasParent = canvas.parentNode || document.body;
                                    var canvasRect = canvas.getBoundingClientRect();
                                    var parentRect = canvasParent.getBoundingClientRect ? canvasParent.getBoundingClientRect() : { left: 0, top: 0 };
                                    var caretXWithinCanvas = tooltipModel.caretX || 0;
                                    var caretYWithinCanvas = tooltipModel.caretY || 0;
                                    // compute left relative to parent
                                    var canvasLeftInParent = canvasRect.left - parentRect.left;
                                    // prefer to display tooltip to the side of the point (right if point in left half, else left)
                                    var tooltipW = tooltipEl.offsetWidth || 160;
                                    var sidePadding = 10; // gap between point and tooltip
                                    var parentWidth = (canvasParent && canvasParent.clientWidth) ? canvasParent.clientWidth : window.innerWidth;
                                    var minPadding = 8;
                                    var maxLeft = parentWidth - tooltipW - minPadding;

                                    var showRight = (caretXWithinCanvas < (canvasRect.width / 2));
                                    var left;
                                    if(showRight){
                                        left = Math.round(canvasLeftInParent + caretXWithinCanvas + sidePadding);
                                    } else {
                                        left = Math.round(canvasLeftInParent + caretXWithinCanvas - tooltipW - sidePadding);
                                    }
                                    if(left < minPadding) left = minPadding;
                                    if(left > maxLeft) left = maxLeft;

                                    // vertical position: keep a fixed offset from top of canvas but try to align near the point's Y
                                    var canvasTopInParent = canvasRect.top - parentRect.top;
                                    var desiredTop = Math.round(canvasTopInParent + Math.max(12, caretYWithinCanvas - (tooltipEl.offsetHeight / 2)));
                                    var topFixed = desiredTop;
                                    // clamp vertical within parent
                                    var parentHeight = (canvasParent && canvasParent.clientHeight) ? canvasParent.clientHeight : window.innerHeight;
                                    var maxTop = parentHeight - tooltipEl.offsetHeight - minPadding;
                                    if(topFixed < minPadding) topFixed = minPadding;
                                    if(topFixed > maxTop) topFixed = maxTop;

                                    tooltipEl.style.left = left + 'px';
                                    tooltipEl.style.top = topFixed + 'px';
                                    // ensure transform doesn't affect layout
                                    tooltipEl.style.transform = 'translateZ(0)';
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            },
                            x: {
                                ticks: {
                                    maxRotation: 0
                                }
                            }
                        }
                    },
                });

                // --- Custom hover handling: increase point size only for the main series (dataset 0)
                (function(){
                    try{
                        if(!mydChart) return;
                        var canvasEl = el;
                        var mainDs = mydChart.data.datasets[0];
                        // ensure we have an array of base radii to restore
                        var baseRadii = Array.isArray(mainDs.pointRadius) ? mainDs.pointRadius.slice() : new Array(mydChart.data.datasets[0].data.length).fill(mainDs.pointRadius || mainDs.radius || 3);
                        // save base array on dataset for future reference
                        mydChart.data.datasets[0].pointRadiusArray = baseRadii;
                        var hoverIdx = null;

                        function resetHover(){
                            if(hoverIdx === null) return;
                            mydChart.data.datasets[0].pointRadius = mydChart.data.datasets[0].pointRadiusArray.slice();
                            mydChart.update('none');
                            hoverIdx = null;
                        }

                        canvasEl.addEventListener('mousemove', function(evt){
                            try{
                                var points = mydChart.getElementsAtEventForMode(evt, 'nearest', {intersect:true}, false) || [];
                                var p = null;
                                for(var i=0;i<points.length;i++){
                                    if(points[i].datasetIndex === 0){ p = points[i]; break; }
                                }
                                if(p){
                                    // show pointer cursor when over a main-series point
                                    try{ canvasEl.style.cursor = 'pointer'; }catch(e){}
                                    var idx = p.index;
                                    if(hoverIdx !== idx){
                                        var arr = mydChart.data.datasets[0].pointRadiusArray.slice();
                                        arr[idx] = 6; // hover size
                                        mydChart.data.datasets[0].pointRadius = arr;
                                        mydChart.update('none');
                                        hoverIdx = idx;
                                    }
                                } else {
                                    // restore default cursor and radii
                                    try{ canvasEl.style.cursor = 'default'; }catch(e){}
                                    resetHover();
                                }
                            }catch(e){
                                // ignore
                            }
                        });

                        canvasEl.addEventListener('mouseleave', function(){ resetHover(); });
                    }catch(e){
                        // ignore
                    }
                })();

                // Make points clickable: open a right-side panel with details for the clicked day
                (function(){
                    try{
                        var canvasEl = el;
                        function createSidePanel(){
                            var panel = document.getElementById('myd-side-panel');
                            if(panel) return panel;

                            // create or reuse backdrop overlay
                            var overlay = document.getElementById('myd-side-panel-backdrop');
                            if(!overlay){
                                overlay = document.createElement('div');
                                overlay.id = 'myd-side-panel-backdrop';
                                overlay.className = 'myd-side-panel-backdrop';
                                document.body.appendChild(overlay);
                                overlay.addEventListener('click', function(){ closeSidePanel(); });
                            }

                            panel = document.createElement('aside');
                            panel.id = 'myd-side-panel';
                            panel.className = 'myd-side-panel';
                            // build header (title + close) and body using DOM methods to avoid innerHTML

                            var header = document.createElement('div');
                            header.className = 'myd-side-panel-header';

                            var h3 = document.createElement('h3');
                            h3.className = 'myd-side-panel-title';
                            h3.textContent = 'Detalhes';

                            var titleWrap = document.createElement('div');
                            titleWrap.className = 'myd-side-panel-title-wrap';

                            var subtitle = document.createElement('div');
                            subtitle.className = 'myd-side-panel-subtitle';
                            subtitle.textContent = '';
                            subtitle.style.display = 'none';

                            titleWrap.appendChild(h3);
                            titleWrap.appendChild(subtitle);

                            var closeBtn = document.createElement('button');
                            closeBtn.id = 'myd-side-panel-close';
                            closeBtn.setAttribute('aria-label', 'Fechar');
                            closeBtn.className = 'myd-side-panel-close-btn';

                            // create close X SVG via DOM
                            (function(){
                                var svgns = 'http://www.w3.org/2000/svg';
                                var svg = document.createElementNS(svgns, 'svg');
                                svg.setAttribute('viewBox','-3.5 0 19 19');
                                svg.setAttribute('xmlns','http://www.w3.org/2000/svg');
                                svg.setAttribute('aria-hidden','true');
                                svg.setAttribute('class','cf-icon-svg');
                                svg.setAttribute('fill','#ed972b');

                                var path = document.createElementNS(svgns, 'path');
                                path.setAttribute('d','M11.383 13.644A1.03 1.03 0 0 1 9.928 15.1L6 11.172 2.072 15.1a1.03 1.03 0 1 1-1.455-1.456l3.928-3.928L.617 5.79a1.03 1.03 0 1 1 1.455-1.456L6 8.261l3.928-3.928a1.03 1.03 0 0 1 1.455 1.456L7.455 9.716z');
                                svg.appendChild(path);

                                closeBtn.appendChild(svg);
                            })();

                            header.appendChild(titleWrap);
                            header.appendChild(closeBtn);

                            var bodyDiv = document.createElement('div');
                            bodyDiv.id = 'myd-side-panel-body';

                            panel.appendChild(header);
                            panel.appendChild(bodyDiv);
                            document.body.appendChild(panel);
                            if(closeBtn) closeBtn.addEventListener('click', closeSidePanel);
                            return panel;
                        }

                        function openSidePanel(title, html, opts){
                            var panel = createSidePanel();
                            var overlay = document.getElementById('myd-side-panel-backdrop');
                            if(overlay){ overlay.style.pointerEvents = 'auto'; overlay.style.opacity = '1'; }
                            var header = panel.querySelector('.myd-side-panel-header h3');
                            if(header){
                                header.textContent = title || 'Detalhes';
                                try{
                                    if(title === 'Pedidos concluídos'){
                                        header.classList.add('myd-title-200');
                                    } else {
                                        header.classList.remove('myd-title-200');
                                    }
                                }catch(e){ /* ignore */ }
                            }
                            try{
                                var subtitleEl = panel.querySelector('.myd-side-panel-subtitle');
                                var metricEl = panel.querySelector('.myd-side-panel-metric');
                                var opt = opts || {};
                                if(subtitleEl){
                                    if(opt.subtitle){
                                        subtitleEl.textContent = String(opt.subtitle);
                                        subtitleEl.style.display = '';
                                    } else {
                                        subtitleEl.textContent = '';
                                        subtitleEl.style.display = 'none';
                                    }
                                }
                                if(metricEl){
                                    if(opt.metric){
                                        metricEl.textContent = String(opt.metric);
                                        metricEl.style.display = '';
                                    } else {
                                        metricEl.textContent = '';
                                        metricEl.style.display = 'none';
                                    }
                                }
                            }catch(e){}
                            var body = document.getElementById('myd-side-panel-body');
                            if(body){
                                // clear existing children
                                while(body.firstChild) body.removeChild(body.firstChild);

                                // If this is the orders listing panel, show two summary cards (Data | Situação)
                                try{
                                    if(String(title).toLowerCase() === 'pedidos concluídos'){
                                        var cardsWrap = document.createElement('div');
                                        cardsWrap.className = 'myd-panel-cards';

                                        // Data card
                                        var cardDate = document.createElement('div');
                                        cardDate.className = 'myd-panel-card myd-panel-card--date';
                                        var lblDate = document.createElement('div'); lblDate.className = 'myd-panel-card-label'; lblDate.textContent = 'Data';
                                        var valDate = document.createElement('div'); valDate.className = 'myd-panel-card-value';
                                        // try to read global stored date (set when user clicked a point)
                                        var raw = (typeof mydSidePanelDate !== 'undefined') ? mydSidePanelDate : '';
                                        var displayDate = '';
                                        if(raw){
                                            if(String(raw).indexOf('date:') === 0){ displayDate = String(raw).substr(5); }
                                            else {
                                                var m = String(raw).match(/^(\d{4})-(\d{2})-(\d{2})/);
                                                if(m) displayDate = m[3] + '/' + m[2] + '/' + m[1];
                                                else displayDate = String(raw);
                                            }
                                        }
                                        // Se não houver data definida, pega do input
                                        if(!displayDate){
                                            var sIn = document.querySelector('input[name="myd_dashboard_start"]');
                                            if(sIn && sIn.value){
                                                var m = String(sIn.value).match(/^(\d{4})-(\d{2})-(\d{2})/);
                                                if(m) displayDate = m[3] + '/' + m[2] + '/' + m[1];
                                                else displayDate = String(sIn.value);
                                            }
                                        }
                                        valDate.textContent = displayDate || '';
                                        cardDate.appendChild(lblDate); cardDate.appendChild(valDate);

                                        // Situação card
                                        var cardState = document.createElement('div');
                                        cardState.className = 'myd-panel-card myd-panel-card--state';
                                        var lblState = document.createElement('div'); lblState.className = 'myd-panel-card-label'; lblState.textContent = 'Situação';
                                        var valState = document.createElement('div'); valState.className = 'myd-panel-card-value';
                                        // reuse existing status badge class if available
                                        var stateBadge = document.createElement('span'); stateBadge.className = 'myd-order-status'; stateBadge.textContent = 'Concluído';
                                        valState.appendChild(stateBadge);
                                        cardState.appendChild(lblState); cardState.appendChild(valState);

                                        cardsWrap.appendChild(cardDate);
                                        cardsWrap.appendChild(cardState);
                                        body.appendChild(cardsWrap);
                                    }
                                }catch(e){/* ignore */}

                                if(!html && html !== 0) {
                                    // nothing to add
                                } else if(html instanceof Node){
                                    body.appendChild(html);
                                } else {
                                    // fallback: insert text
                                    var t = document.createElement('div');
                                    t.textContent = String(html);
                                    body.appendChild(t);
                                }
                            }
                            // animate in
                            requestAnimationFrame(function(){ panel.style.transform = 'translateX(0)'; });
                        }

                        function closeSidePanel(){
                            var panel = document.getElementById('myd-side-panel');
                            var overlay = document.getElementById('myd-side-panel-backdrop');
                            if(overlay){ overlay.style.opacity = '0'; overlay.style.pointerEvents = 'none'; }
                            if(!panel) return;
                            panel.style.transform = 'translateX(100%)';
                        }

                        // Expose functions globally for mydOrdersChart
                        window.mydOpenSidePanel = openSidePanel;
                        window.mydCloseSidePanel = closeSidePanel;
                        window.mydAjaxUrl = ajaxUrl;
                        window.mydCurrencySymbol = currencySymbol;
                        window.mydSidePanelDate = mydSidePanelDate;
                        window.mydAdminUrl = adminUrl;

                        // Global function to open order details panel
                        window.mydOpenOrderDetail = function(orderId){
                            if(!orderId) return;
                            
                            // Loading overlay functions
                            function showOrderDetailLoadingOverlay() {
                                hideOrderDetailLoadingOverlay();
                                var ol = document.createElement('div');
                                ol.id = 'myd-map-loading-overlay';
                                ol.style.position = 'fixed';
                                ol.style.inset = '0';
                                ol.style.display = 'flex';
                                ol.style.alignItems = 'center';
                                ol.style.justifyContent = 'center';
                                ol.style.background = 'rgba(0,0,0,0.45)';
                                ol.style.zIndex = '100000';
                                ol.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;">' +
                                    '<svg width="64" height="64" viewBox="0 0 50 50" aria-hidden="true">' +
                                    '<circle cx="25" cy="25" r="20" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-dasharray="31.4 31.4">' +
                                    '<animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="1s" repeatCount="indefinite"/>' +
                                    '</circle>' +
                                    '</svg>' +
                                    '</div>';
                                document.body.appendChild(ol);
                            }
                            function hideOrderDetailLoadingOverlay() {
                                var ol = document.getElementById('myd-map-loading-overlay');
                                if (ol && ol.parentNode) ol.parentNode.removeChild(ol);
                            }
                            
                            // Create secondary panel if not exists
                            var panelId = 'myd-side-panel-order-detail';
                            var overlayId = 'myd-side-panel-order-detail-backdrop';
                            var panel = document.getElementById(panelId);
                            var overlay = document.getElementById(overlayId);
                            
                            function closeOrderDetailPanel(){
                                if(panel) panel.style.transform = 'translateX(100%)';
                                if(overlay){ overlay.style.opacity = '0'; overlay.style.pointerEvents = 'none'; }
                            }
                            
                            if(!overlay){
                                overlay = document.createElement('div');
                                overlay.id = overlayId;
                                overlay.className = 'myd-side-panel-backdrop';
                                document.body.appendChild(overlay);
                                overlay.addEventListener('click', closeOrderDetailPanel);
                            }
                            if(!panel){
                                panel = document.createElement('aside');
                                panel.id = panelId;
                                panel.className = 'myd-side-panel';
                                var header = document.createElement('div');
                                header.className = 'myd-side-panel-header';
                                var h3 = document.createElement('h3');
                                h3.className = 'myd-side-panel-title';
                                h3.textContent = 'Detalhes do pedido';
                                var closeBtn = document.createElement('button');
                                closeBtn.id = 'myd-side-panel-order-detail-close';
                                closeBtn.setAttribute('aria-label', 'Fechar');
                                closeBtn.className = 'myd-side-panel-close-btn myd-side-panel-close-btn--left';
                                closeBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M5 12H19M5 12L11 6M5 12L11 18" stroke="#2b2b2b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>';
                                panel.appendChild(closeBtn);
                                header.appendChild(h3);
                                panel.appendChild(header);
                                var bodyDiv = document.createElement('div');
                                bodyDiv.id = 'myd-side-panel-order-detail-body';
                                panel.appendChild(bodyDiv);
                                document.body.appendChild(panel);
                                closeBtn.addEventListener('click', closeOrderDetailPanel);
                            }
                            
                            // Clear body and show loading
                            var body = document.getElementById('myd-side-panel-order-detail-body');
                            if(body){ while(body.firstChild) body.removeChild(body.firstChild); }
                            var loadingNode = document.createElement('div');
                            loadingNode.className = 'myd-side-panel-loading';
                            loadingNode.textContent = 'Carregando detalhes do pedido...';
                            if(body) body.appendChild(loadingNode);
                            
                            showOrderDetailLoadingOverlay();
                            
                            // AJAX request
                            var fd = new FormData();
                            fd.append('action', 'myd_get_order_details');
                            fd.append('order_id', orderId);
                            fetch(window.mydAjaxUrl || ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                                .then(function(r){
                                    if(!r.ok){
                                        return r.text().then(function(t){ throw new Error('HTTP '+r.status+'\n'+(t||'')); });
                                    }
                                    return r.json();
                                })
                                .then(function(res){
                                    hideOrderDetailLoadingOverlay();
                                    overlay.style.pointerEvents = 'auto';
                                    overlay.style.opacity = '1';
                                    panel.style.transform = 'translateX(0)';
                                    
                                    var body = document.getElementById('myd-side-panel-order-detail-body');
                                    if(!body) return;
                                    while(body.firstChild) body.removeChild(body.firstChild);
                                    
                                    if(res && res.success && res.data){
                                        var d = res.data;
                                        
                                        // Header principal
                                        var header = document.createElement('div');
                                        header.className = 'myd-order-detail-header';
                                        var title = document.createElement('h2');
                                        title.className = 'myd-order-detail-title';
                                        title.textContent = '#' + (d.number || d.id);
                                        var date = document.createElement('span');
                                        date.className = 'myd-order-detail-date';
                                        date.textContent = (d.date_display || '-');
                                        header.appendChild(title);
                                        header.appendChild(document.createTextNode(' • '));
                                        header.appendChild(date);
                                        var status = document.createElement('span');
                                        status.className = 'myd-order-detail-status';
                                        status.textContent = 'Concluído';
                                        header.appendChild(status);
                                        body.appendChild(header);

                                        var grid = document.createElement('div');
                                        grid.className = 'myd-order-detail-grid';

                                        // Tipo de pagamento
                                        var tipoPagamento = document.createElement('div');
                                        tipoPagamento.className = 'myd-order-detail-info';
                                        var labelTipo = document.createElement('div');
                                        labelTipo.textContent = 'Tipo de pagamento';
                                        labelTipo.style.fontWeight = '500';
                                        labelTipo.style.marginBottom = '2px';
                                        tipoPagamento.appendChild(labelTipo);
                                        var valorTipo = document.createElement('div');
                                        valorTipo.className = 'myd-order-detail-info-value';
                                        if(d.order_payment_type) {
                                            if(d.order_payment_type === 'upon-delivery') {
                                                valorTipo.textContent = 'Pago na entrega';
                                            } else if(d.order_payment_type === 'payment-integration') {
                                                valorTipo.textContent = 'Pago online';
                                            } else {
                                                valorTipo.textContent = d.order_payment_type;
                                            }
                                        } else if(d.payment_method) {
                                            valorTipo.textContent = d.payment_method;
                                        } else {
                                            valorTipo.textContent = '-';
                                        }
                                        tipoPagamento.appendChild(valorTipo);
                                        grid.appendChild(tipoPagamento);

                                        // Canal de venda
                                        var canalVenda = document.createElement('div');
                                        canalVenda.className = 'myd-order-detail-info';
                                        var labelCanal = document.createElement('div');
                                        labelCanal.textContent = 'Canal de venda';
                                        labelCanal.style.fontWeight = '500';
                                        labelCanal.style.marginBottom = '2px';
                                        canalVenda.appendChild(labelCanal);
                                        var valorCanal = document.createElement('div');
                                        valorCanal.className = 'myd-order-detail-info-value-channel';
                                        var canalValue = d.order_channel ? d.order_channel : '-';
                                        if (canalValue === 'SYS') {
                                            var svg = document.createElement('span');
                                            svg.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 197.92 458.7"><path d="M193.05 18.99 7.16.03C3.86-.3.99 1.95.99 4.84v449.03c0 2.89 2.87 5.1 6.12 4.81l101.94-7.44c2.82-.25 4.96-2.34 4.96-4.85l-1.16-154.37c0-2.59 2.29-4.72 5.21-4.85l52.91-3.69c2.92-.17 5.21-2.26 5.21-4.85v-92.31c0-2.55-2.24-4.64-5.16-4.85l-55.29-4.64c-2.96-.17-5.25-2.38-5.16-5.02l3.25-40.26c.1-2.72 2.72-4.81 5.78-4.68l72.52 3.77c3.16.13 5.78-2.09 5.78-4.85v-102c0-2.45-2.07-4.48-4.87-4.8Zm-13.61 87.38c0 2.56-2.13 4.62-4.7 4.5l-75.16-3.5a4.49 4.49 0 0 0-4.7 4.35l-2.64 74.53c-.08 2.45 1.78 4.5 4.19 4.66l61.16 4.31c2.37.2 4.19 2.13 4.19 4.5v67.15c0 2.41-1.86 4.35-4.23 4.5l-59.22 3.43c-2.37.12-4.23 2.09-4.23 4.5l.94 152.65c0 2.33-1.74 4.27-4.03 4.5l-66.57 6.91c-2.64.27-4.97-1.78-4.97-4.46V21.88c0-2.68 2.33-4.78 5.01-4.46l151.01 17.6c2.27.3 3.95 2.19 3.95 4.46v66.88Z"/><path d="M175.49 35.03 24.48 17.42a4.488 4.488 0 0 0-5.01 4.46V438.9c0 2.68 2.33 4.74 4.97 4.46l66.57-6.91c2.29-.23 4.03-2.17 4.03-4.5L94.1 279.3c0-2.41 1.86-4.39 4.23-4.5l59.22-3.43c2.37-.16 4.23-2.09 4.23-4.5v-67.15c0-2.37-1.82-4.31-4.19-4.5l-61.16-4.31c-2.41-.16-4.27-2.21-4.19-4.66l2.64-74.53a4.495 4.495 0 0 1 4.7-4.35l75.16 3.5c2.56.12 4.7-1.94 4.7-4.5V39.49c0-2.28-1.68-4.16-3.95-4.46" style="fill:#fbb80b"/></svg>';
                                            valorCanal.appendChild(svg);
                                            var span = document.createElement('span');
                                            span.textContent = 'Cardápio';
                                            valorCanal.appendChild(span);
                                        } else if (canalValue === 'IFD') {
                                            var svgIfd = document.createElement('span');
                                            svgIfd.innerHTML = '<svg fill="#eb0033" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M8.428 1.67c-4.65 0-7.184 4.149-7.184 6.998 0 2.294 2.2 3.299 4.25 3.299l-.006-.006c4.244 0 7.184-3.854 7.184-6.998 0-2.29-2.175-3.293-4.244-3.293m11.328 0c-4.65 0-7.184 4.149-7.184 6.998 0 2.294 2.2 3.299 4.25 3.299l-.006-.006C21.061 11.96 24 8.107 24 4.963c0-2.29-2.18-3.293-4.244-3.293m-5.584 12.85 2.435 1.834c-2.17 2.07-6.124 3.525-9.353 3.17A8.91 8.91 0 0 1 .23 14.541H0a9.6 9.6 0 0 0 8.828 7.758c3.814.24 7.323-.905 9.947-3.13l-.004.007 1.08 2.988 1.555-7.623-7.234-.02z"/></svg>';
                                            valorCanal.appendChild(svgIfd);
                                            var spanIfd = document.createElement('span');
                                            spanIfd.textContent = 'iFood';
                                            valorCanal.appendChild(spanIfd);
                                        } else if (canalValue === 'WPP') {
                                            var svgWpp = document.createElement('span');
                                            svgWpp.innerHTML = '<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><title>Whatsapp-color</title><path d="M23.993 0C10.763 0 0 10.765 0 24a23.82 23.82 0 0 0 4.57 14.067l-2.99 8.917 9.224-2.948A23.8 23.8 0 0 0 24.007 48C37.237 48 48 37.234 48 24S37.238 0 24.007 0zm-6.7 12.19c-.466-1.114-.818-1.156-1.523-1.185a14 14 0 0 0-.804-.027c-.918 0-1.877.268-2.455.86-.705.72-2.454 2.398-2.454 5.841s2.51 6.773 2.849 7.239c.353.465 4.895 7.632 11.947 10.553 5.515 2.286 7.152 2.074 8.407 1.806 1.834-.395 4.133-1.75 4.711-3.386s.579-3.034.41-3.33c-.17-.296-.636-.465-1.34-.818-.706-.353-4.134-2.046-4.783-2.272-.634-.24-1.24-.155-1.72.522-.677.946-1.34 1.905-1.876 2.483-.423.452-1.115.509-1.693.268-.776-.324-2.948-1.086-5.628-3.47-2.074-1.849-3.484-4.148-3.893-4.84-.41-.705-.042-1.114.282-1.495.353-.438.691-.748 1.044-1.157.352-.41.55-.621.776-1.1.24-.466.07-.946-.1-1.3-.168-.352-1.579-3.795-2.157-5.191" fill="#67c15e" fill-rule="evenodd"/></svg>';
                                            valorCanal.appendChild(svgWpp);
                                            var spanWpp = document.createElement('span');
                                            spanWpp.textContent = 'WhatsApp';
                                            valorCanal.appendChild(spanWpp);
                                        } else {
                                            valorCanal.textContent = canalValue;
                                        }
                                        canalVenda.appendChild(valorCanal);
                                        grid.appendChild(canalVenda);

                                        // Horário do pedido
                                        var horarioPedido = document.createElement('div');
                                        horarioPedido.className = 'myd-order-detail-info';
                                        var labelHP = document.createElement('div');
                                        labelHP.textContent = 'Horário do pedido';
                                        labelHP.style.fontWeight = '500';
                                        labelHP.style.marginBottom = '2px';
                                        horarioPedido.appendChild(labelHP);
                                        var valorHP = document.createElement('div');
                                        valorHP.className = 'myd-order-detail-info-value';
                                        if(d.date_display && d.date_display.split(' ').length > 1) {
                                            valorHP.textContent = d.date_display.split(' ')[1];
                                        } else {
                                            valorHP.textContent = d.date_display || '-';
                                        }
                                        horarioPedido.appendChild(valorHP);
                                        grid.appendChild(horarioPedido);

                                        // Horário de entrega
                                        var horarioEntrega = document.createElement('div');
                                        horarioEntrega.className = 'myd-order-detail-info';
                                        var labelHE = document.createElement('div');
                                        labelHE.textContent = 'Horário de entrega';
                                        labelHE.style.fontWeight = '500';
                                        labelHE.style.marginBottom = '2px';
                                        horarioEntrega.appendChild(labelHE);
                                        var valorHE = document.createElement('div');
                                        valorHE.className = 'myd-order-detail-info-value';
                                        if(typeof d.order_delivery_time !== 'undefined' && d.order_delivery_time) {
                                            var raw = d.order_delivery_time;
                                            var onlyTime = raw;
                                            var match = String(raw).match(/(\d{2}:\d{2})/);
                                            if(match) {
                                                onlyTime = match[1];
                                            }
                                            valorHE.textContent = onlyTime;
                                        } else {
                                            valorHE.textContent = '-';
                                        }
                                        horarioEntrega.appendChild(valorHE);
                                        grid.appendChild(horarioEntrega);

                                        // ID Pedido iFood
                                        if (d.order_channel === 'IFD' && d.ifood_order_id) {
                                            var ifoodIdContainer = document.createElement('div');
                                            ifoodIdContainer.className = 'myd-order-detail-info';
                                            ifoodIdContainer.style.gridColumn = '1 / -1'; // Ocupa a linha toda
                                            
                                            var labelIfoodId = document.createElement('div');
                                            labelIfoodId.textContent = 'Original iFood ID';
                                            labelIfoodId.style.fontWeight = '500';
                                            labelIfoodId.style.marginBottom = '2px';
                                            
                                            var valorIfoodId = document.createElement('div');
                                            valorIfoodId.className = 'myd-order-detail-info-value';
                                            valorIfoodId.textContent = d.ifood_order_id;
                                            valorIfoodId.style.fontSize = '12px';
                                            valorIfoodId.style.color = '#666';
                                            valorIfoodId.style.userSelect = 'all'; // Facilita a cópia do ID
                                            
                                            ifoodIdContainer.appendChild(labelIfoodId);
                                            ifoodIdContainer.appendChild(valorIfoodId);
                                            grid.appendChild(ifoodIdContainer);
                                        }

                                        body.appendChild(grid);

                                        // Itens do Pedido
                                        var itensTitle = document.createElement('h3');
                                        itensTitle.className = 'myd-order-detail-items-title';
                                        itensTitle.textContent = 'Itens do Pedido';
                                        body.appendChild(itensTitle);

                                        var itemsContainer = document.createElement('div');
                                        itemsContainer.className = 'fdm-orders-items-products';
                                        var orderList = document.createElement('div');
                                        orderList.className = 'fdm-order-list-items';

                                        if(Array.isArray(d.items) && d.items.length){
                                            d.items.forEach(function(it){
                                                var prodLoop = document.createElement('div');
                                                prodLoop.className = 'fdm-products-order-loop';

                                                // Imagem do produto
                                                var thumbUrl = '';
                                                if(it.product_image){
                                                    thumbUrl = it.product_image;
                                                } else if(it.image){
                                                    thumbUrl = it.image;
                                                } else {
                                                    thumbUrl = 'https://franguxo.app.br/wp-content/uploads/2025/07/FRANGUXO-LOGO-FUNDO-BRANCO-1024x1024.webp';
                                                }
                                                var thumb = document.createElement('div');
                                                thumb.className = 'myd-item-thumb';
                                                thumb.style.backgroundImage = 'url(' + thumbUrl + ')';
                                                thumb.style.backgroundSize = 'cover';
                                                thumb.style.backgroundPosition = 'center';
                                                thumb.style.backgroundRepeat = 'no-repeat';
                                                prodLoop.appendChild(thumb);

                                                // Corpo do item
                                                var itemBody = document.createElement('div');
                                                itemBody.className = 'myd-order-detail-info';

                                                // Linha título + preço
                                                var itemRow = document.createElement('div');
                                                itemRow.className = 'myd-item-row';
                                                var titlePrice = document.createElement('div');
                                                titlePrice.className = 'myd-item-title-price-wrap';
                                                var itemTitle = document.createElement('span');
                                                itemTitle.className = 'myd-item-title';
                                                itemTitle.textContent = it.product_name || it.name || '-';
                                                var price = document.createElement('span');
                                                price.className = 'myd-item-price';
                                                price.textContent = (it.currency_symbol || 'R$') + ' ' + (it.product_price || it.price || '-');
                                                titlePrice.appendChild(itemTitle);
                                                titlePrice.appendChild(document.createTextNode(' '));
                                                titlePrice.appendChild(price);
                                                itemRow.appendChild(titlePrice);
                                                itemBody.appendChild(itemRow);

                                                // Extras
                                                if(it.product_extras){
                                                    var groups = it.product_extras.split(/\r?\n\r?\n/);
                                                    groups.forEach(function(group){
                                                        var lines = group.split(/\r?\n/).filter(function(e){return e;});
                                                        if(lines.length === 0) return;
                                                        var extras = document.createElement('div');
                                                        extras.className = 'myd-extra-group';
                                                        var label = document.createElement('div');
                                                        label.className = 'myd-extra-title';
                                                        label.style.fontWeight = 'bold';
                                                        label.textContent = lines[0].trim();
                                                        extras.appendChild(label);
                                                        lines.slice(1).forEach(function(extra){
                                                            var extraItem = document.createElement('div');
                                                            extraItem.className = 'myd-extra-item';
                                                            extraItem.textContent = extra;
                                                            extras.appendChild(extraItem);
                                                        });
                                                        itemBody.appendChild(extras);
                                                    });
                                                }

                                                // Observação
                                                if(it.product_note){
                                                    var note = document.createElement('div');
                                                    note.className = 'myd-note';
                                                    note.textContent = 'Observação: ' + it.product_note;
                                                    itemBody.appendChild(note);
                                                }

                                                prodLoop.appendChild(itemBody);
                                                orderList.appendChild(prodLoop);
                                            });
                                        } else {
                                            var empty = document.createElement('div');
                                            empty.textContent = 'Nenhum item encontrado.';
                                            orderList.appendChild(empty);
                                        }
                                        itemsContainer.appendChild(orderList);
                                        body.appendChild(itemsContainer);
                                    } else {
                                        var errDiv = document.createElement('div');
                                        errDiv.className = 'myd-no-orders';
                                        errDiv.textContent = 'Não foi possível carregar os detalhes do pedido.';
                                        body.appendChild(errDiv);
                                    }
                                })
                                .catch(function(err){
                                    hideOrderDetailLoadingOverlay();
                                    overlay.style.pointerEvents = 'auto';
                                    overlay.style.opacity = '1';
                                    panel.style.transform = 'translateX(0)';
                                    var body = document.getElementById('myd-side-panel-order-detail-body');
                                    if(!body) return;
                                    while(body.firstChild) body.removeChild(body.firstChild);
                                    var errDiv = document.createElement('div');
                                    errDiv.className = 'myd-no-orders';
                                    errDiv.textContent = 'Erro ao carregar detalhes: ' + (err && err.message ? err.message : 'unknown');
                                    body.appendChild(errDiv);
                                });
                        };

                        // Function to render orders list in side panel (used by both charts)
                        window.mydRenderOrdersList = function(res){
                            var body = document.getElementById('myd-side-panel-body');
                            if(!body) return;
                            function formatMinutes(min){
                                var m = parseInt(min, 10) || 0;
                                if(m <= 0) return '0 min';
                                var h = Math.floor(m / 60);
                                var r = m % 60;
                                var out = '';
                                if(h > 0) out += h + 'h ';
                                if(r > 0) out += r + 'm';
                                return out.trim();
                            }
                            // remove previous lists/loading/errors but keep top cards
                            try{
                                var nodes = body.querySelectorAll('.myd-order-list, .myd-no-orders, .myd-side-panel-loading');
                                Array.prototype.forEach.call(nodes, function(n){ if(n && n.parentNode) n.parentNode.removeChild(n); });
                            }catch(e){}
                            if(res && res.success && Array.isArray(res.data) && res.data.length){
                                // Agrupar por data (formato dd/mm/yyyy)
                                var grouped = {};
                                res.data.forEach(function(o){
                                    var dateStr = '';
                                    if(o.date_display){
                                        var parts = String(o.date_display).split(' ');
                                        dateStr = parts[0];
                                    }
                                    if(!grouped[dateStr]) grouped[dateStr] = [];
                                    grouped[dateStr].push(o);
                                });
                                var list = document.createElement('div');
                                list.className = 'myd-order-list';
                                // Para cada data, exibe header e pedidos
                                var headerAdded = false;
                                Object.keys(grouped).sort(function(a,b){
                                    // Ordena datas decrescente
                                    var pa = a.split('/'); var pb = b.split('/');
                                    var da = new Date(pa[2], pa[1]-1, pa[0]);
                                    var db = new Date(pb[2], pb[1]-1, pb[0]);
                                    return db-da;
                                }).forEach(function(dateStr){
                                    var pedidos = grouped[dateStr];
                                    // header row (apenas uma vez)
                                    if(!headerAdded){
                                        var header = document.createElement('div');
                                        header.className = 'myd-order-row--header';
                                        var htime = document.createElement('div'); htime.className = 'myd-order-col myd-order-time'; htime.textContent = 'Horário';
                                        var hnum = document.createElement('div'); hnum.className = 'myd-order-col myd-order-number'; hnum.textContent = 'Pedido';
                                        var htempo = document.createElement('div'); htempo.className = 'myd-order-col myd-order-total'; htempo.textContent = 'Tempo de entrega';
                                        header.appendChild(htime); header.appendChild(hnum); header.appendChild(htempo);
                                        list.appendChild(header);
                                        headerAdded = true;
                                    }

                                    // Header da data dentro de um row (agora abaixo do header de colunas)
                                    var dateRow = document.createElement('div');
                                    dateRow.className = 'myd-order-row myd-order-date-header-row';
                                    var dateHeader = document.createElement('div');
                                    dateHeader.className = 'myd-order-date-header';
                                    var dateLabel = document.createElement('strong');
                                    dateLabel.textContent = dateStr;
                                    dateHeader.appendChild(dateLabel);
                                    var countSpan = document.createElement('span');
                                    countSpan.textContent = ' • ' + pedidos.length + ' pedido' + (pedidos.length>1?'s':'');
                                    dateHeader.appendChild(countSpan);
                                    // ocupa as 3 colunas
                                    dateHeader.style.gridColumn = '1 / span 4';
                                    dateRow.appendChild(dateHeader);
                                    list.appendChild(dateRow);

                                    pedidos.forEach(function(o){
                                        var item = document.createElement('div');
                                        item.className = 'myd-order-row';

                                        var colTime = document.createElement('div'); colTime.className = 'myd-order-col myd-order-time';
                                        colTime.textContent = o.date_display ? (String(o.date_display).split(' ')[1] || o.date_display) : '';

                                        var colNum = document.createElement('div'); colNum.className = 'myd-order-col myd-order-number';
                                        var label = document.createElement('span');
                                        label.textContent = (o.number ? String(o.number) : (o.id ? String(o.id) : ''));
                                        label.className = 'myd-order-number-label myd-order-link';
                                        if(o.id) label.dataset.orderId = o.id;
                                        colNum.appendChild(label);

                                        var colTempo = document.createElement('div'); colTempo.className = 'myd-order-col myd-order-total';
                                        // Exibe tempo de entrega em minutos, se disponível
                                        if(typeof o.delivery_time_minutes !== 'undefined' && o.delivery_time_minutes !== null){
                                            colTempo.textContent = formatMinutes(o.delivery_time_minutes);
                                        } else {
                                            colTempo.textContent = '-';
                                        }

                                        item.appendChild(colTime);
                                        item.appendChild(colNum);
                                        item.appendChild(colTempo);

                                        // chevron icon
                                        try{
                                            var svgns = 'http://www.w3.org/2000/svg';
                                            var wrap = document.createElement('div'); wrap.className = 'myd-order-col myd-order-end-icon';
                                            var svg = document.createElementNS(svgns, 'svg');
                                            svg.setAttribute('viewBox','0 0 24 24');
                                            svg.setAttribute('fill','none');
                                            var path = document.createElementNS(svgns, 'path');
                                            path.setAttribute('fill-rule','evenodd');
                                            path.setAttribute('clip-rule','evenodd');
                                            path.setAttribute('d','M8.29289 4.29289C8.68342 3.90237 9.31658 3.90237 9.70711 4.29289L16.7071 11.2929C17.0976 11.6834 17.0976 12.3166 16.7071 12.7071L9.70711 19.7071C9.31658 20.0976 8.68342 20.0976 8.29289 19.7071C7.90237 19.3166 7.90237 18.6834 8.29289 18.2929L14.5858 12L8.29289 5.70711C7.90237 5.31658 7.90237 4.68342 8.29289 4.29289Z');
                                            path.setAttribute('fill','#6b3aa3'); // purple for orders chart
                                            svg.appendChild(path);
                                            wrap.appendChild(svg);
                                            item.appendChild(wrap);
                                        }catch(e){}

                                        // Make row clickable to open order details
                                        if(o.id){
                                            item.style.cursor = 'pointer';
                                            item.addEventListener('click', function(e){
                                                e.preventDefault();
                                                e.stopPropagation();
                                                // Use global function to open order detail
                                                if(typeof window.mydOpenOrderDetail === 'function'){
                                                    window.mydOpenOrderDetail(o.id);
                                                }
                                            });
                                        }

                                        list.appendChild(item);
                                    });
                                });
                                body.appendChild(list);
                            } else {
                                var noOrders = document.createElement('div');
                                noOrders.className = 'myd-no-orders';
                                noOrders.textContent = 'Nenhum pedido encontrado para esta data.';
                                body.appendChild(noOrders);
                            }
                        };

                        // click handler: detect nearest intersecting point on main dataset
                        canvasEl.addEventListener('click', function(evt){
                            try{
                                var pts = mydChart.getElementsAtEventForMode(evt, 'nearest', {intersect:true}, false) || [];
                                var p = null;
                                for(var i=0;i<pts.length;i++){
                                    if(pts[i].datasetIndex === 0){ p = pts[i]; break; }
                                }
                                if(!p) return;
                                var idx = p.index;
                                // map padded index back to original
                                var mappedIdx = idx;
                                if(mydChart.data && mydChart.data.labels){
                                    var labelsLen = mydChart.data.labels.length || 0;
                                    var countsLen = (counts && counts.length) ? counts.length : 0;
                                    if(labelsLen > countsLen){
                                        var offset = Math.round((labelsLen - countsLen) / 2);
                                        mappedIdx = idx - offset;
                                    }
                                }

                                var dayLabel = mydChart.data.labels[idx] || '';
                                var rawDate = (typeof rawDates !== 'undefined' && rawDates[mappedIdx]) ? rawDates[mappedIdx] : '';
                                var displayDate = dayLabel || '';
                                // try to format rawDate if available
                                if(rawDate){
                                    var mm = String(rawDate).match(/^(\d{4})-(\d{2})-(\d{2})/);
                                    if(mm) displayDate = mm[3] + '/' + mm[2] + '/' + mm[1];
                                }
                                var currentCount = (typeof counts !== 'undefined' && counts[mappedIdx] !== undefined) ? counts[mappedIdx] : 0;
                                // Open panel immediately with a loading indicator, then fetch orders via AJAX
                                var loadingNode = document.createElement('div');
                                loadingNode.className = 'myd-side-panel-loading';
                                loadingNode.textContent = 'Carregando pedidos...';
                                openSidePanel('Pedidos concluídos', loadingNode);

                                // perform AJAX request to fetch orders for the selected day
                                var fd = new FormData();
                                fd.append('action', 'myd_get_orders_for_day');
                                // detect current chart view/tab to support aggregated filters
                                try{
                                    var activeTab = document.querySelector('#myd-chart-tabs .myd-chart-tab--active');
                                    var activeView = activeTab ? activeTab.getAttribute('data-view') : 'day_of_month';
                                }catch(e){ var activeView = 'day_of_month'; }

                                if(activeView === 'day_of_week'){
                                    // send weekday index so server can return all orders in the selected range that fall on this weekday
                                    fd.append('agg', 'weekday');
                                    fd.append('weekday_index', String(mappedIdx));
                                    // include date range (start/end inputs) if available
                                    try{
                                        var sIn = document.querySelector('input[name="myd_dashboard_start"]');
                                        var eIn = document.querySelector('input[name="myd_dashboard_end"]');
                                        if(sIn && sIn.value) fd.append('start_date', sIn.value);
                                        if(eIn && eIn.value) fd.append('end_date', eIn.value);
                                    }catch(e){}
                                } else if(activeView === 'month_by_month'){
                                    fd.append('agg', 'month');
                                    // send month label (e.g., 'MM/YYYY') or index mappedIdx
                                    fd.append('month_label', rawDate || ('label:' + displayDate));
                                    try{ var sIn2 = document.querySelector('input[name="myd_dashboard_start"]'); var eIn2 = document.querySelector('input[name="myd_dashboard_end"]'); if(sIn2 && sIn2.value) fd.append('start_date', sIn2.value); if(eIn2 && eIn2.value) fd.append('end_date', eIn2.value); }catch(e){}
                                } else if(activeView === 'hour_by_hour'){
                                    fd.append('agg', 'hour');
                                    // mappedIdx corresponds to hour index when labels are '00h'..'23h'
                                    fd.append('hour', String(mappedIdx));
                                    try{ var sIn3 = document.querySelector('input[name="myd_dashboard_start"]'); var eIn3 = document.querySelector('input[name="myd_dashboard_end"]'); if(sIn3 && sIn3.value) fd.append('start_date', sIn3.value); if(eIn3 && eIn3.value) fd.append('end_date', eIn3.value); }catch(e){}
                                } else {
                                    fd.append('date', rawDate || ('date:' + displayDate));
                                }
                                // store the date/agg for back navigation
                                try{
                                    if(activeView === 'day_of_week'){
                                        mydSidePanelDate = String(mappedIdx);
                                    } else if(activeView === 'month_by_month'){
                                        mydSidePanelDate = rawDate || ('label:' + displayDate);
                                    } else if(activeView === 'hour_by_hour'){
                                        mydSidePanelDate = String(mappedIdx);
                                    } else {
                                        mydSidePanelDate = rawDate || ('date:' + displayDate);
                                    }
                                }catch(e){}
                                fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                                    .then(function(r){
                                        if(!r.ok){
                                            return r.text().then(function(t){
                                                throw new Error('HTTP ' + r.status + '\n' + (t || ''));
                                            });
                                        }
                                        return r.json();
                                    })
                                    .then(function(res){
                                        var body = document.getElementById('myd-side-panel-body');
                                        if(!body) return;
                                        // remove previous lists/loading/errors but keep top cards (.myd-panel-cards)
                                        try{
                                            var nodes = body.querySelectorAll('.myd-order-list, .myd-no-orders, .myd-side-panel-loading');
                                            Array.prototype.forEach.call(nodes, function(n){ if(n && n.parentNode) n.parentNode.removeChild(n); });
                                        }catch(e){}
                                        if(res && res.success && Array.isArray(res.data) && res.data.length){
                                            var list = document.createElement('div');
                                            list.className = 'myd-order-list';

                                            // header row
                                            var header = document.createElement('div');
                                            header.className = 'myd-order-row--header';
                                            var htime = document.createElement('div'); htime.className = 'myd-order-col myd-order-time'; htime.textContent = 'Horário';
                                            var hnum = document.createElement('div'); hnum.className = 'myd-order-col myd-order-number'; hnum.textContent = 'Pedido';
                                            var htotal = document.createElement('div'); htotal.className = 'myd-order-col myd-order-total'; htotal.textContent = 'Total do pedido';
                                            header.appendChild(htime); header.appendChild(hnum); header.appendChild(htotal);
                                            list.appendChild(header);

                                            res.data.forEach(function(o){
                                                var item = document.createElement('div');
                                                item.className = 'myd-order-row';

                                                var colTime = document.createElement('div'); colTime.className = 'myd-order-col myd-order-time';
                                                colTime.textContent = o.date_display ? (String(o.date_display).split(' ')[1] || o.date_display) : '';

                                                var colNum = document.createElement('div'); colNum.className = 'myd-order-col myd-order-number';
                                                var label = document.createElement('span');
                                                label.textContent = (o.number ? String(o.number) : (o.id ? String(o.id) : ''));
                                                label.className = 'myd-order-number-label myd-order-link';
                                                if(o.id) label.dataset.orderId = o.id;
                                                colNum.appendChild(label);

                                                var colTotal = document.createElement('div'); colTotal.className = 'myd-order-col myd-order-total';
                                                try{
                                                    var tot = o.total ? String(o.total) : '';
                                                    if(tot && typeof currencySymbol !== 'undefined' && tot.indexOf(currencySymbol) === -1){ tot = currencySymbol + ' ' + tot; }
                                                    colTotal.textContent = tot;
                                                }catch(e){ colTotal.textContent = o.total || ''; }

                                                item.appendChild(colTime);
                                                item.appendChild(colNum);
                                                item.appendChild(colTotal);
                                                // append end-row chevron SVG
                                                (function(){
                                                    try{
                                                        var svgns = 'http://www.w3.org/2000/svg';
                                                        var wrap = document.createElement('div'); wrap.className = 'myd-order-col myd-order-end-icon';
                                                        var svg = document.createElementNS(svgns, 'svg');
                                                        svg.setAttribute('viewBox','0 0 24 24');
                                                        svg.setAttribute('fill','none');
                                                        svg.setAttribute('xmlns','http://www.w3.org/2000/svg');
                                                        svg.setAttribute('aria-hidden','true');
                                                        svg.setAttribute('stroke','#000000');
                                                        svg.setAttribute('stroke-width','0.00024');

                                                        var path = document.createElementNS(svgns, 'path');
                                                        path.setAttribute('fill-rule','evenodd');
                                                        path.setAttribute('clip-rule','evenodd');
                                                        path.setAttribute('d','M8.29289 4.29289C8.68342 3.90237 9.31658 3.90237 9.70711 4.29289L16.7071 11.2929C17.0976 11.6834 17.0976 12.3166 16.7071 12.7071L9.70711 19.7071C9.31658 20.0976 8.68342 20.0976 8.29289 19.7071C7.90237 19.3166 7.90237 18.6834 8.29289 18.2929L14.5858 12L8.29289 5.70711C7.90237 5.31658 7.90237 4.68342 8.29289 4.29289Z');
                                                        path.setAttribute('fill','#ed972b');
                                                        svg.appendChild(path);
                                                        wrap.appendChild(svg);
                                                        item.appendChild(wrap);
                                                    }catch(e){/* ignore */}
                                                })();

                                                // Torna a linha clicável para abrir um NOVO painel de detalhes (secundário)
                                                if(o.id){
                                                    item.style.cursor = 'pointer';
                                                    item.addEventListener('click', function(e){
                                                        e.preventDefault();
                                                        e.stopPropagation();
                                                        // Funções de loading overlay (replicando myd-map-loading-overlay)
                                                        function showOrderDetailLoadingOverlay() {
                                                            hideOrderDetailLoadingOverlay();
                                                            let ol = document.createElement('div');
                                                            ol.id = 'myd-map-loading-overlay';
                                                            ol.style.position = 'fixed';
                                                            ol.style.inset = '0';
                                                            ol.style.display = 'flex';
                                                            ol.style.alignItems = 'center';
                                                            ol.style.justifyContent = 'center';
                                                            ol.style.background = 'rgba(0,0,0,0.45)';
                                                            ol.style.zIndex = '100000';
                                                            ol.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;">' +
                                                                '<svg width="64" height="64" viewBox="0 0 50 50" aria-hidden="true">' +
                                                                '<circle cx="25" cy="25" r="20" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-dasharray="31.4 31.4">' +
                                                                '<animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="1s" repeatCount="indefinite"/>' +
                                                                '</circle>' +
                                                                '</svg>' +
                                                                '</div>';
                                                            document.body.appendChild(ol);
                                                        }
                                                        function hideOrderDetailLoadingOverlay() {
                                                            const ol = document.getElementById('myd-map-loading-overlay');
                                                            if (ol && ol.parentNode) ol.parentNode.removeChild(ol);
                                                        }
                                                        // Cria painel secundário se não existir (mas não mostra ainda)
                                                        var panelId = 'myd-side-panel-order-detail';
                                                        var overlayId = 'myd-side-panel-order-detail-backdrop';
                                                        var panel = document.getElementById(panelId);
                                                        var overlay = document.getElementById(overlayId);
                                                        if(!overlay){
                                                            overlay = document.createElement('div');
                                                            overlay.id = overlayId;
                                                            overlay.className = 'myd-side-panel-backdrop';
                                                            document.body.appendChild(overlay);
                                                            overlay.addEventListener('click', function(){ closeOrderDetailPanel(); });
                                                        }
                                                        if(!panel){
                                                            panel = document.createElement('aside');
                                                            panel.id = panelId;
                                                            panel.className = 'myd-side-panel';
                                                            // header
                                                            var header = document.createElement('div');
                                                            header.className = 'myd-side-panel-header';
                                                            var h3 = document.createElement('h3');
                                                            h3.className = 'myd-side-panel-title';
                                                            h3.textContent = 'Detalhes do pedido';
                                                            var closeBtn = document.createElement('button');
                                                            closeBtn.id = 'myd-side-panel-order-detail-close';
                                                            closeBtn.setAttribute('aria-label', 'Fechar');
                                                            closeBtn.className = 'myd-side-panel-close-btn myd-side-panel-close-btn--left';
                                                            closeBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M5 12H19M5 12L11 6M5 12L11 18" stroke="#2b2b2b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>';
                                                            panel.appendChild(closeBtn);
                                                            header.appendChild(h3);
                                                            panel.appendChild(header);
                                                            var bodyDiv = document.createElement('div');
                                                            bodyDiv.id = 'myd-side-panel-order-detail-body';
                                                            panel.appendChild(bodyDiv);
                                                            document.body.appendChild(panel);
                                                            closeBtn.addEventListener('click', closeOrderDetailPanel);
                                                        }
                                                        // Função para fechar painel secundário
                                                        function closeOrderDetailPanel(){
                                                            panel.style.transform = 'translateX(100%)';
                                                            overlay.style.opacity = '0';
                                                            overlay.style.pointerEvents = 'none';
                                                        }
                                                        // Limpa body
                                                        var body = document.getElementById('myd-side-panel-order-detail-body');
                                                        if(body){ while(body.firstChild) body.removeChild(body.firstChild); }
                                                        // Loading
                                                        var loadingNode = document.createElement('div');
                                                        loadingNode.className = 'myd-side-panel-loading';
                                                        loadingNode.textContent = 'Carregando detalhes do pedido...';
                                                        if(body) body.appendChild(loadingNode);
                                                        // AJAX para buscar detalhes
                                                        showOrderDetailLoadingOverlay();
                                                        var fd = new FormData();
                                                        fd.append('action', 'myd_get_order_details');
                                                        fd.append('order_id', o.id);
                                                        fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                                                            .then(function(r){
                                                                if(!r.ok){
                                                                    return r.text().then(function(t){ throw new Error('HTTP '+r.status+'\n'+(t||'')); });
                                                                }
                                                                return r.json();
                                                            })
                                                            .then(function(res){
                                                                hideOrderDetailLoadingOverlay();
                                                                // Só mostra o painel após o AJAX concluir
                                                                overlay.style.pointerEvents = 'auto';
                                                                overlay.style.opacity = '1';
                                                                panel.style.transform = 'translateX(0)';
                                                                var body = document.getElementById('myd-side-panel-order-detail-body');
                                                                if(!body) return;
                                                                while(body.firstChild) body.removeChild(body.firstChild);
                                                                if(res && res.success && res.data){
                                                                    var d = res.data;
                                                                    // Header principal
                                                                    var header = document.createElement('div');
                                                                    header.className = 'myd-order-detail-header';
                                                                    var title = document.createElement('h2');
                                                                    title.className = 'myd-order-detail-title';
                                                                    title.textContent = '#' + (d.number || d.id);
                                                                    var date = document.createElement('span');
                                                                    date.className = 'myd-order-detail-date';
                                                                    date.textContent = (d.date_display || '-');
                                                                    header.appendChild(title);
                                                                    header.appendChild(document.createTextNode(' • '));
                                                                    header.appendChild(date);
                                                                    var status = document.createElement('span');
                                                                    status.className = 'myd-order-detail-status';
                                                                    status.textContent = 'Concluído';
                                                                    header.appendChild(status);
                                                                    body.appendChild(header);

                                                                    var grid = document.createElement('div');
                                                                    grid.className = 'myd-order-detail-grid';


                                                                    var tipoPagamento = document.createElement('div');
                                                                    tipoPagamento.className = 'myd-order-detail-info';
                                                                    var labelTipo = document.createElement('div');
                                                                    labelTipo.textContent = 'Tipo de pagamento';
                                                                    labelTipo.style.fontWeight = '500';
                                                                    labelTipo.style.marginBottom = '2px';
                                                                    tipoPagamento.appendChild(labelTipo);
                                                                    var valorTipo = document.createElement('div');
                                                                    valorTipo.className = 'myd-order-detail-info-value';
                                                                    if(d.order_payment_type) {
                                                                        if(d.order_payment_type === 'upon-delivery') {
                                                                            valorTipo.textContent = 'Pago na entrega';
                                                                        } else if(d.order_payment_type === 'payment-integration') {
                                                                            valorTipo.textContent = 'Pago online';
                                                                        } else {
                                                                            valorTipo.textContent = d.order_payment_type;
                                                                        }
                                                                    } else if(d.payment_method) {
                                                                        valorTipo.textContent = d.payment_method;
                                                                    } else {
                                                                        valorTipo.textContent = '-';
                                                                    }
                                                                    tipoPagamento.appendChild(valorTipo);
                                                                    grid.appendChild(tipoPagamento);

                                                                    // Canal de venda
                                                                    var canalVenda = document.createElement('div');
                                                                    canalVenda.className = 'myd-order-detail-info';
                                                                    var labelCanal = document.createElement('div');
                                                                    labelCanal.textContent = 'Canal de venda';
                                                                    labelCanal.style.fontWeight = '500';
                                                                    labelCanal.style.marginBottom = '2px';
                                                                    canalVenda.appendChild(labelCanal);
                                                                    var valorCanal = document.createElement('div');
                                                                    valorCanal.className = 'myd-order-detail-info-value-channel';
                                                                    var canalValue = d.order_channel ? d.order_channel : '-';
                                                                    if (canalValue === 'SYS') {
                                                                        var svg = document.createElement('span');
                                                                        svg.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 197.92 458.7"><path d="M193.05 18.99 7.16.03C3.86-.3.99 1.95.99 4.84v449.03c0 2.89 2.87 5.1 6.12 4.81l101.94-7.44c2.82-.25 4.96-2.34 4.96-4.85l-1.16-154.37c0-2.59 2.29-4.72 5.21-4.85l52.91-3.69c2.92-.17 5.21-2.26 5.21-4.85v-92.31c0-2.55-2.24-4.64-5.16-4.85l-55.29-4.64c-2.96-.17-5.25-2.38-5.16-5.02l3.25-40.26c.1-2.72 2.72-4.81 5.78-4.68l72.52 3.77c3.16.13 5.78-2.09 5.78-4.85v-102c0-2.45-2.07-4.48-4.87-4.8Zm-13.61 87.38c0 2.56-2.13 4.62-4.7 4.5l-75.16-3.5a4.49 4.49 0 0 0-4.7 4.35l-2.64 74.53c-.08 2.45 1.78 4.5 4.19 4.66l61.16 4.31c2.37.2 4.19 2.13 4.19 4.5v67.15c0 2.41-1.86 4.35-4.23 4.5l-59.22 3.43c-2.37.12-4.23 2.09-4.23 4.5l.94 152.65c0 2.33-1.74 4.27-4.03 4.5l-66.57 6.91c-2.64.27-4.97-1.78-4.97-4.46V21.88c0-2.68 2.33-4.78 5.01-4.46l151.01 17.6c2.27.3 3.95 2.19 3.95 4.46v66.88Z"/><path d="M175.49 35.03 24.48 17.42a4.488 4.488 0 0 0-5.01 4.46V438.9c0 2.68 2.33 4.74 4.97 4.46l66.57-6.91c2.29-.23 4.03-2.17 4.03-4.5L94.1 279.3c0-2.41 1.86-4.39 4.23-4.5l59.22-3.43c2.37-.16 4.23-2.09 4.23-4.5v-67.15c0-2.37-1.82-4.31-4.19-4.5l-61.16-4.31c-2.41-.16-4.27-2.21-4.19-4.66l2.64-74.53a4.495 4.495 0 0 1 4.7-4.35l75.16 3.5c2.56.12 4.7-1.94 4.7-4.5V39.49c0-2.28-1.68-4.16-3.95-4.46" style="fill:#fbb80b"/></svg>';
                                                                        valorCanal.appendChild(svg);
                                                                        var span = document.createElement('span');
                                                                        span.textContent = 'Cardápio';
                                                                        valorCanal.appendChild(span);
                                                                    } else if (canalValue === 'IFD') {
                                                                        var svgIfd = document.createElement('span');
                                                                        svgIfd.innerHTML = '<svg fill="#eb0033" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M8.428 1.67c-4.65 0-7.184 4.149-7.184 6.998 0 2.294 2.2 3.299 4.25 3.299l-.006-.006c4.244 0 7.184-3.854 7.184-6.998 0-2.29-2.175-3.293-4.244-3.293m11.328 0c-4.65 0-7.184 4.149-7.184 6.998 0 2.294 2.2 3.299 4.25 3.299l-.006-.006C21.061 11.96 24 8.107 24 4.963c0-2.29-2.18-3.293-4.244-3.293m-5.584 12.85 2.435 1.834c-2.17 2.07-6.124 3.525-9.353 3.17A8.91 8.91 0 0 1 .23 14.541H0a9.6 9.6 0 0 0 8.828 7.758c3.814.24 7.323-.905 9.947-3.13l-.004.007 1.08 2.988 1.555-7.623-7.234-.02z"/></svg>';
                                                                        valorCanal.appendChild(svgIfd);
                                                                        var spanIfd = document.createElement('span');
                                                                        spanIfd.textContent = 'iFood';
                                                                        valorCanal.appendChild(spanIfd);
                                                                    } else if (canalValue === 'WPP') {
                                                                        var svgWpp = document.createElement('span');
                                                                        svgWpp.innerHTML = '<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><g stroke-width="0"/><g stroke-linecap="round" stroke-linejoin="round"/><title>Whatsapp-color</title><path d="M23.993 0C10.763 0 0 10.765 0 24a23.82 23.82 0 0 0 4.57 14.067l-2.99 8.917 9.224-2.948A23.8 23.8 0 0 0 24.007 48C37.237 48 48 37.234 48 24S37.238 0 24.007 0zm-6.7 12.19c-.466-1.114-.818-1.156-1.523-1.185a14 14 0 0 0-.804-.027c-.918 0-1.877.268-2.455.86-.705.72-2.454 2.398-2.454 5.841s2.51 6.773 2.849 7.239c.353.465 4.895 7.632 11.947 10.553 5.515 2.286 7.152 2.074 8.407 1.806 1.834-.395 4.133-1.75 4.711-3.386s.579-3.034.41-3.33c-.17-.296-.636-.465-1.34-.818-.706-.353-4.134-2.046-4.783-2.272-.634-.24-1.24-.155-1.72.522-.677.946-1.34 1.905-1.876 2.483-.423.452-1.115.509-1.693.268-.776-.324-2.948-1.086-5.628-3.47-2.074-1.849-3.484-4.148-3.893-4.84-.41-.705-.042-1.114.282-1.495.353-.438.691-.748 1.044-1.157.352-.41.55-.621.776-1.1.24-.466.07-.946-.1-1.3-.168-.352-1.579-3.795-2.157-5.191" fill="#67c15e" fill-rule="evenodd"/></svg>';
                                                                        valorCanal.appendChild(svgWpp);
                                                                        var spanWpp = document.createElement('span');
                                                                        spanWpp.textContent = 'WhatsApp';
                                                                        valorCanal.appendChild(spanWpp);
                                                                    } else {
                                                                        valorCanal.textContent = canalValue;
                                                                    }
                                                                    canalVenda.appendChild(valorCanal);
                                                                    grid.appendChild(canalVenda);

                                                                    // ...existing code...

                                                                    var horarioPedido = document.createElement('div');
                                                                    horarioPedido.className = 'myd-order-detail-info';
                                                                    var labelHP = document.createElement('div');
                                                                    labelHP.textContent = 'Horário do pedido';
                                                                    labelHP.style.fontWeight = '500';
                                                                    labelHP.style.marginBottom = '2px';
                                                                    horarioPedido.appendChild(labelHP);
                                                                    var valorHP = document.createElement('div');
                                                                    valorHP.className = 'myd-order-detail-info-value';
                                                                    // Extrai apenas a hora de d.date_display
                                                                    if(d.date_display && d.date_display.split(' ').length > 1) {
                                                                        valorHP.textContent = d.date_display.split(' ')[1];
                                                                    } else {
                                                                        valorHP.textContent = d.date_display || '-';
                                                                    }
                                                                    horarioPedido.appendChild(valorHP);
                                                                    grid.appendChild(horarioPedido);

                                                                    var horarioEntrega = document.createElement('div');
                                                                    horarioEntrega.className = 'myd-order-detail-info';
                                                                    var labelHE = document.createElement('div');
                                                                    labelHE.textContent = 'Horário de entrega';
                                                                    labelHE.style.fontWeight = '500';
                                                                    labelHE.style.marginBottom = '2px';
                                                                    horarioEntrega.appendChild(labelHE);
                                                                    var valorHE = document.createElement('div');
                                                                    valorHE.className = 'myd-order-detail-info-value';
                                                                    if(typeof d.order_delivery_time !== 'undefined' && d.order_delivery_time) {
                                                                        // Extrai apenas o horário (HH:mm) se vier junto com a data
                                                                        var raw = d.order_delivery_time;
                                                                        var onlyTime = raw;
                                                                        var match = String(raw).match(/(\d{2}:\d{2})/);
                                                                        if(match) {
                                                                            onlyTime = match[1];
                                                                        }
                                                                        valorHE.textContent = onlyTime;
                                                                    } else {
                                                                        valorHE.textContent = '-';
                                                                    }
                                                                    horarioEntrega.appendChild(valorHE);
                                                                    grid.appendChild(horarioEntrega);

                                                                    body.appendChild(grid);

                                                                    var itensTitle = document.createElement('h3');
                                                                    itensTitle.className = 'myd-order-detail-items-title';
                                                                    itensTitle.textContent = 'Itens do Pedido';
                                                                    body.appendChild(itensTitle);

                                                                    // Container principal igual ao .fdm-orders-items-products
                                                                    var itemsContainer = document.createElement('div');
                                                                    itemsContainer.className = 'fdm-orders-items-products';
                                                                    var orderList = document.createElement('div');
                                                                    orderList.className = 'fdm-order-list-items';

                                                                    if(Array.isArray(d.items) && d.items.length){
                                                                        d.items.forEach(function(it){
                                                                            // Bloco do produto
                                                                            var prodLoop = document.createElement('div');
                                                                            prodLoop.className = 'fdm-products-order-loop';

                                                                            // Imagem do produto
                                                                            var thumbUrl = '';
                                                                            if(it.product_image){
                                                                                thumbUrl = it.product_image;
                                                                            } else if(it.image){
                                                                                thumbUrl = it.image;
                                                                            } else {
                                                                                thumbUrl = 'https://franguxo.app.br/wp-content/uploads/2025/07/FRANGUXO-LOGO-FUNDO-BRANCO-1024x1024.webp';
                                                                            }
                                                                            var thumb = document.createElement('div');
                                                                            thumb.className = 'myd-item-thumb';
                                                                            thumb.style.backgroundImage = 'url(' + thumbUrl + ')';
                                                                            thumb.style.backgroundSize = 'cover';
                                                                            thumb.style.backgroundPosition = 'center';
                                                                            thumb.style.backgroundRepeat = 'no-repeat';
                                                                            prodLoop.appendChild(thumb);

                                                                            // Corpo do item
                                                                            var itemBody = document.createElement('div');
                                                                            itemBody.className = 'myd-order-detail-info';

                                                                            // Linha título + preço juntos
                                                                            var itemRow = document.createElement('div');
                                                                            itemRow.className = 'myd-item-row';
                                                                            var titlePrice = document.createElement('div');
                                                                            titlePrice.className = 'myd-item-title-price-wrap';
                                                                            var title = document.createElement('span');
                                                                            title.className = 'myd-item-title';
                                                                            title.textContent = it.product_name || it.name || '-';
                                                                            var price = document.createElement('span');
                                                                            price.className = 'myd-item-price';
                                                                            price.textContent = (it.currency_symbol || 'R$') + ' ' + (it.product_price || it.price || '-');
                                                                            // Espaço entre nome e preço
                                                                            titlePrice.appendChild(title);
                                                                            titlePrice.appendChild(document.createTextNode(' '));
                                                                            titlePrice.appendChild(price);
                                                                            itemRow.appendChild(titlePrice);
                                                                            itemBody.appendChild(itemRow);

                                                                            // Extras (Molhos)
                                                                            if(it.product_extras){
                                                                                // Separa grupos por dupla quebra de linha
                                                                                var groups = it.product_extras.split(/\r?\n\r?\n/);
                                                                                groups.forEach(function(group){
                                                                                    var lines = group.split(/\r?\n/).filter(function(e){return e;});
                                                                                    if(lines.length === 0) return;
                                                                                    var extras = document.createElement('div');
                                                                                    extras.className = 'myd-extra-group';
                                                                                    // Título do grupo
                                                                                    var label = document.createElement('div');
                                                                                    label.className = 'myd-extra-title';
                                                                                    label.style.fontWeight = 'bold';
                                                                                    label.textContent = lines[0].trim();
                                                                                    extras.appendChild(label);
                                                                                    // Itens do grupo
                                                                                    lines.slice(1).forEach(function(extra){
                                                                                        var extraItem = document.createElement('div');
                                                                                        extraItem.className = 'myd-extra-item';
                                                                                        extraItem.textContent = extra;
                                                                                        extras.appendChild(extraItem);
                                                                                    });
                                                                                    itemBody.appendChild(extras);
                                                                                });
                                                                            }

                                                                            // Observação
                                                                            if(it.product_note){
                                                                                var note = document.createElement('div');
                                                                                note.className = 'myd-note';
                                                                                note.textContent = 'Observação: ' + it.product_note;
                                                                                itemBody.appendChild(note);
                                                                            }

                                                                            prodLoop.appendChild(itemBody);
                                                                            orderList.appendChild(prodLoop);
                                                                        });
                                                                    } else {
                                                                        var empty = document.createElement('div');
                                                                        empty.textContent = 'Nenhum item encontrado.';
                                                                        orderList.appendChild(empty);
                                                                    }
                                                                    itemsContainer.appendChild(orderList);
                                                                    body.appendChild(itemsContainer);
                                                                }else{
                                                                    var err = document.createElement('div');
                                                                    err.className = 'myd-no-orders';
                                                                    err.textContent = 'Não foi possível carregar os detalhes.';
                                                                    body.appendChild(err);
                                                                }
                                                            })
                                                            .catch(function(err){
                                                                hideOrderDetailLoadingOverlay();
                                                                // Mostra painel mesmo em caso de erro
                                                                overlay.style.pointerEvents = 'auto';
                                                                overlay.style.opacity = '1';
                                                                panel.style.transform = 'translateX(0)';
                                                                var body = document.getElementById('myd-side-panel-order-detail-body');
                                                                if(!body) return;
                                                                while(body.firstChild) body.removeChild(body.firstChild);
                                                                var err = document.createElement('div');
                                                                err.className = 'myd-no-orders';
                                                                err.textContent = 'Erro ao carregar detalhes: ' + (err && err.message ? err.message : 'unknown');
                                                                body.appendChild(err);
                                                            });
                                                    });
                                                }
                                                list.appendChild(item);
                                            });
                                            body.appendChild(list);
                                        } else {
                                            var none = document.createElement('div');
                                            none.className = 'myd-no-orders';
                                            none.textContent = 'Nenhum pedido encontrado para este dia.';
                                            body.appendChild(none);
                                        }
                                    })
                                    .catch(function(err){
                                        console.error('myd_get_orders_for_day error', err);
                                        var body = document.getElementById('myd-side-panel-body');
                                        if(!body) return;
                                        try{
                                            var nodes = body.querySelectorAll('.myd-order-list, .myd-no-orders, .myd-side-panel-loading');
                                            Array.prototype.forEach.call(nodes, function(n){ if(n && n.parentNode) n.parentNode.removeChild(n); });
                                        }catch(e){}
                                        var errDiv = document.createElement('div');
                                        errDiv.className = 'myd-no-orders';
                                        errDiv.textContent = 'Erro ao carregar pedidos: ' + (err && err.message ? err.message : 'unknown');
                                        body.appendChild(errDiv);
                                    });
                                
                                // back button handler: re-fetch orders for stored date
                                try{
                                    var backBtnEl = document.getElementById('myd-side-panel-back');
                                    if(backBtnEl){
                                        backBtnEl.addEventListener('click', function(ev){
                                            ev.preventDefault();
                                            if(!mydSidePanelDate) return;
                                            var body = document.getElementById('myd-side-panel-body'); if(body){ try{ var nodes = body.querySelectorAll('.myd-order-list, .myd-no-orders, .myd-side-panel-loading'); Array.prototype.forEach.call(nodes, function(n){ if(n && n.parentNode) n.parentNode.removeChild(n); }); }catch(e){} }
                                            var loading2 = document.createElement('div'); loading2.className='myd-side-panel-loading'; loading2.textContent='Carregando pedidos...';
                                            openSidePanel('Pedidos concluídos', loading2);
                                            var fd_back = new FormData(); fd_back.append('action','myd_get_orders_for_day');
                                            // if mydSidePanelDate encodes aggregation info, forward it; otherwise send as date
                                            try{
                                                var activeTabB = document.querySelector('#myd-chart-tabs .myd-chart-tab--active');
                                                var activeViewB = activeTabB ? activeTabB.getAttribute('data-view') : 'day_of_month';
                                            }catch(e){ var activeViewB = 'day_of_month'; }
                                            if(activeViewB === 'day_of_week'){
                                                fd_back.append('agg','weekday'); fd_back.append('weekday_index', mydSidePanelDate);
                                                try{ var sb=document.querySelector('input[name="myd_dashboard_start"]'); var eb=document.querySelector('input[name="myd_dashboard_end"]'); if(sb && sb.value) fd_back.append('start_date', sb.value); if(eb && eb.value) fd_back.append('end_date', eb.value); }catch(e){}
                                            } else if(activeViewB === 'hour_by_hour'){
                                                fd_back.append('agg','hour'); fd_back.append('hour', mydSidePanelDate);
                                                try{ var sb2=document.querySelector('input[name="myd_dashboard_start"]'); var eb2=document.querySelector('input[name="myd_dashboard_end"]'); if(sb2 && sb2.value) fd_back.append('start_date', sb2.value); if(eb2 && eb2.value) fd_back.append('end_date', eb2.value); }catch(e){}
                                            } else if(activeViewB === 'month_by_month'){
                                                fd_back.append('agg','month'); fd_back.append('month_label', mydSidePanelDate);
                                                try{ var sb3=document.querySelector('input[name="myd_dashboard_start"]'); var eb3=document.querySelector('input[name="myd_dashboard_end"]'); if(sb3 && sb3.value) fd_back.append('start_date', sb3.value); if(eb3 && eb3.value) fd_back.append('end_date', eb3.value); }catch(e){}
                                            } else {
                                                fd_back.append('date', mydSidePanelDate);
                                            }
                                            fetch(ajaxUrl, { method:'POST', body:fd_back, credentials:'same-origin' })
                                                .then(function(r){ if(!r.ok){ return r.text().then(function(t){ throw new Error('HTTP '+r.status+'\n'+(t||'')); }); } return r.json(); })
                                                .then(function(res){
                                                    var body = document.getElementById('myd-side-panel-body'); if(!body) return; try{ var nodes = body.querySelectorAll('.myd-order-list, .myd-no-orders, .myd-side-panel-loading'); Array.prototype.forEach.call(nodes, function(n){ if(n && n.parentNode) n.parentNode.removeChild(n); }); }catch(e){}
                                                    if(res && res.success && Array.isArray(res.data) && res.data.length){
                                                        // reuse same rendering as above: build list header + rows
                                                        var list = document.createElement('div'); list.className='myd-order-list';
                                                        var header = document.createElement('div'); header.className = 'myd-order-row myd-order-row--header';
                                                        var htime = document.createElement('div'); htime.className = 'myd-order-col myd-order-time'; htime.textContent = 'Horário';
                                                        var hnum = document.createElement('div'); hnum.className = 'myd-order-col myd-order-number'; hnum.textContent = 'Pedido';
                                                        var htotal = document.createElement('div'); htotal.className = 'myd-order-col myd-order-total'; htotal.textContent = 'Total do pedido';
                                                        header.appendChild(htime); header.appendChild(hnum); header.appendChild(htotal); list.appendChild(header);
                                                        res.data.forEach(function(o){
                                                            var item = document.createElement('div'); item.className='myd-order-row';
                                                            var colTime = document.createElement('div'); colTime.className='myd-order-col myd-order-time'; colTime.textContent = o.date_display ? (String(o.date_display).split(' ')[1]||o.date_display):'';
                                                            var colNum = document.createElement('div'); colNum.className='myd-order-col myd-order-number'; var label = document.createElement('span'); label.textContent = (o.number?String(o.number):(o.id?String(o.id):'')); label.className='myd-order-number-label myd-order-link'; if(o.id) label.dataset.orderId=o.id; colNum.appendChild(label);
                                                            var colTotal = document.createElement('div'); colTotal.className='myd-order-col myd-order-total';
                                                            try{
                                                                var tot2 = o.total ? String(o.total) : '';
                                                                if(tot2 && typeof currencySymbol !== 'undefined' && tot2.indexOf(currencySymbol) === -1){ tot2 = currencySymbol + ' ' + tot2; }
                                                                colTotal.textContent = tot2;
                                                            }catch(e){ colTotal.textContent = o.total||''; }
                                                            item.appendChild(colTime); item.appendChild(colNum); item.appendChild(colTotal);
                                                            // append end-row chevron SVG
                                                            (function(){
                                                                try{
                                                                    var svgns = 'http://www.w3.org/2000/svg';
                                                                    var wrap = document.createElement('div'); wrap.className = 'myd-order-col myd-order-end-icon';
                                                                    var svg = document.createElementNS(svgns, 'svg');
                                                                    svg.setAttribute('viewBox','0 0 24 24');
                                                                    svg.setAttribute('fill','none');
                                                                    svg.setAttribute('xmlns','http://www.w3.org/2000/svg');
                                                                    svg.setAttribute('aria-hidden','true');
                                                                    svg.setAttribute('stroke','#000000');
                                                                    svg.setAttribute('stroke-width','0.00024');

                                                                    var path = document.createElementNS(svgns, 'path');
                                                                    path.setAttribute('fill-rule','evenodd');
                                                                    path.setAttribute('clip-rule','evenodd');
                                                                    path.setAttribute('d','M8.29289 4.29289C8.68342 3.90237 9.31658 3.90237 9.70711 4.29289L16.7071 11.2929C17.0976 11.6834 17.0976 12.3166 16.7071 12.7071L9.70711 19.7071C9.31658 20.0976 8.68342 20.0976 8.29289 19.7071C7.90237 19.3166 7.90237 18.6834 8.29289 18.2929L14.5858 12L8.29289 5.70711C7.90237 5.31658 7.90237 4.68342 8.29289 4.29289Z');
                                                                    path.setAttribute('fill','#ed972b');
                                                                    svg.appendChild(path);
                                                                    wrap.appendChild(svg);
                                                                    item.appendChild(wrap);
                                                                }catch(e){/* ignore */}
                                                            })();
                                                            // Removido: não abre mais painel de detalhes ao clicar na linha do pedido
                                                            list.appendChild(item);
                                                        });
                                                        body.appendChild(list);
                                                    } else {
                                                        var none = document.createElement('div'); none.className='myd-no-orders'; none.textContent='Nenhum pedido encontrado para este dia.'; body.appendChild(none);
                                                    }
                                                }).catch(function(err){ console.error('back fetch error', err); var body = document.getElementById('myd-side-panel-body'); if(!body) return; try{ var nodes = body.querySelectorAll('.myd-order-list, .myd-no-orders, .myd-side-panel-loading'); Array.prototype.forEach.call(nodes, function(n){ if(n && n.parentNode) n.parentNode.removeChild(n); }); }catch(e){} var e = document.createElement('div'); e.className='myd-no-orders'; e.textContent='Erro ao carregar pedidos: '+(err&&err.message?err.message:'unknown'); body.appendChild(e); });
                                        });
                                    }
                                }catch(e){/* ignore */}                               
                                                    
                            }catch(e){/* ignore */}
                        });
                    }catch(e){/* ignore */}
                })();
                // Build an external, non-interactive legend (outside the canvas)
                function buildExternalLegend(chart){
                    try{
                        var container = document.getElementById('myd-chart-legend');
                        if(!container) return;
                        // clear previous
                        container.innerHTML = '';
                        if(!chart || !chart.data || !chart.data.datasets) return;
                        chart.data.datasets.forEach(function(ds, idx){
                            var item = document.createElement('div');
                            item.className = 'myd-legend-item';

                            var sw = document.createElement('span');
                            sw.className = 'myd-legend-swatch';
                            // prefer a solid color: use borderColor or fallback to backgroundColor
                            sw.style.background = ds.borderColor || ds.backgroundColor || '#ccc';

                            var lbl = document.createElement('span');
                            lbl.className = 'myd-legend-label';
                            // Compose the exact texts requested by user, bolding the day parts
                            if(idx === 0) {
                                lbl.innerHTML = 'Valor total das vendas nos <strong>últimos ' + legendNumDays + ' dias</strong>';
                            } else if (idx === 1) {
                                lbl.innerHTML = 'comparação com os <strong>' + legendNumDays + ' dias anteriores</strong>';
                            } else {
                                lbl.textContent = ds.label || ('Série ' + (idx+1));
                            }

                            item.appendChild(sw);
                            item.appendChild(lbl);
                            container.appendChild(item);
                        });
                    }catch(e){
                        // fail silently
                    }
                }

                // initial legend build
                buildExternalLegend(mydChart);
            }

            // Helper: aggregate series by weekday (Sunday=0 .. Saturday=6)
            function aggregateByWeekday(dates, values){
                var sums = [0,0,0,0,0,0,0];
                if(!dates || !values) return sums;
                for(var i=0;i<dates.length;i++){
                    var dstr = dates[i];
                    if(!dstr) continue;
                    var dt = new Date(dstr + 'T00:00:00');
                    var wd = isNaN(dt.getDay()) ? 0 : dt.getDay();
                    var v = parseFloat(values[i]) || 0;
                    sums[wd] += v;
                }
                return sums;
            }

            // Helper: aggregate by month (format 'MM/YYYY')
            function aggregateByMonth(dates, values){
                var map = {};
                var order = [];
                for(var i=0;i<dates.length;i++){
                    var dstr = dates[i];
                    if(!dstr) continue;
                    var dt = new Date(dstr + 'T00:00:00');
                    if(isNaN(dt.getTime())) continue;
                    var key = String(dt.getMonth()+1).padStart(2,'0') + '/' + dt.getFullYear();
                    if(!(key in map)){ map[key] = 0; order.push(key); }
                    map[key] += parseFloat(values[i]) || 0;
                }
                var outVals = order.map(function(k){ return map[k]; });
                return { labels: order, values: outVals };
            }

            // Switch view according to tab
            function switchChartView(view){
                if(view === 'day_of_week'){
                    var agg = aggregateByWeekday(_myd_orig.rawDates, _myd_orig.values);
                    var aggPrev = aggregateByWeekday(_myd_orig.prevDates, _myd_orig.prevValues || []);
                    var weekLabels = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
                    // update globals so tooltip reads correct counts
                    counts = agg.slice();
                    prevCounts = (aggPrev && Array.isArray(aggPrev)) ? aggPrev.slice() : new Array(7).fill(0);
                    // use weekday labels as rawDates for tooltip formatting
                    rawDates = weekLabels.slice();
                    prevDates = weekLabels.slice();
                    renderChart(weekLabels, agg, aggPrev);
                } else if(view === 'month_by_month'){
                    var res = aggregateByMonth(_myd_orig.rawDates, _myd_orig.values);
                    var resPrev = aggregateByMonth(_myd_orig.prevDates, _myd_orig.prevValues || []);
                    counts = Array.isArray(res.values) ? res.values.slice() : [];
                    prevCounts = Array.isArray(resPrev.values) ? resPrev.values.slice() : [];
                    rawDates = Array.isArray(res.labels) ? res.labels.slice() : [];
                    prevDates = Array.isArray(resPrev.labels) ? resPrev.labels.slice() : rawDates.slice();
                    renderChart(res.labels, res.values, resPrev.values);
                } else if(view === 'hour_by_hour'){
                    // Request server-side hourly aggregation via AJAX
                    var startInput = document.querySelector('input[name="myd_dashboard_start"]');
                    var endInput = document.querySelector('input[name="myd_dashboard_end"]');
                    var startVal = startInput && startInput.value ? startInput.value : serverDefaultStart;
                    var endVal = endInput && endInput.value ? endInput.value : serverDefaultEnd;
                    var formData = new FormData();
                    formData.append('action', 'myd_dashboard_filter');
                    formData.append('start_date', startVal);
                    formData.append('end_date', endVal);
                    formData.append('view', 'hour_by_hour');
                    fetch(ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
                        .then(function(r){ return r.json(); })
                        .then(function(res){
                            if(res && res.success && res.data){
                                // render chart only (do not update cards)
                                renderChart(res.data.labels || [], res.data.data || [], res.data.prev_values || []);
                                // update client-side cached arrays so tooltip and further tab switches use latest data
                                try{
                                    if(Array.isArray(res.data.data)) counts = res.data.data.slice();
                                    else counts = [];
                                    if(Array.isArray(res.data.prev_values)) prevCounts = res.data.prev_values.slice();
                                    else prevCounts = new Array((res.data.labels && res.data.labels.length) ? res.data.labels.length : 0).fill(0);
                                    if(Array.isArray(res.data.labels)) rawDates = res.data.labels.slice();
                                    if(Array.isArray(res.data.prev_labels)) prevDates = res.data.prev_labels.slice();
                                }catch(e){/* ignore */}
                            }
                        })
                        .catch(function(){
                            // on error, render empty hourly chart
                            var hours = [];
                            var prev = [];
                            for(var i=0;i<24;i++){ hours.push(0); prev.push(0); }
                            var labelsH = []; for(var h=0;h<24;h++){ labelsH.push((h<10?'0'+h:h)+'h'); }
                            renderChart(labelsH, hours, prev);
                        });
                } else {
                    // restore original day-by-day series
                    labels = _myd_orig.labels.slice();
                    values = _myd_orig.values.slice();
                    prevValues = _myd_orig.prevValues.slice();
                    rawDates = _myd_orig.rawDates.slice();
                    prevDates = _myd_orig.prevDates.slice();
                    counts = _myd_orig.counts.slice();
                    prevCounts = _myd_orig.prevCounts.slice();
                    // day_of_month - default
                    renderChart(labels, values, prevValues);
                }
            }

            // Tab click handling
            (function(){
                var tabsWrap = document.getElementById('myd-chart-tabs');
                if(!tabsWrap) return;
                tabsWrap.addEventListener('click', function(e){
                    var btn = e.target.closest('.myd-chart-tab');
                    if(!btn) return;
                    var all = tabsWrap.querySelectorAll('.myd-chart-tab');
                    all.forEach(function(b){ b.classList.remove('myd-chart-tab--active'); });
                    btn.classList.add('myd-chart-tab--active');
                    var view = btn.getAttribute('data-view');
                    switchChartView(view);
                });
            })();

            // Expose updateChart function globally for AJAX
            window.mydUpdateDashboard = function(data){
                // update client-side series/dates for subsequent aggregations
                if(data.days && Array.isArray(data.days)) rawDates = data.days;
                if(data.prev_days && Array.isArray(data.prev_days)) prevDates = data.prev_days;
                if(data.labels && Array.isArray(data.labels)) labels = data.labels;
                if(data.data && Array.isArray(data.data)) values = data.data;
                if(data.prev_values && Array.isArray(data.prev_values)) prevValues = data.prev_values;
                if(data.num_days) legendNumDays = data.num_days;
                if(data.counts && Array.isArray(data.counts)) counts = data.counts;
                if(data.prev_counts && Array.isArray(data.prev_counts)) prevCounts = data.prev_counts;
                    // Also refresh the "original" snapshot so aggregated views use the latest filtered dataset
                    try{
                        _myd_orig = {
                            labels: Array.isArray(labels) ? labels.slice() : [],
                            values: Array.isArray(values) ? values.slice() : [],
                            prevValues: Array.isArray(prevValues) ? prevValues.slice() : [],
                            rawDates: Array.isArray(rawDates) ? rawDates.slice() : [],
                            prevDates: Array.isArray(prevDates) ? prevDates.slice() : [],
                            counts: Array.isArray(counts) ? counts.slice() : [],
                            prevCounts: Array.isArray(prevCounts) ? prevCounts.slice() : []
                        };
                    }catch(e){}
                // Update total
                var priceSpan = document.getElementById('myd-card-price-value');
                var deltaTotalEl = document.getElementById('myd-card-total-delta');
                if ( priceSpan ) priceSpan.textContent = data.total_formatted;
                if ( deltaTotalEl && data.delta_total_formatted !== undefined ) {
                    deltaTotalEl.className = 'myd-card-delta myd-card-delta--' + (data.delta_total_direction || 'neutral');
                    var val = deltaTotalEl.querySelector('.myd-delta-value'); if ( val ) val.textContent = data.delta_total_formatted;
                    var arr = deltaTotalEl.querySelector('.myd-delta-arrow'); if ( arr ) arr.textContent = (data.delta_total_direction === 'up' ? '▲' : (data.delta_total_direction === 'down' ? '▼' : '—'));
                }

                // Update avg
                var avgVal = document.getElementById('myd-card-avg-value');
                var avgDelta = document.getElementById('myd-card-avg-delta');
                if ( avgVal ) avgVal.textContent = data.avg_formatted;
                if ( avgDelta && data.delta_avg_formatted !== undefined ) {
                    avgDelta.className = 'myd-card-delta myd-card-delta--' + (data.delta_avg_direction || 'neutral');
                    var v = avgDelta.querySelector('.myd-delta-value'); if ( v ) v.textContent = data.delta_avg_formatted;
                    var a = avgDelta.querySelector('.myd-delta-arrow'); if ( a ) a.textContent = (data.delta_avg_direction === 'up' ? '▲' : (data.delta_avg_direction === 'down' ? '▼' : '—'));
                }

                // Update count
                var countVal = document.getElementById('myd-card-count-value');
                var countDelta = document.getElementById('myd-card-count-delta');
                if ( countVal ) countVal.textContent = data.count_formatted;
                if ( countDelta && data.delta_count_formatted !== undefined ) {
                    countDelta.className = 'myd-card-delta myd-card-delta--' + (data.delta_count_direction || 'neutral');
                    var v2 = countDelta.querySelector('.myd-delta-value'); if ( v2 ) v2.textContent = data.delta_count_formatted;
                    var a2 = countDelta.querySelector('.myd-delta-arrow'); if ( a2 ) a2.textContent = (data.delta_count_direction === 'up' ? '▲' : (data.delta_count_direction === 'down' ? '▼' : '—'));
                }

                // Update economy
                var econVal = document.getElementById('myd-card-economy-value');
                var econDelta = document.getElementById('myd-card-economy-delta');
                if ( econVal ) econVal.textContent = data.economy_formatted;
                if ( econDelta && data.delta_economy_formatted !== undefined ) {
                    econDelta.className = 'myd-card-delta myd-card-delta--' + (data.delta_economy_direction || 'neutral');
                    var v3 = econDelta.querySelector('.myd-delta-value'); if ( v3 ) v3.textContent = data.delta_economy_formatted;
                    var a3 = econDelta.querySelector('.myd-delta-arrow'); if ( a3 ) a3.textContent = (data.delta_economy_direction === 'up' ? '▲' : (data.delta_economy_direction === 'down' ? '▼' : '—'));
                }
                // Update chart (pass previous values if provided)
                renderChart(data.labels, data.data, data.prev_values || prevValues);

                // Update orders chart (análise de pedidos) - atualizar variáveis globais
                if(data.labels && Array.isArray(data.labels)) window.mydOrderLabels = data.labels;
                if(data.counts && Array.isArray(data.counts)) window.mydOrderCounts = data.counts;
                if(data.prev_counts && Array.isArray(data.prev_counts)) window.mydPrevOrderCounts = data.prev_counts;
                if(data.days && Array.isArray(data.days)) window.mydOrderRawDates = data.days;
                if(data.prev_days && Array.isArray(data.prev_days)) window.mydPrevOrderDates = data.prev_days;
                if(data.num_days) window.mydOrdersLegendNumDays = data.num_days;

                // Render orders chart with new data
                if(typeof window.mydRenderOrdersChart === 'function'){
                    window.mydRenderOrdersChart(
                        data.labels || window.mydOrderLabels,
                        data.counts || window.mydOrderCounts,
                        data.prev_counts || window.mydPrevOrderCounts
                    );
                }

                // Update orders section card
                var ordersCountVal = document.getElementById('myd-orders-card-count-value');
                var ordersCountDelta = document.getElementById('myd-orders-card-count-delta');
                if(ordersCountVal) ordersCountVal.textContent = data.count_formatted;
                if(ordersCountDelta && data.delta_count_formatted !== undefined){
                    ordersCountDelta.className = 'myd-card-delta myd-card-delta--' + (data.delta_count_direction || 'neutral');
                    var vOrders = ordersCountDelta.querySelector('.myd-delta-value'); if(vOrders) vOrders.textContent = data.delta_count_formatted;
                    var aOrders = ordersCountDelta.querySelector('.myd-delta-arrow'); if(aOrders) aOrders.textContent = (data.delta_count_direction === 'up' ? '▲' : (data.delta_count_direction === 'down' ? '▼' : '—'));
                }

                 // Update insights charts (Origin, Payments and Delivery Times)
                if(typeof window.mydDashboardData === 'object'){
                    if(data.delivery_avg_overall !== undefined){
                        window.mydDashboardData.delivery_avg_overall = data.delivery_avg_overall;
                    }
                    if(data.delivery_ontime_pct !== undefined){
                        window.mydDashboardData.delivery_ontime_pct = data.delivery_ontime_pct;
                    }
                }
                if(typeof window.mydRenderInsightsCharts === 'function' && (data.channels || data.payments || data.delivery_times || data.delivery_avg_overall !== undefined || data.delivery_ontime_pct !== undefined)){
                     window.mydRenderInsightsCharts(data.channels, data.payments, data.delivery_times);
                 }
            };


            function init(){
                try{
                    var activeTab = document.querySelector('#myd-chart-tabs .myd-chart-tab--active');
                    var activeView = activeTab ? activeTab.getAttribute('data-view') : null;
                    if(activeView){
                        // let switchChartView handle hourly AJAX when needed
                        switchChartView(activeView);
                        return;
                    }
                }catch(e){}
                renderChart(labels, values, prevValues);
            }
            document.addEventListener('DOMContentLoaded', init);
            if ( document.readyState === 'complete' ) init();
        })();
        </script>

            <!-- Script de testes: renderiza charts de insights com dados aleatórios -->
            <script>
            (function(){
                var mydOriginChart = null;
                var mydPaymentChart = null;

                // Gera dados aleatórios para teste (será substituído pela lógica real no futuro)
                function rndInt(min,max){ return Math.floor(Math.random()*(max-min+1))+min; }

                // Dados iniciais injetados pelo PHP
                window.mydDashboardData = <?php echo wp_json_encode($dashboard_data); ?>;

                // Configuração base (labels e cores)
                var originsLabels = ['Cardápio','WhatsApp','iFood'];
                var originColors = ['#ed972b','#25D366','#ff4d4f'];

                var paymentMethodsList = ['DIN','CRD','DEB','VRF','ONLINE'];
                var paymentLabelsMap = {
                    'DIN': 'Dinheiro',
                    'CRD': 'Crédito',
                    'DEB': 'Débito',
                    'VRF': 'VR',
                    'ONLINE': 'Online'
                };
                var paymentsLabels = paymentMethodsList.map(function(m){ return paymentLabelsMap[m] || m; });

                // Tempo de entrega por dia da semana
                var deliveryWeekdayLabels = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
                var mydDeliveryChart = null;

                function renderInsightsCharts(channelsData, paymentsData, deliveryTimesData){
                    try{
                        // 1. Processar dados de Origem
                        // Se não fornecido, tenta pegar do window.mydDashboardData
                        var cData = channelsData;
                        if(!cData && window.mydDashboardData && window.mydDashboardData.channels) {
                            cData = window.mydDashboardData.channels;
                        }
                        var originValues = cData ? Object.values(cData) : [];
                        // Fallback se vazio
                        if(originValues.length === 0) originValues = [0,0,0,0];

                        // Calcular e exibir a origem mais usada
                        var mostUsedOriginEl = document.getElementById('mydOriginMostUsedValue');
                        if(mostUsedOriginEl){
                            var maxOriginValue = 0;
                            var mostUsedOriginIdx = -1;
                            for(var oi = 0; oi < originValues.length; oi++){
                                if(originValues[oi] > maxOriginValue){
                                    maxOriginValue = originValues[oi];
                                    mostUsedOriginIdx = oi;
                                }
                            }
                            if(mostUsedOriginIdx >= 0 && maxOriginValue > 0){
                                mostUsedOriginEl.textContent = originsLabels[mostUsedOriginIdx];
                            } else {
                                mostUsedOriginEl.textContent = '-';
                            }
                        }

                        // Origin pie
                        var oEl = document.getElementById('mydOrdersOriginChart');
                        if(oEl && typeof Chart !== 'undefined'){
                            if(mydOriginChart) mydOriginChart.destroy();
                            mydOriginChart = new Chart(oEl.getContext('2d'), {
                                type: 'pie',
                                data: { labels: originsLabels, datasets:[{ data: originValues, backgroundColor: originColors }] },
                                options: {
                                    responsive:true,
                                    maintainAspectRatio:false,
                                    plugins:{
                                        legend:{
                                            position:'bottom',
                                            labels:{
                                                usePointStyle:true,
                                                pointStyle:'circle',
                                                boxWidth:8,
                                                boxHeight:8,
                                                padding:12
                                            }
                                        },
                                        tooltip: {
                                            enabled: false,
                                            external: function(context){
                                                var tooltipEl = document.getElementById('myd-chart-tooltip');
                                                var chart = context.chart;
                                                var canvas = chart.canvas;
                                                var parent = canvas.parentNode || document.body;

                                                if(!tooltipEl){
                                                    tooltipEl = document.createElement('div');
                                                    tooltipEl.id = 'myd-chart-tooltip';
                                                    tooltipEl.className = 'myd-chart-tooltip';
                                                    tooltipEl.style.pointerEvents = 'none';
                                                    tooltipEl.style.zIndex = 1000;
                                                    tooltipEl.style.opacity = 0;
                                                    tooltipEl.style.visibility = 'hidden';
                                                    tooltipEl.style.transition = 'opacity 120ms ease';
                                                    tooltipEl.style.position = 'absolute';
                                                }

                                                if(tooltipEl.parentNode !== parent){
                                                    parent.appendChild(tooltipEl);
                                                    if(parent.style && window.getComputedStyle(parent).position === 'static'){
                                                        parent.style.position = 'relative';
                                                    }
                                                }

                                                var tooltipModel = context.tooltip;
                                                if(tooltipModel.opacity === 0){
                                                    tooltipEl.style.opacity = 0;
                                                    tooltipEl.style.visibility = 'hidden';
                                                    return;
                                                }
                                                var point = null;
                                                if(tooltipModel.dataPoints && tooltipModel.dataPoints.length){
                                                    point = tooltipModel.dataPoints[0];
                                                }
                                                if(!point){
                                                    tooltipEl.style.opacity = 0;
                                                    return;
                                                }
                                                var idx = point.dataIndex;
                                                var label = chart.data.labels[idx] || '';
                                                var value = chart.data.datasets[0].data[idx];
                                                var total = chart.data.datasets[0].data.reduce(function(a,b){ return a + b; }, 0);
                                                var percent = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                                var html = '';
                                                html += '<div class="myd-tooltip-top">' + label + '</div>';
                                                html += '<div class="myd-tooltip-main">';
                                                html += '<div class="myd-tooltip-count">' + value + ' pedidos (' + percent + '%)</div>';
                                                html += '</div>';
                                                tooltipEl.innerHTML = html;
                                                tooltipEl.style.visibility = 'visible';
                                                tooltipEl.style.opacity = 1;

                                                var canvasRect = canvas.getBoundingClientRect();
                                                var parentRect = parent.getBoundingClientRect ? parent.getBoundingClientRect() : { left: 0, top: 0 };
                                                var caretXWithinCanvas = tooltipModel.caretX || 0;
                                                var caretYWithinCanvas = tooltipModel.caretY || 0;
                                                var canvasLeftInParent = canvasRect.left - parentRect.left;
                                                var tooltipW = tooltipEl.offsetWidth || 160;
                                                var sidePadding = 10;
                                                var parentWidth = (parent && parent.clientWidth) ? parent.clientWidth : window.innerWidth;
                                                var minPadding = 8;
                                                var maxLeft = parentWidth - tooltipW - minPadding;
                                                var showRight = (caretXWithinCanvas < (canvasRect.width / 2));
                                                var left;
                                                if(showRight){
                                                    left = Math.round(canvasLeftInParent + caretXWithinCanvas + sidePadding);
                                                } else {
                                                    left = Math.round(canvasLeftInParent + caretXWithinCanvas - tooltipW - sidePadding);
                                                }
                                                if(left < minPadding) left = minPadding;
                                                if(left > maxLeft) left = maxLeft;
                                                var canvasTopInParent = canvasRect.top - parentRect.top;
                                                var desiredTop = Math.round(canvasTopInParent + Math.max(12, caretYWithinCanvas - (tooltipEl.offsetHeight / 2)));
                                                var topFixed = desiredTop;
                                                var parentHeight = (parent && parent.clientHeight) ? parent.clientHeight : window.innerHeight;
                                                var maxTop = parentHeight - tooltipEl.offsetHeight - minPadding;
                                                if(topFixed < minPadding) topFixed = minPadding;
                                                if(topFixed > maxTop) topFixed = maxTop;
                                                tooltipEl.style.left = left + 'px';
                                                tooltipEl.style.top = topFixed + 'px';
                                                tooltipEl.style.transform = 'translateZ(0)';
                                            }
                                        }
                                    }
                                }
                            });
                        }

                        // 2. Processar dados de Pagamento
                        var pData = paymentsData;
                        if(!pData && window.mydDashboardData && window.mydDashboardData.payments) {
                            pData = window.mydDashboardData.payments;
                        }
                        var pObj = pData || {};
                        var paymentValues = paymentMethodsList.map(function(m){ return pObj[m] ? pObj[m] : 0; });
                        var paymentValuesFormatted = paymentValues.map(function(v){
                            return typeof v === 'number' ? v : 0;
                        });

                        // Calcular e exibir a forma de pagamento mais usada
                        var mostUsedPaymentEl = document.getElementById('mydPaymentMostUsedValue');
                        if(mostUsedPaymentEl){
                            var maxPaymentValue = 0;
                            var mostUsedPaymentIdx = -1;
                            for(var pi = 0; pi < paymentValuesFormatted.length; pi++){
                                if(paymentValuesFormatted[pi] > maxPaymentValue){
                                    maxPaymentValue = paymentValuesFormatted[pi];
                                    mostUsedPaymentIdx = pi;
                                }
                            }
                            if(mostUsedPaymentIdx >= 0 && maxPaymentValue > 0){
                                mostUsedPaymentEl.textContent = paymentsLabels[mostUsedPaymentIdx];
                            } else {
                                mostUsedPaymentEl.textContent = '-';
                            }
                        }

                        // Payment bar
                        var pEl = document.getElementById('mydPaymentMethodChart');
                        if(pEl && typeof Chart !== 'undefined'){
                            if(mydPaymentChart) mydPaymentChart.destroy();

                            // Calcula o limite superior do eixo Y
                            var maxValue = Math.max.apply(null, paymentValuesFormatted);
                            var yMax = 100;
                            if (maxValue > 0) {
                                var base = Math.ceil(maxValue / 50) * 50;
                                yMax = base + 50;
                            }

                            // Cores por forma de pagamento: Dinheiro, Crédito, Débito, VR, Online
                            var paymentColors = ['#8B75D8', '#29E6A5', '#FF4D4F', '#25D366', '#25A0FD'];

                            mydPaymentChart = new Chart(pEl.getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: paymentsLabels,
                                    datasets: [{
                                        label: '', 
                                        data: paymentValuesFormatted,
                                        backgroundColor: paymentColors,
                                    }]
                                },
                                options: {
                                    responsive:true,
                                    maintainAspectRatio:false,
                                    scales:{
                                        y:{
                                            beginAtZero:true,
                                            max: yMax,
                                            ticks: {
                                                callback: function(value){
                                                    return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                                                }
                                            }
                                        },
                                        x:{
                                            ticks:{
                                                maxRotation: 0,
                                                minRotation: 0,
                                                align: 'center'
                                            }
                                        }
                                    },
                                    plugins: {
                                        legend:{ display:false },
                                        tooltip: {
                                            enabled: false,
                                            external: function(context){
                                                // Tooltip customizado igual ao myd-chart-tooltip
                                                var tooltipEl = document.getElementById('myd-chart-tooltip');
                                                var chart = context.chart;
                                                var canvas = chart.canvas;
                                                var parent = canvas.parentNode || document.body;

                                                if(!tooltipEl){
                                                    tooltipEl = document.createElement('div');
                                                    tooltipEl.id = 'myd-chart-tooltip';
                                                    tooltipEl.className = 'myd-chart-tooltip';
                                                    tooltipEl.style.pointerEvents = 'none';
                                                    tooltipEl.style.zIndex = 1000;
                                                    tooltipEl.style.opacity = 0;
                                                    tooltipEl.style.visibility = 'hidden';
                                                    tooltipEl.style.transition = 'opacity 120ms ease';
                                                    tooltipEl.style.position = 'absolute';
                                                }

                                                // Garante que o tooltip esteja no container correto
                                                if(tooltipEl.parentNode !== parent){
                                                    parent.appendChild(tooltipEl);
                                                    if(parent.style && window.getComputedStyle(parent).position === 'static'){
                                                        parent.style.position = 'relative';
                                                    }
                                                }

                                                var tooltipModel = context.tooltip;
                                                if(tooltipModel.opacity === 0){
                                                    tooltipEl.style.opacity = 0;
                                                    tooltipEl.style.visibility = 'hidden';
                                                    return;
                                                }
                                                var chart = context.chart;
                                                var point = null;
                                                if(tooltipModel.dataPoints && tooltipModel.dataPoints.length){
                                                    point = tooltipModel.dataPoints[0];
                                                }
                                                if(!point){
                                                    tooltipEl.style.opacity = 0;
                                                    return;
                                                }
                                                var idx = point.dataIndex;
                                                var label = chart.data.labels[idx] || '';
                                                var value = chart.data.datasets[0].data[idx];
                                                function fmtCurrency(v){
                                                    var n = parseFloat(v) || 0;
                                                    var s = n.toFixed(2).replace('.', ',');
                                                    return 'R$ ' + s;
                                                }
                                                var html = '';
                                                html += '<div class="myd-tooltip-top">' + label + '</div>';
                                                html += '<div class="myd-tooltip-main">';
                                                html += '<div class="myd-tooltip-count">' + fmtCurrency(value) + '</div>';
                                                html += '</div>';
                                                tooltipEl.innerHTML = html;
                                                tooltipEl.style.visibility = 'visible';
                                                tooltipEl.style.opacity = 1;

                                                // Posicionamento do tooltip
                                                var canvas = chart.canvas;
                                                var canvasParent = canvas.parentNode || document.body;
                                                var canvasRect = canvas.getBoundingClientRect();
                                                var parentRect = canvasParent.getBoundingClientRect ? canvasParent.getBoundingClientRect() : { left: 0, top: 0 };
                                                var caretXWithinCanvas = tooltipModel.caretX || 0;
                                                var caretYWithinCanvas = tooltipModel.caretY || 0;
                                                var canvasLeftInParent = canvasRect.left - parentRect.left;
                                                var tooltipW = tooltipEl.offsetWidth || 160;
                                                var sidePadding = 10;
                                                var parentWidth = (canvasParent && canvasParent.clientWidth) ? canvasParent.clientWidth : window.innerWidth;
                                                var minPadding = 8;
                                                var maxLeft = parentWidth - tooltipW - minPadding;
                                                var showRight = (caretXWithinCanvas < (canvasRect.width / 2));
                                                var left;
                                                if(showRight){
                                                    left = Math.round(canvasLeftInParent + caretXWithinCanvas + sidePadding);
                                                } else {
                                                    left = Math.round(canvasLeftInParent + caretXWithinCanvas - tooltipW - sidePadding);
                                                }
                                                if(left < minPadding) left = minPadding;
                                                if(left > maxLeft) left = maxLeft;
                                                var canvasTopInParent = canvasRect.top - parentRect.top;
                                                var desiredTop = Math.round(canvasTopInParent + Math.max(12, caretYWithinCanvas - (tooltipEl.offsetHeight / 2)));
                                                var topFixed = desiredTop;
                                                var parentHeight = (canvasParent && canvasParent.clientHeight) ? canvasParent.clientHeight : window.innerHeight;
                                                var maxTop = parentHeight - tooltipEl.offsetHeight - minPadding;
                                                if(topFixed < minPadding) topFixed = minPadding;
                                                if(topFixed > maxTop) topFixed = maxTop;
                                                tooltipEl.style.left = left + 'px';
                                                tooltipEl.style.top = topFixed + 'px';
                                                tooltipEl.style.transform = 'translateZ(0)';
                                            }
                                        }
                                    }
                                }
                            });
                        }

                        // Delivery time (bar) - Tempo médio de entrega por dia da semana
                        var dEl = document.getElementById('mydDeliveryTimeChart');
                        if(dEl && typeof Chart !== 'undefined'){
                            function formatMinutes(min){
                                var m = parseInt(min, 10) || 0;
                                if(m <= 0) return '0 min';
                                var h = Math.floor(m / 60);
                                var r = m % 60;
                                var out = '';
                                if(h > 0) out += h + 'h ';
                                if(r > 0) out += r + 'm';
                                return out.trim();
                            }
                            // Obter dados de tempo de entrega - prioriza parâmetro, depois window.mydDashboardData
                            var dtData = deliveryTimesData;
                            if(!dtData && window.mydDashboardData && window.mydDashboardData.delivery_times) {
                                dtData = window.mydDashboardData.delivery_times;
                            }
                            dtData = dtData || {};
                            var avgOverall = 0;
                            var ontimePct = 0;
                            if(window.mydDashboardData && typeof window.mydDashboardData.delivery_avg_overall !== 'undefined'){
                                avgOverall = window.mydDashboardData.delivery_avg_overall;
                            }
                            if(window.mydDashboardData && typeof window.mydDashboardData.delivery_ontime_pct !== 'undefined'){
                                ontimePct = window.mydDashboardData.delivery_ontime_pct;
                            }
                            var avgEl = document.getElementById('mydDeliveryAvgValue');
                            if(avgEl){
                                avgEl.textContent = formatMinutes(avgOverall);
                            }
                            var ontimeEl = document.getElementById('mydDeliveryOntimeValue');
                            if(ontimeEl){
                                var pctText = (parseFloat(ontimePct) || 0).toFixed(2).replace('.', ',') + '%';
                                ontimeEl.textContent = pctText;
                            }
                            // Reorganizar para ordem Seg-Dom (backend retorna 0=Dom, 1=Seg, ..., 6=Sab)
                            var deliveryValues = [
                                dtData[1] || 0, // Seg
                                dtData[2] || 0, // Ter
                                dtData[3] || 0, // Qua
                                dtData[4] || 0, // Qui
                                dtData[5] || 0, // Sex
                                dtData[6] || 0, // Sab
                                dtData[0] || 0  // Dom
                            ];
                            
                            // Calcular o limite superior do eixo Y (múltiplo de 20, com margem)
                            var maxDeliveryValue = Math.max.apply(null, deliveryValues);
                            var yMaxDelivery = 80; // mínimo
                            if (maxDeliveryValue > 0) {
                                yMaxDelivery = Math.ceil(maxDeliveryValue / 20) * 20 + 20;
                            }

                            if(mydDeliveryChart) mydDeliveryChart.destroy();
                            mydDeliveryChart = new Chart(dEl.getContext('2d'), {
                                type: 'bar',
                                data: { 
                                    labels: deliveryWeekdayLabels, 
                                    datasets:[{ 
                                        label: 'Tempo médio', 
                                        data: deliveryValues, 
                                        backgroundColor: '#8B75D8' 
                                    }] 
                                },
                                options: { 
                                    responsive:true, 
                                    maintainAspectRatio:false, 
                                    layout: {
                                        padding: { left: 12 }
                                    },
                                    scales:{ 
                                        y:{ 
                                            beginAtZero:true,
                                            max: yMaxDelivery,
                                            ticks: {
                                                stepSize: 20,
                                                padding: 8,
                                                callback: function(value){
                                                    return value + ' min';
                                                }
                                            }
                                        } 
                                    }, 
                                    plugins:{ 
                                        legend:{ display:false },
                                        tooltip: {
                                            enabled: false,
                                            external: function(context){
                                                var tooltipEl = document.getElementById('myd-chart-tooltip');
                                                var chart = context.chart;
                                                var canvas = chart.canvas;
                                                var parent = canvas.parentNode || document.body;

                                                if(!tooltipEl){
                                                    tooltipEl = document.createElement('div');
                                                    tooltipEl.id = 'myd-chart-tooltip';
                                                    tooltipEl.className = 'myd-chart-tooltip';
                                                    tooltipEl.style.pointerEvents = 'none';
                                                    tooltipEl.style.zIndex = 1000;
                                                    tooltipEl.style.opacity = 0;
                                                    tooltipEl.style.visibility = 'hidden';
                                                    tooltipEl.style.transition = 'opacity 120ms ease';
                                                    tooltipEl.style.position = 'absolute';
                                                }

                                                if(tooltipEl.parentNode !== parent){
                                                    parent.appendChild(tooltipEl);
                                                    if(parent.style && window.getComputedStyle(parent).position === 'static'){
                                                        parent.style.position = 'relative';
                                                    }
                                                }

                                                var tooltipModel = context.tooltip;
                                                if(tooltipModel.opacity === 0){
                                                    tooltipEl.style.opacity = 0;
                                                    tooltipEl.style.visibility = 'hidden';
                                                    return;
                                                }
                                                var point = null;
                                                if(tooltipModel.dataPoints && tooltipModel.dataPoints.length){
                                                    point = tooltipModel.dataPoints[0];
                                                }
                                                if(!point){
                                                    tooltipEl.style.opacity = 0;
                                                    return;
                                                }
                                                var idx = point.dataIndex;
                                                var label = chart.data.labels[idx] || '';
                                                var value = chart.data.datasets[0].data[idx];
                                                // Função para formatar minutos em "1h 23m"
                                                // (reuse local formatMinutes)
                                                // Mapear nomes completos dos dias da semana
                                                var weekdayFull = [
                                                    'Domingo',
                                                    'Segunda-feira',
                                                    'Terça-feira',
                                                    'Quarta-feira',
                                                    'Quinta-feira',
                                                    'Sexta-feira',
                                                    'Sábado'
                                                ];
                                                // O backend envia os dados na ordem: Seg, Ter, Qua, Qui, Sex, Sab, Dom
                                                // O índice 0 do gráfico é Segunda-feira (1), ... índice 6 é Domingo (0)
                                                var weekdayMap = [1,2,3,4,5,6,0];
                                                var fullWeekday = weekdayFull[weekdayMap[idx]] || label;
                                                var html = '';
                                                html += '<div class="myd-tooltip-top">' + fullWeekday + '</div>';
                                                html += '<div class="myd-tooltip-main">';
                                                html += '<div class="myd-tooltip-count">' + formatMinutes(value) + '</div>';
                                                html += '</div>';
                                                tooltipEl.innerHTML = html;
                                                tooltipEl.style.visibility = 'visible';
                                                tooltipEl.style.opacity = 1;

                                                var canvasRect = canvas.getBoundingClientRect();
                                                var parentRect = parent.getBoundingClientRect ? parent.getBoundingClientRect() : { left: 0, top: 0 };
                                                var caretXWithinCanvas = tooltipModel.caretX || 0;
                                                var caretYWithinCanvas = tooltipModel.caretY || 0;
                                                var canvasLeftInParent = canvasRect.left - parentRect.left;
                                                var tooltipW = tooltipEl.offsetWidth || 160;
                                                var sidePadding = 10;
                                                var parentWidth = (parent && parent.clientWidth) ? parent.clientWidth : window.innerWidth;
                                                var minPadding = 8;
                                                var maxLeft = parentWidth - tooltipW - minPadding;
                                                var showRight = (caretXWithinCanvas < (canvasRect.width / 2));
                                                var left;
                                                if(showRight){
                                                    left = Math.round(canvasLeftInParent + caretXWithinCanvas + sidePadding);
                                                } else {
                                                    left = Math.round(canvasLeftInParent + caretXWithinCanvas - tooltipW - sidePadding);
                                                }
                                                if(left < minPadding) left = minPadding;
                                                if(left > maxLeft) left = maxLeft;
                                                var canvasTopInParent = canvasRect.top - parentRect.top;
                                                var desiredTop = Math.round(canvasTopInParent + Math.max(12, caretYWithinCanvas - (tooltipEl.offsetHeight / 2)));
                                                var topFixed = desiredTop;
                                                var parentHeight = (parent && parent.clientHeight) ? parent.clientHeight : window.innerHeight;
                                                var maxTop = parentHeight - tooltipEl.offsetHeight - minPadding;
                                                if(topFixed < minPadding) topFixed = minPadding;
                                                if(topFixed > maxTop) topFixed = maxTop;
                                                tooltipEl.style.left = left + 'px';
                                                tooltipEl.style.top = topFixed + 'px';
                                                tooltipEl.style.transform = 'translateZ(0)';
                                            }
                                        }
                                    } 
                                }
                            });
                        }
                        // Click handler para abrir painel lateral com pedidos do dia da semana
                        if (dEl) {
                            dEl.addEventListener('click', function(evt) {
                                if (!mydDeliveryChart) return;
                                var points = mydDeliveryChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
                                if (points && points.length > 0) {
                                    var idx = points[0].index;
                                    // Mapear idx (0=Seg, ..., 6=Dom) para weekday_index do PHP (0=Dom, 1=Seg, ...)
                                    var weekday_map = [1,2,3,4,5,6,0];
                                    var weekday_index = weekday_map[idx];
                                    var weekday_full = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
                                    var full_weekday = weekday_full[weekday_index] || '';
                                    var avg_minutes = 0;
                                    try{
                                        avg_minutes = mydDeliveryChart.data && mydDeliveryChart.data.datasets && mydDeliveryChart.data.datasets[0] ? mydDeliveryChart.data.datasets[0].data[idx] : 0;
                                    }catch(e){ avg_minutes = 0; }
                                    function formatMinutes(min){
                                        var m = parseInt(min, 10) || 0;
                                        if(m <= 0) return '0 min';
                                        var h = Math.floor(m / 60);
                                        var r = m % 60;
                                        var out = '';
                                        if(h > 0) out += h + 'h ';
                                        if(r > 0) out += r + 'm';
                                        return out.trim();
                                    }
                                    var avg_formatted = formatMinutes(avg_minutes);
                                    // Pega o range de datas do filtro
                                    var sIn = document.querySelector('input[name="myd_dashboard_start"]');
                                    var eIn = document.querySelector('input[name="myd_dashboard_end"]');
                                    var startVal = sIn && sIn.value ? sIn.value : '';
                                    var endVal = eIn && eIn.value ? eIn.value : '';
                                    // Abre painel imediatamente
                                    if (typeof window.mydOpenSidePanel === 'function') {
                                        var loadingNode = document.createElement('div');
                                        loadingNode.className = 'myd-side-panel-loading';
                                        loadingNode.textContent = 'Carregando pedidos...';
                                        window.mydOpenSidePanel('Pedidos do dia da semana', loadingNode, {
                                            subtitle: full_weekday,
                                            metric: avg_formatted
                                        });
                                    }
                                    // AJAX para buscar pedidos do dia da semana
                                    var fd = new FormData();
                                    fd.append('action', 'myd_get_orders_for_day');
                                    fd.append('agg', 'weekday');
                                    fd.append('weekday_index', weekday_index);
                                    if(startVal) fd.append('start_date', startVal);
                                    if(endVal) fd.append('end_date', endVal);
                                    fetch(window.mydAjaxUrl || '', { method: 'POST', body: fd, credentials: 'same-origin' })
                                        .then(function(r){ return r.json(); })
                                        .then(function(res){
                                            if(typeof window.mydRenderOrdersList === 'function'){
                                                window.mydRenderOrdersList(res);
                                            }
                                        });
                                }
                            });
                        }
                    }catch(e){ console.error('Erro ao renderizar charts de insights', e); }
                }

                // Expor globalmente
                window.mydRenderInsightsCharts = renderInsightsCharts;

                // Renderiza após DOM pronto e quando Chart.js estiver disponível
                function waitAndRender(){
                    if(typeof Chart === 'undefined') return setTimeout(waitAndRender, 200);
                    renderInsightsCharts();
                }
                if(document.readyState === 'complete' || document.readyState === 'interactive') waitAndRender();
                else document.addEventListener('DOMContentLoaded', waitAndRender);
            })();
            </script>

        ?>

        <link rel="stylesheet" href="<?php echo esc_url( MYD_PLUGN_URL . 'assets/css/myd-settings-modal.css' ); ?>" />
        <script>
        (function(){
            var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
            var mydSettingsNonce = '<?php echo wp_create_nonce( 'myd-settings' ); ?>';
            var userName = <?php echo wp_json_encode( $user_name ); ?>;
            var userEmail = <?php echo wp_json_encode( $current_user->user_email ); ?>;
            var userAvatar = <?php echo wp_json_encode( esc_url( $user_avatar ) ); ?>;
            var firstName = userName.split(' ')[0];

            var settingsBtn = document.getElementById('myd-sidebar-settings');
            if ( settingsBtn ) {
                settingsBtn.addEventListener('click', function(e){
                    e.preventDefault();
                    openMydSettingsModal();
                });
            }

            function openMydSettingsModal(){
                if ( document.getElementById('myd-settings-modal') ) return;

                var overlay = document.createElement('div');
                overlay.id = 'myd-settings-modal';
                overlay.className = 'myd-settings-modal-bg';

                var modal = document.createElement('div');
                modal.className = 'myd-settings-modal';
                modal.setAttribute('role', 'dialog');
                modal.setAttribute('aria-modal', 'true');
                modal.setAttribute('aria-label', 'Configurações');

                // Close button
                var closeBtn = document.createElement('button');
                closeBtn.className = 'myd-settings-modal__close';
                closeBtn.setAttribute('aria-label', 'Fechar modal');
                closeBtn.innerHTML = '<svg fill="currentColor" viewBox="-3.5 0 19 19" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11.383 13.644A1.03 1.03 0 0 1 9.928 15.1L6 11.172 2.072 15.1a1.03 1.03 0 1 1-1.455-1.456l3.928-3.928L.617 5.79a1.03 1.03 0 1 1 1.455-1.456L6 8.261l3.928-3.928a1.03 1.03 0 0 1 1.455 1.456L7.455 9.716z"></path></svg>';
                closeBtn.addEventListener('click', function(){ overlay.remove(); });

                // Sidebar
                var sidebar = document.createElement('div');
                sidebar.className = 'myd-settings-modal__sidebar';

                sidebar.innerHTML =
                    '<div class="myd-settings-modal__sidebar-inner">' +
                        '<div class="myd-settings-modal__avatar-wrapper" id="myd-settings-avatar-wrapper">' +
                            '<img src="' + escapeHtml(userAvatar) + '" alt="Avatar" class="myd-settings-modal__sidebar-avatar" id="myd-settings-sidebar-avatar">' +
                            '<div class="myd-settings-modal__avatar-edit">' +
                                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="#fff"></path></svg>' +
                            '</div>' +
                            '<input type="file" id="myd-settings-avatar-input" accept="image/png,image/jpeg" style="display:none">' +
                        '</div>' +
                        '<div class="myd-settings-modal__greeting">Olá ' + escapeHtml(firstName) + '</div>' +
                        '<div class="myd-settings-modal__email">' + escapeHtml(userEmail) + '</div>' +
                    '</div>' +
                    '<div class="myd-settings-modal__menu">' +
                        '<button class="myd-settings-modal__menu-btn active" data-target="settings-tab-pass">' +
                            '<svg height="24" width="24" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M16 5.5C16 8.53757 13.5376 11 10.5 11H7V13H5V15L4 16H0V12L5.16351 6.83649C5.0567 6.40863 5 5.96094 5 5.5C5 2.46243 7.46243 0 10.5 0C13.5376 0 16 2.46243 16 5.5ZM13 4C13 4.55228 12.5523 5 12 5C11.4477 5 11 4.55228 11 4C11 3.44772 11.4477 3 12 3C12.5523 3 13 3.44772 13 4Z" fill="#454545"></path></svg>' +
                            '<span>Redefinir senha</span>' +
                        '</button>'
                    '</div>';

                // Content area
                var content = document.createElement('div');
                content.className = 'myd-settings-modal__content';

                content.innerHTML =
                    '<div id="settings-tab-pass" class="myd-settings-tab active">' +
                        // Step 1: Send code
                        '<div id="myd-step-send" class="myd-settings-step">' +
                            '<div class="myd-settings-modal__title">Redefinir senha</div>' +
                            '<div class="myd-settings-modal__desc">Enviaremos um código ao seu e-mail para validar a alteração.</div>' +
                            '<div class="myd-settings-modal__form">' +
                                '<button id="myd-send-code" class="myd-settings-modal__save-btn">Enviar código por e-mail</button>' +
                                '<div id="myd-settings-msg" class="myd-settings-modal__msg"></div>' +
                            '</div>' +
                        '</div>' +
                        // Step 2: Enter code (OTP style)
                        '<div id="myd-step-code" class="myd-settings-step" style="display:none">' +
                            '<div class="myd-settings-modal__desc">Um código de 6 dígitos foi enviado para<br><strong>' + escapeHtml(userEmail) + '</strong>.<br>Digite abaixo para continuar:</div>' +
                            '<div class="myd-settings-modal__form">' +
                                '<div class="myd-otp-wrapper">' +
                                    '<input class="myd-otp-input" type="text" inputmode="numeric" maxlength="1" data-idx="0" autocomplete="off">' +
                                    '<input class="myd-otp-input" type="text" inputmode="numeric" maxlength="1" data-idx="1" autocomplete="off">' +
                                    '<input class="myd-otp-input" type="text" inputmode="numeric" maxlength="1" data-idx="2" autocomplete="off">' +
                                    '<span class="myd-otp-dash">-</span>' +
                                    '<input class="myd-otp-input" type="text" inputmode="numeric" maxlength="1" data-idx="3" autocomplete="off">' +
                                    '<input class="myd-otp-input" type="text" inputmode="numeric" maxlength="1" data-idx="4" autocomplete="off">' +
                                    '<input class="myd-otp-input" type="text" inputmode="numeric" maxlength="1" data-idx="5" autocomplete="off">' +
                                '</div>' +
                                '<div id="myd-code-msg" class="myd-settings-modal__msg">Digite o código de 6 dígitos enviado para seu e-mail.</div>' +
                                '<button id="myd-verify-code" class="myd-settings-modal__save-btn">Validar código</button>' +
                            '</div>' +
                        '</div>' +
                        // Step 3: New password
                        '<div id="myd-step-password" class="myd-settings-step" style="display:none">' +
                            '<div class="myd-settings-modal__title">Nova senha</div>' +
                            '<div class="myd-settings-modal__desc">Código validado com sucesso. Digite sua nova senha.</div>' +
                            '<div class="myd-settings-modal__form">' +
                                '<div class="myd-settings-input-wrapper myd-settings-input-wrapper--pass">' +
                                    '<label class="myd-settings-modal__label" for="myd-new-pass">Nova senha</label>' +
                                    '<input class="myd-settings-modal__input" id="myd-new-pass" type="password" placeholder="Mínimo 6 caracteres" minlength="6">' +
                                    '<button type="button" class="myd-pass-eye" data-target="myd-new-pass" aria-label="Mostrar senha">' +
                                        '<svg class="myd-eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" fill="#999"/></svg>' +
                                        '<svg class="myd-eye-closed" style="display:none" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z" fill="#999"/></svg>' +
                                    '</button>' +
                                '</div>' +
                                '<div class="myd-settings-input-wrapper myd-settings-input-wrapper--pass">' +
                                    '<label class="myd-settings-modal__label" for="myd-confirm-pass">Confirmar senha</label>' +
                                    '<input class="myd-settings-modal__input" id="myd-confirm-pass" type="password" placeholder="Repita a senha">' +
                                    '<button type="button" class="myd-pass-eye" data-target="myd-confirm-pass" aria-label="Mostrar senha">' +
                                        '<svg class="myd-eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" fill="#999"/></svg>' +
                                        '<svg class="myd-eye-closed" style="display:none" width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z" fill="#999"/></svg>' +
                                    '</button>' +
                                '</div>' +
                                '<button id="myd-update-pass" class="myd-settings-modal__save-btn">Atualizar senha</button>' +
                                '<div id="myd-pass-msg" class="myd-settings-modal__msg"></div>' +
                            '</div>' +
                        '</div>' +
                    '</div>';

                modal.appendChild(closeBtn);
                modal.appendChild(sidebar);
                modal.appendChild(content);
                overlay.appendChild(modal);
                document.body.appendChild(overlay);

                // Tab navigation handlers
                var menuBtns = sidebar.querySelectorAll('.myd-settings-modal__menu-btn');
                menuBtns.forEach(function(btn){
                    btn.addEventListener('click', function(){
                        menuBtns.forEach(function(b){ b.classList.remove('active'); });
                        btn.classList.add('active');
                        var targetId = btn.getAttribute('data-target');
                        content.querySelectorAll('.myd-settings-tab').forEach(function(tab){
                            tab.classList.toggle('active', tab.id === targetId);
                        });
                    });
                });

                // Step 1: Send code
                document.getElementById('myd-send-code').addEventListener('click', function(){
                    var btn = this; btn.disabled = true; btn.textContent = 'Enviando...';
                    var fd = new FormData(); fd.append('action','myd_send_reset_code'); fd.append('nonce', mydSettingsNonce);
                    fetch(ajaxUrl, { method:'POST', body: fd, credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(res){
                        btn.disabled = false; btn.textContent = 'Enviar código por e-mail';
                        if(res && res.success){
                            // Go to Step 2
                            document.getElementById('myd-step-send').style.display = 'none';
                            document.getElementById('myd-step-code').style.display = 'block';
                            // Focus first OTP input
                            var firstOtp = document.querySelector('.myd-otp-input[data-idx="0"]');
                            if(firstOtp) firstOtp.focus();
                        } else {
                            var msg = document.getElementById('myd-settings-msg');
                            msg.className = 'myd-settings-modal__msg myd-settings-modal__msg--error';
                            msg.textContent = (res && res.data && res.data.message) ? res.data.message : 'Erro ao enviar código.';
                        }
                    }).catch(function(){ btn.disabled = false; btn.textContent = 'Enviar código por e-mail'; var msg = document.getElementById('myd-settings-msg'); msg.className='myd-settings-modal__msg myd-settings-modal__msg--error'; msg.textContent = 'Erro de rede.'; });
                });

                // OTP input logic
                var otpInputs = document.querySelectorAll('.myd-otp-input');
                otpInputs.forEach(function(inp, idx){
                    inp.addEventListener('input', function(e){
                        this.value = this.value.replace(/[^0-9]/g, '');
                        if(this.value.length === 1 && idx < 5){
                            otpInputs[idx + 1].focus();
                        }
                    });
                    inp.addEventListener('keydown', function(e){
                        if(e.key === 'Backspace' && !this.value && idx > 0){
                            otpInputs[idx - 1].focus();
                            otpInputs[idx - 1].value = '';
                        }
                    });
                    inp.addEventListener('paste', function(e){
                        e.preventDefault();
                        var paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                        for(var i = 0; i < paste.length; i++){
                            if(otpInputs[i]) otpInputs[i].value = paste[i];
                        }
                        if(otpInputs[Math.min(paste.length, 5)]) otpInputs[Math.min(paste.length, 5)].focus();
                    });
                });
                function getOtpCode(){
                    var code = '';
                    otpInputs.forEach(function(inp){ code += inp.value; });
                    return code;
                }

                // Password eye toggle
                document.querySelectorAll('.myd-pass-eye').forEach(function(btn){
                    btn.addEventListener('click', function(){
                        var targetId = this.getAttribute('data-target');
                        var inp = document.getElementById(targetId);
                        var isPass = inp.type === 'password';
                        inp.type = isPass ? 'text' : 'password';
                        this.querySelector('.myd-eye-open').style.display = isPass ? 'none' : 'block';
                        this.querySelector('.myd-eye-closed').style.display = isPass ? 'block' : 'none';
                    });
                });

                // Step 2: Validate code
                document.getElementById('myd-verify-code').addEventListener('click', function(){
                    var code = getOtpCode();
                    var msg = document.getElementById('myd-code-msg'); msg.textContent = '';
                    if(code.length < 6){ msg.className='myd-settings-modal__msg myd-settings-modal__msg--error'; msg.textContent='Digite o código completo de 6 dígitos.'; return; }
                    var fd = new FormData(); fd.append('action','myd_verify_reset_code'); fd.append('nonce', mydSettingsNonce); fd.append('code', code);
                    var btn = this; btn.disabled = true; btn.textContent = 'Validando...';
                    fetch(ajaxUrl, { method:'POST', body: fd, credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(res){
                        btn.disabled = false; btn.textContent = 'Validar código';
                        if(res && res.success){
                            // Go to Step 3
                            document.getElementById('myd-step-code').style.display = 'none';
                            document.getElementById('myd-step-password').style.display = 'block';
                        } else {
                            msg.className='myd-settings-modal__msg myd-settings-modal__msg--error';
                            msg.textContent = (res && res.data && res.data.message) ? res.data.message : 'Código inválido.';
                        }
                    }).catch(function(){ btn.disabled = false; btn.textContent = 'Validar código'; msg.className='myd-settings-modal__msg myd-settings-modal__msg--error'; msg.textContent='Erro de rede.'; });
                });

                // Step 3: Update password
                document.getElementById('myd-update-pass').addEventListener('click', function(){
                    var np = document.getElementById('myd-new-pass').value;
                    var cp = document.getElementById('myd-confirm-pass').value;
                    var code = getOtpCode();
                    var msg = document.getElementById('myd-pass-msg'); msg.textContent = '';
                    if(!np || !cp){ msg.className='myd-settings-modal__msg myd-settings-modal__msg--error'; msg.textContent='Preencha ambos os campos.'; return; }
                    if(np !== cp){ msg.className='myd-settings-modal__msg myd-settings-modal__msg--error'; msg.textContent='As senhas não conferem.'; return; }
                    if(np.length < 6){ msg.className='myd-settings-modal__msg myd-settings-modal__msg--error'; msg.textContent='A senha deve ter no mínimo 6 caracteres.'; return; }
                    var fd = new FormData(); fd.append('action','myd_update_password'); fd.append('nonce', mydSettingsNonce); fd.append('code', code); fd.append('new_password', np);
                    var btn = this; btn.disabled = true; btn.textContent = 'Atualizando...';
                    fetch(ajaxUrl, { method:'POST', body: fd, credentials:'same-origin' }).then(function(r){ return r.json(); }).then(function(res){ btn.disabled = false; btn.textContent = 'Atualizar senha';
                        if(res && res.success){ msg.className='myd-settings-modal__msg myd-settings-modal__msg--success'; msg.textContent='Senha atualizada com sucesso!'; }
                        else { msg.className='myd-settings-modal__msg myd-settings-modal__msg--error'; msg.textContent = (res && res.data && res.data.message) ? res.data.message : 'Erro ao atualizar senha.'; }
                    }).catch(function(){ btn.disabled = false; btn.textContent = 'Atualizar senha'; msg.className='myd-settings-modal__msg myd-settings-modal__msg--error'; msg.textContent='Erro de rede.'; });
                });

                // Helper: update avatar in page
                function updateAvatarInUI(url){
                    var imgs = document.querySelectorAll('.myd-sidebar-avatar, .myd-user-avatar, .myd-settings-modal__sidebar-avatar');
                    imgs.forEach(function(i){ i.src = url; });
                }

                // Close on overlay background click
                overlay.addEventListener('click', function(ev){ if ( ev.target === overlay ) overlay.remove(); });
            }

            function escapeHtml(str){
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }
        })();
        </script>

        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler: retorna lista de pedidos para uma data (YYYY-MM-DD)
     */
    public static function ajax_get_orders_for_day(){
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        if ( ! is_user_logged_in() || ( ! current_user_can( 'edit_posts' ) && ! in_array( 'marketing', $roles, true ) ) ) {
            wp_send_json_error( 'unauthorized' );
        }
        // support aggregate modes: weekday, month, hour
        $agg = isset( $_POST['agg'] ) ? sanitize_text_field( wp_unslash( $_POST['agg'] ) ) : '';
        $date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
        $start_param = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
        $end_param = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';

        // default behavior: single date in YYYY-MM-DD
        $d = '';
        if ( $agg === 'weekday' ) {
            $weekday_index = isset( $_POST['weekday_index'] ) ? intval( wp_unslash( $_POST['weekday_index'] ) ) : null;
            if ( $weekday_index === null ) wp_send_json_error( 'missing_weekday' );
            // require start/end to define the range to search
            if ( empty( $start_param ) || empty( $end_param ) ) wp_send_json_error( 'missing_range' );
            $start = $start_param . ' 00:00:00';
            $end = $end_param . ' 23:59:59';
        } elseif ( $agg === 'month' ) {
            $month_label = isset( $_POST['month_label'] ) ? sanitize_text_field( wp_unslash( $_POST['month_label'] ) ) : '';
            if ( empty( $start_param ) || empty( $end_param ) ) wp_send_json_error( 'missing_range' );
            $start = $start_param . ' 00:00:00';
            $end = $end_param . ' 23:59:59';
            // month_label is informational; we'll filter by month matching when iterating
        } elseif ( $agg === 'hour' ) {
            $hour = isset( $_POST['hour'] ) ? intval( wp_unslash( $_POST['hour'] ) ) : null;
            if ( $hour === null ) wp_send_json_error( 'missing_hour' );
            if ( empty( $start_param ) || empty( $end_param ) ) wp_send_json_error( 'missing_range' );
            $start = $start_param . ' 00:00:00';
            $end = $end_param . ' 23:59:59';
        } else {
            if ( ! $date ) wp_send_json_error( 'missing_date' );
            // accept YYYY-MM-DD at the start
            if ( ! preg_match( '/^(\d{4}-\d{2}-\d{2})/', $date, $m ) ) {
                wp_send_json_error( 'invalid_date' );
            }
            $d = $m[1];
            $start = $d . ' 00:00:00';
            $end = $d . ' 23:59:59';
        }

        $out = array();

        // ...existing code...

        if ( empty( $out ) ) {
            // fallback: query posts of type shop_order by date
            $args = array(
                'post_type' => 'shop_order',
                'post_status' => 'any',
                'posts_per_page' => 50,
                'date_query' => array( array( 'after' => $start, 'before' => $end, 'inclusive' => true ) ),
            );
            $posts = get_posts( $args );
            if ( $posts ) {
                foreach ( $posts as $p ) {
                    $id = $p->ID;
                    if ( ! self::is_order_finished( $id ) ) { continue; }
                    // aggregated filters for WP post fallback
                    if ( $agg === 'weekday' ) {
                        $wd = (int) date_i18n( 'w', strtotime( $p->post_date ) );
                        if ( $wd !== $weekday_index ) continue;
                    } elseif ( $agg === 'month' ) {
                        if ( ! empty( $month_label ) ) {
                            $mstr = date_i18n( 'm/Y', strtotime( $p->post_date ) );
                            if ( strpos( $month_label, $mstr ) === false && strpos( $mstr, $month_label ) === false ) continue;
                        }
                    } elseif ( $agg === 'hour' ) {
                        $h = (int) date_i18n( 'G', strtotime( $p->post_date ) );
                        if ( $h !== $hour ) continue;
                    }
                    $meta_total = get_post_meta( $id, '_order_total', true );
                    // Tempo de entrega em minutos
                    $delivery_time = get_post_meta( $id, 'order_delivery_time', true );
                    $delivery_time_minutes = null;
                    if ( ! empty( $delivery_time ) ) {
                        // Se for uma data/hora (ex: 02-02-2026 10:36), calcula diferença para post_date
                        if ( preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})[\sT](\d{2}):(\d{2})/', $delivery_time, $dt_match ) ) {
                            $entrega_ts = strtotime(sprintf('%04d-%02d-%02d %02d:%02d:00', $dt_match[3], $dt_match[2], $dt_match[1], $dt_match[4], $dt_match[5]));
                            $pedido_ts = strtotime($p->post_date);
                            if ($entrega_ts && $pedido_ts && $entrega_ts > $pedido_ts) {
                                $delivery_time_minutes = round(($entrega_ts - $pedido_ts) / 60);
                            }
                        } else {
                            $minutes = 0;
                            if ( preg_match( '/(\d+)\s*h/i', $delivery_time, $h_match ) ) {
                                $minutes += intval( $h_match[1] ) * 60;
                            }
                            if ( preg_match( '/(\d+)\s*min/i', $delivery_time, $m_match ) ) {
                                $minutes += intval( $m_match[1] );
                            }
                            if ( $minutes === 0 && preg_match( '/(\d+)/', $delivery_time, $n_match ) ) {
                                $minutes = intval( $n_match[1] );
                            }
                            if ( $minutes > 0 ) {
                                $delivery_time_minutes = $minutes;
                            }
                        }
                    }
                    $out[] = array(
                        'id' => $id,
                        'number' => $id,
                        'total' => function_exists( 'wc_price' ) && $meta_total !== '' ? wc_price( $meta_total ) : $meta_total,
                        'status' => $p->post_status,
                        'date_display' => date_i18n( 'd/m/Y H:i', strtotime( $p->post_date ) ),
                        'customer' => trim( get_post_meta( $id, '_billing_first_name', true ) . ' ' . get_post_meta( $id, '_billing_last_name', true ) ),
                        'edit_link' => admin_url( 'post.php?post=' . $id . '&action=edit' ),
                        'delivery_time_minutes' => $delivery_time_minutes,
                    );
                }
            }
        }

        // If still empty, try the plugin's custom post type 'mydelivery-orders'
        if ( empty( $out ) ) {
            $args2 = array(
                'post_type' => 'mydelivery-orders',
                'post_status' => 'publish',
                'posts_per_page' => 50,
                'date_query' => array( array( 'after' => $start, 'before' => $end, 'inclusive' => true ) ),
            );
            $posts2 = get_posts( $args2 );
            if ( $posts2 ) {
                foreach ( $posts2 as $p ) {
                    $id = $p->ID;
                    if ( ! self::is_order_finished( $id ) ) { continue; }
                    // aggregated filters for mydelivery-orders
                    if ( $agg === 'weekday' ) {
                        $wd = (int) date( 'w', strtotime( $p->post_date ) );
                        if ( $wd !== $weekday_index ) continue;
                    } elseif ( $agg === 'month' ) {
                        if ( ! empty( $month_label ) ) {
                            $mstr = date( 'm/Y', strtotime( $p->post_date ) );
                            if ( strpos( $month_label, $mstr ) === false && strpos( $mstr, $month_label ) === false ) continue;
                        }
                    } elseif ( $agg === 'hour' ) {
                        $h = (int) date( 'G', strtotime( $p->post_date ) );
                        if ( $h !== $hour ) continue;
                    }
                    $order_total = get_post_meta( $id, 'order_total', true );
                    if ( $order_total === '' || $order_total === null ) {
                        $order_total = get_post_meta( $id, 'myd_order_total', true );
                    }
                    $order_total = $order_total !== '' ? $order_total : '';
                    // Tempo de entrega em minutos
                    $delivery_time = get_post_meta( $id, 'order_delivery_time', true );
                    $delivery_time_minutes = null;
                    if ( ! empty( $delivery_time ) ) {
                        // Se for uma data/hora (ex: 02-02-2026 10:36), calcula diferença para post_date
                        if ( preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})[\sT](\d{2}):(\d{2})/', $delivery_time, $dt_match ) ) {
                            $entrega_ts = strtotime(sprintf('%04d-%02d-%02d %02d:%02d:00', $dt_match[3], $dt_match[2], $dt_match[1], $dt_match[4], $dt_match[5]));
                            $pedido_ts = strtotime($p->post_date);
                            if ($entrega_ts && $pedido_ts && $entrega_ts > $pedido_ts) {
                                $delivery_time_minutes = round(($entrega_ts - $pedido_ts) / 60);
                            }
                        } else {
                            $minutes = 0;
                            if ( preg_match( '/(\d+)\s*h/i', $delivery_time, $h_match ) ) {
                                $minutes += intval( $h_match[1] ) * 60;
                            }
                            if ( preg_match( '/(\d+)\s*min/i', $delivery_time, $m_match ) ) {
                                $minutes += intval( $m_match[1] );
                            }
                            if ( $minutes === 0 && preg_match( '/(\d+)/', $delivery_time, $n_match ) ) {
                                $minutes = intval( $n_match[1] );
                            }
                            if ( $minutes > 0 ) {
                                $delivery_time_minutes = $minutes;
                            }
                        }
                    }
                    $out[] = array(
                        'id' => $id,
                        'number' => $p->post_title ?: $id,
                        'total' => $order_total !== '' && function_exists( 'wc_price' ) ? wc_price( $order_total ) : $order_total,
                        'status' => $p->post_status,
                        'date_display' => date_i18n( 'd/m/Y H:i', strtotime( $p->post_date ) ),
                        'customer' => trim( get_post_meta( $id, 'billing_first_name', true ) . ' ' . get_post_meta( $id, 'billing_last_name', true ) ),
                        'edit_link' => admin_url( 'post.php?post=' . $id . '&action=edit' ),
                        'delivery_time_minutes' => $delivery_time_minutes,
                    );
                }
            }
        }

        // Extra fallback: some orders store their date in meta 'order_date' (dd-mm-YYYY HH:ii).
        // If we still have no results, search by that meta so orders like 1825 are found.
        if ( empty( $out ) ) {
            $args_meta = array(
                'post_type' => 'mydelivery-orders',
                'post_status' => 'publish',
                'posts_per_page' => 50,
                'meta_query' => array(
                    array(
                        'key' => 'order_date',
                        'value' => $d,
                        'compare' => 'LIKE',
                    ),
                ),
            );
            $posts_meta = get_posts( $args_meta );
            if ( $posts_meta ) {
                foreach ( $posts_meta as $p ) {
                    $id = $p->ID;
                    if ( ! self::is_order_finished( $id ) ) { continue; }
                    $order_total = get_post_meta( $id, 'order_total', true );
                    if ( $order_total === '' || $order_total === null ) {
                        $order_total = get_post_meta( $id, 'myd_order_total', true );
                    }
                    if ( $order_total === '' || $order_total === null ) {
                        $order_total = get_post_meta( $id, 'fdm_order_total', true );
                    }
                    $order_total = $order_total !== '' ? $order_total : '';
                    // Tempo de entrega em minutos
                    $delivery_time = get_post_meta( $id, 'order_delivery_time', true );
                    $delivery_time_minutes = null;
                    if ( ! empty( $delivery_time ) ) {
                        // Se for uma data/hora (ex: 02-02-2026 10:36), calcula diferença para post_date
                        if ( preg_match('/^(\d{2})[\/-](\d{2})[\/-](\d{4})[\sT](\d{2}):(\d{2})/', $delivery_time, $dt_match ) ) {
                            $entrega_ts = strtotime(sprintf('%04d-%02d-%02d %02d:%02d:00', $dt_match[3], $dt_match[2], $dt_match[1], $dt_match[4], $dt_match[5]));
                            $pedido_ts = strtotime($p->post_date);
                            if ($entrega_ts && $pedido_ts && $entrega_ts > $pedido_ts) {
                                $delivery_time_minutes = round(($entrega_ts - $pedido_ts) / 60);
                            }
                        } else {
                            $minutes = 0;
                            if ( preg_match( '/(\d+)\s*h/i', $delivery_time, $h_match ) ) {
                                $minutes += intval( $h_match[1] ) * 60;
                            }
                            if ( preg_match( '/(\d+)\s*min/i', $delivery_time, $m_match ) ) {
                                $minutes += intval( $m_match[1] );
                            }
                            if ( $minutes === 0 && preg_match( '/(\d+)/', $delivery_time, $n_match ) ) {
                                $minutes = intval( $n_match[1] );
                            }
                            if ( $minutes > 0 ) {
                                $delivery_time_minutes = $minutes;
                            }
                        }
                    }
                    $out[] = array(
                        'id' => $id,
                        'number' => $p->post_title ?: $id,
                        'total' => $order_total !== '' && function_exists( 'wc_price' ) ? wc_price( $order_total ) : $order_total,
                        'status' => $p->post_status,
                        'date_display' => get_post_meta( $id, 'order_date', true ) ?: date_i18n( 'd/m/Y H:i', strtotime( $p->post_date ) ),
                        'customer' => trim( get_post_meta( $id, 'billing_first_name', true ) . ' ' . get_post_meta( $id, 'billing_last_name', true ) ),
                        'edit_link' => admin_url( 'post.php?post=' . $id . '&action=edit' ),
                        'delivery_time_minutes' => $delivery_time_minutes,
                    );
                }
            }
        }

        wp_send_json_success( $out );
    }

    /**
     * AJAX handler: retorna detalhes de um pedido (por ID)
     */
    public static function ajax_get_order_details(){
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        if ( ! is_user_logged_in() || ( ! current_user_can( 'edit_posts' ) && ! in_array( 'marketing', $roles, true ) ) ) {
            wp_send_json_error( 'unauthorized' );
        }
        $order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
        if ( ! $order_id ) {
            wp_send_json_error( 'missing_id' );
        }


        $out = array();

        // Fallback: try querying post type mydelivery-orders
        $p = get_post( $order_id );
        if ( $p ) {
            $out['id'] = $p->ID;
            $out['number'] = $p->post_title ?: $p->ID;
            $out['date_display'] = date_i18n( 'd/m/Y H:i', strtotime( $p->post_date ) );
            $out['status'] = $p->post_status;
            $out['status_label'] = $p->post_status;
            $out['payment_method'] = get_post_meta( $p->ID, 'payment_method', true );
            $out['order_payment_type'] = get_post_meta( $p->ID, 'order_payment_type', true );
            $out['delivery_type'] = get_post_meta( $p->ID, 'delivery_type', true );
            $out['delivery_code'] = get_post_meta( $p->ID, 'delivery_code', true );
            $out['order_delivery_time'] = get_post_meta( $p->ID, 'order_delivery_time', true );
            // Canal de venda (corrige erro de digitação: order_chanel)
            $order_channel = get_post_meta( $p->ID, 'order_channel', true );
            if ( empty($order_channel) ) {
                $order_channel = get_post_meta( $p->ID, 'order_chanel', true );
            }
            $out['order_channel'] = $order_channel;
            
            // iFood Order ID
            $out['ifood_order_id'] = get_post_meta( $p->ID, 'ifood_order_id', true );
            // items stored as serialized meta? try common meta keys
            $items_meta = get_post_meta( $p->ID, 'order_items', true );
            // Fallbacks para outras chaves comuns
            if ( empty($items_meta) ) {
                $items_meta = get_post_meta( $p->ID, 'myd_order_items', true );
            }
            if ( empty($items_meta) ) {
                $items_meta = get_post_meta( $p->ID, 'fdm_order_items', true );
            }
            $items = array();
            if ( is_array( $items_meta ) ) {
                foreach ( $items_meta as $it ){
                    $items[] = array(
                        'product_name'   => isset($it['product_name']) ? $it['product_name'] : (isset($it['name']) ? $it['name'] : ''),
                        'product_price'  => isset($it['product_price']) ? $it['product_price'] : (isset($it['unit_price']) ? $it['unit_price'] : ''),
                        'product_image'  => isset($it['product_image']) ? $it['product_image'] : (isset($it['image']) ? $it['image'] : ''),
                        'product_extras' => isset($it['product_extras']) ? $it['product_extras'] : '',
                        'product_note'   => isset($it['product_note']) ? $it['product_note'] : '',
                        'quantity'       => isset($it['quantity']) ? $it['quantity'] : 1,
                        'subtotal'       => isset($it['subtotal']) ? $it['subtotal'] : '',
                        'currency_symbol'=> isset($it['currency_symbol']) ? $it['currency_symbol'] : 'R$',
                    );
                }
            }
            $out['items'] = $items;
            $out['total'] = get_post_meta( $p->ID, 'order_total', true );
            wp_send_json_success( $out );
        }

        wp_send_json_error( 'not_found' );
    }
}

// Register AJAX action for logged-in users
add_action( 'wp_ajax_myd_get_orders_for_day', array( __NAMESPACE__ . '\\Myd_Dashboard_Shortcode', 'ajax_get_orders_for_day' ) );
add_action( 'wp_ajax_myd_get_order_details', array( __NAMESPACE__ . '\\Myd_Dashboard_Shortcode', 'ajax_get_order_details' ) );


