<?php
namespace BlackfinWebware\LaravelMailMerge\Services;

use BlackfinWebware\LaravelMailMerge\Models\EmailTemplate;
use BlackfinWebware\LaravelMailMerge\Utils\BlackfinUtils;
use BlackfinWebware\LaravelMailMerge\Utils\ClassFinder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MailMergeService
{
    var $email;
    var $mutable;
    var $objects = null;
    var $headers = [];
    var $debug = false;

    /*
     * prose for README to describe how the different objects will interrelate, and how the system will be able to operate on a group and its nexus to perform the merge.
     * Revolves around a single primary object, what we call the 'nexus' here, and most of the macro expansions will either be properties of this nexus object, or properties of relations of the nexus.
     * The Model name of the nexus is expected to be defined in the MergeDistribution class as its $nexusObject.
     */
    public function __construct(EmailTemplate $email)
    {
        $this->email = $email;
        $this->debug = config('mailmerge.debug');
    }

    /**
     * This will work through all the expansion guides it finds in yur app within the
     * config('mailmerge.namespace')\Macro namespace to see if any will apply to this outgoing message.
     *
     * @throws \ReflectionException
     */
    protected function tryAllExpansionGuides()
    {
        $parentMacroExpansionGuide = 'BlackfinWebware\LaravelMailMerge\Mail\Merge\Macro\ObjectMacroExpansionGuide';
        $classes = ClassFinder::findRecursive(config('mailmerge.namespace') . '\Macro');
        foreach($classes as $className){
            $class = new \ReflectionClass($className);
            if($class->isSubclassOf($parentMacroExpansionGuide)){
                $expansionGuide = new $className($this->mutable, $this->objects);
                if($expansionGuide){
                    if($this->debug) Log::debug(__METHOD__ . " Instantiated class $className in an effort to expand macros");
                    $expansionGuide->run();
                }
                else{
                    Log::error(__METHOD__ . " error encountered instantiating $className");
                }
            }
            else{
                if($this->debug) Log::debug(__METHOD__ . " class $className doesn't extend $parentMacroExpansionGuide, skipping");
            }
        }
    }

    /**
     * The general guide applies all the macros that don't fit neatly into a distinct case. The
     * package's GeneralMacroExpansionGuide will read those rules from the config file, but you can override
     * that by creating your own.
     */
    protected function applyGeneralGuide()
    {
        $appGeneralGuide = config('mailmerge.namespace') . '\Macro\GeneralMacroExpansionGuide';
        $packageGeneralGuide = 'BlackfinWebware\LaravelMailMerge\Mail\Merge\Macro\GeneralMacroExpansionGuide';
        if(class_exists($appGeneralGuide)){
            $expansionGuide = new $appGeneralGuide($this->mutable, $this->objects);
            $expansionGuide->run();
        }
        elseif(class_exists($packageGeneralGuide)){
            $expansionGuide = new $packageGeneralGuide($this->mutable, $this->objects);
            $expansionGuide->run();
        }
        else{
            if($this->debug) Log::debug(__METHOD__ . " Unable to find Macro Expansion Guide for General Cases.");
        }
    }

    /**
     * Apply HTML shortcut substitutions.
     */
    protected function applyHelperExpansions(){
        $this->mutable->message = preg_replace('/mailto:([^@\s]+@[^@\s]+\.[^@\s]+)/', "<a href=\"mailto:$1\">$1</a>", $this->mutable->message);
        $this->mutable->message = preg_replace('/linkto:(https?:\/\/[a-zA-Z0-9_\-\.\/]+)/', "<a href=\"$1\">$1</a>", $this->mutable->message);
    }

    /**
     * If your process has indicated a specific expansion guide to apply, that happens here. If not, we'll try all
     * that we find. Then any general expansions will be applied. If the macros have 100% coverage, each email
     * should be fully populated once this method is complete.
     *
     * @throws \ReflectionException
     */
    protected function expandMacros(){
        //make copy as we are modifying email object
        $this->mutable = $this->email->replicate();

        if(empty($this->objects['expansionGuide'])){
            $this->tryAllExpansionGuides();
        }
        elseif(class_exists($this->objects['expansionGuide'])){
            $expansionGuide = new $this->objects['expansionGuide']($this->mutable, $this->objects);
            if($expansionGuide){
                $expansionGuide->run();
            }
            else{
                Log::error(__METHOD__ . " error encountered when instantiating " . $this->objects['expansionGuide']);
            }
        }
        else{
            if($this->debug) Log::debug(__METHOD__ . " indicated expansion guide class (" . $this->objects['expansionGuide'] . ") not found, so trying all expansion guides in app.");
            $this->tryAllExpansionGuides();
        }
        $this->applyGeneralGuide();
        $this->applyHelperExpansions();
    }

    /**
     * Compose(merge) and send the email.
     *
     * @param $recipient
     * @param $objects
     * @return string
     * @throws \Exception
     */
    public function mergeAndSend($recipient, $objects){
        $this->objects = $objects;
        $this->expandMacros();

        $this->mutable->to = $recipient;
        return BlackfinUtils::sendMail($this->mutable,
                                       $this->headers);
    }

    /**
     * Compose the message in a format to show what would have been sent.
     *
     * @param $recipient
     * @param $objects
     * @return string
     */
    public function mergeForDisplay($recipient, $objects){
        $this->objects = $objects;
        $this->expandMacros();

        $this->mutable->to = $recipient;
        return $this->mutable->toHtmlForBrowser($this->objects['index']);
    }

}
