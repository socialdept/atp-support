<?php

namespace SocialDept\AtpSupport;

use Illuminate\Support\ServiceProvider;
use SocialDept\AtpSupport\Cache\LaravelCacheStore;
use SocialDept\AtpSupport\Contracts\CacheStore;
use SocialDept\AtpSupport\Contracts\DidResolver;
use SocialDept\AtpSupport\Contracts\HandleResolver;
use SocialDept\AtpSupport\Microcosm\ConstellationClient;
use SocialDept\AtpSupport\Microcosm\Microcosm;
use SocialDept\AtpSupport\Microcosm\SlingshotClient;
use SocialDept\AtpSupport\Resolvers\AtProtoHandleResolver;
use SocialDept\AtpSupport\Resolvers\DidResolverManager;
use SocialDept\AtpSupport\Resolvers\DnsHandleResolver;
use SocialDept\AtpSupport\Resolvers\HandleResolverManager;
use SocialDept\AtpSupport\Resolvers\WellKnownHandleResolver;

class AtpSupportServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/atp-support.php', 'atp-support');

        // Register cache store
        $this->app->singleton(CacheStore::class, function ($app) {
            return new LaravelCacheStore($app->make('cache')->store());
        });

        // Register DID resolver
        $this->app->singleton(DidResolver::class, function ($app) {
            return new DidResolverManager();
        });

        // Register individual handle resolution methods
        $this->app->singleton(DnsHandleResolver::class);
        $this->app->singleton(WellKnownHandleResolver::class);
        $this->app->singleton(AtProtoHandleResolver::class);

        // Register handle resolver with configurable fallback chain
        $this->app->singleton(HandleResolver::class, function ($app) {
            $methods = $app['config']['atp-support.handle_resolution.methods'] ?? ['dns', 'well-known', 'xrpc'];

            $resolvers = collect($methods)->map(fn (string $method) => match ($method) {
                'dns' => $app->make(DnsHandleResolver::class),
                'well-known' => $app->make(WellKnownHandleResolver::class),
                'xrpc' => $app->make(AtProtoHandleResolver::class),
                default => null,
            })->filter()->all();

            return new HandleResolverManager($resolvers);
        });

        // Register Resolver service
        $this->app->singleton('resolver', function ($app) {
            return new Resolver(
                $app->make(DidResolver::class),
                $app->make(HandleResolver::class),
                $app->make(CacheStore::class),
            );
        });

        $this->app->alias('resolver', Resolver::class);

        // Register Microcosm clients
        $this->app->singleton(ConstellationClient::class, function ($app) {
            $config = $app['config']['atp-support.microcosm.constellation'] ?? [];

            return new ConstellationClient(
                baseUrl: $config['url'] ?? null,
                timeout: $config['timeout'] ?? null,
            );
        });

        $this->app->singleton(SlingshotClient::class, function ($app) {
            $config = $app['config']['atp-support.microcosm.slingshot'] ?? [];

            return new SlingshotClient(
                baseUrl: $config['url'] ?? null,
                timeout: $config['timeout'] ?? null,
            );
        });

        $this->app->singleton('microcosm', function ($app) {
            return new Microcosm(
                $app->make(ConstellationClient::class),
                $app->make(SlingshotClient::class),
            );
        });

        $this->app->alias('microcosm', Microcosm::class);
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            'resolver', Resolver::class,
            'microcosm', Microcosm::class,
            ConstellationClient::class,
            SlingshotClient::class,
        ];
    }

    /**
     * Console-specific booting.
     */
    protected function bootForConsole(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/atp-support.php' => config_path('atp-support.php'),
        ], 'atp-support-config');
    }
}
