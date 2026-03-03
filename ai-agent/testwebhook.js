const axios = require('axios');

async function test_webhook() {
    const payload = {
        event: "messages.upsert",
        data: {
            messages: [{
                key: {
                    remoteJid: "5511999999999@s.whatsapp.net",
                    fromMe: false
                },
                message: {
                    conversation: "Teste NodeJS Webhook IA Server"
                }
            }]
        }
    };

    try {
        console.log("Enviando POST...");
        // URL enviada pelo Lojista no print
        const res = await axios.post("https://n8n-teste-ai.ojhhy6.easypanel.host/webhook", payload);
        console.log("STATUS HTTP:", res.status);
        console.log("BODY RESPOSTA HTTP:", res.data);
    } catch (e) {
        console.log("Error:", e.message);
    }
}

test_webhook();
