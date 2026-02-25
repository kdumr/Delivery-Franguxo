<?php
use MydPro\Includes\Store_Data;
if ( ! defined( 'ABSPATH' ) ) exit;

function myd_store_status_shortcode($atts = []) {
    $atts = shortcode_atts([
        'logo_url' => '/wp-content/plugins/myd-delivery-pro/assets/img/franguxo%20icon.png'
    ], $atts);
    ob_start();
    $currency_simbol = Store_Data::get_store_data('currency_simbol');
    $store_open = Store_Data::is_store_open();
    $preparation_time = get_option('myd-average-preparation-time', '');
    $business_name = get_option('fdm-business-name', '');
    wp_enqueue_style( 'myd-delivery-frontend' );
    ?>
    <section class="myd-open-store" id="myd-store-status-wrapper" style="opacity: 0; transition: opacity 0.4s ease-in-out;">
        <div class="myd-store-status-row">
            <img src="<?php echo esc_url($atts['logo_url']); ?>" alt="Logo Loja" class="myd-store-status-logo" />
            <div class="myd-store-title"><?php echo esc_html($business_name); ?></div>

            <!-- Badges removidos: use o shortcode [myd_store_status_badges] separadamente onde desejar -->
        </div>
    </section>
    <script>
    (function(){
        try {
            var el = document.getElementById('myd-store-status-wrapper');
            if(!el) return;
            function show(){ el.style.opacity = 1; }
            if(document.readyState === 'complete'){ show(); }
            else { window.addEventListener('load', show); }
        } catch(e){ console.error(e); }
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('myd_store_status', 'myd_store_status_shortcode');

// Badges (pedido mínimo + tempo de preparo) como shortcode separado
function myd_store_status_badges_shortcode() {
    $currency_simbol = Store_Data::get_store_data('currency_simbol');
    $minimum_order = Store_Data::get_store_data('minimum_order');
    $preparation_time = get_option('myd-average-preparation-time', '');
    wp_enqueue_style( 'myd-delivery-frontend' );
    ob_start();

    $min_html = '';
    if ($minimum_order) {
        $min_html = '<div class="myd-minimum-order">'
            . '<span class="myd-store-svg">'
            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 192" width="15" height="15">'
            . '<path fill="#2b2b2b" d="M96,0C42.98,0,0,42.98,0,96s42.98,96,96,96,96-42.98,96-96S149.02,0,96,0ZM103.63,142.96c-.74,4.67,1.01,11.16-4.5,13.4-8.81,3.59-10.82-6.74-9.91-13.3-9.89-.72-21.5-7.58-23.31-17.94-1.4-8.01,7.06-12.19,11.86-6.1,1.93,2.45,1.65,4.68,4.47,7.04,7.01,5.87,26.99,5.45,30.4-4.8,2.47-7.42-.45-12.25-7.14-15.27-12.82-5.78-31.65-5.6-37.98-20.85-6.84-16.49,4.21-34.62,21.68-36.87.14-4.89-.86-12.05,5.18-13.68,8.48-2.29,9.78,7.63,9,13.74,7.08.8,14.25,3.32,18.84,8.97,3.6,4.43,8.14,14.36.68,17.32-8.67,3.44-8.38-6.45-13.2-9.91-4.48-3.22-12.77-3.47-18.11-2.85-12.82,1.48-17.48,17.2-5.64,23.41,12.9,6.76,33.88,5.53,39.64,22.39,5.65,16.54-5.12,32.67-21.96,35.31Z"/>'
            . '</svg>'
            . '</span>'
            . esc_html__('Pedido mín.: ', 'myd-delivery-pro') . ' ' . $currency_simbol . ' ' . number_format((float)$minimum_order, 2, ',', '.')
            . '</div>';
    }

    $prep_html = '';
    if ($preparation_time !== '' && $preparation_time > 0) {
        $prep_html = '<div class="myd-preparation-time">'
            . '<span class="myd-store-svg">'
            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 192" width="15" height="15">'
            . '<path fill="#2b2b2b" d="M96,45.21c-28.06,0-50.79,22.73-50.79,50.79s22.73,50.79,50.79,50.79,50.79-22.73,50.79-50.79-22.73-50.79-50.79-50.79ZM122.98,122.35l-33.33-20v-38.1h9.52v33.33l28.57,16.95-4.76,7.81Z"/>'
            . '<path fill="#2b2b2b" d="M96,0C42.98,0,0,42.98,0,96s42.98,96,96,96,96-42.98,96-96S149.02,0,96,0ZM95.94,159.49c-35.05,0-63.43-28.44-63.43-63.49s28.38-63.49,63.43-63.49,63.56,28.44,63.56,63.49-28.44,63.49-63.56,63.49Z"/>'
            . '</svg>'
            . '</span>'
            . '<span>' . intval($preparation_time) . ' min</span>'
            . '</div>';
    }

    if ($min_html || $prep_html) {
        ?>
        <div class="myd-store-status-badges" id="myd-store-badges-wrapper" style="opacity: 0; transition: opacity 0.4s ease-in-out;">
            <div class="myd-info-trigger" role="button" tabindex="0" aria-haspopup="dialog" aria-label="<?php echo esc_attr__('Informações de entrega', 'myd-delivery-pro'); ?>">
                <?php echo $min_html . $prep_html; ?>
            </div>

            <div class="myd-info-modal" role="dialog" aria-modal="true" aria-hidden="true">
                <div class="myd-info-modal-inner">
                    <div class="myd-info-header">
                        <div class="space"></div>
                        <span class="myd-info-title"><?php echo esc_html__('Informações da loja:', 'myd-delivery-pro'); ?></span>
                        <label class="myd-info-close" aria-label="<?php echo esc_attr__('Fechar', 'myd-delivery-pro'); ?>" tabindex="0" role="button">
                            <svg class="myd-info-close-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="20" height="20" aria-hidden="true">
                                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                <g id="SVGRepo_iconCarrier">
                                    <path d="M6 6L18 18" stroke-width="2" stroke-linecap="round"></path>
                                    <path d="M18 6L6 18" stroke-width="2" stroke-linecap="round"></path>
                                </g>
                            </svg>
                        </label>
                    </div>
                    <!-- Conteúdo carregado via AJAX ao abrir o modal -->
                    <div id="myd-info-body-lazy" style="display:flex;align-items:center;justify-content:center;min-height:120px;">
                        <svg width="36" height="36" viewBox="0 0 50 50" aria-hidden="true">
                            <circle cx="25" cy="25" r="20" fill="none" stroke="#ccc" stroke-width="4" stroke-linecap="round" stroke-dasharray="31.4 31.4">
                                <animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.8s" repeatCount="indefinite"/>
                            </circle>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="myd-label-more" role="button" tabindex="0" aria-haspopup="dialog" aria-label="<?php echo esc_attr__('Mais informações da loja', 'myd-delivery-pro'); ?>">
                <span>Ver mais</span>
                <svg viewBox="-4.5 0 20 20" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="#888888" style="width:10px;height:10px;display:block;">
						<g id="SVGRepo_bgCarrier" stroke-width="0"></g>
						<g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
						<g id="SVGRepo_iconCarrier">
							<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
								<g id="Dribbble-Light-Preview" transform="translate(-305.000000, -6679.000000)" fill="#888888">
									<g id="icons" transform="translate(56.000000, 160.000000)">
										<path d="M249.365851,6538.70769 L249.365851,6538.70769 C249.770764,6539.09744 250.426289,6539.09744 250.830166,6538.70769 L259.393407,6530.44413 C260.202198,6529.66364 260.202198,6528.39747 259.393407,6527.61699 L250.768031,6519.29246 C250.367261,6518.90671 249.720021,6518.90172 249.314072,6519.28247 L249.314072,6519.28247 C248.899839,6519.67121 248.894661,6520.31179 249.302681,6520.70653 L257.196934,6528.32352 C257.601847,6528.71426 257.601847,6529.34685 257.196934,6529.73759 L249.365851,6537.29462 C248.960938,6537.68437 248.960938,6538.31795 249.365851,6538.70769" id="arrow_right-[]"> </path>
									</g>
								</g>
							</g>
						</g>
					</svg>
            </div>


            <script>
            (function(){
                try {
                	// Select multiple triggers if necessary (badges trigger + label-more)
                    var triggers = document.querySelectorAll('.myd-info-trigger, .myd-label-more');
                    var modal = document.querySelector('.myd-info-modal');
                    var closeBtn = modal ? modal.querySelector('.myd-info-close') : null;
                    if (!triggers.length || !modal) return;

                    var lazyLoaded = false;
                    var ajaxUrl = (typeof myd_ajax_object !== 'undefined' && myd_ajax_object.ajax_url)
                        ? myd_ajax_object.ajax_url
                        : '<?php echo esc_js( admin_url("admin-ajax.php") ); ?>';

                    function loadInfoContent(){
                        if (lazyLoaded) return;
                        lazyLoaded = true;
                        var container = document.getElementById('myd-info-body-lazy');
                        if (!container) return;

                        fetch(ajaxUrl + '?action=myd_store_info_content', {
                            method: 'GET',
                            credentials: 'same-origin'
                        })
                        .then(function(r){ return r.json(); })
                        .then(function(resp){
                            if (resp && resp.success && resp.data && resp.data.html) {
                                container.innerHTML = resp.data.html;
                                container.style.display = '';
                                container.style.minHeight = '';
                                container.style.alignItems = '';
                                container.style.justifyContent = '';
                            } else {
                                container.innerHTML = '<p style="text-align:center;color:#999;">Erro ao carregar informações</p>';
                            }
                        })
                        .catch(function(){
                            container.innerHTML = '<p style="text-align:center;color:#999;">Erro ao carregar informações</p>';
                        });
                    }

                    function openModal(){
                        modal.setAttribute('aria-hidden','false');
                        document.body.style.overflow = 'hidden';
                        loadInfoContent();
                    }
                    function closeModal(){
                        modal.setAttribute('aria-hidden','true');
                        document.body.style.overflow = '';
                    }
                    triggers.forEach(function(t){
                        t.addEventListener('click', openModal);
                        t.addEventListener('keydown', function(e){ if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openModal(); }});
                    });
                    if (closeBtn) closeBtn.addEventListener('click', closeModal);
                    modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
                    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeModal(); });
                } catch(e){ console.error(e); }
            })();
            </script>
            <script>
            (function(){
                try {
                    var el = document.getElementById('myd-store-badges-wrapper');
                    if(!el) return;
                    function show(){ el.style.opacity = 1; }
                    if(document.readyState === 'complete'){ show(); }
                    else { window.addEventListener('load', show); }
                } catch(e){}
            })();
            </script>
        </div>
        <?php
    }

    return ob_get_clean();
}
add_shortcode('myd_store_status_badges', 'myd_store_status_badges_shortcode');

