<?php
/**
 * Backwards compatibility global helpers.
 * This file exposes a small set of global functions expected by legacy templates
 * and other plugin files, delegating to the namespaced implementations when available.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'myd_user_is_allowed_admin' ) ) {
    function myd_user_is_allowed_admin( $user = null ) {
        if ( function_exists( '\\MydPro\\Includes\\myd_user_is_allowed_admin' ) ) {
            return \MydPro\Includes\myd_user_is_allowed_admin( $user );
        }
        // conservative default
        return false;
    }
}

// Add other global wrappers here if necessary in future.
