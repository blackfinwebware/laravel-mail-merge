<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Feature;

use BlackfinWebware\LaravelMailMerge\Tests\TestCase;

use BlackfinWebware\LaravelMailMerge\Tests\Models\ConferenceRegistration;

class ConferenceRegistrationTest extends TestCase
{
    public function test_conference_registration_creation()
    {
      /*  created and loaded in DatabaseSeeder */
        $this->assertEquals(5, ConferenceRegistration::count());
    }
}
