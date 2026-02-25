/**
 * Extensão para Recuperação de Sessão na Página de Tracking
 * MyD Delivery Pro
 * 
 * Melhora a recuperação quando usuário acessa diretamente a página de tracking
 */

(function() {
    'use strict';

    class TrackingPageSessionRecovery {
        constructor() {
            this.init();
        }

        init() {
            // Só executa se estivermos na página de tracking
            if (!this.isTrackingPage()) {
                return;
            }

            // Aguarda carregamento completo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setupTrackingRecovery());
            } else {
                this.setupTrackingRecovery();
            }
        }

        isTrackingPage() {
            // Verifica se estamos na página de tracking por URL ou elementos específicos
            return window.location.href.includes('track') || 
                   document.querySelector('.fdm-track-order-wrap') ||
                   document.querySelector('#myd-track-order-status-bar');
        }

        setupTrackingRecovery() {
            // Verifica se há sessão salva e se corresponde ao pedido atual
            const sessionData = this.getOrderSession();
            const currentOrderId = this.getCurrentOrderId();

            if (sessionData && currentOrderId && sessionData.orderId == currentOrderId) {
                this.addTrackingButtons(sessionData);
            }
        }

        getCurrentOrderId() {
            // Tenta extrair o ID do pedido da URL ou da página
            const urlParams = new URLSearchParams(window.location.search);
            const hashParam = urlParams.get('hash');
            
            if (hashParam) {
                try {
                    return atob(hashParam); // Decodifica base64
                } catch (e) {
                    console.warn('Erro ao decodificar hash do pedido:', e);
                }
            }

            // Tenta pegar do elemento da página
            const orderNumberElement = document.querySelector('.fdm-order-list-items-order-number');
            if (orderNumberElement) {
                const text = orderNumberElement.textContent;
                const match = text.match(/\d+/);
                return match ? match[0] : null;
            }

            return null;
        }

        getOrderSession() {
            try {
                let sessionData = sessionStorage.getItem('myd_order_session');
                if (!sessionData) {
                    sessionData = localStorage.getItem('myd_order_session');
                }
                return sessionData ? JSON.parse(sessionData) : null;
            } catch (error) {
                return null;
            }
        }

        addTrackingButtons(sessionData) {
            // Adiciona botões úteis na página de tracking
            const trackingContent = document.querySelector('.fdm-track-order-content');
            if (!trackingContent) return;

            const buttonContainer = document.createElement('div');
            buttonContainer.className = 'myd-tracking-recovery-buttons';
            buttonContainer.innerHTML = `
                <div class="myd-tracking-recovery-info">
                    <h4>🔄 Ações do Pedido</h4>
                    <p>Seu pedido foi salvo automaticamente. Use as opções abaixo se necessário:</p>
                </div>
                <div class="myd-tracking-buttons">
                    <button id="myd-continue-order" class="myd-tracking-btn myd-btn-primary">
                        📱 Continuar Pedido
                    </button>
                    <button id="myd-copy-order-link" class="myd-tracking-btn myd-btn-secondary">
                        🔗 Copiar Link
                    </button>
                    <button id="myd-whatsapp-support" class="myd-tracking-btn myd-btn-info">
                        💬 WhatsApp
                    </button>
                </div>
            `;

            // Adiciona CSS inline para os botões
            const style = document.createElement('style');
            style.textContent = `
                .myd-tracking-recovery-buttons {
                    background: #f8f9fa;
                    border: 1px solid #dee2e6;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                    text-align: center;
                }
                .myd-tracking-recovery-info h4 {
                    margin: 0 0 8px 0;
                    color: #495057;
                    font-size: 16px;
                }
                .myd-tracking-recovery-info p {
                    margin: 0 0 16px 0;
                    color: #6c757d;
                    font-size: 14px;
                }
                .myd-tracking-buttons {
                    display: flex;
                    gap: 12px;
                    justify-content: center;
                    flex-wrap: wrap;
                }
                .myd-tracking-btn {
                    padding: 10px 16px;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    min-width: 120px;
                }
                .myd-btn-primary {
                    background: #28a745;
                    color: white;
                }
                .myd-btn-primary:hover {
                    background: #218838;
                    transform: translateY(-1px);
                }
                .myd-btn-secondary {
                    background: #6c757d;
                    color: white;
                }
                .myd-btn-secondary:hover {
                    background: #5a6268;
                    transform: translateY(-1px);
                }
                .myd-btn-info {
                    background: #17a2b8;
                    color: white;
                }
                .myd-btn-info:hover {
                    background: #138496;
                    transform: translateY(-1px);
                }
                @media (max-width: 768px) {
                    .myd-tracking-buttons {
                        flex-direction: column;
                        align-items: center;
                    }
                    .myd-tracking-btn {
                        width: 100%;
                        max-width: 200px;
                    }
                }
            `;
            document.head.appendChild(style);

            // Insere os botões na página
            trackingContent.appendChild(buttonContainer);

            // Adiciona event listeners
            this.setupButtonEvents(sessionData);
        }

        setupButtonEvents(sessionData) {
            // Botão continuar pedido
            const continueBtn = document.getElementById('myd-continue-order');
            if (continueBtn) {
                continueBtn.addEventListener('click', () => {
                    // Redireciona para a página de produtos com sessão ativa
                    const storeUrl = sessionData.url || window.location.origin;
                    const deliveryUrl = storeUrl.replace(/\/[^\/]*$/, ''); // Remove query parameters
                    
                    // Força uma flag para mostrar o diálogo de recuperação
                    sessionStorage.setItem('myd_force_recovery_dialog', 'true');
                    
                    window.location.href = deliveryUrl;
                });
            }

            // Botão copiar link
            const copyBtn = document.getElementById('myd-copy-order-link');
            if (copyBtn) {
                copyBtn.addEventListener('click', () => {
                    const currentUrl = window.location.href;
                    
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(currentUrl).then(() => {
                            this.showMessage('Link copiado com sucesso!', 'success');
                            copyBtn.textContent = '✅ Copiado!';
                            setTimeout(() => {
                                copyBtn.innerHTML = '🔗 Copiar Link';
                            }, 2000);
                        }).catch(() => {
                            this.fallbackCopyText(currentUrl, copyBtn);
                        });
                    } else {
                        this.fallbackCopyText(currentUrl, copyBtn);
                    }
                });
            }

            // Botão WhatsApp
            const whatsappBtn = document.getElementById('myd-whatsapp-support');
            if (whatsappBtn) {
                whatsappBtn.addEventListener('click', () => {
                    const orderId = sessionData.orderId;
                    const message = `Olá! Preciso de ajuda com meu pedido #${orderId}. Link: ${window.location.href}`;
                    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
                    window.open(whatsappUrl, '_blank');
                });
            }
        }

        fallbackCopyText(text, button) {
            // Fallback para dispositivos que não suportam clipboard API
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                this.showMessage('Link copiado com sucesso!', 'success');
                button.textContent = '✅ Copiado!';
                setTimeout(() => {
                    button.innerHTML = '🔗 Copiar Link';
                }, 2000);
            } catch (err) {
                this.showMessage('Erro ao copiar. Copie manualmente: ' + text, 'error');
            }
            
            document.body.removeChild(textArea);
        }

        showMessage(message, type = 'info') {
            // Remove mensagem existente
            const existingMsg = document.querySelector('.myd-tracking-message');
            if (existingMsg) {
                existingMsg.remove();
            }

            // Cria nova mensagem
            const messageDiv = document.createElement('div');
            messageDiv.className = `myd-tracking-message myd-tracking-message-${type}`;
            messageDiv.textContent = message;
            
            // Estilos da mensagem
            const styles = {
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '12px 20px',
                borderRadius: '6px',
                fontWeight: '600',
                fontSize: '14px',
                zIndex: '10000',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                transition: 'all 0.3s ease'
            };

            // Cores por tipo
            if (type === 'success') {
                styles.background = '#d4edda';
                styles.color = '#155724';
                styles.border = '1px solid #c3e6cb';
            } else if (type === 'error') {
                styles.background = '#f8d7da';
                styles.color = '#721c24';
                styles.border = '1px solid #f5c6cb';
            } else {
                styles.background = '#cce7ff';
                styles.color = '#004085';
                styles.border = '1px solid #b6d4fe';
            }

            Object.assign(messageDiv.style, styles);
            
            document.body.appendChild(messageDiv);

            // Remove após 4 segundos
            setTimeout(() => {
                messageDiv.style.opacity = '0';
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                }, 300);
            }, 4000);
        }
    }

    // Inicializa
    window.addEventListener('load', () => {
        new TrackingPageSessionRecovery();
    });

    if (document.readyState !== 'loading') {
        new TrackingPageSessionRecovery();
    }

})();
