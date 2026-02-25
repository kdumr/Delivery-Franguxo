<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<?php if ( $orders->have_posts() ) : ?>

	<?php
	// Agrupar pedidos do dia atual em seções
	$today = new DateTimeImmutable('now', wp_timezone());
	$sections = [
		'new' => [],            // Novos Pedidos
		'production' => [],     // Em produção (confirmed, waiting)
		'in_delivery' => [],    // Em entrega (in-delivery)
		'done' => []            // Concluídos (done, canceled)
	];

	while ( $orders->have_posts() ) : $orders->the_post();
		$postid = get_the_ID();
		$order_date_raw = get_post_meta( $postid, 'order_date', true );
		if ( empty( $order_date_raw ) ) continue;

		try {
			$od = new DateTimeImmutable( $order_date_raw, wp_timezone() );
		} catch ( Exception $e ) {
			// fallback: skip malformed dates
			continue;
		}

		// Mostrar apenas pedidos do mesmo dia (comparar Y-m-d)
		if ( $od->format('Y-m-d') !== $today->format('Y-m-d') ) continue;

		$order_status_key = get_post_meta( $postid, 'order_status', true );

		// Mapeamento de seção
		if ( $order_status_key === 'new' ) {
			$sections['new'][] = $postid;
		} elseif ( $order_status_key === 'in-delivery' ) {
			$sections['in_delivery'][] = $postid;
		} elseif ( in_array( $order_status_key, array( 'confirmed', 'waiting' ), true ) ) {
			$sections['production'][] = $postid;
		} else {
			// tratar como concluído (done, canceled, etc.)
			$sections['done'][] = $postid;
		}
	endwhile;
	wp_reset_postdata();

	// Helper: retorna timestamp numérico (segundos) indicando quando o order_status mudou.
	if ( ! function_exists( 'myd_get_order_status_changed_ts' ) ) {
		function myd_get_order_status_changed_ts( $postid ) {
			$postid = (int) $postid;
			if ( $postid <= 0 ) return 0;
			$ts = get_post_meta( $postid, 'order_status_changed_ts', true );
			if ( is_array( $ts ) ) $ts = isset( $ts[0] ) ? $ts[0] : null;
			if ( empty( $ts ) ) {
				// fallback: use order_date or post_date
				$order_date = get_post_meta( $postid, 'order_date', true );
				if ( ! empty( $order_date ) ) {
					$t = strtotime( $order_date );
					if ( $t && $t > 0 ) return (int) $t;
				}
				$p = get_post( $postid );
				if ( $p && ! empty( $p->post_date ) ) return (int) strtotime( $p->post_date );
				return 0;
			}
			return (int) $ts;
		}
	}

	// Ordenar os arrays de cada seção pelo timestamp de mudança de status (descendente — mais recente primeiro)
	foreach ( $sections as $k => $arr ) {
		if ( is_array( $arr ) && count( $arr ) > 1 ) {
			usort( $sections[ $k ], function( $a, $b ) {
				$ta = myd_get_order_status_changed_ts( $a );
				$tb = myd_get_order_status_changed_ts( $b );
				if ( $ta === $tb ) return 0;
				return ( $ta > $tb ) ? -1 : 1;
			} );
		}
	}

	// Helper para truncar nomes (multibyte-safe) e renderizar itens mantendo markup original
	if ( ! function_exists( 'myd_truncate_name' ) ) {
		function myd_truncate_name( $str, $max = 28 ) {
			if ( ! is_string( $str ) ) return '';
			$str = trim( $str );
			if ( $max <= 0 ) return '';
			if ( function_exists( 'mb_strlen' ) ) {
				if ( mb_strlen( $str ) <= $max ) return $str;
				$take = max( 0, $max - 3 );
				return mb_substr( $str, 0, $take ) . '...';
			} else {
				if ( strlen( $str ) <= $max ) return $str;
				$take = max( 0, $max - 3 );
				return substr( $str, 0, $take ) . '...';
			}
		}
	}

	// Helper para renderizar itens mantendo markup original
	function myd_render_order_item( $postid ) {
		$date = get_post_meta( $postid, 'order_date', true );

		// tentar parse robusto da data usando timezone do WP
		$order_ts = 0;
		$date_formatted = '';
		if ( ! empty( $date ) ) {
			try {
				$order_dt = new DateTimeImmutable( $date, wp_timezone() );
			} catch ( Exception $e ) {
				$order_dt = false;
			}

			// se não conseguiu, tentar alguns formatos comuns
			if ( ! $order_dt ) {
				$formats = array( 'd/m - H:i', 'd/m/Y - H:i', 'd/m/Y H:i', 'd/m H:i', 'H:i' );
				foreach ( $formats as $fmt ) {
					$tmp = DateTimeImmutable::createFromFormat( $fmt, $date, wp_timezone() );
					if ( $tmp instanceof DateTimeInterface ) { $order_dt = $tmp; break; }
				}
			}

			if ( $order_dt instanceof DateTimeInterface ) {
				$order_ts = (int) $order_dt->getTimestamp();
				$date_formatted = $order_dt->format( 'd/m - H:i' );
			} else {
				// fallback para strtotime; se falhar, usar agora
				$order_ts = (int) strtotime( $date );
				if ( $order_ts <= 0 ) $order_ts = time();
				$date_formatted = date( 'd/m - H:i', $order_ts );
			}
		} else {
			$order_ts = time();
			$date_formatted = date( 'd/m - H:i', $order_ts );
		}
		$order_status = get_post_meta( $postid, 'order_status', true );
		$original_status = $order_status;

			   // Não exibe mais o status textual no badge, apenas minutos

		?>
			<?php // order_ts already calculado acima ?>
			<div class="fdm-orders-items<?php echo ($original_status === 'new') ? ' fdm-new-unclicked' : ''; ?>" id="<?php echo esc_attr( $postid ); ?>" data-order-status="<?php echo esc_attr( $original_status ); ?>" data-order-ts="<?php echo esc_attr( $order_ts ); ?>">
			<div class="fdm-orders-items-left">
				<div class="fdm-order-list-items-order-number"># <?php echo get_the_title( $postid ); ?></div>
				<div class="fdm-order-list-items-date"><?php echo esc_html( $date_formatted ); ?></div>
				<div class="fdm-order-list-items-customer"><?php echo esc_html( myd_truncate_name( get_post_meta( $postid, 'order_customer_name', true ), 28 ) ); ?></div>
			</div>

			<div class="fdm-orders-items-right">
				<?php
				// calcular minutos desde a data do pedido (usar timestamp consistente)
				$now_ts = time();
				$diff = max(0, $now_ts - $order_ts);
				$minutes = (int) floor( $diff / 60 );

				// obter tempo médio de preparo configurado (em minutos)
				$avg_prep = (int) get_option( 'myd-average-preparation-time', 30 );
				if ( $avg_prep <= 0 ) $avg_prep = 30;
				// dividir em 3 partes iguais (inteiro)
				$part = (int) floor( $avg_prep / 3 );
				if ( $part < 1 ) $part = 1;

				// determinar cor do fundo baseado no tempo decorrido
				// verde: minutes <= part
				// laranja: minutes > part e minutes < avg_prep
				// vermelho: minutes >= avg_prep
				if ( $minutes >= $avg_prep ) {
					$time_background = '#ea1d2b'; // vermelho
				} elseif ( $minutes > $part ) {
					$time_background = '#d8800d'; // laranja
				} else {
					$time_background = '#2c9b2c'; // verde
				}
				?>
				<div class="fdm-order-list-items-status" style="background:<?php echo esc_attr( $time_background ); ?>;">
					<div class="myd-order-minutes"><?php echo intval( $minutes ); ?></div>
					<div class="myd-order-minutes-unit">min</div>
				</div>
			</div>
		</div>
		<?php
	}

	// Renderizar seções na ordem desejada, somente se houver itens
	// Pequeno CSS inline para títulos de seção (não altera arquivos globais)
	?>
	<?php
	// função helper para renderizar uma coluna kanban com título e lista de itens
	// sempre renderiza o container (mesmo vazio) para que updates via AJAX/WebSocket funcionem
	function myd_render_section_accordion( $section_id, $title, $items ) {
		$cnt = is_array( $items ) ? count( $items ) : 0;

		// Mapeamento de ícones SVG por seção para o empty state
		$icons = array(
			'myd-section-new' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
			'myd-section-production' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
			'myd-section-in-delivery' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 16V6C13 4.89543 12.1046 4 11 4H5C3.89543 4 3 4.89543 3 6V15C3 16.1046 3.89543 17 5 17H6M13 16H10M13 16L17 16M10 17C10 18.1046 9.10457 19 8 19C6.89543 19 6 18.1046 6 17M10 17C10 15.8954 9.10457 15 8 15C6.89543 15 6 15.8954 6 17M17 16C17 17.1046 17.8954 18 19 18C20.1046 18 21 17.1046 21 16C21 14.8954 20.1046 14 19 14H17L15 8H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
			'myd-section-done' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		);

		$empty_texts = array(
			'myd-section-new' => __( 'Aqui ficam os pedidos que estão aguardando confirmação', 'myd-delivery-pro' ),
			'myd-section-production' => __( 'Aqui ficam os pedidos que estão sendo preparados', 'myd-delivery-pro' ),
			'myd-section-in-delivery' => __( 'Aqui ficam os pedidos que estão indo para o cliente', 'myd-delivery-pro' ),
			'myd-section-done' => __( 'Aqui ficam os pedidos entregues e finalizados', 'myd-delivery-pro' ),
		);

		$icon_svg = isset( $icons[ $section_id ] ) ? $icons[ $section_id ] : '';
		$empty_text = isset( $empty_texts[ $section_id ] ) ? $empty_texts[ $section_id ] : '';
		?>
		<div class="myd-orders-section myd-orders-accordion" id="<?php echo esc_attr( $section_id ); ?>">
			<div class="myd-orders-accordion-header">
				<div class="myd-orders-section-title"><?php echo esc_html( $title ); ?></div>
				<div class="myd-orders-section-count"><?php echo intval( $cnt ); ?></div>
			</div>
			<div class="myd-orders-accordion-body">
				<?php if ( ! empty( $items ) ) {
					foreach ( $items as $pid ) { myd_render_order_item( $pid ); }
				} ?>
				<div class="myd-kanban-empty" style="<?php echo ! empty( $items ) ? 'display:none;' : ''; ?>">
					<?php echo $icon_svg; ?>
					<div class="myd-kanban-empty-text"><?php echo esc_html( $empty_text ); ?></div>
				</div>
			</div>
		</div>
		<?php
	}

	// renderizar accordions
	myd_render_section_accordion( 'myd-section-new', __( 'Novos Pedidos', 'myd-delivery-pro' ), $sections['new'] );
	myd_render_section_accordion( 'myd-section-production', __( 'Em produção', 'myd-delivery-pro' ), $sections['production'] );
	myd_render_section_accordion( 'myd-section-in-delivery', __( 'Em entrega', 'myd-delivery-pro' ), $sections['in_delivery'] );
	myd_render_section_accordion( 'myd-section-done', __( 'Concluídos', 'myd-delivery-pro' ), $sections['done'] );

	?>
		<?php
		// expose average preparation time to JS for live updates
		$__myd_avg_prep = (int) get_option( 'myd-average-preparation-time', 30 );
		if ( $__myd_avg_prep <= 0 ) $__myd_avg_prep = 30;
		?>
		<script>window.MYD_AVG_PREP = <?php echo $__myd_avg_prep; ?>;</script>
	<script>
	// Guard: ensure kanban column containers exist (in case other scripts modify the DOM)
	(function(){
		try{
			var ids = ['myd-section-new','myd-section-production','myd-section-in-delivery','myd-section-done'];
			var titles = {'myd-section-new':'Novos Pedidos','myd-section-production':'Em produção','myd-section-in-delivery':'Em entrega','myd-section-done':'Concluídos'};
			var emptyTexts = {'myd-section-new':'Aqui ficam os pedidos que estão aguardando confirmação','myd-section-production':'Aqui ficam os pedidos que estão sendo preparados','myd-section-in-delivery':'Aqui ficam os pedidos que estão indo para o cliente','myd-section-done':'Aqui ficam os pedidos entregues e finalizados'};
			ids.forEach(function(id){
				if (!document.getElementById(id)){
					var wrap = document.createElement('div'); wrap.id = id; wrap.className = 'myd-orders-section myd-orders-accordion';
					var title = titles[id] || '';
					var emptyText = emptyTexts[id] || '';
					wrap.innerHTML = '<div class="myd-orders-accordion-header"><div class="myd-orders-section-title">'+title+'</div><div class="myd-orders-section-count">0</div></div><div class="myd-orders-accordion-body"><div class="myd-kanban-empty"><div class="myd-kanban-empty-text">'+emptyText+'</div></div></div>';
					document.querySelector('.fdm-orders-loop') && document.querySelector('.fdm-orders-loop').appendChild(wrap);
				}
			});
		} catch(e){}
	})();
	</script>
	<script>
	// Atualiza badges de minutos periodicamente (cada 30s)
	(function(){
		function updateBadges(){
			var avg = parseInt(window.MYD_AVG_PREP || 30, 10);
			if (!avg || avg <= 0) avg = 30;
			var part = Math.floor(avg/3);
			if (part < 1) part = 1;
			var now = Math.floor(Date.now()/1000);
			var items = document.querySelectorAll('.fdm-orders-items[data-order-ts]');
			items.forEach(function(it){
				var ts = parseInt(it.getAttribute('data-order-ts') || '0', 10);
				if (!ts) return;
				var minutes = Math.floor(Math.max(0, now - ts)/60);
				var badge = it.querySelector('.fdm-order-list-items-status');
				if (!badge) return;
				var numEl = badge.querySelector('.myd-order-minutes');
				var unitEl = badge.querySelector('.myd-order-minutes-unit');
				if (numEl) numEl.textContent = String(minutes);
				if (unitEl) unitEl.textContent = 'min';
				// aplicar cor conforme partes
				if (minutes >= avg) {
					badge.style.background = '#ea1d2b';
				} else if (minutes > part) {
					badge.style.background = '#d8800d';
				} else {
					badge.style.background = '#2c9b2c';
				}
			});
		}
		// run first time now
		updateBadges();
		// run every 30 seconds
		setInterval(updateBadges, 30 * 1000);
	})();
	</script>
	<script>
	// Toggle empty state das colunas kanban quando itens são adicionados/removidos
	(function(){
		function updateKanbanEmpty(){
			var sections = document.querySelectorAll('.myd-orders-accordion');
			sections.forEach(function(sec){
				var body = sec.querySelector('.myd-orders-accordion-body');
				if (!body) return;
				var items = body.querySelectorAll('.fdm-orders-items');
				var empty = body.querySelector('.myd-kanban-empty');
				if (empty) {
					empty.style.display = items.length > 0 ? 'none' : '';
				}
			});
		}
		// Observar mudanças no DOM
		var loop = document.querySelector('.fdm-orders-loop');
		if (loop && window.MutationObserver) {
			var obs = new MutationObserver(function(){ setTimeout(updateKanbanEmpty, 50); });
			obs.observe(loop, { childList: true, subtree: true });
		}
		updateKanbanEmpty();
	})();
	</script>
	<style>
		/* === KANBAN BOARD COLUMNS === */
		.myd-orders-accordion {
			flex: 1;
			min-width: 0;
			display: flex;
			flex-direction: column;
			border-radius: 12px;
			overflow: hidden;
			background: #fff;
			border: 1px solid rgba(0,0,0,0.06);
		}
		.myd-orders-accordion-header {
			display: flex;
			align-items: center;
			justify-content: flex-start;
			cursor: default;
			background: #f7f7f8;
			border-bottom: 1px solid rgba(0,0,0,0.06);
			flex-shrink: 0;
		}
		.myd-orders-accordion-header:focus { outline: none; }
		.myd-orders-section-title { font-weight: 700; color: #333; font-size: 14px; }
		#myd-section-production .myd-orders-section-title { color: #2c2c2c; }
		.myd-orders-section-count {
			background: #e8e8e8;
			color: #222;
			padding: 2px 8px;
			border-radius: 12px;
			font-size: 12px;
			font-weight: 600;
			margin-left: 8px;
		}
		/* Esconder o toggle de caret no modo kanban */
		.myd-orders-accordion-toggle { display: none; }
		.myd-accordion-caret { display: none; }

		/* Corpo scrollável da coluna */
		.myd-orders-accordion-body {
			flex: 1;
			overflow-y: auto;
			overflow-x: hidden;
			padding: 10px;
			background: #f7f7f8;
		}
		.myd-orders-accordion-body .fdm-orders-items { padding: 12px 12px; }
		.myd-orders-accordion-body .fdm-orders-items:last-child { margin-bottom: 0; }

		/* Empty state para colunas kanban */
		.myd-kanban-empty {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 40px 20px;
			color: #b0b0b0;
			text-align: center;
			height: 100%;
		}
		.myd-kanban-empty svg {
			width: 48px;
			height: 48px;
			margin-bottom: 12px;
			opacity: 0.4;
		}
		.myd-kanban-empty-text {
			font-size: 13px;
			line-height: 1.5;
			color: #999;
		}

		/* Badge de minutos */
		.fdm-order-list-items-status { display:flex; flex-direction:column; align-items:center; justify-content:center; min-width:44px; height:44px; padding:4px 6px; border-radius:8px; color:#fff; }
		.fdm-order-list-items-status .myd-order-minutes { font-size:16px; font-weight:700; line-height:1; }
		.fdm-order-list-items-status .myd-order-minutes-unit { font-size:11px; opacity:0.95; margin-top:2px; }
		@media (max-width:480px){ .fdm-order-list-items-status{ min-width:36px; height:36px; } .fdm-order-list-items-status .myd-order-minutes{ font-size:14px; } }

		/* === MOBILE: empilhar colunas verticalmente === */
		@media (max-width: 768px) {
			.fdm-orders-loop {
				flex-direction: column !important;
				overflow-y: auto !important;
				overflow-x: hidden !important;
			}
			.myd-orders-accordion {
				flex: none;
				min-height: 200px;
				max-height: 50vh;
			}
		}

		/* Botão fechar overlay de detalhe */
		.myd-detail-close-btn {
			position: absolute;
			top: 12px;
			left: 12px;
			z-index: 10;
			width: 55px;
			height: 55px;
			border-radius: 50%;
			border: none;
			background: #f2f2f2;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: background 0.15s;
		}
		.myd-detail-close-btn:hover { background: #e0e0e0; }
		.myd-detail-close-btn svg { width: 18px; height: 18px; }
	</style>

<?php endif; ?>
