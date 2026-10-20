<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ConfigureClientSession
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $domain = config('app.domain');
        $subdomain = str_ends_with($host, '.'.$domain)
            ? Str::before($host, '.'.$domain)
            : null;
        $isVendor = $subdomain !== null;
        $cookie = $isVendor
            ? 'pawpaw-vendor-session-'.$subdomain
            : 'pawpaw-client-session';

        config([
            'session.cookie' => $cookie,
            'auth.defaults.guard' => $isVendor ? 'vendor' : 'client',
        ]);

        return $next($request);
    }
}
