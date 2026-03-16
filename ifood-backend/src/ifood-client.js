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

  try {
    const response = await axios.get(`${IFOOD_BASE}/order/v1.0/orders/${orderId}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
      timeout: 15000,
    });
    return response.data;
  } catch (err) {
    console.error(`[iFood] Get Order Details Failed (${orderId}):`, err.response?.data || err.message);
    throw err;
  }
}

module.exports = { pollEvents, acknowledgeEvents, getOrderDetails };
