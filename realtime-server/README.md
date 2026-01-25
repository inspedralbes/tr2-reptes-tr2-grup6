# Servidor Socket.io Real-Time - KAIROS

Servidor Node.js que gestiona les notificacions i els blocs de slots en temps real.

## Funcionalitats

### 1. **Bloc de Slots (slot:lock)**
- Impedeix que dos docents seleccioni el mateix slot simultàniament
- Els locks expiren automàticament després de 5 minuts
- Les altres connexions reben notificacions en temps real

### 2. **Reserva de Slots (slot:book)**
- Confirma i emmagatzema la reserva a BD
- Allibera automàticament el lock
- Notifica a tots els clients

### 3. **Notificacions (notification:send)**
- Envia notificacions personalitzades a usuaris específics
- Emmagatzema les notificacions a BD
- Els usuaris reben notificacions en temps real si estan conectats

## Arquitectura

```
realtime-server/
├── server.js          # Servidor Socket.io principal
├── package.json       # Dependències
└── README.md          # Aquesta documentació
```

## Dependències

- **socket.io**: Comunicació en temps real
- **mysql2**: Connexió a BD
- **express**: Servidor HTTP base
- **dotenv**: Variables d'entorn

## Guia de Desenvolupament

### Connectar des del Frontend

```javascript
import io from 'socket.io-client'

const socket = io('ws://localhost:3000')

// Unir-se a sala de notificacions
socket.emit('user:join', userId)

// Escolta de notificacions
socket.on('notification:received', (data) => {
  console.log('Notificació:', data.title, data.message)
})

// Blocar slot
socket.emit('slot:lock', {
  slotId: 1,
  userId: 123,
  allocationId: 456
})

// Escolta de confirmació de lock
socket.on('slot:lock:success', (data) => {
  console.log('Slot blocat per 5 minuts')
})
```

## Events Disponibles

### Client → Server

| Event | Dades | Descripció |
|-------|-------|-----------|
| `slot:lock` | `{slotId, userId, allocationId}` | Blocar slot per a selecció |
| `slot:book` | `{slotId, userId, allocationId}` | Reservar slot definitivament |
| `slot:unlock` | `{slotId}` | Alliberar lock sense reservar |
| `notification:send` | `{userId, title, message, type}` | Enviar notificació |
| `user:join` | `userId` | Unir-se a sala personal |

### Server → Client

| Event | Dades | Descripció |
|-------|-------|-----------|
| `slot:locked` | `{slotId, lockedBy, color}` | Slot blocat per altres |
| `slot:booked` | `{slotId, bookedBy, color}` | Slot reservat definitivament |
| `slot:lock:released` | `{slotId}` | Lock alliberat |
| `notification:received` | `{title, message, type}` | Notificació rebuda |
| `slot:lock:success` | `{success, slotId, expiresIn}` | Lock creat correctament |
| `slot:book:success` | `{success, slotId, message}` | Reserva completada |

## Monitoratge

### Logs Disponibles

```
✅ Usuari connectat: socket_id
👤 Usuari userId s'ha unit a sala personal
❌ Usuari desconnectat: socket_id
🚀 Servidor Socket.io escoltant al port 3000
```

## Desenvolupament Local

```bash
# Instal·lar dependències
npm install

# Executar en mode producció
npm start

# Executar en mode desenvolupament (watch mode)
npm run dev
```

---

**Versió:** 1.0.0  
**Data:** Gener 2026  
**Status:** ✅ COMPLET
