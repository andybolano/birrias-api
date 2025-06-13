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
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Liga Birrias 2024"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-03-01"),
     *             @OA\Property(property="inscription_fee_money", type="number", format="decimal", example="150.00"),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="format", type="string", enum={"league", "league_playoffs", "groups_knockout", "custom"}, example="custom", description="Campo informativo, las fases se configuran dinámicamente"),
     *             @OA\Property(property="groups", type="integer", example="4", description="Campo informativo"),
     *             @OA\Property(property="teams_per_group", type="integer", example="4", description="Campo informativo"),
     *             @OA\Property(property="playoff_size", type="integer", example="8", description="Campo informativo"),
     *             @OA\Property(property="rounds", type="integer", example="2", description="Campo informativo"),
     *             @OA\Property(property="home_away", type="boolean", example="true", description="Campo informativo")
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
            'format' => 'nullable|in:league,league_playoffs,groups_knockout,custom',
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
            'format' => $request->format ?? 'custom',
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
     *             @OA\Property(property="format", type="string", enum={"league", "league_playoffs", "groups_knockout", "custom"}, example="custom", description="Campo informativo, las fases se configuran dinámicamente"),
     *             @OA\Property(property="groups", type="integer", example="4", description="Campo informativo"),
     *             @OA\Property(property="teams_per_group", type="integer", example="4", description="Campo informativo"),
     *             @OA\Property(property="playoff_size", type="integer", example="8", description="Campo informativo"),
     *             @OA\Property(property="rounds", type="integer", example="2", description="Campo informativo"),
     *             @OA\Property(property="home_away", type="boolean", example="true", description="Campo informativo")
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
            'format' => 'sometimes|in:league,league_playoffs,groups_knockout,custom',
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
     * @OA\Post(
     *     path="/api/tournaments/{id}/teams/bulk",
     *     tags={"Tournaments"},
     *     summary="Agregar múltiples equipos al torneo",
     *     description="Agrega varios equipos existentes al torneo de una sola vez (solo admin propietario)",
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
     *             required={"team_ids"},
     *             @OA\Property(
     *                 property="team_ids", 
     *                 type="array", 
     *                 @OA\Items(type="string", format="uuid"), 
     *                 example={"550e8400-e29b-41d4-a716-446655440000", "550e8400-e29b-41d4-a716-446655440001"},
     *                 description="Array de IDs de equipos a agregar"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Equipos agregados al torneo exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="5 teams added to tournament successfully"),
     *             @OA\Property(property="added_count", type="integer", example=5),
     *             @OA\Property(property="skipped_count", type="integer", example=2, description="Equipos que ya estaban en el torneo"),
     *             @OA\Property(property="total_teams", type="integer", example=7, description="Total de equipos en el torneo después de la operación")
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
     *         description="Error de validación",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function addMultipleTeams(Request $request, Tournament $tournament): JsonResponse
    {
        $this->authorize('update', $tournament);
        
        $request->validate([
            'team_ids' => 'required|array|min:1',
            'team_ids.*' => 'required|exists:teams,id'
        ]);

        // Obtener equipos que ya están en el torneo
        $existingTeamIds = $tournament->teams()->pluck('teams.id')->toArray();
        
        // Filtrar equipos nuevos (que no están ya en el torneo)
        $newTeamIds = array_diff($request->team_ids, $existingTeamIds);
        
        // Agregar solo los equipos nuevos
        if (!empty($newTeamIds)) {
            $tournament->teams()->syncWithoutDetaching($newTeamIds);
        }
        
        // Contar resultados
        $addedCount = count($newTeamIds);
        $skippedCount = count($request->team_ids) - $addedCount;
        $totalTeams = $tournament->teams()->count();
        
        return response()->json([
            'message' => $addedCount === 1 
                ? '1 team added to tournament successfully' 
                : "{$addedCount} teams added to tournament successfully",
            'added_count' => $addedCount,
            'skipped_count' => $skippedCount,
            'total_teams' => $totalTeams
        ]);
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
     * @OA\Post(
     *     path="/api/tournaments/{id}/generate-fixtures",
     *     tags={"Tournaments"},
     *     summary="Generar fixture del torneo",
     *     description="Genera automáticamente los partidos del torneo organizados por fechas/jornadas. Usa algoritmo Round Robin para asegurar que ningún equipo juegue más de una vez por fecha (solo admin propietario)",
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
     *         description="Fixture generado exitosamente organizando partidos por fechas",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Fixture generated successfully"),
     *             @OA\Property(property="matches_created", type="integer", example=15),
     *             @OA\Property(property="total_rounds", type="integer", example=5),
     *             @OA\Property(property="format", type="string", example="league"),
     *             @OA\Property(property="dates_organization", type="string", example="Partidos organizados por fechas usando algoritmo Round Robin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la generación del fixture",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not enough teams to generate fixtures")
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
     *     )
     * )
     */
    public function generateFixtures(Tournament $tournament): JsonResponse
    {
        $this->authorize('update', $tournament);
        
        // Obtener equipos del torneo
        $teams = $tournament->teams()->get();
        $teamCount = $teams->count();
        
        if ($teamCount < 2) {
            return response()->json([
                'message' => 'Not enough teams to generate fixtures. At least 2 teams are required.'
            ], 400);
        }
        
        // Limpiar partidos existentes del torneo
        $tournament->matches()->delete();
        
        $matchesCreated = 0;
        $totalRounds = 0;
        
        switch ($tournament->format) {
            case 'league':
                $result = $this->generateLeagueFixtures($tournament, $teams);
                break;
                
            case 'league_playoffs':
                $result = $this->generateLeaguePlayoffsFixtures($tournament, $teams);
                break;
                
            case 'groups_knockout':
                $result = $this->generateGroupsKnockoutFixtures($tournament, $teams);
                break;
                
            case 'custom':
                // Para formato custom, usamos la generación de liga simple por defecto
                // Los usuarios pueden configurar fases dinámicas después si lo necesitan
                $result = $this->generateLeagueFixtures($tournament, $teams);
                break;
                
            default:
                return response()->json([
                    'message' => 'Unsupported tournament format: ' . $tournament->format
                ], 400);
        }
        
        return response()->json([
            'message' => 'Fixture generated successfully',
            'matches_created' => $result['matches_created'],
            'total_rounds' => $result['total_rounds'],
            'format' => $tournament->format,
            'organization' => 'Partidos organizados por fechas/jornadas para facilitar la programación de calendarios',
            'note' => 'Cada fecha/ronda contiene partidos donde ningún equipo juega más de una vez'
        ]);
    }
    
    /**
     * Genera fixture para formato Liga Simple
     */
    private function generateLeagueFixtures(Tournament $tournament, $teams)
    {
        $teamIds = $teams->pluck('id')->toArray();
        $teamCount = count($teamIds);
        $rounds = $tournament->rounds ?? 1;
        $homeAway = $tournament->home_away ?? false;
        
        $matchesCreated = 0;
        $currentRound = 1;
        
        // Generar fixture usando algoritmo Round Robin para organizar por fechas
        for ($round = 1; $round <= $rounds; $round++) {
            $fixtures = $this->generateRoundRobinFixtures($teamIds, $homeAway);
            
            foreach ($fixtures as $dateIndex => $dateMatches) {
                foreach ($dateMatches as $match) {
                    \App\Models\FootballMatch::create([
                        'tournament_id' => $tournament->id,
                        'round' => $currentRound,
                        'home_team' => $match['home'],
                        'away_team' => $match['away'],
                        'status' => 'scheduled'
                    ]);
                    $matchesCreated++;
                }
                $currentRound++;
            }
        }
        
        return [
            'matches_created' => $matchesCreated,
            'total_rounds' => $currentRound - 1
        ];
    }
    
    /**
     * Genera fixtures usando algoritmo Round Robin organizando por fechas
     * Cada fecha tendrá partidos donde ningún equipo juega más de una vez
     */
    private function generateRoundRobinFixtures($teamIds, $homeAway = false)
    {
        $teamCount = count($teamIds);
        $fixtures = [];
        
        // Si el número de equipos es impar, agregar un "bye" (descanso)
        if ($teamCount % 2 !== 0) {
            $teamIds[] = null; // null representa el "bye"
            $teamCount++;
        }
        
        $totalRounds = $teamCount - 1;
        $matchesPerRound = $teamCount / 2;
        
        // Algoritmo Round Robin clásico
        for ($round = 0; $round < $totalRounds; $round++) {
            $fixtures[$round] = [];
            
            for ($match = 0; $match < $matchesPerRound; $match++) {
                $home = $teamIds[$match];
                $away = $teamIds[$teamCount - 1 - $match];
                
                // Saltar si uno de los equipos es "bye" (null)
                if ($home === null || $away === null) {
                    continue;
                }
                
                $fixtures[$round][] = [
                    'home' => $home,
                    'away' => $away
                ];
                
                // Si es ida y vuelta, agregar el partido de vuelta en otra fecha
                if ($homeAway) {
                    // Buscar la fecha correspondiente para el partido de vuelta
                    $returnRound = $round + $totalRounds;
                    if (!isset($fixtures[$returnRound])) {
                        $fixtures[$returnRound] = [];
                    }
                    
                    $fixtures[$returnRound][] = [
                        'home' => $away,
                        'away' => $home
                    ];
                }
            }
            
            // Rotar equipos para la siguiente fecha (excepto el primero)
            $first = array_shift($teamIds);
            $last = array_pop($teamIds);
            array_unshift($teamIds, $first, $last);
        }
        
        return $fixtures;
    }
    
    /**
     * Genera fixture para formato Liga + Playoffs
     */
    private function generateLeaguePlayoffsFixtures(Tournament $tournament, $teams)
    {
        // Primero generar la fase de liga
        $leagueResult = $this->generateLeagueFixtures($tournament, $teams);
        
        // Luego generar los playoffs (estructura básica)
        $playoffSize = $tournament->playoff_size ?? 8;
        $playoffRounds = ceil(log($playoffSize, 2)); // Número de rondas eliminatorias
        
        $matchesCreated = $leagueResult['matches_created'];
        $currentRound = $leagueResult['total_rounds'] + 1;
        
        // Generar estructura de playoffs (sin equipos específicos aún)
        $teamsInRound = $playoffSize;
        for ($round = 1; $round <= $playoffRounds; $round++) {
            $matchesInRound = $teamsInRound / 2;
            
            for ($match = 1; $match <= $matchesInRound; $match++) {
                \App\Models\FootballMatch::create([
                    'tournament_id' => $tournament->id,
                    'round' => $currentRound,
                    'home_team' => $teams->first()->id, // Placeholder
                    'away_team' => $teams->skip(1)->first()->id, // Placeholder
                    'status' => 'scheduled'
                ]);
                $matchesCreated++;
            }
            
            $teamsInRound = $matchesInRound;
            $currentRound++;
        }
        
        return [
            'matches_created' => $matchesCreated,
            'total_rounds' => $currentRound - 1
        ];
    }
    
    /**
     * Genera fixture para formato Grupos + Eliminatorias
     */
    private function generateGroupsKnockoutFixtures(Tournament $tournament, $teams)
    {
        $teamIds = $teams->pluck('id')->toArray();
        $teamCount = count($teamIds);
        $groups = $tournament->groups ?? 4;
        $teamsPerGroup = $tournament->teams_per_group ?? 4;
        
        if ($teamCount < $groups * 2) {
            throw new \Exception('Not enough teams for the specified group configuration');
        }
        
        $matchesCreated = 0;
        $currentRound = 1;
        
        // Dividir equipos en grupos
        $teamGroups = array_chunk($teamIds, $teamsPerGroup);
        
        // Generar partidos para cada grupo
        foreach ($teamGroups as $groupIndex => $groupTeams) {
            $groupTeamCount = count($groupTeams);
            
            // Todos contra todos en el grupo
            for ($i = 0; $i < $groupTeamCount; $i++) {
                for ($j = $i + 1; $j < $groupTeamCount; $j++) {
                    \App\Models\FootballMatch::create([
                        'tournament_id' => $tournament->id,
                        'round' => $currentRound,
                        'home_team' => $groupTeams[$i],
                        'away_team' => $groupTeams[$j],
                        'status' => 'scheduled'
                    ]);
                    $matchesCreated++;
                }
            }
        }
        
        // Generar fase eliminatoria (estructura básica)
        $playoffSize = $tournament->playoff_size ?? 8;
        $playoffRounds = ceil(log($playoffSize, 2));
        $currentRound++;
        
        $teamsInRound = $playoffSize;
        for ($round = 1; $round <= $playoffRounds; $round++) {
            $matchesInRound = $teamsInRound / 2;
            
            for ($match = 1; $match <= $matchesInRound; $match++) {
                \App\Models\FootballMatch::create([
                    'tournament_id' => $tournament->id,
                    'round' => $currentRound,
                    'home_team' => $teamIds[0], // Placeholder
                    'away_team' => $teamIds[1], // Placeholder
                    'status' => 'scheduled'
                ]);
                $matchesCreated++;
            }
            
            $teamsInRound = $matchesInRound;
            $currentRound++;
        }
        
        return [
            'matches_created' => $matchesCreated,
            'total_rounds' => $currentRound - 1
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/tournaments/formats",
     *     tags={"Tournaments"},
     *     summary="Obtener formatos de torneo disponibles",
     *     description="Obtiene la lista de formatos de torneo disponibles. NOTA: Con el nuevo sistema de fases dinámicas, se recomienda usar 'custom' y configurar las fases individualmente.",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de formatos disponibles",
     *         @OA\JsonContent(
     *             @OA\Property(property="formats", type="array", @OA\Items(
     *                 @OA\Property(property="value", type="string", example="custom"),
     *                 @OA\Property(property="label", type="string", example="Fases Dinámicas"),
     *                 @OA\Property(property="description", type="string", example="Configuración personalizada usando fases dinámicas"),
     *                 @OA\Property(property="required_params", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="optional_params", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="deprecated", type="boolean", example=false)
     *             ))
     *         )
     *     )
     * )
     */
    public function getFormats(): JsonResponse
    {
        $formats = [
            [
                'value' => 'custom',
                'label' => 'Fases Dinámicas (Recomendado)',
                'description' => 'Configuración personalizada usando el sistema de fases dinámicas. Permite crear cualquier tipo de torneo.',
                'required_params' => [],
                'optional_params' => [],
                'deprecated' => false,
                'note' => 'Usa /api/tournaments/{id}/phases para configurar las fases después de crear el torneo'
            ],
            [
                'value' => 'league',
                'label' => 'Liga Simple (Legado)',
                'description' => 'Todos los equipos juegan contra todos en una o más vueltas',
                'required_params' => ['rounds'],
                'optional_params' => ['home_away'],
                'ignored_params' => ['groups', 'teams_per_group', 'playoff_size'],
                'deprecated' => true,
                'note' => 'Formato legado. Se recomienda usar fases dinámicas.'
            ],
            [
                'value' => 'league_playoffs',
                'label' => 'Liga + Playoffs (Legado)',
                'description' => 'Fase de liga seguida de playoffs con los mejores equipos',
                'required_params' => ['rounds', 'playoff_size'],
                'optional_params' => ['home_away'],
                'ignored_params' => ['groups', 'teams_per_group'],
                'deprecated' => true,
                'note' => 'Formato legado. Se recomienda usar fases dinámicas.'
            ],
            [
                'value' => 'groups_knockout',
                'label' => 'Grupos + Eliminatorias (Legado)',
                'description' => 'Fase de grupos seguida de eliminatorias directas',
                'required_params' => ['groups', 'teams_per_group', 'playoff_size', 'rounds'],
                'optional_params' => ['home_away'],
                'ignored_params' => [],
                'deprecated' => true,
                'note' => 'Formato legado. Se recomienda usar fases dinámicas.'
            ]
        ];

        return response()->json([
            'formats' => $formats,
            'recommendation' => 'Se recomienda usar el formato "custom" y configurar las fases dinámicamente usando los endpoints de /api/tournaments/{id}/phases',
            'migration_guide' => 'Los formatos legados siguen funcionando pero las fases dinámicas ofrecen mayor flexibilidad'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/tournaments/{id}/fixtures",
     *     tags={"Tournaments"},
     *     summary="Obtener fixture del torneo",
     *     description="Obtiene todos los partidos del torneo organizados por fechas/jornadas (rounds). Cada fecha contiene partidos donde ningún equipo juega más de una vez.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del torneo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="round",
     *         in="query",
     *         description="Filtrar por fecha/jornada específica",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fixture del torneo",
     *         @OA\JsonContent(
     *             @OA\Property(property="tournament_id", type="string", format="uuid"),
     *             @OA\Property(property="tournament_name", type="string"),
     *             @OA\Property(property="format", type="string"),
     *             @OA\Property(property="total_matches", type="integer"),
     *             @OA\Property(property="total_rounds", type="integer"),
     *             @OA\Property(property="rounds", type="array", @OA\Items(
     *                 @OA\Property(property="round", type="integer"),
     *                 @OA\Property(property="matches_count", type="integer"),
     *                 @OA\Property(property="matches", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="round", type="integer"),
     *                     @OA\Property(property="home_team", type="object"),
     *                     @OA\Property(property="away_team", type="object"),
     *                     @OA\Property(property="match_date", type="string", format="datetime", nullable=true),
     *                     @OA\Property(property="venue", type="string", nullable=true),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="home_score", type="integer"),
     *                     @OA\Property(property="away_score", type="integer")
     *                 ))
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Torneo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getFixtures(Request $request, Tournament $tournament): JsonResponse
    {
        $query = $tournament->matches()
            ->with(['homeTeam:id,name,shield', 'awayTeam:id,name,shield', 'phase:id,name,type,phase_number'])
            ->orderBy('round')
            ->orderBy('created_at');
        
        // Filtrar por ronda si se especifica
        if ($request->has('round')) {
            $query->where('round', $request->round);
        }

        // Filtrar por fase si se especifica
        if ($request->has('phase_id')) {
            $query->where('phase_id', $request->phase_id);
        }
        
        $matches = $query->get();
        
        // Agrupar partidos por fase y luego por ronda
        $phases = $matches->groupBy('phase_id')->map(function ($phaseMatches, $phaseId) {
            $phase = $phaseMatches->first()->phase;
            
            $rounds = $phaseMatches->groupBy('round')->map(function ($roundMatches, $roundNumber) {
                return [
                    'round' => $roundNumber,
                    'matches_count' => $roundMatches->count(),
                    'matches' => $roundMatches->map(function ($match) {
                        return [
                            'id' => $match->id,
                            'round' => $match->round,
                            'group_number' => $match->group_number,
                            'match_type' => $match->match_type,
                            'home_team' => [
                                'id' => $match->homeTeam->id,
                                'name' => $match->homeTeam->name,
                                'shield' => $match->homeTeam->shield
                            ],
                            'away_team' => [
                                'id' => $match->awayTeam->id,
                                'name' => $match->awayTeam->name,
                                'shield' => $match->awayTeam->shield
                            ],
                            'match_date' => $match->match_date,
                            'venue' => $match->venue,
                            'stream_url' => $match->stream_url,
                            'status' => $match->status,
                            'home_score' => $match->home_score,
                            'away_score' => $match->away_score,
                            'created_at' => $match->created_at,
                            'updated_at' => $match->updated_at
                        ];
                    })->values()
                ];
            })->values();

            return [
                'phase_id' => $phaseId,
                'phase_name' => $phase ? $phase->name : 'Fixture Legado',
                'phase_type' => $phase ? $phase->type : 'legacy',
                'phase_number' => $phase ? $phase->phase_number : 1,
                'total_matches' => $phaseMatches->count(),
                'total_rounds' => $phaseMatches->max('round') ?? 0,
                'rounds' => $rounds
            ];
        })->values();
        
        return response()->json([
            'tournament_id' => $tournament->id,
            'tournament_name' => $tournament->name,
            'format' => $tournament->format,
            'total_matches' => $matches->count(),
            'total_phases' => $phases->count(),
            'phases' => $phases
        ]);
    }
}