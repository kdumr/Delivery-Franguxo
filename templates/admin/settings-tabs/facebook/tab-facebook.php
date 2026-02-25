<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div id="tab-facebook-content" class="myd-tabs-content">
    <h2><?php esc_html_e( 'Facebook / Pixel', 'myd-delivery-pro' ); ?></h2>
    <p><?php esc_html_e( 'Configure o Facebook Pixel e o token do Conversions API (opcional).', 'myd-delivery-pro' ); ?></p>

    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="myd_facebook_pixel_id"><?php esc_html_e( 'Facebook Pixel ID', 'myd-delivery-pro' ); ?></label>
                </th>
                <td>
                    <input name="myd_facebook_pixel_id" type="text" id="myd_facebook_pixel_id" value="<?php echo esc_attr( get_option( 'myd_facebook_pixel_id' ) ); ?>" class="regular-text" placeholder="123456789012345">
                    <p class="description"><?php esc_html_e( 'ID do Pixel (ex: 123456789012345). Usado para o tracking client-side.', 'myd-delivery-pro' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="myd_facebook_capi_token"><?php esc_html_e( 'Facebook CAPI Access Token', 'myd-delivery-pro' ); ?></label>
                </th>
                <td>
                    <input name="myd_facebook_capi_token" type="text" id="myd_facebook_capi_token" value="<?php echo esc_attr( get_option( 'myd_facebook_capi_token' ) ); ?>" class="regular-text" placeholder="EAAB...">
                    <p class="description"><?php esc_html_e( 'Access Token do Conversions API (opcional). Usado para enviar eventos server-side ao Facebook.', 'myd-delivery-pro' ); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="myd_facebook_currency"><?php esc_html_e( 'Currency', 'myd-delivery-pro' ); ?></label>
                </th>
                <td>
                    <input name="myd_facebook_currency" type="text" id="myd_facebook_currency" value="<?php echo esc_attr( get_option( 'myd_facebook_currency', 'BRL' ) ); ?>" class="regular-text" placeholder="BRL">
                    <p class="description"><?php esc_html_e( 'Moeda usada nos eventos enviados ao Facebook (ex: BRL).', 'myd-delivery-pro' ); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
</div>
