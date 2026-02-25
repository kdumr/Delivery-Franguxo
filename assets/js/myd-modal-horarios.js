// Função para ativar o NiceScroll na tabela de horários
function ativarNiceScroll() {
    $(".schedule-table-scroll").niceScroll({
        cursorcolor: "#ff6600",
        cursorwidth: "8px",
        cursorborder: "none",
        cursorborderradius: "10px",
        autohidemode: false,
        background: "#f2f2f2"
    });
}
// Função para destruir o NiceScroll
function destruirNiceScroll() {
    if ($('.schedule-table-scroll').getNiceScroll().length) {
        $('.schedule-table-scroll').getNiceScroll().remove();
    }
}
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('myModal');
    const btn = document.getElementById('myd-status-card-btn');
    const span = document.querySelector('#myModal .close');
    // Adiciona overlay de loading ao modal (apenas uma vez)
    let loadingOverlay = modal.querySelector('.myd-modal-loading-overlay');
    if (!loadingOverlay) {
        loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'myd-modal-loading-overlay';
        loadingOverlay.style = 'display:none;position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.7);z-index:1000;align-items:center;justify-content:center;';
        loadingOverlay.innerHTML = '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%;height:100%"><svg width="48" height="48" viewBox="0 0 50 50" style="margin-bottom:12px;"><circle cx="25" cy="25" r="20" fill="none" stroke="#ff6600" stroke-width="5" stroke-linecap="round" stroke-dasharray="31.4 31.4" transform="rotate(-90 25 25)"><animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="1s" repeatCount="indefinite"/></circle></svg><span style="color:#ff6600;font-weight:600;font-size:1.1rem;">Salvando...</span></div>';
        modal.querySelector('.modal-content').appendChild(loadingOverlay);
    }
    function showModalLoading(text = 'Salvando...') {
        loadingOverlay.querySelector('span').textContent = text;
        loadingOverlay.style.display = 'flex';
    }
    function hideModalLoading() { loadingOverlay.style.display = 'none'; }

    // Export globally
    window.mydShowModalLoading = showModalLoading;
    window.mydHideModalLoading = hideModalLoading;
    if (!modal || !btn || !span) return;
    btn.onclick = function () {
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('show');
            setTimeout(ativarNiceScroll, 350);
        }, 10);
    };
    span.onclick = function () {
        modal.classList.remove('show');
        destruirNiceScroll();
        setTimeout(() => {
            modal.style.display = 'none';
        }, 350);
    };
    window.onclick = function (event) {
        if (event.target === modal) {
            modal.classList.remove('show');
            destruirNiceScroll();
            setTimeout(() => {
                modal.style.display = 'none';
            }, 350);
        }
    };

    // Função para exibir notificação
    window.mydShowNotification = function (message, duration, type) {
        showNotification(message, duration, type);
    };

    function showNotification(message, duration = 3000, type = 'success') {
        // Expose for debug:
        window.mydShowNotification = showNotification;

        const notification = document.createElement('div');
        notification.className = 'myd-notification-card';
        if (type === 'error') {
            notification.classList.add('myd-notification-error');
        }
        notification.textContent = message;

        // Add icon based on type
        const icon = document.createElement('span');
        icon.className = 'myd-notification-icon';

        if (type === 'error') {
            // Error SVG
            icon.innerHTML = '<svg viewBox="0 0 512 512" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000" width="20" height="20" style="fill:#fff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>error-filled</title> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="add" fill="#ffffff" transform="translate(42.666667, 42.666667)"> <path d="M213.333333,3.55271368e-14 C331.136,3.55271368e-14 426.666667,95.5306667 426.666667,213.333333 C426.666667,331.136 331.136,426.666667 213.333333,426.666667 C95.5306667,426.666667 3.55271368e-14,331.136 3.55271368e-14,213.333333 C3.55271368e-14,95.5306667 95.5306667,3.55271368e-14 213.333333,3.55271368e-14 Z M262.250667,134.250667 L213.333333,183.168 L164.416,134.250667 L134.250667,164.416 L183.168,213.333333 L134.250667,262.250667 L164.416,292.416 L213.333333,243.498667 L262.250667,292.416 L292.416,262.250667 L243.498667,213.333333 L292.416,164.416 L262.250667,134.250667 Z" id="Combined-Shape"> </path> </g> </g> </g></svg>';
        } else {
            // Success SVG (Updated)
            icon.innerHTML = '<svg viewBox="0 0 24.00 24.00" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff" stroke-width="0.00024000000000000003" width="24" height="24"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12ZM16.0303 8.96967C16.3232 9.26256 16.3232 9.73744 16.0303 10.0303L11.0303 15.0303C10.7374 15.3232 10.2626 15.3232 9.96967 15.0303L7.96967 13.0303C7.67678 12.7374 7.67678 12.2626 7.96967 11.9697C8.26256 11.6768 8.73744 11.6768 9.03033 11.9697L10.5 13.4393L12.7348 11.2045L14.9697 8.96967C15.2626 8.67678 15.7374 8.67678 16.0303 8.96967Z" fill="#ffffff"></path> </g></svg>';
        }

        notification.prepend(icon);

        document.body.appendChild(notification);

        // Slide in
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });

        // Remove after duration
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 400);
        }, duration);
    }

    // --- Habilitar botão salvar e enviar alterações ---
    const btnSalvar = modal.querySelector('.btn-salvar');
    const inputs = modal.querySelectorAll('input[type="time"]');
    let originalValues = Array.from(inputs).map(input => input.value);

    // Habilita o botão se algum input mudar
    inputs.forEach(input => {
        input.addEventListener('input', function () {
            const changed = Array.from(inputs).some((inp, i) => inp.value !== originalValues[i]);
            btnSalvar.disabled = !changed;
        });
    });

    // Ao clicar em salvar, envia os dados via AJAX
    btnSalvar.addEventListener('click', function () {
        if (btnSalvar.disabled) return;
        btnSalvar.disabled = true;
        btnSalvar.textContent = 'Salvando...';
        showModalLoading();

        // Monta estrutura igual ao option PHP
        const dias = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const data = {};
        let idx = 0;
        dias.forEach(day => {
            const start = inputs[idx++].value;
            const end = inputs[idx++].value;
            data[day] = [];
            if (start || end) {
                data[day].push({ start, end });
            }
        });

        // Envia via AJAX para endpoint customizado
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=myd_save_delivery_time&data=' + encodeURIComponent(JSON.stringify(data))
        })
            .then(r => r.json())
            .then(resp => {
                btnSalvar.textContent = 'Salvar';
                hideModalLoading();
                if (resp.success) {
                    originalValues = Array.from(inputs).map(input => input.value);
                    btnSalvar.disabled = true;
                    btnSalvar.classList.add('btn-success');
                    setTimeout(() => btnSalvar.classList.remove('btn-success'), 1200);
                    showNotification('Configuração salva', 3000, 'success');
                } else {
                    btnSalvar.disabled = false;
                    // Error message
                    showNotification('Erro ao salvar configurações', 4000, 'error');
                }
            })
            .catch(() => {
                btnSalvar.textContent = 'Salvar';
                hideModalLoading();
                btnSalvar.disabled = false;
                showNotification('Erro ao salvar configurações', 4000, 'error');
            });
    });
});
