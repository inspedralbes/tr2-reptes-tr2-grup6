# KAIROS Project

Plataforma de Gestió de Tallers Educatius amb PHP + Vue.js

## Estructura del Proyecto

```
kairos-project/
├── backend/            # Backend PHP
│   ├── config/        # Configuración de base de datos
│   ├── api/           # Endpoints REST
│   ├── models/        # Modelos de datos
│   └── controllers/   # Lógica de negocio
├── frontend/          # Frontend Vue.js + Tailwind
│   ├── src/
│   └── public/
└── database/          # Scripts SQL
    └── kairos_db.sql
```

## Instalación

### 1. Base de Datos
1. Crea una base de datos llamada `kairos_db`
2. Importa el archivo `database/kairos_db.sql`
3. Configura las credenciales en `backend/config/Database.php`

### 2. Backend (PHP)
- Coloca la carpeta en tu servidor local (htdocs/XAMPP o www/Laragon)
- Asegúrate de tener PHP 7.4+ y MySQL

### 3. Frontend (Vue.js)
```bash
cd frontend
npm install
npm run dev
```

## Paleta de Colores KAIROS

- **Navy** (#0F172A): Color principal (autoridad)
- **Gold** (#C5A059): Detalles premium
- **Blue** (#3B82F6): Elementos interactivos
- **Surface** (#F8FAFC): Fondos
- **Light** (#FFFFFF): Texto y tarjetas

## Usuario Admin por Defecto
- Email: admin@kairos.cat
- Password: admin123

## Tecnologías
- Backend: PHP 8.0 + PDO + MySQL
- Frontend: Vue 3 + Vite + Tailwind CSS + Pinia
- Enrutamiento: Vue Router
