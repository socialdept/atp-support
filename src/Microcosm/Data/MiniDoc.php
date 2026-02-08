<?php

namespace SocialDept\AtpSupport\Microcosm\Data;

class MiniDoc
{
    public function __construct(
        public readonly string $did,
        public readonly string $handle,
        public readonly string $pds,
        public readonly string $signingKey,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            did: $data['did'] ?? '',
            handle: $data['handle'] ?? '',
            pds: $data['pds'] ?? '',
            signingKey: $data['signing_key'] ?? '',
        );
    }
}
