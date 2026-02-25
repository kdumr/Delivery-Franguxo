<?php

use MydPro\Includes\Myd_Store_Formatting;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<?php foreach ( $extras as $formated_extras ) : ?>
	<?php
	if ( empty( $formated_extras['extra_options'] ) || empty( $formated_extras['extra_title'] ) ) :
		return;
	endif;
	?>

	<?php
		$attr_extra_required = $product_extra->get_extra_required( $formated_extras );
		$min_to_select = $formated_extras['extra_min_limit'] ?? '';
		$max_to_select = $formated_extras['extra_limit'] ?? '';
		$is_product_extra_available = $formated_extras['extra_available'] ?? '';
	?>

	<?php if ( $is_product_extra_available !== 'hide' ) : ?>
		<div class="myd-product-extra-wrapper">
			<?php if ( $is_product_extra_available === 'not-available' ) : ?>
				<span class="myd-product-item__not-available"><?php esc_html_e( 'Not available', 'myd-delivery-pro' ); ?></span>
				<div class="myd-product-item__not-available-overlay"></div>
				<?php $attr_extra_required = false; ?>
			<?php endif; ?>
			<div
				class="fdm-extra-option-title"
				data-obj="<?php echo \esc_attr( $attr_extra_required ); ?>"
				<?php if ( ! empty( $max_to_select ) ) : ?>
					data-select-limit="<?php echo \esc_attr( $max_to_select ); ?>"
				<?php endif; ?>
				<?php if ( ! empty( $min_to_select ) ) : ?>
					data-min="<?php echo \esc_attr( $min_to_select ); ?>"
				<?php endif; ?>
			>
				<?php
				$required_tag = $product_extra->get_extra_required_tag( $formated_extras );
				$extra_title = $product_extra->get_title( $formated_extras );
				?>

				<div class="fdm-extra-option-title-text">
					<div class="fdm-extra-info">
						<span class="fdm-extra-option-limit-text">
							<?php echo \esc_html( $extra_title ); ?>
						</span>

						<span class="fdm-extra-option-required">
							<?php echo \esc_html( $required_tag ); ?>
						</span>

					</div>
						<span class="fdm-extra-option-limit-desc">
							<?php
							$min_to_select = ! empty( $min_to_select ) ? (int) $min_to_select : 0;
							$max_to_select = ! empty( $max_to_select ) ? (int) $max_to_select : 0;

							if ( $min_to_select > 0 ) {
								if ( $max_to_select > 0 ) {
									/* translators: 1: min limit, 2: max limit */
									printf( \esc_html__( 'Escolha pelo menos %1$s (máx. %2$s)', 'myd-delivery-pro' ), $min_to_select, $max_to_select );
								} else {
									/* translators: %s: min limit */
									printf( \esc_html__( 'Escolha pelo menos %s', 'myd-delivery-pro' ), $min_to_select );
								}
							} elseif ( $max_to_select > 0 ) {
								/* translators: %s: max limit */
								printf( \esc_html__( '(máx. %s)', 'myd-delivery-pro' ), $max_to_select );
							}
							?>
						</span>
				</div>

				<?php $data_type = uniqid( $product_extra->get_title( $formated_extras ) . '-' ); ?>
				<?php $data_extra_group = $product_extra->get_title( $formated_extras ); ?>

				<?php foreach ( $formated_extras['extra_options'] as $key => $options ) : ?>
					<?php if ( $options['extra_option_available'] !== 'hide' ) : ?>
						<?php $price = empty( $options['extra_option_price'] ) ? 0 : $options['extra_option_price']; ?>
						<?php $formated_price = $product_extra->format_extra_price( $product->get_currency(), $product->get_price( $options['extra_option_price'] ) ); ?>
						<?php $custom_class = $product_extra->cat_to_class( $product_extra->get_title( $formated_extras ) ); ?>
						<?php $unique_key = uniqid(); ?>
						<div class="myd-extra-item-loop">
							
							<?php if ( $options['extra_option_available'] === 'not-available' ) : ?>
								<span class="myd-product-item__not-available"><?php esc_html_e( 'Not available', 'myd-delivery-pro' ); ?></span>
								<div class="myd-product-item__not-available-overlay"></div>
							<?php endif; ?>
							<div class="myd-extra-item-loop-text">
								<label class="myd-extra-label" for="option_prod_exta[]"><?php echo esc_html( $options['extra_option_name'] ); ?></label><br>
								<p class="myd-extra-description"><?php echo \esc_html( $options['extra_option_description'] ); ?></p>
								<?php if ( ! empty( $formated_price ) ) : ?>
									<span class="myd-extra-price">+ <?php echo \esc_html( $formated_price ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( $options['extra_option_available'] !== 'not-available' && $is_product_extra_available !== 'not-available' ) : ?>
								<div class="myd-extra-item-loop-checkbox">
									<div class="myd-qty-control">
										<button type="button" class="myd-qty-minus" onclick="this.nextElementSibling.stepDown(); this.nextElementSibling.dispatchEvent(new Event('change'))">-</button>
										<input
											type="number"
											id="myd-extra-<?php echo esc_attr( $unique_key ); ?>"
											data-name="<?php echo esc_attr( $options['extra_option_name'] ); ?>"
											data-price="<?php echo esc_attr( $price ); ?>"
											data-type="<?php echo esc_attr( $data_type ); ?>"
											data-extra-group="<?php echo esc_attr( $data_extra_group ); ?>"
											data-min-limit="<?php echo esc_attr( $min_to_select ); ?>"
											data-max-limit="<?php echo esc_attr( $max_to_select ); ?>"
											class="option_prod_exta <?php echo esc_attr( $custom_class ); ?>"
											name="option_prod_exta[<?php echo esc_attr( $unique_key ); ?>]"
											value="0"
											min="0"
											step="1"
											readonly="readonly"
										>
										<button type="button" class="myd-qty-plus" onclick="var w=this.closest('.myd-product-extra-wrapper');var t=w.querySelector('.fdm-extra-option-title');var m=t?parseInt(t.dataset.selectLimit)||Infinity:Infinity;var s=0;w.querySelectorAll('input.option_prod_exta').forEach(function(i){s+=parseInt(i.value)||0});if(s<m){this.previousElementSibling.stepUp();this.previousElementSibling.dispatchEvent(new Event('change'));}">+</button>
									</div>
								</div>
							<?php endif; ?>
						</div>

						<?php if ( $options !== end( $formated_extras['extra_options'] ) ) : ?>
							<hr class="myd-space-extras">
						<?php endif; ?>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.myd-product-extra-wrapper').forEach(wrapper => {
    const title = wrapper.querySelector('.fdm-extra-option-title');
    if (!title) return;

    const max = parseInt(title.dataset.selectLimit) || Infinity;
    const inputs = wrapper.querySelectorAll('input[type="number"].option_prod_exta');

    if (inputs.length === 0) return;

    function updateButtons() {
      let sum = Array.from(inputs).reduce((acc, inp) => acc + (parseInt(inp.value) || 0), 0);
      
      inputs.forEach(inp => {
        const control = inp.closest('.myd-qty-control');
        if (!control) return;

        const minus = control.querySelector('.myd-qty-minus');
        const plus = control.querySelector('.myd-qty-plus');
        const value = parseInt(inp.value) || 0;

        if (minus) minus.disabled = value <= 0;
        if (plus) plus.disabled = sum >= max;
      });
    }

    inputs.forEach(inp => {
      inp.addEventListener('change', updateButtons);
      inp.addEventListener('input', updateButtons);
    });
    
    // Initial update
    updateButtons();
  });
});
</script>
