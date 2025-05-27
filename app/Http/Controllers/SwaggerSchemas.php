<?php

namespace App\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="fullname", type="string", example="Juan Pérez"),
 *     @OA\Property(property="email", type="string", format="email", example="juan@birrias.com"),
 *     @OA\Property(property="username", type="string", example="juan_perez"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="role", type="string", enum={"admin", "player"}, example="admin"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active"),
 *     @OA\Property(property="avatar_url", type="string", example="https://example.com/avatar.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Tournament",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="Liga Birrias 2024"),
 *     @OA\Property(property="start_date", type="string", format="date", example="2024-03-01"),
 *     @OA\Property(property="inscription_fee_money", type="number", format="decimal", example="150.00"),
 *     @OA\Property(property="currency", type="string", example="USD"),
 *     @OA\Property(property="owner", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active"),
 *     @OA\Property(property="format", type="string", enum={"league", "league_playoffs", "groups_knockout"}, example="league"),
 *     @OA\Property(property="groups", type="integer", example="4"),
 *     @OA\Property(property="teams_per_group", type="integer", example="4"),
 *     @OA\Property(property="playoff_size", type="integer", example="8"),
 *     @OA\Property(property="rounds", type="integer", example="2"),
 *     @OA\Property(property="home_away", type="boolean", example="true"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Team",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="Real Madrid CF"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Player",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="position", type="string", example="Delantero"),
 *     @OA\Property(property="jersey", type="integer", example="10"),
 *     @OA\Property(property="birthDay", type="string", format="date", example="1995-03-15"),
 *     @OA\Property(property="personId", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Match",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="tournament_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="round", type="integer", example="1"),
 *     @OA\Property(property="home_team", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="away_team", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="match_date", type="string", format="date-time", example="2024-03-01T15:00:00Z"),
 *     @OA\Property(property="venue", type="string", example="Estadio Santiago Bernabéu"),
 *     @OA\Property(property="stream_url", type="string", example="https://stream.example.com/match123"),
 *     @OA\Property(property="status", type="string", enum={"scheduled", "live", "finished"}, example="scheduled"),
 *     @OA\Property(property="home_score", type="integer", example="2"),
 *     @OA\Property(property="away_score", type="integer", example="1"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */

/**
 * @OA\Schema(
 *     schema="MatchEvent",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="match_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="player_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="team_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="event_type", type="string", enum={"goal", "yellow_card", "red_card", "substitution"}, example="goal"),
 *     @OA\Property(property="minute", type="integer", example="45"),
 *     @OA\Property(property="description", type="string", example="Golazo desde fuera del área"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Standing",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="tournament_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="team_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="group_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="matches_played", type="integer", example="10"),
 *     @OA\Property(property="wins", type="integer", example="7"),
 *     @OA\Property(property="draws", type="integer", example="2"),
 *     @OA\Property(property="losses", type="integer", example="1"),
 *     @OA\Property(property="goals_for", type="integer", example="25"),
 *     @OA\Property(property="goals_against", type="integer", example="8"),
 *     @OA\Property(property="goal_difference", type="integer", example="17"),
 *     @OA\Property(property="points", type="integer", example="23"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00Z")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(property="errors", type="object", example={"field": {"error message"}})
 * )
 */

/**
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Error message")
 * )
 */

/**
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Operation completed successfully")
 * )
 */

class SwaggerSchemas
{
    // Esta clase solo contiene documentación de Swagger
}