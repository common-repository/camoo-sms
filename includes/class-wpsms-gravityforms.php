<?php

namespace CAMOO_SMS;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Gravityforms
{
    public static function get_field($form_id): array
    {
        $option_field = [];

        if (!$form_id) {
            return $option_field;
        }

        if (!class_exists('RGFormsModel')) {
            return $option_field;
        }

        $fields = \RGFormsModel::get_form_meta($form_id);

        if ($fields) {
            foreach ($fields['fields'] as $field) {
                if (isset($field['label'])) {
                    $option_field[$field['id']] = $field['label'];
                } elseif (isset($field->label)) {
                    $option_field[$field->id] = $field->label;
                }
            }
        }

        return $option_field;
    }
}
