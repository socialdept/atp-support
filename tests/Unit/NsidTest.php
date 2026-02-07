<?php

namespace SocialDept\AtpSupport\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\Nsid;

class NsidTest extends TestCase
{
    public function test_parse_valid_nsid(): void
    {
        $nsid = Nsid::parse('app.bsky.feed.post');

        $this->assertSame('app.bsky.feed.post', $nsid->toString());
    }

    public function test_get_authority(): void
    {
        $nsid = Nsid::parse('app.bsky.feed.post');

        $this->assertSame('app.bsky.feed', $nsid->getAuthority());
    }

    public function test_get_name(): void
    {
        $nsid = Nsid::parse('app.bsky.feed.post');

        $this->assertSame('post', $nsid->getName());
    }

    public function test_get_segments(): void
    {
        $nsid = Nsid::parse('app.bsky.feed.post');

        $this->assertSame(['app', 'bsky', 'feed', 'post'], $nsid->getSegments());
    }

    public function test_to_domain(): void
    {
        $nsid = Nsid::parse('app.bsky.feed.post');

        $this->assertSame('post.feed.bsky.app', $nsid->toDomain());
    }

    public function test_get_authority_domain(): void
    {
        $nsid = Nsid::parse('app.bsky.feed.post');

        $this->assertSame('feed.bsky.app', $nsid->getAuthorityDomain());
    }

    public function test_to_string(): void
    {
        $nsid = Nsid::parse('app.bsky.feed.post');

        $this->assertSame('app.bsky.feed.post', (string) $nsid);
    }

    public function test_is_valid(): void
    {
        $this->assertTrue(Nsid::isValid('app.bsky.feed.post'));
        $this->assertTrue(Nsid::isValid('com.atproto.repo.getRecord'));

        $this->assertFalse(Nsid::isValid(''));
        $this->assertFalse(Nsid::isValid('invalid'));
        $this->assertFalse(Nsid::isValid('two.segments'));
    }

    public function test_equals(): void
    {
        $nsid1 = Nsid::parse('app.bsky.feed.post');
        $nsid2 = Nsid::parse('app.bsky.feed.post');
        $nsid3 = Nsid::parse('app.bsky.feed.like');

        $this->assertTrue($nsid1->equals($nsid2));
        $this->assertFalse($nsid1->equals($nsid3));
    }

    public function test_empty_nsid_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('NSID cannot be empty');

        Nsid::parse('');
    }

    public function test_invalid_format_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid NSID format');

        Nsid::parse('123.invalid.nsid');
    }

    public function test_too_few_segments_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('NSID must have at least 3 segments');

        Nsid::parse('two.segments');
    }
}
