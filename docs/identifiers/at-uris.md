# AT-URIs

Part of the [atp-support docs](../introduction.md#documentation). Parsing and building `at://` URIs.

`AtUri` is an immutable value object for the `at://` URIs used throughout AT Protocol:

```php
use SocialDept\AtpSupport\AtUri;

$uri = AtUri::parse('at://did:plc:xyz/app.bsky.feed.post/3k4abc');

$uri->did;        // did:plc:xyz
$uri->collection; // app.bsky.feed.post
$uri->rkey;       // 3k4abc

// Build one
$uri = AtUri::make('did:plc:xyz', 'app.bsky.feed.post', '3k4abc');
echo $uri; // at://did:plc:xyz/app.bsky.feed.post/3k4abc

// Invalid URIs give null
AtUri::parse('not-a-uri'); // null
```

`parse()` checks only the shape of the URI. The first segment lands in `did` whatever it is, so a URI written with a handle in place of a DID parses with the handle there. Check it with [`Identity::isDid()`](validation.md) when that matters.

## Partial URIs

By default `parse()` accepts only a full record URI, with a collection and a record key. Pass `partial: true` to also accept a repo or a collection:

```php
$repo = AtUri::parse('at://did:plc:xyz', partial: true);
$repo->isIdentity();   // true

$collection = AtUri::parse('at://did:plc:xyz/app.bsky.feed.post', partial: true);
$collection->isCollection(); // true

$uri->isRecord();      // true, for a full record URI
```

`AtUri::make()` takes the collection and record key as optional too, and the string form leaves out whatever is missing.

## See also

- [AT-URI specification](https://atproto.com/specs/at-uri-scheme)
