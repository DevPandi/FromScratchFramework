<?php

namespace Taiyo\FromScratch\Autoload;

final class PSR4Autoloader
{
    protected const NAMESPACE_REGEX = '#^([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\\)+$#'; // One thing i hate in php pcre = find \ needs \\\\ ... Pandi do not under stand it.
    protected static ?self $loader = null;
    protected array $namespaces = [];

    // okay, singleton classes are not so good, but for a autoloader? We only need one autoloader.
    protected function __construct(?string $namespace, ?string $path)
    {
        if ($namespace!== null && $path !== null) {
            $this->registerNamespace($namespace, $path);
        }
    }

    // PSR4
    protected function checkPSR4Namespace($namespace, $path): void
    {
        if (empty($namespace) || \preg_match(static::NAMESPACE_REGEX, $namespace) === false) {
            throw new \InvalidArgumentException('"' . $namespace . '" is invalid.');
        }

        if (!is_dir($path)) {
            throw new \InvalidArgumentException('"' . $path . '" not found.');
        }
    }

    public function registerNamespace(string $namespace, string $path): self
    {
        $namespace = trim($namespace);
        $path = trim($path);

        // unify path separators
        $path = str_replace(['\\\\', '\\'], '/', $path);

        // add slash to end.
        if (\str_ends_with($path, '/') === false) {
            $path .= '/';
        }

        // remove back slash from beginning.
        if (\str_starts_with($namespace, '\\')) {
            $namespace = \substr($namespace, 1);
        }

        // adds backslash to end.
        if (\str_ends_with($namespace, '\\') === false) {
            $namespace .= '\\';
        }

        // check namespace and path,
        $this->checkPSR4Namespace($namespace, $path);

        // register
        $this->namespaces[$namespace] = $path;

        return $this;
    }

    public function load(string $className): void
    {
        foreach ($this->namespaces as $namespace => $path) {
            if (\str_starts_with($className, $namespace)) {
                $path = \str_replace($namespace, $path, $className) . '.php';
                $path = \str_replace(['\\\\', '\\'], '/', $path);

                if (\file_exists($path)) {
                    include($path);
                }
            }
        }
    }

    /**
     * @param string $namespace
     * @param string $path
     * @return static
     */
    public static function initAutoloader(string $namespace, string $path): self
    {
        if (self::$loader === null) {
            self::$loader = new PSR4Autoloader($namespace, $path);

            spl_autoload_register([self::$loader, 'load']);
        }

        return self::$loader;
    }
}
