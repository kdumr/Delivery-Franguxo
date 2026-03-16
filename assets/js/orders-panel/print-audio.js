/* Consolidated audio + printing helpers extracted from panel.php.old
   Exposes: window.MydAudio, window.MydAlert, window.triggerAutoPrint,
   window.printOrderSingle, window._myd_manualPrint
*/
(function () {
    'use strict';

    // Audio helper (looping notify sound)
    var MydAudio = (function () {
        var audio = null;
        var checkAudio = null;
        var isLooping = false;
        var STORAGE_KEY = 'myd_audio_unlocked_v1';

        function init() {
            var origin = window.location.origin || '';
            if (!audio) {
                try { audio = new Audio(origin + '/wp-content/plugins/sistema-delivery-franguxo/assets/songs/trim.mp3'); }
                catch (e) { console.warn('[Orders Panel] falha ao criar Audio', e); audio = null; }
                if (audio) { audio.preload = 'auto'; audio.volume = 1.0; audio.loop = true; }
            }
            if (!checkAudio) {
                try { checkAudio = new Audio(origin + '/wp-content/plugins/sistema-delivery-franguxo/assets/songs/check.mp3'); }
                catch (e) { console.warn('[Orders Panel] falha ao criar checkAudio', e); checkAudio = null; }
                if (checkAudio) { checkAudio.preload = 'auto'; checkAudio.volume = 1.0; checkAudio.loop = false; }
            }
        }

        function startLoop() {
            try {
                init(); if (!audio) return;
                if (isLooping) return;
                audio.loop = true;
                isLooping = true;
                try { audio.currentTime = 0; } catch (_) { }
                var p = null;
                try { p = audio.play(); } catch (err) { console.warn('[Orders Panel] audio.play() threw', err); }
                if (p && p.catch) {
                    p.catch(function (err) { console.warn('[Orders Panel] áudio bloqueado ou falhou ao tocar', err); });
                }
            } catch (e) { console.warn('[Orders Panel] erro ao iniciar loop de áudio', e); }
        }

        function stop() {
            try {
                if (!audio) return;
                audio.loop = false;
                isLooping = false;
                audio.pause();
                try { audio.currentTime = 0; } catch (_) { }
            } catch (e) { /* noop */ }
        }

        function playFinished() {
            try {
                init(); if (!checkAudio) return;
                try { checkAudio.currentTime = 0; } catch (_) { }
                var p = null;
                try { p = checkAudio.play(); } catch (err) { console.warn('[Orders Panel] checkAudio.play() threw', err); }
                if (p && p.catch) {
                    p.catch(function (err) { console.warn('[Orders Panel] áudio check bloqueado ou falhou ao tocar', err); });
                }
            } catch (e) { console.warn('[Orders Panel] erro ao tocar áudio check', e); }
        }

        function markUnlocked() { try { localStorage.setItem(STORAGE_KEY, String(Date.now())); } catch (_) { } }
        function isUnlocked() { try { return !!localStorage.getItem(STORAGE_KEY); } catch (_) { return false; } }

        function tryUnlockViaAudioContext(done) {
            try {
                var AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (AudioCtx) {
                    var ctx = window.__MYD_UNLOCK_AUDIO_CTX;
                    if (!ctx) {
                        try { ctx = new AudioCtx(); window.__MYD_UNLOCK_AUDIO_CTX = ctx; }
                        catch (e) { ctx = null; }
                    }
                    if (ctx && typeof ctx.resume === 'function') {
                        ctx.resume().then(function () { markUnlocked(); if (done) done(true); }).catch(function () { if (done) done(false); });
                        return;
                    }
                }
            } catch (e) { /* ignore */ }
            if (done) done(false);
        }

        function tryUnlockViaPlayPause(done) {
            try {
                init(); if (!audio) { if (done) done(true); return; }
                var p = null;
                try { p = audio.play(); } catch (e) { p = null; }
                if (p && p.then) {
                    p.then(function () { try { audio.pause(); audio.currentTime = 0; } catch (_) { } markUnlocked(); if (done) done(true); })
                        .catch(function () { markUnlocked(); if (done) done(false); });
                } else {
                    try { audio.pause(); audio.currentTime = 0; } catch (_) { }
                    markUnlocked(); if (done) done(true);
                }
            } catch (e) { markUnlocked(); if (done) done(false); }
        }

        function unlock() {
            if (isUnlocked()) return; // already
            tryUnlockViaAudioContext(function (ok) { if (ok) return; tryUnlockViaPlayPause(function () { /* noop */ }); });
            function onGesture() { try { tryUnlockViaPlayPause(function () { /* noop */ }); } catch (_) { } finally { removeListeners(); } }
            function addListeners() { try { ['click', 'keydown', 'touchstart'].forEach(function (ev) { document.addEventListener(ev, onGesture, true); }); } catch (e) { } }
            function removeListeners() { try { ['click', 'keydown', 'touchstart'].forEach(function (ev) { document.removeEventListener(ev, onGesture, true); }); } catch (e) { } }
            addListeners();
        }

        try { if (!isUnlocked()) { unlock(); } } catch (_) { }

        return { startLoop: startLoop, stop: stop, playFinished: playFinished, unlock: unlock };
    })();

    // Alert pending orders manager
    var MydAlert = (function () {
        var pending = new Set();
        function add(id) { try { pending.add(String(id)); MydAudio.startLoop(); } catch (_) { } }
        function removeLocal(id) { try { pending.delete(String(id)); } catch (_) { } }
        function removeFromSocket(id) { try { pending.delete(String(id)); if (pending.size === 0) MydAudio.stop(); } catch (_) { } }
        function count() { try { return pending.size; } catch (_) { return 0; } }
        return { add: add, removeLocal: removeLocal, removeFromSocket: removeFromSocket, count: count };
    })();

    // send to local print server (tries configured endpoint + fallbacks)
    function sendToLocalPrintServer(payload) {
        if (!payload) return Promise.reject(new Error('missing_payload'));
        if (typeof fetch !== 'function') { return Promise.reject(new Error('fetch_not_available')); }
        var body = null;
        try { body = JSON.stringify(payload); } catch (err) { return Promise.reject(err); }
        var candidates = [];
        try { if (typeof window !== 'undefined' && window.MYD_LOCAL_PRINT_ENDPOINT) { candidates.push(String(window.MYD_LOCAL_PRINT_ENDPOINT)); } } catch (_) { }
        candidates.push('http://127.0.0.1:3420/print');
        candidates.push('http://localhost:3420/print');
        var index = 0;
        function attempt() {
            if (index >= candidates.length) { return Promise.reject(new Error('all_local_print_endpoints_failed')); }
            var url = candidates[index++];
            return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body }).catch(function (err) { console.warn('[Orders Panel] failed sending to local print server via ' + url, err); return attempt(); });
        }
        return attempt();
    }

    // Full auto-print implementation
    function triggerAutoPrint(orderId) {
        if (!orderId) { console.warn('[Orders Panel] auto-print skipped: missing order id'); return; }
        window.__myd_printed_orders = window.__myd_printed_orders || new Set();
        if (window.__myd_printed_orders.has(String(orderId))) {
            console.log('[Orders Panel] auto-print dedup skipped for order', orderId);
            return;
        }
        window.__myd_printed_orders.add(String(orderId));

        try { window.triggerAutoPrint = triggerAutoPrint; } catch (_) { /* noop */ }
        if (typeof fetch !== 'function') { console.error('[Orders Panel] auto-print requires fetch API'); return; }
        console.log('[Orders Panel] auto-print triggered for order', orderId);
        try {
            var encodedBody = 'action=get_order_print_data&order_id=' + encodeURIComponent(orderId);
            fetch('/wp-admin/admin-ajax.php', { method: 'POST', body: encodedBody, headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) { throw new Error('print_data_fetch_failed_status_' + r.status); } return r.json(); })
                .then(function (resp) {
                    if (resp && resp.success && resp.data) {
                        var orderData = resp.data;
                        console.log('[Orders Panel] orderData fetched for auto-print:', orderData);
                        var payload = { orderData: orderData, escpos: true };
                        try { var storedPrinter = localStorage.getItem('myd-default-printer'); if (storedPrinter) payload.printer = storedPrinter; } catch (_) { }
                        try { var storedCopies = localStorage.getItem('myd-print-copies'); if (storedCopies) payload.copies = storedCopies; } catch (_) { }
                        var copies = 1;
                        try { var sc = localStorage.getItem('myd-print-copies'); if (sc) { copies = parseInt(String(sc), 10) || 1; } } catch (_) { }
                        if (copies < 1) copies = 1; var MAX_COPIES = 10; if (copies > MAX_COPIES) copies = MAX_COPIES;

                        function sendCopyAttempt(n) {
                            if (n > copies) return Promise.resolve();
                            return sendToLocalPrintServer(payload)
                                .then(function () {
                                    console.log('[Orders Panel] local print server acknowledged auto-print copy ' + n + ' for order', orderId);
                                    return new Promise(function (resolve) { setTimeout(resolve, 800); })
                                        .then(function () { return sendCopyAttempt(n + 1); });
                                })
                                .catch(function (err) {
                                    console.error('[Orders Panel] failed sending copy ' + n + ' to local print server', err);
                                    // Continues to the next copy anyway
                                    return new Promise(function (resolve) { setTimeout(resolve, 800); })
                                        .then(function () { return sendCopyAttempt(n + 1); });
                                });
                        }

                        return sendCopyAttempt(1).then(function () { console.log('[Orders Panel] completed auto-print attempts for order', orderId); }).catch(function (err) { console.error('[Orders Panel] error during auto-print attempts for order', orderId, err); });
                    }
                    throw new Error('missing_order_data_for_print');
                }).catch(function (err) { console.error('[Orders Panel] auto-print flow failed for order ' + orderId, err); });
        } catch (err) { console.error('[Orders Panel] unexpected auto-print error for order ' + orderId, err); }
    }

    // Manual flows
    window._myd_manualPrint = function (orderId) {
        if (!orderId) { console.warn('[Orders Panel] manual print skipped: missing order id'); return Promise.reject(new Error('missing_order_id')); }
        if (typeof fetch !== 'function') { console.error('[Orders Panel] manual print requires fetch API'); return Promise.reject(new Error('fetch_not_available')); }
        try {
            var encodedBody = 'action=get_order_print_data&order_id=' + encodeURIComponent(orderId);
            return fetch('/wp-admin/admin-ajax.php', { method: 'POST', body: encodedBody, headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw new Error('print_data_fetch_failed_status_' + r.status); return r.json(); })
                .then(function (resp) {
                    if (resp && resp.success && resp.data) {
                        var orderData = resp.data;
                        var payload = { orderData: orderData, escpos: true };
                        try { var storedPrinter = localStorage.getItem('myd-default-printer'); if (storedPrinter) payload.printer = storedPrinter; } catch (_) { }
                        try { var storedCopies = localStorage.getItem('myd-print-copies'); if (storedCopies) payload.copies = storedCopies; } catch (_) { }
                        var body = null; try { body = JSON.stringify(payload); } catch (err) { return Promise.reject(err); }
                        var copies = 1; try { var sc = localStorage.getItem('myd-print-copies'); if (sc) copies = parseInt(String(sc), 10) || 1; } catch (_) { }
                        if (copies < 1) copies = 1; var MAX_COPIES = 10; if (copies > MAX_COPIES) copies = MAX_COPIES;

                        function sendLocalCopy(n) {
                            if (n > copies) return Promise.resolve();
                            return sendToLocalPrintServer(payload)
                                .then(function () {
                                    console.log('[Orders Panel] manual print acknowledged copy ' + n + ' for order', orderId);
                                    return new Promise(function (resolve) { setTimeout(resolve, 800); })
                                        .then(function () { return sendLocalCopy(n + 1); });
                                })
                                .catch(function (err) {
                                    console.error('[Orders Panel] failed sending copy ' + n + ' to local print server', err);
                                    return new Promise(function (resolve) { setTimeout(resolve, 800); })
                                        .then(function () { return sendLocalCopy(n + 1); });
                                });
                        }

                        return sendLocalCopy(1);
                    }
                    throw new Error('missing_order_data_for_print');
                }).catch(function (err) { console.error('[Orders Panel] manual print flow failed for order ' + orderId, err); throw err; });
        } catch (err) { console.error('[Orders Panel] unexpected manual print error for order ' + orderId, err); return Promise.reject(err); }
    };

    window.printOrderSingle = function (orderId) {
        if (!orderId) { console.warn('[Orders Panel] printOrderSingle skipped: missing order id'); return Promise.reject(new Error('missing_order_id')); }
        if (typeof fetch !== 'function') { console.error('[Orders Panel] printOrderSingle requires fetch API'); return Promise.reject(new Error('fetch_not_available')); }
        try {
            var encodedBody = 'action=get_order_print_data&order_id=' + encodeURIComponent(orderId);
            return fetch('/wp-admin/admin-ajax.php', { method: 'POST', body: encodedBody, headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) throw new Error('print_data_fetch_failed_status_' + r.status); return r.json(); })
                .then(function (resp) {
                    if (resp && resp.success && resp.data) {
                        var orderData = resp.data;
                        var payload = { orderData: orderData, escpos: true };
                        try { var storedPrinter = localStorage.getItem('myd-default-printer'); if (storedPrinter) payload.printer = storedPrinter; } catch (_) { }
                        payload.copies = 1;
                        var body = null; try { body = JSON.stringify(payload); } catch (err) { return Promise.reject(err); }
                        return fetch('http://127.0.0.1:3420/print', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body })
                            .catch(function () { return fetch('http://localhost:3420/print', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body }); })
                            .then(function (res) { if (!res || !res.ok) throw new Error('local_print_failed'); console.log('[Orders Panel] printOrderSingle acknowledged for order', orderId); return res; });
                    }
                    throw new Error('missing_order_data_for_print');
                }).catch(function (err) { console.error('[Orders Panel] printOrderSingle flow failed for order ' + orderId, err); throw err; });
        } catch (err) { console.error('[Orders Panel] unexpected printOrderSingle error for order ' + orderId, err); return Promise.reject(err); }
    };

    // Expose on window
    try { window.MydAudio = MydAudio; window.MydAlert = MydAlert; window.triggerAutoPrint = triggerAutoPrint; } catch (e) { /* noop */ }

})();
