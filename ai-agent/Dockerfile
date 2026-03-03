FROM node:20-alpine

WORKDIR /usr/src/app

ARG CACHEBUST=1
COPY package*.json ./

RUN npm install --omit=dev

COPY . .

EXPOSE 3000

CMD [ "node", "index.js" ]
