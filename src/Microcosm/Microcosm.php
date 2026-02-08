<?php

namespace SocialDept\AtpSupport\Microcosm;

class Microcosm
{
    public function __construct(
        protected ConstellationClient $constellation,
        protected SlingshotClient $slingshot,
    ) {}

    public function constellation(): ConstellationClient
    {
        return $this->constellation;
    }

    public function slingshot(): SlingshotClient
    {
        return $this->slingshot;
    }
}
