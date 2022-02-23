<?php
/**
 * @see ClassHelper in https://stackoverflow.com/questions/22761554/php-get-all-class-names-inside-a-particular-namespace
 */
namespace BlackfinWebware\LaravelMailMerge\Utils;

use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ClassFinder
{
    /**
     * @param string $namespace
     * @return array
     */
    public static function findRecursive(string $namespace): array
    {
        $namespacePath = self::translateNamespacePath($namespace);
        if ($namespacePath === '') {
            Log::error(__METHOD__ . 'Unable to discover appropriate filesystem path from which to search for classes.');
            return [];
        }

        return self::searchClasses($namespace, $namespacePath);
    }

    /**
     * Convert namespace into absolute path on filesystem.
     *
     * @param string $namespace
     * @return string
     */
    protected static function translateNamespacePath(string $namespace): string
    {
        //Laravel call to get document root
        $rootPath = base_path() . DIRECTORY_SEPARATOR;
        //adapt from namespace to filesystem path Laravel convention
        $nsParts = explode('\\', str_replace('App\\', 'app\\', $namespace));

        if (empty($nsParts)) {
            if ($namespace){
                Log::debug(__METHOD__ . " Unable to break down provided namespace into its constituent elements.");
            }
            return '';
        }

        return realpath($rootPath. implode(DIRECTORY_SEPARATOR, $nsParts)) ?: '';
    }

    /**
     * Obtain a list of classes within the namespace recursively.
     *
     * @param string $namespace
     * @param string $namespacePath
     * @return array
     */
    private static function searchClasses(string $namespace, string $namespacePath): array
    {
        $classes = [];

        /**
         * @var \RecursiveDirectoryIterator $iterator
         * @var \SplFileInfo $item
         */
        foreach ($iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($namespacePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        ) as $item) {
            if ($item->isDir()) {
                $nextPath = $iterator->current()->getPathname();
                $nextNamespace = $namespace . '\\' . $item->getFilename();
                $classes = array_merge($classes, self::searchClasses($nextNamespace, $nextPath));
                continue;
            }
            if ($item->isFile() && $item->getExtension() === 'php') {
                $class = $namespace . '\\' . $item->getBasename('.php');
                if (!class_exists($class)) {
                    continue;
                }
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
