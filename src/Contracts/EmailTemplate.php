<?php

namespace BlackfinWebware\LaravelMailMerge\Contracts;


/**
 * Class EmailTemplate
 *
 * @property int $id
 * @property string $name
 * @property string $to
 * @property string $cc
 * @property string $bcc
 * @property string $from
 * @property string $replyto
 * @property string $subject
 * @property string $message
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @package BlackfinWebware\LaravelMailMerge\Models
 */
interface EmailTemplate
{
    public function toHtmlForBrowser($index);
}
