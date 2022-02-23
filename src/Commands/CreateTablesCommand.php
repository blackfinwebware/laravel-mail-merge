<?php

namespace BlackfinWebware\LaravelMailMerge\Commands;

use Illuminate\Console\Command;

class CreateTablesCommand extends Command
{
    public $signature = 'mailmerge';

    public $description = 'Command to create package tables if using these.';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
