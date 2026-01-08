# WebSocket Entity Command System

## 📋 Panoramica

Ogni container entity ora ha un **WebSocket server** che permette di inviare comandi specifici dal backend Laravel a singoli container.

## 🏗️ Architettura

### Container Entity
- Ogni container avvia un **WebSocket server** sulla porta `8080` (interna)
- La porta host viene assegnata **dinamicamente** (partendo da 9001) e salvata nella tabella `containers`.
- Il sistema Laravel recupera automaticamente la porta corretta dal database.

### Mapping Porte (Automatico)
I container vengono creati con il mapping corretto:
`localhost:{ws_port_dal_db} → container:8080`

La porta viene salvata nel campo `ws_port` della tabella `containers`.

## 🚀 Utilizzo

### 1. Avviare i container

Usa il comando artisan per creare le entità (questo le creerà con la configurazione WebSocket corretta):

```bash
php artisan app:test-docker
```

Il sistema assegnerà automaticamente le porte e le salverà nel database.

### 2. Inviare comandi via Laravel

#### Comando: `entity:send`

Il comando ora recupera la porta direttamente dal database.

**Sintassi:**
```bash
php artisan entity:send {uid} {command} [--action=ACTION] [--params=JSON]
```

#### Esempi:

**a) Muovere un'entity verso l'alto:**
```bash
php artisan entity:send entity_uid_1 move --action=up
```

**b) Muovere un'entity verso destra:**
```bash
php artisan entity:send entity_uid_2 move --action=right
```

**c) Ottenere la posizione corrente:**
```bash
php artisan entity:send entity_uid_1 get_position
```

**d) Passare parametri custom (JSON):**
```bash
php artisan entity:send entity_uid_1 custom_command --params='{"key":"value"}'
```

## 🔧 Comandi disponibili

### `move`
Muove l'entity in una direzione specifica.

**Parametri:**
- `action`: `up`, `down`, `left`, `right`

**Esempio:**
```bash
php artisan entity:send abc123 move --action=up
```

**Risposta:**
```json
{
  "success": true,
  "action": "up",
  "message": "Movement executed"
}
```

### `get_position`
Ottiene la posizione corrente dell'entity.

**Esempio:**
```bash
php artisan entity:send abc123 get_position
```

**Risposta:**
```json
{
  "success": true,
  "entity_uid": "abc123",
  "position": {
    "i": 5,
    "j": 10
  }
}
```

## 🛠️ Aggiungere nuovi comandi

### Nel container (index.js)

Modifica la funzione `handleWebSocketCommand`:

```javascript
function handleWebSocketCommand(data, ws) {
  const { command, params } = data;

  switch (command) {
    case 'move':
      // ... existing code ...
      break;

    case 'get_position':
      // ... existing code ...
      break;

    // Aggiungi il tuo nuovo comando qui
    case 'my_custom_command':
      // La tua logica
      ws.send(JSON.stringify({
        success: true,
        message: 'Custom command executed'
      }));
      break;

    default:
      ws.send(JSON.stringify({
        success: false,
        error: `Unknown command: ${command}`
      }));
  }
}
```

## 📝 Note importanti

1. **Calcolo porta WebSocket**: La porta si calcola come `9000 + entity.id` (dove `id` è l'ID nel database)

2. **Timeout**: Il comando Laravel ha un timeout di 5 secondi per la connessione WebSocket

3. **Formato messaggi**: Tutti i messaggi sono in formato JSON

4. **Errori comuni**:
   - `Impossibile connettersi`: Il container non è in esecuzione o la porta è sbagliata
   - `WebSocket handshake fallito`: Problema di connessione
   - `Entity non trovata`: UID non presente nel database

## 🔍 Debug

Per vedere i log del WebSocket nel container:

```bash
docker logs -f entity_1
```

Vedrai messaggi come:
```
WebSocket server listening on port 8080
[WebSocket] Client connected to entity abc123
[WebSocket] Received command: { command: 'move', params: { action: 'up' } }
[Entity abc123] Movement performed: up
```

## 🎯 Workflow completo

1. **Crea entity nel database** → ottieni `id` e `uid`
2. **Configura docker-compose** → porta = 9000 + id
3. **Avvia container** → `docker-compose up -d`
4. **Invia comandi** → `php artisan entity:send {uid} {command}`
5. **Verifica risposta** → controlla output del comando

## ✅ Esempio completo

```bash
# 1. Avvia i container
docker-compose -f docker-compose.entities.example.yml up -d

# 2. Verifica che siano attivi
docker ps

# 3. Invia un comando di movimento
php artisan entity:send entity_uid_1 move --action=right

# Output atteso:
# ✅ Comando inviato con successo!
# Risposta: {
#   "success": true,
#   "action": "right",
#   "message": "Movement executed"
# }

# 4. Verifica la nuova posizione
php artisan entity:send entity_uid_1 get_position
```

---

**Creato per:** Sistema di gestione entity con Docker + Laravel + WebSocket
**Versione:** 1.0.0
