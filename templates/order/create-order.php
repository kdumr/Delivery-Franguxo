<?php
/**
 * Template: Criar Pedido Manual
 * Página dedicada para criar pedidos manuais a partir do painel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Verificar se usuário tem permissão
if ( ! current_user_can( 'edit_posts' ) ) {
	wp_die( __( 'Você não tem permissão para acessar esta página.', 'myd-delivery-pro' ) );
}

$currency_symbol = \MydPro\Includes\Store_Data::get_store_data( 'currency_simbol' );

// Obter configurações de entrega
$shipping_type = get_option( 'myd-delivery-mode' );
$shipping_options_all = get_option( 'myd-delivery-mode-options' );
$shipping_options = isset( $shipping_options_all[ $shipping_type ] ) ? $shipping_options_all[ $shipping_type ] : [];

// Configurações por bairro
$neighborhoods = [];
if ( in_array( $shipping_type, [ 'per-neighborhood', 'fixed-per-neighborhood' ], true ) && isset( $shipping_options['options'] ) ) {
	foreach ( $shipping_options['options'] as $opt ) {
		$neighborhoods[] = [
			'name'  => $opt['from'],
			'price' => isset( $opt['price'] ) ? floatval( $opt['price'] ) : 0,
		];
	}
}
$fixed_price = isset( $shipping_options['price'] ) ? floatval( $shipping_options['price'] ) : 0;

// Configurações per-distance
$google_api_key = '';
$origin_lat = 0;
$origin_lng = 0;
$distance_ranges = [];
if ( $shipping_type === 'per-distance' ) {
	$google_api_key = get_option( 'myd-shipping-distance-google-api-key', '' );
	$origin_lat = floatval( get_option( 'myd-shipping-distance-address-latitude', 0 ) );
	$origin_lng = floatval( get_option( 'myd-shipping-distance-address-longitude', 0 ) );
	if ( isset( $shipping_options['options'] ) && is_array( $shipping_options['options'] ) ) {
		foreach ( $shipping_options['options'] as $opt ) {
			$distance_ranges[] = [
				'from'  => floatval( $opt['from'] ?? 0 ),
				'to'    => floatval( $opt['to'] ?? 0 ),
				'price' => floatval( $opt['price'] ?? 0 ),
			];
		}
	}
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php esc_html_e( 'Criar Pedido Manual', 'myd-delivery-pro' ); ?> - <?php bloginfo( 'name' ); ?></title>
	<link rel="stylesheet" href="<?php echo plugins_url( 'assets/css/order-panel-frontend.min.css', dirname( __FILE__, 2 ) . '/myd-delivery-pro.php' ); ?>">
	<link rel="stylesheet" href="<?php echo plugins_url( 'assets/css/create-order.min.css', dirname( __FILE__, 2 ) . '/myd-delivery-pro.php' ); ?>">
	<style>
		* { box-sizing: border-box; }
		body {
			margin: 0;
			padding: 0;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
			background: #f5f6f7;
			color: #333;
		}
		.myd-create-order-page {
			max-width: 800px;
			margin: 0 auto;
			padding: 24px;
		}
		.myd-page-header {
			display: flex;
			align-items: center;
			gap: 16px;
			margin-bottom: 24px;
			padding-bottom: 16px;
			border-bottom: 1px solid #e0e0e0;
		}
		.myd-back-btn {
			display: flex;
			align-items: center;
			gap: 6px;
			padding: 8px 16px;
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 6px;
			color: #333;
			text-decoration: none;
			font-size: 14px;
			cursor: pointer;
			transition: all 0.2s;
		}
		.myd-back-btn:hover {
			background: #f5f5f5;
			border-color: #ccc;
		}
		.myd-page-title {
			font-size: 24px;
			font-weight: 600;
			margin: 0 auto;
			text-align: center;
		}
		.myd-form-card {
			background: #fff;
			border-radius: 12px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.08);
			padding: 24px;
		}
		.myd-form-section {
			margin-bottom: 24px;
			padding-bottom: 24px;
			border-bottom: 1px solid #eee;
		}
		.myd-form-section:last-child {
			margin-bottom: 0;
			padding-bottom: 0;
			border-bottom: none;
		}
		.myd-section-title {
			font-size: 16px;
			font-weight: 600;
			margin: 0 0 16px;
			color: #222;
		}
		.myd-form-group label {
			display: block;
			font-size: 14px;
			font-weight: 500;
			margin-bottom: 6px;
			color: #444;
		}
		.myd-form-group input,
		.myd-form-group select,
		.myd-form-group textarea {
			width: 100%;
			padding: 10px 12px;
			border: 1px solid #ddd;
			border-radius: 6px;
			font-size: 14px;
			transition: border-color 0.2s;
		}
		.myd-form-group input:focus,
		.myd-form-group select:focus,
		.myd-form-group textarea:focus {
			outline: none;
			border-color: #f38a00;
		}
		.myd-form-row {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 16px;
		}
		.myd-form-row-3 {
			display: grid;
			grid-template-columns: 100px 1fr 1fr;
			gap: 16px;
		}
		.myd-radio-group {
			display: flex;
			gap: 16px;
			margin-top: 8px;
		}
		.myd-radio-group label {
			display: flex;
			align-items: center;
			gap: 6px;
			cursor: pointer;
			font-weight: 400;
		}
		.myd-product-search-wrap {
			position: relative;
		}
		.myd-product-results {
			display: none;
			position: absolute;
			top: 100%;
			left: 0;
			right: 0;
			background: #fff;
			border: 1px solid #ddd;
			border-top: none;
			border-radius: 0 0 6px 6px;
			max-height: 250px;
			overflow-y: auto;
			z-index: 100;
			box-shadow: 0 4px 12px rgba(0,0,0,0.1);
		}
		.myd-product-result-item {
			padding: 12px;
			cursor: pointer;
			border-bottom: 1px solid #eee;
			display: flex;
			justify-content: space-between;
			align-items: center;
			transition: background 0.15s;
		}
		.myd-product-result-item:hover {
			background: #f8f9fa;
		}
		.myd-product-result-item:last-child {
			border-bottom: none;
		}
		.myd-order-items {
			margin-top: 12px;
		}
		.myd-order-item {
			display: flex;
			align-items: center;
			gap: 12px;
			padding: 12px;
			background: #f8f9fa;
			border: 1px solid #eee;
			border-radius: 8px;
			margin-bottom: 8px;
		}
		.myd-order-item-info {
			flex: 1;
		}
		.myd-order-item-name {
			font-weight: 500;
			margin-bottom: 4px;
		}
		.myd-order-item-price {
			font-size: 13px;
			color: #666;
		}
		.myd-qty-controls {
			display: flex;
			align-items: center;
			gap: 8px;
		}
		.myd-qty-btn {
			width: 32px;
			height: 32px;
			border: 1px solid #ddd;
			border-radius: 6px;
			background: #fff;
			cursor: pointer;
			font-size: 18px;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: all 0.15s;
		}
		.myd-qty-btn:hover {
			background: #f5f5f5;
			border-color: #ccc;
		}
		.myd-order-item-total {
			min-width: 90px;
			text-align: right;
			font-weight: 600;
		}
		.myd-remove-item {
			width: 32px;
			height: 32px;
			border: none;
			background: #fee;
			color: #c00;
			border-radius: 6px;
			cursor: pointer;
			font-size: 16px;
			transition: all 0.15s;
		}
		.myd-remove-item:hover {
			background: #fcc;
		}
		.myd-order-summary {
			background: #f8f9fa;
			padding: 20px;
			border-radius: 8px;
			margin-top: 16px;
		}
		.myd-summary-row {
			display: flex;
			justify-content: space-between;
			margin-bottom: 10px;
			font-size: 14px;
		}
		.myd-summary-row.total {
			font-weight: 700;
			font-size: 18px;
			border-top: 1px solid #ddd;
			padding-top: 12px;
			margin-top: 12px;
			margin-bottom: 0;
		}
		.myd-form-actions {
			display: flex;
			gap: 12px;
			justify-content: flex-end;
			margin-top: 24px;
		}
		.myd-btn {
			padding: 12px 24px;
			border-radius: 8px;
			font-size: 15px;
			font-weight: 500;
			cursor: pointer;
			transition: all 0.2s;
			border: none;
		}
		.myd-btn-cancel {
			background: #f0f0f0;
			color: #333;
		}
		.myd-btn-cancel:hover {
			background: #e0e0e0;
		}
		.myd-btn-save {
			background: #f38a00;
			color: #fff;
		}
		.myd-btn-save:hover {
			background: #e07d00;
		}
		.myd-btn-save:disabled {
			background: #ccc;
			cursor: not-allowed;
		}
		.myd-empty-items {
			text-align: center;
			padding: 24px;
			color: #999;
			font-size: 14px;
		}
		.myd-toast-container {
			position: fixed;
			bottom: 24px;
			right: 24px;
			z-index: 10000;
		}
		.myd-toast {
			background: #333;
			color: #fff;
			padding: 14px 20px;
			border-radius: 8px;
			margin-top: 8px;
			box-shadow: 0 4px 12px rgba(0,0,0,0.2);
			animation: slideIn 0.3s ease;
		}
		.myd-toast.success {
			background: #28a745;
		}
		.myd-toast.error {
			background: #dc3545;
		}
		@keyframes slideIn {
			from { opacity: 0; transform: translateX(100%); }
			to { opacity: 1; transform: translateX(0); }
		}
		@media (max-width: 600px) {
			.myd-form-row,
			.myd-form-row-3 {
				grid-template-columns: 1fr;
			}
			.myd-page-header {
				flex-direction: column;
				align-items: flex-start;
			}
		}
		
		/* ===== MAP PICKER MODAL ===== */
		#myd-map-picker {
			position: fixed;
			inset: 0;
			display: none;
			align-items: center;
			justify-content: center;
			background: rgba(0,0,0,0.55);
			z-index: 9999;
		}
		.myd-map-picker__inner {
			width: min(90vw, 640px);
			background: #fff;
			border-radius: 12px;
			box-shadow: 0 18px 40px rgba(0,0,0,0.22);
			overflow: hidden;
			display: flex;
			flex-direction: column;
			max-height: 90vh;
		}
		.myd-map-picker__header {
			padding: 14px 18px;
			font-weight: 600;
			border-bottom: 1px solid rgba(0,0,0,0.08);
		}
		.myd-map-picker__map {
			height: 360px;
			width: 100%;
		}
		.myd-map-picker__footer {
			display: flex;
			justify-content: flex-end;
			gap: 12px;
			padding: 14px 18px;
			border-top: 1px solid rgba(0,0,0,0.08);
		}
		.myd-map-picker__btn-cancel {
			padding: 10px 18px;
			border-radius: 6px;
			border: 1px solid #ea1d2c;
			color: #ea1d2c;
			background: #fff;
			cursor: pointer;
		}
		.myd-map-picker__btn-cancel:hover {
			background-color: #fef2f2;
		}
		.myd-map-picker__btn-confirm {
			padding: 10px 18px;
			border-radius: 6px;
			border: none;
			background: #f38a00;
			color: #ffffff;
			font-weight: 600;
			cursor: pointer;
		}
		.myd-map-picker__btn-confirm:hover {
			background-color: #e07d00;
		}
		.myd-map-picker__details-view {
			display: none;
			flex-direction: column;
		}
		.myd-map-picker__details-minimap {
			height: 180px;
			width: 100%;
			position: relative;
			overflow: hidden;
		}
		.myd-map-picker__back-btn {
			position: absolute;
			top: 12px;
			left: 12px;
			width: 48px;
			height: 48px;
			border-radius: 50%;
			border: none;
			background: #f38a00;
			color: #fff;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			z-index: 10;
			box-shadow: 0 2px 8px rgba(0,0,0,0.2);
		}
		.myd-map-picker__back-btn:hover {
			background-color: #e07d00;
		}
		.myd-map-picker__address-label {
			position: absolute;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -100%);
			background: #fff;
			padding: 6px 12px;
			border-radius: 4px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.15);
			font-size: 12px;
			font-weight: 600;
			color: #1f2937;
			z-index: 5;
			white-space: nowrap;
		}
		.myd-map-picker__details-address {
			padding: 16px 18px;
			border-bottom: 1px solid rgba(0,0,0,0.08);
		}
		.myd-map-picker__address-main {
			font-size: 16px;
			font-weight: 600;
			color: #1f2937;
		}
		.myd-map-picker__address-secondary {
			font-size: 14px;
			color: #6b7280;
			margin-top: 4px;
		}
		.myd-map-picker__details-form {
			padding: 16px 18px;
			display: flex;
			flex-direction: column;
			gap: 16px;
		}
		.myd-map-picker__form-row {
			display: flex;
			gap: 12px;
		}
		.myd-map-picker__field-wrapper {
			display: flex;
			flex-direction: column;
		}
		.myd-map-picker__field-wrapper--number {
			flex: 0 0 120px;
		}
		.myd-map-picker__field-wrapper--complement,
		.myd-map-picker__field-wrapper--reference {
			flex: 1;
		}
		.myd-map-picker__label {
			font-size: 12px;
			font-weight: 500;
			color: #6b7280;
			margin-bottom: 4px;
		}
		.myd-map-picker__input {
			padding: 12px;
			border: 1px solid #d0d5dd;
			border-radius: 8px;
			font-size: 16px;
			outline: none;
			transition: border-color 0.2s ease;
		}
		.myd-map-picker__input:focus {
			border-color: #f38a00;
		}
		.myd-map-picker__details-footer {
			padding: 16px 18px;
			border-top: 1px solid rgba(0,0,0,0.08);
			margin-top: auto;
		}
		.myd-map-picker__btn-save {
			width: 100%;
			padding: 14px 18px;
			border-radius: 8px;
			border: none;
			background: #f38a00;
			color: #fff;
			font-weight: 600;
			font-size: 16px;
			cursor: pointer;
		}
		.myd-map-picker__btn-save:hover {
			background-color: #e07d00;
		}
		.myd-map-picker__minimap-container {
			width: 100%;
			height: 100%;
			position: absolute;
			top: 0;
			left: 0;
			z-index: 1;
		}
		.myd-map-picker__drag-card {
			position: absolute;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 10px 14px;
			background: #ffffff;
			border-radius: 12px;
			box-shadow: 0 6px 24px rgba(15,23,42,0.22);
			transform: translate(-50%, calc(-100% - 52px));
			min-width: 200px;
			max-width: 240px;
			text-align: center;
			font-size: 14px;
			font-weight: 600;
			color: #1f2937;
			user-select: none;
			pointer-events: auto;
			cursor: grab;
		}
		.myd-map-picker__drag-card-title {
			margin-bottom: 4px;
			font-size: 15px;
		}
		.myd-map-picker__drag-card-subtitle {
			font-size: 12px;
			font-weight: 500;
			color: #6b7280;
		}
		.myd-map-picker__drag-card-arrow {
			position: absolute;
			bottom: -6px;
			left: 50%;
			width: 0;
			height: 0;
			transform: translateX(-50%);
			border-left: 10px solid transparent;
			border-right: 10px solid transparent;
			border-top: 12px solid #ffffff;
		}
		.myd-autocomplete-input {
			width: 100%;
			padding: 14px 16px;
			border: 1px solid #d0d5dd;
			border-radius: 8px;
			font-size: 15px;
			transition: border-color 0.2s;
		}
		.myd-autocomplete-input:focus {
			outline: none;
			border-color: #f38a00;
		}
		.myd-autocomplete-input::placeholder {
			color: #9ca3af;
		}
		.myd-autocomplete-inner { position: relative; }
		.myd-address-preview { display: none; margin-bottom: 16px; }
		.myd-address-info-title { font-size:14px; font-weight:600; color:#374151; margin:0 0 12px 0; }
		.myd-address-row { display:flex; align-items:flex-start; gap:12px; }
		.myd-address-icon-wrap { flex:0 0 24px; padding-top:2px; }
		.myd-address-content { flex:1; min-width:0; }
		#myd-address-line1 { font-weight:600; font-size:15px; color:#1f2937; }
		#myd-address-line2 { font-size:14px; color:#4b5563; margin-top:2px; }
		#myd-address-line3 { font-size:13px; color:#6b7280; margin-top:2px; }
		#myd-address-change { background:none; border:none; color:#2563eb; cursor:pointer; font-size:14px; font-weight:500; white-space:nowrap; }
		.myd-no-number-label { display:flex; align-items:center; gap:8px; margin-top:12px; cursor:pointer; font-size:14px; color:#4b5563; }
		.myd-no-number { width:18px; height:18px; cursor:pointer; }
		.myd-address-extra { display:none; }
		.myd-delivery-fee-display { padding:10px 12px; background:#f8f9fa; border-radius:6px; font-weight:600; color:#333; }
		.myd-autocomplete-suggestions {
			display: none;
			position: absolute;
			top: 100%;
			left: 0;
			right: 0;
			background: #fff;
			border: 1px solid #e5e7eb;
			border-top: none;
			border-radius: 0 0 8px 8px;
			max-height: 300px;
			overflow-y: auto;
			z-index: 1000;
			box-shadow: 0 4px 12px rgba(0,0,0,0.12);
		}
		.myd-autocomplete-suggestion {
			display: flex;
			align-items: center;
			padding: 12px 14px;
			cursor: pointer;
			border-bottom: 1px solid rgba(0,0,0,0.04);
			transition: background 0.15s;
		}
		.myd-autocomplete-suggestion:hover,
		.myd-autocomplete-suggestion--active {
			background: #f9fafb;
		}
		.myd-autocomplete-suggestion:last-child {
			border-bottom: none;
		}
		.myd-autocomplete-suggestion__icon {
			flex: 0 0 36px;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 10px;
			color: #f38a00;
		}
		.myd-autocomplete-suggestion__content {
			flex: 1;
			min-width: 0;
		}
		.myd-autocomplete-suggestion__main {
			font-weight: 600;
			font-size: 15px;
			color: #1f2937;
			white-space: normal;
			word-break: break-word;
		}
		.myd-autocomplete-suggestion__secondary {
			font-size: 13px;
			color: #6b7280;
			margin-top: 2px;
		}
		.myd-no-products { padding:12px; color:#666; }
		.myd-product-result-price { color:#666; font-size:13px; }
		.myd-extras-list { margin-top:4px; }
		.myd-extra-item-line { font-size:12px; color:#f38a00; }
		.myd-item-note { margin-top:4px; font-size:12px; color:#888; font-style:italic; }
		.myd-qty-count { min-width:24px; text-align:center; display:inline-block; }
		.myd-autocomplete-typing {
			padding: 12px 14px;
			color: #6b7280;
			font-size: 14px;
		}
		/* Loading overlay */
		.myd-loading-overlay {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(255,255,255,0.92);
			display: none;
			align-items: center;
			justify-content: center;
			flex-direction: column;
			z-index: 10000;
		}
		.myd-loading-overlay.active {
			display: flex;
		}
		.myd-loading-spinner {
			width: 48px;
			height: 48px;
			border: 4px solid #e5e7eb;
			border-top-color: #f38a00;
			border-radius: 50%;
			animation: myd-spin 0.8s linear infinite;
		}
		@keyframes myd-spin {
			to { transform: rotate(360deg); }
		}
		.myd-loading-text {
			margin-top: 16px;
			font-size: 15px;
			color: #4b5563;
			font-weight: 500;
		}
		/* Popup de Extras */
		.myd-extras-overlay {
			position: fixed;
			top: 0; left: 0; right: 0; bottom: 0;
			background: rgba(0,0,0,0.5);
			display: none;
			align-items: center;
			justify-content: center;
			z-index: 10001;
		}
		.myd-extras-overlay.active {
			display: flex;
		}
		.myd-extras-popup {
			background: #fff;
			border-radius: 12px;
			width: 90%;
			max-width: 480px;
			max-height: 85vh;
			display: flex;
			flex-direction: column;
			box-shadow: 0 20px 60px rgba(0,0,0,0.2);
			animation: myd-popup-in 0.2s ease-out;
		}
		@keyframes myd-popup-in {
			from { opacity: 0; transform: scale(0.95) translateY(10px); }
			to { opacity: 1; transform: scale(1) translateY(0); }
		}
		.myd-extras-popup__header {
			padding: 20px 24px 16px;
			border-bottom: 1px solid #e5e7eb;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.myd-extras-popup__title {
			font-size: 18px;
			font-weight: 700;
			color: #1f2937;
			margin: 0;
		}
		.myd-extras-popup__close {
			width: 32px; height: 32px;
			border: none; background: #f3f4f6;
			border-radius: 50%; cursor: pointer;
			font-size: 18px; color: #6b7280;
			display: flex; align-items: center; justify-content: center;
			transition: all 0.15s;
		}
		.myd-extras-popup__close:hover {
			background: #e5e7eb; color: #374151;
		}
		.myd-extras-popup__body {
			padding: 16px 24px;
			overflow-y: auto;
			flex: 1;
		}
		.myd-extras-popup__footer {
			padding: 16px 24px;
			border-top: 1px solid #e5e7eb;
			display: flex;
			gap: 10px;
		}
		.myd-extras-popup__footer button {
			flex: 1;
			padding: 12px;
			border-radius: 8px;
			font-size: 15px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.15s;
		}
		.myd-extras-btn-cancel {
			background: #f3f4f6;
			border: 1px solid #e5e7eb;
			color: #4b5563;
		}
		.myd-extras-btn-cancel:hover {
			background: #e5e7eb;
		}
		.myd-extras-btn-confirm {
			background: #f38a00;
			border: none;
			color: #fff;
		}
		.myd-extras-btn-confirm:hover {
			background: #e07d00;
		}
		.myd-extras-btn-confirm:disabled {
			background: #d1d5db;
			cursor: not-allowed;
		}
		.myd-extras-loading {
			text-align: center;
			padding: 40px;
			color: #9ca3af;
		}

		/* ===== Estilos do template de extras (frontend) ===== */
		.myd-product-extra-wrapper {
			position: relative;
			margin-bottom: 16px;
		}
		.myd-product-extra-wrapper:last-child {
			margin-bottom: 0;
		}
		.fdm-extra-option-title {
			margin-bottom: 10px;
			width: 100%;
		}
		.fdm-extra-option-title-text {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			gap: 6px;
			margin-bottom: 10px;
			padding-bottom: 8px;
			border-bottom: 1px solid #e5e7eb;
		}
		.fdm-extra-option-limit-text {
			font-size: 15px;
			font-weight: 600;
			color: #1f2937;
		}
		.fdm-extra-option-limit-desc {
			font-size: 12px;
			color: #9ca3af;
		}
		.fdm-extra-option-required {
			color: #ea1d2b;
			font-size: 12px;
			font-weight: 600;
		}
		.myd-extra-item-loop {
			align-items: center;
			display: flex;
			flex-wrap: nowrap;
			min-height: 60px;
			position: relative;
			padding: 8px 0;
		}
		.myd-extra-item-loop-text {
			display: flex;
			flex-wrap: wrap;
			padding-right: 12px;
			flex: 1;
		}
		.myd-extra-label {
			width: 100%;
			font-size: 14px;
			font-weight: 500;
			color: #374151;
			cursor: default;
		}
		.myd-extra-description {
			font-size: 13px;
			line-height: 1.3;
			color: #9ca3af;
			margin: 2px 0 4px;
			width: 100%;
		}
		.myd-extra-price {
			color: #50a773;
			font-size: 14px;
			font-weight: 700;
		}
		.myd-extra-item-loop-checkbox {
			flex-shrink: 0;
		}
		.myd-qty-control {
			display: flex;
			align-items: center;
			gap: 0;
		}
		.myd-qty-minus, .myd-qty-plus {
			width: 32px;
			height: 32px;
			display: flex;
			justify-content: center;
			align-items: center;
			border: 1px solid #ddd;
			cursor: pointer;
			font-size: 18px;
			font-weight: 700;
			background: #fff;
			color: #374151;
			transition: all 0.15s;
		}
		.myd-qty-minus {
			border-radius: 5px 0 0 5px;
		}
		.myd-qty-plus {
			border-radius: 0 5px 5px 0;
		}
		.myd-qty-minus:hover, .myd-qty-plus:hover {
			background: #f9fafb;
			border-color: #bbb;
		}
		.myd-qty-plus:disabled,
		.myd-qty-minus:disabled {
			opacity: 0.4;
			pointer-events: none;
		}
		.myd-extras-popup__body input[type="number"].option_prod_exta {
			width: 40px;
			height: 32px;
			text-align: center;
			border: 1px solid #ddd;
			border-left: none;
			border-right: none;
			font-size: 14px;
			font-weight: 600;
			-moz-appearance: textfield;
			background: #fff;
		}
		.myd-extras-popup__body input[type="number"].option_prod_exta::-webkit-inner-spin-button,
		.myd-extras-popup__body input[type="number"].option_prod_exta::-webkit-outer-spin-button {
			-webkit-appearance: none;
			margin: 0;
		}
		.myd-space-extras {
			border: none;
			border-top: 1px dashed #eaeaea;
			margin: 4px 0;
		}
		.myd-product-item__not-available {
			background: red;
			color: #fff;
			font-size: 0.85rem;
			padding: 3px 8px;
			position: absolute;
			right: 0;
			top: 0;
			white-space: nowrap;
			z-index: 2;
			border-radius: 3px;
		}
		.myd-product-item__not-available-overlay {
			background: #fff;
			bottom: 0; left: 0; right: 0; top: 0;
			opacity: 0.6;
			position: absolute;
			z-index: 1;
		}
		.myd-extras-popup__body .myd-extra__clickable-label {
			display: none;
		}
		/* Observação do produto no popup de extras */
		.myd-product-note-wrap {
			padding: 12px 0 0;
			border-top: 1px dashed #eaeaea;
			margin-top: 8px;
		}
		.myd-product-note-wrap label {
			display: block;
			font-size: 13px;
			font-weight: 600;
			color: #444;
			margin-bottom: 6px;
		}
		.myd-product-note-wrap textarea {
			width: 100%;
			min-height: 60px;
			padding: 8px 10px;
			border: 1px solid #ddd;
			border-radius: 6px;
			font-size: 13px;
			line-height: 1.4;
			resize: vertical;
			box-sizing: border-box;
			background: #fafafa;
			transition: border-color .2s;
		}
		.myd-product-note-wrap textarea:focus {
			outline: none;
			border-color: #f38a00;
			background: #fff;
		}
	</style>
</head>
<body>
	<!-- Loading Overlay -->
	<div id="myd-loading-overlay" class="myd-loading-overlay">
		<div class="myd-loading-spinner"></div>
	</div>

	<!-- Alignment helper styles (temporary) -->
	<style>
	/* Garante largura fixa para ícones e alinha labels/títulos */
	.myd-form-card { position: relative; }
	.myd-form-group label, .myd-section-title { display: flex; align-items: center; }
	/* Ícones menores e consistentes */
	.myd-field-icon, .myd-section-icon { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 20px; width: 20px; height: 20px; margin-right: 10px; }
	.myd-field-icon svg, .myd-section-icon svg { width: 100%; height: 100%; display: block; }
	/* Propriedades SVG que foram removidas dos atributos inline */
	.myd-section-icon svg { fill-rule: evenodd; clip-rule: evenodd; stroke-linejoin: round; stroke-miterlimit: 2; }
	/* Estilo específico para o label do autocomplete (igual ao label dos campos) */
	.myd-autocomplete-label { display:block; font-size:14px; font-weight:500; margin-bottom:6px; color:#444; }
	</style>

	<!-- Popup de Extras -->
	<div id="myd-extras-overlay" class="myd-extras-overlay">
		<div class="myd-extras-popup">
			<div class="myd-extras-popup__header">
				<h3 class="myd-extras-popup__title" id="myd-extras-product-name"></h3>
				<button type="button" class="myd-extras-popup__close" id="myd-extras-close">&times;</button>
			</div>
			<div class="myd-extras-popup__body" id="myd-extras-body">
				<div class="myd-extras-loading">Carregando extras...</div>
			</div>
			<div class="myd-extras-popup__footer">
				<button type="button" class="myd-extras-btn-cancel" id="myd-extras-cancel">Cancelar</button>
				<button type="button" class="myd-extras-btn-confirm" id="myd-extras-confirm">Adicionar</button>
			</div>
		</div>
	</div>

	<div class="myd-create-order-page">
		<header class="myd-page-header">
			<h1 class="myd-page-title">Criar pedido</h1>
		</header>

		<div class="myd-form-card">

			<form id="myd-create-order-form" autocomplete="off">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'myd_create_manual_order' ); ?>">
				
				<!-- Dados do Cliente -->
				<div class="myd-form-section">
					<h3 class="myd-section-title">
						<span class="myd-section-icon">
							<svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="m 8 1 c -1.65625 0 -3 1.34375 -3 3 s 1.34375 3 3 3 s 3 -1.34375 3 -3 s -1.34375 -3 -3 -3 z m -1.5 7 c -2.492188 0 -4.5 2.007812 -4.5 4.5 v 0.5 c 0 1.109375 0.890625 2 2 2 h 8 c 1.109375 0 2 -0.890625 2 -2 v -0.5 c 0 -2.492188 -2.007812 -4.5 -4.5 -4.5 z m 0 0" fill="#000000"></path> </g></svg>
						</span>
						Dados do cliente
					</h3>
					<div class="myd-form-row">
						<div class="myd-form-group">
							<label for="customer_name">Nome do cliente *</label>
							<input type="text" id="customer_name" name="customer_name" placeholder="Nome completo" required>
						</div>
						<div class="myd-form-group">
							<label for="customer_phone">Telefone *</label>
							<input type="tel" id="customer_phone" name="customer_phone" placeholder="(00) 00000-0000" required>
						</div>
					</div>
				</div>

				<!-- Endereço -->
				<div class="myd-form-section">
				<div class="myd-form-section myd-address-section">
					<h3 class="myd-section-title">
						<span class="myd-section-icon">
							<svg fill="#000000" viewBox="0 0 32 32" version="1.1" xml:space="preserve" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g id="Layer1"> <path d="M16,2c-6.071,0 -11,4.929 -11,11c0,2.778 1.654,6.081 3.699,9.019c2.939,4.224 6.613,7.707 6.613,7.707c0.386,0.365 0.99,0.365 1.376,-0c0,-0 3.674,-3.483 6.613,-7.707c2.045,-2.938 3.699,-6.241 3.699,-9.019c0,-6.071 -4.929,-11 -11,-11Zm0,5.5c-3.036,0 -5.5,2.464 -5.5,5.5c0,3.036 2.464,5.5 5.5,5.5c3.036,-0 5.5,-2.464 5.5,-5.5c0,-3.036 -2.464,-5.5 -5.5,-5.5Zm0,2c1.932,0 3.5,1.568 3.5,3.5c0,1.932 -1.568,3.5 -3.5,3.5c-1.932,-0 -3.5,-1.568 -3.5,-3.5c0,-1.932 1.568,-3.5 3.5,-3.5Z"></path> </g> </g></svg>
						</span>
						Endereço de entrega
					</h3>
						<div class="myd-form-row" style="margin-bottom:12px;">
							<div class="myd-form-group" style="flex:0 0 220px;">
								<label for="delivery_type">Tipo de entrega</label>
								<select id="delivery_type" name="delivery_type">
									<option value="delivery" selected>Entrega</option>
									<option value="pickup">Retirada</option>
								</select>
							</div>
							<div class="myd-form-group" style="flex:1 1 auto;">
								<label for="order_channel">Canal de venda</label>
								<select id="order_channel" name="order_channel">
									<option value="WPP">Whatsapp</option>
									<option value="IFD">iFood</option>
								</select>
							</div>
						</div>
					
					<?php if ( $shipping_type === 'per-distance' ) : ?>
					<!-- Per-distance: Autocomplete customizado igual ao frontend -->
					<div id="myd-autocomplete-wrapper">
						<label for="address_autocomplete" class="myd-autocomplete-label">Adicione seu endereço com número</label>
						<div class="myd-autocomplete-inner">
							<input 
								type="text" 
								id="address_autocomplete" 
								class="myd-autocomplete-input"
								placeholder="Digite seu endereço com número..."
								autocomplete="off"
							>
							<div id="myd-autocomplete-suggestions" class="myd-autocomplete-suggestions"></div>
						</div>
					</div>
					
					<!-- Preview do endereço selecionado -->
						<div id="myd-address-preview" class="myd-address-preview">
							<p class="myd-address-info-title">Informação de entrega</p>
							<div class="myd-address-row">
								<!-- Ícone pin -->
								<div class="myd-address-icon-wrap">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#1f2937"/>
									</svg>
								</div>
								<!-- Conteúdo -->
								<div class="myd-address-content">
									<div id="myd-address-line1"></div>
									<div id="myd-address-line2"></div>
									<div id="myd-address-line3"></div>
								</div>
								<!-- Botão Trocar -->
								<button type="button" id="myd-address-change">Trocar</button>
							</div>
							<!-- Checkbox endereço sem número -->
							<label id="myd-no-number-label" class="myd-no-number-label">
								<input type="checkbox" id="myd-no-number" class="myd-no-number">
								Endereço sem número
							</label>
					
					<!-- Campos extras após selecionar endereço (preenchidos no modal) -->
					<div id="myd-address-extra" class="myd-address-extra">
						<div class="myd-form-row">
							<div class="myd-form-group">
								<label for="address_comp">Complemento</label>
								<input type="text" id="address_comp" name="address_comp" placeholder="Apto, bloco...">
							</div>
							<div class="myd-form-group">
								<label for="reference">Ponto de Referência</label>
								<input type="text" id="reference" name="reference" placeholder="Próximo a...">
							</div>
						</div>
					</div>
					
					<!-- Campos hidden para dados do endereço -->
					<input type="hidden" id="address" name="address">
					<input type="hidden" id="address_number" name="address_number">
					<input type="hidden" id="neighborhood" name="neighborhood">
					<input type="hidden" id="address_latitude" name="address_latitude">
					<input type="hidden" id="address_longitude" name="address_longitude">
					<input type="hidden" id="address_formatted" name="address_formatted">
					
					<?php elseif ( ! empty( $neighborhoods ) ) : ?>
					<!-- Per-neighborhood: Dropdown de bairros -->
					<div class="myd-form-group">
						<label for="neighborhood">Bairro *</label>
						<select id="neighborhood" name="neighborhood" required>
							<option value="">Selecione o bairro...</option>
							<?php foreach ( $neighborhoods as $nb ) : ?>
								<option value="<?php echo esc_attr( $nb['name'] ); ?>" data-price="<?php echo esc_attr( $nb['price'] ); ?>">
									<?php echo esc_html( html_entity_decode( $nb['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ); ?> - <?php echo esc_html( $currency_symbol ); ?> <?php echo number_format( $nb['price'], 2, ',', '.' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					
					<div class="myd-form-group">
						<label for="address">Rua/Avenida *</label>
						<input type="text" id="address" name="address" placeholder="Nome da rua" required>
					</div>
					<div class="myd-form-row">
						<div class="myd-form-group">
							<label for="address_number">Número *</label>
							<input type="text" id="address_number" name="address_number" placeholder="Nº" required>
						</div>
						<div class="myd-form-group">
							<label for="address_comp">Complemento</label>
							<input type="text" id="address_comp" name="address_comp" placeholder="Apto, bloco...">
						</div>
					</div>
					<div class="myd-form-group">
						<label for="reference">Ponto de Referência</label>
						<input type="text" id="reference" name="reference" placeholder="Próximo a...">
					</div>
					
					<?php else : ?>
					<!-- Outros métodos: Campos manuais -->
					<div class="myd-form-group">
						<label for="neighborhood">Bairro *</label>
						<input type="text" id="neighborhood" name="neighborhood" placeholder="Bairro" required>
					</div>
					<div class="myd-form-group">
						<label for="address">Rua/Avenida *</label>
						<input type="text" id="address" name="address" placeholder="Nome da rua" required>
					</div>
					<div class="myd-form-row">
						<div class="myd-form-group">
							<label for="address_number">Número *</label>
							<input type="text" id="address_number" name="address_number" placeholder="Nº" required>
						</div>
						<div class="myd-form-group">
							<label for="address_comp">Complemento</label>
							<input type="text" id="address_comp" name="address_comp" placeholder="Apto, bloco...">
						</div>
					</div>
					<div class="myd-form-group">
						<label for="reference">Ponto de Referência</label>
						<input type="text" id="reference" name="reference" placeholder="Próximo a...">
					</div>
					<?php endif; ?>
				</div>
				</div>

				<!-- Produtos -->
				<div class="myd-form-section">
					<h3 class="myd-section-title">
						<span class="myd-section-icon">
							<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <rect x="5" y="4" width="14" height="17" rx="2" stroke="#000000" stroke-width="2"></rect> <path d="M9 9H15" stroke="#000000" stroke-width="2" stroke-linecap="round"></path> <path d="M9 13H15" stroke="#000000" stroke-width="2" stroke-linecap="round"></path> <path d="M9 17H13" stroke="#000000" stroke-width="2" stroke-linecap="round"></path> </g></svg>
						</span>
						Produtos do pedido
					</h3>
					<div class="myd-form-group">
						<label for="product_search">Buscar produto</label>
						<div class="myd-product-search-wrap">
							<input type="text" id="product_search" placeholder="Digite para buscar..." autocomplete="off">
							<div id="product_results" class="myd-product-results"></div>
						</div>
					</div>
					<div id="order_items" class="myd-order-items">
						<div class="myd-empty-items">Nenhum produto adicionado</div>
					</div>
				</div>
				
				<!-- Pagamento -->
				<div class="myd-form-section">
					<h3 class="myd-section-title">
						<span class="myd-section-icon">
							<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <rect x="3" y="6" width="18" height="13" rx="2" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></rect> <path d="M3 10H20.5" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M7 15H9" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
						</span>
						Pagamento
					</h3>
					<div class="myd-form-row">
						<div class="myd-form-group">
							<label for="payment_method">Forma de pagamento *</label>
							<select id="payment_method" name="payment_method" required>
								<option value="">Selecione...</option>
								<option value="DIN">Dinheiro</option>
								<option value="CRD">Crédito</option>
								<option value="DEB">Débito</option>
								<option value="PIX">Pix</option>
								<option value="VRF">Vale Refeição</option>
							</select>
						</div>
						<div class="myd-delivery-fee-field">
							<label>Taxa de entrega</label>
							<div id="delivery_fee_display" class="myd-delivery-fee-display">
								<?php echo esc_html( $currency_symbol ); ?> 0,00
							</div>
							<input type="hidden" id="delivery_fee" name="delivery_fee" value="0">
						</div>
					</div>
					<div class="myd-form-row">
						<div class="myd-form-group myd-change-field">
							<label for="change_for">Troco para (<?php echo esc_html( $currency_symbol ); ?>)</label>
							<input type="text" id="change_for" name="change_for" placeholder="Ex: 50,00">
						</div>
					</div>
				</div>

				<!-- Resumo -->
				<div class="myd-order-summary">
					<div class="myd-summary-row">
						<span>Subtotal:</span>
						<span id="summary_subtotal"><?php echo esc_html( $currency_symbol ); ?> 0,00</span>
					</div>
					<div class="myd-summary-row myd-delivery-fee-row">
						<span>Taxa de Entrega:</span>
						<span id="summary_delivery"><?php echo esc_html( $currency_symbol ); ?> 0,00</span>
					</div>
					<div class="myd-summary-row total">
						<span>Total:</span>
						<span id="summary_total"><?php echo esc_html( $currency_symbol ); ?> 0,00</span>
					</div>
				</div>
				
				<!-- Ações -->
				<div class="myd-form-actions">
					<button type="submit" class="myd-btn myd-btn-save" id="btn_submit">Criar pedido</button>
				</div>
			</form>
		</div>
	</div>
	
	<div id="toast_container" class="myd-toast-container"></div>

	<script>
	(function(){
		var currencySymbol = '<?php echo esc_js( $currency_symbol ); ?>';
		var restNonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';
		var orderItems = [];
		var searchTimeout = null;
		var shippingType = '<?php echo esc_js( $shipping_type ); ?>';
		var fixedPrice = <?php echo floatval( $fixed_price ); ?>;
		var hasNeighborhoodSelect = <?php echo ! empty( $neighborhoods ) ? 'true' : 'false'; ?>;
		
		// Per-distance config
		var googleApiKey = '<?php echo esc_js( $google_api_key ); ?>';
		var originLat = <?php echo floatval( $origin_lat ); ?>;
		var originLng = <?php echo floatval( $origin_lng ); ?>;
		var distanceRanges = <?php echo wp_json_encode( $distance_ranges ); ?>;
		var googleMapsLoaded = false;
		
		// Elements
		var form = document.getElementById('myd-create-order-form');
		var productSearch = document.getElementById('product_search');
		var productResults = document.getElementById('product_results');
		var orderItemsContainer = document.getElementById('order_items');
		var paymentSelect = document.getElementById('payment_method');
		var deliveryFeeInput = document.getElementById('delivery_fee');
		var deliveryFeeDisplay = document.getElementById('delivery_fee_display');
		var deliveryTypeSelect = document.getElementById('delivery_type');
		var orderChannelSelect = document.getElementById('order_channel');
		var neighborhoodSelect = document.getElementById('neighborhood');
		
		// Toast notification
		function showToast(message, type) {
			var container = document.getElementById('toast_container');
			var toast = document.createElement('div');
			toast.className = 'myd-toast ' + (type || '');
			toast.textContent = message;
			container.appendChild(toast);
			setTimeout(function(){ toast.remove(); }, 4000);
		}
		
		// ===== PER-DISTANCE: Map Picker igual ao Frontend =====
		var mapPickerModal = null;
		var mapPickerInstance = null;
		var mapPickerMarker = null;
		var mapPickerGeocoder = null;
		var mapPickerPendingResolve = null;
		var mapPickerPendingData = null;
		var mapPickerMapView = null;
		var mapPickerDetailsView = null;
		var mapPickerDetailsNumber = null;
		var mapPickerDetailsComplement = null;
		var mapPickerDetailsReference = null;
		var mapPickerDetailsAddress = null;
		
		if (shippingType === 'per-distance' && googleApiKey) {
			// Carregar Google Maps API
			window.initGoogleMapsForManualOrder = function() {
				googleMapsLoaded = true;
				initMapPickerAutocomplete();
			};
			
			var script = document.createElement('script');
			script.src = 'https://maps.googleapis.com/maps/api/js?key=' + googleApiKey + '&libraries=places&callback=initGoogleMapsForManualOrder';
			script.async = true;
			script.defer = true;
			document.head.appendChild(script);
		}
		
		function ensureMapPickerModal() {
			if (mapPickerModal) return;
			
			mapPickerModal = document.createElement('div');
			mapPickerModal.id = 'myd-map-picker';
			
			var inner = document.createElement('div');
			inner.className = 'myd-map-picker__inner';
			
			// === MAP VIEW ===
			mapPickerMapView = document.createElement('div');
			mapPickerMapView.className = 'myd-map-picker__map-view';
			
			var header = document.createElement('div');
			header.className = 'myd-map-picker__header';
			header.textContent = 'Confirme a localização';
			
			var mapContainer = document.createElement('div');
			mapContainer.className = 'myd-map-picker__map';
			mapContainer.id = 'myd-map-picker-container';
			
			var footer = document.createElement('div');
			footer.className = 'myd-map-picker__footer';
			
			var cancelBtn = document.createElement('button');
			cancelBtn.type = 'button';
			cancelBtn.className = 'myd-map-picker__btn-cancel';
			cancelBtn.textContent = 'Cancelar';
			
			var confirmBtn = document.createElement('button');
			confirmBtn.type = 'button';
			confirmBtn.className = 'myd-map-picker__btn-confirm';
			confirmBtn.textContent = 'Usar este local';
			
			footer.appendChild(cancelBtn);
			footer.appendChild(confirmBtn);
			mapPickerMapView.appendChild(header);
			mapPickerMapView.appendChild(mapContainer);
			mapPickerMapView.appendChild(footer);
			
			// === DETAILS VIEW ===
			mapPickerDetailsView = document.createElement('div');
			mapPickerDetailsView.className = 'myd-map-picker__details-view';
			
			var detailsMiniMap = document.createElement('div');
			detailsMiniMap.className = 'myd-map-picker__details-minimap';
			
			var backBtn = document.createElement('button');
			backBtn.type = 'button';
			backBtn.className = 'myd-map-picker__back-btn';
			backBtn.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			detailsMiniMap.appendChild(backBtn);
			
			var addressLabel = document.createElement('div');
			addressLabel.className = 'myd-map-picker__address-label';
			addressLabel.textContent = 'ENDEREÇO';
			detailsMiniMap.appendChild(addressLabel);
			
			mapPickerDetailsAddress = document.createElement('div');
			mapPickerDetailsAddress.className = 'myd-map-picker__details-address';
			mapPickerDetailsAddress.innerHTML = '<div class="myd-map-picker__address-main"></div><div class="myd-map-picker__address-secondary"></div>';
			
			var detailsForm = document.createElement('div');
			detailsForm.className = 'myd-map-picker__details-form';
			
			var row1 = document.createElement('div');
			row1.className = 'myd-map-picker__form-row';
			
			var numberWrapper = document.createElement('div');
			numberWrapper.className = 'myd-map-picker__field-wrapper myd-map-picker__field-wrapper--number';
			numberWrapper.innerHTML = '<label class="myd-map-picker__label">Número</label>';
			mapPickerDetailsNumber = document.createElement('input');
			mapPickerDetailsNumber.type = 'text';
			mapPickerDetailsNumber.className = 'myd-map-picker__input';
			numberWrapper.appendChild(mapPickerDetailsNumber);
			
			var complementWrapper = document.createElement('div');
			complementWrapper.className = 'myd-map-picker__field-wrapper myd-map-picker__field-wrapper--complement';
			complementWrapper.innerHTML = '<label class="myd-map-picker__label">Complemento</label>';
			mapPickerDetailsComplement = document.createElement('input');
			mapPickerDetailsComplement.type = 'text';
			mapPickerDetailsComplement.className = 'myd-map-picker__input';
			mapPickerDetailsComplement.placeholder = 'Apartamento/Bloco/Casa';
			complementWrapper.appendChild(mapPickerDetailsComplement);
			
			row1.appendChild(numberWrapper);
			row1.appendChild(complementWrapper);
			
			var referenceWrapper = document.createElement('div');
			referenceWrapper.className = 'myd-map-picker__field-wrapper myd-map-picker__field-wrapper--reference';
			referenceWrapper.innerHTML = '<label class="myd-map-picker__label">Ponto de referência</label>';
			mapPickerDetailsReference = document.createElement('input');
			mapPickerDetailsReference.type = 'text';
			mapPickerDetailsReference.className = 'myd-map-picker__input';
			mapPickerDetailsReference.placeholder = 'Ponto de referência';
			referenceWrapper.appendChild(mapPickerDetailsReference);
			
			detailsForm.appendChild(row1);
			detailsForm.appendChild(referenceWrapper);
			
			var detailsFooter = document.createElement('div');
			detailsFooter.className = 'myd-map-picker__details-footer';
			var saveBtn = document.createElement('button');
			saveBtn.type = 'button';
			saveBtn.className = 'myd-map-picker__btn-save';
			saveBtn.textContent = 'Salvar endereço';
			detailsFooter.appendChild(saveBtn);
			
			mapPickerDetailsView.appendChild(detailsMiniMap);
			mapPickerDetailsView.appendChild(mapPickerDetailsAddress);
			mapPickerDetailsView.appendChild(detailsForm);
			mapPickerDetailsView.appendChild(detailsFooter);
			
			inner.appendChild(mapPickerMapView);
			inner.appendChild(mapPickerDetailsView);
			mapPickerModal.appendChild(inner);
			document.body.appendChild(mapPickerModal);
			
			// Event handlers
			backBtn.onclick = function() {
				mapPickerDetailsView.style.display = 'none';
				mapPickerMapView.style.display = 'block';
			};
			
			saveBtn.onclick = function() {
				if (mapPickerPendingResolve && mapPickerPendingData) {
					mapPickerPendingData.streetNumber = mapPickerDetailsNumber.value.trim();
					mapPickerPendingData.complement = mapPickerDetailsComplement.value.trim();
					mapPickerPendingData.reference = mapPickerDetailsReference.value.trim();
					mapPickerModal.style.display = 'none';
					mapPickerDetailsView.style.display = 'none';
					mapPickerMapView.style.display = 'block';
					mapPickerPendingResolve(mapPickerPendingData);
				}
			};
			
			cancelBtn.onclick = function() {
				mapPickerModal.style.display = 'none';
				mapPickerDetailsView.style.display = 'none';
				mapPickerMapView.style.display = 'block';
				if (mapPickerPendingResolve) mapPickerPendingResolve(null);
			};
			
			confirmBtn.onclick = function() {
				var position = mapPickerMarker.getPosition();
				if (!position) {
					if (mapPickerPendingResolve) mapPickerPendingResolve(null);
					return;
				}
				
				mapPickerGeocoder.geocode({ location: position }, function(results, status) {
					if (status !== 'OK' || !results || !results.length) {
						showToast('Não foi possível validar essa localização.', 'error');
						return;
					}
					
					var selected = results.find(function(item) {
						return item.address_components && item.address_components.some(function(c) {
							return c.types.indexOf('sublocality_level_1') !== -1 || c.types.indexOf('neighborhood') !== -1;
						});
					}) || results[0];
					
					var comp = selected.address_components || [];
					var street_number = comp.find(function(c) { return c.types.indexOf('street_number') !== -1; });
					var route = comp.find(function(c) { return c.types.indexOf('route') !== -1; });
					var sublocality = comp.find(function(c) { return c.types.indexOf('sublocality_level_1') !== -1 || c.types.indexOf('sublocality') !== -1; });
					var cityComp = comp.find(function(c) { return c.types.indexOf('locality') !== -1 || c.types.indexOf('administrative_area_level_2') !== -1; });
					var stateComp = comp.find(function(c) { return c.types.indexOf('administrative_area_level_1') !== -1; });
					
					var mainAddress = '';
					if (route) mainAddress = route.long_name;
					if (street_number) mainAddress += ', ' + street_number.long_name;
					
					var secondaryAddress = '';
					if (sublocality) secondaryAddress = sublocality.long_name;
					if (cityComp) secondaryAddress += (secondaryAddress ? ', ' : '') + cityComp.long_name;
					if (stateComp) secondaryAddress += ' - ' + stateComp.short_name;
					
					var mainEl = mapPickerDetailsAddress.querySelector('.myd-map-picker__address-main');
					var secondaryEl = mapPickerDetailsAddress.querySelector('.myd-map-picker__address-secondary');
					if (mainEl) mainEl.textContent = mainAddress || selected.formatted_address;
					if (secondaryEl) secondaryEl.textContent = secondaryAddress;
					
					mapPickerDetailsNumber.value = street_number ? street_number.long_name : '';
					mapPickerDetailsComplement.value = '';
					mapPickerDetailsReference.value = '';
					
					// Mini map in details
					var existingMiniMapDiv = detailsMiniMap.querySelector('.myd-map-picker__minimap-container');
					if (existingMiniMapDiv) existingMiniMapDiv.remove();
					
					var miniMapDiv = document.createElement('div');
					miniMapDiv.className = 'myd-map-picker__minimap-container';
					detailsMiniMap.insertBefore(miniMapDiv, detailsMiniMap.firstChild);
					
					var miniMapInstance = new google.maps.Map(miniMapDiv, {
						center: position,
						zoom: 17,
						mapTypeControl: false,
						streetViewControl: false,
						fullscreenControl: false,
						zoomControl: false,
						gestureHandling: 'none',
						clickableIcons: false
					});
					
					new google.maps.Marker({
						position: position,
						map: miniMapInstance,
						draggable: false
					});
					
					mapPickerPendingData = {
						formatted_address: selected.formatted_address,
						address_components: selected.address_components,
						geometry: { location: { lat: position.lat(), lng: position.lng() } }
					};
					
					mapPickerMapView.style.display = 'none';
					mapPickerDetailsView.style.display = 'flex';
					mapPickerDetailsView.style.flexDirection = 'column';
					
					setTimeout(function() { mapPickerDetailsNumber.focus(); }, 100);
				});
			};
		}
		
		function openMapPicker(seedPlace) {
			return new Promise(function(resolve) {
				if (!window.google || !google.maps) {
					var loadingEl = document.getElementById('myd-loading-overlay');
					if (loadingEl) loadingEl.classList.remove('active');
					showToast('Não foi possível exibir o mapa.', 'error');
					resolve(null);
					return;
				}
				
				ensureMapPickerModal();
				
				var fallbackCenter = { lat: originLat || -14.235, lng: originLng || -51.9253 };
				var center = fallbackCenter;
				
				if (seedPlace && seedPlace.geometry && seedPlace.geometry.location) {
					var loc = seedPlace.geometry.location;
					center = {
						lat: typeof loc.lat === 'function' ? loc.lat() : loc.lat,
						lng: typeof loc.lng === 'function' ? loc.lng() : loc.lng
					};
				}
				
				var mapContainer = document.getElementById('myd-map-picker-container');
				
				if (!mapPickerInstance) {
					mapPickerInstance = new google.maps.Map(mapContainer, {
						center: center,
						zoom: center.lat ? 17 : 5,
						mapTypeControl: false,
						streetViewControl: false,
						fullscreenControl: false,
						clickableIcons: false
					});
					
					mapPickerInstance.addListener('click', function(event) {
						if (mapPickerMarker && event && event.latLng) {
							mapPickerMarker.setPosition(event.latLng);
						}
					});
				} else {
					mapPickerInstance.setCenter(center);
					mapPickerInstance.setZoom(center.lat ? 17 : 5);
				}
				
				if (!mapPickerMarker) {
					mapPickerMarker = new google.maps.Marker({ draggable: true });
				}
				mapPickerMarker.setMap(mapPickerInstance);
				mapPickerMarker.setPosition(center);
				
				if (!mapPickerGeocoder) {
					mapPickerGeocoder = new google.maps.Geocoder();
				}
				
				mapPickerModal.style.display = 'flex';
				mapPickerMapView.style.display = 'block';
				mapPickerDetailsView.style.display = 'none';
				
				// Esconder loading
				var loadingEl = document.getElementById('myd-loading-overlay');
				if (loadingEl) loadingEl.classList.remove('active');
				
				setTimeout(function() {
					google.maps.event.trigger(mapPickerInstance, 'resize');
					mapPickerInstance.setCenter(center);
				}, 100);
				
				mapPickerPendingResolve = resolve;
			});
		}
		
		function initMapPickerAutocomplete() {
			var addressInput = document.getElementById('address_autocomplete');
			var suggestionsContainer = document.getElementById('myd-autocomplete-suggestions');
			if (!addressInput || !suggestionsContainer) return;
			
			var debounceTimer = null;
			var lastQuery = '';
			
			// Buscar sugestões via API REST
			function fetchSuggestions(query) {
				var bias = '';
				if (originLat && originLng) {
					bias = '&location=' + originLat + ',' + originLng + '&radius=5000';
				}
				var url = '/wp-json/my-delivery/v1/places/autocomplete?input=' + encodeURIComponent(query) + '&components=country:br' + bias;
				
				fetch(url)
					.then(function(res) { return res.json(); })
					.then(function(data) {
						if (data.predictions && data.predictions.length) {
							showSuggestions(data.predictions);
						} else {
							hideSuggestions();
						}
					})
					.catch(function() {
						hideSuggestions();
					});
			}
			
			// Exibir sugestões com ícone amarelo
			function showSuggestions(predictions) {
				suggestionsContainer.innerHTML = '';
				
				predictions.forEach(function(item) {
					var div = document.createElement('div');
					div.className = 'myd-autocomplete-suggestion';
					
					// Ícone amarelo
					var icon = document.createElement('div');
					icon.className = 'myd-autocomplete-suggestion__icon';
					var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
					svg.setAttribute('viewBox', '0 0 24 24');
					svg.setAttribute('width', '20');
					svg.setAttribute('height', '20');
					svg.innerHTML = '<path fill="currentColor" d="M12 2C8.686 2 6 4.686 6 8c0 4.5 6 12 6 12s6-7.5 6-12c0-3.314-2.686-6-6-6zm0 8.5A2.5 2.5 0 1 1 12 5.5a2.5 2.5 0 0 1 0 5z"/>';
					svg.style.color = '#ffae00';
					icon.appendChild(svg);
					
					// Texto principal
					var textWrapper = document.createElement('div');
					textWrapper.className = 'myd-autocomplete-suggestion__content';
					
					var mainText = document.createElement('div');
					mainText.className = 'myd-autocomplete-suggestion__main';
					mainText.textContent = item.structured_formatting ? item.structured_formatting.main_text : item.description;
					
					var secondaryText = document.createElement('div');
					secondaryText.className = 'myd-autocomplete-suggestion__secondary';
					secondaryText.textContent = item.structured_formatting ? item.structured_formatting.secondary_text : '';
					
					textWrapper.appendChild(mainText);
					textWrapper.appendChild(secondaryText);
					
					div.appendChild(icon);
					div.appendChild(textWrapper);
					
					div.addEventListener('click', function() {
						selectPlace(item);
					});
					
					suggestionsContainer.appendChild(div);
				});
				
				suggestionsContainer.style.display = 'block';
			}
			
			function hideSuggestions() {
				suggestionsContainer.innerHTML = '';
				suggestionsContainer.style.display = 'none';
			}
			
			function showTypingIndicator() {
				suggestionsContainer.innerHTML = '<div class="myd-autocomplete-typing">Buscando endereços...</div>';
				suggestionsContainer.style.display = 'block';
			}
			
			var loadingOverlay = document.getElementById('myd-loading-overlay');
			
			function showLoading() {
				if (loadingOverlay) loadingOverlay.classList.add('active');
			}
			
			function hideLoading() {
				if (loadingOverlay) loadingOverlay.classList.remove('active');
			}
			
			// Selecionar lugar e buscar detalhes
			async function selectPlace(item) {
				hideSuggestions();
				addressInput.value = item.description;
				showLoading();
				
				// Buscar detalhes do lugar
				var detailsUrl = '/wp-json/my-delivery/v1/places/details?place_id=' + encodeURIComponent(item.place_id) + '&fields=formatted_address,geometry,address_component,address_components';
				
				try {
					var res = await fetch(detailsUrl);
					var data = await res.json();
					
					if (!data.result || !data.result.geometry) {
						hideLoading();
						showToast('Não foi possível obter detalhes do endereço.', 'error');
						return;
					}
					
					var place = {
						formatted_address: data.result.formatted_address,
						address_components: data.result.address_components,
						geometry: {
							location: {
								lat: function() { return data.result.geometry.location.lat; },
								lng: function() { return data.result.geometry.location.lng; }
							}
						}
					};
					
					// Abrir map picker
					var confirmedPlace = await openMapPicker(place);
					hideLoading();
					if (!confirmedPlace) return;
					
					// Extrair dados do endereço confirmado
					var comp = confirmedPlace.address_components || [];
					var streetName = '';
					var streetNumber = confirmedPlace.streetNumber || '';
					var neighborhood = '';
					var city = '';
					var state = '';
					var postalCode = '';
					var country = '';
					
					comp.forEach(function(c) {
						if (c.types.indexOf('route') !== -1) streetName = c.long_name;
						if (c.types.indexOf('street_number') !== -1 && !streetNumber) streetNumber = c.long_name;
						if (c.types.indexOf('sublocality_level_1') !== -1 || c.types.indexOf('neighborhood') !== -1) neighborhood = c.long_name;
						if (c.types.indexOf('administrative_area_level_2') !== -1) city = c.long_name;
						if (c.types.indexOf('administrative_area_level_1') !== -1) state = c.long_name;
						if (c.types.indexOf('postal_code') !== -1) postalCode = c.long_name;
						if (c.types.indexOf('country') !== -1) country = c.long_name;
					});
					
					// Preencher campos hidden
					document.getElementById('address').value = streetName;
					document.getElementById('address_number').value = streetNumber;
					document.getElementById('neighborhood').value = neighborhood;
					document.getElementById('address_latitude').value = confirmedPlace.geometry.location.lat;
					document.getElementById('address_longitude').value = confirmedPlace.geometry.location.lng;
					document.getElementById('address_formatted').value = confirmedPlace.formatted_address;
					document.getElementById('address_comp').value = confirmedPlace.complement || '';
					document.getElementById('reference').value = confirmedPlace.reference || '';
					
					// Mostrar preview (3 linhas igual frontend)
					// Linha 1: Rua, Nº numero
					document.getElementById('myd-address-line1').textContent = streetName + (streetNumber ? ', Nº ' + streetNumber : '');
					// Linha 2: Bairro | CEP
					var line2Parts = [];
					if (neighborhood) line2Parts.push(neighborhood);
					if (postalCode) line2Parts.push(postalCode);
					document.getElementById('myd-address-line2').textContent = line2Parts.join(' | ');
					// Linha 3: Cidade, Estado, País
					var line3Parts = [];
					if (city) line3Parts.push(city);
					if (state) line3Parts.push(state);
					if (country) line3Parts.push(country);
					document.getElementById('myd-address-line3').textContent = line3Parts.join(', ');
					document.getElementById('myd-address-preview').style.display = 'block';
					document.getElementById('myd-address-extra').style.display = 'block';
					document.getElementById('myd-autocomplete-wrapper').style.display = 'none';
					
					// Calcular taxa de entrega por distância
					calculateDistanceFee(confirmedPlace.geometry.location.lat, confirmedPlace.geometry.location.lng);
				} catch (e) {
					hideLoading();
					showToast('Erro ao buscar detalhes do endereço.', 'error');
				}
			}
			
			// Event listener com debounce
			addressInput.addEventListener('input', function() {
				var query = addressInput.value.trim();
				
				if (debounceTimer) {
					clearTimeout(debounceTimer);
				}
				
				if (query.length < 3) {
					hideSuggestions();
					return;
				}
				
				if (query === lastQuery) return;
				lastQuery = query;
				
				showTypingIndicator();
				
				debounceTimer = setTimeout(function() {
					fetchSuggestions(query);
				}, 500);
			});
			
			// Fechar sugestões ao clicar fora
			document.addEventListener('click', function(e) {
				if (!addressInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
					hideSuggestions();
				}
			});
			
			// Navegação por teclado
			addressInput.addEventListener('keydown', function(e) {
				var items = suggestionsContainer.querySelectorAll('.myd-autocomplete-suggestion');
				if (!items.length) return;
				
				var current = suggestionsContainer.querySelector('.myd-autocomplete-suggestion--active');
				var currentIndex = current ? Array.from(items).indexOf(current) : -1;
				
				if (e.key === 'ArrowDown') {
					e.preventDefault();
					if (current) current.classList.remove('myd-autocomplete-suggestion--active');
					var nextIndex = (currentIndex + 1) % items.length;
					items[nextIndex].classList.add('myd-autocomplete-suggestion--active');
					items[nextIndex].scrollIntoView({ block: 'nearest' });
				} else if (e.key === 'ArrowUp') {
					e.preventDefault();
					if (current) current.classList.remove('myd-autocomplete-suggestion--active');
					var prevIndex = currentIndex <= 0 ? items.length - 1 : currentIndex - 1;
					items[prevIndex].classList.add('myd-autocomplete-suggestion--active');
					items[prevIndex].scrollIntoView({ block: 'nearest' });
				} else if (e.key === 'Enter') {
					e.preventDefault();
					if (current) {
						current.click();
					} else if (items.length) {
						items[0].click();
					}
				} else if (e.key === 'Escape') {
					hideSuggestions();
				}
			});
		}
		
		function calculateDistanceFee(destLat, destLng) {
			if (!google || !google.maps) return;
			
			var service = new google.maps.DistanceMatrixService();
			service.getDistanceMatrix({
				origins: [{ lat: originLat, lng: originLng }],
				destinations: [{ lat: destLat, lng: destLng }],
				travelMode: 'DRIVING',
				unitSystem: google.maps.UnitSystem.METRIC
			}, function(response, status) {
				if (status !== 'OK' || !response.rows[0] || !response.rows[0].elements[0]) {
					showToast('Erro ao calcular distância', 'error');
					return;
				}
				
				var element = response.rows[0].elements[0];
				if (element.status !== 'OK') {
					showToast('Não foi possível calcular a rota', 'error');
					return;
				}
				
				var distanceMeters = element.distance.value;
				var distanceKm = distanceMeters / 1000;
				var fee = 0;
				var found = false;
				
				for (var i = 0; i < distanceRanges.length; i++) {
					var range = distanceRanges[i];
					if (distanceKm >= range.from && distanceKm <= range.to) {
						fee = range.price;
						found = true;
						break;
					}
				}
				
				if (!found) {
					showToast('Endereço fora da área de entrega (' + distanceKm.toFixed(1) + ' km)', 'error');
					deliveryFeeInput.value = 0;
					deliveryFeeDisplay.textContent = 'Fora da área';
					updateSummary();
					return;
				}
				
				deliveryFeeInput.value = fee;
				deliveryFeeDisplay.textContent = currencySymbol + ' ' + formatPrice(fee) + ' (' + distanceKm.toFixed(1) + ' km)';
				updateSummary();
			});
		}
		
		// Botão trocar endereço
		var changeBtn = document.getElementById('myd-address-change');
		if (changeBtn) {
			changeBtn.addEventListener('click', function() {
				var autocompleteWrapper = document.getElementById('myd-autocomplete-wrapper');
				var addressInput = document.getElementById('address_autocomplete');
				addressInput.value = '';
				autocompleteWrapper.style.display = '';
				document.getElementById('myd-address-preview').style.display = 'none';
				document.getElementById('myd-address-extra').style.display = 'none';
				// Limpar campos
				document.getElementById('address').value = '';
				document.getElementById('address_number').value = '';
				document.getElementById('neighborhood').value = '';
				document.getElementById('address_latitude').value = '';
				document.getElementById('address_longitude').value = '';
				document.getElementById('address_comp').value = '';
				document.getElementById('reference').value = '';
				// Resetar checkbox
				var noNumberCheckbox = document.getElementById('myd-no-number');
				if (noNumberCheckbox) noNumberCheckbox.checked = false;
				deliveryFeeInput.value = 0;
				deliveryFeeDisplay.textContent = currencySymbol + ' 0,00';
				updateSummary();
				addressInput.focus();
			});
		}
		
		// Checkbox endereço sem número
		var noNumberCheckbox = document.getElementById('myd-no-number');
		if (noNumberCheckbox) {
			noNumberCheckbox.addEventListener('change', function() {
				var addressNumberInput = document.getElementById('address_number');
				var line1El = document.getElementById('myd-address-line1');
				var streetName = document.getElementById('address').value || '';
				
				if (this.checked) {
					addressNumberInput.value = 'S/N';
					line1El.textContent = streetName + ', S/N';
				} else {
					// Restaurar número original se disponível
					addressNumberInput.value = '';
					line1El.textContent = streetName;
				}
			});
		}
		// ===== FIM PER-DISTANCE =====

		// Tipo de entrega / Canal de venda - mostrar/ocultar endereço e ajustar taxa
		function setPickupState(isPickup) {
			var idsToHide = ['myd-autocomplete-wrapper', 'myd-address-preview', 'myd-address-extra'];
			idsToHide.forEach(function(id){ var el = document.getElementById(id); if (el) el.style.display = isPickup ? 'none' : ''; });

			// limpar campos de endereço quando for retirada
			if (isPickup) {
				['address','address_number','neighborhood','address_latitude','address_longitude','address_formatted','address_comp','reference'].forEach(function(n){ var el = document.getElementById(n); if (el) el.value = ''; });
				deliveryFeeInput.value = 0;
				if (deliveryFeeDisplay) deliveryFeeDisplay.textContent = currencySymbol + ' 0,00';
			} else {
				// ao voltar para entrega, manter comportamento padrão (usuário escolhe o endereço)
				if (document.getElementById('myd-autocomplete-wrapper')) document.getElementById('myd-autocomplete-wrapper').style.display = '';
			}
			updateSummary();
		}

		if (deliveryTypeSelect) {
			deliveryTypeSelect.addEventListener('change', function(){
				setPickupState(this.value === 'pickup');
			});
			// inicializar conforme valor atual
			setPickupState(deliveryTypeSelect.value === 'pickup');
		}
		

		
		// Neighborhood change - calcular taxa automaticamente (per-neighborhood)
		if (neighborhoodSelect && hasNeighborhoodSelect && shippingType !== 'per-distance') {
			neighborhoodSelect.addEventListener('change', function(){
				var selected = this.options[this.selectedIndex];
				var fee = 0;
				
				if (selected && selected.dataset.price) {
					if (shippingType === 'fixed-per-neighborhood') {
						fee = fixedPrice;
					} else {
						fee = parseFloat(selected.dataset.price) || 0;
					}
				}
				
				deliveryFeeInput.value = fee;
				if (deliveryFeeDisplay) deliveryFeeDisplay.textContent = currencySymbol + ' ' + formatPrice(fee);
				updateSummary();
			});
		}
		
		// Payment method toggle
		function toggleChangeField() {
			var changeField = document.querySelector('.myd-change-field');
			changeField.style.display = paymentSelect.value === 'DIN' ? 'block' : 'none';
		}
		paymentSelect.addEventListener('change', toggleChangeField);
		// Verificar valor inicial
		toggleChangeField();
		
		// Change for input formatting
		var changeForInput = document.getElementById('change_for');
		function formatCurrencyInput(value) {
			// Remove tudo exceto números e vírgula
			value = value.replace(/[^0-9,]/g, '');
			// Se tem vírgula, separa
			var parts = value.split(',');
			var integer = parts[0] || '0';
			var decimal = parts[1] || '';
			// Limita decimal a 2 dígitos
			decimal = decimal.substring(0, 2);
			// Formata integer com pontos se necessário
			integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
			// Monta
			var formatted = integer;
			if (decimal || value.includes(',')) {
				formatted += ',' + decimal.padEnd(2, '0');
			} else {
				formatted += ',00';
			}
			return currencySymbol + ' ' + formatted;
		}
		// Change for input formatting (copied from myd-troco-input)
		function formatCurrencyInput() {
			var v = changeForInput.value.replace(/\D/g, '');
			if (!v) {
				changeForInput.value = '';
				return;
			}
			// Limite de 9 dígitos (até 9.999.999,99)
			if (v.length > 9) v = v.slice(0,9);
			while (v.length < 3) v = '0' + v;
			var intPart = v.slice(0, v.length - 2);
			var decPart = v.slice(-2);
			intPart = intPart.replace(/^0+/, '') || '0';
			var intFormatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
			changeForInput.value = 'R$ ' + intFormatted + ',' + decPart;
		}
		// Retorna valor numérico do input formatado
		function getInputValue() {
			var val = changeForInput.value.replace(/[^\d,]/g, '').replace(',', '.');
			return parseFloat(val) || 0;
		}
		changeForInput.addEventListener('input', function(e){
			var before = changeForInput.value;
			formatCurrencyInput(e);
			var valor = getInputValue();
			// Aqui pode adicionar lógica se necessário, mas por enquanto só formata
		});
		// Permitir colar valor já formatado
		changeForInput.addEventListener('paste', function(e){
			setTimeout(function(){ formatCurrencyInput(); }, 0);
		});
		// Selecionar tudo ao focar
		changeForInput.addEventListener('focus', function(){
			setTimeout(function(){ changeForInput.select(); }, 10);
		});
		
		// Product search
		productSearch.addEventListener('input', function(){
			var query = this.value.trim();
			if (query.length < 2) {
				productResults.style.display = 'none';
				return;
			}
			
			clearTimeout(searchTimeout);
			searchTimeout = setTimeout(function(){
				fetch('/wp-json/myd-delivery/v1/products/search?q=' + encodeURIComponent(query), {
					method: 'GET',
					headers: { 'X-WP-Nonce': restNonce },
					credentials: 'same-origin'
				})
				.then(function(res){ return res.json(); })
				.then(function(data){
					if (!data || !data.products || !data.products.length) {
						productResults.innerHTML = '<div class="myd-no-products">Nenhum produto encontrado</div>';
						productResults.style.display = 'block';
						return;
					}
					
					   // Função para decodificar entidades HTML
					   function decodeHtml(html) {
						   var txt = document.createElement('textarea');
						   txt.innerHTML = html;
						   return txt.value;
					   }
					   var html = '';
					   data.products.forEach(function(p){
						   var decodedName = decodeHtml(p.name);
						   html += '<div class="myd-product-result-item" data-id="' + p.id + '" data-name="' + escapeHtml(p.name) + '" data-price="' + p.price + '">';
						   html += '<span>' + decodedName + '</span>';
						   html += '<span class="myd-product-result-price">' + currencySymbol + ' ' + formatPrice(p.price) + '</span>';
						   html += '</div>';
					   });
					productResults.innerHTML = html;
					productResults.style.display = 'block';
					
					productResults.querySelectorAll('.myd-product-result-item').forEach(function(item){
						item.addEventListener('click', function(){
							var productData = {
								id: parseInt(this.dataset.id),
								name: decodeHtml(this.dataset.name),
								price: parseFloat(this.dataset.price),
								quantity: 1
							};
							openExtrasPopup(productData);
							productSearch.value = '';
							productResults.style.display = 'none';
						});
					});
				})
				.catch(function(err){
					console.error('Erro ao buscar produtos:', err);
				});
			}, 300);
		});
		
		// Close results on outside click
		document.addEventListener('click', function(e){
			if (!productSearch.contains(e.target) && !productResults.contains(e.target)) {
				productResults.style.display = 'none';
			}
		});
		
		// ===== EXTRAS POPUP =====
		var extrasOverlay = document.getElementById('myd-extras-overlay');
		var extrasBody = document.getElementById('myd-extras-body');
		var extrasProductName = document.getElementById('myd-extras-product-name');
		var extrasCloseBtn = document.getElementById('myd-extras-close');
		var extrasCancelBtn = document.getElementById('myd-extras-cancel');
		var extrasConfirmBtn = document.getElementById('myd-extras-confirm');
		var pendingProduct = null;
		
		var productNoteHtml = '<div class="myd-product-note-wrap"><label for="myd-product-note">Observação do produto</label><textarea id="myd-product-note" placeholder="Ex: sem cebola, bem passado..." maxlength="500"></textarea></div>';

		function appendProductNoteField() {
			var existing = extrasBody.querySelector('.myd-product-note-wrap');
			if (!existing) {
				extrasBody.insertAdjacentHTML('beforeend', productNoteHtml);
			}
		}

		function getProductNote() {
			var ta = extrasBody.querySelector('#myd-product-note');
			return ta ? ta.value.trim() : '';
		}

		function openExtrasPopup(product) {
			pendingProduct = product;
			extrasProductName.textContent = product.name;
			extrasBody.innerHTML = '<div class="myd-extras-loading">Carregando extras...</div>';
			extrasOverlay.classList.add('active');
			extrasConfirmBtn.disabled = false;
			
			// Buscar HTML de extras renderizado pelo servidor (mesmo template do frontend)
			fetch('/wp-json/myd-delivery/v1/products/' + product.id + '/extras', {
				method: 'GET',
				headers: { 'X-WP-Nonce': restNonce },
				credentials: 'same-origin'
			})
			.then(function(res) { return res.json(); })
			.then(function(data) {
				if (!data.has_extras || !data.html) {
					// Sem extras — mostrar apenas campo de observação
					extrasBody.innerHTML = '';
					appendProductNoteField();
					return;
				}
				extrasBody.innerHTML = data.html;
				appendProductNoteField();
				initExtrasFromDOM();
				validateExtrasFromDOM();
			})
			.catch(function(err) {
				console.error('Erro ao carregar extras:', err);
				// Em caso de erro, mostrar apenas campo de observação
				extrasBody.innerHTML = '';
				appendProductNoteField();
			});
		}
		
		function closeExtrasPopup() {
			extrasOverlay.classList.remove('active');
			pendingProduct = null;
		}
		
		/**
		 * Inicializa os controles de qty do HTML renderizado pelo servidor.
		 * Replica a lógica do frontend: botões -/+ com inputs option_prod_exta.
		 */
		function initExtrasFromDOM() {
			// Inicializar limites dos botões
			extrasBody.querySelectorAll('.fdm-extra-option-title').forEach(function(group) {
				var max = parseInt(group.dataset.selectLimit) || Infinity;
				var inputs = group.querySelectorAll('input[type="number"].option_prod_exta');
				if (inputs.length === 0) return;
				
				function updateButtons() {
					var sum = 0;
					inputs.forEach(function(inp) { sum += (parseInt(inp.value) || 0); });
					inputs.forEach(function(inp) {
						var control = inp.parentElement;
						var minus = control.querySelector('.myd-qty-minus');
						var plus = control.querySelector('.myd-qty-plus');
						var value = parseInt(inp.value) || 0;
						if (minus) minus.disabled = value <= 0;
						if (plus) plus.disabled = sum >= max;
					});
					validateExtrasFromDOM();
				}
				
				inputs.forEach(function(inp) {
					inp.addEventListener('change', updateButtons);
					inp.addEventListener('input', updateButtons);
				});
				
				// Substituir os onclick inline dos botões por event listeners
				group.querySelectorAll('.myd-qty-minus').forEach(function(btn) {
					btn.onclick = null;
					btn.removeAttribute('onclick');
					btn.addEventListener('click', function(e) {
						e.preventDefault();
						var input = this.nextElementSibling;
						if (input && parseInt(input.value) > 0) {
							input.value = parseInt(input.value) - 1;
							input.dispatchEvent(new Event('change'));
						}
					});
				});
				
				group.querySelectorAll('.myd-qty-plus').forEach(function(btn) {
					btn.onclick = null;
					btn.removeAttribute('onclick');
					btn.addEventListener('click', function(e) {
						e.preventDefault();
						var input = this.previousElementSibling;
						if (input) {
							input.value = parseInt(input.value) + 1;
							input.dispatchEvent(new Event('change'));
						}
					});
				});
				
				updateButtons();
			});
		}
		
		/**
		 * Valida seleções obrigatórias com base nos data-attributes do DOM.
		 */
		function validateExtrasFromDOM() {
			var valid = true;
			extrasBody.querySelectorAll('.fdm-extra-option-title').forEach(function(group) {
				var obj = group.dataset.obj;
				// obj contém o atributo data-obj que indica se é obrigatório
				if (obj && obj.indexOf('required') !== -1) {
					var minRequired = parseInt(group.dataset.min) || 1;
					var inputs = group.querySelectorAll('input[type="number"].option_prod_exta');
					var sum = 0;
					inputs.forEach(function(inp) { sum += (parseInt(inp.value) || 0); });
					if (sum < minRequired) {
						valid = false;
					}
				}
			});
			extrasConfirmBtn.disabled = !valid;
		}
		
		/**
		 * Coleta extras selecionados a partir dos inputs option_prod_exta no DOM.
		 * Usa os mesmos data-attributes do template do frontend.
		 */
		function collectExtrasFromDOM() {
			var groupsMap = {};
			extrasBody.querySelectorAll('input.option_prod_exta').forEach(function(input) {
				var qty = parseInt(input.value) || 0;
				if (qty <= 0) return;
				
				var name = input.dataset.name || '';
				var price = parseFloat(input.dataset.price) || 0;
				var groupName = input.dataset.extraGroup || 'Extras';
				
				if (!groupsMap[groupName]) {
					groupsMap[groupName] = { group: groupName, items: [], total: 0 };
				}
				
				var itemTotal = price * qty;
				groupsMap[groupName].items.push({
					name: name,
					price: price,
					quantity: qty,
					total: itemTotal
				});
				groupsMap[groupName].total += itemTotal;
			});
			
			var groups = [];
			var extrasTotal = 0;
			Object.keys(groupsMap).forEach(function(key) {
				groups.push(groupsMap[key]);
				extrasTotal += groupsMap[key].total;
			});
			
			return { total: extrasTotal, groups: groups };
		}
		
		// Eventos do popup
		extrasCloseBtn.addEventListener('click', closeExtrasPopup);
		extrasCancelBtn.addEventListener('click', closeExtrasPopup);
		extrasOverlay.addEventListener('click', function(e) {
			if (e.target === extrasOverlay) closeExtrasPopup();
		});
		extrasConfirmBtn.addEventListener('click', function() {
			if (!pendingProduct) return;
			var extras = collectExtrasFromDOM();
			pendingProduct.extras = extras;
			pendingProduct.note = getProductNote();
			addProduct(pendingProduct);
			closeExtrasPopup();
		});
		// ===== FIM EXTRAS POPUP =====
		
		// Add product
		function addProduct(product){
			// Sempre adicionar como novo item (extras podem ser diferentes)
			orderItems.push(product);
			renderItems();
			updateSummary();
		}
		
		// Render items
		function renderItems(){
			if (!orderItems.length) {
				orderItemsContainer.innerHTML = '<div class="myd-empty-items">Nenhum produto adicionado</div>';
				return;
			}
			
			var html = '';
			orderItems.forEach(function(item, index){
				var extrasTotal = (item.extras && item.extras.total) ? item.extras.total : 0;
				var unitPrice = item.price + extrasTotal;
				var total = unitPrice * item.quantity;
				html += '<div class="myd-order-item">';
				html += '<div class="myd-order-item-info">';
				html += '<div class="myd-order-item-name">' + escapeHtml(item.name) + '</div>';
				html += '<div class="myd-order-item-price">' + currencySymbol + ' ' + formatPrice(item.price) + ' cada</div>';
				// Exibir extras selecionados
                    if (item.extras && item.extras.groups && item.extras.groups.length) {
                    	html += '<div class="myd-extras-list">';
                    	item.extras.groups.forEach(function(g) {
                    		g.items.forEach(function(ei) {
                    			html += '<div class="myd-extra-item-line">+ ' + escapeHtml(ei.name);
                    			if (ei.quantity > 1) html += ' (' + ei.quantity + 'x)';
                    			if (ei.price > 0) html += ' (' + currencySymbol + ' ' + formatPrice(ei.total) + ')';
                    			html += '</div>';
                    		});
                    	});
                    	html += '</div>';
                	}
				if (item.note) {
					html += '<div class="myd-item-note">Obs: ' + escapeHtml(item.note) + '</div>';
				}
				html += '</div>';
				html += '<div class="myd-qty-controls">';
				html += '<button type="button" class="myd-qty-btn" data-action="decrease" data-index="' + index + '">−</button>';
				html += '<span class="myd-qty-count">' + item.quantity + '</span>';
				html += '<button type="button" class="myd-qty-btn" data-action="increase" data-index="' + index + '">+</button>';
				html += '</div>';
				html += '<div class="myd-order-item-total">' + currencySymbol + ' ' + formatPrice(total) + '</div>';
				html += '<button type="button" class="myd-remove-item" data-index="' + index + '">✕</button>';
				html += '</div>';
			});
			orderItemsContainer.innerHTML = html;
			
			// Quantity events
			orderItemsContainer.querySelectorAll('.myd-qty-btn').forEach(function(btn){
				btn.addEventListener('click', function(){
					var idx = parseInt(this.dataset.index);
					if (this.dataset.action === 'increase') {
						orderItems[idx].quantity++;
					} else if (orderItems[idx].quantity > 1) {
						orderItems[idx].quantity--;
					}
					renderItems();
					updateSummary();
				});
			});
			
			// Remove events
			orderItemsContainer.querySelectorAll('.myd-remove-item').forEach(function(btn){
				btn.addEventListener('click', function(){
					orderItems.splice(parseInt(this.dataset.index), 1);
					renderItems();
					updateSummary();
				});
			});
		}
		
		// Update summary
		function updateSummary(){
			var subtotal = orderItems.reduce(function(sum, item){
				var extrasTotal = (item.extras && item.extras.total) ? item.extras.total : 0;
				return sum + ((item.price + extrasTotal) * item.quantity);
			}, 0);
			
			var deliveryFee = parseFloat(deliveryFeeInput.value) || 0;
			
			var total = subtotal + deliveryFee;
			
			document.getElementById('summary_subtotal').textContent = currencySymbol + ' ' + formatPrice(subtotal);
			document.getElementById('summary_delivery').textContent = currencySymbol + ' ' + formatPrice(deliveryFee);
			document.getElementById('summary_total').textContent = currencySymbol + ' ' + formatPrice(total);
		}
		
		// Format price
		function formatPrice(value){
			return parseFloat(value || 0).toFixed(2).replace('.', ',');
		}
		
		// Escape HTML
		function escapeHtml(str){
			var div = document.createElement('div');
			div.textContent = str;
			return div.innerHTML;
		}
		
		// Submit form
		form.addEventListener('submit', function(e){
			e.preventDefault();
			
			if (!orderItems.length) {
				showToast('Adicione pelo menos um produto ao pedido', 'error');
				return;
			}
			
			var submitBtn = document.getElementById('btn_submit');
			var originalText = submitBtn.textContent;
			submitBtn.textContent = 'Criando...';
			submitBtn.disabled = true;
			
			var formData = new FormData(form);
			
			var orderChannel = formData.get('order_channel') || '';
			// Mapear canais
			if (orderChannel === 'Whatsapp') {
				orderChannel = 'WPP';
			} else if (orderChannel === 'iFood') {
				orderChannel = 'IFD';
			}
			
			var payload = {
				nonce: formData.get('nonce'),
				order_type: (formData.get('delivery_type') === 'pickup') ? 'pickup' : 'delivery',
				customer_name: formData.get('customer_name'),
				customer_phone: formData.get('customer_phone'),
				payment_method: formData.get('payment_method'),
				change_for: getInputValue().toString() || '',
				order_notes: formData.get('order_notes') || '',
				items: orderItems,
				address: formData.get('address'),
				address_number: formData.get('address_number'),
				neighborhood: formData.get('neighborhood'),
				address_comp: formData.get('address_comp'),
				reference: formData.get('reference'),
				delivery_fee: formData.get('delivery_fee'),
				order_channel: orderChannel
			};
			
			fetch('/wp-json/myd-delivery/v1/orders/create-manual', {
				method: 'POST',
				headers: { 
					'Content-Type': 'application/json',
					'X-WP-Nonce': restNonce
				},
				body: JSON.stringify(payload),
				credentials: 'same-origin'
			})
			.then(function(res){ return res.json(); })
			.then(function(resp){
				if (resp.success) {
					showToast('Pedido #' + resp.order_id + ' criado com sucesso!', 'success');
					// Resetar formulário para criar novo pedido
					form.reset();
					orderItems = [];
					renderItems();
					updateSummary();
					// Limpar preview do endereço
					var addrPreview = document.getElementById('myd-address-preview');
					if (addrPreview) addrPreview.style.display = 'none';
					var addrInput = document.getElementById('address');
					if (addrInput) addrInput.style.display = '';
					deliveryFeeInput.value = '0';
					var deliveryFeeDisplay = document.getElementById('delivery_fee_display');
					if (deliveryFeeDisplay) deliveryFeeDisplay.textContent = currencySymbol + ' 0,00';
				} else {
					showToast(resp.message || 'Erro ao criar pedido', 'error');
				}
			})
			.catch(function(err){
				console.error('Erro ao criar pedido:', err);
				showToast('Erro de conexão', 'error');
			})
			.finally(function(){
				submitBtn.textContent = originalText;
				submitBtn.disabled = false;
			});
		});
	})();
	</script>
</body>
</html>
