<?php
/*
  Plugin Name: WP PayPal
  Version: 1.2.3.28
  Plugin URI: https://wphowto.net/wordpress-paypal-plugin-732
  Author: naa986
  Author URI: https://wphowto.net/
  Description: Easily accept PayPal payments in WordPress
  Text Domain: wp-paypal
  Domain Path: /languages
 */

if (!defined('ABSPATH')){
    exit;
}

class WP_PAYPAL {
    
    var $plugin_version = '1.2.3.28';
    var $db_version = '1.0.2';
    var $plugin_url;
    var $plugin_path;
    
    function __construct() {
        define('WP_PAYPAL_VERSION', $this->plugin_version);
        define('WP_PAYPAL_DB_VERSION', $this->db_version);
        define('WP_PAYPAL_SITE_URL', site_url());
        define('WP_PAYPAL_HOME_URL', home_url());
        define('WP_PAYPAL_URL', $this->plugin_url());
        define('WP_PAYPAL_PATH', $this->plugin_path());
        $debug_enabled = get_option('wp_paypal_enable_debug');
        if (isset($debug_enabled) && !empty($debug_enabled)) {
            define('WP_PAYPAL_DEBUG', true);
        } else {
            define('WP_PAYPAL_DEBUG', false);
        }
        $use_sandbox = get_option('wp_paypal_enable_testmode');
        if (isset($use_sandbox) && !empty($use_sandbox)) {
            define('WP_PAYPAL_USE_SANDBOX', true);
        } else {
            define('WP_PAYPAL_USE_SANDBOX', false);
        }
        define('WP_PAYPAL_DEBUG_LOG_PATH', $this->debug_log_path());
        $this->plugin_includes();
        $this->loader_operations();
    }

    function plugin_includes() {
        include_once('wp-paypal-order.php');
        include_once('wp-paypal-checkout.php');
        include_once('paypal-ipn.php');
        if(is_admin()){
            include_once('addons/wp-paypal-addons-menu.php');
        }
    }

