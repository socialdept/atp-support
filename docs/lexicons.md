# Lexicon resolution

Part of the [atp-support docs](introduction.md#documentation). Finding a lexicon's schema through DNS and its authority's repo.

A lexicon's authority publishes a TXT record at `_lexicon.{authority domain}` holding `did=<DID>`, and the schema as a `com.atproto.lexicon.schema` record in that DID's repo, keyed by the NSID. `LexiconDnsResolver` follows both steps:

```php
use SocialDept\AtpSupport\Resolvers\LexiconDnsResolver;

$resolver = app(LexiconDnsResolver::class);

// The schema with its record. Null when the authority names no DID
// in DNS, or has not published one.
$published = $resolver->fetch('com.example.myrecord');

// What a given repo publishes, before or regardless of DNS
$published = $resolver->fetchFrom('did:plc:abc', 'com.example.myrecord');

// The record value as an array, or a RuntimeException
$schema = $resolver->resolve('com.example.myrecord');

// Individual steps
$did = $resolver->lookupDns('com.example');   // reads _lexicon.example.com
$schema = $resolver->retrieveSchema($pdsEndpoint, $did, 'com.example.myrecord');
```

`fetch()` and `fetchFrom()` return a `PublishedLexicon` with readonly `nsid`, `did`, `uri`, `cid` and `schema`. The `schema` is the lexicon without the record's `$type`, and `cid` is `null` when the PDS sent none.

## Missing or unreachable

`fetch()` and `fetchFrom()` return `null` for a lexicon that is not published, and throw `LexiconResolutionException` when the authority cannot be read: its DID names no PDS, the PDS does not answer, or it returns something that is not a schema. Keep the two apart before acting on a missing schema.

Two other failures pass through unchanged: an NSID that is not valid throws `InvalidArgumentException`, and a DID whose document cannot be resolved at all throws `DidResolutionException`.

`resolve()` and `retrieveSchema()` are older and throw a `RuntimeException` for both cases. `resolve()` returns the record value with its `$type`.

## Resolution is not hierarchical

Each authority needs its own TXT record, so `com.example.feed.post` is looked up at `_lexicon.feed.example.com` and is not covered by the record for `com.example`. [`Nsid::lexiconDnsName()`](identifiers/nsids.md) gives the name to look up for any NSID.

## Construction

The container builds the resolver with the shared `Resolver`, a 10 second request timeout and a 2 second connect timeout. Construct it yourself to change them, or to replace `dns_get_record` in tests:

```php
use SocialDept\AtpSupport\Resolver;

$resolver = new LexiconDnsResolver(
    resolver: app(Resolver::class),
    httpTimeout: 5,
    connectTimeout: 1,
    dnsLookup: fn (string $hostname, int $type) => [['txt' => 'did=did:plc:abc']],
);
```

The lookup callable takes a hostname and a DNS record type, and returns an array of records or `false`, as `dns_get_record` does.
