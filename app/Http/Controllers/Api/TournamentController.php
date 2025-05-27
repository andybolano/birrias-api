<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TournamentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tournaments",
     *     tags={"Tournaments"},
     *     summary="Listar mis torneos",
     *     description="Obtiene la lista de torneos del usuario autenticado (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado del torneo",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de torneos",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
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
     *         description="Sin permisos",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tournament::with(['owner', 'teams', 'matches'])
            ->where('owner', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tournaments = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($tournaments);
    }

    /**
     * @OA\Post(
     *     path="/api/tournaments",
     *     tags={"Tournaments"},
     *     summary="Crear nuevo torneo",
     *     description="Crea un nuevo torneo (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","format"},
     *             @OA\Property(property="name", type="string", example="Liga Birrias 2024"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-03-01"),
     *             @OA\Property(property="inscription_fee_money", type="number", format="decimal", example="150.00"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="format", type="string", enum={"league", "league_playoffs", "groups_knockout"}, example="league"),
     *             @OA\Property(property="groups", type="integer", example="4"),
     *             @OA\Property(property="teams_per_group", type="integer", example="4"),
     *             @OA\Property(property="playoff_size", type="integer", example="8"),
     *             @OA\Property(property="rounds", type="integer", example="2"),
     *             @OA\Property(property="home_away", type="boolean", example="true")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Torneo creado exitosamente",
     *         @OA\JsonContent(type="object")
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
            'start_date' => 'nullable|date',
            'inscription_fee_money' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'format' => 'required|in:league,league_playoffs,groups_knockout',
            'groups' => 'nullable|integer|min:1',
            'teams_per_group' => 'nullable|integer|min:2',
            'playoff_size' => 'nullable|integer|min:2',
            'rounds' => 'nullable|integer|min:1',
            'home_away' => 'boolean'
        ]);

        $tournament = Tournament::create([
            'id' => Str::uuid(),
            'name' => $request->name,
            'start_date' => $request->start_date,
            'inscription_fee_money' => $request->inscription_fee_money,
            'currency' => $request->currency ?? 'USD',
            'owner' => $request->user()->id,
            'status' => 'inactive',
            'format' => $request->format,
            'groups' => $request->groups,
            'teams_per_group' => $request->teams_per_group,
            'playoff_size' => $request->playoff_size,
            'rounds' => $request->rounds,
            'home_away' => $request->boolean('home_away', false)
        ]);

        return response()->json($tournament->load(['owner']), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/tournaments/{id}",
     *     tags={"Tournaments"},
     *     summary="Ver torneo específico",
     *     description="Obtiene la información detallada de un torneo específico",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del torneo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del torneo",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="inscription_fee_money", type="number"),
     *             @OA\Property(property="currency", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="format", type="string"),
     *             @OA\Property(property="owner", type="object"),
     *             @OA\Property(property="teams", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="matches", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="standings", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="groups", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos para ver este torneo",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Torneo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Tournament $tournament): JsonResponse
    {
        $this->authorize('view', $tournament);
        
        return response()->json(
            $tournament->load(['owner', 'teams', 'matches', 'standings', 'groups'])
        );
    }

    /**
     * @OA\Put(
     *     path="/api/tournaments/{id}",
     *     tags={"Tournaments"},
     *     summary="Actualizar torneo",
     *     description="Actualiza la información de un torneo existente (solo admin propietario)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del torneo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Liga Birrias 2024 - Actualizada"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-03-15"),
     *             @OA\Property(property="inscription_fee_money", type="number", format="decimal", example="200.00"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active"),
     *             @OA\Property(property="format", type="string", enum={"league", "league_playoffs", "groups_knockout"}, example="league"),
     *             @OA\Property(property="groups", type="integer", example="4"),
     *             @OA\Property(property="teams_per_group", type="integer", example="4"),
     *             @OA\Property(property="playoff_size", type="integer", example="8"),
     *             @OA\Property(property="rounds", type="integer", example="2"),
     *             @OA\Property(property="home_away", type="boolean", example="true")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Torneo actualizado exitosamente",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos para actualizar este torneo",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Torneo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function update(Request $request, Tournament $tournament): JsonResponse
    {
        $this->authorize('update', $tournament);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'start_date' => 'nullable|date',
            'inscription_fee_money' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'status' => 'sometimes|in:active,inactive',
            'format' => 'sometimes|in:league,league_playoffs,groups_knockout',
            'groups' => 'nullable|integer|min:1',
            'teams_per_group' => 'nullable|integer|min:2',
            'playoff_size' => 'nullable|integer|min:2',
            'rounds' => 'nullable|integer|min:1',
            'home_away' => 'boolean'
        ]);

        $tournament->update($request->only([
            'name', 'start_date', 'inscription_fee_money', 'currency',
            'status', 'format', 'groups', 'teams_per_group', 
            'playoff_size', 'rounds', 'home_away'
        ]));

        return response()->json($tournament->load(['owner']));
    }

    /**
     * @OA\Delete(
     *     path="/api/tournaments/{id}",
     *     tags={"Tournaments"},
     *     summary="Eliminar torneo",
     *     description="Elimina un torneo existente (solo admin propietario)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del torneo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Torneo eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Tournament deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos para eliminar este torneo",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Torneo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(Tournament $tournament): JsonResponse
    {
        $this->authorize('delete', $tournament);
        
        $tournament->delete();
        
        return response()->json(['message' => 'Tournament deleted successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/tournaments/{id}/teams",
     *     tags={"Tournaments"},
     *     summary="Agregar equipo al torneo",
     *     description="Agrega un equipo existente al torneo (solo admin propietario)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del torneo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"team_id"},
     *             @OA\Property(property="team_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del equipo a agregar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Equipo agregado al torneo exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Team added to tournament successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos para modificar este torneo",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Torneo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación - Equipo no existe",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function addTeam(Request $request, Tournament $tournament): JsonResponse
    {
        $this->authorize('update', $tournament);
        
        $request->validate([
            'team_id' => 'required|exists:teams,id'
        ]);

        $tournament->teams()->syncWithoutDetaching([$request->team_id]);
        
        return response()->json(['message' => 'Team added to tournament successfully']);
    }

    /**
     * @OA\Delete(
     *     path="/api/tournaments/{id}/teams",
     *     tags={"Tournaments"},
     *     summary="Remover equipo del torneo",
     *     description="Remueve un equipo del torneo (solo admin propietario)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del torneo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"team_id"},
     *             @OA\Property(property="team_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del equipo a remover")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Equipo removido del torneo exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Team removed from tournament successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sin permisos para modificar este torneo",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Torneo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación - Equipo no existe",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function removeTeam(Request $request, Tournament $tournament): JsonResponse
    {
        $this->authorize('update', $tournament);
        
        $request->validate([
            'team_id' => 'required|exists:teams,id'
        ]);

        $tournament->teams()->detach($request->team_id);
        
        return response()->json(['message' => 'Team removed from tournament successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/tournaments/formats",
     *     tags={"Tournaments"},
     *     summary="Obtener formatos de torneo disponibles",
     *     description="Obtiene la lista de formatos de torneo disponibles con sus parámetros",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de formatos disponibles",
     *         @OA\JsonContent(
     *             @OA\Property(property="formats", type="array", @OA\Items(
     *                 @OA\Property(property="value", type="string", example="league"),
     *                 @OA\Property(property="label", type="string", example="Liga Simple"),
     *                 @OA\Property(property="description", type="string", example="Todos contra todos"),
     *                 @OA\Property(property="required_params", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="optional_params", type="array", @OA\Items(type="string"))
     *             ))
     *         )
     *     )
     * )
     */
    public function getFormats(): JsonResponse
    {
        $formats = [
            [
                'value' => 'league',
                'label' => 'Liga Simple',
                'description' => 'Todos los equipos juegan contra todos en una o más vueltas',
                'required_params' => ['rounds'],
                'optional_params' => ['home_away'],
                'ignored_params' => ['groups', 'teams_per_group', 'playoff_size']
            ],
            [
                'value' => 'league_playoffs',
                'label' => 'Liga + Playoffs',
                'description' => 'Fase de liga seguida de playoffs con los mejores equipos',
                'required_params' => ['rounds', 'playoff_size'],
                'optional_params' => ['home_away'],
                'ignored_params' => ['groups', 'teams_per_group']
            ],
            [
                'value' => 'groups_knockout',
                'label' => 'Grupos + Eliminatorias',
                'description' => 'Fase de grupos seguida de eliminatorias directas',
                'required_params' => ['groups', 'teams_per_group', 'playoff_size', 'rounds'],
                'optional_params' => ['home_away'],
                'ignored_params' => []
            ]
        ];

        return response()->json(['formats' => $formats]);
    }
}