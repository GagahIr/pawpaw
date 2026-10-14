<?php

namespace App\Http\Middleware;

use App\Models\VendorStaff;
use Closure;
use Illuminate\Http\Request;

class EnsureVendorStaff
{
    public function handle(Request $request, Closure $next)
    {
        $staff = VendorStaff::where('user_id', $request->user()->id)
            ->where('vendor_id', app('vendor.context')->id())
            ->first();

        abort_unless($staff, 403);

        $request->attributes->set('vendor_staff', $staff);

        return $next($request);
    }
}
