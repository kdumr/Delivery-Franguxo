require('dotenv').config();
const express = require('express');
const cors = require('cors');
const https = require('https');
const fs = require('fs');
const jwt = require('jsonwebtoken');
const { Server } = require('socket.io');
const mysql = require('mysql2/promise');
const dns = require('dns').promises;
const net = require('net');
const crypto = require('crypto');

// Configurações de segurança
const SECRET_KEY = process.env.SECRET_KEY || 'your-super-secret-key-change-this-in-production';
const PORT = process.env.PORT || 80;
const USE_HTTPS = process.env.USE_HTTPS === 'true'; // Default false, set to 'true' for HTTPS
const IFOOD_CLIENT_SECRET = process.env.IFOOD_CLIENT_SECRET;
const WP_WEBHOOK_URL = process.env.WP_WEBHOOK_URL;
const CORS_ORIGIN = process.env.CORS_ORIGIN || "*";

// Certificado para HTTPS (apenas se USE_HTTPS estiver true)
let options = {};
if (USE_HTTPS) {
  if (!fs.existsSync('key.pem') || !fs.existsSync('cert.pem')) {
    console.log('HTTPS requested but certificate files not found. Expecting generated certs or mounted certificates.');
  }
  options = {
    key: fs.readFileSync('key.pem'),
    cert: fs.readFileSync('cert.pem')
  };
}

const app = express();
app.use(cors());
app.use(express.json({
  verify: (req, res, buf) => {
    req.rawBody = buf;
  }
}));

// Middleware para verificar token JWT
function verifyToken(req, res, next) {
  const token = req.headers['authorization']?.split(' ')[1]; // Bearer token
  if (!token) return res.status(401).json({ error: 'No token provided' });

  jwt.verify(token, SECRET_KEY, (err, decoded) => {
    if (err) {
      const ip = req.headers['x-forwarded-for'] || req.connection.remoteAddress || req.socket.remoteAddress || req.ip;
      console.warn(`[HTTP Auth Fail] Invalid token from ${ip}: ${err.message}`);
      return res.status(403).json({ error: 'Invalid token' });
    }
    req.user = decoded;
    next();
  });
}

// Endpoint para gerar token (para desenvolvimento; em produção, gere no WP)
app.post('/auth', (req, res) => {
  const { myd_customer_id } = req.body;
  if (!myd_customer_id && myd_customer_id !== 0) return res.status(400).json({ error: 'myd_customer_id required' });

  const tokenPayload = {};
  if (myd_customer_id && myd_customer_id !== 0) tokenPayload.myd_customer_id = myd_customer_id;

  const token = jwt.sign(tokenPayload, SECRET_KEY, { expiresIn: '24h' });
  res.json({ token });
});

// Endpoint seguro para notificar (usado pelo WordPress)
app.post('/notify', verifyToken, (req, res) => {
  const { myd_customer_id, order_id, status } = req.body || {};
  try {
    // Emitir evento para o room do cliente, se tivermos o id
    if (myd_customer_id) {
      io.to(`user-${myd_customer_id}`).emit('order.updated', { order_id, status });
    }
    // Emitir sempre para room de administradores
    io.to('admins').emit('order.status', { order_id, status });
    console.log(`[Notify] order_id=${order_id || 'n/a'} status=${status || 'n/a'} customer=${myd_customer_id || 'n/a'}`);
    res.json({ success: true, delivered: { user: !!myd_customer_id, admins: true } });
  } catch (e) {
    console.error('[Notify] emit error:', e.message);
    res.status(500).json({ error: 'emit failed' });
  }
});

// Endpoint para atualizar status da loja (broadcast)
app.post('/notify/store', verifyToken, (req, res) => {
  const { open, force } = req.body || {};
  if (typeof open === 'undefined') return res.status(400).json({ error: 'open required' });
  const payload = { open: !!open };
  if (force && ['ignore', 'open', 'close'].includes(force)) payload.force = force;
  io.emit('store.status', payload);
  res.json({ success: true, emitted: payload });
});

