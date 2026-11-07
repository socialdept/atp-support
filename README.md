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

This Laravel package holds the shared AT Protocol plumbing that atp-client, atp-schema, atp-appview and other `atp-*` packages build on. It resolves DIDs and handles to DID documents and PDS endpoints with caching, validates DIDs, handles and NSIDs, parses AT-URIs, finds lexicons published through DNS, checks signatures against published keys, and talks to the Microcosm services Constellation and Slingshot. To learn all about it, head over to [the documentation](https://socialde.pt/docs/atp-support).

Here are a few short examples of what you can do.

Resolve a handle to a DID, or any actor to its PDS:

```php
use SocialDept\AtpSupport\Facades\Resolver;

$did = Resolver::handleToDid('alice.bsky.social');
$pds = Resolver::resolvePds('alice.bsky.social');
```

Validate identifiers:

```php
use SocialDept\AtpSupport\Identity;
use SocialDept\AtpSupport\Nsid;

Identity::isDid('did:plc:abc123');        // true
Identity::isHandle('alice.bsky.social');  // true
Nsid::isValid('app.bsky.feed.post');      // true
```

Parse an AT-URI:

```php
use SocialDept\AtpSupport\AtUri;

$uri = AtUri::parse('at://did:plc:xyz/app.bsky.feed.post/3k4abc');

$uri->collection; // app.bsky.feed.post
$uri->rkey;       // 3k4abc
```

Count the likes on a post through Constellation:

```php
use SocialDept\AtpSupport\Facades\Microcosm;

$likes = Microcosm::constellation()->getBacklinksCount(
    subject: 'at://did:plc:abc/app.bsky.feed.post/rk1',
    source: 'app.bsky.feed.like:subject.uri',
);
```

## Documentation

You'll find the documentation on [https://socialde.pt/docs/atp-support](https://socialde.pt/docs/atp-support).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving it? Feel free to [create an issue on GitHub](https://github.com/socialdept/atp-support/issues).

## Testing

```bash
vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