    function loader_operations() {
        register_activation_hook(__FILE__, array($this, 'activate_handler'));
        add_action('plugins_loaded', array($this, 'plugins_loaded_handler'));
        add_action('admin_notices', array($this, 'admin_notice'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'plugin_scripts'));
        add_action('admin_menu', array($this, 'add_options_menu'));
        add_action('init', array($this, 'plugin_init'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_filter('manage_wp_paypal_order_posts_columns', 'wp_paypal_order_columns');
        add_action('manage_wp_paypal_order_posts_custom_column', 'wp_paypal_custom_column', 10, 2);
        add_shortcode('wp_paypal', 'wp_paypal_button_handler');
        add_action('wp_ajax_wppaypalcheckout_ajax_process_order', 'wp_paypal_checkout_ajax_process_order');
        add_action('wp_ajax_nopriv_wppaypalcheckout_ajax_process_order', 'wp_paypal_checkout_ajax_process_order');
        add_action('wp_paypal_checkout_process_order', 'wp_paypal_checkout_process_order_handler');
        add_shortcode('wp_paypal_checkout', 'wp_paypal_checkout_button_handler');
    }

    function plugins_loaded_handler() {  //Runs when plugins_loaded action gets fired
        if(is_admin() && current_user_can('manage_options')){
            add_filter('plugin_action_links', array($this, 'add_plugin_action_links'), 10, 2);
        }
        load_plugin_textdomain( 'wp-paypal', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
        $this->check_upgrade();
    }

    function admin_notice() {
        if (WP_PAYPAL_DEBUG) {  //debug is enabled. Check to make sure log file is writable
            $log_file = WP_PAYPAL_DEBUG_LOG_PATH;
            if(!file_exists($log_file)){
                return;
            }
            if (!is_writeable($log_file)) {
                echo '<div class="updated"><p>' . __('WP PayPal Debug log file is not writable. Please check to make sure that it has the correct file permission (ideally 644). Otherwise the plugin will not be able to write to the log file. The log file can be found in the root directory of the plugin - ', 'wp-paypal') . '<code>' . WP_PAYPAL_URL . '</code></p></div>';
            }
        }
    }

    function activate_handler() {
        add_option('wp_paypal_db_version', $this->db_version);
        add_option('wp_paypal_email', get_bloginfo('admin_email'));
        add_option('wp_paypal_currency_code', 'USD');
        add_option('wp_paypal_enable_ipn_validation', '1');
        add_option('wp_paypal_enable_receiver_check', '1');
        wp_paypal_set_default_email_options();
    }

    function check_upgrade() {
        if (is_admin()) {
            $db_version = get_option('wp_paypal_db_version');
            if (!isset($db_version) || $db_version != $this->db_version) {
                $this->activate_handler();
                update_option('wp_paypal_db_version', $this->db_version);
            }
        }
    }

    function plugin_init() {
        //register orders
        wp_paypal_order_page();
        //process PayPal IPN
        wp_paypal_process_ipn();
    }

    function add_meta_boxes() {
        //add_meta_box('wp-paypal-order-box', __('Edit PayPal Order', 'wp-paypal'), 'wp_paypal_order_meta_box', 'wp_paypal_order', 'normal', 'high');
    }

    function enqueue_admin_scripts($hook) {
        if('wp_paypal_order_page_wp-paypal-addons' != $hook) {
            return;
        }
        wp_register_style('wp-paypal-addons-menu', WP_PAYPAL_URL.'/addons/wp-paypal-addons-menu.css');
        wp_enqueue_style('wp-paypal-addons-menu');
    }
    
    function plugin_scripts() {
        if (is_404()) {
            return;
        }
        if (!is_admin()) {
            global $post;
            if(is_a($post, 'WP_Post')
                    && has_shortcode($post->post_content, 'wp_paypal_checkout')
                        || has_shortcode(get_post_meta($post->ID, 'wp-paypal-custom-field', true), 'wp_paypal_checkout')){
                $options = wp_paypal_checkout_get_option();
                if(!is_wp_paypal_checkout_configured()){
                    return;
                }
                $args = array(
                    'client-id' => $options['app_client_id'],
                    'currency' => $options['currency_code'],                 
                );
                if(isset($options['enable_funding']) && !empty($options['enable_funding'])){
                    $args['enable-funding'] = $options['enable_funding'];
                }
                if(isset($options['disable_funding']) && !empty($options['disable_funding'])){
                    $args['disable-funding'] = $options['disable_funding'];
                }
                $sdk_js_url = add_query_arg($args, 'https://www.paypal.com/sdk/js');
                wp_enqueue_script('jquery');
                wp_register_script('wp-paypal', $sdk_js_url, array('jquery'), null);
                wp_enqueue_script('wp-paypal');
            }        
        }
    }

    function plugin_url() {
        if ($this->plugin_url){
            return $this->plugin_url;
        }
        return $this->plugin_url = plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
    }

    function plugin_path() {
        if ($this->plugin_path){
            return $this->plugin_path;
        }
        return $this->plugin_path = untrailingslashit(plugin_dir_path(__FILE__));
    }

    function debug_log_path() {
        return WP_PAYPAL_PATH . '/logs/'. $this->debug_log_file_name();
    }
        
    function debug_log_file_name() {
        return 'log-'.$this->debug_log_file_suffix().'.txt';
    }
    
    function debug_log_file_suffix() {
        $suffix = get_option('wppaypal_logfile_suffix');
        if(isset($suffix) && !empty($suffix)) {
            return $suffix;
        }
        $suffix = uniqid();
        update_option('wppaypal_logfile_suffix', $suffix);
        return $suffix;
    }
    
    function add_plugin_action_links($links, $file) {
        if ($file == plugin_basename(dirname(__FILE__) . '/main.php')) {
            $links[] = '<a href="'.esc_url(admin_url('edit.php?post_type=wp_paypal_order&page=wp-paypal-settings')).'">'.__('Settings', 'wp-paypal').'</a>';
        }
        return $links;
    }

    function add_options_menu() {
        if (is_admin()) {
            add_submenu_page('edit.php?post_type=wp_paypal_order', __('Settings', 'wp-paypal'), __('Settings', 'wp-paypal'), 'manage_options', 'wp-paypal-settings', array($this, 'options_page'));
            add_submenu_page('edit.php?post_type=wp_paypal_order', __('Debug', 'wp-paypal'), __('Debug', 'wp-paypal'), 'manage_options', 'wp-paypal-debug', array($this, 'debug_page'));
            add_submenu_page('edit.php?post_type=wp_paypal_order', __('Add-ons', 'wp-paypal'), __('Add-ons', 'wp-paypal'), 'manage_options', 'wp-paypal-addons', 'wp_paypal_display_addons_menu');
        }
    }

    function options_page() {
        $plugin_tabs = array(
            'wp-paypal-settings' => __('General', 'wp-paypal'),
            'wp-paypal-settings&tab=emails' => __('Emails', 'wp-paypal')
        );
        echo '<div class="wrap"><h2>'.__('WP PayPal', 'wp-paypal').' v' . WP_PAYPAL_VERSION . '</h2>';
        $url = 'https://wphowto.net/wordpress-paypal-plugin-732';
        $link_msg = sprintf(__('Please visit the <a target="_blank" href="%s">WP PayPal</a> documentation page for setup instructions.', 'wp-paypal'), esc_url($url));
        $allowed_html_tags = array(
            'a' => array(
                'href' => array(),
                'target' => array()
            )
        );
        echo '<div class="update-nag">'.wp_kses($link_msg, $allowed_html_tags).'</div>';
        $current = '';
        $tab = '';
        if (isset($_GET['page'])) {
            $current = sanitize_text_field($_GET['page']);
            if (isset($_GET['tab'])) {
                $tab = sanitize_text_field($_GET['tab']);
                $current .= "&tab=" . $tab;
            }
        }
        $content = '';
        $content .= '<h2 class="nav-tab-wrapper">';
        foreach ($plugin_tabs as $location => $tabname) {
            if ($current == $location) {
                $class = ' nav-tab-active';
            } else {
                $class = '';
            }
            $content .= '<a class="nav-tab' . $class . '" href="?post_type=wp_paypal_order&page=' . $location . '">' . $tabname . '</a>';
        }
        $content .= '</h2>';
        $allowed_html_tags = array(
            'a' => array(
                'href' => array(),
                'class' => array()
            ),
            'h2' => array(
                'href' => array(),
                'class' => array()
            )
        );
        echo wp_kses($content, $allowed_html_tags);
        
        if(!empty($tab))
        { 
            switch ($tab)
            {
               case 'emails':
                   $this->email_settings();
                   break;
            }
        }
        else
        {
            $this->general_settings();
        }

        echo '</div>';
    }

    function general_settings() {
        if (isset($_POST['wp_paypal_update_settings'])) {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'wp_paypal_general_settings')) {
                wp_die('Error! Nonce Security Check Failed! please save the settings again.');
            }
            //
            $checkout_app_client_id = '';
            if(isset($_POST['checkout_app_client_id']) && !empty($_POST['checkout_app_client_id'])){
                $checkout_app_client_id = sanitize_text_field($_POST['checkout_app_client_id']);
            }
            $checkout_currency_code = '';
            if(isset($_POST['checkout_currency_code']) && !empty($_POST['checkout_currency_code'])){
                $checkout_currency_code = sanitize_text_field($_POST['checkout_currency_code']);
            }
            $checkout_return_url = '';
            if(isset($_POST['checkout_return_url']) && !empty($_POST['checkout_return_url'])){
                $checkout_return_url = esc_url_raw($_POST['checkout_return_url']);
            }
            $checkout_cancel_url = '';
            if(isset($_POST['checkout_cancel_url']) && !empty($_POST['checkout_cancel_url'])){
                $checkout_cancel_url = esc_url_raw($_POST['checkout_cancel_url']);
            }
            $checkout_enable_funding = '';
            if(isset($_POST['checkout_enable_funding'])){
                $checkout_enable_funding = sanitize_text_field($_POST['checkout_enable_funding']);
            }
            $checkout_disable_funding = '';
            if(isset($_POST['checkout_disable_funding'])){
                $checkout_disable_funding = sanitize_text_field($_POST['checkout_disable_funding']);
            }
            $paypal_checkout_options = array();
            $paypal_checkout_options['app_client_id'] = $checkout_app_client_id;
            $paypal_checkout_options['currency_code'] = $checkout_currency_code;
            $paypal_checkout_options['return_url'] = $checkout_return_url;
            $paypal_checkout_options['cancel_url'] = $checkout_cancel_url;
            $paypal_checkout_options['enable_funding'] = $checkout_enable_funding;
            $paypal_checkout_options['disable_funding'] = $checkout_disable_funding;
            wp_paypal_checkout_update_option($paypal_checkout_options);
            //
            update_option('wp_paypal_enable_testmode', (isset($_POST["enable_testmode"]) && $_POST["enable_testmode"] == '1') ? '1' : '');
            update_option('wp_paypal_merchant_id', sanitize_text_field($_POST["paypal_merchant_id"]));
            update_option('wp_paypal_email', sanitize_email($_POST["paypal_email"]));
            update_option('wp_paypal_currency_code', sanitize_text_field($_POST["currency_code"]));
            update_option('wp_paypal_enable_ipn_validation', (isset($_POST["enable_ipn_validation"]) && $_POST["enable_ipn_validation"] == '1') ? '1' : '');
            update_option('wp_paypal_enable_receiver_check', (isset($_POST["enable_receiver_check"]) && $_POST["enable_receiver_check"] == '1') ? '1' : '');
            echo '<div id="message" class="updated fade"><p><strong>';
            echo __('Settings Saved', 'wp-paypal').'!';
            echo '</strong></p></div>';
        }
        $paypal_checkout_options = wp_paypal_checkout_get_option();
        //these options may not be set as they were added later
        if(!isset($paypal_checkout_options['enable_funding']) || empty($paypal_checkout_options['enable_funding'])){
            $paypal_checkout_options['enable_funding'] = '';
        }
        if(!isset($paypal_checkout_options['disable_funding']) || empty($paypal_checkout_options['disable_funding'])){
            $paypal_checkout_options['disable_funding'] = '';
        }
        //
        $funding_src_url = "https://wphowto.net/wordpress-paypal-plugin-732";
        $funding_src_link = sprintf(__('You can find the full list of funding sources <a target="_blank" href="%s">here</a>.', 'wp-paypal'), esc_url($funding_src_url));
        $allowed_html_tags = array(
            'a' => array(
                'href' => array(),
                'title' => array()
            )
        );
              
        ?>
        <table class="wppaypal-general-settings-table">
            <tbody>
                <tr>
                    <td valign="top">
                        <form method="post" action="">
                            <?php wp_nonce_field('wp_paypal_general_settings'); ?>
                            <h2><?php _e('PayPal Checkout', 'wp-paypal');?></h2>
                            <p><?php printf(__('These settings apply to %s shortcode buttons', 'wp-paypal'), '[wp_paypal_checkout]');?></p>
                            <table class="form-table">

                                <tbody>

                                    <tr valign="top">
                                        <th scope="row"><label for="checkout_app_client_id"><?php _e('Client ID', 'wp-paypal');?></label></th>
                                        <td><input name="checkout_app_client_id" type="text" id="checkout_app_client_id" value="<?php echo esc_attr($paypal_checkout_options['app_client_id']); ?>" class="regular-text">
                                            <p class="description"><?php _e('The client ID for your PayPal REST API app', 'wp-paypal');?></p></td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"><label for="checkout_currency_code"><?php _e('Currency Code', 'wp-paypal');?></label></th>
                                        <td><input name="checkout_currency_code" type="text" id="checkout_currency_code" value="<?php echo esc_attr($paypal_checkout_options['currency_code']); ?>" class="regular-text">
                                            <p class="description"><?php _e('The default currency of the payment', 'wp-paypal');?> (<?php _e('example', 'wp-paypal');?>: USD, CAD, GBP, EUR)</p></td>
                                    </tr>
                                    
                                    <tr valign="top">
                                        <th scope="row"><label for="checkout_return_url"><?php _e('Return URL', 'wp-paypal');?></label></th>
                                        <td><input name="checkout_return_url" type="text" id="checkout_return_url" value="<?php echo esc_url($paypal_checkout_options['return_url']); ?>" class="regular-text">
                                            <p class="description"><?php _e('The page URL to which the customer will be redirected after a successful payment (optional)', 'wp-paypal');?></p></td>
                                    </tr>
                                    
                                    <tr valign="top">
                                        <th scope="row"><label for="checkout_cancel_url"><?php _e('Cancel URL', 'wp-paypal');?></label></th>
                                        <td><input name="checkout_cancel_url" type="text" id="checkout_cancel_url" value="<?php echo esc_url($paypal_checkout_options['cancel_url']); ?>" class="regular-text">
                                            <p class="description"><?php _e('The page URL to which the customer will be redirected when a payment is cancelled (optional)', 'wp-paypal');?></p></td>
                                    </tr>
                                    
                                    <tr valign="top">
                                        <th scope="row"><label for="checkout_enable_funding"><?php _e('Enabled Funding Sources', 'wp-paypal');?></label></th>
                                        <td><textarea name="checkout_enable_funding" id="checkout_enable_funding" class="large-text"><?php echo esc_html($paypal_checkout_options['enable_funding']); ?></textarea>
                                            <p class="description"><?php echo __('Enabled funding sources in comma-separated format', 'wp-paypal').' ';?>(Example: <strong>venmo</strong> or <strong>venmo,credit</strong> or <strong>venmo,credit,paylater</strong>).<?php echo ' '.__('This is not required as the eligibility is determined automatically. However, this field can be used to ensure a funding source is always rendered, if eligible.', 'wp-paypal').' '.wp_kses($funding_src_link, $allowed_html_tags);?></p></td>
                                    </tr>
                                    
                                    <tr valign="top">
                                        <th scope="row"><label for="checkout_disable_funding"><?php _e('Disabled Funding Sources', 'wp-paypal');?></label></th>
                                        <td><textarea name="checkout_disable_funding" id="checkout_disable_funding" class="large-text"><?php echo esc_html($paypal_checkout_options['disable_funding']); ?></textarea>
                                            <p class="description"><?php echo __('Disabled funding sources in comma-separated format', 'wp-paypal').' ';?>(Example: <strong>credit</strong> or <strong>card,credit</strong> or <strong>card,credit,paylater</strong>).<?php echo ' '.__('Any funding sources that you enter here are not displayed as buttons at checkout.', 'wp-paypal').' '.wp_kses($funding_src_link, $allowed_html_tags);?></p></td>
                                    </tr>

                                </tbody>

                            </table>
                            <h2><?php _e('PayPal Payments Standard', 'wp-paypal');?></h2>
                            <p><?php printf(__('These settings apply to %s shortcode buttons', 'wp-paypal'), '[wp_paypal]');?></p>
                            <table class="form-table">

                                <tbody>

                                    <tr valign="top">
                                        <th scope="row"><?php _e('Enable Test Mode', 'wp-paypal');?></th>
                                        <td> <fieldset><legend class="screen-reader-text"><span>Enable Test Mode</span></legend><label for="enable_testmode">
                                                    <input name="enable_testmode" type="checkbox" id="enable_testmode" <?php if (get_option('wp_paypal_enable_testmode') == '1') echo ' checked="checked"'; ?> value="1">
                                                    <?php _e('Check this option if you want to enable PayPal sandbox for testing', 'wp-paypal');?></label>
                                            </fieldset></td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"><label for="paypal_merchant_id"><?php _e('PayPal Merchant ID', 'wp-paypal');?></label></th>
                                        <td><input name="paypal_merchant_id" type="text" id="paypal_merchant_id" value="<?php echo esc_attr(get_option('wp_paypal_merchant_id')); ?>" class="regular-text">
                                            <p class="description"><?php _e('Your PayPal Merchant ID', 'wp-paypal');?></p></td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"><label for="paypal_email"><?php _e('PayPal Email', 'wp-paypal');?></label></th>
                                        <td><input name="paypal_email" type="text" id="paypal_email" value="<?php echo esc_attr(get_option('wp_paypal_email')); ?>" class="regular-text">
                                            <p class="description"><?php _e('Your PayPal email address', 'wp-paypal');?></p></td>
                                    </tr>

                                    <tr valign="top">
                                        <th scope="row"><label for="currency_code"><?php _e('Currency Code', 'wp-paypal');?></label></th>
                                        <td><input name="currency_code" type="text" id="currency_code" value="<?php echo esc_attr(get_option('wp_paypal_currency_code')); ?>" class="regular-text">
                                            <p class="description"><?php _e('The currency of the payment', 'wp-paypal');?> (<?php _e('example', 'wp-paypal');?>: USD, CAD, GBP, EUR)</p></td>
                                    </tr>
                                    
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Enable IPN Validation', 'wp-paypal');?></th>
                                        <td> <fieldset><legend class="screen-reader-text"><span>Enable IPN Validation</span></legend><label for="enable_ipn_validation">
                                                    <input name="enable_ipn_validation" type="checkbox" id="enable_ipn_validation" <?php if (get_option('wp_paypal_enable_ipn_validation') == '1') echo ' checked="checked"'; ?> value="1">
                                                    <?php _e('Check this option if you want to send the IPN data to PayPal for validation', 'wp-paypal');?></label>
                                            </fieldset></td>
                                    </tr>
                                    
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Enable Receiver Check', 'wp-paypal');?></th>
                                        <td> <fieldset><legend class="screen-reader-text"><span>Enable Receiver Check</span></legend><label for="enable_receiver_check">
                                                    <input name="enable_receiver_check" type="checkbox" id="enable_receiver_check" <?php if (get_option('wp_paypal_enable_receiver_check') == '1') echo ' checked="checked"'; ?> value="1">
                                                    <?php _e('Check this option if you want the seller account in the settings to match the receiver account when processing a payment. This option should be disabled when accepting payments on separate accounts.', 'wp-paypal');?></label>
                                            </fieldset></td>
                                    </tr>

                                </tbody>

                            </table>

                            <p class="submit"><input type="submit" name="wp_paypal_update_settings" id="wp_paypal_update_settings" class="button button-primary" value="<?php _e('Save Changes', 'wp-paypal');?>"></p></form>
                    </td>
                    <td valign="top" style="width: 300px">
                        <div style="background: #ffc; border: 1px solid #333; margin: 2px; padding: 3px 15px">
                        <h3><?php _e('Need More Features?', 'wp-paypal')?></h3>
                        <ol>
                        <li><?php printf(__('Check out the <a href="%s">plugin add-ons</a>.', 'wp-paypal'), 'edit.php?post_type=wp_paypal_order&page=wp-paypal-addons');?></li>
                        </ol>    
                        <h3><?php _e('Need Help?', 'wp-paypal')?></h3>
                        <ol>
                        <li><?php printf(__('Use the <a href="%s">Debug</a> menu for diagnostics.', 'wp-paypal'), 'edit.php?post_type=wp_paypal_order&page=wp-paypal-debug');?></li>
                        <li><?php printf(__('Check out the <a target="_blank" href="%s">support forum</a> and <a target="_blank" href="%s">FAQ</a>.', 'wp-paypal'), 'https://wordpress.org/support/plugin/wp-paypal/', 'https://wordpress.org/plugins/wp-paypal/#faq');?></li>
                        <li><?php printf(__('Visit the <a target="_blank" href="%s">plugin homepage</a>.', 'wp-paypal'), 'https://wphowto.net/wordpress-paypal-plugin-732');?></li>
                        </ol>
                        <h3><?php _e('Rate This Plugin', 'wp-paypal')?></h3>
                        <p><?php printf(__('Please <a target="_blank" href="%s">rate us</a> and give feedback.', 'wp-paypal'), 'https://wordpress.org/support/plugin/wp-paypal/reviews?rate=5#new-post');?></p>
                        </div>
                    </td>
                </tr>
            </tbody> 
        </table>    
        <?php
    }
    
    function email_settings() 
    {
        if (isset($_POST['wp_paypal_update_email_settings'])) 
        {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'wp_paypal_email_settings_nonce')) {
                wp_die(__('Error! Nonce Security Check Failed! please save the email settings again.', 'wp-paypal'));
            }
            $_POST = stripslashes_deep($_POST);
            $email_from_name = '';
            if(isset($_POST['email_from_name']) && !empty($_POST['email_from_name'])){
                $email_from_name = sanitize_text_field($_POST['email_from_name']);
            }
            $email_from_address= '';
            if(isset($_POST['email_from_address']) && !empty($_POST['email_from_address'])){
                $email_from_address = sanitize_email($_POST['email_from_address']);
            }
            $purchase_email_enabled = (isset($_POST["purchase_email_enabled"]) && $_POST["purchase_email_enabled"] == '1') ? '1' : '';
            $purchase_email_subject = '';
            if(isset($_POST['purchase_email_subject']) && !empty($_POST['purchase_email_subject'])){
                $purchase_email_subject = sanitize_text_field($_POST['purchase_email_subject']);
            }
            $purchase_email_type = '';
            if(isset($_POST['purchase_email_type']) && !empty($_POST['purchase_email_type'])){
                $purchase_email_type = sanitize_text_field($_POST['purchase_email_type']);
            }
            $purchase_email_body = '';
            if(isset($_POST['purchase_email_body']) && !empty($_POST['purchase_email_body'])){
                $purchase_email_body = wp_kses_post($_POST['purchase_email_body']);
            }
            $sale_notification_email_enabled = (isset($_POST["sale_notification_email_enabled"]) && $_POST["sale_notification_email_enabled"] == '1') ? '1' : '';
            $sale_notification_email_recipient = '';
            if(isset($_POST['sale_notification_email_recipient']) && !empty($_POST['sale_notification_email_recipient'])){
                $sale_notification_email_recipient = sanitize_text_field($_POST['sale_notification_email_recipient']);
            }
            $sale_notification_email_subject = '';
            if(isset($_POST['sale_notification_email_subject']) && !empty($_POST['sale_notification_email_subject'])){
                $sale_notification_email_subject = sanitize_text_field($_POST['sale_notification_email_subject']);
            }
            $sale_notification_email_type = '';
            if(isset($_POST['sale_notification_email_type']) && !empty($_POST['sale_notification_email_type'])){
                $sale_notification_email_type = sanitize_text_field($_POST['sale_notification_email_type']);
            }
            $sale_notification_email_body = '';
            if(isset($_POST['sale_notification_email_body']) && !empty($_POST['sale_notification_email_body'])){
                $sale_notification_email_body = wp_kses_post($_POST['sale_notification_email_body']);
            }
            $paypal_options = array();
            $paypal_options['email_from_name'] = $email_from_name;
            $paypal_options['email_from_address'] = $email_from_address;
            $paypal_options['purchase_email_enabled'] = $purchase_email_enabled;
            $paypal_options['purchase_email_subject'] = $purchase_email_subject;
            $paypal_options['purchase_email_type'] = $purchase_email_type;
            $paypal_options['purchase_email_body'] = $purchase_email_body;
            $paypal_options['sale_notification_email_enabled'] = $sale_notification_email_enabled;
            $paypal_options['sale_notification_email_recipient'] = $sale_notification_email_recipient;
            $paypal_options['sale_notification_email_subject'] = $sale_notification_email_subject;
            $paypal_options['sale_notification_email_type'] = $sale_notification_email_type;
            $paypal_options['sale_notification_email_body'] = $sale_notification_email_body;
            wp_paypal_update_email_option($paypal_options);
            echo '<div id="message" class="updated fade"><p><strong>';
            echo __('Settings Saved', 'wp-paypal').'!';
            echo '</strong></p></div>';
        }
        
