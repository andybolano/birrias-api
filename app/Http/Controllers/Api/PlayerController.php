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
     *     description="Crea un nuevo jugador (solo admin)",
     *     security={{"apiAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="position", type="string", maxLength=50, example="Delantero", description="Posición del jugador"),
     *             @OA\Property(property="jersey", type="integer", minimum=1, maximum=999, example=10, description="Número de camiseta"),
     *             @OA\Property(property="birthDay", type="string", format="date", example="1995-03-15", description="Fecha de nacimiento"),
     *             @OA\Property(property="personId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID del usuario asociado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Jugador creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="position", type="string", nullable=true),
     *             @OA\Property(property="jersey", type="integer", nullable=true),
     *             @OA\Property(property="birthDay", type="string", format="date", nullable=true),
     *             @OA\Property(property="personId", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="person", type="object", nullable=true),
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
            'position' => 'nullable|string|max:50',
            'jersey' => 'nullable|integer|min:1|max:999',
            'birthDay' => 'nullable|date',
            'personId' => 'nullable|exists:users,id',
        ]);

        $player = Player::create([
            'id' => Str::uuid(),
            'position' => $request->position,
            'jersey' => $request->jersey,
            'birthDay' => $request->birthDay,
            'personId' => $request->personId,
        ]);

        return response()->json($player->load(['person']), 201);
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
     *             @OA\Property(property="position", type="string", maxLength=50, example="Mediocampista", description="Nueva posición del jugador"),
     *             @OA\Property(property="jersey", type="integer", minimum=1, maximum=999, example=8, description="Nuevo número de camiseta"),
     *             @OA\Property(property="birthDay", type="string", format="date", example="1995-03-15", description="Nueva fecha de nacimiento"),
     *             @OA\Property(property="personId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="Nuevo ID del usuario asociado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jugador actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="position", type="string", nullable=true),
     *             @OA\Property(property="jersey", type="integer", nullable=true),
     *             @OA\Property(property="birthDay", type="string", format="date", nullable=true),
     *             @OA\Property(property="personId", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="person", type="object", nullable=true),
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
            'position' => 'nullable|string|max:50',
            'jersey' => 'nullable|integer|min:1|max:999',
            'birthDay' => 'nullable|date',
            'personId' => 'nullable|exists:users,id',
        ]);

        $player->update($request->only([
            'position', 'jersey', 'birthDay', 'personId'
        ]));

        return response()->json($player->load(['person']));
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
     *     path="/api/players/import",
     *     tags={"Players"},
     *     summary="Importar jugadores desde CSV/Excel",
     *     description="Importa múltiples jugadores desde un archivo CSV o Excel y los asocia a un equipo (solo admin)",
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
     *                     description="Archivo CSV/Excel con datos de jugadores. Columnas esperadas: position, jersey, birthDay"
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
     *             @OA\Property(property="message", type="string", example="Players imported successfully"),
     *             @OA\Property(property="count", type="integer", example=15, description="Número de jugadores importados"),
     *             @OA\Property(
     *                 property="players",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="position", type="string", nullable=true),
     *                     @OA\Property(property="jersey", type="integer", nullable=true),
     *                     @OA\Property(property="birthDay", type="string", format="date", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="datetime"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime")
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
     *         description="Error de validación - Archivo inválido o equipo no existe",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
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
        $lines = explode("\n", $content);
        
        $players = [];
        $header = str_getcsv(array_shift($lines));
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $data = str_getcsv($line);
            $playerData = array_combine($header, $data);
            
            $player = Player::create([
                'id' => Str::uuid(),
                'position' => $playerData['position'] ?? null,
                'jersey' => $playerData['jersey'] ?? null,
                'birthDay' => $playerData['birthDay'] ?? null,
            ]);
            
            // Associate with team
            $player->teams()->attach($request->team_id);
            $players[] = $player;
        }
        
        return response()->json([
            'message' => 'Players imported successfully',
            'count' => count($players),
            'players' => $players
        ], 201);
    }
}