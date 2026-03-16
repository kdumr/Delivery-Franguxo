<?php
if ( ! defined( 'ABSPATH' ) ) {
    // If not running in WP context, allow including for testing
}
?>
<div id="simple-auth-modal" class="simple-auth-modal">
    <div class="simple-auth-modal-content">
            <div class="simple-auth-close-btn" role="button" tabindex="0" aria-label="Fechar" onclick="SimpleAuth.closeModal()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();SimpleAuth.closeModal();}">
                <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" class="simple-auth-close-icon" aria-hidden="true" fill="#000"><path d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12l-4.89 4.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z"></path></svg>
            </div>
        
        <!-- Wrapper com scroll para o conteúdo do modal -->
        <div class="simple-auth-content-wrapper">
            <!-- Titles per step: email title shown by default, register title shown when register step is active -->

            <!-- Etapa 1: Email -->
        <div id="step-email" data-login-mode="phone">
            <h2 class="simple-auth-title simple-auth-title-email" style="margin-bottom: 12px;">Login</h2>
            <div class="simple-auth-form-group-6mb">
                <label id="login-identifier-label" class="simple-auth-label">Telefone (Whatsapp):</label>
                <input type="tel" id="user-email" placeholder="(22) 99999-9999" maxlength="17" class="simple-auth-input" oninput="maskPhoneInput(this)">
                    <div id="email-check-error" class="simple-auth-error-msg" style="display:none;margin-top:12px"></div>
            </div>
            <button type="button" id="simple-auth-continue-btn" onclick="SimpleAuth.checkEmail()" class="simple-auth-btn-primary" aria-live="polite" aria-busy="false" style="margin-bottom: 12px;">Continuar</button>
        </div>

        <!-- Etapa 2: Login (oculta inicialmente) -->
        <div id="step-login" style="display: none;">
            <!-- Label pequeno mostrando o email encontrado e link Trocar -->
            <div id="login-email-row" class="simple-auth-email-row">
                <div id="login-email-label" class="simple-auth-email-label">Email: <span id="login-email-value" class="simple-auth-email-value"></span></div>
                <a id="login-change-btn" href="#" onclick="SimpleAuth.backToEmail(); return false;" class="simple-auth-change-btn">Trocar</a>
            </div>
            <div class="simple-auth-form-group-10mb" style="position:relative;">
                <label class="simple-auth-label">Senha:</label>
                <div style="position:relative;">
                    <input type="password" id="user-password" placeholder="Sua senha" class="simple-auth-input" style="padding-right:44px;">
                    <button type="button" aria-label="Mostrar senha" class="simple-auth-password-toggle" data-target="user-password" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:transparent;padding:6px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path stroke="#888" stroke-width="2" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/>
                            <circle cx="12" cy="12" r="3" stroke="#888" stroke-width="2"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="simple-auth-forgot-pass">
                <span id="forgot-pass-label" onclick="SimpleAuth.forgotPassword()" class="simple-auth-forgot-pass-link">Esqueci a senha</span>
            </div>
            <div id="login-error-msg" class="simple-auth-error-msg" style="display:none"></div>
            <button class="simple-auth-login-btn simple-auth-btn-login" onclick="SimpleAuth.doLogin()">Entrar</button>
            <button onclick="SimpleAuth.backToEmail()" class="simple-auth-btn-secondary">Voltar</button>
        </div>

        <!-- Etapa 3: Registro (oculta inicialmente) -->
        <div id="step-register" style="display: none;">
            <h2 class="simple-auth-title simple-auth-title-register" style="display:none">Crie sua conta</h2>
            <p class="simple-auth-warning-msg">
                📝 Novo(a) por aqui? Vamos criar sua conta:
            </p>
            <div id="register-error-msg" class="simple-auth-error-msg" style="display:none"></div>

            <div class="simple-auth-form-group">
                <label class="simple-auth-label">Nome Completo: <span class="simple-auth-required">*</span></label>
                <input type="text" id="user-name" placeholder="Seu nome completo" class="simple-auth-input" oninput="maskNameInput(this)">
                <div id="user-name-error" class="simple-auth-field-error"></div>
                <script>
                function maskNameInput(el) {
                    // Permite apenas letras, espaços, acentos e hífen
                    el.value = el.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿ\s'-]/g, '');
                }
                </script>
            </div>

            <div class="simple-auth-form-group">
                <label class="simple-auth-label">Telefone (Whatsapp): <span class="simple-auth-required">*</span></label>
                <input type="tel" id="user-phone" placeholder="(11) 99999-9999" class="simple-auth-input" maxlength="17" oninput="maskPhoneInput(this)">
                <script>
                function maskPhoneInput(el) {
                    let v = el.value.replace(/\D/g, '').slice(0,14);
                    let formatted = v;
                    if (v.length > 0) {
                        formatted = '(' + v.substring(0,2);
                        if (v.length >= 3) {
                            formatted += ') ' + v.substring(2,7);
                        }
                        if (v.length >= 8) {
                            formatted += '-' + v.substring(7);
                        }
                    }
                    el.value = formatted;
                }
                </script>
            </div>
            <div id="user-phone-error" class="simple-auth-field-error"></div>

            <div class="simple-auth-form-group">
                <label class="simple-auth-label">Email: <span class="simple-auth-required">*</span></label>
                <input type="email" id="user-email-register" placeholder="seuemail@exemplo.com" class="simple-auth-input">
                <div id="user-email-register-error" class="simple-auth-field-error"></div>
            </div>

            <div class="simple-auth-form-group">
                <label class="simple-auth-label">CPF: (Opcional)</label>
                <input type="text" id="user-cpf" placeholder="000.000.000-00" maxlength="14" class="simple-auth-input">
                <div id="user-cpf-error" class="simple-auth-field-error"></div>
            </div>

            <div class="simple-auth-form-group">
                <label class="simple-auth-label">Data de Nascimento: <span class="simple-auth-required">*</span></label>
                <input type="date" id="user-birthdate" placeholder="YYYY-MM-DD" class="simple-auth-input-date">
            </div>

            <div class="simple-auth-form-group">
                <label class="simple-auth-label">Criar senha: <span class="simple-auth-required">*</span></label>
                <input type="password" id="user-new-password" placeholder="Crie uma senha (mín. 6 caracteres)" class="simple-auth-input">
            </div>

            <div class="simple-auth-form-group">
                <label class="simple-auth-label">Confirmar senha: <span class="simple-auth-required">*</span></label>
                <input type="password" id="user-new-password-confirm" placeholder="Repita a senha" class="simple-auth-input">
                <div id="register-password-error" class="simple-auth-field-error"></div>
            </div>

            <script>
            function validateAndRegister() {
                var nameEl = document.getElementById('user-name');
                var nameVal = (nameEl ? nameEl.value : '').trim();
                var nameErr = document.getElementById('user-name-error');
                var phoneEl = document.getElementById('user-phone');
                var phoneVal = (phoneEl ? phoneEl.value : '').trim();
                var phoneErr = document.getElementById('user-phone-error');

                // Valida nome: precisa ter pelo menos um espaço (nome + sobrenome)
                if (nameVal && nameVal.indexOf(' ') === -1) {
                    if (nameErr) { nameErr.textContent = 'Digite seu nome completo. Exemplo: Paulo da Silva'; nameErr.style.display = 'block'; }
                    if (nameEl) nameEl.focus();
                    return;
                }
                if (nameErr) { nameErr.textContent = ''; nameErr.style.display = 'none'; }

                // Valida telefone: deve ter entre 10 e 11 dígitos
                if (phoneVal) {
                    var phoneDigits = phoneVal.replace(/\D/g, '');
                    if (phoneDigits.length > 0 && (phoneDigits.length < 10 || phoneDigits.length > 11)) {
                        if (phoneErr) { phoneErr.textContent = 'Digite um número válido DDD+Número. Exemplo: (22) 99999-9999'; phoneErr.style.display = 'block'; }
                        if (phoneEl) phoneEl.focus();
                        return;
                    }
                }
                if (phoneErr) { phoneErr.textContent = ''; phoneErr.style.display = 'none'; }

                // Passou validações, chama o registro
                SimpleAuth.doRegister();
            }
            </script>
            <div class="simple-auth-btn-group">
                <button onclick="SimpleAuth.backToEmail()" class="simple-auth-btn-flex-secondary">Voltar</button>
                <button onclick="validateAndRegister()" class="simple-auth-btn-flex">Criar Conta</button>
            </div>
        </div>
        </div> <!-- Fecha simple-auth-content-wrapper -->
    </div>
</div>
<script>
// Toggle password visibility for the simple auth modal (fallback if site doesn't include a global handler)
(function(){
    function toggleHandler(e){
        var btn = e.currentTarget;
        var targetId = btn.getAttribute('data-target');
        if(!targetId) return;
        var inp = document.getElementById(targetId);
        if(!inp) return;
        if(inp.type === 'password'){
            inp.type = 'text';
            btn.setAttribute('aria-pressed','true');
            // change icon to 'eye-off'
            btn.querySelector('svg').innerHTML = '<path stroke="#888" stroke-width="2" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><line x1="4" y1="20" x2="20" y2="4" stroke="#888" stroke-width="2"/>';
        } else {
            inp.type = 'password';
            btn.setAttribute('aria-pressed','false');
            btn.querySelector('svg').innerHTML = '<path stroke="#888" stroke-width="2" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z"/><circle cx="12" cy="12" r="3" stroke="#888" stroke-width="2"/>';
        }
    }
    document.addEventListener('click', function(e){
        var t = e.target.closest && e.target.closest('.simple-auth-password-toggle');
        if(t) toggleHandler({currentTarget: t});
    });
})();
</script>
