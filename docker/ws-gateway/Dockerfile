FROM node:18

WORKDIR /app

COPY index.js ./
COPY package*.json ./

RUN npm install --omit=dev

CMD ["node", "index.js"]
