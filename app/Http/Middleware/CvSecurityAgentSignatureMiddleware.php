<?php

namespace App\Http\Middleware;

use App\Models\CvSecurityAgent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CvSecurityAgentSignatureMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var CvSecurityAgent|null $agent */
        $agent = $request->attributes->get('cvsecurity_agent');
        if (!$agent) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $timestamp = trim((string) $request->header('X-CV-Timestamp', ''));
        $signature = strtolower(trim((string) $request->header('X-CV-Signature', '')));

        if ($timestamp === '' || $signature === '' || !ctype_digit($timestamp)) {
            return response()->json(['message' => 'Invalid signature headers.'], 401);
        }

        $ts = (int) $timestamp;
        if (abs(now()->timestamp - $ts) > 300) {
            return response()->json(['message' => 'Request timestamp expired.'], 401);
        }

        $secret = $agent->decryptedAuthToken();
        if (!$secret) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $path = '/' . ltrim($request->path(), '/');
        $content = in_array(strtoupper($request->method()), ['GET', 'HEAD'], true)
            ? ''
            : (string) $request->getContent();
        $payload = $timestamp . "\n" . strtoupper($request->method()) . "\n" . $path . "\n" . $content;
        $expected = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid request signature.'], 401);
        }

        return $next($request);
    }
}
