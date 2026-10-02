<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing_page.pages.home');
});

Route::livewire('/vendor-register', 'vendor.register-form')->name('vendor.register');
