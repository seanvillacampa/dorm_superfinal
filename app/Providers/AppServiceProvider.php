<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Skip ngrok's browser interstitial warning page in local dev.
        // The header value can be anything — ngrok just checks its presence.
        if (app()->environment('local')) {
            \Illuminate\Support\Facades\Request::macro('isNgrok', function () {
                return str_contains(request()->getHost(), 'ngrok');
            });

            \Illuminate\Support\Facades\Event::listen(
                \Illuminate\Foundation\Http\Events\RequestHandled::class,
                function ($event) {
                    if (str_contains($event->request->getHost(), 'ngrok')) {
                        $event->response->headers->set('ngrok-skip-browser-warning', '1');
                    }
                }
            );
        }
    }
}
