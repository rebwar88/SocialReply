<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMetaSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        // GET requests are Meta's webhook verification handshake
        // (hub.challenge) and carry no signature to check.
        if ($request->isMethod('get')) {
            return $next($request);
        }

        $signatureHeader = $request->header('X-Hub-Signature-256', '');
        $appSecret = config('services.meta.app_secret');

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), (string) $appSecret);

        if (! $signatureHeader || ! hash_equals($expected, $signatureHeader)) {
            abort(401, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}
