<?php
/**
 * Author: Jairo Rodríguez <jairo@bfunky.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BFunky\Test\HttpParser\Exception;

use BFunky\HttpParser\Exception\HttpParserBadFormatException;
use PHPUnit\Framework\TestCase;

class HttpParserBadFormatExceptionTest extends TestCase
{
    public function testMessage()
    {
        $exception = new HttpParserBadFormatException();
        $this->assertEquals($exception->getMessage(), 'Bad format on raw data');
    }
}
