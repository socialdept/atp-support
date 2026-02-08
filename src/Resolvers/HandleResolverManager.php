<?php

namespace SocialDept\AtpSupport\Resolvers;

use SocialDept\AtpSupport\Contracts\HandleResolutionMethod;
use SocialDept\AtpSupport\Contracts\HandleResolver;
use SocialDept\AtpSupport\Exceptions\HandleResolutionException;
use SocialDept\AtpSupport\Identity;

class HandleResolverManager implements HandleResolver
{
    /** @var HandleResolutionMethod[] */
    protected array $methods;

    /**
     * @param  HandleResolutionMethod[]  $methods  Ordered list of resolution methods to try
     */
    public function __construct(array $methods)
    {
        $this->methods = $methods;
    }

    public function resolve(string $handle): string
    {
        if (! Identity::isHandle($handle)) {
            throw HandleResolutionException::invalidFormat($handle);
        }

        foreach ($this->methods as $method) {
            $did = $method->attempt($handle);

            if ($did !== null) {
                return $did;
            }
        }

        throw HandleResolutionException::resolutionFailed($handle, 'All resolution methods failed');
    }
}
