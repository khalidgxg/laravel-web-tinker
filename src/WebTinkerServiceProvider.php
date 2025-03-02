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
                __DIR__.'/../config/web-tinker.php' => config_path('web-tinker-custom.php'),
            ], 'config-custom-tinker');

            $this->publishes([
                __DIR__.'/../resources/views' => base_path('resources/views/vendor/web-tinker-custom'),
            ], 'views-custom-tinker');

            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/web-tinker-custom'),
            ], 'web-tinker-custom-assets');
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'web-tinker-custom');

        $this->app->bind(OutputModifier::class, config('web-tinker-custom.output_modifier', config('web-tinker.output_modifier')));

        Route::middlewareGroup('web-tinker-custom', config('web-tinker-custom.middleware', config('web-tinker.middleware', [])));

        $this
            ->registerRoutes()
            ->registerWebTinkerGate();
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/web-tinker.php', 'web-tinker-custom');

        $this->commands(InstallCommand::class);
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('web-tinker-custom.path', config('web-tinker.path')),
            'middleware' => 'web-tinker-custom'
        ];
    }

    protected function registerRoutes()
    {
        Route::domain(config('web-tinker-custom.domain', config('web-tinker.domain', '')))->group(function () {
            Route::prefix(config('web-tinker-custom.path', config('web-tinker.path', 'tinker-custom')))->group(function () {
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
