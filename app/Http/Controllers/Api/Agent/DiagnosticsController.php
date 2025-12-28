<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\EchoRequest;
use Illuminate\Http\Request;

class DiagnosticsController extends Controller
{
    public function ping(Request $request)
    {
        $agent_uuid = (string) $request->header('X-Agent-UUID', '');

        return response()->json([
            'ok' => true,
            'agent_uuid' => $agent_uuid,
            'server_time' => now()->toIso8601String(),
            'app' => 'cloud',
            'version' => 1,
        ]);
    }

    public function echo(EchoRequest $request)
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->validated();

        $agent_uuid = (string) $request->header('X-Agent-UUID', '');

        return response()->json([
            'ok' => true,
            'agent_uuid' => $agent_uuid,
            'server_time' => now()->toIso8601String(),
            'echo' => $payload,
            'app' => 'cloud',
            'version' => 1,
        ]);
    }
}

