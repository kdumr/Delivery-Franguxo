'use strict';
const axios = require('axios');
const { getOrderDetails, acknowledgeEvents } = require('./ifood-client');

/**
 * Known event codes that carry an orderId and need processing.
 */
const ORDER_EVENTS = new Set([
  'PLC',
  'CONFIRMED',
  'INTEGRATED',
  'READY_TO_PICKUP',
  'DISPATCHED',
  'CONCLUDED',
  'CANCELLATION_REQUESTED',
  'CANCELLED',
  'ORDER_CREATED', // some sandbox variants
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
 *
 * @param {Object} payload
 * @param {string} wpUrl     e.g. https://franguxo.app.br
 * @param {string} wpSecret  shared secret for X-WP-Secret header
 * @returns {Promise<boolean>}
 */
async function forwardToWordPress(payload, wpUrl, wpSecret) {
  const url = `${wpUrl.replace(/\/$/, '')}/wp-json/myd/v1/ifood/order`;

  try {
    const response = await axios.post(url, payload, {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Secret': wpSecret,
      },
      timeout: 15000,
    });

    console.log(`[WP Forward] Success — status=${response.status} orderId=${payload.event.orderId || '—'}`);
    return true;
  } catch (err) {
    const status = err.response?.status;
    const body = err.response?.data;
    console.error(`[WP Forward] Failed — status=${status}:`, body || err.message);
    return false; // Do not throw, return false so the caller knows it failed
  }
}

module.exports = { processEvent };

