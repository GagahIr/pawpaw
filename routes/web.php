<?php

use App\Http\Controllers\Client\GoogleLoginController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing_page.pages.home');
});

Route::livewire('/vendor-register', 'vendor.register-form')->name('vendor.register');
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
