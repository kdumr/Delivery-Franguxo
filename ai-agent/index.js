require('dotenv').config();
const express = require('express');
const cors = require('cors');
const axios = require('axios');
const { createClient } = require('redis');

const app = express();
app.use(express.json());
app.use(cors());

// URL base onde o WordPress está rodando
const WP_URL = process.env.WP_URL || 'http://localhost/franguxo';

async function fetchConfigFromWP() {
    try {
        const token = process.env.WP_GEMINI_TOKEN || '';
        const response = await axios.get(`${WP_URL}/wp-json/myd-delivery/v1/gemini/config`, {
            timeout: 3000,
            headers: { 'x-gemini-token': token }
        });
        return response.data;
    } catch (e) {
        console.error("ERRO [fetchConfigFromWP]: Falha ao tentar contato ou autenticar com o WordPress", e.message);
        return null; // WordPress deve estar fora do ar, URL incorreta, ou Token inválido
    }
}

/**
 * Consulta pedidos em andamento do respectivo número
 */
async function fetchActiveOrdersFromWP(phoneNumber) {
    try {
        const token = process.env.WP_GEMINI_TOKEN || '';
        const response = await axios.get(`${WP_URL}/wp-json/myd-delivery/v1/gemini/orders/active/${phoneNumber}`, {
            timeout: 3000,
            headers: { 'x-gemini-token': token }
        });
        return response.data;
    } catch (e) {
        console.error(`ERRO [fetchActiveOrdersFromWP] falha para o celular ${phoneNumber}:`, e.message);
        return null; // Erro de autenticação 401 ou erro de servidor.
    }
}

/**
 * Cliente Redis para armazenar histórico e sessão
 */
const redisClient = createClient({
    url: process.env.REDIS_URL || 'redis://localhost:6379'
});

redisClient.on('error', (err) => console.log('Redis Client Error', err));
redisClient.connect().catch(console.error);

/**
 * Recebe todos os Eventos que a Evolution dispara
 */
