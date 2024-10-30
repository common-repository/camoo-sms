<?php
/**
 * Author: Jairo Rodríguez <jairo@bfunky.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BFunky\HttpParser;

use BFunky\HttpParser\Entity\HttpRequestHeader;

class HttpRequestParser extends AbstractHttpParser
{
    /** @inheritdoc */
    protected function setHttpHeader(string $method, string $path, string $protocol): void
    {
        $this->httpHeader = new HttpRequestHeader($method, $path, $protocol);
    }
}
