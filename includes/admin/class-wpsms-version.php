<?php

namespace CAMOO_SMS;

use CAMOO_SMS\Admin\Helper;
use CAMOO_SMS\Config\Bootstrap;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * WP SMS version class
 *
 * @category   class
 */
class Version
{
    public function __construct()
    {
        add_action('wp_camoo_sms_pro_after_setting_logo', [$this, 'pro_setting_title']);
        add_filter('plugin_row_meta', [$this, 'pro_meta_links'], 10, 2);
    }

    public function pro_meta_links($links, $file): array
    {
        if (strpos($file, 'camoo-sms.php') !== false) {
            $links[] = sprintf(
                __(
                    '<b><a href="%s" target="_blank" class="wpsms-plugin-meta-link wp-camoo-sms-pro" title="Join Camoo now">Join Camoo Now!</a></b>',
                    'wp-camoo-sms'
                ),
                WP_CAMOO_SMS_SITE . '/join'
            );
        }

        return $links;
    }

    /** @internal param $string */
    public function pro_setting_title(): void
    {
        echo ' CAMOO SMS';
    }

    /** Version notice */
    public function version_notice()
    {
        Helper::notice(
            sprintf(__(
                'The <a href="%s" target="_blank">CAMOO SMS</a> is out of date and not compatible with new version of CAMOO-SMS, Please update the plugin to the <a href="%s" target="_blank">latest version</a>.',
                Bootstrap::DOMAIN_TEXT
            ), WP_CAMOO_SMS_SITE, 'https://github.com/camoo/wp-camoo-sms/releases'),
            'error'
        );
    }
}

(new Version());
