<?php

declare(strict_types=1);

namespace Camoo\Sms;

use Camoo\Sms\Lib\Utils;
use LogicException;

use const PHP_VERSION_ID;

/**
 * Class Constants
 */
class Constants
{
    final public const CLIENT_VERSION = '4.0.0';

    public const CLIENT_TIMEOUT = 30; // 30 sec

    public const MIN_PHP_VERSION = 80100;

    public const DS = '/';

    final public const END_POINT_URL = 'https://api.camoo.cm';

    public const END_POINT_VERSION = 'v1';

    final public const RESOURCE_VIEW = 'view';

    final public const RESOURCE_BALANCE = 'balance';

    final public const RESOURCE_TOP_UP = 'topup';

    final public const JSON_RESPONSE_FORMAT = 'json';

    final public const SMS_MAX_RECIPIENTS = 50;

    final public const CLEAR_OBJECT = [Base::class, 'clear'];

    final public const MAP_MOBILE = [Utils::class, 'mapMobile'];

    final public const PERSONALIZE_MSG_KEYS = ['%NAME%'];

    final public const CREDENTIAL_ELEMENTS = ['api_key', 'api_secret'];

    final public static function getPhpVersion(): string
    {
        if (!defined('PHP_VERSION_ID')) {
            $version = explode('.', PHP_VERSION); //@codeCoverageIgnore
            // @codeCoverageIgnoreStart
            define(
                'PHP_VERSION_ID',
                (int)$version[0] * 10000 + (int)$version[1] * 100 + (int)$version[2]
            ); //@codeCoverageIgnoreEnd
        }

        if (PHP_VERSION_ID < static::MIN_PHP_VERSION) {
            throw new LogicException(
                'Your PHP-Version belongs to a release that is no longer supported.' .
                'You should upgrade your PHP version as soon as possible,' .
                ' as it may be exposed to un-patched security vulnerabilities',
                E_USER_ERROR
            );
        }

        return 'PHP/' . PHP_VERSION_ID;
    }

    final public static function getSMSPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR;
    }
}
