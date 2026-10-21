<?php

namespace SocialDept\AtpSupport\Microcosm;

/**
 * The record does not exist, as far as the service knows. Not a failure of
 * the service: the answer is final until the record is written.
 */
class RecordNotFoundException extends MicrocosmException
{
}
