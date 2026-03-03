require('dotenv').config();
const express = require('express');
const cors = require('cors');
const axios = require('axios');
const { GoogleGenAI } = require('@google/genai');

const app = express();
app.use(express.json());
app.use(cors());

// URL base onde o WordPress está rodando
const WP_URL = process.env.WP_URL || 'http://localhost/franguxo';

/**
 * Helper para buscar do Wordpress a API_KEY do Lojista, o 
 * System Prompt que o dono cadastrou, e a instância da Evolution API ativa.
 */
async function fetchConfigFromWP() {
    try {
        const response = await axios.get(`${WP_URL}/wp-json/myd-delivery/v1/gemini/config`, { timeout: 3000 });
        return response.data;
    } catch (e) {
        console.error("ERRO [fetchConfigFromWP]: Falha ao tentar contato com o WordPress", e.message);
        return null; // WordPress deve estar fora do ar ou URL incorreta
    }
}

/**
 * Histórico e estado em memória básico (MVP)
 * Evitamos usar banco de dados externo agora para manter a migração simples.
 * Mapearemos `numeroCelular => [history]`
 */
const conversationHistory = new Map();

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

    // 1) Busca configurações em tempo real do WordPress
    const wpConfig = await fetchConfigFromWP();
    if (!wpConfig || wpConfig.gemini_enabled !== true || !wpConfig.gemini_api_key) {
        console.log(`[INFO] Mensagem recebida de ${phoneNumber}, mas a IA está desativada no WP.`);
        return;
    }

    console.log(`[INFO][${phoneNumber}]: ${text}`);

    // Marca como Lida ou Digitando usando Evolution (Bonus UX assincrono)
    const evoUrl = wpConfig.evolution_api_url?.replace(/\/+$/, '');
    const evoToken = wpConfig.evolution_api_key;
    const evoInstance = wpConfig.evolution_instance; // Note: the WP API returns {"evolution_instance": "..."}

    await simulateTyping(evoUrl, evoToken, evoInstance, phoneNumber);

    const startTime = Date.now();
    try {
        const ai = new GoogleGenAI({ apiKey: wpConfig.gemini_api_key });
        const systemInstruction = wpConfig.gemini_system_prompt || "You are a helpful assistant.";

        const promptParams = {
            contents: text,
            config: {
                systemInstruction: systemInstruction
            }
        };

        let reply = "";
        try {
            // Tenta primeiro o modelo 2.0 que é o default
            const response = await ai.models.generateContent({
                model: 'gemini-2.0-flash',
                contents: promptParams.contents,
                config: promptParams.config
            });
            reply = response.text;
        } catch (modelErr) {
            // Conta de teste ou Free Tier pode bater limite rápido no 2.0.
            // Caimos pro 1.5-flash de fallback:
            const status = modelErr.response?.status || modelErr.status;
            if (status === 429) {
                console.log("[AVISO] Cota do Gemini 2.0 excedida! Tentando fallback para gemini-1.5-flash...");
                const responseFallback = await ai.models.generateContent({
                    model: 'gemini-1.5-flash',
                    contents: promptParams.contents,
                    config: promptParams.config
                });
                reply = responseFallback.text;
            } else {
                throw modelErr; // Repassa erro se não for cota
            }
        }

        console.log(`[SUCESSO] Gemini gerou resposta em ${Date.now() - startTime}ms`);
        await sendEvolutionMessage(evoUrl, evoToken, evoInstance, phoneNumber, reply);

    } catch (apiError) {
        console.error("[ERRO_GEMINI API] Falhou: ", apiError.response?.data || apiError.message);
        // Mensagem utilitária se de fato ambos os modelos estourarem a cota
        if (apiError.response?.status === 429 || apiError.message?.includes('429')) {
            await sendEvolutionMessage(evoUrl, evoToken, evoInstance, phoneNumber, "Desculpe, nosso atendente virtual está sobrecarregado no momento (Muitos pedidos!). Por favor, aguarde um humano.");
        }
    }
});

/**
 * Função utilitária para chamar a Evolution API e enviar mensagem de volta
 */
async function sendEvolutionMessage(baseUrl, token, instanceName, number, textContent) {
    if (!baseUrl || !instanceName) return;

    const endpoint = `${baseUrl}/message/sendText/${instanceName}`;
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
        console.error("[EVO_ERR] Enviar Mensagem Falhou:", err.message);
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
