# 🎯 Guía de Estados de Fases - Birrias Tournament API v2.2.0

## 📋 Sistema de Estados Optimizado

El sistema de fases dinámicas ahora incluye un **control de estados robusto** que permite gestionar el flujo del torneo de manera clara y controlada.

## 🔄 Estados Disponibles

### 1. **`pending`** - Pendiente
- **Descripción**: Fase creada pero no iniciada
- **Color**: Gris
- **Acciones permitidas**:
  - ✅ Generar fixtures
  - ✅ Modificar configuración
  - ✅ Iniciar fase (`→ active`)
  - ✅ Cancelar fase (`→ cancelled`)

### 2. **`active`** - Activa
- **Descripción**: Fase en curso, partidos programados/jugándose
- **Color**: Verde
- **Acciones permitidas**:
  - ✅ Actualizar resultados de partidos
  - ✅ Completar fase (`→ completed`)
  - ✅ Cancelar fase (`→ cancelled`)
  - ❌ Modificar configuración estructural

### 3. **`completed`** - Completada
- **Descripción**: Fase finalizada, todos los partidos terminados
- **Color**: Azul
- **Acciones permitidas**:
  - ✅ Consultar resultados
  - ✅ Ver estadísticas finales
  - ❌ Modificar cualquier cosa (estado final)

### 4. **`cancelled`** - Cancelada
- **Descripción**: Fase cancelada por algún motivo
- **Color**: Rojo
- **Acciones permitidas**:
  - ✅ Consultar datos históricos
  - ❌ Reactivar o modificar (estado final)

## 🔀 Flujo de Estados

### Flujo Normal
```
pending → active → completed
```

### Flujo con Cancelación
```
pending → cancelled
active → cancelled
```

### Transiciones Válidas
| Estado Actual | Puede cambiar a |
|---------------|-----------------|
| `pending` | `active`, `cancelled` |
| `active` | `completed`, `cancelled` |
| `completed` | *(ninguno - estado final)* |
| `cancelled` | *(ninguno - estado final)* |

## 🛠️ API Endpoints

### Consultar Estados Disponibles
```bash
GET /api/tournament-phase-types

# Respuesta incluye:
{
  "phase_statuses": [
    {
      "value": "pending",
      "label": "Pendiente",
      "description": "Fase creada pero no iniciada. Se pueden generar fixtures.",
      "color": "gray",
      "can_transition_to": ["active", "cancelled"]
    }
    // ... otros estados
  ],
  "status_flow": {
    "initial": "pending",
    "normal_flow": "pending → active → completed",
    "cancellation": "pending|active → cancelled",
    "final_states": ["completed", "cancelled"]
  }
}
```

### Crear Fase con Estado Específico
```bash
POST /api/tournaments/{tournament_id}/phases
Authorization: Bearer {token}
{
  "name": "Liga Regular",
  "type": "round_robin",
  "status": "pending",  // ← Opcional, por defecto es 'pending'
  "home_away": true,
  "teams_advance": 8
}
```

### Cambiar Estado de Fase (Método Directo)
```bash
PUT /api/tournaments/{tournament_id}/phases/{phase_id}
Authorization: Bearer {token}
{
  "status": "active"  // Cambio directo con validación de transición
}
```

### Métodos Específicos de Gestión de Estados

#### Iniciar Fase
```bash
POST /api/tournaments/{tournament_id}/phases/{phase_id}/start
Authorization: Bearer {token}

# Respuesta:
{
  "message": "Phase started successfully",
  "phase": {
    "id": "uuid",
    "name": "Liga Regular",
    "status": "active",
    // ... otros campos
  },
  "progress": {
    "total_matches": 12,
    "completed_matches": 0,
    "scheduled_matches": 12,
    "in_progress_matches": 0,
    "completion_percentage": 0
  }
}
```

#### Completar Fase
```bash
POST /api/tournaments/{tournament_id}/phases/{phase_id}/complete
Authorization: Bearer {token}

# Respuesta:
{
  "message": "Phase completed successfully",
  "phase": {
    "status": "completed",
    // ... otros campos
  },
  "progress": {
    "completion_percentage": 100
  }
}
```

#### Cancelar Fase
```bash
POST /api/tournaments/{tournament_id}/phases/{phase_id}/cancel
Authorization: Bearer {token}

# Respuesta:
{
  "message": "Phase cancelled successfully",
  "phase": {
    "status": "cancelled"
  }
}
```

### Consultar Progreso de Fase
```bash
GET /api/tournaments/{tournament_id}/phases/{phase_id}/progress
# Público, no requiere autenticación

# Respuesta:
{
  "phase_id": "uuid",
  "phase_name": "Liga Regular",
  "phase_status": "active",
  "progress": {
    "total_matches": 12,
    "completed_matches": 8,
    "scheduled_matches": 4,
    "in_progress_matches": 0,
    "completion_percentage": 66.67
  },
  "can_be_started": false,
  "can_be_completed": true,
  "can_be_cancelled": true,
  "should_auto_complete": false
}
```

