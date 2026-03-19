<?php

namespace App\Http\Controllers\Api\CvSecurity\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\CvSecurity\Agent\StatusReportRequest;
use App\Models\CvSecurityAgent;
use App\Services\CvSecurity\AgentBridgeService;
use Illuminate\Http\JsonResponse;

class StatusReportController extends Controller
{
    public function __invoke(StatusReportRequest $request, AgentBridgeService $bridge): JsonResponse
    {
        /** @var CvSecurityAgent $agent */
        $agent = $request->attributes->get('cvsecurity_agent');

        return response()->json($bridge->reportStatus($agent, $request->validated()));
    }
}

