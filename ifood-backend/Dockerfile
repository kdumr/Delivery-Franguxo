# ─── Build stage ─────────────────────────────────────────────────────────────
FROM node:20-alpine

WORKDIR /app

COPY package.json ./
RUN npm install --omit=dev

COPY server.js ifood-token.js ifood-orders.js ./

RUN mkdir -p /app/data

EXPOSE 7000

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD wget -qO- http://localhost:7000/health || exit 1

CMD ["node", "server.js"]
