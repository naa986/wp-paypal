<?php
/*
  Plugin Name: WP PayPal
  Version: 1.0.9
  Plugin URI: http://wphowto.net/
  Author: naa986
  Author URI: http://wphowto.net/
  Description: Easily accept PayPal payments in WordPress
  Text Domain: wp-paypal
  Domain Path: /languages
 */

if (!defined('ABSPATH'))
    exit;

class WP_PAYPAL {
    
    var $plugin_version = '1.0.9';
    var $plugin_url;
    var $plugin_path;
    
    function __construct() {
        define('WP_PAYPAL_VERSION', $this->plugin_version);
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
        include_once('paypal-ipn.php');
    }

    function loader_operations() {
        register_activation_hook(__FILE__, array($this, 'activate_handler'));
        add_action('plugins_loaded', array($this, 'plugins_loaded_handler'));
        if (is_admin()) {
            add_filter('plugin_action_links', array($this, 'add_plugin_action_links'), 10, 2);
        }
        add_action('admin_notices', array($this, 'admin_notice'));
        add_action('wp_enqueue_scripts', array($this, 'plugin_scripts'));
        add_action('admin_menu', array($this, 'add_options_menu'));
        add_action('init', array($this, 'plugin_init'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_filter('manage_wp_paypal_order_posts_columns', 'wp_paypal_order_columns');
        add_action('manage_wp_paypal_order_posts_custom_column', 'wp_paypal_custom_column', 10, 2);
        add_shortcode('wp_paypal', 'wp_paypal_button_handler');
    }

    function plugins_loaded_handler() {  //Runs when plugins_loaded action gets fired
        load_plugin_textdomain( 'wp-paypal', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
        $this->check_upgrade();
    }

    function admin_notice() {
        if (WP_PAYPAL_DEBUG) {  //debug is enabled. Check to make sure log file is writable
            $real_file = WP_PAYPAL_DEBUG_LOG_PATH;
            if (!is_writeable($real_file)) {
                echo '<div class="updated"><p>' . __('WP PayPal Debug log file is not writable. Please check to make sure that it has the correct file permission (ideally 644). Otherwise the plugin will not be able to write to the log file. The log file (log.txt) can be found in the root directory of the plugin - ', 'wp-paypal') . '<code>' . WP_PAYPAL_URL . '</code></p></div>';
            }
        }
    }

    function activate_handler() {
        add_option('wp_paypal_plugin_version', $this->plugin_version);
        add_option('wp_paypal_email', get_bloginfo('admin_email'));
        add_option('wp_paypal_currency_code', 'USD');
    }

    function check_upgrade() {
        if (is_admin()) {
            $plugin_version = get_option('wp_paypal_plugin_version');
            if (!isset($plugin_version) || $plugin_version != $this->plugin_version) {
                $this->activate_handler();
                update_option('wp_paypal_plugin_version', $this->plugin_version);
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

    function plugin_scripts() {
        if (!is_admin()) {
            
        }
    }

    function plugin_url() {
        if ($this->plugin_url)
            return $this->plugin_url;
        return $this->plugin_url = plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
    }

    function plugin_path() {
        if ($this->plugin_path)
            return $this->plugin_path;
        return $this->plugin_path = untrailingslashit(plugin_dir_path(__FILE__));
    }

    function debug_log_path() {
        return WP_PAYPAL_PATH . '/log.txt';
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
        }
    }

    function options_page() {
        $plugin_tabs = array(
            'wp-paypal-settings' => __('General', 'wp-paypal')
        );
        echo '<div class="wrap">' . screen_icon() . '<h2>'.__('WP PayPal', 'wp-paypal').' v' . WP_PAYPAL_VERSION . '</h2>';
        $url = 'http://wphowto.net/wordpress-paypal-plugin-732';
        $link_msg = sprintf( wp_kses( __( 'Please visit the <a target="_blank" href="%s">WP PayPal</a> documentation page for usage instructions.', 'wp-paypal' ), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( $url ) );
        echo '<div class="update-nag">'.$link_msg.'</div>';
        echo '<div id="poststuff"><div id="post-body">';

        if (isset($_GET['page'])) {
            $current = $_GET['page'];
            if (isset($_GET['action'])) {
                $current .= "&action=" . $_GET['action'];
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
        echo $content;

        $this->general_settings();

        echo '</div></div>';
        echo '</div>';
    }

    function general_settings() {
        if (isset($_POST['wp_paypal_update_settings'])) {
            $nonce = $_REQUEST['_wpnonce'];
            if (!wp_verify_nonce($nonce, 'wp_paypal_general_settings')) {
                wp_die('Error! Nonce Security Check Failed! please save the settings again.');
            }
            update_option('wp_paypal_enable_testmode', (isset($_POST["enable_testmode"]) && $_POST["enable_testmode"] == '1') ? '1' : '');
            update_option('wp_paypal_email', trim($_POST["paypal_email"]));
            update_option('wp_paypal_currency_code', trim($_POST["currency_code"]));
            echo '<div id="message" class="updated fade"><p><strong>';
            echo __('Settings Saved', 'wp-paypal').'!';
            echo '</strong></p></div>';
        }
        ?>

        <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
            <?php wp_nonce_field('wp_paypal_general_settings'); ?>

            <table class="form-table">

                <tbody>

                    <tr valign="top">
                        <th scope="row"><?Php _e('Enable Test Mode', 'wp-paypal');?></th>
                        <td> <fieldset><legend class="screen-reader-text"><span>Enable Test Mode</span></legend><label for="enable_testmode">
                                    <input name="enable_testmode" type="checkbox" id="enable_testmode" <?php if (get_option('wp_paypal_enable_testmode') == '1') echo ' checked="checked"'; ?> value="1">
                                    <?Php _e('Check this option if you want to enable PayPal sandbox for testing', 'wp-paypal');?></label>
                            </fieldset></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><label for="paypal_email"><?Php _e('PayPal Email', 'wp-paypal');?></label></th>
                        <td><input name="paypal_email" type="text" id="paypal_email" value="<?php echo get_option('wp_paypal_email'); ?>" class="regular-text">
                            <p class="description"><?Php _e('Your PayPal email address', 'wp-paypal');?></p></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><label for="currency_code"><?Php _e('Currency Code', 'wp-paypal');?></label></th>
                        <td><input name="currency_code" type="text" id="currency_code" value="<?php echo get_option('wp_paypal_currency_code'); ?>" class="regular-text">
                            <p class="description"><?Php _e('The currency of the payment', 'wp-paypal');?> (<?Php _e('example', 'wp-paypal');?>: USD, CAD, GBP, EUR)</p></td>
                    </tr>

                </tbody>

            </table>

            <p class="submit"><input type="submit" name="wp_paypal_update_settings" id="wp_paypal_update_settings" class="button button-primary" value="<?Php _e('Save Changes', 'wp-paypal');?>"></p></form>

        <?php
    }

    function debug_page() {
        ?>
        <div class="wrap">
            <h2><?Php _e('WP PayPal Debug Log', 'wp-paypal');?></h2>
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
                    $real_file = WP_PAYPAL_DEBUG_LOG_PATH;
                    $content = file_get_contents($real_file);
                    $content = esc_textarea($content);
                    ?>
                    <div id="template"><textarea cols="70" rows="25" name="wp_paypal_log" id="wp_paypal_log"><?php echo $content; ?></textarea></div>                     
                    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                        <?php wp_nonce_field('wp_paypal_debug_log_settings'); ?>
                        <table class="form-table">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row"><?Php _e('Enable Debug', 'wp-paypal');?></th>
                                    <td> <fieldset><legend class="screen-reader-text"><span>Enable Debug</span></legend><label for="enable_debug">
                                                <input name="enable_debug" type="checkbox" id="enable_debug" <?php if (get_option('wp_paypal_enable_debug') == '1') echo ' checked="checked"'; ?> value="1">
                                                <?Php _e('Check this option if you want to enable debug', 'wp-paypal');?></label>
                                        </fieldset></td>
                                </tr>

                            </tbody>

                        </table>
                        <p class="submit"><input type="submit" name="wp_paypal_update_log_settings" id="wp_paypal_update_log_settings" class="button button-primary" value="<?Php _e('Save Changes', 'wp-paypal');?>"></p>
                    </form>
                    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                        <?php wp_nonce_field('wp_paypal_reset_log_settings'); ?>                            
                        <p class="submit"><input type="submit" name="wp_paypal_reset_log" id="wp_paypal_reset_log" class="button" value="<?Php _e('Reset Log', 'wp-paypal');?>"></p>
                    </form>
                </div>         
            </div>
        </div>
        <?php
    }

}

$GLOBALS['wp_paypal'] = new WP_PAYPAL();

function wp_paypal_button_handler($atts) {
    $testmode = get_option('wp_paypal_enable_testmode');
    if (isset($testmode) && !empty($testmode)) {
        $atts['env'] = "sandbox";
    }
    $atts['callback'] = home_url() . '/?wp_paypal_ipn=1';
    $paypal_email = get_option('wp_paypal_email');
    $currency = get_option('wp_paypal_currency_code');
    if (isset($atts['currency']) && !empty($atts['currency'])) {
        
    } else {
        $atts['currency'] = $currency;
    }
    $target = '';
    if(isset($atts['target']) && !empty($atts['target'])) {
        $target = $atts['target'];
        unset($atts['target']);
    }
    $id = uniqid();
    $button_code = '<div id="'.$id.'">';
    $button_code .= '<script async src="' . WP_PAYPAL_URL . '/lib/paypal-button.min.js?merchant=' . $paypal_email . '"';
    foreach ($atts as $key => $value) {
        if($key=='button_image'){
            continue;
        }
        $button_code .= ' data-' . $key . '="' . $value . '"';
    }
    //$button_code .= 'async';
    $button_code .= '></script>';
    $button_code .= '</div>';
    if(isset($atts['button_image']) && filter_var($atts['button_image'], FILTER_VALIDATE_URL)){
        $button_image_url = esc_url($atts['button_image']);
        $output = <<<EOT
        <script>
        /* <![CDATA[ */
            jQuery(document).ready(function($){
                $(function(){
                    $('div#$id button').replaceWith('<input type="image" src="$button_image_url">');
                });
            });
            /* ]]> */    
        </script>       
EOT;
        $button_code .= $output;
    }
    if(!empty($target)){
        $output = <<<EOT
        <script>
        /* <![CDATA[ */
            jQuery(document).ready(function($){
                $(function(){
                    $('div#$id form').prop('target', '$target');
                });
            });
            /* ]]> */    
        </script>       
EOT;
        $button_code .= $output;
    }
    return $button_code;
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
