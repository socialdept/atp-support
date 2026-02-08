<?php

namespace SocialDept\AtpSupport\Microcosm\Data;

class BacklinkReference
{
    public function __construct(
        public readonly string $did,
        public readonly string $collection,
        public readonly string $rkey,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            did: $data['did'] ?? '',
            collection: $data['collection'] ?? '',
            rkey: $data['rkey'] ?? '',
        );
    }

    public function uri(): string
    {
        return "at://{$this->did}/{$this->collection}/{$this->rkey}";
    }
}
