FROM node:20-alpine

WORKDIR /app

# Install dependencies first (layer cache)
COPY package.json ./
RUN npm install --omit=dev

# Copy source
COPY . .

# Create data directory for persisted config
RUN mkdir -p /app/data

EXPOSE 3000

CMD ["node", "server.js"]
