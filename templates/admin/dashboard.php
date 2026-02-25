<?php

use MydPro\Includes\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$license = Plugin::instance()->license;
$license_key = $license->get_key();
$license_status = $license->get_status();
$license_error = $license->get_error();
$status_tag = [
	'error' => 'myd-license-status--error',
	'active' => 'myd-license-status--success',
	'expired' => 'myd-license-status--invalid',
	'deactivated' => 'myd-license-status--invalid',
	'' => 'myd-license-status--invalid',
	'mismatch' => 'myd-license-status--invalid',
];

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Dashboard', 'myd-delivery-pro' ); ?></h1>

	<section class="myd-custom-content-page">
		<div class="myd-admin-cards myd-card-1columns">
			<div class="myd-admin-cards__item myd-card--20padding">
				<span class="myd-license-status-label">
					<?php esc_html_e( 'License Status', 'myd-delivery-pro' );?>
				</span>
				<span class="myd-license-status-tag <?php echo esc_attr( $status_tag[ $license_status ] ); ?>">
					<?php echo esc_html( $license_status ); ?>
				</span>

				<?php if ( $license_status === 'error' || $license_status === 'mismatch' && ! empty( $license_error ) ) : ?>
					<p class="myd-admin-license-erro"><?php echo esc_html( $license_error['message'] ?? 'Undefined Error. Contact the support.' ); ?></p>
				<?php endif; ?>

				<?php if ( myd_user_is_allowed_admin() ) : ?>
					<form action="" method="post">
						<table class="form-table">
							<tbody>
								<tr>
									<th>
										<label for="fdm-license"><?php esc_html_e( 'License Key', 'myd-delivery-pro' ); ?></label>
									</th>
									<td>
										<input class="regular-text" id="fdm-license" type="text" name="fdm-license"  value="<?php echo esc_html( str_replace( substr( ( $license_key  ), 0, 5 ), '****', $license_key ) ); ?>">
									</td>
								</tr>
						</table>

						<p class="submit">
							<button type="submit" name="myd-active-license" id="myd-active-license" class="button button-primary">
								<?php esc_html_e( 'Activate', 'myd-delivery-pro' ); ?>
							</button>
							<button type="submit" name="myd-deactivate-license" id="myd-deactivate-license" class="button">
								<?php esc_html_e( 'Deactivate', 'myd-delivery-pro'); ?>
							</button>
						</p>
					</form>

					<hr>

				<?php else : ?>
					<h4><?php esc_html_e( 'Note: You need a admin permissions to mange license.', 'myd-delivery-pro' ); ?></h4>
				<?php endif; ?>

				
			</div>
		</div>

		<?php if ( $license_status === 'active' || $license_status === 'expired' ) : ?>
			<div class="myd-admin-cards myd-card-3columns">
				<div class="myd-admin-cards__item myd-cards--flex-centered myd-card--20padding">
					<img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/package.png'); ?>" width="100px" alt="products">
					<h3 class="myd-admin-cards__title"><?php esc_html_e( 'Products', 'myd-delivery-pro' );?></h3>
					<p class="myd-admin-cards__description"><?php esc_html_e( 'Create, edit and manage your products', 'myd-delivery-pro' ); ?></p>
					<a class="button button-primary myd-cards--margin-top10" href="<?php echo esc_attr( site_url( '/wp-admin/edit.php?post_type=mydelivery-produtos' ) ); ?>">
						<?php echo esc_html_e( 'Go to Products', 'myd-delivery-pro' );?>
					</a>
				</div>

				<div class="myd-admin-cards__item myd-cards--flex-centered myd-card--20padding">
					<img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/orders.png');?>" width="100px" alt="orders">
					<h3 class="myd-admin-cards__title"><?php esc_html_e( 'Orders', 'myd-delivery-pro' ); ?></h3>
					<p class="myd-admin-cards__description"><?php esc_html_e( 'Check all your orders and manage it', 'myd-delivery-pro' ); ?></p>
					<a class="button button-primary myd-cards--margin-top10" href="<?php echo esc_attr( site_url( '/wp-admin/edit.php?post_type=mydelivery-orders' ) ); ?>">
						<?php echo esc_html_e( 'Go to Orders', 'myd-delivery-pro' );?>
					</a>
				</div>

				<div class="myd-admin-cards__item myd-cards--flex-centered myd-card--20padding">
					<img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/customers.png');?>" width="100px" alt="customers">
					<h3 class="myd-admin-cards__title"><?php esc_html_e( 'Customers', 'myd-delivery-pro' ); ?></h3>
					<p class="myd-admin-cards__description"><?php esc_html_e( 'Manage all your customers', 'myd-delivery-pro' ); ?></p>
					<a class="button button-primary myd-cards--margin-top10" href="<?php echo esc_attr( site_url( '/wp-admin/admin.php?page=myd-delivery-customers' ) ); ?>">
						<?php echo esc_html_e( 'Go to Customers', 'myd-delivery-pro' ); ?>
					</a>
				</div>

				<div class="myd-admin-cards__item myd-cards--flex-centered myd-card--20padding">
					<img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/coupon.png');?>" width="100px" alt="coupons">
					<h3 class="myd-admin-cards__title"><?php esc_html_e( 'Coupons', 'myd-delivery-pro' ); ?></h3>
					<p class="myd-admin-cards__description"><?php esc_html_e( 'Do you want give a discount? Use your cupons for it', 'myd-delivery-pro' ); ?></p>
					<a class="button button-primary myd-cards--margin-top10" href="<?php echo esc_attr( site_url( '/wp-admin/edit.php?post_type=mydelivery-coupons' ) ); ?>">
						<?php echo esc_html_e( 'Go to Coupons', 'myd-delivery-pro' );?>
					</a>
				</div>

				<div class="myd-admin-cards__item myd-cards--flex-centered myd-card--20padding">
					<img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/reports.png');?>" width="100px" alt="reports">
					<h3 class="myd-admin-cards__title"><?php esc_html_e( 'Reports', 'myd-delivery-pro' ); ?></h3>
					<p class="myd-admin-cards__description"><?php esc_html_e( 'Check many info about your store, orders and more', 'myd-delivery-pro' ); ?></p>
					<a class="button button-primary myd-cards--margin-top10" href="<?php echo esc_attr( site_url( '/wp-admin/admin.php?page=myd-delivery-reports' ) ); ?>">
						<?php echo esc_html_e( 'Go to Reports', 'myd-delivery-pro' );?>
					</a>
				</div>

				<div class="myd-admin-cards__item myd-cards--flex-centered myd-card--20padding">
					<img src="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/img/settings.png');?>" width="100px" alt="settings">
					<h3 class="myd-admin-cards__title"><?php esc_html_e( 'Settings', 'myd-delivery-pro' ); ?></h3>
					<p class="myd-admin-cards__description"><?php esc_html_e( 'Manage your Store settings here', 'myd-delivery-pro' ); ?></p>
					<a class="button button-primary myd-cards--margin-top10" href="<?php echo esc_attr( site_url( '/wp-admin/admin.php?page=myd-delivery-settings' ) ); ?>">
						<?php echo esc_html_e( 'Go to Settings', 'myd-delivery-pro' );?>
					</a>
				</div>
			</div>
		<?php endif; ?>
	</section>
</div>
