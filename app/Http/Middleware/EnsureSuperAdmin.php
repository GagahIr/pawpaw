<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        abort_unless(
            $user?->role?->name === 'super_admin'
                && $user->deleted_at === null,
            403,
            'Akses ditolak.'
        );

        return $next($request);
    }
}
