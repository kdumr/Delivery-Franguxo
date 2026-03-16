'use strict';
const axios = require('axios');

const IFOOD_BASE = 'https://merchant-api.ifood.com.br';

/**
 * Holds the current access token + expiry per clientId
 * { [clientId]: { accessToken, expiresAt } }
 */
const tokenCache = {};

/**
 * Get (or refresh) iFood access token.
 * Automatically refreshes when < 5 minutes remain.
 * @param {string} clientId
 * @param {string} clientSecret
 * @returns {Promise<string>} accessToken
 */
async function getToken(clientId, clientSecret) {
  const cached = tokenCache[clientId];
  if (cached && cached.expiresAt - Date.now() > 5 * 60 * 1000) {
    return cached.accessToken;
  }

  const params = new URLSearchParams({
    grantType: 'client_credentials',
    clientId,
    clientSecret,
    authorizationCode: '',
    authorizationCodeVerifier: '',
    refreshToken: '',
  });

  const response = await axios.post(
    `${IFOOD_BASE}/authentication/v1.0/oauth/token`,
    params.toString(),
    {
      headers: {
        accept: 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      timeout: 15000,
    }
  );

  const { accessToken, expiresIn } = response.data;
  tokenCache[clientId] = {
    accessToken,
    expiresAt: Date.now() + expiresIn * 1000,
  };

  console.log(`[TokenManager] Token refreshed for clientId=${clientId.substring(0, 8)}...`);
  return accessToken;
}

module.exports = { getToken };
