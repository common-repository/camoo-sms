<style>
    #topup-caption{
        border-style:solid !important;
        border-width: 2px !important;
        border-color:#55CDFC !important;
        border-radius:5px !important;
    }
</style>

<div class="wrap">
    <h2><?php _e('Top up your account', 'wp-camoo-sms'); ?></h2>
    <div class="postbox-container" style="padding-top: 20px;">
        <div class="meta-box-sortables">
            <div class="postbox">
                <h2 class="hndle" style="cursor: default;padding: 0 10px 10px 10px;font-size: 13px;">
                    <span><?php _e('Top Up account form', 'wp-camoo-sms'); ?></span></h2>

                <div class="inside">
                    <form method="post" action="">
                        <?php wp_nonce_field('camoo_topup', 'camoo_topup'); ?>
                        <table class="form-table">
                            <caption id="topup-caption"><?php _e(sprintf('Quick and easy top up your SMS account via Mobile Money. The process is handled by our partner <strong>%s</strong>', 'MAVIANCE PLC'), 'wp-camoo-sms'); ?></caption>
                            <tr>
                                <th scope="row">
                                    <label for="wp_get_phonenumber"><?php _e('Phone number', 'wp-camoo-sms'); ?>:</label>
                                </th>
                                <td>
                                    <input type="number" name="camoo_phone" id="topup-phone"
                                           placeholder="<?php _e('e.g: 612345678', 'wp-camoo-sms');?>" maxlength="18" required/>

                                    <p class="description"><?php _e('Specify your <strong>mobile money</strong> phone number. <strong>MTN</strong> or <strong>Orange</strong> Cameroon supported.', 'wp-camoo-sms'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="wp_get_amount"><?php _e('Amount', 'wp-camoo-sms'); ?>:</label>
                                </th>
                                <td>
                                    <input type="number" name="camoo_amount" id="topup-amount"
                                                                             placeholder="5000" maxlength="18" required/>
                                    <p class="description"><?php _e('Add the amount you want to top up', 'wp-camoo-sms'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <p class="submit" style="padding: 0;">
                                        <input type="submit" class="button-primary" name="topupAccount"
                                               value="<?php _e('Top up', 'wp-camoo-sms'); ?>"/>
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
