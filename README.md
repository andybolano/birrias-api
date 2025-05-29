# 🏆 Birrias Tournament API

Sistema completo de gestión de torneos de fútbol con **fases dinámicas configurables**.

## 🚀 Características Principales

- ✅ **Gestión de Torneos** con configuración dinámica de fases
- ✅ **Sistema de Fases Flexibles** (Liga, Eliminatorias, Grupos)
- ✅ **Control de Estados de Fases** (pending, active, completed, cancelled)
- ✅ **Generación Automática de Fixtures** por fase
- ✅ **Gestión de Equipos y Jugadores**
- ✅ **Sistema de Autenticación** con roles (Admin/Player)
- ✅ **API RESTful** completamente documentada
- ✅ **Base de datos SQLite** para desarrollo

## 🎯 Sistema de Fases Dinámicas (MVP)

### Tipos de Fase Disponibles

| Tipo | Descripción | Parámetros |
|------|-------------|------------|
| `round_robin` | Liga - Todos contra todos | `home_away`, `teams_advance` |
| `single_elimination` | Eliminación directa | `teams_advance`, `home_away` |
| `groups` | Fase de grupos | `groups_count`, `teams_per_group`, `teams_advance` |

### Estados de Fase

| Estado | Descripción | Color | Acciones |
|--------|-------------|-------|----------|
| `pending` | Fase creada, no iniciada | Gris | Generar fixtures, modificar, iniciar |
| `active` | Fase en curso | Verde | Actualizar resultados, completar |
| `completed` | Fase finalizada | Azul | Solo consulta (estado final) |
| `cancelled` | Fase cancelada | Rojo | Solo consulta (estado final) |

### Flujo de Estados
```
pending → active → completed
    ↓        ↓
cancelled ← cancelled
```

### Ejemplos de Configuración

#### 🏅 Torneo Estilo Mundial
```json
{
  "phases": [
    {
      "name": "Fase de Grupos",
      "type": "groups",
      "groups_count": 8,
      "teams_per_group": 4,
      "teams_advance": 16
    },
    {
      "name": "Octavos de Final",
      "type": "single_elimination",
      "teams_advance": 8,
      "home_away": false
    },
    {
      "name": "Cuartos de Final",
      "type": "single_elimination",
      "teams_advance": 4,
      "home_away": false
    },
    {
      "name": "Semifinales",
      "type": "single_elimination",
      "teams_advance": 2,
      "home_away": false
    },
    {
      "name": "Final",
      "type": "single_elimination",
      "teams_advance": 1,
      "home_away": false
    }
  ]
}
```

#### 🏆 Liga + Playoffs
```json
{
  "phases": [
    {
      "name": "Temporada Regular",
      "type": "round_robin",
      "home_away": true,
      "teams_advance": 8
    },
    {
      "name": "Playoffs",
      "type": "single_elimination",
      "teams_advance": 1,
      "home_away": true
    }
  ]
}
```

## 📚 Documentación de la API

### 🔐 Autenticación

```bash
# Login
POST /api/login
{
  "email": "admin@birrias.com",
  "password": "password"
}

# Respuesta
{
  "token": "27|MfMUxunyum3Au2n529xWkKAmB3uzZsay1QB4dZkc7b9150ef",
  "user": {...}
}
```

### 🏟️ Gestión de Torneos

#### Crear Torneo
```bash
POST /api/tournaments
Authorization: Bearer {token}
{
  "name": "Copa Birrias 2024",
  "start_date": "2024-06-01"
  # El campo "format" es opcional - se recomienda usar "custom" o omitirlo
  # Las fases se configuran dinámicamente después
}
```

#### Listar Torneos
```bash
GET /api/tournaments
Authorization: Bearer {token}
```

#### Ver Torneo Específico
```bash
GET /api/tournaments/{id}
# Público, no requiere autenticación
```

### ⚡ Sistema de Fases Dinámicas

#### Obtener Tipos de Fase Disponibles
```bash
GET /api/tournament-phase-types
# Público

# Respuesta
{
  "phase_types": [
    {
      "value": "round_robin",
      "label": "Liga (Todos contra Todos)",
      "description": "Cada equipo juega contra todos los demás equipos. Ideal para ligas regulares.",
      "supports_home_away": true,
      "required_fields": [],
      "optional_fields": ["home_away", "teams_advance"],
      "example": {
        "name": "Liga Regular",
        "type": "round_robin",
        "home_away": true,
        "teams_advance": 8
      }
    },
    {
      "value": "single_elimination",
      "label": "Eliminación Directa",
      "description": "Eliminación directa - quien pierde queda eliminado. Ideal para playoffs.",
      "supports_home_away": true,
      "required_fields": ["teams_advance"],
      "optional_fields": ["home_away"],
      "example": {
        "name": "Playoffs",
        "type": "single_elimination",
        "teams_advance": 8,
        "home_away": true
      }
    },
    {
      "value": "groups",
      "label": "Fase de Grupos",
      "description": "Los equipos se dividen en grupos y juegan todos contra todos dentro del grupo. Ideal para mundiales.",
      "supports_home_away": false,
      "required_fields": ["groups_count", "teams_per_group"],
      "optional_fields": ["teams_advance"],
      "example": {
        "name": "Fase de Grupos",
        "type": "groups",
        "groups_count": 4,
        "teams_per_group": 4,
        "teams_advance": 8
      }
    }
  ],
  "note": "MVP simplificado - Solo 3 tipos de fase disponibles",
  "total_types": 3
}
```