        $paypal_options = wp_paypal_get_email_option();
        
        $email_tags_url = "https://wphowto.net/wordpress-paypal-plugin-732";
        $email_tags_link = sprintf(__('You can find the full list of available email tags <a target="_blank" href="%s">here</a>.', 'wp-paypal'), esc_url($email_tags_url));
        $allowed_html_tags = array(
            'a' => array(
                'href' => array(),
                'title' => array()
            )
        );
        ?>
        <table class="wppaypal-email-settings-table">
            <tbody>
                <tr>
                    <td valign="top">
                        <form method="post" action="">
                            <?php wp_nonce_field('wp_paypal_email_settings_nonce'); ?>

                            <h2><?php _e('Email Sender Options', 'wp-paypal');?></h2>
                            <table class="form-table">
                                <tbody>                   
                                    <tr valign="top">
                                        <th scope="row"><label for="email_from_name"><?php _e('From Name', 'wp-paypal');?></label></th>
                                        <td><input name="email_from_name" type="text" id="email_from_name" value="<?php echo esc_attr($paypal_options['email_from_name']); ?>" class="regular-text">
                                            <p class="description"><?php _e('The sender name that appears in outgoing emails. Leave empty to use the default.', 'wp-paypal');?></p></td>
                                    </tr>                
                                    <tr valign="top">
                                        <th scope="row"><label for="email_from_address"><?php _e('From Email Address', 'wp-paypal');?></label></th>
                                        <td><input name="email_from_address" type="text" id="email_from_address" value="<?php echo esc_attr($paypal_options['email_from_address']); ?>" class="regular-text">
                                            <p class="description"><?php _e('The sender email that appears in outgoing emails. Leave empty to use the default.', 'wp-paypal');?></p></td>
                                    </tr>
                                </tbody>
                            </table>
                            <h2><?php _e('Purchase Receipt Email', 'wp-paypal');?></h2>
                            <p><?php _e('A purchase receipt email is sent to the customer after completion of a successful purchase', 'wp-paypal');?></p>
                            <table class="form-table">
                                <tbody>
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Enable/Disable', 'wp-paypal');?></th>
                                        <td> <fieldset><legend class="screen-reader-text"><span>Enable/Disable</span></legend><label for="purchase_email_enabled">
                                                    <input name="purchase_email_enabled" type="checkbox" id="purchase_email_enabled" <?php if ($paypal_options['purchase_email_enabled'] == '1') echo ' checked="checked"'; ?> value="1">
                                                    <?php _e('Enable this email notification', 'wp-paypal');?></label>
                                            </fieldset></td>
                                    </tr>                   
                                    <tr valign="top">
                                        <th scope="row"><label for="purchase_email_subject"><?php _e('Subject', 'wp-paypal');?></label></th>
                                        <td><input name="purchase_email_subject" type="text" id="purchase_email_subject" value="<?php echo esc_attr($paypal_options['purchase_email_subject']); ?>" class="regular-text">
                                            <p class="description"><?php _e('The subject line for the purchase receipt email.', 'wp-paypal');?></p></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="purchase_email_type"><?php _e('Email Type', 'wp-paypal');?></label></th>
                                        <td>
                                        <select name="purchase_email_type" id="purchase_email_type">
                                            <option <?php echo ($paypal_options['purchase_email_type'] === 'plain')?'selected="selected"':'';?> value="plain"><?php _e('Plain Text', 'wp-paypal')?></option>
                                            <option <?php echo ($paypal_options['purchase_email_type'] === 'html')?'selected="selected"':'';?> value="html"><?php _e('HTML', 'wp-paypal')?></option>
                                        </select>
                                        <p class="description"><?php _e('The content type of the purchase receipt email.', 'wp-paypal')?></p>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="purchase_email_body"><?php _e('Email Body', 'wp-paypal');?></label></th>
                                        <td><?php wp_editor($paypal_options['purchase_email_body'], 'purchase_email_body', array('textarea_name' => 'purchase_email_body'));?>
                                            <p class="description"><?php echo __('The main content of the purchase receipt email.', 'wp-paypal').' '.wp_kses($email_tags_link, $allowed_html_tags);?></p></td>
                                    </tr>
                                </tbody>
                            </table>
                            <h2><?php _e('Sale Notification Email', 'wp-paypal');?></h2>
                            <p><?php _e('A sale notification email is sent to the chosen recipient after completion of a successful purchase', 'wp-paypal');?></p>
                            <table class="form-table">
                                <tbody>
                                    <tr valign="top">
                                        <th scope="row"><?php _e('Enable/Disable', 'wp-paypal');?></th>
                                        <td> <fieldset><legend class="screen-reader-text"><span>Enable/Disable</span></legend><label for="sale_notification_email_enabled">
                                                    <input name="sale_notification_email_enabled" type="checkbox" id="sale_notification_email_enabled" <?php if ($paypal_options['sale_notification_email_enabled'] == '1') echo ' checked="checked"'; ?> value="1">
                                                    <?php _e('Enable this email notification', 'wp-paypal');?></label>
                                            </fieldset></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="sale_notification_email_recipient"><?php _e('Recipient', 'wp-paypal');?></label></th>
                                        <td><input name="sale_notification_email_recipient" type="text" id="sale_notification_email_recipient" value="<?php echo esc_attr($paypal_options['sale_notification_email_recipient']); ?>" class="regular-text">
                                            <p class="description"><?php _e('The email address that should receive a notification anytime a sale is made. Multiple recipients can be specified by separating the addresses with a comma.', 'wp-paypal');?></p></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="sale_notification_email_subject"><?php _e('Subject', 'wp-paypal');?></label></th>
                                        <td><input name="sale_notification_email_subject" type="text" id="sale_notification_email_subject" value="<?php echo esc_attr($paypal_options['sale_notification_email_subject']); ?>" class="regular-text">
                                            <p class="description"><?php _e('The subject line for the sale notification email.', 'wp-paypal');?></p></td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="sale_notification_email_type"><?php _e('Email Type', 'wp-paypal');?></label></th>
                                        <td>
                                        <select name="sale_notification_email_type" id="sale_notification_email_type">
                                            <option <?php echo ($paypal_options['sale_notification_email_type'] === 'plain')?'selected="selected"':'';?> value="plain"><?php _e('Plain Text', 'wp-paypal')?></option>
                                            <option <?php echo ($paypal_options['sale_notification_email_type'] === 'html')?'selected="selected"':'';?> value="html"><?php _e('HTML', 'wp-paypal')?></option>
                                        </select>
                                        <p class="description"><?php _e('The content type of the sale notification email.', 'wp-paypal')?></p>
                                        </td>
                                    </tr>
                                    <tr valign="top">
                                        <th scope="row"><label for="sale_notification_email_body"><?php _e('Email Body', 'wp-paypal');?></label></th>
                                        <td><?php wp_editor($paypal_options['sale_notification_email_body'], 'sale_notification_email_body', array('textarea_name' => 'sale_notification_email_body'));?>
                                            <p class="description"><?php echo __('The main content of the sale notification email.', 'wp-paypal').' '.wp_kses($email_tags_link, $allowed_html_tags);?></p></td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <p class="submit"><input type="submit" name="wp_paypal_update_email_settings" id="wp_paypal_update_email_settings" class="button button-primary" value="<?php _e('Save Changes', 'wp-paypal');?>"></p></form>
                    </td>
                    <td valign="top" style="width: 300px">
                        <div style="background: #ffc; border: 1px solid #333; margin: 2px; padding: 3px 15px">
                        <h3><?php _e('Need More Features?', 'wp-paypal')?></h3>
                        <ol>
                        <li><?php printf(__('Check out the <a href="%s">plugin add-ons</a>.', 'wp-paypal'), 'edit.php?post_type=wp_paypal_order&page=wp-paypal-addons');?></li>
                        </ol>    
                        <h3><?php _e('Need Help?', 'wp-paypal')?></h3>
                        <ol>
                        <li><?php printf(__('Use the <a href="%s">Debug</a> menu for diagnostics.', 'wp-paypal'), 'edit.php?post_type=wp_paypal_order&page=wp-paypal-debug');?></li>
                        <li><?php printf(__('Check out the <a target="_blank" href="%s">support forum</a> and <a target="_blank" href="%s">FAQ</a>.', 'wp-paypal'), 'https://wordpress.org/support/plugin/wp-paypal/', 'https://wordpress.org/plugins/wp-paypal/#faq');?></li>
                        <li><?php printf(__('Visit the <a target="_blank" href="%s">plugin homepage</a>.', 'wp-paypal'), 'https://wphowto.net/wordpress-paypal-plugin-732');?></li>
                        </ol>
                        <h3><?php _e('Rate This Plugin', 'wp-paypal')?></h3>
                        <p><?php printf(__('Please <a target="_blank" href="%s">rate us</a> and give feedback.', 'wp-paypal'), 'https://wordpress.org/support/plugin/wp-paypal/reviews?rate=5#new-post');?></p>
                        </div>
                    </td>
                </tr>
            </tbody> 
        </table>
        <?php
    }

    function debug_page() {
        ?>
        <div class="wrap">
            <h2><?php _e('WP PayPal Debug Log', 'wp-paypal');?></h2>
            <div id="poststuff">
                <div id="post-body">
                    <?php
                    if (isset($_POST['wp_paypal_update_log_settings'])) {
                        $nonce = $_REQUEST['_wpnonce'];
                        if (!wp_verify_nonce($nonce, 'wp_paypal_debug_log_settings')) {
                            wp_die('Error! Nonce Security Check Failed! please save the settings again.');
                        }
                        update_option('wp_paypal_enable_debug', (isset($_POST["enable_debug"]) && $_POST["enable_debug"] == '1') ? '1' : '');
                        echo '<div id="message" class="updated fade"><p>'.__('Settings Saved', 'wp-paypal').'!</p></div>';
                    }
                    if (isset($_POST['wp_paypal_reset_log'])) {
                        $nonce = $_REQUEST['_wpnonce'];
                        if (!wp_verify_nonce($nonce, 'wp_paypal_reset_log_settings')) {
                            wp_die('Error! Nonce Security Check Failed! please save the settings again.');
                        }
                        if (wp_paypal_reset_log()) {
                            echo '<div id="message" class="updated fade"><p>'.__('Debug log file has been reset', 'wp-paypal').'!</p></div>';
                        } else {
                            echo '<div id="message" class="error"><p>'.__('Debug log file could not be reset', 'wp-paypal').'!</p></div>';
                        }
                    }
                    $log_file = WP_PAYPAL_DEBUG_LOG_PATH;
                    $content = '';
                    if(file_exists($log_file))
                    {
                        $content = file_get_contents($log_file);
                    }
                    ?>
                    <div id="template"><textarea cols="70" rows="25" name="wp_paypal_log" id="wp_paypal_log"><?php echo esc_textarea($content); ?></textarea></div>                     
                    <form method="post" action="">
                        <?php wp_nonce_field('wp_paypal_debug_log_settings'); ?>
                        <table class="form-table">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row"><?php _e('Enable Debug', 'wp-paypal');?></th>
                                    <td> <fieldset><legend class="screen-reader-text"><span>Enable Debug</span></legend><label for="enable_debug">
                                                <input name="enable_debug" type="checkbox" id="enable_debug" <?php if (get_option('wp_paypal_enable_debug') == '1') echo ' checked="checked"'; ?> value="1">
                                                <?php _e('Check this option if you want to enable debug', 'wp-paypal');?></label>
                                        </fieldset></td>
                                </tr>

                            </tbody>

                        </table>
                        <p class="submit"><input type="submit" name="wp_paypal_update_log_settings" id="wp_paypal_update_log_settings" class="button button-primary" value="<?php _e('Save Changes', 'wp-paypal');?>"></p>
                    </form>
                    <form method="post" action="">
                        <?php wp_nonce_field('wp_paypal_reset_log_settings'); ?>                            
                        <p class="submit"><input type="submit" name="wp_paypal_reset_log" id="wp_paypal_reset_log" class="button" value="<?php _e('Reset Log', 'wp-paypal');?>"></p>
                    </form>
                </div>         
            </div>
        </div>
        <?php
    }

}

