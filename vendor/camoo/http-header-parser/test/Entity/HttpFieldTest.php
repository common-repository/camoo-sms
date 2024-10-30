<?php
/**
 * Author: Jairo Rodríguez <jairo@bfunky.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BFunky\Test\HttpParser\Entity;

use BFunky\HttpParser\Entity\HttpField;
use PHPUnit\Framework\TestCase;

class HttpFieldTest extends TestCase
{
    public function testGetters()
    {
        $field = new HttpField('a name', 'a value');
        $this->assertEquals($field->getName(), 'a name');
        $this->assertEquals($field->getValue(), 'a value');
    }

    public function testSetters()
    {
        $field = new HttpField('a name', 'a value');
        $field->setName('a different name');
        $field->setValue('a different value');
        $this->assertEquals($field->getName(), 'a different name');
        $this->assertEquals($field->getValue(), 'a different value');
    }
}
