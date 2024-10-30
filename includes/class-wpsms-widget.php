<?php

namespace CAMOO_SMS;

use WP_Widget;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Widget extends WP_Widget
{
    /** Register widget with WordPress. */
    public function __construct()
    {
        $widget_options = [
            'classname' => 'wpcamoosms_widget',
            'description' => __('SMS newsletter form', 'wp-camoo-sms'),
        ];

        // Add Actions
        add_action('widgets_init', [$this, 'register_widget']);

        parent::__construct('wpcamoosms_widget', __('SMS Newsletter', 'wp-camoo-sms'), $widget_options);
    }

    /**
     * Front-end display of widget.
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     *
     * @see WP_Widget::widget()
     */
    public function widget($args, $instance)
    {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        $widget_id = $this->get_numerics($args['widget_id']);

        Newsletter::loadNewsLetter($widget_id, $instance);

        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @param array $instance Previously saved values from database.
     *
     * @return void
     *
     * @see WP_Widget::form()
     */
    public function form($instance)
    {
        $title = !empty($instance['title']) ? $instance['title'] : __('Subscribe SMS', 'wp-camoo-sms');
        $description = !empty($instance['description']) ? $instance['description'] : '';

        // Load template
        include WP_CAMOO_SMS_DIR . 'includes/templates/widget.php';
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     *
     * @see WP_Widget::update()
     */
    public function update($new_instance, $old_instance)
    {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['description'] = (!empty($new_instance['description'])) ? $new_instance['description'] : '';

        return $instance;
    }

    public function get_numerics($str)
    {
        preg_match('/\d+/', $str, $matches);

        return $matches[0];
    }

    /** Register widget */
    public function register_widget()
    {
        register_widget('\CAMOO_SMS\Widget');
    }
}

(new Widget());
