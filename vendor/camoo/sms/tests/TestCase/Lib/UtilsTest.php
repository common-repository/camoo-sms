<?php

namespace CamooSms\Test\TestCase\Lib;

use Camoo\Sms\Entity\Recipient;
use Camoo\Sms\Lib\Utils;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class UtilsTest
 *
 * @author CamooSarl
 *
 * @covers \Camoo\Sms\Lib\Utils
 */
class UtilsTest extends TestCase
{
    /**
     * @covers       \Camoo\Sms\Lib\Utils::satanizer
     *
     * @backupGlobals disabled
     *
     * @dataProvider satanizerDataProviderFailure2
     */
    public function testSatanizerFailure2($xData)
    {
        $this->assertEmpty(Utils::satanizer($xData));
    }

    /**
     * @covers \Camoo\Sms\Lib\Utils::satanizer
     *
     * @backupGlobals disabled
     */
    public function testSatanizerSuccess()
    {
        $this->assertIsString(Utils::satanizer('<b>foo</b>'));
        $this->assertIsString(Utils::satanizer('<b/bar'));
        $this->assertIsString(Utils::satanizer('%abcdef09'));
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::stripAllTags
     *
     * @backupGlobals disabled
     *
     * @dataProvider stripAllTagsDataProvider
     */
    public function testStripAllTags($string, $remove_breaks)
    {
        $this->assertIsString(Utils::stripAllTags($string, $remove_breaks));
    }

    /**
     * @covers \Camoo\Sms\Lib\Utils::decodeJson
     *
     * @backupGlobals disabled
     */
    public function testDecodeJsonFailure()
    {
        $this->assertNull(Utils::decodeJson(''));
    }

    /** @covers \Camoo\Sms\Lib\Utils::phoneUtil */
    public function testPhoneUtil()
    {
        $this->assertInstanceOf(PhoneNumberUtil::class, Utils::phoneUtil());
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::getNumberProto
     *
     * @dataProvider numberProviderSuccess
     */
    public function testGetNumberProtoSuccess($tel, $ccode)
    {
        $this->assertInstanceOf(PhoneNumber::class, Utils::getNumberProto($tel, $ccode));
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::getNumberProto
     *
     * @dataProvider numberProviderFailure
     */
    public function testGetNumberProtoFailure($tel, $ccode)
    {
        $this->assertNull(Utils::getNumberProto($tel, $ccode));
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::isValidPhoneNumber
     *
     * @dataProvider validNumberProviderSuccess
     */
    public function testIsValidPhoneNumber($tel, $ccode, $strict)
    {
        $this->assertTrue(Utils::isValidPhoneNumber($tel, $ccode, $strict));
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::getPhoneRegional
     *
     * @dataProvider numberProviderSuccess
     */
    public function testGetPhoneRcode($tel, $ccode)
    {
        $this->assertIsString(Utils::getPhoneRegional(Utils::getNumberProto($tel, $ccode)));
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::getPhoneCcode
     *
     * @dataProvider numberProviderSuccess
     */
    public function testGetPhoneCcode($tel, $ccode)
    {
        $this->assertIsInt(Utils::getPhoneCcode(Utils::getNumberProto($tel, $ccode)));
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::isCmMTN
     *
     * @dataProvider mtnProviderSuccess
     */
    public function testIsCmMTNSuccess($tel)
    {
        $this->assertTrue(Utils::isCmMTN($tel));
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::isCmMTN
     *
     * @dataProvider mtnProviderFailure
     */
    public function testIsCmMTNFailure($tel)
    {
        $this->assertFalse(Utils::isCmMTN($tel));
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::clearSender
     *
     * @dataProvider senderProvider
     */
    public function testClearSender($sender)
    {
        $this->assertIsString(Utils::clearSender($sender));
    }

    /** @covers \Camoo\Sms\Lib\Utils::normaliseKeys */
    public function testNormaliseKeys()
    {
        $rep = [
            'sms' => [
                'messages' => [
                    'message-id' => '1763763',
                ],
            ],
        ];
        $this->assertInstanceOf(stdClass::class, Utils::normaliseKeys($rep));
    }

    /** @covers \Camoo\Sms\Lib\Utils::randomStr */
    public function testRandomStr()
    {
        $this->assertIsString(Utils::randomStr());
        $this->assertNotNull(Utils::randomStr());
    }

    /** @covers \Camoo\Sms\Lib\Utils::decodeJson */
    public function testDecodeJson()
    {
        $this->assertIsArray(Utils::decodeJson('{"test":"ok"}', true));
        $this->assertInstanceOf(stdClass::class, Utils::decodeJson('{"test":"ok"}'));
    }

    /** @covers \Camoo\Sms\Lib\Utils::isMultiArray */
    public function testisMultiArraySuccess()
    {
        $rep = [
            'sms' => [
                'messages' => [
                    [
                        'message-id' => '1763763',
                    ],
                ],
            ],
        ];
        $this->assertTrue(Utils::isMultiArray($rep));
    }

    /** @covers \Camoo\Sms\Lib\Utils::mapMobile */
    public function testMapMobile()
    {
        $hTo1 = ['name' => 'John Doe', 'mobile' => '00237612345678'];
        $hTo2 = ['+237612345678', '+237612345679', '+237612345610', '+33689764530', '+4917612345671'];
        $this->assertMatchesRegularExpression('/^\+/', Utils::mapMobile($hTo1));
        $this->assertStringContainsString('237612345678', Utils::mapMobile($hTo1));
        $this->assertNull(Utils::mapMobile($hTo2));
        $this->assertEquals('+272982978', Utils::mapMobile('+272982978'));
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::makeNumberE164Format
     *
     * @dataProvider makeNumberE164FormatData
     */
    public function testMakeNumberE164Format($data)
    {
        $this->assertIsArray(Utils::makeNumberE164Format($data));
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::phoneNumberE164Format
     *
     * @dataProvider mtnProviderSuccess
     */
    public function testphoneNumberE164Format($tel)
    {
        $this->assertMatchesRegularExpression('/^\+/', Utils::phoneNumberE164Format($tel));
    }

    /** @covers \Camoo\Sms\Lib\Utils::phoneNumberE164Format */
    public function testPhoneNumberE164FormatNull()
    {
        $this->assertNull(Utils::phoneNumberE164Format(''));
    }

    /**
     * @covers       \Camoo\Sms\Lib\Utils::isMultiArray
     *
     * @dataProvider isMultiArrayProviderFailure
     */
    public function testisMultiArrayFailure($option)
    {
        $this->assertFalse(Utils::isMultiArray($option));
    }

    public function doBulkSmsProvider(): array
    {
        return [
            [
                [
                    'message' => 'foo Bar',
                    'to' => [['name' => 'John Doe', 'mobile' => '+237612345678'], ['name' => 'Jeanne Doe', 'mobile' => '+237612345679']],
                    'from' => 'YourCompany',
                ], 1,
            ],
            [
                [
                    'message' => 'foo Bar',
                    'to' => ['+237612345678', '+237612345679', '+237612345610', '+33689764530', '+4917612345671'],
                    'from' => 'YourCompany',
                ], 2,
            ],
        ];
    }

    public function backgroundProcessData(): array
    {
        return [
            [[]],
            [['path_to_php' => '/usr/local/bin/php4']],
        ];
    }

    public function isMultiArrayProviderFailure(): array
    {
        return [
            [[]],
            [['a', 'b']],
            [['a' => 'b']],
        ];
    }

    public function makeNumberE164FormatData(): array
    {
        return [
            [[['name' => 'John Doe', 'mobile' => '00237612345678'], ['name' => 'Jeanne Doe', 'mobile' => '+237612345679']]],
            [['+237612345678', '00237612345679', '237612345610', '33689764530', '004917612345671']],
            ['0033689764530'],
            ['0033689764530', 12354644],
            [new Recipient('0033689764532')],
        ];
    }

    public function senderProvider(): array
    {
        return [
            ['Your Company'],
            ['Camoo S.A.R.L'],
            ['00237667123456'],
            ['698123456'],
        ];
    }

    public function mtnProviderFailure(): array
    {
        return [
            [245123456],
            [886123456],
            [667123456],
            [698123456],
            [640123456],
            [641123456],
            [644123456],
        ];
    }

    public function mtnProviderSuccess(): array
    {
        return [
            [674512345],
            [674612345],
            [674712345],
            [674812345],
            [674912345],
            [675912345],
            [679123456],
        ];
    }

    public function numberProviderFailure(): array
    {
        return [
            ['0', 'CM'],
            ['', 'FR'],
            ['6', 'CM'],
            ['123', null],
        ];
    }

    public function validNumberProviderSuccess(): array
    {
        return [
            ['671234567', 'CM', true],
            ['671234567', 'FR', true],
            ['691234567', 'CM', false],
            ['661234567', 'CM', false],
        ];
    }

    public function numberProviderSuccess(): array
    {
        return [
            ['671234567', 'CM'],
            ['671234567', 'FR'],
            ['691234567', 'CM'],
            ['661234567', 'CM'],
        ];
    }

    public function stripAllTagsDataProvider(): array
    {
        return [
            ['fooo', 1],
            ['[]', true],
            ['<script>alert("Test")</script>', true],
            ['fooo', false],
            ['fooo', 0],
        ];
    }

    public function satanizerDataProviderFailure2(): array
    {
        return [
            [file_get_contents(dirname(__DIR__, 2) . '/Fixture/UTF-8-test.txt')],
        ];
    }
}
