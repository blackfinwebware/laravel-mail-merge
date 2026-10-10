<?php

namespace App\Mail\Merge\Distribution;

use BlackfinWebware\LaravelMailMerge\Mail\Merge\Distribution\MergeDistribution;

/** The base class's defaults: no one. */
class Nobody extends MergeDistribution
{
    protected $nexusObject = 'ConferenceRegistration';
}
