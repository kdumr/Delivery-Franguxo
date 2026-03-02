const { contextBridge, ipcRenderer } = require('electron');

// Expor IPC seguro para o Front-end
contextBridge.exposeInMainWorld('electronAPI', {
    showWhatsApp: () => ipcRenderer.send('show-whatsapp'),
    hideWhatsApp: () => ipcRenderer.send('hide-whatsapp')
});

// Listener injetado diretamente pelo preload
window.addEventListener('DOMContentLoaded', () => {
    function setupWhatsAppNavigation() {
        document.addEventListener('click', function (e) {
            // Clique em Menu, Pedidos ou Botão Voltar
            const isMenuBtn = e.target.closest('#myd-sidebar-menu-btn, a[href="#myd-section-menu"]');
            const isOrdersBtn = e.target.closest('#myd-sidebar-orders-btn, a[href="#myd-section-orders"]');
            const isBackBtn = e.target.closest('.myd-btn-back');

            if (isMenuBtn || isOrdersBtn || isBackBtn) {
                ipcRenderer.send('hide-whatsapp');
            }

            // Clique no botão WhatsApp
            const waBtn = e.target.closest('#myd-sidebar-whatsapp-btn, a[href="#myd-section-whatsapp"]');
            if (waBtn) {
                e.preventDefault();

                // Lógica isolada para limpar interface atual e mostrar View do WhatsApp
                const dashboardPanel = document.getElementById('dashboard-panel');
                if (dashboardPanel) dashboardPanel.style.display = 'none';

                const welcomeCover = document.getElementById('myd-welcome-cover');
                if (welcomeCover) welcomeCover.style.display = 'none';

                const ordersList = document.getElementById('myd-orders-list-panel');
                if (ordersList) ordersList.style.display = 'none';

                document.querySelectorAll('.fdm-orders-full-details').forEach(function (n) {
                    try {
                        n.style.display = 'none';
                        n.classList.remove('myd-detail-open');
                    } catch (err) { }
                });

                document.querySelectorAll('.fdm-orders-items.fdm-active').forEach(function (n) {
                    n.classList.remove('fdm-active');
                });

                // Atualizar sidebar
                document.querySelectorAll('.myd-sidebar .myd-sidebar-item').forEach(function (n) {
                    n.classList.remove('active');
                });
                waBtn.classList.add('active');

                // Acionar backend Electron
                ipcRenderer.send('show-whatsapp');
            }
        });
    }

    setTimeout(setupWhatsAppNavigation, 500);
});

// Listener para criar e atualizar a bolinha vermelha no ícone do WhatsApp
ipcRenderer.on('whatsapp-unread-count', (event, count) => {
    const waBtn = document.querySelector('#myd-sidebar-whatsapp-btn') || document.querySelector('a[href="#myd-section-whatsapp"]');
    if (!waBtn) return;

    let indicator = waBtn.querySelector('.wa-unread-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'wa-unread-indicator';
        indicator.style.position = 'absolute';
        indicator.style.top = '12px';
        indicator.style.right = '12px';
        indicator.style.width = '10px';
        indicator.style.height = '10px';
        indicator.style.backgroundColor = '#f14646'; // vermelho
        indicator.style.borderRadius = '50%';
        indicator.style.zIndex = '100';
        indicator.style.pointerEvents = 'none';

        waBtn.style.position = 'relative';
        waBtn.appendChild(indicator);
    }

    if (count > 0) {
        indicator.style.display = 'block';
    } else {
        indicator.style.display = 'none';
    }
});