## 🎮 Casos de Uso Prácticos

### Crear y Gestionar Liga Simple
```bash
# 1. Crear torneo
POST /api/tournaments
{
  "name": "Liga Birrias 2024"
}

# 2. Crear fase (automáticamente en 'pending')
POST /api/tournaments/{id}/phases
{
  "name": "Liga Regular",
  "type": "round_robin",
  "home_away": true,
  "teams_advance": 8
}

# 3. Agregar equipos
POST /api/tournaments/{id}/teams/bulk
{
  "team_ids": ["uuid1", "uuid2", "uuid3", "uuid4"]
}

# 4. Generar fixtures (solo en estado 'pending')
POST /api/tournaments/{id}/phases/{phase_id}/generate-fixtures

# 5. Iniciar la fase
POST /api/tournaments/{id}/phases/{phase_id}/start

# 6. Actualizar resultados de partidos
PUT /api/matches/{match_id}
{
  "home_score": 2,
  "away_score": 1,
  "status": "finished"
}

# 7. Completar fase cuando todos los partidos terminen
POST /api/tournaments/{id}/phases/{phase_id}/complete
```

### Gestionar Torneo Multi-Fase
```bash
# Liga Regular → Playoffs

# 1. Crear fase 1 (Liga)
POST /api/tournaments/{id}/phases
{
  "name": "Liga Regular",
  "type": "round_robin",
  "status": "pending"
}

# 2. Crear fase 2 (Playoffs) - queda en 'pending'
POST /api/tournaments/{id}/phases
{
  "name": "Playoffs",
  "type": "single_elimination",
  "teams_advance": 1
}

# 3. Iniciar solo la primera fase
POST /api/tournaments/{id}/phases/{phase1_id}/start

# 4. Cuando termine la liga, completarla
POST /api/tournaments/{id}/phases/{phase1_id}/complete

# 5. Iniciar playoffs
POST /api/tournaments/{id}/phases/{phase2_id}/start
```

## 🔍 Validaciones y Reglas

### Validaciones de Transición
- ✅ **`pending → active`**: Solo si la fase tiene fixtures generados
- ✅ **`active → completed`**: Solo si todos los partidos están terminados
- ✅ **`pending|active → cancelled`**: Siempre permitido
- ❌ **Estados finales**: `completed` y `cancelled` no pueden cambiar

### Reglas de Negocio
1. **Una sola fase activa por torneo** (recomendado)
2. **Generar fixtures solo en estado `pending`**
3. **Modificar configuración solo en estado `pending`**
4. **Actualizar resultados solo en estado `active`**
5. **Auto-completar fase** cuando todos los partidos terminen

### Errores Comunes
```bash
# Error: Intentar iniciar fase ya activa
POST /api/tournaments/{id}/phases/{phase_id}/start
# Respuesta 400:
{
  "message": "Phase cannot be started",
  "current_status": "active",
  "reason": "Only pending phases can be started"
}

# Error: Transición inválida
PUT /api/tournaments/{id}/phases/{phase_id}
{
  "status": "pending"  // Desde 'completed'
}
# Respuesta 422:
{
  "errors": {
    "status": ["Cannot transition from 'completed' to 'pending'"]
  }
}
```

## 🎯 Beneficios del Sistema

### Para Administradores
- ✅ **Control total** del flujo del torneo
- ✅ **Prevención de errores** con validaciones automáticas
- ✅ **Visibilidad clara** del estado de cada fase
- ✅ **Gestión granular** de transiciones

### Para Desarrolladores
- ✅ **Estados bien definidos** sin ambigüedades
- ✅ **Validaciones automáticas** de transiciones
- ✅ **Métodos helper** para verificar capacidades
- ✅ **Progreso automático** basado en partidos

### Para Usuarios Finales
- ✅ **Interfaz clara** con colores por estado
- ✅ **Acciones contextuales** según el estado
- ✅ **Progreso visual** de cada fase
- ✅ **Información transparente** del flujo

## 🚀 Próximas Mejoras

### Funcionalidades Futuras
- 🔮 **Auto-transición**: Completar automáticamente cuando todos los partidos terminen
- 🔮 **Notificaciones**: Alertas cuando una fase cambie de estado
- 🔮 **Historial**: Log de cambios de estado con timestamps
- 🔮 **Validaciones avanzadas**: Reglas de negocio más específicas

---

**¡El sistema de estados de fases proporciona un control robusto y claro del flujo del torneo!** 🎯🏆 