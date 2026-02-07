<h1 align="center">ATP Support</h1>

<h3 align="center">
    Resolve DIDs, handles, and identities for AT Protocol in Laravel.
</h3>

<p align="center">
    <br>
    <a href="https://packagist.org/packages/socialdept/atp-support" title="Latest Version on Packagist"><img src="https://img.shields.io/packagist/v/socialdept/atp-support.svg?style=flat-square"></a>
    <a href="https://packagist.org/packages/socialdept/atp-support" title="Total Downloads"><img src="https://img.shields.io/packagist/dt/socialdept/atp-support.svg?style=flat-square"></a>
    <a href="https://github.com/socialdept/atp-support/actions/workflows/tests.yml" title="GitHub Tests Action Status"><img src="https://img.shields.io/github/actions/workflow/status/socialdept/atp-support/tests.yml?branch=main&label=tests&style=flat-square"></a>
    <a href="LICENSE" title="Software License"><img src="https://img.shields.io/github/license/socialdept/atp-support?style=flat-square"></a>
</p>

---

## What is ATP Support?

**ATP Support** is the foundational Laravel package for the SocialDept AT Protocol ecosystem. It provides DID and handle resolution, identity validation, AT-URI parsing, NSID utilities, and shared configuration used by all other `atp-*` packages.

If you're building anything on AT Protocol with Laravel, this is your starting point.

## Why use ATP Support?

- **Identity resolution** - Resolve DIDs, handles, and PDS endpoints with intelligent caching
- **Validation utilities** - Validate DIDs, handles, and NSIDs with battle-tested logic
- **AT-URI parsing** - Parse and create `at://` URIs as immutable value objects
- **Shared configuration** - Common AT Protocol settings (PLC directory, PDS endpoint, public API) in one place
- **Extensible resolvers** - Pluggable DID method resolvers with support for `did:plc` and `did:web`
- **DNS lexicon resolution** - Discover lexicon schemas via DNS TXT records

## Quick Example

```php
use SocialDept\AtpSupport\Facades\Resolver;
use SocialDept\AtpSupport\Identity;
use SocialDept\AtpSupport\AtUri;
use SocialDept\AtpSupport\Nsid;

// Resolve a handle to a DID
$did = Resolver::handleToDid('alice.bsky.social');

// Resolve a DID to its PDS endpoint
$pds = Resolver::resolvePds('did:plc:ewvi7nxzyoun6zhxrhs64oiz');

// Validate identities
Identity::isDid('did:plc:abc123');          // true
Identity::isHandle('alice.bsky.social');     // true

// Parse AT-URIs
$uri = AtUri::parse('at://did:plc:xyz/app.bsky.feed.post/3k4abc');
$uri->did;        // did:plc:xyz
$uri->collection; // app.bsky.feed.post
$uri->rkey;       // 3k4abc

// Work with NSIDs
$nsid = Nsid::parse('app.bsky.feed.post');
$nsid->getAuthority(); // app.bsky.feed
$nsid->getName();      // post
```

## Installation

```bash
composer require socialdept/atp-support
```

Publish the configuration:

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
    'timeout' => env('ATP_RESOLVER_TIMEOUT', 10),
    'cache' => [
        'enabled' => env('ATP_RESOLVER_CACHE_ENABLED', true),
        'did_ttl' => env('ATP_RESOLVER_CACHE_DID_TTL', 3600),
        'handle_ttl' => env('ATP_RESOLVER_CACHE_HANDLE_TTL', 3600),
        'pds_ttl' => env('ATP_RESOLVER_CACHE_PDS_TTL', 3600),
    ],
];
```

Other `atp-*` packages read shared values like `public_api` directly from this config.

## Usage

### Resolving Identities

The `Resolver` facade is the main entry point for all resolution operations:

```php
use SocialDept\AtpSupport\Facades\Resolver;

// Resolve a DID to its DID Document
$doc = Resolver::resolveDid('did:plc:ewvi7nxzyoun6zhxrhs64oiz');
$doc->getPdsEndpoint(); // https://morel.us-east.host.bsky.network
$doc->getHandle();      // alice.bsky.social

// Convert a handle to a DID
$did = Resolver::handleToDid('alice.bsky.social');

// Auto-detect and resolve (accepts both DIDs and handles)
$doc = Resolver::resolveIdentity('alice.bsky.social');

// Resolve directly to PDS endpoint
$pds = Resolver::resolvePds('alice.bsky.social');
```

### Caching

All resolutions are cached by default with configurable TTLs. You can bypass or clear the cache:

```php
// Bypass cache for a single call
$doc = Resolver::resolveDid('did:plc:abc123', useCache: false);

// Clear specific caches
Resolver::clearDidCache('did:plc:abc123');
Resolver::clearHandleCache('alice.bsky.social');
Resolver::clearPdsCache('did:plc:abc123');

// Clear everything
Resolver::clearCache();
```

### Validating Identities

The `Identity` class provides static validation methods:

```php
use SocialDept\AtpSupport\Identity;

Identity::isDid('did:plc:abc123');     // true
Identity::isDid('not-a-did');          // false

Identity::isHandle('alice.bsky.social'); // true
Identity::isHandle('invalid');           // false

