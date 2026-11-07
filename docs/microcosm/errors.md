# Errors

Part of the [atp-support docs](../introduction.md#documentation). Telling a missing record apart from a service that is down.

Both clients throw `MicrocosmException`. When the service answered with an XRPC error, `error` names it and `status` is the HTTP status. A missing record throws the `RecordNotFoundException` subclass, so it can be told apart from a service that is down:

```php
use SocialDept\AtpSupport\Microcosm\MicrocosmException;
use SocialDept\AtpSupport\Microcosm\RecordNotFoundException;

try {
    $record = $slingshot->getRecord($did, 'app.bsky.actor.profile', 'self');
} catch (RecordNotFoundException) {
    $record = null;              // not there: safe to remember
} catch (MicrocosmException $e) {
    $e->isUnavailable();         // true without a response or on a 5xx: try again later
    $e->error;                   // such as 'InvalidRequest', or null
    $e->status;                  // the HTTP status, or null
}
```

`isUnavailable()` is also `true` when the service answered with a body that does not decode to a JSON object or array, since that exception carries no status.
