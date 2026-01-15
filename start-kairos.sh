#!/bin/bash
# Script para iniciar el proyecto KAIROS con Docker
# Ejecuta: ./start-kairos.sh

echo "🚀 Iniciando KAIROS con Docker..."

# Detener contenedores existentes
echo ""
echo "📦 Deteniendo contenedores existentes..."
docker-compose down

# Construir e iniciar servicios
echo ""
echo "🔨 Construyendo e iniciando servicios..."
docker-compose up -d --build

# Esperar a que MySQL esté listo
echo ""
echo "⏳ Esperando a que MySQL esté listo..."
sleep 10

# Ejecutar migraciones
echo ""
echo "📊 Ejecutando migraciones de base de datos..."

echo "   - Migración de notificaciones..."
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_notificacions.sql 2>/dev/null

echo "   - Migración de alumnos..."
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_alumnes.sql 2>/dev/null

echo "   - Migración de sectores..."
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_sectors.sql 2>/dev/null

echo "   - Migración de historial de consumo..."
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_historial_consumo.sql 2>/dev/null

# Crear directorio de uploads
echo ""
echo "📁 Creando directorio de uploads..."
mkdir -p backend/uploads/evidencies

# Mostrar estado de servicios
echo ""
echo "✅ Servicios iniciados:"
docker-compose ps

echo ""
echo "🌐 URLs de acceso:"
echo "   Frontend: http://localhost:5173"
echo "   Backend:  http://localhost:8000"
echo "   MySQL:    localhost:3308"

echo ""
echo "👤 Usuario admin por defecto:"
echo "   Email:    admin@kairos.cat"
echo "   Password: admin123"

echo ""
echo "✨ KAIROS está listo para usar!"
echo "   Presiona Ctrl+C para detener los servicios"
echo ""
