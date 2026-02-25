<?php

namespace MydPro\Includes\Legacy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy class to manage old version of our repeater field.
 *
 * @since 1.9.23
 */
class Legacy_Repeater {
	/**
	 * Check if is necessary update item on database.
	 *
	 * @since 1.9.23
	 */
	public static function need_update_db( $repeater_legacy_value, $repeater_main_value ) {
		/**
		 * Legacy data on database - needs to update new field name on database.
		 */
		if ( ! empty( $repeater_legacy_value ) && ( empty( $repeater_main_value ) || ! is_array( $repeater_main_value ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Update repeater value on databse.
	 *
	 * @since 1.9.23
	 */
	public static function update_repeater_database( $repeater_legacy_value, $args, $post_id, $need_update = true ) {
		$product_extras = array();
		$extras_to_interate = (int) $repeater_legacy_value - 1;

		for ( $limit = 0; $limit <= $extras_to_interate; $limit++ ) {
			$product_extras[ $limit ] = self::build_legacy_repeater_value( $args, $limit, $post_id );
		}

		if ( $need_update === true ) {
			\update_post_meta( $post_id, $args['name'], $product_extras );
		}

		return $product_extras;
	}

	/**
	 * Build repeater value.
	 *
	 * @param array $args
	 * @param string|int $limit
	 * @param int $post_id
	 * @return array
	 */
	public static function build_legacy_repeater_value( $args, $limit, $post_id ) {
		$repeater_value = array();

		foreach ( $args['fields'] as $field ) {
			if ( $field['type'] !== 'repeater' ) {
				$db_name = $args['legacy'] . '_' . $limit . '_' . $field['legacy'];
				if ( isset( $args['repeater_type'] ) && $args['repeater_type'] === 'internal' ) {
					$db_name = $args['prefix'] . $args['legacy'] . '_' . $limit . '_' . $field['legacy'];
				}
				$value = \get_post_meta( $post_id, $db_name, true );
				$repeater_value[ $field['name'] ] = $value;
			} else {
				$db_name_array = $args['legacy'] . '_' . $limit . '_' . $field['legacy'];
				$value = \get_post_meta( $post_id, $db_name_array, true );
				$field['prefix'] = $args['legacy'] . '_' . $limit . '_';
				$repeater_value[ $field['name'] ] = self::update_repeater_database( $value, $field, $post_id, false );
			}
		}

		return $repeater_value;
	}
}
