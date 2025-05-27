<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/teams",
     *     tags={"Teams"},
     *     summary="Listar equipos",
     *     description="Obtiene la lista de equipos",
     *     @OA\Parameter(
     *         name="tournament_id",
     *         in="query",
     *         description="Filtrar por torneo",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de equipos",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Team::with(['players', 'tournaments']);

        if ($request->has('tournament_id')) {
            $query->whereHas('tournaments', function ($q) use ($request) {
                $q->where('tournament_id', $request->tournament_id);
            });
        }

        $teams = $query->orderBy('name')->paginate(15);

        return response()->json($teams);
    }

    /**
     * @OA\Post(
     *     path="/api/teams",
     *     tags={"Teams"},
     *     summary="Crear nuevo equipo",
     *     description="Crea un nuevo equipo (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string", example="Club Deportivo Birrias", description="Nombre del equipo"),
     *                 @OA\Property(property="shield", type="string", format="binary", description="Imagen del escudo del equipo (opcional)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Equipo creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="shield", type="string", nullable=true, description="URL completa del escudo del equipo"),
     *             @OA\Property(property="players", type="array", @OA\Items(type="object")),
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
     *         description="Error de validación",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'shield' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $shieldPath = null;
        if ($request->hasFile('shield')) {
            $shieldPath = $request->file('shield')->store('team-shields', 'public');
        }

        $team = Team::create([
            'id' => Str::uuid(),
            'name' => $request->name,
            'shield' => $shieldPath,
        ]);

        return response()->json($team->load(['players']), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/teams/{id}",
     *     tags={"Teams"},
     *     summary="Ver equipo específico",
     *     description="Obtiene la información detallada de un equipo específico",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del equipo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del equipo",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="shield", type="string", nullable=true, description="Ruta del archivo del escudo del equipo"),
     *             @OA\Property(property="players", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="tournaments", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="standings", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="created_at", type="string", format="datetime"),
     *             @OA\Property(property="updated_at", type="string", format="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Equipo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Team $team): JsonResponse
    {
        return response()->json(
            $team->load(['players', 'tournaments', 'standings'])
        );
    }

    /**
     * @OA\Put(
     *     path="/api/teams/{id}",
     *     tags={"Teams"},
     *     summary="Actualizar equipo",
     *     description="Actualiza la información de un equipo existente (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del equipo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string", example="Club Deportivo Birrias FC", description="Nuevo nombre del equipo"),
     *                 @OA\Property(property="shield", type="string", format="binary", description="Nueva imagen del escudo del equipo (opcional)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Equipo actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="shield", type="string", nullable=true, description="Ruta del archivo del escudo del equipo"),
     *             @OA\Property(property="players", type="array", @OA\Items(type="object")),
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
     *         description="Equipo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function update(Request $request, Team $team): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'shield' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $updateData = ['name' => $request->name];

        if ($request->hasFile('shield')) {
            // Eliminar la imagen anterior si existe
            if ($team->shield && Storage::disk('public')->exists($team->shield)) {
                Storage::disk('public')->delete($team->shield);
            }
            
            $updateData['shield'] = $request->file('shield')->store('team-shields', 'public');
        }

        $team->update($updateData);

        return response()->json($team->load(['players']));
    }

    /**
     * @OA\Delete(
     *     path="/api/teams/{id}",
     *     tags={"Teams"},
     *     summary="Eliminar equipo",
     *     description="Elimina un equipo existente (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del equipo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Equipo eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Team deleted successfully")
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
     *         description="Equipo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(Team $team): JsonResponse
    {
        // Eliminar la imagen del escudo si existe
        if ($team->shield && Storage::disk('public')->exists($team->shield)) {
            Storage::disk('public')->delete($team->shield);
        }
        
        $team->delete();
        
        return response()->json(['message' => 'Team deleted successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/teams/{id}/players",
     *     tags={"Teams"},
     *     summary="Agregar jugador al equipo",
     *     description="Agrega un jugador existente al equipo (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del equipo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"player_id"},
     *             @OA\Property(property="player_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del jugador a agregar")
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
     *         description="Equipo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación - Jugador no existe",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function addPlayer(Request $request, Team $team): JsonResponse
    {
        $request->validate([
            'player_id' => 'required|exists:players,id'
        ]);

        $team->players()->syncWithoutDetaching([$request->player_id]);
        
        return response()->json(['message' => 'Player added to team successfully']);
    }

    /**
     * @OA\Delete(
     *     path="/api/teams/{id}/players",
     *     tags={"Teams"},
     *     summary="Remover jugador del equipo",
     *     description="Remueve un jugador del equipo (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del equipo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"player_id"},
     *             @OA\Property(property="player_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del jugador a remover")
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
     *         description="Equipo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación - Jugador no existe",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function removePlayer(Request $request, Team $team): JsonResponse
    {
        $request->validate([
            'player_id' => 'required|exists:players,id'
        ]);

        $team->players()->detach($request->player_id);
        
        return response()->json(['message' => 'Player removed from team successfully']);
    }
}