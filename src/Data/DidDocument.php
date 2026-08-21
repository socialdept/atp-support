<?php

namespace SocialDept\AtpSupport\Data;

class DidDocument
{
    /**
     * Create a new DID Document instance.
     *
     * @param  string  $id  The DID (e.g., "did:plc:abc123")
     * @param  array  $alsoKnownAs  Alternative identifiers (handles)
     * @param  array  $verificationMethod  Verification methods (keys)
     * @param  array  $service  Service endpoints (PDS, etc.)
     * @param  array  $raw  The raw DID document
     */
    public function __construct(
        public readonly string $id,
        public readonly array $alsoKnownAs = [],
        public readonly array $verificationMethod = [],
        public readonly array $service = [],
        public readonly array $raw = [],
    ) {
    }

    /**
     * Create a DID Document from a raw array.
     *
     * @param  array  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            alsoKnownAs: $data['alsoKnownAs'] ?? [],
            verificationMethod: $data['verificationMethod'] ?? [],
            service: $data['service'] ?? [],
            raw: $data,
        );
    }

    /**
     * Get the PDS (Personal Data Server) endpoint.
     */
    public function getPdsEndpoint(): ?string
    {
        foreach ($this->service as $service) {
            if (($service['type'] ?? '') === 'AtprotoPersonalDataServer') {
                return $service['serviceEndpoint'] ?? null;
            }
        }

        return null;
    }

    /**
     * Get a published signing key as a `did:key`, by verification-method fragment.
     *
     * Defaults to `#atproto`, the repo signing key every account publishes and
     * the one that signs service auth, space commits, and space tokens. Ids are
     * written both bare (`#atproto`) and fully qualified (`did:plc:x#atproto`)
     * in the wild, so either matches.
     */
    public function getSigningKey(string $fragment = '#atproto'): ?string
    {
        foreach ($this->verificationMethod as $method) {
            $id = $method['id'] ?? null;

            if (! is_string($id) || ($id !== $fragment && ! str_ends_with($id, $fragment))) {
                continue;
            }

            $multibase = $method['publicKeyMultibase'] ?? null;

            if (is_string($multibase) && $multibase !== '') {
                return 'did:key:'.$multibase;
            }
        }

        return null;
    }

    /**
     * Get the handle from alsoKnownAs.
     */
    public function getHandle(): ?string
    {
        foreach ($this->alsoKnownAs as $alias) {
            if (str_starts_with($alias, 'at://')) {
                return substr($alias, 5);
            }
        }

        return null;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return $this->raw;
    }
}
