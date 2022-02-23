<?php

namespace BlackfinWebware\LaravelMailMerge\Tests;

use BlackfinWebware\LaravelMailMerge\Tests\Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

use BlackfinWebware\LaravelMailMerge\ServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate',
                       ['--database' => 'testbench'])->run();

        $this->loadMigrationsFrom(__DIR__.'/database/migrations/');

        $this->seed(DatabaseSeeder::class);

      /*  Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'VendorName\\Skeleton\\Database\\Factories\\'.class_basename($modelName).'Factory'
        ); */
    }

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class,];
    }

    public function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('mailmerge.macro_sets', ['general' => ['app_name' => env('APP_NAME', 'MyApp'),
                                                                   'primary_contact_email' => 'taylor@example.com'],
                                                     'macro_set_name' => BlackfinWebware\LaravelMailMerge\Tests\Mail\Merge\Macro\ConferenceRegistrationMacroExpansionGuide::class]);
        $app['config']->set('mailmerge.namespace', 'BlackfinWebware\\LaravelMailMerge\\Tests\\Mail\\Merge\\');
        $app['config']->set('mailmerge.use_queues', false);
        $app['config']->set('mailmerge.primary_admin_email', 'taylor@example.com');
    }
}
