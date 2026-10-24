<?php

use App\Http\Controllers\Client\GoogleLoginController;
use App\Http\Middleware\EnsureClient;
use App\Http\Middleware\EnsureVendorStaff;
use App\Http\Middleware\ResolveVendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing_page.pages.home');
})->name('landing_page');

$domain = config('app.domain');

Route::domain($domain)->group(function () {
    Route::get('/', fn() => view('landing_page.pages.home'))->name('landing_page');
    Route::livewire('/vendor-register', 'vendor.register-form')->name('vendor.register');
    Route::livewire('/client-dashboard', 'client.dashboard.dashboard')->middleware(['auth:client', EnsureClient::class])->name('client.dashboard');
    Route::livewire('/clinic', 'client.list_clinic.list-clinic2')->middleware('auth:client', EnsureClient::class)->name('client.clinic');
    Route::livewire('/vendor/{slug}', 'client.vendor_detail.vendor-detail')->middleware('auth:client', EnsureClient::class)->name('client.vendor.detail');
});

Route::domain('{subdomain}.' . $domain)
    ->middleware(ResolveVendor::class)
    ->name('vendor.')
    ->group(function () {
        Route::redirect('/', '/admin');

        Route::middleware('guest:vendor')->group(function () {
            Route::livewire('/login', 'vendor.auth.login')->name('login');
        });

        Route::middleware(['auth:vendor', EnsureVendorStaff::class])->group(function () {
            Route::livewire('/admin', 'vendor.dashboard.dashboard')->name('admin.dashboard');
            Route::livewire('/admin/operations', 'vendor.operation_hours.form')->name('admin.operations');
            Route::livewire('/admin/services', 'vendor.services.services')->name('admin.services');
            Route::livewire('/admin/services/{categoryServiceId}/manage', 'vendor.services.manage-service')->name('admin.services.manage');
            Route::livewire('/admin/profile-vendor', 'vendor.profile_vendor.profile-vendor')->name('admin.profile.profile-vendor');
            Route::livewire('/admin/manage-staff', 'vendor.manage_staff.manage')->name('admin.manage_staff');

            Route::post('/logout', function (Request $request) {
                Auth::guard('vendor')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('vendor.login');
            })->name('admin.logout');
        });
    });

Route::livewire('/client-register', 'client.auth.register')->middleware('guest:client')->name('client.register');
Route::livewire('/client-login', 'client.auth.login')->middleware('guest:client')->name('client.login');
Route::livewire('/client-forgot-password', 'client.auth.forgot-password')->middleware('guest:client')->name('client.password.request');
Route::livewire('/client-reset-password/{token}', 'client.auth.reset-password')->middleware('guest:client')->name('client.password.reset');

Route::get('/auth/google', [GoogleLoginController::class, 'redirect'])->middleware('guest:client')->name('client.google.redirect');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback'])->middleware('guest:client')->name('client.google.callback');

Route::post('/logout', function (Request $request): RedirectResponse {
    Auth::guard('client')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth:client')->name('client.logout');
