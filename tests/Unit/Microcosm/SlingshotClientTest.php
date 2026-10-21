<?php

namespace SocialDept\AtpSupport\Tests\Unit\Microcosm;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\Microcosm\Data\GetRecordResponse;
use SocialDept\AtpSupport\Microcosm\Data\MiniDoc;
use SocialDept\AtpSupport\Microcosm\MicrocosmException;
use SocialDept\AtpSupport\Microcosm\RecordNotFoundException;
use SocialDept\AtpSupport\Microcosm\SlingshotClient;

class SlingshotClientTest extends TestCase
{
    private function createClientWithMock(MockHandler $mock): SlingshotClient
    {
        $handler = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handler]);

        $client = new SlingshotClient('https://slingshot.test');

        $ref = new \ReflectionProperty($client, 'client');
        $ref->setValue($client, $guzzle);

        return $client;
    }

    public function test_get_record_returns_typed_response(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'uri' => 'at://did:plc:abc/app.bsky.feed.post/rk1',
                'cid' => 'bafyreiabc123',
                'value' => [
                    '$type' => 'app.bsky.feed.post',
                    'text' => 'Hello world',
                    'createdAt' => '2024-01-01T00:00:00.000Z',
                ],
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $response = $client->getRecord('did:plc:abc', 'app.bsky.feed.post', 'rk1');

        $this->assertInstanceOf(GetRecordResponse::class, $response);
        $this->assertSame('at://did:plc:abc/app.bsky.feed.post/rk1', $response->uri);
        $this->assertSame('bafyreiabc123', $response->cid);
        $this->assertSame('Hello world', $response->value['text']);
    }

    public function test_get_record_by_uri_sends_at_uri_parameter(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'uri' => 'at://did:plc:abc/app.bsky.feed.post/rk1',
                'cid' => 'bafyreiabc123',
                'value' => ['text' => 'Hello'],
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $response = $client->getRecordByUri('at://did:plc:abc/app.bsky.feed.post/rk1');

        $this->assertInstanceOf(GetRecordResponse::class, $response);
        $this->assertSame('at://did:plc:abc/app.bsky.feed.post/rk1', $response->uri);
    }

    public function test_resolve_mini_doc_returns_typed_response(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'did' => 'did:plc:z72i7hdynmk6r22z27h6tvur',
                'handle' => 'bsky.app',
                'pds' => 'https://puffball.us-east.host.bsky.network',
                'signing_key' => 'zQ3shQo6TF2moaqMTrUZEM1jeuYRQXeHEx4evX9751y2qPqRA',
            ])),
        ]);

        $client = $this->createClientWithMock($mock);
        $doc = $client->resolveMiniDoc('did:plc:z72i7hdynmk6r22z27h6tvur');

        $this->assertInstanceOf(MiniDoc::class, $doc);
        $this->assertSame('did:plc:z72i7hdynmk6r22z27h6tvur', $doc->did);
        $this->assertSame('bsky.app', $doc->handle);
        $this->assertSame('https://puffball.us-east.host.bsky.network', $doc->pds);
        $this->assertSame('zQ3shQo6TF2moaqMTrUZEM1jeuYRQXeHEx4evX9751y2qPqRA', $doc->signingKey);
    }

    public function test_it_throws_microcosm_exception_on_http_error(): void
    {
        $mock = new MockHandler([
            new Response(404, [], 'not found'),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzle = new Client([
            'handler' => $handler,
            'http_errors' => true,
        ]);

        $client = new SlingshotClient('https://slingshot.test');
        $ref = new \ReflectionProperty($client, 'client');
        $ref->setValue($client, $guzzle);

        $this->expectException(MicrocosmException::class);
        $this->expectExceptionMessage('Microcosm request to');

        $client->resolveMiniDoc('did:plc:invalid');
    }

    public function test_it_throws_microcosm_exception_on_invalid_json(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'not json'),
        ]);

        $client = $this->createClientWithMock($mock);

        $this->expectException(MicrocosmException::class);
        $this->expectExceptionMessage('invalid response');

        $client->resolveMiniDoc('did:plc:test');
    }

    public function test_a_missing_record_is_told_apart_from_a_service_that_is_down(): void
    {
        $client = $this->createClientWithMock(new MockHandler([
            new Response(400, [], json_encode(['error' => 'RecordNotFound', 'message' => 'Could not locate record'])),
            new Response(500, [], json_encode(['error' => 'ServerError', 'message' => 'sorry, something went wrong'])),
            new ConnectException('Connection refused', new Request('GET', 'https://slingshot.test')),
            new Response(400, [], json_encode(['error' => 'InvalidRequest', 'message' => 'Errored while trying to resolve handle to DID'])),
        ]));

        $missing = $this->failure(fn () => $client->getRecord('did:plc:abc', 'app.bsky.feed.post', 'gone'));
        $this->assertInstanceOf(RecordNotFoundException::class, $missing);
        $this->assertSame(400, $missing->status);
        $this->assertFalse($missing->isUnavailable());
        $this->assertStringContainsString('Could not locate record', $missing->getMessage());

        $down = $this->failure(fn () => $client->getRecordByUri('at://did:plc:abc/app.bsky.feed.post/rk1'));
        $this->assertNotInstanceOf(RecordNotFoundException::class, $down);
        $this->assertSame('ServerError', $down->error);
        $this->assertTrue($down->isUnavailable());

        $unreachable = $this->failure(fn () => $client->getRecord('did:plc:abc', 'app.bsky.feed.post', 'rk1'));
        $this->assertNull($unreachable->status);
        $this->assertTrue($unreachable->isUnavailable());

        $refused = $this->failure(fn () => $client->resolveMiniDoc('nobody.example'));
        $this->assertNotInstanceOf(RecordNotFoundException::class, $refused);
        $this->assertSame('InvalidRequest', $refused->error);
        $this->assertFalse($refused->isUnavailable());
    }

    private function failure(callable $call): MicrocosmException
    {
        try {
            $call();
        } catch (MicrocosmException $e) {
            return $e;
        }

        $this->fail('Expected a MicrocosmException.');
    }
}
