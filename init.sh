#!/usr/bin/env bash

# Script d'Inicialització de KAIROS

set -e

echo "🚀 Inicialitzant KAIROS..."

# 1. Verificar si Docker està instal·lat
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no està instal·lat. Si us plau, instal·leu Docker primer."
    exit 1
fi

# 2. Copiar arxiu .env si no existeix
if [ ! -f .env ]; then
    echo "📋 Creant fitxer .env des de .env.example..."
    cp .env.example .env
    echo "⚠️  Si us plau, editeu .env amb les vostres configuracions."
fi

# 3. Crear estructura de carpetes necessàries
echo "📁 Creant estructura de carpetes..."
mkdir -p backend/logs
mkdir -p frontend/dist
mkdir -p realtime-server/logs

# 4. Construir i iniciar contenidors
echo "🐳 Construint i iniciant contenidors Docker..."
docker-compose up -d

# 5. Esperar a que MySQL estigui llest
echo "⏳ Esperant a que MySQL estigui disponible..."
sleep 10

# 6. Instal·lar dependències del frontend
echo "📦 Instal·lant dependències de Frontend..."
cd frontend
if [ -f "package.json" ]; then
    npm install
fi
cd ..

# 7. Instal·lar dependències de Node.js realtime
echo "📦 Instal·lant dependències de Servidor Real-time..."
cd realtime-server
if [ -f "package.json" ]; then
    npm install
fi
cd ..

echo ""
echo "✅ KAIROS s'ha inicialitzat correctament!"
echo ""
echo "🌐 Accés a l'aplicació:"
echo "   - Frontend: http://localhost:5173"
echo "   - Backend API: http://localhost/api"
echo "   - Real-time Server: ws://localhost:3000"
echo "   - PhpMyAdmin: http://localhost:8080"
echo ""
echo "📝 Pròximes accions:"
echo "   1. Editar .env amb les vostres configuracions"
echo "   2. Importar esquema de BD: docker-compose exec db mysql -u kairos_user -p kairos_db < backend/database/migrations/schema.sql"
echo "   3. Visitar http://localhost per accedir a l'aplicació"
echo ""
