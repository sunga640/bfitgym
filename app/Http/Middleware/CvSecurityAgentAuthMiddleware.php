<?php

namespace App\Http\Middleware;

use App\Models\CvSecurityAgent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CvSecurityAgentAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $agent_uuid = trim((string) $request->header('X-CV-Agent-UUID', ''));
        $token = trim((string) $request->header('X-CV-Agent-Token', ''));

        if ($agent_uuid === '' || $token === '') {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        /** @var CvSecurityAgent|null $agent */
        $agent = CvSecurityAgent::query()
            ->where('uuid', $agent_uuid)
            ->where('status', CvSecurityAgent::STATUS_ACTIVE)
            ->first();

        if (!$agent) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $token_hash = hash('sha256', $token);
        if (!hash_equals((string) $agent->auth_token_hash, $token_hash)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $request->attributes->set('cvsecurity_agent', $agent);

        return $next($request);
    }
}

