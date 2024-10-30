<div class="wrap">
    <h2><?php _e('Groups', 'wp-camoo-sms'); ?></h2>

    <div class="wpsms-button-group">
        <?php add_thickbox(); ?>
        <a name="<?php _e('Add Group', 'wp-camoo-sms'); ?>"
           href="admin.php?page=wp-camoo-sms-subscribers-group#TB_inline?&width=400&height=125&inlineId=add-group"
           class="thickbox button"><span
                    class="dashicons dashicons-groups"></span> <?php _e('Add Group', 'wp-camoo-sms'); ?></a>
        <div id="add-group" style="display:none;">
            <form action="" method="post">
               <input type="hidden" name="camoo_sms_n" value="<?php echo wp_create_nonce('camoo_sms_n'); ?>"/>
                <table>
                    <tr>
                        <td style="padding-top: 10px;">
                            <label for="wp_group_name"
                                   class="wp_camoo_sms_subscribers_label"><?php _e('Name', 'wp-camoo-sms'); ?></label>
                            <input type="text" id="wp_group_name" name="wp_group_name"
                                   class="wp_camoo_sms_subscribers_input_text" required/>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" style="padding-top: 20px;">
                            <input type="submit" class="button-primary" name="wp_add_group"
                                   value="<?php _e('Add', 'wp-camoo-sms'); ?>"/>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>

    <form id="subscribers-filter" method="get">
        <input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page']) ?>"/>
        <?php $list_table->search_box(__('Search', 'wp-camoo-sms'), 'search_id'); ?>
        <?php $list_table->display(); ?>
    </form>
</div>
