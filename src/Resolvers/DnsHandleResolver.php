<?php

namespace SocialDept\AtpSupport\Resolvers;

use SocialDept\AtpSupport\Contracts\HandleResolutionMethod;
use SocialDept\AtpSupport\Identity;

class DnsHandleResolver implements HandleResolutionMethod
{
    /** @var callable(string, int): array|false */
    protected $dnsLookup;

    /**
     * @param  callable|null  $dnsLookup  Custom DNS lookup function for testing. Signature: (string $hostname, int $type): array|false
     */
    public function __construct(?callable $dnsLookup = null)
    {
        $this->dnsLookup = $dnsLookup ?? 'dns_get_record';
    }

    public function attempt(string $handle): ?string
    {
        try {
            $hostname = "_atproto.{$handle}";

            $records = ($this->dnsLookup)($hostname, DNS_TXT);

            if ($records === false || $records === []) {
                return null;
            }

            foreach ($records as $record) {
                $txt = $record['txt'] ?? '';

                if (! str_starts_with($txt, 'did=')) {
                    continue;
                }

                $did = substr($txt, 4);

                if (Identity::isDid($did)) {
                    return $did;
                }
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }
}
