<?php

namespace BlackfinWebware\LaravelMailMerge\Tests\Unit;

use BlackfinWebware\LaravelMailMerge\Mail\BasicMessage;
use BlackfinWebware\LaravelMailMerge\Mail\BasicQueueableMessage;
use BlackfinWebware\LaravelMailMerge\Tests\TestCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The two Mailables that carry a merged message. Mail::fake() never builds a Mailable, so these are
 * built here directly (a message that couldn't be built would only fail when really sent).
 */
class MessagesTest extends TestCase
{
    public static function messageClasses(): array
    {
        return ['sent right away' => [BasicMessage::class], 'queued' => [BasicQueueableMessage::class]];
    }

    protected function data(array $overrides = []): array
    {
        return $overrides + ['from' => 'events@example.com', 'subject' => 'Your registration', 'body' => '<p>Dear <b>Ada</b></p>'];
    }

    #[DataProvider('messageClasses')]
    public function test_the_body_is_rendered_as_html_in_the_mail_layout(string $class)
    {
        $html = (new $class($this->data()))->render();

        $this->assertStringContainsString('Ada</b>', $html);
        $this->assertStringContainsString('Conference Desk', $html, 'The mail layout carries the app name');
    }

    #[DataProvider('messageClasses')]
    public function test_the_envelope(string $class)
    {
        $envelope = (new $class($this->data(['replyto' => 'desk@example.com'])))->envelope();

        $this->assertSame('events@example.com', $envelope->from->address);
        $this->assertSame('desk@example.com', $envelope->replyTo[0]->address);
        $this->assertSame('Your registration', $envelope->subject);

        $this->assertSame([], (new $class($this->data()))->envelope()->replyTo, 'No reply-to');
    }

    #[DataProvider('messageClasses')]
    public function test_custom_headers(string $class)
    {
        $this->assertSame(['X-Campaign' => 'spring'], (new $class($this->data(['customHeaders' => ['X-Campaign' => 'spring']])))->headers()->text);
        $this->assertSame([], (new $class($this->data()))->headers()->text, 'None given');
    }

    #[DataProvider('messageClasses')]
    public function test_an_attachment_is_added_only_if_the_file_can_be_read(string $class)
    {
        $file = tempnam(sys_get_temp_dir(), 'mm') ;
        file_put_contents($file, 'agenda');

        $attachments = (new $class($this->data(['attachment' => $file])))->attachments();
        $this->assertCount(1, $attachments);
        $this->assertSame(basename($file), $attachments[0]->as);

        $this->assertSame([], (new $class($this->data(['attachment' => $file . '-missing'])))->attachments());
        $this->assertSame([], (new $class($this->data()))->attachments());
        unlink($file);
    }

    public function test_only_the_queueable_message_is_queued()
    {
        $this->assertInstanceOf(ShouldQueue::class, new BasicQueueableMessage($this->data()));
        $this->assertNotInstanceOf(ShouldQueue::class, new BasicMessage($this->data()));
    }

    public function test_a_queued_message_survives_the_queue()
    {
        $message = unserialize(serialize(new BasicQueueableMessage($this->data(['replyto' => 'desk@example.com']))));

        $this->assertSame('desk@example.com', $message->envelope()->replyTo[0]->address);
        $this->assertStringContainsString('Ada</b>', $message->render());
    }
}
