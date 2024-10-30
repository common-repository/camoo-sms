<?php

namespace CAMOO_SMS;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Shortcode
{
    public function __construct()
    {
        // Add the shortcode [wp-camoo-sms-subscriber-form]
        add_shortcode('wp-camoo-sms-subscriber-form', [$this, 'register_shortcode']);
    }

    /**
     * Shortcode plugin
     *
     * @internal param param $Not
     */
    public function register_shortcode($atts)
    {
        Newsletter::loadNewsLetter();
    }
}

(new Shortcode());
