<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery(".wpsms-value").hide();
        jQuery(".wpsms-group").show();

        jQuery("select#select_sender").change(function () {
            var get_method = "";
            jQuery("select#select_sender option:selected").each(
                function () {
                    get_method += jQuery(this).attr('id');
                }
            );
            if (get_method == 'wp_subscribe_username') {
                jQuery(".wpsms-value").hide();
                jQuery(".wpsms-group").fadeIn();
            } else if (get_method == 'wp_users') {
                jQuery(".wpsms-value").hide();
                jQuery(".wpsms-users").fadeIn();
            } else if (get_method == 'wp_tellephone') {
                jQuery(".wpsms-value").hide();
                jQuery(".wpsms-numbers").fadeIn();
                jQuery("#wp_get_number").focus();
            } else if (get_method == 'wp_role') {
                jQuery(".wpsms-value").hide();
                jQuery(".wprole-group").fadeIn();
            }
        });

        jQuery("#wp_get_message").counter({
            count: 'up',
            goal: 'sky',
            msg: '<?php
            use CAMOO_SMS\SMS_Send;

            _e('characters', 'wp-camoo-sms'); ?>'
        })
    });
</script>
<?php
/**
 * @var SMS_Send $this
 */
$options = \CAMOO_SMS\Option::getOptions('wp_camoo_sms_settings');
            $gateway_name = $options['gateway_name'];
            $posCamoo = strpos($gateway_name, 'camoo');
            $isCamoo = $posCamoo !== false;
            $currency = $isCamoo ? 'XAF' : 'Units';
            ?>

