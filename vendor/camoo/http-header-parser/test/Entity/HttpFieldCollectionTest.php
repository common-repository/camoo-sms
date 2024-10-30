<?php
/**
 * Author: Jairo Rodríguez <jairo@bfunky.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BFunky\Test\HttpParser\Entity;

use BFunky\HttpParser\Entity\HttpField;
use BFunky\HttpParser\Entity\HttpFieldCollection;
use BFunky\HttpParser\Exception\HttpFieldNotFoundOnCollection;
use PHPUnit\Framework\TestCase;

class HttpFieldCollectionTest extends TestCase
{
    public function testGetElement()
    {
        $collection = new HttpFieldCollection([HttpField::fromKeyAndValue('key', 'value')]);
        $element = $collection->get('key');
        $this->assertEquals('value', $element->getValue());
    }

    public function testAddElement()
    {
        $collection = new HttpFieldCollection();
        $collection->add(HttpField::fromKeyAndValue('key', 'value'));
        $element = $collection->get('key');
        $this->assertEquals('value', $element->getValue());
    }

    public function testDeleteElement()
    {
        $this->expectException(HttpFieldNotFoundOnCollection::class);
        $collection = new HttpFieldCollection([HttpField::fromKeyAndValue('key', 'value')]);
        $collection->delete('key');
        $collection->get('key');
    }

    public function testNamedConstructorFromHttpFieldArray()
    {
        $collection = HttpFieldCollection::fromHttpFieldArray([HttpField::fromKeyAndValue('key', 'value')]);
        $element = $collection->get('key');
        $this->assertEquals('value', $element->getValue());
    }

    public function testGetMethodReturnsErrorIfElementDoesNotExists()
    {
        $this->expectException(HttpFieldNotFoundOnCollection::class);
        $collection = new HttpFieldCollection();
        $collection->get('key');
    }

    public function testDeleteMethodReturnsErrorIfElementDoesNotExists()
    {
        $this->expectException(HttpFieldNotFoundOnCollection::class);
        $collection = new HttpFieldCollection();
        $collection->delete('key');
    }

    public function testCanGetFields(): void
    {
        $field = HttpField::fromKeyAndValue('key', 'value');
        //var_dump($field);
        $collection = new HttpFieldCollection([$field]);
        //var_dump($collection->getHttpFields());
        $this->assertCount(1, $collection->getHttpFields());
    }
}
