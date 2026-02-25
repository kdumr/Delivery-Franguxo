(function($){
    'use strict';
    console.debug('[myd-dashboard-settings] script loaded');
    $(document).ready(function(){
        $(document).on('click', '#myd-sidebar-settings', function(e){
            e.preventDefault();
            console.debug('[myd-dashboard-settings] settings clicked');
            try {
                // If SimpleAuth is ready, open modal immediately
                if (typeof SimpleAuth !== 'undefined' && typeof SimpleAuth.showSimpleModal === 'function') {
                    console.debug('[myd-dashboard-settings] calling SimpleAuth.showSimpleModal');
                    SimpleAuth.showSimpleModal(true);
                    // Try switching to 'forgot password' flow if available
                    var tries = 0;
                    var ti = setInterval(function(){
                        var forgot = document.querySelector('#forgot-pass-label');
                        if (forgot) {
                            try { forgot.click(); } catch(err) { console.debug('forgot click failed', err); }
                            clearInterval(ti);
                            return;
                        }
                        tries++;
                        if (tries > 30) clearInterval(ti);
                    }, 200);
                    return;
                }

                // If SimpleAuth isn't available yet, dispatch MydLoginRequired and retry opening modal later
                console.debug('[myd-dashboard-settings] SimpleAuth not ready, dispatching MydLoginRequired');
                window.dispatchEvent(new Event('MydLoginRequired'));

                // Try to call showSimpleModal a few times in case SimpleAuth initializes shortly after
                var retries = 0;
                var retryInterval = setInterval(function(){
                    if (typeof SimpleAuth !== 'undefined' && typeof SimpleAuth.showSimpleModal === 'function') {
                        try { SimpleAuth.showSimpleModal(true); } catch(err) { console.debug('retry showSimpleModal failed', err); }
                        clearInterval(retryInterval);
                        return;
                    }
                    retries++;
                    if (retries > 20) clearInterval(retryInterval);
                }, 250);

            } catch(e) {
                console.warn('Erro ao abrir modal de configurações:', e);
            }
        });
    });
})(jQuery);
