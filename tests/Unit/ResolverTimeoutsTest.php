<?php

namespace SocialDept\AtpSupport\Tests\Unit;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SocialDept\AtpSupport\Microcosm\ConstellationClient;
use SocialDept\AtpSupport\Microcosm\SlingshotClient;
use SocialDept\AtpSupport\Resolvers\AtProtoHandleResolver;
use SocialDept\AtpSupport\Resolvers\PlcDidResolver;
use SocialDept\AtpSupport\Resolvers\WebDidResolver;
use SocialDept\AtpSupport\Resolvers\WellKnownHandleResolver;

/**
 * Resolution often runs while a user waits, so every client gets a connect
 * timeout: an unreachable host fails in seconds rather than holding the
 * request for the whole timeout.
 */
class ResolverTimeoutsTest extends TestCase
{
    /**
     * @return array<string, array{callable(?int, ?float): object}>
     */
    public static function resolvers(): array
    {
        return [
            'plc' => [fn (?int $t, ?float $c) => new PlcDidResolver(null, $t, $c)],
            'web' => [fn (?int $t, ?float $c) => new WebDidResolver($t, $c)],
            'handle via xrpc' => [fn (?int $t, ?float $c) => new AtProtoHandleResolver(null, $t, $c)],
            'handle via well-known' => [fn (?int $t, ?float $c) => new WellKnownHandleResolver(null, $t, $c)],
        ];
    }

    #[DataProvider('resolvers')]
    public function test_resolvers_default_to_a_short_timeout_and_a_connect_timeout(callable $make): void
    {
        $client = $this->client($make(null, null));

        $this->assertSame(5, $client->getConfig('timeout'));
        $this->assertSame(2, $client->getConfig('connect_timeout'));
    }

    #[DataProvider('resolvers')]
    public function test_resolver_timeouts_can_be_set(callable $make): void
    {
        $client = $this->client($make(8, 0.5));

        $this->assertSame(8, $client->getConfig('timeout'));
        $this->assertSame(0.5, $client->getConfig('connect_timeout'));
    }

    public function test_microcosm_clients_keep_their_timeouts_and_gain_a_connect_timeout(): void
    {
        $this->assertSame(2, $this->client(new ConstellationClient())->getConfig('connect_timeout'));
        $this->assertSame(2, $this->client(new SlingshotClient())->getConfig('connect_timeout'));
        $this->assertSame(0.5, $this->client(new SlingshotClient(null, null, 0.5))->getConfig('connect_timeout'));
    }

    protected function client(object $resolver): Client
    {
        return (new ReflectionProperty($resolver, 'client'))->getValue($resolver);
    }
}
