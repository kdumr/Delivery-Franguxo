'use strict';
/**
 * iFood Token Manager
 * Manages the OAuth token lifecycle for the iFood Merchant API.
 * Automatically refreshes when close to expiration.
 */

const IFOOD_AUTH_URL = 'https://merchant-api.ifood.com.br/authentication/v1.0/oauth/token';

let _token = null;
let _expiresAt = 0; // Unix timestamp in ms

/**
 * Returns a valid access token, refreshing if necessary.
 * @param {string} clientId
 * @param {string} clientSecret
 * @returns {Promise<string>} accessToken
 */
async function getIfoodToken(clientId, clientSecret) {
  const now = Date.now();
  const bufferMs = 5 * 60 * 1000; // refresh 5 min before expiry

  if (_token && _expiresAt - bufferMs > now) {
    return _token;
  }

  console.log('[iFood Token] Fetching new access token...');

  const body = new URLSearchParams({
    grantType: 'client_credentials',
    clientId,
    clientSecret,
    authorizationCode: '',
    authorizationCodeVerifier: '',
    refreshToken: '',
  });

  const res = await fetch(IFOOD_AUTH_URL, {
    method: 'POST',
    headers: {
      'accept': 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body,
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`iFood auth failed (${res.status}): ${text}`);
  }

  const data = await res.json();

  if (!data.accessToken) {
    throw new Error('iFood auth response missing accessToken');
  }

  _token = data.accessToken;
  const expiresIn = data.expiresIn || 3600;
  _expiresAt = now + expiresIn * 1000;

  console.log(`[iFood Token] Token obtained. Expires in ${expiresIn}s.`);
  return _token;
}

/**
 * Force-clears the cached token (call on auth error).
 */
function invalidateToken() {
  _token = null;
  _expiresAt = 0;
}

module.exports = { getIfoodToken, invalidateToken };
