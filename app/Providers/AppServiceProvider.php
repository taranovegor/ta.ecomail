<?php

namespace App\Providers;

use App\Contracts;
use App\Search;
use App\Services\Import\ContactImporterResolver;
use App\Services\Import\XmlContactImporter;
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

        $this->app->singleton(ContactImporterResolver::class, static function () {
            return new ContactImporterResolver([
                new XmlContactImporter,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
