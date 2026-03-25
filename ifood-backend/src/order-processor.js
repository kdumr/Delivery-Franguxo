'use strict';
const axios = require('axios');
const { getOrderDetails, acknowledgeEvents } = require('./ifood-client');

/**
 * Known event codes that carry an orderId and need processing.
 */
const ORDER_EVENTS = new Set([
  // Short codes (event.code)
  'PLC',
  'CFM',
  'RTP',
  'DSP',
  'CON',
  'CAN',
  // Full codes (event.fullCode) — some iFood envs use these
  'CONFIRMED',
  'INTEGRATED',
  'READY_TO_PICKUP',
  'DISPATCHED',
  'CONCLUDED',
  'CANCELLATION_REQUESTED',
  'CANCELLED',
  'ORDER_CREATED',
]);

/**
 * Deduplication set — prevents double-processing the same event id.
 * In production, persist this in Redis/DB. Here we use a memory Set
 * with a simple TTL clear every 10 min.
 */
const processedEventIds = new Set();
setInterval(() => processedEventIds.clear(), 10 * 60 * 1000);

/**
 * Process a single iFood event and forward to WordPress.
 *
 * @param {Object} event   raw iFood event object
 * @param {Object} config  { clientId, clientSecret, wpUrl, wpSecret }
 */
async function processEvent(event, config) {
  const { clientId, clientSecret, wpUrl, wpSecret } = config;

  // Guard: skip unknown / non-order events
  const code = (event.code || event.fullCode || '').trim();
  const eventId = (event.id || '').trim();

  if (!code || !eventId) {
    console.log(`[Processor] Skipping event without code or id.`);
    return;
  }

  // Deduplication
  if (processedEventIds.has(eventId)) {
    console.log(`[Processor] Duplicate event ignored: id=${eventId}`);
    return;
  }
  processedEventIds.add(eventId);

  const orderId = (event.orderId || event.order_id || '').trim();
  console.log(`[Processor] Event code=${code} orderId=${orderId} eventId=${eventId}`);

  // Fetch full order details only for events that have an order
  let orderDetails = null;
  if (orderId && ORDER_EVENTS.has(code)) {
    try {
      orderDetails = await getOrderDetails(orderId, clientId, clientSecret);
    } catch (err) {
      console.error(`[Processor] Failed to fetch order details for ${orderId}:`, err.response?.data || err.message);

      // Fallback: sometimes iFood (especially in Sandbox) puts the real order UUID in event.id
      if (eventId && eventId !== orderId) {
        console.log(`[Processor] Attempting fallback to fetch order details using event id: ${eventId}`);
        try {
          orderDetails = await getOrderDetails(eventId, clientId, clientSecret);
          console.log(`[Processor] Fallback successful! Order details fetched using eventId.`);
        } catch (fbErr) {
          console.error(`[Processor] Fallback also failed for ${eventId}:`, fbErr.response?.data || fbErr.message);
        }
      }
    }
  }

  // Build the payload for WordPress
  const payload = {
    event: {
      id: event.id,
      code,
      fullCode: event.fullCode || code,
      merchantId: event.merchantId || '',
      createdAt: event.createdAt || new Date().toISOString(),
      orderId: orderId, // Crucial para o webhook buscar qual pedido deve ser modificado em eventos como o CAN
    },
    order: orderDetails || null,
  };

  // Forward to WordPress
  if (wpUrl && wpSecret) {
    const success = await forwardToWordPress(payload, wpUrl, wpSecret);
    return success;
  } else {
    console.warn('[Processor] WordPress URL or secret not configured — skipping forward');
    console.log('[Processor] Payload:', JSON.stringify(payload, null, 2));
    return false; // Not integrated successfully yet
  }
}

/**
 * Send the processed event + order to WordPress REST API.
 * Returns true ONLY when WordPress confirms { success: true } in the body.
 * Retries up to MAX_RETRIES times with exponential backoff on transient errors.
 *
 * @param {Object} payload
 * @param {string} wpUrl     e.g. https://franguxo.app.br
 * @param {string} wpSecret  shared secret for X-WP-Secret header
 * @returns {Promise<boolean>}
 */
const MAX_RETRIES = 3;

function isTransient(err) {
  const status = err.response?.status;
  return !status || status >= 500 || status === 429 || err.code === 'ECONNABORTED' || err.code === 'ETIMEDOUT';
}

async function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function forwardToWordPress(payload, wpUrl, wpSecret) {
  const url = `${wpUrl.replace(/\/$/, '')}/wp-json/myd/v1/ifood/order`;
  const orderId = payload.event?.orderId || '—';
  const eventId = payload.event?.id || '—';

  for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
    const t0 = Date.now();
    try {
      const response = await axios.post(url, payload, {
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Secret': wpSecret,
        },
        timeout: 15000,
      });

      const duration = Date.now() - t0;
      const wpSuccess = response.data?.success === true;

      console.log(JSON.stringify({
        event: '[WP Forward]',
        attempt,
        orderId,
        eventId,
        status: response.status,
        wpSuccess,
        duration,
      }));

      if (!wpSuccess) {
        // WordPress responded 2xx but reported internal failure — do NOT ACK
        console.warn(JSON.stringify({
          event: '[WP Forward] wpSuccess=false — NO ACK',
          orderId,
          eventId,
          wpBody: response.data,
        }));
        return false;
      }

      return true;

    } catch (err) {
      const duration = Date.now() - t0;
      const status = err.response?.status;
      const body = err.response?.data;

      console.error(JSON.stringify({
        event: '[WP Forward] Error',
        attempt,
        orderId,
        eventId,
        status,
        duration,
        error: err.message,
        wpBody: body,
      }));

      if (isTransient(err) && attempt < MAX_RETRIES) {
        const delay = Math.pow(2, attempt) * 1000; // 2s, 4s, 8s
        const jitter = Math.floor(Math.random() * 500);
        console.log(`[WP Forward] Transient error — retrying in ${delay + jitter}ms (attempt ${attempt}/${MAX_RETRIES})`);
        await sleep(delay + jitter);
        continue;
      }

      // Permanent error or max retries reached — do NOT ACK
      return false;
    }
  }

  return false;
}

module.exports = { processEvent };

