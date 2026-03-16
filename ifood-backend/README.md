# iFood Backend

Backend Node.js intermediário entre iFood e WordPress, implantado via Docker no EasyPanel.

## Fluxo

```
iFood → POST /ifood/webhook → (processa evento) → WordPress REST API
         └── Polling 30s (fallback se webhook silent > 60s)
```

## Configuração

### 1. Clone / faça upload para o EasyPanel

No EasyPanel, crie um novo app de tipo **Docker** apontando para este diretório.

### 2. Variáveis de Ambiente

Copie `.env.example` como `.env` e preencha:

| Variável | Descrição |
|---|---|
| `PORT` | Porta do servidor (padrão: `3000`) |
| `BACKEND_SECRET` | Segredo compartilhado com o WordPress |
| `IFOOD_CLIENT_ID` | Client ID gerado no portal iFood |
| `IFOOD_CLIENT_SECRET` | Client Secret do portal iFood |
| `IFOOD_MERCHANT_ID` | ID do restaurante no iFood |
| `WP_BASE_URL` | URL do WordPress (ex: `https://franguxo.app.br`) |
| `WP_API_SECRET` | Segredo para validar o recebimento no WordPress |

> As variáveis iFood e WordPress também podem ser configuradas dinamicamente via push do WordPress (endpoint `/config`).

### 3. Webhook URL no Portal iFood

No [Portal do Desenvolvedor iFood](https://developer.ifood.com.br), cadastre como URL de Webhook:

```
https://SEU-BACKEND.easypanel.host/ifood/webhook
```

### 4. Configurar o WordPress

No painel de Settings → iFood, preencha:
- Client ID / Client Secret
- Merchant ID
- URL do Backend + Backend Secret

O WordPress fará automaticamente um push de configurações para este backend.

## Endpoints

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/health` | Status e informações do backend |
| `POST` | `/ifood/webhook` | Recebe eventos do iFood |
| `POST` | `/config` | Push de configurações do WordPress |

## Desenvolvimento Local

```bash
cp .env.example .env
# edite o .env
npm install
npm run dev
```

Ou via Docker:

```bash
docker-compose up
```