app.post('/webhook', async (req, res) => {
    // Retorna OK para a Evolution não tentar re-enviar
    res.status(200).send('OK');

    const body = req.body;

    // Log the entire webhook payload for debugging if needed (Evolution V2 formats)
    console.log("[WEBHOOK_RECV] EVENT:", body.event);

    // Verificamos se o evento é "mensagem chegando"
    if (body.event !== 'messages.upsert') {
        return;
    }

    // A Evolution API V1 e V2 pode enviar a mensagem aninhada de formas diferentes
    let messageData = null;
    if (body.data && Array.isArray(body.data.messages) && body.data.messages.length > 0) {
        messageData = body.data.messages[0];
    } else if (body.data && body.data.key) {
        // Formato flat V2 em alguns endpoints
        messageData = body.data;
    } else if (body.data && body.data.message) {
        messageData = body.data;
    } else {
        console.log("[AVISO] Formato de mensagem desconhecido! Body recebido:");
        console.log(JSON.stringify(body, null, 2));
        return;
    }

    // Ignora mensagens enviadas pelo próprio Bot (fromMe)
    const key = messageData.key || {};
    if (key.fromMe || !messageData.message) {
        return;
    }

    // Extrai o número e ignora grupos (@g.us)
    const remoteJid = key.remoteJid;
    if (!remoteJid || remoteJid.includes('@g.us') || remoteJid.includes('status@broadcast')) return;

    const phoneNumber = remoteJid.replace('@s.whatsapp.net', '');

    // Extrai o texto da conversa (Evolution API embute o texto de diferentes formas conforme o tipo)
    let text = null;
    if (messageData.message.conversation) {
        text = messageData.message.conversation;
    } else if (messageData.message.extendedTextMessage && messageData.message.extendedTextMessage.text) {
        text = messageData.message.extendedTextMessage.text;
    } else {
        // Pode ser imagem, áudio, botão, list response... Se não há texto claro, ignora
        return;
    }

    if (!text) return;

    // 1) Busca configurações básicas da Evolution ativas no WordPress
    const wpConfig = await fetchConfigFromWP();
    if (!wpConfig || !wpConfig.evolution_api_url) {
        console.log(`[INFO] Mensagem recebida de ${phoneNumber}, mas a conexão da Evolution API não está cadastrada no WP.`);
        return;
    }

    console.log(`[INFO][${phoneNumber}]: ${text}`);

    // Marca como Lida ou Digitando usando Evolution (Bonus UX assincrono)
    const evoUrl = wpConfig.evolution_api_url?.replace(/\/+$/, '');
    const evoToken = wpConfig.evolution_api_key;
    const evoInstance = wpConfig.evolution_instance; // Note: the WP API returns {"evolution_instance": "..."}

    await simulateTyping(evoUrl, evoToken, evoInstance, phoneNumber);

    // 2) Consulta Redis para ver se o cliente já está em uma sessão de navegação
    const redisKey = `session:${phoneNumber}`;
    let session = await redisClient.get(redisKey);

    let replyText = "";

    // Se a sessão NÃO existe, envia o Menu Principal e cria a sessão (Expira em 2h = 7200s)
    if (!session) {
        replyText = `Olá, seja bem vindo(a) ao Franguxo | Frango Frito.\n\nPara fazer o seu pedido acesse: ${WP_URL}\n\n*1.* Fazer pedido online\n*2.* Acessar cardápio\n*3.* Horário de funcionamento\n*5.* Acessar meus pedidos\n\n_Envie o número da opção desejada._`;

        await redisClient.setEx(redisKey, 7200, JSON.stringify({ state: 'MAIN_MENU', lastInteraction: Date.now() }));
    }
    // Se a sessão JÁ existe, lida com a resposta baseada no Menu
    else {
        let sessionData = JSON.parse(session);
        // Atualiza o Timeout pra mais 2h a partir de agora só pelo fato de ele ter respondido
        await redisClient.expire(redisKey, 7200);

        const choice = text.trim();

        if (sessionData.state === 'MAIN_MENU' || !sessionData.state) {
            if (choice === '1' || choice === '2') {
                replyText = `Para fazer seu pedido acesse: ${WP_URL}\n\nPara voltar ao menu, digite *0*.`;
                sessionData.state = 'SUB_MENU';
            }
            else if (choice === '3') {
                const horas = await fetchStoreHoursFromWP();
                replyText = `🕐 Nossos horários de funcionamento:\n${horas}\n\nPara voltar ao menu, digite *0*.`;
                sessionData.state = 'SUB_MENU';
            }
            else if (choice === '5') {
                const activeOrders = await fetchActiveOrdersFromWP(phoneNumber);
                if (activeOrders && activeOrders.length > 0) {
                    replyText = `📦 Você tem ${activeOrders.length} pedido(s) em andamento:\n\n`;
                    for (const order of activeOrders) {
                        replyText += `*Pedido #${order.numero_pedido}*\nStatus: ${order.status_atual}\n---\n`;
                    }
                    replyText += `\nPara voltar ao menu, digite *0*.`;
                } else {
                    replyText = `No momento não encontramos nenhum pedido ativo vinculado ao número ${phoneNumber}.\n\nPara voltar ao menu, digite *0*.`;
                }
                sessionData.state = 'SUB_MENU';
            }
            else if (choice === '0') {
                replyText = `*1.* Fazer pedido online\n*2.* Acessar cardápio\n*3.* Horário de funcionamento\n*5.* Acessar meus pedidos\n\n_Envie o número da opção desejada._`;
            }
            else {
                replyText = `Opção inválida.\n\nPor favor, digite 1, 2, 3 ou 5 para navegar no menu principal. Se precisar acessar o site: ${WP_URL}`;
            }
        }
        else {
            // Está no SUB_MENU (já escolheu algo antes)
            if (choice === '0') {
                replyText = `*Menu Principal*\n\nPara fazer o seu pedido acesse: ${WP_URL}\n\n*1.* Fazer pedido online\n*2.* Acessar cardápio\n*3.* Horário de funcionamento\n*5.* Acessar meus pedidos\n\n_Envie o número da opção desejada._`;
                sessionData.state = 'MAIN_MENU';
            } else {
                replyText = `Por favor, digite *0* para voltar ao menu principal.`;
            }
        }

        // Salva as atualizações de estado do menu no Redis
        await redisClient.setEx(redisKey, 7200, JSON.stringify(sessionData));
    }

    try {
        await sendEvolutionMessage(evoUrl, evoToken, evoInstance, phoneNumber, replyText);
    } catch (apiError) {
        console.error("[ERRO ENVIO EVO] Falhou: ", apiError.message);
    }
});

/**
 * Função utilitária para chamar a Evolution API e enviar mensagem de volta
 */
async function sendEvolutionMessage(baseUrl, token, instanceName, number, textContent) {
    if (!baseUrl || !instanceName) return;

    // Remove eventuais barras sobrando no final da baseUrl para nao gerar duplas barras ex: //message
    const cleanBaseUrl = baseUrl.replace(/\/+$/, '');

    // Na Evolution API, o endpoint de envio de texto é /message/sendText/{instanceName}
    const endpoint = `${cleanBaseUrl}/message/sendText/${instanceName}`;

    try {
        await axios.post(endpoint, {
            number: number,
            text: textContent
        }, {
            headers: {
                'apikey': token,
                'Content-Type': 'application/json'
            }
        });
    } catch (err) {
        // Log more details about the Evolution API error
        console.error("[EVO_ERR] Enviar Mensagem Falhou:", err.response?.status, err.response?.data || err.message, "URL tentada:", endpoint);
    }
}

/**
 * UX - Enviar status de presença "composing"
 */
async function simulateTyping(baseUrl, token, instanceName, number) {
    if (!baseUrl || !instanceName) return;
    try {
        await axios.post(`${baseUrl}/chat/sendPresence/${instanceName}`, {
            number: number,
            delay: 3000,
            presence: "composing"
        }, { headers: { apikey: token } });
    } catch (e) { } // Falhar UX silenciosamente
}

// Inicializa servidor NodeJS na porta 3000
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 Gemini Evolution AI Server Started na Porta ${PORT}`);
    console.log(`🔗 O WP apontado para Sync de Lojistas é: ${WP_URL}`);
});
