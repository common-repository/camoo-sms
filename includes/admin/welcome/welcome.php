<div class="wrap wps-wrap about-wrap full-width-layout">
    <div class="wp-camoo-sms-welcome">
        <h1><?php printf(__('Welcome to CAMOO-SMS&nbsp;%s', 'wp-camoo-sms'), WP_CAMOO_SMS_VERSION); ?></h1>

        <p class="about-text">
            <?php _e('Thank you for updating to the latest version!', 'wp-camoo-sms'); ?>
            <a href="https://wwww.camoo.cm" target="_blank"><img src="<?php echo plugins_url('camoo-sms/assets/images/logo.svg'); ?>"/></a>
        </p>

        <div class="wp-badge"><?php printf(__('Version %s', 'wp-camoo-sms'), WP_CAMOO_SMS_VERSION); ?></div>

        <h2 class="nav-tab-wrapper wp-clearfix">
            <a href="#" class="nav-tab nav-tab-active" data-tab="whats-news"><?php _e('What&#8217;s New', 'wp-camoo-sms'); ?></a>
        </h2>

        <div data-content="whats-news" class="tab-content current">
            <section class="center-section">
                <div class="left">
                    <div class="content-padding">
                        <h2><?php _e('Improvement feature for CAMOO-SMS', 'wp-camoo-sms'); ?></h2>
                    </div>
                </div>
            </section>

            <section class="normal-section">
                <div class="left">
                    <div class="content-padding">
                        <h2><?php _e('SMS box in WooCommerce orders', 'wp-camoo-sms'); ?></h2>
                        <p><?php _e('You can send SMS to customer orders.', 'wp-camoo-sms'); ?></p>
                    </div>
                </div>

                <div class="right text-center">
                    <img src="<?php echo plugins_url('camoo-sms/assets/images/welcome/what-is-new/wc-order-box.png'); ?>"/>
                </div>
            </section>

            <section class="normal-section">
                <div class="right">
                    <div class="content-padding">
                        <h2><?php _e('SMS option in WooCommerce order notes', 'wp-camoo-sms'); ?></h2>
                        <p><?php _e('Send your notes with SMS!', 'wp-camoo-sms'); ?></p>
                    </div>
                </div>

                <div class="left text-center">
                    <img src="<?php echo plugins_url('camoo-sms/assets/images/welcome/what-is-new/wc-order-box-note.png'); ?>"/>
                </div>
            </section>

        </div>

        <div data-content="credit" class="tab-content">
            <div class="about-wrap-content">
                <p class="about-description"><?php echo sprintf(__('CAMOO-SMS is created by some people and is one of the <a href="%s" target="_blank">Camoo.cm</a> projects.', 'wp-camoo-sms'), 'https://www.camoo.cm'); ?></p>
                <h3 class="wp-people-group"><?php _e('Project Leaders', 'wp-camoo-sms'); ?></h3>
                <ul class="wp-people-group ">
                    <li class="wp-person">
                        <a href="https://profiles.wordpress.org/camoo" class="web"><?php echo get_avatar('wordpress@camoo.sarl', 62, '', '', ['class' => 'gravatar']); ?><?php _e('Camoo Sarl', 'wp-camoo-sms'); ?></a>
                        <span class="title"><?php _e('Original Author', 'wp-camoo-sms'); ?></span>
                    </li>
                </ul>

                <p class="clear"><?php echo sprintf(__('CAMOO-SMS is a fork of WP-SMS and is being developed on GitHub, if you’re interested in contributing to the plugin, please look at the <a href="%s" target="_blank">GitHub page</a>.', 'wp-camoo-sms'), 'https://github.com/camoo/wp-camoo-sms'); ?></p>
            </div>
        </div>

        <div data-content="changelog" class="tab-content">
            <?php \CAMOO_SMS\Welcome::show_change_log(); ?>
        </div>
        <hr style="clear: both;">
        <div class="wps-return-to-dashboard">
            <a href="admin.php?page=wp-camoo-sms-settings"><?php _e('Go to CAMOO-SMS &rarr; Settings', 'wp-camoo-sms'); ?></a>
        </div>
    </div>
</div>
