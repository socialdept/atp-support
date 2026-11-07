# Resolving identities

Part of the [atp-support docs](../introduction.md#documentation). The `Resolver` facade, from handles and DIDs to DID documents and PDS endpoints, and the exceptions it throws.

## The Resolver facade

The `Resolver` facade is the entry point for resolution:

```php
use SocialDept\AtpSupport\Facades\Resolver;

// Resolve a DID to its DID document
$doc = Resolver::resolveDid('did:plc:ewvi7nxzyoun6zhxrhs64oiz');
$doc->getPdsEndpoint(); // the PDS URL, or null
$doc->getHandle();      // the handle from alsoKnownAs, or null

// Resolve a handle to its DID
$did = Resolver::handleToDid('alice.bsky.social');

// Resolve a handle to its DID document
$doc = Resolver::resolveHandle('alice.bsky.social');

// Accept either a DID or a handle
$doc = Resolver::resolveIdentity('alice.bsky.social');

// Resolve a DID or a handle straight to its PDS endpoint
$pds = Resolver::resolvePds('alice.bsky.social');
```

| Method | Returns |
| --- | --- |
| `resolveDid($did, $useCache = true)` | `DidDocument` |
| `handleToDid($handle, $useCache = true)` | The DID as a string |
| `resolveHandle($handle, $useCache = true)` | `DidDocument` |
| `resolveIdentity($actor, $useCache = true)` | `DidDocument`, for a DID or a handle |
| `resolvePds($actor, $useCache = true)` | The PDS endpoint, or `null` when the DID document names none |

Every method caches what it finds. See [Caching](caching.md) for skipping and clearing it.

`resolveHandle()` and `resolveIdentity()` do not check that the DID document lists the handle back in `alsoKnownAs`. Compare `getHandle()` with the handle you started from when that matters.

## DID documents

`DidDocument` carries the parts of the document as readonly properties: `id`, `alsoKnownAs`, `verificationMethod`, `service` and `raw`, the whole document as resolved.

| Method | Returns |
| --- | --- |
| `getPdsEndpoint()` | The `serviceEndpoint` of the `AtprotoPersonalDataServer` service, or `null` |
| `getHandle()` | The first `at://` entry in `alsoKnownAs` without its prefix, or `null` |
| `getSigningKey($fragment = '#atproto')` | The signing key as a `did:key`, or `null`. See [Keys and signatures](../keys.md) |
| `toArray()` | The raw document |

`DidDocument::fromArray($data)` builds one from a decoded document.

## How handles resolve

`handleToDid()` first checks the handle's format, then tries the methods named in `handle_resolution.methods` in order: a DNS TXT record, the `.well-known` file, then the configured PDS. The first DID found wins. See [Handle resolution order](../getting-started/installation.md#handle-resolution-order) to change the order.

To try one method alone, without the cache, call it directly. Each returns the DID or `null`:

```php
Resolver::handleToDidViaDns('alice.bsky.social');
Resolver::handleToDidViaWellKnown('alice.bsky.social');
Resolver::handleToDidViaXrpc('alice.bsky.social');
```

## How DIDs resolve

`did:plc` identifiers are fetched from `plc_directory`. `did:web` identifiers are fetched from `https://{domain}/.well-known/did.json`, or from `https://{domain}/{path}/did.json` when the DID carries a path. Any other method throws, unless you [register a resolver](custom-resolvers.md) for it.

## Exceptions

| Exception | Thrown when |
| --- | --- |
| `ResolverException` | The base class of the three below |
| `DidResolutionException` | A DID is malformed, uses a method with no resolver, or its document cannot be fetched |
| `HandleResolutionException` | A handle is malformed, or no resolution method found its DID |
| `LexiconResolutionException` | A lexicon's authority cannot be read. See [Lexicon resolution](../lexicons.md) |

All four live in `SocialDept\AtpSupport\Exceptions`.

## See also

- [DID specification](https://www.w3.org/TR/did-1.0/)
- [AT Protocol documentation](https://atproto.com/)
