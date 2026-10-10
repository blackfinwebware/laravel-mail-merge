<?php

namespace BlackfinWebware\LaravelMailMerge\Tests;

use App\Mail\Merge\Distribution\PaidConferenceRegistrantsMergeDistribution;
use App\Mail\Merge\Macro\ConferenceRegistrationMacroExpansionGuide;
use BlackfinWebware\LaravelMailMerge\Models\EmailTemplate;
use BlackfinWebware\LaravelMailMerge\ServiceProvider;
use BlackfinWebware\LaravelMailMerge\Tests\Models\ConferenceRegistration;
use BlackfinWebware\LaravelMailMerge\Tests\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * The package in a bare Laravel app (Orchestra Testbench) with an in-memory SQLite database.
 *
 * The app's own merge classes are the fixtures in tests/Fixtures/app/Mail/Merge, autoloaded as
 * App\Mail\Merge\... like a real app's, and the config is set up the way an app would publish it.
 * Mail is sent with the "array" mailer unless a test fakes it.
 */
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return ['MailMerge' => \BlackfinWebware\LaravelMailMerge\Facade::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.name', 'Conference Desk');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.from.address', 'noreply@example.com');

        $app['config']->set('mailmerge.namespace', 'App\\Mail\\Merge');
        $app['config']->set('mailmerge.groups', ['PaidRegistrants' => PaidConferenceRegistrantsMergeDistribution::class]);
        $app['config']->set('mailmerge.macro_sets', [
            'general'                 => ['app_name' => 'Conference Desk', 'primary_contact_email' => 'help@example.com'],
            'conference_registration' => ConferenceRegistrationMacroExpansionGuide::class,
        ]);
        $app['config']->set('mailmerge.use_queues', false);
        $app['config']->set('mailmerge.debug', false);
        $app['config']->set('mailmerge.sandbox_email', false);
        $app['config']->set('mailmerge.primary_admin_email', 'admin@example.com');
        $app['config']->set('mailmerge.middleware', ['web']);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');
    }

    /** Send for real: BlackfinUtils::sendMail() only addresses the actual recipients in production. */
    protected function inProduction(): void
    {
        config(['app.env' => 'production']);
    }

    /**
     * Registrations for the Dummy Conference: Ada, Grace and Linus have paid in full (in that order
     * by name), Margaret is pending.
     *
     * @return array<string, User> by first name
     */
    protected function registrants(): array
    {
        $users = [];
        foreach (['Ada Lovelace' => 'Paid in Full', 'Linus Torvalds' => 'Paid in Full', 'Grace Hopper' => 'Paid in Full',
                  'Margaret Hamilton' => 'Pending'] as $name => $status) {
            $first = strtok($name, ' ');
            $users[$first] = User::create(['name' => $name, 'email' => strtolower($first) . '@example.com', 'password' => 'x']);
            ConferenceRegistration::create(['user_id' => $users[$first]->id, 'conference' => 'Dummy Conference', 'status' => $status]);
        }

        return $users;
    }

    protected function confirmationTemplate(array $attributes = []): EmailTemplate
    {
        return EmailTemplate::create($attributes + [
            'name'    => 'conference_registration_confirmation',
            'from'    => 'events@example.com',
            'subject' => 'Your <<conference_name>> registration',
            'message' => "Dear <<registrant_name>>,\n\nYou are registered for <<conference_name>>: <<conference_registration_summary>>. "
                       . "We have received <<conference_registration_amount_paid>>.\n\nQuestions? Write to <<primary_contact_email>>. -- <<app_name>>",
        ]);
    }
}
