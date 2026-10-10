<?php

namespace BlackfinWebware\LaravelMailMerge\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** A mail merge message sent right away (mailmerge.use_queues false). */
class BasicMessage extends Mailable
{
    use SerializesModels, ComposesBasicMessage;
}
