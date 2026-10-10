<?php

namespace App\Mail\Merge\Macro;

use BlackfinWebware\LaravelMailMerge\Mail\Merge\Macro\ObjectMacroExpansionGuide;

class ConferenceRegistrationMacroExpansionGuide extends ObjectMacroExpansionGuide
{
    public $quickSearch = ['conference_', 'registrant_'];
    public $macros = ['conference_name',
                      'conference_registration_amount_paid',
                      'conference_registration_summary',
                      'registrant_name',
                      'registrant_email'];
    public $requiredObjects = ['conference_registration'];

    public function expansions()
    {
        $registration = $this->objects['conference_registration'];

        return ['conference_name'                     => $registration->conference,
                'conference_registration_amount_paid' => $registration->total_paid,
                'conference_registration_summary'     => $registration->getBriefSummary(),
                'registrant_name'                     => $registration->registrant->name,
                'registrant_email'                    => $registration->registrant->email];
    }
}
