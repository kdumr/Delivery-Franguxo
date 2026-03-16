'use strict';
require('dotenv').config();

const express   = require('express');
const fs        = require('fs');
const path      = require('path');
const https     = require('https');
const crypto    = require('crypto');

const { pollEvents, acknowledgeEvents, processEvent } = require('./ifood-orders');

// ─── Config ──────────────────────────────────────────────────────────────────
const PORT           = process.env.PORT           || 3001;
const USE_HTTPS      = process.env.USE_HTTPS      === 'true';
const BACKEND_SECRET = process.env.BACKEND_SECRET || '';

let IFOOD_CLIENT_ID     = process.env.IFOOD_CLIENT_ID     || '';
let IFOOD_CLIENT_SECRET = process.env.IFOOD_CLIENT_SECRET || '';
let IFOOD_MERCHANT_ID   = process.env.IFOOD_MERCHANT_ID   || '';
let WP_BASE_URL         = process.env.WP_BASE_URL         || '';
let WP_API_SECRET       = process.env.WP_API_SECRET       || '';

// ─── Persistent config ────────────────────────────────────────────────────────
const CONFIG_PATH = path.join(__dirname, 'config.json');

function loadConfig() {
  try {
    if (fs.existsSync(CONFIG_PATH)) {
      const cfg = JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf8'));
      if (cfg.merchantId)   IFOOD_MERCHANT_ID   = cfg.merchantId;
      if (cfg.wpApiSecret)  WP_API_SECRET       = cfg.wpApiSecret;
      if (cfg.wpBaseUrl)    WP_BASE_URL         = cfg.wpBaseUrl;
      if (cfg.clientId)     IFOOD_CLIENT_ID     = cfg.clientId;
      if (cfg.clientSecret) IFOOD_CLIENT_SECRET = cfg.clientSecret;
      console.log('[Config] Loaded from config.json ✓');
    }
  } catch (e) {
    console.warn('[Config] Could not load config.json:', e.message);
  }
}

function saveConfig() {
  try {
    fs.writeFileSync(CONFIG_PATH, JSON.stringify({
      merchantId:   IFOOD_MERCHANT_ID,
      wpApiSecret:  WP_API_SECRET,
      wpBaseUrl:    WP_BASE_URL,
      clientId:     IFOOD_CLIENT_ID,
      clientSecret: IFOOD_CLIENT_SECRET,
    }, null, 2));
    console.log('[Config] Saved to config.json ✓');
  } catch (e) {
    console.error('[Config] Save failed:', e.message);
  }
}

loadConfig();

// ─── Express ──────────────────────────────────────────────────────────────────
const app = express();
app.use(express.json({
  verify: (req, _res, buf) => { req.rawBody = buf; }
}));

// ── Webhook iFood (primário) ──────────────────────────────────────────────────
app.post('/ifood/webhook', async (req, res) => {
  // Respond 202 immediately — iFood expects fast response
  res.status(202).send();

  const signature = req.headers['x-ifood-signature'];

  if (!signature) {
    console.warn('[Webhook] Missing X-iFood-Signature — ignoring');
    lastWebhookAt = Date.now();
    return;
  }

  if (!IFOOD_CLIENT_SECRET) {
    console.warn('[Webhook] Client secret not configured — cannot validate signature');
    lastWebhookAt = Date.now();
  } else {
    const hmac = crypto.createHmac('sha256', IFOOD_CLIENT_SECRET)
      .update(req.rawBody)
      .digest('hex');

    if (hmac !== signature) {
      console.warn('[Webhook] Invalid HMAC — ignoring');
      return;
    }
  }

  lastWebhookAt = Date.now();

  const events = Array.isArray(req.body) ? req.body : [req.body];
  console.log(`[Webhook] ${events.length} event(s) received`);

  for (const event of events) {
    await processEvent(event, {
      clientId: IFOOD_CLIENT_ID,
      clientSecret: IFOOD_CLIENT_SECRET,
      wpUrl: WP_BASE_URL,
      wpSecret: WP_API_SECRET,
    }).catch(e => console.error('[Webhook] processEvent error:', e.message));
  }

  acknowledgeEvents(events, IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET)
    .catch(e => console.error('[Webhook] ACK error:', e.message));
});

