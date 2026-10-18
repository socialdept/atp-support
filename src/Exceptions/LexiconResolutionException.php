<?php

namespace SocialDept\AtpSupport\Exceptions;

/**
 * A lexicon's authority could not be read: its DID names no PDS, the PDS
 * did not answer, or it answered with something that is not a schema. Not
 * thrown when a lexicon is simply unpublished, which callers must be able
 * to tell apart.
 */
class LexiconResolutionException extends ResolverException
{
    public static function unreachable(string $nsid, string $reason): self
    {
        return new self("Could not read lexicon {$nsid}: {$reason}");
    }
}
