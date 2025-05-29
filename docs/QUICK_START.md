# 🚀 Guía de Inicio Rápido - Birrias Tournament API v2.0 (MVP)

## 📋 Flujo Simplificado para Crear Torneos

Con el sistema de fases dinámicas simplificado, crear un torneo es más simple que nunca. Solo 3 tipos de fase para cubrir todos los casos comunes.

### 🎯 Paso a Paso

#### 1. Autenticación
```bash
curl -X POST "http://localhost:8000/api/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@birrias.com",
    "password": "password"
  }'

# Respuesta:
# {
#   "token": "27|MfMUxunyum3Au2n529xWkKAmB3uzZsay1QB4dZkc7b9150ef",
#   "user": {...}
# }
```

#### 2. Crear Torneo (¡Sin formato requerido!)
```bash
curl -X POST "http://localhost:8000/api/tournaments" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {tu_token}" \
  -d '{
    "name": "Liga Birrias 2024",
    "start_date": "2024-06-01"
  }'

# Respuesta:
# {
#   "id": "uuid-del-torneo",
#   "name": "Liga Birrias 2024",
#   "format": "custom",  // ← Automáticamente asignado
#   "status": "inactive",
#   ...
# }
```

#### 3. Configurar Fases Dinámicamente

##### Fase 1: Liga Regular
```bash
curl -X POST "http://localhost:8000/api/tournaments/{tournament_id}/phases" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {tu_token}" \
  -d '{
    "name": "Liga Regular",
    "type": "round_robin",
    "home_away": true,
    "teams_advance": 8
  }'

# Respuesta:
# {
#   "id": "uuid-fase-1",
#   "phase_number": 1,
#   "name": "Liga Regular",
#   "type": "round_robin",
#   "is_active": true,
#   ...
# }
```

##### Fase 2: Playoffs
```bash
curl -X POST "http://localhost:8000/api/tournaments/{tournament_id}/phases" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {tu_token}" \
  -d '{
    "name": "Playoffs",
    "type": "single_elimination",
    "teams_advance": 1,
    "home_away": true
  }'
```

#### 4. Agregar Equipos
```bash
curl -X POST "http://localhost:8000/api/tournaments/{tournament_id}/teams/bulk" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {tu_token}" \
  -d '{
    "team_ids": [
      "uuid-equipo-1",
      "uuid-equipo-2",
      "uuid-equipo-3",
      "uuid-equipo-4"
    ]
  }'
```

#### 5. Generar Fixtures por Fase
```bash
# Generar fixtures para Liga Regular
curl -X POST "http://localhost:8000/api/tournaments/{tournament_id}/phases/{fase1_id}/generate-fixtures" \
  -H "Authorization: Bearer {tu_token}"

# Generar fixtures para Playoffs
curl -X POST "http://localhost:8000/api/tournaments/{tournament_id}/phases/{fase2_id}/generate-fixtures" \
  -H "Authorization: Bearer {tu_token}"
```

#### 6. Consultar Fixtures
```bash
# Ver todos los fixtures organizados por fases
curl -X GET "http://localhost:8000/api/tournaments/{tournament_id}/fixtures"

# Ver fixtures de una fase específica
curl -X GET "http://localhost:8000/api/tournaments/{tournament_id}/fixtures?phase_id={fase_id}"
```

## 🎮 Ejemplos de Configuración (MVP)

### ⚽ Liga Simple
```bash
# 1. Crear torneo
POST /api/tournaments
{
  "name": "Liga Simple 2024"
}

# 2. Una sola fase: todos contra todos
POST /api/tournaments/{id}/phases
{
  "name": "Temporada",
  "type": "round_robin",
  "home_away": true
}
```

### 🏆 Copa Eliminatoria
```bash
# 1. Crear torneo
POST /api/tournaments
{
  "name": "Copa Eliminatoria 2024"
}

# 2. Una sola fase: eliminación directa
POST /api/tournaments/{id}/phases
{
  "name": "Copa",
  "type": "single_elimination",
  "teams_advance": 16,
  "home_away": false
}
```

