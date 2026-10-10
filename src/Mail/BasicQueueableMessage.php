<?php

namespace BlackfinWebware\LaravelMailMerge\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** A mail merge message sent through the queue (mailmerge.use_queues true). */
class BasicQueueableMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, ComposesBasicMessage;
}
