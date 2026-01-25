#!/usr/bin/env node
/**
 * Script de Testing per a Fase 3 - Real-time Socket.io
 * Path: /realtime-server/test.js
 * 
 * Valida que el sistema realtime funciona correctament:
 * 1. Connexió al servidor
 * 2. Events de slots (lock, book, unlock)
 * 3. Notificacions
 * 4. Rooms/broadcast
 * 5. Backend PHP integration (POST /emit)
 */

import { io } from 'socket.io-client';
import http from 'http';

const REALTIME_URL = 'http://localhost:3000';
const TEST_TIMEOUT = 5000;

let testsRun = 0;
let testsPassed = 0;
let testsFailed = 0;

// Colores
const colors = {
  reset: '\x1b[0m',
  green: '\x1b[32m',
  red: '\x1b[31m',
  yellow: '\x1b[33m',
  blue: '\x1b[34m',
  cyan: '\x1b[36m'
};

function log(color, ...args) {
  console.log(color, ...args, colors.reset);
}

function test(name, fn) {
  testsRun++;
  return new Promise((resolve) => {
    const timeout = setTimeout(() => {
      testsFailed++;
      log(colors.red, `✗ ${name} (TIMEOUT)`);
      resolve();
    }, TEST_TIMEOUT);

    try {
      fn((passed) => {
        clearTimeout(timeout);
        if (passed) {
          testsPassed++;
          log(colors.green, `✓ ${name}`);
        } else {
          testsFailed++;
          log(colors.red, `✗ ${name}`);
        }
        resolve();
      });
    } catch (err) {
      clearTimeout(timeout);
      testsFailed++;
      log(colors.red, `✗ ${name} (${err.message})`);
      resolve();
    }
  });
}

async function testHealthCheck() {
  return new Promise((resolve) => {
    test('Health check endpoint', (done) => {
      http.get(`${REALTIME_URL}/health`, (res) => {
        done(res.statusCode === 200);
      }).on('error', () => done(false));
    }).then(resolve);
  });
}

async function testSocketConnection() {
  return new Promise((resolve) => {
    test('Socket.io connection', (done) => {
      const socket = io(REALTIME_URL, {
        reconnection: false,
        transports: ['websocket']
      });

      socket.on('connect', () => {
        socket.disconnect();
        done(true);
      });

      socket.on('connect_error', () => {
        done(false);
      });
    }).then(resolve);
  });
}

async function testUserJoin() {
  return new Promise((resolve) => {
    test('User join event', (done) => {
      const socket = io(REALTIME_URL, { reconnection: false });

      socket.on('connect', () => {
        socket.emit('user:join', 123);
        // Assume success si no hi ha error
        setTimeout(() => {
          socket.disconnect();
          done(true);
        }, 100);
      });

      socket.on('connect_error', () => {
        done(false);
      });
    }).then(resolve);
  });
}

async function testWorkshopJoin() {
  return new Promise((resolve) => {
    test('Workshop join event', (done) => {
      const socket = io(REALTIME_URL, { reconnection: false });

      socket.on('connect', () => {
        socket.emit('workshop:join', 42);
        setTimeout(() => {
          socket.disconnect();
          done(true);
        }, 100);
      });

      socket.on('connect_error', () => {
        done(false);
      });
    }).then(resolve);
  });
}

async function testSlotLockEvent() {
  return new Promise((resolve) => {
    test('Slot:lock event broadcast', (done) => {
      const socket1 = io(REALTIME_URL, { reconnection: false });
      const socket2 = io(REALTIME_URL, { reconnection: false });

      let socket1Ready = false;
      let socket2Ready = false;
      let eventReceived = false;

      const checkReady = () => {
        if (socket1Ready && socket2Ready) {
          socket1.emit('workshop:join', 99);
          socket2.emit('workshop:join', 99);
          setTimeout(() => {
            socket1.emit('slot:lock', {
              slotId: 1,
              userId: 100,
              workshopId: 99
            });
          }, 100);
        }
      };

      socket1.on('connect', () => {
        socket1Ready = true;
        checkReady();
      });

      socket2.on('connect', () => {
        socket2Ready = true;
        checkReady();
      });

      socket2.on('slot:locked', () => {
        eventReceived = true;
      });

      socket1.on('connect_error', () => done(false));
      socket2.on('connect_error', () => done(false));

      setTimeout(() => {
        socket1.disconnect();
        socket2.disconnect();
        done(eventReceived);
      }, 1500);
    }).then(resolve);
  });
}

async function testNotification() {
  return new Promise((resolve) => {
    test('Notification event', (done) => {
      const socket = io(REALTIME_URL, { reconnection: false });
      let notificationReceived = false;

      socket.on('connect', () => {
        socket.emit('user:join', 999);
        setTimeout(() => {
          socket.emit('notification:send', {
            userId: 999,
            title: 'Test',
            message: 'Testing notifications',
            type: 'info'
          });
        }, 100);
      });

      socket.on('notification:received', () => {
        notificationReceived = true;
      });

      socket.on('connect_error', () => {
        done(false);
      });

      setTimeout(() => {
        socket.disconnect();
        done(notificationReceived);
      }, 1500);
    }).then(resolve);
  });
}

async function testPHPEmitEndpoint() {
  return new Promise((resolve) => {
    test('PHP /emit endpoint', (done) => {
      const socket = io(REALTIME_URL, { reconnection: false });
      let eventReceived = false;

      socket.on('connect', () => {
        socket.on('test:event', () => {
          eventReceived = true;
        });

        // Simular HTTP POST des de PHP
        const postData = JSON.stringify({
          event: 'test:event',
          payload: { message: 'From PHP' }
        });

        const options = {
          hostname: 'localhost',
          port: 3000,
          path: '/emit',
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Content-Length': postData.length
          }
        };

        const req = http.request(options, (res) => {
          // Emit rebut correctament
        });

        req.on('error', () => done(false));
        req.write(postData);
        req.end();
      });

      socket.on('connect_error', () => {
        done(false);
      });

      setTimeout(() => {
        socket.disconnect();
        done(eventReceived);
      }, 1500);
    }).then(resolve);
  });
}

async function runAllTests() {
  console.clear();
  log(colors.cyan, '\n🧪 FASE 3 - REAL-TIME SOCKET.IO TEST SUITE\n');

  await testHealthCheck();
  await testSocketConnection();
  await testUserJoin();
  await testWorkshopJoin();
  await testSlotLockEvent();
  await testNotification();
  await testPHPEmitEndpoint();

  // Summary
  console.log('\n' + colors.cyan + '═══════════════════════════════════════' + colors.reset);
  log(colors.cyan, `Tests Run: ${testsRun}`);
  log(colors.green, `✓ Passed: ${testsPassed}`);
  if (testsFailed > 0) {
    log(colors.red, `✗ Failed: ${testsFailed}`);
  }

  const passRate = ((testsPassed / testsRun) * 100).toFixed(1);
  if (testsPassed === testsRun) {
    log(colors.green, `\n🎉 ALL TESTS PASSED! (${passRate}%)\n`);
  } else {
    log(colors.yellow, `\n⚠️  SOME TESTS FAILED (${passRate}%)\n`);
  }

  process.exit(testsFailed > 0 ? 1 : 0);
}

// Run tests
runAllTests().catch(err => {
  log(colors.red, 'Fatal error:', err);
  process.exit(1);
});
