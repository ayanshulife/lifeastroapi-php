<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use LifeAstroApi\LifeAstro;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One client for the whole app; it is built the first time something asks for it.
        $this->app->singleton(LifeAstro::class, static fn (): LifeAstro => new LifeAstro(
            apiKey: config('services.lifeastro.key'),
            baseUrl: config('services.lifeastro.base_url'),
            timeout: (float) config('services.lifeastro.timeout', 30),
        ));
    }
}
