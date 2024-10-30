<?php

namespace CAMOO_SMS;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly
use CAMOO_SMS\Admin\Helper;

class Option
{
    public const MAIN_SETTING_KEY = 'wp_camoo_sms_settings';

    /**
     * Get the whole Plugin Options
     *
     * @param string|null $settingName setting name
     */
    public static function getOptions(?string $settingName = null): mixed
    {
        if (null === $settingName) {
            $settingName = static::MAIN_SETTING_KEY;
        }
        // hook after find option
        add_filter('option_' . static::MAIN_SETTING_KEY, [Option::class, 'afterFind']);

        return get_option($settingName);
    }

    /** Get the only Option that we want */
    public static function getOption(mixed $option, ?string $settingName = null): mixed
    {
        if (null === $settingName) {
            $camooSmsOptions = self::getOptions();

            return $camooSmsOptions[$option] ?? '';
        }
        $options = self::getOptions($settingName);

        return $options[$option] ?? '';
    }

    /** Add an option */
    public static function addOption(string $option, mixed $value): void
    {
        add_option($option, $value);
    }

    /** Update Option */
    public static function updateOption(string $key, mixed $value): void
    {
        $options = self::getOptions();
        $options[$key] = $value;

        update_option(static::MAIN_SETTING_KEY, $options);
    }

    public static function afterFind(mixed $xData): mixed
    {
        if (is_array($xData)) {
            if (!empty($xData['gateway_username'])) {
                $xData['gateway_username'] = Helper::decrypt($xData['gateway_username']);
            }
            if (!empty($xData['gateway_password'])) {
                $xData['gateway_password'] = Helper::decrypt($xData['gateway_password']);
            }
        }

        return $xData;
    }

    public static function beforeSave(array $option = []): array
    {
        // ENCRYPT API SECRET KEY
        if (!empty($option['gateway_password'])) {
            $option['gateway_password'] = Helper::encrypt($option['gateway_password']);
        }
        if (!empty($option['gateway_username'])) {
            $option['gateway_username'] = Helper::encrypt($option['gateway_username']);
        }

        return $option;
    }
}