$GLOBALS['wp_paypal'] = new WP_PAYPAL();

function wp_paypal_button_handler($atts) {
    $atts = array_map('sanitize_text_field', $atts);
    $testmode = get_option('wp_paypal_enable_testmode');
    if (isset($testmode) && !empty($testmode)) {
        $atts['env'] = "sandbox";
    }
    $notify_url = home_url() . '/?wp_paypal_ipn=1';
    if(!isset($atts['notify_url']) || empty($atts['notify_url'])){
        $atts['notify_url'] = $notify_url;
    }
    $paypal_email = get_option('wp_paypal_email');
    $currency = get_option('wp_paypal_currency_code');
    if (isset($atts['currency']) && !empty($atts['currency'])) {
        
    } else {
        $atts['currency'] = $currency;
    }
    
    if(isset($atts['button']) && $atts['button']=="cart"){ 
        $button_code = wp_paypal_get_add_to_cart_button($atts);
    }
    else if(isset($atts['button']) && $atts['button']=="viewcart"){
        $button_code = wp_paypal_get_view_cart_button($atts);
    }
    else if(isset($atts['button']) && $atts['button']=="buynow"){ 
        $button_code = wp_paypal_get_buy_now_button($atts);
    }
    else if(isset($atts['button']) && $atts['button']=="donate"){
        $button_code = wp_paypal_get_donate_button($atts);
    }
    else if(isset($atts['button']) && $atts['button']=="subscribe"){
        $button_code = wp_paypal_get_subscribe_button($atts);
    }
    else{
        $button_code = __('Please enter a correct button type', 'wp-paypal');
    }
    return $button_code;
}

