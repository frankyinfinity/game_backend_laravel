FROM node:18-alpine

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .

# Comando che verrà eseguito quando il container si avvia
# Passa l'entity ID come argomento
CMD ["node", "entity.js"]