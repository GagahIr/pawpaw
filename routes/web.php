<?php

use App\Http\Middleware\EnsureVendorStaff;
use App\Http\Middleware\ResolveVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing_page.pages.home');
})->name('landing_page');

// Route::get('/admin', function () {
//     return view('layouts.admin');
// })->name('admin');

// Route::livewire('/admin/operations', 'vendor.operation_hours.form')->name('admin.operations');

// Route::livewire('/vendor-register', 'vendor.register-form')->name('vendor.register');

$domain = config('app.domain');

Route::domain($domain)->group(function () {
    Route::get('/', fn () => view('landing_page.pages.home'))->name('landing_page');
    Route::livewire('/vendor-register', 'vendor.register-form')->name('vendor.register');
});
Route::domain('{subdomain}.'.$domain)
    ->middleware(ResolveVendor::class)
    ->name('vendor.')
    ->group(function () {
        Route::redirect('/', '/admin');

        Route::middleware('guest')->group(function () {
            Route::livewire('/login', 'vendor.auth.login')->name('login');
        });

        Route::middleware(['auth', EnsureVendorStaff::class])->group(function () {
            Route::livewire('/admin', 'vendor.dashboard.dashboard')->name('admin.dashboard');
            Route::livewire('/admin/operations', 'vendor.operation_hours.form')->name('admin.operations');
            Route::livewire('/admin/services', 'vendor.services.services')->name('admin.services');
            Route::livewire('/admin/services/{categoryServiceId}/manage', 'vendor.services.manage-service')->name('admin.services.manage');
            Route::livewire('/admin/profile-vendor', 'vendor.profile_vendor.profile-vendor')->name('admin.profile.profile-vendor');
            Route::livewire('/admin/manage-staff', 'vendor.manage_staff.manage')->name('admin.manage_staff');

            Route::post('/logout', function (Request $request) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('vendor.login');
            })->name('logout');
        });
    });
