// entity.js
const args = process.argv.slice(2);
const entityIdArg = args.find(arg => arg.startsWith('--entity-id='));
const entityId = entityIdArg ? entityIdArg.split('=')[1] : 'unknown';

console.log(`[Entity ${entityId}] Entity service started.`);
console.log(`[Entity ${entityId}] Simulating some game logic for entity ID: ${entityId}`);

// Per ora, solo un log che mostra che è attivo
// Mantiene il processo vivo
setInterval(() => {
  console.log(`[Entity ${entityId}] Still alive...`);
}, 10000);