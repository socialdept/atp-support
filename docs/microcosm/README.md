# Microcosm

Part of the [atp-support docs](../introduction.md#documentation).

[Microcosm](https://microcosm.blue) runs protocol-level discovery services for AT Protocol. This package has HTTP clients for two of them. Both are in the container, and the `Microcosm` facade reaches either one:

```php
use SocialDept\AtpSupport\Facades\Microcosm;

Microcosm::constellation()->getBacklinks(...);
Microcosm::slingshot()->getRecord(...);
```

Their base URLs and request timeouts come from the `microcosm` section of the [config](../getting-started/installation.md#configuration):

```env
ATP_CONSTELLATION_URL=https://constellation.microcosm.blue
ATP_CONSTELLATION_TIMEOUT=10
ATP_SLINGSHOT_URL=https://slingshot.microcosm.blue
ATP_SLINGSHOT_TIMEOUT=5
```

The values shown are the defaults. Both clients connect within the shared `connect_timeout`.

- **[Constellation](constellation.md)**: Backlinks, finding the records that link to a subject, and counting them.
- **[Slingshot](slingshot.md)**: Fetching records and minimal identity documents from Slingshot's cache.
- **[Errors](errors.md)**: Telling a missing record apart from a service that is down.
