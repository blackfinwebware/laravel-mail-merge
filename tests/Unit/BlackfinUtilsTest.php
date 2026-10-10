<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Unit;

use BlackfinWebware\LaravelMailMerge\Tests\TestCase;
use BlackfinWebware\LaravelMailMerge\Utils\BlackfinUtils;
use PHPUnit\Framework\Attributes\DataProvider;

class BlackfinUtilsTest extends TestCase
{
    public function test_smart_punctuation_becomes_plain_ascii()
    {
        $utf8 = "\u{2018}a\u{2019} \u{201C}b\u{201D} c\u{2013}d e\u{2014}f g\u{2026} h\u{2011}i";
        $this->assertSame("'a' \"b\" c-d e--f g... h-i", BlackfinUtils::replaceSmartQuotes($utf8));

        $windows1252 = chr(145) . 'a' . chr(146) . ' ' . chr(147) . 'b' . chr(148) . ' c' . chr(150) . 'd e' . chr(151) . 'f g' . chr(133);
        $this->assertSame("'a' \"b\" c-d e--f g...", BlackfinUtils::replaceSmartQuotes($windows1252));
    }

    public function test_snake_case()
    {
        $this->assertSame('paid_conference_registrants', BlackfinUtils::snakeCase('Paid Conference Registrants'));
    }

    public static function camelCases(): array
    {
        return [
            'underscores'    => ['paid_registrants', true, 'PaidRegistrants'],
            'spaces'         => ['paid registrants', true, 'PaidRegistrants'],
            'hyphens/commas' => ['paid-up,registrants', true, 'PaidUpRegistrants'],
            'already camel'  => ['PaidRegistrants', true, 'PaidRegistrants'],
            'lower first'    => ['paid_registrants', false, 'paidRegistrants'],
            'empty'          => ['', true, ''],
        ];
    }

    #[DataProvider('camelCases')]
    public function test_camel_case(string $in, bool $capitalizeFirst, string $out)
    {
        $this->assertSame($out, BlackfinUtils::camelCase($in, $capitalizeFirst));
    }

    public static function addressLists(): array
    {
        return [
            'one address stays a string' => ['a@example.com', 'a@example.com'],
            'commas'                     => ['a@example.com, b@example.com,c@example.com', ['a@example.com', 'b@example.com', 'c@example.com']],
            'one per line'               => ["a@example.com\r\nb@example.com\n", ['a@example.com', 'b@example.com']],
            'empty'                      => ['', ''],
            'null'                       => [null, ''],
            'already a list'             => [['a@example.com'], ['a@example.com']],
        ];
    }

    #[DataProvider('addressLists')]
    public function test_email_string_to_array($in, $out)
    {
        $this->assertSame($out, BlackfinUtils::emailStringToArray($in));
    }

    public function test_string_begins_and_ends_with()
    {
        $this->assertTrue(BlackfinUtils::stringBeginsWith('<<conference_name>>', '<<conf'));
        $this->assertFalse(BlackfinUtils::stringBeginsWith('<<conference_name>>', 'conf'));
        $this->assertTrue(BlackfinUtils::stringEndsWith('<<conference_name>>', 'name>>'));
        $this->assertFalse(BlackfinUtils::stringEndsWith('<<conference_name>>', 'name'));
        $this->assertTrue(BlackfinUtils::stringEndsWith('anything', ''));
    }

    public function test_string_contains_ignores_case_and_searches_lists()
    {
        $this->assertTrue(BlackfinUtils::stringContains('Successfully sent 3 emails.', 'success'));
        $this->assertFalse(BlackfinUtils::stringContains('Problems sending mail', 'success'));
        $this->assertTrue(BlackfinUtils::stringContains(['subject', 'Dear <<Registrant_Name>>'], '<<registrant_name>>'));
        $this->assertFalse(BlackfinUtils::stringContains(['subject', 'body'], '<<'));
        $this->assertFalse(BlackfinUtils::stringContains(null, 'x'));
    }

    public function test_get_class_name()
    {
        $this->assertSame('PendingRegistrants', BlackfinUtils::getClassName('App\\Mail\\Merge\\Distribution\\PendingRegistrants'));
        $this->assertSame('Weather', BlackfinUtils::getClassName('Weather'), 'A class name with no namespace is returned as it is');
    }

    public function test_locale_dates()
    {
        config(['app.locale' => 'en_US']);
        $this->assertSame('3/7/26 2:05 pm', BlackfinUtils::localeDatetime('2026-03-07 14:05:09'));
        $this->assertSame('3/7/2026 2:05:09 pm', BlackfinUtils::localeDatetime('2026-03-07 14:05:09', false));
        $this->assertSame('3/7/2026', BlackfinUtils::localeDate('2026-03-07 14:05:09'));

        config(['app.locale' => 'en_GB']);
        $this->assertSame('2026-03-07 14:05', BlackfinUtils::localeDatetime('2026-03-07 14:05:09'));
        $this->assertSame('2026-03-07 14:05:09', BlackfinUtils::localeDatetime('2026-03-07 14:05:09', false));
        $this->assertSame('2026-03-07', BlackfinUtils::localeDate('2026-03-07 14:05:09'));

        $this->assertSame('', BlackfinUtils::localeDate(''));
        $this->assertSame('', BlackfinUtils::localeDatetime(''));
        $this->assertSame('not a date', BlackfinUtils::localeDate('not a date'), 'Text that is not a date comes back unchanged');
    }
}
