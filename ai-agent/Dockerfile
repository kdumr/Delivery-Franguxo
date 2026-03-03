FROM node:20-alpine

# Instala o Redis Server nativamente no Alpine
RUN apk update && apk add redis

WORKDIR /usr/src/app

ARG CACHEBUST=1
COPY package*.json ./

RUN npm install --omit=dev

COPY . .

# Dá permissão ao script de inicialização dupla (Redis + Node)
RUN chmod +x start.sh

EXPOSE 3000

CMD ["./start.sh"]
