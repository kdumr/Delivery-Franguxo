require('dotenv').config();
const express = require('express');
const cors = require('cors');
const https = require('https');
const fs = require('fs');
const path = require('path');
const jwt = require('jsonwebtoken');
const { Server } = require('socket.io');
const mysql = require('mysql2/promise');
const dns = require('dns').promises;
const net = require('net');
const crypto = require('crypto');

const { pollEvents, acknowledgeEvents, processEvent } = require('./ifood-orders');

// ─── Config ─────────────────────────────────────────────────────────────────
const SECRET_KEY = process.env.SECRET_KEY || 'your-super-secret-key-change-this-in-production';
const BACKEND_SECRET = process.env.BACKEND_SECRET || 'backend-config-secret-change-me'; // WP → backend
const PORT = process.env.PORT || 80;
const USE_HTTPS = process.env.USE_HTTPS === 'true';
const CORS_ORIGIN = process.env.CORS_ORIGIN || '*';

// iFood credentials from env (base values; can be overridden by WordPress push)
let IFOOD_CLIENT_ID = process.env.IFOOD_CLIENT_ID || '';
let IFOOD_CLIENT_SECRET = process.env.IFOOD_CLIENT_SECRET || '';

// WordPress destination
let WP_BASE_URL = process.env.WP_WEBHOOK_URL || '';  // e.g. https://franguxo.app.br
let WP_API_SECRET = process.env.WP_API_SECRET || '';

// iFood Merchant ID — pushed by WordPress when settings are saved
let IFOOD_MERCHANT_ID = process.env.IFOOD_MERCHANT_ID || '';

// ─── Persistent config (survives restarts) ──────────────────────────────────
const CONFIG_PATH = path.join(__dirname, 'config.json');

function loadConfig() {
  try {
    if (fs.existsSync(CONFIG_PATH)) {
      const raw = fs.readFileSync(CONFIG_PATH, 'utf8');
      const cfg = JSON.parse(raw);
      if (cfg.merchantId) IFOOD_MERCHANT_ID = cfg.merchantId;
      if (cfg.wpApiSecret) WP_API_SECRET = cfg.wpApiSecret;
      if (cfg.wpBaseUrl) WP_BASE_URL = cfg.wpBaseUrl;
      if (cfg.clientId) IFOOD_CLIENT_ID = cfg.clientId;
      if (cfg.clientSecret) IFOOD_CLIENT_SECRET = cfg.clientSecret;
      console.log('[Config] Loaded from config.json');
    }
  } catch (e) {
    console.warn('[Config] Could not load config.json:', e.message);
  }
}

function saveConfig() {
  try {
    const cfg = {
      merchantId: IFOOD_MERCHANT_ID,
      wpApiSecret: WP_API_SECRET,
      wpBaseUrl: WP_BASE_URL,
      clientId: IFOOD_CLIENT_ID,
      clientSecret: IFOOD_CLIENT_SECRET,
    };
    fs.writeFileSync(CONFIG_PATH, JSON.stringify(cfg, null, 2));
    console.log('[Config] Saved to config.json');
  } catch (e) {
    console.error('[Config] Could not save config.json:', e.message);
  }
}

loadConfig();

// ─── Express / HTTP ──────────────────────────────────────────────────────────
let options = {};
if (USE_HTTPS) {
  options = {
    key: fs.readFileSync('key.pem'),
    cert: fs.readFileSync('cert.pem'),
  };
}

const app = express();
app.use(cors());
app.use(express.json({
  verify: (req, res, buf) => { req.rawBody = buf; }
}));

// ─── JWT Middleware ──────────────────────────────────────────────────────────
function verifyToken(req, res, next) {
  const token = req.headers['authorization']?.split(' ')[1];
  if (!token) return res.status(401).json({ error: 'No token provided' });

  jwt.verify(token, SECRET_KEY, (err, decoded) => {
    if (err) {
      const ip = req.headers['x-forwarded-for'] || req.socket.remoteAddress || req.ip;
      console.warn(`[HTTP Auth Fail] Invalid token from ${ip}: ${err.message}`);
      return res.status(403).json({ error: 'Invalid token' });
    }
    req.user = decoded;
    next();
  });
}

// ─── Routes ──────────────────────────────────────────────────────────────────

// Token for internal clients (WP, front-end dashboard)
app.post('/auth', (req, res) => {
  const { myd_customer_id } = req.body;
  if (!myd_customer_id && myd_customer_id !== 0) return res.status(400).json({ error: 'myd_customer_id required' });
  const token = jwt.sign({ myd_customer_id }, SECRET_KEY, { expiresIn: '24h' });
  res.json({ token });
});

