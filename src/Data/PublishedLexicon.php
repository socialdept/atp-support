<?php

namespace SocialDept\AtpSupport\Data;

/**
 * A lexicon as its authority publishes it: a com.atproto.lexicon.schema
 * record, keyed by the NSID, in the repo of the DID its DNS names.
 */
final class PublishedLexicon
{
    /**
     * @param  array<string, mixed>  $schema  The lexicon, without the record's $type
     */
    public function __construct(
        public readonly string $nsid,
        public readonly string $did,
        public readonly string $uri,
        public readonly ?string $cid,
        public readonly array $schema,
    ) {
    }
}
