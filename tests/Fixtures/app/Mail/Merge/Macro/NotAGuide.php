<?php

namespace App\Mail\Merge\Macro;

/** In the Macro folder but not a guide: the class scan must skip it. */
class NotAGuide
{
    public function __construct()
    {
        throw new \LogicException('NotAGuide should never be instantiated');
    }
}