<div class="wrap">
    <h2><?php _e('Send SMS', 'wp-camoo-sms'); ?> via <?php echo strtoupper($gateway_name); ?></h2>
    <div class="postbox-container" style="padding-top: 20px;">
        <div class="meta-box-sortables">
            <div class="postbox">
                <h2 class="hndle" style="cursor: default;padding: 0 10px 10px 10px;font-size: 13px;">
                    <span><?php _e('Send SMS form', 'wp-camoo-sms'); ?></span></h2>

                <div class="inside">
                    <form method="post" action="">
                        <?php wp_nonce_field('camoo_sms_send', 'camoo_sms_send'); ?>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <label for="wp_get_sender"><?php _e('Send from', 'wp-camoo-sms'); ?>:</label>
                                </th>
                                <td>
                                    <input type="text" name="wp_get_sender" id="wp_get_sender"
                                           value="<?php echo $this->sms->from; ?>" maxlength="18"/>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <label for="select_sender"><?php _e('Send to', 'wp-camoo-sms'); ?>:</label>
                                </th>
                                <td>
                                    <select name="wp_send_to" id="select_sender">
                                        <option value="wp_subscribe_username"
                                                id="wp_subscribe_username"><?php _e('Subscribe users', 'wp-camoo-sms'); ?></option>
                                        <option value="wp_users"
                                                id="wp_users"><?php _e('Wordpress Users', 'wp-camoo-sms'); ?></option>
                                        <option value="wp_role"
                                                id="wp_role"<?php $mobile_field = \CAMOO_SMS\Option::getOption('add_mobile_field');
            if (empty($mobile_field) || $mobile_field != 1) {
                echo 'disabled title="' . __('To enable this item, you should enable the Mobile number field in the Settings > Features', 'wp-camoo-sms') . '"';
            } ?>><?php _e('Role', 'wp-camoo-sms'); ?></option>
                                        <option value="wp_tellephone"
                                                id="wp_tellephone"><?php _e('Number(s)', 'wp-camoo-sms'); ?></option>
                                    </select>

                                    <?php if (!empty($mobile_field) or $mobile_field == 1) { ?>
                                        <select name="wpcamoosms_group_role" class="wpsms-value wprole-group">
                                            <?php
                foreach ($wpcamoosms_list_of_role as $key_item => $val_item) {
                    ?>
                                                <option value="<?php echo $key_item; ?>"<?php if ($val_item['count'] < 1) {
                                                    echo ' disabled';
                                                } ?>><?php _e($val_item['name'], 'wp-camoo-sms'); ?>
                                                    (<?php echo sprintf(__('<b>%s</b> Users have mobile number.', 'wp-camoo-sms'), $val_item['count']); ?>
                                                    )
                                                </option>
                                            <?php } ?>
                                        </select>
                                    <?php } ?>

                                    <select name="wpcamoosms_group_name" class="wpsms-value wpsms-group">
                                        <option value="all">
                                            <?php
                                            global $wpdb;
            $username_active = $wpdb->query("SELECT * FROM {$wpdb->prefix}camoo_sms_subscribes WHERE status = '1'");
            echo sprintf(__('All (%s subscribers active)', 'wp-camoo-sms'), $username_active);
            ?>
                                        </option>
                                        <?php foreach ($get_group_result as $items) { ?>
                                            <option value="<?php echo $items->ID; ?>"><?php echo $items->name; ?></option>
                                        <?php } ?>
                                    </select>

                                    <span class="wpsms-value wpsms-users">
                        <span><?php echo sprintf(__('<b>%s</b> Users have mobile number.', 'wp-camoo-sms'), count($get_users_mobile)); ?></span>
                    </span>
                                    <span class="wpsms-value wpsms-numbers">
                                        <div class="clearfix"></div>
                                        <textarea cols="80" rows="5" style="direction:ltr;margin-top 5px;"
                                                  id="wp_get_number" name="wp_get_number"></textarea>
                                        <div class="clearfix"></div>
                                        <span style="font-size: 14px"><?php echo sprintf(__('For example: <code>%s</code>. International numbers should begin with <code>\'+\'</code>', 'wp-camoo-sms'), $this->sms->validateNumber); ?></span>
                                    </span>
                                </td>
                            </tr>

                            <?php if (!$this->sms->canSendBulk) { ?>
                                <tr>
                                    <td></td>
                                    <td><?php _e('This gateway does not support sending bulk message and used first number to sending sms.', 'wp-camoo-sms'); ?></td>
                                </tr>
                            <?php } ?>

                            <tr valign="top">
                                <th scope="row">
                                    <label for="wp_get_message"><?php _e('Message', 'wp-camoo-sms'); ?>:</label>
                                </th>
                                <td>
                                    <textarea dir="auto" cols="80" rows="5" name="wp_get_message"
                                              id="wp_get_message"></textarea><br/>
                                    <p class="number">
                                        <?php echo __('Your account credit', 'wp-camoo-sms') . ': ' . sprintf('%s %s', $currency, $credit); ?>
                                    </p>
                                </td>
                            </tr>
                            <?php
                            if ($isCamoo === true) {
                                if ($this->sms->flash === 'enable') { ?>
                                    <tr>
                                        <td><?php _e('Send a Flash', 'wp-camoo-sms'); ?>:</td>
                                        <td>
                                            <input type="radio" id="flash_yes" name="wp_flash" value="true"/>
                                            <label for="flash_yes"><?php _e('Yes', 'wp-camoo-sms'); ?></label>
                                            <input type="radio" id="flash_no" name="wp_flash" value="false"
                                                   checked="checked"/>
                                            <label for="flash_no"><?php _e('No', 'wp-camoo-sms'); ?></label>
                                            <br/>
                                            <p class="description"><?php _e('Flash is possible to send messages without being asked, opens', 'wp-camoo-sms'); ?></p>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                <tr>
                                    <td><?php _e('SMS route', 'wp-camoo-sms'); ?>:</td>
                                    <td>
                                        <input type="radio" id="route_premium" name="wp_route" value="premium"
                                               checked="checked"/>
                                        <label for="flash_yes"><?php _e('Premium', 'wp-camoo-sms'); ?></label>
                                        <input type="radio" id="route_classic" name="wp_route" value="classic"/>
                                        <label for="flash_no"><?php _e('Classic', 'wp-camoo-sms'); ?></label>
                                        <br/>
                                        <p class="description"><?php echo sprintf(__("The SMS route that is used to send the message. <span style='color:#ff0000;'>The classic route works only for cameroonian mobile phone numbers.</span> <a href='%s' target='_blank'>Check Wiki for more explanation</a>", 'wp-camoo-sms'), 'https://github.com/camoo/sms/wiki/Send-a-message#optional-parameters'); ?></p>
                                    </td>
                                </tr>
                                <?php
                            }
            ?>
                            <tr>
                                <td>
                                    <p class="submit" style="padding: 0;">
                                        <input type="submit" class="button-primary" name="sendSMS"
                                               value="<?php _e('Send SMS', 'wp-camoo-sms'); ?>"/>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
