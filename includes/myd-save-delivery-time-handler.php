<?php
add_action('wp_ajax_myd_save_delivery_time', function() {
    if (!function_exists('myd_user_is_allowed_admin')) {
        require_once __DIR__ . '/class-plugin.php';
    }
    if (!myd_user_is_allowed_admin()) {
        wp_send_json_error(['message' => 'Sem permissão.']);
    }
    $data = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : null;
    if (!is_array($data)) {
        wp_send_json_error(['message' => 'Dados inválidos.']);
    }
    // Normaliza estrutura: cada dia deve ser array de ranges
    $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    $save = [];
    foreach ($days as $day) {
        $save[$day] = [];
        if (isset($data[$day]) && is_array($data[$day])) {
            foreach ($data[$day] as $range) {
                $start = isset($range['start']) ? $range['start'] : '';
                $end = isset($range['end']) ? $range['end'] : '';
                if ($start || $end) {
                    $save[$day][] = [ 'start' => $start, 'end' => $end ];
                }
            }
        }
    }
    update_option('myd-delivery-time', $save);
    wp_send_json_success(['message' => 'Horários salvos.']);
});
