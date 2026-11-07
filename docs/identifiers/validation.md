# Validating identities

Part of the [atp-support docs](../introduction.md#documentation). Checking DIDs and handles, and reading a DID's method.

The `Identity` class has static checks. They only look at the format of the string, and make no network calls:

```php
use SocialDept\AtpSupport\Identity;

Identity::isDid('did:plc:abc123');           // true
Identity::isDid('not-a-did');                // false

Identity::isHandle('alice.bsky.social');     // true
Identity::isHandle('invalid');               // false

Identity::isPlcDid('did:plc:abc123');        // true
Identity::isWebDid('did:web:example.com');   // true

Identity::extractDidMethod('did:plc:abc123'); // "plc"
```

| Method | Returns |
| --- | --- |
| `Identity::isDid($value)` | Whether the string is a well-formed DID. `null` is `false` |
| `Identity::isHandle($value)` | Whether the string is a well-formed handle, a domain name of at least two labels. `null` is `false` |
| `Identity::isPlcDid($did)` | Whether the DID uses the `plc` method |
| `Identity::isWebDid($did)` | Whether the DID uses the `web` method |
| `Identity::extractDidMethod($did)` | The method, such as `plc` or `web`, or `null` for a malformed DID |

To check NSIDs, see [NSIDs](nsids.md).
