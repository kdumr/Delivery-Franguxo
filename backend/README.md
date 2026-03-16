# MyDelivery Push Serv3. Rode o container:
   ```bash
   docker run -d -p 3000:3000 --env-file .env myd-push-server
   ```

   Para HTTP (sem SSL), certifique-se de que `USE_HTTPS=false` no .env.
Servidor seguro de push em tempo real usando Socket.IO para atualizar badges de pedidos.

## Segurança
- Usa HTTPS/WSS com certificado auto-assinado (substitua por certificado real em produção).
- Autenticação com JWT: clientes e WordPress precisam de token válido.
- Endpoint /notify protegido por token para evitar interceptações.

## Como rodar com Docker

1. Copie `.env.example` para `.env` e configure as variáveis:
   ```bash
   cp .env.example .env
   # Edite .env com seus valores
   ```

2. Construa a imagem:
   ```bash
   docker build -t myd-push-server .
   ```

3. Rode o container:
   ```bash
   docker run -p 3000:3000 --env-file .env myd-push-server
   ```

4. Ou use docker-compose:
   ```bash
   docker-compose up -d
   ```

5. Para produção, use certificado real:
   - Monte volumes para key.pem e cert.pem, ou gere dentro do container.

## Deploy no EasyPanel (recomendado)

- Configure `.env` com `PORT=80` e `USE_HTTPS=false` (deixe o EasyPanel cuidar do TLS).
- No EasyPanel, crie um container a partir desta imagem e mapeie a porta externa 80 (ou 443 no painel) para a porta interna 80 do container.
- Defina a variável de ambiente `SECRET_KEY` no painel para o mesmo valor usado no WordPress (`myd_push_secret`).

Exemplo de comando de deploy local (para testar):
```bash
docker build -t myd-push-server .
docker run -d -p 80:80 --env-file .env myd-push-server
```

## Configuração no WordPress

1. Adicione opções no WP (via wp-admin ou código):
   - `myd_push_server_url`: https://your-push-server.com
   - `myd_push_secret`: your-super-secret-key (mesmo que SECRET_KEY no Docker)

2. O plugin já chama o notifier quando order_status muda ou em process_payment.

## Frontend (Browser)

1. Inclua socket.io-client no seu site (via CDN ou npm):
   ```html
   <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
   ```

2. O código em `assets/js/profile-bar.js` já conecta automaticamente se `sessionStorage.mydCurrentUser` existir.

3. Para obter token, o JS faz POST /wp-json/myd-delivery/v1/push/auth com myd_customer_id.

## Desenvolvimento
- Para gerar certificado: rode `npm run generate-cert`
- Porta padrão: 3000
- Ambiente: SECRET_KEY via .env