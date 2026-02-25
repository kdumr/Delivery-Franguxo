<?php
use MydPro\Includes\Store_Data;
// Shortcode: [myd_set_store_status_box]
if ( ! function_exists( 'myd_get_status_icon_svg' ) ) {
function myd_get_status_icon_svg($status, $store_open) {
    if ($store_open) {
        return '<svg xmlns="" x="0px" y="0px" width="24" height="24" viewBox="0 0 48 48" style="margin-right: 8px;"><path fill="#4caf50" d="M44,24c0,11.045-8.955,20-20,20S4,35.045,4,24S12.955,4,24,4S44,12.955,44,24z"></path><path fill="#ccff90" d="M34.602,14.602L21,28.199l-5.602-5.598l-2.797,2.797L21,33.801l16.398-16.402L34.602,14.602z"></path></svg>';
    } else {
        return '<svg xmlns="" x="0px" y="0px" width="24" height="24" viewBox="0 0 48 48" style="margin-right: 8px;"><path fill="#f44336" d="M44,24c0,11.045-8.955,20-20,20S4,35.045,4,24S12.955,4,24,4S44,12.955,44,24z"></path><path fill="#ffcdd2" d="M31.617,18.383l-2.033-2.033L24,21.967l-5.583-5.583l-2.033,2.033L21.967,24l-5.583,5.583l2.033,2.033L24,26.033l5.583,5.583l2.033-2.033L26.033,24L31.617,18.383z"></path></svg>';
    }
}
}

/**
 * Backwards-compatible wrapper expected by templates: returns inline SVG for open/close.
 * @param bool $is_open true for open icon, false for close icon
 * @return string SVG markup
 */
if ( ! function_exists( 'myd_get_status_icon_svg_inline' ) ) {
function myd_get_status_icon_svg_inline($is_open) {
    return myd_get_status_icon_svg($is_open ? 'open' : 'close', $is_open);
}
}

