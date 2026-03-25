<?php

use MydPro\Includes\Myd_Legacy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clock delivery time
 */
$clock_option = get_option( 'myd-delivery-time' );
if ( isset( $clock_option[0] ) && $clock_option[0] === 'initial' ) {
	$old_repeater = Myd_Legacy::get_old_delivery_clock();
	update_option( 'myd-delivery-time', $old_repeater );
	$clock_option = get_option( 'myd-delivery-time' );
}



$force_close_store = get_option( 'myd-delivery-force-open-close-store' );

?>
<div id="tab-opening-hours-content" class="myd-tabs-content">
	<h2>
		<?php esc_html_e( 'Opening Hours Settings', 'myd-delivery-pro' ); ?>
	</h2>
	<table class="form-table">
		<tbody>
						<label for="myd-delivery-force-open-close-store">
							<?php esc_html_e( 'Force Open/Close Store', 'myd-delivery-pro' ); ?>
						</label>
				</th>
					<td>
						<select
							name="myd-delivery-force-open-close-store"
							id="myd-delivery-force-open-close-store"
						>
							<option
								value="ignore"
								<?php selected( $force_close_store, 'ignore' ); ?>
							>
								<?php esc_html_e( 'Respect the hours configured below', 'myd-delivery-pro' ); ?>
							</option>
							<option
								value="open"
								<?php selected( $force_close_store, 'open' ); ?>
							>
								<?php esc_html_e( 'Force store open', 'myd-delivery-pro' ); ?>
							</option>
							<option
								value="close"
								<?php selected( $force_close_store, 'close' ); ?>
							>
								<?php esc_html_e( 'Force store closed', 'myd-delivery-pro' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'This option will force store to be opened or closed. The settings below will be ignored if you use this.', 'myd-delivery-pro' ); ?>
						</p>
					</td>
				</tr>
			<tr>
				<th scope="row">
					<label>
						<?php esc_html_e( 'Monday', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<div class="myd-repeater">
						<div class="myd-repeater__rows">
							<?php if ( isset( $clock_option['monday'] ) ) : ?>
								<?php foreach ( $clock_option['monday'] as $k => $v ) : ?>
									<div
										class="myd-repeater__row" id="myd-repeater__row-<?php echo esc_attr( $k ); ?>"
										data-row="<?php echo esc_attr( $k ); ?>"
									>
										<span>
											<?php esc_html_e( 'Start at', 'myd-delivery-pro' ); ?>
										</span>
										<input
											name="myd-delivery-time[monday][<?php echo esc_attr( $k ); ?>][start]"
											type="time"
											id="myd-delivery-time[monday][<?php echo esc_attr( $k ); ?>][start]"
											value="<?php echo esc_attr( $v['start'] ); ?>"
											class="regular-text myd-input-repeater"
										>

										<span>
											<?php esc_html_e( 'End', 'myd-delivery-pro' ); ?>
										</span>
										<input
											name="myd-delivery-time[monday][<?php echo esc_attr( $k ); ?>][end]"
											type="time"
											id="myd-delivery-time[monday][<?php echo esc_attr( $k ); ?>][end]"
											value="<?php echo esc_attr( $v['end'] ); ?>"
											class="regular-text myd-input-repeater"
										>

										<span
											class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"
										>
											<?php echo esc_html_e( 'remove', 'myd-delivery-pro' ); ?>
										</span>
									</div>

								<?php endforeach; ?>

							<?php else : ?>
								<div
									class="myd-repeater__row"
									id="myd-repeater__row-0"
									data-row="0"
								>
									<span>
										<?php esc_html_e( 'Start at', 'myd-delivery-pro' ); ?>
									</span>
									<input
										name="myd-delivery-time[monday][0][start]"
										type="time"
										id="myd-delivery-time[monday][0][start]"
										value=""
										class="regular-text myd-input-repeater"
									>

									<span>
										<?php esc_html_e( 'End', 'myd-delivery-pro' ); ?>
									</span>
									<input
										name="myd-delivery-time[monday][0][end]"
										type="time"
										id="myd-delivery-time[monday][0][end]"
										value=""
										class="regular-text myd-input-repeater"
									>

									<span
										class="myd-repeater__remove"
										onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"
									>
										<?php echo esc_html_e( 'remove', 'myd-delivery-pro' ); ?>
									</span>
								</div>

							<?php endif; ?>
						</div>
						<a
							href="#"
							class="button button-small button-secondary myd-repeater__button"
							onclick="window.MydAdmin.mydRepeaterAddRow(event)"
						>
							<?php esc_html_e( 'Add more', 'myd-delivery-pro' ); ?>
						</a>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="fdm-estimate-time-delivery">
						<?php esc_html_e( 'Tuesday', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<div class="myd-repeater">
						<div class="myd-repeater__rows">
							<?php if ( isset( $clock_option['tuesday'] ) ) : ?>
								<?php foreach ( $clock_option['tuesday'] as $k => $v ) : ?>
									<div
										class="myd-repeater__row"
										id="myd-repeater__row-<?php echo esc_attr( $k ); ?>"
										data-row="<?php echo esc_attr( $k ); ?>"
									>
										<span>
											<?php esc_html_e( 'Start at', 'myd-delivery-pro' ); ?>
										</span>
										<input
											name="myd-delivery-time[tuesday][<?php echo esc_attr( $k ); ?>][start]"
											type="time"
											id="myd-delivery-time[tuesday][<?php echo esc_attr( $k ); ?>][start]"
											value="<?php echo esc_attr( $v['start'] ); ?>"
											class="regular-text myd-input-repeater"
										>

										<span>
											<?php esc_html_e( 'End', 'myd-delivery-pro' ); ?>
										</span>
										<input
											name="myd-delivery-time[tuesday][<?php echo esc_attr( $k ); ?>][end]"
											type="time"
											id="myd-delivery-time[tuesday][<?php echo esc_attr( $k ); ?>][end]"
											value="<?php echo esc_attr( $v['end'] ); ?>"
											class="regular-text myd-input-repeater"
										>

										<span
											class="myd-repeater__remove"
											onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"
										>
											<?php echo esc_html_e( 'remove', 'myd-delivery-pro' ); ?>
										</span>
									</div>
								<?php endforeach; ?>

							<?php else : ?>
								<div
									class="myd-repeater__row"
									id="myd-repeater__row-0"
									data-row="0"
								>
									<span>
										<?php esc_html_e( 'Start at', 'myd-delivery-pro' ); ?>
									</span>
									<input
										name="myd-delivery-time[tuesday][0][start]"
										type="time"
										id="myd-delivery-time[tuesday][0][start]"
										value=""
										class="regular-text myd-input-repeater"
									>

									<span>
										<?php esc_html_e( 'End', 'myd-delivery-pro' ); ?>
									</span>
									<input
										name="myd-delivery-time[tuesday][0][end]"
										type="time"
										id="myd-delivery-time[tuesday][0][end]"
										value=""
										class="regular-text myd-input-repeater"
									>

									<span
										class="myd-repeater__remove"
										onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"
									>
										<?php echo esc_html_e( 'remove', 'myd-delivery-pro' ); ?>
									</span>
								</div>
							<?php endif; ?>
						</div>

						<a
							href="#"
							class="button button-small button-secondary myd-repeater__button"
							onclick="window.MydAdmin.mydRepeaterAddRow(event)"
						>
							<?php esc_html_e( 'Add more', 'myd-delivery-pro' ); ?>
						</a>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="fdm-estimate-time-delivery">
						<?php esc_html_e( 'Wednesday', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<div class="myd-repeater">
						<div class="myd-repeater__rows">
							<?php if ( isset( $clock_option['wednesday'] ) ) : ?>
								<?php foreach ( $clock_option['wednesday'] as $k => $v ) : ?>
									<div
										class="myd-repeater__row"
										id="myd-repeater__row-<?php echo esc_attr( $k ); ?>"
										data-row="<?php echo esc_attr( $k ); ?>"
									>
										<span>
											<?php esc_html_e( 'Start at', 'myd-delivery-pro' ); ?>
										</span>
										<input
											name="myd-delivery-time[wednesday][<?php echo esc_attr( $k ); ?>][start]"
											type="time"
											id="myd-delivery-time[wednesday][<?php echo esc_attr( $k ); ?>][start]"
											value="<?php echo esc_attr( $v['start'] ); ?>"
											class="regular-text myd-input-repeater"
										>

										<span>
											<?php esc_html_e( 'End', 'myd-delivery-pro' ); ?>
										</span>
										<input
											name="myd-delivery-time[wednesday][<?php echo esc_attr( $k ); ?>][end]"
											type="time"
											id="myd-delivery-time[wednesday][<?php echo esc_attr( $k ); ?>][end]"
											value="<?php echo esc_attr( $v['end'] ); ?>"
											class="regular-text myd-input-repeater"
										>

										<span
											class="myd-repeater__remove"
											onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"
										>
											<?php echo esc_html_e( 'remove', 'myd-delivery-pro' ); ?>
										</span>
									</div>
								<?php endforeach; ?>

							<?php else : ?>
								<div
									class="myd-repeater__row"
									id="myd-repeater__row-0"
									data-row="0"
								>
									<span>
										<?php esc_html_e( 'Start at', 'myd-delivery-pro' ); ?>
									</span>
									<input
										name="myd-delivery-time[wednesday][0][start]"
										type="time"
										id="myd-delivery-time[wednesday][0][start]"
										value=""
										class="regular-text myd-input-repeater"
									>

									<span>
										<?php esc_html_e( 'End', 'myd-delivery-pro' ); ?>
									</span>
									<input
										name="myd-delivery-time[wednesday][0][end]"
										type="time"
										id="myd-delivery-time[wednesday][0][end]"
										value=""
										class="regular-text myd-input-repeater"
									>

									<span
										class="myd-repeater__remove"
										onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"
									>
										<?php echo esc_html_e( 'remove', 'myd-delivery-pro' ); ?>
									</span>
								</div>
							<?php endif; ?>
						</div>

						<a
							href="#"
							class="button button-small button-secondary myd-repeater__button"
							onclick="window.MydAdmin.mydRepeaterAddRow(event)"
						>
							<?php esc_html_e( 'Add more', 'myd-delivery-pro' ); ?>
						</a>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="fdm-estimate-time-delivery">
						<?php esc_html_e( 'Thursday', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<div class="myd-repeater">
						<div class="myd-repeater__rows">
							<?php if ( isset( $clock_option['thursday'] ) ) : ?>
								<?php foreach ( $clock_option['thursday'] as $k => $v ) : ?>
									<div
										class="myd-repeater__row"
										id="myd-repeater__row-<?php echo esc_attr( $k ); ?>"
										data-row="<?php echo esc_attr( $k ); ?>"
									>
										<span>
											<?php esc_html_e( 'Start at', 'myd-delivery-pro' ); ?>
										</span>
										<input
											name="myd-delivery-time[thursday][<?php echo esc_attr( $k ); ?>][start]"
											type="time"
											id="myd-delivery-time[thursday][<?php echo esc_attr( $k ); ?>][start]"
											value="<?php echo esc_attr( $v['start'] ); ?>"
											class="regular-text myd-input-repeater"
										>

										<span>
											<?php esc_html_e( 'End', 'myd-delivery-pro' ); ?>
										</span>
										<input
											name="myd-delivery-time[thursday][<?php echo esc_attr($k); ?>][end]"
											type="time"
											id="myd-delivery-time[thursday][<?php echo esc_attr($k); ?>][end]"
											value="<?php echo esc_attr($v['end']); ?>"
											class="regular-text myd-input-repeater"
										>

										<span
											class="myd-repeater__remove"
											onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"
										>
											<?php echo esc_html_e( 'remove', 'myd-delivery-pro' ); ?>
										</span>
									</div>
								<?php endforeach; ?>

							<?php else : ?>
								<div
									class="myd-repeater__row"
									id="myd-repeater__row-0"
									data-row="0"
								>
									<span>
										<?php esc_html_e( 'Start at', 'myd-delivery-pro' ); ?>
									</span>
									<input
										name="myd-delivery-time[thursday][0][start]"
										type="time"
										id="myd-delivery-time[thursday][0][start]"
										value=""
										class="regular-text myd-input-repeater"
									>

									<span>
										<?php esc_html_e( 'End', 'myd-delivery-pro' ); ?>
									</span>
									<input
										name="myd-delivery-time[thursday][0][end]"
										type="time"
										id="myd-delivery-time[thursday][0][end]"
										value=""
										class="regular-text myd-input-repeater"
									>

									<span
										class="myd-repeater__remove"
										onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"
									>
										<?php echo esc_html_e( 'remove', 'myd-delivery-pro' ); ?>
									</span>
								</div>
							<?php endif; ?>
						</div>

						<a
							href="#"
							class="button button-small button-secondary myd-repeater__button"
							onclick="window.MydAdmin.mydRepeaterAddRow(event)"
						>
							<?php esc_html_e( 'Add more', 'myd-delivery-pro' ); ?>
						</a>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="fdm-estimate-time-delivery">
						<?php esc_html_e( 'Friday', 'myd-delivery-pro' ); ?>
					</label>
				</th>
				<td>
					<div class="myd-repeater">
						<div class="myd-repeater__rows">
							<?php if (isset($clock_option['friday'])) : ?>
								<?php foreach ($clock_option['friday'] as $k => $v) : ?>
									<div class="myd-repeater__row" id="myd-repeater__row-<?php echo esc_attr($k); ?>" data-row="<?php echo esc_attr($k); ?>">
										<span><?php esc_html_e('Start at', 'myd-delivery-pro'); ?></span>
										<input name="myd-delivery-time[friday][<?php echo esc_attr($k); ?>][start]" type="time" id="myd-delivery-time[friday][<?php echo esc_attr($k); ?>][start]" value="<?php echo esc_attr($v['start']); ?>" class="regular-text myd-input-repeater">

										<span><?php esc_html_e('End', 'myd-delivery-pro'); ?></span>
										<input name="myd-delivery-time[friday][<?php echo esc_attr($k); ?>][end]" type="time" id="myd-delivery-time[friday][<?php echo esc_attr($k); ?>][end]" value="<?php echo esc_attr($v['end']); ?>" class="regular-text myd-input-repeater">

										<span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"><?php echo esc_html_e('remove', 'myd-delivery-pro'); ?></span>
									</div>
								<?php endforeach; ?>

							<?php else : ?>
								<div class="myd-repeater__row" id="myd-repeater__row-0" data-row="0">
									<span><?php esc_html_e('Start at', 'myd-delivery-pro'); ?></span>
									<input name="myd-delivery-time[friday][0][start]" type="time" id="myd-delivery-time[friday][0][start]" value="" class="regular-text myd-input-repeater">

									<span><?php esc_html_e('End', 'myd-delivery-pro'); ?></span>
									<input name="myd-delivery-time[friday][0][end]" type="time" id="myd-delivery-time[friday][0][end]" value="" class="regular-text myd-input-repeater">

									<span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"><?php echo esc_html_e('remove', 'myd-delivery-pro'); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<a href="#" class="button button-small button-secondary myd-repeater__button" onclick="window.MydAdmin.mydRepeaterAddRow(event)"><?php esc_html_e('Add more', 'myd-delivery-pro'); ?></a>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="fdm-estimate-time-delivery"><?php esc_html_e('Saturday', 'myd-delivery-pro'); ?></label>
				</th>
				<td>
					<div class="myd-repeater">
						<div class="myd-repeater__rows">
							<?php if (isset($clock_option['saturday'])) : ?>
								<?php foreach ($clock_option['saturday'] as $k => $v) : ?>
									<div class="myd-repeater__row" id="myd-repeater__row-<?php echo esc_attr($k); ?>" data-row="<?php echo esc_attr($k); ?>">
										<span><?php esc_html_e('Start at', 'myd-delivery-pro'); ?></span>
										<input name="myd-delivery-time[saturday][<?php echo esc_attr($k); ?>][start]" type="time" id="myd-delivery-time[saturday][<?php echo esc_attr($k); ?>][start]" value="<?php echo esc_attr($v['start']); ?>" class="regular-text myd-input-repeater">

										<span><?php esc_html_e('End', 'myd-delivery-pro'); ?></span>
										<input name="myd-delivery-time[saturday][<?php echo esc_attr($k); ?>][end]" type="time" id="myd-delivery-time[saturday][<?php echo esc_attr($k); ?>][end]" value="<?php echo esc_attr($v['end']); ?>" class="regular-text myd-input-repeater">

										<span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"><?php echo esc_html_e('remove', 'myd-delivery-pro'); ?></span>
									</div>
								<?php endforeach; ?>

							<?php else : ?>
								<div class="myd-repeater__row" id="myd-repeater__row-0" data-row="0">
									<span><?php esc_html_e('Start at', 'myd-delivery-pro'); ?></span>
									<input name="myd-delivery-time[saturday][0][start]" type="time" id="myd-delivery-time[saturday][0][start]" value="" class="regular-text myd-input-repeater">

									<span><?php esc_html_e('End', 'myd-delivery-pro'); ?></span>
									<input name="myd-delivery-time[saturday][0][end]" type="time" id="myd-delivery-time[saturday][0][end]" value="" class="regular-text myd-input-repeater">

									<span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"><?php echo esc_html_e('remove', 'myd-delivery-pro'); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<a href="#" class="button button-small button-secondary myd-repeater__button" onclick="window.MydAdmin.mydRepeaterAddRow(event)"><?php esc_html_e('Add more', 'myd-delivery-pro'); ?></a>
					</div>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="fdm-estimate-time-delivery"><?php esc_html_e('Sunday', 'myd-delivery-pro'); ?></label>
				</th>
				<td>
					<div class="myd-repeater">
						<div class="myd-repeater__rows">
							<?php if (isset($clock_option['sunday'])) : ?>
								<?php foreach ($clock_option['sunday'] as $k => $v) : ?>
									<div class="myd-repeater__row" id="myd-repeater__row-<?php echo esc_attr($k); ?>" data-row="<?php echo esc_attr($k); ?>">
										<span><?php esc_html_e('Start at', 'myd-delivery-pro'); ?></span>
										<input name="myd-delivery-time[sunday][<?php echo esc_attr($k); ?>][start]" type="time" id="myd-delivery-time[sunday][<?php echo esc_attr($k); ?>][start]" value="<?php echo esc_attr($v['start']); ?>" class="regular-text myd-input-repeater">

										<span><?php esc_html_e('End', 'myd-delivery-pro'); ?></span>
										<input name="myd-delivery-time[sunday][<?php echo esc_attr($k); ?>][end]" type="time" id="myd-delivery-time[sunday][<?php echo esc_attr($k); ?>][end]" value="<?php echo esc_attr($v['end']); ?>" class="regular-text myd-input-repeater">

										<span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"><?php echo esc_html_e('remove', 'myd-delivery-pro'); ?></span>
									</div>
								<?php endforeach; ?>

							<?php else : ?>
								<div class="myd-repeater__row" id="myd-repeater__row-0" data-row="0">
									<span><?php esc_html_e('Start at', 'myd-delivery-pro'); ?></span>
									<input name="myd-delivery-time[sunday][0][start]" type="time" id="myd-delivery-time[sunday][0][start]" value="" class="regular-text myd-input-repeater">

									<span><?php esc_html_e('End', 'myd-delivery-pro'); ?></span>
									<input name="myd-delivery-time[sunday][0][end]" type="time" id="myd-delivery-time[sunday][0][end]" value="" class="regular-text myd-input-repeater">

									<span class="myd-repeater__remove" onclick="window.MydAdmin.mydRepeaterRemoveRow(event)"><?php echo esc_html_e('remove', 'myd-delivery-pro'); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<a href="#" class="button button-small button-secondary myd-repeater__button" onclick="window.MydAdmin.mydRepeaterAddRow(event)"><?php esc_html_e('Add more', 'myd-delivery-pro'); ?></a>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</div>


<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['myd-preparation-time'])) {
    if ( current_user_can('manage_options') ) {
        // Verifica nonce padrão do options.php, se existir
        if ( isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'fmd-settings-group-options') ) {
            update_option('myd-preparation-time', intval($_POST['myd-preparation-time']));
        }
    }
}
?>

