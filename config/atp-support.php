<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PLC Directory URL
    |--------------------------------------------------------------------------
    |
    | The URL of the PLC (Public Ledger of Credentials) directory used for
    | resolving DID:PLC identifiers. The default is the official AT Protocol
    | PLC directory.
    |
    */

    'plc_directory' => env('ATP_PLC_DIRECTORY', 'https://plc.directory'),

    /*
    |--------------------------------------------------------------------------
    | PDS Endpoint
    |--------------------------------------------------------------------------
    |
    | The Personal Data Server endpoint used for handle resolution. This is
    | used when resolving handles to DIDs via the AT Protocol API.
    |
    */

    'pds_endpoint' => env('ATP_PDS_ENDPOINT', 'https://bsky.social'),

    /*
    |--------------------------------------------------------------------------
    | Public API Endpoint
    |--------------------------------------------------------------------------
    |
    | The public AT Protocol API endpoint used for unauthenticated read
    | operations. This is Bluesky's public AppView API.
    |
    */

    'public_api' => env('ATP_PUBLIC_API', 'https://public.api.bsky.app'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for HTTP requests to external services when
    | resolving DIDs, handles, and lexicons.
    |
    */

    'timeout' => env('ATP_RESOLVER_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for resolved DIDs and handles.
    | TTL values are in seconds.
    |
    */

    'cache' => [

        // Enable or disable caching globally
        'enabled' => env('ATP_RESOLVER_CACHE_ENABLED', true),

        // Cache TTL for DID documents (1 hour default)
        'did_ttl' => env('ATP_RESOLVER_CACHE_DID_TTL', 3600),

        // Cache TTL for handle resolutions (1 hour default)
        'handle_ttl' => env('ATP_RESOLVER_CACHE_HANDLE_TTL', 3600),

        // Cache TTL for PDS endpoints (1 hour default)
        'pds_ttl' => env('ATP_RESOLVER_CACHE_PDS_TTL', 3600),

    ],

];
