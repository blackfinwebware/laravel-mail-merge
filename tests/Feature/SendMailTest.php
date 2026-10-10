<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Feature;

use BlackfinWebware\LaravelMailMerge\Mail\BasicMessage;
use BlackfinWebware\LaravelMailMerge\Mail\BasicQueueableMessage;
use BlackfinWebware\LaravelMailMerge\Models\EmailTemplate;
use BlackfinWebware\LaravelMailMerge\Tests\TestCase;
use BlackfinWebware\LaravelMailMerge\Utils\BlackfinUtils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * BlackfinUtils::sendMail(): every merged message goes out through it. In production (and not
 * sandboxed) it goes to its recipients; otherwise to the primary admin address instead.
 */
class SendMailTest extends TestCase
{
    protected function email(array $attributes = []): EmailTemplate
    {
        return new EmailTemplate($attributes + ['to' => 'ada@example.com', 'from' => 'events@example.com',
                                                'subject' => 'Registration', 'message' => 'Dear Ada']);
    }

    protected function sent(): BasicMessage
    {
        $sent = null;
        Mail::assertSent(BasicMessage::class, function ($message) use (&$sent) {
            $sent = $message;
            return true;
        });

        return $sent;
    }

    public function test_in_production_it_goes_to_the_recipients()
    {
        Mail::fake();
        $this->inProduction();

        $result = BlackfinUtils::sendMail($this->email(['to' => 'ada@example.com, grace@example.com', 'cc' => 'linus@example.com',
                                                        'bcc' => "office@example.com\nboard@example.com", 'replyto' => 'desk@example.com']));

        $this->assertSame(0, $result, 'Zero means sent');
        $message = $this->sent();
        $this->assertTrue($message->hasTo('ada@example.com') && $message->hasTo('grace@example.com'));
        $this->assertTrue($message->hasCc('linus@example.com'));
        $this->assertTrue($message->hasBcc('office@example.com') && $message->hasBcc('board@example.com'));
        $this->assertFalse($message->hasBcc('admin@example.com'));
        $this->assertTrue($message->hasFrom('events@example.com'));
        $this->assertTrue($message->hasReplyTo('desk@example.com'));
        $this->assertTrue($message->hasSubject('Registration'));
    }

    public function test_debugging_in_production_copies_the_admin()
    {
        Mail::fake();
        $this->inProduction();
        config(['mailmerge.debug' => true]);

        BlackfinUtils::sendMail($this->email());
        BlackfinUtils::sendMail($this->email(['bcc' => 'office@example.com, board@example.com']));
        BlackfinUtils::sendMail($this->email(['bcc' => 'office@example.com']));

        $sent = Mail::sent(BasicMessage::class);
        $this->assertCount(3, $sent);
        foreach ($sent as $message) {
            $this->assertTrue($message->hasBcc('admin@example.com'));
        }
        $this->assertTrue($sent[1]->hasBcc('office@example.com') && $sent[1]->hasBcc('board@example.com'));
        $this->assertTrue($sent[2]->hasBcc('office@example.com'));
    }

