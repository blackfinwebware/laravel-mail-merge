<?php

return [
         /*
          | Macros are grouped in sets by like function. They may be related to a specific model or process which we refer to
          | as a nexus here. The General macros will be those that will be applied to all outbound merged emails. Those
          | defined within a Macro Expansion Guide are application specific and the Guide provides a map to expand each
          | macro appropriately for the email recipient.
          */

         'macro_sets' => ['general' => ['app_name' => env('APP_NAME', 'MyApp'),
                                        'primary_contact_email' => 'joe_user@example.com'],
                          'conference_registration' => App\Mail\Merge\Macro\ConferenceRegistrationMacroExpansionGuide::class,
                          'password_reset_request' => App\Mail\Merge\Macro\PasswordResetRequestMacroExpansionGuide::class],

          /*
          | Groups here are those that can be identified as intended targets of an email merge. Each group
          | must be identified with a Merge Distribution class. This serves two functions: 1) provides a way to get a
          | recipient list and 2) a way to reverse that and find the instance of our nexus object related to each
          | recipient. This nexus object is what will provide the values for the macro expansions.
          */

         'groups' => ['NY2022Registrants' => App\Mail\Merge\Distribution\NY2022ConferenceRegistration::class],

         'tables' => ['groups' => 'mailmerge_groups',
                      'users' => 'users',
                      'users_groups' => 'mailmerge_groups_users',
                      'email_templates' => 'mailmerge_email_templates',
                      'email_history' => 'mailmerge_email_history'],
          /*
          | Queue mail when sending -- this allows the actual send be handled asynchronously and is preferred. You must
          | have queueing configured in your system.
          */

         'use_queues' => true,

         'primary_admin_email' => 'tom@blackfin.biz',

          /*
          | Used within the package to provide more debugging info to the log, and other items. If true, the primary admin
          | email will get bcc'd on outbound emails when in production and sandbox is set to false.
          */

         'debug' => true,

          /*
          | If true, when your app is not in production OR you have debug set to true, it will send all the generated
          | messages to the primary_admin_email, and NOT to the intended recipient.
          */

         'sandbox_email' => true,

          /*
          | This is the generic layout that comes with the package, replace this with your apps layout.
          */
         'blade_layout' => 'mailmerge::layouts.app',
         'notification_email_class' => App\Models\NotificationEmail::class,
         'broadcast_email_class' => App\Models\BroadcastEmail::class,
];
