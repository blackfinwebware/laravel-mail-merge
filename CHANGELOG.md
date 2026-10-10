# Changelog

All notable changes to `blackfinwebware/laravel-mail-merge` will be documented in this file.

## 1.1.0 - 2026-10-10

- Laravel 13 support (PHP 8.3+); CI tests Laravel 10-13 on each supported PHP version, at the lowest and
  latest allowed dependency versions.
- Dev: Testbench 8, 9.2+, 10 or 11; PHPUnit 10.5-13 (Testbench 9.0 and 9.1 can't run the tests on Laravel 11);
  `minimum-stability` is `stable`, so the lowest-version CI runs test real releases (it was `dev`, which picked a
  moving 11.x development snapshot).
- Every Laravel 10 and 11 release now carries an unpatched security advisory (both are end of life), and
  Composer 2.9+ refuses to install them by default. 1.x still supports them, and CI tests them with Composer's
  insecure-version block turned off; prefer Laravel 12 or 13.

## 1.0.0 - 2026-10-10

Laravel 10, 11 and 12 (PHP 8.1+); a full test suite.


- Laravel 10-12 Mailable API (envelope / content / headers / attachments) for `BasicMessage` and
  `BasicQueueableMessage`, sharing one trait, `ComposesBasicMessage`. Finishes the earlier partial
  conversion: `headers()` and `attachments()` returned nothing when there were no custom headers or
  attachment (a TypeError whenever a message was built, so no email went out); `replyTo` was `''`
  rather than an array; `BasicMessage` lacked its imports.
- `BlackfinUtils::sendMail()`: `Mail::failures()` (removed in Laravel 9) no longer used; cc and bcc
  are passed on; `strftime()` (deprecated in PHP 8.1) replaced with `date()`.
- `BlackfinUtils::sendMail()` with `mailmerge.debug` on in production: a template with a single Bcc address
  threw a TypeError instead of sending.
- `getDistributionList()` in `MergeDistribution` (and the `make:mergeDistribution` stub) returned `[]` for a
  `Collection` return type, a TypeError; the stub also lacked its `Collection` import and its
  `getNexusForUser()` returned an array for an `object` return type.
- `mailmerge.namespace` works with or without a trailing backslash (`LaravelMailMerge::namespaced()`): finding a
  group's distribution class or macro guide by name needed one, the class scan and the generators needed none.
- Finding the app's macro guides (`ClassFinder`) uses Composer's PSR-4 map, so it works for any namespace, not
  only `App\` under the base path.
- A guide's `macros` are matched exactly (`<<name>>`); only `quickSearch` entries match as prefixes. The default
  for "starts with" was the string `'false'`, which is true, so every macro matched as a prefix.
- `${var}` string interpolation (deprecated in PHP 8.2) replaced.
- `getClassName()` returned `false` for a class name with no namespace.
- `emailStringToArray()` accepts null (deprecated in PHP 8.1) and arrays; failures and debug logging with several
  recipients no longer try to print an array.
- A missing `from` uses `config('mail.from.address')`, not `env()`, which is null once config is cached.
- A broadcast to the template's own To reports "Successfully sent 1 email." like a group broadcast.
- Email template pages: deleting a template deleted nothing (the route parameter was never bound); editing or
  updating an unknown template is a 404; the macro list works with no `general` macros; no `groups` configured
  no longer errors.
- The service provider merges the package's config defaults, so keys an app's published config leaves out work.
- composer.json requires PHP 8.1 and illuminate/* 10-12; dev tooling is Testbench 8-10 and PHPUnit 10-11 (Pest
  and Collision removed); the hand-set `version` field is removed (versions come from git tags).
- Tests: `tests/Unit`, `tests/Feature` (92 tests), run by GitHub Actions on Laravel 10, 11 and 12. Old test
  files, case-duplicated test folders and a stray HTML file removed.

## 1.0.0beta - 2022-02-23

- initial beta release
