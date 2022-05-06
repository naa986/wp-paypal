<?php

function wp_paypal_process_ipn() {
    if (!empty($_GET['wp_paypal_ipn']) && $_GET['wp_paypal_ipn'] == '1') {
        $ipn_response = !empty($_POST) ? $_POST : false;
        if (!$ipn_response) {
            wp_die( "Empty PayPal IPN Request", "PayPal IPN", array( 'response' => 200 ) );
            return;
        }
        wp_paypal_debug_log("Received IPN from PayPal", true);
        wp_paypal_debug_log_array($ipn_response, true);
        $paypal_adr = "https://www.paypal.com/cgi-bin/webscr";
        if (WP_PAYPAL_USE_SANDBOX) {
            $paypal_adr = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        }
        wp_paypal_debug_log("Checking if IPN response is valid via ".$paypal_adr, true);
        // Get received values from post data
        $validate_ipn = array('cmd' => '_notify-validate');
        $validate_ipn += stripslashes_deep($ipn_response);
        // Send back post vars to paypal
        $params = array(
            'body' => $validate_ipn,
            'sslverify' => false,
            'timeout' => 60,
            'httpversion' => '1.1',
            'compress' => false,
            'decompress' => false,
            'user-agent' => 'WP PayPal/' . WP_PAYPAL_VERSION
        );
        wp_paypal_debug_log("IPN Request: ", true);
        wp_paypal_debug_log_array($params, true);
        // Post back to get a response
        $response = wp_remote_post($paypal_adr, $params);
        //wp_paypal_debug_log("IPN Response: ", true);
        //wp_paypal_debug_log_array($response, true);
        // check to see if the request was valid
        $ipn_verified = false;
        if (!is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && strstr($response['body'], 'VERIFIED')) {
            header( 'HTTP/1.1 200 OK' );
            wp_paypal_debug_log("Received valid response from PayPal", true);
            $ipn_verified = true;
        }
        
        if(!$ipn_verified){
            wp_paypal_debug_log("Received invalid response from PayPal", false);
            if (is_wp_error($response)) {
                wp_paypal_debug_log("Error response: ".$response->get_error_message(), false);
            }
            wp_die( "PayPal IPN Request Failure", "PayPal IPN", array( 'response' => 200 ) );
            return;
        }
        $txn_type = '';
        if(isset($ipn_response['txn_type']) && !empty($ipn_response['txn_type'])){
            $txn_type = sanitize_text_field($ipn_response['txn_type']);
        }
        $supported_txn_types = array("web_accept", "cart", "subscr_signup");
        if (!in_array($txn_type, $supported_txn_types)) {
            wp_paypal_debug_log("This payment cannot be processed", false);
            return;
        }
        //process data
        $payment_status = '';
        if (isset($ipn_response['payment_status'])) {
            $payment_status = sanitize_text_field($ipn_response['payment_status']);
            wp_paypal_debug_log("Payment Status - " . $payment_status, true);
        }
        
        //Only check the payment_status when it's not a subscription signup
        if($txn_type != 'subscr_signup'){
            if ($payment_status != 'Completed') {  //only process a completed payment
                wp_paypal_debug_log("This payment cannot be processed", false);
                return;
            }
        }
        $payment_data = array();
        if (isset($ipn_response['subscr_id']) && !empty($ipn_response['subscr_id'])) {
            $payment_data['txn_id'] = sanitize_text_field($ipn_response['subscr_id']);
        }
        if (isset($ipn_response['txn_id']) && !empty($ipn_response['txn_id'])) {
            $payment_data['txn_id'] = sanitize_text_field($ipn_response['txn_id']);
        }
        if(!isset($payment_data['txn_id']) || empty($payment_data['txn_id'])){
            wp_paypal_debug_log("No txn_id. This payment cannot be processed", false);
            return;
        }
        $args = array(
            'post_type' => 'wp_paypal_order',
            'meta_query' => array(
                array(
                    'key' => '_txn_id',
                    'value' => $payment_data['txn_id'],
                    'compare' => '=',
                ),
            ),
        );
        $query = new WP_Query($args);
        if ($query->have_posts()) {  //a record already exists
            wp_paypal_debug_log("An order with this transaction ID already exists. This payment will not be processed.", false);
            return;
        }
        $payment_data['first_name'] = '';
        if (isset($ipn_response['first_name']) && !empty($ipn_response['first_name'])) {
            $payment_data['first_name'] = sanitize_text_field($ipn_response['first_name']);
        }
        $payment_data['last_name'] = '';
        if (isset($ipn_response['last_name']) && !empty($ipn_response['last_name'])) {
            $payment_data['last_name'] = sanitize_text_field($ipn_response['last_name']);
        }
        $payment_data['item_names'] = '';
        if (isset($ipn_response['item_name']) && !empty($ipn_response['item_name'])) {
            $payment_data['item_names'] = sanitize_text_field($ipn_response['item_name']);
        }
        if(isset($ipn_response['txn_type']) && $ipn_response['txn_type'] == 'cart'){
            if(isset($ipn_response['num_cart_items']) && !empty($ipn_response['num_cart_items'])){
                $num_cart_items = sanitize_text_field($ipn_response['num_cart_items']);
                for($i = 1; $i <= $num_cart_items; $i++){
                    $product_name = isset($ipn_response['item_name'.$i]) && !empty($ipn_response['item_name'.$i]) ? sanitize_text_field($ipn_response['item_name'.$i]) : '';
                    if(!empty($product_name)){
                        $payment_data['item_names'] .= !empty($payment_data['item_names']) ? ', '.$product_name : $product_name;
                    }
                }
            }
            
        }

        if (isset($ipn_response['mc_gross']) && !empty($ipn_response['mc_gross'])) {
            $payment_data['mc_gross'] = sanitize_text_field($ipn_response['mc_gross']);
        }
        //subscription trial 1 check
        if (isset($ipn_response['mc_amount1'])) {
            $payment_data['mc_gross'] = sanitize_text_field($ipn_response['mc_amount1']);
        }
        else if (isset($ipn_response['mc_amount3']) && !empty($ipn_response['mc_amount3'])) {  //regular subscription check
            $payment_data['mc_gross'] = sanitize_text_field($ipn_response['mc_amount3']);
        }
        
        if(!isset($payment_data['mc_gross'])){
            wp_paypal_debug_log("mc_gross is not valid. This payment cannot be processed.", false);
            return;
        }
        $payment_data['mc_currency'] = '';
        if (isset($ipn_response['mc_currency']) && !empty($ipn_response['mc_currency'])) {
            $payment_data['mc_currency'] = sanitize_text_field($ipn_response['mc_currency']);
        }
        else{
            wp_paypal_debug_log("mc_currency is not valid. This payment cannot be processed.", false);
            return;
        }
        $seller_id = get_option('wp_paypal_merchant_id');
        $seller_email = get_option('wp_paypal_email');
        if (isset($seller_id) && !empty($seller_id) && isset($ipn_response['receiver_id']) && !empty($ipn_response['receiver_id'])) {
            $receiver_id = sanitize_text_field($ipn_response['receiver_id']);
            if ($seller_id != $receiver_id) {
                wp_paypal_debug_log("Seller PayPal ID (".$seller_id.") and Receiver PayPal ID (".$receiver_id.") do not match. This payment cannot be processed.", false);
                return;
            }
        }
        else if (isset($seller_email) && !empty($seller_email) && isset($ipn_response['receiver_email']) && !empty($ipn_response['receiver_email'])) {
            $receiver_email = sanitize_text_field($ipn_response['receiver_email']);
            if ($seller_email != $receiver_email) {
                wp_paypal_debug_log("Seller PayPal email (".$seller_email.") and Receiver PayPal email (".$receiver_email.") do not match. This payment cannot be processed.", false);
                return;
            }
        }
        else{
            wp_paypal_debug_log("Seller PayPal ID and Receiver PayPal ID could not be verified. This payment cannot be processed.", false);
            return;
        }
        $payment_data['payer_email'] = '';
        if (isset($ipn_response['payer_email']) && !empty($ipn_response['payer_email'])) {
            $payment_data['payer_email'] = sanitize_text_field($ipn_response['payer_email']);
        }
        $payment_data['custom'] = '';
        if (isset($ipn_response['custom']) && !empty($ipn_response['custom'])) {
            $payment_data['custom'] = sanitize_text_field($ipn_response['custom']);
        }
        $ship_to = '';
        if (isset($ipn_response['address_street'])) {
            $address_street = sanitize_text_field($ipn_response['address_street']);
            $ship_to .= !empty($address_street) ? $address_street.'<br />' : '';
            $address_city = isset($ipn_response['address_city']) ? sanitize_text_field($ipn_response['address_city']) : '';
            $ship_to .= !empty($address_city) ? $address_city.', ' : '';
            $address_state = isset($ipn_response['address_state']) ? sanitize_text_field($ipn_response['address_state']) : '';
            $ship_to .= !empty($address_state) ? $address_state.' ' : '';
            $address_zip = isset($ipn_response['address_zip']) ? sanitize_text_field($ipn_response['address_zip']) : '';
            $ship_to .= !empty($address_zip) ? $address_zip.'<br />' : '';
            $address_country = isset($ipn_response['address_country']) ? sanitize_text_field($ipn_response['address_country']) : '';
            $ship_to .= !empty($address_country) ? $address_country : '';
        }
        $wp_paypal_order = array(
            'post_title' => 'order',
            'post_type' => 'wp_paypal_order',
            'post_content' => '',
            'post_status' => 'publish',
        );
        wp_paypal_debug_log("Inserting order information", true);
        $post_id = wp_insert_post($wp_paypal_order, true);  //insert a new order
        if (is_wp_error($post_id)) {
            wp_paypal_debug_log("Error inserting order information: ".$post_id->get_error_message(), false);
            return;
        }
        if (!$post_id) {
            wp_paypal_debug_log("Order information could not be inserted", false);
            return;
        }
        $post_updated = false;
        if ($post_id > 0) {
            $post_content = '';
            if(!empty($payment_data['item_names'])){
                $post_content .= '<strong>Product(s):</strong> '.$payment_data['item_names'].'<br />';
            }
            if(isset($payment_data['custom']) && !empty($payment_data['custom'])){
                $post_content .= '<strong>Custom:</strong> '.$payment_data['custom'].'<br />';
            }
            if(!empty($ship_to)){
                $ship_to = '<h2>'.__('Ship To', 'wp-paypal').'</h2><br />'.$payment_data['first_name'].' '.$payment_data['last_name'].'<br />'.$ship_to.'<br />';
            }
            $post_content .= $ship_to;
            $post_content .= '<h2>'.__('Payment Data', 'wp-paypal').'</h2><br />';
            $post_content .= print_r($ipn_response, true);
            $updated_post = array(
                'ID' => $post_id,
                'post_title' => $post_id,
                'post_type' => 'wp_paypal_order',
                'post_content' => $post_content
            );
            $updated_post_id = wp_update_post($updated_post, true);  //update the order
            if (is_wp_error($updated_post_id)) {
                wp_paypal_debug_log("Error updating order information: ".$updated_post_id->get_error_message(), false);
                return;
            }
            if (!$updated_post_id) {
                wp_paypal_debug_log("Order information could not be updated", false);
                return;
            }
            if ($updated_post_id > 0) {  //successfully updated
                $post_updated = true;
            }
        }
        //save order information
        if ($post_updated) {
            update_post_meta($post_id, '_txn_id', $payment_data['txn_id']);
            update_post_meta($post_id, '_first_name', $payment_data['first_name']);
            update_post_meta($post_id, '_last_name', $payment_data['last_name']);
            update_post_meta($post_id, '_payer_email', $payment_data['payer_email']);
            update_post_meta($post_id, '_mc_gross', $payment_data['mc_gross']);
            update_post_meta($post_id, '_payment_status', $payment_status);
            wp_paypal_debug_log("Order information updated", true);
            
            $email_options = wp_paypal_get_email_option();
            add_filter('wp_mail_from', 'wp_paypal_set_email_from');
            add_filter('wp_mail_from_name', 'wp_paypal_set_email_from_name');
            if(isset($email_options['purchase_email_enabled']) && !empty($email_options['purchase_email_enabled']) && !empty($payment_data['payer_email'])){
                $subject = $email_options['purchase_email_subject'];
                $type = $email_options['purchase_email_type'];
                $body = $email_options['purchase_email_body'];
                $body = wp_paypal_do_email_tags($payment_data, $body);
                if($type == "html"){
                    add_filter('wp_mail_content_type', 'wp_paypal_set_html_email_content_type');
                    $body = apply_filters('wp_paypal_email_body_wpautop', true) ? wpautop($body) : $body;
                }
                wp_paypal_debug_log("Sending a purchase receipt email to ".$payment_data['payer_email'], true);
                $mail_sent = wp_mail($payment_data['payer_email'], $subject, $body);
                if($type == "html"){
                    remove_filter('wp_mail_content_type', 'wp_paypal_set_html_email_content_type');
                }
                if($mail_sent == true){
                    wp_paypal_debug_log("Email was sent successfully by WordPress", true);
                }
                else{
                    wp_paypal_debug_log("Email could not be sent by WordPress", false);
                }
            }
            if(isset($email_options['sale_notification_email_enabled']) && !empty($email_options['sale_notification_email_enabled']) && !empty($email_options['sale_notification_email_recipient'])){
                $subject = $email_options['sale_notification_email_subject'];
                $type = $email_options['sale_notification_email_type'];
                $body = $email_options['sale_notification_email_body'];
                $body = wp_paypal_do_email_tags($payment_data, $body);
                if($type == "html"){
                    add_filter('wp_mail_content_type', 'wp_paypal_set_html_email_content_type');
                    $body = apply_filters('wp_paypal_email_body_wpautop', true) ? wpautop($body) : $body;
                }
                $email_recipients = explode(",", $email_options['sale_notification_email_recipient']);
                foreach($email_recipients as $email_recipient){
                    $to = sanitize_email($email_recipient);
                    if(is_email($to)){
                        wp_paypal_debug_log("Sending a sale notification email to ".$to, true);
                        $mail_sent = wp_mail($to, $subject, $body);
                        if($mail_sent == true){
                            wp_paypal_debug_log("Email was sent successfully by WordPress", true);
                        }
                        else{
                            wp_paypal_debug_log("Email could not be sent by WordPress", false);
                        }
                    }
                }
                if($type == "html"){
                    remove_filter('wp_mail_content_type', 'wp_paypal_set_html_email_content_type');
                }
            }
            remove_filter('wp_mail_from', 'wp_paypal_set_email_from');
            remove_filter('wp_mail_from_name', 'wp_paypal_set_email_from_name');
            
            do_action('wp_paypal_order_processed', $post_id);
            $ipn_response['order_id'] = $post_id;
            do_action('wp_paypal_ipn_processed', $ipn_response);
        } else {
            wp_paypal_debug_log("Order information could not be updated", false);
            return;
        }
        wp_paypal_debug_log("IPN processing completed", true, true);
    }
}

