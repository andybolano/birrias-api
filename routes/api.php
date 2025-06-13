<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TournamentController;
use App\Http\Controllers\Api\TournamentPhaseController;
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
Route::get('/tournament-phase-types', [TournamentPhaseController::class, 'getPhaseTypes']);
Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);
Route::get('/tournaments/{tournament}/fixtures', [TournamentController::class, 'getFixtures']);
Route::get('/tournaments/{tournament}/phases', [TournamentPhaseController::class, 'index']);
Route::get('/tournaments/{tournament}/phases/{phase}/progress', [TournamentPhaseController::class, 'getPhaseProgress']);
Route::get('/teams', [TeamController::class, 'index']);
Route::get('/teams/{team}', [TeamController::class, 'show']);
Route::get('/matches', [MatchController::class, 'index']);
Route::get('/matches/{match}', [MatchController::class, 'show']);
Route::get('/matches/{match}/lineups', [MatchController::class, 'getLineups']);
Route::get('/matches/{match}/events', [MatchController::class, 'getEvents']);
Route::get('/standings', [StandingController::class, 'index']);
Route::get('/standings/tournament/{tournamentId}', [StandingController::class, 'byTournament']);
Route::get('/standings/group/{groupId}', [StandingController::class, 'byGroup']);

// Protected routes (auth required)
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Tournament routes - Admin only for create/update/delete
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('tournaments', TournamentController::class)->except(['show', 'index']);
        Route::post('/tournaments/{tournament}/teams/bulk', [TournamentController::class, 'addMultipleTeams']);
        Route::post('/tournaments/{tournament}/teams', [TournamentController::class, 'addTeam']);
        Route::delete('/tournaments/{tournament}/teams', [TournamentController::class, 'removeTeam']);
        Route::post('/tournaments/{tournament}/generate-fixtures', [TournamentController::class, 'generateFixtures']);
        
        // Tournament Phases routes
        Route::apiResource('tournaments.phases', TournamentPhaseController::class)->except(['show', 'index']);
        Route::post('/tournaments/{tournament}/phases/{phase}/generate-fixtures', [TournamentPhaseController::class, 'generateFixtures']);
        
        // Phase status management routes
        Route::post('/tournaments/{tournament}/phases/{phase}/start', [TournamentPhaseController::class, 'startPhase']);
        Route::post('/tournaments/{tournament}/phases/{phase}/complete', [TournamentPhaseController::class, 'completePhase']);
        Route::post('/tournaments/{tournament}/phases/{phase}/cancel', [TournamentPhaseController::class, 'cancelPhase']);
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
        // Specific routes MUST come before apiResource to avoid conflicts
        Route::get('/players/template', [PlayerController::class, 'downloadTemplate']);
        Route::post('/players/import', [PlayerController::class, 'import']);
        Route::post('/players/{player}/teams', [PlayerController::class, 'addToTeam']);
        Route::delete('/players/{player}/teams', [PlayerController::class, 'removeFromTeam']);
        Route::post('/players/{player}/teams/check-conflicts', [PlayerController::class, 'checkTournamentConflicts']);
        
        // apiResource routes come last
        Route::apiResource('players', PlayerController::class);
    });
    
    // Match routes - Admin only for create/update/delete
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('matches', MatchController::class)->except(['index', 'show']);
        Route::patch('/matches/{match}/start-live', [MatchController::class, 'startLive']);
        Route::patch('/matches/{match}/finish', [MatchController::class, 'finish']);
        Route::post('/matches/{match}/events', [MatchController::class, 'addEvent']);
        Route::patch('/matches/{match}', [MatchController::class, 'update']);
        Route::patch('/matches/{match}/schedule', [MatchController::class, 'schedule']);
        Route::post('/matches/{match}/lineups', [MatchController::class, 'registerLineup']);
        Route::post('/matches/{match}/events', [MatchController::class, 'registerEvent']);
        Route::post('/matches/{match}/substitutions', [MatchController::class, 'registerSubstitution']);
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

// Health check endpoint for Render.com
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'service' => 'Birrias API'
    ]);
});

// Include authentication routes
require __DIR__.'/auth.php';
