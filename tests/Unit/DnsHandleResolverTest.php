<?php

namespace SocialDept\AtpSupport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\Resolvers\DnsHandleResolver;

class DnsHandleResolverTest extends TestCase
{
    public function test_it_returns_did_when_valid_txt_record_exists(): void
    {
        $resolver = new DnsHandleResolver(fn () => [
            ['txt' => 'did=did:plc:ewvi7nxzyoun6zhxrhs64oiz'],
        ]);

        $result = $resolver->attempt('bsky.app');

        $this->assertSame('did:plc:ewvi7nxzyoun6zhxrhs64oiz', $result);
    }

    public function test_it_returns_null_when_no_txt_records_found(): void
    {
        $resolver = new DnsHandleResolver(fn () => []);

        $this->assertNull($resolver->attempt('example.com'));
    }

    public function test_it_returns_null_when_dns_query_returns_false(): void
    {
        $resolver = new DnsHandleResolver(fn () => false);

        $this->assertNull($resolver->attempt('example.com'));
    }

    public function test_it_returns_null_when_dns_query_throws(): void
    {
        $resolver = new DnsHandleResolver(function () {
            throw new \RuntimeException('DNS failure');
        });

        $this->assertNull($resolver->attempt('example.com'));
    }

    public function test_it_returns_null_when_txt_value_is_not_a_valid_did(): void
    {
        $resolver = new DnsHandleResolver(fn () => [
            ['txt' => 'did=not-a-valid-did'],
        ]);

        $this->assertNull($resolver->attempt('example.com'));
    }

    public function test_it_skips_txt_records_without_did_prefix(): void
    {
        $resolver = new DnsHandleResolver(fn () => [
            ['txt' => 'v=spf1 include:example.com ~all'],
            ['txt' => 'did=did:plc:ewvi7nxzyoun6zhxrhs64oiz'],
        ]);

        $result = $resolver->attempt('example.com');

        $this->assertSame('did:plc:ewvi7nxzyoun6zhxrhs64oiz', $result);
    }

    public function test_it_returns_null_when_all_txt_records_lack_did_prefix(): void
    {
        $resolver = new DnsHandleResolver(fn () => [
            ['txt' => 'v=spf1 include:example.com ~all'],
            ['txt' => 'google-site-verification=abc123'],
        ]);

        $this->assertNull($resolver->attempt('example.com'));
    }

    public function test_it_queries_atproto_subdomain(): void
    {
        $queriedHostname = null;

        $resolver = new DnsHandleResolver(function (string $hostname) use (&$queriedHostname) {
            $queriedHostname = $hostname;

            return [['txt' => 'did=did:plc:ewvi7nxzyoun6zhxrhs64oiz']];
        });

        $resolver->attempt('alice.bsky.social');

        $this->assertSame('_atproto.alice.bsky.social', $queriedHostname);
    }
}
