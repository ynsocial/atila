<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DetectSuspiciousPayload
{
    public function handle(Request $request, Closure $next)
    {
        $patterns = config('antispam.sqli_patterns', []);
        $raw = $request->all();

        // Honeypot
        if (filled($request->input('website'))) {
            Log::warning('honeypot_hit', [
                'ip' => $request->ip(),
                'ua' => (string) $request->userAgent(),
            ]);
            abort(400, 'Your request looks automated and was rejected.');
        }

        $haystack = strtolower(json_encode($raw));
        foreach ($patterns as $re) {
            if (@preg_match('/' . $re . '/i', $haystack)) {
                if (preg_match('/' . $re . '/i', $haystack)) {
                    Log::warning('suspicious_payload', [
                        'ip' => $request->ip(),
                        'ua' => (string) $request->userAgent(),
                        'reason' => $re,
                    ]);
                    abort(400, 'Your request could not be processed.');
                }
            }
        }

        return $next($request);
    }
}

