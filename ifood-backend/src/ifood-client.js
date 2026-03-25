'use strict';
const axios = require('axios');
const { getToken } = require('./token-manager');

const IFOOD_BASE = 'https://merchant-api.ifood.com.br';


/**
 * Poll iFood for new events.
 * Keeps the merchant ONLINE — must run every 30s.
 * @param {string} merchantId
 * @param {string} clientId
 * @param {string} clientSecret
 * @returns {Promise<Array>} events array (may be empty)
 */
async function pollEvents(merchantId, clientId, clientSecret) {
  const token = await getToken(clientId, clientSecret);

  const headers = {
    Authorization: `Bearer ${token}`,
    'x-polling-merchants': merchantId,
  };

  const response = await axios.get(`${IFOOD_BASE}/order/v1.0/events:polling`, {
    headers,
    timeout: 20000,
  });

  return response.data || [];
}

/**
 * Acknowledge processed events so iFood stops re-sending them.
 * @param {Array} events
 * @param {string} clientId
 * @param {string} clientSecret
 */
async function acknowledgeEvents(events, clientId, clientSecret) {
  if (!events || events.length === 0) return;

  const token = await getToken(clientId, clientSecret);
  const ids = events.map((e) => ({ id: e.id }));

  try {
    console.log(`[iFood-Debug] ACK request to: ${IFOOD_BASE}/order/v1.0/events/acknowledgment`);
    console.log(`[iFood-Debug] ACK payload:`, JSON.stringify(ids));
    await axios.post(`${IFOOD_BASE}/order/v1.0/events/acknowledgment`, ids, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      timeout: 15000,
    });
    console.log(`[iFood] Acknowledged ${ids.length} event(s)`);
  } catch (err) {
    console.error(`[iFood] ACK Request Failed:`, err.response?.data || err.message);
    throw err;
  }
}

/**
 * Fetch full order details from iFood.
 * Order details are immutable — query only once.
 * @param {string} orderId
 * @param {string} clientId
 * @param {string} clientSecret
 * @returns {Promise<Object>} order details
 */
async function getOrderDetails(orderId, clientId, clientSecret) {
  const token = await getToken(clientId, clientSecret);
  const cleanOrderId = orderId.replace(/[^a-f0-9\-]/gi, ''); // remove any invisible chars

  try {
    const url = `${IFOOD_BASE}/order/v1.0/orders/${cleanOrderId}`;
    console.log(`[iFood-Debug] GET Order Details URL: ${url}`);

    const response = await axios.get(url, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
      timeout: 15000,
    });
    return response.data;
  } catch (err) {
    console.error(`[iFood] Get Order Details Failed (${cleanOrderId}):`, err.response?.data || err.message);
    throw err;
  }
}
/**
 * Confirm an order on iFood.
 * Called when the merchant confirms the order in WordPress.
 * @param {string} orderId   iFood order UUID
 * @param {string} clientId
 * @param {string} clientSecret
 * @returns {Promise<boolean>}
 */
async function confirmOrder(orderId, clientId, clientSecret) {
  const token = await getToken(clientId, clientSecret);
  const cleanOrderId = orderId.replace(/[^a-f0-9\-]/gi, '');

  try {
    const url = `${IFOOD_BASE}/order/v1.0/orders/${cleanOrderId}/confirm`;
    console.log(`[iFood] Confirming order: ${cleanOrderId}`);
    await axios.post(url, null, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
      timeout: 15000,
    });
    console.log(`[iFood] Order confirmed successfully: ${cleanOrderId}`);
    return true;
  } catch (err) {
    console.error(`[iFood] Confirm order failed (${cleanOrderId}):`, err.response?.data || err.message);
    throw err;
  }
}

/**
 * Dispatch an order on iFood (tell iFood the order left the store).
 * iFood docs: POST /order/v1.0/orders/{id}/dispatch → 202 Accepted
 * @param {string} orderId   iFood order UUID
 * @param {string} clientId
 * @param {string} clientSecret
 * @returns {Promise<boolean>}
 */
async function dispatchOrder(orderId, clientId, clientSecret) {
  const token = await getToken(clientId, clientSecret);
  const cleanOrderId = orderId.replace(/[^a-f0-9\-]/gi, '');

  try {
    const url = `${IFOOD_BASE}/order/v1.0/orders/${cleanOrderId}/dispatch`;
    console.log(`[iFood] Dispatching order: ${cleanOrderId}`);
    await axios.post(url, null, {
      headers: { Authorization: `Bearer ${token}` },
      timeout: 15000,
    });
    console.log(`[iFood] Order dispatched successfully: ${cleanOrderId}`);
    return true;
  } catch (err) {
    console.error(`[iFood] Dispatch order failed (${cleanOrderId}):`, err.response?.data || err.message);
    throw err;
  }
}

/**
 * Mark an order as ready to pickup on iFood (Takeout / Indoor orders).
 * iFood docs: POST /order/v1.0/orders/{id}/readyToPickup → 202 Accepted
 * @param {string} orderId   iFood order UUID
 * @param {string} clientId
 * @param {string} clientSecret
 * @returns {Promise<boolean>}
 */
async function readyToPickupOrder(orderId, clientId, clientSecret) {
  const token = await getToken(clientId, clientSecret);
  const cleanOrderId = orderId.replace(/[^a-f0-9\-]/gi, '');

  try {
    const url = `${IFOOD_BASE}/order/v1.0/orders/${cleanOrderId}/readyToPickup`;
    console.log(`[iFood] Marking order ready to pickup: ${cleanOrderId}`);
    await axios.post(url, null, {
      headers: { Authorization: `Bearer ${token}` },
      timeout: 15000,
    });
    console.log(`[iFood] Order marked ready to pickup: ${cleanOrderId}`);
    return true;
  } catch (err) {
    console.error(`[iFood] ReadyToPickup failed (${cleanOrderId}):`, err.response?.data || err.message);
    throw err;
  }
}

/**
 * Request cancellation of an order on iFood.
 * iFood docs: POST /order/v1.0/orders/{id}/requestCancellation → 202 Accepted
 * @param {string} orderId       iFood order UUID
 * @param {string} clientId
 * @param {string} clientSecret
 * @param {string} [reason]      cancellation reason code
 * @returns {Promise<boolean>}
 */
async function requestCancellation(orderId, clientId, clientSecret, reason) {
  const token = await getToken(clientId, clientSecret);
  const cleanOrderId = orderId.replace(/[^a-f0-9\-]/gi, '');

  try {
    const url = `${IFOOD_BASE}/order/v1.0/orders/${cleanOrderId}/requestCancellation`;
    console.log(`[iFood] Requesting cancellation: ${cleanOrderId}`);
    const body = reason ? { cancellationCode: reason } : {};
    await axios.post(url, body, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      timeout: 15000,
    });
    console.log(`[iFood] Cancellation requested: ${cleanOrderId}`);
    return true;
  } catch (err) {
    console.error(`[iFood] Cancellation request failed (${cleanOrderId}):`, err.response?.data || err.message);
    throw err;
  }
}

module.exports = {
  pollEvents,
  acknowledgeEvents,
  getOrderDetails,
  confirmOrder,
  dispatchOrder,
  readyToPickupOrder,
  requestCancellation,
};