// Notify via Socket.io (called by WordPress)
app.post('/notify', verifyToken, (req, res) => {
  const { myd_customer_id, order_id, status } = req.body || {};
  try {
    if (myd_customer_id) io.to(`user-${myd_customer_id}`).emit('order.updated', { order_id, status });
    io.to('admins').emit('order.status', { order_id, status });
    res.json({ success: true });
  } catch (e) {
    res.status(500).json({ error: 'emit failed' });
  }
});

// Store status broadcast
app.post('/notify/store', verifyToken, (req, res) => {
  const { open, force } = req.body || {};
  if (typeof open === 'undefined') return res.status(400).json({ error: 'open required' });
  const payload = { open: !!open };
  if (force && ['ignore', 'open', 'close'].includes(force)) payload.force = force;
  io.emit('store.status', payload);
  res.json({ success: true, emitted: payload });
});

// ── iFood Webhook (primary) ──────────────────────────────────────────────────
app.post('/ifood/webhook', async (req, res) => {
  // Respond fast — iFood expects 202 quickly
  res.status(202).send();

  const signature = req.headers['x-ifood-signature'];
  if (!signature || !IFOOD_CLIENT_SECRET) {
    console.warn('[iFood Webhook] Missing signature or client secret');
    return;
  }

  const hmac = crypto.createHmac('sha256', IFOOD_CLIENT_SECRET)
    .update(req.rawBody)
    .digest('hex');

  if (hmac !== signature) {
    console.warn('[iFood Webhook] Invalid HMAC signature — ignoring');
    lastWebhookAt = Date.now(); // still count as "webhook received" to suppress polling
    return;
  }

  const events = Array.isArray(req.body) ? req.body : [req.body];
  console.log(`[iFood Webhook] Received ${events.length} event(s)`);

  lastWebhookAt = Date.now(); // suppress polling while webhooks are working

  for (const event of events) {
    await processEvent(event, {
      clientId: IFOOD_CLIENT_ID,
      clientSecret: IFOOD_CLIENT_SECRET,
      wpUrl: WP_BASE_URL,
      wpSecret: WP_API_SECRET,
      io,
    }).catch(e => console.error('[iFood Webhook] processEvent error:', e.message));
  }

  // Acknowledge after processing
  acknowledgeEvents(events, IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET)
    .catch(e => console.error('[iFood Webhook] ACK error:', e.message));
});

// ── Configuration Push (called by WordPress when settings are saved) ─────────
app.post('/config', (req, res) => {
  const secret = req.headers['x-backend-secret'] || req.body?.backendSecret;
  if (!BACKEND_SECRET || secret !== BACKEND_SECRET) {
    console.warn('[Config] Unauthorized config push attempt');
    return res.status(401).json({ error: 'Unauthorized' });
  }

  const { merchantId, wpApiSecret, wpBaseUrl, clientId, clientSecret } = req.body || {};

  if (merchantId) IFOOD_MERCHANT_ID = merchantId;
  if (wpApiSecret) WP_API_SECRET = wpApiSecret;
  if (wpBaseUrl) WP_BASE_URL = wpBaseUrl;
  if (clientId) IFOOD_CLIENT_ID = clientId;
  if (clientSecret) IFOOD_CLIENT_SECRET = clientSecret;

  saveConfig();

  console.log(`[Config] Updated — merchantId=${IFOOD_MERCHANT_ID} wpBaseUrl=${WP_BASE_URL}`);
  res.json({ success: true, merchantId: IFOOD_MERCHANT_ID });
});

// ── iFood Status ─────────────────────────────────────────────────────────────
app.get('/ifood/status', verifyToken, (req, res) => {
  res.json({
    merchantId: IFOOD_MERCHANT_ID || null,
    wpBaseUrl: WP_BASE_URL || null,
    polling: {
      active: !!pollingTimer,
      lastPollAt: lastPollAt ? new Date(lastPollAt).toISOString() : null,
      lastWebhookAt: lastWebhookAt ? new Date(lastWebhookAt).toISOString() : null,
    },
    credentialsConfigured: !!(IFOOD_CLIENT_ID && IFOOD_CLIENT_SECRET),
  });
});

// ┌────────────────────────────────────────────────────────────────────────────┐
// │ iFood Polling (fallback)                                                   │
// └────────────────────────────────────────────────────────────────────────────┘
const POLL_INTERVAL_MS = 30 * 1000; // 30 seconds
const WEBHOOK_TIMEOUT_MS = 60 * 1000; // consider webhooks "working" if received in last 60s

let pollingTimer = null;
let lastPollAt = 0;
let lastWebhookAt = 0;

