<?php
/**
 * Author: Jairo Rodríguez <jairo@bfunky.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BFunky\HttpParser\Exception;

class HttpParserBadFormatException extends AbstractHttpParserException
{
    /** @var string */
    public const MESSAGE = 'Bad format on raw data';

    /** HttpParserBadFormatException constructor. */
    public function __construct()
    {
        $this->message = self::MESSAGE;
        parent::__construct($this->message);
    }
}
