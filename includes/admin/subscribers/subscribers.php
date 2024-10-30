<div class="wrap">
    <h2><?php _e('Subscribers', 'wp-camoo-sms'); ?></h2>
    <?php add_thickbox(); ?>
    <div class="wpsms-button-group">
        <a name="<?php _e('Add Subscribe', 'wp-camoo-sms'); ?>" href="admin.php?page=wp-camoo-sms-subscribers#TB_inline?&width=400&height=250&inlineId=add-subscriber" class="thickbox button"><span class="dashicons dashicons-admin-users"></span> <?php _e('Add Subscribe', 'wp-camoo-sms'); ?>
        </a>
        <a href="admin.php?page=wp-camoo-sms-subscribers-group" class="button"><span class="dashicons dashicons-category"></span> <?php _e('Manage Group', 'wp-camoo-sms'); ?>
        </a>
        <a name="<?php _e('Import', 'wp-camoo-sms'); ?>" href="admin.php?page=wp-camoo-sms-subscribers#TB_inline?&width=400&height=270&inlineId=import-subscriber" class="thickbox button"><span class="dashicons dashicons-undo"></span> <?php _e('Import', 'wp-camoo-sms'); ?>
        </a>
        <a name="<?php _e('Export', 'wp-camoo-sms'); ?>" href="admin.php?page=wp-camoo-sms-subscribers#TB_inline?&width=400&height=150&inlineId=export-subscriber" class="thickbox button"><span class="dashicons dashicons-redo"></span> <?php _e('Export', 'wp-camoo-sms'); ?>
        </a>
    </div>
    <div id="add-subscriber" style="display:none;">
        <form action="" method="post">
            <input type="hidden" name="camoo_sms_n" value="<?php echo wp_create_nonce('camoo_sms_n'); ?>"/>
            <table>
                <tr>
                    <td style="padding-top: 10px;">
                        <label for="wp_subscribe_name" class="wp_camoo_sms_subscribers_label"><?php _e('Name', 'wp-camoo-sms'); ?></label>
                        <input type="text" id="wp_subscribe_name" name="wp_subscribe_name" class="wp_camoo_sms_subscribers_input_text" required/>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top: 10px;">
                        <label for="wp_subscribe_mobile" class="wp_camoo_sms_subscribers_label"><?php _e('Mobile', 'wp-camoo-sms'); ?></label>
                        <input type="text" id="wp_subscribe_mobile" name="wp_subscribe_mobile" class="wp_camoo_sms_subscribers_input_text" required/>
                    </td>
                </tr>
                <?php
                $groups = \CAMOO_SMS\Newsletter::getGroups();
    if ($groups) { ?>
                    <tr>
                        <td style="padding-top: 10px;">
                            <label class="wp_camoo_sms_subscribers_label" for="wpcamoosms_group_name"><?php _e('Group', 'wp-camoo-sms'); ?>
                                :</label>
                            <select name="wpcamoosms_group_name" id="wpcamoosms_group_name" class="wp_camoo_sms_subscribers_input_text">
                                <?php foreach ($groups as $items) { ?>
                                    <option value="<?php echo $items->ID; ?>"><?php echo $items->name; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td>
                            <span class="wp_camoo_sms_subscribers_label" for="wpcamoosms_group_name"><?php _e('Group', 'wp-camoo-sms'); ?>:</span>
                            <?php echo sprintf(__('There is no group! <a href="%s">Add</a>', 'wp-camoo-sms'), 'admin.php?page=wp-camoo-sms-subscribers-group'); ?>
                        </td>
                    </tr>
                <?php } ?>

                <tr>
                    <td colspan="2" style="padding-top: 20px;">
                        <input type="submit" class="button-primary" name="wp_add_subscribe" value="<?php _e('Add', 'wp-camoo-sms'); ?>"/>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <div id="import-subscriber" style="display:none;">
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="camoo_sms_n" value="<?php echo wp_create_nonce('camoo_sms_n'); ?>"/>
            <table>
                <tr>
                    <td style="padding-top: 10px;">
                        <input id="async-upload" type="file" name="wps-import-file"/>
                        <p class="upload-html-bypass"><?php echo sprintf(__('<code>Excel 97-2003 Workbook (*.xls)</code> is the only acceptable format. Please see <a href="%s">this image</a> to show a standard xls import file.', 'wp-camoo-sms'), plugins_url('camoo-sms/assets/images/standard-xml-file.png')); ?></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="wpcamoosms_group_name" class="wp_camoo_sms_subscribers_label"><?php _e('Group', 'wp-camoo-sms'); ?></label>
                        <?php if ($groups) { ?>
                        <select name="wpcamoosms_group_name" id="wpcamoosms_group_name" class="wp_camoo_sms_subscribers_input_text">
                            <?php
                foreach ($groups as $items) {
                    ?>
                                <option value="<?php echo $items->ID; ?>"><?php echo $items->name; ?></option>
                            <?php }
                } else { ?>
                                <?php echo sprintf(__('There is no group! <a href="%s">Add</a>', 'wp-camoo-sms'), 'admin.php?page=wp-camoo-sms-subscribers-group'); ?>
                        <?php } ?>
                    </select>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top: 10px;">
                        <input type="checkbox" name="ignore_duplicate" value="ignore"/> <?php _e('Ignore duplicate subscribers if exist to other group.', 'wp-camoo-sms'); ?>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding-top: 20px;">
                        <input type="submit" class="button-primary" name="wps_import" value="<?php _e('Upload', 'wp-camoo-sms'); ?>"/>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <div id="export-subscriber" style="display:none;">
		<form method="post" action="<?php echo add_query_arg(['pagename' => 'camoo_export'], get_home_url());?>">
        <input type="hidden" name="camoo_sms_export_nonce" value="<?php echo wp_create_nonce('camoo_sms_export_nonce'); ?>"/>
            <table>
                <tr>
                    <td style="padding-top: 10px;">
                        <label for="export-file-type" class="wp_camoo_sms_subscribers_label"><?php _e('Export To', 'wp-camoo-sms'); ?></label>
                        <select id="export-file-type" name="export-file-type" class="wp_camoo_sms_subscribers_input_text">
                            <option value="0"><?php _e('Please select.', 'wp-camoo-sms'); ?></option>
                            <option value="excel">Excel</option>
                            <option value="xml">XML</option>
                            <option value="csv">CSV</option>
                            <option value="tsv">TSV</option>
                        </select>
                        <p class="description"><?php _e('Select the output file type.', 'wp-camoo-sms'); ?></p>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="padding-top: 10px;">
                        <input type="submit" class="button-primary" name="camoo_export" value="<?php _e('Export', 'wp-camoo-sms'); ?>"/>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <form id="subscribers-filter" method="get">
        <input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page']) ?>"/>
        <?php $list_table->search_box(__('Search', 'wp-camoo-sms'), 'search_id'); ?>
        <?php $list_table->display(); ?>
    </form>
</div>
