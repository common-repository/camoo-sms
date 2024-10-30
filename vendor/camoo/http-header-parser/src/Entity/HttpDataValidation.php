<?php
/**
 * Author: jairo.rodriguez <jairo@bfunky.net>
 */

namespace BFunky\HttpParser\Entity;

use BFunky\HttpParser\Exception\HttpParserBadFormatException;

class HttpDataValidation
{
    public static function isField(string $httpLine): bool
    {
        return str_contains($httpLine, ':');
    }

    /**
     * @throws HttpParserBadFormatException
     */
    public static function checkHeaderOrRaiseError(string $method, string $path, string $protocol): void
    {
        if (empty($method) || empty($path) || empty($protocol)) {
            throw new HttpParserBadFormatException();
        }
    }
}
