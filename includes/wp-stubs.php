<?php
// Lightweight stubs to help static analyzers and IDEs when WordPress is not loaded.
// These are no-op safe definitions and will only be declared if they do not exist.

if ( ! function_exists( '_doing_it_wrong' ) ) {
    function _doing_it_wrong( $function = null, $message = null, $version = null ) {
        // noop for static analysis
        return null;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text = '', $domain = '' ) {
        return $text;
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action(...$args) { return null; }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action(...$args) { return null; }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter(...$args) { return null; }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $hook_name = '', $value = null, ...$args ) {
        return $value;
    }
}

if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( $args = array(), $url = '' ) {
        if ( empty( $args ) || ! is_array( $args ) ) {
            return $url;
        }
        $query = array();
        foreach ( $args as $key => $value ) {
            $query[] = rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
        }
        $separator = strpos( $url, '?' ) === false ? '?' : '&';
        return $url . ( $query ? $separator . implode( '&', $query ) : '' );
    }
}

if ( ! function_exists( 'register_activation_hook' ) ) {
    function register_activation_hook(...$args) { return null; }
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
    function register_deactivation_hook(...$args) { return null; }
}

if ( ! function_exists( 'load_plugin_textdomain' ) ) {
    function load_plugin_textdomain(...$args) { return null; }
}

if ( ! function_exists( 'is_admin' ) ) {
    function is_admin(...$args) { return false; }
}

if ( ! function_exists( 'wp_is_post_revision' ) ) {
    function wp_is_post_revision(...$args) { return false; }
}

if ( ! function_exists( 'get_post_meta' ) ) {
    function get_post_meta(...$args) { return null; }
}

if ( ! function_exists( 'update_post_meta' ) ) {
    function update_post_meta(...$args) { return null; }
}

if ( ! function_exists( 'get_posts' ) ) {
    function get_posts(...$args) { return array(); }
}

if ( ! function_exists( 'get_post_type' ) ) {
    function get_post_type(...$args) { return ''; }
}

if ( ! function_exists( 'register_rest_route' ) ) {
    function register_rest_route(...$args) { return null; }
}

if ( ! function_exists( 'wp_authenticate' ) ) {
    function wp_authenticate(...$args) {
        // Return a minimal user-like object for static analysis
        return (object) [
            'ID' => 0,
            'user_email' => '',
            'user_nicename' => '',
            'display_name' => '',
        ];
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error(...$args) { return false; }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str = '' ) { return is_string( $str ) ? trim( $str ) : ''; }
}

if ( ! function_exists( 'get_transient' ) ) {
    function get_transient( $transient = '' ) { return false; }
}

if ( ! function_exists( 'set_transient' ) ) {
    function set_transient( $transient = '', $value = null, $expiration = 0 ) { return true; }
}

if ( ! function_exists( 'delete_transient' ) ) {
    function delete_transient( $transient = '' ) { return true; }
}

if ( ! function_exists( 'wp_remote_get' ) ) {
    function wp_remote_get( $url = '', $args = array() ) {
        return array(
            'body' => '{}',
            'headers' => array(),
            'response' => array( 'code' => 200 ),
        );
    }
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
    function wp_remote_retrieve_response_code( $response = array() ) {
        return isset( $response['response']['code'] ) ? (int) $response['response']['code'] : 0;
    }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response = array() ) {
        return isset( $response['body'] ) ? $response['body'] : '';
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option(...$args) { return null; }
}

if ( ! function_exists( 'get_bloginfo' ) ) {
    function get_bloginfo(...$args) { return ''; }
}

if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can(...$args) { return false; }
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
    function wp_get_current_user(...$args) {
        return new class {
            public function exists() { return false; }
            public $display_name = '';
            public $user_email = '';
        };
    }
}

if ( ! function_exists( 'wp_localize_script' ) ) {
    function wp_localize_script(...$args) { return null; }
}

if ( ! function_exists( 'wp_register_script' ) ) {
    function wp_register_script(...$args) { return null; }
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
    function wp_enqueue_script(...$args) { return null; }
}

if ( ! function_exists( 'wp_register_style' ) ) {
    function wp_register_style(...$args) { return null; }
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
    function wp_enqueue_style(...$args) { return null; }
}

if ( ! defined( 'JWT_AUTH_SECRET_KEY' ) ) {
    define( 'JWT_AUTH_SECRET_KEY', null );
}

// Minimal WP_Error stub for static analysis
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public $errors = [];
        public function __construct(...$args) { $this->errors = $args; }
        public function get_error_codes() { return array_keys($this->errors); }
    }
}

// Additional stubs observed during static analysis
if ( ! function_exists( 'get_user_meta' ) ) {
    function get_user_meta(...$args) { return null; }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time(...$args) { return date('Y-m-d H:i:s'); }
}

if ( ! function_exists( 'wp_logout_url' ) ) {
    function wp_logout_url(...$args) { return ''; }
}

if ( ! function_exists( 'home_url' ) ) {
    function home_url(...$args) { return 'http://localhost'; }
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
    function is_user_logged_in(...$args) { return false; }
}

if ( ! function_exists( 'admin_url' ) ) {
    function admin_url(...$args) { return 'http://localhost/wp-admin/'; }
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
    function wp_create_nonce(...$args) { return ''; }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) { return $text; }
}

if ( ! function_exists( 'deactivate_plugins' ) ) {
    function deactivate_plugins(...$args) { return true; }
}

if ( ! function_exists( 'esc_html_e' ) ) {
    function esc_html_e( $text, $domain = '' ) { echo $text; }
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
    function wp_safe_redirect(...$args) { return true; }
}

if ( ! function_exists( 'site_url' ) ) {
    function site_url(...$args) { return 'http://localhost'; }
}

if ( ! function_exists( 'is_plugin_active' ) ) {
    function is_plugin_active(...$args) { return false; }
}

if ( ! function_exists( 'flush_rewrite_rules' ) ) {
    function flush_rewrite_rules(...$args) { return null; }
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled(...$args) { return false; }
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
    function wp_schedule_event(...$args) { return null; }
}

if ( ! function_exists( 'dbDelta' ) ) {
    function dbDelta(...$args) { return null; }
}

if ( ! function_exists( 'wp_generate_password' ) ) {
    function wp_generate_password( $len = 12, $special_chars = true ) { return bin2hex(random_bytes(max(1, intval($len/2)))); }
}

if ( ! function_exists( 'wp_generate_auth_cookie' ) ) {
    function wp_generate_auth_cookie(...$args) { return ''; }
}

if ( ! class_exists( 'JWT' ) ) {
    class JWT {
        public static function encode($payload = null, $key = null, $alg = null) { return ''; }
    }
}

return true;
