<?php

namespace BlackfinWebware\LaravelMailMerge;

use BlackfinWebware\LaravelMailMerge\Console\CreateMergeDistribution;
use BlackfinWebware\LaravelMailMerge\Console\CreateMacroExpansion;
use Illuminate\Support\Facades\Route;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
                             __DIR__.'/../config/mailmerge.php' => config_path('mailmerge.php'),
                         ], 'laravel-mail-merge-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations/');
       /* $this->publishes([
                             __DIR__.'/../database/migrations/' => database_path('migrations'),
                         ], 'laravel-mail-merge-migrations'); */

        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mailmerge');

        $this->publishes([
                             __DIR__.'/../resources/views' => resource_path('views/blackfinwebware/laravelmailmerge'),
                         ], 'laravel-mail-merge-views');

        $this->publishes([
                             __DIR__.'/../resources/assets' => public_path('mailmerge'),
                         ], 'laravel-mail-merge-assets');

        $this->commands([
                            CreateMergeDistribution::class,
                            CreateMacroExpansion::class,
                        ]);
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('mailmerge.route_prefix'),
            'middleware' => config('mailmerge.middleware'),
        ];
    }

}
