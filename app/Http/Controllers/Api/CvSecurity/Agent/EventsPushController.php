<?php

namespace App\Http\Controllers\Api\CvSecurity\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\CvSecurity\Agent\EventsPushRequest;
use App\Models\CvSecurityAgent;
use App\Services\CvSecurity\AgentBridgeService;
use Illuminate\Http\JsonResponse;

class EventsPushController extends Controller
{
    public function __invoke(EventsPushRequest $request, AgentBridgeService $bridge): JsonResponse
    {
        /** @var CvSecurityAgent $agent */
        $agent = $request->attributes->get('cvsecurity_agent');

        $events = $request->validated()['events'] ?? [];
        return response()->json($bridge->ingestEvents($agent, $events));
    }
}

