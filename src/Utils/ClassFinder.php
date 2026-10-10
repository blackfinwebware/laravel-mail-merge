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
        $namespace = trim($namespace, '\\');

        // Where Composer's PSR-4 autoloading puts the namespace (the longest matching prefix wins)
        $best = '';
        $bestPath = '';
        foreach (\Composer\Autoload\ClassLoader::getRegisteredLoaders() as $loader) {
            foreach ($loader->getPrefixesPsr4() as $prefix => $dirs) {
                $prefix = rtrim($prefix, '\\');
                if (($namespace === $prefix || str_starts_with($namespace, $prefix . '\\')) && strlen($prefix) > strlen($best)) {
                    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($namespace, strlen($prefix)));
                    foreach ($dirs as $dir) {
                        if ($path = realpath($dir . $relative)) {
                            $best = $prefix;
                            $bestPath = $path;
                            break;
                        }
                    }
                }
            }
        }
        if ($bestPath !== '') {
            return $bestPath;
        }

        // Otherwise the Laravel convention: App\Foo is in app/Foo under the base path
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
