<?php

function wp_paypal_order_page() {
    $labels = array(
        'name' => __('Orders', 'wp-paypal'),
        'singular_name' => __('Order', 'wp-paypal'),
        'menu_name' => __('WP PayPal', 'wp-paypal'),
        'name_admin_bar' => __('Order', 'wp-paypal'),
        'add_new' => __('Add New', 'wp-paypal'),
        'add_new_item' => __('Add New Order', 'wp-paypal'),
        'new_item' => __('New Order', 'wp-paypal'),
        'edit_item' => __('Edit Order', 'wp-paypal'),
        'view_item' => __('View Order', 'wp-paypal'),
        'all_items' => __('All Orders', 'wp-paypal'),
        'search_items' => __('Search Orders', 'wp-paypal'),
        'parent_item_colon' => __('Parent Orders:', 'wp-paypal'),
        'not_found' => __('No Orders found.', 'wp-paypal'),
        'not_found_in_trash' => __('No orders found in Trash.', 'wp-paypal')
    );
    
    $capability = 'manage_options';
    $capabilities = array(
        'edit_post' => $capability,
        'read_post' => $capability,
        'delete_post' => $capability,
        'create_posts' => $capability,
        'edit_posts' => $capability,
        'edit_others_posts' => $capability,
        'publish_posts' => $capability,
        'read_private_posts' => $capability,
        'read' => $capability,
        'delete_posts' => $capability,
        'delete_private_posts' => $capability,
        'delete_published_posts' => $capability,
        'delete_others_posts' => $capability,
        'edit_private_posts' => $capability,
        'edit_published_posts' => $capability
    );
    
    $args = array(
        'labels' => $labels,
        'public' => false,
        'exclude_from_search' => true,
 	'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_nav_menus' => false,
        'show_in_menu' => current_user_can('manage_options') ? true : false,
        'query_var' => false,
        'rewrite' => false,
        'capabilities' => $capabilities,
        'has_archive' => false,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('editor')
    );

    register_post_type('wp_paypal_order', $args);
}

function wp_paypal_order_columns($columns) {
    unset($columns['title']);
    unset($columns['date']);
    $edited_columns = array(
        'title' => __('Order', 'wp-paypal'),
        'txn_id' => __('Transaction ID', 'wp-paypal'),
        'first_name' => __('First Name', 'wp-paypal'),
        'last_name' => __('Last Name', 'wp-paypal'),
        'payer_email' => __('Email', 'wp-paypal'),
        'mc_gross' => __('Total', 'wp-paypal'),
        'payment_status' => __('Payment Status', 'wp-paypal'),
        'custom' => __('Custom', 'wp-paypal'),
        'date' => __('Date', 'wp-paypal')
    );
    return array_merge($columns, $edited_columns);
}

function wp_paypal_custom_column($column, $post_id) {
    switch ($column) {
        case 'title' :
            echo esc_html($post_id);
            break;
        case 'txn_id' :
            echo esc_html(get_post_meta($post_id, '_txn_id', true));
            break;
        case 'first_name' :
            echo esc_html(get_post_meta($post_id, '_first_name', true));
            break;
        case 'last_name' :
            echo esc_html(get_post_meta($post_id, '_last_name', true));
            break;
        case 'payer_email' :
            echo esc_html(get_post_meta($post_id, '_payer_email', true));
            break;
        case 'mc_gross' :
            echo esc_html(get_post_meta($post_id, '_mc_gross', true));
            break;
        case 'payment_status' :
            echo esc_html(get_post_meta($post_id, '_payment_status', true));
            break;
        case 'custom' :
            echo esc_html(get_post_meta($post_id, '_custom', true));
            break;
    }
}

function wppaypal_order_meta_boxes($post){
    $post_type = 'wp_paypal_order';
    /** Product Data **/
    add_meta_box('wppaypal_order_data', __('Order Data'),  'wppaypal_render_order_data_meta_box', $post_type, 'normal', 'high');
}

