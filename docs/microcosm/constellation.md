# Constellation

Part of the [atp-support docs](../introduction.md#documentation). Backlinks, finding the records that link to a subject, and counting them.

Constellation indexes backlinks: for a subject, such as an AT-URI, a DID or a URL, it finds the records that link to it.

```php
use SocialDept\AtpSupport\Microcosm\ConstellationClient;

$constellation = app(ConstellationClient::class);

// Find the likes on a post
$backlinks = $constellation->getBacklinks(
    subject: 'at://did:plc:z72i7hdynmk6r22z27h6tvur/app.bsky.feed.post/3mcibiyf7fs2r',
    source: 'app.bsky.feed.like:subject.uri',
    limit: 50,
);

$backlinks->total;   // the number of backlinks
$backlinks->records; // BacklinkReference[]
$backlinks->cursor;  // the cursor for the next page, or null

// Just the count
$count = $constellation->getBacklinksCount(
    subject: 'at://did:plc:abc/app.bsky.feed.post/rk1',
    source: 'app.bsky.feed.like:subject.uri',
);

// A summary of every kind of link pointing at a target
$summary = $constellation->getAllLinks('at://did:plc:abc/app.bsky.feed.post/rk1');
$summary->total();                             // records across every collection and path
$summary->forCollection('app.bsky.feed.like'); // counts per path for one collection
```

The `source` parameter uses the `collection:path` format, where the path is the dot notation location of the linking field within the record.

## Methods

| Method | Returns |
| --- | --- |
| `getBacklinks($subject, $source, $dids = null, $limit = 16, $reverse = false, $cursor = null)` | `GetBacklinksResponse` |
| `getBacklinksCount($subject, $source)` | `int` |
| `getAllLinks($target)` | `LinkSummary` |
| `getManyToManyCounts($subject, $source, $pathToOther, $dids = null, $otherSubjects = null, $limit = 16)` | The service's `counts_by_other_subject` array |

`$dids` narrows the results to records by those accounts. `$reverse` reverses the order.

## Data objects

These live in `SocialDept\AtpSupport\Microcosm\Data`.

| Class | Holds |
| --- | --- |
| `GetBacklinksResponse` | `total`, `records` and `cursor` |
| `BacklinkReference` | `did`, `collection` and `rkey`, with `uri()` for the record's AT-URI |
| `LinkSummary` | `links`, keyed by collection and then path, each with `records` and `distinct_dids` counts, plus `forCollection()` and `total()` |

A failed request throws `MicrocosmException`. See [Errors](errors.md).
