<?php

namespace App\Http\Middleware;

use App\Models\VendorStaff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('vendor');

        abort_unless(
            $user?->deleted_at === null && in_array($user->role?->name, ['owner', 'front_office'], true),
            403,
        );

        $staff = VendorStaff::where('user_id', $user->id)
            ->where('vendor_id', app('vendor.context')->id())
            ->first();

        abort_unless($staff !== null, 403);

        $request->attributes->set('vendor_staff', $staff);

        return $next($request);
    }
}
