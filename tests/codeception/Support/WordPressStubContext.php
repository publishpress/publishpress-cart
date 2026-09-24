<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Delegates WordPress function calls to per-test stub handlers.
 */
final class WordPressStubContext
{
    /**
     * @var array<string, callable>
     */
    private static $handlers = [];

    /**
     * @var array<string, mixed>
     */
    private static $state = [];

    /**
     * @param string   $function
     * @param callable $handler
     * @return void
     */
    public static function set(string $function, callable $handler): void
    {
        self::$handlers[$function] = $handler;
    }

    /**
     * @param string $function
     * @return bool
     */
    public static function has(string $function): bool
    {
        return isset(self::$handlers[$function]);
    }

    /**
     * @param string $function
     * @param array  $args
     * @return mixed
     */
    public static function invoke(string $function, array $args)
    {
        if (! isset(self::$handlers[$function])) {
            throw new \RuntimeException(
                sprintf('No WordPress stub handler registered for %s().', $function)
            );
        }

        return (self::$handlers[$function])(...$args);
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    public static function setState(string $key, $value): void
    {
        self::$state[$key] = $value;
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public static function getState(string $key, $default = null)
    {
        return array_key_exists($key, self::$state) ? self::$state[$key] : $default;
    }

    /**
     * @return void
     */
    public static function clear(): void
    {
        self::$handlers = [];
        self::$state = [];
    }
}
