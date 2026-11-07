# Installation

Part of the [atp-support docs](../introduction.md#documentation). Installing the package, its config file, timeouts and handle resolution order.

## Installing

```bash
composer require socialdept/atp-support
```

The service provider and the `Resolver` facade are discovered automatically. Publish the config file when you want to change it:

```bash
php artisan vendor:publish --tag=atp-support-config
```

## Configuration

All shared AT Protocol settings live in `config/atp-support.php`:

```php
return [
    'plc_directory' => env('ATP_PLC_DIRECTORY', 'https://plc.directory'),
    'pds_endpoint' => env('ATP_PDS_ENDPOINT', 'https://bsky.social'),
    'public_api' => env('ATP_PUBLIC_API', 'https://public.api.bsky.app'),
    'timeout' => env('ATP_RESOLVER_TIMEOUT', 5),
    'connect_timeout' => env('ATP_RESOLVER_CONNECT_TIMEOUT', 2),
    'microcosm' => [
        'constellation' => [
            'url' => env('ATP_CONSTELLATION_URL', 'https://constellation.microcosm.blue'),
            'timeout' => env('ATP_CONSTELLATION_TIMEOUT', 10),
        ],
        'slingshot' => [
            'url' => env('ATP_SLINGSHOT_URL', 'https://slingshot.microcosm.blue'),
            'timeout' => env('ATP_SLINGSHOT_TIMEOUT', 5),
        ],
    ],
    'handle_resolution' => [
        'methods' => ['dns', 'well-known', 'xrpc'],
    ],
    'cache' => [
        'enabled' => env('ATP_RESOLVER_CACHE_ENABLED', true),
        'did_ttl' => env('ATP_RESOLVER_CACHE_DID_TTL', 3600),
        'handle_ttl' => env('ATP_RESOLVER_CACHE_HANDLE_TTL', 3600),
        'pds_ttl' => env('ATP_RESOLVER_CACHE_PDS_TTL', 3600),
    ],
];
```

- `plc_directory` is where `did:plc` identifiers are resolved.
- `pds_endpoint` is the server the `xrpc` handle resolution method asks.
- `public_api` is not used by this package. Other `atp-*` packages read it from this config.
- `microcosm` sets the [Microcosm](../microcosm/README.md) base URLs and request timeouts.
- `cache` sets how long resolved DID documents, handles and PDS endpoints are kept, in seconds. See [Caching](../identity/caching.md).

### Timeouts

Resolution often runs while a user waits, for example verifying a service auth token or starting a login. `timeout` bounds a whole request and `connect_timeout` bounds connecting, so an unreachable host fails in two seconds rather than holding the request. Both apply to every DID and handle resolver. The Microcosm clients use `connect_timeout` too, with their own `timeout` from the `microcosm` section. The [lexicon resolver](../lexicons.md) takes its timeouts as constructor arguments instead, 10 seconds for the request and 2 to connect by default.

### Handle resolution order

`handle_resolution.methods` lists the ways a handle is resolved to a DID, tried in order until one answers:

- `dns` reads the `_atproto.{handle}` TXT record for a `did=` value.
- `well-known` fetches `https://{handle}/.well-known/atproto-did`.
- `xrpc` calls `com.atproto.identity.resolveHandle` on `pds_endpoint`.

Names other than these three are ignored. Remove a method to skip it, for example `['dns', 'well-known']` to never ask a PDS.
