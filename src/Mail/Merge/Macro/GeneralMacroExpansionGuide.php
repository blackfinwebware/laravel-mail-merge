<?php

namespace BlackfinWebware\LaravelMailMerge\Mail\Merge\Macro;

use Illuminate\Support\Facades\Log;

class GeneralMacroExpansionGuide extends ObjectMacroExpansionGuide
{
    public $quickSearch = [];
    public $macros = [];
    public $requiredObjects = [];

    public function expansions(){
        $groups = config('mailmerge.macro_sets');
        if(!empty($groups['general'])) {
            if(is_array($groups['general'])){
                $this->macros = array_keys($groups['general']);
                return $groups['general'];
            }
        }
        return [];
    }

    public function run()
    {
        foreach($this->expansions() as $macro => $expansion) {
            if(empty($expansion)) {
                Log::debug(__METHOD__ . " got blank macro expansion for macro $macro in class " . get_class($this));
            }
            if(config('mailmerge.debug')){
                Log::debug(__METHOD__ . " got macro expansion for macro $macro in class " . get_class($this) . ': ' . $expansion);
            }
            $this->replaceMacro($macro, $expansion);
        }
    }
}
