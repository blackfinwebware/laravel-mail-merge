<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Mail\Merge\Macro;

use BlackfinWebware\LaravelMailMerge\Mail\Merge\Macro\ObjectMacroExpansionGuide;

class ConferenceRegistrationMacroExpansionGuide extends ObjectMacroExpansionGuide
{
    public $quickSearch = [];
    public $macros = ['conference_name',
                      'conference_dates',
                      'conference_registration_date',
                      'conference_registration_amount_paid',
                      'conference_registration_summary',
                      'registrant_name'];
    public $requiredObjects = ['conference_registration'];

    public function expansions(){
        return ['conference_name' => $this->objects['conference_registration']->conference,
                'conference_registration_date' => $this->objects['conference_registration']->created_at,
                'conference_registration_amount_paid' => $this->objects['conference_registration']->total_paid,
                'conference_registration_summary' => $this->objects['conference_registration']->getBriefSummary(),
                'registrant_name'  => $this->objects['conference_registration']->registrant->name];
    }
}