// Shortcode desativado: agora o controle está embutido em templates/order/panel.php
// add_shortcode('myd_set_store_status_box', function() {
    if ( ! function_exists( 'is_user_logged_in' ) || ! function_exists( 'myd_user_is_allowed_admin' ) ) {
        return '';
    }

    if (!is_user_logged_in() || !myd_user_is_allowed_admin()) {
        return '';
    }
    if (
        isset($_POST['myd_store_status_box_nonce'], $_POST['myd_store_status']) &&
        wp_verify_nonce($_POST['myd_store_status_box_nonce'], 'myd_store_status_box')
    ) {
        $new_status = sanitize_text_field($_POST['myd_store_status']);
        $allowed_statuses = ['ignore', 'open', 'close'];

        if (in_array($new_status, $allowed_statuses, true)) {
            $current_status = get_option('myd-delivery-force-open-close-store', 'ignore');
            if ($new_status !== $current_status) {
                update_option('myd-delivery-force-open-close-store', $new_status);
                exit;
            }
        }
    }
    // Sempre busca o valor atualizado da configuração
    $current = get_option('myd-delivery-force-open-close-store', 'ignore');
    $store_open = Store_Data::is_store_open();
    $status_map = [
        'ignore' => ['text' => $store_open ? 'Loja aberta' : 'Loja fechada', 'subtext' => $store_open ? '(Dentro do horário programado)' : '(Fora do horário programado)'],
        'open'   => ['text' => 'Loja aberta', 'subtext' => ''],
        'close'  => ['text' => 'Loja fechada', 'subtext' => ''],
    ];
    $current_texts = $status_map[$current];
    ob_start();
    $svg_open = myd_get_status_icon_svg('open', true);
    $svg_close = myd_get_status_icon_svg('close', false);
    ?>
    <form method="post" id="myd-store-status-form">
        <?php wp_nonce_field('myd_store_status_box', 'myd_store_status_box_nonce'); ?>
    </form>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        .select-menu {
            width: 250px;
            margin: 20px auto;
        }

        .select-menu .select-btn {
            display: flex;
            height: 95px;
            background: #fff;
            padding: 20px;
            font-size: 18px;
            font-weight: 400;
            border-radius: 8px;
            align-items: center;
            cursor: pointer;
            justify-content: space-between;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .select-btn i {
            font-size: 25px;
            transition: 0.3s;
        }

        .select-btn.status-open {
            background-color: #d4edda; /* Verde claro */
            color: #155724; /* Verde escuro */
        }

        .select-btn.status-close {
            background-color: #f8d7da; /* Vermelho claro */
            color: #721c24; /* Vermelho escuro */
        }

        .select-btn.status-ignore {
            background: #fff;
            color: #333;
        }

        .select-menu.active .select-btn i {
            transform: rotate(-180deg);
        }

        .select-menu .options {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%; /* Ajusta para o tamanho exato do botão select */
            z-index: 1000;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
            background: #e5e5e5;
            box-shadow: 0 0 3px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            pointer-events: none;
            visibility: hidden;
        }

        .select-menu.active .options {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
            visibility: visible;
        }

        .options .option {
            display: flex;
            height: 55px;
            cursor: pointer;
            padding: 0 16px;
            border-radius: 8px;
            align-items: center;
            
        }

        .options .option:hover {
            background-color: #f0f0f0; /* Cor de fundo ao passar o mouse */
            transition: background-color 0.3s ease; /* Suaviza a transição */
        }

        .options .option.active {
            background-color: #ffffff; /* destaca a opção selecionada */
            box-shadow: inset 0 0 0 2px rgba(0,0,0,0.04);
        }

        .option i {
            font-size: 25px;
            margin-right: 12px;
        }

        .option .option-text {
            font-size: 18px;
            color: #333;
        }

        /* Force SVG icon sizes to be consistent */
        .select-menu .select-btn svg,
        .select-menu .options .option svg {
            width: 24px !important;
            height: 24px !important;
            flex-shrink: 0;
            margin-right: 8px;
        }

        .sBtn-text {
            display: block;
            line-height: 1.2;
        }
        .sBtn-subtext {
            font-size: 12px; /* Ajusta o tamanho da fonte para maior visibilidade */
            color: #888; /* Torna a cor mais clara */
            line-height: 1.4; /* Ajusta o espaçamento entre linhas */
            display: block; /* Garante que fique abaixo do texto principal */
            margin-top: 4px; /* Adiciona mais espaçamento acima */
            text-align: left; /* Garante alinhamento consistente */
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function initAdminStoreStatus() {
            const optionMenu = document.querySelector(".select-menu");
            if (!optionMenu) return;
            const selectBtn = optionMenu.querySelector(".select-btn"),
                  options = optionMenu.querySelectorAll(".option"),
                  sBtn_text = optionMenu.querySelector(".sBtn-text"),
                  sBtn_subtext = optionMenu.querySelector(".sBtn-subtext");

            // SVG templates
            <?php if ( function_exists( 'json_encode' ) ) : ?>
                var svgOpen = <?php echo json_encode( $svg_open ); ?>;
                var svgClose = <?php echo json_encode( $svg_close ); ?>;
            <?php else : ?>
                var svgOpen = '';
                var svgClose = '';
            <?php endif; ?>

            // helper to update UI based on status for a given menu element
            function applyStatusToUI(menu, status, storeOpen) {
                const btn = menu.querySelector('.select-btn');
                if (!btn) return;
                btn.classList.remove('status-open','status-close','status-ignore');
                // For 'ignore', visually show as open/close depending on storeOpen
                if (status === 'ignore') {
                    btn.classList.add(storeOpen ? 'status-open' : 'status-close');
                } else {
                    btn.classList.add('status-' + status);
                }

                // update texts based on runtime storeOpen
                const statusText = (function(){
                    if (status === 'open') return 'Loja aberta';
                    if (status === 'close') return 'Loja fechada';
                    // ignore: show according to storeOpen
                    return storeOpen ? 'Loja aberta' : 'Loja fechada';
                })();
                const subText = (function(){
                    if (status === 'ignore') return storeOpen ? '(Dentro do horário programado)' : '(Fora do horário programado)';
                    return '';
                })();

                const txt = menu.querySelector('.sBtn-text');
                const sub = menu.querySelector('.sBtn-subtext');
                if (txt) txt.textContent = statusText;
                if (sub) sub.textContent = subText;
                // replace svg icon
                try {
                    const icon = menu.querySelector('svg');
                    if (icon) {
                        if (status === 'open') icon.outerHTML = svgOpen;
                        else if (status === 'close') icon.outerHTML = svgClose;
                        else { // ignore: show according to storeOpen
                            icon.outerHTML = storeOpen ? svgOpen : svgClose;
                        }
                    }
                } catch(e) { console.error('SVG update error', e); }
            }

            selectBtn.addEventListener("click", () => {
                optionMenu.classList.toggle('active');
            });

            function markSelectedOption(status, menu) {
                // mark corresponding .option with class active
                const m = menu || optionMenu;
                m.querySelectorAll('.option').forEach(function(opt){
                    if (opt.getAttribute('data-status') === status) {
                        opt.classList.add('active');
                    } else {
                        opt.classList.remove('active');
                    }
                });
            }

            var adminPushToken = null;
            var pushUrl = '<?php echo esc_js( get_option( 'myd_push_server_url', '' ) ); ?>';

            options.forEach(option => {
                option.addEventListener("click", (e) => {
                    e.stopPropagation();
                    let selectedOptionStatus = option.getAttribute('data-status');
                    optionMenu.classList.remove("active");

                    const nonceInput = document.querySelector('#myd-store-status-form input[name="myd_store_status_box_nonce"]');
                    const nonce = nonceInput ? nonceInput.value : '';
                    const formData = new FormData();
                    formData.append('myd_store_status', selectedOptionStatus);
                    formData.append('myd_store_status_box_nonce', nonce);

                    // send update to WP (same as before) but don't reload; update UI immediately
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    }).then(function(response) {
                        if (response.ok) {
                            // optimistic UI update for this menu
                            applyStatusToUI(optionMenu, selectedOptionStatus, <?php echo $store_open ? 'true' : 'false'; ?>);
                            // mark selected option visually
                            try { markSelectedOption(selectedOptionStatus); } catch(e) { console.error(e); }
                            // also notify push server directly so sockets receive update immediately
                            try {
                                if (pushUrl) {
                                    // ensure we have a token
                                    function ensureToken(cb) {
                                        if (adminPushToken) return cb({ token: adminPushToken });
                                        fetch('<?php echo esc_url_raw( rest_url("myd-delivery/v1/push/auth") ); ?>', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({ myd_customer_id: 0 })
                                        }).then(r => r.json()).then(function(d){ adminPushToken = d.token; cb(d); }).catch(function(e){ console.error('Token fetch error', e); cb(null); });
                                    }

                                    ensureToken(function(tdata){
                                        if (!tdata || !tdata.token) return;
                                        fetch(pushUrl + '/notify/store', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + tdata.token },
                                            body: JSON.stringify({ open: selectedOptionStatus === 'open' })
                                        }).then(function(r){
                                            if (!r.ok) console.warn('Push notify/store failed');
                                        }).catch(function(err){ console.error('Error notifying push server', err); });
                                    });
                                }
                            } catch(err) { console.error(err); }
                        } else {
                            alert('Ocorreu um erro ao salvar o status.');
                        }
                    }).catch(function(error) {
                        console.error('Fetch error:', error);
                        alert('Ocorreu um erro de rede.');
                    });
                });
            });

            // Connect to push server to receive realtime store.status updates
            try {
                var pushUrl = '<?php echo esc_js( get_option( 'myd_push_server_url', '' ) ); ?>';
                if (pushUrl) {
                    function requestToken(cb) {
                        fetch('<?php echo esc_url_raw( rest_url("myd-delivery/v1/push/auth") ); ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ myd_customer_id: 0 })
                        }).then(r => r.json()).then(cb).catch(function(e){ console.error('Token fetch error', e); });
                    }
                    requestToken(function(data){
                        if (!data || !data.token) return;
                        var socket = io(pushUrl, { auth: { token: data.token } });
                        socket.on('connect', function(){ console.log('[Admin StoreStatus] connected to push server'); });
                        socket.on('store.status', function(payload){
                            console.log('[Admin StoreStatus] store.status', payload);
                            try {
                                // Fetch current authoritative state from WP and update UI accordingly
                                fetch('<?php echo esc_url_raw( rest_url("myd-delivery/v1/store/status") ); ?>', { method: 'GET', cache: 'no-store' })
                                .then(r => r.json()).then(function(data){
                                    if (!data) return;
                                    const force = data.force || 'ignore';
                                    const open = !!data.open;
                                    document.querySelectorAll('.select-menu').forEach(function(menu){
                                        if (force === 'ignore') {
                                            applyStatusToUI(menu, 'ignore', open);
                                            try { markSelectedOption('ignore', menu); } catch(e) { console.error(e); }
                                        } else {
                                            applyStatusToUI(menu, force, open);
                                            try { markSelectedOption(force, menu); } catch(e) { console.error(e); }
                                        }
                                    });
                                }).catch(function(e){ console.error('Error fetching store status', e); });
                            } catch(e) { console.error(e); }
                        });
                    });
                }
            } catch(e) { console.error(e); }

        }

        // If socket.io not present, load it dynamically and then init; otherwise init immediately
        if (typeof io === 'undefined') {
            var s = document.createElement('script');
            s.src = 'https://cdn.socket.io/4.7.2/socket.io.min.js';
            s.async = true;
            s.onload = function(){ try { initAdminStoreStatus(); } catch(e){ console.error(e); } };
            document.head.appendChild(s);
        } else {
            initAdminStoreStatus();
        }
    });
    </script>
        });
    </script>

    <div class="select-menu">
        <div class="select-btn <?php
            if ($current === 'ignore') {
                echo $store_open ? 'status-open' : 'status-close';
            } else {
                echo 'status-' . esc_attr($current);
            }
        ?>">
            <div style="display: flex; align-items: center;">
                <?php echo myd_get_status_icon_svg($current, $store_open); ?>
                <div>
                    <span class="sBtn-text"><?php echo esc_html($current_texts['text']); ?></span>
                    <span class="sBtn-subtext"><?php echo esc_html($current_texts['subtext']); ?></span>
                </div>
            </div>
            <i class="bx bx-chevron-down"></i>
        </div>
        <div class="options">
            <div class="option" data-status="ignore">
                <i class="bx bx-minus-circle" style="color: #333;"></i>
                <span class="option-text">Abrir nos horários definidos</span>
            </div>
            <div class="option" data-status="open">
                <i class="bx bx-check-circle" style="color: #28a745;"></i>
                <span class="option-text">Manter aberto</span>
            </div>
            <div class="option" data-status="close">
                <i class="bx bx-x-circle" style="color: #ea1d2b;"></i>
                <span class="option-text">Manter fechado</span>
            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
// });