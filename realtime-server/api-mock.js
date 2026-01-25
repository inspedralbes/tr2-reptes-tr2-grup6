/**
 * API Mock para Testing
 * Path: /realtime-server/api-mock.js
 * Simula respuestas de la API PHP mientras se configura Docker
 */

import express from 'express';
import cors from 'cors';

const app = express();
app.use(cors());
app.use(express.json());

// Mock data
const users = {
  'test@example.com': {
    id: 1,
    email: 'test@example.com',
    full_name: 'Test User',
    role: 'user'
  }
};

const tokens = {};

// Login endpoint
app.post('/api/auth/login', (req, res) => {
  const { email, password } = req.body;
  
  if (!email || !password) {
    return res.status(400).json({
      success: false,
      message: 'Email i password requerits'
    });
  }

  // Mock validation
  if (password === 'password123' && email === 'test@example.com') {
    const token = 'mock_jwt_token_' + Date.now();
    tokens[token] = users['test@example.com'];
    
    return res.json({
      success: true,
      message: 'Login correcte',
      data: {
        user: users['test@example.com'],
        token: token
      }
    });
  }

  res.status(401).json({
    success: false,
    message: 'Credencials invàlides'
  });
});

// Register endpoint
app.post('/api/auth/register', (req, res) => {
  const { email, password, full_name } = req.body;
  
  if (!email || !password || !full_name) {
    return res.status(400).json({
      success: false,
      message: 'Email, password i full_name requerits'
    });
  }

  if (users[email]) {
    return res.status(400).json({
      success: false,
      message: 'Email ja està registrat'
    });
  }

  const userId = Object.keys(users).length + 1;
  const newUser = {
    id: userId,
    email,
    full_name,
    role: 'user'
  };

  users[email] = newUser;
  const token = 'mock_jwt_token_' + Date.now();
  tokens[token] = newUser;

  res.status(201).json({
    success: true,
    message: 'Registre completat',
    data: {
      user: newUser,
      token: token
    }
  });
});

// Get current user
app.get('/api/auth/me', (req, res) => {
  const token = req.headers.authorization?.split(' ')[1];
  
  if (!token || !tokens[token]) {
    return res.status(401).json({
      success: false,
      message: 'No autenticat'
    });
  }

  res.json({
    success: true,
    data: tokens[token]
  });
});

// Get workshops
app.get('/api/workshops', (req, res) => {
  const mockWorkshops = [
    {
      id: 1,
      name: 'Soldadura Bàsica',
      description: 'Introducció a la soldadura i seguretat',
      instructor: 'Joan García',
      capacity: 20,
      duration: '4 hores',
      center: 'Institut Tècnic'
    },
    {
      id: 2,
      name: 'Programació Arduino',
      description: 'Projectes amb Arduino i microcontroladors',
      instructor: 'Maria López',
      capacity: 25,
      duration: '6 hores',
      center: 'Institut Tecnològic'
    },
    {
      id: 3,
      name: 'Impressió 3D',
      description: 'Disseny i impressió de models 3D',
      instructor: 'Pere Rodríguez',
      capacity: 15,
      duration: '4 hores',
      center: 'Institut Tècnic'
    }
  ];

  res.json({
    success: true,
    data: mockWorkshops,
    total: mockWorkshops.length
  });
});

// Get slots
app.get('/api/workshops/:id/slots', (req, res) => {
  const mockSlots = [
    {
      id: 1,
      workshop_id: parseInt(req.params.id),
      date: '2026-02-15',
      start_time: '09:00',
      end_time: '11:00',
      capacity: 20,
      available: 18,
      status: 'available',
      center: 'Institut Tècnic'
    },
    {
      id: 2,
      workshop_id: parseInt(req.params.id),
      date: '2026-02-16',
      start_time: '14:00',
      end_time: '16:00',
      capacity: 20,
      available: 12,
      status: 'available',
      center: 'Institut Tècnic'
    },
    {
      id: 3,
      workshop_id: parseInt(req.params.id),
      date: '2026-02-17',
      start_time: '10:00',
      end_time: '12:00',
      capacity: 20,
      available: 5,
      status: 'available',
      center: 'Institut Tècnic'
    }
  ];

  res.json({
    success: true,
    data: mockSlots,
    total: mockSlots.length
  });
});

// Lock slot
app.post('/api/slots/lock', (req, res) => {
  const { slot_id } = req.body;
  
  res.json({
    success: true,
    message: 'Slot bloquejat',
    data: {
      slot_id,
      lock_token: 'lock_token_' + Date.now(),
      expires_in: 300
    }
  });
});

// Book slot
app.post('/api/slots/book', (req, res) => {
  const { slot_id, lock_token } = req.body;
  
  res.json({
    success: true,
    message: 'Slot reservat',
    data: {
      slot_id,
      status: 'booked',
      allocation_id: Math.floor(Math.random() * 10000)
    }
  });
});

// Health check
app.get('/health', (req, res) => {
  res.json({
    success: true,
    message: 'API Mock - Funcionant',
    timestamp: new Date().toISOString()
  });
});

const PORT = process.env.API_PORT || 8000;
app.listen(PORT, () => {
  console.log(`\n🚀 API Mock escoltant al port ${PORT}`);
  console.log(`📍 http://localhost:${PORT}`);
  console.log(`\n Test credentials:`);
  console.log(`   Email: test@example.com`);
  console.log(`   Password: password123\n`);
});
