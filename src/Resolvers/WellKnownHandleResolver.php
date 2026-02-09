<?php

namespace SocialDept\AtpSupport\Resolvers;

use GuzzleHttp\Client;
use SocialDept\AtpSupport\Concerns\HasConfig;
use SocialDept\AtpSupport\Contracts\HandleResolutionMethod;
use SocialDept\AtpSupport\Identity;

class WellKnownHandleResolver implements HandleResolutionMethod
{
    use HasConfig;

    protected Client $client;

    public function __construct(?Client $client = null, ?int $timeout = null)
    {
        $this->client = $client ?? new Client([
            'timeout' => $timeout ?? $this->getConfig('atp-support.timeout', 10),
            'headers' => [
                'Accept' => 'text/plain',
                'User-Agent' => 'Beacon/1.0',
            ],
        ]);
    }

    public function attempt(string $handle): ?string
    {
        try {
            $url = "https://{$handle}/.well-known/atproto-did";

            $response = $this->client->get($url);

            $did = trim($response->getBody()->getContents());

            if (Identity::isDid($did)) {
                return $did;
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }
}