async function runPollCycle() {
  if (!IFOOD_CLIENT_ID || !IFOOD_CLIENT_SECRET) {
    // Credentials not configured yet — skip silently
    return;
  }

  lastPollAt = Date.now();

  try {
    const events = await pollEvents(IFOOD_MERCHANT_ID, IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET);

    if (!events || events.length === 0) return;

    console.log(`[iFood Polling] ${events.length} event(s) received`);

    for (const event of events) {
      await processEvent(event, {
        clientId: IFOOD_CLIENT_ID,
        clientSecret: IFOOD_CLIENT_SECRET,
        wpUrl: WP_BASE_URL,
        wpSecret: WP_API_SECRET,
        io,
      }).catch(e => console.error('[iFood Polling] processEvent error:', e.message));
    }

    await acknowledgeEvents(events, IFOOD_CLIENT_ID, IFOOD_CLIENT_SECRET);

  } catch (err) {
    console.error('[iFood Polling] Error:', err.message);
  }
}

function startIfoodPolling() {
  if (pollingTimer) return; // already running
  console.log(`[iFood Polling] Starting — interval=${POLL_INTERVAL_MS / 1000}s`);
  pollingTimer = setInterval(runPollCycle, POLL_INTERVAL_MS);
  runPollCycle(); // first run immediately
}

// Start polling once server is ready (deferred so `io` is defined)
// Will also serve as keepalive for iFood merchant online status
function initIfoodPolling() {
  startIfoodPolling();
}

// Misc routes
app.get('/test', (req, res) => res.sendFile(__dirname + '/test.html'));
app.get('/health', (req, res) => res.json({ status: 'ok' }));

// ─── HTTP / HTTPS Server ─────────────────────────────────────────────────────
const server = USE_HTTPS ? https.createServer(options, app) : require('http').createServer(app);
const io = new Server(server, {
  cors: { origin: CORS_ORIGIN, methods: ['GET', 'POST'] }
});

// ─── Loyalty cleanup (DB) ────────────────────────────────────────────────────
const DB_HOST = process.env.DB_HOST || '187.110.167.98';
const DB_PORT = process.env.DB_PORT || '3306';
const DB_USER = process.env.DB_USER || process.env.MYSQL_USER || 'franguxo_backend';
const DB_PASS = process.env.DB_PASS || process.env.MYSQL_PASS || 'z}H.i)fI-(Yajr?t';
const DB_NAME = process.env.DB_NAME || process.env.MYSQL_DATABASE || 'franguxo_wp_naktw';
const DB_PREFIX = process.env.DB_PREFIX || 'Fgz5Ggu_';
const LOYALTY_INTERVAL = process.env.LOYALTY_CLEAN_INTERVAL ? parseInt(process.env.LOYALTY_CLEAN_INTERVAL, 10) : 10;

let dbPool = null;
async function initDbPool() {
  if (dbPool) return dbPool;
  if (!DB_USER || !DB_PASS || !DB_NAME) return null;

  let hostToUse = process.env.DB_HOST_IP || DB_HOST;
  if (!process.env.DB_HOST_IP) {
    try {
      const v4 = await dns.resolve4(DB_HOST).catch(() => []);
      const v6 = await dns.resolve6(DB_HOST).catch(() => []);
      const addrs = [...v4, ...v6];
      for (const a of addrs) {
        try {
          await new Promise((resolve, reject) => {
            const s = net.createConnection({ host: a, port: DB_PORT }, () => { s.destroy(); resolve(); });
            s.setTimeout(3000, () => { s.destroy(); reject(new Error('timeout')); });
            s.on('error', (e) => { s.destroy(); reject(e); });
          });
          hostToUse = a;
          break;
        } catch (_) { }
      }
    } catch (_) { }
  }

  dbPool = mysql.createPool({
    host: hostToUse, port: DB_PORT, user: DB_USER, password: DB_PASS,
    database: DB_NAME, waitForConnections: true, connectionLimit: 5, timezone: 'Z'
  });
  return dbPool;
}

