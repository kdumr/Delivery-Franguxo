#!/bin/sh

# Inicia o servidor Redis em background usando o shell nativo
redis-server > /dev/null 2>&1 &

# Aguarda o redis subir silenciosamente
sleep 2

# Inicia o App Node.js
node index.js
