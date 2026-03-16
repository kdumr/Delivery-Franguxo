'use strict';
/**
 * iFood Order Processor
 * Handles event processing, order fetching, acknowledgment and
 * forwarding to WordPress.
 */

const { getIfoodToken, invalidateToken } = require('./ifood-token');

const IFOOD_BASE = 'https://merchant-api.ifood.com.br';

/**
 * Build common iFood API headers with a fresh token.
 */
async function ifoodHeaders(clientId, clientSecret) {
  const token = await getIfoodToken(clientId, clientSecret);
  return {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };
}

/**
 * Fetch detailed order data from iFood.
 * @param {string} orderId
 * @param {string} clientId
 * @param {string} clientSecret
 */
async function fetchOrderDetails(orderId, clientId, clientSecret) {
  const headers = await ifoodHeaders(clientId, clientSecret);
  const res = await fetch(`${IFOOD_BASE}/order/v1.0/orders/${orderId}`, { headers });

  if (res.status === 401) {
    invalidateToken();
    throw new Error('iFood token expired while fetching order details');
  }
  if (!res.ok) {
    throw new Error(`fetchOrderDetails failed: ${res.status} ${await res.text()}`);
  }
  return res.json();
}

/**
 * Acknowledge events so iFood doesn't resend them.
 * @param {Array<{id:string}>} events
 */
async function acknowledgeEvents(events, clientId, clientSecret) {
  if (!events || events.length === 0) return;
  const headers = await ifoodHeaders(clientId, clientSecret);
  const ids = events.map(e => e.id).filter(Boolean);
  if (!ids.length) return;

  const res = await fetch(`${IFOOD_BASE}/events/v1.0/events/acknowledgment`, {
    method: 'POST',
    headers,
    body: JSON.stringify(ids.map(id => ({ id }))),
  });

  if (!res.ok) {
    console.warn(`[iFood ACK] Failed to acknowledge ${ids.length} event(s): ${res.status}`);
  } else {
    console.log(`[iFood ACK] Acknowledged ${ids.length} event(s)`);
  }
}

/**
 * Poll iFood for new events.
 * Returns array of events (may be empty).
 */
async function pollEvents(merchantId, clientId, clientSecret) {
  const headers = await ifoodHeaders(clientId, clientSecret);
  const url = `${IFOOD_BASE}/events/v1.0/events:polling`;

  const res = await fetch(url, {
    method: 'GET',
    headers: {
      ...headers,
      ...(merchantId ? { 'x-polling-merchants': merchantId } : {}),
    },
  });

  if (res.status === 204) return []; // no new events
  if (res.status === 401) {
    invalidateToken();
    throw new Error('iFood token expired during polling');
  }
  if (!res.ok) {
    throw new Error(`pollEvents failed: ${res.status} ${await res.text()}`);
  }
  return res.json();
}

/**
 * Forward order data to WordPress REST API.
 * @param {object} order - full order details from iFood
 * @param {string} wpUrl - WordPress base URL
 * @param {string} wpSecret - shared secret header value
 */
async function forwardToWordPress(order, wpUrl, wpSecret) {
  const endpoint = `${wpUrl.replace(/\/$/, '')}/wp-json/myd-delivery/v1/ifood/create-order`;

  const res = await fetch(endpoint, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-MyD-Secret': wpSecret,
    },
    body: JSON.stringify(order),
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`WordPress rejected order: ${res.status} ${text}`);
  }

  const result = await res.json();
  console.log(`[iFood→WP] Order ${order.id} created: post_id=${result.post_id}`);
  return result;
}

/**
 * Process a single iFood event.
 * Only acts on PLACED events.
 */
async function processEvent(event, { clientId, clientSecret, wpUrl, wpSecret, io }) {
  const code = event.code || event.fullCode || '';
  const orderId = event.orderId;

  if (code === 'KEEPALIVE' || code === 'HEARTBEAT') return;

  console.log(`[iFood Event] code=${code} orderId=${orderId || 'n/a'}`);

  // Notify sockets regardless of type
  if (io && orderId) {
    io.to('admins').emit('ifood.event', { code, orderId, raw: event });
  }

  if (code !== 'PLACED') return; // Only auto-create orders on PLACED

  try {
    const order = await fetchOrderDetails(orderId, clientId, clientSecret);
    console.log(`[iFood Event] Fetched order details for ${orderId}`);

    // Emit enriched order to admins via socket
    if (io) io.to('admins').emit('ifood.new_order', order);

    if (wpUrl && wpSecret) {
      await forwardToWordPress(order, wpUrl, wpSecret);
    } else {
      console.warn('[iFood Event] WordPress URL or secret not configured – skipping WP forward');
    }
  } catch (err) {
    console.error(`[iFood Event] Error processing PLACED event for ${orderId}:`, err.message);
  }
}

module.exports = { pollEvents, acknowledgeEvents, processEvent, forwardToWordPress };
