<?php

namespace BlackfinWebware\LaravelMailMerge\Mail\Merge\Distribution;

use Illuminate\Database\Eloquent\Collection;

abstract class MergeDistribution
{
    protected $groupName = '';
    //object in system that is the primary focus for this merge distribution
    protected $nexusObject = '';

    /**
     * Returns a Collection of User objects that each have email and name attributes.
     *
     * @return array
     */
    public function getDistributionList(): Collection
    {
        return [];
    }

    /**
     * When merging the messages and users, and trying to resolve the macros, this allows the process to map
     * the email recipient(user) back to the object at the nexus that will allow for correct macro expansion.
     *
     * @param $user
     * @return object
     */
    public function getNexusForUser($user): object
    {
    }

    /**
     * Return the nexus object name.
     *
     * @return string
     */
    public function getNexusObjectName(): string
    {
        return $this->nexusObject;
    }
}
