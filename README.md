# KAIROS Project

Plataforma de Gestió de Tallers Educatius amb PHP + Vue.js + Docker

## 🚀 Inicio Rápido con Docker

### Opción 1: Script Automático (Recomendado)

**Windows (PowerShell):**
```powershell
.\start-kairos.ps1
```

**Linux/Mac:**
```bash
chmod +x start-kairos.sh
./start-kairos.sh
```

### Opción 2: Manual

```bash
# 1. Levantar servicios
docker-compose up -d --build

# 2. Esperar a que MySQL esté listo (10 segundos)
# 3. Ejecutar migraciones
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_notificacions.sql
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_alumnes.sql
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_sectors.sql
docker exec -i kairos-db mysql -u root -prootpass kairos_db < database/migration_historial_consumo.sql

# 4. Crear directorio de uploads
mkdir -p backend/uploads/evidencies
```

## 📦 Servicios Docker

| Servicio | Puerto | URL |
|----------|--------|-----|
| **Frontend (Vue.js)** | 5173 | http://localhost:5173 |
| **Backend (PHP)** | 8000 | http://localhost:8000 |
| **MySQL** | 3308 | localhost:3308 |

## 👤 Usuario Admin por Defecto

- **Email**: admin@kairos.cat
- **Password**: admin123

## ✨ Funcionalidades Implementadas

### 🔔 Sistema de Notificaciones
- Notificaciones en tiempo real con polling automático
- Campana con contador de notificaciones no leídas
- Emails automáticos al asignar/rechazar talleres
- Integrado en flujo de asignación inteligente

### 👨‍🎓 Gestión de Alumnos
- Alta individual de alumnos
- Importación masiva desde CSV
- Exportación de listas en CSV
- Vinculación con solicitudes de talleres

### 📎 Upload de Evidencias
- Drag & drop de archivos
- Validación de tipos (PDF, JPG, PNG, DOCX)
- Límite de 5MB por archivo
- Descarga de evidencias con permisos

### 📧 Emails Automáticos
- Email de asignación de taller
- Email de rechazo de solicitud
- Recordatorios para profesores
- Templates HTML personalizados

### 🎯 Funcionalidades Existentes
- Catálogo de talleres por sectores
- Solicitud de talleres
- Asignación inteligente con algoritmo de prioridad
- Gestión de profesores y centros
- Dashboard de analíticas
- Agenda de profesores

## 🏗️ Estructura del Proyecto

```
kairos-project/
├── backend/            # Backend PHP
│   ├── api/           # Endpoints REST
│   ├── config/        # Configuración BD
│   ├── models/        # Modelos de datos
│   ├── utils/         # Servicios (Notificaciones, Emails)
│   └── uploads/       # Archivos subidos
├── frontend/          # Frontend Vue.js + Tailwind
│   ├── src/
│   │   ├── components/  # Componentes reutilizables
│   │   ├── views/       # Vistas de páginas
│   │   ├── stores/      # Pinia stores
│   │   └── router/      # Vue Router
│   └── Dockerfile
├── database/          # Scripts SQL
│   ├── kairos_db.sql
│   ├── migration_*.sql
└── docker-compose.yml
```

## 🎨 Paleta de Colores KAIROS

- **Navy** (#0F172A): Color principal (autoridad)
- **Gold** (#C5A059): Detalles premium
- **Blue** (#3B82F6): Elementos interactivos
- **Surface** (#F8FAFC): Fondos
- **Light** (#FFFFFF): Texto y tarjetas

## 🛠️ Comandos Útiles

```bash
# Ver logs
docker logs kairos-frontend --tail 50
docker logs kairos-php --tail 50
docker logs kairos-db --tail 50

# Reiniciar servicios
docker-compose restart

# Detener servicios
docker-compose down

# Acceder a MySQL
docker exec -it kairos-db mysql -u root -prootpass kairos_db

# Ver estado de servicios
docker-compose ps
```

## 📝 Configuración SMTP (Producción)

1. Copiar `.env.example` a `.env` en la carpeta `backend/`
2. Configurar credenciales SMTP:
```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu_email@gmail.com
SMTP_PASS=tu_password_aplicacion
SMTP_FROM=noreply@kairos.cat
APP_ENV=production
```

## 🧪 Testing

### Probar Notificaciones
1. Login como admin
2. Ir a "Gestió Peticions"
3. Ejecutar "Asignació Intel·ligent"
4. Verificar notificación en campana
5. Login como centro y verificar notificación recibida

### Probar Gestión de Alumnos
1. Login como centro educativo
2. Ir a "Gestió d'Alumnes"
3. Seleccionar una solicitud
4. Añadir alumnos (individual o CSV)
5. Exportar lista

### Probar Upload de Evidencias
1. Login como centro
2. Ir a "Les Meves Sol·licituds"
3. Abrir solicitud asignada
4. Subir archivo en checklist
5. Verificar descarga como admin

## 🔧 Tecnologías

- **Backend**: PHP 8.2 + Apache + PDO + MySQL
- **Frontend**: Vue 3 + Vite + Tailwind CSS + Pinia + Vue Router
- **Base de Datos**: MySQL 8.0
- **Infraestructura**: Docker + Docker Compose
- **Emails**: PHPMailer (configuración opcional)

## 📚 Documentación Adicional

- [Implementation Plan](./implementation_plan.md) - Plan técnico detallado
- [Walkthrough](./walkthrough.md) - Resumen de implementación
- [Task List](./task.md) - Lista de tareas y progreso

## 🤝 Contribuir

Este proyecto es parte del TR2 REPTES - DAW 2025-26 de INS Pedralbes.

## 📄 Licencia

Proyecto educativo - INS Pedralbes
