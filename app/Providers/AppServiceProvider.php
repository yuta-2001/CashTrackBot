<?php

namespace App\Providers;

use GuzzleHttp\Client;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 環境でHTTPSを強制する
        // \URL::forceScheme('https');

        $this->app->singleton(MessagingApiApi::class, function ($app) {
            $client = new Client();
            $config = new Configuration();
            $config->setAccessToken(config('line.channel_access_token'));

            return new MessagingApiApi(
                client: $client,
                config: $config,
            );
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
