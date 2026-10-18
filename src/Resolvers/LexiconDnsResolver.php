<?php

namespace SocialDept\AtpSupport\Resolvers;

use Illuminate\Support\Facades\Http;
use SocialDept\AtpSupport\Data\PublishedLexicon;
use SocialDept\AtpSupport\Exceptions\LexiconResolutionException;
use SocialDept\AtpSupport\Identity;
use SocialDept\AtpSupport\Nsid;
use SocialDept\AtpSupport\Resolver;

/**
 * Lexicons as the network publishes them.
 *
 * An NSID's authority publishes a TXT record at _lexicon.<authority domain>
 * holding did=<DID>, and the schema as a com.atproto.lexicon.schema record,
 * keyed by the NSID, in that DID's repo.
 */
class LexiconDnsResolver
{
    public const COLLECTION = 'com.atproto.lexicon.schema';

    /**
     * HTTP timeout in seconds.
     */
    protected int $httpTimeout;

    /**
     * HTTP connect timeout in seconds.
     */
    protected float $connectTimeout;

    /**
     * Resolver instance.
     */
    protected Resolver $resolver;

    /** @var callable(string, int): array|false */
    protected $dnsLookup;

    /**
     * Create a new LexiconDnsResolver.
     *
     * @param  callable|null  $dnsLookup  Custom DNS lookup function for testing. Signature: (string $hostname, int $type): array|false
     */
    public function __construct(Resolver $resolver, int $httpTimeout = 10, float $connectTimeout = 2, ?callable $dnsLookup = null)
    {
        $this->resolver = $resolver;
        $this->httpTimeout = $httpTimeout;
        $this->connectTimeout = $connectTimeout;
        $this->dnsLookup = $dnsLookup ?? 'dns_get_record';
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
        try {
            $published = $this->fetch($nsid);
        } catch (LexiconResolutionException $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }

        if ($published === null) {
            throw new \RuntimeException("No published lexicon found for NSID: {$nsid}");
        }

        // As the record value, the way this has always answered.
        return ['$type' => self::COLLECTION, ...$published->schema];
    }

    /**
     * The published lexicon, with the repo, URI and CID of its record. Null
     * when the authority names no DID in DNS, or has not published one.
     *
     * @throws LexiconResolutionException when the authority's PDS cannot be read
     */
    public function fetch(string $nsid): ?PublishedLexicon
    {
        $did = $this->lookupDns(Nsid::parse($nsid)->getAuthority());

        return $did === null ? null : $this->fetchFrom($did, $nsid);
    }

    /**
     * The lexicon a given repo publishes for an NSID, or null if it has none.
     * Useful to check a repo before DNS points at it.
     *
     * @throws LexiconResolutionException when the repo's PDS cannot be read
     */
    public function fetchFrom(string $did, string $nsid): ?PublishedLexicon
    {
        $pds = $this->resolver->resolvePds($did)
            ?? throw LexiconResolutionException::unreachable($nsid, "{$did} names no PDS");

        try {
            $response = $this->getRecord($pds, $did, $nsid);
        } catch (\Throwable $e) {
            throw LexiconResolutionException::unreachable($nsid, $e->getMessage());
        }

        if ($response->status() === 400 && in_array($response->json('error'), ['RecordNotFound', 'NotFound'], true)) {
            return null;
        }

        if (! $response->successful()) {
            throw LexiconResolutionException::unreachable($nsid, "{$pds} answered HTTP {$response->status()}");
        }

        $value = $response->json('value');

        if (! is_array($value) || ! isset($value['lexicon'])) {
            throw LexiconResolutionException::unreachable($nsid, "{$pds} returned a record that is not a lexicon");
        }

        unset($value['$type']);

        return new PublishedLexicon(
            nsid: $nsid,
            did: $did,
            uri: is_string($uri = $response->json('uri')) ? $uri : 'at://'.$did.'/'.self::COLLECTION.'/'.$nsid,
            cid: is_string($cid = $response->json('cid')) ? $cid : null,
            schema: $value,
        );
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
            $records = ($this->dnsLookup)($hostname, DNS_TXT);

            if ($records === false || empty($records)) {
                return null;
            }

            // Look for TXT record with did= prefix
            foreach ($records as $record) {
                $txt = $record['txt'] ?? '';

                if (str_starts_with($txt, 'did=') && Identity::isDid($did = substr($txt, 4))) {
                    return $did;
                }
            }
        } catch (\Throwable) {
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
        $response = $this->getRecord($pdsEndpoint, $did, $nsid);

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

    protected function getRecord(string $pdsEndpoint, string $did, string $nsid): \Illuminate\Http\Client\Response
    {
        return Http::timeout($this->httpTimeout)->connectTimeout($this->connectTimeout)
            ->acceptJson()
            ->get(rtrim($pdsEndpoint, '/').'/xrpc/com.atproto.repo.getRecord', [
                'repo' => $did,
                'collection' => self::COLLECTION,
                'rkey' => $nsid,
            ]);
    }
}
