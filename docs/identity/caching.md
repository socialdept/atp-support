# Caching

Part of the [atp-support docs](../introduction.md#documentation). How resolutions are cached, skipping the cache for one call, and clearing it.

Resolutions are kept in your application's default cache store. Each kind has its own lifetime in seconds, set in `config/atp-support.php`:

| Key | Env var | Default | Holds |
| --- | --- | --- | --- |
| `cache.did_ttl` | `ATP_RESOLVER_CACHE_DID_TTL` | `3600` | DID documents |
| `cache.handle_ttl` | `ATP_RESOLVER_CACHE_HANDLE_TTL` | `3600` | The DID a handle resolved to |
| `cache.pds_ttl` | `ATP_RESOLVER_CACHE_PDS_TTL` | `3600` | PDS endpoints |

A PDS endpoint is cached under the string you passed, so a lookup by handle and one by DID are separate entries. A DID document that names no PDS is not cached as a PDS endpoint.

> [!NOTE]
> The config also has `cache.enabled` (`ATP_RESOLVER_CACHE_ENABLED`), but the resolver does not read it. Setting it to `false` does not turn caching off. Pass `useCache: false` instead.

## Skipping the cache

Every resolver method takes `useCache`. With `false` it neither reads nor writes the cache:

```php
use SocialDept\AtpSupport\Facades\Resolver;

$doc = Resolver::resolveDid('did:plc:abc123', useCache: false);
```

## Clearing the cache

```php
Resolver::clearDidCache('did:plc:abc123');
Resolver::clearHandleCache('alice.bsky.social');
Resolver::clearPdsCache('did:plc:abc123');
```

> [!WARNING]
> `Resolver::clearCache()` flushes the whole default cache store, not only the resolver's entries. Everything else your application keeps in that store goes with it. Prefer the three methods above.
