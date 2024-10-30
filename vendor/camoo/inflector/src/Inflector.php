<?php

declare(strict_types=1);

namespace Camoo\Inflector;

use Camoo\Inflector\Exception\InflectorException;
use Doctrine\Inflector\InflectorFactory;

/**
 * @method static string capitalize(string $word)
 * @method static string classify(string $word)
 * @method static string camelize(string $word)
 * @method static string tableize(string $word)
 * @method static string seemsUtf8(string $word)
 * @method static string unaccent(string $word)
 * @method static string urlize(string $word)
 * @method static string singularize(string $word)
 * @method static string pluralize(string $word)
 */
class Inflector
{
    /** Inflector should not be Instantiated */
    private function __construct()
    {
    }

    public static function __callStatic(string $method, array $args): string
    {
        $inflector = InflectorFactory::create()->build();
        if (!method_exists($inflector, $method)) {
            throw new InflectorException(sprintf('Method %s::%s does not exist', get_class(new self()), $method));
        }

        return call_user_func_array([$inflector, $method], $args);
    }

    public static function humanize(string $word, string $separator = '_'): string
    {
        return implode(' ', array_map([self::class, 'classify'], explode($separator, $word)));
    }
}