// Endpoint para receber webhooks do iFood
app.post('/ifood/webhook', async (req, res) => {
  const timestamp = new Date().toLocaleString('pt-BR');
  const signature = req.headers['x-ifood-signature'];

  console.log(`\n--- [iFood Webhook] Received at ${timestamp} ---`);

  // 1. Validar Ass Signature
  if (!signature) {
    console.error('[iFood Webhook] Error: Missing X-IFood-Signature header');
    return res.status(401).send('Missing signature');
  }

  const hmac = crypto.createHmac('sha256', IFOOD_CLIENT_SECRET)
    .update(req.rawBody)
    .digest('hex');

  if (hmac !== signature) {
    console.error('[iFood Webhook] Error: Invalid signature');
    return res.status(401).send('Invalid signature');
  }

  const payload = req.body;
  console.log('Payload:', JSON.stringify(payload, null, 2));

  // 2. Encaminhar para o WordPress (franguxo.app.br)
  try {
    const wpUrl = WP_WEBHOOK_URL || 'https://dev.franguxo.app.br/wp-json/myd-delivery/v1/ifood/webhook';
    console.log(`[iFood Webhook] Forwarding to WordPress: ${wpUrl}`);

    const wpResponse = await fetch(wpUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Forwarded-From': 'MyDelivery-Push-Server'
      },
      body: JSON.stringify(payload)
    });

    const wpResult = await wpResponse.json();
    console.log('[iFood Webhook] WordPress response:', JSON.stringify(wpResult, null, 2));
  } catch (error) {
    console.error('[iFood Webhook] Error forwarding to WordPress:', error.message);
  }

  // 3. Notificar via Socket.io para o painel de pedidos (admins)
  try {
    const events = Array.isArray(payload) ? payload : [payload];
    events.forEach(event => {
      // Evitar emitir order.status para KEEPALIVE para não gerar erro no front (missing order_id)
      if (event.code !== 'KEEPALIVE') {
        io.to('admins').emit('order.status', {
          order_id: event.orderId,
          status: event.code || event.fullCode,
          source: 'ifood'
        });
      }

      // Emitir evento genérico (usado pelo widget de status/polling híbrido)
      io.to('admins').emit('ifood.event', event);
    });
    console.log(`[iFood Webhook] Socket.io notifications sent to admins`);
  } catch (error) {
    console.error('[iFood Webhook] Socket.io emit error:', error.message);
  }

  console.log('--- [iFood Webhook] Processed ---\n');

  // O iFood recomenda responder com 202 Accepted rapidamente
  res.status(202).send();
});

// Rota para página de teste
app.get('/test', (req, res) => {
  res.sendFile(__dirname + '/test.html');
});

app.get('/health', (req, res) => {
  res.json({ status: 'ok' });
});

const server = USE_HTTPS ? https.createServer(options, app) : require('http').createServer(app);
const io = new Server(server, {
  cors: {
    origin: CORS_ORIGIN, // Ajuste para o domínio do seu site em produção
    methods: ["GET", "POST"]
  }
});

// --- Loyalty cleanup background task (configurable) ---
const DB_HOST = process.env.DB_HOST || '187.110.167.98';
const DB_PORT = process.env.DB_PORT || '3306';
const DB_USER = process.env.DB_USER || process.env.MYSQL_USER || 'franguxo_backend';
const DB_PASS = process.env.DB_PASS || process.env.MYSQL_PASS || 'z}H.i)fI-(Yajr?t';
const DB_NAME = process.env.DB_NAME || process.env.MYSQL_DATABASE || 'franguxo_wp_naktw';
const DB_PREFIX = process.env.DB_PREFIX || 'Fgz5Ggu_';
const LOYALTY_INTERVAL = process.env.LOYALTY_CLEAN_INTERVAL ? parseInt(process.env.LOYALTY_CLEAN_INTERVAL, 10) : 10; // seconds

