<?php

namespace App\Providers;

use App\Services\WebhookDispatcher;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WebhookDispatcher::class);

        AliasLoader::getInstance()->alias('Webhook', \App\Facades\Webhook::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $caBundle = env('HTTP_CA_BUNDLE');

        if (! $caBundle) {
            $combined = 'C:/xampp/php/extras/ssl/combined-ca.pem';
            $caBundle = is_file($combined) ? $combined : true;
        }

        Http::globalOptions([
            'verify' => $caBundle === 'false' ? false : $caBundle,
        ]);
    }
}
