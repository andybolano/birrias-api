<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Birrias API",
 *     version="1.0.0",
 *     description="API RESTful completa para gestión de torneos de fútbol amateur desarrollada con Laravel 12 + Breeze API + Sanctum",
 *     @OA\Contact(
 *         email="admin@birrias.com",
 *         name="Birrias API Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Birrias API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="apiAuth",
 *     type="apiKey",
 *     in="header",
 *     name="Authorization",
 *     description="Enter token in format (Bearer <token>)"
 * )
 *
 * @OA\Components(
 *     @OA\Schema(
 *         schema="ErrorResponse",
 *         type="object",
 *         @OA\Property(property="message", type="string", example="Error message"),
 *         @OA\Property(property="errors", type="object", nullable=true)
 *     )
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Endpoints de autenticación"
 * )
 *
 * @OA\Tag(
 *     name="Tournaments",
 *     description="Gestión de torneos"
 * )
 *
 * @OA\Tag(
 *     name="Teams",
 *     description="Gestión de equipos"
 * )
 *
 * @OA\Tag(
 *     name="Players",
 *     description="Gestión de jugadores"
 * )
 *
 * @OA\Tag(
 *     name="Matches",
 *     description="Gestión de partidos"
 * )
 *
 * @OA\Tag(
 *     name="Standings",
 *     description="Tabla de posiciones"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}