async function cleanupExpiredLoyaltyPointsOnce() {
  const pool = await initDbPool();
  if (!pool) return { ok: true };
  let conn;
  try {
    conn = await pool.getConnection();
    const [rows] = await conn.execute(
      `SELECT DISTINCT user_id FROM \`${DB_PREFIX}usermeta\` WHERE meta_key = ? AND meta_value <= NOW()`,
      ['myd_loyalty_expires_at']
    );
    if (!rows || rows.length === 0) return { ok: true };
    for (const r of rows) {
      const uid = parseInt(r.user_id, 10);
      if (!uid) continue;
      const nowSql = new Date().toISOString().slice(0, 19).replace('T', ' ');
      const [u] = await conn.execute(
        `UPDATE \`${DB_PREFIX}usermeta\` SET meta_value = ? WHERE user_id = ? AND meta_key = ?`,
        ['0', uid, 'myd_loyalty_points']
      );
      if (!u || u.affectedRows === 0) {
        await conn.execute(`INSERT INTO \`${DB_PREFIX}usermeta\` (user_id, meta_key, meta_value) VALUES (?, ?, ?)`, [uid, 'myd_loyalty_points', '0']);
      }
      const [ur] = await conn.execute(
        `UPDATE \`${DB_PREFIX}usermeta\` SET meta_value = ? WHERE user_id = ? AND meta_key = ?`,
        [nowSql, uid, 'myd_loyalty_reset_at']
      );
      if (!ur || ur.affectedRows === 0) {
        await conn.execute(`INSERT INTO \`${DB_PREFIX}usermeta\` (user_id, meta_key, meta_value) VALUES (?, ?, ?)`, [uid, 'myd_loyalty_reset_at', nowSql]);
      }
      await conn.execute(`DELETE FROM \`${DB_PREFIX}usermeta\` WHERE user_id = ? AND meta_key = ?`, [uid, 'myd_loyalty_expires_at']);
      console.log(`[LoyaltyCleaner] Reset points for user ${uid}`);
    }
    return { ok: true };
  } catch (err) {
    return { ok: false, error: err };
  } finally {
    if (conn) conn.release();
  }
}

const BASE_INTERVAL = Math.max(1, LOYALTY_INTERVAL);
const MAX_INTERVAL = Math.max(BASE_INTERVAL, parseInt(process.env.LOYALTY_MAX_INTERVAL || '3600', 10));
let currentInterval = BASE_INTERVAL;
let lastErrorLog = 0;
function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

(async () => {
  const pool = await initDbPool();
  if (!pool) return;
  console.log(`[LoyaltyCleaner] started. interval=${BASE_INTERVAL}s`);
  while (true) {
    try {
      const res = await cleanupExpiredLoyaltyPointsOnce();
      if (res && res.ok) {
        currentInterval = BASE_INTERVAL;
      } else {
        const now = Date.now();
        if (now - lastErrorLog > 60000) { console.error('[LoyaltyCleaner] error:', res?.error?.message); lastErrorLog = now; }
        currentInterval = Math.min(MAX_INTERVAL, Math.floor(currentInterval * 2));
      }
    } catch (e) {
      const now = Date.now();
      if (now - lastErrorLog > 60000) { console.error('[LoyaltyCleaner] unexpected:', e?.message); lastErrorLog = now; }
      currentInterval = Math.min(MAX_INTERVAL, Math.floor(currentInterval * 2));
    }
    await sleep(currentInterval * 1000);
  }
})();

// ─── Socket.IO ───────────────────────────────────────────────────────────────
io.use((socket, next) => {
  const token = socket.handshake.auth.token;
  const addr = socket.handshake.address || 'unknown';
  if (!token) {
    console.warn(`[Socket Auth Fail] Missing token from ${addr}`);
    return next(new Error('Authentication error'));
  }
  jwt.verify(token, SECRET_KEY, (err, decoded) => {
    if (err) {
      console.warn(`[Socket Auth Fail] Invalid token from ${addr}: ${err.message}`);
      return next(new Error('Authentication error'));
    }
    socket.myd_customer_id = decoded.myd_customer_id;
    socket.role = decoded.role || null;
    next();
  });
});

io.on('connection', (socket) => {
  const addr = socket.handshake.address || 'unknown';
  console.log(`[Socket] connected id=${socket.id} customer=${socket.myd_customer_id || 'anon'} role=${socket.role || 'none'} ip=${addr}`);
  if (socket.myd_customer_id) socket.join(`user-${socket.myd_customer_id}`);
  if (socket.role === 'admin') {
    socket.join('admins');
    console.log(`[Socket] ${socket.id} joined admins room`);
  }
  socket.on('disconnect', (reason) => {
    console.log(`[Socket] disconnected id=${socket.id} reason=${reason}`);
  });
});

app.get('/clients', verifyToken, (req, res) => {
  const clients = [];
  io.sockets.sockets.forEach((s) => {
    clients.push({ id: s.id, myd_customer_id: s.myd_customer_id || null, rooms: Array.from(s.rooms || []) });
  });
  res.json({ count: clients.length, clients });
});

// ─── Start ───────────────────────────────────────────────────────────────────
server.listen(PORT, '0.0.0.0', () => {
  console.log(`[Server] Running on ${USE_HTTPS ? 'https' : 'http'}://0.0.0.0:${PORT}`);
  initIfoodPolling();
});

if (USE_HTTPS && (!fs.existsSync('key.pem') || !fs.existsSync('cert.pem'))) {
  try {
    require('child_process').execSync('openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365 -nodes -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"');
    console.log('[Server] Self-signed cert generated.');
  } catch (e) {
    console.error('[Server] Failed to generate cert:', e.message);
  }
}

process.on('unhandledRejection', (reason) => {
  console.error('[UnhandledRejection]', reason?.stack || reason);
n);