function wp_paypal_get_add_to_cart_button($atts){
    $button_code = '';
    $action_url = 'https://www.paypal.com/cgi-bin/webscr';
    if(isset($atts['env']) && $atts['env'] == "sandbox"){
        $action_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    }
    $method = 'post';
    $amp = false;
    if(function_exists('amp_is_request') && amp_is_request()){
        $method = 'get';
        $amp = true;
    }
    $target = '';
    if(isset($atts['target']) && !empty($atts['target'])) {
        $target = 'target="'.esc_attr($atts['target']).'" ';
    }
    $form_class = '';
    if(isset($atts['form_class']) && !empty($atts['form_class'])) {
        $form_class = 'class="'.esc_attr($atts['form_class']).'" ';
    }
    $button_code .= '<form '.$form_class.$target.'action="'.esc_url($action_url).'" method="'.$method.'" >';
    $button_code .= '<input type="hidden" name="charset" value="utf-8">';
    $button_code .= '<input type="hidden" name="cmd" value="_cart">';
    $button_code .= '<input type="hidden" name="add" value="1">';
    $business = '';
    $paypal_merchant_id = get_option('wp_paypal_merchant_id');
    $paypal_email = get_option('wp_paypal_email');
    if(isset($paypal_merchant_id) && !empty($paypal_merchant_id)) {
        $business = $paypal_merchant_id;
    }
    else if(isset($paypal_email) && !empty($paypal_email)) {
        $business = $paypal_email;
    }
    //
    if(isset($atts['business']) && !empty($atts['business'])) {
        $business = $atts['business'];
    }
    //
    if(isset($business) && !empty($business)) {
        $button_code .= '<input type="hidden" name="business" value="'.esc_attr($business).'">';
    }
    if(isset($atts['lc']) && !empty($atts['lc'])) {
        $lc = $atts['lc'];
        $button_code .= '<input type="hidden" name="lc" value="'.esc_attr($lc).'">';
    }
    if(isset($atts['name']) && !empty($atts['name'])) {
        $name = $atts['name'];
        $button_code .= '<input type="hidden" name="item_name" value="'.esc_attr($name).'">';
    }
    if(isset($atts['item_number']) && !empty($atts['item_number'])) {
        $item_number = $atts['item_number'];
        $button_code .= '<input type="hidden" name="item_number" value="'.esc_attr($item_number).'">';
    }
    if(isset($atts['amount']) && is_numeric($atts['amount'])) {
        $amount = $atts['amount'];
        $button_code .= '<input type="hidden" name="amount" value="'.esc_attr($amount).'">';
    }
    if(isset($atts['currency']) && !empty($atts['currency'])) {
        $currency = $atts['currency'];
        $button_code .= '<input type="hidden" name="currency_code" value="'.esc_attr($currency).'">';
    }
    $button_code .= '<input type="hidden" name="button_subtype" value="products">';
    $no_note = 0; //default
    if(isset($atts['no_note']) && is_numeric($atts['no_note'])) {
        $no_note = $atts['no_note'];
        $button_code .= '<input type="hidden" name="no_note" value="'.esc_attr($no_note).'">';
    }
    if(isset($atts['cn']) && !empty($atts['cn'])) {
        $cn = $atts['cn'];
        $button_code .= '<input type="hidden" name="cn" value="'.esc_attr($cn).'">';
    }
    $no_shipping = 0; //default
    if(isset($atts['no_shipping']) && is_numeric($atts['no_shipping'])) {
        $no_shipping = $atts['no_shipping'];
        $button_code .= '<input type="hidden" name="no_shipping" value="'.esc_attr($no_shipping).'">';
    }
    if(isset($atts['shipping']) && is_numeric($atts['shipping'])) {
        $shipping = $atts['shipping'];
        $button_code .= '<input type="hidden" name="shipping" value="'.esc_attr($shipping).'">';
    }
    if(isset($atts['shipping2']) && is_numeric($atts['shipping2'])) {
        $shipping2 = $atts['shipping2'];
        $button_code .= '<input type="hidden" name="shipping2" value="'.esc_attr($shipping2).'">';
    }
    if(isset($atts['tax']) && is_numeric($atts['tax'])) {
        $tax = $atts['tax'];
        $button_code .= '<input type="hidden" name="tax" value="'.esc_attr($tax).'">';
    }
    if(isset($atts['tax_rate']) && is_numeric($atts['tax_rate'])) {
        $tax_rate = $atts['tax_rate'];
        $button_code .= '<input type="hidden" name="tax_rate" value="'.esc_attr($tax_rate).'">';
    }
    if(isset($atts['handling']) && is_numeric($atts['handling'])) {
        $handling = $atts['handling'];
        $button_code .= '<input type="hidden" name="handling" value="'.esc_attr($handling).'">';
    }
    if(isset($atts['weight']) && is_numeric($atts['weight'])) {
        $weight = $atts['weight'];
        $button_code .= '<input type="hidden" name="weight" value="'.esc_attr($weight).'">';
    }
    if(isset($atts['weight_unit']) && !empty($atts['weight_unit'])) {
        $weight_unit = $atts['weight_unit'];
        $button_code .= '<input type="hidden" name="weight_unit" value="'.esc_attr($weight_unit).'">';
    }
    if(isset($atts['shopping_url']) && filter_var($atts['shopping_url'], FILTER_VALIDATE_URL)){
        $shopping_url = $atts['shopping_url'];
        $button_code .= '<input type="hidden" name="shopping_url" value="'.esc_attr($shopping_url).'">';
    }
    if(isset($atts['return']) && filter_var($atts['return'], FILTER_VALIDATE_URL)){
        $return = $atts['return'];
        $button_code .= '<input type="hidden" name="return" value="'.esc_attr($return).'">';
    }
    if(isset($atts['cancel_return']) && filter_var($atts['cancel_return'], FILTER_VALIDATE_URL)){
        $cancel_return = $atts['cancel_return'];
        $button_code .= '<input type="hidden" name="cancel_return" value="'.esc_attr($cancel_return).'">';
    }
    if(isset($atts['notify_url']) && !empty($atts['notify_url'])) {
        $notify_url = $atts['notify_url'];
        $button_code .= '<input type="hidden" name="notify_url" value="'.esc_attr($notify_url).'">';
    }
    $button_code = apply_filters('wppaypal_variations', $button_code, $atts);
    if(strpos($button_code, 'Error') !== false){
        return $button_code;
    }
    $custom_input_code = '';
    $custom_input_code = apply_filters('wppaypal_custom_input', $custom_input_code, $button_code, $atts);
    if(!empty($custom_input_code)){
        $button_code .= $custom_input_code;
    }
    else if(isset($atts['custom']) && !empty($atts['custom'])) {
        $custom = $atts['custom'];
        $button_code .= '<input type="hidden" name="custom" value="'.esc_attr($custom).'">';
    }
    $button_code .= '<input type="hidden" name="bn" value="WPPayPal_AddToCart_WPS_US">';
    $button_image_url = WP_PAYPAL_URL.'/images/add-to-cart.png';
    if(isset($atts['button_image']) && filter_var($atts['button_image'], FILTER_VALIDATE_URL)){
        $button_image_url = $atts['button_image'];
    }
    //$button_code .= '<input type="image" src="'.esc_url($button_image_url).'" border="0" name="submit">';
    $button_submit_code = '<input type="image" src="'.esc_url($button_image_url).'" border="0" name="submit">';
    $button_text = 'Add to Cart';
    if(isset($atts['button_text']) && !empty($atts['button_text'])){
        $button_text = $atts['button_text'];
        $button_submit_code = '<input type="submit" value="'.esc_attr($button_text).'">';
    }
    //
    if($amp){
        $button_submit_code = '<input type="submit" value="'.esc_attr($button_text).'">';
    }
    //
    $button_code .= $button_submit_code;
    $button_code .= '</form>';
    return $button_code;        
}

