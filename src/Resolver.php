<?php

namespace SocialDept\AtpSupport;

use SocialDept\AtpSupport\Concerns\HasConfig;
use SocialDept\AtpSupport\Contracts\CacheStore;
use SocialDept\AtpSupport\Contracts\DidResolver;
use SocialDept\AtpSupport\Contracts\HandleResolver;
use SocialDept\AtpSupport\Data\DidDocument;
use SocialDept\AtpSupport\Exceptions\DidResolutionException;
use SocialDept\AtpSupport\Exceptions\HandleResolutionException;
use SocialDept\AtpSupport\Resolvers\AtProtoHandleResolver;
use SocialDept\AtpSupport\Resolvers\DnsHandleResolver;
use SocialDept\AtpSupport\Resolvers\WellKnownHandleResolver;

class Resolver
{
    use HasConfig;

    /**
     * Create a new Resolver instance.
     */
    public function __construct(
        protected DidResolver $didResolver,
        protected HandleResolver $handleResolver,
        protected CacheStore $cache
    ) {
    }

    /**
     * Resolve a DID to a DID Document.
     *
     * @param string $did
     * @param bool $useCache
     * @return DidDocument
     * @throws DidResolutionException
     */
    public function resolveDid(string $did, bool $useCache = true): DidDocument
    {
        $cacheKey = "did:{$did}";

        if ($useCache && $this->cache->has($cacheKey)) {
            $cached = $this->cache->get($cacheKey);

            if ($cached instanceof DidDocument) {
                return $cached;
            }
        }

        $document = $this->didResolver->resolve($did);

        if ($useCache) {
            $ttl = $this->getConfig('atp-support.cache.did_ttl', 3600);
            $this->cache->put($cacheKey, $document, $ttl);
        }

        return $document;
    }

    /**
     * Convert a handle to its DID.
     *
     * @param string $handle
     * @param bool $useCache
     * @return string
     * @throws HandleResolutionException
     */
    public function handleToDid(string $handle, bool $useCache = true): string
    {
        $cacheKey = "handle:{$handle}";

        if ($useCache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $did = $this->handleResolver->resolve($handle);

        if ($useCache) {
            $ttl = $this->getConfig('atp-support.cache.handle_ttl', 3600);
            $this->cache->put($cacheKey, $did, $ttl);
        }

        return $did;
    }

    /**
     * Resolve a handle to its DID Document.
     *
     * @param string $handle
     * @param bool $useCache
     * @return DidDocument
     * @throws DidResolutionException
     * @throws HandleResolutionException
     */
    public function resolveHandle(string $handle, bool $useCache = true): DidDocument
    {
        $did = $this->handleToDid($handle, $useCache);

        return $this->resolveDid($did, $useCache);
    }

    /**
     * Resolve an identity (DID or handle) to its DID Document.
     *
     * @param string $actor A DID or handle
     * @param bool $useCache
     * @return DidDocument
     * @throws DidResolutionException
     * @throws HandleResolutionException
     */
    public function resolveIdentity(string $actor, bool $useCache = true): DidDocument
    {
        return Identity::isDid($actor)
            ? $this->resolveDid($actor, $useCache)
            : $this->resolveHandle($actor, $useCache);
    }

    /**
     * Clear cached data for a DID.
     *
     * @param  string  $did
     */
    public function clearDidCache(string $did): void
    {
        $this->cache->forget("did:{$did}");
    }

    /**
     * Clear cached data for a handle.
     *
     * @param  string  $handle
     */
    public function clearHandleCache(string $handle): void
    {
        $this->cache->forget("handle:{$handle}");
    }

    /**
     * Clear all cached data.
     */
    public function clearCache(): void
    {
        $this->cache->flush();
    }

    /**
     * Resolve a DID or handle to its PDS endpoint.
     *
     * @param string $actor A DID (e.g., "did:plc:abc123") or handle (e.g., "user.bsky.social")
     * @param bool $useCache
     * @return string|null The PDS endpoint URL or null if not found
     * @throws DidResolutionException
     * @throws HandleResolutionException
     */
    public function resolvePds(string $actor, bool $useCache = true): ?string
    {
        $cacheKey = "pds:{$actor}";

        if ($useCache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        // Determine if input is a DID or handle
        $document = $this->resolveIdentity($actor, $useCache);

        $pdsEndpoint = $document->getPdsEndpoint();

        if ($useCache && $pdsEndpoint !== null) {
            $ttl = $this->getConfig('atp-support.cache.pds_ttl', 3600);
            $this->cache->put($cacheKey, $pdsEndpoint, $ttl);
        }

        return $pdsEndpoint;
    }

    /**
     * Clear cached PDS endpoint for a DID or handle.
     *
     * @param  string  $actor
     */
    public function clearPdsCache(string $actor): void
    {
        $this->cache->forget("pds:{$actor}");
    }

    /**
     * Resolve a handle to a DID via DNS TXT record lookup.
     *
     * Queries the _atproto.{handle} TXT record for a did= value.
     * Returns null if no valid record is found.
     */
    public function handleToDidViaDns(string $handle): ?string
    {
        return app(DnsHandleResolver::class)->attempt($handle);
    }

    /**
     * Resolve a handle to a DID via HTTP .well-known lookup.
     *
     * Fetches https://{handle}/.well-known/atproto-did for a plaintext DID.
     * Returns null if the request fails or the response is not a valid DID.
     */
    public function handleToDidViaWellKnown(string $handle): ?string
    {
        return app(WellKnownHandleResolver::class)->attempt($handle);
    }

    /**
     * Resolve a handle to a DID via XRPC call to the PDS endpoint.
     *
     * Calls com.atproto.identity.resolveHandle on the configured PDS.
     * Returns null if the request fails.
     */
    public function handleToDidViaXrpc(string $handle): ?string
    {
        return app(AtProtoHandleResolver::class)->attempt($handle);
    }
}
