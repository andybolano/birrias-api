<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Standing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StandingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/standings",
     *     tags={"Standings"},
     *     summary="Listar tablas de posiciones",
     *     description="Obtiene las tablas de posiciones con filtros opcionales",
     *     @OA\Parameter(
     *         name="tournament_id",
     *         in="query",
     *         description="Filtrar por torneo",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="group_id",
     *         in="query",
     *         description="Filtrar por grupo",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de posiciones ordenada por puntos",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="tournament_id", type="string", format="uuid"),
     *                 @OA\Property(property="team_id", type="string", format="uuid"),
     *                 @OA\Property(property="group_id", type="string", format="uuid", nullable=true),
     *                 @OA\Property(property="matches_played", type="integer"),
     *                 @OA\Property(property="wins", type="integer"),
     *                 @OA\Property(property="draws", type="integer"),
     *                 @OA\Property(property="losses", type="integer"),
     *                 @OA\Property(property="goals_for", type="integer"),
     *                 @OA\Property(property="goals_against", type="integer"),
     *                 @OA\Property(property="goal_difference", type="integer"),
     *                 @OA\Property(property="points", type="integer"),
     *                 @OA\Property(property="tournament", type="object"),
     *                 @OA\Property(property="team", type="object"),
     *                 @OA\Property(property="group", type="object", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Standing::with(['tournament', 'team', 'group']);

        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        if ($request->has('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        $standings = $query->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        return response()->json($standings);
    }

    /**
     * @OA\Get(
     *     path="/api/standings/{id}",
     *     tags={"Standings"},
     *     summary="Ver posición específica",
     *     description="Obtiene la información detallada de una posición específica en la tabla",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la posición",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información de la posición",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="tournament_id", type="string", format="uuid"),
     *             @OA\Property(property="team_id", type="string", format="uuid"),
     *             @OA\Property(property="group_id", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="matches_played", type="integer"),
     *             @OA\Property(property="wins", type="integer"),
     *             @OA\Property(property="draws", type="integer"),
     *             @OA\Property(property="losses", type="integer"),
     *             @OA\Property(property="goals_for", type="integer"),
     *             @OA\Property(property="goals_against", type="integer"),
     *             @OA\Property(property="goal_difference", type="integer"),
     *             @OA\Property(property="points", type="integer"),
     *             @OA\Property(property="tournament", type="object"),
     *             @OA\Property(property="team", type="object"),
     *             @OA\Property(property="group", type="object", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="datetime"),
     *             @OA\Property(property="updated_at", type="string", format="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Posición no encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(Standing $standing): JsonResponse
    {
        return response()->json(
            $standing->load(['tournament', 'team', 'group'])
        );
    }

    /**
     * @OA\Get(
     *     path="/api/standings/tournament/{tournamentId}",
     *     tags={"Standings"},
     *     summary="Tabla de posiciones por torneo",
     *     description="Obtiene la tabla de posiciones de un torneo específico",
     *     @OA\Parameter(
     *         name="tournamentId",
     *         in="path",
     *         description="ID del torneo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tabla de posiciones",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     )
     * )
     */
    public function byTournament(string $tournamentId): JsonResponse
    {
        $standings = Standing::with(['team', 'group'])
            ->where('tournament_id', $tournamentId)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        return response()->json($standings);
    }

    /**
     * @OA\Get(
     *     path="/api/standings/group/{groupId}",
     *     tags={"Standings"},
     *     summary="Tabla de posiciones por grupo",
     *     description="Obtiene la tabla de posiciones de un grupo específico",
     *     @OA\Parameter(
     *         name="groupId",
     *         in="path",
     *         description="ID del grupo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tabla de posiciones del grupo",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="tournament_id", type="string", format="uuid"),
     *                 @OA\Property(property="team_id", type="string", format="uuid"),
     *                 @OA\Property(property="group_id", type="string", format="uuid"),
     *                 @OA\Property(property="matches_played", type="integer"),
     *                 @OA\Property(property="wins", type="integer"),
     *                 @OA\Property(property="draws", type="integer"),
     *                 @OA\Property(property="losses", type="integer"),
     *                 @OA\Property(property="goals_for", type="integer"),
     *                 @OA\Property(property="goals_against", type="integer"),
     *                 @OA\Property(property="goal_difference", type="integer"),
     *                 @OA\Property(property="points", type="integer"),
     *                 @OA\Property(property="team", type="object"),
     *                 @OA\Property(property="tournament", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Grupo no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function byGroup(string $groupId): JsonResponse
    {
        $standings = Standing::with(['team', 'tournament'])
            ->where('group_id', $groupId)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        return response()->json($standings);
    }

    /**
     * @OA\Post(
     *     path="/api/standings/recalculate",
     *     tags={"Standings"},
     *     summary="Recalcular tabla de posiciones",
     *     description="Recalcula la tabla de posiciones de un torneo basado en los partidos finalizados (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tournament_id"},
     *             @OA\Property(property="tournament_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del torneo a recalcular")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tabla de posiciones recalculada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Standings recalculated successfully"),
     *             @OA\Property(
     *                 property="standings", 
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="tournament_id", type="string", format="uuid"),
     *                     @OA\Property(property="team_id", type="string", format="uuid"),
     *                     @OA\Property(property="matches_played", type="integer"),
     *                     @OA\Property(property="wins", type="integer"),
     *                     @OA\Property(property="draws", type="integer"),
     *                     @OA\Property(property="losses", type="integer"),
     *                     @OA\Property(property="goals_for", type="integer"),
     *                     @OA\Property(property="goals_against", type="integer"),
     *                     @OA\Property(property="goal_difference", type="integer"),
     *                     @OA\Property(property="points", type="integer"),
     *                     @OA\Property(property="team", type="object")
     *                 )
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
     *         description="Error de validación - Torneo no existe",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function recalculate(Request $request): JsonResponse
    {
        $request->validate([
            'tournament_id' => 'required|exists:tournaments,id'
        ]);

        // Reset all standings for this tournament
        Standing::where('tournament_id', $request->tournament_id)->delete();

        // Recalculate from finished matches
        $matches = \App\Models\FootballMatch::where('tournament_id', $request->tournament_id)
            ->where('status', 'finished')
            ->get();

        foreach ($matches as $match) {
            $this->updateStandingsFromMatch($match);
        }

        $standings = Standing::with(['team'])
            ->where('tournament_id', $request->tournament_id)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        return response()->json([
            'message' => 'Standings recalculated successfully',
            'standings' => $standings
        ]);
    }

    private function updateStandingsFromMatch($match): void
    {
        $homeStanding = Standing::firstOrCreate([
            'tournament_id' => $match->tournament_id,
            'team_id' => $match->home_team,
        ]);

        $awayStanding = Standing::firstOrCreate([
            'tournament_id' => $match->tournament_id,
            'team_id' => $match->away_team,
        ]);

        // Update matches played
        $homeStanding->increment('matches_played');
        $awayStanding->increment('matches_played');

        // Update goals
        $homeStanding->goals_for += $match->home_score;
        $homeStanding->goals_against += $match->away_score;
        $awayStanding->goals_for += $match->away_score;
        $awayStanding->goals_against += $match->home_score;

        // Update wins/draws/losses and points
        if ($match->home_score > $match->away_score) {
            $homeStanding->increment('wins');
            $homeStanding->points += 3;
            $awayStanding->increment('losses');
        } elseif ($match->home_score < $match->away_score) {
            $awayStanding->increment('wins');
            $awayStanding->points += 3;
            $homeStanding->increment('losses');
        } else {
            $homeStanding->increment('draws');
            $awayStanding->increment('draws');
            $homeStanding->points += 1;
            $awayStanding->points += 1;
        }

        // Update goal difference
        $homeStanding->goal_difference = $homeStanding->goals_for - $homeStanding->goals_against;
        $awayStanding->goal_difference = $awayStanding->goals_for - $awayStanding->goals_against;

        $homeStanding->save();
        $awayStanding->save();
    }
}