<?php

namespace SocialDept\AtpSupport\Tests\Unit\Microcosm;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\Microcosm\ConstellationClient;
use SocialDept\AtpSupport\Microcosm\Data\BacklinkReference;
use SocialDept\AtpSupport\Microcosm\Data\GetBacklinksResponse;
use SocialDept\AtpSupport\Microcosm\Data\LinkSummary;
use SocialDept\AtpSupport\Microcosm\MicrocosmException;

class ConstellationClientTest extends TestCase
{
    private function createClientWithMock(MockHandler $mock): ConstellationClient
    {
        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);

        $client = new ConstellationClient('https://constellation.test');

        // Inject the mock Guzzle client via reflection
        $ref = new \ReflectionProperty($client, 'client');
        $ref->setValue($client, $guzzle);

        return $client;
    }

    public function test_get_backlinks_returns_typed_response(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'total' => 2,
                'records' => [
                    ['did' => 'did:plc:abc', 'collection' => 'app.bsky.feed.like', 'rkey' => 'rk1'],
                    ['did' => 'did:plc:def', 'collection' => 'app.bsky.feed.like', 'rkey' => 'rk2'],
                ],
                'cursor' => 'abc123',
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $response = $client->getBacklinks('did:plc:test', 'app.bsky.feed.like:subject.uri');

        $this->assertInstanceOf(GetBacklinksResponse::class, $response);
        $this->assertSame(2, $response->total);
        $this->assertCount(2, $response->records);
        $this->assertSame('abc123', $response->cursor);

        $this->assertInstanceOf(BacklinkReference::class, $response->records[0]);
        $this->assertSame('did:plc:abc', $response->records[0]->did);
        $this->assertSame('app.bsky.feed.like', $response->records[0]->collection);
        $this->assertSame('rk1', $response->records[0]->rkey);
    }

    public function test_get_backlinks_handles_empty_response(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'total' => 0,
                'records' => [],
                'cursor' => null,
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $response = $client->getBacklinks('did:plc:test', 'app.bsky.feed.like:subject.uri');

        $this->assertSame(0, $response->total);
        $this->assertCount(0, $response->records);
        $this->assertNull($response->cursor);
    }

    public function test_get_backlinks_count_returns_integer(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['total' => 42])),
        ]);

        $client = $this->createClientWithMock($mock);
        $count = $client->getBacklinksCount('did:plc:test', 'app.bsky.graph.follow:subject');

        $this->assertSame(42, $count);
    }

    public function test_get_all_links_returns_link_summary(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'links' => [
                    'app.bsky.feed.like' => [
                        '.subject.uri' => ['records' => 10, 'distinct_dids' => 8],
                    ],
                ],
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $summary = $client->getAllLinks('did:plc:test');

        $this->assertInstanceOf(LinkSummary::class, $summary);
        $this->assertArrayHasKey('app.bsky.feed.like', $summary->links);
        $this->assertSame(10, $summary->total());
    }

    public function test_get_many_to_many_counts_returns_array(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'counts_by_other_subject' => [
                    ['subject' => 'at://did:plc:abc/app.bsky.feed.post/123', 'count' => 5],
                ],
                'cursor' => null,
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $counts = $client->getManyToManyCounts(
            'did:plc:test',
            'app.bsky.feed.like:subject.uri',
            'subject.uri',
        );

        $this->assertCount(1, $counts);
        $this->assertSame(5, $counts[0]['count']);
    }

    public function test_it_throws_microcosm_exception_on_http_error(): void
    {
        $mock = new MockHandler([
            new Response(500, [], 'Internal Server Error'),
        ]);

        // Use a handler that throws on 4xx/5xx
        $handler = HandlerStack::create($mock);
        $guzzle = new Client([
            'handler' => $handler,
            'http_errors' => true,
        ]);

        $client = new ConstellationClient('https://constellation.test');
        $ref = new \ReflectionProperty($client, 'client');
        $ref->setValue($client, $guzzle);

        $this->expectException(MicrocosmException::class);
        $this->expectExceptionMessage('Microcosm request to');

        $client->getBacklinksCount('did:plc:test', 'app.bsky.feed.like:subject.uri');
    }

    public function test_it_throws_microcosm_exception_on_invalid_json(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'not json'),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(MicrocosmException::class);
        $this->expectExceptionMessage('invalid response');

        $client->getBacklinksCount('did:plc:test', 'app.bsky.feed.like:subject.uri');
    }
}
