<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TournamentController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\StandingController;

// Include authentication routes
require __DIR__.'/auth.php';

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes (no auth required)
Route::get('/tournaments/formats', [TournamentController::class, 'getFormats']);
Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);
Route::get('/teams', [TeamController::class, 'index']);
Route::get('/teams/{team}', [TeamController::class, 'show']);
Route::get('/matches', [MatchController::class, 'index']);
Route::get('/matches/{match}', [MatchController::class, 'show']);
Route::get('/standings', [StandingController::class, 'index']);
Route::get('/standings/tournament/{tournamentId}', [StandingController::class, 'byTournament']);
Route::get('/standings/group/{groupId}', [StandingController::class, 'byGroup']);

// Protected routes (auth required)
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Tournament routes - Admin only for create/update/delete
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('tournaments', TournamentController::class)->except(['show', 'index']);
        Route::post('/tournaments/{tournament}/teams', [TournamentController::class, 'addTeam']);
        Route::delete('/tournaments/{tournament}/teams', [TournamentController::class, 'removeTeam']);
    });
    
    // Get user's tournaments (admin only)
    Route::middleware(['role:admin'])->get('/tournaments', [TournamentController::class, 'index']);
    
    // Team routes - Admin only for create/update/delete
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('teams', TeamController::class)->except(['index', 'show']);
        Route::post('/teams/{team}/players', [TeamController::class, 'addPlayer']);
        Route::delete('/teams/{team}/players', [TeamController::class, 'removePlayer']);
    });
    
    // Player routes - Admin only for create/update/delete
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('players', PlayerController::class);
        Route::post('/players/import', [PlayerController::class, 'import']);
        Route::post('/players/{player}/teams', [PlayerController::class, 'addToTeam']);
        Route::delete('/players/{player}/teams', [PlayerController::class, 'removeFromTeam']);
        Route::post('/players/{player}/teams/check-conflicts', [PlayerController::class, 'checkTournamentConflicts']);
    });
    
    // Match routes - Admin only for create/update/delete
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('matches', MatchController::class)->except(['index', 'show']);
        Route::patch('/matches/{match}/start-live', [MatchController::class, 'startLive']);
        Route::patch('/matches/{match}/finish', [MatchController::class, 'finish']);
        Route::post('/matches/{match}/events', [MatchController::class, 'addEvent']);
    });
    
    // Standing routes - Admin only for recalculate
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/standings/recalculate', [StandingController::class, 'recalculate']);
    });
    
    // Player access routes (both admin and player)
    Route::get('/my-profile', function (Request $request) {
        return response()->json($request->user());
    });
});
