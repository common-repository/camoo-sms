<?php

namespace CamooSms\Test\TestCase\Objects;

use Camoo\Sms\Entity\Recipient;
use Camoo\Sms\Objects\Message;
use Camoo\Sms\Objects\RecipientCollection;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Class MessageTest
 *
 * @author CamooSarl
 *
 * @covers \Camoo\Sms\Objects\Message
 */
class MessageTest extends TestCase
{
    private Message $oMessage;

    public function setUp(): void
    {
        $this->oMessage = new Message();
    }

    public function tearDown(): void
    {
        unset($this->oMessage);
    }

    /**
     * @covers \Camoo\Sms\Objects\Message::validatorDefault
     *
     * @dataProvider defaultDataProviderSuccess
     */
    public function testValidatorDefaultSuccess($payload)
    {
        $oValidator = $this->oMessage->validatorDefault(new Validator($payload));
        $this->assertInstanceOf(Validator::class, $oValidator);
        $this->assertTrue($oValidator->validate());
    }

    /**
     * @covers \Camoo\Sms\Objects\Message::validatorDefault
     *
     * @dataProvider defaultDataProviderFailure
     */
    public function testValidatorDefaultFailure($payload)
    {
        $oValidator = $this->oMessage->validatorDefault(new Validator($payload));
        $this->assertInstanceOf(Validator::class, $oValidator);
        $this->assertFalse($oValidator->validate());
    }

    /**
     * @covers \Camoo\Sms\Objects\Message::validatorView
     *
     * @dataProvider viewDataProviderFailure
     */
    public function testValidatorViewFailure($payload)
    {
        $oValidator = $this->oMessage->validatorView(new Validator($payload));
        $this->assertInstanceOf(Validator::class, $oValidator);
        $this->assertFalse($oValidator->validate());
    }

    /**
     * @covers \Camoo\Sms\Objects\Message::validatorView
     *
     * @dataProvider viewDataProviderSuccess
     */
    public function testValidatorViewSuccess($payload)
    {
        $oValidator = $this->oMessage->validatorView(new Validator($payload));
        $this->assertInstanceOf(Validator::class, $oValidator);
        $this->assertTrue($oValidator->validate());
    }

    public function viewDataProviderSuccess(): array
    {
        return [
            [['id' => 'fh84948fiif']],
            [['id' => 123456]],
        ];
    }

    public function viewDataProviderFailure(): array
    {
        return [
            [['id' => '']],
            [['id' => null]],
            [['id' => 0]],
        ];
    }

    public function defaultDataProviderFailure(): array
    {
        return [
            [['to' => ['691243568'], 'message' => 'foo bar', 'from' => 'FooBar']],
            [['to' => ['237691243568'], 'message' => 'foo bar', 'from' => 'FooBar', 'pgp_public_file' => '/tmp/test.pub']],
        ];
    }

    public function defaultDataProviderSuccess(): array
    {
        $collection = new RecipientCollection();
        $collection->add('237692123456');
        $collection->add('237692123451');
        $collection->add('237692123452');
        $collection->add('237692123453');
        $collection->add('237692123454');

        return [
            [['to' => ['237691243568'], 'message' => 'foo bar', 'from' => 'FooBar']],
            [['to' => [['mobile' => '237691243568']], 'message' => 'foo bar', 'from' => 'FooBar']],
            [['to' => ['237691243568', '4917610830190'], 'message' => 'foo bar', 'from' => 'FooBar']],
            [['to' => [new Recipient('237692123456')], 'message' => 'foo bar', 'from' => 'FooBar']],
            [['to' => $collection, 'message' => 'foo bar', 'from' => 'FooBar']],
        ];
    }
}
