<?php

namespace App\Mail\Merge\Distribution;

use BlackfinWebware\LaravelMailMerge\Mail\Merge\Distribution\MergeDistribution;
use Illuminate\Database\Eloquent\Collection;

class Broken extends MergeDistribution
{
    protected $nexusObject = 'ConferenceRegistration';

    public function getDistributionList(): Collection
    {
        throw new \RuntimeException('database unavailable');
    }
}
