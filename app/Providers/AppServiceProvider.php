<?php

namespace App\Providers;

use App\Models\LaundryOrder;
use App\Policies\LaundryOrderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    protected array $policies = [
        LaundryOrder::class => LaundryOrderPolicy::class,
    ];

    public function boot(): void
    {
        // Register laundry policy
        Gate::policy(LaundryOrder::class, LaundryOrderPolicy::class);

        // Share unread message count with tenant layout
        view()->composer('components.layouts.tenant', function ($view) {
            $unreadCount = 0;
            if (auth()->check() && auth()->user()->hasRole('tenant')) {
                $tenant = auth()->user()->tenant;
                if ($tenant) {
                    $unreadCount = \App\Models\TenantMessage::where('tenant_id', $tenant->id)
                        ->get()
                        ->sum(function ($message) {
                            return $message->unreadRepliesCountFor(auth()->user());
                        });
                }
            }
            $view->with('unreadMessagesCount', $unreadCount);
        });

        if (app()->environment('local')) {
            // Trust all proxies in local dev so cloudflared/ngrok
            // host headers are respected and asset URLs use the tunnel domain.
            \Illuminate\Http\Request::setTrustedProxies(
                ['127.0.0.1', '::1', 'REMOTE_ADDR'],
                \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
            );

            // Force HTTPS when the request comes through a tunnel
            \Illuminate\Support\Facades\Event::listen(
                \Illuminate\Foundation\Http\Events\RequestHandled::class,
                function ($event) {
                    $host = $event->request->getHost();
                    // Works for any tunnel: cloudflare, ngrok, etc.
                    if (str_contains($host, '.trycloudflare.com')
                        || str_contains($host, '.ngrok')
                        || str_contains($host, '.ngrok-free.app')) {
                        $event->response->headers->set('ngrok-skip-browser-warning', '1');
                    }
                }
            );
        }
    }
}