function wp_paypal_get_view_cart_button($atts){
    $button_code = '';
    $action_url = 'https://www.paypal.com/cgi-bin/webscr';
    if(isset($atts['env']) && $atts['env'] == "sandbox"){
        $action_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    }
    $method = 'post';
    $amp = false;
    if(function_exists('amp_is_request') && amp_is_request()){
        $method = 'get';
        $amp = true;
    }
    $target = '';
    if(isset($atts['target']) && !empty($atts['target'])) {
        $target = 'target="'.esc_attr($atts['target']).'" ';
    }
    $form_class = '';
    if(isset($atts['form_class']) && !empty($atts['form_class'])) {
        $form_class = 'class="'.esc_attr($atts['form_class']).'" ';
    }
    $button_code .= '<form '.$form_class.$target.'action="'.esc_url($action_url).'" method="'.$method.'" >';
    $button_code .= '<input type="hidden" name="charset" value="utf-8">';
    $button_code .= '<input type="hidden" name="cmd" value="_cart">';
    $button_code .= '<input type="hidden" name="display" value="1">';
    if(isset($atts['shopping_url']) && !empty($atts['shopping_url'])) {
        $shopping_url = $atts['shopping_url'];
        $button_code .= '<input type="hidden" name="shopping_url" value="'.esc_attr($shopping_url).'">';
    }
    $business = '';
    $paypal_merchant_id = get_option('wp_paypal_merchant_id');
    $paypal_email = get_option('wp_paypal_email');
    if(isset($paypal_merchant_id) && !empty($paypal_merchant_id)) {
        $business = $paypal_merchant_id;
    }
    else if(isset($paypal_email) && !empty($paypal_email)) {
        $business = $paypal_email;
    }
    
    if(isset($business) && !empty($business)) {
        $button_code .= '<input type="hidden" name="business" value="'.esc_attr($business).'">';
    }
    $button_image_url = WP_PAYPAL_URL.'/images/view-cart.png';
    if(isset($atts['button_image']) && filter_var($atts['button_image'], FILTER_VALIDATE_URL)){
        $button_image_url = $atts['button_image'];
    }
    //$button_code .= '<input type="image" src="'.esc_url($button_image_url).'" border="0" name="submit">';
    $button_submit_code = '<input type="image" src="'.esc_url($button_image_url).'" border="0" name="submit">';
    $button_text = 'View Cart';
    if(isset($atts['button_text']) && !empty($atts['button_text'])){
        $button_text = $atts['button_text'];
        $button_submit_code = '<input type="submit" value="'.esc_attr($button_text).'">';
    }
    //
    if($amp){
        $button_submit_code = '<input type="submit" value="'.esc_attr($button_text).'">';
    }
    //
    $button_code .= $button_submit_code;
    $button_code .= '</form>';
    return $button_code;        
}

function wp_paypal_get_buy_now_button($atts){
    $button_code = '';
    $action_url = 'https://www.paypal.com/cgi-bin/webscr';
    if(isset($atts['env']) && $atts['env'] == "sandbox"){
        $action_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    }
    $method = 'post';
    $amp = false;
    if(function_exists('amp_is_request') && amp_is_request()){
        $method = 'get';
        $amp = true;
    }
    $target = '';
    if(isset($atts['target']) && !empty($atts['target'])) {
        $target = 'target="'.esc_attr($atts['target']).'" ';
    }
    $form_class = '';
    if(isset($atts['form_class']) && !empty($atts['form_class'])) {
        $form_class = 'class="'.esc_attr($atts['form_class']).'" ';
    }
    $button_code .= '<form '.$form_class.$target.'action="'.esc_url($action_url).'" method="'.$method.'" >';
    $button_code .= '<input type="hidden" name="charset" value="utf-8">';
    $button_code .= '<input type="hidden" name="cmd" value="_xclick">';
    $business = '';
    $paypal_merchant_id = get_option('wp_paypal_merchant_id');
    $paypal_email = get_option('wp_paypal_email');
    if(isset($paypal_merchant_id) && !empty($paypal_merchant_id)) {
        $business = $paypal_merchant_id;
    }
    else if(isset($paypal_email) && !empty($paypal_email)) {
        $business = $paypal_email;
    }
    //
    if(isset($atts['business']) && !empty($atts['business'])) {
        $business = $atts['business'];
    }
    //
    $price_variation = false;
    if(isset($atts['os0_amount0']) && is_numeric($atts['os0_amount0'])) {
        $price_variation = true;
    }
    if(isset($business) && !empty($business)) {
        $button_code .= '<input type="hidden" name="business" value="'.esc_attr($business).'">';
    }
    if(isset($atts['lc']) && !empty($atts['lc'])) {
        $lc = $atts['lc'];
        $button_code .= '<input type="hidden" name="lc" value="'.esc_attr($lc).'">';
    }
    /* product name sent via URL */
    if(isset($_GET['wppp_name']) && !empty($_GET['wppp_name']))
    {
        $atts['name'] = sanitize_text_field(stripslashes($_GET['wppp_name']));
    }
    /* end */
    if(isset($atts['name']) && !empty($atts['name'])) {
        $name = $atts['name'];
        $button_code .= '<input type="hidden" name="item_name" value="'.esc_attr($name).'">';
    }
    if(isset($atts['item_number']) && !empty($atts['item_number'])) {
        $item_number = $atts['item_number'];
        $button_code .= '<input type="hidden" name="item_number" value="'.esc_attr($item_number).'">';
    }
    if(!$price_variation){
        $amount_input_code = '';
        $amount_input_code = apply_filters('wppaypal_buynow_custom_amount', $amount_input_code, $button_code, $atts);
        if(!empty($amount_input_code)){
            $button_code .= $amount_input_code;
        }
        else{
            /* product amount sent via URL */
            if(isset($_GET['wppp_amount']) && !empty($_GET['wppp_amount']))
            {
                $atts['amount'] = sanitize_text_field($_GET['wppp_amount']);
            }
            /* end */
            if(isset($atts['amount']) && is_numeric($atts['amount']) && $atts['amount'] > 0) {
                $amount = $atts['amount'];
                $button_code .= '<input type="hidden" name="amount" value="'.esc_attr($amount).'">';
            }
            else{
                $error = __('Amount cannot be empty', 'wp-paypal');
                return $error;
            }
        }
    }
    if(isset($atts['currency']) && !empty($atts['currency'])) {
        $currency = $atts['currency'];
        $button_code .= '<input type="hidden" name="currency_code" value="'.esc_attr($currency).'">';
    }
    $no_shipping = 0; //default
    if(isset($atts['no_shipping']) && is_numeric($atts['no_shipping'])) {
        $no_shipping = $atts['no_shipping'];
        $button_code .= '<input type="hidden" name="no_shipping" value="'.esc_attr($no_shipping).'">';
    }
    if(isset($atts['shipping']) && is_numeric($atts['shipping'])) {
        $shipping = $atts['shipping'];
        $button_code .= '<input type="hidden" name="shipping" value="'.esc_attr($shipping).'">';
    }
    if(isset($atts['shipping2']) && is_numeric($atts['shipping2'])) {
        $shipping2 = $atts['shipping2'];
        $button_code .= '<input type="hidden" name="shipping2" value="'.esc_attr($shipping2).'">';
    }
    if(isset($atts['tax']) && is_numeric($atts['tax'])) {
        $tax = $atts['tax'];
        $button_code .= '<input type="hidden" name="tax" value="'.esc_attr($tax).'">';
    }
    if(isset($atts['tax_rate']) && is_numeric($atts['tax_rate'])) {
        $tax_rate = $atts['tax_rate'];
        $button_code .= '<input type="hidden" name="tax_rate" value="'.esc_attr($tax_rate).'">';
    }
    $button_code = apply_filters('wppaypal_enable_buynow_discount', $button_code, $atts);
    if(isset($atts['handling']) && is_numeric($atts['handling'])) {
        $handling = $atts['handling'];
        $button_code .= '<input type="hidden" name="handling" value="'.esc_attr($handling).'">';
    }
    if(isset($atts['quantity']) && empty($atts['quantity'])) {
        $quantity_input_code = '';
        $quantity_input_code = apply_filters('wppaypal_variable_quantity', $quantity_input_code, $button_code, $atts);
        if(!empty($quantity_input_code)){
            $button_code .= $quantity_input_code;
        }
    }
    if(isset($atts['undefined_quantity']) && is_numeric($atts['undefined_quantity'])) {
        $undefined_quantity = $atts['undefined_quantity'];
        $button_code .= '<input type="hidden" name="undefined_quantity" value="'.esc_attr($undefined_quantity).'">';
    }
    if(isset($atts['weight']) && is_numeric($atts['weight'])) {
        $weight = $atts['weight'];
        $button_code .= '<input type="hidden" name="weight" value="'.esc_attr($weight).'">';
    }
    if(isset($atts['weight_unit']) && !empty($atts['weight_unit'])) {
        $weight_unit = $atts['weight_unit'];
        $button_code .= '<input type="hidden" name="weight_unit" value="'.esc_attr($weight_unit).'">';
    }
    if(isset($atts['return']) && filter_var($atts['return'], FILTER_VALIDATE_URL)){
        $return = $atts['return'];
        $button_code .= '<input type="hidden" name="return" value="'.esc_attr($return).'">';
    }
    if(isset($atts['cancel_return']) && filter_var($atts['cancel_return'], FILTER_VALIDATE_URL)){
        $cancel_return = $atts['cancel_return'];
        $button_code .= '<input type="hidden" name="cancel_return" value="'.esc_attr($cancel_return).'">';
    }
    if(isset($atts['notify_url']) && !empty($atts['notify_url'])) {
        $notify_url = $atts['notify_url'];
        $button_code .= '<input type="hidden" name="notify_url" value="'.esc_attr($notify_url).'">';
    }
    $button_code = apply_filters('wppaypal_variations', $button_code, $atts);
    if(strpos($button_code, 'Error') !== false){
        return $button_code;
    }
    $custom_input_code = '';
    $custom_input_code = apply_filters('wppaypal_custom_input', $custom_input_code, $button_code, $atts);
    if(!empty($custom_input_code)){
        $button_code .= $custom_input_code;
    }
    else if(isset($atts['custom']) && !empty($atts['custom'])) {
        $custom = $atts['custom'];
        $button_code .= '<input type="hidden" name="custom" value="'.esc_attr($custom).'">';
    }
    $button_code .= '<input type="hidden" name="bn" value="WPPayPal_BuyNow_WPS_US">';
    $button_image_url = WP_PAYPAL_URL.'/images/buy-now.png';
    if(isset($atts['button_image']) && filter_var($atts['button_image'], FILTER_VALIDATE_URL)){
        $button_image_url = $atts['button_image'];
    }
    //$button_code .= '<input type="image" src="'.esc_url($button_image_url).'" border="0" name="submit">';
    $button_submit_code = '<input type="image" src="'.esc_url($button_image_url).'" border="0" name="submit">';
    $button_text = 'Buy Now';
    if(isset($atts['button_text']) && !empty($atts['button_text'])){
        $button_text = $atts['button_text'];
        $button_submit_code = '<input type="submit" value="'.esc_attr($button_text).'">';
    }
    //
    if($amp){
        $button_submit_code = '<input type="submit" value="'.esc_attr($button_text).'">';
    }
    //
    $button_code .= $button_submit_code;
    $button_code .= '</form>';
    return $button_code;        
}

