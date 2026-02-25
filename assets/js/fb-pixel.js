(function(){
    'use strict';

    // Config via localized MYD_FB
    var cfg = (typeof MYD_FB !== 'undefined') ? MYD_FB : { pixelId: '', currency: 'BRL' };
    var PIXEL_ID = cfg.pixelId || '';
    var CURRENCY = cfg.currency || 'BRL';
    if (!PIXEL_ID) return; // nothing to do

    function ensureFbqInitialized(id){
        try{
            if (window.fbq && typeof window.fbq === 'function') {
                return;
            }
            !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)
            }(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
            try{ fbq('init', id); }catch(e){}
        }catch(e){}
    }

    function sendPurchase(value, extra){
        try{
            var v = Number(value || 0);
            if (isNaN(v)) v = 0;
            // build base payload
            var payload = Object.assign({ value: Number(v).toFixed(2), currency: CURRENCY }, (extra||{}));
            if (window.fbq && typeof window.fbq === 'function') {
                try { fbq('track', 'Purchase', payload); }
                catch(e){ console.warn('fb-pixel: fbq track failed', e); }
            }
            // Also try sending to server-side CAPI endpoint (so server can add IP and user agent)
            try{
                if (typeof MYD_FB !== 'undefined' && MYD_FB.fbCapiUrl) {
                    maybeSendToCapi('Purchase', Number(v), payload);
                }
            }catch(e){ console.warn('fb-pixel: sending to CAPI failed', e); }
        }catch(e){ console.warn('fb-pixel: error sending purchase', e); }
    }

    function sendAddToCart(value, extra){
        try{
            var v = Number(value || 0);
            if (isNaN(v)) v = 0;
            var payload = Object.assign({ value: Number(v).toFixed(2), currency: CURRENCY }, (extra||{}));
            if (window.fbq && typeof window.fbq === 'function') {
                try { fbq('track', 'AddToCart', payload); }
                catch(e){ console.warn('fb-pixel: fbq AddToCart failed', e); }
            }
            try{
                if (typeof MYD_FB !== 'undefined' && MYD_FB.fbCapiUrl) {
                    maybeSendToCapi('AddToCart', Number(v), payload);
                }
            }catch(e){ console.warn('fb-pixel: sending AddToCart to CAPI failed', e); }
        }catch(e){ console.warn('fb-pixel: error sending AddToCart', e); }
    }

    function sendCustomizeProduct(value, extra){
        try{
            var v = Number(value || 0);
            if (isNaN(v)) v = 0;
            var payload = Object.assign({ value: Number(v).toFixed(2), currency: CURRENCY }, (extra||{}));
            if (window.fbq && typeof window.fbq === 'function') {
                try { fbq('track', 'CustomizeProduct', payload); }
                catch(e){ console.warn('fb-pixel: fbq CustomizeProduct failed', e); }
            }
            try{
                if (typeof MYD_FB !== 'undefined' && MYD_FB.fbCapiUrl) {
                    maybeSendToCapi('CustomizeProduct', Number(v), payload);
                }
            }catch(e){ console.warn('fb-pixel: sending CustomizeProduct to CAPI failed', e); }
        }catch(e){ console.warn('fb-pixel: error sending CustomizeProduct', e); }
    }

    function sendInitiateCheckout(value, extra){
        try{
            var v = Number(value || 0);
            if (isNaN(v)) v = 0;
            var payload = Object.assign({ value: Number(v).toFixed(2), currency: CURRENCY }, (extra||{}));
            if (window.fbq && typeof window.fbq === 'function') {
                try { fbq('track', 'InitiateCheckout', payload); }
                catch(e){ console.warn('fb-pixel: fbq InitiateCheckout failed', e); }
            }
            try{
                if (typeof MYD_FB !== 'undefined' && MYD_FB.fbCapiUrl) {
                    maybeSendToCapi('InitiateCheckout', Number(v), payload);
                }
            }catch(e){ console.warn('fb-pixel: sending InitiateCheckout to CAPI failed', e); }
        }catch(e){ console.warn('fb-pixel: error sending InitiateCheckout', e); }
    }

    function getCookie(name) {
        var v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
        return v ? v.pop() : '';
    }

    function maybeSendToCapi(eventName, value, payload) {
        try{
            var url = (typeof MYD_FB !== 'undefined' && MYD_FB.fbCapiUrl) ? MYD_FB.fbCapiUrl : null;
            var nonce = (typeof MYD_FB !== 'undefined' && MYD_FB.fbCapiNonce) ? MYD_FB.fbCapiNonce : null;
            if (!url) return;

            // Extract hashed user data keys from payload.custom_data if present
            var user_data = {};
            if (payload && payload.custom_data) {
                var cd = payload.custom_data;
                ['em','ph','fn','ln','name','ct','zp','customer_address','order_summary'].forEach(function(k){
                    if (typeof cd[k] !== 'undefined' && cd[k]) user_data[k] = cd[k];
                });
            }

            // Add fbp/fbc if available
            var fbp = getCookie('_fbp') || (payload && payload.fbp) || '';
            var fbc = getCookie('_fbc') || (payload && payload.fbc) || '';
            if (fbp) user_data['fbp'] = fbp;
            if (fbc) user_data['fbc'] = fbc;

            // Build custom_data for event (value, currency, contents)
            var custom_data = {};
            if (payload && payload.contents) custom_data.contents = payload.contents;
            if (payload && payload.content_type) custom_data.content_type = payload.content_type;
            custom_data.value = Number(value || 0);
            custom_data.currency = payload && payload.currency ? payload.currency : CURRENCY;

            var body = {
                event_name: eventName,
                event_time: Math.floor(Date.now()/1000),
                event_id: (payload && payload.order_id) ? String(payload.order_id) : String(Math.random()).replace('0.', '') + Date.now(),
                event_source_url: window.location.href,
                value: Number(value || 0),
                currency: custom_data.currency,
                contents: custom_data.contents,
                content_type: custom_data.content_type,
                custom_data: custom_data,
                user_data: user_data
            };

            var headers = {'Content-Type': 'application/json'};
            if (nonce) headers['X-WP-Nonce'] = nonce;

            fetch(url, { method: 'POST', headers: headers, body: JSON.stringify(body), credentials: 'include' })
                .then(function(res){ if (!res.ok) console.warn('fb-pixel: CAPI response not ok', res.status); return res.json().catch(function(){return null}); })
                .then(function(json){ /* optionally inspect response */ })
                .catch(function(err){ console.warn('fb-pixel: CAPI fetch failed', err); });
        }catch(e){ console.warn('fb-pixel: maybeSendToCapi error', e); }
    }

    // Hash helper using SubtleCrypto SHA-256, returns hex lowercase
    function hashStringSHA256(text){
        try{
            if (!text) return Promise.resolve('');
            var enc = new TextEncoder();
            var data = enc.encode(String(text).trim().toLowerCase());
            return crypto.subtle.digest('SHA-256', data).then(function(hashBuffer){
                var hashArray = Array.from(new Uint8Array(hashBuffer));
                return hashArray.map(b => ('00' + b.toString(16)).slice(-2)).join('');
            }).catch(function(){ return ''; });
        }catch(e){ return Promise.resolve(''); }
    }

    // Listen for the order completion event
    window.addEventListener('MydOrderComplete', function(ev){
        try{
            var total = null;
            if (ev && ev.detail && typeof ev.detail.orderTotal !== 'undefined') total = ev.detail.orderTotal;
            if (total === null && ev && typeof ev.orderTotal !== 'undefined') total = ev.orderTotal;

            // Try to collect richer order/customer info from several sources while MydOrder is still available
            var order = (window.MydOrder && typeof window.MydOrder === 'object') ? window.MydOrder : null;
            var sessionOrder = null;
            try{
                if (window.MydOrderSessionPersistence && typeof window.MydOrderSessionPersistence.getOrderSession === 'function'){
                    sessionOrder = window.MydOrderSessionPersistence.getOrderSession();
                    if (sessionOrder && sessionOrder.orderData) sessionOrder = sessionOrder.orderData;
                }
            }catch(e){ sessionOrder = null; }

            var orderId = (order && order.id) || (sessionOrder && (sessionOrder.id || sessionOrder.order_id)) || (document.getElementById('finished-order-number') && document.getElementById('finished-order-number').innerText) || null;

            var customerName = null;
            var customerPhone = null;
            var customerEmail = null;
            var customerCity = null;
            var customerZip = null;
            var addressParts = [];
            if (order) {
                if (order.customer) {
                    customerName = order.customer.name || order.customer.fullname || order.customer.firstName || customerName;
                    customerPhone = order.customer.phone || order.customer.phone_number || order.customer.telephone || customerPhone;
                    customerEmail = order.customer.email || order.customer.email_address || customerEmail;
                }
                if (order.shipping) {
                    var sh = order.shipping;
                    if (sh.address) addressParts.push(sh.address + (sh.address_number ? (', ' + sh.address_number) : ''));
                    if (sh.neighborhood) addressParts.push(sh.neighborhood);
                    if (sh.city) addressParts.push(sh.city + (sh.state ? (', ' + sh.state) : ''));
                    if (sh.zipcode) addressParts.push(sh.zipcode);
                    if (sh.city) customerCity = customerCity || sh.city;
                    if (sh.zipcode) customerZip = customerZip || sh.zipcode;
                }
                // fallback: sometimes address is on root
                if (!addressParts.length && order.address) {
                    addressParts.push(order.address + (order.address_number ? (', ' + order.address_number) : ''));
                    if (order.neighborhood) addressParts.push(order.neighborhood);
                    if (order.city) addressParts.push(order.city + (order.state ? (', ' + order.state) : ''));
                    if (order.city) customerCity = customerCity || order.city;
                    if (order.zipcode) customerZip = customerZip || order.zipcode;
                }
            }
            if ((!customerName || !customerPhone || !addressParts.length) && sessionOrder) {
                customerName = customerName || sessionOrder.customer_name || sessionOrder.customerName || sessionOrder.name || null;
                customerPhone = customerPhone || sessionOrder.customer_phone || sessionOrder.customerPhone || null;
                customerEmail = customerEmail || sessionOrder.customer_email || sessionOrder.email || (sessionOrder.user && sessionOrder.user.email) || null;
                if (!addressParts.length) {
                    var a = sessionOrder.address || sessionOrder.shipping_address || null;
                    if (a) addressParts.push(a);
                }
                customerCity = customerCity || sessionOrder.city || sessionOrder.shipping_city || null;
                customerZip = customerZip || sessionOrder.zipcode || sessionOrder.postcode || null;
            }

            var customerAddress = addressParts.join(', ');

            // Items: try different shapes
            var items = [];
            if (order && (order.items || order.cart || order.cartItems)) {
                var raw = order.items || order.cart || order.cartItems || [];
                if (Array.isArray(raw)) items = raw;
                else if (raw.items) items = raw.items;
            } else if (sessionOrder && (sessionOrder.items || sessionOrder.cart)) {
                items = sessionOrder.items || sessionOrder.cart || [];
            }

            // Build contents array for FB
            var contents = [];
            try{
                if (Array.isArray(items)){
                    contents = items.map(function(it){
                        var id = it.product_id || it.id || it.productId || it.sku || '';
                        var qty = parseInt(it.quantity || it.qty || it.quantity_item || it.q || 1) || 1;
                        var price = Number(it.product_price || it.price || it.unit_price || it.item_price || it.total || 0) || 0;
                        return { id: String(id), quantity: qty, item_price: Number(price).toFixed(2) };
                    });
                }
            }catch(e){ contents = []; }

            // Build a compact order summary string
            var orderSummary = '';
            try{
                orderSummary = contents.map(function(c){ return (c.id||'') + ' x' + c.quantity; }).join(', ');
            }catch(e){ orderSummary = ''; }

            if (total === null && order && typeof order.total !== 'undefined') total = order.total;
            if (total === null && sessionOrder && typeof sessionOrder.orderTotal !== 'undefined') total = sessionOrder.orderTotal;
            if (total === null) total = 0;

            ensureFbqInitialized(PIXEL_ID);

            // Prepare name parts for hashing (first/last)
            var firstName = null, lastName = null;
            try{
                if (customerName && typeof customerName === 'string'){
                    var parts = customerName.trim().split(/\s+/);
                    if (parts.length) firstName = parts[0];
                    if (parts.length > 1) lastName = parts.slice(1).join(' ');
                }
            }catch(e){}

            // Hash PII fields before sending: fn, ln, full name, phone, email, city, zip, address, orderSummary
            Promise.all([
                hashStringSHA256(firstName),
                hashStringSHA256(lastName),
                hashStringSHA256(customerName),
                hashStringSHA256(customerPhone),
                hashStringSHA256(customerEmail),
                hashStringSHA256(customerCity),
                hashStringSHA256(customerZip),
                hashStringSHA256(customerAddress),
                hashStringSHA256(orderSummary)
            ]).then(function(hashes){
                var hFn = hashes[0] || undefined;
                var hLn = hashes[1] || undefined;
                var hName = hashes[2] || undefined;
                var hPhone = hashes[3] || undefined;
                var hEmail = hashes[4] || undefined;
                var hCity = hashes[5] || undefined;
                var hZip = hashes[6] || undefined;
                var hAddress = hashes[7] || undefined;
                var hSummary = hashes[8] || undefined;

                var extra = {
                    order_id: orderId || undefined,
                    contents: contents.length ? contents : undefined,
                    content_type: contents.length ? 'product' : undefined,
                    custom_data: {
                        // hashed values (SHA-256 hex lowercase) mapped to friendly keys
                        fn: hFn,
                        ln: hLn,
                        name: hName,
                        ph: hPhone,
                        em: hEmail,
                        ct: hCity,
                        zp: hZip,
                        customer_address: hAddress,
                        order_summary: hSummary
                    }
                };
                sendPurchase(total, extra);
            }).catch(function(err){
                // fallback: send without hashed PII if something fails
                console.warn('fb-pixel: hashing failed, sending without hashed PII', err);
                var extra = {
                    order_id: orderId || undefined,
                    contents: contents.length ? contents : undefined,
                    content_type: contents.length ? 'product' : undefined,
                };
                sendPurchase(total, extra);
            });
        }catch(e){ console.warn('fb-pixel handler error', e); }
    }, false);

    // Listen for product additions to cart
    window.addEventListener('MydAddedToCart', function(ev){
        try{
            var product = null;
            if (ev && ev.detail && ev.detail.product) product = ev.detail.product;
            // fallback: try reading last item from window.MydCart or session
            if (!product) {
                try{
                    if (window.MydCart && window.MydCart.items && window.MydCart.items.length) product = window.MydCart.items[window.MydCart.items.length - 1];
                }catch(e){}
            }

            if (!product && window.MydOrderSessionPersistence && typeof window.MydOrderSessionPersistence.getOrderSession === 'function'){
                try{
                    var s = window.MydOrderSessionPersistence.getOrderSession();
                    if (s && s.orderData && s.orderData.cart && Array.isArray(s.orderData.cart) && s.orderData.cart.length) {
                        product = s.orderData.cart[s.orderData.cart.length - 1];
                    }
                }catch(e){}
            }

            if (!product) return;

            var prodId = product.product_id || product.id || product.productId || product.sku || '';
            var prodName = product.name || product.product_name || product.title || '';
            var qty = parseInt(product.quantity || product.qty || 1) || 1;
            var price = Number(product.product_price || product.price || product.unit_price || product.total || 0) || 0;

            // try to collect customer PII for hashing (if available)
            var customerName = (window.MydOrder && window.MydOrder.customer && (window.MydOrder.customer.name || window.MydOrder.customer.fullname)) || null;
            var customerPhone = (window.MydOrder && window.MydOrder.customer && (window.MydOrder.customer.phone || window.MydOrder.customer.phone_number)) || null;
            var customerEmail = (window.MydOrder && window.MydOrder.customer && (window.MydOrder.customer.email || window.MydOrder.customer.email_address)) || null;

            // prepare first/last name
            var firstName = null, lastName = null;
            try{
                if (customerName && typeof customerName === 'string'){
                    var parts = customerName.trim().split(/\s+/);
                    if (parts.length) firstName = parts[0];
                    if (parts.length > 1) lastName = parts.slice(1).join(' ');
                }
            }catch(e){}

            // Hash PII then send AddToCart
            Promise.all([ hashStringSHA256(firstName), hashStringSHA256(lastName), hashStringSHA256(customerName), hashStringSHA256(customerPhone), hashStringSHA256(customerEmail) ]).then(function(hashes){
                var hFn = hashes[0] || undefined;
                var hLn = hashes[1] || undefined;
                var hName = hashes[2] || undefined;
                var hPhone = hashes[3] || undefined;
                var hEmail = hashes[4] || undefined;
                    var contents = [{ id: String(prodId), quantity: qty, item_price: Number(price).toFixed(2) }];

                    // Detect paid extras (customizations) on the product and compute extras total
                    var hasPaidExtras = false;
                    var extrasTotal = 0;
                    try{
                        if (product && product.extras && Array.isArray(product.extras.groups)){
                            product.extras.groups.forEach(function(g){
                                if (!g || !Array.isArray(g.items)) return;
                                g.items.forEach(function(it){
                                    var itQty = parseInt(it.quantity || it.qty || 0) || 0;
                                    var itPrice = Number(it.price || it.unit_price || it.value || 0) || 0;
                                    if (itQty > 0 && itPrice > 0) {
                                        hasPaidExtras = true;
                                        extrasTotal += itQty * itPrice;
                                    }
                                });
                            });
                        }
                    }catch(e){ hasPaidExtras = false; extrasTotal = 0; }

                    var extra = {
                        contents: contents,
                        content_type: 'product',
                        content_name: prodName || undefined,
                        custom_data: {
                            fn: hFn,
                            ln: hLn,
                            name: hName,
                            ph: hPhone,
                            em: hEmail
                        }
                    };
                    if (hasPaidExtras) {
                        extra.custom_data.CustomizeProduct = true;
                    }
                    sendAddToCart(price * qty, extra);
                    if (hasPaidExtras) {
                        try{ sendCustomizeProduct(extrasTotal, extra); }catch(e){}
                    }
            }).catch(function(){
                var contents = [{ id: String(prodId), quantity: qty, item_price: Number(price).toFixed(2) }];
                var extra = { contents: contents, content_type: 'product', content_name: prodName || undefined };
                try{
                    var hasPaidExtrasFallback = false;
                    var extrasTotalFallback = 0;
                    if (product && product.extras && Array.isArray(product.extras.groups)){
                        product.extras.groups.forEach(function(g){
                            if (!g || !Array.isArray(g.items)) return;
                            g.items.forEach(function(it){
                                var itQty = parseInt(it.quantity || it.qty || 0) || 0;
                                var itPrice = Number(it.price || it.unit_price || it.value || 0) || 0;
                                if (itQty > 0 && itPrice > 0) {
                                    hasPaidExtrasFallback = true;
                                    extrasTotalFallback += itQty * itPrice;
                                }
                            });
                        });
                    }
                    if (hasPaidExtrasFallback) extra.custom_data = extra.custom_data || {}, extra.custom_data.CustomizeProduct = true;
                }catch(e){}
                sendAddToCart(price * qty, extra);
                if (hasPaidExtrasFallback) {
                    try{ sendCustomizeProduct(extrasTotalFallback, extra); }catch(e){}
                }
            });

        }catch(e){ console.warn('fb-pixel AddToCart handler error', e); }
    }, false);

    // Removed duplicate MydDraftOrderCreated InitiateCheckout sender to avoid double events.

    // Also listen for HTTP-OK draft creation (fires as soon as admin-ajax returns 200)
    window.addEventListener('MydDraftOrderHttpOk', function(ev){
        try{
            var payloadDetail = ev && ev.detail ? ev.detail : ev;
            var orderObj = (payloadDetail && payloadDetail.currentOrder) ? payloadDetail.currentOrder : payloadDetail;
            if (!orderObj) return;

            var total = null;
            if (orderObj && typeof orderObj.total !== 'undefined') total = orderObj.total;
            if (total === null) total = 0;

            // build contents from order/cart
            var items = [];
            if (orderObj && (orderObj.items || orderObj.cart || orderObj.cartItems)) {
                var raw = orderObj.items || orderObj.cart || orderObj.cartItems || [];
                if (Array.isArray(raw)) items = raw;
                else if (raw.items) items = raw.items;
            }

            var contents = [];
            try{
                if (Array.isArray(items)){
                    contents = items.map(function(it){
                        var id = it.product_id || it.id || it.productId || it.sku || '';
                        var qty = parseInt(it.quantity || it.qty || it.quantity_item || it.q || 1) || 1;
                        var price = Number(it.product_price || it.price || it.unit_price || it.item_price || it.total || 0) || 0;
                        return { id: String(id), quantity: qty, item_price: Number(price).toFixed(2) };
                    });
                }
            }catch(e){ contents = []; }

            var extra = {
                contents: contents.length ? contents : undefined,
                content_type: contents.length ? 'product' : undefined
            };

            sendInitiateCheckout(total, extra);
        }catch(e){ console.warn('fb-pixel InitiateCheckout (HTTP OK) handler error', e); }
    }, false);
})();
