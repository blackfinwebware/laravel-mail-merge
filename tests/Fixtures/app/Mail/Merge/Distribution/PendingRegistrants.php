<?php

namespace App\Mail\Merge\Distribution;

use BlackfinWebware\LaravelMailMerge\Tests\Models\User;
use Illuminate\Database\Eloquent\Collection;

/** Registrants still owing; found by name without the suffix. */
class PendingRegistrants extends PaidConferenceRegistrantsMergeDistribution
{
    public function getDistributionList(): Collection
    {
        return User::join('conference_registrations', 'users.id', 'conference_registrations.user_id')
                   ->where('conference_registrations.status', 'Pending')
                   ->select('users.*')
                   ->get();
    }

    public function getNexusForUser($user): object
    {
        return \BlackfinWebware\LaravelMailMerge\Tests\Models\ConferenceRegistration::where('user_id', $user->id)->first();
    }
}
