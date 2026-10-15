<?php

use App\Http\Middleware\EnsureVendorStaff;
use App\Http\Middleware\ResolveVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Client\GoogleLoginController;
use Illuminate\Http\RedirectResponse;
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
Route::livewire('/client-register', 'client.client-register-form')->middleware('guest')->name('client.register');
Route::livewire('/client-login', 'client.client-login-form')->middleware('guest')->name('client.login');
Route::livewire('/client-forgot-password', 'client.client-forgot-password-form')->middleware('guest')->name('client.password.request');
Route::livewire('/client-reset-password/{token}', 'client.client-reset-password-form')->middleware('guest')->name('client.password.reset');

Route::get('/auth/google', [GoogleLoginController::class, 'redirect'])->middleware('guest')->name('client.google.redirect');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback'])->middleware('guest')->name('client.google.callback');

Route::post('/logout', function (Request $request): RedirectResponse {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');
// Route::view('/client-register', 'client-register-form')
//     ->name('client.register');
