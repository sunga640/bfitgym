<?php

namespace App\Http\Controllers\Api\CvSecurity\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\CvSecurity\Agent\MemberSyncPullRequest;
use App\Models\CvSecurityAgent;
use App\Services\CvSecurity\AgentBridgeService;
use Illuminate\Http\JsonResponse;

class MemberSyncPullController extends Controller
{
    public function __invoke(MemberSyncPullRequest $request, AgentBridgeService $bridge): JsonResponse
    {
        /** @var CvSecurityAgent $agent */
        $agent = $request->attributes->get('cvsecurity_agent');
        $limit = (int) ($request->validated()['limit'] ?? 100);

        return response()->json($bridge->claimMemberSyncItems($agent, $limit));
    }
}

