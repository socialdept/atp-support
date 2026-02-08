<?php

namespace SocialDept\AtpSupport\Microcosm\Data;

class GetRecordResponse
{
    public function __construct(
        public readonly string $uri,
        public readonly string $cid,
        public readonly array $value,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uri: $data['uri'] ?? '',
            cid: $data['cid'] ?? '',
            value: $data['value'] ?? [],
        );
    }
}
