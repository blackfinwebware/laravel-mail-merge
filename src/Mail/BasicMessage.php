<?php

namespace BlackfinWebware\LaravelMailMerge\Mail;

use BlackfinWebware\LaravelMailMerge\Utils\BlackfinUtils;
use Html2Text\Html2Text;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class BasicMessage extends Mailable
{
    use SerializesModels;

    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $pm = $this->message;

        return $this->to($this->message['to'])->
            subject($this->message['subject'])->
            markdown('mailmerge::emails.basic.message', ['body' => $this->message['body'],
                                                          'appName' => config('app.name')])->
            withSwiftMessage(function ($message) use ($pm) {
                if(!empty($pm['customHeaders'])) {
                    foreach($pm['customHeaders'] as $header => $value) {
                        $message->getHeaders()
                                ->addTextHeader($header, $value);
                    }
                }
                if($pm['attachment'] && is_readable($pm['attachment'])) {
                    $message->attach(\Swift_Attachment::fromPath($pm['attachment']));
                    $message->attach($pm['attachment']);
                }
                if(!empty($pm['cc'])) {
                    $message->addCc($pm['cc']);
                }
                if(!empty($pm['bcc'])) {
                    $message->setBcc($pm['bcc']);
                }
                if(!empty($pm['replyto'])) {
                    $message->setReplyTo($pm['replyto']);
                }
            });
    }
}
