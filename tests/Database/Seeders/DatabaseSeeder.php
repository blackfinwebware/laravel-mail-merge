<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Database\Seeders;

use BlackfinWebware\LaravelMailMerge\Models\EmailTemplate;
use BlackfinWebware\LaravelMailMerge\Tests\Models\ConferenceRegistration;
use BlackfinWebware\LaravelMailMerge\Tests\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
//use Faker\Generator as Faker;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //for testing only -- not to be released with package
        // \BlackfinWebware\LaravelMailMerge\Tests\Models\User::factory(5)->create();
        $this->faker = \Faker\Factory::create('en');
         for($i = 0;$i < 5;$i++){
             $params = [
                 'name' => $this->faker->name(),
                 'email' => $this->faker->unique()->safeEmail(),
                 'email_verified_at' => now(),
                 'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                 'remember_token' => Str::random(10),
             ];
             User::create($params);
         }
         $users = \BlackfinWebware\LaravelMailMerge\Tests\Models\User::whereNotNull('email_verified_at')->inRandomOrder()->limit(5)->get();
        // dd($users);
         foreach($users as $user){
             ConferenceRegistration::create(['user_id' => $user->id,
                                             'conference' => 'Dummy Conference',
                                             'status' => 'Paid in Full',]);
         }
         $params = ['name' => 'password_reset_request',
                    'to' => '',
                    'from' => 'tom@blackfin.biz',
                    'subject' => 'Password Reset Request',
                    'message' => 'This is an automated message from the <<app_name>> System and was triggered by a user action. Someone has requested to have the password reset for the account with the email address of <<prr_email>> and username <<member_username>>. If this was you, please confirm this by clicking on this link: <<password_reset_link>> and following the instructions that you find there. This must be done within one week of the initial request or it will no longer work.

If you did not submit this request, please ignore it. If it continues to happen, please forward the message to <<primary_contact_email>>.'];
         EmailTemplate::create($params);

         $params = ['name' => 'conference_registration_confirmation',
                    'to' => '',
                    'from' => 'tom@blackfin.biz',
                    'subject' => 'Conference Registration Confirmation',
                    'message' => 'Dear <<registrant_name>>,

This message confirms your registration for the <<conference_name>> Conference. We show that your registration, completed on <<conference_registration_date>>, includes <<conference_registration_summary>>, and that we have received <<conference_registration_amount_paid>>.

If you have any questions or concerns, please direct them to <<primary_contact_email>>.

Thank you for choosing <<conference_name>>! We look forward to seeing you at the conference.'];
         EmailTemplate::create($params);
    }
}
