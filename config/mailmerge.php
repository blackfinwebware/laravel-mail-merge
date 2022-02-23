<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    | To integrate the mailmerge package, there will be some classes you'll want to create in your app to identify
    | Groups and Macro Sets(see below). This parameter provides you with the ability to customize your root namespace.
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
                                   'primary_contact_email' => 'taylor@example.com'],
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
    | provided package UI.
    */

    'blade_layout' => 'mailmerge::layouts.app',
    'route_prefix' => 'mailmerge',
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
