<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
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
        $isAdmin = $request->is('super-admin', 'super-admin/*')
            || $request->route('sessionContext') === 'super-admin';
        abort_if($isAdmin && $isVendor, 404);
        $guard = $isVendor ? 'vendor' : ($isAdmin ? 'web' : 'client');
        $cookie = match ($guard) {
            'vendor' => 'pawpaw-vendor-session-'.$subdomain,
            'web' => 'pawpaw-super-admin-session',
            default => 'pawpaw-client-session',
        };

        config([
            'session.cookie' => $cookie,
        ]);
        app('session')->driver()->setName($cookie);
        Auth::shouldUse($guard);
        URL::defaults(['sessionContext' => $isAdmin ? 'super-admin' : 'site']);

        return $next($request);
    }
}
