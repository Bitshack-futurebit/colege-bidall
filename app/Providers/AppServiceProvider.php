<?php

namespace App\Providers;

use App\Services\WhiteLabelContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhiteLabelContext::class);
    }

    public function boot(): void
    {
        //
    }
}
