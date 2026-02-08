<?php

namespace SocialDept\AtpSupport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\AtUri;

class AtUriTest extends TestCase
{
    public function test_parse_valid_uri(): void
    {
        $uri = AtUri::parse('at://did:plc:ewvi7nxzyoun6zhxrhs64oiz/app.bsky.feed.post/3abc123');

        $this->assertNotNull($uri);
        $this->assertSame('did:plc:ewvi7nxzyoun6zhxrhs64oiz', $uri->did);
        $this->assertSame('app.bsky.feed.post', $uri->collection);
        $this->assertSame('3abc123', $uri->rkey);
    }

    public function test_parse_invalid_uri_returns_null(): void
    {
        $this->assertNull(AtUri::parse('not-a-uri'));
        $this->assertNull(AtUri::parse('at://did:plc:abc'));
        $this->assertNull(AtUri::parse('https://example.com'));
        $this->assertNull(AtUri::parse(''));
    }

    public function test_parse_uri_missing_rkey_returns_null(): void
    {
        $this->assertNull(AtUri::parse('at://did:plc:abc/app.bsky.feed.post'));
    }

    public function test_to_string(): void
    {
        $uri = AtUri::make('did:plc:abc', 'app.bsky.feed.post', 'rkey123');

        $this->assertSame('at://did:plc:abc/app.bsky.feed.post/rkey123', (string) $uri);
    }

    public function test_parse_roundtrip(): void
    {
        $original = 'at://did:plc:ewvi7nxzyoun6zhxrhs64oiz/app.bsky.feed.post/3abc123';
        $parsed = AtUri::parse($original);

        $this->assertSame($original, (string) $parsed);
    }

    public function test_make_constructor(): void
    {
        $uri = AtUri::make('did:web:example.com', 'app.bsky.actor.profile', 'self');

        $this->assertSame('did:web:example.com', $uri->did);
        $this->assertSame('app.bsky.actor.profile', $uri->collection);
        $this->assertSame('self', $uri->rkey);
    }

    public function test_parse_partial_identity_uri(): void
    {
        $uri = AtUri::parse('at://did:plc:abc123', partial: true);

        $this->assertNotNull($uri);
        $this->assertSame('did:plc:abc123', $uri->did);
        $this->assertNull($uri->collection);
        $this->assertNull($uri->rkey);
    }

    public function test_parse_partial_identity_uri_with_trailing_slash(): void
    {
        $uri = AtUri::parse('at://did:plc:abc123/', partial: true);

        $this->assertNotNull($uri);
        $this->assertSame('did:plc:abc123', $uri->did);
        $this->assertNull($uri->collection);
        $this->assertNull($uri->rkey);
    }

    public function test_parse_partial_collection_uri(): void
    {
        $uri = AtUri::parse('at://did:plc:abc123/app.bsky.feed.post', partial: true);

        $this->assertNotNull($uri);
        $this->assertSame('did:plc:abc123', $uri->did);
        $this->assertSame('app.bsky.feed.post', $uri->collection);
        $this->assertNull($uri->rkey);
    }

    public function test_parse_partial_full_record_uri(): void
    {
        $uri = AtUri::parse('at://did:plc:abc123/app.bsky.feed.post/rk1', partial: true);

        $this->assertNotNull($uri);
        $this->assertSame('did:plc:abc123', $uri->did);
        $this->assertSame('app.bsky.feed.post', $uri->collection);
        $this->assertSame('rk1', $uri->rkey);
    }

    public function test_parse_partial_rejects_invalid(): void
    {
        $this->assertNull(AtUri::parse('not-a-uri', partial: true));
        $this->assertNull(AtUri::parse('https://example.com', partial: true));
        $this->assertNull(AtUri::parse('', partial: true));
    }

    public function test_parse_without_partial_still_rejects_partial_uris(): void
    {
        $this->assertNull(AtUri::parse('at://did:plc:abc123'));
        $this->assertNull(AtUri::parse('at://did:plc:abc123/app.bsky.feed.post'));
    }

    public function test_is_record(): void
    {
        $this->assertTrue(AtUri::make('did:plc:abc', 'app.bsky.feed.post', 'rk1')->isRecord());
        $this->assertFalse(AtUri::make('did:plc:abc', 'app.bsky.feed.post')->isRecord());
        $this->assertFalse(AtUri::make('did:plc:abc')->isRecord());
    }

    public function test_is_collection(): void
    {
        $this->assertFalse(AtUri::make('did:plc:abc', 'app.bsky.feed.post', 'rk1')->isCollection());
        $this->assertTrue(AtUri::make('did:plc:abc', 'app.bsky.feed.post')->isCollection());
        $this->assertFalse(AtUri::make('did:plc:abc')->isCollection());
    }

    public function test_is_identity(): void
    {
        $this->assertFalse(AtUri::make('did:plc:abc', 'app.bsky.feed.post', 'rk1')->isIdentity());
        $this->assertFalse(AtUri::make('did:plc:abc', 'app.bsky.feed.post')->isIdentity());
        $this->assertTrue(AtUri::make('did:plc:abc')->isIdentity());
    }

    public function test_to_string_identity(): void
    {
        $this->assertSame('at://did:plc:abc', (string) AtUri::make('did:plc:abc'));
    }

    public function test_to_string_collection(): void
    {
        $this->assertSame('at://did:plc:abc/app.bsky.feed.post', (string) AtUri::make('did:plc:abc', 'app.bsky.feed.post'));
    }

    public function test_parse_partial_roundtrip(): void
    {
        $identity = 'at://did:plc:abc';
        $this->assertSame($identity, (string) AtUri::parse($identity, partial: true));

        $collection = 'at://did:plc:abc/app.bsky.feed.post';
        $this->assertSame($collection, (string) AtUri::parse($collection, partial: true));

        $record = 'at://did:plc:abc/app.bsky.feed.post/rk1';
        $this->assertSame($record, (string) AtUri::parse($record, partial: true));
    }
}
