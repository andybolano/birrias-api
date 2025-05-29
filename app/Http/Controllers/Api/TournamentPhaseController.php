<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentPhase;
use App\Models\FootballMatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class TournamentPhaseController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tournaments/{tournament_id}/phases",
     *     tags={"Tournament Phases"},
     *     summary="Listar fases del torneo",
     *     description="Obtiene todas las fases configuradas para un torneo",
     *     @OA\Parameter(
     *         name="tournament_id",
     *         in="path",
     *         description="ID del torneo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de fases del torneo",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     )
     * )
     */
    public function index(Tournament $tournament): JsonResponse
    {
        $phases = $tournament->phases()
            ->ordered()
            ->withCount('matches')
            ->get();

        return response()->json($phases);
    }

    /**
     * @OA\Post(
     *     path="/api/tournaments/{tournament_id}/phases",
     *     tags={"Tournament Phases"},
     *     summary="Crear nueva fase",
     *     description="Crea una nueva fase para el torneo (solo admin propietario). Tipos disponibles: round_robin, single_elimination, groups",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="tournament_id",
     *         in="path",
     *         description="ID del torneo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "type"},
     *             @OA\Property(property="name", type="string", example="Liga Regular"),
     *             @OA\Property(property="type", type="string", enum={"round_robin", "single_elimination", "groups"}, example="round_robin"),
     *             @OA\Property(property="status", type="string", enum={"pending", "active", "completed", "cancelled"}, example="pending", description="Estado inicial (por defecto: pending)"),
     *             @OA\Property(property="home_away", type="boolean", example=true, description="Solo para round_robin y single_elimination"),
     *             @OA\Property(property="teams_advance", type="integer", example=8, description="Equipos que avanzan a la siguiente fase"),
     *             @OA\Property(property="groups_count", type="integer", example=4, description="Solo para type=groups"),
     *             @OA\Property(property="teams_per_group", type="integer", example=4, description="Solo para type=groups")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Fase creada exitosamente",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function store(Request $request, Tournament $tournament): JsonResponse
    {
        $this->authorize('update', $tournament);

        // Validaciones básicas
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', TournamentPhase::getAvailableTypes()),
            'status' => 'sometimes|in:' . implode(',', TournamentPhase::getAvailableStatuses()),
            'home_away' => 'boolean',
            'teams_advance' => 'nullable|integer|min:1'
        ];

        // Validaciones específicas para grupos
        if ($request->type === TournamentPhase::TYPE_GROUPS) {
            $rules['groups_count'] = 'required|integer|min:2';
            $rules['teams_per_group'] = 'required|integer|min:2';
        }

        $request->validate($rules);

        // Validaciones adicionales
        $this->validatePhaseConfiguration($request);

        // Obtener el siguiente número de fase
        $nextPhaseNumber = $tournament->phases()->max('phase_number') + 1;
        $nextOrder = $tournament->phases()->max('order') + 1;

        // Determinar status inicial
        $status = $request->status ?? TournamentPhase::STATUS_PENDING;
        
        // Solo la primera fase puede ser activa por defecto si no hay otras fases
        if ($tournament->phases()->count() === 0 && !$request->has('status')) {
            $status = TournamentPhase::STATUS_PENDING; // Cambiar a pending por defecto
        }

        $phase = TournamentPhase::create([
            'id' => Str::uuid(),
            'tournament_id' => $tournament->id,
            'phase_number' => $nextPhaseNumber,
            'name' => $request->name,
            'type' => $request->type,
            'status' => $status,
            'home_away' => $request->boolean('home_away', false),
            'teams_advance' => $request->teams_advance,
            'groups_count' => $request->groups_count,
            'teams_per_group' => $request->teams_per_group,
            'order' => $nextOrder
        ]);

        return response()->json($phase, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/tournaments/{tournament_id}/phases/{phase_id}",
     *     tags={"Tournament Phases"},
     *     summary="Actualizar fase",
     *     description="Actualiza una fase existente (solo admin propietario)",
     *     security={{"apiAuth":{}}},
     *     @OA\Parameter(
     *         name="tournament_id",
     *         in="path",
     *         description="ID del torneo",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="phase_id",
     *         in="path",
     *         description="ID de la fase",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Liga Regular Actualizada"),
     *             @OA\Property(property="status", type="string", enum={"pending", "active", "completed", "cancelled"}, example="active"),
     *             @OA\Property(property="home_away", type="boolean", example=true),
     *             @OA\Property(property="teams_advance", type="integer", example=6)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fase actualizada exitosamente"
     *     )
     * )
     */
    public function update(Request $request, Tournament $tournament, TournamentPhase $phase): JsonResponse
    {
        $this->authorize('update', $tournament);

        if ($phase->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Phase does not belong to this tournament'], 400);
        }

        // Validaciones básicas
        $rules = [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:' . implode(',', TournamentPhase::getAvailableTypes()),
            'status' => 'sometimes|in:' . implode(',', TournamentPhase::getAvailableStatuses()),
            'home_away' => 'boolean',
            'teams_advance' => 'nullable|integer|min:1'
        ];

        // Validaciones específicas para grupos
        if ($request->type === TournamentPhase::TYPE_GROUPS || $phase->type === TournamentPhase::TYPE_GROUPS) {
            $rules['groups_count'] = 'sometimes|integer|min:2';
            $rules['teams_per_group'] = 'sometimes|integer|min:2';
        }

        $request->validate($rules);

        // Validar transiciones de estado
        if ($request->has('status')) {
            $this->validateStatusTransition($phase, $request->status);
        }

        $phase->update($request->only([
            'name', 'type', 'status', 'home_away', 'teams_advance', 
            'groups_count', 'teams_per_group'
        ]));

        return response()->json($phase);
    }

    /**
     * @OA\Delete(
     *     path="/api/tournaments/{tournament_id}/phases/{phase_id}",
     *     tags={"Tournament Phases"},
     *     summary="Eliminar fase",
     *     description="Elimina una fase del torneo (solo admin propietario)",
     *     security={{"apiAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fase eliminada exitosamente"
     *     )
     * )
     */
    public function destroy(Tournament $tournament, TournamentPhase $phase): JsonResponse
    {
        $this->authorize('update', $tournament);

        if ($phase->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Phase does not belong to this tournament'], 400);
        }

        // Eliminar partidos de esta fase
        $phase->matches()->delete();
        
        // Eliminar la fase
        $phase->delete();

        return response()->json(['message' => 'Phase deleted successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/tournaments/{tournament_id}/phases/{phase_id}/generate-fixtures",
     *     tags={"Tournament Phases"},
     *     summary="Generar fixtures para la fase",
     *     description="Genera automáticamente los partidos para una fase específica (solo admin propietario)",
     *     security={{"apiAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fixtures generados exitosamente"
     *     )
     * )
     */
    public function generateFixtures(Tournament $tournament, TournamentPhase $phase): JsonResponse
    {
        $this->authorize('update', $tournament);

        if ($phase->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Phase does not belong to this tournament'], 400);
        }

        // Obtener equipos del torneo
        $teams = $tournament->teams()->get();
        $teamCount = $teams->count();

        if ($teamCount < 2) {
            return response()->json([
                'message' => 'Not enough teams to generate fixtures. At least 2 teams are required.'
            ], 400);
        }

        // Limpiar partidos existentes de esta fase
        $phase->matches()->delete();

        $matchesCreated = 0;

        switch ($phase->type) {
            case TournamentPhase::TYPE_ROUND_ROBIN:
                $matchesCreated = $this->generateRoundRobinFixtures($tournament, $phase);
                break;
                
            case TournamentPhase::TYPE_SINGLE_ELIMINATION:
                $matchesCreated = $this->generateSingleEliminationFixtures($tournament, $phase);
                break;
                
            case TournamentPhase::TYPE_GROUPS:
                $matchesCreated = $this->generateGroupsFixtures($tournament, $phase);
                break;
                
            default:
                return response()->json([
                    'message' => 'Unsupported phase type: ' . $phase->type
                ], 400);
        }

        return response()->json([
            'message' => 'Fixtures generated successfully',
            'matches_created' => $matchesCreated,
            'phase_name' => $phase->name,
            'phase_type' => $phase->type
        ]);
    }

    /**
     * Valida la configuración específica de la fase
     */
    private function validatePhaseConfiguration(Request $request): void
    {
        // Validar que home_away solo se use en tipos que lo soporten
        if ($request->boolean('home_away') && !in_array($request->type, [TournamentPhase::TYPE_ROUND_ROBIN, TournamentPhase::TYPE_SINGLE_ELIMINATION])) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['home_away' => ['Home/away matches are only supported for round_robin and single_elimination phases']]
            );
        }

        // Validar configuración de grupos
        if ($request->type === TournamentPhase::TYPE_GROUPS) {
            if (!$request->groups_count || !$request->teams_per_group) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    ['groups' => ['Groups configuration requires both groups_count and teams_per_group']]
                );
            }
        }
    }

    /**
     * Valida las transiciones de estado permitidas
     */
    private function validateStatusTransition(TournamentPhase $phase, string $newStatus): void
    {
        $currentStatus = $phase->status;
        
        // Definir transiciones válidas
        $validTransitions = [
            TournamentPhase::STATUS_PENDING => [
                TournamentPhase::STATUS_ACTIVE,
                TournamentPhase::STATUS_CANCELLED
            ],
            TournamentPhase::STATUS_ACTIVE => [
                TournamentPhase::STATUS_COMPLETED,
                TournamentPhase::STATUS_CANCELLED
            ],
            TournamentPhase::STATUS_COMPLETED => [
                // Las fases completadas no pueden cambiar de estado
            ],
            TournamentPhase::STATUS_CANCELLED => [
                // Las fases canceladas no pueden cambiar de estado
            ]
        ];

        if (!isset($validTransitions[$currentStatus]) || 
            !in_array($newStatus, $validTransitions[$currentStatus])) {
            
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                ['status' => ["Cannot transition from '{$currentStatus}' to '{$newStatus}'"]]
            );
        }
    }

    /**
     * Genera fixtures para fase round robin (todos contra todos)
     */
    private function generateRoundRobinFixtures(Tournament $tournament, TournamentPhase $phase): int
    {
        $teams = $tournament->teams()->get();
        $teamIds = $teams->pluck('id')->toArray();
        $teamCount = count($teamIds);

        if ($teamCount < 2) {
            throw new \Exception('Not enough teams for round robin');
        }

        $matchesCreated = 0;
        $round = 1;

        // Generar todas las combinaciones
        for ($i = 0; $i < $teamCount; $i++) {
            for ($j = $i + 1; $j < $teamCount; $j++) {
                FootballMatch::create([
                    'id' => Str::uuid(),
                    'tournament_id' => $tournament->id,
                    'phase_id' => $phase->id,
                    'round' => $round,
                    'match_type' => 'regular',
                    'home_team' => $teamIds[$i],
                    'away_team' => $teamIds[$j],
                    'status' => 'scheduled'
                ]);
                $matchesCreated++;

                // Si es ida y vuelta
                if ($phase->home_away) {
                    FootballMatch::create([
                        'id' => Str::uuid(),
                        'tournament_id' => $tournament->id,
                        'phase_id' => $phase->id,
                        'round' => $round + 1,
                        'match_type' => 'regular',
                        'home_team' => $teamIds[$j],
                        'away_team' => $teamIds[$i],
                        'status' => 'scheduled'
                    ]);
                    $matchesCreated++;
                }
            }
        }

        return $matchesCreated;
    }

    /**
     * Genera fixtures para eliminación directa
     */
    private function generateSingleEliminationFixtures(Tournament $tournament, TournamentPhase $phase): int
    {
        $teamsAdvance = $phase->teams_advance ?? 8;
        $rounds = ceil(log($teamsAdvance, 2));
        $matchesCreated = 0;

        $teamsInRound = $teamsAdvance;
        for ($round = 1; $round <= $rounds; $round++) {
            $matchesInRound = $teamsInRound / 2;

            for ($match = 1; $match <= $matchesInRound; $match++) {
                // Usar equipos placeholder por ahora
                $teams = $tournament->teams()->take(2)->get();
                
                FootballMatch::create([
                    'id' => Str::uuid(),
                    'tournament_id' => $tournament->id,
                    'phase_id' => $phase->id,
                    'round' => $round,
                    'match_type' => $this->getMatchType($round, $rounds),
                    'home_team' => $teams->first()->id,
                    'away_team' => $teams->skip(1)->first()->id,
                    'status' => 'scheduled'
                ]);
                $matchesCreated++;

                // Si es ida y vuelta
                if ($phase->home_away) {
                    FootballMatch::create([
                        'id' => Str::uuid(),
                        'tournament_id' => $tournament->id,
                        'phase_id' => $phase->id,
                        'round' => $round,
                        'match_type' => $this->getMatchType($round, $rounds) . '_return',
                        'home_team' => $teams->skip(1)->first()->id,
                        'away_team' => $teams->first()->id,
                        'status' => 'scheduled'
                    ]);
                    $matchesCreated++;
                }
            }

            $teamsInRound = $matchesInRound;
        }

        return $matchesCreated;
    }

    /**
     * Genera fixtures para fase de grupos
     */
    private function generateGroupsFixtures(Tournament $tournament, TournamentPhase $phase): int
    {
        $teams = $tournament->teams()->get();
        $teamIds = $teams->pluck('id')->toArray();
        $groupsCount = $phase->groups_count ?? 4;
        $teamsPerGroup = $phase->teams_per_group ?? 4;

        // Dividir equipos en grupos
        $teamGroups = array_chunk($teamIds, $teamsPerGroup);
        $matchesCreated = 0;

        foreach ($teamGroups as $groupIndex => $groupTeams) {
            $groupNumber = $groupIndex + 1;
            $groupTeamCount = count($groupTeams);

            // Todos contra todos en el grupo
            for ($i = 0; $i < $groupTeamCount; $i++) {
                for ($j = $i + 1; $j < $groupTeamCount; $j++) {
                    FootballMatch::create([
                        'id' => Str::uuid(),
                        'tournament_id' => $tournament->id,
                        'phase_id' => $phase->id,
                        'round' => 1,
                        'group_number' => $groupNumber,
                        'match_type' => 'group',
                        'home_team' => $groupTeams[$i],
                        'away_team' => $groupTeams[$j],
                        'status' => 'scheduled'
                    ]);
                    $matchesCreated++;

                    // Si es ida y vuelta
                    if ($phase->home_away) {
                        FootballMatch::create([
                            'id' => Str::uuid(),
                            'tournament_id' => $tournament->id,
                            'phase_id' => $phase->id,
                            'round' => 2,
                            'group_number' => $groupNumber,
                            'match_type' => 'group',
                            'home_team' => $groupTeams[$j],
                            'away_team' => $groupTeams[$i],
                            'status' => 'scheduled'
                        ]);
                        $matchesCreated++;
                    }
                }
            }
        }

        return $matchesCreated;
    }

    /**
     * Determina el tipo de partido según la ronda
     */
    private function getMatchType(int $round, int $totalRounds): string
    {
        if ($round === $totalRounds) {
            return 'final';
        } elseif ($round === $totalRounds - 1) {
            return 'semifinal';
        } elseif ($round === $totalRounds - 2) {
            return 'quarterfinal';
        } else {
            return 'elimination';
        }
    }

    /**
     * @OA\Get(
     *     path="/api/tournament-phase-types",
     *     tags={"Tournament Phases"},
     *     summary="Obtener tipos de fase disponibles",
     *     description="Obtiene la lista de tipos de fase disponibles para el MVP (3 tipos simplificados) y estados disponibles",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de tipos de fase disponibles",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     )
     * )
     */
    public function getPhaseTypes(): JsonResponse
    {
        $phaseTypes = [
            [
                'value' => TournamentPhase::TYPE_ROUND_ROBIN,
                'label' => 'Liga (Todos contra Todos)',
                'description' => 'Cada equipo juega contra todos los demás equipos. Ideal para ligas regulares.',
                'supports_home_away' => true,
                'required_fields' => [],
                'optional_fields' => ['home_away', 'teams_advance'],
                'example' => [
                    'name' => 'Liga Regular',
                    'type' => 'round_robin',
                    'home_away' => true,
                    'teams_advance' => 8
                ]
            ],
            [
                'value' => TournamentPhase::TYPE_SINGLE_ELIMINATION,
                'label' => 'Eliminación Directa',
                'description' => 'Eliminación directa - quien pierde queda eliminado. Ideal para playoffs.',
                'supports_home_away' => true,
                'required_fields' => ['teams_advance'],
                'optional_fields' => ['home_away'],
                'example' => [
                    'name' => 'Playoffs',
                    'type' => 'single_elimination',
                    'teams_advance' => 8,
                    'home_away' => true
                ]
            ],
            [
                'value' => TournamentPhase::TYPE_GROUPS,
                'label' => 'Fase de Grupos',
                'description' => 'Los equipos se dividen en grupos y juegan todos contra todos dentro del grupo. Ideal para mundiales.',
                'supports_home_away' => false,
                'required_fields' => ['groups_count', 'teams_per_group'],
                'optional_fields' => ['teams_advance'],
                'example' => [
                    'name' => 'Fase de Grupos',
                    'type' => 'groups',
                    'groups_count' => 4,
                    'teams_per_group' => 4,
                    'teams_advance' => 8
                ]
            ]
        ];

        $phaseStatuses = [
            [
                'value' => TournamentPhase::STATUS_PENDING,
                'label' => 'Pendiente',
                'description' => 'Fase creada pero no iniciada. Se pueden generar fixtures.',
                'color' => 'gray',
                'can_transition_to' => ['active', 'cancelled']
            ],
            [
                'value' => TournamentPhase::STATUS_ACTIVE,
                'label' => 'Activa',
                'description' => 'Fase en curso. Los partidos se están jugando.',
                'color' => 'green',
                'can_transition_to' => ['completed', 'cancelled']
            ],
            [
                'value' => TournamentPhase::STATUS_COMPLETED,
                'label' => 'Completada',
                'description' => 'Fase finalizada. Todos los partidos terminados.',
                'color' => 'blue',
                'can_transition_to' => []
            ],
            [
                'value' => TournamentPhase::STATUS_CANCELLED,
                'label' => 'Cancelada',
                'description' => 'Fase cancelada. No se puede reactivar.',
                'color' => 'red',
                'can_transition_to' => []
            ]
        ];

        return response()->json([
            'phase_types' => $phaseTypes,
            'phase_statuses' => $phaseStatuses,
            'status_flow' => [
                'initial' => 'pending',
                'normal_flow' => 'pending → active → completed',
                'cancellation' => 'pending|active → cancelled',
                'final_states' => ['completed', 'cancelled']
            ],
            'note' => 'MVP simplificado - Solo 3 tipos de fase disponibles con 4 estados',
            'total_types' => count($phaseTypes),
            'total_statuses' => count($phaseStatuses)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/tournaments/{tournament_id}/phases/{phase_id}/start",
     *     tags={"Tournament Phases"},
     *     summary="Iniciar fase",
     *     description="Cambia el estado de la fase a 'active' (solo admin propietario)",
     *     security={{"apiAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fase iniciada exitosamente"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="La fase no puede ser iniciada"
     *     )
     * )
     */
    public function startPhase(Tournament $tournament, TournamentPhase $phase): JsonResponse
    {
        $this->authorize('update', $tournament);

        if ($phase->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Phase does not belong to this tournament'], 400);
        }

        if (!$phase->canBeStarted()) {
            return response()->json([
                'message' => 'Phase cannot be started',
                'current_status' => $phase->status,
                'reason' => 'Only pending phases can be started'
            ], 400);
        }

        $phase->start();

        return response()->json([
            'message' => 'Phase started successfully',
            'phase' => $phase->fresh(),
            'progress' => $phase->getProgress()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/tournaments/{tournament_id}/phases/{phase_id}/complete",
     *     tags={"Tournament Phases"},
     *     summary="Completar fase",
     *     description="Cambia el estado de la fase a 'completed' (solo admin propietario)",
     *     security={{"apiAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fase completada exitosamente"
     *     )
     * )
     */
    public function completePhase(Tournament $tournament, TournamentPhase $phase): JsonResponse
    {
        $this->authorize('update', $tournament);

        if ($phase->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Phase does not belong to this tournament'], 400);
        }

        if (!$phase->canBeCompleted()) {
            return response()->json([
                'message' => 'Phase cannot be completed',
                'current_status' => $phase->status,
                'reason' => 'Only active phases can be completed'
            ], 400);
        }

        $phase->complete();

        return response()->json([
            'message' => 'Phase completed successfully',
            'phase' => $phase->fresh(),
            'progress' => $phase->getProgress()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/tournaments/{tournament_id}/phases/{phase_id}/cancel",
     *     tags={"Tournament Phases"},
     *     summary="Cancelar fase",
     *     description="Cambia el estado de la fase a 'cancelled' (solo admin propietario)",
     *     security={{"apiAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fase cancelada exitosamente"
     *     )
     * )
     */
    public function cancelPhase(Tournament $tournament, TournamentPhase $phase): JsonResponse
    {
        $this->authorize('update', $tournament);

        if ($phase->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Phase does not belong to this tournament'], 400);
        }

        if (!$phase->canBeCancelled()) {
            return response()->json([
                'message' => 'Phase cannot be cancelled',
                'current_status' => $phase->status,
                'reason' => 'Only pending or active phases can be cancelled'
            ], 400);
        }

        $phase->cancel();

        return response()->json([
            'message' => 'Phase cancelled successfully',
            'phase' => $phase->fresh()
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/tournaments/{tournament_id}/phases/{phase_id}/progress",
     *     tags={"Tournament Phases"},
     *     summary="Obtener progreso de la fase",
     *     description="Obtiene información detallada del progreso de una fase",
     *     @OA\Response(
     *         response=200,
     *         description="Progreso de la fase"
     *     )
     * )
     */
    public function getPhaseProgress(Tournament $tournament, TournamentPhase $phase): JsonResponse
    {
        if ($phase->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Phase does not belong to this tournament'], 400);
        }

        $progress = $phase->getProgress();
        
        return response()->json([
            'phase_id' => $phase->id,
            'phase_name' => $phase->name,
            'phase_status' => $phase->status,
            'progress' => $progress,
            'can_be_started' => $phase->canBeStarted(),
            'can_be_completed' => $phase->canBeCompleted(),
            'can_be_cancelled' => $phase->canBeCancelled(),
            'should_auto_complete' => $phase->shouldAutoComplete()
        ]);
    }
}
