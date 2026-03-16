# iFood Backend

Serviço Node.js dedicado à integração do iFood com o WordPress.

## Arquitetura

```
iFood
  ├── Webhook → POST /ifood/webhook  (primário)
  └── Polling ← events:polling       (fallback a cada 30s)
         ↓
  iFood Backend (este serviço)
  ├── Valida HMAC, processa eventos
  ├── Busca detalhes do pedido (PLACED)
  ├── Acknowledgment automático
  └── POST /wp-json/myd-delivery/v1/ifood/create-order
         ↓
  WordPress → cria myd_order
```

## Configuração

### 1. Copie o `.env.example`

```bash
cp .env.example .env
```

Edite o `.env` com suas credenciais.

### 2. Rodar com Docker Compose

```bash
docker compose up -d
```

### 3. Rodar manualmente

```bash
npm install
node server.js
```

## Variáveis de Ambiente

| Variável | Descrição |
|---|---|
| `PORT` | Porta do servidor (padrão: `3001`) |
| `BACKEND_SECRET` | Segredo para autenticar o WordPress ao fazer push de config |
| `WP_BASE_URL` | URL base do WordPress (ex: `https://seusite.com.br`) |
| `WP_API_SECRET` | Segredo usado no header `X-MyD-Secret` ao criar pedidos no WP |
| `IFOOD_CLIENT_ID` | Client ID iFood (opcional se usar push via WP) |
| `IFOOD_CLIENT_SECRET` | Client Secret iFood (opcional se usar push via WP) |
| `IFOOD_MERCHANT_ID` | Merchant ID iFood (opcional se usar push via WP) |

## Endpoints

| Método | Rota | Descrição |
|---|---|---|
| `POST` | `/ifood/webhook` | Recebe eventos do iFood (webhook) |
| `POST` | `/config` | WordPress envia config `{ merchantId, clientId, ... }` |
| `GET` | `/ifood/status` | Status atual do serviço |
| `GET` | `/health` | Health check |

## EasyPanel / Deploy

Configure as variáveis de ambiente no painel e aponte o build para este diretório.

**Webhook URL para configurar no portal iFood:**
```
https://SEU-DOMINIO/ifood/webhook
```
