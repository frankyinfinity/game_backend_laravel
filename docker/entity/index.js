// entity.js

// Leggi i parametri dalle variabili d'ambiente
const entityUid = process.env.ENTITY_UID;
const entityTileI = process.env.ENTITY_TILE_I;
const entityTileJ = process.env.ENTITY_TILE_J;

console.log(`Entity service started.`);
console.log(`Entity UID: ${entityUid}`);
console.log(`Tile Position: (${entityTileI}, ${entityTileJ})`);

// Per ora, solo un log che mostra che è attivo
// Mantiene il processo vivo
setInterval(() => {
  console.log(`[Entity ${entityUid}] Still alive... Position: (${entityTileI}, ${entityTileJ})`);
}, 10000);