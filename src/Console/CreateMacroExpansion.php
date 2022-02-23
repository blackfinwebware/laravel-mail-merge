<?php

namespace BlackfinWebware\LaravelMailMerge\Console;

use Illuminate\Console\GeneratorCommand;

class CreateMacroExpansion extends GeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:macroExpansion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Macro Expansion class for Laravel Mail Merge Package';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'MacroExpansion';

    protected function getStub()
    {
        return __DIR__ . '/stubs/macroExpansionGuide.php.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        $namespace = config('mailmerge.namespace');
        $mailmergeNamespace = str_replace(['App\\', 'app\\'], '', $namespace);
        return $rootNamespace . '\\' . $mailmergeNamespace . '\Macro';
    }

    public function handle()
    {
        parent::handle();

        $this->setMailmergeNamespace();
    }

    protected function setMailmergeNamespace()
    {
        // Get the fully qualified class name (FQN)
        $class = $this->qualifyClass($this->getNameInput());

        // get the destination path, based on the default namespace
        $path = $this->getPath($class);

        $content = file_get_contents($path);

        $namespace = config('mailmerge.namespace');
        $content = str_replace('MailmergeNamespace', $namespace, $content);

        file_put_contents($path, $content);
    }
}




/*extends Command
{
/**
 * The name and signature of the console command.
 *
 * @var string
  * /
protected $signature = 'command:name';

/**
 * The console command description.
 *
 * @var string
 * /
protected $description = 'Command description';

/**
 * Create a new command instance.
 *
 * @return void
 * /
public function __construct()
{
    parent::__construct();
}

/**
 * Execute the console command.
 *
 * @return int
 * /
public function handle()
{
    return 0;
}
}
*/
