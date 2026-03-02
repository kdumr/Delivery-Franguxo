FROM node:20-alpine

WORKDIR /usr/src/app

COPY ai-agent/package*.json ./

RUN npm install --omit=dev

COPY ai-agent/ .

EXPOSE 3000

CMD [ "node", "index.js" ]
