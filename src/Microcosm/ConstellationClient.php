<?php

namespace SocialDept\AtpSupport\Microcosm;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SocialDept\AtpSupport\Concerns\HasConfig;
use SocialDept\AtpSupport\Microcosm\Data\GetBacklinksResponse;
use SocialDept\AtpSupport\Microcosm\Data\LinkSummary;

class ConstellationClient
{
    use HasConfig;

    protected Client $client;

    protected string $baseUrl;

    public function __construct(?string $baseUrl = null, ?int $timeout = null)
    {
        $this->baseUrl = rtrim(
            $baseUrl ?? $this->getConfig('atp-support.microcosm.constellation.url', 'https://constellation.microcosm.blue'),
            '/',
        );

        $this->client = new Client([
            'timeout' => $timeout ?? $this->getConfig('atp-support.microcosm.constellation.timeout', 10),
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'Beacon/1.0',
            ],
        ]);
    }

    /**
     * Get backlinks pointing at a subject.
     *
     * @param  string  $subject  The target (AT-URI, DID, or URL)
     * @param  string  $source  Collection and path in "collection:path" format (e.g. "app.bsky.feed.like:subject.uri")
     * @param  array<string>|null  $dids  Filter to specific DIDs
     * @param  int  $limit  Max results (1–100, default 16)
     * @param  bool  $reverse  Reverse chronological order
     * @param  string|null  $cursor  Pagination cursor
     */
    public function getBacklinks(
        string $subject,
        string $source,
        ?array $dids = null,
        int $limit = 16,
        bool $reverse = false,
        ?string $cursor = null,
    ): GetBacklinksResponse {
        $query = [
            'subject' => $subject,
            'source' => $source,
            'limit' => $limit,
        ];

        if ($dids !== null) {
            $query['did'] = $dids;
        }

        if ($reverse) {
            $query['reverse'] = 'true';
        }

        if ($cursor !== null) {
            $query['cursor'] = $cursor;
        }

        $data = $this->request('/xrpc/blue.microcosm.links.getBacklinks', $query);

        return GetBacklinksResponse::fromArray($data);
    }

    /**
     * Get the total count of backlinks pointing at a subject.
     *
     * @param  string  $subject  The target (AT-URI, DID, or URL)
     * @param  string  $source  Collection and path in "collection:path" format
     */
    public function getBacklinksCount(string $subject, string $source): int
    {
        $data = $this->request('/xrpc/blue.microcosm.links.getBacklinksCount', [
            'subject' => $subject,
            'source' => $source,
        ]);

        return $data['total'] ?? 0;
    }

    /**
     * Get a summary of all link sources pointing at a target.
     *
     * @param  string  $target  The target (AT-URI, DID, or URL)
     */
    public function getAllLinks(string $target): LinkSummary
    {
        $data = $this->request('/links/all', [
            'target' => $target,
        ]);

        return LinkSummary::fromArray($data);
    }

    /**
     * Get many-to-many relationship counts.
     *
     * @param  string  $subject  The target subject
     * @param  string  $source  Collection and path in "collection:path" format
     * @param  string  $pathToOther  Path to the related subject within the source record
     * @param  array<string>|null  $dids  Filter to specific DIDs
     * @param  array<string>|null  $otherSubjects  Filter to specific other subjects
     * @param  int  $limit  Max results
     */
    public function getManyToManyCounts(
        string $subject,
        string $source,
        string $pathToOther,
        ?array $dids = null,
        ?array $otherSubjects = null,
        int $limit = 16,
    ): array {
        $query = [
            'subject' => $subject,
            'source' => $source,
            'pathToOther' => $pathToOther,
            'limit' => $limit,
        ];

        if ($dids !== null) {
            $query['did'] = $dids;
        }

        if ($otherSubjects !== null) {
            $query['otherSubject'] = $otherSubjects;
        }

        $data = $this->request('/xrpc/blue.microcosm.links.getManyToManyCounts', $query);

        return $data['counts_by_other_subject'] ?? [];
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