function wp_paypal_get_donate_button($atts){
    $button_code = '';
    $action_url = 'https://www.paypal.com/cgi-bin/webscr';
    if(isset($atts['env']) && $atts['env'] == "sandbox"){
        $action_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    }
    $method = 'post';
    $amp = false;
    if(function_exists('amp_is_request') && amp_is_request()){
        $method = 'get';
        $amp = true;
    }
    $target = '';
    if(isset($atts['target']) && !empty($atts['target'])) {
        $target = 'target="'.esc_attr($atts['target']).'" ';
    }
    $form_class = '';
    if(isset($atts['form_class']) && !empty($atts['form_class'])) {
        $form_class = 'class="'.esc_attr($atts['form_class']).'" ';
    }
    $button_code .= '<form '.$form_class.$target.'action="'.esc_url($action_url).'" method="'.$method.'" >';
    $button_code .= '<input type="hidden" name="charset" value="utf-8">';
    $button_code .= '<input type="hidden" name="cmd" value="_donations">';
    $business = '';
    $paypal_merchant_id = get_option('wp_paypal_merchant_id');
    $paypal_email = get_option('wp_paypal_email');
    if(isset($paypal_merchant_id) && !empty($paypal_merchant_id)) {
        $business = $paypal_merchant_id;
    }
    else if(isset($paypal_email) && !empty($paypal_email)) {
        $business = $paypal_email;
    }
    //
    if(isset($atts['business']) && !empty($atts['business'])) {
        $business = $atts['business'];
    }
    //
    if(isset($business) && !empty($business)) {
        $button_code .= '<input type="hidden" name="business" value="'.esc_attr($business).'">';
    }
    if(isset($atts['lc']) && !empty($atts['lc'])) {
        $lc = $atts['lc'];
        $button_code .= '<input type="hidden" name="lc" value="'.esc_attr($lc).'">';
    }
    if(isset($atts['name']) && !empty($atts['name'])) {
        $name = $atts['name'];
        $button_code .= '<input type="hidden" name="item_name" value="'.esc_attr($name).'">';
    }
    if(isset($atts['item_number']) && !empty($atts['item_number'])) {
        $item_number = $atts['item_number'];
        $button_code .= '<input type="hidden" name="item_number" value="'.esc_attr($item_number).'">';
    }
    //
    $amount_input_code = '';
    $amount_input_code = apply_filters('wppaypal_custom_donations', $amount_input_code, $button_code, $atts);
    if(!empty($amount_input_code)){
        $button_code .= $amount_input_code;
    }
    else{
        if(isset($atts['amount']) && is_numeric($atts['amount']) && $atts['amount'] > 0) {
            $amount = $atts['amount'];
            $button_code .= '<input type="hidden" name="amount" value="'.esc_attr($amount).'">';
        }
    }
    //
    if(isset($atts['currency']) && !empty($atts['currency'])) {
        $currency = $atts['currency'];
        $button_code .= '<input type="hidden" name="currency_code" value="'.esc_attr($currency).'">';
    }
    $no_shipping = 0; //default
    if(isset($atts['no_shipping']) && is_numeric($atts['no_shipping'])) {
        $no_shipping = $atts['no_shipping'];
        $button_code .= '<input type="hidden" name="no_shipping" value="'.esc_attr($no_shipping).'">';
    }
    if(isset($atts['return']) && filter_var($atts['return'], FILTER_VALIDATE_URL)){
        $return = $atts['return'];
        $button_code .= '<input type="hidden" name="return" value="'.esc_attr($return).'">';
    }
    if(isset($atts['cancel_return']) && filter_var($atts['cancel_return'], FILTER_VALIDATE_URL)){
        $cancel_return = $atts['cancel_return'];
        $button_code .= '<input type="hidden" name="cancel_return" value="'.esc_attr($cancel_return).'">';
    }
    if(isset($atts['notify_url']) && !empty($atts['notify_url'])) {
        $notify_url = $atts['notify_url'];
        $button_code .= '<input type="hidden" name="notify_url" value="'.esc_attr($notify_url).'">';
    }
    $custom_input_code = '';
    $custom_input_code = apply_filters('wppaypal_custom_input', $custom_input_code, $button_code, $atts);
    if(!empty($custom_input_code)){
        $button_code .= $custom_input_code;
    }
    else if(isset($atts['custom']) && !empty($atts['custom'])) {
        $custom = $atts['custom'];
        $button_code .= '<input type="hidden" name="custom" value="'.esc_attr($custom).'">';
    }
    $button_code .= '<input type="hidden" name="bn" value="WPPayPal_Donate_WPS_US">';
    $button_image_url = WP_PAYPAL_URL.'/images/donate.png';
    if(isset($atts['button_image']) && filter_var($atts['button_image'], FILTER_VALIDATE_URL)){
        $button_image_url = $atts['button_image'];
    }
    //$button_code .= '<input type="image" src="'.esc_url($button_image_url).'" border="0" name="submit">';
    $button_submit_code = '<input type="image" src="'.esc_url($button_image_url).'" border="0" name="submit">';
    $button_text = 'Donate';
    if(isset($atts['button_text']) && !empty($atts['button_text'])){
        $button_text = $atts['button_text'];
        $button_submit_code = '<input type="submit" value="'.esc_attr($button_text).'">';
    }
    //
    if($amp){
        $button_submit_code = '<input type="submit" value="'.esc_attr($button_text).'">';
    }
    //
    $button_code .= $button_submit_code;
    $button_code .= '</form>';
    return $button_code;        
}

