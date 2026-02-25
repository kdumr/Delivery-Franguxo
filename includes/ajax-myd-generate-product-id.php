<?php
// Endpoint AJAX para gerar código único de produto
add_action('wp_ajax_myd_generate_product_id', function() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Sem permissão']);
    }
    require_once __DIR__ . '/class-custom-fields.php';
    require_once __DIR__ . '/class-register-custom-fields.php';
    $fields = \MydPro\Includes\Custom_Fields\Register_Custom_Fields::get_registered_fields();
    $custom = new \MydPro\Includes\Custom_Fields\Myd_Custom_Fields($fields);
    $id = (new ReflectionClass($custom))->getMethod('generate_unique_product_id');
    $id->setAccessible(true);
    $unique = $id->invoke($custom);
    wp_send_json_success(['id' => $unique]);
});
