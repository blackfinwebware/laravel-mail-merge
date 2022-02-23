<?php

namespace BlackfinWebware\LaravelMailMerge\Exceptions;

use Exception;

class TemplateNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Expected to find an email template name but none provided.');
    }
}