    public static function notToTheRecipients(): array
    {
        return ['not in production' => ['testing', false], 'sandboxed in production' => ['production', true]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('notToTheRecipients')]
    public function test_otherwise_it_goes_to_the_admin_instead(string $env, bool $sandbox)
    {
        Mail::fake();
        config(['app.env' => $env, 'mailmerge.sandbox_email' => $sandbox]);

        BlackfinUtils::sendMail($this->email(['cc' => 'linus@example.com', 'bcc' => 'office@example.com']));

        $message = $this->sent();
        $this->assertTrue($message->hasTo('admin@example.com'));
        $this->assertFalse($message->hasTo('ada@example.com'));
        $this->assertFalse($message->hasCc('linus@example.com') || $message->hasBcc('office@example.com'));
        $this->assertStringContainsString('would have sent it to ada@example.com', $message->render());
    }

    public function test_smart_punctuation_is_made_plain()
    {
        Mail::fake();
        $this->inProduction();

        BlackfinUtils::sendMail($this->email(['subject' => "Ada\u{2019}s \u{201C}pass\u{201D}", 'message' => "See you \u{2014} soon\u{2026}"]));

        $message = $this->sent();
        $this->assertTrue($message->hasSubject("Ada's \"pass\""));
        $this->assertStringContainsString('See you -- soon...', $message->render());
    }

    public function test_defaults_for_a_missing_subject_and_from()
    {
        Mail::fake();
        $this->inProduction();

        BlackfinUtils::sendMail($this->email(['subject' => '', 'from' => '']));

        $message = $this->sent();
        $this->assertTrue($message->hasSubject('Conference Desk on ' . gethostname() . ' on ' . date('M j Y')));
        $this->assertTrue($message->hasFrom('noreply@example.com'), 'The app\'s mail.from.address');
    }

    public function test_headers_and_an_attachment_are_passed_on()
    {
        Mail::fake();
        $this->inProduction();
        $file = tempnam(sys_get_temp_dir(), 'mm');

        BlackfinUtils::sendMail($this->email(), ['X-Campaign' => 'spring'], $file);

        $message = $this->sent();
        $this->assertSame(['X-Campaign' => 'spring'], $message->headers()->text);
        $this->assertCount(1, $message->attachments());
        unlink($file);
    }

    public function test_with_queues_on_the_message_is_queued()
    {
        Mail::fake();
        $this->inProduction();
        config(['mailmerge.use_queues' => true]);

        BlackfinUtils::sendMail($this->email());

        Mail::assertQueued(BasicQueueableMessage::class, fn ($message) => $message->hasTo('ada@example.com'));
        Mail::assertNotSent(BasicMessage::class);
    }

    public function test_a_failure_is_reported_and_logged_not_thrown()
    {
        $this->inProduction();
        // The mail mock first: building it can log a deprecation (old Mockery on PHP 8.4), which a log spy can't take
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Connection refused'));
        Log::spy();

        $result = BlackfinUtils::sendMail($this->email(['to' => 'ada@example.com, grace@example.com']));

        $this->assertSame('Problems sending mail to ada@example.com, grace@example.com', $result);
        Log::shouldHaveReceived('error')->withArgs(fn ($text) => str_contains($text, 'Connection refused'));
    }

    public function test_a_real_send_through_the_mailer()
    {
        // No fake: the message is built and handed to the "array" transport, as it would be to SMTP
        config(['mailmerge.debug' => true]);
        BlackfinUtils::sendMail($this->email(['to' => 'ada@example.com, grace@example.com']));
        $this->inProduction();
        BlackfinUtils::sendMail($this->email(['to' => 'ada@example.com, grace@example.com', 'cc' => 'linus@example.com', 'replyto' => 'desk@example.com']));

        $delivered = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(2, $delivered);

        $sandboxed = $delivered[0]->getOriginalMessage();
        $this->assertSame(['admin@example.com'], array_map(fn ($a) => $a->getAddress(), $sandboxed->getTo()));
        $this->assertSame([], $sandboxed->getCc());

        $real = $delivered[1]->getOriginalMessage();
        $this->assertSame(['ada@example.com', 'grace@example.com'], array_map(fn ($a) => $a->getAddress(), $real->getTo()));
        $this->assertSame(['linus@example.com'], array_map(fn ($a) => $a->getAddress(), $real->getCc()));
        $this->assertSame(['admin@example.com'], array_map(fn ($a) => $a->getAddress(), $real->getBcc()), 'Debug copies the admin');
        $this->assertSame('desk@example.com', $real->getReplyTo()[0]->getAddress());
        $this->assertStringContainsString('Dear Ada', $real->getHtmlBody());
        $this->assertNotEmpty($real->getTextBody(), 'A plain-text part too');
    }
}
