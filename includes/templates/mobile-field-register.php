<?php
/**
 * @var string $mobile
 */
if (wp_camoo_sms_get_option('international_mobile')) {
    $wp_camoo_sms_input_mobile = ' wp-camoo-sms-input-mobile';
} else {
    $wp_camoo_sms_input_mobile = '';
}
?>
<p>
    <label for="mobile"><?php _e('Your Mobile Number', 'wp-camoo-sms') ?><br/>
        <input type="text" name="mobile" id="mobile" class="input<?php echo $wp_camoo_sms_input_mobile ?>"
               value="<?php echo esc_attr(stripslashes($mobile)); ?>" size="25"/></label>
</p>
