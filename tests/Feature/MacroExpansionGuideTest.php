<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Feature;

use BlackfinWebware\LaravelMailMerge\Mail\Merge\Macro\GeneralMacroExpansionGuide;
use BlackfinWebware\LaravelMailMerge\Mail\Merge\Macro\ObjectMacroExpansionGuide;
use BlackfinWebware\LaravelMailMerge\Models\EmailTemplate;
use BlackfinWebware\LaravelMailMerge\Tests\TestCase;
use Illuminate\Support\Facades\Log;

/** A guide for a "ticket": <<ticket_seat>> and <<ticket_gate>>, from the ticket object. */
class TicketGuide extends ObjectMacroExpansionGuide
{
    public $quickSearch = ['ticket_'];
    public $macros = ['ticket_seat', 'ticket_gate'];
    public $requiredObjects = ['ticket'];

    public function expansions()
    {
        return ['ticket_seat' => $this->objects['ticket']->seat, 'ticket_gate' => $this->objects['ticket']->gate];
    }

    /** The protected helpers, for testing them directly */
    public function __call($method, $arguments)
    {
        return $this->$method(...$arguments);
    }
}

class MacroExpansionGuideTest extends TestCase
{
    protected function email(array $attributes = []): EmailTemplate
    {
        return new EmailTemplate($attributes + ['from' => 'events@example.com', 'cc' => '', 'bcc' => '',
                                                'subject' => 'Seat <<ticket_seat>>', 'message' => 'Seat <<ticket_seat>>, gate <<ticket_gate>>.']);
    }

    protected function ticket(): object
    {
        return (object) ['seat' => '14C', 'gate' => 'B'];
    }

    public function test_macros_are_expanded_in_the_subject_and_message()
    {
        $email = $this->email();

        (new TicketGuide($email, ['ticket' => $this->ticket()]))->run();

        $this->assertSame('Seat 14C', $email->subject);
        $this->assertSame('Seat 14C, gate B.', $email->message);
    }

    public function test_a_guide_whose_macros_are_not_in_the_message_does_nothing()
    {
        Log::spy();
        $email = $this->email(['subject' => 'Hello', 'message' => 'No macros of this guide here: <<seat>>.']);

        (new TicketGuide($email, []))->run();

        $this->assertSame('No macros of this guide here: <<seat>>.', $email->message);
        Log::shouldNotHaveReceived('error');
    }

    public function test_without_its_object_a_guide_logs_and_leaves_the_macros()
    {
        Log::spy();
        $email = $this->email();

        (new TicketGuide($email, ['ticket' => 'not an object']))->run();

        $this->assertSame('Seat <<ticket_seat>>, gate <<ticket_gate>>.', $email->message);
        Log::shouldHaveReceived('error')->withArgs(fn ($text) => str_contains($text, 'missing a required object: ticket'));
    }

    public function test_quick_search_finds_a_prefix_anywhere_in_the_message()
    {
        $email = $this->email(['subject' => '', 'message' => 'Your <<ticket_row>>']);
        $guide = new TicketGuide($email, []);
        $this->assertTrue($guide->applies(), 'quickSearch matches <<ticket_ as a prefix');

        $guide->quickSearch = [];
        $this->assertFalse($guide->applies(), 'macros must match exactly: <<ticket_row>> is not one of them');
    }

    public function test_contains_macros_in_the_chosen_fields()
    {
        $email = $this->email(['subject' => '<<ticket_seat>>', 'message' => 'none', 'cc' => '<<ticket_gate>>']);
        $guide = new TicketGuide($email, []);

        $this->assertFalse($guide->containsMacros('ticket_seat'), 'message only');
        $this->assertTrue($guide->containsMacros('ticket_seat', 'both'));
        $this->assertFalse($guide->containsMacros('ticket_gate', 'both'));
        $this->assertTrue($guide->containsMacros(['nope', 'ticket_gate'], 'all'));
        $this->assertTrue($guide->containsMacros('ticket_', 'both', true), 'as a prefix');
    }

    public function test_replace_macro_in_the_chosen_fields()
    {
        $email = $this->email(['subject' => '<<x>>', 'message' => '<<x>>', 'cc' => '<<x>>@example.com', 'from' => '<<x>>@example.com']);
        $guide = new TicketGuide($email, []);

        $guide->replaceMacro('x', 'ada', 'address');
        $this->assertSame(['<<x>>', '<<x>>', 'ada@example.com', 'ada@example.com'], [$email->subject, $email->message, $email->cc, $email->from]);

        $guide->replaceMacro('x', 'Ada', 'subject');
        $this->assertSame(['Ada', '<<x>>'], [$email->subject, $email->message]);

        $guide->replaceMacro('[x]', 'y', 'message', true);   // literal: no << >> added
        $email->message = 'a [x] b';
        $guide->replaceMacro('[x]', 'y', 'message', true);
        $this->assertSame('a y b', $email->message);
    }

    public function test_a_missing_value_expands_to_nothing()
    {
        $email = $this->email();

        (new TicketGuide($email, ['ticket' => (object) ['seat' => null, 'gate' => 'B']]))->run();

        $this->assertSame('Seat , gate B.', $email->message);
    }

    public function test_the_general_guide_uses_the_general_macros_in_the_config()
    {
        $email = $this->email(['subject' => '<<app_name>>', 'message' => 'Write to <<primary_contact_email>>.']);

        (new GeneralMacroExpansionGuide($email, []))->run();

        $this->assertSame('Conference Desk', $email->subject);
        $this->assertSame('Write to help@example.com.', $email->message);
    }

    public function test_the_general_guide_without_general_macros()
    {
        config(['mailmerge.macro_sets' => []]);
        $email = $this->email(['message' => '<<app_name>>']);

        (new GeneralMacroExpansionGuide($email, []))->run();

        $this->assertSame('<<app_name>>', $email->message);
    }
}
