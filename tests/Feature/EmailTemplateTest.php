<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Feature;

use BlackfinWebware\LaravelMailMerge\Tests\Models\ConferenceRegistration;
use BlackfinWebware\LaravelMailMerge\Tests\TestCase;

use BlackfinWebware\LaravelMailMerge\Models\EmailTemplate;

class EmailTemplateTest extends TestCase
{
    public function test_email_template_creation()
    {
      /*  $email_template = EmailTemplate::create([
                                                    'name' => 'password_reset_request',
                                                    'to' => '',
                                                    'from' => 'tom@blackfin.biz',
                                                    'subject' => 'Password Reset Request',
                                                    'message' => 'This is an automated message from the <<app_name>> System and was triggered by a user action. Someone has requested to have the password reset for the account with the email address of <<prr_email>> and username <<member_username>>. If this was you, please confirm this by clicking on this link: <<password_reset_link>> and following the instructions that you find there. This must be done within one week of the initial request or it will no longer work.

If you did not submit this request, please ignore it. If it continues to happen, please forward the message to <<primary_contact_email>>.'
                                     ]); */
       // $this->assertNotNull($email_template);
        $this->assertEquals(2, EmailTemplate::count());
      //  $this->assertEquals(1, ConferenceRegistration::count());
    }
}
