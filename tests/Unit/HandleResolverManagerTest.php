<?php

namespace SocialDept\AtpSupport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\Contracts\HandleResolutionMethod;
use SocialDept\AtpSupport\Exceptions\HandleResolutionException;
use SocialDept\AtpSupport\Resolvers\HandleResolverManager;

class HandleResolverManagerTest extends TestCase
{
    public function test_it_returns_did_from_first_successful_method(): void
    {
        $method1 = $this->makeMethod('did:plc:first');
        $method2 = $this->makeMethod('did:plc:second');

        $manager = new HandleResolverManager([$method1, $method2]);

        $this->assertSame('did:plc:first', $manager->resolve('alice.bsky.social'));
    }

    public function test_it_skips_failed_methods_and_tries_next(): void
    {
        $method1 = $this->makeMethod(null);
        $method2 = $this->makeMethod('did:plc:second');

        $manager = new HandleResolverManager([$method1, $method2]);

        $this->assertSame('did:plc:second', $manager->resolve('alice.bsky.social'));
    }

    public function test_it_throws_when_all_methods_fail(): void
    {
        $method1 = $this->makeMethod(null);
        $method2 = $this->makeMethod(null);

        $manager = new HandleResolverManager([$method1, $method2]);

        $this->expectException(HandleResolutionException::class);
        $this->expectExceptionMessage('Failed to resolve handle: alice.bsky.social');

        $manager->resolve('alice.bsky.social');
    }

    public function test_it_throws_for_invalid_handle_format(): void
    {
        $manager = new HandleResolverManager([]);

        $this->expectException(HandleResolutionException::class);
        $this->expectExceptionMessage('Invalid handle format');

        $manager->resolve('not a handle');
    }

    public function test_it_works_with_single_method(): void
    {
        $method = $this->makeMethod('did:plc:single');

        $manager = new HandleResolverManager([$method]);

        $this->assertSame('did:plc:single', $manager->resolve('alice.bsky.social'));
    }

    public function test_it_respects_method_ordering(): void
    {
        $calls = [];

        $method1 = $this->makeMethodWithTracking($calls, 'method1', null);
        $method2 = $this->makeMethodWithTracking($calls, 'method2', 'did:plc:found');
        $method3 = $this->makeMethodWithTracking($calls, 'method3', 'did:plc:never-reached');

        $manager = new HandleResolverManager([$method1, $method2, $method3]);
        $manager->resolve('alice.bsky.social');

        $this->assertSame(['method1', 'method2'], $calls);
    }

    public function test_it_throws_with_empty_methods_array(): void
    {
        $manager = new HandleResolverManager([]);

        $this->expectException(HandleResolutionException::class);

        $manager->resolve('alice.bsky.social');
    }

    private function makeMethod(?string $did): HandleResolutionMethod
    {
        $mock = $this->createMock(HandleResolutionMethod::class);
        $mock->method('attempt')->willReturn($did);

        return $mock;
    }

    private function makeMethodWithTracking(array &$calls, string $name, ?string $did): HandleResolutionMethod
    {
        $mock = $this->createMock(HandleResolutionMethod::class);
        $mock->method('attempt')->willReturnCallback(function () use (&$calls, $name, $did) {
            $calls[] = $name;

            return $did;
        });

        return $mock;
    }
}
