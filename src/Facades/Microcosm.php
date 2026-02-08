<?php

namespace SocialDept\AtpSupport\Facades;

use Illuminate\Support\Facades\Facade;
use SocialDept\AtpSupport\Microcosm\ConstellationClient;
use SocialDept\AtpSupport\Microcosm\SlingshotClient;

/**
 * @method static ConstellationClient constellation()
 * @method static SlingshotClient slingshot()
 *
 * @see \SocialDept\AtpSupport\Microcosm\Microcosm
 */
class Microcosm extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'microcosm';
    }
}
