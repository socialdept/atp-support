<?php

namespace SocialDept\AtpSupport\Tests\Unit\Microcosm;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\Microcosm\Data\BacklinkReference;

class BacklinkReferenceTest extends TestCase
{
    public function test_it_can_be_created_from_array(): void
    {
        $ref = BacklinkReference::fromArray([
            'did' => 'did:plc:abc123',
            'collection' => 'app.bsky.feed.like',
            'rkey' => '3lbvokem55s2y',
        ]);

        $this->assertSame('did:plc:abc123', $ref->did);
        $this->assertSame('app.bsky.feed.like', $ref->collection);
        $this->assertSame('3lbvokem55s2y', $ref->rkey);
    }

    public function test_it_handles_missing_fields(): void
    {
        $ref = BacklinkReference::fromArray([]);

        $this->assertSame('', $ref->did);
        $this->assertSame('', $ref->collection);
        $this->assertSame('', $ref->rkey);
    }

    public function test_uri_returns_valid_at_uri(): void
    {
        $ref = BacklinkReference::fromArray([
            'did' => 'did:plc:abc123',
            'collection' => 'app.bsky.feed.like',
            'rkey' => '3lbvokem55s2y',
        ]);

        $this->assertSame('at://did:plc:abc123/app.bsky.feed.like/3lbvokem55s2y', $ref->uri());
    }
}
