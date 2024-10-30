<?php

declare(strict_types=1);

namespace Camoo\Sms\Lib;

use Camoo\Sms\Constants;
use Camoo\Sms\Entity\Recipient;
use Exception;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\PhoneNumberUtil;
use stdClass;

class Utils
{
    public static function phoneUtil(): PhoneNumberUtil
    {
        return PhoneNumberUtil::getInstance();
    }

    public static function getNumberProto(string $xTel, ?string $sCcode = null): ?PhoneNumber
    {
        if (empty($xTel)) {
            return null;
        }

        try {
            $instance = self::phoneUtil()->parse($xTel, $sCcode);
        } catch (NumberParseException) {
            $instance = null;
        }

        return $instance;
    }

    public static function isValidPhoneNumber(Recipient|array|string $xTel, string $sCcode, ?bool $bStrict = null): bool
    {
        $phoneNumber = $xTel instanceof Recipient ? $xTel->phoneNumber : $xTel;

        $to = is_array($phoneNumber) && !empty($phoneNumber['mobile']) ? $phoneNumber['mobile'] : $phoneNumber;
        $bRet = ($oNumberProto = self::getNumberProto($to, $sCcode)) &&
            self::phoneUtil()->isValidNumber($oNumberProto) &&
            !empty(self::phoneUtil()->getNumberType($oNumberProto));
        if ($bRet && $bStrict === true) {
            return self::getPhoneRegional($oNumberProto) === strtoupper($sCcode);
        }

        return $bRet;
    }

    public static function getPhoneRegional(PhoneNumber $oNumberProto): ?string
    {
        return self::phoneUtil()->getRegionCodeForNumber($oNumberProto);
    }

    public static function getPhoneCcode(PhoneNumber $oNumberProto): ?int
    {
        return $oNumberProto->getCountryCode();
    }

    public static function isCmMTN(string $xTel): bool
    {
        return self::getPhoneCarrier($xTel) === 'MTN';
    }

    public static function getPhoneCarrier(string $xTel, string $sCcode = 'CM'): ?string
    {
        if (null !== ($oNumberProto = self::getNumberProto($xTel, $sCcode))) {
            $oCarrierMapper = PhoneNumberToCarrierMapper::getInstance();
            $sCarrier = $oCarrierMapper->getNameForNumber($oNumberProto, 'en');
            if (!empty($sCarrier)) {
                $asCarrier = explode(' ', $sCarrier);

                return strtoupper($asCarrier[0]);
            }
        }

        return null;
    }

    /**
     * Make clear sender
     *
     * If the originator ('from' field) is invalid, some networks may reject the network
     * whilst stinging you with the financial cost! While this cannot correct them, it
     * will try its best to correctly format them.
     */
    public static function clearSender(string $inp): string
    {
        $ret = preg_replace('/[^a-zA-Z0-9]/', '', $inp);

        if (preg_match('/[a-zA-Z]/', $inp)) {
            // Alphanumeric format so make sure it's < 11 chars
            $ret = substr($ret, 0, 11);
        } else {
            // Numerical, remove any prepending '00'
            if (str_starts_with($ret, '00')) {
                $ret = ltrim($ret, '0');
                $ret = substr($ret, 0, 15);
            }
        }

        return (string)$ret;
    }

    /**
     * @param stdClass|array|null $response
     * @param string|int|null     $property
     */
    public static function normaliseKeys(mixed $response, bool $associative = false, mixed $property = null): stdClass|array
    {
        $result = $associative ? [] : new stdClass();

        if (null === $response) {
            return $result;
        }

        foreach ((array)$response as $index => $value) {
            if ($value instanceof stdClass || is_array($value)) {
                $value = self::normaliseKeys($value, $associative, $index);
            }
            if (is_int($index) && is_string($property)) {
                $result instanceof stdClass ?
                    $result->{$property}[$index] = $value : $result[$property][$index] = $value;
                continue;
            }
            $name = str_replace('-', '_', (string)$index);
            $result instanceof stdClass ? $result->{$name} = $value : $result[$name] = $value;
        }

        return $result;
    }

