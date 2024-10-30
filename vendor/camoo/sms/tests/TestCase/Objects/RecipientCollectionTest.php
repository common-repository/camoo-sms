<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase\Objects;

use Camoo\Sms\Entity\Recipient;
use Camoo\Sms\Objects\RecipientCollection;
use PHPUnit\Framework\TestCase;

final class RecipientCollectionTest extends TestCase
{
    private ?RecipientCollection $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new RecipientCollection(['+237612345678', '+237612345679', '+237612345610', '+33689764530']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->collection = null;
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(RecipientCollection::class, $this->collection);
        $this->assertIsIterable($this->collection);
    }

    public function testCanAdd(): void
    {
        $collection = new RecipientCollection();
        $collection->add('+33689764530');
        $array = iterator_to_array($collection);
        $this->assertEquals($array[33689764530], new Recipient('+33689764530'));
    }

    public function testWithoutRecipients(): void
    {
        $collection = new RecipientCollection();
        $this->assertCount(0, $collection);
    }
}
