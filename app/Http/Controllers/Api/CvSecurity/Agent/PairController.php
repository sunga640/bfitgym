<?php

namespace App\Http\Controllers\Api\CvSecurity\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\CvSecurity\Agent\PairRequest;
use App\Services\CvSecurity\AgentBridgeService;
use Illuminate\Http\JsonResponse;

class PairController extends Controller
{
    public function __invoke(PairRequest $request, AgentBridgeService $bridge): JsonResponse
    {
        try {
            $data = $bridge->pair($request->validated(), $request->ip());
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json($data);
    }
}

