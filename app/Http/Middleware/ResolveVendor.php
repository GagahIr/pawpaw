<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ResolveVendor
{
    protected array $reserved = ['www', 'app', 'api', 'admin', 'mail'];

    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $subdomain = Str::before($host, '.'.config('app.domain'));

        abort_if($subdomain === $host || in_array($subdomain, $this->reserved, true), 404);

        $vendor = Vendor::where('slug', $subdomain)->first();
        abort_unless($vendor, 404);

        app('vendor.context')->set($vendor);

        URL::defaults(['subdomain' => $subdomain]);
        $request->route()?->forgetParameter('subdomain');

        return $next($request);
    }
}
