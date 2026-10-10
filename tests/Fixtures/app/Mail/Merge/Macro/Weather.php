<?php

namespace App\Mail\Merge\Macro;

use BlackfinWebware\LaravelMailMerge\Mail\Merge\Macro\ObjectMacroExpansionGuide;

/** Found by the class scan; named without the MacroExpansionGuide suffix. Needs no objects. */
class Weather extends ObjectMacroExpansionGuide
{
    public $macros = ['forecast'];

    public function expansions()
    {
        return ['forecast' => 'Sunny'];
    }
}