    /** @throws Exception */
    public static function randomStr(): string
    {
        $bytes = random_bytes(5);

        return bin2hex($bytes);
    }

    public static function decodeJson(string $sJSON, bool $associative = false): stdClass|array|null
    {
        $xData = json_decode($sJSON, $associative);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        if (empty($xData)) {
            return null;
        }

        return $xData;
    }

    public static function isMultiArray(array $option): bool
    {
        rsort($option);

        return isset($option[0]) && is_array($option[0]);
    }

    public static function mapMobile(mixed $xValue): ?string
    {
        if (is_string($xValue)) {
            return $xValue;
        }
        if (is_array($xValue) && !empty($xValue['mobile'])) {
            return self::phoneNumberE164Format($xValue['mobile']);
        }

        return null;
    }

    public static function makeNumberE164Format(mixed $xValue): array
    {
        if (is_string($xValue)) {
            return [self::phoneNumberE164Format($xValue)];
        }

        if ($xValue instanceof Recipient) {
            $xValue = ['mobile' => self::phoneNumberE164Format($xValue->phoneNumber), 'name' => $xValue->name];
        }

        if (is_array($xValue) || is_iterable($xValue)) {
            $xValue = self::extractNumber($xValue);
        }

        return array_filter($xValue);
    }

    public static function satanizer(string $filtered, bool $keepNewlines = false): array|string
    {
        if (!mb_check_encoding($filtered, 'UTF-8')) {
            return '';
        }
        if (str_contains($filtered, '<')) {
            $callback = function (array $match) {
                if (!str_contains($match[0], '>')) {
                    return htmlentities($match[0], ENT_QUOTES | ENT_IGNORE, 'UTF-8');
                }

                return $match[0];
            };
            $filtered = preg_replace_callback('%<[^>]*?((?=<)|>|$)%', $callback, $filtered);
            $filtered = self::stripAllTags($filtered, false);
            $filtered = str_replace("<\n", "&lt;\n", $filtered);
        }
        if (!$keepNewlines) {
            $filtered = preg_replace('/[\r\n\t ]+/', ' ', $filtered);
        }
        $filtered = trim($filtered);
        $found = false;
        while (preg_match('/%[a-f0-9]{2}/i', $filtered, $match)) {
            $filtered = str_replace($match[0], '', $filtered);
            $found = true;
        }
        if ($found) {
            $filtered = trim(preg_replace('/ +/', ' ', $filtered));
        }

        return $filtered;
    }

    public static function stripAllTags(string $string, bool $removeBreaks = false): string
    {
        $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
        $string = strip_tags($string);
        if ($removeBreaks) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }

        return trim($string);
    }

    public static function phoneNumberE164Format(Recipient|array|string $xTel): ?string
    {
        $phoneNumber = $xTel instanceof Recipient ? $xTel->phoneNumber : $xTel;
        if (is_array($phoneNumber)) {
            $phoneNumber = $phoneNumber['mobile'];
        }
        if ($sTel = preg_replace('/[^\dxX]/', '', $phoneNumber)) {
            return '+' . ltrim($sTel, '0');
        }

        return null;
    }

    public static function formatRecipient(mixed $recipient): string
    {
        return is_array($recipient) ? implode(
            ',',
            array_map(Constants::MAP_MOBILE, $recipient)
        ) : $recipient;
    }

    public static function getRecipientName(mixed $number): ?string
    {
        if (!is_array($number) && !$number instanceof Recipient) {
            return null;
        }

        if (is_array($number) && empty($number['name'])) {
            return null;
        }

        return $number instanceof Recipient ? $number->name : $number['name'];
    }

    private static function extractNumber(mixed $phoneNumbers): array
    {
        $numbers = [];
        foreach ($phoneNumbers as $number) {
            if (!is_string($number) && !$number instanceof Recipient && !is_iterable($number)) {
                continue;
            }
            $phoneNumber = self::phoneNumberE164Format($number);
            $phoneNumberName = self::getRecipientName($number);
            $numbers[] = is_string($number) || empty($phoneNumberName) ? $phoneNumber :
                ['mobile' => $phoneNumber, 'name' => $phoneNumberName];
        }

        return $numbers;
    }
}
