<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Feature;

use BlackfinWebware\LaravelMailMerge\Mail\BasicMessage;
use BlackfinWebware\LaravelMailMerge\Models\EmailTemplate;
use BlackfinWebware\LaravelMailMerge\Tests\TestCase;
use Illuminate\Support\Facades\Mail;

/** The package's pages for managing templates, under /mailmerge/email-templates. */
class EmailTemplateControllerTest extends TestCase
{
    protected function form(array $fields = []): array
    {
        return $fields + ['name' => 'welcome', 'from' => 'events@example.com', 'subject' => 'Welcome to <<app_name>>',
                          'message' => 'Dear <<registrant_name>>'];
    }

    public function test_the_list()
    {
        $this->confirmationTemplate();

        $this->get('/mailmerge/email-templates')->assertOk()
             ->assertSee('conference_registration_confirmation')
             ->assertSee('/mailmerge/email-templates/create');
    }

    public function test_the_new_template_page_offers_the_groups_and_macros()
    {
        $this->get('/mailmerge/email-templates/create')->assertOk()
             ->assertSee('PaidRegistrants')
             ->assertSee('primary_contact_email')
             ->assertSee('registrant_name');
    }

    public function test_saving_a_new_template()
    {
        Mail::fake();

        $this->post('/mailmerge/email-templates', $this->form(['to' => "ada@example.com\ngrace@example.com, linus@example.com"]))
             ->assertRedirect();

        $template = EmailTemplate::where('name', 'welcome')->firstOrFail();
        $this->assertSame('ada@example.com,grace@example.com,linus@example.com', $template->to);
        $this->assertSame('Welcome to <<app_name>>', $template->subject);
        Mail::assertNothingSent();
    }

    public function test_template_names_are_unique()
    {
        $this->confirmationTemplate();

        $this->post('/mailmerge/email-templates', $this->form(['name' => 'conference_registration_confirmation']))
             ->assertSessionHasErrors('name');
        $this->assertSame(1, EmailTemplate::count());
    }

    public function test_sending_needs_a_group_or_recipients()
    {
        Mail::fake();

        $this->post('/mailmerge/email-templates', $this->form(['send' => 1]))->assertSessionHasErrors();

        $this->assertSame(0, EmailTemplate::count());
        Mail::assertNothingSent();
    }

    public function test_a_test_send_shows_the_merged_messages_without_sending()
    {
        Mail::fake();
        $this->registrants();

        $this->post('/mailmerge/email-templates', $this->form(['group' => 'PaidRegistrants', 'testsend' => 1]))
             ->assertOk()
             ->assertSee('MailMerge Results')
             ->assertSee('Dear Ada Lovelace', false)
             ->assertSee('Welcome to Conference Desk', false);
        Mail::assertNothingSent();
    }

    public function test_saving_and_sending_to_a_group()
    {
        Mail::fake();
        $this->inProduction();
        $this->registrants();

        $this->post('/mailmerge/email-templates', $this->form(['group' => 'PaidRegistrants', 'send' => 1]))
             ->assertRedirect()->assertSessionHas('alert-info', 'Successfully sent 3 emails.');

        Mail::assertSent(BasicMessage::class, 3);
    }

    public function test_a_send_that_fails_is_reported()
    {
        $this->inProduction();
        $this->registrants();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Connection refused'));

        $this->post('/mailmerge/email-templates', $this->form(['group' => 'PaidRegistrants', 'send' => 1]))
             ->assertSessionHas('alert-danger', fn ($text) => str_contains($text, 'Problems sending mail to ada@example.com'));
    }

    public function test_a_group_that_cannot_be_used_is_reported()
    {
        $this->post('/mailmerge/email-templates', $this->form(['group' => 'Nobody', 'testsend' => 1]))
             ->assertSessionHasErrors();
    }

    public function test_editing_a_template()
    {
        $template = $this->confirmationTemplate();
        $this->get("/mailmerge/email-templates/{$template->id}/edit")->assertOk()->assertSee('conference_registration_confirmation');
        $this->get('/mailmerge/email-templates/999/edit')->assertNotFound();

        $this->put("/mailmerge/email-templates/{$template->id}", ['name' => $template->name, 'subject' => 'Changed'])
             ->assertRedirect('/mailmerge/email-templates');

        $this->assertSame('Changed', $template->fresh()->subject);
    }

    public function test_an_edited_template_keeps_a_unique_name()
    {
        $template = $this->confirmationTemplate();
        EmailTemplate::create(['name' => 'other']);

        $this->put("/mailmerge/email-templates/{$template->id}", ['name' => 'other'])->assertSessionHasErrors('name');
        $this->put("/mailmerge/email-templates/{$template->id}", ['name' => ''])->assertSessionHasErrors('name');
        $this->assertSame('conference_registration_confirmation', $template->fresh()->name);
    }

    public function test_test_sending_and_sending_from_the_edit_page()
    {
        Mail::fake();
        $this->inProduction();
        $this->registrants();
        $template = $this->confirmationTemplate();

        $this->put("/mailmerge/email-templates/{$template->id}", ['name' => $template->name, 'group' => 'PaidRegistrants', 'testsend' => 1])
             ->assertOk()->assertSee('Dear Grace Hopper', false);
        Mail::assertNothingSent();

        $this->put("/mailmerge/email-templates/{$template->id}", ['name' => $template->name, 'group' => 'PaidRegistrants', 'send' => 1])
             ->assertRedirect('/mailmerge/email-templates')->assertSessionHas('alert-info', 'Successfully sent 3 emails.');
        Mail::assertSent(BasicMessage::class, 3);
    }

    public function test_cloning_a_template()
    {
        $template = $this->confirmationTemplate();
        $this->get("/mailmerge/email-templates/clone/{$template->id}")->assertOk()->assertSee('Dear &lt;&lt;registrant_name&gt;&gt;', false);

        $this->post('/mailmerge/email-templates/cloneUpdate', ['name' => $template->name] + $template->only('subject', 'message'))
             ->assertSessionHasErrors('name');

        $this->post('/mailmerge/email-templates/cloneUpdate', ['name' => 'copy'] + $template->only('subject', 'message'))
             ->assertRedirect('/mailmerge/email-templates');
        $this->assertSame($template->message, EmailTemplate::where('name', 'copy')->value('message'));
    }

    public function test_deleting_a_template()
    {
        $template = $this->confirmationTemplate();
        $keep = EmailTemplate::create(['name' => 'keep']);

        $this->from('/mailmerge/email-templates')->delete("/mailmerge/email-templates/{$template->id}")
             ->assertRedirect('/mailmerge/email-templates')->assertSessionHas('alert-info');

        $this->assertNull($template->fresh());
        $this->assertNotNull($keep->fresh());
        $this->delete('/mailmerge/email-templates/999')->assertNotFound();
    }

    public function test_the_macro_list_without_general_macros()
    {
        config(['mailmerge.macro_sets' => ['conference_registration' => \App\Mail\Merge\Macro\ConferenceRegistrationMacroExpansionGuide::class]]);

        $this->get('/mailmerge/email-templates/create')->assertOk()->assertSee('registrant_name');
    }
}
