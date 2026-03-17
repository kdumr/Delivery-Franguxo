'use strict';
require('dotenv').config();

const express = require('express');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const { pollEvents, acknowledgeEvents } = require('./src/ifood-client');
const { processEvent } = require('./src/order-processor');

// ─── Configuration ─────────────────────────────────────────────────────────
const PORT = process.env.PORT || 3000;

// Secret that WordPress must send to push config updates to this backend
const BACKEND_SECRET = process.env.BACKEND_SECRET || 'change-me-in-dotenv';

// iFood credentials — set via env OR pushed by WordPress at runtime
let IFOOD_CLIENT_ID     = process.env.IFOOD_CLIENT_ID || '';
let IFOOD_CLIENT_SECRET = process.env.IFOOD_CLIENT_SECRET || '';
let IFOOD_MERCHANT_ID   = process.env.IFOOD_MERCHANT_ID || '';

// WordPress connection
let WP_BASE_URL  = process.env.WP_BASE_URL || '';   // e.g. https://franguxo.app.br
let WP_API_SECRET = process.env.WP_API_SECRET || ''; // X-WP-Secret header value

// ─── Persistent config (survives restarts) ──────────────────────────────────
const CONFIG_PATH = path.join(__dirname, 'data', 'config.json');

function loadConfig() {
  try {
    if (fs.existsSync(CONFIG_PATH)) {
      const cfg = JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf8'));
      if (cfg.clientId)      IFOOD_CLIENT_ID     = cfg.clientId;
      if (cfg.clientSecret)  IFOOD_CLIENT_SECRET = cfg.clientSecret;
      if (cfg.merchantId)    IFOOD_MERCHANT_ID   = cfg.merchantId;
      if (cfg.wpBaseUrl)     WP_BASE_URL         = cfg.wpBaseUrl;
      if (cfg.wpApiSecret)   WP_API_SECRET       = cfg.wpApiSecret;
      console.log('[Config] Loaded from config.json:', Object.keys(cfg).filter(k => cfg[k]).join(', '));
    } else {
      console.log('[Config] No config.json found — starting with env variables only.');
    }
  } catch (e) {
    console.warn('[Config] Failed to load config.json:', e.message);
  }
}

// Ensure it loads at boot time!
loadConfig();

function saveConfig() {
  try {
    const dir = path.dirname(CONFIG_PATH);
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    fs.writeFileSync(CONFIG_PATH, JSON.stringify({
      clientId: IFOOD_CLIENT_ID,
      clientSecret: IFOOD_CLIENT_SECRET,
      merchantId: IFOOD_MERCHANT_ID,
      wpBaseUrl: WP_BASE_URL,
      wpApiSecret: WP_API_SECRET,
    }, null, 2));
    console.log('[Config] Saved.');
  } catch (e) {
    console.error('[Config] Save failed:', e.message);
  }
}

loadConfig();

// ─── Express App ────────────────────────────────────────────────────────────
const app = express();

// Parse JSON but also keep raw body for HMAC validation
app.use(express.json({
  verify: (req, _res, buf) => { req.rawBody = buf; },
}));

// ─── Health Check ───────────────────────────────────────────────────────────
app.get('/health', (_req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString(),
    credentialsOk: !!(IFOOD_CLIENT_ID && IFOOD_CLIENT_SECRET && IFOOD_MERCHANT_ID),
    wpConfigured: !!(WP_BASE_URL && WP_API_SECRET),
    polling: {
      active: !!pollingTimer,
      lastPollAt: lastPollAt ? new Date(lastPollAt).toISOString() : null,
      lastWebhookAt: lastWebhookAt ? new Date(lastWebhookAt).toISOString() : null,
    },
  });
});