let dbPool = null;
async function initDbPool() {
  if (dbPool) return dbPool;
  if (!DB_USER || !DB_PASS || !DB_NAME) {
    console.log('[LoyaltyCleaner] DB config incomplete, loyalty cleaner disabled. Set DB_USER, DB_PASS and DB_NAME.');
    return null;
  }

  // allow overriding with an env var that contains a pre-resolved IP
  let hostToUse = process.env.DB_HOST_IP || DB_HOST;

  // Try to resolve DNS addresses (IPv4/IPv6) and test TCP connectivity to pick a responsive IP
  if (!process.env.DB_HOST_IP) {
    try {
      let addrs = [];
      try {
        const v4 = await dns.resolve4(DB_HOST).catch(() => []);
        addrs = addrs.concat(v4 || []);
      } catch (e) { }
      try {
        const v6 = await dns.resolve6(DB_HOST).catch(() => []);
        addrs = addrs.concat(v6 || []);
      } catch (e) { }
      if (addrs.length === 0) {
        // fallback to lookup which may return one address
        const res = await dns.lookup(DB_HOST).catch(() => null);
        if (res && res.address) addrs.push(res.address);
      }
      if (addrs.length > 0) {
        // try connecting to each address with short timeout
        let connected = false;
        for (const a of addrs) {
          try {
            await new Promise((resolve, reject) => {
              const s = net.createConnection({ host: a, port: DB_PORT }, () => { s.destroy(); resolve(); });
              s.setTimeout(3000, () => { s.destroy(); reject(new Error('timeout')); });
              s.on('error', (err) => { s.destroy(); reject(err); });
            });
            hostToUse = a;
            console.log(`[LoyaltyCleaner] Selected responsive address ${a} for ${DB_HOST}`);
            connected = true;
            break;
          } catch (err) {
            // try next
            console.warn(`[LoyaltyCleaner] address ${a} not reachable: ${err && err.message ? err.message : err}`);
          }
        }
        if (!connected) {
          console.warn(`[LoyaltyCleaner] no responsive addresses found for ${DB_HOST}; will attempt using hostname directly.`);
        }
      } else {
        console.warn(`[LoyaltyCleaner] DNS resolution returned no addresses for ${DB_HOST}`);
      }
    } catch (e) {
      console.warn(`[LoyaltyCleaner] DNS resolution/testing failed for ${DB_HOST}: ${e && e.message ? e.message : e}`);
    }
  }

  dbPool = mysql.createPool({
    host: hostToUse,
    port: DB_PORT,
    user: DB_USER,
    password: DB_PASS,
    database: DB_NAME,
    waitForConnections: true,
    connectionLimit: 5,
    timezone: 'Z'
  });
  return dbPool;
}

async function cleanupExpiredLoyaltyPointsOnce() {
  const pool = await initDbPool();
  if (!pool) return { ok: true };
  const usermeta = `${DB_PREFIX}usermeta`;
  let conn;
  try {
    conn = await pool.getConnection();
    // find user_ids with expires_at <= now
    const [rows] = await conn.execute(
      `SELECT DISTINCT user_id FROM \`${DB_PREFIX}usermeta\` WHERE meta_key = ? AND meta_value <= NOW()`,
      ['myd_loyalty_expires_at']
    );
    if (!rows || rows.length === 0) {
      // no expirations
      return { ok: true };
    }
    for (const r of rows) {
      const uid = parseInt(r.user_id, 10);
      if (!uid) continue;
      const nowSql = new Date().toISOString().slice(0, 19).replace('T', ' ');
      // try update myd_loyalty_points, else insert
      const [updateRes] = await conn.execute(
        `UPDATE \`${DB_PREFIX}usermeta\` SET meta_value = ? WHERE user_id = ? AND meta_key = ?`,
        ['0', uid, 'myd_loyalty_points']
      );
      if (!updateRes || updateRes.affectedRows === 0) {
        await conn.execute(
          `INSERT INTO \`${DB_PREFIX}usermeta\` (user_id, meta_key, meta_value) VALUES (?, ?, ?)`,
          [uid, 'myd_loyalty_points', '0']
        );
      }
      // update reset timestamp
      const [updReset] = await conn.execute(
        `UPDATE \`${DB_PREFIX}usermeta\` SET meta_value = ? WHERE user_id = ? AND meta_key = ?`,
        [nowSql, uid, 'myd_loyalty_reset_at']
      );
      if (!updReset || updReset.affectedRows === 0) {
        await conn.execute(
          `INSERT INTO \`${DB_PREFIX}usermeta\` (user_id, meta_key, meta_value) VALUES (?, ?, ?)`,
          [uid, 'myd_loyalty_reset_at', nowSql]
        );
      }
      // remove expires meta
      await conn.execute(
        `DELETE FROM \`${DB_PREFIX}usermeta\` WHERE user_id = ? AND meta_key = ?`,
        [uid, 'myd_loyalty_expires_at']
      );
      console.log(`[LoyaltyCleaner] Reset points for user ${uid}`);
    }
    return { ok: true };
  } catch (err) {
    // bubble up error
    return { ok: false, error: err };
  } finally {
    if (conn) conn.release();
  }
}

// Start periodic cleaner (runs only if DB config present)
// Run cleanup loop with exponential backoff on failure and throttled error logs
const BASE_INTERVAL = Math.max(1, LOYALTY_INTERVAL);
const MAX_INTERVAL = Math.max(BASE_INTERVAL, parseInt(process.env.LOYALTY_MAX_INTERVAL || '3600', 10));
let currentInterval = BASE_INTERVAL;
let lastErrorLog = 0;

