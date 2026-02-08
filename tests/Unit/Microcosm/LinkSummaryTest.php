<?php

namespace SocialDept\AtpSupport\Tests\Unit\Microcosm;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\Microcosm\Data\LinkSummary;

class LinkSummaryTest extends TestCase
{
    private function makeSummary(): LinkSummary
    {
        return LinkSummary::fromArray([
            'links' => [
                'app.bsky.feed.like' => [
                    '.subject.uri' => ['records' => 150, 'distinct_dids' => 120],
                ],
                'app.bsky.feed.repost' => [
                    '.subject.uri' => ['records' => 30, 'distinct_dids' => 25],
                ],
                'app.bsky.graph.follow' => [
                    '.subject' => ['records' => 500, 'distinct_dids' => 490],
                ],
            ],
        ]);
    }

    public function test_it_can_be_created_from_array(): void
    {
        $summary = $this->makeSummary();

        $this->assertCount(3, $summary->links);
        $this->assertArrayHasKey('app.bsky.feed.like', $summary->links);
        $this->assertArrayHasKey('app.bsky.feed.repost', $summary->links);
        $this->assertArrayHasKey('app.bsky.graph.follow', $summary->links);
    }

    public function test_it_handles_empty_links(): void
    {
        $summary = LinkSummary::fromArray(['links' => []]);

        $this->assertSame([], $summary->links);
    }

    public function test_it_handles_missing_links_key(): void
    {
        $summary = LinkSummary::fromArray([]);

        $this->assertSame([], $summary->links);
    }

    public function test_for_collection_returns_matching_paths(): void
    {
        $summary = $this->makeSummary();
        $likes = $summary->forCollection('app.bsky.feed.like');

        $this->assertCount(1, $likes);
        $this->assertSame(150, $likes['.subject.uri']['records']);
        $this->assertSame(120, $likes['.subject.uri']['distinct_dids']);
    }

    public function test_for_collection_returns_empty_for_unknown(): void
    {
        $summary = $this->makeSummary();

        $this->assertSame([], $summary->forCollection('app.bsky.feed.post'));
    }

    public function test_total_sums_all_record_counts(): void
    {
        $summary = $this->makeSummary();

        $this->assertSame(680, $summary->total());
    }

    public function test_total_returns_zero_for_empty(): void
    {
        $summary = LinkSummary::fromArray([]);

        $this->assertSame(0, $summary->total());
    }
}
