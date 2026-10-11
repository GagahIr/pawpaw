<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing_page.pages.home');
});

Route::livewire('/vendor-register', 'vendor.register-form')->name('vendor.register');
Route::livewire('/client-register', 'client.client-register-form')->name('client.register');
Route::livewire('/client-login', 'client.client-login-form')->name('client.login');
// Route::view('/client-register', 'client-register-form')
//     ->name('client.register');
