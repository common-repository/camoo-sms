<?php

namespace CamooSms\Test\TestCase\Objects;

use Camoo\Sms\Entity\ObjectEntity;
use Camoo\Sms\Entity\Recipient;
use Camoo\Sms\Exception\CamooSmsException;
use Camoo\Sms\Lib\Utils;
use Camoo\Sms\Objects\Balance;
use Camoo\Sms\Objects\Base;
use Camoo\Sms\Objects\Message;
use Camoo\Sms\Objects\RecipientCollection;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Class BaseTest
 *
 * @author CamooSarl
 *
 * @covers \Camoo\Sms\Objects\Base
 */
class BaseTest extends TestCase
{
    private ?Base $oBase;

    public function setUp(): void
    {
        $this->oBase = Base::create();
    }

    public function tearDown(): void
    {
        unset($this->oBase);
    }

    /**
     * @covers \Camoo\Sms\Objects\Base::create
     *
     * @runInSeparateProcess
     */
    public function testCreate()
    {
        $this->assertInstanceOf(Base::class, Base::create());
    }

    /**
     * @covers \Camoo\Sms\Objects\Base::set
     *
     * @runInSeparateProcess
     *
     * @dataProvider setDataProviderSuccess
     */
    public function testSetSuccess($property, $value, $object)
    {
        $this->oBase->set($property, $value, $object);
        if ($property === 'to') {
            $value = Utils::makeNumberE164Format($value);
        }
        $this->assertEquals($value, $object->{$property});
    }

    /**
     * @covers \Camoo\Sms\Objects\Base::set
     *
     * @runInSeparateProcess
     *
     * @testWith        ["test", 4, null]
     */
    public function testSetNull($property, $value, $object)
    {
        $this->assertNull($this->oBase->set($property, $value, $object));
    }

    /**
     * @covers \Camoo\Sms\Objects\Base::set
     *
     * @runInSeparateProcess
     *
     * @dataProvider setDataProviderFailure
     */
    public function testSetFailure($property, $value, $object)
    {
        $this->expectException(CamooSmsException::class);
        $this->oBase->set($property, $value, $object);
    }

