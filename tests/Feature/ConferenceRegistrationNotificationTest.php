<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Feature;

use BlackfinWebware\LaravelMailMerge\Facade as MailMerge;
use BlackfinWebware\LaravelMailMerge\Mail\BasicMessage;
use BlackfinWebware\LaravelMailMerge\Models\EmailTemplate;
use BlackfinWebware\LaravelMailMerge\Tests\TestCase;

use Illuminate\Support\Facades\Mail;

class ConferenceRegistrationNotificationTest extends TestCase
{
    public function test_conference_registration_notification_creation_for_testsend()
    {
      $emailTemplate = EmailTemplate::whereName('conference_registration_confirmation')->first();
      $this->assertNotNull($emailTemplate);
      $emailsInHtml = MailMerge::composeBroadcast($emailTemplate, ['group' => 'DummyConferenceRegistrants']);
      /* make sure that all the macros were expanded -- since the output of this compose is intended for browser display,
        the double less than and double greater than will have been turned into html entities already */
      $this->assertStringNotContainsString('&lt;&lt;', $emailsInHtml, 'Contains double less than, some macro may not have been expanded');
      $this->assertStringNotContainsString('&gt;&gt;', $emailsInHtml, 'Contains double greater than, some macro may not have been expanded');
      /* test that they were expanded to values from the database seeding as well as test model */
      $this->assertStringContainsString('Dummy Conference', $emailsInHtml, 'Expected replacement value not found');
      $this->assertStringContainsString('Opening Ceremony', $emailsInHtml, 'Expected replacement value not found');
      $this->assertStringContainsString('taylor@example.com', $emailsInHtml, 'Expected replacement value not found');
      $this->assertStringContainsString('$1038.50', $emailsInHtml, 'Expected replacement value not found');
    }

    public function test_conference_registration_notification_creation_for_send()
    {
        Mail::fake();
        $emailTemplate = EmailTemplate::whereName('conference_registration_confirmation')->first();
        $this->assertNotNull($emailTemplate);
        $return = MailMerge::composeAndSendBroadcast($emailTemplate, ['group' => 'DummyConferenceRegistrants']);
        Mail::assertSent(BasicMessage::class, 5);
    }

}
