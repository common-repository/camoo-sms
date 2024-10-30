<?php
/**
 * @var array $cf7_options
 * @var array $cf7_options_field
 */

use CAMOO_SMS\Config\Bootstrap;

?>
<div id="wpcf7-camoo-sms" class="contact-form-editor-wpsms">
    <h3><?php _e('Send to number', 'wp-camoo-sms'); ?></h3>
    <fieldset>
        <legend><?php _e('After submit form you can send a sms message to number', 'wp-camoo-sms'); ?><br></legend>
        <table class="form-table">
            <caption><?php  _e('Send "To" Configuration', Bootstrap::DOMAIN_TEXT)?></caption>
            <tbody>
            <tr>
                <th scope="row"><label for="wpcf7-sms-sender"><?php _e('Send to', 'wp-camoo-sms'); ?>:</label></th>
                <td>
                    <input type="text" value="<?php echo $cf7_options['phone']; ?>" size="70" class="large-text code"
                           name="wpcf7-sms[phone]" id="wpcf7-sms-sender">
                    <p class="description"><?php _e('<b>Note:</b> To send more than one number, separate the numbers with a comma. (e.g. 237673123123,237691123456)', 'wp-camoo-sms'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="wpcf7-sms-message"><?php _e('Message body', 'wp-camoo-sms'); ?>:</label></th>
                <td>
                    <textarea class="large-text" rows="4" cols="100" name="wpcf7-sms[message]"
                              id="wpcf7-sms-message"><?php echo $cf7_options['message']; ?></textarea>
                    <p class="description"><?php _e('<b>Note:</b> Use %% Instead of [], for example: %your-name%', 'wp-camoo-sms'); ?></p>
                </td>
            </tr>
            </tbody>
        </table>

        <h3><?php _e('Send to form', 'wp-camoo-sms'); ?></h3>
        <legend><?php _e('After submit form you can send a sms message to field', 'wp-camoo-sms'); ?><br></legend>
        <table class="form-table">
            <caption><?php  _e('Map "Fields" Configuration', Bootstrap::DOMAIN_TEXT)?></caption>
                <tbody>

                <tr>
                <th scope="row"><label for="wpcf7-sms-sender-form"><?php _e('Send to field', 'wp-camoo-sms'); ?>:</label>
                </th>
                <td>
                    <input type="text" value="<?php echo $cf7_options_field['phone']; ?>" size="70"
                           class="large-text code" name="wpcf7-sms-form[phone]" id="wpcf7-sms-sender-form">
                    <p class="description"><?php _e('<b>Note:</b> Use %% Instead of [], for example: %your-mobile%', 'wp-camoo-sms'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="wpcf7-sms-message-form"><?php _e('Message body', 'wp-camoo-sms'); ?>:</label>
                </th>
                <td>
                    <textarea class="large-text" rows="4" cols="100" name="wpcf7-sms-form[message]"
                              id="wpcf7-sms-message-form"><?php echo $cf7_options_field['message']; ?></textarea>
                    <p class="description"><?php _e('<b>Note:</b> Use %% Instead of [], for example: %your-name%', 'wp-camoo-sms'); ?></p>
                </td>
            </tr>
            </tbody>
        </table>
    </fieldset>
</div>
