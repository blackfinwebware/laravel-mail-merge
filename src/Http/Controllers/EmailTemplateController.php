<?php

namespace BlackfinWebware\LaravelMailMerge\Http\Controllers;

use BlackfinWebware\LaravelMailMerge\LaravelMailMerge;
use BlackfinWebware\LaravelMailMerge\Models\EmailTemplate;
use BlackfinWebware\LaravelMailMerge\Services\MailMergeService;
use BlackfinWebware\LaravelMailMerge\Utils\BlackfinUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;


/**
 * Description of EmailTemplateController
 *
 * @author Tom Cowin <tom@cowin.us>
 */

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the email templates.
     *
     * @param  Index  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('mailmerge::email_templates.index', [
            'page_title' => 'Email Templates',
            'emails' => EmailTemplate::paginate(25)]);
    }

    /**
     * Show the form for creating a new email template.
     *
     * @param  Create  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $email = new EmailTemplate();
        $groups = array_keys(config('mailmerge.groups'));
        return view('mailmerge::email_templates.create', [
            'email' => $email,
            'page_title' => 'Create Email Template',
            'groups' => $groups,
            'macros' => $this->retrieveAvailableMacros()
        ]);
    }

    /**
     * Store a newly created email template in storage.
     *
     * @param  Store  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
                               'name' => 'unique:mailmerge_email_templates|max:255',
                           ]);
        $email = new EmailTemplate();
        $objects = [];
        if(empty($request->group) && empty($request->to) && (!empty($request->send) || !empty($request->testsend))){
            return redirect()->back()->withErrors('You must specify the recipient by selecting a Group or entering email(s) in the To field.');
        }
        $email->fill($request->except('to'));

        $to_emails = preg_split("/[\s*\r\n,]+/", $request->to, -1, PREG_SPLIT_NO_EMPTY);
        if(!empty($to_emails)){
            $email->to = implode(',', $to_emails);
        }

        if ($email->save()) {
            if(!empty($request->testsend)){
                if(!empty($request->group)){
                    $objects = ['group' => $request->group];
                }
                try{
                    $results = LaravelMailMerge::composeBroadcast($email, $objects);
                } catch(\Exception $e){
                    return redirect()->back()->withErrors('Errors encountered when attempting to compose broadcast emails: ' . $e->getMessage());
                }

                return view('mailmerge::email_templates.testsend', [
                    'page_title' => 'Create Email Template',
                    'emails' => $results
                ]);
            }
            elseif(!empty($request->send)){
                if(!empty($request->group)) {
                    $objects = ['group' => $request->group];
                }
                try{
                    $results = LaravelMailMerge::composeAndSendBroadcast($email, $objects);
                } catch(\Exception $e){
                    return redirect()->back()->withErrors('Errors encountered when attempting to compose broadcast emails: ' . $e->getMessage());
                }
                if(empty($results)){
                    session()->flash('alert-info', 'Email successfully transmitted');
                }
                elseif(BlackfinUtils::stringContains($results, 'success')){
                    session()->flash('alert-info', (is_array($results) ? implode('<br>', $results)  : $results));
                }
                else{
                    session()->flash('alert-danger', 'Errors encountered when transmitting:<br>' . implode('<br>', $results));
                }
            }

            return redirect()->back()->withMessage('Email(s) queued successfully');
        } else {
            session()->flash('alert-info', 'Encountered error when attempting to save EmailTemplate');
        }
        return redirect()->back();
    }

    /**
     * Show the form for editing the email template.
     *
     * @param  Request  $request
     * @param  EmailTemplate  $email
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $emailTemplateId)
    {
        $macros = $this->retrieveAvailableMacros();
        $email = EmailTemplate::find($emailTemplateId);
        $groups = array_keys(config('mailmerge.groups'));
        return view('mailmerge::email_templates.edit', [
            'page_title' => 'Edit Email Template',
            'email' => $email,
            'groups' => $groups,
            'macros' => $macros
        ]);
    }

    /**
     * Take a namespaced class that exists, and is a MacroExpansionGuide, extract macros from it into an array with
     * the key being a descriptive name of the Model or area that it covers.
     *
     * @param String $className
     * @return Array
     * @throws \ReflectionException
     */
    protected function extractMacrosFromClass(String $className) : Array
    {
        $reflector = new \ReflectionClass($className);
        $expansionGuide = $reflector->newInstanceWithoutConstructor();
        $objectName = str_replace(['MacroExpansionGuide', 'MacroExpansion', 'ExpansionGuide', 'MacroGuide', 'Guide'], '', BlackfinUtils::getClassName($className));
        return [$objectName => $expansionGuide->macros];
    }

    /**
     * Read the package config to retrieve a set of available macros organized by model / area in a associative array
     * that will be passed to the view for display.
     *
     * @return Array
     */
    public function retrieveAvailableMacros() : Array
    {
        $macros = [];
        $sets = config('mailmerge.macro_sets');
        $keys = array_keys($sets);
        foreach($keys as $key){
            if(strcasecmp($key, 'general') == 0){
                if(is_array($sets[$key])){
                    $general = ['General' => array_keys($sets[$key])];
                }
                elseif(class_exists($sets[$key])){
                    try{
                        $general = $this->extractMacrosFromClass($sets[$key]);
                    } catch(\ReflectionException $e){
                        Log::error(__METHOD__ . " unable to reflect upon class " . $sets[$key] . " so macros from this not displaying on edit: " . $e->getMessage());
                    }
                }
            }
            elseif(!is_array($sets[$key]) && class_exists($sets[$key])){
                try{
                    $macros += $this->extractMacrosFromClass($sets[$key]);
                } catch(\ReflectionException $e){
                    Log::error(__METHOD__ . " unable to reflect upon class " . $sets[$key] . " so macros from this not displaying on edit: " . $e->getMessage());
                }
                /* for testing width on edit screen of horiz scrolling avail macros display
                   $macros[$objectName . '1'] = $expansionGuide->macros;
                   $macros[$objectName . '2'] = $expansionGuide->macros;
                   $macros[$objectName . '3'] = $expansionGuide->macros; */
            }
        }

        ksort($macros);

        return $general + $macros;
    }

    /**
     * Update an existing email template.
     *
     * @param  Update  $request
     * @param  EmailTemplate  $email
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $emailTemplateId)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                Rule::unique('mailmerge_email_templates')->ignore($emailTemplateId),
            ]
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $email = EmailTemplate::find($emailTemplateId);
        $objects = [];
        if(empty($request->group) && empty($request->to) && (!empty($request->send) || !empty($request->testsend))){
            return redirect()->back()->withErrors('You must specify the recipient by selecting a Group or entering email(s) in the To field.');
        }
        $email->fill($request->all());

        if ($email->save()) {
            session()->flash('alert-info', 'Email template successfully updated');
        }
        else {
            session()->flash('alert-danger', 'Errors encountered when updating email template');
        }
        if(!empty($request->testsend)){
            if(!empty($request->group)){
                $objects = ['group' => $request->group];
            }
            try{
                $results = LaravelMailMerge::composeBroadcast($email, $objects);
            } catch(\Exception $e){
                return redirect()->back()->withErrors('Errors encountered when attempting to compose broadcast emails: ' . $e->getMessage());
            }
            return view('mailmerge::email_templates.testsend', [
                'page_title' => 'Create Email Template',
                'emails' => $results
            ]);
        }
        elseif(!empty($request->send)){
            if(!empty($request->group)){
                $objects = ['group' => $request->group];
            }
            try{
                $results = LaravelMailMerge::composeAndSendBroadcast($email, $objects);
            } catch(\Exception $e){
                return redirect()->back()->withErrors('Errors encountered when attempting to compose broadcast emails: ' . $e->getMessage());
            }
            if(empty($results)){
                session()->flash('alert-info', 'Email successfully transmitted');
            }
            elseif(BlackfinUtils::stringContains($results, 'success')){
                session()->flash('alert-info', (is_array($results) ? implode('<br>', $results)  : $results));
            }
            else{
                session()->flash('alert-danger', 'Errors encountered when transmitting:<br>' . implode('<br>', $results));
            }
        }

        return redirect()->route('email-templates.index');
    }

    /**
     * Show the form for cloning the specified email template.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function clone($id)
    {
        $page_title = 'Clone Email Template';
        $original = EmailTemplate::findOrFail($id);
        $email = $original->replicate();
        $groups = array_keys(config('mailmerge.groups'));
        $macros = $this->retrieveAvailableMacros();

        return view('mailmerge::email_templates.clone', compact('page_title','email', 'groups', 'macros'));
    }

    /**
     * Store a newly cloned email template in storage.
     *
     * @param  Store  $request
     * @return \Illuminate\Http\Response
     */
    public function cloneUpdate(Request $request)
    {
        $request->validate([
                               'name' => 'unique:mailmerge_email_templates|max:255',
                           ]);
        $email = new EmailTemplate;
        $email->fill($request->all());

        if ($email->save()) {
            session()->flash('alert-info', 'Email Template saved successfully');
            return redirect()->route('email-templates.index');
        } else {
            session()->flash('alert-info', 'Encountered error when attempting to save Email Template');
        }
        return redirect()->back();
    }

    /**
     * Delete a  email template from  storage.
     *
     * @param  Destroy  $request
     * @param  EmailTemplate  $email
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Request $request, EmailTemplate $email)
    {
        if ($email->delete()) {
            session()->flash('alert-info', 'Email successfully deleted');
        } else {
            session()->flash('alert-danger', 'Error occurred while deleting EmailTemplate');
        }

        return redirect()->back();
    }
}
