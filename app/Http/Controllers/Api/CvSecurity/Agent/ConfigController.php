<?php

namespace App\Http\Controllers\Api\CvSecurity\Agent;

use App\Http\Controllers\Controller;
use App\Models\CvSecurityAgent;
use App\Services\CvSecurity\AgentBridgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function __invoke(Request $request, AgentBridgeService $bridge): JsonResponse
    {
        /** @var CvSecurityAgent $agent */
        $agent = $request->attributes->get('cvsecurity_agent');

        return response()->json($bridge->config($agent));
    }
}

