/**
 * Sistema de Autenticação de Clientes - MyD Delivery Pro
 * 
 * Gerencia login, registro e perfil do cliente para nunca perder pedidos
 */
(function($) {
    'use strict';

    console.log('[AUTH] Iniciando definição do objeto MydCustomerAuth...');

    var MydCustomerAuth = {
        
        // Configuração
        config: {
            wrapper: '.myd-customer-auth',
            loginModal: '#myd-login-modal',
            registerModal: '#myd-register-modal',
            profileModal: '#myd-profile-modal',
            ordersModal: '#myd-orders-modal',
            loginForm: '#myd-login-form',
            registerForm: '#myd-register-form',
            profileForm: '#myd-profile-form',
        },

        // Estado atual
        currentCustomer: null,
        
        /**
         * Inicialização
         */
        init: function() {
            console.log('[AUTH] Método init() chamado');
            console.log('[AUTH] mydCustomerAuth data:', mydCustomerAuth);
            
            this.currentCustomer = mydCustomerAuth.current_user;
            console.log('[AUTH] Current customer:', this.currentCustomer);
            
            this.bindEvents();
            this.injectAuthInterface();
            this.updateAuthStatus();
            this.checkAutoLogin();
            
            console.log('[AUTH] Inicialização completa');
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Botões de autenticação
            $(document).on('click', '.myd-show-login', function(e) {
                e.preventDefault();
                self.showLoginModal();
            });

            $(document).on('click', '.myd-show-register', function(e) {
                e.preventDefault();
                self.showRegisterModal();
            });

            $(document).on('click', '.myd-show-profile', function(e) {
                e.preventDefault();
                self.showProfileModal();
            });

            $(document).on('click', '.myd-show-orders', function(e) {
                e.preventDefault();
                self.showOrdersModal();
            });

            $(document).on('click', '.myd-logout', function(e) {
                e.preventDefault();
                self.handleLogout();
            });

            // Formulários
            $(document).on('submit', this.config.loginForm, function(e) {
                e.preventDefault();
                self.handleLogin($(this));
            });

            $(document).on('submit', this.config.registerForm, function(e) {
                e.preventDefault();
                self.handleRegister($(this));
            });

            $(document).on('submit', this.config.profileForm, function(e) {
                e.preventDefault();
                self.handleProfileUpdate($(this));
            });

            // Formulários do checkout
            $(document).on('submit', '#myd-checkout-login-form', function(e) {
                e.preventDefault();
                self.handleCheckoutLogin($(this));
            });

            $(document).on('submit', '#myd-checkout-register-form', function(e) {
                e.preventDefault();
                self.handleCheckoutRegister($(this));
            });

            // Abas do modal de checkout
            $(document).on('click', '.myd-tab-btn', function(e) {
                e.preventDefault();
                self.switchTab($(this));
            });

            // Fechar modais
            $(document).on('click', '.myd-modal-close, .myd-modal-overlay', function(e) {
                if (e.target === this) {
                    self.closeModals();
                }
            });

            // Alternar entre login e registro
            $(document).on('click', '.myd-toggle-register', function(e) {
                e.preventDefault();
                self.closeModals();
                self.showRegisterModal();
            });

            $(document).on('click', '.myd-toggle-login', function(e) {
                e.preventDefault();
                self.closeModals();
                self.showLoginModal();
            });

            // Auto-preencher dados no checkout se logado
            if (this.currentCustomer) {
                this.setupCheckoutIntegration();
            }
        },

        /**
         * Injeta interface de autenticação na página (apenas modais)
         */
        injectAuthInterface: function() {
            // Verifica se já existe
            if ($('.myd-customer-auth').length > 0) {
                return;
            }

            // Injeta apenas os modais, sem o painel principal
            var authHtml = this.getAuthModalsHTML();
            $('body').append(authHtml);
            
            // Intercepta tentativas de checkout para mostrar login
            this.interceptCheckout();
        },

        /**
         * HTML apenas dos modais (sem painel principal)
         */
        getAuthModalsHTML: function() {
            return `
            <div class="myd-customer-auth">
                ${this.getLoginModalHTML()}
                ${this.getRegisterModalHTML()}
                ${this.getProfileModalHTML()}
                ${this.getOrdersModalHTML()}
            </div>
            `;
        },

        /**
         * Intercepta tentativas de checkout para solicitar login
         */
        interceptCheckout: function() {
            var self = this;
            
            console.log('[AUTH] Interceptador de checkout configurado');
            
            // Intercepta eventos MyD de checkout
            window.addEventListener('MydCheckoutPlaceOrder', function(e) {
                console.log('[AUTH] Evento MydCheckoutPlaceOrder detectado, usuário logado:', !!self.currentCustomer);
                
                // Se não estiver logado, previne o evento e mostra modal de autenticação
                if (!self.currentCustomer) {
                    console.log('[AUTH] Bloqueando checkout - usuário não logado');
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    self.showCheckoutAuthModal();
                    return false;
                }
                console.log('[AUTH] Permitindo checkout - usuário logado');
            }, true); // true para capturar na fase de captura antes do listener original
            
            // Intercepta eventos de pagamento também
            window.addEventListener('MydCheckoutPlacePayment', function(e) {
                console.log('[AUTH] Evento MydCheckoutPlacePayment detectado, usuário logado:', !!self.currentCustomer);
                
                // Se não estiver logado, previne o evento 
                if (!self.currentCustomer) {
                    console.log('[AUTH] Bloqueando pagamento - usuário não logado');
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    self.showCheckoutAuthModal();
                    return false;
                }
                console.log('[AUTH] Permitindo pagamento - usuário logado');
            }, true);
            
            // Backup: Intercepta cliques no botão de finalizar pedido (caso ainda use botões)
            $(document).on('click', '[data-action="create-order"], .myd-checkout-btn, .checkout-btn, .finalizar-pedido, #btn-create-order', function(e) {
                console.log('[AUTH] Clique em botão de checkout detectado, usuário logado:', !!self.currentCustomer);
                
                if (!self.currentCustomer) {
                    console.log('[AUTH] Bloqueando clique - usuário não logado');
                    e.preventDefault();
                    e.stopPropagation();
                    self.showCheckoutAuthModal();
                    return false;
                }
            });
            
            // Backup: Intercepta submissão de formulários de checkout
            $(document).on('submit', '.checkout-form, #checkout-form, .myd-checkout-form', function(e) {
                console.log('[AUTH] Submit de formulário detectado, usuário logado:', !!self.currentCustomer);
                
                if (!self.currentCustomer) {
                    console.log('[AUTH] Bloqueando submit - usuário não logado');
                    e.preventDefault();
                    e.stopPropagation();
                    self.showCheckoutAuthModal();
                    return false;
                }
            });
        },

        /**
         * Mostra modal específico para checkout
         */
        showCheckoutAuthModal: function() {
            var self = this;
            
            console.log('[AUTH] Mostrando modal de autenticação no checkout');
            
            // Cria modal específico para checkout se não existir
            if ($('#myd-checkout-auth-modal').length === 0) {
                console.log('[AUTH] Criando modal de checkout');
                var checkoutAuthHtml = this.getCheckoutAuthModalHTML();
                $('body').append(checkoutAuthHtml);
            } else {
                console.log('[AUTH] Modal já existe, apenas mostrando');
            }
            
            $('#myd-checkout-auth-modal').fadeIn(300);
            console.log('[AUTH] Modal de checkout exibido');
        },

        /**
         * HTML do modal de autenticação no checkout
         */
        getCheckoutAuthModalHTML: function() {
            return `
            <div id="myd-checkout-auth-modal" class="myd-modal myd-checkout-modal" style="display: none;">
                <div class="myd-modal-overlay"></div>
                <div class="myd-modal-content">
                    <div class="myd-modal-header">
                        <h3><i class="fas fa-user-circle"></i> Finalizar Pedido</h3>
                        <button type="button" class="myd-modal-close">&times;</button>
                    </div>
                    <div class="myd-modal-body">
                        <div class="myd-checkout-auth-message">
                            <div class="myd-auth-info">
                                <i class="fas fa-info-circle"></i>
                                <div>
                                    <h4>Para finalizar seu pedido</h4>
                                    <p>Faça login ou crie uma conta para garantir que seu pedido nunca seja perdido, mesmo se você fechar o navegador.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="myd-checkout-auth-options">
                            <div class="myd-auth-tabs">
                                <button type="button" class="myd-tab-btn active" data-tab="login">
                                    <i class="fas fa-sign-in-alt"></i> Já tenho conta
                                </button>
                                <button type="button" class="myd-tab-btn" data-tab="register">
                                    <i class="fas fa-user-plus"></i> Criar conta
                                </button>
                            </div>
                            
                            <div class="myd-tab-content">
                                <div id="checkout-login-tab" class="myd-tab active">
                                    <form id="myd-checkout-login-form">
                                        <div class="myd-form-group">
                                            <label for="checkout-login-email">Email</label>
                                            <input type="email" id="checkout-login-email" name="email" required>
                                        </div>
                                        <div class="myd-form-group">
                                            <label for="checkout-login-password">Senha</label>
                                            <input type="password" id="checkout-login-password" name="password" required>
                                        </div>
                                        <div class="myd-form-group myd-form-checkbox">
                                            <label>
                                                <input type="checkbox" name="remember" value="1">
                                                Lembrar de mim
                                            </label>
                                        </div>
                                        <div class="myd-form-group">
                                            <button type="submit" class="myd-btn myd-btn-primary myd-btn-block">
                                                <i class="fas fa-sign-in-alt"></i> Entrar e Finalizar Pedido
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <div id="checkout-register-tab" class="myd-tab">
                                    <form id="myd-checkout-register-form">
                                        <div class="myd-form-group">
                                            <label for="checkout-register-name">Nome Completo</label>
                                            <input type="text" id="checkout-register-name" name="name" required>
                                        </div>
                                        <div class="myd-form-group">
                                            <label for="checkout-register-email">Email</label>
                                            <input type="email" id="checkout-register-email" name="email" required>
                                        </div>
                                        <div class="myd-form-group">
                                            <label for="checkout-register-phone">Telefone</label>
                                            <input type="tel" id="checkout-register-phone" name="phone" required>
                                        </div>
                                        <div class="myd-form-group">
                                            <label for="checkout-register-password">Criar Senha</label>
                                            <input type="password" id="checkout-register-password" name="password" required>
                                        </div>
                                        <div class="myd-form-group">
                                            <button type="submit" class="myd-btn myd-btn-primary myd-btn-block">
                                                <i class="fas fa-user-plus"></i> Criar Conta e Finalizar Pedido
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="myd-checkout-auth-benefits">
                            <h5><i class="fas fa-star"></i> Benefícios de ter uma conta:</h5>
                            <ul>
                                <li><i class="fas fa-check"></i> Seus pedidos nunca se perdem</li>
                                <li><i class="fas fa-check"></i> Acompanhe o status em tempo real</li>
                                <li><i class="fas fa-check"></i> Checkout mais rápido</li>
                                <li><i class="fas fa-check"></i> Histórico de pedidos</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            `;
        },

        /**
         * HTML do modal de login
         */
        getLoginModalHTML: function() {
            return `
            <div id="myd-login-modal" class="myd-modal" style="display: none;">
                <div class="myd-modal-overlay"></div>
                <div class="myd-modal-content">
                    <div class="myd-modal-header">
                        <h3><i class="fas fa-sign-in-alt"></i> Entrar na Sua Conta</h3>
                        <button type="button" class="myd-modal-close">&times;</button>
                    </div>
                    <div class="myd-modal-body">
                        <form id="myd-login-form">
                            <div class="myd-form-group">
                                <label for="login-email">Email</label>
                                <input type="email" id="login-email" name="email" required>
                            </div>
                            <div class="myd-form-group">
                                <label for="login-password">Senha</label>
                                <input type="password" id="login-password" name="password" required>
                            </div>
                            <div class="myd-form-group myd-form-checkbox">
                                <label>
                                    <input type="checkbox" name="remember" value="1">
                                    Lembrar de mim
                                </label>
                            </div>
                            <div class="myd-form-group">
                                <button type="submit" class="myd-btn myd-btn-primary myd-btn-block">
                                    <i class="fas fa-sign-in-alt"></i> Entrar
                                </button>
                            </div>
                        </form>
                        <div class="myd-form-footer">
                            <p>Não tem conta? <a href="#" class="myd-toggle-register">Criar conta</a></p>
                        </div>
                    </div>
                </div>
            </div>
            `;
        },

        /**
         * HTML do modal de registro
         */
        getRegisterModalHTML: function() {
            return `
            <div id="myd-register-modal" class="myd-modal" style="display: none;">
                <div class="myd-modal-overlay"></div>
                <div class="myd-modal-content">
                    <div class="myd-modal-header">
                        <h3><i class="fas fa-user-plus"></i> Criar Sua Conta</h3>
                        <button type="button" class="myd-modal-close">&times;</button>
                    </div>
                    <div class="myd-modal-body">
                        <form id="myd-register-form">
                            <div class="myd-form-group">
                                <label for="register-name">Nome Completo</label>
                                <input type="text" id="register-name" name="name" required>
                            </div>
                            <div class="myd-form-group">
                                <label for="register-email">Email</label>
                                <input type="email" id="register-email" name="email" required>
                            </div>
                            <div class="myd-form-group">
                                <label for="register-phone">Telefone</label>
                                <input type="tel" id="register-phone" name="phone" required>
                            </div>
                            <div class="myd-form-group">
                                <label for="register-password">Senha</label>
                                <input type="password" id="register-password" name="password" required>
                            </div>
                            
                            <div class="myd-form-section">
                                <h4>Endereço (opcional)</h4>
                                <div class="myd-form-row">
                                    <div class="myd-form-group myd-form-col-8">
                                        <label for="register-address">Rua</label>
                                        <input type="text" id="register-address" name="address[street]">
                                    </div>
                                    <div class="myd-form-group myd-form-col-4">
                                        <label for="register-number">Número</label>
                                        <input type="text" id="register-number" name="address[number]">
                                    </div>
                                </div>
                                <div class="myd-form-group">
                                    <label for="register-complement">Complemento</label>
                                    <input type="text" id="register-complement" name="address[complement]">
                                </div>
                                <div class="myd-form-row">
                                    <div class="myd-form-group myd-form-col-6">
                                        <label for="register-neighborhood">Bairro</label>
                                        <input type="text" id="register-neighborhood" name="address[neighborhood]">
                                    </div>
                                    <div class="myd-form-group myd-form-col-6">
                                        <label for="register-zipcode">CEP</label>
                                        <input type="text" id="register-zipcode" name="address[zipcode]">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="myd-form-group">
                                <button type="submit" class="myd-btn myd-btn-primary myd-btn-block">
                                    <i class="fas fa-user-plus"></i> Criar Conta
                                </button>
                            </div>
                        </form>
                        <div class="myd-form-footer">
                            <p>Já tem conta? <a href="#" class="myd-toggle-login">Fazer login</a></p>
                        </div>
                    </div>
                </div>
            </div>
            `;
        },

        /**
         * HTML do modal de perfil
         */
        getProfileModalHTML: function() {
            return `
            <div id="myd-profile-modal" class="myd-modal" style="display: none;">
                <div class="myd-modal-overlay"></div>
                <div class="myd-modal-content">
                    <div class="myd-modal-header">
                        <h3><i class="fas fa-user-edit"></i> Meu Perfil</h3>
                        <button type="button" class="myd-modal-close">&times;</button>
                    </div>
                    <div class="myd-modal-body">
                        <form id="myd-profile-form">
                            <div class="myd-form-group">
                                <label for="profile-name">Nome Completo</label>
                                <input type="text" id="profile-name" name="name" required>
                            </div>
                            <div class="myd-form-group">
                                <label for="profile-email">Email</label>
                                <input type="email" id="profile-email" name="email" disabled>
                                <small>O email não pode ser alterado</small>
                            </div>
                            <div class="myd-form-group">
                                <label for="profile-phone">Telefone</label>
                                <input type="tel" id="profile-phone" name="phone" required>
                            </div>
                            
                            <div class="myd-form-section">
                                <h4>Endereço</h4>
                                <div class="myd-form-row">
                                    <div class="myd-form-group myd-form-col-8">
                                        <label for="profile-address">Rua</label>
                                        <input type="text" id="profile-address" name="address[street]">
                                    </div>
                                    <div class="myd-form-group myd-form-col-4">
                                        <label for="profile-number">Número</label>
                                        <input type="text" id="profile-number" name="address[number]">
                                    </div>
                                </div>
                                <div class="myd-form-group">
                                    <label for="profile-complement">Complemento</label>
                                    <input type="text" id="profile-complement" name="address[complement]">
                                </div>
                                <div class="myd-form-row">
                                    <div class="myd-form-group myd-form-col-6">
                                        <label for="profile-neighborhood">Bairro</label>
                                        <input type="text" id="profile-neighborhood" name="address[neighborhood]">
                                    </div>
                                    <div class="myd-form-group myd-form-col-6">
                                        <label for="profile-zipcode">CEP</label>
                                        <input type="text" id="profile-zipcode" name="address[zipcode]">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="myd-form-group">
                                <button type="submit" class="myd-btn myd-btn-primary myd-btn-block">
                                    <i class="fas fa-save"></i> Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            `;
        },

        /**
         * HTML do modal de pedidos
         */
        getOrdersModalHTML: function() {
            return `
            <div id="myd-orders-modal" class="myd-modal myd-modal-large" style="display: none;">
                <div class="myd-modal-overlay"></div>
                <div class="myd-modal-content">
                    <div class="myd-modal-header">
                        <h3><i class="fas fa-history"></i> Meus Pedidos</h3>
                        <button type="button" class="myd-modal-close">&times;</button>
                    </div>
                    <div class="myd-modal-body">
                        <div class="myd-orders-loading" style="display: none;">
                            <div class="myd-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                Carregando pedidos...
                            </div>
                        </div>
                        <div class="myd-orders-list"></div>
                    </div>
                </div>
            </div>
            `;
        },

        /**
         * Atualiza status de autenticação (só para debug)
         */
        updateAuthStatus: function() {
            // Como não temos mais painel principal, esta função agora só serve para debug
            if (this.currentCustomer) {
                console.log('Cliente logado:', this.currentCustomer.name);
            } else {
                console.log('Cliente não logado - modal aparecerá no checkout');
            }
        },

        /**
         * Verifica login automático
         */
        checkAutoLogin: function() {
            // Se há dados no localStorage de login anterior, oferece recuperar
            var savedEmail = localStorage.getItem('myd_last_email');
            if (savedEmail && !this.currentCustomer) {
                this.showAutoLoginPrompt(savedEmail);
            }
        },

        /**
         * Mostra prompt de login automático
         */
        showAutoLoginPrompt: function(email) {
            var message = 'Você tem login salvo (' + email + '). Deseja fazer login automaticamente?';
            if (confirm(message)) {
                $('#login-email').val(email);
                this.showLoginModal();
            }
        },

        /**
         * Mostra modal de login
         */
        showLoginModal: function() {
            this.closeModals();
            $(this.config.loginModal).fadeIn(300);
            $('#login-email').focus();
        },

        /**
         * Mostra modal de registro
         */
        showRegisterModal: function() {
            this.closeModals();
            $(this.config.registerModal).fadeIn(300);
            $('#register-name').focus();
        },

        /**
         * Mostra modal de perfil
         */
        showProfileModal: function() {
            if (!this.currentCustomer) {
                this.showLoginModal();
                return;
            }

            this.closeModals();
            
            // Preenche formulário
            $('#profile-name').val(this.currentCustomer.name);
            $('#profile-email').val(this.currentCustomer.email);
            $('#profile-phone').val(this.currentCustomer.phone || '');
            
            if (this.currentCustomer.address) {
                $('#profile-address').val(this.currentCustomer.address.street || '');
                $('#profile-number').val(this.currentCustomer.address.number || '');
                $('#profile-complement').val(this.currentCustomer.address.complement || '');
                $('#profile-neighborhood').val(this.currentCustomer.address.neighborhood || '');
                $('#profile-zipcode').val(this.currentCustomer.address.zipcode || '');
            }
            
            $(this.config.profileModal).fadeIn(300);
        },

        /**
         * Mostra modal de pedidos
         */
        showOrdersModal: function() {
            if (!this.currentCustomer) {
                this.showLoginModal();
                return;
            }

            this.closeModals();
            $(this.config.ordersModal).fadeIn(300);
            this.loadCustomerOrders();
        },

        /**
         * Switch entre abas do modal de checkout
         */
        switchTab: function($tabBtn) {
            var tabId = $tabBtn.data('tab');
            
            // Remove active de todas as abas
            $('.myd-tab-btn').removeClass('active');
            $('.myd-tab').removeClass('active');
            
            // Ativa a aba clicada
            $tabBtn.addClass('active');
            $('#checkout-' + tabId + '-tab').addClass('active');
        },

        /**
         * Handle login no checkout
         */
        handleCheckoutLogin: function($form) {
            var self = this;
            var formData = this.getFormData($form);

            this.showFormLoading($form);

            $.ajax({
                url: mydCustomerAuth.ajax_url,
                type: 'POST',
                data: {
                    action: 'myd_customer_login',
                    nonce: mydCustomerAuth.nonce,
                    ...formData
                },
                success: function(response) {
                    self.hideFormLoading($form);
                    
                    if (response.success) {
                        self.currentCustomer = response.data.customer;
                        self.showMessage('Login realizado! Finalizando seu pedido...', 'success');
                        
                        // Fecha modal e prossegue com checkout
                        $('#myd-checkout-auth-modal').fadeOut(300, function() {
                            self.proceedWithCheckout();
                        });
                    } else {
                        self.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    self.hideFormLoading($form);
                    self.showMessage('Erro ao fazer login. Tente novamente.', 'error');
                }
            });
        },

        /**
         * Handle registro no checkout
         */
        handleCheckoutRegister: function($form) {
            var self = this;
            var formData = this.getFormData($form);

            // Validação de nome: apenas letras, espaços e hífen
            var nome = (formData.name || '').trim();
            var nomeValido = /^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]+$/.test(nome);
            if (!nomeValido) {
                this.showMessage('O nome não pode conter caracteres especiais como @, . ou números.', 'error');
                return;
            }

            this.showFormLoading($form);

            $.ajax({
                url: mydCustomerAuth.ajax_url,
                type: 'POST',
                data: {
                    action: 'myd_customer_register',
                    nonce: mydCustomerAuth.nonce,
                    ...formData
                },
                success: function(response) {
                    self.hideFormLoading($form);
                    
                    if (response.success) {
                        self.currentCustomer = response.data.customer;
                        self.showMessage('Conta criada! Finalizando seu pedido...', 'success');
                        
                        // Fecha modal e prossegue com checkout
                        $('#myd-checkout-auth-modal').fadeOut(300, function() {
                            self.proceedWithCheckout();
                        });
                    } else {
                        self.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    self.hideFormLoading($form);
                    self.showMessage('Erro ao criar conta. Tente novamente.', 'error');
                }
            });
        },

        /**
         * Prossegue com o checkout após login/registro
         */
        proceedWithCheckout: function() {
            var self = this;
            
            console.log('[AUTH] Prosseguindo com checkout após autenticação');
            
            // Preenche dados do checkout automaticamente
            setTimeout(function() {
                self.fillCheckoutData();
                
                console.log('[AUTH] Dados preenchidos, disparando evento de checkout em 1 segundo...');
                
                // Após 1 segundo, dispara o evento original do MyD para prosseguir com checkout
                setTimeout(function() {
                    // Dispara evento MyD para continuar o checkout
                    if (window.Myd && window.Myd.newEvent) {
                        console.log('[AUTH] Disparando evento MydCheckoutPlaceOrder');
                        window.Myd.newEvent("MydCheckoutPlaceOrder", {});
                    } else {
                        console.log('[AUTH] MyD não disponível, tentando fallback com botões');
                        // Fallback: tenta clicar no botão se os eventos MyD não funcionarem
                        var $checkoutBtn = $('[data-action="create-order"], .myd-checkout-btn, .checkout-btn, .finalizar-pedido, #btn-create-order').first();
                        if ($checkoutBtn.length) {
                            console.log('[AUTH] Clicando em botão de checkout:', $checkoutBtn);
                            $checkoutBtn.trigger('click');
                        } else {
                            console.log('[AUTH] Nenhum botão encontrado, tentando formulário');
                            // Último recurso: submete formulário
                            var $checkoutForm = $('.checkout-form, #checkout-form, .myd-checkout-form').first();
                            if ($checkoutForm.length) {
                                console.log('[AUTH] Submetendo formulário:', $checkoutForm);
                                $checkoutForm.trigger('submit');
                            } else {
                                console.log('[AUTH] ERRO: Nenhum método de checkout encontrado!');
                            }
                        }
                    }
                }, 1000);
            }, 500);
        },

        /**
         * Fecha todos os modais
         */
        closeModals: function() {
            $('.myd-modal').fadeOut(200);
        },

        /**
         * Handle login
         */
        handleLogin: function($form) {
            var self = this;
            var formData = this.getFormData($form);

            this.showFormLoading($form);

            $.ajax({
                url: mydCustomerAuth.ajax_url,
                type: 'POST',
                data: {
                    action: 'myd_customer_login',
                    nonce: mydCustomerAuth.nonce,
                    ...formData
                },
                success: function(response) {
                    self.hideFormLoading($form);
                    
                    if (response.success) {
                        self.currentCustomer = response.data.customer;
                        self.closeModals();
                        self.updateAuthStatus();
                        self.showMessage(response.data.message, 'success');
                        self.setupCheckoutIntegration();
                        
                        // Salva email para próximo login
                        localStorage.setItem('myd_last_email', formData.email);
                        
                        // Reload para atualizar toda a página com dados do usuário
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        self.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    self.hideFormLoading($form);
                    self.showMessage('Erro ao fazer login. Tente novamente.', 'error');
                }
            });
        },

        /**
         * Handle register
         */
        handleRegister: function($form) {
            var self = this;
            var formData = this.getFormData($form);

            // Validação de nome: apenas letras, espaços e hífen
            var nome = (formData.name || '').trim();
            var nomeValido = /^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]+$/.test(nome);
            if (!nomeValido) {
                this.showMessage('O nome não pode conter caracteres especiais como @, . ou números.', 'error');
                return;
            }

            this.showFormLoading($form);

            $.ajax({
                url: mydCustomerAuth.ajax_url,
                type: 'POST',
                data: {
                    action: 'myd_customer_register',
                    nonce: mydCustomerAuth.nonce,
                    ...formData
                },
                success: function(response) {
                    self.hideFormLoading($form);
                    
                    if (response.success) {
                        self.currentCustomer = response.data.customer;
                        self.closeModals();
                        self.updateAuthStatus();
                        self.showMessage(response.data.message, 'success');
                        self.setupCheckoutIntegration();
                        
                        // Salva email para próximo login
                        localStorage.setItem('myd_last_email', formData.email);
                        
                        // Reload para atualizar toda a página com dados do usuário
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        self.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    self.hideFormLoading($form);
                    self.showMessage('Erro ao criar conta. Tente novamente.', 'error');
                }
            });
        },

        /**
         * Handle logout
         */
        handleLogout: function() {
            var self = this;

            if (!confirm('Tem certeza que deseja sair?')) {
                return;
            }

            $.ajax({
                url: mydCustomerAuth.ajax_url,
                type: 'POST',
                data: {
                    action: 'myd_customer_logout',
                    nonce: mydCustomerAuth.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.currentCustomer = null;
                        self.updateAuthStatus();
                        self.showMessage(response.data.message, 'success');
                        
                        // Remove dados salvos
                        localStorage.removeItem('myd_last_email');
                        
                        // Reload para limpar dados da página
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                },
                error: function() {
                    self.showMessage('Erro ao fazer logout.', 'error');
                }
            });
        },

        /**
         * Handle profile update
         */
        handleProfileUpdate: function($form) {
            var self = this;
            var formData = this.getFormData($form);

            this.showFormLoading($form);

            $.ajax({
                url: mydCustomerAuth.ajax_url,
                type: 'POST',
                data: {
                    action: 'myd_update_customer_profile',
                    nonce: mydCustomerAuth.nonce,
                    ...formData
                },
                success: function(response) {
                    self.hideFormLoading($form);
                    
                    if (response.success) {
                        self.currentCustomer = response.data.customer;
                        self.updateAuthStatus();
                        self.showMessage(response.data.message, 'success');
                        
                        // Fecha modal após sucesso
                        setTimeout(function() {
                            self.closeModals();
                        }, 2000);
                    } else {
                        self.showMessage(response.data.message, 'error');
                    }
                },
                error: function() {
                    self.hideFormLoading($form);
                    self.showMessage('Erro ao atualizar perfil.', 'error');
                }
            });
        },

        /**
         * Carrega pedidos do cliente
         */
        loadCustomerOrders: function() {
            var self = this;
            
            $('.myd-orders-loading').show();
            $('.myd-orders-list').html('');

            $.ajax({
                url: mydCustomerAuth.ajax_url,
                type: 'POST',
                data: {
                    action: 'myd_get_customer_orders',
                    nonce: mydCustomerAuth.nonce
                },
                success: function(response) {
                    $('.myd-orders-loading').hide();
                    
                    if (response.success) {
                        self.renderOrdersList(response.data.orders);
                    } else {
                        $('.myd-orders-list').html('<p>Erro ao carregar pedidos.</p>');
                    }
                },
                error: function() {
                    $('.myd-orders-loading').hide();
                    $('.myd-orders-list').html('<p>Erro ao carregar pedidos.</p>');
                }
            });
        },

        /**
         * Renderiza lista de pedidos
         */
        renderOrdersList: function(orders) {
            if (orders.length === 0) {
                $('.myd-orders-list').html(`
                    <div class="myd-no-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <p>Você ainda não fez nenhum pedido.</p>
                        <button type="button" class="myd-btn myd-btn-primary" onclick="jQuery('.myd-modal').fadeOut();">
                            Fazer Primeiro Pedido
                        </button>
                    </div>
                `);
                return;
            }

            var ordersHtml = '<div class="myd-orders">';
            
            orders.forEach(function(order) {
                var statusClass = 'myd-status-' + (order.status || 'pending');
                var statusText = self.getStatusText(order.status);
                var paymentClass = 'myd-payment-' + (order.payment_status || 'pending');
                var paymentText = self.getPaymentStatusText(order.payment_status);

                ordersHtml += `
                    <div class="myd-order-item">
                        <div class="myd-order-header">
                            <div class="myd-order-id">
                                <strong>Pedido #${order.id}</strong>
                                <span class="myd-order-date">${order.date}</span>
                            </div>
                            <div class="myd-order-status">
                                <span class="myd-status ${statusClass}">${statusText}</span>
                            </div>
                        </div>
                        <div class="myd-order-details">
                            <div class="myd-order-info">
                                <span class="myd-order-items">${order.items_count} item(s)</span>
                                <span class="myd-order-total">R$ ${order.total}</span>
                            </div>
                            <div class="myd-order-payment">
                                <span class="myd-payment-status ${paymentClass}">${paymentText}</span>
                            </div>
                        </div>
                        <div class="myd-order-actions">
                            <a href="${order.track_url}" class="myd-btn myd-btn-sm" target="_blank">
                                <i class="fas fa-eye"></i> Rastrear
                            </a>
                        </div>
                    </div>
                `;
            });
            
            ordersHtml += '</div>';
            $('.myd-orders-list').html(ordersHtml);
        },

        /**
         * Get status text
         */
        getStatusText: function(status) {
            var statusMap = {
                'pending': 'Pendente',
                'confirmed': 'Confirmado',
                'preparing': 'Preparando',
                'ready': 'Pronto',
                'out_for_delivery': 'Saiu para Entrega',
                'delivered': 'Entregue',
                'cancelled': 'Cancelado'
            };
            return statusMap[status] || 'Pendente';
        },

        /**
         * Get payment status text
         */
        getPaymentStatusText: function(status) {
            var statusMap = {
                'pending': 'Aguardando',
                'paid': 'Pago',
                'failed': 'Falhou',
                'cancelled': 'Cancelado'
            };
            return statusMap[status] || 'Aguardando';
        },

        /**
         * Setup checkout integration
         */
        setupCheckoutIntegration: function() {
            var self = this;
            
            // Preenche dados automaticamente no checkout
            if (this.currentCustomer) {
                setTimeout(function() {
                    self.fillCheckoutData();
                }, 500);
            }
        },

        /**
         * Preenche dados do checkout automaticamente
         */
        fillCheckoutData: function() {
            if (!this.currentCustomer) return;

            // Nome
            var $nameField = $('input[name="customer[name]"], #customer-name, .customer-name');
            if ($nameField.length && !$nameField.val()) {
                $nameField.val(this.currentCustomer.name).trigger('change');
            }

            // Telefone
            var $phoneField = $('input[name="customer[phone]"], #customer-phone, .customer-phone');
            if ($phoneField.length && !$phoneField.val() && this.currentCustomer.phone) {
                $phoneField.val(this.currentCustomer.phone).trigger('change');
            }

            // Endereço
            if (this.currentCustomer.address) {
                var addressFields = {
                    'input[name="customer[address][street]"], #customer-address, .customer-address': this.currentCustomer.address.street,
                    'input[name="customer[address][number]"], #address-number, .address-number': this.currentCustomer.address.number,
                    'input[name="customer[address][complement]"], #address-complement, .address-complement': this.currentCustomer.address.complement,
                    'input[name="customer[address][neighborhood]"], #neighborhood, .neighborhood': this.currentCustomer.address.neighborhood,
                    'input[name="customer[address][zipcode]"], #zipcode, .zipcode': this.currentCustomer.address.zipcode
                };

                Object.keys(addressFields).forEach(function(selector) {
                    var $field = $(selector);
                    var value = addressFields[selector];
                    if ($field.length && !$field.val() && value) {
                        $field.val(value).trigger('change');
                    }
                });
            }
        },

        /**
         * Get form data
         */
        getFormData: function($form) {
            var data = {};
            var formArray = $form.serializeArray();
            
            formArray.forEach(function(item) {
                // Handle nested arrays like address[street]
                if (item.name.includes('[') && item.name.includes(']')) {
                    var parts = item.name.replace(/\]/g, '').split('[');
                    if (parts.length === 2) {
                        if (!data[parts[0]]) data[parts[0]] = {};
                        data[parts[0]][parts[1]] = item.value;
                    }
                } else {
                    data[item.name] = item.value;
                }
            });
            
            return data;
        },

        /**
         * Show form loading
         */
        showFormLoading: function($form) {
            var $button = $form.find('button[type="submit"]');
            $button.prop('disabled', true);
            $button.data('original-text', $button.html());
            $button.html('<i class="fas fa-spinner fa-spin"></i> Processando...');
        },

        /**
         * Hide form loading
         */
        hideFormLoading: function($form) {
            var $button = $form.find('button[type="submit"]');
            $button.prop('disabled', false);
            $button.html($button.data('original-text'));
        },

        /**
         * Show message
         */
        showMessage: function(message, type) {
            type = type || 'info';
            
            // Remove mensagens anteriores
            $('.myd-message').remove();
            
            var messageHtml = `
                <div class="myd-message myd-message-${type}">
                    <div class="myd-message-content">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                        <span>${message}</span>
                    </div>
                </div>
            `;
            
            $('body').prepend(messageHtml);
            
            $('.myd-message').fadeIn(300);
            
            // Remove automaticamente após 5 segundos
            setTimeout(function() {
                $('.myd-message').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Inicializa quando documento estiver pronto
    $(document).ready(function() {
        console.log('[AUTH] Documento pronto, iniciando sistema de autenticação...');
        console.log('[AUTH] mydCustomerAuth:', window.mydCustomerAuth);
        
        if (typeof window.mydCustomerAuth !== 'undefined') {
            console.log('[AUTH] Configuração encontrada, inicializando...');
            MydCustomerAuth.init();
        } else {
            console.log('[AUTH] ERRO: mydCustomerAuth não definido - script pode não estar carregado pelo WordPress');
            
            // Para teste, vamos inicializar mesmo assim
            window.mydCustomerAuth = {
                ajax_url: '/wp-admin/admin-ajax.php',
                nonce: 'teste',
                current_user: null,
                messages: {}
            };
            MydCustomerAuth.init();
        }
    });

    // Expõe globalmente para debug
    window.MydCustomerAuth = MydCustomerAuth;

    console.log('[AUTH] Script customer-authentication.js carregado!');

})(jQuery);
