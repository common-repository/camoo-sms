<?php
/**
 * Author: Jairo Rodríguez <jairo@bfunky.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BFunky\HttpParser;

use BFunky\HttpParser\Exception\HttpParserBadFormatException;

interface HttpParserInterface
{
    /** @throws HttpParserBadFormatException */
    public function parse(string $rawHttpHeader): void;
}
