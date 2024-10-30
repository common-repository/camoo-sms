<?php

namespace CAMOO_SMS\Admin;

class Helper
{
    /**
     * Show Admin WordPress Ui Notice
     *
     * @param string $text         where Show Text Notification
     * @param string $model        Type Of Model from list : error / warning / success / info
     * @param bool   $close_button Check Show close Button Or false for not
     * @param bool   $echo         Check Echo or return in function
     * @param string $style_extra  add extra Css Style To Code
     *
     * @return string WordPress html Notice code
     *
     * @author Mehrshad Darzi
     */
    public static function notice(
        string $text,
        string $model = 'info',
        bool $close_button = true,
        bool $echo = true,
        string $style_extra = 'padding:12px;'
    ): ?string {
        $text = '
        <div class="notice notice-' . $model . '' . ($close_button === true ? ' is-dismissible' : '') . '">
           <div style="' . $style_extra . '">' . $text . '</div>
        </div>
        ';

        if ($echo) {
            echo $text;

            return null;
        }

        return $text;
    }

    public static function adminUrl($args, $adminFile = 'admin.php'): string
    {
        return add_query_arg($args, WP_CAMOO_SMS_ADMIN_URL . $adminFile);
    }

    public static function getPhpVersion()
    {
        if (!defined('CAMOO_SMS_PHP_ID')) {
            $version = explode('.', PHP_VERSION);
            define('CAMOO_SMS_PHP_ID', (int)$version[0] * 10000 + (int)$version[1] * 100 + (int)$version[2]);
        }

        return CAMOO_SMS_PHP_ID;
    }

    public static function encrypt($string, $sCipher = 'AES-256-CBC')
    {
        if (empty($string)) {
            return '';
        }
        if (!defined('NONCE_SALT')) {
            return $string;
        }
        $key = hash('sha256', NONCE_SALT);
        $cipherLength = openssl_cipher_iv_length($sCipher);
        $iv = openssl_random_pseudo_bytes($cipherLength);
        $ciphertext_raw = openssl_encrypt($string, $sCipher, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);

        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    public static function decrypt($string, $sCipher = 'AES-256-CBC')
    {
        if (empty($string) || !self::isBase64Encoded($string)) {
            return '';
        }
        if (!defined('NONCE_SALT')) {
            return self::isBase64Encoded($string) ? null : $string;
        }
        $enc = base64_decode($string);
        $key = hash('sha256', NONCE_SALT);
        $cipherLength = openssl_cipher_iv_length($sCipher);
        $iv = substr($enc, 0, $cipherLength);
        $sha2len = 32;
        $ciphertext_raw = substr($enc, $cipherLength + $sha2len);

        return openssl_decrypt($ciphertext_raw, $sCipher, $key, OPENSSL_RAW_DATA, $iv);
    }

    public static function isBase64Encoded($string): bool
    {
        return base64_encode(base64_decode($string)) === $string;
    }

    public static function sanitizer($value, $type = null)
    {
        $hMapTypes = [
            'number' => function ($value) {
                return (int)sanitize_key($value);
            },
            'text' => function ($value) {
                return sanitize_text_field($value);
            },
            'email' => function ($value) {
                return sanitize_email($value);
            },
            'textarea' => function ($value) {
                return sanitize_textarea_field($value);
            },
            'multiselect' => function ($value) {
                $output = [];
                if (empty($value)) {
                    return $output;
                }
                foreach ($value as $item) {
                    $output[] = sanitize_textarea_field($item);
                }

                return $output;
            },
        ];

        if (null === $type || !array_key_exists($type, $hMapTypes)) {
            return sanitize_text_field($value);
        }

        return call_user_func($hMapTypes[$type], $value);
    }

    /**
     * satanise string
     */
    public static function sataniseRequest($filter, array $input = []): array
    {
        if (has_filter($filter, [Helper::class, 'sanitizer'])) {
            foreach ($input as $key => $value) {
                if (is_array($value)) {
                    $input[$key] = self::sataniseRequest($filter, $value);
                    continue;
                }
                $input[$key] = apply_filters($filter, $value);
            }
        }

        return $input;
    }
}
