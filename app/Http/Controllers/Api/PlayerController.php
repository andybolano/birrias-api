<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PlayerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/players",
     *     tags={"Players"},
     *     summary="Listar jugadores",
     *     description="Obtiene la lista de jugadores con filtros opcionales (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="team_id",
     *         in="query",
     *         description="Filtrar por equipo",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de jugadores paginada",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data", 
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="first_name", type="string", nullable=true),
     *                     @OA\Property(property="last_name", type="string", nullable=true),
     *                     @OA\Property(property="identification_number", type="string", nullable=true),
     *                     @OA\Property(property="eps", type="string", nullable=true),
     *                     @OA\Property(property="full_name", type="string", description="Nombre completo del jugador"),
     *                     @OA\Property(property="position", type="string", nullable=true),
     *                     @OA\Property(property="jersey", type="integer", nullable=true),
     *                     @OA\Property(property="birthDay", type="string", format="date", nullable=true),
     *                     @OA\Property(property="personId", type="string", format="uuid", nullable=true),
     *                     @OA\Property(property="person", type="object", nullable=true),
     *                     @OA\Property(property="teams", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="created_at", type="string", format="datetime"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime")
     *                 )
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos (solo admin)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Player::with(['person', 'teams']);

        if ($request->has('team_id')) {
            $query->whereHas('teams', function ($q) use ($request) {
                $q->where('team_id', $request->team_id);
            });
        }

        $players = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($players);
    }

    /**
     * @OA\Post(
     *     path="/api/players",
     *     tags={"Players"},
     *     summary="Crear nuevo jugador",
     *     description="Crea un nuevo jugador (solo admin). Un jugador puede estar en varios equipos, pero no en múltiples equipos del mismo torneo.",
     *     security={{"apiAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", maxLength=255, example="Juan", description="Nombre del jugador"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, example="Pérez", description="Apellido del jugador"),
     *             @OA\Property(property="identification_number", type="string", maxLength=255, example="12345678", description="Número de identificación"),
     *             @OA\Property(property="eps", type="string", maxLength=255, example="Sura", description="EPS del jugador"),
     *             @OA\Property(property="position", type="string", maxLength=50, example="Delantero", description="Posición del jugador"),
     *             @OA\Property(property="jersey", type="integer", minimum=1, maximum=999, example=10, description="Número de camiseta"),
     *             @OA\Property(property="birthDay", type="string", format="date", example="1995-03-15", description="Fecha de nacimiento"),
     *             @OA\Property(property="personId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del usuario asociado"),
     *             @OA\Property(property="team_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="ID del equipo al que se asociará el jugador (opcional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Jugador creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="first_name", type="string", nullable=true),
     *             @OA\Property(property="last_name", type="string", nullable=true),
     *             @OA\Property(property="identification_number", type="string", nullable=true),
     *             @OA\Property(property="eps", type="string", nullable=true),
     *             @OA\Property(property="full_name", type="string", description="Nombre completo del jugador"),
     *             @OA\Property(property="position", type="string", nullable=true),
     *             @OA\Property(property="jersey", type="integer", nullable=true),
     *             @OA\Property(property="birthDay", type="string", format="date", nullable=true),
     *             @OA\Property(property="personId", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="person", type="object", nullable=true),
     *             @OA\Property(property="teams", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="created_at", type="string", format="datetime"),
     *             @OA\Property(property="updated_at", type="string", format="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos (solo admin)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o conflicto de torneo",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Player cannot be added to this team due to tournament conflicts"),
     *             @OA\Property(property="conflicts", type="array", @OA\Items(
     *                 @OA\Property(property="tournament_name", type="string"),
     *                 @OA\Property(property="team_name", type="string"),
     *                 @OA\Property(property="tournament_id", type="string")
     *             ))
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'identification_number' => 'nullable|string|max:255|unique:players,identification_number',
            'eps' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:50',
            'jersey' => 'nullable|integer|min:1|max:999',
            'birthDay' => 'nullable|date',
            'personId' => 'nullable|exists:users,id',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $player = Player::create([
            'id' => Str::uuid(),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'identification_number' => $request->identification_number,
            'eps' => $request->eps,
            'position' => $request->position,
            'jersey' => $request->jersey,
            'birthDay' => $request->birthDay,
            'personId' => $request->personId,
        ]);

        // Asociar al equipo si se proporciona team_id
        if ($request->has('team_id') && $request->team_id) {
            // Verificar conflictos de torneo
            $validation = $player->canJoinTeam($request->team_id);
            
            if (!$validation['can_join']) {
                $player->delete(); // Eliminar el jugador creado si hay conflictos
                return response()->json([
                    'message' => 'Player cannot be added to this team due to tournament conflicts',
                    'conflicts' => $validation['conflicts']
                ], 422);
            }
            
            $player->teams()->attach($request->team_id);
        }

        return response()->json($player->load(['person', 'teams']), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/players/{id}",
     *     tags={"Players"},
     *     summary="Ver jugador específico",
     *     description="Obtiene la información detallada de un jugador específico (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del jugador",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del jugador",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="first_name", type="string", nullable=true),
     *             @OA\Property(property="last_name", type="string", nullable=true),
     *             @OA\Property(property="identification_number", type="string", nullable=true),
     *             @OA\Property(property="eps", type="string", nullable=true),
     *             @OA\Property(property="full_name", type="string", description="Nombre completo del jugador"),
     *             @OA\Property(property="position", type="string", nullable=true),
     *             @OA\Property(property="jersey", type="integer", nullable=true),
     *             @OA\Property(property="birthDay", type="string", format="date", nullable=true),
     *             @OA\Property(property="personId", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="person", type="object", nullable=true),
     *             @OA\Property(property="teams", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="matchEvents", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="created_at", type="string", format="datetime"),
     *             @OA\Property(property="updated_at", type="string", format="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos (solo admin)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Jugador no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Player $player): JsonResponse
    {
        return response()->json(
            $player->load(['person', 'teams', 'matchEvents'])
        );
    }

    /**
     * @OA\Put(
     *     path="/api/players/{id}",
     *     tags={"Players"},
     *     summary="Actualizar jugador",
     *     description="Actualiza la información de un jugador existente (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del jugador",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", maxLength=255, example="Juan Carlos", description="Nuevo nombre del jugador"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, example="González", description="Nuevo apellido del jugador"),
     *             @OA\Property(property="identification_number", type="string", maxLength=255, example="87654321", description="Nuevo número de identificación"),
     *             @OA\Property(property="eps", type="string", maxLength=255, example="Compensar", description="Nueva EPS del jugador"),
     *             @OA\Property(property="position", type="string", maxLength=50, example="Mediocampista", description="Nueva posición del jugador"),
     *             @OA\Property(property="jersey", type="integer", minimum=1, maximum=999, example=8, description="Nuevo número de camiseta"),
     *             @OA\Property(property="birthDay", type="string", format="date", example="1995-03-15", description="Nueva fecha de nacimiento"),
     *             @OA\Property(property="personId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Nuevo ID del usuario asociado"),
     *             @OA\Property(property="team_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="ID del equipo al que se asociará el jugador (opcional, null para desasociar)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jugador actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="first_name", type="string", nullable=true),
     *             @OA\Property(property="last_name", type="string", nullable=true),
     *             @OA\Property(property="identification_number", type="string", nullable=true),
     *             @OA\Property(property="eps", type="string", nullable=true),
     *             @OA\Property(property="full_name", type="string", description="Nombre completo del jugador"),
     *             @OA\Property(property="position", type="string", nullable=true),
     *             @OA\Property(property="jersey", type="integer", nullable=true),
     *             @OA\Property(property="birthDay", type="string", format="date", nullable=true),
     *             @OA\Property(property="personId", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="person", type="object", nullable=true),
     *             @OA\Property(property="teams", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="created_at", type="string", format="datetime"),
     *             @OA\Property(property="updated_at", type="string", format="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos (solo admin)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Jugador no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function update(Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'identification_number' => 'nullable|string|max:255|unique:players,identification_number,' . $player->id,
            'eps' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:50',
            'jersey' => 'nullable|integer|min:1|max:999',
            'birthDay' => 'nullable|date',
            'personId' => 'nullable|exists:users,id',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $player->update($request->only([
            'first_name', 'last_name', 'identification_number', 'eps',
            'position', 'jersey', 'birthDay', 'personId'
        ]));

        // Gestionar asociación con equipo
        if ($request->has('team_id')) {
            if ($request->team_id) {
                // Verificar conflictos de torneo antes de cambiar asociación
                $validation = $player->canJoinTeam($request->team_id);
                
                if (!$validation['can_join']) {
                    return response()->json([
                        'message' => 'Player cannot be added to this team due to tournament conflicts',
                        'conflicts' => $validation['conflicts']
                    ], 422);
                }
                
                // Asociar al nuevo equipo (reemplaza asociaciones anteriores)
                $player->teams()->sync([$request->team_id]);
            } else {
                // Desasociar de todos los equipos
                $player->teams()->detach();
            }
        }

        return response()->json($player->load(['person', 'teams']));
    }

    /**
     * @OA\Delete(
     *     path="/api/players/{id}",
     *     tags={"Players"},
     *     summary="Eliminar jugador",
     *     description="Elimina un jugador existente (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del jugador",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jugador eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Player deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos (solo admin)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Jugador no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(Player $player): JsonResponse
    {
        $player->delete();
        
        return response()->json(['message' => 'Player deleted successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/players/{id}/teams",
     *     tags={"Players"},
     *     summary="Agregar jugador a equipo",
     *     description="Agrega un jugador a un equipo específico (solo admin). Valida que el jugador no esté ya en otro equipo del mismo torneo.",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del jugador",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"team_id"},
     *             @OA\Property(property="team_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del equipo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jugador agregado al equipo exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Player added to team successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos (solo admin)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Jugador no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o conflicto de torneo",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Player cannot be added to this team due to tournament conflicts"),
     *             @OA\Property(property="conflicts", type="array", @OA\Items(
     *                 @OA\Property(property="tournament_name", type="string"),
     *                 @OA\Property(property="team_name", type="string"),
     *                 @OA\Property(property="tournament_id", type="string")
     *             )),
     *             @OA\Property(property="details", type="string", example="A player cannot be in multiple teams within the same tournament")
     *         )
     *     )
     * )
     */
    public function addToTeam(Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id'
        ]);

        // Verificar conflictos de torneo
        $validation = $player->canJoinTeam($request->team_id);
        
        if (!$validation['can_join']) {
            return response()->json([
                'message' => 'Player cannot be added to this team due to tournament conflicts',
                'conflicts' => $validation['conflicts'],
                'details' => 'A player cannot be in multiple teams within the same tournament'
            ], 422);
        }

        $player->teams()->syncWithoutDetaching([$request->team_id]);
        
        return response()->json(['message' => 'Player added to team successfully']);
    }

    /**
     * @OA\Delete(
     *     path="/api/players/{id}/teams",
     *     tags={"Players"},
     *     summary="Remover jugador de equipo",
     *     description="Remueve un jugador de un equipo específico (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del jugador",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"team_id"},
     *             @OA\Property(property="team_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del equipo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jugador removido del equipo exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Player removed from team successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos (solo admin)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Jugador no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación - Equipo no existe",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function removeFromTeam(Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id'
        ]);

        $player->teams()->detach($request->team_id);
        
        return response()->json(['message' => 'Player removed from team successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/players/{id}/teams/check-conflicts",
     *     tags={"Players"},
     *     summary="Verificar conflictos de torneo",
     *     description="Verifica si un jugador puede ser agregado a un equipo sin violar las reglas de torneo (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del jugador",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"team_id"},
     *             @OA\Property(property="team_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del equipo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resultado de la verificación",
     *         @OA\JsonContent(
     *             @OA\Property(property="can_join", type="boolean", example=true),
     *             @OA\Property(property="conflicts", type="array", @OA\Items(
     *                 @OA\Property(property="tournament_name", type="string"),
     *                 @OA\Property(property="team_name", type="string"),
     *                 @OA\Property(property="tournament_id", type="string")
     *             )),
     *             @OA\Property(property="message", type="string", example="Player can be added to this team")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos (solo admin)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Jugador no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación - Equipo no existe",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function checkTournamentConflicts(Request $request, Player $player): JsonResponse
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id'
        ]);

        $validation = $player->canJoinTeam($request->team_id);
        
        return response()->json([
            'can_join' => $validation['can_join'],
            'conflicts' => $validation['conflicts'],
            'message' => $validation['can_join'] 
                ? 'Player can be added to this team' 
                : 'Player cannot be added to this team due to tournament conflicts'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/players/import",
     *     tags={"Players"},
     *     summary="Importar jugadores desde CSV/Excel",
     *     description="Importa múltiples jugadores desde un archivo CSV o Excel y los asocia a un equipo (solo admin). IMPORTANTE: Las identificaciones deben ser únicas tanto en la base de datos como dentro del archivo CSV.",
     *     security={{"apiAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "team_id"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Archivo CSV/Excel con datos de jugadores. Columnas esperadas: Nombres, apellidos, identificacion, eps, posicion, Numero de camiseta, fecha de nacimiento. NOTA: Las identificaciones deben ser únicas."
     *                 ),
     *                 @OA\Property(
     *                     property="team_id",
     *                     type="string",
     *                     format="uuid",
     *                     example="550e8400-e29b-41d4-a716-446655440000",
     *                     description="ID del equipo al que se asignarán los jugadores"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Jugadores importados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Importación completada"),
     *             @OA\Property(property="total_processed", type="integer", example=10, description="Total de líneas procesadas"),
     *             @OA\Property(property="successful", type="integer", example=8, description="Jugadores creados exitosamente"),
     *             @OA\Property(property="errors", type="integer", example=1, description="Número de errores encontrados"),
     *             @OA\Property(property="skipped", type="integer", example=1, description="Líneas saltadas"),
     *             @OA\Property(
     *                 property="players",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="first_name", type="string", nullable=true),
     *                     @OA\Property(property="last_name", type="string", nullable=true),
     *                     @OA\Property(property="identification_number", type="string", nullable=true),
     *                     @OA\Property(property="eps", type="string", nullable=true),
     *                     @OA\Property(property="full_name", type="string", description="Nombre completo del jugador"),
     *                     @OA\Property(property="position", type="string", nullable=true),
     *                     @OA\Property(property="jersey", type="integer", nullable=true),
     *                     @OA\Property(property="birthDay", type="string", format="date", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="datetime"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="error_details",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 description="Detalles de errores encontrados, incluyendo identificaciones duplicadas"
     *             ),
     *             @OA\Property(
     *                 property="skipped_details",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 description="Detalles de líneas saltadas (solo si hay líneas saltadas)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos (solo admin)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación - Archivo inválido, equipo no existe, o identificaciones duplicadas",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Faltan columnas requeridas en el archivo CSV"),
     *                     @OA\Property(property="missing_columns", type="array", @OA\Items(type="string"), example={"eps", "posicion"}),
     *                     @OA\Property(property="found_columns", type="array", @OA\Items(type="string"), example={"Nombres", "apellidos", "identificacion"}),
     *                     @OA\Property(property="expected_columns", type="array", @OA\Items(type="string"), example={"Nombres", "apellidos", "identificacion", "eps", "posicion", "Numero de camiseta", "fecha de nacimiento"})
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="El archivo está vacío o no contiene datos válidos"),
     *                     @OA\Property(property="error", type="string", example="EMPTY_FILE")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="message", type="string", example="Importación completada"),
     *                     @OA\Property(property="error_details", type="array", @OA\Items(type="string"), example={"Línea 3: la identificación '12345678' ya existe en la base de datos (Jugador: Juan Pérez)", "Línea 5: la identificación '87654321' está duplicada en el archivo CSV"})
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:2048',
            'team_id' => 'required|exists:teams,id'
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getRealPath());
        
        // Detectar y manejar diferentes tipos de salto de línea
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);
        
        // Filtrar líneas vacías
        $lines = array_filter($lines, function($line) {
            return !empty(trim($line));
        });
        
        if (empty($lines)) {
            return response()->json([
                'message' => 'El archivo está vacío o no contiene datos válidos',
                'error' => 'EMPTY_FILE'
            ], 422);
        }
        
        $players = [];
        $errors = [];
        $skipped = [];
        $processedIdentifications = []; // Para rastrear identificaciones en el archivo actual
        
        // Obtener header
        $headerLine = array_shift($lines);
        $header = str_getcsv($headerLine);
        
        // Limpiar headers (remover espacios y caracteres especiales)
        $header = array_map('trim', $header);
        
        // Mapeo de columnas del CSV a campos del modelo
        $columnMapping = [
            'Nombres' => 'first_name',
            'apellidos' => 'last_name', 
            'identificacion' => 'identification_number',
            'eps' => 'eps',
            'posicion' => 'position',
            'Numero de camiseta' => 'jersey',
            'fecha de nacimiento' => 'birthDay'
        ];
        
        // Verificar que todas las columnas requeridas estén presentes
        $missingColumns = [];
        foreach ($columnMapping as $csvColumn => $modelField) {
            if (!in_array($csvColumn, $header)) {
                $missingColumns[] = $csvColumn;
            }
        }
        
        if (!empty($missingColumns)) {
            return response()->json([
                'message' => 'Faltan columnas requeridas en el archivo CSV',
                'missing_columns' => $missingColumns,
                'found_columns' => $header,
                'expected_columns' => array_keys($columnMapping)
            ], 422);
        }
        
        foreach ($lines as $lineNumber => $line) {
            $lineNumber += 2; // +2 porque empezamos desde línea 2 (después del header)
            
            if (empty(trim($line))) {
                $skipped[] = "Línea {$lineNumber}: línea vacía";
                continue;
            }
            
            try {
                $data = str_getcsv($line);
                
                // Verificar que el número de columnas coincida
                if (count($data) !== count($header)) {
                    $errors[] = "Línea {$lineNumber}: número de columnas incorrecto. Esperado: " . count($header) . ", encontrado: " . count($data);
                    continue;
                }
                
                $playerData = array_combine($header, $data);
                
                if ($playerData === false) {
                    $errors[] = "Línea {$lineNumber}: error al combinar datos con headers";
                    continue;
                }
                
                // Mapear datos usando el mapeo de columnas
                $mappedData = [];
                foreach ($columnMapping as $csvColumn => $modelField) {
                    $value = isset($playerData[$csvColumn]) ? trim($playerData[$csvColumn]) : null;
                    $mappedData[$modelField] = empty($value) ? null : $value;
                }
                
                // Validar que al menos tenga nombre o apellido
                if (empty($mappedData['first_name']) && empty($mappedData['last_name'])) {
                    $skipped[] = "Línea {$lineNumber}: sin nombre ni apellido";
                    continue;
                }
                
                // VALIDACIÓN DE IDENTIFICACIÓN ÚNICA
                if (!empty($mappedData['identification_number'])) {
                    $identificationNumber = $mappedData['identification_number'];
                    
                    // Verificar si ya existe en la base de datos
                    $existingPlayer = Player::where('identification_number', $identificationNumber)->first();
                    if ($existingPlayer) {
                        $errors[] = "Línea {$lineNumber}: la identificación '{$identificationNumber}' ya existe en la base de datos (Jugador: {$existingPlayer->full_name})";
                        continue;
                    }
                    
                    // Verificar si ya se procesó en este archivo
                    if (in_array($identificationNumber, $processedIdentifications)) {
                        $errors[] = "Línea {$lineNumber}: la identificación '{$identificationNumber}' está duplicada en el archivo CSV";
                        continue;
                    }
                    
                    // Agregar a la lista de identificaciones procesadas
                    $processedIdentifications[] = $identificationNumber;
                }
                
                // Limpiar y formatear datos
                if (!empty($mappedData['jersey'])) {
                    if (is_numeric($mappedData['jersey'])) {
                        $mappedData['jersey'] = (int) $mappedData['jersey'];
                    } else {
                        $errors[] = "Línea {$lineNumber}: número de camiseta inválido: {$mappedData['jersey']}";
                        continue;
                    }
                }
                
                if (!empty($mappedData['birthDay'])) {
                    try {
                        $date = date('Y-m-d', strtotime($mappedData['birthDay']));
                        if ($date === '1970-01-01') { // strtotime falló
                            $mappedData['birthDay'] = null;
                            $errors[] = "Línea {$lineNumber}: fecha de nacimiento inválida: {$mappedData['birthDay']}";
                        } else {
                            $mappedData['birthDay'] = $date;
                        }
                    } catch (Exception $e) {
                        $mappedData['birthDay'] = null;
                        $errors[] = "Línea {$lineNumber}: error al procesar fecha: {$e->getMessage()}";
                    }
                }
                
                $player = Player::create([
                    'id' => Str::uuid(),
                    'first_name' => $mappedData['first_name'],
                    'last_name' => $mappedData['last_name'],
                    'identification_number' => $mappedData['identification_number'],
                    'eps' => $mappedData['eps'],
                    'position' => $mappedData['position'],
                    'jersey' => $mappedData['jersey'],
                    'birthDay' => $mappedData['birthDay'],
                ]);
                
                // Associate with team
                $player->teams()->attach($request->team_id);
                $players[] = $player;
                
            } catch (Exception $e) {
                $errors[] = "Línea {$lineNumber}: error inesperado: {$e->getMessage()}";
                continue;
            }
        }
        
        $response = [
            'message' => 'Importación completada',
            'total_processed' => count($lines),
            'successful' => count($players),
            'errors' => count($errors),
            'skipped' => count($skipped),
            'players' => $players
        ];
        
        if (!empty($errors)) {
            $response['error_details'] = $errors;
        }
        
        if (!empty($skipped)) {
            $response['skipped_details'] = $skipped;
        }
        
        return response()->json($response, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/players/template",
     *     tags={"Players"},
     *     summary="Descargar plantilla CSV para importación",
     *     description="Descarga un archivo CSV con el formato correcto para importar jugadores (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Tipo de plantilla: 'empty' para plantilla vacía, 'example' para plantilla con ejemplos",
     *         required=false,
     *         @OA\Schema(type="string", enum={"empty", "example"}, default="empty")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Archivo CSV descargado",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos (solo admin)",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function downloadTemplate(Request $request)
    {
        $type = $request->get('type', 'empty');
        
        // Generar contenido CSV dinámicamente para evitar problemas de archivos
        $headers = [
            'Nombres',
            'apellidos', 
            'identificacion',
            'eps',
            'posicion',
            'Numero de camiseta',
            'fecha de nacimiento'
        ];
        
        $csvContent = implode(',', $headers) . "\n";
        
        if ($type === 'example') {
            // Agregar datos de ejemplo
            $examples = [
                ['Juan Carlos', 'García López', '12345678', 'Sura', 'Delantero', '10', '1995-03-15'],
                ['María Elena', 'Rodríguez Silva', '87654321', 'Compensar', 'Mediocampista', '8', '1997-07-22'],
                ['Pedro Antonio', 'Martínez Ruiz', '11223344', 'Nueva EPS', 'Defensa', '4', '1993-11-08']
            ];
            
            foreach ($examples as $example) {
                $csvContent .= implode(',', $example) . "\n";
            }
            
            $filename = 'plantilla-jugadores-con-ejemplos.csv';
        } else {
            $filename = 'plantilla-jugadores-vacia.csv';
        }
        
        return response($csvContent)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}