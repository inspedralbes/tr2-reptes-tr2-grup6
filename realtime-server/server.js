import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';
import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

const app = express();
const httpServer = createServer(app);
app.use(express.json());

const io = new Server(httpServer, {
  cors: {
    origin: [
      process.env.FRONTEND_URL || 'http://localhost:5174',
      'http://localhost:5173',
      'http://localhost:5175'
    ],
    methods: ['GET', 'POST']
  }
});

// Pool de connexions a BD
const pool = mysql.createPool({
  host: process.env.DB_HOST || 'db',
  user: process.env.DB_USER || 'kairos_user',
  password: process.env.DB_PASSWORD || 'kairos_secret_password_change_this',
  database: process.env.DB_NAME || 'kairos_db',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// Emmagatzematge de locks de slots (en memòria)
// NOTE: El backend PHP continua sent la font de veritat per lock/book.
// El servidor en temps real només notifica events perquè altres clients
// actualitzin l'estat de la UI immediatament.
const slotLocks = new Map();

/**
 * Event: Connexió d'usuari
 */
io.on('connection', (socket) => {
  console.log(`✅ Usuari connectat: ${socket.id}`);
  
  /**
   * Blocar un slot per evitar seleccions simultànies (broadcast only)
   * Event: slot:lock
   */
  socket.on('slot:lock', (data = {}) => {
    const { slotId, userId, allocationId = null, workshopId = null } = data;

    try {
      if (!slotId || !userId) {
        socket.emit('slot:lock:error', {
          success: false,
          message: 'Falten slotId o userId'
        });
        return;
      }

      // Comprovar si el slot ja està blocat (evita duplicats)
      if (slotLocks.has(slotId)) {
        const existingLock = slotLocks.get(slotId);
        socket.emit('slot:lock:failed', {
          success: false,
          message: 'Slot ja està blocat per un altre usuari',
          locked_by: existingLock.userId
        });
        return;
      }

      // Crear lock temporal en memòria per broadcast
      const lockTimeout = setTimeout(() => {
        slotLocks.delete(slotId);
        if (workshopId) {
          io.to(`workshop:${workshopId}`).emit('slot:lock:released', { slotId, workshopId });
        }
        io.emit('slot:lock:released', { slotId, workshopId });
      }, 5 * 60 * 1000); // 5 minuts

      slotLocks.set(slotId, {
        userId,
        workshopId,
        allocationId,
        socketId: socket.id,
        timeout: lockTimeout
      });

      // Notificar clients de la mateixa sala de taller i global per fallback
      if (workshopId) {
        socket.join(`workshop:${workshopId}`);
        io.to(`workshop:${workshopId}`).emit('slot:locked', {
          slotId,
          workshopId,
          lockedBy: userId,
          allocationId
        });
      }

      io.emit('slot:locked', {
        slotId,
        workshopId,
        lockedBy: userId,
        allocationId
      });

      socket.emit('slot:lock:success', {
        success: true,
        slotId,
        workshopId,
        expiresIn: 300
      });

    } catch (error) {
      console.error('Error bloquejant slot:', error);
      socket.emit('slot:lock:error', {
        success: false,
        message: 'Error al blocar el slot'
      });
    }
  });
  
  /**
   * Confirmar i reservar un slot
   * Event: slot:book
   */
  socket.on('slot:book', (data = {}) => {
    const { slotId, userId, allocationId = null, workshopId = null } = data;

    try {
      if (!slotId || !userId) {
        socket.emit('slot:book:error', {
          success: false,
          message: 'Falten slotId o userId'
        });
        return;
      }

      // Alliberar lock en memòria (només broadcast)
      if (slotLocks.has(slotId)) {
        const lock = slotLocks.get(slotId);
        clearTimeout(lock.timeout);
        slotLocks.delete(slotId);
      }

      const payload = {
        slotId,
        workshopId,
        bookedBy: userId,
        allocationId,
        color: 'blue',
        message: `Slot reservat per l'usuari ${userId}`
      };

      if (workshopId) {
        io.to(`workshop:${workshopId}`).emit('slot:booked', payload);
      }
      io.emit('slot:booked', payload);

      socket.emit('slot:book:success', {
        success: true,
        slotId,
        workshopId,
        message: 'Slot reservat correctament'
      });

    } catch (error) {
      console.error('Error reservant slot:', error);
      socket.emit('slot:book:error', {
        success: false,
        message: 'Error al reservar el slot'
      });
    }
  });
  
  /**
   * Alliberar lock sense reservar
   * Event: slot:unlock
   */
  socket.on('slot:unlock', (data = {}) => {
    const { slotId, workshopId = null } = data;

    if (slotLocks.has(slotId)) {
      const lock = slotLocks.get(slotId);
      clearTimeout(lock.timeout);
      slotLocks.delete(slotId);

      if (workshopId) {
        io.to(`workshop:${workshopId}`).emit('slot:lock:released', { slotId, workshopId });
      }
      io.emit('slot:lock:released', { slotId, workshopId });
    }
  });
  
  /**
   * Enviar notificació a usuari específic
   * Event: notification:send
   */
  socket.on('notification:send', async (data) => {
    const { userId, title, message, type } = data;
    
    try {
      const connection = await pool.getConnection();
      
      // Guardar notificació a BD
      await connection.execute(
        'INSERT INTO notifications (user_id, title, message, notification_type) VALUES (?, ?, ?, ?)',
        [userId, title, message, type || 'info']
      );
      
      connection.release();
      
      // Emetre a usuari específic (si està connectat)
      const socketsOfUser = await io.in(`user:${userId}`).fetchSockets();
      socketsOfUser.forEach(s => {
        s.emit('notification:received', { title, message, type });
      });
      
    } catch (error) {
      console.error('Error enviant notificació:', error);
    }
  });
  
  /**
   * Usuari es uneix a sala de sala per a notificacions personalitzades
   * Event: user:join
   */
  socket.on('user:join', (userId) => {
    if (!userId) return;
    socket.join(`user:${userId}`);
    console.log(`👤 Usuari ${userId} s'ha unit a sala personal`);
  });

  /**
   * Unir-se a sala específica de taller per rebre events només d'aquell taller
   * Event: workshop:join
   */
  socket.on('workshop:join', (workshopId) => {
    if (!workshopId) return;
    socket.join(`workshop:${workshopId}`);
    console.log(`🏫 Socket ${socket.id} s'ha unit a workshop:${workshopId}`);
  });

  /**
   * Notificació de nova sol·licitud (per admins)
   */
  socket.on('request:created', (payload = {}) => {
    io.emit('request:created', payload);
  });

  /**
   * Notificar resultat d'assignació
   */
  socket.on('assignment:executed', (payload = {}) => {
    io.emit('assignment:executed', payload);
  });
  
  /**
   * Desconnexió
   */
  socket.on('disconnect', () => {
    console.log(`❌ Usuari desconnectat: ${socket.id}`);
    
    // Alliberar qualsevol lock d'aquest socket
    for (const [slotId, lock] of slotLocks.entries()) {
      if (lock.socketId === socket.id) {
        clearTimeout(lock.timeout);
        slotLocks.delete(slotId);
        io.emit('slot:lock:released', { slotId, workshopId: lock.workshopId });
      }
    }
  });
});

/**
 * Salut check
 */
app.get('/health', (req, res) => {
  res.json({
    success: true,
    message: 'Servidor Socket.io - Funcionant correctament',
    timestamp: new Date().toISOString()
  });
});

/**
 * Endpoint per rebre events des del backend PHP
 * POST /emit
 * Body: { event: 'request:created', payload: {...}, room: 'workshop:42' }
 */
app.post('/emit', (req, res) => {
  try {
    const { event, payload, room } = req.body;

    if (!event || !payload) {
      return res.status(400).json({
        success: false,
        message: 'Falten event o payload'
      });
    }

    console.log(`📤 Event rebut de PHP: ${event}`);

    // Emetre a sala específica si es proporciona
    if (room) {
      io.to(room).emit(event, payload);
      console.log(`   → Broadcast a room: ${room}`);
    } else {
      // Broadcast global
      io.emit(event, payload);
      console.log(`   → Broadcast global`);
    }

    res.json({
      success: true,
      message: `Event ${event} emès correctament`,
      event,
      room: room || 'global'
    });

  } catch (error) {
    console.error('Error procesant event de PHP:', error);
    res.status(500).json({
      success: false,
      message: 'Error al processar event: ' + error.message
    });
  }
});

/**
 * Iniciar servidor
 */
const PORT = process.env.PORT || 3000;
httpServer.listen(PORT, () => {
  console.log(`\n🚀 Servidor KAIROS Real-time escoltant al port ${PORT}`);
  console.log(`📡 WebSocket: ws://localhost:${PORT}`);
  console.log(`✅ Socket.io - Funcionant\n`);
});

export default io;
