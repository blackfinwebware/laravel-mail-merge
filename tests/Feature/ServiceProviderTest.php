<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Feature;

use BlackfinWebware\LaravelMailMerge\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ServiceProviderTest extends TestCase
{
    public function test_the_template_table_is_migrated()
    {
        $this->assertTrue(Schema::hasTable('mailmerge_email_templates'));
        $this->assertTrue(Schema::hasColumns('mailmerge_email_templates', ['name', 'to', 'cc', 'bcc', 'from', 'replyto', 'subject', 'message']));
    }

    public function test_the_routes_use_the_configured_prefix_and_middleware()
    {
        $route = Route::getRoutes()->getByName('email-templates.index');

        $this->assertSame('mailmerge/email-templates', $route->uri());
        $this->assertSame(['web'], $route->gatherMiddleware());
        foreach (['create', 'store', 'edit', 'update', 'destroy', 'clone', 'cloneUpdate'] as $name) {
            $this->assertNotNull(Route::getRoutes()->getByName("email-templates.$name"), $name);
        }
        $this->assertNull(Route::getRoutes()->getByName('email-templates.show'));
    }

    public function test_config_defaults_fill_in_what_the_app_leaves_out()
    {
        $this->assertSame('mailmerge::layouts.app', config('mailmerge.blade_layout'), 'Not set by the app (tests/TestCase.php)');
        $this->assertSame('App\\Mail\\Merge', config('mailmerge.namespace'));
    }

    public function test_views_and_publishable_files()
    {
        $this->assertTrue(view()->exists('mailmerge::emails.basic.message'));

        foreach (['laravel-mail-merge-config', 'laravel-mail-merge-views', 'laravel-mail-merge-assets'] as $tag) {
            $paths = \Illuminate\Support\ServiceProvider::pathsToPublish(\BlackfinWebware\LaravelMailMerge\ServiceProvider::class, $tag);
            $this->assertNotEmpty($paths, $tag);
            foreach (array_keys($paths) as $from) {
                $this->assertFileExists($from);
            }
        }
    }

    public function test_the_generator_commands_write_working_classes()
    {
        $distribution = app_path('Mail/Merge/Distribution/GeneratedSpeakers.php');
        $guide = app_path('Mail/Merge/Macro/SpeakerMacroExpansionGuide.php');
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory(app_path('Mail')));

        $this->artisan('make:mergeDistribution', ['name' => 'GeneratedSpeakers'])->assertExitCode(0);
        $this->artisan('make:macroExpansion', ['name' => 'SpeakerMacroExpansionGuide'])->assertExitCode(0);

        $this->assertStringContainsString('namespace App\\Mail\\Merge\\Distribution;', file_get_contents($distribution));
        $this->assertStringContainsString('namespace App\\Mail\\Merge\\Macro;', file_get_contents($guide));

        require_once $distribution;
        require_once $guide;
        $speakers = new \App\Mail\Merge\Distribution\GeneratedSpeakers();
        $this->assertCount(0, $speakers->getDistributionList());
        $this->assertIsObject($speakers->getNexusForUser(null));
        $email = new \BlackfinWebware\LaravelMailMerge\Models\EmailTemplate(['message' => 'x']);
        $this->assertSame([], (new \App\Mail\Merge\Macro\SpeakerMacroExpansionGuide($email, []))->expansions());
    }

    public function test_the_generators_with_a_trailing_backslash_in_the_namespace()
    {
        config(['mailmerge.namespace' => 'App\\Mail\\Merge\\']);
        $this->beforeApplicationDestroyed(fn () => File::deleteDirectory(app_path('Mail')));

        $this->artisan('make:mergeDistribution', ['name' => 'GeneratedJudges'])->assertExitCode(0);

        $this->assertStringContainsString('namespace App\\Mail\\Merge\\Distribution;', file_get_contents(app_path('Mail/Merge/Distribution/GeneratedJudges.php')));
    }
}