// ─── Config Push (WordPress → Backend) ─────────────────────────────────────
// WordPress calls this whenever the admin saves iFood settings
app.post('/config', (req, res) => {
  const secret = req.headers['x-backend-secret'];
  if (!secret || secret !== BACKEND_SECRET) {
    console.warn('[Config] Unauthorized push attempt');
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const { clientId, clientSecret, merchantId, wpBaseUrl, wpApiSecret } = req.body || {};

  if (clientId)      IFOOD_CLIENT_ID     = clientId;
  if (clientSecret)  IFOOD_CLIENT_SECRET = clientSecret;
  if (merchantId)    IFOOD_MERCHANT_ID   = merchantId;
  if (wpBaseUrl)     WP_BASE_URL         = wpBaseUrl;
  if (wpApiSecret)   WP_API_SECRET        = wpApiSecret;

  saveConfig();
  console.log(`[Config] Updated: merchantId=${IFOOD_MERCHANT_ID} wpBaseUrl=${WP_BASE_URL}`);
  res.json({ success: true });
});

// ─── iFood Webhook (Primary method) ─────────────────────────────────────────
// Registered in iFood Developer Portal → My Apps → Webhook URL
app.post('/ifood/webhook', async (req, res) => {
  // iFood expects a 202 response within 2 seconds — respond first, process async
  res.status(202).send();

  lastWebhookAt = Date.now();

  // Validate HMAC signature
  const signature = req.headers['x-ifood-signature'];
  if (signature && IFOOD_CLIENT_SECRET) {
    const expected = crypto
      .createHmac('sha256', IFOOD_CLIENT_SECRET)
      .update(req.rawBody)
      .digest('hex');

    if (expected !== signature) {
      console.warn('[Webhook] Invalid HMAC signature — ignoring payload');
      return;
    }
  } else if (!signature) {
    console.warn('[Webhook] No signature header — processing anyway (dev mode)');
  }

  const events = Array.isArray(req.body) ? req.body : [req.body];
  console.log(`[Webhook] Received ${events.length} event(s)`);

  // Sort events by createdAt (iFood may deliver out of order)
  events.sort((a, b) => new Date(a.createdAt || 0) - new Date(b.createdAt || 0));

  if (events.length > 0) {
    console.log(`[Webhook] Event #0 Debug Dump:`, JSON.stringify(events[0], null, 2));
  }

  const successfulEvents = [];

  for (const event of events) {
    try {
      const success = await processEvent(event, getIfoodConfig());
      if (success !== false) { 
        // We consider undefined (non-order events) or true as successfully handled
        successfulEvents.push(event);
      }
    } catch (e) {
      console.error('[Webhook] processEvent error:', e.message);
    }
  }

  // Acknowledge only events that were successfully processed
  if (successfulEvents.length > 0) {
    acknowledgeEvents(successfulEvents, IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET).catch((e) =>
      console.error('[Webhook] ACK error:', e.message)
    );
  } else {
    console.log('[Webhook] No events to acknowledge.');
  }
});

// ─── Direct Confirm Order (WordPress → Backend → iFood) ─────────────────────
app.post('/ifood/confirm', async (req, res) => {
  const secret = req.headers['x-backend-secret'];
  if (!secret || secret !== BACKEND_SECRET) {
    console.warn('[Confirm] Unauthorized attempt');
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const { orderId } = req.body || {};
  if (!orderId) {
    return res.status(400).json({ error: 'Missing orderId parameter' });
  }

  try {
    const { confirmOrder } = require('./src/ifood-client');
    await confirmOrder(orderId, IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET);
    res.json({ success: true, message: `Order ${orderId} confirmed successfully.` });
  } catch (err) {
    const status = err.response ? err.response.status : 500;
    const msg = err.response ? JSON.stringify(err.response.data) : err.message;
    res.status(status).json({ success: false, error: msg });
  }
});

// ─── Helper: build config object ────────────────────────────────────────────
function getIfoodConfig() {
  return {
    clientId: IFOOD_CLIENT_ID,
    clientSecret: IFOOD_CLIENT_SECRET,
    merchantId: IFOOD_MERCHANT_ID,
    wpUrl: WP_BASE_URL,
    wpSecret: WP_API_SECRET,
  };
}

// ─── Polling (Fallback) ──────────────────────────────────────────────────────
// Runs every 30s. Also keeps the merchant ONLINE in iFood.
// Only polls when webhooks have been silent for > 60 seconds.
const POLL_INTERVAL_MS   = 30 * 1000;
const WEBHOOK_SILENCE_MS = 60 * 1000; // treat webhooks as "missing" after 60s silence

let pollingTimer   = null;
let lastPollAt     = 0;
let lastWebhookAt  = 0;

async function runPollCycle() {
  if (!IFOOD_CLIENT_ID || !IFOOD_CLIENT_SECRET || !IFOOD_MERCHANT_ID) return;

  lastPollAt = Date.now();

  // If webhooks are flowing, skip processing but still poll to maintain ONLINE status
  const webhooksActive = Date.now() - lastWebhookAt < WEBHOOK_SILENCE_MS;

  try {
    const events = await pollEvents(IFOOD_MERCHANT_ID, IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET);

    if (!events || events.length === 0) return;

    if (webhooksActive) {
      console.log(`[Polling] Webhooks active — ${events.length} event(s) skipped (already handled by webhook)`);
      // Still acknowledge to keep the queue clean
      await acknowledgeEvents(events, IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET);
      return;
    }

    console.log(`[Polling] FALLBACK — processing ${events.length} event(s)`);

    events.sort((a, b) => new Date(a.createdAt || 0) - new Date(b.createdAt || 0));

    const successfulEvents = [];

    for (const event of events) {
      try {
        const success = await processEvent(event, getIfoodConfig());
        if (success !== false) {
          successfulEvents.push(event);
        }
      } catch (e) {
        console.error('[Polling] processEvent error:', e.message);
      }
    }

    if (successfulEvents.length > 0) {
      await acknowledgeEvents(successfulEvents, IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET);
    } else {
      console.log('[Polling] No events to acknowledge.');
    }

  } catch (err) {
    console.error('[Polling] Error:', err.message);
  }
}

function startPolling() {
  if (pollingTimer) return;
  console.log(`[Polling] Starting — interval=${POLL_INTERVAL_MS / 1000}s`);
  pollingTimer = setInterval(runPollCycle, POLL_INTERVAL_MS);
  runPollCycle(); // immediate first run
}

// ─── Start Server ────────────────────────────────────────────────────────────
app.listen(PORT, '0.0.0.0', () => {
  console.log(`[Server] iFood Backend running on http://0.0.0.0:${PORT}`);
  console.log(`[Server] WordPress target: ${WP_BASE_URL || '(not configured)'}`);
  console.log(`[Server] iFood merchant:   ${IFOOD_MERCHANT_ID || '(not configured)'}`);
  startPolling();
});

process.on('unhandledRejection', (reason) => {
  console.error('[UnhandledRejection]', reason?.stack || reason);
});

process.on('uncaughtException', (err) => {
  console.error('[UncaughtException]', err.stack || err);
});
