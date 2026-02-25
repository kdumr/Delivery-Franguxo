// Exibe popup de Notas de Atualizações ao abrir o painel de pedidos
jQuery(document).ready(function($){
        // Atalho manual para abrir popup ao clicar no botão do SVG na sidebar
        $(document).on('click', '#myd-update-notes-btn', function(e) {
            e.preventDefault();
            // Permite abrir múltiplas vezes pelo botão, mesmo que já tenha aberto antes
            if (document.getElementById('myd-update-notes-popup')) {
                // Se já estiver aberto, não faz nada
                return;
            }
            // Libera a flag para permitir abrir novamente
            window.mydUpdateNotesShown = undefined;
            var notesHtmlManual = (window.mydUpdateNotesUrlObj && window.mydUpdateNotesUrlObj.html) ? window.mydUpdateNotesUrlObj.html : null;
            if (notesHtmlManual) {
                exibirPopup(notesHtmlManual, null); // null para ignorar versionamento
            }
        });
    // Funções utilitárias para cookies
    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days*24*60*60*1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + (value || '')  + expires + '; path=/';
    }
    function getCookie(name) {
        var nameEQ = name + '=';
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }

    // Se a página mostrar uma mensagem de "sem permissão", não carregamos/exibimos as notas
    var pageText = (document.body && document.body.innerText) ? document.body.innerText : '';
    var noPermissionSelectors = ['.fdm-not-logged', '.myd-dashboard-unauth'];
    var hasNoPermissionEl = noPermissionSelectors.some(function(sel){ return document.querySelector(sel); });
    var hasNoPermissionText = /Desculpe, você não tem permiss/i.test(pageText) || /Desculpe, você não tem acesso/i.test(pageText);
    if (hasNoPermissionEl || hasNoPermissionText) return;

    // Verifica permissão de exibição: se o objeto localizado não indicar permissão, não faz nada
    var canView = (window.mydUpdateNotesUrlObj && window.mydUpdateNotesUrlObj.can_view) ? true : false;
    if (!canView) return; // usuário não tem permissão para ver as notas

    // Usa o HTML embutido passado pelo PHP (não faz fetch público)
    var notesHtml = (window.mydUpdateNotesUrlObj && window.mydUpdateNotesUrlObj.html) ? window.mydUpdateNotesUrlObj.html : null;
    if (notesHtml) {
        // Detecta versão no início do texto (ex: 'Versão 2.1.3')
        var versaoMatch = String(notesHtml).match(/Vers[aã]o\s+([0-9]+\.[0-9]+\.[0-9]+)/i);
        var versaoAtual = versaoMatch ? versaoMatch[1] : null;
        var versaoSalva = getCookie('mydUpdateNotesVersion');
        function versionCompare(a, b) {
            if (!a || !b) return 1;
            var pa = a.split('.').map(Number), pb = b.split('.').map(Number);
            for (var i = 0; i < Math.max(pa.length, pb.length); i++) {
                var na = pa[i] || 0, nb = pb[i] || 0;
                if (na > nb) return 1;
                if (na < nb) return -1;
            }
            return 0;
        }
        // Só exibe se não houver cookie ou se a versão for maior
        if (!versaoAtual || !versaoSalva || versionCompare(versaoAtual, versaoSalva) > 0) {
            exibirPopup(notesHtml, versaoAtual);
        }
    }

    function exibirPopup(data, versaoAtual) {
        if (typeof window.mydUpdateNotesShown === 'undefined') {
            window.mydUpdateNotesShown = true;
            var popup = document.createElement('div');
            popup.id = 'myd-update-notes-popup';
            popup.style.position = 'fixed';
            popup.style.top = '0';
            popup.style.left = '0';
            popup.style.width = '100vw';
            popup.style.height = '100vh';
            popup.style.background = 'rgba(0,0,0,0.35)';
            popup.style.zIndex = '100000';
            popup.style.display = 'flex';
            popup.style.alignItems = 'center';
            popup.style.justifyContent = 'center';
            popup.innerHTML = '<div style="background:#fff;max-width:715px;width:90vw;padding:32px 24px 24px 24px;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.18);position:relative;text-align:left;">' +
                '<h2 style="margin-top:0;font-size:1.5rem;font-weight:700;text-align:center;">Notas de atualizações</h2>' +
                '<div id="myd-update-notes-content" style="margin:18px 0 24px 0;font-size:15px;line-height:1.6;background:#f5f3f3;padding:16px 10px;border-radius:8px;max-height:60vh;overflow-y:auto;">Carregando notas...</div>' +
                '<button id="myd-update-notes-close" style="margin-top:8px;padding:10px 22px;border-radius:6px;background:#4caf50;color:#fff;font-weight:600;border:none;cursor:pointer;font-size:1rem;display:block;margin-left:auto;margin-right:auto;">Fechar</button>' +
                '</div>';
            document.body.appendChild(popup);

            // Exibir imagem em tela cheia ao clicar
            var img = popup.querySelector('img[alt="Sugestão"]');
            if (img) {
                img.style.cursor = 'pointer';
                img.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var overlay = document.createElement('div');
                    overlay.id = 'myd-update-notes-img-overlay';
                    overlay.style.position = 'fixed';
                    overlay.style.top = '0';
                    overlay.style.left = '0';
                    overlay.style.width = '100vw';
                    overlay.style.height = '100vh';
                    overlay.style.background = 'rgba(0,0,0,0.85)';
                    overlay.style.zIndex = '100001';
                    overlay.style.display = 'flex';
                    overlay.style.alignItems = 'center';
                    overlay.style.justifyContent = 'center';
                    overlay.innerHTML = '<img src="' + img.src + '" alt="Sugestão" style="max-width:90vw;max-height:90vh;border-radius:12px;box-shadow:0 4px 32px #000a;">';
                    document.body.appendChild(overlay);
                    function closeOverlay() {
                        if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                        document.removeEventListener('keydown', escListener);
                    }
                    overlay.addEventListener('click', closeOverlay);
                    var escListener = function(ev) {
                        if (ev.key === 'Escape') closeOverlay();
                    };
                    document.addEventListener('keydown', escListener);
                });
            }

            
            document.getElementById('myd-update-notes-close').onclick = function(){
                popup.remove();
                // Só grava o cookie se versaoAtual não for null (ou seja, não foi aberto pelo botão manual)
                if (versaoAtual) setCookie('mydUpdateNotesVersion', versaoAtual, 365);
                window.mydUpdateNotesShown = undefined;
            };
            var contentDiv = document.getElementById('myd-update-notes-content');
            contentDiv.innerHTML = data;
            // Força o tamanho do h3 para 20px
            var h3s = contentDiv.querySelectorAll('h3');
            h3s.forEach(function(h3){ h3.style.fontSize = '20px'; });

        }
    }
});
