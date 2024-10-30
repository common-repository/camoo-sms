<?php

declare(strict_types=1);

namespace CamooSms\Test\TestCase\Objects;

use Camoo\Sms\Objects\TopUp;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Class TopUpTest
 *
 * @author CamooSarl
 *
 * @covers \Camoo\Sms\Objects\TopUp
 */
class TopUpTest extends TestCase
{
    /**
     * @covers \Camoo\Sms\Objects\TopUp::validatorDefault
     *
     * @dataProvider defaultDataProviderSuccess
     */
    public function testValidatorDefaultSuccess(array $payload): void
    {
        $oValidator = (new TopUp())->validatorDefault(new Validator($payload));
        $this->assertInstanceOf(Validator::class, $oValidator);
        $this->assertTrue($oValidator->validate());
    }

    /**
     * @covers \Camoo\Sms\Objects\TopUp::validatorDefault
     *
     * @dataProvider defaultDataProviderFailure
     */
    public function testValidatorDefaultFailure(array $payload): void
    {
        $oValidator = (new TopUp())->validatorDefault(new Validator($payload));
        $this->assertInstanceOf(Validator::class, $oValidator);
        $this->assertFalse($oValidator->validate());
    }

    public function defaultDataProviderFailure(): array
    {
        return [
            [['amount' => 1000, 'to' => '691243568']],
            [['amount' => 'foo', 'phonenumber' => '671243568']],
            [['amount' => '1500', 'to' => '671243568']],
            [['amount' => 2999, 'phonenumber' => '671243568']],
            [['amount' => 2999.99, 'phonenumber' => '691243568']],
        ];
    }

    public function defaultDataProviderSuccess(): array
    {
        return [
            [['amount' => 3000, 'phonenumber' => '671243568']],
            [['amount' => 4000, 'phonenumber' => '691243568']],
        ];
    }
}