// AJAX endpoint: retorna conteúdo do modal de informações da loja (lazy load)
function myd_ajax_store_info_content() {
    $currency_simbol = Store_Data::get_store_data('currency_simbol');
    $minimum_order = Store_Data::get_store_data('minimum_order');
    $preparation_time = get_option('myd-average-preparation-time', '');

    ob_start();
    ?>
    <div class="myd-info-body">
        <p class="myd-info-body-title"><?php echo esc_html__('Tempo de entrega e pedido mínimo:', 'myd-delivery-pro'); ?></p>
        <?php if ($minimum_order) : ?>
            <p class="myd-info-row"><strong><?php echo esc_html__('Pedido mínimo:', 'myd-delivery-pro'); ?></strong> <?php echo $currency_simbol . ' ' . number_format((float)$minimum_order, 2, ',', '.'); ?></p>
        <?php endif; ?>
        <?php if ($preparation_time !== '' && $preparation_time > 0) : ?>
            <p class="myd-info-row"><strong><?php echo esc_html__('Tempo médio de entrega:', 'myd-delivery-pro'); ?></strong> <?php echo intval($preparation_time) . ' ' . esc_html__('min', 'myd-delivery-pro'); ?></p>
        <?php endif; ?>
    </div>
    <div class="myd-info-body">
        <p class="myd-info-body-title">Bandeiras aceitas:</p>
        <div class="myd-bandeiras-chips" style="display: flex; justify-content: center; flex-wrap: wrap; gap: 8px;">
        <?php
        $bandeiras_unificadas = [
            ['credito' => 'credit_card_visa',      'debito' => 'debit_card_visa',   'img' => 'visa.png',      'label' => 'Cartão Visa'],
            ['credito' => 'credit_card_master',     'debito' => 'debit_card_master', 'img' => 'master.png',    'label' => 'Cartão Master'],
            ['credito' => 'credit_card_elo',        'debito' => 'debit_card_elo',    'img' => 'elo.png',       'label' => 'Cartão Elo'],
            ['credito' => 'credit_card_cabal',      'debito' => 'debit_card_cabal',  'img' => 'cabal.png',     'label' => 'Cartão Cabal'],
            ['credito' => 'credit_card_hipercard',  'debito' => null,                'img' => 'hipercard.png', 'label' => 'Cartão Hipercard'],
            ['credito' => 'credit_card_amex',       'debito' => null,                'img' => 'amex.png',      'label' => 'Cartão American Express'],
            ['credito' => 'credit_card_diners',     'debito' => null,                'img' => 'diners.png',    'label' => 'Cartão Diners'],
            ['credito' => 'credit_card_hiper',      'debito' => null,                'img' => 'hiper.png',     'label' => 'Cartão Hiper'],
        ];
        foreach ($bandeiras_unificadas as $b) {
            $tem_credito = $b['credito'] && get_option($b['credito']) === '1';
            $tem_debito  = $b['debito'] && get_option($b['debito']) === '1';
            if ($tem_credito || $tem_debito) {
                $tipos = [];
                if ($tem_credito) $tipos[] = 'Crédito';
                if ($tem_debito)  $tipos[] = 'Débito';
                echo '<span class="myd-bandeira-chip" style="display:inline-flex;align-items:center;padding:3px 10px 3px 6px;border-radius:16px;background:#f5f5f5;border:1px solid #e0e0e0;font-size:13px;font-weight:500;gap:6px;white-space:nowrap;">'
                    .'<img src="'.esc_attr(MYD_PLUGN_URL.'assets/img/'.$b['img']).'" alt="'.esc_attr($b['label']).'" style="height:18px;width:auto;vertical-align:middle;">'
                    .'<span style="color:#444;">'.esc_html($b['label'].' - '.implode('/', $tipos)).'</span>'
                .'</span>';
            }
        }

        $vouchers = [
            ['opt'=>'voucher_pluxee', 'img'=>'pluxee.png', 'label'=>'Pluxee'],
            ['opt'=>'voucher_ticket', 'img'=>'ticket.png', 'label'=>'Ticket'],
            ['opt'=>'voucher_alelo', 'img'=>'alelo.png', 'label'=>'Alelo'],
            ['opt'=>'voucher_vr', 'img'=>'vrbeneficios.png', 'label'=>'VR Benefícios'],
        ];
        foreach ($vouchers as $v) {
            if (get_option($v['opt']) === '1') {
                echo '<span class="myd-bandeira-chip" style="display:inline-flex;align-items:center;padding:3px 10px 3px 6px;border-radius:16px;background:#f5f5f5;border:1px solid #e0e0e0;font-size:13px;font-weight:500;gap:6px;white-space:nowrap;">'
                    .'<img src="'.esc_attr(MYD_PLUGN_URL.'assets/img/'.$v['img']).'" alt="'.esc_attr($v['label']).'" style="height:18px;width:auto;vertical-align:middle;">'
                    .'<span style="color:#444;">'.esc_html($v['label']).'</span>'
                .'</span>';
            }
        }

        $digitais = [
            ['opt'=>'digital_pix', 'img'=>'pix.png', 'label'=>'PIX'],
            ['opt'=>'digital_googlepay', 'img'=>'googlepay.png', 'label'=>'Google Pay'],
            ['opt'=>'digital_applepay', 'img'=>'applepay.png', 'label'=>'Apple Pay'],
            ['opt'=>'digital_samsungpay', 'img'=>'samsungpay.png', 'label'=>'Samsung Pay'],
        ];
        foreach ($digitais as $d) {
            if (get_option($d['opt']) === '1') {
                echo '<span class="myd-bandeira-chip" style="display:inline-flex;align-items:center;padding:3px 10px 3px 6px;border-radius:16px;background:#f5f5f5;border:1px solid #e0e0e0;font-size:13px;font-weight:500;gap:6px;white-space:nowrap;">'
                    .'<img src="'.esc_attr(MYD_PLUGN_URL.'assets/img/'.$d['img']).'" alt="'.esc_attr($d['label']).'" style="height:18px;width:auto;vertical-align:middle;">'
                    .'<span style="color:#444;">'.esc_html($d['label']).'</span>'
                .'</span>';
            }
        }
        ?>
        </div>
    </div>
    <div class="myd-info-body">
        <p class="myd-info-body-title"><?php echo esc_html__('Endereço e telefone:', 'myd-delivery-pro'); ?></p>
        <?php
        $store_address = get_option('myd-business-address');
        $store_whatsapp = get_option('myd-business-whatsapp');
        $whatsapp_clean = preg_replace('/\D/', '', $store_whatsapp);
        if (strlen($whatsapp_clean) === 13 && substr($whatsapp_clean, 0, 2) === '55') {
            $whatsapp_clean = substr($whatsapp_clean, 2);
        }
        $whatsapp_fmt = $whatsapp_clean;
        if (strlen($whatsapp_clean) === 11) {
            $whatsapp_fmt = '(' . substr($whatsapp_clean, 0, 2) . ') ' . substr($whatsapp_clean, 2, 5) . '-' . substr($whatsapp_clean, 7, 4);
        }
        ?>
        <p class="myd-info-row">
            <strong><?php echo esc_html__('Endereço:', 'myd-delivery-pro'); ?></strong>
            <?php echo esc_html($store_address); ?>
        </p>
        <p class="myd-info-row">
            <strong><?php echo esc_html__('WhatsApp:', 'myd-delivery-pro'); ?></strong>
            <?php echo esc_html($whatsapp_fmt); ?>
        </p>
    </div>
    <?php
    $html = ob_get_clean();
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_myd_store_info_content', 'myd_ajax_store_info_content');
add_action('wp_ajax_nopriv_myd_store_info_content', 'myd_ajax_store_info_content');

// Badge de status da loja (aberta/fechada)
function myd_store_status_badge_shortcode() {
    $store_open = \MydPro\Includes\Store_Data::is_store_open();
    wp_enqueue_style( 'myd-delivery-frontend' );
    ob_start();
    ?>
    <div id="myd-store-badge-wrapper" class="myd-store-status-badge <?php echo $store_open ? 'open' : 'closed'; ?>" style="opacity: 0; transition: opacity 0.4s ease-in-out;">
        <div class="myd-store-status-badge-content">
            <span><?php echo $store_open ? 'Loja aberta' : 'Loja Fechada'; ?></span>
        </div>
    </div>
    <script>
    (function(){
        try {
            var el = document.getElementById('myd-store-badge-wrapper');
            if(!el) return;
            function show(){ el.style.opacity = 1; }
            if(document.readyState === 'complete'){ show(); }
            else { window.addEventListener('load', show); }
        } catch(e){ console.error(e); }
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('myd_store_status_badge', 'myd_store_status_badge_shortcode');

// Badge somente quando a loja estiver aberta
function myd_store_open_badge_shortcode() {
    if ( ! \MydPro\Includes\Store_Data::is_store_open() ) {
        return '';
    }
    return myd_store_status_badge_shortcode();
}
add_shortcode('myd_store_open_badge', 'myd_store_open_badge_shortcode');

// Inline script for realtime store status updates
add_action('wp_footer', function() {
    if ( ! is_admin() ) {
        $push_url = esc_js( get_option( 'myd_push_server_url', '' ) );
        if ( empty( $push_url ) ) return;
        ?>
        <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
        <script>
        (function(){
            try {
                var pushUrl = '<?php echo $push_url; ?>';
                if (!pushUrl) return;
                function requestToken(cb) {
                    // fetch token for anonymous store events - use 0 as fallback id
                    fetch('<?php echo esc_url_raw( rest_url("myd-delivery/v1/push/auth") ); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ myd_customer_id: 0 })
                    }).then(r=>r.json()).then(cb).catch(function(e){ console.error('Token fetch error', e); });
                }

                requestToken(function(data){
                    if (!data || !data.token) return;
                    var socket = io(pushUrl, { auth: { token: data.token } });
                    socket.on('connect', function(){ console.log('[StoreStatus] connected to push server'); });
                    socket.on('store.status', function(payload){
                        console.log('[StoreStatus] store.status', payload);
                        try {
                            var open = !!payload.open;
                            var badges = document.querySelectorAll('.myd-store-status-badge');
                            if (!badges || !badges.length) return;
                            badges.forEach(function(badgeDiv){
                                badgeDiv.classList.toggle('open', open);
                                badgeDiv.classList.toggle('closed', !open);
                                badgeDiv.style.color = open ? '#28a745' : '#ea1d2b';
                                badgeDiv.style.backgroundColor = open ? '#eafaf1' : '#f8d7da';
                                var span = badgeDiv.querySelector('span');
                                if (span) span.textContent = open ? 'Loja aberta' : 'Loja Fechada';
                            });
                        } catch(e) { console.error(e); }
                    });
                });
            } catch(e) { console.error(e); }
        })();
        </script>
        <?php
    }
});