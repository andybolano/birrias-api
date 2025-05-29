# Plantillas CSV para Importación de Jugadores

Este directorio contiene las plantillas CSV para la importación masiva de jugadores.

## Archivos Disponibles

### 📄 `plantilla-jugadores-vacia.csv`
Plantilla vacía con solo los headers. Ideal para llenar con tus propios datos.

### 📄 `plantilla-jugadores.csv`
Plantilla con ejemplos de datos. Útil para entender el formato esperado.

## Formato Requerido

Las columnas deben estar en este orden exacto:

| Columna | Descripción | Ejemplo |
|---------|-------------|---------|
| `Nombres` | Nombre(s) del jugador | Juan Carlos |
| `apellidos` | Apellido(s) del jugador | García López |
| `identificacion` | Número de identificación | 12345678 |
| `eps` | EPS del jugador | Sura |
| `posicion` | Posición en el campo | Delantero |
| `Numero de camiseta` | Número de camiseta (1-999) | 10 |
| `fecha de nacimiento` | Fecha en formato YYYY-MM-DD | 1995-03-15 |

## Cómo Usar

1. **Descargar plantilla**: 
   - GET `/api/players/template` (plantilla vacía)
   - GET `/api/players/template?type=example` (plantilla con ejemplos)

2. **Llenar datos**: Completa el archivo CSV con la información de tus jugadores

3. **Importar**: 
   - POST `/api/players/import`
   - Adjunta el archivo CSV y el `team_id`

## Notas Importantes

- ✅ **Formatos soportados**: CSV, TXT, XLSX, XLS
- ✅ **Tamaño máximo**: 2MB
- ✅ **Codificación**: UTF-8 recomendado
- ⚠️ **Headers obligatorios**: Deben coincidir exactamente
- ⚠️ **Fechas**: Formato YYYY-MM-DD
- ⚠️ **Números de camiseta**: Entre 1 y 999 