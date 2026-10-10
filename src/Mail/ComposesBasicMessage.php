<?php

namespace BlackfinWebware\LaravelMailMerge\Mail;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/**
 * The message BlackfinUtils::sendMail() composes ($message: from, subject, body, and optionally
 * replyto, attachment and customHeaders), in the Mailable envelope / content / attachments API of
 * Laravel 10+. Recipients (to, cc, bcc) are set by the caller through Mail::to()->cc()->bcc().
 *
 * Each method returns its declared type in every case: a method that returned nothing (no custom
 * headers, no attachment) failed with a TypeError when the message was built, so no email was sent.
 */
trait ComposesBasicMessage
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function headers(): Headers
    {
        return new Headers(text: $this->message['customHeaders'] ?? []);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->message['from']),
            replyTo: !empty($this->message['replyto']) ? [new Address($this->message['replyto'])] : [],
            subject: $this->message['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mailmerge::emails.basic.message',
            with: [
                'body'    => $this->message['body'],
                'appName' => config('app.name'),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $path = $this->message['attachment'] ?? null;
        if ($path && is_readable($path)) {
            return [Attachment::fromPath($path)->as(basename($path))];
        }

        return [];
    }
}
