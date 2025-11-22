// entity.js

console.log(`Entity service started.`);

// Per ora, solo un log che mostra che è attivo
// Mantiene il processo vivo
setInterval(() => {
  console.log(`[Entity Still alive...`);
}, 10000);