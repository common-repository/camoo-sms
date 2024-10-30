<?php if (!isset($instance['description'])) { ?>
    <h2 class="widget-title">Subscribe SMS</h2>
<?php } ?>
<div id="wpsms-subscribe">
    <div id="wpsms-result"></div>
    <div id="wpsms-step-1">
        <?php if (isset($instance['description'])) { ?>
            <p><?php echo isset($instance['description']) ? $instance['description'] : ''; ?></p>
        <?php } ?>
        <div class="wpsms-subscribe-form">
            <label><?php _e('Your name', 'wp-camoo-sms'); ?>:</label>
            <input id="wpsms-name" type="text" placeholder="<?php _e('Your name', 'wp-camoo-sms'); ?>" class="wpsms-input"/>
        </div>
        <?php
        if (wp_camoo_sms_get_option('international_mobile')) {
            $wp_camoo_sms_input_mobile = ' wp-camoo-sms-input-mobile';
        } else {
            $wp_camoo_sms_input_mobile = '';
        }
?>
        <div class="wpsms-subscribe-form">
            <label><?php _e('Your mobile', 'wp-camoo-sms'); ?>:</label>
            <input id="wpsms-mobile" type="text" placeholder="<?php echo wp_camoo_sms_get_option('mobile_terms_field_place_holder'); ?>" class="wpsms-input<?php echo $wp_camoo_sms_input_mobile ?>"/>
        </div>

        <?php if (wp_camoo_sms_get_option('newsletter_form_groups')) { ?>
            <div class="wpsms-subscribe-form">
                <label><?php _e('Group', 'wp-camoo-sms'); ?>:</label>
                <select id="wpsms-groups" class="wpsms-input">
                    <?php foreach ($get_group_result as $items) { ?>
                        <option value="<?php echo $items->ID; ?>"><?php echo $items->name; ?></option>
                    <?php } ?>
                </select>
            </div>
        <?php } ?>

        <div class="wpsms-subscribe-form">
            <label>
                <input type="radio" name="subscribe_type" id="wpsms-type-subscribe" value="subscribe" checked="checked"/>
                <?php _e('Subscribe', 'wp-camoo-sms'); ?>
            </label>

            <label>
                <input type="radio" name="subscribe_type" id="wpsms-type-unsubscribe" value="unsubscribe"/>
                <?php _e('Unsubscribe', 'wp-camoo-sms'); ?>
            </label>
        </div>
        <?php if (wp_camoo_sms_get_option('gdpr_compliance') == 1) { ?>
            <div class="wpsms-subscribe-form">
                <label><input id="wpsms-gdpr-confirmation" type="checkbox" <?php echo wp_camoo_sms_get_option('newsletter_form_gdpr_confirm_checkbox') == 'checked' ? 'checked="checked"' : ''; ?>>
                    <?php echo wp_camoo_sms_get_option('newsletter_form_gdpr_text') ? esc_html(trim(wp_camoo_sms_get_option('newsletter_form_gdpr_text'))) : 'GDPR text...'; ?>
                </label>
            </div>
        <?php } ?>

        <button class="wpsms-button" id="wpsms-submit"><?php _e('Subscribe', 'wp-camoo-sms'); ?></button>
    </div>
    <?php $disable_style = wp_camoo_sms_get_option('disable_style_in_front');
if (empty($disable_style) and !$disable_style) { ?>
    <div id="wpsms-step-2">
    <?php } else { ?>
        <div id="wpsms-step-2" style="display: none;">
    <?php } ?>

            <div class="wpsms-subscribe-form">
                <label><?php _e('Activation code:', 'wp-camoo-sms'); ?></label>
                <input type="text" id="wpsms-ativation-code" placeholder="<?php _e('Activation code:', 'wp-camoo-sms'); ?>" class="wpsms-input"/>
            </div>
            <button class="wpsms-button" id="activation"><?php _e('Activation', 'wp-camoo-sms'); ?></button>
        </div>
        <input type="hidden" id="wpsms-widget-id" value="<?php echo $widget_id; ?>">
        <input type="hidden" id="newsletter-form-verify" value="<?php echo wp_camoo_sms_get_option('newsletter_form_verify'); ?>">
    </div>