### 🌍 Estilo Mundial
```bash
# 1. Crear torneo
POST /api/tournaments
{
  "name": "Mundial Birrias 2024"
}

# 2. Fase de Grupos
POST /api/tournaments/{id}/phases
{
  "name": "Fase de Grupos",
  "type": "groups",
  "groups_count": 4,
  "teams_per_group": 4,
  "teams_advance": 8
}

# 3. Cuartos de Final
POST /api/tournaments/{id}/phases
{
  "name": "Cuartos de Final",
  "type": "single_elimination",
  "teams_advance": 4,
  "home_away": false
}

# 4. Semifinales
POST /api/tournaments/{id}/phases
{
  "name": "Semifinales",
  "type": "single_elimination",
  "teams_advance": 2,
  "home_away": false
}

# 5. Final
POST /api/tournaments/{id}/phases
{
  "name": "Final",
  "type": "single_elimination",
  "teams_advance": 1,
  "home_away": false
}
```

## 🔍 Consultas Útiles

### Ver Tipos de Fase Disponibles (MVP)
```bash
curl -X GET "http://localhost:8000/api/tournament-phase-types"

# Respuesta:
# {
#   "phase_types": [
#     {
#       "value": "round_robin",
#       "label": "Liga (Todos contra Todos)",
#       "description": "Cada equipo juega contra todos los demás equipos. Ideal para ligas regulares.",
#       "supports_home_away": true,
#       "required_fields": [],
#       "optional_fields": ["home_away", "teams_advance"],
#       "example": {...}
#     },
#     {
#       "value": "single_elimination",
#       "label": "Eliminación Directa",
#       "description": "Eliminación directa - quien pierde queda eliminado. Ideal para playoffs.",
#       "supports_home_away": true,
#       "required_fields": ["teams_advance"],
#       "optional_fields": ["home_away"],
#       "example": {...}
#     },
#     {
#       "value": "groups",
#       "label": "Fase de Grupos",
#       "description": "Los equipos se dividen en grupos y juegan todos contra todos dentro del grupo. Ideal para mundiales.",
#       "supports_home_away": false,
#       "required_fields": ["groups_count", "teams_per_group"],
#       "optional_fields": ["teams_advance"],
#       "example": {...}
#     }
#   ],
#   "note": "MVP simplificado - Solo 3 tipos de fase disponibles",
#   "total_types": 3
# }
```

### Ver Formatos Disponibles (Legados + Nuevo)
```bash
curl -X GET "http://localhost:8000/api/tournaments/formats"
```

### Ver Fases de un Torneo
```bash
curl -X GET "http://localhost:8000/api/tournaments/{tournament_id}/phases"
```

### Ver Equipos Disponibles
```bash
curl -X GET "http://localhost:8000/api/teams?all=true"
```

## ✨ Ventajas del Sistema Simplificado

### ✅ Antes (Complejo)
```bash
# Muchos tipos de fase y configuraciones complejas
POST /api/tournaments/{id}/phases
{
  "name": "Mi Fase",
  "type": "double_elimination",  // ← Muchos tipos
  "config": {                   // ← Configuración compleja
    "bracket_seeding": "ranked",
    "grand_final_advantage": true,
    "bracket_reset": true,
    "upper_bracket_advantage": "game"
  }
}
```

### 🚀 Ahora (MVP Simplificado)
```bash
# Solo 3 tipos esenciales, configuración simple
POST /api/tournaments/{id}/phases
{
  "name": "Mi Fase",
  "type": "single_elimination",  // ← Solo 3 tipos esenciales
  "teams_advance": 8,           // ← Configuración directa
  "home_away": true             // ← Sin objetos complejos
}
```

## 🎯 Beneficios del MVP

1. **Simplicidad**: Solo 3 tipos de fase cubren el 90% de casos de uso
2. **Rapidez**: Menos configuración = desarrollo más rápido
3. **Claridad**: Parámetros directos, sin objetos complejos
4. **Mantenibilidad**: Menos código = menos bugs
5. **Escalabilidad**: Fácil agregar más tipos después si es necesario

## 📚 Recursos Adicionales

- **Documentación Completa**: `README.md`
- **Documentación Swagger**: http://localhost:8000/api/documentation

---

**¡Ahora crear torneos es más simple y directo que nunca!** 🎉🏆 