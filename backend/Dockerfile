# Use Node.js Alpine image
FROM node:18-alpine

# Set working directory
WORKDIR /app

# Copy package.json and install dependencies
COPY package.json ./
# Use npm ci when possible for reproducible installs, fall back to npm install
RUN if [ -f package-lock.json ]; then npm ci --only=production; else npm install --only=production; fi

# Copy server files
COPY server.js ./
COPY test.html ./
COPY .env* ./

# Generate self-signed certificate if not provided
RUN if [ ! -f key.pem ] || [ ! -f cert.pem ]; then \
      apk add --no-cache openssl && \
      openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 365 -nodes -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"; \
    fi

# Expose container port (match PORT in .env, default 80 for EasyPanel HTTP)
EXPOSE 80

# Start the server
CMD ["npm", "start"]