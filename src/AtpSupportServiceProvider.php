<?php

namespace SocialDept\AtpSupport;

use Illuminate\Support\ServiceProvider;
use SocialDept\AtpSupport\Cache\LaravelCacheStore;
use SocialDept\AtpSupport\Contracts\CacheStore;
use SocialDept\AtpSupport\Contracts\DidResolver;
use SocialDept\AtpSupport\Contracts\HandleResolver;
use SocialDept\AtpSupport\Resolvers\AtProtoHandleResolver;
use SocialDept\AtpSupport\Resolvers\DidResolverManager;

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

        // Register handle resolver
        $this->app->singleton(HandleResolver::class, function ($app) {
            return new AtProtoHandleResolver();
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
        return ['resolver', Resolver::class];
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
