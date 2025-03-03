<?php

namespace Spatie\WebTinker;

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\WebTinker\Console\InstallCommand;
use Spatie\WebTinker\Http\Controllers\WebTinkerController;
use Spatie\WebTinker\Http\Middleware\Authorize;
use Spatie\WebTinker\OutputModifiers\OutputModifier;

class WebTinkerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/custom-web-tinker.php' => config_path('custom-custom-web-tinker.php'),
            ], 'config-custom-tinker');

            $this->publishes([
                __DIR__.'/../resources/views' => base_path('resources/views/vendor/custom-custom-web-tinker'),
            ], 'views-custom-tinker');

            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/custom-custom-web-tinker'),
            ], 'custom-custom-web-tinker-assets');
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'custom-custom-web-tinker');

        $this->app->bind(OutputModifier::class, config('custom-custom-web-tinker.output_modifier', config('custom-web-tinker.output_modifier')));

        Route::middlewareGroup('custom-custom-web-tinker', config('custom-custom-web-tinker.middleware', config('custom-web-tinker.middleware', [])));

        $this
            ->registerRoutes()
            ->registerWebTinkerGate();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/custom-web-tinker.php', 'custom-custom-web-tinker');

        $this->commands(InstallCommand::class);
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('custom-custom-web-tinker.path', config('custom-web-tinker.path')),
            'middleware' => 'custom-custom-web-tinker'
        ];
    }

    protected function registerRoutes()
    {
        Route::domain(config('custom-custom-web-tinker.domain', config('custom-web-tinker.domain', '')))->group(function () {
            Route::prefix(config('custom-custom-web-tinker.path', config('custom-web-tinker.path', 'tinker-custom')))->group(function () {
                Route::get('/', 'Spatie\\WebTinker\\Http\\Controllers\\WebTinkerController@index');
                Route::post('/', 'Spatie\\WebTinker\\Http\\Controllers\\WebTinkerController@execute');
                Route::get('/classes', 'Spatie\\WebTinker\\Http\\Controllers\\WebTinkerController@getAvailableClasses');
            });
        });

        return $this;
    }

    protected function registerWebTinkerGate()
    {
        Gate::define('viewWebTinkerCustom', function ($user = null) {
            return app()->environment('local');
        });

        return $this;
    }
}
