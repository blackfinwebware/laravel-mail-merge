<?php

namespace BlackfinWebware\LaravelMailMerge\Exceptions;

use Exception;

class RecipientNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Expecting to find a designated email recipient but none provided.');
    }
}
