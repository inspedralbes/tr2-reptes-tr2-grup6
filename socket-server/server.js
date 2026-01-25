const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: ['http://localhost:5173', 'http://localhost:3000', 'http://0.0.0.0:5173', 'http://127.0.0.1:5173'],
    methods: ['GET', 'POST'],
    credentials: true
  },
  transports: ['polling', 'websocket'],
  allowEIO3: true,
  pingTimeout: 60000,
  pingInterval: 25000,
  upgradeTimeout: 30000,
  maxHttpBufferSize: 1e6,
  connectTimeout: 45000
});

// Middleware
app.use(cors());
app.use(express.json());

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'Socket.io server is running', timestamp: new Date() });
});

// Socket.io connection handlers
io.on('connection', (socket) => {
  console.log(`✅ Cliente conectado: ${socket.id} desde ${socket.handshake.address}`);
  
  // Enviar confirmación de conexión
  socket.emit('connect_success', {
    message: 'Conectado al servidor de tiempo real',
    socketId: socket.id,
    timestamp: new Date()
  });

  socket.on('user:join', (userId) => {
    console.log(`👤 Usuario unido: ${userId} (${socket.id})`);
    socket.join(`user:${userId}`);
    socket.emit('user:joined', { userId, socketId: socket.id });
    io.emit('user:connected', { userId, socketId: socket.id });
  });

  socket.on('workshop-scheduled', (data) => {
    console.log(`📅 Taller programado:`, data);
    io.emit('workshop:scheduled', data);
  });

  socket.on('slot:locked', (data) => {
    console.log(`🔒 Slot bloqueado:`, data);
    io.emit('slot:locked', data);
  });

  socket.on('slot:released', (data) => {
    console.log(`🔓 Slot liberado:`, data);
    io.emit('slot:released', data);
  });

  socket.on('slot:booked', (data) => {
    console.log(`✅ Slot reservado:`, data);
    io.emit('slot:booked', data);
  });

  socket.on('ping', () => {
    console.log(`📡 Ping recibido de ${socket.id}`);
    socket.emit('pong');
  });

  socket.on('disconnect', (reason) => {
    console.log(`❌ Cliente desconectado: ${socket.id} (Razón: ${reason})`);
  });

  socket.on('error', (error) => {
    console.error(`⚠️ Error en socket ${socket.id}:`, error);
  });

  socket.on('exception', (error) => {
    console.error(`⚠️ Excepción en socket ${socket.id}:`, error);
  });
});

// Manejo de errores global
server.on('error', (error) => {
  console.error('❌ Error del servidor:', error);
});

// Start server
const PORT = process.env.PORT || 3000;
server.listen(PORT, '0.0.0.0', () => {
  console.log(`🚀 Socket.io server escuchando en puerto ${PORT}`);
  console.log(`📡 CORS habilitado para múltiples orígenes`);
  console.log(`🔧 Transports: websocket, polling`);
  console.log(`📊 Conexiones conectadas: ${io.engine.clientsCount}`);
});
