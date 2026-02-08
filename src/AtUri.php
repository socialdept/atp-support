<?php

namespace SocialDept\AtpSupport;

use Stringable;

class AtUri implements Stringable
{
    public function __construct(
        public readonly string $did,
        public readonly ?string $collection = null,
        public readonly ?string $rkey = null,
    ) {
    }

    public static function parse(string $uri, bool $partial = false): ?static
    {
        if (preg_match('#^at://([^/]+)/([^/]+)/([^/]+)$#', $uri, $matches)) {
            return new static($matches[1], $matches[2], $matches[3]);
        }

        if (! $partial) {
            return null;
        }

        if (preg_match('#^at://([^/]+)/([^/]+)$#', $uri, $matches)) {
            return new static($matches[1], $matches[2]);
        }

        if (preg_match('#^at://([^/]+?)/?$#', $uri, $matches)) {
            return new static($matches[1]);
        }

        return null;
    }

    public static function make(string $did, ?string $collection = null, ?string $rkey = null): static
    {
        return new static($did, $collection, $rkey);
    }

    public function isRecord(): bool
    {
        return $this->collection !== null && $this->rkey !== null;
    }

    public function isCollection(): bool
    {
        return $this->collection !== null && $this->rkey === null;
    }

    public function isIdentity(): bool
    {
        return $this->collection === null && $this->rkey === null;
    }

    public function __toString(): string
    {
        if ($this->collection === null) {
            return "at://{$this->did}";
        }

        if ($this->rkey === null) {
            return "at://{$this->did}/{$this->collection}";
        }

        return "at://{$this->did}/{$this->collection}/{$this->rkey}";
    }
}
