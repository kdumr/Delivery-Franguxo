'use strict';
/**
 * iFood Order Processor
 * Polling, acknowledgment, order details and WordPress forwarding.
 */

const { getIfoodToken, invalidateToken } = require('./ifood-token');
const IFOOD_BASE = 'https://merchant-api.ifood.com.br';

async function ifoodHeaders(clientId, clientSecret) {
  const token = await getIfoodToken(clientId, clientSecret);
  return { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json', Accept: 'application/json' };
}

async function fetchOrderDetails(orderId, clientId, clientSecret) {
  const headers = await ifoodHeaders(clientId, clientSecret);
  const res = await fetch(`${IFOOD_BASE}/order/v1.0/orders/${orderId}`, { headers });
  if (res.status === 401) { invalidateToken(); throw new Error('Token expired fetching order'); }
  if (!res.ok) throw new Error(`fetchOrderDetails ${res.status}: ${await res.text()}`);
  return res.json();
}

async function acknowledgeEvents(events, clientId, clientSecret) {
  if (!events || events.length === 0) return;
  const ids = events.map(e => e.id).filter(Boolean);
  if (!ids.length) return;
  const headers = await ifoodHeaders(clientId, clientSecret);
  const res = await fetch(`${IFOOD_BASE}/events/v1.0/events/acknowledgment`, {
    method: 'POST',
    headers,
    body: JSON.stringify(ids.map(id => ({ id }))),
  });
  if (!res.ok) console.warn(`[iFood ACK] Failed for ${ids.length} events: ${res.status}`);
  else console.log(`[iFood ACK] Acknowledged ${ids.length} event(s)`);
}

async function pollEvents(merchantId, clientId, clientSecret) {
  const headers = await ifoodHeaders(clientId, clientSecret);
  const res = await fetch(`${IFOOD_BASE}/events/v1.0/events:polling`, {
    headers: { ...headers, ...(merchantId ? { 'x-polling-merchants': merchantId } : {}) },
  });
  if (res.status === 204) return [];
  if (res.status === 401) { invalidateToken(); throw new Error('Token expired during polling'); }
  if (!res.ok) throw new Error(`pollEvents ${res.status}: ${await res.text()}`);
  return res.json();
}

async function forwardToWordPress(order, wpUrl, wpSecret) {
  const endpoint = `${wpUrl.replace(/\/$/, '')}/wp-json/myd-delivery/v1/ifood/create-order`;
  const res = await fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-MyD-Secret': wpSecret },
    body: JSON.stringify(order),
  });
  if (!res.ok) throw new Error(`WordPress rejected order ${order.id}: ${res.status} ${await res.text()}`);
  const result = await res.json();
  console.log(`[iFood→WP] Order ${order.id} → post_id=${result.post_id}`);
  return result;
}

async function processEvent(event, { clientId, clientSecret, wpUrl, wpSecret }) {
  const code = event.code || event.fullCode || '';
  const orderId = event.orderId;
  if (code === 'KEEPALIVE' || code === 'HEARTBEAT') return;
  console.log(`[iFood Event] code=${code} orderId=${orderId || 'n/a'}`);
  if (code !== 'PLACED') return;

  try {
    const order = await fetchOrderDetails(orderId, clientId, clientSecret);
    if (wpUrl && wpSecret) await forwardToWordPress(order, wpUrl, wpSecret);
    else console.warn('[iFood Event] WP URL or secret not set — skipping forward');
  } catch (err) {
    console.error(`[iFood Event] Error processing PLACED ${orderId}:`, err.message);
  }
}

module.exports = { pollEvents, acknowledgeEvents, processEvent };
