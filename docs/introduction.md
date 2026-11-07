# Introduction

**ATP Support** holds the shared AT Protocol plumbing that atp-client, atp-schema, atp-appview and other `atp-*` packages build on:

- **Identity resolution:** resolve DIDs, handles and PDS endpoints, with results cached in your Laravel cache.
- **Validation:** check DIDs, handles and NSIDs before you use them.
- **AT-URIs and NSIDs:** parse and build `at://` URIs and namespace identifiers as value objects.
- **Shared configuration:** the PLC directory, the PDS endpoint, the public API and resolver timeouts, in one config file the other packages read.
- **Extensible resolvers:** `did:plc` and `did:web` out of the box, and a resolver of your own for any other DID method.
- **Lexicon resolution:** find a lexicon's schema through its authority's `_lexicon` DNS record.
- **Keys and signatures:** parse `did:key` values and verify signatures the way the protocol requires.
- **Microcosm:** clients for Constellation, which indexes backlinks, and Slingshot, which caches records and identities.

> [!NOTE]
> The resolver here replaces [atp-resolver](https://github.com/socialdept/atp-resolver), which is deprecated and archived. Use this package for DID and handle resolution in new code.

## A first look

The `Resolver` facade turns a handle or a DID into a DID document, a DID or a PDS endpoint:

```php
use SocialDept\AtpSupport\Facades\Resolver;

$did = Resolver::handleToDid('alice.bsky.social');
$doc = Resolver::resolveIdentity('alice.bsky.social');

$doc->getPdsEndpoint();
$doc->getHandle();
```

Identifiers are plain static checks and value objects:

```php
use SocialDept\AtpSupport\AtUri;
use SocialDept\AtpSupport\Identity;
use SocialDept\AtpSupport\Nsid;

Identity::isHandle('alice.bsky.social'); // true

$uri = AtUri::parse('at://did:plc:xyz/app.bsky.feed.post/3k4abc');
$uri->rkey; // 3k4abc

Nsid::parse('app.bsky.feed.post')->getAuthority(); // app.bsky.feed
```

## Documentation

Start with [Installation](getting-started/installation.md), then [Resolving identities](identity/resolving.md). The docs are grouped by area, and each group has an index of its own:

- **[Getting started](getting-started/README.md)**: [Installation](getting-started/installation.md)
- **[Identity](identity/README.md)**: [Resolving identities](identity/resolving.md), [Caching](identity/caching.md), [Custom DID resolvers](identity/custom-resolvers.md)
- **[Identifiers](identifiers/README.md)**: [Validating identities](identifiers/validation.md), [AT-URIs](identifiers/at-uris.md), [NSIDs](identifiers/nsids.md)
- **[Lexicon resolution](lexicons.md)**: Finding a lexicon's schema through DNS and its authority's repo.
- **[Keys and signatures](keys.md)**: Parsing `did:key` values, reading a DID document's signing key, and verifying signatures.
- **[Microcosm](microcosm/README.md)**: [Constellation](microcosm/constellation.md), [Slingshot](microcosm/slingshot.md), [Errors](microcosm/errors.md)
