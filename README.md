# BlackfinWebware LaravelMailMerge

[![Latest Version on Packagist](https://img.shields.io/packagist/v/blackfinwebware/laravel-mail-merge.svg?style=flat-square)](https://packagist.org/packages/blackfinwebware/laravel-mail-merge)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/blackfinwebware/laravel-mail-merge/run-tests?label=tests)](https://github.com/blackfinwebware/laravel-mail-merge/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/blackfinwebware/laravel-mail-merge/Check%20&%20fix%20styling?label=code%20style)](https://github.com/blackfinwebware/laravel-mail-merge/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/blackfinwebware/laravel-mail-merge.svg?style=flat-square)](https://packagist.org/packages/blackfinwebware/laravel-mail-merge)
 
This provides a facility to merge email templates with objects in your system to produce customized emails to 
individuals or to definable groups. It differs from base Laravel email templating in that it enables you and/or your 
users to maintain the email content from the UI in the browser for both repeatable event notification emails as well as for individualized group 
announcements, and does so in a highly configurable way.

## Installation 

You can install the package via composer:

```bash
composer require blackfinwebware/laravel-mail-merge
```

### Migrations

You'll want to publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-mail-merge-migrations"
php artisan migrate
```

### Views
Optionally, if you'd like to customize the views, you can publish them using

```bash
php artisan vendor:publish --tag="laravel-mail-merge-views"
```

Note that these provide a UI for your Email Templates -- https://yourapp.example.com/mailmerge/email-templates

### Groups
When preparing to send an email to a group of people, you'll want to first create and refine a Merge Distribution:

```bash
php artisan make:mergeDistribution MyGroupName
```
This will create the class App\Mail\Merge\Distribution\MyGroupName.php. The namespace will match your configured
namespace. 

Edit this to define how to derive the group and, conversely, to work backward to obtain the key related 
object, or <mark>nexus</mark>, that will enable the mailmerge to replace macros in your email template. 

The nexus object name defined in this class will be used in an attempt to locate an appropriate Macro Expansion class.

See below for a practical example.

### Macro Expansions
To define a set of macro expansions (or email template parameters and their substitutions), you'll want to first create a Macro 
Expansion class.

```bash
php artisan make:macroExpansion MyClassName
```
This will create the class App\Mail\Merge\Macro\MyClassName.php. The namespace will match your configured
namespace. You'll edit this class to define the set of macros that you want to use in your email template, and then
explcitly how to expand those.

### Config
These are the contents of the published config file. Tailor it to your app:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    | To integrate the mailmerge package, there will be some classes you'll need to create in your app to identify
    | Groups and Macro Sets(see below). This parameter provides you with the ability to customize your root namespace
    | for these classes. Note that if you change it here, you'll need to change it wherever it appears in this file, 
    | and remember to clear your config cache after making changes in this file.
    */

    'namespace' => 'App\\Mail\\Merge',

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    | Tables needed to support the package. At the least we need the EmailTemplates table
    | which corresponds with our EmailTemplate model.
    */

    'tables' => ['email_templates' => 'mailmerge_email_templates'],

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    | Groups here are those that can be identified as intended targets of an email merge. Each group
    | must be identified with a Merge Distribution class. This serves two functions: 1) it provides a way to get a
    | recipient list and 2) a way to reverse that and find the instance of our nexus object related to each
    | recipient. This nexus object is what will provide the values for the macro expansions - typically this is
    | a specific model in your app that is related directly or indirectly to a user, but is traversable from the
    | user so that macro values can be discovered and expanded quickly for each user.
    */

    'groups' => ['GroupName' => App\Mail\Merge\Distribution\GroupNameMergeDistribution::class],

    /*
    |--------------------------------------------------------------------------
    | Macro Sets
    |--------------------------------------------------------------------------
    | Macros are grouped in sets by like function. They may be related to a specific model or process which we refer to
    | as a nexus here. The General macros are applied to all outbound emails. Those defined within a Macro Expansion
    | Guide are application specific where the Guide provides a map to expand each macro appropriately for the
    | recipient.
    */

    'macro_sets' => ['general' => ['app_name' => env('APP_NAME', 'MyApp'),
                                   'primary_contact_email' => 'taylor@laravel.com'],
                     'macro_set_name' => App\Mail\Merge\Macro\ModelNameMacroExpansionGuide::class],

    /*
    |--------------------------------------------------------------------------
    | Queue outbound email
    |--------------------------------------------------------------------------
    | Queue mail when sending -- this allows the actual send to be handled asynchronously and is much preferred.
    | You must have queueing configured in your system.
    */

    'use_queues' => true,

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    | Used within the package to provide more debugging info to the log, and other items. If true, the primary
    | admin email will get bcc'd on outbound emails when in production and sandbox is false.
    */

    'debug' => true,

    /*
    |--------------------------------------------------------------------------
    | Sandbox Email
    |--------------------------------------------------------------------------
    | If true, when your app is not in production OR you have debug set to true, it will send all the generated
    | messages to the primary_admin_email, and NOT to the intended recipient(s).
    */

    'sandbox_email' => true,
    'primary_admin_email' => env('MAIL_FROM_ADDRESS', 'sysadmin@example.com'),

    /*
    |--------------------------------------------------------------------------
    | UI App Layout
    |--------------------------------------------------------------------------
    | App layout template. This is initially populated with a minimalist package layout for demo / test of the
    | provided package UI, using Bootstrap 4.6. Hint: to use your own layout, you may just need to remove the
    | 'mailmerge::'.
    */

    'blade_layout' => 'mailmerge::layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Route prefix
    |--------------------------------------------------------------------------
    | If you want package provided routes to have a prefix so they are set apart from you main application, you
    | can leave this at the default or modify as appropriate.
    */

    'route_prefix' => 'mailmerge',

    /*
    |--------------------------------------------------------------------------
    | Route middleware groups
    |--------------------------------------------------------------------------
    | Wrap the routes in the web middleware so that they run through the normal Laravel processes. You may want to use
    | the 'auth' middleware group as well if you are protecting your routes with a login layer.
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Advanced
    |--------------------------------------------------------------------------
    | If you have enough differentiation between your notification and broadcast emails,
    | and/or have different user roles that are maintaining these, you can specify their
    | classnames here. Note that they must extend BlackfinWebware\LaravelMailMerge\Models\EmailTemplate
    |
    | 'notification_email_class' => App\Models\NotificationEmailTemplate::class,
    | 'broadcast_email_class' => App\Models\BroadcastEmailTemplate::class,
    */
];
```

## Usage

```php
$skeleton = new VendorName\Skeleton();
echo $skeleton->echoPhrase('Hello, VendorName!');
```


## Example

You can define an EmailTemplate named 'password_reset_request', where your message body might be similar to this:

> This is an automated message from the <<app_name>> System and was triggered by a user action. Someone has requested to have the password reset for the account with the email address of <<password_reset_request_email>> and username <<member_username>>.
> If this was you, please confirm this by clicking on this link: <<password_reset_link>>  and following the instructions there. This must be done within one week of the initial request or it will no longer work.
>
> If you did not submit this request, please ignore it. If this continues to happen, please report it to <<primary_contact_email>>.

And define in the config where to derive appropriate values for those general use macros:

```
$macros = ['general' => ['app_name' => env('APP_NAME', 'MyApp'),
                         'primary_contact_email' => 'joe_user@example.com'],
                         'password_reset_request' => App\Mail\Merge\Macro\PasswordResetRequestMacroExpansionGuide::class];
```

And then within the linked Macro Expansion Guide, you'd define the macros and what they will expand to when the
mailmerge is performed:

```
public function expansions(){
        $password_reset_link = url("/password_reset_request/reset/" . $this->objects['password_reset_request']->reset_access_token);

        return ['password_reset_request_email' => $this->objects['password_reset_request']->email,
                'member_username' => $this->objects['password_reset_request']->user->username,
                'password_reset_link' => $password_reset_link];
    }
```

You can then call the following method on the facade to produce and send the appropriately populated email:

`Mailmerge::composeAndSend('password_reset_request', $password_reset_request);`
## Advanced

### Email Template Type Differentiation

If you are sending out event-driven direct notifications to individual users as well as broadcast emails to groups, 
it may help to separate these into different tables as the fields could be slightly different, and the roles that interact
with these may also be different.

#### Notifications

- Typically to a single user
- Heavy repetition, static content
- Triggered by an event, automated
- Eg. New User Welcome, Password Reset, Subscription expiration warning, Registration confirmation

#### Broadcasts

- Typically to a group
- Content a bit more dynamic and time or process sensitive
- Admin or SuperUser initiated
- eg. Conference Abstract Acceptance, App Update News(varying by subscription plan)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [:author_name](https://github.com/:author_username)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
