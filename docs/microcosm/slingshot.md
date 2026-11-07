# Slingshot

Part of the [atp-support docs](../introduction.md#documentation). Fetching records and minimal identity documents from Slingshot's cache.

Slingshot is a cache of records and identities from across the network.

```php
use SocialDept\AtpSupport\Microcosm\SlingshotClient;

$slingshot = app(SlingshotClient::class);

// Fetch a record
$record = $slingshot->getRecord('did:plc:abc', 'app.bsky.feed.post', 'rk1');
$record->uri;   // the AT-URI
$record->cid;   // the CID
$record->value; // the record as an array

// Fetch by AT-URI
$record = $slingshot->getRecordByUri('at://did:plc:abc/app.bsky.feed.post/rk1');

// Resolve a DID or a handle to a minimal identity document
$doc = $slingshot->resolveMiniDoc('did:plc:z72i7hdynmk6r22z27h6tvur');
$doc->did;
$doc->handle;
$doc->pds;
$doc->signingKey;
```

## Methods

| Method | Returns |
| --- | --- |
| `getRecord($repo, $collection, $rkey, $cid = null)` | `GetRecordResponse` |
| `getRecordByUri($atUri, $cid = null)` | `GetRecordResponse` |
| `resolveMiniDoc($identifier)` | `MiniDoc` |

Pass `$cid` to match a specific CID.

## Data objects

These live in `SocialDept\AtpSupport\Microcosm\Data`.

| Class | Holds |
| --- | --- |
| `GetRecordResponse` | `uri`, `cid` and `value` |
| `MiniDoc` | `did`, `handle`, `pds` and `signingKey` |

A record that does not exist throws `RecordNotFoundException`, and any other failure `MicrocosmException`. See [Errors](errors.md).