#### Listar Fases de un Torneo
```bash
GET /api/tournaments/{tournament_id}/phases
# Público

# Respuesta
[
  {
    "id": "uuid",
    "phase_number": 1,
    "name": "Liga Regular",
    "type": "round_robin",
    "home_away": true,
    "teams_advance": 8,
    "is_active": true,
    "matches_count": 12
  }
]
```

#### Crear Nueva Fase
```bash
POST /api/tournaments/{tournament_id}/phases
Authorization: Bearer {token}

# Liga Regular
{
  "name": "Liga Regular",
  "type": "round_robin",
  "home_away": true,
  "teams_advance": 8
}

# Playoffs
{
  "name": "Playoffs",
  "type": "single_elimination",
  "teams_advance": 8,
  "home_away": true
}

# Fase de Grupos
{
  "name": "Fase de Grupos",
  "type": "groups",
  "groups_count": 4,
  "teams_per_group": 4,
  "teams_advance": 8
}
```

#### Actualizar Fase
```bash
PUT /api/tournaments/{tournament_id}/phases/{phase_id}
Authorization: Bearer {token}
{
  "name": "Liga Regular Actualizada",
  "teams_advance": 6
}
```

#### Eliminar Fase
```bash
DELETE /api/tournaments/{tournament_id}/phases/{phase_id}
Authorization: Bearer {token}
```

#### Generar Fixtures para una Fase
```bash
POST /api/tournaments/{tournament_id}/phases/{phase_id}/generate-fixtures
Authorization: Bearer {token}

# Respuesta
{
  "message": "Fixtures generated successfully",
  "matches_created": 12,
  "phase_name": "Liga Regular",
  "phase_type": "round_robin"
}
```

### 🎮 Gestión de Estados de Fases

#### Iniciar Fase (pending → active)
```bash
POST /api/tournaments/{tournament_id}/phases/{phase_id}/start
Authorization: Bearer {token}

# Respuesta
{
  "message": "Phase started successfully",
  "phase": {
    "id": "uuid",
    "name": "Liga Regular",
    "status": "active"
  },
  "progress": {
    "total_matches": 12,
    "completed_matches": 0,
    "completion_percentage": 0
  }
}
```

#### Completar Fase (active → completed)
```bash
POST /api/tournaments/{tournament_id}/phases/{phase_id}/complete
Authorization: Bearer {token}

# Respuesta
{
  "message": "Phase completed successfully",
  "phase": {
    "status": "completed"
  },
  "progress": {
    "completion_percentage": 100
  }
}
```

#### Cancelar Fase (pending|active → cancelled)
```bash
POST /api/tournaments/{tournament_id}/phases/{phase_id}/cancel
Authorization: Bearer {token}

# Respuesta
{
  "message": "Phase cancelled successfully",
  "phase": {
    "status": "cancelled"
  }
}
```

#### Consultar Progreso de Fase
```bash
GET /api/tournaments/{tournament_id}/phases/{phase_id}/progress
# Público

# Respuesta
{
  "phase_id": "uuid",
  "phase_name": "Liga Regular",
  "phase_status": "active",
  "progress": {
    "total_matches": 12,
    "completed_matches": 8,
    "scheduled_matches": 4,
    "completion_percentage": 66.67
  },
  "can_be_started": false,
  "can_be_completed": true,
  "can_be_cancelled": true,
  "should_auto_complete": false
}
```

### 📅 Consulta de Fixtures

#### Ver Fixtures del Torneo (Organizados por Fases)
```bash
GET /api/tournaments/{tournament_id}/fixtures
# Público

# Parámetros opcionales:
# ?round=1 - Filtrar por ronda específica
# ?phase_id=uuid - Filtrar por fase específica

# Respuesta
{
  "tournament_id": "uuid",
  "tournament_name": "Copa Birrias 2024",
  "total_matches": 26,
  "total_phases": 2,
  "phases": [
    {
      "phase_id": "uuid",
      "phase_name": "Liga Regular",
      "phase_type": "round_robin",
      "phase_number": 1,
      "total_matches": 12,
      "total_rounds": 2,
      "rounds": [
        {
          "round": 1,
          "matches_count": 6,
          "matches": [
            {
              "id": "uuid",
              "round": 1,
              "group_number": null,
              "match_type": "regular",
              "home_team": {
                "id": "uuid",
                "name": "Arsenal",
                "shield": "url"
              },
              "away_team": {
                "id": "uuid",
                "name": "Barcelona",
                "shield": "url"
              },
              "match_date": null,
              "venue": null,
              "status": "scheduled",
              "home_score": 0,
              "away_score": 0
            }
          ]
        }
      ]
    }
  ]
}
```

