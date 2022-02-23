<?php

namespace  BlackfinWebware\LaravelMailMerge\Tests\Mail\Merge\Distribution;

use BlackfinWebware\LaravelMailMerge\Tests\Models\ConferenceRegistration;
use BlackfinWebware\LaravelMailMerge\Mail\Merge\Distribution\MergeDistribution;
use BlackfinWebware\LaravelMailMerge\Tests\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DummyConferenceRegistrants extends MergeDistribution
{
    protected $groupName = 'Dummy Conference Registrants';
    //object in system that is the primary focus for this merge distribution -- presumably in the App\Models namespace
    protected $nexusObject = 'ConferenceRegistration';

    /**
     * Returns an array or Collection of User objects that each have email and name attributes.
     *
     * @return Collection
     */
    public function getDistributionList(): Collection
    {
        return User::join('conference_registrations', 'users.id', 'conference_registrations.user_id')
                    ->where('conference_registrations.conference', 'Dummy Conference')
                    ->where('conference_registrations.status', 'Paid in Full')
                    ->select('users.*')
                    ->orderBy('users.name')
                    ->get();
    }

    /**
     * When merging the messages and users, and trying to resolve the macros, this allows the process to map
     * the email recipient(user) back to the object at the nexus that will provide the correct values for macro expansion.
     *
     * @param $user
     * @return object instance of nexus object
     */
    public function getNexusForUser($user): object
    {
        return ConferenceRegistration::where('conference_registrations.conference', 'Dummy Conference')
                                     ->where('conference_registrations.status', 'Paid in Full')
                                     ->where('conference_registrations.user_id', $user->id)
                                     ->select('conference_registrations.*')
                                     ->first();
    }

}
