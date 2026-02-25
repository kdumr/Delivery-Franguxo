<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>
<div id="tab-print-content" class="myd-tabs-content">
    
    <h2><?php esc_html_e( 'Print Settings', 'myd-delivery-pro' );?></h2>
    <p><?php esc_html_e( 'In this section you can configure the print options.', 'myd-delivery-pro' );?></p>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="fdm-print-size"><?php esc_html_e( 'Print size', 'myd-delivery-pro' );?></label>
                    </th>
                    <td>
                        <select name="fdm-print-size" id="fdm-print-size">
                            <option value=""><?php esc_html_e( 'Select', 'myd-delivery-pro' );?></option>
                            <option value="52mm" <?php selected( get_option('fdm-print-size'), '52mm' );?>>58mm</option>
                            <option value="70mm" <?php selected( get_option('fdm-print-size'), '70mm' );?>>76mm</option>
                            <option value="74mm" <?php selected( get_option('fdm-print-size'), '74mm' );?>>80mm</option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Select the printing page size.', 'myd-delivery-pro' );?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="fdm-print-font-size"><?php esc_html_e( 'Font size', 'myd-delivery-pro' );?></label>
                    </th>
                    <td>
                        <input name="fdm-print-font-size" type="number" id="fdm-print-font-size" value="<?php echo get_option( 'fdm-print-font-size' );?>" class="regular-text">
                    </td>
                </tr>
            </tbody>
        </table>
    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Important', 'myd-delivery-pro' );?></h2>
        <p><?php esc_html_e( 'The print order is executaded direct on you web browser, its not a software installed in you computer. This plugin will be generate the the file and visualization like configured above, but same times you need set some configurations direct in your printer.', 'myd-delivery-pro' );?></p>
    </div>

    <h2><?php esc_html_e( 'Print message', 'myd-delivery-pro' );?></h2>
    <p><?php esc_html_e( 'In this section you can customize your print message.', 'myd-delivery-pro' );?></p>

    <div class="card">
        <h2 class="title"><?php esc_html_e( 'Coming soon!', 'myd-delivery-pro' );?></h2>
        <p><?php esc_html_e( 'Soon you will can customize all text/message in your printing file.', 'myd-delivery-pro' );?></p>
    </div>
</div>