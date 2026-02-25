// Torna o botão de perfil clicável e exibe o modal de login se não estiver logado
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('myd-profile-bar__button');
  if (!btn) return;
  console.debug && console.debug('[profile-bar] init - profile button found, initializing');

  // -- Helpers / modules -------------------------------------------------
  function getCurrentUserFromSession() {
    try {
      var stored = sessionStorage.getItem('mydCurrentUser');
      if (stored) return JSON.parse(stored);
    } catch (e) { /* ignore parse error */ }
    if (typeof mydCustomerAuth !== 'undefined' && mydCustomerAuth.current_user) return mydCustomerAuth.current_user;
    return null;
  }

  // (Removido ensureProfileCssLoaded pois agora é carregado via PHP/Link tag)

  function refreshOrdersBadge() {
    try {
      var url = '/wp-json/myd-delivery/v1/order/count';
      // console.debug('[badge] requesting order count from:', url);
      try {
        var parsed = getCurrentUserFromSession();
        if (parsed && parsed.id) url += '?myd_customer_id=' + encodeURIComponent(parsed.id);
      } catch (e) { }
      fetch(url, { method: 'GET', cache: 'no-store', credentials: 'same-origin' })
        .then(function (res) { return res.ok ? res.json() : { count: 0 }; })
        .then(function (data) {
          // console.debug('[badge] count response', data);
          var count = 0;
          if (data && typeof data === 'object') {
            if (typeof data.count !== 'undefined') count = parseInt(data.count, 10) || 0;
            else if (data.data && typeof data.data.count !== 'undefined') count = parseInt(data.data.count, 10) || 0;
            else if (Array.isArray(data)) count = data.length;
          }
          var badge = document.querySelector('.myd-profile-badge');
          if (!badge) {
            var ordersBtn = document.getElementById('myd-profile-bar__orders-button');
            if (ordersBtn) {
              badge = document.createElement('span');
              badge.className = 'myd-profile-badge';
              badge.setAttribute('aria-hidden', 'true');
              ordersBtn.appendChild(badge);
            }
          }
          if (badge) {
            if (count > 0) {
              var text = (count > 9) ? '9+' : String(count);
              badge.textContent = text;
              if (count > 9) badge.classList.add('myd-profile-badge--wide'); else badge.classList.remove('myd-profile-badge--wide');
              badge.style.display = 'inline-flex';
            } else {
              badge.style.display = 'none';
            }
          }
        })
        .catch(function () { var b = document.querySelector('.myd-profile-badge'); if (b) b.style.display = 'none'; });
    } catch (e) { console.error(e); }
  }

  // -- Orders modal (Mantendo lógica original de innerHTML para orders pois o pedido foi especificamente sobre profile-modal.
  // Se necessário, separaríamos isso também, mas o foco é myd-profile-modal)
  function openOrdersModal(e) {
    e && e.preventDefault();
    e && e.stopPropagation();
    console.debug && console.debug('[profile-bar] openOrdersModal');
    var user = getCurrentUserFromSession();
    var isLogged = (window.MYD_DATA && MYD_DATA.isLoggedIn);
    if (!isLogged || !user) { window.dispatchEvent(new Event('MydLoginRequired')); return; }

    // ensureProfileCssLoaded(); // Não necessário mais
    var ordersModalBg = document.createElement('div');
    ordersModalBg.className = 'myd-orders-modal-bg';
    ordersModalBg.innerHTML = `
      <div class="myd-orders-modal">
        <div class="myd-orders-modal__header">
          <h2 class="myd-orders-modal__title">Meus Pedidos</h2>
          <button class="myd-orders-modal__close" id="close-orders-modal">&times;</button>
        </div>
        <div class="myd-orders-modal__content">
          <div id="myd-orders-list" class="myd-orders-list">
            <div class="myd-orders-loading">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier">
                  <path d="M17.6566 12H21M3 12H6.34315M12 6.34342L12 3M12 21L12 17.6569" stroke="#363853" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  <path d="M16 8.00025L18.3642 5.63611M5.63629 18.364L8.00025 16M8.00022 8.00025L5.63608 5.63611M18.364 18.364L16 16" stroke="#ff8800" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </g>
              </svg>
              <div>Carregando pedidos...</div>
            </div>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(ordersModalBg);

    ordersModalBg.addEventListener('click', function (ev) {
      // If an order item was clicked, open its tracking URL (if available)
      try {
        var clickedItem = ev.target.closest && ev.target.closest('.myd-order-item');
        if (clickedItem) {
          var trackUrl = clickedItem.getAttribute('data-track-url');
          if (trackUrl) {
            // Prevent default anchor behavior and stop propagation so the page doesn't jump
            if (ev.preventDefault) ev.preventDefault();
            if (ev.stopPropagation) ev.stopPropagation();
            try {
              // Try to open a centered popup window; fallback to new tab if blocked
              var w = 900, h = 700;
              var left = (screen.width / 2) - (w / 2);
              var top = (screen.height / 2) - (h / 2);
              var opts = 'toolbar=0,location=0,status=0,menubar=0,scrollbars=1,resizable=1';
              opts += ',width=' + w + ',height=' + h + ',top=' + top + ',left=' + left;
              var win = window.open(trackUrl, '_blank', opts);
              if (!win) window.open(trackUrl, '_blank');
            } catch (e) { window.open(trackUrl, '_blank'); }
            return;
          }
        }
      } catch (e) { /* ignore */ }

      if ((ev.target.closest && ev.target.closest('#close-orders-modal')) || (ev.target.classList && ev.target.classList.contains('myd-orders-modal-bg'))) {
        document.body.removeChild(ordersModalBg);
      }
    });

    var userId = (user && user.id) ? user.id : null;
    if (typeof mydCustomerAuth !== 'undefined' && window.jQuery && typeof window.jQuery.ajax === 'function' && userId) {
      window.jQuery.ajax({
        url: mydCustomerAuth.ajax_url,
        type: 'POST',
        data: { action: 'myd_get_customer_orders', nonce: mydCustomerAuth.nonce },
        success: function (r) {
          var orders = (r && r.success && r.data && Array.isArray(r.data)) ? r.data : [];
          var filtered = orders.filter(function (order) { return String(order.myd_customer_id) === String(userId); });
          var ordersList = document.getElementById('myd-orders-list');
          if (filtered.length > 0) {
            ordersList.innerHTML = filtered.slice(0, 10).map(function (order, idx) {
              var productName = order.product_name || 'Pedido';
              var total = order.total || order.order_total || 0;
              if (typeof total === 'string') {
                var num = parseFloat(total.replace(',', '.'));
                if (!isNaN(num)) total = 'R$ ' + num.toFixed(2).replace('.', ',');
              } else if (typeof total === 'number') {
                total = 'R$ ' + total.toFixed(2).replace('.', ',');
              } else total = 'R$ 0,00';
              var statusText = order.status || 'Pendente';
              var statusClass = 'myd-order-status--pending';
              if (statusText === 'confirmed') { statusText = `<span id="order-status-anim-${order.id}-${idx}">Pedido sendo feito<span class="dots">...</span></span>`; }
              else if (statusText === 'finished') { statusText = 'Pedido finalizado<svg style="width:1em; height:1em; vertical-align:middle; margin-left:4px;" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="m16 0c8.836556 0 16 7.163444 16 16s-7.163444 16-16 16-16-7.163444-16-16 7.163444-16 16-16zm5.7279221 11-7.0710679 7.0710678-4.2426406-4.2426407-1.4142136 1.4142136 5.6568542 5.6568542 8.4852814-8.4852813z" fill="#2c9b2c" fill-rule="evenodd"></path></g></svg>'; statusClass = 'myd-order-status--completed'; }
              else if (statusText === 'in-delivery') { statusText = 'Em rota'; statusClass = 'myd-order-status--processing'; }
              else if (statusText === 'new') { statusText = 'Novo pedido'; }
              else if (statusText === 'waiting') { statusText = 'Aguardando'; }
              else if (statusText === 'canceled') { statusText = 'Cancelado<svg style="width:1em; height:1em; vertical-align:middle; margin-left:4px;" fill="#e30d0d" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" stroke="#e30d0d"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>cancel</title> <path d="M16 29c-7.18 0-13-5.82-13-13s5.82-13 13-13 13 5.82 13 13-5.82 13-13 13zM21.961 12.209c0.244-0.244 0.244-0.641 0-0.885l-1.328-1.327c-0.244-0.244-0.641-0.244-0.885 0l-3.761 3.761-3.761-3.761c-0.244-0.244-0.641-0.244-0.885 0l-1.328 1.327c-0.244 0.244-0.244 0.641 0 0.885l3.762 3.762-3.762 3.76c-0.244 0.244-0.244 0.641 0 0.885l1.328 1.328c0.244 0.244 0.641 0.244 0.885 0l3.761-3.762 3.761 3.762c0.244 0.244 0.641 0.244 0.885 0l1.328-1.328c0.244-0.244 0.244-0.641 0-0.885l-3.762-3.76 3.762-3.762z"></path> </g></svg>'; statusClass = 'myd-order-status--cancelled'; }
              var orderDate = (order.date || '').replace(/-/g, '/');
              return `
                  <div class="myd-order-item" data-track-url="${order.track_url || ''}">
                  <div class="myd-order-header">
                    <div class="myd-order-number">Pedido #${order.id}</div>
                    <div style="font-size:0.9em; color:#666;">${orderDate}</div>
                  </div>
                  <div class="myd-order-details">
                    <div class="myd-order-status ${statusClass}">${statusText}</div>
                    <div class="myd-order-date">${productName}</div>
                    <div class="myd-order-total">${total}</div>
                  </div>
                </div>
              `;
            }).join('');

            // Add animation for confirmed orders
            setTimeout(function () {
              filtered.slice(0, 10).forEach(function (order, idx) {
                if (order.status === 'confirmed') {
                  var el = document.getElementById('order-status-anim-' + order.id + '-' + idx);
                  if (el) {
                    var dots = el.querySelector('.dots');
                    if (dots) {
                      var i = 0;
                      setInterval(function () { i = (i + 1) % 4; dots.textContent = '.'.repeat(i ? i : 3); }, 500);
                    }
                  }
                }
              });
            }, 100);
          } else {
            ordersList.innerHTML = '<div style="text-align:center; padding:40px; color:#666;">Nenhum pedido encontrado.</div>';
          }
        },
        error: function () { document.getElementById('myd-orders-list').innerHTML = '<div style="text-align:center; padding:40px; color:#a00;">Erro ao carregar pedidos.</div>'; }
      });
    } else {
      document.getElementById('myd-orders-list').innerHTML = '<div style="text-align:center; padding:40px; color:#a00;">Erro: dados de usuário não disponíveis.</div>';
    }
  }

  // Attach orders button
  var ordersBtn = document.getElementById('myd-profile-bar__orders-button');
  if (ordersBtn) ordersBtn.addEventListener('click', openOrdersModal);

  // initial badge refresh shortly after load
  setTimeout(refreshOrdersBadge, 200);

  // -- Profile modal (REFACTORED to use PHP template) ---------------------

  // Cache do elemento modal (agora já existe no DOM se o user estiver logado)
  var profileModalBg = document.querySelector('.myd-profile-modal-bg');

  // Inicializa listeners do modal de perfil se ele existir
  if (profileModalBg) {
    // 1. Fechar modal clicando fora ou no botão X
    profileModalBg.addEventListener('click', function (e) {
      if (e.target === profileModalBg || (e.target.closest && e.target.closest('#close-profile-modal'))) {
        profileModalBg.style.display = 'none';
      }
    });

    // 2. Navegação do Menu (Abas)
    var menuBtns = profileModalBg.querySelectorAll('.myd-profile-modal__menu-btn');
    menuBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var targetId = btn.getAttribute('data-target');
        if (!targetId) return; // botão sem target (ex: fidelidade por enquanto)

        // Atualiza active nos botões
        menuBtns.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');

        // Alterna abas
        var tabs = profileModalBg.querySelectorAll('.myd-profile-tab');
        tabs.forEach(function (tab) { tab.style.display = 'none'; });

        var targetTab = document.getElementById(targetId);
        if (targetTab) targetTab.style.display = 'block';

        // Lógica para esconder o botão de sair no mobile se não estiver na aba "Minha conta"
        var logoutBtn = document.getElementById('logout-profile-modal');
        if (logoutBtn) {
          if (targetId === 'tab-profile') {
            logoutBtn.classList.remove('myd-profile-modal__logout--mobile-hidden');
          } else {
            logoutBtn.classList.add('myd-profile-modal__logout--mobile-hidden');
          }
        }
      });
    });

    // 3. Logout
    var logoutBtn = document.getElementById('logout-profile-modal');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', function () {
        sessionStorage.removeItem('mydCurrentUser');
        if (typeof mydCustomerAuth !== 'undefined') mydCustomerAuth.current_user = null;
        if (window.MYD_DATA && MYD_DATA.logoutUrl) window.location.href = MYD_DATA.logoutUrl; else window.location.href = '/wp-login.php?action=logout';
      });
    }

    // 4. Máscara de telefone
    var phoneInput = document.getElementById('profile-phone');
    if (phoneInput) {
      function maskPhoneInput(e) {
        var v = (e.target.value || '').replace(/\D/g, '').slice(0, 11);
        if (v.length > 6) e.target.value = '(' + v.slice(0, 2) + ') ' + v.slice(2, 7) + '-' + v.slice(7);
        else if (v.length > 2) e.target.value = '(' + v.slice(0, 2) + ') ' + v.slice(2);
        else if (v.length > 0) e.target.value = '(' + v; else e.target.value = '';
        e.target.dataset.raw = v;
      }
      phoneInput.addEventListener('input', maskPhoneInput);
      if (phoneInput.value) maskPhoneInput({ target: phoneInput });
    }

    // 5. Toggle password visibility
    var passToggles = profileModalBg.querySelectorAll('.myd-password-toggle');
    passToggles.forEach(function (toggle) {
      toggle.addEventListener('click', function () {
        var targetId = toggle.getAttribute('data-target');
        var input = document.getElementById(targetId);
        if (!input) return;
        if (input.type === 'password') {
          input.type = 'text';
          toggle.querySelector('svg').innerHTML = '<path stroke="#888" stroke-width="2" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3" stroke="#888" stroke-width="2" fill="#888"/><line x1="4" y1="20" x2="20" y2="4" stroke="#888" stroke-width="2"/>';
        } else {
          input.type = 'password';
          toggle.querySelector('svg').innerHTML = '<path stroke="#888" stroke-width="2" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3" stroke="#888" stroke-width="2"/>';
        }
      });
    });

    // 6. Form Profile Submit
    var profileForm = document.getElementById('profile-form');
    var profileCooldown = 0;
    if (profileForm) {
      profileForm.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var now = Date.now();
        if (profileCooldown && now < profileCooldown) {
          notifyUser('Muitas tentativas de atualização. Por favor, tente novamente mais tarde.', 'error');
          return;
        }
        var phoneEl = document.getElementById('profile-phone');
        var nameInput = document.getElementById('profile-fullname');

        // Captura valores atuais "seguros" (do cache/sessão) para restaurar em caso de erro/validação inválida
        var user = getCurrentUserFromSession() || {};
        var oldName = nameInput.value;
        var oldPhone = phoneEl.value;

        profileCooldown = now + 5000;
        var name = nameInput.value.trim();
        name = name.replace(/\s{2,}/g, ' ');

        // -- Helpers para restaurar valores
        function rollbackInputs() {
          if (nameInput) nameInput.value = oldName;
          if (phoneEl) {
            phoneEl.value = oldPhone;
            maskPhoneInput({ target: phoneEl });
          }
        }

        // Validations (Caracteres especiais permanecem no front para UX rápida)
        var nomeValido = /^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]+$/.test(name);
        if (!nomeValido) {
          notifyUser('O nome não pode conter caracteres especiais como @, . ou números.', 'error');
          rollbackInputs();
          nameInput.focus();
          return;
        }

        var phoneRaw = phoneEl.value.replace(/\D/g, '');

        if (typeof mydCustomerAuth !== 'undefined' && window.jQuery && typeof window.jQuery.ajax === 'function') {
          showProfileLoading();
          window.jQuery.ajax({
            url: mydCustomerAuth.ajax_url,
            type: 'POST',
            data: {
              action: 'myd_update_customer_profile',
              nonce: mydCustomerAuth.nonce,
              name: name,
              phone: phoneRaw
            },
            success: function (response) {
              hideProfileLoading();
              if (response && response.success && response.data && response.data.customer) {
                var c = response.data.customer;

                // Atualiza inputs com os dados oficiais do servidor
                if (nameInput) nameInput.value = c.name || '';
                if (phoneEl) {
                  phoneEl.value = c.phone || '';
                  maskPhoneInput({ target: phoneEl });
                }

                // Atualiza a barra do topo (se existir)
                var displayBarName = document.querySelector('.myd-profile-bar__name');
                if (displayBarName && c.name) {
                  displayBarName.textContent = c.name;
                }

                // --- SINCRONIZAÇÃO COM O CHECKOUT ---
                var checkoutName = document.getElementById('input-customer-name');
                if (checkoutName) checkoutName.value = c.name || '';
                var checkoutPhone = document.getElementById('input-customer-phone');
                if (checkoutPhone) {
                  checkoutPhone.value = c.phone || '';
                  if (typeof maskPhoneInput === 'function') maskPhoneInput({ target: checkoutPhone });
                }

                notifyUser('Perfil atualizado com sucesso!', 'success');

                // Atualiza objetos de sessão/cache
                if (mydCustomerAuth.current_user) {
                  mydCustomerAuth.current_user.name = c.name;
                  mydCustomerAuth.current_user.display_name = c.name;
                  mydCustomerAuth.current_user.phone = c.phone;
                  mydCustomerAuth.current_user.birthdate = c.birthdate;
                }
                user.name = c.name;
                user.display_name = c.name;
                user.phone = c.phone;
                sessionStorage.setItem('mydCurrentUser', JSON.stringify(mydCustomerAuth.current_user || user));
              } else {
                // Rollback com dados do servidor se disponíveis, senão usa local
                if (response && response.data && response.data.customer) {
                  var c = response.data.customer;
                  if (nameInput) nameInput.value = c.name || '';
                  if (phoneEl) {
                    phoneEl.value = c.phone || '';
                    maskPhoneInput({ target: phoneEl });
                  }
                } else {
                  if (nameInput) nameInput.value = oldName;
                  if (phoneEl) {
                    phoneEl.value = oldPhone;
                    maskPhoneInput({ target: phoneEl });
                  }
                }
                var msg = (response && response.data && response.data.message) || 'Erro ao atualizar perfil.';
                notifyUser(msg, 'error');
              }
            }, error: function () {
              hideProfileLoading();
              // Rollback em caso de erro de rede/servidor
              if (nameInput) nameInput.value = oldName;
              if (phoneEl) {
                phoneEl.value = oldPhone;
                maskPhoneInput({ target: phoneEl });
              }
              notifyUser('Erro ao atualizar perfil.', 'error');
            }
          });
        } else {
          // Simulation or offline
          user.name = name; user.display_name = name; user.phone = phoneRaw; sessionStorage.setItem('mydCurrentUser', JSON.stringify(user));
          notifyUser('Perfil salvo localmente (simulação).', 'success');
        }
      });
    }

    // 7. Form Password Submit
    var passForm = document.getElementById('password-form');
    if (passForm) {
      passForm.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var pass = document.getElementById('new-password').value.trim();
        var conf = document.getElementById('confirm-password').value.trim();
        var msg = document.getElementById('password-error-msg');
        msg.textContent = '';

        if (!pass || !conf) { notifyUser('Preencha os dois campos.', 'error'); return; }
        if (pass.length < 6) { notifyUser('A senha deve ter pelo menos 6 caracteres.', 'error'); return; }
        if (pass !== conf) { notifyUser('As senhas não conferem.', 'error'); return; }

        if (typeof mydCustomerAuth !== 'undefined' && window.jQuery && typeof window.jQuery.ajax === 'function') {
          var btnSubmit = passForm.querySelector('button[type="submit"]');
          showProfileLoading(); // reusing loader for consistency
          window.jQuery.ajax({
            url: mydCustomerAuth.ajax_url, type: 'POST', data: { action: 'myd_update_customer_password', nonce: mydCustomerAuth.nonce, password: pass },
            success: function (r) {
              hideProfileLoading();
              if (r && r.success) {
                if (btnSubmit) btnSubmit.disabled = true;
                notifyUser('Senha alterada com sucesso!', 'success');
                setTimeout(function () { location.reload(); }, 1200);
              } else {
                notifyUser((r && r.data && r.data.message) || 'Erro ao alterar senha.', 'error');
              }
            },
            error: function () {
              hideProfileLoading();
              notifyUser('Erro ao alterar senha.', 'error');
            }
          });
        } else {
          notifyUser('Erro: jQuery ou autenticação não disponível.', 'error');
        }
      });
    }
  }

  // Helper functions
  function notifyUser(text, type) {
    if (window.SimpleAuth && typeof SimpleAuth.showPopupNotification === 'function') {
      SimpleAuth.showPopupNotification(text, type || 'error');
    } else {
      alert(text);
    }
  }

  function showProfileLoading() {
    hideProfileLoading();
    let ol = document.createElement('div');
    ol.id = 'myd-map-loading-overlay';
    ol.style.position = 'absolute';
    ol.style.inset = '0';
    ol.style.display = 'flex';
    ol.style.alignItems = 'center';
    ol.style.justifyContent = 'center';
    ol.style.background = 'rgba(0,0,0,0.45)';
    ol.style.zIndex = '100001';
    ol.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;">' +
      '<svg width="64" height="64" viewBox="0 0 50 50" aria-hidden="true">' +
      '<circle cx="25" cy="25" r="20" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-dasharray="31.4 31.4">' +
      '<animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="1s" repeatCount="indefinite"/>' +
      '</circle>' +
      '</svg>' +
      '</div>';

    // Tenta usar o modal como container
    if (profileModalBg) {
      var modalContent = profileModalBg.querySelector('.myd-profile-modal');
      if (modalContent) {
        if (getComputedStyle(modalContent).position === 'static') modalContent.style.position = 'relative';
        modalContent.appendChild(ol);
        return;
      }
    }
    document.body.appendChild(ol);
  }

  function hideProfileLoading() {
    const ol = document.getElementById('myd-map-loading-overlay');
    if (ol && ol.parentNode) ol.parentNode.removeChild(ol);
  }

  // Função para abrir o modal
  function openProfileModal() {
    var user = getCurrentUserFromSession();
    var isLogged = (window.MYD_DATA && MYD_DATA.isLoggedIn);
    if (!isLogged || !user) { window.dispatchEvent(new Event('MydLoginRequired')); return; }

    console.debug && console.debug('[profile-bar] openProfileModal');

    if (profileModalBg) {
      profileModalBg.style.display = 'flex';
    } else {
      console.error('Modal de perfil não encontrado no DOM. Verifique se myd-profile-modal.php foi carregado.');
    }
  }

  btn.addEventListener('click', openProfileModal);

  // Delegated fallback para cliques (mantendo compatibilidade com original)
  document.body.addEventListener('click', function (ev) {
    try {
      if (!ev || !ev.target) return;
      var ordersBtnEl = ev.target.closest && ev.target.closest('#myd-profile-bar__orders-button');
      var profileBtnEl = ev.target.closest && ev.target.closest('#myd-profile-bar__button');

      if (ordersBtnEl) { openOrdersModal(ev); return; }
      if (profileBtnEl) {
        if (typeof btn !== 'undefined' && btn && profileBtnEl === btn) {
          return; // já tratado pelo listener direto
        }
        openProfileModal(ev);
        return;
      }
    } catch (e) { /* ignore */ }
  }, true);

  // -- Socket.IO para push em tempo real --------------------------------
  (function () {
    var socket;
    var pushUrl = MYD_DATA && MYD_DATA.pushUrl ? MYD_DATA.pushUrl : '';
    var mydCustomerId;
    function connectSocket() {
      try {
        var parsed = getCurrentUserFromSession();
        if (!parsed || !parsed.id) return;
        mydCustomerId = parsed.id;
        if (!pushUrl) return;

        // If socket.io library is not present yet, load it dynamically and retry
        if (typeof io === 'undefined') {
          if (!window.__myd_loading_socket_io) {
            window.__myd_loading_socket_io = true;
            var s = document.createElement('script');
            s.src = 'https://cdn.socket.io/4.7.2/socket.io.min.js';
            s.async = true;
            s.onload = function () { window.__myd_loading_socket_io = false; try { connectSocket(); } catch (e) { } };
            s.onerror = function () { window.__myd_loading_socket_io = false; setTimeout(connectSocket, 1000); };
            document.head.appendChild(s);
          } else {
            // retry shortly if loading is already in progress
            setTimeout(connectSocket, 500);
          }
          return;
        }

        fetch('/wp-json/myd-delivery/v1/push/auth', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ myd_customer_id: mydCustomerId }) })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data.token) {
              socket = io(pushUrl, { auth: { token: data.token } });
              socket.on('order.updated', function (d) { refreshOrdersBadge(); });
              socket.on('connect', function () { console.log('[Socket.IO] connected'); });
              socket.on('disconnect', function (reason) { console.log('[Socket.IO] disconnected', reason); });
              socket.on('connect_error', function (err) { console.error('[Socket.IO] connect_error', err && err.message); });
            } else console.error('[Socket.IO] token missing');
          })
          .catch(function (err) { console.error('[Socket.IO] token request failed', err); });
      } catch (e) { console.error('[Socket.IO] Error in connectSocket:', e); }
    }
    setTimeout(connectSocket, 1000);
  })();
});