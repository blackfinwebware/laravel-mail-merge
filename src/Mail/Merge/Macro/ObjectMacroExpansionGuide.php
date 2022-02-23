<?php

namespace BlackfinWebware\LaravelMailMerge\Mail\Merge\Macro;

use BlackfinWebware\LaravelMailMerge\Utils\BlackfinUtils;
use Illuminate\Support\Facades\Log;

abstract class ObjectMacroExpansionGuide
{
    /**
     * When creating your own MacroExpansionGuide, you'll need to populate these first three properties and the method
     * expansions below.
     * <i>macros</i> must contain a list of macros that this guide will replace, for eg., if the nexus object
     * is foo, macros should contain strings like foo_name, foo_email, foo_registration_code, etc.
     * <i>quickSearch</i> is optional - it may provide a shortcut so that this guide can quickly tell if it should be
     * used on the email. In our example, we'd expect quickSearch to contain 'foo_' only.
     * <i>requiredObjects</i> must list the snake case version of the nexus object - 'foo' in our example. The guide
     * will verify that an instantiation of foo has been passed in before it tries to expand any macros.
     * <i>expansions</i> must provide an associative array that will map the macros to their logical expansions, using
     * either a static or dynamic expression.
     * Location -- the guide in this example must be in config('mailmerge.namespace')\Macro but
     * you can actually name the class whatever - Foo or FooMacroExpansionGuide, etc.
     */
    public $quickSearch = [];
    public $macros = [];
    public $requiredObjects = [];

    protected $email = null;
    protected $objects = null;
    protected $missingObject = '';
    protected $debug = false;

    public function __construct(&$email, $objects){
        $this->email = $email;
        $this->objects = $objects;
        $this->debug = config('mailmerge.debug');
    }

    /**
     * Determine if single macro string is contained with the string or array of strings.
     *
     * @param $macro
     * @param $strings
     * @param string $startsWith
     * @return bool
     */
    protected function containsMacro($macro, $strings, $startsWith = 'false'){
        if($startsWith){
            $needle = "<<${macro}";
        }
        else{
            $needle = "<<${macro}>>";
        }
        if(BlackfinUtils::stringContains($strings, $needle)){
            return true;
        }
        return false;
    }

    /**
     * Determine if any of the macro strings are contained within the indicated set of fields in the message.
     * Returns on first occurrence.
     *
     * @param $macroString
     * @param string $elements
     * @param string $startsWith
     * @return bool
     */
    protected function containsMacros($macroString, $elements = 'message', $startsWith = 'false'){
        if($elements == 'message'){
            $searchSubjects = array($this->email->message);
        }
        elseif($elements == 'both'){
            $searchSubjects = array($this->email->message,
                                    $this->email->subject);
        }
        elseif($elements == 'all'){
            $searchSubjects = array($this->email->message,
                                    $this->email->subject,
                                    $this->email->cc,
                                    $this->email->bcc,
                                    $this->email->from);
        }
        if(is_array($macroString)){
            foreach($macroString as $string){
                if($this->containsMacro($string, $searchSubjects, $startsWith)){
                    return true;
                }
            }
        }
        else{
            if($this->containsMacro($macroString, $searchSubjects, $startsWith)){
                return true;
            }
        }
        return false;
    }

    /**
     * Replace the macro string with the indicated replacement text in the message fields.
     *
     * @param $macroString
     * @param $replacementText
     * @param string $elements
     * @param false $literal
     */
    protected function replaceMacro($macroString, $replacementText, $elements = 'both', $literal = false){
        $textFields = ['subject', 'message'];
        $addressFields = ['cc', 'bcc', 'from'];

        switch($elements){
            case 'both' :       $fields = $textFields;
                                break;
            case 'all' :        $fields = array_merge($textFields, $addressFields);
                                break;
            case 'address' :    $fields = $addressFields;
                                break;
            case 'subject' :    $fields = ['subject'];
                                break;
            default :           $fields = ['message'];
        }
        if(!$literal){
            $macroString = "<<${macroString}>>";
        }
        foreach($fields as $field){
            $this->email->$field = str_replace($macroString, $replacementText, $this->email->{$field});
            if($this->debug){
                Log::debug(__METHOD__ . " replacing $macroString with $replacementText in $field");
            }
        }
    }

    /**
     * Check if passed in objects array contains the objects as required by this expansion guide.
     *
     * @return bool
     */
    protected function haveRequiredObjects(){
        foreach($this->requiredObjects as $requiredObject){
            if(empty($this->objects[$requiredObject]) || !is_object($this->objects[$requiredObject])){
                $this->missingObject = $requiredObject;
                return false;
            }
        }
        return true;
    }

    /**
     * Check if this expansion guide should be applied to the message, in that the email contains at least
     * one of the macro strings (see array keys of expansions).
     *
     * @return bool
     */
    protected function applies(){
        if((!empty($this->quickSearch) && $this->containsMacros($this->quickSearch, 'all', true)) ||
            $this->containsMacros($this->macros, 'all')){
            return true;
        }
        return false;
    }

    /**
     * The guide itself which specifies what each macro should be expanded to.
     * For eg. ['users_email' => 'user.email',
     *          'membership_renewal_date' => Membership::where('user_id', $user->id)->first()->pluck('renewal_date')]
     * @return array
     */
    public function expansions(){
        return [];
    }

    public function run()
    {
        if($this->applies()){
            if($this->debug){
                Log::debug(__METHOD__ . " applies as it contains at least one macro we need to expand.");
            }
            if($this->haveRequiredObjects()) {
                if($this->debug){
                    Log::debug(__METHOD__ . " we have the required object(s) to process macro expansions");
                }
                foreach($this->expansions() as $macro => $expansion){
                    if(empty($expansion)){
                        Log::debug(__METHOD__ . " got blank macro expansion for macro $macro in class " . get_class($this));
                    }
                    $this->replaceMacro($macro, $expansion);
                }
            }
            else{
                Log::error(__METHOD__ . " trying to expand macros from {" . get_class($this) . "} but missing a required object: {$this->missingObject}");
            }
        }
    }
}
