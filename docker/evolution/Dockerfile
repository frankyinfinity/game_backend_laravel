FROM node:18

# Crea una directory dentro il container
WORKDIR /app

# Copia i file nella directory di lavoro del container
COPY index.js .
COPY package*.json ./
RUN npm install

CMD ["node", "index.js"]