# ─── Build stage ─────────────────────────────────────────────────────────────
FROM node:20-alpine AS base

WORKDIR /app

COPY package.json ./
RUN npm install --omit=dev

COPY server.js ifood-token.js ifood-orders.js ./
COPY .env* ./

RUN mkdir -p /app/data

RUN addgroup -S ifood && adduser -S ifood -G ifood
RUN chown -R ifood:ifood /app
USER ifood

# Porta padrão 80 (igual ao PORT no .env)
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD wget -qO- http://localhost:80/health || exit 1

CMD ["node", "server.js"]