// ── Config Push (from WordPress when settings are saved) ─────────────────────
app.post('/config', (req, res) => {
  const incomingSecret = req.headers['x-backend-secret'] || req.body?.backendSecret;
  if (!BACKEND_SECRET || incomingSecret !== BACKEND_SECRET) {
    console.warn('[Config] Unauthorized push attempt');
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const { merchantId, wpApiSecret, wpBaseUrl, clientId, clientSecret } = req.body || {};
  if (merchantId)   IFOOD_MERCHANT_ID   = merchantId;
  if (wpApiSecret)  WP_API_SECRET       = wpApiSecret;
  if (wpBaseUrl)    WP_BASE_URL         = wpBaseUrl;
  if (clientId)     IFOOD_CLIENT_ID     = clientId;
  if (clientSecret) IFOOD_CLIENT_SECRET = clientSecret;

  saveConfig();
  console.log(`[Config] Updated — merchantId=${IFOOD_MERCHANT_ID} wpBaseUrl=${WP_BASE_URL}`);
  res.json({ success: true, merchantId: IFOOD_MERCHANT_ID });
});

// ── Status ────────────────────────────────────────────────────────────────────
app.get('/health', (_req, res) => res.json({ status: 'ok' }));

app.get('/ifood/status', (req, res) => {
  const secret = req.headers['x-backend-secret'];
  if (BACKEND_SECRET && secret !== BACKEND_SECRET) return res.status(401).json({ error: 'Unauthorized' });

  res.json({
    merchantId:             IFOOD_MERCHANT_ID || null,
    wpBaseUrl:              WP_BASE_URL       || null,
    credentialsConfigured:  !!(IFOOD_CLIENT_ID && IFOOD_CLIENT_SECRET),
    wpConfigured:           !!(WP_BASE_URL && WP_API_SECRET),
    polling: {
      lastPollAt:    lastPollAt    ? new Date(lastPollAt).toISOString()    : null,
      lastWebhookAt: lastWebhookAt ? new Date(lastWebhookAt).toISOString() : null,
    },
  });
});

// ─── Polling (fallback) ───────────────────────────────────────────────────────
const POLL_INTERVAL_MS = 30 * 1000;
let lastPollAt    = 0;
let lastWebhookAt = 0;

async function runPollCycle() {
  if (!IFOOD_CLIENT_ID || !IFOOD_CLIENT_SECRET) return;

  lastPollAt = Date.now();
  try {
    const events = await pollEvents(IFOOD_MERCHANT_ID, IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET);
    if (!events || events.length === 0) return;

    console.log(`[Polling] ${events.length} event(s) received`);

    for (const event of events) {
      await processEvent(event, {
        clientId: IFOOD_CLIENT_ID,
        clientSecret: IFOOD_CLIENT_SECRET,
        wpUrl: WP_BASE_URL,
        wpSecret: WP_API_SECRET,
      }).catch(e => console.error('[Polling] processEvent error:', e.message));
    }

    await acknowledgeEvents(events, IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET);
  } catch (err) {
    console.error('[Polling] Error:', err.message);
  }
}

// ─── Start ────────────────────────────────────────────────────────────────────
let httpsOptions = {};
if (USE_HTTPS) {
  httpsOptions = { key: fs.readFileSync('key.pem'), cert: fs.readFileSync('cert.pem') };
}

const server = USE_HTTPS
  ? https.createServer(httpsOptions, app)
  : require('http').createServer(app);

server.listen(PORT, '0.0.0.0', () => {
  console.log(`\n🚀 iFood Backend running on ${USE_HTTPS ? 'https' : 'http'}://0.0.0.0:${PORT}`);
  console.log(`   Webhook URL: http://YOUR-SERVER:${PORT}/ifood/webhook`);
  console.log(`   Config push: POST http://YOUR-SERVER:${PORT}/config`);
  console.log(`   Status:      GET  http://YOUR-SERVER:${PORT}/ifood/status\n`);

  // Start polling loop — also keeps merchant online on iFood
  setInterval(runPollCycle, POLL_INTERVAL_MS);
  runPollCycle();
});

process.on('unhandledRejection', (reason) => {
  console.error('[UnhandledRejection]', reason?.stack || reason);
});
