<?php

namespace App\Http\Controllers\Api\CvSecurity\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\CvSecurity\Agent\MemberSyncResultsRequest;
use App\Models\CvSecurityAgent;
use App\Services\CvSecurity\AgentBridgeService;
use Illuminate\Http\JsonResponse;

class MemberSyncResultsController extends Controller
{
    public function __invoke(MemberSyncResultsRequest $request, AgentBridgeService $bridge): JsonResponse
    {
        /** @var CvSecurityAgent $agent */
        $agent = $request->attributes->get('cvsecurity_agent');

        $results = $request->validated()['results'] ?? [];
        return response()->json($bridge->applyMemberSyncResults($agent, $results));
    }
}

