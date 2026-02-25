<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>
<div id="tab-shortcodes-content" class="myd-tabs-content">
    
    <h2><?php esc_html_e( 'Shortcodes', 'myd-delivery-pro' );?></h2>
    <p><?php esc_html_e( 'On this page you will find the shortcodes for use in your pages.', 'myd-delivery-pro');?></p>
    
        <table class="form-table">
            <tbody>
                
                <tr>
                    <th scope="row">
                        <label for="myd-shortcode-products"><?php esc_html_e( 'Delivery Page', 'myd-delivery-pro' );?></label>
                    </th>
                    <td>
                        <input type="text" id="myd-shortcode-products" value="[mydelivery-products]" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Show delivery system with produtcs, menu, card and more.', 'myd-delivery-pro' );?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="myd-shortcode-orders"><?php esc_html_e( 'Orders', 'myd-delivery-pro' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="myd-shortcode-orders" value="[mydelivery-orders]" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Show page to manage orders in progress.', 'myd-delivery-pro' );?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="myd-shortcode-track-order"><?php esc_html_e( 'Track Order', 'myd-delivery-pro' );?></label>
                    </th>
                    <td>
                        <input type="text" id="myd-shortcode-track-order" value="[mydelivery-track-order]" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Show the page for customers check your order data', 'myd-delivery-pro' );?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="myd-shortcode-store-status"><?php esc_html_e( 'Store Status', 'myd-delivery-pro' );?></label>
                    </th>
                    <td>
                        <input type="text" id="myd-shortcode-store-status" value="[myd_store_status]" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Show store open/close status and minimum order value.', 'myd-delivery-pro' );?></p>
                    </td>
                </tr>
            
            </tbody>
        </table>
        <div class="card">
            <h3><?php esc_html_e( 'Important', 'myd-delivery-pro' );?></h3>
            <p><?php esc_html_e( 'You must use these shortcodes in your pages for the system works. If you use our plugin with Widgets for Elementor it is not necessary to use the shortcodes.', 'myd-delivery-pro' );?></p>
        </div>
</div>