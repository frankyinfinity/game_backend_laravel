# Usa un'immagine base di Node.js
FROM node:18

# Crea una directory dentro il container
WORKDIR /app

# Copia i file nella directory di lavoro del container
COPY index.js .

# Se ci sono dipendenze (come un package.json), dovresti copiarle e installarle
# COPY package*.json ./
# RUN npm install

# Comando per eseguire lo script
CMD ["node", "index.js"]
