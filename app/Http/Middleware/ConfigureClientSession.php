<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfigureClientSession
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user()?->role?->name !== 'client') {
            return $response;
        }

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === config('session.cookie')
                && $cookie->getPath() === config('session.path')
                && $cookie->getDomain() === config('session.domain')) {
                $response->headers->setCookie($cookie->withExpires(0));
            }
        }

        return $response;
    }
}