Identity::isPlcDid('did:plc:abc123');  // true
Identity::isWebDid('did:web:example.com'); // true

Identity::extractDidMethod('did:plc:abc123'); // "plc"
```

### Parsing AT-URIs

The `AtUri` value object parses the `at://` URI format used throughout AT Protocol:

```php
use SocialDept\AtpSupport\AtUri;

$uri = AtUri::parse('at://did:plc:xyz/app.bsky.feed.post/3k4abc');

$uri->did;        // did:plc:xyz
$uri->collection; // app.bsky.feed.post
$uri->rkey;       // 3k4abc

// Create programmatically
$uri = AtUri::make('did:plc:xyz', 'app.bsky.feed.post', '3k4abc');
echo $uri; // at://did:plc:xyz/app.bsky.feed.post/3k4abc

// Returns null for invalid URIs
AtUri::parse('not-a-uri'); // null
```

### Working with NSIDs

Namespace Identifiers are the reversed-domain notation used for AT Protocol collections and methods:

```php
use SocialDept\AtpSupport\Nsid;

$nsid = Nsid::parse('app.bsky.feed.post');

$nsid->getAuthority();       // app.bsky.feed
$nsid->getName();            // post
$nsid->getSegments();        // ['app', 'bsky', 'feed', 'post']
$nsid->toDomain();           // post.feed.bsky.app
$nsid->getAuthorityDomain(); // feed.bsky.app

// Validation
Nsid::isValid('app.bsky.feed.post');  // true
Nsid::isValid('invalid');             // false

// Equality
$nsid->equals(Nsid::parse('app.bsky.feed.post')); // true
```

### DNS Lexicon Resolution

Discover lexicon schemas published via DNS TXT records:

```php
use SocialDept\AtpSupport\Resolvers\LexiconDnsResolver;

$resolver = app(LexiconDnsResolver::class);

// Full pipeline: DNS lookup -> DID resolution -> XRPC fetch
$schema = $resolver->resolve('com.example.myrecord');

// Individual steps
$did = $resolver->lookupDns('example.com');
$schema = $resolver->retrieveSchema($pdsEndpoint, $did, 'com.example.myrecord');
```

### Custom DID Resolvers

Register custom resolvers for additional DID methods:

```php
use SocialDept\AtpSupport\Contracts\DidResolver;
use SocialDept\AtpSupport\Data\DidDocument;

class CustomDidResolver implements DidResolver
{
    public function resolve(string $did): DidDocument
    {
        // Your resolution logic
    }

    public function supports(string $method): bool
    {
        return $method === 'custom';
    }
}

// Register in a service provider
$manager = app(DidResolverManager::class);
$manager->register('custom', new CustomDidResolver());
```

## API Reference

### Facade Methods

| Method | Description |
|--------|-------------|
| `Resolver::resolveDid($did)` | Resolve DID to DidDocument |
| `Resolver::handleToDid($handle)` | Convert handle to DID string |
| `Resolver::resolveHandle($handle)` | Resolve handle to DidDocument |
| `Resolver::resolveIdentity($actor)` | Auto-detect and resolve DID or handle |
| `Resolver::resolvePds($actor)` | Get PDS endpoint for DID or handle |
| `Resolver::clearDidCache($did)` | Clear cached DID data |
| `Resolver::clearHandleCache($handle)` | Clear cached handle data |
| `Resolver::clearPdsCache($actor)` | Clear cached PDS data |
| `Resolver::clearCache()` | Clear all cached data |

### Value Objects

| Class | Description |
|-------|-------------|
| `AtUri` | Immutable AT-URI parser (`at://did/collection/rkey`) |
| `Nsid` | Immutable Namespace Identifier |
| `DidDocument` | Resolved DID Document with PDS and handle access |

### Validation

| Method | Description |
|--------|-------------|
| `Identity::isDid($value)` | Validate DID format |
| `Identity::isHandle($value)` | Validate handle format |
| `Identity::isPlcDid($did)` | Check for `did:plc` method |
| `Identity::isWebDid($did)` | Check for `did:web` method |
| `Identity::extractDidMethod($did)` | Get method from DID string |
| `Nsid::isValid($nsid)` | Validate NSID format |

### Exceptions

| Exception | Description |
|-----------|-------------|
| `ResolverException` | Base exception for all resolution errors |
| `DidResolutionException` | DID resolution failures |
| `HandleResolutionException` | Handle resolution failures |

## Requirements

- PHP 8.2+
- Laravel 11+

## Resources

- [AT Protocol Documentation](https://atproto.com/)
- [DID Specification](https://www.w3.org/TR/did-1.0/)
- [AT-URI Specification](https://atproto.com/specs/at-uri-scheme)
- [NSID Specification](https://atproto.com/specs/nsid)

## Support & Contributing

Found a bug or have a feature request? [Open an issue](https://github.com/socialdept/atp-support/issues).

Want to contribute? We'd love your help! Check out the [contribution guidelines](CONTRIBUTING.md).

## Credits

- [Miguel Batres](https://batres.co) - founder & lead maintainer
- [All contributors](https://github.com/socialdept/atp-support/graphs/contributors)

## License

ATP Support is open-source software licensed under the [MIT license](LICENSE).

---

**Built for the Atmosphere** • By Social Dept.
