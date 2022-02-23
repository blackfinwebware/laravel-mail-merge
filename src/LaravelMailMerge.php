<?php

namespace BlackfinWebware\LaravelMailMerge;

use BlackfinWebware\LaravelMailMerge\Exceptions\RecipientNotFoundException;
use BlackfinWebware\LaravelMailMerge\Exceptions\TemplateNotFoundException;
use BlackfinWebware\LaravelMailMerge\Models\EmailTemplate;
use BlackfinWebware\LaravelMailMerge\Services\MailMergeService;
use BlackfinWebware\LaravelMailMerge\Utils\BlackfinUtils;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LaravelMailMerge
{
    /**
     * Retrieve MergeDistribution group based upon groupName -- should be either specified in the config, or be
     * derivable given expected naming convention of App\Mail\Merge\Distribution\{GroupName}(MergeDistribution).
     *
     * @param $groupName
     */
    protected static function findMergeDistribution(String $groupName)
    {
        $groups = config('mailmerge.groups');
        if(!empty($groups[$groupName])) {
            $distributionClassName = $groups[$groupName];
            if(class_exists($distributionClassName)) {
                return $distributionClassName;
            }
        }
        $distributionClassName = config('mailmerge.namespace') . 'Distribution\\' . BlackfinUtils::camelCase($groupName);
        if(class_exists($distributionClassName)) {
            return $distributionClassName;
        }
        else{
            $distributionClassName .= 'MergeDistribution';
            if(class_exists($distributionClassName)) {
                return $distributionClassName;
            }
        }
        return null;
    }

    /**
     * Retrieve appropriate macro expansions that correspond with the nexus - the primary object at the center of this
     * process. Check the config macro_sets first, then, if that isn't set, try to find a guide in the class using the
     * naming convention 'App\Mail\Merge\Macro\{NexusObjectName}(MacroExpansionGuide)'
     *
     * @param $nexusObjectName
     * @return String classname of MacroExpansionGuide or null if not found
     */
    protected static function findMacroExpansion(String $nexusObjectName) : String
    {
        if($nexusObjectName){
            $nexusSlug = Str::snake($nexusObjectName);
            $groups = config('mailmerge.macro_sets');
            if(!empty($groups[$nexusSlug])) {
                if(!is_array($groups[$nexusSlug]) && class_exists($groups[$nexusSlug])){
                    return $groups[$nexusSlug];
                }
            }
            $className = config('mailmerge.namespace') . 'Macro\\' . $nexusObjectName . 'MacroExpansionGuide';
            if(class_exists($className)){
                return $className;
            }
            else{
                $className = config('mailmerge.namespace') . 'Macro\\' . $nexusObjectName;
                if(class_exists($className)){
                    return $className;
                }
            }
        }
        return '';
    }

    /**
     * Given the emailTemplate instance and an objects array that specifies a group, locate the required objects in
     * the system, and then compose the appropriate messages to each recipient, transmitting in turn. Returns an
     * array of failure message(s) if there were any issues, otherwise will return an empty array.
     *
     * Locate the required corresponding MergeDistribution class, and possibly the corresponding MacroExpansionGuide
     * class. Use these to derive the set of recipients and the appropriate objects that will allow for correct macro
     * expansion.
     *
     * @param EmailTemplate $email
     * @param array $objects
     * @return Array of possible Mail success or failure messages
     * @throws \Exception
     */
    public static function composeAndSendBroadcast(EmailTemplate $email, Array $objects = []) : Array
    {
        $messages = [];
        $success = 0;
        $mailMergeService = new MailMergeService($email);

        if(empty($objects['group'])) {
            if(empty($email->to)){
                throw new \Exception('Neither group nor recipients provided.');
            }
            $return = $mailMergeService->mergeAndSend($email->to, $objects);
            if($return !== 0){
                $messages[] = $return;
            }
        }
        else{
            list($distributionClass, $objects, $recipients, $nexusSlug) = self::assembleMergeResources($objects);
            foreach($recipients as $recipient) {
                $objects[$nexusSlug] = $distributionClass->getNexusForUser($recipient);
                $return = $mailMergeService->mergeAndSend($recipient->email, $objects);
                if($return !== 0 && !empty($return)){
                    $messages[] = $return;
                }
                else{
                    $success++;
                }
            }
        }
        if(empty($messages) && $success){
            $messages[] = "Successfully sent $success email" . ($success > 1 ? 's.' : '.');
        }

        return $messages;
    }

    /**
     * Retrieve appropriste classes, strings and recipient list for the indicated group merge.
     *
     * @param array $objects
     * @return array
     * @throws \Exception
     */
    public static function assembleMergeResources(array $objects): array
    {
        $distributionClassName = self::findMergeDistribution($objects['group']);
        if(!$distributionClassName) {
            throw new \Exception("Unable to find Merge Distribution class when trying to compose email for group " . $objects['group'] . " in " . __METHOD__);
        }
        $distributionClass = new $distributionClassName();
        $expansionGuide = self::findMacroExpansion($distributionClass->getNexusObjectName());
        if(!$expansionGuide) {
            Log::debug("Unable to find Macro Expansion class based upon an object of " . $distributionClass->getNexusObjectName() . " when trying to compose email in " . __METHOD__);
        }
        else {
            $objects['expansionGuide'] = $expansionGuide;
        }
        try {
            $recipients = $distributionClass->getDistributionList();
        } catch(\Exception $e) {
            throw new \Exception("Encountered error [" . $e->getMessage() . "] when trying to retrieve the distribution list for " . $objects['group']);
        }
        if($recipients->isEmpty()) {
            throw new \Exception("No recipients found for distribution of group " . $objects['group']);
        }
        $nexusSlug = Str::snake($distributionClass->getNexusObjectName());
        return array($distributionClass, $objects, $recipients, $nexusSlug);
    }

    /**
     * Given the emailTemplate instance and an objects array that specifies a group, locate the required objects in
     * the system, and then compose the appropriate messages to each recipient, formatting each for display and return
     * the entire set as one HTML string.
     *
     * Locate the required corresponding MergeDistribution class, and possibly the corresponding MacroExpansionGuide
     * class. Use these to derive the set of recipients and the appropriate objects that will allow for correct macro
     * expansion.
     *
     * @param EmailTemplate $email
     * @param array $objects
     * @return string
     * @throws \Exception
     */
    public static function composeBroadcast(EmailTemplate $email, Array $objects = []) : String
    {
        $content = '';
        $objects['index'] = 1;
        $mailMergeService = new MailMergeService($email);
        if(empty($objects['group'])) {
            if(empty($email->to)){
                throw new \Exception('Neither group nor recipients provided.');
            }
            $content .= $mailMergeService->mergeForDisplay($email->to, $objects);
        }
        else{
            list($distributionClass, $objects, $recipients, $nexusSlug) = self::assembleMergeResources($objects);
            foreach($recipients as $recipient) {
                $objects[$nexusSlug] = $distributionClass->getNexusForUser($recipient);
                $content .= $mailMergeService->mergeForDisplay($recipient->email, $objects);
                $objects['index']++;
            }
        }
        return $content;
    }

    /**
     * Provided the named template and an objects array expected to contain the message recipient (to) and an
     * object instance that will provide the values for the macro substitutions, perform the merge on the emailTemplate
     * and transmit the resulting message. Returns a failure message if there was an issue, otherwise will return a 0.
     *
     * @param String $templateName
     * @param array $objects
     * @throws RecipientNotFoundException
     * @throws TemplateNotFoundException
     */
    public static function composeAndSendNotification(String $templateName, Array $objects){
        $notificationEmailClass = config('mailmerge.notification_email_class');
        if($notificationEmailClass && class_exists($notificationEmailClass)){
            $emailTemplate = $notificationEmailClass::whereName($templateName)->first();
        }
        else{
            $emailTemplate = EmailTemplate::whereName($templateName)->first();
        }
        if(!$emailTemplate){
            throw new TemplateNotFoundException();
        }

        if(empty($objects['to'])){
            throw new RecipientNotFoundException();
        }
        $mailMergeService = new MailMergeService($emailTemplate);
        return $mailMergeService->mergeAndSend($objects['to'], $objects);
    }
}
