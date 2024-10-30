<?php
/**
 * Author: Jairo Rodríguez <jairo@bfunky.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BFunky\Test\HttpParser;

use BFunky\HttpParser\Entity\HttpRequestHeader;
use BFunky\HttpParser\Exception\HttpParserBadFormatException;
use BFunky\HttpParser\HttpRequestParser;
use PHPUnit\Framework\TestCase;

class HttpRequestParserTest extends TestCase
{
    public function testParseHttpRequestHeader()
    {
        $parser = new HttpRequestParser();
        $raw = <<<RAW
POST /path HTTP/1.1
User-Agent: PHP-SOAP/\BeSimple\SoapClient
Host: url.com:80
Accept: */*
Accept-Encoding: deflate, gzip
Content-Type:text/xml; charset=utf-8
Content-Length: 1108
Expect: 100-continue

RAW;
        $parser->parse($raw);
        $this->assertEquals($parser->get('User-Agent'), 'PHP-SOAP/\BeSimple\SoapClient');
        $this->assertEquals($parser->get('Host'), 'url.com:80');
        $this->assertEquals($parser->get('Accept'), '*/*');
        $this->assertEquals($parser->get('Accept-Encoding'), 'deflate, gzip');
        $this->assertEquals($parser->get('Content-Type'), 'text/xml; charset=utf-8');
        $this->assertEquals($parser->get('Content-Length'), '1108');
        $this->assertEquals($parser->get('Expect'), '100-continue');
        /**
         * @var HttpRequestHeader $entityHeader
         */
        $entityHeader = $parser->getHeader();
        $this->assertInstanceOf('\BFunky\HttpParser\Entity\HttpRequestHeader', $entityHeader);
        $this->assertEquals($entityHeader->getMethod(), 'POST');
        $this->assertEquals($entityHeader->getPath(), '/path');
        $this->assertEquals($entityHeader->getProtocol(), 'HTTP/1.1');
    }

    public function testThrownExceptionIfWrongRawData()
    {
        $this->expectException(HttpParserBadFormatException::class);
        $parser = new HttpRequestParser();
        $raw = <<<RAW
POST /path
User-Agent: PHP-SOAP/\BeSimple\SoapClient

RAW;
        $parser->parse($raw);
    }
}
