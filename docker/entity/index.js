// entity.js

const http = require('http');

// Leggi i parametri dalle variabili d'ambiente
const entityUid = process.env.ENTITY_UID;
const entityTileI = process.env.ENTITY_TILE_I;
const entityTileJ = process.env.ENTITY_TILE_J;
const backendUrl = process.env.BACKEND_URL;
const apiUserEmail = process.env.API_USER_EMAIL;
const apiUserPassword = process.env.API_USER_PASSWORD;

console.log(`Entity service started.`);
console.log(`Entity UID: ${entityUid}`);
console.log(`Tile Position: (${entityTileI}, ${entityTileJ})`);
console.log(`Using Credentials: ${apiUserEmail} / ${apiUserPassword ? '******' : 'MISSING'}`);

// Variabili per tracciare la posizione attuale
let currentTileI = entityTileI;
let currentTileJ = entityTileJ;

// Funzione per recuperare la posizione attuale dall'API
function fetchCurrentPosition() {
  const path = `/entities/position?uid=${entityUid}`;

  const options = {
    hostname: new URL(backendUrl).hostname,
    port: new URL(backendUrl).port || 80,
    path: path,
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Basic ' + Buffer.from(apiUserEmail + ':' + apiUserPassword).toString('base64')
    },
  };

  const req = http.request(options, (res) => {
    let data = '';

    res.on('data', (chunk) => {
      data += chunk;
    });

    res.on('end', () => {
      try {
        const response = JSON.parse(data);
        if (response.success) {
          currentTileI = response.tile_i;
          currentTileJ = response.tile_j;
          console.log(`[Entity ${entityUid}] Still alive... Position: (${currentTileI}, ${currentTileJ})`);
        } else {
          console.error(`Errore nel recupero della posizione: ${response.message}`);
        }
      } catch (error) {
        console.error(`Errore nel parsing della risposta: ${error.message}`);
        console.error(`Risposta ricevuta (primi 500 caratteri): ${data.substring(0, 500)}`);
      }
    });
  });

  req.on('error', (error) => {
    console.error(`Errore nella richiesta della posizione: ${error.message}`);
  });

  req.end();
}

// Timer ogni 5 secondi che chiama l'API
setInterval(() => {
  fetchCurrentPosition();
}, 5000);