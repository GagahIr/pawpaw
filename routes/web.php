<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing_page.pages.home');
});

Route::livewire('/vendor-register', 'vendor.register-form')->name('vendor.register');
Route::livewire('/client-register', 'client.client-register-form')->middleware('guest')->name('client.register');
Route::livewire('/client-login', 'client.client-login-form')->name('client.login');

Route::post('/logout', function (Request $request): RedirectResponse {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');
// Route::view('/client-register', 'client-register-form')
//     ->name('client.register');
