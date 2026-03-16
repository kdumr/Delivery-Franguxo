# ─── Build stage ─────────────────────────────────────────────────────────────
FROM node:20-alpine AS base

WORKDIR /app

# Install dependencies first (cached layer)
COPY package.json ./
RUN npm install --omit=dev

# Copy source files
COPY server.js ifood-token.js ifood-orders.js ./

# Copy .env if exists (optional — prefer runtime env vars)
COPY .env* ./

# ─── Runtime ──────────────────────────────────────────────────────────────────
# Create directory for persistent config.json
RUN mkdir -p /app/data

# Run as non-root user for security
RUN addgroup -S ifood && adduser -S ifood -G ifood
RUN chown -R ifood:ifood /app
USER ifood

# Expose port (default 3001)
EXPOSE 3001

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD wget -qO- http://localhost:3001/health || exit 1

CMD ["node", "server.js"]
