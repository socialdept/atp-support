<?php

namespace SocialDept\AtpSupport\Tests\Unit;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use SocialDept\AtpSupport\Exceptions\LexiconResolutionException;
use SocialDept\AtpSupport\Resolver;
use SocialDept\AtpSupport\Resolvers\LexiconDnsResolver;

class LexiconDnsResolverTest extends TestCase
{
    protected const DID = 'did:plc:authority';

    protected const SCHEMA = ['lexicon' => 1, 'id' => 'com.example.thing', 'defs' => ['main' => ['type' => 'token', 'description' => 'x']]];

    /**
     * TXT records by hostname.
     *
     * @var array<string, array<int, string>>
     */
    protected array $txt = ['_lexicon.example.com' => ['did='.self::DID]];

    /**
     * @var array<int, string>
     */
    protected array $looked = [];

    protected function resolver(?string $pds = 'https://pds.example'): LexiconDnsResolver
    {
        $resolver = $this->createStub(Resolver::class);
        $resolver->method('resolvePds')->willReturn($pds);

        return new LexiconDnsResolver($resolver, dnsLookup: function (string $hostname, int $type) {
            $this->looked[] = $hostname;

            return array_map(fn (string $txt) => ['txt' => $txt], $this->txt[$hostname] ?? []) ?: false;
        });
    }

    protected function published(): void
    {
        Http::fake(['pds.example/*' => Http::response([
            'uri' => 'at://'.self::DID.'/com.atproto.lexicon.schema/com.example.thing',
            'cid' => 'bafyschema',
            'value' => ['$type' => 'com.atproto.lexicon.schema', ...self::SCHEMA],
        ])]);
    }

    public function test_fetch_answers_the_schema_with_its_record(): void
    {
        $this->published();

        $published = $this->resolver()->fetch('com.example.thing');

        $this->assertSame('com.example.thing', $published->nsid);
        $this->assertSame(self::DID, $published->did);
        $this->assertSame('at://did:plc:authority/com.atproto.lexicon.schema/com.example.thing', $published->uri);
        $this->assertSame('bafyschema', $published->cid);
        $this->assertSame(self::SCHEMA, $published->schema);
        $this->assertSame(['_lexicon.example.com'], $this->looked);
        Http::assertSent(fn ($request) => $request['repo'] === self::DID && $request['collection'] === 'com.atproto.lexicon.schema' && $request['rkey'] === 'com.example.thing');
    }

    public function test_a_lexicon_nobody_publishes_is_null_not_an_error(): void
    {
        Http::fake(['pds.example/*' => Http::response(['error' => 'RecordNotFound', 'message' => 'Could not locate record'], 400)]);

        $this->assertNull($this->resolver()->fetch('com.example.thing'));
        $this->assertNull($this->resolver()->fetch('org.nobody.thing'));
    }

    public function test_dns_is_not_inherited_from_a_parent_authority(): void
    {
        $this->published();

        $this->assertNull($this->resolver()->fetch('com.example.feed.post'));
        $this->assertSame(['_lexicon.feed.example.com'], $this->looked);
    }

    public function test_a_txt_record_that_names_no_did_is_ignored(): void
    {
        $this->txt['_lexicon.example.com'] = ['v=spf1 -all', 'did=not-a-did'];

        $this->assertNull($this->resolver()->lookupDns('com.example'));
    }

    public function test_an_unreachable_authority_is_an_error_not_a_missing_lexicon(): void
    {
        Http::fake(['pds.example/*' => Http::response('down', 503)]);

        $this->expectException(LexiconResolutionException::class);
        $this->expectExceptionMessage('answered HTTP 503');

        $this->resolver()->fetch('com.example.thing');
    }

    public function test_a_connection_failure_is_an_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->expectException(LexiconResolutionException::class);

        $this->resolver()->fetchFrom(self::DID, 'com.example.thing');
    }

    public function test_a_did_without_a_pds_is_an_error(): void
    {
        $this->expectException(LexiconResolutionException::class);
        $this->expectExceptionMessage('names no PDS');

        $this->resolver(pds: null)->fetchFrom(self::DID, 'com.example.thing');
    }

    public function test_a_record_that_is_not_a_lexicon_is_an_error(): void
    {
        Http::fake(['pds.example/*' => Http::response(['uri' => 'at://x', 'value' => ['text' => 'hello']])]);

        $this->expectException(LexiconResolutionException::class);

        $this->resolver()->fetch('com.example.thing');
    }

    public function test_resolve_still_answers_the_record_value_and_throws_runtime_exceptions(): void
    {
        $this->published();

        $this->assertSame(['$type' => 'com.atproto.lexicon.schema', ...self::SCHEMA], $this->resolver()->resolve('com.example.thing'));

        $this->expectException(\RuntimeException::class);
        $this->resolver()->resolve('org.nobody.thing');
    }
}