function sleep(ms) { return new Promise(resolve => setTimeout(resolve, ms)); }

(async () => {
  const pool = await initDbPool();
  if (!pool) return;
  console.log(`[LoyaltyCleaner] started. Base interval seconds=${BASE_INTERVAL}`);
  while (true) {
    try {
      const res = await cleanupExpiredLoyaltyPointsOnce();
      if (res && res.ok) {
        // success: reset interval
        currentInterval = BASE_INTERVAL;
      } else {
        // treat as error
        const errMsg = res && res.error ? (res.error.message || String(res.error)) : 'unknown';
        const now = Date.now();
        if (now - lastErrorLog > 60000) {
          console.error('[LoyaltyCleaner] error:', errMsg);
          lastErrorLog = now;
        }
        // increase backoff
        currentInterval = Math.min(MAX_INTERVAL, Math.floor(currentInterval * 2));
      }
    } catch (e) {
      const now = Date.now();
      if (now - lastErrorLog > 60000) {
        console.error('[LoyaltyCleaner] unexpected error:', e && e.message ? e.message : e);
        lastErrorLog = now;
      }
      currentInterval = Math.min(MAX_INTERVAL, Math.floor(currentInterval * 2));
    }
    await sleep(currentInterval * 1000);
  }
})();


// Socket.IO com autenticação
io.use((socket, next) => {
  const token = socket.handshake.auth.token;
  const addr = socket.handshake.address || (socket.conn && socket.conn.remoteAddress) || (socket.request && socket.request.connection && socket.request.connection.remoteAddress) || 'unknown';
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
    console.log(`[Socket Auth Success] id=${socket.id} customer=${socket.myd_customer_id || 'anon'} role=${socket.role || 'none'} ip=${addr}`);
    console.log(`[Socket Auth] Decoded token:`, JSON.stringify(decoded, null, 2));
    next();
  });
});

io.on('connection', (socket) => {
  const addr = socket.handshake.address || (socket.conn && socket.conn.remoteAddress) || (socket.request && socket.request.connection && socket.request.connection.remoteAddress) || 'unknown';
  console.log(`Client connected: id=${socket.id} customer=${socket.myd_customer_id || 'anon'} role=${socket.role || 'none'} ip=${addr}`);
  if (socket.myd_customer_id) {
    socket.join(`user-${socket.myd_customer_id}`);
  }
  if (socket.role === 'admin') {
    socket.join('admins');
    console.log(`Socket ${socket.id} joined admins room (admin user)`);
  } else {
    console.log(`Socket ${socket.id} NOT joining admins room (role: ${socket.role || 'none'})`);
  }

  socket.on('disconnect', (reason) => {
    const daddr = socket.handshake.address || (socket.conn && socket.conn.remoteAddress) || (socket.request && socket.request.connection && socket.request.connection.remoteAddress) || 'unknown';
    console.log(`Client disconnected: id=${socket.id} customer=${socket.myd_customer_id || 'anon'} ip=${daddr} reason=${reason}`);
  });
});

// Endpoint to list currently connected clients (id, myd_customer_id, ip, rooms)
app.get('/clients', verifyToken, (req, res) => {
  const clients = [];
  io.sockets.sockets.forEach((s) => {
    const sip = s.handshake.address || (s.conn && s.conn.remoteAddress) || (s.request && s.request.connection && s.request.connection.remoteAddress) || null;
    clients.push({ id: s.id, myd_customer_id: s.myd_customer_id || null, ip: sip, rooms: Array.from(s.rooms || []) });
  });
  res.json({ count: clients.length, clients });
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`Push server running on ${USE_HTTPS ? 'https' : 'http'}://0.0.0.0:${PORT}`);
});

// Apenas para desenvolvimento: gerar certificado auto-assinado se solicitado explicitamente
if (USE_HTTPS) {
  if (!fs.existsSync('key.pem') || !fs.existsSync('cert.pem')) {
    console.log('Generating self-signed certificate (dev)...');
    const { execSync } = require('child_process');
    try {
      execSync('openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365 -nodes -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"');
      console.log('Certificate generated.');
    } catch (err) {
      console.error('Failed to generate certificate:', err.message);
    }
  }
}

// Log unhandled rejections to aid debugging instead of crashing silently
process.on('unhandledRejection', (reason, promise) => {
  console.error('[LoyaltyCleaner] Unhandled Rejection at:', promise, 'reason:', reason && reason.stack ? reason.stack : reason);
});