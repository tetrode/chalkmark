<?php

declare(strict_types=1);

namespace Chalkmark\Theme;

/**
 * Minimal theme registry/loader. Themes are simple associative arrays mapping
 * color keys (e.g., 'h1', 'text') to ANSI escape sequences (or '' to disable).
 *
 * Built-in themes live in src/Theme/themes/*.php and return an array.
 */
final class ThemeRegistry
{
    /** @var array<string,array<string,string>> */
    private static array $registered = [];

    /**
     * Register a theme at runtime.
     *
     * @param array<string,string> $palette
     */
    public static function register(string $name, array $palette): void
    {
        self::$registered[$name] = $palette;
    }

    /**
     * Retrieve a theme palette by name or file path. Falls back to 'default'.
     *
     * @return array<string,string>
     */
    public static function get(string $name): array
    {
        // If already registered explicitly
        if (isset(self::$registered[$name])) {
            return self::$registered[$name];
        }

        // If $name points to a file, try to load it
        if (self::looksLikePath($name)) {
            $palette = self::loadFromFile($name);
            if ($palette !== null) {
                return $palette;
            }
        }

        // Try built-in themes directory
        $builtin = __DIR__.'/themes/'.basename($name).'.php';
        $palette = self::loadFromFile($builtin);
        if ($palette !== null) {
            return $palette;
        }

        // Fallback to built-in default
        $fallback = __DIR__.'/themes/default.php';
        $palette = self::loadFromFile($fallback);

        return $palette ?? [];
    }

    /**
     * @return list<string> names of available built-in themes (without .php)
     */
    public static function listBuiltins(): array
    {
        $dir = __DIR__.'/themes';
        if (!is_dir($dir)) {
            return [];
        }
        $out = [];
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            if (substr($f, -4) === '.php') {
                $out[] = substr($f, 0, -4);
            }
        }

        return $out;
    }

    private static function looksLikePath(string $value): bool
    {
        return str_contains($value, DIRECTORY_SEPARATOR) || str_ends_with($value, '.php');
    }

    /**
     * @return array<string,string>|null
     */
    private static function loadFromFile(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }
        /** @var mixed $data */
        $data = (static function (string $p) {
            /** @noinspection PhpIncludeInspection */
            return include $p;
        })($path);
        if (is_array($data)) {
            // Coerce values to string
            $out = [];
            foreach ($data as $k => $v) {
                if (!is_string($k)) {
                    continue;
                }
                $out[$k] = (string)$v;
            }
            return $out;
        }

        return null;
    }
}
