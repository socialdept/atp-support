<?php

namespace SocialDept\AtpSupport\Contracts;

interface HandleResolutionMethod
{
    /**
     * Attempt to resolve a handle to a DID.
     *
     * Returns the DID string on success, or null on failure.
     * Must never throw exceptions — all errors are silently caught.
     *
     * @param  string  $handle  The handle to resolve (e.g., "user.bsky.social")
     * @return string|null The resolved DID, or null if resolution failed
     */
    public function attempt(string $handle): ?string;
}
