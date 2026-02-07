<?php

namespace SocialDept\AtpSupport\Resolvers;

use Illuminate\Support\Facades\Http;
use SocialDept\AtpSupport\Nsid;
use SocialDept\AtpSupport\Resolver;

class LexiconDnsResolver
{
    /**
     * HTTP timeout in seconds.
     */
    protected int $httpTimeout;

    /**
     * Resolver instance.
     */
    protected Resolver $resolver;

    /**
     * Create a new LexiconDnsResolver.
     */
    public function __construct(Resolver $resolver, int $httpTimeout = 10)
    {
        $this->resolver = $resolver;
        $this->httpTimeout = $httpTimeout;
    }

    /**
     * Resolve NSID to Lexicon schema via DNS and XRPC.
     *
     * @return array Raw schema array
     *
     * @throws \RuntimeException
     */
    public function resolve(string $nsid): array
    {
        $nsidParsed = Nsid::parse($nsid);

        // Step 1: Query DNS TXT record for DID
        $did = $this->lookupDns($nsidParsed->getAuthority());
        if ($did === null) {
            throw new \RuntimeException("No DID found for NSID: {$nsid}");
        }

        // Step 2: Resolve DID to PDS endpoint
        $pdsUrl = $this->resolver->resolvePds($did);
        if ($pdsUrl === null) {
            throw new \RuntimeException("No PDS endpoint found for DID: {$did}");
        }

        // Step 3: Fetch lexicon schema from repository
        return $this->retrieveSchema($pdsUrl, $did, $nsid);
    }

    /**
     * Perform DNS TXT lookup for _lexicon.{authority}.
     */
    public function lookupDns(string $authority): ?string
    {
        // Convert authority to domain (e.g., pub.leaflet -> leaflet.pub)
        $parts = explode('.', $authority);
        $domain = implode('.', array_reverse($parts));

        // Query DNS TXT record at _lexicon.<domain>
        $hostname = "_lexicon.{$domain}";

        try {
            $records = dns_get_record($hostname, DNS_TXT);

            if ($records === false || empty($records)) {
                return null;
            }

            // Look for TXT record with did= prefix
            foreach ($records as $record) {
                if (isset($record['txt']) && str_starts_with($record['txt'], 'did=')) {
                    return substr($record['txt'], 4); // Remove 'did=' prefix
                }
            }
        } catch (\Exception $e) {
            // DNS query failed
            return null;
        }

        return null;
    }

    /**
     * Retrieve schema via XRPC from PDS.
     */
    public function retrieveSchema(string $pdsEndpoint, string $did, string $nsid): array
    {
        $response = Http::timeout($this->httpTimeout)
            ->get("{$pdsEndpoint}/xrpc/com.atproto.repo.getRecord", [
                'repo' => $did,
                'collection' => 'com.atproto.lexicon.schema',
                'rkey' => $nsid,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Failed to retrieve schema for NSID: {$nsid}");
        }

        $data = $response->json();

        // Extract the lexicon schema from the record value
        if (isset($data['value']) && is_array($data['value']) && isset($data['value']['lexicon'])) {
            return $data['value'];
        }

        throw new \RuntimeException("Invalid schema response for NSID: {$nsid}");
    }
}