function wp_paypal_get_subscribe_button($atts){
    $button_code = '';
    $action_url = 'https://www.paypal.com/cgi-bin/webscr';
    if(isset($atts['env']) && $atts['env'] == "sandbox"){
        $action_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    }
    $method = 'post';
    $amp = false;
    if(function_exists('amp_is_request') && amp_is_request()){
        $method = 'get';
        $amp = true;
    }
    $target = '';
    if(isset($atts['target']) && !empty($atts['target'])) {
        $target = 'target="'.esc_attr($atts['target']).'" ';
    }
    $form_class = '';
    if(isset($atts['form_class']) && !empty($atts['form_class'])) {
        $form_class = 'class="'.esc_attr($atts['form_class']).'" ';
    }
    $button_code .= '<form '.$form_class.$target.'action="'.esc_url($action_url).'" method="'.$method.'" >';
    $button_code .= '<input type="hidden" name="charset" value="utf-8">';
    $button_code .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
    $business = '';
    $paypal_merchant_id = get_option('wp_paypal_merchant_id');
    $paypal_email = get_option('wp_paypal_email');
    if(isset($paypal_merchant_id) && !empty($paypal_merchant_id)) {
        $business = $paypal_merchant_id;
    }
    else if(isset($paypal_email) && !empty($paypal_email)) {
        $business = $paypal_email;
    }
    //
    if(isset($atts['business']) && !empty($atts['business'])) {
        $business = $atts['business'];
    }
    //
    if(isset($business) && !empty($business)) {
        $button_code .= '<input type="hidden" name="business" value="'.esc_attr($business).'">';
    }
    if(isset($atts['lc']) && !empty($atts['lc'])) {
        $lc = $atts['lc'];
        $button_code .= '<input type="hidden" name="lc" value="'.esc_attr($lc).'">';
    }
    if(isset($atts['name']) && !empty($atts['name'])) {
        $name = $atts['name'];
        $button_code .= '<input type="hidden" name="item_name" value="'.esc_attr($name).'">';
    }
    if(isset($atts['item_number']) && !empty($atts['item_number'])) {
        $item_number = $atts['item_number'];
        $button_code .= '<input type="hidden" name="item_number" value="'.esc_attr($item_number).'">';
    }
    if(isset($atts['a1']) && is_numeric($atts['a1'])) {
        $a1 = $atts['a1'];
        $button_code .= '<input type="hidden" name="a1" value="'.esc_attr($a1).'">';
        if(isset($atts['p1']) && is_numeric($atts['p1'])) {
            $p1 = $atts['p1'];
            $button_code .= '<input type="hidden" name="p1" value="'.esc_attr($p1).'">';
        }
        else{
            $button_code = __('Please enter a trial period duration p1', 'wp-paypal');
            return $button_code;
        }
        if(isset($atts['t1']) && !empty($atts['t1'])) {
            $t1 = $atts['t1'];
            $button_code .= '<input type="hidden" name="t1" value="'.esc_attr($t1).'">';
        }
        else{
            $button_code = __('Please enter a trial period units of duration t1', 'wp-paypal');
            return $button_code;
        }
    }
    if(isset($atts['a2']) && is_numeric($atts['a2'])) {
        $a2 = $atts['a2'];
        $button_code .= '<input type="hidden" name="a2" value="'.esc_attr($a2).'">';
        if(isset($atts['p2']) && is_numeric($atts['p2'])) {
            $p2 = $atts['p2'];
            $button_code .= '<input type="hidden" name="p2" value="'.esc_attr($p2).'">';
        }
        else{
            $button_code = __('Please enter a trial period 2 duration p2', 'wp-paypal');
            return $button_code;
        }
        if(isset($atts['t2']) && !empty($atts['t2'])) {
            $t2 = $atts['t2'];
            $button_code .= '<input type="hidden" name="t2" value="'.esc_attr($t2).'">';
        }
        else{
            $button_code = __('Please enter a trial period 2 units of duration t1', 'wp-paypal');
            return $button_code;
        }
    }
    $a3_input_code = '';
    $a3_input_code = apply_filters('wppaypal_subscribe_a3', $a3_input_code, $button_code, $atts);
    if(!empty($a3_input_code)){
        $button_code .= $a3_input_code;
    }
    else{
        if(isset($atts['a3']) && is_numeric($atts['a3']) && $atts['a3'] > 0) {
            $a3 = $atts['a3'];
            $button_code .= '<input type="hidden" name="a3" value="'.esc_attr($a3).'">';
        }
        else{
            $error = __('Please enter a regular subscription price a3', 'wp-paypal');
            return $error;
        }
    }
    if(isset($atts['p3']) && is_numeric($atts['p3'])) {
        $p3 = $atts['p3'];
        $button_code .= '<input type="hidden" name="p3" value="'.esc_attr($p3).'">';
    }
    else{
        $button_code = __('Please enter a subscription duration p3', 'wp-paypal');
        return $button_code;
    }
    if(isset($atts['t3']) && !empty($atts['t3'])) {
        $t3 = $atts['t3'];
        $button_code .= '<input type="hidden" name="t3" value="'.esc_attr($t3).'">';
    }
    else{
        $button_code = __('Please enter a subscription units of duration t3', 'wp-paypal');
        return $button_code;
    }
    if(isset($atts['src']) && is_numeric($atts['src'])) {
        $src = $atts['src'];
        $button_code .= '<input type="hidden" name="src" value="'.esc_attr($src).'">';
        if($src == '1'){
            if(isset($atts['srt']) && is_numeric($atts['srt'])) {
                $srt = $atts['srt'];
                $button_code .= '<input type="hidden" name="srt" value="'.esc_attr($srt).'">';
            }
        }
    }
    if(isset($atts['sra']) && is_numeric($atts['sra'])) {
        $sra = $atts['sra'];
        $button_code .= '<input type="hidden" name="sra" value="'.esc_attr($sra).'">';
    }
    if(isset($atts['currency']) && !empty($atts['currency'])) {
        $currency = $atts['currency'];
        $button_code .= '<input type="hidden" name="currency_code" value="'.esc_attr($currency).'">';
    }
    $button_code .= '<input type="hidden" name="no_note" value="1">'; //For Subscribe buttons, always set no_note to 1
    $no_shipping = 0; //default
    if(isset($atts['no_shipping']) && is_numeric($atts['no_shipping'])) {
        $no_shipping = $atts['no_shipping'];
        $button_code .= '<input type="hidden" name="no_shipping" value="'.esc_attr($no_shipping).'">';
    }
    if(isset($atts['return']) && filter_var($atts['return'], FILTER_VALIDATE_URL)){
        $return = $atts['return'];
        $button_code .= '<input type="hidden" name="return" value="'.esc_attr($return).'">';
    }
    if(isset($atts['cancel_return']) && filter_var($atts['cancel_return'], FILTER_VALIDATE_URL)){
        $cancel_return = $atts['cancel_return'];
        $button_code .= '<input type="hidden" name="cancel_return" value="'.esc_attr($cancel_return).'">';
    }
    if(isset($atts['notify_url']) && !empty($atts['notify_url'])) {
        $notify_url = $atts['notify_url'];
        $button_code .= '<input type="hidden" name="notify_url" value="'.esc_attr($notify_url).'">';
    }
    $custom_input_code = '';
    $custom_input_code = apply_filters('wppaypal_custom_input', $custom_input_code, $button_code, $atts);
    if(!empty($custom_input_code)){
        $button_code .= $custom_input_code;
    }
    else if(isset($atts['custom']) && !empty($atts['custom'])) {
        $custom = $atts['custom'];
        $button_code .= '<input type="hidden" name="custom" value="'.esc_attr($custom).'">';
    }
    $button_code .= '<input type="hidden" name="bn" value="WPPayPal_Subscribe_WPS_US">';
    $button_image_url = WP_PAYPAL_URL.'/images/subscribe.png';
    if(isset($atts['button_image']) && filter_var($atts['button_image'], FILTER_VALIDATE_URL)){
        $button_image_url = $atts['button_image'];
    }
    //$button_code .= '<input type="image" src="'.esc_url($button_image_url).'" border="0" name="submit">';
    $button_submit_code = '<input type="image" src="'.esc_url($button_image_url).'" border="0" name="submit">';
    $button_text = 'Subscribe';
    if(isset($atts['button_text']) && !empty($atts['button_text'])){
        $button_text = $atts['button_text'];
        $button_submit_code = '<input type="submit" value="'.esc_attr($button_text).'">';
    }
    //
    if($amp){
        $button_submit_code = '<input type="submit" value="'.esc_attr($button_text).'">';
    }
    //
    $button_code .= $button_submit_code;
    $button_code .= '</form>';
    return $button_code;        
}

function wp_paypal_get_email_option(){
    $options = get_option('wp_paypal_email_options');
    if(!is_array($options)){
        $options = wp_paypal_get_empty_email_options_array();
    }
    return $options;
}

function wp_paypal_update_email_option($new_options){
    $empty_options = wp_paypal_get_empty_email_options_array();
    $options = wp_paypal_get_email_option();
    if(is_array($options)){
        $current_options = array_merge($empty_options, $options);
        $updated_options = array_merge($current_options, $new_options);
        update_option('wp_paypal_email_options', $updated_options);
    }
    else{
        $updated_options = array_merge($empty_options, $new_options);
        update_option('wp_paypal_email_options', $updated_options);
    }
}

function wp_paypal_get_empty_email_options_array(){
    $options = array();
    $options['email_from_name'] = '';
    $options['email_from_address'] = '';
    $options['purchase_email_enabled'] = '';
    $options['purchase_email_subject'] = '';
    $options['purchase_email_type'] = '';
    $options['purchase_email_body'] = '';
    $options['sale_notification_email_enabled'] = '';
    $options['sale_notification_email_recipient'] = '';
    $options['sale_notification_email_subject'] = '';
    $options['sale_notification_email_type'] = '';
    $options['sale_notification_email_body'] = '';
    return $options;
}

function wp_paypal_set_default_email_options(){
    $options = wp_paypal_get_email_option();
    $options['purchase_email_type'] = 'plain';
    $options['purchase_email_subject'] = __("Purchase Receipt", "wp-paypal");
    $purchage_email_body = __("Dear", "wp-paypal")." {first_name},\n\n";
    $purchage_email_body .= __("Thank you for your purchase. Your purchase details are shown below for your reference:", "wp-paypal")."\n\n";
    $purchage_email_body .= __("Transaction ID:", "wp-paypal")." {txn_id}\n";
    $purchage_email_body .= __("Product(s):", "wp-paypal")." {item_names}\n";
    $purchage_email_body .= __("Total:", "wp-paypal")." {mc_currency} {mc_gross}";
    $options['purchase_email_body'] = $purchage_email_body;
    $options['sale_notification_email_recipient'] = get_bloginfo('admin_email');
    $options['sale_notification_email_subject'] = __("New Customer Order", "wp-paypal");
    $options['sale_notification_email_type'] = 'plain';
    $sale_notification_email_body = __("Hello", "wp-paypal")."\n\n";
    $sale_notification_email_body .= __("A purchase has been made.", "wp-paypal")."\n\n";
    $sale_notification_email_body .= __("Purchased by:", "wp-paypal")." {first_name} {last_name}\n";
    $sale_notification_email_body .= __("Product(s) sold:", "wp-paypal")." {item_names}\n";
    $sale_notification_email_body .= __("Total:", "wp-paypal")." {mc_currency} {mc_gross}\n\n";
    $sale_notification_email_body .= __("Thank you", "wp-paypal");       
    $options['sale_notification_email_body'] = $sale_notification_email_body;
    add_option('wp_paypal_email_options', $options);
}


function wp_paypal_debug_log($msg, $success, $end = false) {
    if (!WP_PAYPAL_DEBUG) {
        return;
    }
    $date_time = date('F j, Y g:i a');//the_date('F j, Y g:i a', '', '', FALSE);
    $text = '[' . $date_time . '] - ' . (($success) ? 'SUCCESS :' : 'FAILURE :') . $msg . "\n";
    if ($end) {
        $text .= "\n------------------------------------------------------------------\n\n";
    }
    // Write to log.txt file
    $fp = fopen(WP_PAYPAL_DEBUG_LOG_PATH, 'a');
    fwrite($fp, $text);
    fclose($fp);  // close file
}

function wp_paypal_debug_log_array($array_msg, $success, $end = false) {
    if (!WP_PAYPAL_DEBUG) {
        return;
    }
    $date_time = date('F j, Y g:i a');//the_date('F j, Y g:i a', '', '', FALSE);
    $text = '[' . $date_time . '] - ' . (($success) ? 'SUCCESS :' : 'FAILURE :') . "\n";
    ob_start();
    print_r($array_msg);
    $var = ob_get_contents();
    ob_end_clean();
    $text .= $var;
    if ($end) {
        $text .= "\n------------------------------------------------------------------\n\n";
    }
    // Write to log.txt file
    $fp = fopen(WP_PAYPAL_DEBUG_LOG_PATH, 'a');
    fwrite($fp, $text);
    fclose($fp);  // close filee
}

function wp_paypal_reset_log() {
    $log_reset = true;
    $date_time = date('F j, Y g:i a');//the_date('F j, Y g:i a', '', '', FALSE);
    $text = '[' . $date_time . '] - SUCCESS : Log reset';
    $text .= "\n------------------------------------------------------------------\n\n";
    $fp = fopen(WP_PAYPAL_DEBUG_LOG_PATH, 'w');
    if ($fp != FALSE) {
        @fwrite($fp, $text);
        @fclose($fp);
    } else {
        $log_reset = false;
    }
    return $log_reset;
}
