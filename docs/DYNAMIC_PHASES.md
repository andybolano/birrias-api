# 🎯 Sistema de Fases Dinámicas - Documentación Técnica

## 📋 Índice
1. [Introducción](#introducción)
2. [Arquitectura](#arquitectura)
3. [Tipos de Fase](#tipos-de-fase)
4. [API Reference](#api-reference)
5. [Ejemplos Prácticos](#ejemplos-prácticos)
6. [Configuraciones Avanzadas](#configuraciones-avanzadas)
7. [Troubleshooting](#troubleshooting)

## 🚀 Introducción

El Sistema de Fases Dinámicas permite crear torneos completamente personalizables donde cada fase puede tener diferentes configuraciones y tipos de competencia. A diferencia del sistema anterior con formatos fijos, ahora puedes crear cualquier estructura de torneo que puedas imaginar.

### Ventajas del Sistema
- ✅ **Flexibilidad Total**: Combina diferentes tipos de fase
- ✅ **Configuración Granular**: Cada fase tiene sus propios parámetros
- ✅ **Escalabilidad**: Fácil agregar nuevos tipos de fase
- ✅ **Generación Automática**: Fixtures automáticos por tipo de fase
- ✅ **Control de Flujo**: Gestión de equipos que avanzan entre fases

## 🏗️ Arquitectura

### Estructura de Base de Datos

```sql
-- Tabla principal de fases
tournament_phases:
  - id (UUID)
  - tournament_id (UUID, FK)
  - phase_number (INT) -- Número secuencial de la fase
  - name (VARCHAR) -- Nombre descriptivo
  - type (ENUM) -- Tipo de fase
  - config (JSON) -- Configuración específica
  - home_away (BOOLEAN) -- Soporte ida/vuelta
  - teams_advance (INT) -- Equipos que avanzan
  - groups_count (INT) -- Número de grupos (para tipo groups)
  - teams_per_group (INT) -- Equipos por grupo
  - is_active (BOOLEAN) -- Fase actualmente activa
  - is_completed (BOOLEAN) -- Fase completada
  - order (INT) -- Orden de ejecución

-- Tabla de partidos actualizada
matches:
  - phase_id (UUID, FK) -- Vinculación con la fase
  - group_number (INT) -- Número de grupo (para fases de grupos)
  - match_type (VARCHAR) -- Tipo de partido (regular, semifinal, final, etc.)
```

### Relaciones
```
Tournament 1:N TournamentPhase 1:N Match
```

### Flujo de Datos
```
1. Crear Torneo
2. Definir Fases (secuencialmente)
3. Agregar Equipos al Torneo
4. Generar Fixtures por Fase
5. Ejecutar Fases (actualizar resultados)
6. Avanzar Equipos a Siguiente Fase
```

## 🎮 Tipos de Fase

### 1. Round Robin (`round_robin`)
**Descripción**: Todos los equipos juegan contra todos los demás.

**Configuración**:
```json
{
  "name": "Liga Regular",
  "type": "round_robin",
  "home_away": true, // Ida y vuelta
  "teams_advance": 8, // Cuántos avanzan (opcional)
  "config": {
    "rounds": 2 // Número de vueltas
  }
}
```

**Algoritmo de Generación**:
- Combinaciones C(n,2) para n equipos
- Si `home_away=true`, duplica partidos invirtiendo local/visitante
- Organiza en rondas balanceadas

### 2. Single Elimination (`single_elimination`)
**Descripción**: Eliminación directa - quien pierde queda eliminado.

**Configuración**:
```json
{
  "name": "Playoffs",
  "type": "single_elimination",
  "teams_advance": 8, // Equipos que participan
  "home_away": true, // Ida y vuelta por eliminatoria
  "config": {
    "bracket_seeding": "ranked", // random, ranked
    "bye_rounds": true // Permitir descansos
  }
}
```

**Algoritmo de Generación**:
- Calcula rondas: `ceil(log2(teams_advance))`
- Crea bracket balanceado
- Si `home_away=true`, crea partidos de ida y vuelta

### 3. Groups (`groups`)
**Descripción**: Divide equipos en grupos, todos contra todos dentro del grupo.

**Configuración**:
```json
{
  "name": "Fase de Grupos",
  "type": "groups",
  "groups_count": 4,
  "teams_per_group": 4,
  "teams_advance": 8, // Total que avanzan de todos los grupos
  "home_away": false,
  "config": {
    "group_assignment": "random", // random, seeded
    "advance_per_group": 2 // Cuántos por grupo avanzan
  }
}
```

**Algoritmo de Generación**:
- Divide equipos en `groups_count` grupos
- Aplica round_robin dentro de cada grupo
- Asigna `group_number` a cada partido

### 4. Double Elimination (`double_elimination`)
**Descripción**: Doble eliminación - necesitas perder dos veces para ser eliminado.

**Configuración**:
```json
{
  "name": "Bracket Doble",
  "type": "double_elimination",
  "teams_advance": 16,
  "config": {
    "bracket_seeding": "ranked",
    "grand_final_advantage": true // Ventaja para ganador del upper bracket
  }
}
```

### 5. Playoffs (`playoffs`)
**Descripción**: Configuración personalizada de playoffs.

**Configuración**:
```json
{
  "name": "Playoffs Personalizados",
  "type": "playoffs",
  "teams_advance": 8,
  "config": {
    "format": "best_of_three", // best_of_one, best_of_three, best_of_five
    "seeding_method": "conference_based"
  }
}
```

## 📡 API Reference

### Endpoints Principales

#### Obtener Tipos de Fase
```http
GET /api/tournament-phase-types
```

**Respuesta**:
```json
{
  "phase_types": [
    {
      "value": "round_robin",
      "label": "Todos contra Todos",
      "description": "Cada equipo juega contra todos los demás equipos",
      "supports_home_away": true,
      "required_fields": [],
      "optional_fields": ["home_away", "teams_advance"],
      "config_options": {
        "rounds": "Número de vueltas"
      }
    }
  ]
}
```

#### Crear Fase
```http
POST /api/tournaments/{tournament_id}/phases
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Fase de Grupos",
  "type": "groups",
  "groups_count": 4,
  "teams_per_group": 4,
  "teams_advance": 8,
  "home_away": false,
  "config": {
    "group_assignment": "random"
  }
}
```

#### Generar Fixtures
```http
POST /api/tournaments/{tournament_id}/phases/{phase_id}/generate-fixtures
Authorization: Bearer {token}
```

**Respuesta**:
```json
{
  "message": "Fixtures generated successfully",
  "matches_created": 24,
  "phase_name": "Fase de Grupos",
  "phase_type": "groups"
}
```

#### Consultar Fixtures por Fase
```http
GET /api/tournaments/{tournament_id}/fixtures?phase_id={phase_id}
```

### Validaciones

#### Crear Fase
```php
$rules = [
    'name' => 'required|string|max:255',
    'type' => 'required|in:round_robin,single_elimination,double_elimination,groups,playoffs',
    'home_away' => 'boolean',
    'teams_advance' => 'nullable|integer|min:1',
    'groups_count' => 'nullable|integer|min:1',
    'teams_per_group' => 'nullable|integer|min:2',
    'config' => 'nullable|array'
];
```

## 🎯 Ejemplos Prácticos

### Ejemplo 1: Copa del Mundo
```bash
# 1. Crear torneo (formato automáticamente "custom")
POST /api/tournaments
{
  "name": "Copa del Mundo Birrias 2024",
  "start_date": "2024-06-01"
}

# 2. Fase de Grupos (32 equipos, 8 grupos de 4)
POST /api/tournaments/{id}/phases
{
  "name": "Fase de Grupos",
  "type": "groups",
  "groups_count": 8,
  "teams_per_group": 4,
  "teams_advance": 16
}

# 3. Octavos de Final
POST /api/tournaments/{id}/phases
{
  "name": "Octavos de Final",
  "type": "single_elimination",
  "teams_advance": 8,
  "home_away": false
}

# 4. Cuartos de Final
POST /api/tournaments/{id}/phases
{
  "name": "Cuartos de Final",
  "type": "single_elimination",
  "teams_advance": 4,
  "home_away": false
}

# 5. Semifinales
POST /api/tournaments/{id}/phases
{
  "name": "Semifinales",
  "type": "single_elimination",
  "teams_advance": 2,
  "home_away": false
}

# 6. Final
POST /api/tournaments/{id}/phases
{
  "name": "Final",
  "type": "single_elimination",
  "teams_advance": 1,
  "home_away": false
}
```

### Ejemplo 2: Liga con Playoffs
```bash
# 1. Temporada Regular
POST /api/tournaments/{id}/phases
{
  "name": "Temporada Regular",
  "type": "round_robin",
  "home_away": true,
  "teams_advance": 8,
  "config": {
    "rounds": 2
  }
}

# 2. Playoffs
POST /api/tournaments/{id}/phases
{
  "name": "Playoffs",
  "type": "single_elimination",
  "teams_advance": 1,
  "home_away": true,
  "config": {
    "bracket_seeding": "ranked"
  }
}
```

### Ejemplo 3: Torneo de Conferencias
```bash
# 1. Fase de Conferencias
POST /api/tournaments/{id}/phases
{
  "name": "Conferencia Este",
  "type": "groups",
  "groups_count": 2,
  "teams_per_group": 6,
  "teams_advance": 4
}

# 2. Playoffs Inter-Conferencia
POST /api/tournaments/{id}/phases
{
  "name": "Playoffs",
  "type": "single_elimination",
  "teams_advance": 1,
  "home_away": true
}
```

## ⚙️ Configuraciones Avanzadas

### Campo `config` por Tipo de Fase

#### Round Robin
```json
{
  "config": {
    "rounds": 2,
    "balanced_schedule": true,
    "rest_days": 1
  }
}
```

#### Single Elimination
```json
{
  "config": {
    "bracket_seeding": "ranked", // random, ranked, manual
    "bye_rounds": true,
    "third_place_match": true,
    "overtime_rules": "extra_time_penalties"
  }
}
```

#### Groups
```json
{
  "config": {
    "group_assignment": "seeded", // random, seeded, manual
    "advance_per_group": 2,
    "tiebreaker_rules": [
      "points",
      "goal_difference", 
      "goals_scored",
      "head_to_head"
    ],
    "group_names": ["A", "B", "C", "D"]
  }
}
```

### Estados de Fase

```php
// Estados posibles
$phase->is_active = true;    // Fase actualmente en curso
$phase->is_completed = false; // Fase no completada
$phase->order = 1;           // Primera fase

// Transiciones automáticas
// Cuando todos los partidos de una fase terminan:
// - is_completed = true
// - is_active = false
// - Siguiente fase: is_active = true
```

### Avance de Equipos

```php
// Configuración de avance
$phase->teams_advance = 8; // 8 equipos avanzan a la siguiente fase

// Para grupos:
$config = [
    "advance_per_group" => 2, // 2 por grupo
    "groups_count" => 4       // 4 grupos = 8 total
];

// Cálculo automático:
// teams_advance = advance_per_group * groups_count
```

## 🔧 Troubleshooting

### Problemas Comunes

#### 1. Error: "Not enough teams for phase"
```
Causa: Intentar generar fixtures sin suficientes equipos
Solución: Agregar más equipos al torneo antes de generar fixtures
```

#### 2. Error: "Phase does not belong to this tournament"
```
Causa: Intentar generar fixtures para una fase de otro torneo
Solución: Verificar que el phase_id corresponda al tournament_id
```

#### 3. Fixtures no se generan correctamente
```
Causa: Configuración incorrecta de la fase
Solución: Verificar que todos los campos requeridos estén presentes
```

### Validaciones por Tipo

#### Groups
```php
// Validaciones automáticas:
if ($teams_count < $groups_count * 2) {
    throw new Exception('Not enough teams for groups configuration');
}

if ($teams_per_group < 2) {
    throw new Exception('Each group must have at least 2 teams');
}
```

#### Single Elimination
```php
// Validaciones automáticas:
if ($teams_advance < 2) {
    throw new Exception('Need at least 2 teams for elimination');
}

if (!isPowerOfTwo($teams_advance)) {
    // Se ajusta automáticamente al siguiente poder de 2
    $teams_advance = nextPowerOfTwo($teams_advance);
}
```

### Debugging

#### Ver Estado de Fases
```bash
GET /api/tournaments/{id}/phases

# Verificar:
# - order: Orden correcto de ejecución
# - is_active: Solo una fase activa a la vez
# - matches_count: Partidos generados correctamente
```

#### Ver Fixtures Detallados
```bash
GET /api/tournaments/{id}/fixtures?phase_id={phase_id}

# Verificar:
# - group_number: Asignación correcta de grupos
# - match_type: Tipos de partido correctos
# - round: Organización por rondas
```

## 📈 Métricas y Monitoreo

### Estadísticas por Fase
```sql
-- Partidos por fase
SELECT 
    tp.name,
    tp.type,
    COUNT(m.id) as total_matches,
    COUNT(CASE WHEN m.status = 'finished' THEN 1 END) as completed_matches
FROM tournament_phases tp
LEFT JOIN matches m ON tp.id = m.phase_id
GROUP BY tp.id;
```

### Performance
```php
// Optimizaciones implementadas:
// 1. Eager loading de relaciones
$phases = $tournament->phases()->with('matches')->get();

// 2. Índices en base de datos
// - (tournament_id, phase_number)
// - (tournament_id, order)
// - (phase_id, round)

// 3. Caché de fixtures
Cache::remember("tournament_{$id}_fixtures", 3600, function() {
    return $this->getFixtures();
});
```

---

**¡El Sistema de Fases Dinámicas te da el poder de crear cualquier tipo de torneo que puedas imaginar!** 🏆 