    /**
     * @covers \Camoo\Sms\Objects\Base::get
     *
     * @runInSeparateProcess
     *
     * @dataProvider getDataProviderSuccess
     */
    public function testGetSuccess(array $sets, ObjectEntity $object): void
    {
        $base = $this->oBase;
        $entity = $object->getInstance();
        foreach ($sets as $key => $value) {
            $base->set($key, $value, $entity);
        }

        $result = $base->get($entity);
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * @covers \Camoo\Sms\Objects\Base::get
     *
     * @runInSeparateProcess
     *
     * @dataProvider getDataProviderFailure
     */
    public function testGetFailure(array $sets, ObjectEntity $object): void
    {
        $this->expectException(CamooSmsException::class);
        $entity = $object->getInstance();
        array_map(function (string $key, mixed $set) use ($entity) {
            $this->oBase->set($key, $set, $entity);
        }, array_keys($sets), array_values($sets));

        $this->oBase->get($entity);
    }

    /**
     * @runInSeparateProcess
     *
     * @dataProvider isUTF8DataProviderFailure
     */
    public function testIsValidUTF8Encoded($message): void
    {
        $oValidator = new Validator(['message' => $message]);
        Base::create()->isValidUTF8Encoded($oValidator, 'message');
        $this->assertFalse($oValidator->validate());
    }

    /**
     * @covers \Camoo\Sms\Objects\Base::has
     *
     * @runInSeparateProcess
     *
     * @testWith        ["to"]
     *                  ["tel"]
     */
    public function testHas($property): void
    {
        $this->assertIsBool(Message::create()->has($property));
    }

    /**
     * @covers \Camoo\Sms\Objects\Base::isPossibleNumber
     *
     * @runInSeparateProcess
     *
     * @testWith        ["0"]
     *                  [["9999"]]
     *                  [["8888"]]
     */
    public function testIsPossibleNumberFailure($to): void
    {
        $oValidator = new Validator(['message' => 'foo', 'from' => 'Bar', 'to' => $to]);
        $this->assertNull(Base::create()->isPossibleNumber($oValidator, 'to'));
        $this->assertFalse($oValidator->validate());
    }

    /**
     * @covers \Camoo\Sms\Objects\Base::notEmptyRule
     *
     * @runInSeparateProcess
     *
     * @testWith        ["0"]
     *                  ["tel"]
     *                  [""]
     */
    public function testNotEmptyRule($value): void
    {
        $oValidator = new Validator(['id' => $value]);
        Base::create()->notEmptyRule($oValidator, 'id');
        $this->assertIsBool($oValidator->validate());
    }

    /** @dataProvider provideValidePossibleNumbers */
    public function testIsPossiblePhoneNumber(mixed $to): void
    {
        $data = [
            'to' => [$to],
        ];
        $validator = new Validator($data);
        $this->oBase->isPossibleNumber($validator, 'to');
        $this->assertTrue($validator->validate());
    }

    /**
     * @covers \Camoo\Sms\Objects\Base::canTopUpCM
     *
     * @runInSeparateProcess
     *
     * @dataProvider provideInvalidTopUpData
     */
    public function testCanTopUpCMFailure(string $to, string $field): void
    {
        $oValidator = new Validator([$field => $to]);
        Base::create()->canTopUpCM($oValidator, $field);
        $this->assertFalse($oValidator->validate());
    }

    /**
     * @covers \Camoo\Sms\Objects\Base::canTopUpCM
     *
     * @runInSeparateProcess
     *
     * @dataProvider provideValidTopUpData
     */
    public function testCanTopUpCM(string $to): void
    {
        $oValidator = new Validator(['phonenumber' => $to]);
        Base::create()->canTopUpCM($oValidator, 'phonenumber');
        $this->assertTrue($oValidator->validate());
    }

    public function provideValidTopUpData(): array
    {
        return [
            ['673123456'],
            ['693123456'],
        ];
    }

    public function provideInvalidTopUpData(): array
    {
        return [
            ['0', 'phonenumber'],
            ['193123456', 'phonenumber'],
            ['693123456', 'to'],
        ];
    }

    public function getDataProviderSuccess(): array
    {
        return [
            [['to' => '237612345678', 'from' => 'Foo', 'message' => 'Foo Bar'],  ObjectEntity::MESSAGE],

            [['to' => [
                '237612345678',
                '237612345671',
                '237612345672',
                '237612345673',

            ], 'from' => 'Foo', 'message' => 'Foo Bar'],  ObjectEntity::MESSAGE],

            [['to' => '237672345678', 'from' => 'Foo', 'message' => 'Foo Bar', 'route' => 'classic'],  ObjectEntity::MESSAGE],

            [['phonenumber' => '672345678', 'amount' => 3000], ObjectEntity::BALANCE],
        ];
    }

    public function getDataProviderFailure(): array
    {
        return [
            [['to' => '612345678', 'from' => 'Foo', 'message' => 'Foo Bar'], ObjectEntity::MESSAGE],
            [
                ['to' => [
                    '33672345678',
                ], 'from' => 'Foo', 'message' => 'Foo Bar', 'route' => 'classic'], ObjectEntity::MESSAGE,
            ],
            [['phonenumber' => '237692345678', 'amount' => 3000.01], ObjectEntity::BALANCE],
        ];
    }

    public function setDataProviderSuccess(): array
    {
        return [
            ['from', 'FooBar', new Message()],
            ['to', '237612345678', new Message()],
            ['to', ['237612345678', '33612345678'], new Message()],
            ['to', [['mobile' => '237612345678', 'name' => 'John Doe'], ['mobile' => '33612345678', 'Jeanne Doe']], new Message()],
            ['phonenumber', '612345678', new Balance()],
        ];
    }

    public function setDataProviderFailure(): array
    {
        return [
            ['sender', 'FooBar', new Message()],
            ['phone', '237612345678', new Message()],
            ['tel', ['237612345678', '33612345678'], new Message()],
            ['sms', [['mobile' => '237612345678', 'name' => 'John Doe'], ['mobile' => '33612345678', 'Jeanne Doe']], new Message()],
            ['to', '612345678', new Balance()],
        ];
    }

    public function isUTF8DataProviderFailure(): array
    {
        return [
            [file_get_contents(dirname(__DIR__, 2) . '/Fixture/UTF-8-test.txt')],
        ];
    }

    public function provideValidePossibleNumbers(): array
    {
        return [
            iterator_to_array(new RecipientCollection(['237693123456'])),
            [new Recipient('237693123456')],
            ['mobile' => '237693123456'],
            ['237693123456'],
        ];
    }
}
