<?php

namespace App\Http\Controllers\Api\CvSecurity\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\CvSecurity\Agent\HeartbeatRequest;
use App\Models\CvSecurityAgent;
use App\Services\CvSecurity\AgentBridgeService;
use Illuminate\Http\JsonResponse;

class HeartbeatController extends Controller
{
    public function __invoke(HeartbeatRequest $request, AgentBridgeService $bridge): JsonResponse
    {
        /** @var CvSecurityAgent $agent */
        $agent = $request->attributes->get('cvsecurity_agent');

        return response()->json(
            $bridge->heartbeat($agent, $request->validated(), $request->ip())
        );
    }
}

