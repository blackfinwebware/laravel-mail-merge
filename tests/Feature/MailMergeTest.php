<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Feature;

use App\Mail\Merge\Distribution\PaidConferenceRegistrantsMergeDistribution;
use App\Mail\Merge\Distribution\PendingRegistrants;
use App\Mail\Merge\Macro\ConferenceRegistrationMacroExpansionGuide;
use BlackfinWebware\LaravelMailMerge\Exceptions\RecipientNotFoundException;
use BlackfinWebware\LaravelMailMerge\Exceptions\TemplateNotFoundException;
use BlackfinWebware\LaravelMailMerge\LaravelMailMerge;
use BlackfinWebware\LaravelMailMerge\Mail\BasicMessage;
use BlackfinWebware\LaravelMailMerge\Models\EmailTemplate;
use BlackfinWebware\LaravelMailMerge\Tests\TestCase;
use BlackfinWebware\LaravelMailMerge\Utils\ClassFinder;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The three ways in: composeBroadcast (preview a merge as HTML), composeAndSendBroadcast (send one
 * merged message per member of a group, or to the template's To), and composeAndSendNotification
 * (send one named template to one recipient).
 */
class MailMergeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Previewing a broadcast
    // -------------------------------------------------------------------------

    public function test_a_group_preview_has_one_fully_merged_message_per_member()
    {
        Mail::fake();
        $this->registrants();

        $html = LaravelMailMerge::composeBroadcast($this->confirmationTemplate(), ['group' => 'PaidRegistrants']);

        $this->assertSame(3, substr_count($html, "<div class='message_container'>"), 'Ada, Grace and Linus; not Margaret (pending)');
        $this->assertStringNotContainsString('&lt;&lt;', $html, 'Every macro expanded');
        foreach (['Message #: 1<br>From: events@example.com<br>To: ada@example.com', 'Message #: 2', 'To: grace@example.com',
                  'Message #: 3', 'To: linus@example.com', 'Dear Ada Lovelace', 'Dear Grace Hopper', 'Dear Linus Torvalds',
                  'Subject: Your Dummy Conference registration', 'We have received $1038.50', 'Opening Ceremony; Full Tutorials',
                  'Write to help@example.com. -- Conference Desk'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('Margaret', $html);
        Mail::assertNothingSent();
    }

    public function test_a_preview_shows_any_macro_left_unexpanded()
    {
        $this->registrants();
        $template = $this->confirmationTemplate(['message' => 'Dear <<registrant_name>>, your badge: <<badge_number>>']);

        $html = LaravelMailMerge::composeBroadcast($template, ['group' => 'PaidRegistrants']);

        $this->assertStringContainsString('your badge: &lt;&lt;badge_number&gt;&gt;', $html);
    }

    public function test_a_preview_to_the_templates_own_recipients()
    {
        $template = $this->confirmationTemplate(['to' => 'desk@example.com', 'message' => 'Hello from <<app_name>>, see mailto:help@example.com']);

        $html = LaravelMailMerge::composeBroadcast($template);

        $this->assertStringContainsString('To: desk@example.com', $html);
        $this->assertStringContainsString('Hello from Conference Desk', $html);
        $this->assertStringContainsString('<a href="mailto:help@example.com">help@example.com</a>', $html, 'mailto: shortcut');
    }

    public function test_a_preview_needs_a_group_or_recipients()
    {
        $this->expectExceptionMessage('Neither group nor recipients provided.');

        LaravelMailMerge::composeBroadcast($this->confirmationTemplate());
    }

    // -------------------------------------------------------------------------
    // Sending a broadcast
    // -------------------------------------------------------------------------

    public function test_a_group_broadcast_sends_each_member_their_own_message()
    {
        Mail::fake();
        $this->inProduction();
        $this->registrants();

        $results = LaravelMailMerge::composeAndSendBroadcast($this->confirmationTemplate(), ['group' => 'PaidRegistrants']);

        $this->assertSame(['Successfully sent 3 emails.'], $results);
        Mail::assertSent(BasicMessage::class, 3);
        foreach (['ada' => 'Ada Lovelace', 'grace' => 'Grace Hopper', 'linus' => 'Linus Torvalds'] as $first => $name) {
            Mail::assertSent(BasicMessage::class, fn ($m) => $m->hasTo("$first@example.com") && $m->hasSubject('Your Dummy Conference registration')
                                                              && str_contains($m->render(), "Dear $name"));
        }
        Mail::assertNotSent(BasicMessage::class, fn ($m) => $m->hasTo('margaret@example.com'));
    }

    public function test_a_broadcast_to_the_templates_own_recipients()
    {
        Mail::fake();
        $this->inProduction();

        $results = LaravelMailMerge::composeAndSendBroadcast($this->confirmationTemplate(['to' => 'desk@example.com', 'message' => '<<app_name>>']));

        $this->assertSame(['Successfully sent 1 email.'], $results);
        Mail::assertSent(BasicMessage::class, fn ($m) => $m->hasTo('desk@example.com'));
    }

    public function test_failed_sends_are_listed()
    {
        $this->inProduction();
        $this->registrants();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Connection refused'));

        $results = LaravelMailMerge::composeAndSendBroadcast($this->confirmationTemplate(), ['group' => 'PaidRegistrants']);

        $this->assertSame(['Problems sending mail to ada@example.com', 'Problems sending mail to grace@example.com',
                           'Problems sending mail to linus@example.com'], $results);
    }

    public function test_a_broadcast_needs_a_group_or_recipients()
    {
        $this->expectExceptionMessage('Neither group nor recipients provided.');

        LaravelMailMerge::composeAndSendBroadcast($this->confirmationTemplate());
    }

    public static function badGroups(): array
    {
        return [
            'unknown group'     => ['Volunteers', 'Unable to find Merge Distribution class'],
            'no one in it'      => ['Nobody', 'No recipients found for distribution of group Nobody'],
            'list fails'        => ['Broken', 'Encountered error [database unavailable] when trying to retrieve the distribution list for Broken'],
        ];
    }

    #[DataProvider('badGroups')]
    public function test_a_group_that_cannot_be_used(string $group, string $error)
    {
        Mail::fake();
        $this->expectExceptionMessage($error);

        LaravelMailMerge::composeAndSendBroadcast($this->confirmationTemplate(), ['group' => $group]);
    }

    // -------------------------------------------------------------------------
    // Finding the group's distribution class and its macro guide
    // -------------------------------------------------------------------------

    public static function groupNames(): array
    {
        return [
            'listed in the config'              => ['PaidRegistrants', PaidConferenceRegistrantsMergeDistribution::class],
            'by name, with the suffix'          => ['Paid Conference Registrants', PaidConferenceRegistrantsMergeDistribution::class],
            'by name, snake case'               => ['paid_conference_registrants', PaidConferenceRegistrantsMergeDistribution::class],
            'by name, no suffix'                => ['pending registrants', PendingRegistrants::class],
        ];
    }

    #[DataProvider('groupNames')]
    public function test_the_groups_distribution_class_is_found(string $group, string $class)
    {
        $this->registrants();

        foreach (['App\\Mail\\Merge', 'App\\Mail\\Merge\\'] as $namespace) {   // with or without the trailing backslash
            config(['mailmerge.namespace' => $namespace]);
            [$distribution, $objects, $recipients, $nexusSlug] = LaravelMailMerge::assembleMergeResources(['group' => $group]);

            $this->assertInstanceOf($class, $distribution, $namespace);
            $this->assertSame(ConferenceRegistrationMacroExpansionGuide::class, $objects['expansionGuide']);
            $this->assertSame('conference_registration', $nexusSlug);
            $this->assertNotEmpty($recipients);
        }
    }

    public function test_the_macro_guide_is_also_found_by_name()
    {
        $this->registrants();
        config(['mailmerge.macro_sets' => ['general' => ['app_name' => 'Conference Desk']]]);   // not listed

        [, $objects] = LaravelMailMerge::assembleMergeResources(['group' => 'PaidRegistrants']);

        $this->assertSame(ConferenceRegistrationMacroExpansionGuide::class, $objects['expansionGuide']);
    }

    public function test_namespaced_joins_the_configured_namespace()
    {
        foreach (['App\\Mail\\Merge', 'App\\Mail\\Merge\\'] as $namespace) {
            config(['mailmerge.namespace' => $namespace]);
            $this->assertSame('App\\Mail\\Merge\\Macro\\Weather', LaravelMailMerge::namespaced('Macro\\Weather'));
            $this->assertSame('App\\Mail\\Merge\\Macro', LaravelMailMerge::namespaced('\\Macro'));
        }
    }

    // -------------------------------------------------------------------------
    // Notifications: one named template to one recipient
    // -------------------------------------------------------------------------

    public function test_a_notification_is_merged_and_sent()
    {
        Mail::fake();
        $this->inProduction();
        $users = $this->registrants();
        $this->confirmationTemplate();

        $result = LaravelMailMerge::composeAndSendNotification('conference_registration_confirmation', [
            'to' => 'ada@example.com', 'conference_registration' => $users['Ada']->registration(),
            'expansionGuide' => ConferenceRegistrationMacroExpansionGuide::class,
        ]);

        $this->assertSame(0, $result);
        Mail::assertSent(BasicMessage::class, fn ($m) => $m->hasTo('ada@example.com') && str_contains($m->render(), 'Dear Ada Lovelace'));
    }

    public function test_without_a_guide_every_guide_in_the_app_is_tried()
    {
        Mail::fake();
        $this->inProduction();
        $users = $this->registrants();
        $this->confirmationTemplate(['subject' => 'Forecast: <<forecast>>']);

        LaravelMailMerge::composeAndSendNotification('conference_registration_confirmation', [
            'to' => 'grace@example.com', 'conference_registration' => $users['Grace']->registration(),
        ]);

        // ConferenceRegistration... and Weather are found in App\Mail\Merge\Macro; NotAGuide is skipped
        Mail::assertSent(BasicMessage::class, fn ($m) => $m->hasSubject('Forecast: Sunny') && str_contains($m->render(), 'Dear Grace Hopper'));
    }

    public function test_an_unknown_guide_falls_back_to_trying_them_all()
    {
        Mail::fake();
        $this->inProduction();
        $this->confirmationTemplate(['subject' => '<<forecast>>', 'message' => '<<forecast>>']);

        LaravelMailMerge::composeAndSendNotification('conference_registration_confirmation', ['to' => 'ada@example.com', 'expansionGuide' => 'App\\Nope']);

        Mail::assertSent(BasicMessage::class, fn ($m) => $m->hasSubject('Sunny'));
    }

    public function test_a_notification_needs_its_template_and_a_recipient()
    {
        $this->confirmationTemplate();

        try {
            LaravelMailMerge::composeAndSendNotification('no_such_template', ['to' => 'ada@example.com']);
            $this->fail('No exception for a missing template');
        } catch (TemplateNotFoundException) {
        }

        $this->expectException(RecipientNotFoundException::class);
        LaravelMailMerge::composeAndSendNotification('conference_registration_confirmation', []);
    }

    public function test_a_notification_template_class_can_be_configured()
    {
        Mail::fake();
        $this->inProduction();
        EmailTemplate::create(['name' => 'welcome', 'from' => 'events@example.com', 'subject' => 'Default', 'message' => 'x']);
        config(['mailmerge.notification_email_class' => NotificationTemplate::class]);

        LaravelMailMerge::composeAndSendNotification('welcome', ['to' => 'ada@example.com', 'expansionGuide' => 'App\\Nope']);

        Mail::assertSent(BasicMessage::class, fn ($m) => $m->hasSubject('Default') && $m->hasFrom('events@example.com'));
        $this->assertSame(1, NotificationTemplate::$lookups);
    }

    public function test_the_facade()
    {
        $this->assertSame('App\\Mail\\Merge\\Macro', \MailMerge::namespaced('Macro'));
    }

    // -------------------------------------------------------------------------
    // Finding the app's guides
    // -------------------------------------------------------------------------

    public function test_class_finder_lists_the_classes_in_a_namespace()
    {
        $classes = ClassFinder::findRecursive('App\\Mail\\Merge');

        sort($classes);
        $this->assertSame([
            'App\\Mail\\Merge\\Distribution\\Broken', 'App\\Mail\\Merge\\Distribution\\Nobody',
            'App\\Mail\\Merge\\Distribution\\PaidConferenceRegistrantsMergeDistribution', 'App\\Mail\\Merge\\Distribution\\PendingRegistrants',
            'App\\Mail\\Merge\\Macro\\ConferenceRegistrationMacroExpansionGuide', 'App\\Mail\\Merge\\Macro\\NotAGuide', 'App\\Mail\\Merge\\Macro\\Weather',
        ], $classes);
        $this->assertSame([], ClassFinder::findRecursive('App\\Nowhere'));
    }
}

/** An app's own template model for notifications (mailmerge.notification_email_class). */
class NotificationTemplate extends EmailTemplate
{
    public static int $lookups = 0;

    public static function whereName($name)
    {
        static::$lookups++;

        return static::query()->where('name', $name);
    }
}
