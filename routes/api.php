<?php

use App\Http\Controllers\Api\Agent\AgentAccessLogsBatchController;
use App\Http\Controllers\Api\Agent\AgentCommandResultController;
use App\Http\Controllers\Api\Agent\AgentCommandsController;
use App\Http\Controllers\Api\Agent\AgentHeartbeatController;
use App\Http\Controllers\Api\Agent\AgentRegisterController;
use App\Http\Controllers\Api\Agent\DiagnosticsController;
use App\Http\Controllers\Api\CvSecurity\Agent\ConfigController as CvSecurityConfigController;
use App\Http\Controllers\Api\CvSecurity\Agent\EventsPushController as CvSecurityEventsPushController;
use App\Http\Controllers\Api\CvSecurity\Agent\HeartbeatController as CvSecurityHeartbeatController;
use App\Http\Controllers\Api\CvSecurity\Agent\MemberSyncPullController as CvSecurityMemberSyncPullController;
use App\Http\Controllers\Api\CvSecurity\Agent\MemberSyncResultsController as CvSecurityMemberSyncResultsController;
use App\Http\Controllers\Api\CvSecurity\Agent\PairController as CvSecurityPairController;
use App\Http\Controllers\Api\CvSecurity\Agent\StatusReportController as CvSecurityStatusReportController;
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

Route::prefix('cvsecurity/agent')->middleware(['throttle:cvsecurity-agent'])->group(function () {
    Route::post('/pair', CvSecurityPairController::class);

    Route::middleware(['cvsecurity.agent.auth', 'cvsecurity.agent.signature'])->group(function () {
        Route::post('/heartbeat', CvSecurityHeartbeatController::class);
        Route::get('/config', CvSecurityConfigController::class);
        Route::get('/member-sync/pull', CvSecurityMemberSyncPullController::class);
        Route::post('/member-sync/results', CvSecurityMemberSyncResultsController::class);
        Route::post('/events/push', CvSecurityEventsPushController::class);
        Route::post('/status/report', CvSecurityStatusReportController::class);
    });
});
