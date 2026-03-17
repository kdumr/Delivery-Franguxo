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
 * Confirm an order in iFood.
 * @param {string} orderId 
 * @param {string} clientId 
 * @param {string} clientSecret 
 */
async function confirmOrder(orderId, clientId, clientSecret) {
  const token = await getToken(clientId, clientSecret);
  const cleanOrderId = orderId.replace(/[^a-f0-9\-]/gi, '');

  try {
    const url = `${IFOOD_BASE}/order/v1.0/orders/${cleanOrderId}/confirm`;
    console.log(`[iFood] Confirming Order: ${cleanOrderId}`);
    
    await axios.post(url, {}, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
      timeout: 15000,
    });
    console.log(`[iFood] Order ${cleanOrderId} confirmed successfully!`);
    return true;
  } catch (err) {
    console.error(`[iFood] Confirm Order Failed (${cleanOrderId}):`, err.response?.data || err.message);
    throw err;
  }
}

module.exports = { pollEvents, acknowledgeEvents, getOrderDetails, confirmOrder };