function wppaypal_render_order_data_meta_box($post){
    $post_id = $post->ID;
    $transaction_id = get_post_meta($post_id, '_txn_id', true);
    if(!isset($transaction_id) || empty($transaction_id)){
        $transaction_id = '';
    }
    $customer_first_name = get_post_meta($post_id, '_first_name', true);
    if(!isset($customer_first_name) || empty($customer_first_name)){
        $customer_first_name = '';
    }
    $customer_last_name = get_post_meta($post_id, '_last_name', true);
    if(!isset($customer_last_name) || empty($customer_last_name)){
        $customer_last_name = '';
    }
    $payer_email = get_post_meta($post_id, '_payer_email', true);
    if(!isset($payer_email) || empty($payer_email)){
        $payer_email = '';
    }
    $total_amount = get_post_meta($post_id, '_mc_gross', true);
    if(!isset($total_amount) || !is_numeric($total_amount)){
        $total_amount = '';
    }
    $payment_status = get_post_meta($post_id, '_payment_status', true);
    if(!isset($payment_status) || empty($payment_status)){
        $payment_status = '';
    }
    $custom = get_post_meta($post_id, '_custom', true);
    if(!isset($custom) || empty($custom)){
        $custom = '';
    }
    ?>
    <table>
        <tbody>
            <tr>
                <td valign="top">
                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <th scope="row"><label for="_wppaypal_order_txn_id"><?php _e('Transaction ID', 'wp-paypal');?></label></th>
                                <td><input name="_wppaypal_order_txn_id" type="text" id="_wppaypal_order_txn_id" value="<?php echo esc_attr($transaction_id); ?>" class="regular-text"></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="_wppaypal_order_first_name"><?php _e('First Name', 'wp-paypal');?></label></th>
                                <td><input name="_wppaypal_order_first_name" type="text" id="_wppaypal_order_first_name" value="<?php echo esc_attr($customer_first_name); ?>" class="regular-text"></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="_wppaypal_order_last_name"><?php _e('Last Name', 'wp-paypal');?></label></th>
                                <td><input name="_wppaypal_order_last_name" type="text" id="_wppaypal_order_last_name" value="<?php echo esc_attr($customer_last_name); ?>" class="regular-text"></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="_wppaypal_order_payer_email"><?php _e('Payer Email', 'wp-paypal');?></label></th>
                                <td><input name="_wppaypal_order_payer_email" type="text" id="_wppaypal_order_payer_email" value="<?php echo esc_attr($payer_email); ?>" class="regular-text"></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="_wppaypal_order_mc_gross"><?php _e('Total Amount', 'wp-paypal');?></label></th>
                                <td><input name="_wppaypal_order_mc_gross" type="text" id="_wppaypal_order_mc_gross" value="<?php echo esc_attr($total_amount); ?>" class="regular-text"></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="_wppaypal_order_payment_status"><?php _e('Payment Status', 'wp-paypal');?></label></th>
                                <td><input name="_wppaypal_order_payment_status" type="text" id="_wppaypal_order_payment_status" value="<?php echo esc_attr($payment_status); ?>" class="regular-text"></td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><label for="_wppaypal_order_custom"><?php _e('Custom Data', 'wp-paypal');?></label></th>
                                <td><input name="_wppaypal_order_custom" type="text" id="_wppaypal_order_custom" value="<?php echo esc_attr($custom); ?>" class="regular-text"></td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody> 
    </table>
    <?php
    wp_nonce_field(basename(__FILE__), 'wppaypal_order_data_meta_box_nonce');
}

function wppaypal_order_data_meta_box_save($post_id, $post){
    if(!isset($_POST['wppaypal_order_data_meta_box_nonce']) || !wp_verify_nonce($_POST['wppaypal_order_data_meta_box_nonce'], basename(__FILE__))){
        return;
    }
    if((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit'])){
        return;
    }
    if(isset($post->post_type) && 'revision' == $post->post_type){
        return;
    }
    if(!current_user_can('manage_options')){
        return;
    }
    //update the values
    if(isset($_POST['_wppaypal_order_txn_id'])){
        $transaction_id = sanitize_text_field($_POST['_wppaypal_order_txn_id']);
        update_post_meta($post_id, '_txn_id', $transaction_id);
    }
    if(isset($_POST['_wppaypal_order_first_name'])){
        $customer_first_name = sanitize_text_field($_POST['_wppaypal_order_first_name']);
        update_post_meta($post_id, '_first_name', $customer_first_name);
    }
    if(isset($_POST['_wppaypal_order_last_name'])){
        $customer_last_name = sanitize_text_field($_POST['_wppaypal_order_last_name']);
        update_post_meta($post_id, '_last_name', $customer_last_name);
    }
    if(isset($_POST['_wppaypal_order_payer_email'])){
        $payer_email = sanitize_text_field($_POST['_wppaypal_order_payer_email']);
        update_post_meta($post_id, '_payer_email', $payer_email);
    }
    if(isset($_POST['_wppaypal_order_mc_gross']) && is_numeric($_POST['_wppaypal_order_mc_gross'])){
        $total_amount = sanitize_text_field($_POST['_wppaypal_order_mc_gross']);
        update_post_meta($post_id, '_mc_gross', $total_amount);
    }
    if(isset($_POST['_wppaypal_order_payment_status'])){
        $payment_status = sanitize_text_field($_POST['_wppaypal_order_payment_status']);
        update_post_meta($post_id, '_payment_status', $payment_status);
    }
    if(isset($_POST['_wppaypal_order_custom'])){
        $custom = sanitize_text_field($_POST['_wppaypal_order_custom']);
        update_post_meta($post_id, '_custom', $custom);
    }
}

add_action('save_post_wp_paypal_order', 'wppaypal_order_data_meta_box_save', 10, 2 );
