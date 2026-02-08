<?php

namespace SocialDept\AtpSupport\Microcosm\Data;

class LinkSummary
{
    /**
     * @param  array<string, array<string, array{records: int, distinct_dids: int}>>  $links
     */
    public function __construct(
        public readonly array $links,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            links: $data['links'] ?? [],
        );
    }

    /**
     * Filter links to a specific collection.
     *
     * @return array<string, array{records: int, distinct_dids: int}>
     */
    public function forCollection(string $collection): array
    {
        return $this->links[$collection] ?? [];
    }

    /**
     * Sum all record counts across all collections and paths.
     */
    public function total(): int
    {
        $total = 0;

        foreach ($this->links as $paths) {
            foreach ($paths as $counts) {
                $total += $counts['records'] ?? 0;
            }
        }

        return $total;
    }
}