function wp_paypal_do_email_tags($payment_data, $content){
    $search = array(
        '{first_name}', 
        '{last_name}', 
        '{txn_id}',
        '{item_names}',
        '{mc_currency}',
        '{mc_gross}',
        '{payer_email}',
        '{custom}'
    );
    $replace = array(
        $payment_data['first_name'], 
        $payment_data['last_name'],
        $payment_data['txn_id'],
        $payment_data['item_names'],
        $payment_data['mc_currency'],
        $payment_data['mc_gross'],
        $payment_data['payer_email'],
        $payment_data['custom']
    );
    $content = str_replace($search, $replace, $content);
    return $content;
}

function wp_paypal_set_email_from($from){
    $email_options = wp_paypal_get_email_option();
    if(isset($email_options['email_from_address']) && !empty($email_options['email_from_address'])){
        $from = $email_options['email_from_address'];
    }
    return $from;
}

function wp_paypal_set_email_from_name($from_name){
    $email_options = wp_paypal_get_email_option();
    if(isset($email_options['email_from_name']) && !empty($email_options['email_from_name'])){
        $from_name = $email_options['email_from_name'];
    }
    return $from_name;
}

function wp_paypal_set_html_email_content_type($content_type){
    $content_type = 'text/html';
    return $content_type;
}