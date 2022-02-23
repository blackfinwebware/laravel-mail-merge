<?php

namespace BlackfinWebware\LaravelMailMerge;

/**
 * @see \BlackfinWebware\LaravelMailMerge\LaravelMailMerge
 */
class Facade extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return LaravelMailMerge::class;
    }
}
