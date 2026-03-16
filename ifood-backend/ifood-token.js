'use strict';
/**
 * iFood Token Manager
 * Manages the OAuth token lifecycle for the iFood Merchant API.
 * Automatically refreshes when close to expiration.
 */

const IFOOD_AUTH_URL = 'https://merchant-api.ifood.com.br/authentication/v1.0/oauth/token';

let _token = null;
let _expiresAt = 0;

async function getIfoodToken(clientId, clientSecret) {
  const now = Date.now();
  const bufferMs = 5 * 60 * 1000;

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
    headers: { accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
    body,
  });

  if (!res.ok) throw new Error(`iFood auth failed (${res.status}): ${await res.text()}`);

  const data = await res.json();
  if (!data.accessToken) throw new Error('iFood auth: missing accessToken in response');

  _token = data.accessToken;
  _expiresAt = now + (data.expiresIn || 3600) * 1000;
  console.log(`[iFood Token] Token obtained. Expires in ${data.expiresIn || 3600}s`);
  return _token;
}

function invalidateToken() {
  _token = null;
  _expiresAt = 0;
}

module.exports = { getIfoodToken, invalidateToken };
