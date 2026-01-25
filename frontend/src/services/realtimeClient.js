import { io } from 'socket.io-client'

const DEFAULT_URL = import.meta.env.VITE_REALTIME_URL || 'http://localhost:3000'

console.log('🔧 Socket.io conectando a:', DEFAULT_URL)

let socket = null

const getSocket = () => socket

const connect = (userId = null) => {
  if (socket && socket.connected) {
    console.log('✅ Socket ya conectado:', socket.id)
    return socket
  }

  console.log('📡 Intentando conectar Socket.io a', DEFAULT_URL)

  socket = io(DEFAULT_URL, {
    transports: ['polling', 'websocket'],
    autoConnect: false, // Disabled to prevent connection refused errors
    reconnection: true,
    reconnectionDelay: 1000,
    reconnectionDelayMax: 5000,
    reconnectionAttempts: Infinity,
    query: { userId },
    timeout: 20000,
    forceNew: false,
    multiplex: true,
    upgrade: true,
    rememberUpgrade: true
  })

  // Manejadores de eventos
  socket.on('connect', () => {
    console.log('✅ Socket.io conectado:', socket.id)
  })

  socket.on('connect_success', (data) => {
    console.log('✅ Conexión exitosa:', data)
  })

  socket.on('disconnect', (reason) => {
    console.log('❌ Socket.io desconectado. Razón:', reason)
  })

  socket.on('error', (error) => {
    console.error('⚠️ Socket.io error:', error)
  })

  socket.on('connect_error', (error) => {
    console.error('⚠️ Socket.io connect_error:', error)
  })

  return socket
}

const disconnect = () => {
  if (socket) {
    socket.disconnect()
    socket = null
  }
}

const emit = (event, payload = {}) => {
  if (!socket) return
  socket.emit(event, payload)
}

const on = (event, handler) => {
  if (!socket) return () => { }
  socket.on(event, handler)
  return () => socket.off(event, handler)
}

export default {
  connect,
  disconnect,
  emit,
  on,
  getSocket
}
