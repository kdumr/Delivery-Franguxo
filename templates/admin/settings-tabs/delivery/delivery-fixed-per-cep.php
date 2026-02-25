<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$active = $delivery_mode === 'fixed-per-cep' ? 'myd-tabs-content--active' : '' ;
/**
 * TODO: check this later
 */
$delivery_mode_fixed_per_cep_price = isset( $delivery_mode_options['fixed-per-cep']['price'] ) ? $delivery_mode_options['fixed-per-cep']['price'] : '';
$delivery_mode_fixed_per_cep_options = isset( $delivery_mode_options['fixed-per-cep']['options'] ) ? $delivery_mode_options['fixed-per-cep']['options'] : '';
?>
<div class="myd-delivery-type-content <?php echo esc_attr( $active );?>" id="myd-delivery-fixed-per-cep">
    <h2><?php esc_html_e( 'Fixed price (Limit by Zipcode range)', 'myd-delivery-pro' ) ;?></h2>
    <p><?php esc_html_e( 'With this method you can define a fixed price for delivery and limit area by Zipcode range.', 'myd-delivery-pro' ) ;?></p>

    <table class="form-table">
        <tbody>
    	    <tr>
        	    <th scope="row">
                    <label for="myd-delivery-mode-options[fixed-per-cep][price]"><?php esc_html_e( 'Price', 'myd-delivery-pro' );?></label>
                </th>
                <td>
                    <input name="myd-delivery-mode-options[fixed-per-cep][price]" type="number" step="0.001" id="myd-delivery-mode-options[fixed-per-cep][price]" value="<?php echo esc_attr( $delivery_mode_fixed_per_cep_price );?>" class="regular-text">
                </td>
            </tr>
        </tbody>
    </table>

    <table class="wp-list-table widefat fixed striped myd-options-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'From Zipcode', 'myd-delivery-pro' );?></th>
                <th><?php esc_html_e( 'To Zipcode', 'myd-delivery-pro' );?></th>
                <th class="myd-options-table__action"><?php esc_html_e( 'Action', 'myd-delivery-pro' );?></th>
            </tr>
        </thead>
        <tbody>
            <?php if( isset( $delivery_mode_fixed_per_cep_options ) && ! empty( $delivery_mode_fixed_per_cep_options ) ): ?>

                <?php foreach( $delivery_mode_fixed_per_cep_options as $k => $v ): ?>
                    <tr class="myd-options-table__row-content" data-row-index='<?php echo esc_attr( $k );?>' data-row-field-base="myd-delivery-mode-options[fixed-per-cep][options]">
                        <td>
                            <input name="myd-delivery-mode-options[fixed-per-cep][options][<?php echo esc_attr( $k );?>][from]" data-data-index="from" type="number" id="myd-delivery-mode-options[fixed-per-cep][options][<?php echo esc_attr( $k );?>][from]" value="<?php echo esc_attr( $v['from'] );?>" class="regular-text">
                        </td>
                        <td>
                            <input name="myd-delivery-mode-options[fixed-per-cep][options][<?php echo esc_attr( $k );?>][to]" data-data-index="to" type="number" id="myd-delivery-mode-options[fixed-per-cep][options][<?php echo esc_attr( $k );?>][to]" value="<?php echo esc_attr( $v['to'] );?>" class="regular-text">
                        </td>
                        <td>
                            <span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterTableRemoveRow(this)"><?php echo esc_html_e( 'remove', 'myd-delivery-pro' );?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php else: ?>

                <tr class="myd-options-table__row-content" data-row-index='0' data-row-field-base="myd-delivery-mode-options[fixed-per-cep][options]">
                    <td>
                        <input name="myd-delivery-mode-options[fixed-per-cep][options][0][from]" data-data-index="from" type="number" id="myd-delivery-mode-options[fixed-per-cep][options][0][from]" value="" class="regular-text">
                    </td>
                    <td>
                        <input name="myd-delivery-mode-options[fixed-per-cep][options][0][to]" data-data-index="to" type="number" id="myd-delivery-mode-options[fixed-per-cep][options][0][to]" value="" class="regular-text">
                    </td>
                    <td>
                        <span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterTableRemoveRow(this)"><?php echo esc_html_e( 'remove', 'myd-delivery-pro' );?></span>
                    </td>
                </tr>

            <?php endif;?>
        </tbody>
    </table>
    <a href="#" class="button button-small button-secondary myd-repeater-table__button" onclick="window.MydAdmin.mydRepeaterTableAddRow(event)"><?php esc_html_e( 'Add more', 'myd-delivery-pro' );?></a>
</div>
