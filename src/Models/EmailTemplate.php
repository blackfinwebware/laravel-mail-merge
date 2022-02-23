<?php

namespace BlackfinWebware\LaravelMailMerge\Models;

use Illuminate\Database\Eloquent\Model;

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
class EmailTemplate extends Model implements \BlackfinWebware\LaravelMailMerge\Contracts\EmailTemplate
{
	protected $table = 'mailmerge_email_templates';

    public $fillable = ['name', 'to', 'cc', 'bcc', 'from', 'replyto', 'subject', 'message'];

    public function toHtmlForBrowser($index){
        $content = "<div class='message_container'><pre>";
        $content .= "Message #: " . $index . "<br>";
        $content .= "From: " . htmlentities($this->from) . "<br>";
        $content .= "To: " . htmlentities($this->to) . "<br>";
                                         //ensure missed macro expansions are visible to user
        $content .= "Subject: " . str_replace(['<<', '>>'],['&lt;&lt;', '&gt;&gt;'], $this->subject) . "<br><br>";
        $content .= str_replace(['<<', '>>'],['&lt;&lt;', '&gt;&gt;'], $this->message);
        $content .= "</pre></div>";
        return $content;
    }
}
