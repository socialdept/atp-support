<?php

namespace SocialDept\AtpSupport\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\Resolvers\WellKnownHandleResolver;

class WellKnownHandleResolverTest extends TestCase
{
    public function test_it_returns_did_on_successful_response(): void
    {
        $resolver = $this->makeResolver([
            new Response(200, [], 'did:plc:ewvi7nxzyoun6zhxrhs64oiz'),
        ]);

        $result = $resolver->attempt('bsky.app');

        $this->assertSame('did:plc:ewvi7nxzyoun6zhxrhs64oiz', $result);
    }

    public function test_it_trims_whitespace_from_response(): void
    {
        $resolver = $this->makeResolver([
            new Response(200, [], "  did:plc:ewvi7nxzyoun6zhxrhs64oiz\n"),
        ]);

        $result = $resolver->attempt('bsky.app');

        $this->assertSame('did:plc:ewvi7nxzyoun6zhxrhs64oiz', $result);
    }

    public function test_it_returns_null_on_http_404(): void
    {
        $resolver = $this->makeResolver([
            new Response(404),
        ]);

        $this->assertNull($resolver->attempt('example.com'));
    }

    public function test_it_returns_null_on_http_500(): void
    {
        $resolver = $this->makeResolver([
            new Response(500),
        ]);

        $this->assertNull($resolver->attempt('example.com'));
    }

    public function test_it_returns_null_on_connection_error(): void
    {
        $resolver = $this->makeResolver([
            new ConnectException(
                'Connection refused',
                new Request('GET', 'https://example.com/.well-known/atproto-did'),
            ),
        ]);

        $this->assertNull($resolver->attempt('example.com'));
    }

    public function test_it_returns_null_when_response_is_not_a_valid_did(): void
    {
        $resolver = $this->makeResolver([
            new Response(200, [], 'not-a-valid-did'),
        ]);

        $this->assertNull($resolver->attempt('example.com'));
    }

    public function test_it_returns_null_on_empty_response(): void
    {
        $resolver = $this->makeResolver([
            new Response(200, [], ''),
        ]);

        $this->assertNull($resolver->attempt('example.com'));
    }

    private function makeResolver(array $responses): WellKnownHandleResolver
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        return new WellKnownHandleResolver($client);
    }
}
