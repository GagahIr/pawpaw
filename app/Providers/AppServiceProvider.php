<?php

namespace App\Providers;

use App\Http\Middleware\ResolveVendor;
use App\Services\ClientUserProvider;
use App\Services\VendorUserProvider;
use App\Supports\VendorContext;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
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
        Auth::provider('client-aware-eloquent', fn (Application $app, array $config): ClientUserProvider => new ClientUserProvider($app['hash'], $config['model']));

        Auth::provider('vendor-aware-eloquent', fn (Application $app, array $config): VendorUserProvider => new VendorUserProvider($app['hash'], $config['model']));

        Livewire::addPersistentMiddleware([ResolveVendor::class]);
    }
}
