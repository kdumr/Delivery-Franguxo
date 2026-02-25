<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user = wp_get_current_user();
if ( ! $current_user->exists() ) {
    return;
}

$user_id = $current_user->ID;
$user_name = $current_user->display_name ?: $current_user->user_login;
$first_name = explode( ' ', $user_name )[0];
$user_email = $current_user->user_email;
$user_phone = get_user_meta( $user_id, 'myd_customer_phone', true ) ?: get_user_meta( $user_id, 'phone', true ) ?: get_user_meta( $user_id, 'billing_phone', true );

// --- Lógica de Fidelidade (Copiada/Adaptada de template.php) ---
$myd_fidelidade_ativo = get_option( 'myd_fidelidade_ativo', 'off' );
$show_loyalty = ($myd_fidelidade_ativo === 'on');

if ( $show_loyalty ) {
    $myd_fidelidade_tipo = get_option( 'myd_fidelidade_tipo', 'loyalty_value' );
    $myd_fidelidade_valor = get_option( 'myd_fidelidade_valor', '' );
    $myd_fidelidade_quantidade = intval( get_option( 'myd_fidelidade_quantidade', 0 ) );
    $myd_fidelidade_premio_tipo = get_option( 'myd_fidelidade_premio_tipo', 'percent' );
    $myd_fidelidade_premio_percent = get_option( 'myd_fidelidade_premio_percent', '' );
    $myd_fidelidade_premio_fixo = get_option( 'myd_fidelidade_premio_fixo', '' );
    $myd_fidelidade_pontos_necessarios = intval( get_option( 'myd_fidelidade_pontos_necessarios', 0 ) );

    // Helpers
    if ( ! function_exists( 'myd_format_currency' ) ) {
        function myd_format_currency( $v ) {
            $v = str_replace( array( 'R$', ' ' ), '', $v );
            $v = str_replace( ',', '.', str_replace( '.', '', $v ) );
            $num = floatval( $v );
            return number_format( $num, 2, ',', '.' );
        }
    }

    $progress_percent = 0;
    $amount_left = 0;
    $orders_count = 0;
    $sum = 0.0;
    $points_count = 0;

    $last_reset = get_user_meta( $user_id, 'myd_loyalty_reset_at', true );
    $expires_at = get_user_meta( $user_id, 'myd_loyalty_expires_at', true );

    if ( $myd_fidelidade_tipo === 'loyalty_value' ) {
        // ... (Lógica simplificada: assumindo que os pontos/meta user meta já estão atualizados pelo hook principal)
        // Recalcular apenas para garantir display correto se não houver meta
        // Mas para performance no modal, vamos confiar no user meta 'myd_loyalty_points' se existir, ou reusar lógica se crítico.
        // Dado que o template.php faz query completa, vamos tentar simplificar lendo os metas que o sistema já deveria ter salvo.
        
        // Na implementação original do template.php, ele faz um loop de orders. 
        // Para manter consistência exata, idealmente copiamos o loop.
        $orders = get_posts( array(
            'post_type' => 'mydelivery-orders',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array('key' => 'myd_customer_id','value' => $user_id,'compare' => '='),
                array('key' => 'order_status','value' => 'draft','compare' => '!=')
            )
        ) );
        foreach ( $orders as $o ) {
            $order_status_meta = get_post_meta( $o->ID, 'order_status', true );
            if ( $order_status_meta === 'canceled' ) continue;
            if ( ! empty( $last_reset ) ) {
                $ot = strtotime( $o->post_date );
                $rt = strtotime( $last_reset );
                if ( $ot <= $rt ) continue;
            }
            $total = get_post_meta( $o->ID, 'order_subtotal', true );
            if ( empty( $total ) ) $total = get_post_meta( $o->ID, 'myd_order_subtotal', true );
            if ( empty( $total ) ) {
                $total_val = get_post_meta( $o->ID, 'order_total', true );
                if ( empty( $total_val ) ) $total_val = get_post_meta( $o->ID, 'myd_order_total', true );
                $delivery_val = get_post_meta( $o->ID, 'order_delivery_price', true );
                $delivery_num = floatval( str_replace( ',', '.', str_replace( '.', '', (string) $delivery_val ) ) );
                $total_num = floatval( str_replace( ',', '.', str_replace( '.', '', (string) $total_val ) ) );
                $total = number_format( max(0, $total_num - $delivery_num), 2, '.', '' );
            }
            $total = str_replace( array( 'R$', ' ' ), '', $total );
            $total = str_replace( ',', '.', str_replace( '.', '', $total ) );
            $sum += floatval( $total );
        }

        $target_raw = $myd_fidelidade_valor;
        $target = 0.0;
        if ( ! empty( $target_raw ) ) {
            $t = str_replace( array( 'R$', ' ' ), '', $target_raw );
            $t = str_replace( ',', '.', str_replace( '.', '', $t ) );
            $target = floatval( $t );
        }

        if ( $target > 0 ) {
            if ( $myd_fidelidade_pontos_necessarios > 0 ) {
               $points_count = intval( get_user_meta( $user_id, 'myd_loyalty_points', true ) );
               if ( ! empty( $expires_at ) ) {
                   $exp_ts = strtotime( $expires_at );
                   if ( $exp_ts !== false && $exp_ts <= (int) current_time( 'timestamp' ) ) {
                       $points_count = 0;
                   }
               }
               // Fallback calc se meta estiver zerado mas sum > 0 (opcional, mantendo lógica do template.php que confia no meta para pontos)
            } else {
               $progress_percent = min( 100, (int) round( ( $sum / $target ) * 100 ) );
               $amount_left = max( 0, $target - $sum );
            }
        }

    } else {
        $orders_count = intval( get_user_meta( $user_id, 'myd_loyalty_points', true ) );
    }
}
?>
<link rel="stylesheet" href="<?php echo esc_attr( MYD_PLUGN_URL . 'assets/css/myd-profile-modal.css' ); ?>" />
<div class="myd-profile-modal-bg d-none">
    <div class="myd-profile-modal" role="dialog" aria-modal="true" aria-label="Perfil do usuário">
        <button class="myd-profile-modal__close" id="close-profile-modal" aria-label="Fechar modal">
            <svg fill="currentColor" viewBox="-3.5 0 19 19" xmlns="http://www.w3.org/2000/svg" class="cf-icon-svg" aria-hidden="true"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M11.383 13.644A1.03 1.03 0 0 1 9.928 15.1L6 11.172 2.072 15.1a1.03 1.03 0 1 1-1.455-1.456l3.928-3.928L.617 5.79a1.03 1.03 0 1 1 1.455-1.456L6 8.261l3.928-3.928a1.03 1.03 0 0 1 1.455 1.456L7.455 9.716z"></path></g></svg>
        </button>
        <div class="myd-profile-modal__sidebar">
            <div class="myd-profile-modal__sidebar-inner">
                <div class="myd-profile-modal__greeting">Olá <?php echo esc_html( $first_name ); ?></div>
                <div class="myd-profile-modal__email"><?php echo esc_html( $user_email ); ?></div>
            </div>
            <div class="myd-profile-modal__menu">
                <button class="myd-profile-modal__menu-btn active" id="profile-menu-profile" data-target="tab-profile">
                    <svg height="24px" width="24px" version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#454545">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <g>
                            <path class="myd-profile-svg-fill" d="M458.159,404.216c-18.93-33.65-49.934-71.764-100.409-93.431c-28.868,20.196-63.938,32.087-101.745,32.087 c-37.828,0-72.898-11.89-101.767-32.087c-50.474,21.667-81.479,59.782-100.398,93.431C28.731,448.848,48.417,512,91.842,512 c43.426,0,164.164,0,164.164,0s120.726,0,164.153,0C463.583,512,483.269,448.848,458.159,404.216z"></path>
                            <path class="myd-profile-svg-fill" d="M256.005,300.641c74.144,0,134.231-60.108,134.231-134.242v-32.158C390.236,60.108,330.149,0,256.005,0 c-74.155,0-134.252,60.108-134.252,134.242V166.4C121.753,240.533,181.851,300.641,256.005,300.641z"></path>
                            </g>
                        </g>
                    </svg>
                    <span style="margin-top: 4px; font-size: 12px;">Minha conta</span>
                </button>
                <button class="myd-profile-modal__menu-btn" id="profile-menu-password" data-target="tab-password">
                    <svg height="24px" width="24px" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M16 5.5C16 8.53757 13.5376 11 10.5 11H7V13H5V15L4 16H0V12L5.16351 6.83649C5.0567 6.40863 5 5.96094 5 5.5C5 2.46243 7.46243 0 10.5 0C13.5376 0 16 2.46243 16 5.5ZM13 4C13 4.55228 12.5523 5 12 5C11.4477 5 11 4.55228 11 4C11 3.44772 11.4477 3 12 3C12.5523 3 13 3.44772 13 4Z" fill="#454545"></path>
                        </g>
                    </svg>
                    <span style="margin-top: 4px; font-size: 12px;">Senha</span>
                </button>
                <button class="myd-profile-modal__menu-btn" id="profile-menu-loyalty" data-target="tab-loyalty" style="display: flex; flex-direction: column; align-items: center;">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="24px" height="24px">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <rect width="24" height="24" fill="none"></rect>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.64793 4C6.68245 4 5.83818 4.04974 5.12309 4.20454C4.39827 4.36144 3.74647 4.63881 3.2275 5.13645C2.20341 6.11847 2.01269 7.65595 2.00052 9.47125C1.99664 10.0487 2.39359 10.5095 2.89068 10.6497C3.05598 10.6963 3.35401 10.8084 3.59563 11.0229C3.80658 11.2102 3.99993 11.4922 3.99993 12C3.99993 12.5078 3.80658 12.7898 3.59563 12.9771C3.35401 13.1916 3.05598 13.3037 2.89068 13.3503C2.39359 13.4905 1.99664 13.9513 2.00052 14.5288C2.01269 16.344 2.20341 17.8815 3.2275 18.8635C3.74647 19.3612 4.39827 19.6386 5.12309 19.7955C5.83818 19.9503 6.68245 20 7.64793 20H16.3519C17.3174 20 18.1617 19.9503 18.8768 19.7955C19.6016 19.6386 20.2534 19.3612 20.7724 18.8635C21.7965 17.8815 21.9872 16.344 21.9994 14.5288C22.0032 13.9513 21.6063 13.4905 21.1092 13.3503C20.9439 13.3037 20.6459 13.1916 20.4042 12.9771C20.1933 12.7898 19.9999 12.5078 19.9999 12C19.9999 11.4922 20.1933 11.2102 20.4042 11.0229C20.6459 10.8084 20.9439 10.6963 21.1092 10.6497C21.6063 10.5095 22.0032 10.0487 21.9994 9.47125C21.9872 7.65595 21.7965 6.11847 20.7724 5.13645C20.2534 4.63881 19.6016 4.36144 18.8768 4.20454C18.1617 4.04974 17.3174 4 16.3519 4H7.64793ZM15.9999 7C15.9999 6.44772 15.5522 6 14.9999 6C14.4476 6 13.9999 6.44772 13.9999 7V8C13.9999 8.55228 14.4476 9 14.9999 9C15.5522 9 15.9999 8.55228 15.9999 8V7ZM14.9999 15C15.5522 15 15.9999 15.4477 15.9999 16V17C15.9999 17.5523 15.5522 18 14.9999 18C14.4476 18 13.9999 17.5523 13.9999 17V16C13.9999 15.4477 14.4476 15 14.9999 15ZM15.9999 11C15.9999 10.4477 15.5522 10 14.9999 10C14.4476 10 13.9999 10.4477 13.9999 11V13C13.9999 13.5523 14.4476 14 14.9999 14C15.5522 14 15.9999 13.5523 15.9999 13V11Z" fill="#323232"></path> 
                        </g>
                    </svg>
                    <span style="margin-top: 4px; font-size: 12px;">Fidelidade</span>
                </button>
            </div>
            <button class="myd-profile-modal__logout" id="logout-profile-modal">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:1.1em; height:1.1em; vertical-align:middle;">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier"> 
                        <path d="M15 12L2 12M2 12L5.5 9M2 12L5.5 15" stroke="#bb0000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        <path d="M9.00195 7C9.01406 4.82497 9.11051 3.64706 9.87889 2.87868C10.7576 2 12.1718 2 15.0002 2L16.0002 2C18.8286 2 20.2429 2 21.1215 2.87868C22.0002 3.75736 22.0002 5.17157 22.0002 8L22.0002 16C22.0002 18.8284 22.0002 20.2426 21.1215 21.1213C20.3531 21.8897 19.1752 21.9862 17 21.9983M9.00195 17C9.01406 19.175 9.11051 20.3529 9.87889 21.1213C10.5202 21.7626 11.4467 21.9359 13 21.9827" stroke="#bb0000" stroke-width="1.5" stroke-linecap="round"></path>
                    </g>
                </svg>
                Sair
            </button>
        </div>
        <div class="myd-profile-modal__content" id="profile-modal-content">
            <!-- ABA PERFIL -->
            <div id="tab-profile" class="myd-profile-tab">
                <div class="myd-profile-modal__title">Minha conta</div>
                <form class="myd-profile-modal__form" id="profile-form">
                    <div>
                        <label class="myd-profile-modal__label" for="profile-fullname">Nome completo</label>
                        <input class="myd-profile-modal__input" id="profile-fullname" type="text" value="<?php echo esc_attr( $user_name ); ?>" autocomplete="name" oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s'-]/g,'')">
                    </div>
                    <div>
                        <label class="myd-profile-modal__label" for="profile-phone">Telefone</label>
                        <input class="myd-profile-modal__input" id="profile-phone" type="tel" value="<?php echo esc_attr( $user_phone ); ?>" autocomplete="tel" inputmode="numeric">
                    </div>
                    <button type="submit" class="myd-profile-modal__save-btn">Salvar Perfil</button>
                </form>
            </div>

            <!-- ABA SENHA -->
            <div id="tab-password" class="myd-profile-tab d-none">
                <div class="myd-profile-modal__title">Alterar senha</div>
                <form class="myd-profile-modal__form" id="password-form">
                    <div class="myd-input-wrapper">
                        <label class="myd-profile-modal__label" for="new-password">Nova senha</label>
                        <input class="myd-profile-modal__input myd-profile-modal__input--password" id="new-password" type="password" autocomplete="new-password" minlength="6">
                        <button type="button" tabindex="-1" class="myd-password-toggle" data-target="new-password">
                            <svg id="eye-new-password" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24"><path stroke="#888" stroke-width="2" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3" stroke="#888" stroke-width="2"/></svg>
                        </button>
                    </div>
                    <div class="myd-input-wrapper">
                        <label class="myd-profile-modal__label" for="confirm-password">Confirmar senha</label>
                        <input class="myd-profile-modal__input myd-profile-modal__input--password" id="confirm-password" type="password" autocomplete="new-password" minlength="6">
                        <button type="button" tabindex="-1" class="myd-password-toggle" data-target="confirm-password">
                            <svg id="eye-confirm-password" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24"><path stroke="#888" stroke-width="2" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3" stroke="#888" stroke-width="2"/></svg>
                        </button>
                    </div>
                    <div id="password-error-msg" class="myd-password-error-msg"></div>
                    <button type="submit" class="myd-profile-modal__save-btn">Salvar Senha</button>
                </form>
            </div>

            <!-- ABA FIDELIDADE -->
            <div id="tab-loyalty" class="myd-profile-tab d-none">
                <div class="myd-profile-modal__title">Fidelidade</div>
                <?php if ( $show_loyalty ) : ?>
                    <div class="myd-loyalty-wrapper">
                        <div class="myd-loyalty-bar">
                        <?php if ( $myd_fidelidade_pontos_necessarios > 0 ) : ?>
                            <!-- Modo Pontos -->
                            <div class="myd-loyalty-header" style="display:flex;justify-content:space-between;align-items:center;">
                                <div class="myd-loyalty-count" style="white-space:nowrap;font-size:14px;color:#666;"><?php echo $points_count; ?> de <?php echo $myd_fidelidade_pontos_necessarios; ?> pontos</div>
                            </div>
                            <div class="myd-loyalty-quantity-wrapper" style="display:flex;align-items:center;gap:12px;margin-top:12px;margin-bottom:20px;">
                                <div class="myd-loyalty-slots" style="flex:1;display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                    <?php for ( $i = 1; $i <= $myd_fidelidade_pontos_necessarios; $i++ ) : 
                                        $filled = ( $i <= $points_count ) ? ' myd-loyalty-slot--filled' : '';
                                    ?>
                                        <div class="myd-loyalty-slot<?php echo $filled; ?>" aria-hidden="true"></div>
                                    <?php endfor; ?>
                                </div>
                                <div class="myd-loyalty-gift-wrap" style="margin-left:auto;">
                                    <div class="myd-loyalty-gift<?php echo ($points_count >= $myd_fidelidade_pontos_necessarios) ? ' myd-loyalty-gift--filled' : ''; ?>" aria-hidden="true">🎁</div>
                                </div>
                            </div>
                            <?php if ( $points_count >= $myd_fidelidade_pontos_necessarios ) : ?>
                                <div style="margin-top:8px; margin-bottom:12px; color:#2c9b2c;"><strong>Parabéns! Você já pode resgatar o prêmio.</strong></div>
                            <?php endif; ?>

                        <?php else : ?>
                            <!-- Modo Valor (Barra de Progresso) -->
                            <?php $target_display = $myd_fidelidade_valor ? 'R$ ' . myd_format_currency( $myd_fidelidade_valor ) : ''; ?>
                            <div style="margin-bottom:5px;">Progresso: <strong>R$ <?php echo number_format( $sum, 2, ',', '.' ); ?></strong> de <strong><?php echo $target_display; ?></strong>.</div>
                            <div class="myd-loyalty-progress" style="background:#eee;border-radius:10px;height:12px;width:100%;margin-bottom:15px;overflow:hidden;">
                                <div class="myd-loyalty-progress__fill" style="background:#ffae00;height:100%;width:<?php echo esc_attr( $progress_percent ); ?>%"></div>
                            </div>
                            <?php if ( $amount_left <= 0 ) : ?>
                                <div style="margin-bottom:12px; color:#2c9b2c;"><strong>Parabéns! Você já atingiu o valor para receber o prêmio.</strong></div>
                            <?php else : ?>
                                <div style="margin-bottom:12px;">Faltam <strong>R$ <?php echo number_format( $amount_left, 2, ',', '.' ); ?></strong> para receber o prêmio.</div>
                            <?php endif; ?>
                        <?php endif; ?>
                        </div>
                        <!-- Descrição / Regras -->
                        <div class="myd-loyalty-desc" style="text-align: left; margin-top: 20px;">
                            <?php
                                if ( ! empty( $expires_at ) && $expires_at > current_time('timestamp') ) {
                                    $exp_date = date( get_option( 'date_format' ), strtotime( $expires_at ) );
                                    echo '<div style="margin-bottom:10px; color:#c00; font-size:0.9em;">Seus pontos expiram em <strong>' . $exp_date . '</strong>.</div>';
                                }
                            
                                $needed_display = ($myd_fidelidade_pontos_necessarios > 0) ? $myd_fidelidade_pontos_necessarios . ' pontos' : 'R$ ' . myd_format_currency($myd_fidelidade_valor) . ' em compras';
                                
                                $prize_display = ($myd_fidelidade_premio_tipo === 'percent') ? rtrim(trim($myd_fidelidade_premio_percent), '%') . '%' : 'R$ ' . myd_format_currency($myd_fidelidade_premio_fixo);
                            ?>
                            <p style="margin-bottom: 10px;">Acumule <strong><?php echo $needed_display; ?></strong> para ganhar <strong><?php echo $prize_display; ?></strong> de desconto.</p>
                            
                            <h4 style="margin-top:20px; font-size:1em; font-weight:bold; text-align: left;">Regras</h4>
                            <ul class="myd-loyalty-rules" style="text-align: left; padding-left: 20px; list-style-type: disc;">
                                <li>O vale compra é aplicado automaticamente no checkout.</li>
                                <li>Válido apenas para pedidos pelo site.</li>
                                <li>A taxa de entrega não contabiliza pontos.</li>
                                <?php 
                                    $f_exp = get_option( 'myd_fidelidade_expiracao', 'never' );
                                    $exp_text = ($f_exp === 'never') ? 'Nunca' : intval($f_exp) . ' dias';
                                ?>
                                <li>Os pontos expiram após: <?php echo $exp_text; ?>.</li>
                            </ul>
                        </div>
                    </div>
                <?php else : ?>
                    <div style="text-align:center; padding:30px; color:#888;">Nenhum programa de fidelidade ativo no momento.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
