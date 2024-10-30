<?php

namespace CAMOO_SMS;

class SystemInfo
{
    /** System info admin page */
    public function render_page()
    {
        include_once 'system-info.php';
        // Export log file
        if (isset($_POST['wpcamoosms_download_info']) && isset($_POST['camoo_sms_dl']) && wp_verify_nonce($_POST['camoo_sms_dl'], 'camoo_sms_dl')) {
            $style = '
				<style>
				#adminmenumain {display: none;}
				#wpadminbar {display: none;}
				#screen-meta-links {display: none;}
				</style>
			';
            echo $style;
            header('Content-type: application/text');
            header('Content-Disposition: attachment; filename=' . date('Y-d-m H:i:s') . '.html');
            header('Pragma: no-cache');
            header('Expires: 0');
            exit;
        }
    }

    /**
     * Get WordPress information
     *
     * @return array
     */
    public static function getWordpressInfo()
    {
        $information = [];

        // Check multisite
        $information['Multisite']['status'] = is_multisite() ? __('Enabled', 'wp-camoo-sms') : __('Disabled', 'wp-camoo-sms');
        $information['Multisite']['desc'] = 'Check WP multisite.';

        // Get version
        $information['Version']['status'] = get_bloginfo('version');

        // Get language
        $information['Language']['status'] = get_bloginfo('language');

        // Get active theme
        $information['Active Theme']['status'] = wp_get_theme();

        // Get ABSPATH
        $information['ABSPATH']['status'] = ABSPATH;

        // Get remote post status
        $remote = wp_remote_post('https://google.com');
        if (is_wp_error($remote)) {
            $information['Remote Post status']['status'] = $remote->get_error_message();
        } else {
            $information['Remote Post status']['status'] = 'OK!';
        }

        // Get WP_DEBUG
        $wp_debug = WP_DEBUG;
        if ($wp_debug) {
            $wp_debug = 'True';
        } else {
            $wp_debug = 'False';
        }
        $information['WP_DEBUG']['status'] = $wp_debug;

        // Get activated plugins
        $active_plugins = Option::getOptions('active_plugins');
        $all_plugins = get_plugins();
        $final = [];
        foreach ($active_plugins as $p) {
            if (isset($all_plugins[$p])) {
                $final[] = $all_plugins[$p]['Name'];
            }
        }

        $information['Active Plugins']['status'] = implode('<br>', $final);

        return $information;
    }

    /** Get PHP information */
    public static function getPHPInfo()
    {
        $information = [];

        // Get PHP version
        $information['Version']['status'] = phpversion();

        // Check shell_exec enabled or not
        $information['shell_exec']['status'] = function_exists('shell_exec') ? __('Enabled', 'wp-camoo-sms') : __('Disabled', 'wp-camoo-sms');

        return $information;
    }
}
