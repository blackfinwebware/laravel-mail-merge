<?php

namespace App\Mail\Merge\Distribution;

use BlackfinWebware\LaravelMailMerge\Mail\Merge\Distribution\MergeDistribution;
use BlackfinWebware\LaravelMailMerge\Tests\Models\ConferenceRegistration;
use BlackfinWebware\LaravelMailMerge\Tests\Models\User;
use Illuminate\Database\Eloquent\Collection;

/** Everyone paid in full for the Dummy Conference; found by name with the MergeDistribution suffix. */
class PaidConferenceRegistrantsMergeDistribution extends MergeDistribution
{
    protected $groupName = 'Paid Conference Registrants';
    protected $nexusObject = 'ConferenceRegistration';

    public function getDistributionList(): Collection
    {
        return User::join('conference_registrations', 'users.id', 'conference_registrations.user_id')
                   ->where('conference_registrations.conference', 'Dummy Conference')
                   ->where('conference_registrations.status', 'Paid in Full')
                   ->select('users.*')
                   ->orderBy('users.name')
                   ->get();
    }

    public function getNexusForUser($user): object
    {
        return ConferenceRegistration::where('conference', 'Dummy Conference')
                                     ->where('status', 'Paid in Full')
                                     ->where('user_id', $user->id)
                                     ->first();
    }
}
