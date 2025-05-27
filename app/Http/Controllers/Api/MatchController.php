<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Models\MatchEvent;
use App\Models\Standing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class MatchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/matches",
     *     tags={"Matches"},
     *     summary="Listar partidos",
     *     description="Obtiene la lista de partidos con filtros opcionales",
     *     @OA\Parameter(
     *         name="tournament_id",
     *         in="query",
     *         description="Filtrar por torneo",
     *         required=false,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado del partido",
     *         required=false,
     *         @OA\Schema(type="string", enum={"scheduled", "live", "finished"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de partidos paginada",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="tournament_id", type="string", format="uuid"),
     *                     @OA\Property(property="round", type="integer", nullable=true),
     *                     @OA\Property(property="home_team", type="string", format="uuid"),
     *                     @OA\Property(property="away_team", type="string", format="uuid"),
     *                     @OA\Property(property="match_date", type="string", format="datetime", nullable=true),
     *                     @OA\Property(property="venue", type="string", nullable=true),
     *                     @OA\Property(property="stream_url", type="string", nullable=true),
     *                     @OA\Property(property="status", type="string", enum={"scheduled", "live", "finished"}),
     *                     @OA\Property(property="home_score", type="integer"),
     *                     @OA\Property(property="away_score", type="integer"),
     *                     @OA\Property(property="tournament", type="object"),
     *                     @OA\Property(property="homeTeam", type="object"),
     *                     @OA\Property(property="awayTeam", type="object"),
     *                     @OA\Property(property="events", type="array", @OA\Items(type="object"))
     *                 )
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = FootballMatch::with(['tournament', 'homeTeam', 'awayTeam', 'events']);

        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $matches = $query->orderBy('match_date')->paginate(15);

        return response()->json($matches);
    }

    /**
     * @OA\Post(
     *     path="/api/matches",
     *     tags={"Matches"},
     *     summary="Crear nuevo partido",
     *     description="Crea un nuevo partido (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tournament_id", "home_team", "away_team"},
     *             @OA\Property(property="tournament_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del torneo"),
     *             @OA\Property(property="round", type="integer", minimum=1, example=1, description="Número de jornada/ronda"),
     *             @OA\Property(property="home_team", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440001", description="ID del equipo local"),
     *             @OA\Property(property="away_team", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440002", description="ID del equipo visitante"),
     *             @OA\Property(property="match_date", type="string", format="datetime", example="2024-03-15 15:00:00", description="Fecha y hora del partido"),
     *             @OA\Property(property="venue", type="string", example="Estadio Birrias", description="Lugar del partido"),
     *             @OA\Property(property="stream_url", type="string", format="url", example="https://stream.birrias.com/live/123", description="URL de transmisión en vivo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Partido creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="tournament_id", type="string", format="uuid"),
     *             @OA\Property(property="round", type="integer", nullable=true),
     *             @OA\Property(property="home_team", type="string", format="uuid"),
     *             @OA\Property(property="away_team", type="string", format="uuid"),
     *             @OA\Property(property="match_date", type="string", format="datetime", nullable=true),
     *             @OA\Property(property="venue", type="string", nullable=true),
     *             @OA\Property(property="stream_url", type="string", nullable=true),
     *             @OA\Property(property="status", type="string", example="scheduled"),
     *             @OA\Property(property="home_score", type="integer", example=0),
     *             @OA\Property(property="away_score", type="integer", example=0),
     *             @OA\Property(property="tournament", type="object"),
     *             @OA\Property(property="homeTeam", type="object"),
     *             @OA\Property(property="awayTeam", type="object")
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
            'tournament_id' => 'required|exists:tournaments,id',
            'round' => 'nullable|integer|min:1',
            'home_team' => 'required|exists:teams,id',
            'away_team' => 'required|exists:teams,id|different:home_team',
            'match_date' => 'nullable|date',
            'venue' => 'nullable|string|max:255',
            'stream_url' => 'nullable|url',
        ]);

        $match = FootballMatch::create([
            'id' => Str::uuid(),
            'tournament_id' => $request->tournament_id,
            'round' => $request->round,
            'home_team' => $request->home_team,
            'away_team' => $request->away_team,
            'match_date' => $request->match_date,
            'venue' => $request->venue,
            'stream_url' => $request->stream_url,
            'status' => 'scheduled',
            'home_score' => 0,
            'away_score' => 0,
        ]);

        return response()->json($match->load(['tournament', 'homeTeam', 'awayTeam']), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/matches/{id}",
     *     tags={"Matches"},
     *     summary="Ver partido específico",
     *     description="Obtiene la información detallada de un partido específico",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del partido",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="tournament_id", type="string", format="uuid"),
     *             @OA\Property(property="round", type="integer", nullable=true),
     *             @OA\Property(property="home_team", type="string", format="uuid"),
     *             @OA\Property(property="away_team", type="string", format="uuid"),
     *             @OA\Property(property="match_date", type="string", format="datetime", nullable=true),
     *             @OA\Property(property="venue", type="string", nullable=true),
     *             @OA\Property(property="stream_url", type="string", nullable=true),
     *             @OA\Property(property="status", type="string", enum={"scheduled", "live", "finished"}),
     *             @OA\Property(property="home_score", type="integer"),
     *             @OA\Property(property="away_score", type="integer"),
     *             @OA\Property(property="tournament", type="object"),
     *             @OA\Property(property="homeTeam", type="object"),
     *             @OA\Property(property="awayTeam", type="object"),
     *             @OA\Property(
     *                 property="events",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="event_type", type="string"),
     *                     @OA\Property(property="minute", type="integer"),
     *                     @OA\Property(property="description", type="string", nullable=true),
     *                     @OA\Property(property="player", type="object", nullable=true),
     *                     @OA\Property(property="team", type="object")
     *                 )
     *             ),
     *             @OA\Property(property="created_at", type="string", format="datetime"),
     *             @OA\Property(property="updated_at", type="string", format="datetime")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Partido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(FootballMatch $match): JsonResponse
    {
        return response()->json(
            $match->load(['tournament', 'homeTeam', 'awayTeam', 'events.player', 'events.team'])
        );
    }

    /**
     * @OA\Put(
     *     path="/api/matches/{id}",
     *     tags={"Matches"},
     *     summary="Actualizar partido",
     *     description="Actualiza la información de un partido existente (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="round", type="integer", minimum=1, example=2, description="Número de jornada/ronda"),
     *             @OA\Property(property="match_date", type="string", format="datetime", example="2024-03-15 15:00:00", description="Nueva fecha y hora del partido"),
     *             @OA\Property(property="venue", type="string", example="Estadio Municipal", description="Nuevo lugar del partido"),
     *             @OA\Property(property="stream_url", type="string", format="url", example="https://stream.birrias.com/live/456", description="Nueva URL de transmisión"),
     *             @OA\Property(property="status", type="string", enum={"scheduled", "live", "finished"}, example="live", description="Nuevo estado del partido"),
     *             @OA\Property(property="home_score", type="integer", minimum=0, example=2, description="Marcador del equipo local"),
     *             @OA\Property(property="away_score", type="integer", minimum=0, example=1, description="Marcador del equipo visitante")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Partido actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="tournament_id", type="string", format="uuid"),
     *             @OA\Property(property="round", type="integer", nullable=true),
     *             @OA\Property(property="home_team", type="string", format="uuid"),
     *             @OA\Property(property="away_team", type="string", format="uuid"),
     *             @OA\Property(property="match_date", type="string", format="datetime", nullable=true),
     *             @OA\Property(property="venue", type="string", nullable=true),
     *             @OA\Property(property="stream_url", type="string", nullable=true),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="home_score", type="integer"),
     *             @OA\Property(property="away_score", type="integer"),
     *             @OA\Property(property="tournament", type="object"),
     *             @OA\Property(property="homeTeam", type="object"),
     *             @OA\Property(property="awayTeam", type="object")
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
     *         description="Partido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function update(Request $request, FootballMatch $match): JsonResponse
    {
        $request->validate([
            'round' => 'nullable|integer|min:1',
            'match_date' => 'nullable|date',
            'venue' => 'nullable|string|max:255',
            'stream_url' => 'nullable|url',
            'status' => 'nullable|in:scheduled,live,finished',
            'home_score' => 'nullable|integer|min:0',
            'away_score' => 'nullable|integer|min:0',
        ]);

        $match->update($request->only([
            'round', 'match_date', 'venue', 'stream_url', 
            'status', 'home_score', 'away_score'
        ]));

        // Update standings if match is finished
        if ($request->status === 'finished' && $match->status !== 'finished') {
            $this->updateStandings($match);
        }

        return response()->json($match->load(['tournament', 'homeTeam', 'awayTeam']));
    }

    /**
     * @OA\Delete(
     *     path="/api/matches/{id}",
     *     tags={"Matches"},
     *     summary="Eliminar partido",
     *     description="Elimina un partido existente (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Partido eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Match deleted successfully")
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
     *         description="Partido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(FootballMatch $match): JsonResponse
    {
        $match->delete();
        
        return response()->json(['message' => 'Match deleted successfully']);
    }

    /**
     * @OA\Patch(
     *     path="/api/matches/{id}/start-live",
     *     tags={"Matches"},
     *     summary="Iniciar transmisión en vivo",
     *     description="Cambia el estado del partido a 'en vivo' para iniciar la transmisión (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transmisión iniciada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Match started live")
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
     *         description="Partido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function startLive(FootballMatch $match): JsonResponse
    {
        $match->update(['status' => 'live']);
        
        return response()->json(['message' => 'Match started live']);
    }

    /**
     * @OA\Patch(
     *     path="/api/matches/{id}/finish",
     *     tags={"Matches"},
     *     summary="Finalizar partido",
     *     description="Cambia el estado del partido a 'finalizado' y actualiza automáticamente la tabla de posiciones (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Partido finalizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Match finished")
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
     *         description="Partido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function finish(FootballMatch $match): JsonResponse
    {
        $match->update(['status' => 'finished']);
        $this->updateStandings($match);
        
        return response()->json(['message' => 'Match finished']);
    }

    /**
     * @OA\Post(
     *     path="/api/matches/{match}/events",
     *     tags={"Matches"},
     *     summary="Agregar evento al partido",
     *     description="Agrega un evento (gol, tarjeta, etc.) a un partido en vivo (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="match",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"team_id","event_type","minute"},
     *             @OA\Property(property="player_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="team_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="event_type", type="string", enum={"goal", "yellow_card", "red_card", "substitution"}, example="goal"),
     *             @OA\Property(property="minute", type="integer", example="45"),
     *             @OA\Property(property="description", type="string", example="Golazo desde fuera del área")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Evento agregado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="match_id", type="string", format="uuid"),
     *             @OA\Property(property="event_type", type="string"),
     *             @OA\Property(property="minute", type="integer"),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function addEvent(Request $request, FootballMatch $match): JsonResponse
    {
        $request->validate([
            'player_id' => 'nullable|exists:players,id',
            'team_id' => 'required|exists:teams,id',
            'event_type' => 'required|in:goal,yellow_card,red_card,substitution',
            'minute' => 'required|integer|min:0|max:120',
            'description' => 'nullable|string|max:500',
        ]);

        $event = MatchEvent::create([
            'id' => Str::uuid(),
            'match_id' => $match->id,
            'player_id' => $request->player_id,
            'team_id' => $request->team_id,
            'event_type' => $request->event_type,
            'minute' => $request->minute,
            'description' => $request->description,
        ]);

        // Update score if goal
        if ($request->event_type === 'goal') {
            if ($request->team_id === $match->home_team) {
                $match->increment('home_score');
            } else {
                $match->increment('away_score');
            }
        }

        return response()->json($event->load(['player', 'team']), 201);
    }

    /**
     * Update standings based on match result
     */
    private function updateStandings(FootballMatch $match): void
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
            // Home win
            $homeStanding->increment('wins');
            $homeStanding->points += 3;
            $awayStanding->increment('losses');
        } elseif ($match->home_score < $match->away_score) {
            // Away win
            $awayStanding->increment('wins');
            $awayStanding->points += 3;
            $homeStanding->increment('losses');
        } else {
            // Draw
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