<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class AccessLogger
{
    public function info(string $message, array $context = []): void
    {
        Log::channel('access')->info($message, $this->withTraceContext($context));
    }

    public function warning(string $message, array $context = []): void
    {
        Log::channel('access')->warning($message, $this->withTraceContext($context));
    }

    public function error(string $message, array $context = []): void
    {
        Log::channel('access')->error($message, $this->withTraceContext($context));
    }

    private function withTraceContext(array $context): array
    {
        $request_id = null;
        $trace_id = null;

        try {
            if (app()->bound('request')) {
                $request = request();
                $request_id = trim((string) ($request->header('X-Request-Id') ?? $request->header('X-Request-ID') ?? $request->input('request_id', '')));
                $trace_id = trim((string) ($request->header('X-Trace-Id') ?? $request->header('X-Trace-ID') ?? $request->input('trace_id', '')));
            }
        } catch (\Throwable) {
            // Ignore; keep logging safe in any context (queues/CLI/tests).
        }

        if ($request_id !== '') {
            $context['request_id'] = $request_id;
        }
        if ($trace_id !== '') {
            $context['trace_id'] = $trace_id;
        }

        return $context;
    }
}

