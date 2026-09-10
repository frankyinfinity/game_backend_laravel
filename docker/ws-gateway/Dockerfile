FROM node:18

WORKDIR /app

COPY index.js ./
COPY package*.json ./

RUN npm install --omit=dev

# Esponi la porta del gateway WebSocket
EXPOSE 9001

CMD ["node", "index.js"]
