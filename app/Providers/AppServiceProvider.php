<?php

namespace App\Providers;

use App\Contracts;
use App\Search;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            Contracts\ContactSearchInterface::class,
            Search\MeilisearchContactSearch::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
