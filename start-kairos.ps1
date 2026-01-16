# Script para iniciar el proyecto KAIROS con Docker
# Ejecuta: .\start-kairos.ps1

Write-Host "🚀 Iniciando KAIROS con Docker..." -ForegroundColor Cyan

# Detener contenedores existentes
Write-Host "`n📦 Deteniendo contenedores existentes..." -ForegroundColor Yellow
docker-compose down

# Construir e iniciar servicios
Write-Host "`n🔨 Construyendo e iniciando servicios..." -ForegroundColor Yellow
docker-compose up -d --build

# Esperar a que MySQL esté listo
Write-Host "`n⏳ Esperando a que MySQL esté listo..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

# Ejecutar migraciones
Write-Host "`n📊 Ejecutando migraciones de base de datos..." -ForegroundColor Yellow

Write-Host "   - Migración de notificaciones..." -ForegroundColor Gray
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_notificacions.sql 2>$null

Write-Host "   - Migración de alumnos..." -ForegroundColor Gray
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_alumnes.sql 2>$null

Write-Host "   - Migración de sectores..." -ForegroundColor Gray
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_sectors.sql 2>$null

Write-Host "   - Migración de historial de consumo..." -ForegroundColor Gray
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_historial_consumo.sql 2>$null

Write-Host "   - Migración de fechas en talleres..." -ForegroundColor Gray
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_talleres_fecha.sql 2>$null

# Crear directorio de uploads
Write-Host "`n📁 Creando directorio de uploads..." -ForegroundColor Yellow
if (!(Test-Path "backend/uploads/evidencies")) {
    New-Item -ItemType Directory -Path "backend/uploads/evidencies" -Force | Out-Null
}

# Mostrar estado de servicios
Write-Host "`n✅ Servicios iniciados:" -ForegroundColor Green
docker-compose ps

Write-Host "`n🌐 URLs de acceso:" -ForegroundColor Cyan
Write-Host "   Frontend: http://localhost:5173" -ForegroundColor White
Write-Host "   Backend:  http://localhost:8000" -ForegroundColor White
Write-Host "   MySQL:    localhost:3308" -ForegroundColor White

Write-Host "`n👤 Usuario admin por defecto:" -ForegroundColor Cyan
Write-Host "   Email:    admin@kairos.cat" -ForegroundColor White
Write-Host "   Password: admin123" -ForegroundColor White

Write-Host "`n✨ KAIROS está listo para usar!" -ForegroundColor Green
Write-Host "   Presiona Ctrl+C para detener los servicios`n" -ForegroundColor Gray
