# Custom DID resolvers

Part of the [atp-support docs](../introduction.md#documentation). Resolving a DID method beyond `did:plc` and `did:web`.

A DID resolver implements `SocialDept\AtpSupport\Contracts\DidResolver`:

```php
use SocialDept\AtpSupport\Contracts\DidResolver;
use SocialDept\AtpSupport\Data\DidDocument;

class CustomDidResolver implements DidResolver
{
    public function resolve(string $did): DidDocument
    {
        // Your resolution logic
    }

    public function supports(string $method): bool
    {
        return $method === 'custom';
    }
}
```

The container binds `DidResolver` to a single `DidResolverManager`, the one the `Resolver` facade uses. Register your resolver on it for its method, in a service provider:

```php
use SocialDept\AtpSupport\Contracts\DidResolver;
use SocialDept\AtpSupport\Resolvers\DidResolverManager;

/** @var DidResolverManager $manager */
$manager = app(DidResolver::class);
$manager->register('custom', new CustomDidResolver());
```

Registering a method that already has a resolver, such as `plc` or `web`, replaces it.

> [!IMPORTANT]
> Ask the container for `DidResolver::class`, not `DidResolverManager::class`. The manager class itself is not bound, so `app(DidResolverManager::class)` builds a new manager the facade never uses, and the resolver registered on it is never called.
