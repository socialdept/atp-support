<?php

namespace SocialDept\AtpSupport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\PathHelper;

class PathHelperTest extends TestCase
{
    public function test_path_to_namespace_simple(): void
    {
        $this->assertSame('App\\Lexicons', PathHelper::pathToNamespace('app/Lexicons'));
    }

    public function test_path_to_namespace_nested(): void
    {
        $this->assertSame('App\\Services\\Clients', PathHelper::pathToNamespace('app/Services/Clients'));
    }

    public function test_path_to_namespace_single_segment(): void
    {
        $this->assertSame('App', PathHelper::pathToNamespace('app'));
    }

    public function test_path_to_namespace_preserves_existing_uppercase(): void
    {
        $this->assertSame('App\\Generated\\Lexicons', PathHelper::pathToNamespace('app/Generated/Lexicons'));
    }
}
