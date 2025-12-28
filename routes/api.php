<?php

use App\Http\Controllers\Api\Agent\AgentAccessLogsBatchController;
use App\Http\Controllers\Api\Agent\AgentCommandResultController;
use App\Http\Controllers\Api\Agent\AgentCommandsController;
use App\Http\Controllers\Api\Agent\AgentHeartbeatController;
use App\Http\Controllers\Api\Agent\AgentRegisterController;
use App\Http\Controllers\Api\Agent\DiagnosticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Agent endpoints MUST be protected by:
| - agent.auth (X-Agent-UUID, X-Agent-Token)
| - throttle:agent (rate limiter)
|
*/

Route::prefix('agent')->middleware(['throttle:agent'])->group(function () {
    // Registration uses enrollment code (NO agent.auth)
    Route::post('/register', AgentRegisterController::class);

    Route::middleware(['agent.auth'])->group(function () {
        Route::post('/heartbeat', AgentHeartbeatController::class);
        Route::get('/commands', [AgentCommandsController::class, 'index']);
        Route::post('/commands/{command}/result', AgentCommandResultController::class);
        Route::post('/access-logs/batch', AgentAccessLogsBatchController::class);

        Route::get('/diagnostics/ping', [DiagnosticsController::class, 'ping']);
        Route::post('/diagnostics/echo', [DiagnosticsController::class, 'echo']);
    });
});
