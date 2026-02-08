<?php

namespace SocialDept\AtpSupport\Microcosm\Data;

class GetBacklinksResponse
{
    /**
     * @param  array<BacklinkReference>  $records
     */
    public function __construct(
        public readonly int $total,
        public readonly array $records,
        public readonly ?string $cursor,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            total: $data['total'] ?? 0,
            records: array_map(
                fn (array $record) => BacklinkReference::fromArray($record),
                $data['records'] ?? [],
            ),
            cursor: $data['cursor'] ?? null,
        );
    }
}
