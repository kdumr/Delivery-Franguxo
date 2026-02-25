<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper to manage order meta operations in a single place.
 */
class Order_Meta {
    public static function set_payment_dataid( $order_id, $dataid ) {
        if ( empty( $order_id ) || empty( $dataid ) ) {
            return false;
        }

        $val = sanitize_text_field( (string) $dataid );
        try {
            update_post_meta( $order_id, 'order_payment_dataid', $val );
            return true;
        } catch ( \Throwable $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[MYD][Order_Meta] Failed to set payment dataid: ' . $e->getMessage() );
            }
            return false;
        }
    }

    /**
     * Normalize a meta value returned by get_post_meta — if array, return first item.
     */
    protected static function normalize_single_meta( $val ) {
        if ( is_array( $val ) ) {
            return isset( $val[0] ) ? $val[0] : '';
        }
        return $val;
    }

    /**
     * Ensure an initial status is set atomically.
     * If $only_if_current_in is provided as array, the status will be UPDATED
     * when current status is one of the array members (overwrite allowed).
     * Otherwise it tries to add the meta atomically and returns false if already exists.
     *
     * @param int $order_id
     * @param string $value
     * @param string|array|null $only_if_current_in
     * @return bool True if the meta was created or updated, false otherwise.
     */
    public static function ensure_initial_status( $order_id, $value, $only_if_current_in = null ) {
        $order_id = (int) $order_id;
        if ( $order_id <= 0 ) return false;
        $value = sanitize_text_field( (string) $value );

        $cur = get_post_meta( $order_id, 'order_status', true );
        $cur = self::normalize_single_meta( $cur );

        // If caller provided an array of allowed current statuses, and the current
        // status matches one of them, perform an update (overwrite) to set the new value.
        if ( is_array( $only_if_current_in ) ) {
            // normalize array values to strings
            $allowed = array_map( function( $v ){ return (string) $v; }, $only_if_current_in );
            if ( in_array( (string) $cur, $allowed, true ) ) {
                $updated = update_post_meta( $order_id, 'order_status', $value );
                return (bool) $updated;
            }
            return false;
        }

        // If caller supplied a single allowed value, only set when current matches it.
        if ( is_string( $only_if_current_in ) && $only_if_current_in !== '' ) {
            if ( $cur === (string) $only_if_current_in ) {
                return (bool) update_post_meta( $order_id, 'order_status', $value );
            }
            return false;
        }

        // Otherwise, try to add atomically (won't overwrite existing meta)
        $added = add_post_meta( $order_id, 'order_status', $value, true );
        return (bool) $added;
    }

    /**
     * Initialize hooks to record status change notes.
     */
    public static function init_hooks() {
        // Avoid duplicate hooks
        static $inited = false;
        if ( $inited ) return;
        $inited = true;

        add_action( 'added_post_meta', array( __CLASS__, 'on_added_post_meta' ), 10, 4 );
        add_action( 'updated_post_meta', array( __CLASS__, 'on_updated_post_meta' ), 10, 4 );
    }

    public static function on_added_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
        if ( $meta_key !== 'order_status' ) return;
        self::record_status_change_note( (int) $object_id, $_meta_value, 'meta' );
    }

    public static function on_updated_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
        if ( $meta_key !== 'order_status' ) return;
        self::record_status_change_note( (int) $object_id, $_meta_value, 'meta' );
    }

    /**
     * Record a status change into order_notes and set order_status_changed_ts.
     * Note structure: array of items with keys: type, text, date, source
     */
    public static function record_status_change_note( $order_id, $status_value, $source = 'meta' ) {
        $order_id = (int) $order_id;
        if ( $order_id <= 0 ) return false;
        $status_value = is_scalar( $status_value ) ? (string) $status_value : json_encode( $status_value );

        $notes = get_post_meta( $order_id, 'order_notes', true );
        if ( ! is_array( $notes ) ) $notes = array();

        $notes[] = array(
            'type' => 'status',
            'text' => sanitize_text_field( $status_value ),
            'date' => current_time( 'mysql' ),
            'source' => is_string( $source ) ? $source : 'meta',
        );

        update_post_meta( $order_id, 'order_notes', $notes );
        update_post_meta( $order_id, 'order_status_changed_ts', (int) current_time( 'timestamp' ) );
        return true;
    }
}

// Auto-init hooks so status changes are recorded when this file is loaded.
Order_Meta::init_hooks();
