<?php

namespace App\Providers;

use App\Http\Middleware\ResolveVendor;
use App\Supports\VendorContext;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped('vendor.context', fn () => new VendorContext);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::addPersistentMiddleware([ResolveVendor::class]);
    }
}
