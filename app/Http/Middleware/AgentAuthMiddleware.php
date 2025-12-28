<?php

namespace App\Http\Middleware;

use App\Models\AccessControlAgent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $agent_uuid = (string) $request->header('X-Agent-UUID', '');
        $token = (string) $request->header('X-Agent-Token', '');

        if ($agent_uuid === '' || $token === '') {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        /** @var AccessControlAgent|null $agent */
        $agent = AccessControlAgent::query()
            ->where('uuid', $agent_uuid)
            ->where('status', AccessControlAgent::STATUS_ACTIVE)
            ->first();

        if (!$agent) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $token_hash = hash('sha256', $token);

        if (!hash_equals((string) $agent->secret_hash, $token_hash)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $request->attributes->set('access_control_agent', $agent);

        return $next($request);
    }
}
