# Usa un'immagine base di Node.js
FROM node:18

# Crea una directory dentro il container
WORKDIR /app

# Copia i file nella directory di lavoro del container
COPY index.js .

# Copia package.json e installa le dipendenze
COPY package*.json ./
RUN npm install

# Esponi la porta per il WebSocket
EXPOSE 8080

# Comando per eseguire lo script
CMD ["node", "index.js"]
