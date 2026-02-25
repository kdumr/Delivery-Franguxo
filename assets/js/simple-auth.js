/**
 * Versão simplificada do Sistema de Autenticação
 * Foca apenas no interceptor de checkout
 */
(function ($) {
    'use strict';

    // ...

    // Objeto simples apenas para interceptar checkout
    var SimpleAuth = {
        // Preenche o campo de nome do cliente no checkout, se possível
        fillCustomerNameInput: function () {
            if (SimpleAuth.currentUser && SimpleAuth.currentUser.name) {
                var $input = $('#input-customer-name');
                if ($input.length) {
                    $input.val(SimpleAuth.currentUser.name).prop('readonly', true);
                    // Adiciona listener para forçar o valor sempre que o campo for alterado
                    $input.off('input.simpleauth change.simpleauth').on('input.simpleauth change.simpleauth', function () {
                        if ($(this).val() !== SimpleAuth.currentUser.name) {
                            $(this).val(SimpleAuth.currentUser.name);
                        }
                    });
                }
            }
        },
        currentUser: null,

        ensureCssLoaded: function () {
            try {
                if (document.querySelector('link[data-simple-auth="login-css"]')) return;
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = '/assets/css/loginmodal.css';
                link.setAttribute('data-simple-auth', 'login-css');
                document.head.appendChild(link);
            } catch (e) { /* noop */ }
        },

        init: function () {
            // ...

            // Pega usuário atual do WordPress (se disponível)
            if (typeof mydCustomerAuth !== 'undefined' && mydCustomerAuth.current_user) {
                this.currentUser = mydCustomerAuth.current_user;
                // ...
            } else {
                // ...
                this.currentUser = null;
            }

            // Verifica se há sessão ativa no browser
            var storedUser = sessionStorage.getItem('mydCurrentUser');
            if (storedUser && !this.currentUser) {
                try {
                    this.currentUser = JSON.parse(storedUser);
                    // ...
                } catch (e) {
                    // ...
                    sessionStorage.removeItem('mydCurrentUser');
                }
            }

            this.setupInterceptors();

            // Sempre tenta preencher o campo de nome ao entrar no checkout (usuário já logado)
            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    $(mutation.addedNodes).find('#input-customer-name').each(function () {
                        SimpleAuth.fillCustomerNameInput();
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });


            // Sempre tenta preencher ao trocar de aba para o checkout
            $(document).on('click', '.myd-cart__nav-next', function () {
                setTimeout(function () {
                    // Se a aba de checkout ficou ativa, força o preenchimento
                    if ($('.myd-cart__checkout').hasClass('myd-cart__content--active')) {
                        SimpleAuth.fillCustomerNameInput();
                    }
                }, 300);
            });

            // Sempre tenta preencher ao ativar a aba de checkout por qualquer meio
            $(document).on('click', '[data-tab-content="myd-cart__checkout"]', function () {
                setTimeout(function () {
                    SimpleAuth.fillCustomerNameInput();
                }, 300);
            });

            // Também tenta preencher ao exibir o checkout (ex: ao abrir modal, etc)
            $(document).on('DOMSubtreeModified', '.myd-cart__checkout', function () {
                SimpleAuth.fillCustomerNameInput();
            });

            // E ao carregar a página, caso já esteja no checkout
            $(function () {
                setTimeout(function () {
                    SimpleAuth.fillCustomerNameInput();
                }, 800);
            });
        },

        // Show a global error in the visible step (register vs login)
        showGlobalError: function (message) {
            try {
                var $target = $('#step-register').is(':visible') ? $('#register-error-msg') : $('#login-error-msg');
                if (!$target || $target.length === 0) $target = $('#login-error-msg');
                $target.text(message).css('display', 'block').attr('role', 'alert').attr('aria-live', 'polite');
                // Scroll into view if necessary
                try { $target[0].scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) { }
            } catch (e) { console.warn('showGlobalError failed', e); }
        },

        hideGlobalError: function () {
            try {
                $('#login-error-msg').hide().text('');
                $('#register-error-msg').hide().text('');
            } catch (e) { }
        },

        setupInterceptors: function () {
            // Sempre exibe o modal ao receber o evento MydLoginRequired
            var self = this;
            window.addEventListener('MydLoginRequired', function (e) {
                if (!self.currentUser) {
                    // For login-required signals we want to force the modal open
                    // even if the internal user-interaction gate hasn't been set yet.
                    self.showSimpleModal(true);
                } else {
                    // no-op if already logged
                }

                // Ensure SimpleAuth knows whether the user has interacted with the page
                // (keep existing behaviour but store on SimpleAuth explicitly)
                SimpleAuth._userInteracted = false;
                ['click', 'keydown', 'touchstart'].forEach(function (evt) {
                    var onceUser = function () {
                        SimpleAuth._userInteracted = true;
                        ['click', 'keydown', 'touchstart'].forEach(function (e) { document.removeEventListener(e, onceUser); });
                    };
                    document.addEventListener(evt, onceUser, { once: true });
                });

            });
            // ...

            // Variável global para controlar acesso
            window.mydAuthBlocked = true;

            // INTERCEPTADOR 1: Eventos MyD
            window.addEventListener('MydCheckoutPlaceOrder', function (e) {
                // ...

                if (!self.currentUser) {
                    // ...
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    self.showSimpleModal();
                    return false;
                }
            }, true);

            window.addEventListener('MydCheckoutPlacePayment', function (e) {
                // ...

                if (!self.currentUser) {
                    // ...
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    self.showSimpleModal();
                    return false;
                }
            }, true);

            // INTERCEPTADOR 2: Bloqueia transição da tela de pedido para checkout se não estiver logado
            // Usa capture para garantir prioridade máxima no bloqueio
            document.addEventListener('click', function (e) {
                var target = e.target;
                if ($(target).hasClass('myd-cart__nav-next')) {
                    var navActive = $('.myd-cart__nav--active');
                    if (navActive.length && navActive.data('tab-content') === 'myd-cart__content') {
                        var next = navActive.data('next');
                        if (next === 'myd-cart__checkout') {
                            if (!self.currentUser) {
                                // ...
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();
                                self.showSimpleModal();
                                return false;
                            } else {
                                // Usuário logado, deixa seguir normalmente
                                // ...
                            }
                        }
                    }
                }
            }, true); // capture = true

            // INTERCEPTADOR 3: Submissão de formulários
            $(document).on('submit', 'form', function (e) {
                var form = $(this);
                var isCheckoutForm = (
                    form.attr('id') && (form.attr('id').toLowerCase().includes('checkout') || form.attr('id').toLowerCase().includes('payment') || form.attr('id').toLowerCase().includes('order')) ||
                    form.attr('class') && (form.attr('class').toLowerCase().includes('checkout') || form.attr('class').toLowerCase().includes('payment') || form.attr('class').toLowerCase().includes('order')) ||
                    form.find('input[type="submit"]').val() && (form.find('input[type="submit"]').val().toLowerCase().includes('pagar') || form.find('input[type="submit"]').val().toLowerCase().includes('finalizar'))
                );

                if (isCheckoutForm && !self.currentUser) {
                    // ...
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    self.showSimpleModal();
                    return false;
                }
            });

            // INTERCEPTADOR 4: Hook em funções globais do MyD
            var self = this;

            // Função para verificar autenticação antes de qualquer ação
            window.mydCheckAuth = function () {
                if (!self.currentUser) {
                    // ...
                    self.showSimpleModal();
                    return false;
                }
                return true;
            };

            // Override em funções do MyD se existirem
            setTimeout(function () {
                // Hook mydCheckout
                if (typeof window.mydCheckout !== 'undefined') {
                    var originalProcess = window.mydCheckout.processOrder || function () { };
                    window.mydCheckout.processOrder = function () {
                        if (!mydCheckAuth()) return false;
                        return originalProcess.apply(this, arguments);
                    };

                    var originalPlaceOrder = window.mydCheckout.placeOrder || function () { };
                    window.mydCheckout.placeOrder = function () {
                        if (!mydCheckAuth()) return false;
                        return originalPlaceOrder.apply(this, arguments);
                    };
                }

                // Hook Myd global
                if (typeof window.Myd !== 'undefined') {
                    var originalNewEvent = window.Myd.newEvent || function () { };
                    window.Myd.newEvent = function (eventName, data) {
                        if (eventName && (eventName.includes('Checkout') || eventName.includes('Payment') || eventName.includes('Order'))) {
                            if (!mydCheckAuth()) return false;
                        }
                        return originalNewEvent.apply(this, arguments);
                    };
                }

                // Hook funções globais comuns de checkout
                var checkoutFunctions = ['processCheckout', 'submitOrder', 'placeOrder', 'finalizeOrder', 'goToPayment', 'proceedToPayment'];
                checkoutFunctions.forEach(function (funcName) {
                    if (typeof window[funcName] === 'function') {
                        var originalFunc = window[funcName];
                        window[funcName] = function () {
                            if (!mydCheckAuth()) return false;
                            return originalFunc.apply(this, arguments);
                        };
                    }
                });
            }, 1000);

            // INTERCEPTADOR 5: MutationObserver para detectar mudanças de página/estado
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (mutation.type === 'childList') {
                            // Verifica se elementos de pagamento foram adicionados
                            $(mutation.addedNodes).find('[id*="payment"], [class*="payment"], [id*="checkout"], [class*="checkout"]').each(function () {
                                if (!self.currentUser) {
                                    // ...
                                    self.showSimpleModal();
                                }
                            });
                        }
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            // INTERCEPTADOR 6: Monitoramento de mudanças de URL/hash
            var currentUrl = window.location.href;
            setInterval(function () {
                if (window.location.href !== currentUrl) {
                    currentUrl = window.location.href;

                    // Verifica se URL contém termos relacionados a pagamento
                    if (currentUrl.toLowerCase().includes('payment') ||
                        currentUrl.toLowerCase().includes('checkout') ||
                        currentUrl.toLowerCase().includes('pagamento') ||
                        currentUrl.toLowerCase().includes('finalizar')) {

                        if (!self.currentUser) {
                            // ...
                            self.showSimpleModal();

                            // Tenta voltar para página anterior
                            setTimeout(function () {
                                if (history.length > 1) {
                                    history.back();
                                } else {
                                    window.location.href = '/';
                                }
                            }, 100);
                        }
                    }
                }
            }, 500);



            // INTERCEPTADOR 8: AJAX requests relacionados a pagamento
            if (typeof $ !== 'undefined') {
                var originalAjax = $.ajax;
                $.ajax = function (urlOrOptions, maybeOptions) {
                    try {
                        var settings = (typeof urlOrOptions === 'object') ? (urlOrOptions || {}) : (maybeOptions || {});
                        if (typeof urlOrOptions === 'string') settings.url = urlOrOptions;

                        var lowerUrl = (settings.url ? settings.url.toString() : '').toLowerCase();
                        var method = (settings.type || settings.method || 'GET').toString().toUpperCase();
                        var dataStr = '';
                        if (settings && settings.data) {
                            try {
                                // Best effort normalization of data payload
                                if (typeof settings.data === 'string') {
                                    dataStr = settings.data;
                                } else if (window.URLSearchParams && settings.data instanceof URLSearchParams) {
                                    dataStr = settings.data.toString();
                                } else {
                                    dataStr = JSON.stringify(settings.data);
                                }
                            } catch (e) { /* noop */ }
                        }
                        var lowerData = dataStr.toLowerCase();

                        // Allow WordPress REST and admin-ajax calls to pass through unmodified
                        if (lowerUrl.includes('/wp-json/') || lowerUrl.includes('admin-ajax.php')) {
                            return originalAjax.apply(this, arguments);
                        }

                        // Only block clearly checkout/payment-related operations
                        var isSensitive = (
                            lowerUrl.includes('/checkout') || lowerUrl.includes('checkout') ||
                            lowerUrl.includes('payment') ||
                            lowerData.includes('checkout') || lowerData.includes('payment') ||
                            lowerData.includes('finalizar') || lowerData.includes('pagar')
                        );

                        // Do NOT broadly block "order" endpoints, many safe APIs include that term
                        if (isSensitive && method !== 'GET' && !self.currentUser) {
                            self.showSimpleModal();
                            // Return a rejected Deferred promise to preserve jqXHR-like interface
                            var d = $.Deferred();
                            d.reject({ status: 401, statusText: 'Unauthorized' });
                            return d.promise();
                        }
                    } catch (e) {
                        // If any parsing error occurs, fall back to original Ajax
                    }
                    return originalAjax.apply(this, arguments);
                };
            }

            // INTERCEPTADOR 9: Fetch API
            if (typeof fetch !== 'undefined') {
                var originalFetch = window.fetch;
                window.fetch = function (input, init) {
                    var url = typeof input === 'string' ? input : input.url;

                    if (url) {
                        var lower = url.toString().toLowerCase();
                        // Allow REST API and admin-ajax calls to pass through (they may include 'order')
                        if (lower.includes('/wp-json/') || lower.includes('admin-ajax.php')) {
                            return originalFetch.apply(this, arguments);
                        }

                        // Only block client-side calls that are clearly checkout/payment related
                        if (lower.includes('payment') || lower.includes('checkout') || lower.includes('/checkout')) {
                            if (!self.currentUser) {
                                self.showSimpleModal();
                                return Promise.reject(new Error('Authentication required'));
                            }
                        }
                        // For "order" keyword: be conservative — many API endpoints include "order" (eg. order count)
                        // so we avoid blocking generic "order" requests here. If you need to block specific order
                        // endpoints, match their full path explicitly.
                    }

                    return originalFetch.apply(this, arguments);
                };
            }

            // ...
        },

        showSimpleModal: function (force) {
            // Prevent re-entrance: if modal already open or loading, don't start another
            if (this._modalLoading || this._modalOpen) {
                return;
            }
            // If page hasn't received user interaction yet and caller didn't force, don't auto-open
            if (!force && !this._userInteracted) {
                return;
            }
            this._modalLoading = true;
            // Exibe loading global enquanto o modal via REST estiver sendo carregado
            try { this.showLoading(); } catch (e) { /* noop */ }

            // Remove modal anterior se existir
            $('#simple-auth-modal').remove();

            // Carrega modal via REST (partial PHP)
            var restUrl = '/wp-json/myd-delivery/v1/simple-auth/loginmodal';
            try {
                fetch(restUrl, { method: 'GET', credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data && data.html) {

                            $('body').append(data.html);
                            // Oculta o spinner de carregamento pois o modal já foi injetado
                            try { SimpleAuth.hideLoading(); } catch (e) { /* noop */ }
                            // mark as open
                            SimpleAuth._modalLoading = false;
                            SimpleAuth._modalOpen = true;

                            // Inicializações que dependem do DOM injetado:
                            setTimeout(function () {
                                // Format registration CPF field
                                var $cpf = $('#user-cpf');
                                if ($cpf.length) {
                                    $cpf.on('input', function (e) {
                                        var v = $(this).val();
                                        var digits = v.replace(/\D/g, '').slice(0, 11);
                                        var formatted = digits;
                                        if (digits.length > 9) {
                                            formatted = digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                                        } else if (digits.length > 6) {
                                            formatted = digits.replace(/(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
                                        } else if (digits.length > 3) {
                                            formatted = digits.replace(/(\d{3})(\d{0,3})/, '$1.$2');
                                        }
                                        $(this).val(formatted);
                                    });

                                    // Ao colar, força formatação
                                    $cpf.on('paste', function (e) {
                                        setTimeout(function () { $cpf.trigger('input'); }, 50);
                                    });
                                }

                                // Format initial CPF input (kept id #user-email for compatibility)
                                var $initialCpf = $('#user-email');
                                if ($initialCpf.length) {
                                    $initialCpf.on('input', function (e) {
                                        var $el = $(this);
                                        var loginMode = $('#step-email').attr('data-login-mode') || 'cpf';
                                        var v = $el.val();

                                        if (loginMode === 'cpf') {
                                            var digits = v.replace(/\D/g, '').slice(0, 11);
                                            var formatted = digits;
                                            if (digits.length > 9) {
                                                formatted = digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                                            } else if (digits.length > 6) {
                                                formatted = digits.replace(/(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
                                            } else if (digits.length > 3) {
                                                formatted = digits.replace(/(\d{3})(\d{0,3})/, '$1.$2');
                                            }
                                            $el.val(formatted);
                                        } else {
                                            // Phone mask
                                            var digits = v.replace(/\D/g, '').slice(0, 11);
                                            var formatted = digits;
                                            if (digits.length > 0) {
                                                formatted = '(' + digits.substring(0, 2);
                                                if (digits.length > 2) {
                                                    formatted += ') ' + digits.substring(2, 7);
                                                }
                                                if (digits.length > 7) {
                                                    formatted += '-' + digits.substring(7);
                                                }
                                            }
                                            $el.val(formatted);
                                        }
                                    });
                                    $initialCpf.on('paste', function (e) { setTimeout(function () { $initialCpf.trigger('input'); }, 50); });
                                }

                                // Antes de enviar registro, garante que o CPF será enviado apenas com dígitos
                                $(document).on('click', 'button[onclick=\"validateAndRegister()\"], button:contains(\"Criar Conta\")', function () {
                                    var $c = $('#user-cpf');
                                    if ($c.length) {
                                        $c.val(($c.val() || '').toString().replace(/\D/g, ''));
                                    }
                                });
                            }, 30);

                            // Efeito hover para o label 'Esqueci a senha'
                            $(document).on('mouseenter', '#forgot-pass-label', function () { $(this).css('opacity', '0.65'); });
                            $(document).on('mouseleave', '#forgot-pass-label', function () { $(this).css('opacity', '1'); });

                            // Foca no campo de email
                            setTimeout(function () {
                                $('#user-email').focus();
                            }, 100);
                        }
                    })
                    .catch(function (e) {
                        // Fallback: se falhar, tenta construir inline (antigo comportamento)
                        console.warn('Failed to load modal via REST, fallback to inline.');
                        try { SimpleAuth.hideLoading(); } catch (err) { /* noop */ }
                        SimpleAuth._modalLoading = false;
                    });
            } catch (e) {
                console.warn('Fetch not available, modal not loaded via REST.');
                try { this.hideLoading(); } catch (err) { /* noop */ }
                this._modalLoading = false;
            }

            // ...
        },

        closeModal: function () {
            // ...
            $('#simple-auth-modal').remove();
            this._modalOpen = false;
            this._modalLoading = false;
        },

        checkEmail: function () {
            // Guard: prevent multiple parallel submissions
            var $continue = $('.simple-auth-btn-primary');
            if ($continue.data('simpleauth-pending')) return;

            var identifier = $('#user-email').val().trim();
            // Get current login mode from the new toggle
            var loginMode = $('#step-email').attr('data-login-mode') || 'cpf';
            var digits = (identifier || '').toString().replace(/\D/g, '');

            if (loginMode === 'cpf') {
                if (!digits || digits.length !== 11) {
                    try { $('#email-check-error').text('Por favor, digite um CPF válido (11 dígitos)').show(); } catch (e) { }
                    try { $('#user-email').focus(); } catch (e) { }
                    return;
                }
            } else if (loginMode === 'phone') {
                if (!digits || (digits.length !== 10 && digits.length !== 11)) {
                    try { $('#email-check-error').text('Por favor, digite um telefone válido com DDD').show(); } catch (e) { }
                    try { $('#user-email').focus(); } catch (e) { }
                    return;
                }
            }

            // clear any previous error message
            $('#email-check-error').hide().text('');
            // Ensure we start on the email step and do not allow premature UI changes
            try { $('#step-login, #step-register').hide(); $('#step-email').show(); } catch (e) { }
            // Reset last checked email - will be set only after a successful response
            try { SimpleAuth._lastEmailChecked = null; } catch (e) { }
            // Disable continue button to avoid duplicate clicks
            $continue.prop('disabled', true).data('simpleauth-pending', true).addClass('simple-auth-btn-disabled').attr('aria-busy', 'true');
            this.showLoading();

            // Verifica se identificador (email ou cpf) existe via AJAX
            if (typeof mydCustomerAuth !== 'undefined') {
                $.ajax({
                    url: mydCustomerAuth.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'myd_check_email_exists',
                        nonce: mydCustomerAuth.nonce,
                        identifier: digits
                    },
                    //Timeout to detect hung requests quickly
                    timeout: 8000,
                    success: function (response) {
                        console.debug('[SimpleAuth] checkEmail success response:', response);
                        // ...
                        // Hide any previous error
                        $('#email-check-error').hide().text('');

                        if (response && response.success) {
                            var exists = response.data && response.data.exists;
                            // If server returned an email for this CPF, use it as display value
                            var displayVal = (response.data && response.data.email) ? response.data.email : digits;
                            try { SimpleAuth._lastEmailChecked = displayVal; } catch (e) { }
                            if (exists) {
                                // identifier existe - preparar login mostrando o email vinculado
                                SimpleAuth.prepareLoginStep(displayVal);
                            } else {
                                // não existe - preparar registro (passa o cpf digits para preencher o CPF)
                                SimpleAuth.prepareRegisterStep(digits);
                            }
                        } else {
                            // Response invalid but request succeeded - show error and keep on email step
                            $('#email-check-error').text((response && response.data && response.data.message) ? response.data.message : 'Não foi possível verificar o e-mail. Tente novamente.').show();
                            SimpleAuth.hideLoading();
                            try { SimpleAuth._lastEmailChecked = null; } catch (e) { }
                            // Ensure we remain on the email step (defensive)
                            try { SimpleAuth.backToEmail(); } catch (e) { /* noop */ }
                            // Re-enable continue button
                            $continue.prop('disabled', false).data('simpleauth-pending', false).removeClass('simple-auth-btn-disabled').attr('aria-busy', 'false');
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.warn('[SimpleAuth] checkEmail AJAX error:', textStatus, errorThrown, jqXHR);
                        // Network or server error: present a clear 'Sem conexão' message for network issues
                        var isNetworkError = (textStatus === 'timeout' || textStatus === 'error' && (!jqXHR || jqXHR.status === 0));
                        var msg = 'Erro de conexão. Verifique sua internet e tente novamente.';
                        try {
                            if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                                msg = jqXHR.responseJSON.data.message;
                            }
                        } catch (e) { }

                        if (isNetworkError) {
                            msg = 'Não foi possível realizar o login, verifique sua conexão e tente novamente.';
                        }

                        $('#email-check-error').text(msg).show();
                        SimpleAuth.hideLoading();
                        try { SimpleAuth._lastEmailChecked = null; } catch (e) { }
                        try { SimpleAuth.backToEmail(); } catch (e) { /* noop */ }
                        $continue.prop('disabled', false).data('simpleauth-pending', false).removeClass('simple-auth-btn-disabled').attr('aria-busy', 'false');
                    }
                });
            } else {
                // Ambiente sem backend ou não foi possível iniciar a chamada AJAX.
                // Removemos o fallback de debug: em caso de falha, mostrar mensagem clara ao usuário.
                $('#email-check-error').text('Não foi possível realizar o login, verifique sua conexão e tente novamente.').show();
                SimpleAuth.hideLoading();
                try { SimpleAuth._lastEmailChecked = null; } catch (e) { }
                try { SimpleAuth.backToEmail(); } catch (e) { }
                $continue.prop('disabled', false).data('simpleauth-pending', false).removeClass('simple-auth-btn-disabled').attr('aria-busy', 'false');
            }
        },

        showLoading: function () {
            // Create a page backdrop (behind modal) and a spinner on top of everything.
            // Backdrop darkens the page behind the modal; spinner appears centered in viewport above modal.

            // Remove existing artifacts
            $('.simple-auth-page-backdrop').remove();
            $('.simple-auth-loading-spinner').remove();

            // Backdrop appended to body, z-index slightly below modal (modal uses 99999)
            var $backdrop = $('<div class="simple-auth-page-backdrop" aria-hidden="true"></div>');
            // Spinner appended to body, z-index above modal
            var $spinner = $('<div class="simple-auth-loading-spinner" aria-hidden="true"></div>');

            // SVG spinner
            var svg = '<svg viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="none" class="hds-flight-icon--animation-loading">' +
                '<g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier">' +
                '<g fill="#ffae00" fill-rule="evenodd" clip-rule="evenodd">' +
                '<path d="M8 1.5a6.5 6.5 0 100 13 6.5 6.5 0 000-13zM0 8a8 8 0 1116 0A8 8 0 010 8z" opacity=".2"></path>' +
                '<path d="M7.25.75A.75.75 0 018 0a8 8 0 018 8 .75.75 0 01-1.5 0A6.5 6.5 0 008 1.5a.75.75 0 01-.75-.75z"></path>' +
                '</g></g></svg>';

            $spinner.html('<div class="simple-auth-loading-inner">' + svg + '</div>');

            $('body').append($backdrop).append($spinner);

            // Ensure CSS file is loaded
            try { if (typeof this.ensureCssLoaded === 'function') this.ensureCssLoaded(); } catch (e) { }

            $backdrop.fadeIn(120);
            $spinner.fadeIn(120);
        },

        hideLoading: function () {
            $('.simple-auth-loading-spinner').fadeOut(120, function () { $(this).remove(); });
            $('.simple-auth-page-backdrop').fadeOut(120, function () { $(this).remove(); });
        },

        showLoginStep: function (email, bypassGuard) {
            // Guard: only allow advancing from email step when the email was recently checked
            try {
                // Require that a successful check recorded the last checked email equal to `email`.
                if (!bypassGuard && email && SimpleAuth._lastEmailChecked !== email) {
                    // Do not advance if we don't have a matching last-checked email
                    return;
                }
            } catch (e) { }
            // Esconde o passo de email e mostra apenas o login
            SimpleAuth.hideLoading();
            // restore per-step titles: show email title, hide register title
            $('#simple-auth-modal .simple-auth-title-email').show();
            $('#simple-auth-modal .simple-auth-title-register').hide();
            // garante que somente o passo de login esteja visível
            $('#step-email').hide();
            $('#step-register').hide();
            $('#step-login').show();
            $('#step-email').attr('aria-hidden', 'true');
            $('#step-login').attr('aria-hidden', 'false');
            // Preenche apenas o rótulo/label com o email vinculado (não sobrescreve o campo CPF)
            if (email) {
                $('#login-email-value').text(email);
                $('#login-email-row').show();
            }
            $('#user-password').focus();
        },

        showRegisterStep: function (email, bypassGuard) {
            // Guard: only allow advancing from email step when the email was recently checked
            try {
                // Require that a successful check recorded the last checked email equal to `email`.
                if (!bypassGuard && email && SimpleAuth._lastEmailChecked !== email) {
                    return;
                }
            } catch (e) { }
            // Esconde o passo de email e mostra apenas o registro
            SimpleAuth.hideLoading();
            // show register title and hide email title
            $('#simple-auth-modal .simple-auth-title-email').hide();
            $('#simple-auth-modal .simple-auth-title-register').show();
            $('#step-email').hide();
            $('#step-login').hide();
            $('#step-register').show();
            if (email) {
                // If the provided value looks like an email, prefill the visible email field
                if (this.isValidEmail(email)) {
                    $('#user-email-register').val(email);
                } else {
                    var digits = email.toString().replace(/\D/g, '');
                    var loginMode = $('#step-email').attr('data-login-mode') || 'cpf';

                    if (loginMode === 'phone') {
                        var formatted = digits;
                        if (digits.length > 0) {
                            formatted = '(' + digits.substring(0, 2);
                            if (digits.length > 2) {
                                formatted += ') ' + digits.substring(2, 7);
                            }
                            if (digits.length > 7) {
                                formatted += '-' + digits.substring(7);
                            }
                        }
                        $('#user-phone').val(formatted);
                    } else if (digits.length === 11) {
                        $('#user-cpf').val(digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4'));
                    } else {
                        $('#user-email-register').val(email);
                    }
                }
            }
            $('#user-name').focus();
        },

        backToEmail: function () {
            // ...
            $('#step-login, #step-register').hide();
            // show email title and hide register title when going back
            $('#simple-auth-modal .simple-auth-title-email').show();
            $('#simple-auth-modal .simple-auth-title-register').hide();
            $('#step-code').remove(); // Remove o input de código se existir
            $('#step-email').show();
            // limpa mensagens de erro e label
            $('#login-error-msg').hide().text('');
            $('#login-email-value').text('');
            $('#login-email-row').hide();
            $('#user-email').focus();
        },

        doLogin: function () {
            var identifier = $('#user-email').val().trim();
            var password = $('#user-password').val().trim();

            // ...

            if (!password) {
                $('#login-error-msg').text('Por favor, digite sua senha').show();
                $('#user-password').focus();
                return;
            }

            this.showLoading();

            if (typeof mydCustomerAuth !== 'undefined') {
                // Ensure we send CPF digits only to the backend
                var digits = (identifier || '').toString().replace(/\D/g, '');
                $.ajax({
                    url: mydCustomerAuth.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'myd_customer_login',
                        nonce: mydCustomerAuth.nonce,
                        identifier: digits,
                        password: password
                    },
                    success: function (response) {
                        // ...

                        if (response.success) {
                            SimpleAuth.currentUser = response.data.customer;
                            SimpleAuth.onAuthSuccess('Login realizado!');
                        } else {
                            // Ao erro, garantimos que o passo de login esteja ativo e o de email oculto
                            SimpleAuth.hideLoading();
                            $('#step-email').hide();
                            $('#step-register').hide();
                            $('#step-login').show();
                            $('#step-email').attr('aria-hidden', 'true');
                            $('#step-login').attr('aria-hidden', 'false');
                            $('#login-error-msg').text(response.data.message || 'Senha incorreta').show();
                            // Mantém o foco na senha para tentar novamente
                            $('#user-password').focus();
                        }
                    },
                    error: function () {
                        SimpleAuth.hideLoading();
                        $('#step-email').hide();
                        $('#step-register').hide();
                        $('#step-login').show();
                        $('#login-error-msg').text('Erro no login. Tente novamente.').show();
                        $('#user-password').focus();
                    }
                });
            } else {
                // Simula login para teste
                setTimeout(function () {
                    SimpleAuth.currentUser = { id: 1, name: 'Usuário Teste', email: email };
                    SimpleAuth.onAuthSuccess('Login simulado!');
                }, 1000);
            }
        },

        doRegister: function () {
            var email = $('#user-email-register').length ? $('#user-email-register').val().trim() : $('#user-email').val().trim();
            var name = $('#user-name').val().trim();
            // Validação de nome: apenas letras, espaços, acentos e hífen
            var nomeValido = /^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]+$/.test(name);
            if (!nomeValido) {
                SimpleAuth.showPopupNotification('O nome não pode conter caracteres especiais como @, . ou números.', 'error');
                $('#user-name').focus();
                return;
            }
            var nameSemEspacos = name.replace(/\s/g, '');
            if (nameSemEspacos.length < 4) {
                SimpleAuth.showPopupNotification('O nome deve ter pelo menos 4 letras (sem contar espaços).', 'error');
                $('#user-name').focus();
                return;
            }
            // Valida nome completo: precisa ter pelo menos um espaço (nome + sobrenome)
            if (name.indexOf(' ') === -1) {
                $('#user-name-error').text('Digite seu nome completo. Exemplo: Carlos Eduardo').css('display', 'block');
                $('#user-name').focus();
                return;
            } else {
                $('#user-name-error').hide().text('');
            }
            var phone = $('#user-phone').val().trim();
            var birthdate = $('#user-birthdate').val().trim();
            var password = $('#user-new-password').val().trim();
            var passwordConfirm = $('#user-new-password-confirm').val() ? $('#user-new-password-confirm').val().trim() : '';
            var cpf = $('#user-cpf').val().trim();

            // ...

            // Limpa mensagens de erro
            $('#user-cpf-error').hide().text('');
            $('#user-phone-error').hide().text('');
            $('#user-name-error').hide().text('');
            this.hideGlobalError();
            $('#register-password-error').hide().text('');
            $('#user-email-register-error').hide().text('');

            // Valida email básico
            if (!email) {
                $('#user-email-register-error').text('Por favor, informe o email.').show();
                return;
            }
            var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRe.test(email)) {
                $('#user-email-register-error').text('Informe um email válido.').show();
                return;
            }

            if (!name || !phone || !password) {
                this.showGlobalError('Por favor, preencha todos os campos obrigatórios.');
                return;
            }

            // Verifica confirmação de senha
            if (password !== passwordConfirm) {
                $('#register-password-error').text('As senhas não conferem.').css('display', 'block');
                $('#user-new-password-confirm').focus();
                return;
            }

            // Validação completa de CPF (apenas se preenchido)
            if (cpf && !this.isCpfValid(cpf)) {
                $('#user-cpf-error').text('CPF inválido.').css('display', 'block');
                return;
            }

            // Birthdate is optional but if provided, basic validation
            if (birthdate) {
                // HTML date input provides yyyy-mm-dd; ensure it's plausible
                var parts = birthdate.split('-');
                if (parts.length !== 3 || parts[0].length !== 4) {
                    alert('Por favor, informe uma data de nascimento válida.');
                    return;
                }
            }

            if (password.length < 6) {
                alert('A senha deve ter pelo menos 6 caracteres');
                return;
            }
            // Validação do telefone: exige exatamente 10 ou 11 dígitos numéricos
            try {
                var phoneRaw = (phone || '').toString().replace(/\D/g, '');
                if (!phoneRaw || phoneRaw.length < 10 || phoneRaw.length > 11) {
                    // Mostra erro abaixo do campo de telefone e foca o campo
                    try { $('#user-phone-error').text('Digite um número válido DDD+Número. Exemplo: (22) 99999-9999').css('display', 'block'); } catch (e) { }
                    try { $('#user-phone').focus(); $('#user-phone')[0] && $('#user-phone')[0].select && $('#user-phone')[0].select(); } catch (e) { }
                    return;
                }
            } catch (e) { }

            this.showLoading();

            if (typeof mydCustomerAuth !== 'undefined') {
                $.ajax({
                    url: mydCustomerAuth.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'myd_customer_register',
                        nonce: mydCustomerAuth.nonce,
                        email: email,
                        name: name,
                        phone: phone,
                        cpf: cpf,
                        birthdate: birthdate,
                        password: password
                    },
                    success: function (response) {
                        // ...
                        if (response.success) {
                            SimpleAuth.currentUser = response.data.customer;
                            SimpleAuth.onAuthSuccess('Conta criada!');
                        } else {
                            SimpleAuth.hideLoading();
                            // Mensagem do servidor (pode ser email/telefone/cpf)
                            var msg = (response && response.data && response.data.message) ? response.data.message : '';
                            if (msg) {
                                var lower = msg.toLowerCase();
                                if (lower.indexOf('email') !== -1 && lower.indexOf('já está cadastrado') !== -1) {
                                    SimpleAuth.showLoginStep(email);
                                    SimpleAuth.showGlobalError('Esse email já possui conta. Faça login.');
                                } else if (lower.indexOf('cpf') !== -1 && lower.indexOf('já está cadastrado') !== -1) {
                                    $('#user-cpf-error').text('Este CPF já está cadastrado.').css('display', 'block');
                                    $('#step-register').show();
                                } else if (lower.indexOf('telefone') !== -1) {
                                    // Mostrar erro específico do telefone abaixo do campo
                                    try { $('#user-phone-error').text(msg).css('display', 'block'); } catch (e) { }
                                    $('#step-register').show();
                                } else {
                                    $('#step-register').show();
                                    alert(msg || 'Erro ao criar conta');
                                }
                            } else {
                                $('#step-register').show();
                                alert('Erro ao criar conta');
                            }
                        }
                    },
                    error: function () {
                        SimpleAuth.hideLoading();
                        $('#step-register').show();
                        alert('Erro ao criar conta. Tente novamente.');
                    }
                });
            } else {
                // Simula registro para teste
                setTimeout(function () {
                    SimpleAuth.currentUser = { id: 2, name: name, email: email };
                    SimpleAuth.onAuthSuccess('Conta criada!');
                }, 1000);
            }
        },

        onAuthSuccess: function (message) {
            // ...

            // Salva usuário na sessão
            if (this.currentUser) {
                sessionStorage.setItem('mydCurrentUser', JSON.stringify(this.currentUser));
                // ...
                // Atualiza objeto global do sistema, se existir
                if (typeof mydStoreInfo !== 'undefined' && mydStoreInfo.auth) {
                    mydStoreInfo.auth.isLoggedIn = true;
                    mydStoreInfo.auth.user = this.currentUser;
                    // ...
                }
                if (typeof mydCustomerAuth !== 'undefined') {
                    mydCustomerAuth.current_user = this.currentUser;
                    // ...
                }
            }

            // Mostra mensagem de sucesso usando showSuccessScreen
            this.showSuccessScreen(message);

            // Troca a tela manualmente: remove active de products, adiciona em checkout
            setTimeout(function () {
                $('.myd-cart__products').removeClass('myd-cart__content--active');
                $('.myd-cart__checkout').addClass('myd-cart__content--active');

                // Tenta preencher o campo de nome várias vezes após a transição
                var tries = 0;
                var maxTries = 10;
                function fillNameField() {
                    var $input = $('#input-customer-name');
                    if ($input.length && SimpleAuth.currentUser && SimpleAuth.currentUser.name) {
                        $input.val(SimpleAuth.currentUser.name).prop('readonly', true);
                        return;
                    }
                    tries++;
                    if (tries < maxTries) setTimeout(fillNameField, 300);
                }
                fillNameField();

                SimpleAuth.closeModal();
                SimpleAuth.proceedWithCheckout();
            }, 2000);
        },

        proceedWithCheckout: function () {
            // ...

            // Marca que agora está autenticado
            window.mydAuthBlocked = false;

            // Aguarda um pouco para o sistema processar o login
            setTimeout(function () {
                // ...

                // Método 1: Força clique no último botão de checkout/pagamento que foi clicado
                var lastClickedButton = $('.last-checkout-attempt');
                if (lastClickedButton.length > 0) {
                    // ...
                    lastClickedButton.removeClass('last-checkout-attempt').trigger('click');
                    return;
                }

                // Método 2: Procura botões de checkout/pagamento visíveis
                var checkoutButtons = $(
                    'button:visible[id*="checkout"], button:visible[id*="payment"], button:visible[id*="pagar"], button:visible[id*="finalizar"], ' +
                    'button:visible[class*="checkout"], button:visible[class*="payment"], ' +
                    'input:visible[type="submit"][value*="pagar"], input:visible[type="submit"][value*="checkout"], input:visible[type="submit"][value*="finalizar"], ' +
                    'a:visible[href*="checkout"], a:visible[href*="payment"]'
                ).filter(function () {
                    var text = $(this).text().toLowerCase();
                    var value = $(this).val() ? $(this).val().toLowerCase() : '';
                    return text.includes('pagar') || text.includes('finalizar') || text.includes('checkout') ||
                        value.includes('pagar') || value.includes('finalizar') || value.includes('checkout');
                });

                if (checkoutButtons.length > 0) {
                    // ...
                    checkoutButtons.first().trigger('click');
                    return;
                }

                // Método 3: Submete formulário de checkout ativo
                var checkoutForms = $('form:visible').filter(function () {
                    var id = $(this).attr('id') || '';
                    var className = $(this).attr('class') || '';
                    return id.toLowerCase().includes('checkout') || id.toLowerCase().includes('payment') ||
                        className.toLowerCase().includes('checkout') || className.toLowerCase().includes('payment');
                });

                if (checkoutForms.length > 0) {
                    // ...
                    checkoutForms.first().submit();
                    return;
                }

                // Método 4: Dispara eventos MyD se disponível
                if (window.Myd && window.Myd.newEvent) {
                    // ...
                    window.Myd.newEvent('MydCheckoutPlaceOrder', {});
                    return;
                }

                // Método 5: Chama funções globais de checkout
                var checkoutFunctions = ['processCheckout', 'submitOrder', 'placeOrder', 'finalizeOrder', 'proceedToPayment', 'goToCheckout'];
                for (var i = 0; i < checkoutFunctions.length; i++) {
                    if (typeof window[checkoutFunctions[i]] === 'function') {
                        // ...
                        try {
                            window[checkoutFunctions[i]]();
                            return;
                        } catch (e) {
                            console.log('[AUTH-SIMPLE] Erro ao chamar função:', e);
                        }
                    }
                }

                // Método 6: Força navegação para página de checkout se existir
                var checkoutUrls = ['/checkout', '/pagamento', '/finalizar', '?step=checkout', '?step=payment'];
                for (var j = 0; j < checkoutUrls.length; j++) {
                    if (document.querySelector('a[href*="' + checkoutUrls[j] + '"]')) {
                        // ...
                        window.location.href = checkoutUrls[j];
                        return;
                    }
                }

                // ...

                // Método 7: Recarrega a página para tentar continuar o processo
                setTimeout(function () {
                    window.location.reload();
                }, 1000);

            }, 800);
        },

        isValidEmail: function (email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        // Validação completa de CPF (dígitos verificadores)
        isCpfValid: function (cpf) {
            if (!cpf) return false;
            var s = cpf.replace(/\D/g, '');
            if (s.length !== 11) return false;
            if (/^(\d)\1{10}$/.test(s)) return false; // sequências repetidas

            var calc = function (t) {
                var sum = 0;
                for (var i = 0; i < t; i++) {
                    sum += parseInt(s.charAt(i), 10) * ((t + 1) - i);
                }
                var mod = (sum * 10) % 11;
                return (mod === 10) ? 0 : mod;
            };

            var d1 = calc(9);
            if (d1 !== parseInt(s.charAt(9), 10)) return false;
            var d2 = calc(10);
            if (d2 !== parseInt(s.charAt(10), 10)) return false;
            return true;
        },

        // Mostra mensagem na área de notificação global (`#myd-popup-notification`)
        showPopupNotification: function (message, type) {
            try {
                var tpl = document.getElementById('myd-popup-notification');
                var msg = document.getElementById('myd-popup-notification__message');
                if (!tpl || !msg) return;
                msg.innerHTML = message;
                if (type === 'success') tpl.style.background = '#35a575';
                else if (type === 'error') tpl.style.background = '#cb2027';
                else tpl.style.background = '';
                tpl.style.opacity = '1'; tpl.style.visibility = 'visible';
                clearTimeout(tpl.__myd_popup_timeout);
                tpl.__myd_popup_timeout = setTimeout(function () {
                    try { tpl.style.opacity = ''; tpl.style.visibility = ''; msg.innerHTML = ''; tpl.style.background = ''; } catch (e) { }
                }, 4000);
            } catch (e) { }
        },

        forgotPassword: function () {
            var identifier = $('#user-email').val().trim();
            if (!identifier) {
                alert('Digite o email ou CPF usado no cadastro para recuperar a senha.');
                $('#user-email').focus();
                return;
            }
            var isEmail = this.isValidEmail(identifier);
            var isCpf = this.isCpfValid(identifier);
            if (!isEmail && !isCpf) {
                alert('Digite o email ou CPF usado no cadastro para recuperar a senha.');
                $('#user-email').focus();
                return;
            }

            var self = this;
            this.showLoading();
            $('#step-login, #step-email, #step-register, #step-code').hide();

            var data = { action: 'myd_forgot_password' };
            if (isEmail) {
                data.email = identifier;
            } else {
                // envia apenas dígitos do CPF
                data.identifier = identifier.replace(/\D/g, '');
            }

            $.ajax({
                url: (typeof mydCustomerAuth !== 'undefined' && mydCustomerAuth.ajax_url) ? mydCustomerAuth.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: data,
                success: function (response) {
                    SimpleAuth.hideLoading();
                    if (response.success) {
                        // backend pode retornar o email associado ao CPF
                        var emailForRecovery = (response.data && response.data.email) ? response.data.email : (isEmail ? identifier : null);
                        if (!emailForRecovery) {
                            // fallback: mostrar mensagem e voltar ao login
                            self.showPopupNotification(response.data && response.data.message ? response.data.message : 'Enviamos um código de recuperação para seu e-mail.');
                            $('#step-login').show();
                            return;
                        }
                        // Mostra etapa de código usando o email real
                        self.showCodeStep(emailForRecovery);
                    } else {
                        $('#step-login').show();
                        alert(response.data && response.data.message ? response.data.message : 'Erro ao enviar recuperação.');
                    }
                },
                error: function () {
                    SimpleAuth.hideLoading();
                    $('#step-login').show();
                    alert('Erro ao enviar recuperação.');
                }
            });
        },

        showCodeStep: function (email) {
            if ($('#step-code').length === 0) {
                var codeHtml = `
                                <div id="step-code" style="display: none;">
                                        <p style="color: #2196F3; margin-bottom: 15px;">
                                                Um código de 6 dígitos foi enviado para <b>${email}</b>.<br>Digite abaixo para continuar:
                                        </p>
                                        <form id="formCodigo" autocomplete="off" style="margin-bottom: 10px;">
                                            <div class="code-wrap" id="codeWrap">
                                                <input id="box-1" class="code-box" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Dígito 1 de 6">
                                                <input id="box-2" class="code-box" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Dígito 2 de 6">
                                                <input id="box-3" class="code-box" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Dígito 3 de 6">
                                                <span class="dash">-</span>
                                                <input id="box-4" class="code-box" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Dígito 4 de 6">
                                                <input id="box-5" class="code-box" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Dígito 5 de 6">
                                                <input id="box-6" class="code-box" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" autocomplete="one-time-code" aria-label="Dígito 6 de 6">
                                            </div>
                                            <input type="hidden" name="codigo" id="codigoValue">
                                        </form>
                                        <div id="code-error-msg" style="color:#d32f2f; min-height:22px; font-size:14px; text-align:center; margin-top:8px; margin-bottom:18px;"></div>
                                        <button id="btnValidarCodigo" style="width: 100%; background: #4CAF50; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-size: 16px; margin-bottom: 10px;">Validar código</button>
                                </div>`;
                $('#simple-auth-modal .simple-auth-content-wrapper').append(codeHtml);
                // JS para navegação e colagem
                setTimeout(function () {
                    var wrap = document.getElementById('codeWrap');
                    var boxes = Array.from(wrap.querySelectorAll('.code-box'));
                    var hidden = document.getElementById('codigoValue');
                    function onlyDigit(char) { return /\d/.test(char); }
                    function updateHidden() {
                        hidden.value = boxes.map(b => b.value || '').join('');
                    }
                    boxes.forEach((box, idx) => {
                        box.addEventListener('input', (e) => {
                            let v = e.target.value.replace(/\D/g, '');
                            if (v.length > 1) v = v.slice(-1);
                            e.target.value = v;
                            if (v && idx < boxes.length - 1) {
                                boxes[idx + 1].focus();
                                boxes[idx + 1].select?.();
                            }
                            updateHidden();
                            document.getElementById('code-error-msg').textContent = '';
                        });
                        box.addEventListener('keydown', (e) => {
                            const { key } = e;
                            if (key === 'Backspace' && !box.value && idx > 0) {
                                boxes[idx - 1].focus();
                                boxes[idx - 1].value = '';
                                e.preventDefault();
                                updateHidden();
                            }
                            if (key === 'ArrowLeft' && idx > 0) {
                                boxes[idx - 1].focus(); e.preventDefault();
                            }
                            if (key === 'ArrowRight' && idx < boxes.length - 1) {
                                boxes[idx + 1].focus(); e.preventDefault();
                            }
                        });
                        box.addEventListener('paste', (e) => {
                            const text = (e.clipboardData || window.clipboardData).getData('text') || '';
                            const digits = text.replace(/\D/g, '').slice(0, boxes.length);
                            if (digits.length > 1) {
                                e.preventDefault();
                                digits.split('').forEach((d, i) => {
                                    if (boxes[i]) boxes[i].value = d;
                                });
                                (boxes[digits.length - 1] || boxes[boxes.length - 1]).focus();
                                updateHidden();
                            }
                        });
                        box.addEventListener('keypress', (e) => {
                            if (!onlyDigit(e.key)) e.preventDefault();
                        });
                    });
                    document.getElementById('formCodigo').addEventListener('submit', (e) => {
                        e.preventDefault();
                        updateHidden();
                    });
                    document.getElementById('btnValidarCodigo').addEventListener('click', function () {
                        updateHidden();
                        SimpleAuth.validateResetCodeOnly(email);
                    });
                    boxes[0].focus();
                }, 100);
            }
            $('#step-email, #step-login, #step-register').hide();
            $('#step-code').show();
            // foca no primeiro box
            setTimeout(function () { $('#box-1').focus(); }, 200);
        },

        showPasswordStep: function (email, code) {
            if ($('#step-password').length === 0) {
                var passHtml = `
                <div id="step-password" style="display: none;">
                    <p style="color: #2196F3; margin-bottom: 15px;">
                        Código validado! Agora escolha sua nova senha:
                    </p>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Nova senha:</label>
                        <input type="password" id="user-reset-new-password" placeholder="Nova senha" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Confirmar senha:</label>
                        <input type="password" id="user-reset-confirm-password" placeholder="Repita a nova senha" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; box-sizing: border-box;">
                    </div>
                    <div id="password-error-msg" style="color:#d32f2f; min-height:22px; font-size:14px; text-align:center; margin-bottom:18px;"></div>
                    <button onclick="SimpleAuth.validateResetCode('${email}', '${code}')" style="width: 100%; background: #4CAF50; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-size: 16px; margin-bottom: 10px;">Redefinir senha</button>
                </div>`;
                $('#simple-auth-modal .simple-auth-content-wrapper').append(passHtml);
                // Limpa erro ao digitar
                setTimeout(function () {
                    $('#user-reset-new-password, #user-reset-confirm-password').on('input', function () {
                        $('#password-error-msg').text('');
                    });
                }, 100);
            }
            $('#step-code, #step-email, #step-login, #step-register').hide();
            $('#step-password').show();
            $('#user-reset-new-password').focus();
        },

        validateResetCodeOnly: function (email) {
            var code = '';
            if ($('#codigoValue').length) {
                code = $('#codigoValue').val().trim();
            } else if ($('#user-reset-code').length) {
                code = $('#user-reset-code').val().trim();
            }
            if (!code || code.length !== 6) {
                document.getElementById('code-error-msg').textContent = 'Digite o código de 6 dígitos enviado para seu e-mail.';
                if ($('#box-1').length) $('#box-1').focus(); else $('#user-reset-code').focus();
                return;
            }
            SimpleAuth.showLoading();
            $('#step-code').hide();
            $.ajax({
                url: (typeof mydCustomerAuth !== 'undefined' && mydCustomerAuth.ajax_url) ? mydCustomerAuth.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'myd_validate_reset_code',
                    email: email,
                    code: code,
                    only_validate: 1
                },
                success: function (response) {
                    SimpleAuth.hideLoading();
                    if (response.success) {
                        SimpleAuth.showPasswordStep(email, code);
                    } else {
                        $('#step-code').show();
                        document.getElementById('code-error-msg').textContent = (response.data && response.data.message) ? response.data.message : 'Código inválido.';
                    }
                },
                error: function () {
                    SimpleAuth.hideLoading();
                    $('#step-code').show();
                    document.getElementById('code-error-msg').textContent = 'Erro ao validar código.';
                }
            });
        },
        validateResetCode: function (email, code) {
            var newPassword = $('#user-reset-new-password').val().trim();
            var confirmPassword = $('#user-reset-confirm-password').val().trim();
            if (!newPassword || newPassword.length < 6) {
                $('#password-error-msg').text('A senha deve ter pelo menos 6 dígitos.');
                $('#user-reset-new-password').focus();
                return;
            }
            if (newPassword !== confirmPassword) {
                $('#password-error-msg').text('As senhas não conferem.');
                $('#user-reset-confirm-password').focus();
                return;
            }
            SimpleAuth.showLoading();
            $('#step-password').hide();
            $.ajax({
                url: (typeof mydCustomerAuth !== 'undefined' && mydCustomerAuth.ajax_url) ? mydCustomerAuth.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'myd_validate_reset_code',
                    email: email,
                    code: code,
                    new_password: newPassword
                },
                success: function (response) {
                    SimpleAuth.hideLoading();
                    if (response.success) {
                        SimpleAuth.showSuccessScreen('Senha alterada com sucesso.');
                        setTimeout(function () {
                            SimpleAuth.backToEmail();
                        }, 5000);
                    } else {
                        $('#step-password').show();
                        $('#password-error-msg').text(response.data && response.data.message ? response.data.message : 'Erro ao redefinir senha.');
                    }
                },
                error: function () {
                    SimpleAuth.hideLoading();
                    $('#step-password').show();
                    $('#password-error-msg').text('Erro ao redefinir senha.');
                }
            });
        },

        showSuccessScreen: function (message) {
            // Remove qualquer tela anterior de sucesso
            $('#simple-auth-success-screen').remove();
            // Substitui o conteúdo da etapa de senha pelo sucesso
            var svg = `<div style="display:flex;justify-content:center;margin-bottom:18px;">
            <svg viewBox="0 0 24 24" fill="none" width="64" height="64" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#00bd16" stroke-width="2"></path> <path d="M9 12L10.6828 13.6828V13.6828C10.858 13.858 11.142 13.858 11.3172 13.6828V13.6828L15 10" stroke="#00bd16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
            </div>`;
            var html = `<div id="simple-auth-success-screen" style="display:none;">
                <div style="background:#fff;border-radius:12px;box-shadow:0 4px 24px #0001;padding:40px 32px;max-width:340px;margin:auto;display:flex;flex-direction:column;align-items:center;">
                    ${svg}
                    <div style="font-size:20px;color:#00bd16;font-weight:600;text-align:center;">${message}</div>
                </div>
            </div>`;
            // Esconde todas as etapas e mostra o sucesso no local
            $('#step-password').hide();
            $('#step-code').hide();
            $('#step-login').hide();
            $('#step-register').hide();
            $('#step-email').hide();
            SimpleAuth.hideLoading();
            // Adiciona dentro do modal
            $('#simple-auth-modal .simple-auth-content-wrapper').append(html);
            var $screen = $('#simple-auth-success-screen');
            $screen.fadeIn(400);
            setTimeout(function () {
                $screen.fadeOut(400, function () {
                    $screen.remove();
                    SimpleAuth.backToEmail();
                    window.location.reload(); // Recarrega a página após fechar a tela de sucesso
                });
            }, 2000);
        },

        /**
         * Prepara e exibe o step de login quando email existe
         */
        prepareLoginStep: function (email, bypassGuard) {
            // Guard: only allow advancing from email step when the email was recently checked
            try {
                if (!bypassGuard && email && SimpleAuth._lastEmailChecked !== email) {
                    return false;
                }
            } catch (e) { }

            // Esconde o loading e outros steps, mostra apenas o login
            SimpleAuth.hideLoading();

            // Esconde step-email e step-register, mostra step-login
            $('#step-email').hide();
            $('#step-register').hide();
            $('#step-login').show();

            // Preenche apenas o rótulo/label com o email vinculado (não sobrescreve o campo CPF)
            if (email) {
                // preenche label visível na etapa de login
                $('#login-email-value').text(email);
                $('#login-email-row').show();
            }

            // Re-habilita o botão continuar
            var $continue = $('.simple-auth-btn-primary');
            $continue.prop('disabled', false).data('simpleauth-pending', false).removeClass('simple-auth-btn-disabled').attr('aria-busy', 'false');

            // Foca no campo de senha
            $('#user-password').focus();

            return true;
        },

        /**
         * Prepara e exibe o step de registro quando email não existe
         */
        prepareRegisterStep: function (email, bypassGuard) {
            // Guard: only allow advancing from email step when the email was recently checked
            try {
                if (!bypassGuard && email && SimpleAuth._lastEmailChecked !== email) {
                    return false;
                }
            } catch (e) { }

            // Esconde o loading e outros steps, mostra apenas o registro
            SimpleAuth.hideLoading();

            // Esconde step-email e step-login, mostra step-register
            $('#step-email').hide();
            $('#step-login').hide();
            $('#step-register').show();

            if (email) {
                // If identifier looks like an email, prefill email field.
                if (this.isValidEmail(email)) {
                    $('#user-email-register').val(email);
                } else {
                    var digits = email.toString().replace(/\D/g, '');
                    var loginMode = $('#step-email').attr('data-login-mode') || 'cpf';

                    if (loginMode === 'phone') {
                        var formatted = digits;
                        if (digits.length > 0) {
                            formatted = '(' + digits.substring(0, 2);
                            if (digits.length > 2) {
                                formatted += ') ' + digits.substring(2, 7);
                            }
                            if (digits.length > 7) {
                                formatted += '-' + digits.substring(7);
                            }
                        }
                        $('#user-phone').val(formatted);
                    } else if (digits.length === 11) {
                        $('#user-cpf').val(digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4'));
                    } else {
                        $('#user-email-register').val(email);
                    }
                }
            }

            // Re-habilita o botão continuar
            var $continue = $('.simple-auth-btn-primary');
            $continue.prop('disabled', false).data('simpleauth-pending', false).removeClass('simple-auth-btn-disabled').attr('aria-busy', 'false');

            // Foca no campo de nome
            $('#user-name').focus();

            return true;
        }
    };

    // Expõe globalmente
    window.SimpleAuth = SimpleAuth;

    // Inicializa quando documento pronto
    $(document).ready(function () {
        // ...
        SimpleAuth.init();
    });

    // ...

})(typeof jQuery !== 'undefined' ? jQuery : function () { return { ready: function (fn) { fn(); } }; });
