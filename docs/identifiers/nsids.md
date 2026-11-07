# NSIDs

Part of the [atp-support docs](../introduction.md#documentation). Parsing, validating and converting namespace identifiers.

Namespace identifiers are the reversed domain names AT Protocol uses for collections and methods. `Nsid` is a value object for them:

```php
use SocialDept\AtpSupport\Nsid;

$nsid = Nsid::parse('app.bsky.feed.post');

$nsid->getAuthority();       // app.bsky.feed
$nsid->getName();            // post
$nsid->getSegments();        // ['app', 'bsky', 'feed', 'post']
$nsid->toDomain();           // post.feed.bsky.app
$nsid->getAuthorityDomain(); // feed.bsky.app
$nsid->lexiconDnsName();     // _lexicon.feed.bsky.app
$nsid->toString();           // app.bsky.feed.post

// Validation
Nsid::isValid('app.bsky.feed.post'); // true
Nsid::isValid('invalid');            // false

// Equality
$nsid->equals(Nsid::parse('app.bsky.feed.post')); // true
```

`Nsid::parse()` and `new Nsid()` throw `InvalidArgumentException` for an NSID that is empty, longer than 317 characters, not in reversed domain form, or shorter than three segments. `Nsid::isValid()` runs the same checks and returns a bool.

`lexiconDnsName()` is the DNS name whose TXT record names the repo publishing the NSID's lexicon. See [Lexicon resolution](../lexicons.md).

## See also

- [NSID specification](https://atproto.com/specs/nsid)
