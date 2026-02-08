<?php

namespace SocialDept\AtpSupport\Microcosm;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SocialDept\AtpSupport\Concerns\HasConfig;
use SocialDept\AtpSupport\Microcosm\Data\GetRecordResponse;
use SocialDept\AtpSupport\Microcosm\Data\MiniDoc;

class SlingshotClient
{
    use HasConfig;

    protected Client $client;

    protected string $baseUrl;

    public function __construct(?string $baseUrl = null, ?int $timeout = null)
    {
        $this->baseUrl = rtrim(
            $baseUrl ?? $this->getConfig('atp-support.microcosm.slingshot.url', 'https://slingshot.microcosm.blue'),
            '/',
        );

        $this->client = new Client([
            'timeout' => $timeout ?? $this->getConfig('atp-support.microcosm.slingshot.timeout', 5),
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'Beacon/1.0',
            ],
        ]);
    }

    /**
     * Get a cached record by repo, collection, and rkey.
     *
     * @param  string  $repo  The DID of the repo
     * @param  string  $collection  The NSID of the collection
     * @param  string  $rkey  The record key
     * @param  string|null  $cid  Optional CID to match
     */
    public function getRecord(string $repo, string $collection, string $rkey, ?string $cid = null): GetRecordResponse
    {
        $query = [
            'repo' => $repo,
            'collection' => $collection,
            'rkey' => $rkey,
        ];

        if ($cid !== null) {
            $query['cid'] = $cid;
        }

        $data = $this->request('/xrpc/com.atproto.repo.getRecord', $query);

        return GetRecordResponse::fromArray($data);
    }

    /**
     * Get a cached record by AT-URI.
     *
     * @param  string  $atUri  The AT-URI (e.g. "at://did:plc:abc/app.bsky.feed.post/rkey")
     * @param  string|null  $cid  Optional CID to match
     */
    public function getRecordByUri(string $atUri, ?string $cid = null): GetRecordResponse
    {
        $query = ['at_uri' => $atUri];

        if ($cid !== null) {
            $query['cid'] = $cid;
        }

        $data = $this->request('/xrpc/blue.microcosm.repo.getRecordByUri', $query);

        return GetRecordResponse::fromArray($data);
    }

    /**
     * Resolve a DID or handle to a minimal identity document.
     *
     * @param  string  $identifier  A DID or handle
     */
    public function resolveMiniDoc(string $identifier): MiniDoc
    {
        $data = $this->request('/xrpc/blue.microcosm.identity.resolveMiniDoc', [
            'identifier' => $identifier,
        ]);

        return MiniDoc::fromArray($data);
    }

    /**
     * @return array<string, mixed>
     */
    protected function request(string $endpoint, array $query): array
    {
        try {
            $response = $this->client->get("{$this->baseUrl}{$endpoint}", [
                'query' => $query,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (! is_array($data)) {
                throw MicrocosmException::invalidResponse($endpoint);
            }

            return $data;
        } catch (GuzzleException $e) {
            throw MicrocosmException::requestFailed($endpoint, $e->getMessage());
        }
    }
}
