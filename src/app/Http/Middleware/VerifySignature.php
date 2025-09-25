<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class VerifySignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->user()?->secret;

        $payload = $request->file('file');

        $signature = $request->header('X-Signature');

        if (!$signature) {
            throw new BadRequestHttpException('Missing "X-Signature" header');
        }

        $calculated = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($calculated, $signature)) {
            throw new BadRequestHttpException('Invalid signature');
        }

        return $next($request);
    }
}
