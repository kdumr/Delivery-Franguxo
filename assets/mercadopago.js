// Integração MercadoPago.JS V2 SDK
// Este arquivo inicializa o SDK e expõe funções para tokenização e deviceId

let mp = null;
let mpDeviceId = null;

/**
 * Inicializa o MercadoPago SDK usando a public key vinda do backend.
 * @param {string} publicKey
 */
async function initMercadoPago(publicKey) {
    if (!publicKey) {
        console.error('MercadoPago public key não fornecida.');
        return;
    }
    if (!window.MercadoPago) {
        console.error('MercadoPago.JS V2 SDK não carregado.');
        return;
    }
    mp = new window.MercadoPago(publicKey, {
        locale: 'pt-BR'
    });
    // Device ID é gerado automaticamente pelo SDK e pode ser acessado via mp.deviceSessionId
    mpDeviceId = mp.deviceSessionId;
    console.log('MercadoPago SDK inicializado. Device ID:', mpDeviceId);
}

/**
 * Retorna o device session id atual (anti-fraude)
 */
function getMercadoPagoDeviceId() {
    return mpDeviceId;
}

/**
 * Tokeniza um cartão de crédito usando o SDK
 * @param {object} cardData
 * @returns {Promise<object>} token response
 */
async function tokenizeCard(cardData) {
    if (!mp) throw new Error('MercadoPago SDK não inicializado');
    return await mp.card.createToken(cardData);
}

window.initMercadoPago = initMercadoPago;
window.getMercadoPagoDeviceId = getMercadoPagoDeviceId;
window.tokenizeCard = tokenizeCard;
