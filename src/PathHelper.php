<?php

namespace SocialDept\AtpSupport;

class PathHelper
{
    /**
     * Convert a relative path to a PHP namespace.
     *
     * Examples:
     *   'app/Lexicons' → 'App\Lexicons'
     *   'app/Services/Clients' → 'App\Services\Clients'
     */
    public static function pathToNamespace(string $path): string
    {
        return implode('\\', array_map('ucfirst', explode('/', $path)));
    }
}
