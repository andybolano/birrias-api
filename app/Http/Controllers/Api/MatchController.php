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
     *     description="Crea un nuevo partido (solo admin). NOTA: Con el sistema de fases dinámicas, phase_id es requerido.",
     *     security={{"apiAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tournament_id", "phase_id", "home_team", "away_team"},
     *             @OA\Property(property="tournament_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del torneo"),
     *             @OA\Property(property="phase_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440003", description="ID de la fase del torneo (requerido)"),
     *             @OA\Property(property="round", type="integer", minimum=1, example=1, description="Número de jornada/ronda"),
     *             @OA\Property(property="group_number", type="integer", minimum=1, example=1, description="Número de grupo (para fases de grupos)"),
     *             @OA\Property(property="match_type", type="string", example="regular", description="Tipo de partido (regular, semifinal, final, etc.)"),
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
     *             @OA\Property(property="phase_id", type="string", format="uuid"),
     *             @OA\Property(property="round", type="integer", nullable=true),
     *             @OA\Property(property="group_number", type="integer", nullable=true),
     *             @OA\Property(property="match_type", type="string"),
     *             @OA\Property(property="home_team", type="string", format="uuid"),
     *             @OA\Property(property="away_team", type="string", format="uuid"),
     *             @OA\Property(property="match_date", type="string", format="datetime", nullable=true),
     *             @OA\Property(property="venue", type="string", nullable=true),
     *             @OA\Property(property="stream_url", type="string", nullable=true),
     *             @OA\Property(property="status", type="string", example="scheduled"),
     *             @OA\Property(property="home_score", type="integer", example=0),
     *             @OA\Property(property="away_score", type="integer", example=0),
     *             @OA\Property(property="tournament", type="object"),
     *             @OA\Property(property="phase", type="object"),
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
            'phase_id' => 'required|exists:tournament_phases,id',
            'round' => 'nullable|integer|min:1',
            'group_number' => 'nullable|integer|min:1',
            'match_type' => 'nullable|string|max:50',
            'home_team' => 'required|exists:teams,id',
            'away_team' => 'required|exists:teams,id|different:home_team',
            'match_date' => 'nullable|date',
            'venue' => 'nullable|string|max:255',
            'stream_url' => 'nullable|url',
        ]);

        $match = FootballMatch::create([
            'id' => Str::uuid(),
            'tournament_id' => $request->tournament_id,
            'phase_id' => $request->phase_id,
            'round' => $request->round,
            'group_number' => $request->group_number,
            'match_type' => $request->match_type ?? 'regular',
            'home_team' => $request->home_team,
            'away_team' => $request->away_team,
            'match_date' => $request->match_date,
            'venue' => $request->venue,
            'stream_url' => $request->stream_url,
            'status' => 'scheduled',
            'home_score' => 0,
            'away_score' => 0,
        ]);

        return response()->json($match->load(['tournament', 'phase', 'homeTeam', 'awayTeam']), 201);
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
            'status' => 'nullable|in:created,scheduled,live,finished',
            'home_score' => 'nullable|integer|min:0',
            'away_score' => 'nullable|integer|min:0',
        ]);

        $data = $request->only([
            'round', 'match_date', 'venue', 'stream_url', 
            'status', 'home_score', 'away_score'
        ]);

        // Si se asigna una fecha y el partido está en estado 'created', cambiar a 'scheduled'
        if ($request->has('match_date') && $match->status === 'created') {
            $data['status'] = 'scheduled';
        }

        $match->update($data);

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
     * @OA\Patch(
     *     path="/api/matches/{id}/schedule",
     *     tags={"Matches"},
     *     summary="Programar fecha y hora del partido",
     *     description="Asigna fecha y hora al partido y cambia su estado a 'scheduled'",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"match_date"},
     *             @OA\Property(
     *                 property="match_date",
     *                 type="string",
     *                 format="date-time",
     *                 description="Fecha y hora del partido",
     *                 example="2024-03-20T15:30:00Z"
     *             ),
     *             @OA\Property(
     *                 property="venue",
     *                 type="string",
     *                 description="Lugar donde se jugará el partido",
     *                 example="Estadio Principal"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Partido programado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Match scheduled successfully"),
     *             @OA\Property(property="match", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la solicitud",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Partido no encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function schedule(Request $request, FootballMatch $match): JsonResponse
    {
        $request->validate([
            'match_date' => 'required|date',
            'venue' => 'nullable|string|max:255'
        ]);

        // Verificar que el partido esté en estado 'created' o 'scheduled'
        if (!in_array($match->status, ['created', 'scheduled'])) {
            return response()->json([
                'message' => 'Only matches in "created" or "scheduled" status can be scheduled'
            ], 400);
        }

        // Actualizar el partido
        $match->update([
            'match_date' => $request->match_date,
            'venue' => $request->venue,
            'status' => 'scheduled'
        ]);

        return response()->json([
            'message' => 'Match scheduled successfully',
            'match' => $match->load(['tournament', 'homeTeam', 'awayTeam'])
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/matches/{id}/lineups",
     *     tags={"Matches"},
     *     summary="Registrar alineación del partido",
     *     description="Registra la alineación de un equipo para el partido",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"team_id", "players"},
     *             @OA\Property(
     *                 property="team_id",
     *                 type="string",
     *                 format="uuid",
     *                 description="ID del equipo"
     *             ),
     *             @OA\Property(
     *                 property="players",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"player_id", "is_starter"},
     *                     @OA\Property(
     *                         property="player_id",
     *                         type="string",
     *                         format="uuid"
     *                     ),
     *                     @OA\Property(
     *                         property="is_starter",
     *                         type="boolean"
     *                     ),
     *                     @OA\Property(
     *                         property="shirt_number",
     *                         type="integer"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Alineación registrada exitosamente"
     *     )
     * )
     */
    public function registerLineup(Request $request, FootballMatch $match): JsonResponse
    {
        $request->validate([
            'team_id' => 'required|uuid|exists:teams,id',
            'players' => 'required|array|min:1',
            'players.*.player_id' => 'required|uuid|exists:players,id',
            'players.*.is_starter' => 'required|boolean',
            'players.*.shirt_number' => 'nullable|integer|min:1'
        ]);

        // Verificar que el equipo pertenece al partido
        if (!in_array($request->team_id, [$match->home_team, $match->away_team])) {
            return response()->json([
                'message' => 'Team does not belong to this match'
            ], 400);
        }

        // Eliminar alineación anterior del equipo
        $match->lineups()->where('team_id', $request->team_id)->delete();

        // Registrar nueva alineación
        foreach ($request->players as $player) {
            $match->lineups()->create([
                'team_id' => $request->team_id,
                'player_id' => $player['player_id'],
                'is_starter' => $player['is_starter'],
                'shirt_number' => $player['shirt_number'] ?? null
            ]);
        }

        return response()->json([
            'message' => 'Lineup registered successfully',
            'lineup' => $match->lineups()->where('team_id', $request->team_id)->with('player')->get()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/matches/{id}/events",
     *     tags={"Matches"},
     *     summary="Registrar evento del partido",
     *     description="Registra un evento (gol, tarjeta) durante el partido",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"player_id", "type", "minute"},
     *             @OA\Property(
     *                 property="player_id",
     *                 type="string",
     *                 format="uuid"
     *             ),
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 enum={"goal", "yellow_card", "red_card", "blue_card"}
     *             ),
     *             @OA\Property(
     *                 property="minute",
     *                 type="integer",
     *                 minimum=1
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evento registrado exitosamente"
     *     )
     * )
     */
    public function registerEvent(Request $request, FootballMatch $match): JsonResponse
    {
        $request->validate([
            'player_id' => 'required|uuid|exists:players,id',
            'type' => 'required|in:' . implode(',', MatchEvent::getAvailableTypes()),
            'minute' => 'required|integer|min:1',
            'description' => 'nullable|string'
        ]);

        // Verificar que el jugador está en la alineación
        $playerInLineup = $match->lineups()
            ->where('player_id', $request->player_id)
            ->exists();

        if (!$playerInLineup) {
            return response()->json([
                'message' => 'Player is not in the match lineup'
            ], 400);
        }

        // Registrar el evento
        $event = $match->events()->create([
            'player_id' => $request->player_id,
            'type' => $request->type,
            'minute' => $request->minute,
            'description' => $request->description
        ]);

        // Si es un gol, actualizar el marcador
        if ($request->type === MatchEvent::TYPE_GOAL) {
            $playerTeam = $match->lineups()
                ->where('player_id', $request->player_id)
                ->first()
                ->team_id;

            if ($playerTeam === $match->home_team) {
                $match->increment('home_score');
            } else {
                $match->increment('away_score');
            }
        }

        return response()->json([
            'message' => 'Event registered successfully',
            'event' => $event->load('player')
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/matches/{id}/lineups",
     *     tags={"Matches"},
     *     summary="Consultar alineaciones del partido",
     *     description="Obtiene las alineaciones de ambos equipos para el partido",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Alineaciones del partido",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="home_team",
     *                 type="object",
     *                 @OA\Property(property="team_id", type="string", format="uuid"),
     *                 @OA\Property(property="team_name", type="string"),
     *                 @OA\Property(
     *                     property="starters",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="player_id", type="string", format="uuid"),
     *                         @OA\Property(property="player_name", type="string"),
     *                         @OA\Property(property="shirt_number", type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="substitutes",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="player_id", type="string", format="uuid"),
     *                         @OA\Property(property="player_name", type="string"),
     *                         @OA\Property(property="shirt_number", type="integer")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="away_team",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Partido no encontrado"
     *     )
     * )
     */
    public function getLineups(FootballMatch $match): JsonResponse
    {
        $homeTeam = $match->homeTeam;
        $awayTeam = $match->awayTeam;

        // Obtener alineaciones del equipo local
        $homeLineups = $match->lineups()
            ->where('team_id', $homeTeam->id)
            ->with('player')
            ->get();

        // Obtener alineaciones del equipo visitante
        $awayLineups = $match->lineups()
            ->where('team_id', $awayTeam->id)
            ->with('player')
            ->get();

        // Formatear respuesta
        $response = [
            'home_team' => [
                'team_id' => $homeTeam->id,
                'team_name' => $homeTeam->name,
                'starters' => $homeLineups->where('is_starter', true)->map(function ($lineup) {
                    return [
                        'player_id' => $lineup->player->id,
                        'player_name' => $lineup->player->name,
                        'shirt_number' => $lineup->shirt_number
                    ];
                })->values(),
                'substitutes' => $homeLineups->where('is_starter', false)->map(function ($lineup) {
                    return [
                        'player_id' => $lineup->player->id,
                        'player_name' => $lineup->player->name,
                        'shirt_number' => $lineup->shirt_number
                    ];
                })->values()
            ],
            'away_team' => [
                'team_id' => $awayTeam->id,
                'team_name' => $awayTeam->name,
                'starters' => $awayLineups->where('is_starter', true)->map(function ($lineup) {
                    return [
                        'player_id' => $lineup->player->id,
                        'player_name' => $lineup->player->name,
                        'shirt_number' => $lineup->shirt_number
                    ];
                })->values(),
                'substitutes' => $awayLineups->where('is_starter', false)->map(function ($lineup) {
                    return [
                        'player_id' => $lineup->player->id,
                        'player_name' => $lineup->player->name,
                        'shirt_number' => $lineup->shirt_number
                    ];
                })->values()
            ]
        ];

        return response()->json($response);
    }

    /**
     * @OA\Get(
     *     path="/api/matches/{id}/events",
     *     tags={"Matches"},
     *     summary="Consultar eventos del partido",
     *     description="Obtiene todos los eventos (goles, tarjetas) del partido",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Eventos del partido",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="type", type="string", enum={"goal", "yellow_card", "red_card", "blue_card"}),
     *                 @OA\Property(property="minute", type="integer"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="player", type="object"),
     *                 @OA\Property(property="created_at", type="string", format="datetime")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Partido no encontrado"
     *     )
     * )
     */
    public function getEvents(FootballMatch $match): JsonResponse
    {
        $events = $match->events()
            ->with(['player', 'substitutePlayer'])
            ->orderBy('minute')
            ->orderBy('created_at')
            ->get();

        return response()->json($events);
    }

    /**
     * @OA\Post(
     *     path="/api/matches/{id}/substitutions",
     *     tags={"Matches"},
     *     summary="Registrar sustitución",
     *     description="Registra una sustitución durante el partido",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del partido",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"player_out_id", "player_in_id", "minute"},
     *             @OA\Property(
     *                 property="player_out_id",
     *                 type="string",
     *                 format="uuid",
     *                 description="ID del jugador que sale"
     *             ),
     *             @OA\Property(
     *                 property="player_in_id",
     *                 type="string",
     *                 format="uuid",
     *                 description="ID del jugador que entra"
     *             ),
     *             @OA\Property(
     *                 property="minute",
     *                 type="integer",
     *                 minimum=1,
     *                 description="Minuto de la sustitución"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 description="Descripción opcional"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sustitución registrada exitosamente"
     *     )
     * )
     */
    public function registerSubstitution(Request $request, FootballMatch $match): JsonResponse
    {
        $request->validate([
            'player_out_id' => 'required|uuid|exists:players,id',
            'player_in_id' => 'required|uuid|exists:players,id',
            'minute' => 'required|integer|min:1',
            'description' => 'nullable|string'
        ]);

        // Verificar que el jugador que sale está en la alineación
        $playerOutLineup = $match->lineups()
            ->where('player_id', $request->player_out_id)
            ->first();

        if (!$playerOutLineup) {
            return response()->json([
                'message' => 'Player out is not in the match lineup'
            ], 400);
        }

        // Verificar que el jugador que entra está en la alineación como suplente
        $playerInLineup = $match->lineups()
            ->where('player_id', $request->player_in_id)
            ->where('is_starter', false)
            ->first();

        if (!$playerInLineup) {
            return response()->json([
                'message' => 'Player in must be a substitute in the match lineup'
            ], 400);
        }

        // Verificar que ambos jugadores son del mismo equipo
        if ($playerOutLineup->team_id !== $playerInLineup->team_id) {
            return response()->json([
                'message' => 'Both players must be from the same team'
            ], 400);
        }

        // Registrar la sustitución
        $substitution = $match->events()->create([
            'player_id' => $request->player_out_id,
            'substitute_player_id' => $request->player_in_id,
            'type' => MatchEvent::TYPE_SUBSTITUTION,
            'minute' => $request->minute,
            'description' => $request->description
        ]);

        // Actualizar las alineaciones: el que entra se convierte en titular, el que sale en suplente
        $playerOutLineup->update(['is_starter' => false]);
        $playerInLineup->update(['is_starter' => true]);

        return response()->json([
            'message' => 'Substitution registered successfully',
            'substitution' => $substitution->load(['player', 'substitutePlayer'])
        ]);
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