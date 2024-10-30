<div class="wrap">
    <h2><?php _e('Outbox SMS', 'wp-camoo-sms'); ?></h2>

    <form id="outbox-filter" method="get">
        <input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page']) ?>"/>
        <?php $list_table->search_box(__('Search', 'wp-camoo-sms'), 'search_id'); ?>
        <?php $list_table->display(); ?>
    </form>
</div>
