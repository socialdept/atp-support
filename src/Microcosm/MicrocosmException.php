<?php

namespace SocialDept\AtpSupport\Microcosm;

use RuntimeException;

class MicrocosmException extends RuntimeException
{
    public static function requestFailed(string $endpoint, string $message): self
    {
        return new self("Microcosm request to [{$endpoint}] failed: {$message}");
    }

    public static function invalidResponse(string $endpoint): self
    {
        return new self("Microcosm returned an invalid response from [{$endpoint}]");
    }
}
