# Birrias API 🏆

API RESTful completa para gestión de torneos de fútbol amateur desarrollada con **Laravel 12** + **Breeze API** + **Sanctum**.

## 🚀 Características

- **Autenticación JWT** con roles (`admin`, `player`)
- **CRUD completo** para torneos, equipos, jugadores y partidos
- **Gestión de fixtures** automática por tipo de torneo
- **Tabla de posiciones** autocalculada
- **Eventos en vivo** (goles, tarjetas, sustituciones)
- **Importación de jugadores** desde CSV/Excel
- **Control de transmisiones** en vivo
- **API REST** totalmente documentada

## 📋 Requisitos

- **PHP 8.2+**
- **Composer**
- **PostgreSQL 13+**
- **Node.js 18+** (opcional, para frontend)

## 🛠️ Instalación

### 1. Clonar el repositorio

```bash
git clone <repository-url>
cd birrias-api
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar base de datos

Crea una base de datos PostgreSQL llamada `birrias_db`:

```sql
CREATE DATABASE birrias_db;
```

### 4. Configurar variables de entorno

```bash
cp .env.example .env
```

Edita el archivo `.env` con tus credenciales de base de datos:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=birrias_db
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### 5. Generar clave de aplicación

```bash
php artisan key:generate
```

### 6. Ejecutar migraciones

```bash
php artisan migrate
```

### 7. Poblar base de datos (opcional)

```bash
php artisan db:seed
```

Esto creará:
- 2 administradores: `admin@birrias.com` / `carlos@birrias.com`
- 15 jugadores de prueba
- 12 equipos
- 3 torneos de ejemplo
- Datos relacionados

**Contraseña por defecto**: `password`

### 8. Ejecutar servidor

```bash
php artisan serve
```

La API estará disponible en: `http://localhost:8000`

## 📚 Documentación de API

### Autenticación

#### Registro
```http
POST /register
Content-Type: application/json

{
    "fullname": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password",
    "password_confirmation": "password",
    "username": "juan_perez",
    "phone": "+1234567890",
    "role": "admin"
}
```

#### Login
```http
POST /login
Content-Type: application/json

{
    "email": "admin@birrias.com",
    "password": "password"
}
```

**Respuesta:**
```json
{
    "access_token": "1|token...",
    "token_type": "Bearer",
    "user": { ... }
}
```

### Endpoints Principales

#### Rutas Públicas (sin autenticación)
- `GET /api/tournaments/{id}` - Ver torneo específico
- `GET /api/teams` - Listar equipos
- `GET /api/matches` - Listar partidos
- `GET /api/standings/tournament/{id}` - Tabla de posiciones

#### Rutas Protegidas (requieren autenticación)

**Header requerido:**
```
Authorization: Bearer {token}
```

##### Torneos (solo admin)
- `GET /api/tournaments` - Mis torneos
- `POST /api/tournaments` - Crear torneo
- `PUT /api/tournaments/{id}` - Actualizar torneo
- `DELETE /api/tournaments/{id}` - Eliminar torneo
- `POST /api/tournaments/{id}/teams` - Agregar equipo
- `DELETE /api/tournaments/{id}/teams` - Quitar equipo

##### Equipos (solo admin)
- `POST /api/teams` - Crear equipo
- `PUT /api/teams/{id}` - Actualizar equipo
- `DELETE /api/teams/{id}` - Eliminar equipo
- `POST /api/teams/{id}/players` - Agregar jugador
- `DELETE /api/teams/{id}/players` - Quitar jugador

##### Jugadores (solo admin)
- `GET /api/players` - Listar jugadores
- `POST /api/players` - Crear jugador
- `POST /api/players/import` - Importar desde CSV
- `PUT /api/players/{id}` - Actualizar jugador
- `DELETE /api/players/{id}` - Eliminar jugador

##### Partidos (solo admin)
- `POST /api/matches` - Crear partido
- `PUT /api/matches/{id}` - Actualizar partido
- `PATCH /api/matches/{id}/start-live` - Iniciar transmisión
- `PATCH /api/matches/{id}/finish` - Finalizar partido
- `POST /api/matches/{id}/events` - Agregar evento

## 🏗️ Estructura de Base de Datos

### Entidades Principales

- **Users**: Usuarios del sistema (admin/jugadores)
- **Tournaments**: Torneos con diferentes formatos
- **Teams**: Equipos participantes
- **Players**: Jugadores con datos personales
- **Matches**: Partidos con marcadores y estados
- **MatchEvents**: Eventos dentro del partido
- **Standings**: Tabla de posiciones autocalculada

### Tipos de Torneo

1. **League**: Liga simple (todos contra todos)
2. **League Playoffs**: Liga + playoffs finales
3. **Groups Knockout**: Grupos + eliminación directa

## 🎯 Funcionalidades Avanzadas

### Importación de Jugadores

Formato CSV esperado:
```csv
position,jersey,birthDay
Delantero,10,1995-03-15
Portero,1,1990-08-22
```

```http
POST /api/players/import
Content-Type: multipart/form-data

file: players.csv
team_id: uuid-del-equipo
```

### Eventos en Vivo

```http
POST /api/matches/{match_id}/events
Authorization: Bearer {token}

{
    "player_id": "uuid-jugador",
    "team_id": "uuid-equipo",
    "event_type": "goal",
    "minute": 45,
    "description": "Golazo desde fuera del área"
}
```

Tipos de eventos: `goal`, `yellow_card`, `red_card`, `substitution`

### Tabla de Posiciones

Se actualiza automáticamente cuando un partido termina. También se puede recalcular manualmente:

```http
POST /api/standings/recalculate
Authorization: Bearer {token}

{
    "tournament_id": "uuid-torneo"
}
```

## 🔐 Roles y Permisos

### Admin
- Crear/gestionar torneos
- Gestionar equipos y jugadores
- Controlar partidos y eventos
- Acceso total a la API

### Player
- Ver información pública
- Acceder a su perfil personal
- Consultar partidos y torneos

## 🧪 Testing

```bash
# Ejecutar tests
php artisan test

# Con coverage
php artisan test --coverage
```

## 🚀 Despliegue

### Producción

1. Configurar variables de entorno para producción
2. Ejecutar migraciones: `php artisan migrate --force`
3. Limpiar caché: `php artisan config:cache`
4. Optimizar: `php artisan optimize`

### Docker (opcional)

```dockerfile
# Dockerfile de ejemplo incluido
docker build -t birrias-api .
docker run -p 8000:8000 birrias-api
```

## 🛠️ Stack Tecnológico

- **Backend**: Laravel 12, PHP 8.2+
- **Autenticación**: Laravel Sanctum + Breeze API
- **Base de datos**: PostgreSQL
- **Validación**: Form Requests
- **Autorización**: Policies + Middleware
- **Docs**: Postman Collection incluida

## 📝 Próximas Funcionalidades

- [ ] Sistema de notificaciones en tiempo real
- [ ] Generación automática de fixtures
- [ ] Estadísticas avanzadas de jugadores
- [ ] API para aplicaciones móviles
- [ ] Dashboard de administración
- [ ] Integración con redes sociales

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE.md](LICENSE.md) para detalles.

## 🆘 Soporte

Para reportar bugs o solicitar nuevas funcionalidades, por favor crea un [issue](https://github.com/tu-usuario/birrias-api/issues).

---

**Desarrollado con ❤️ para la comunidad futbolística amateur**