### 👥 Gestión de Equipos

#### Agregar Equipos al Torneo (Individual)
```bash
POST /api/tournaments/{tournament_id}/teams
Authorization: Bearer {token}
{
  "team_id": "uuid"
}
```

#### Agregar Múltiples Equipos al Torneo
```bash
POST /api/tournaments/{tournament_id}/teams/bulk
Authorization: Bearer {token}
{
  "team_ids": ["uuid1", "uuid2", "uuid3"]
}

# Respuesta
{
  "message": "3 teams added to tournament successfully",
  "added_count": 3,
  "skipped_count": 0,
  "total_teams": 8
}
```

#### Listar Equipos
```bash
GET /api/teams
# Público

# Parámetros:
# ?all=true - Obtener todos los equipos sin paginación
```

### 🎮 Gestión de Partidos

#### Listar Partidos
```bash
GET /api/matches
# Público
```

#### Actualizar Resultado de Partido
```bash
PUT /api/matches/{match_id}
Authorization: Bearer {token}
{
  "home_score": 2,
  "away_score": 1,
  "status": "finished"
}
```

## 🛠️ Instalación y Configuración

### Requisitos
- PHP 8.1+
- Composer
- SQLite

### Instalación
```bash
# Clonar repositorio
git clone <repository-url>
cd birrias-api

# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Crear usuario admin (opcional)
php artisan tinker
User::create([
    'name' => 'Admin',
    'email' => 'admin@birrias.com',
    'password' => Hash::make('password'),
    'role' => 'admin'
]);

# Iniciar servidor
php artisan serve --host=0.0.0.0 --port=8000
```

### Seeders (Opcional)
```bash
# Crear datos de prueba
php artisan db:seed
```

## 📖 Documentación Swagger

La documentación completa de la API está disponible en:
```
http://localhost:8000/api/documentation
```

## 🔄 Flujo de Trabajo Típico

### 1. Crear Torneo con Fases Dinámicas
```bash
# 1. Crear torneo (sin especificar formato)
POST /api/tournaments
{
  "name": "Liga Birrias 2024",
  "start_date": "2024-06-01"
  # El formato se establece automáticamente como "custom"
}

# 2. Crear primera fase (Liga)
POST /api/tournaments/{id}/phases
{
  "name": "Temporada Regular",
  "type": "round_robin",
  "home_away": true,
  "teams_advance": 8
}

# 3. Crear segunda fase (Playoffs)
POST /api/tournaments/{id}/phases
{
  "name": "Playoffs",
  "type": "single_elimination",
  "teams_advance": 1,
  "home_away": true
}

# 4. Agregar equipos
POST /api/tournaments/{id}/teams/bulk
{
  "team_ids": ["uuid1", "uuid2", ...]
}

# 5. Generar fixtures para cada fase
POST /api/tournaments/{id}/phases/{phase1_id}/generate-fixtures
POST /api/tournaments/{id}/phases/{phase2_id}/generate-fixtures

# 6. Consultar fixtures
GET /api/tournaments/{id}/fixtures
```

### 2. Gestión Durante el Torneo
```bash
# Actualizar resultados
PUT /api/matches/{match_id}
{
  "home_score": 2,
  "away_score": 1,
  "status": "finished"
}

# Ver standings
GET /api/standings/tournament/{tournament_id}

# Avanzar a siguiente fase (manual por ahora)
# Los equipos clasificados se determinan según los resultados
```

## 🎯 Casos de Uso Cubiertos

### Liga Simple
1. **Temporada Regular**: Todos contra todos, ida y vuelta

### Liga con Playoffs
1. **Temporada Regular**: Todos contra todos, ida y vuelta
2. **Playoffs**: Los mejores 8 equipos, eliminación directa

### Copa Estilo Mundial
1. **Fase de Grupos**: 8 grupos de 4 equipos
2. **Octavos**: 16 equipos, eliminación directa
3. **Cuartos**: 8 equipos, eliminación directa
4. **Semifinales**: 4 equipos, eliminación directa
5. **Final**: 2 equipos, partido único

## 🚨 Notas Importantes

1. **Solo un administrador** puede crear/modificar torneos y fases
2. **Las fases se ejecutan secuencialmente** según el campo `order`
3. **Los fixtures se generan automáticamente** según el tipo de fase
4. **Los equipos que avanzan** se configuran por fase
5. **Cada fase es independiente** en su configuración
6. **MVP simplificado**: Solo 3 tipos de fase para mayor simplicidad

## 📞 Soporte

Para soporte técnico o preguntas sobre la implementación, contacta al equipo de desarrollo.

---

**¡El sistema de fases dinámicas simplificado te permite crear los torneos más comunes de forma rápida y sencilla!** 🏆⚽