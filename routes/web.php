<?php

use App\Http\Controllers\Client\GoogleLoginController;
use App\Http\Middleware\EnsureClient;
use App\Http\Middleware\EnsureSuperAdmin;
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
    Route::get('/', fn () => view('landing_page.pages.home'))->name('landing_page');
    Route::livewire('/vendor-register', 'vendor.register-form')->name('vendor.register');
    Route::livewire('/vendor-domain', 'vendor.auth.domain')->name('vendor.domain');
    Route::livewire('/vendor-login', 'vendor.auth.domain');
    Route::redirect('/akun/profil', '/profile');

    Route::middleware(['auth:client', EnsureClient::class])->group(function () {
        Route::livewire('/client-dashboard', 'client.dashboard.dashboard')->name('client.dashboard');
        Route::livewire('/clinic', 'client.list_clinic.list-clinic2')->name('client.clinic');
        Route::livewire('/my-bookings', 'client.my_bookings.my-bookings')->name('client.my-bookings');
        Route::livewire('/clinic/{slug}', 'client.vendor_detail.vendor-detail')->name('client.vendor.detail');
        Route::livewire('/clinic/booking/{slug}', 'client.booking.booking')->name('client.clinic.booking');
        Route::livewire('/profile', 'client.profile_client.profile-client')->name('client.profile');
        Route::livewire('/history', 'client.history.history')->name('client.history');
    });
});

Route::domain($domain)->prefix('super-admin')->name('super_admin.')->group(function () {
    Route::redirect('/', '/super-admin/dashboard');
    Route::middleware('guest:web')->group(function () {
        Route::livewire('/login', 'super_admin.auth.login')->name('login');
    });

    Route::middleware(['auth:web', EnsureSuperAdmin::class])->group(function () {
        Route::livewire('/dashboard', 'super_admin.dashboard.dashboard')->name('dashboard');
        Route::livewire('/manage-vendors', 'super_admin.manage_vendor.manage-vendor')->name('manage_vendors');
        Route::livewire('/verification-vendors', 'super_admin.verification_vendor.verification-vendor')->name('verification_vendors');

        Route::post('/logout', function (Request $request): RedirectResponse {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('super_admin.login');
        })->name('logout');
    });
});

Route::domain('{subdomain}.'.$domain)
    ->middleware(ResolveVendor::class)
    ->name('vendor.')
    ->group(function () {
        Route::redirect('/', '/admin');

        Route::middleware('guest:vendor')->group(function () {
            Route::livewire('/login', 'vendor.auth.login')->name('login');
            Route::livewire('/forgot-password', 'vendor.auth.forgot-password')->name('password.request');
            Route::livewire('/reset-password/{token}', 'vendor.auth.reset-password')->name('password.reset');
        });

        Route::middleware(['auth:vendor', EnsureVendorStaff::class])->group(function () {
            Route::livewire('/admin', 'vendor.dashboard.dashboard')->name('admin.dashboard');
            Route::livewire('/admin/operations', 'vendor.operation_hours.form')->name('admin.operations');
            Route::livewire('/admin/services', 'vendor.services.services')->name('admin.services');
            Route::livewire('/admin/services/{categoryServiceId}/manage', 'vendor.services.manage-service')->name('admin.services.manage');
            Route::livewire('/admin/profile-vendor', 'vendor.profile_vendor.profile-vendor')->name('admin.profile.profile-vendor');
            Route::livewire('/admin/manage-staff', 'vendor.manage_staff.manage')->name('admin.manage_staff');
            Route::livewire('/admin/booking-clinic', 'vendor.booking_clinic.booking-clinic')->name('admin.booking-clinic');
            Route::livewire('/admin/booking-pet-hotel', 'vendor.booking_pet_hotel.booking-pet-hotel')->name('admin.booking-pet-hotel');
            Route::livewire('/admin/booking-grooming', 'vendor.booking_grooming.booking-grooming')->name('admin.booking-grooming');
            Route::livewire('/admin/my-profile', 'vendor.owner_profile.owner-profile')->name('admin.my_profile');

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
