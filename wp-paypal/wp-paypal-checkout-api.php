<?php
add_action('wp_ajax_wppaypalcheckout_pp_api_create_order', 'wp_paypal_checkout_pp_api_create_order');
add_action('wp_ajax_nopriv_wppaypalcheckout_pp_api_create_order', 'wp_paypal_checkout_pp_api_create_order');
add_action('wp_ajax_wppaypalcheckout_pp_api_capture_order', 'wp_paypal_checkout_pp_api_capture_order');
add_action('wp_ajax_nopriv_wppaypalcheckout_pp_api_capture_order', 'wp_paypal_checkout_pp_api_capture_order');
add_action('wp_paypal_checkout_process_order', 'wp_paypal_checkout_process_order_handler', 10, 2);

function wp_paypal_checkout_pp_api_create_order(){
    //The data will be in JSON format string (not actual JSON object). By using json_decode it can be converted to a json object or array.
    $json_order_data = isset($_POST['data']) ? stripslashes_deep($_POST['data']) : '{}';
    $order_data_array = json_decode($json_order_data, true);
    $encoded_item_description = isset($order_data_array['purchase_units'][0]['description']) ? $order_data_array['purchase_units'][0]['description'] : '';
    $decoded_item_description = html_entity_decode($encoded_item_description);
    wp_paypal_debug_log("Checkout - Create-order request received for item: ".$decoded_item_description, true);

    //Set this decoded item name back to the order data.
    $order_data_array['purchase_units'][0]['description'] = $decoded_item_description;
    wp_paypal_debug_log_array($order_data_array, true);
    if(empty($json_order_data)){
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Empty data received.', 'wp-paypal'),
            )
        );
    }
    $options = wp_paypal_checkout_get_option();
    $currency_code = $options['currency_code'];
    $description = $order_data_array['purchase_units'][0]['description'];
    $amount = $order_data_array['purchase_units'][0]['amount']['value'];
    $total_amount = $amount;   
    wp_paypal_debug_log("Checkout - Creating order data to send to PayPal: ", true);
    $pp_api_order_data = [
        "intent" => "CAPTURE",
        "payment_source" => [
            "paypal" => [
                "experience_context" => [
                    "payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
                ]
            ]
        ], 			
        "purchase_units" => [
            [
                "description" => $description,
                "amount" => [
                    "value" => (string) $total_amount,
                    "currency_code" => $currency_code,
                ],
            ]
        ]
    ];
    //
    $shipping_preference = '';
    if(isset($order_data_array['payment_source']['paypal']['experience_context']['shipping_preference'])
            && !empty($order_data_array['payment_source']['paypal']['experience_context']['shipping_preference'])){       
        $shipping_preference = $order_data_array['payment_source']['paypal']['experience_context']['shipping_preference'];
        $pp_api_order_data['payment_source']['paypal']['experience_context']['shipping_preference'] = $shipping_preference;
    }
    //
    $amount_breakdown = false;
    //shipping
    if(isset($order_data_array['purchase_units'][0]['amount']['breakdown']['shipping']['value']) 
            && is_numeric($order_data_array['purchase_units'][0]['amount']['breakdown']['shipping']['value']) 
                && $order_data_array['purchase_units'][0]['amount']['breakdown']['shipping']['value'] > 0){
        $shipping = $order_data_array['purchase_units'][0]['amount']['breakdown']['shipping']['value'];
        $pp_api_order_data['purchase_units'][0]['amount']['breakdown']['shipping']['currency_code'] = $currency_code;
        $pp_api_order_data['purchase_units'][0]['amount']['breakdown']['shipping']['value'] = (string) $shipping;
        $total_amount = $amount + $shipping;
        $amount_breakdown = true;
    }
    //break down amount when needed
    if($amount_breakdown){
        $pp_api_order_data['purchase_units'][0]['amount']['breakdown']['item_total']['currency_code'] = $currency_code;
        $pp_api_order_data['purchase_units'][0]['amount']['breakdown']['item_total']['value'] = (string) $amount;
        $pp_api_order_data['purchase_units'][0]['amount']['value'] = (string) $total_amount;
    }
    //
    $json_encoded_pp_api_order_data = wp_json_encode($pp_api_order_data);   
    wp_paypal_debug_log_array($json_encoded_pp_api_order_data, true);  
    $access_token = wp_paypal_checkout_get_paypal_access_token();
    if (!$access_token) {
        wp_paypal_debug_log('Checkout - Access token could not be created using PayPal API', false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Checkout - Access token could not be created using PayPal API.', 'wp-paypal'),
            )
        );
    }
    $url = 'https://api-m.paypal.com/v2/checkout/orders';
    if(isset($options['test_mode']) && $options['test_mode'] == "1"){
        $url = 'https://api-m.sandbox.paypal.com/v2/checkout/orders';
    }
    $response = wp_safe_remote_post($url, array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ),
        'body' => $json_encoded_pp_api_order_data
    ));

    if (is_wp_error($response)) {
        wp_paypal_debug_log('Checkout - Error response', false);
        wp_paypal_debug_log_array($response, false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg'  => __('Failed to create the order using PayPal API.', 'wp-paypal'),
            )
        );
    }

    $body = wp_remote_retrieve_body($response);
    if(!isset($body) || empty($body)){
        wp_paypal_debug_log('Checkout - Error response from invalid body', false);
        wp_paypal_debug_log_array($response, false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Invalid response body from PayPal API order creation.', 'wp-paypal'),
            )
        );
    }
    $data = json_decode($body);
    if(!isset($data) || empty($data)){
        wp_paypal_debug_log('Checkout - Invalid response data from PayPal API order creation', false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Invalid response data from PayPal API order creation.', 'wp-paypal'),
            )
        );
    }
    wp_paypal_debug_log('Response data from order creation', true);
    wp_paypal_debug_log_array($data, true);
    if(!isset($data->id) || empty($data->id)){
        wp_paypal_debug_log('Checkout - No order ID from PayPal API order creation', false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('No order ID from PayPal API order creation.', 'wp-paypal'),
            )
        );
    }
    $paypal_order_id = $data->id;
    wp_send_json( 
        array( 
            'success' => true,
            'order_id' => $paypal_order_id,
            'additional_data' => array(),
        )
    );
}

function wp_paypal_checkout_get_paypal_access_token() {
    $options = wp_paypal_checkout_get_option();
    $url = 'https://api-m.paypal.com/v1/oauth2/token';
    $client_id = $options['app_client_id'];
    $secret_key = $options['app_secret_key'];
    if(isset($options['test_mode']) && $options['test_mode'] == "1"){
        $url = 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
        $client_id = $options['app_sandbox_client_id'];
        $secret_key = $options['app_sandbox_secret_key'];
    }
    if(!isset($client_id) || empty($client_id)){
        wp_paypal_debug_log('Checkout - No client ID. Access token cannot be created.', false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Failed to create an access token using PayPal API.', 'wp-paypal'),
            )
        );
    }
    if(!isset($secret_key) || empty($secret_key)){
        wp_paypal_debug_log('Checkout - No secret key. Access token cannot be created.', false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Failed to create an access token using PayPal API.', 'wp-paypal'),
            )
        );
    }
    $secret_key = base64_decode($secret_key);
    $auth = base64_encode($client_id . ':' . $secret_key);
    wp_paypal_debug_log('Creating access token', true);
    $response = wp_safe_remote_post($url, array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ),
        'body' => 'grant_type=client_credentials'
    ));

    if (is_wp_error($response)) {
        wp_paypal_debug_log('Error response', false);
        wp_paypal_debug_log_array($response, false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Failed to create an access token using PayPal API.', 'wp-paypal'),
            )
        );
    }

    $body = wp_remote_retrieve_body($response);
    if(!isset($body) || empty($body)){
        wp_paypal_debug_log('Error response from invalid body', false);
        wp_paypal_debug_log_array($response, false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Invalid response body when creating an access token using PayPal API.', 'wp-paypal'),
            )
        );
    }
    $data = json_decode($body);
    wp_paypal_debug_log('Response data for access token', true);
    wp_paypal_debug_log_array($data, true);
    if(!isset($data->access_token) || empty($data->access_token)){
        wp_paypal_debug_log('No valid access token from PayPal API response', false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('No valid access token from PayPal API response.', 'wp-paypal'),
            )
        );
    }

    return $data->access_token;
}

function wp_paypal_checkout_pp_api_capture_order(){
    $json_pp_bn_data = isset( $_POST['data'] ) ? stripslashes_deep( $_POST['data'] ) : '{}';
    $array_pp_bn_data = json_decode( $json_pp_bn_data, true );
    $order_id = isset( $array_pp_bn_data['order_id'] ) ? sanitize_text_field($array_pp_bn_data['order_id']) : '';
    $checkoutvar = isset( $array_pp_bn_data['checkoutvar'] ) ? $array_pp_bn_data['checkoutvar'] : array();
    wp_paypal_debug_log('Checkout - PayPal capture order request received - PayPal order ID: ' . $order_id, true);
    if(empty($order_id)){
        wp_paypal_debug_log('Checkout - Empty order ID received from PayPal capture order request', false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Error! Empty order ID received for PayPal capture order request.', 'wp-paypal'),
            )
        );
    }
    wp_paypal_debug_log("Creating data to send to PayPal for capturing the order: ", true);
    $api_params = array( 'order_id' => $order_id );
    $json_api_params = json_encode($api_params);  
    wp_paypal_debug_log_array($json_api_params, true);  
    $access_token = wp_paypal_checkout_get_paypal_access_token();
    if (!$access_token) {
        wp_paypal_debug_log('Checkout - Access token could not be created using PayPal API', false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Access token could not be created using PayPal API.', 'wp-paypal'),
            )
        );
    }
    $options = wp_paypal_checkout_get_option();
    $url = 'https://api-m.paypal.com/v2/checkout/orders';
    if(isset($options['test_mode']) && $options['test_mode'] == "1"){
        $url = 'https://api-m.sandbox.paypal.com/v2/checkout/orders';
    }
    $url .= '/'.$order_id.'/capture';
    $response = wp_safe_remote_post($url, array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ),
        'body' => $json_api_params
    ));
    if (is_wp_error($response)) {
        wp_paypal_debug_log('Error response', false);
        wp_paypal_debug_log_array($response, false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Failed to capture the order using PayPal API.', 'wp-paypal'),
            )
        );
    }

    $body = wp_remote_retrieve_body($response);
    if(!isset($body) || empty($body)){
        wp_paypal_debug_log('Error response from invalid body', false);
        wp_paypal_debug_log_array($response, false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Invalid response body from PayPal API order capture.', 'wp-paypal'),
            )
        );
    }
    $capture_response_data = json_decode($body, true);
    if(!isset($capture_response_data) || empty($capture_response_data)){
        wp_paypal_debug_log('Empty response data', false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Empty response data from PayPal API order capture.', 'wp-paypal'),
            )
        );
    }
    wp_paypal_debug_log('Response data from order capture', true);
    wp_paypal_debug_log_array($capture_response_data, true);
    //
    wp_paypal_debug_log('Checkout - Retrieving order details', true);
    $url = 'https://api-m.paypal.com/v2/checkout/orders';
    if(isset($options['test_mode']) && $options['test_mode'] == "1"){
        $url = 'https://api-m.sandbox.paypal.com/v2/checkout/orders';
    }
    $url .= '/'.$order_id;
    $order_response = wp_safe_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ),
    ));
    if (is_wp_error($order_response)) {
        wp_paypal_debug_log('Error response', false);
        wp_paypal_debug_log_array($order_response, false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Failed to retrieve order details using PayPal API.', 'wp-paypal'),
            )
        );
    }
    $order_body = wp_remote_retrieve_body($order_response);
    if(!isset($order_body) || empty($order_body)){
        wp_paypal_debug_log('Error response from invalid body', false);
        wp_paypal_debug_log_array($order_response, false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Invalid response body from retrieving order details using PayPal API.', 'wp-paypal'),
            )
        );
    }
    $order_details_data = json_decode($order_body, true);
    if(!isset($order_details_data) || empty($order_details_data)){
        wp_paypal_debug_log('Empty response data from retrieving order details', false);
        wp_send_json(
            array(
                'success' => false,
                'err_msg' => __('Empty response data from PayPal API order details.', 'wp-paypal'),
            )
        );
    }
    wp_paypal_debug_log('Response data from retrieving order details', true);
    wp_paypal_debug_log_array($order_details_data, true);
    //
    do_action('wp_paypal_checkout_process_order', $order_details_data, $checkoutvar);
    wp_send_json_success();  
}

function wp_paypal_checkout_process_order_handler($order_details_data, $checkoutvar)
{
    if(!isset($order_details_data['payer'])){
        wp_paypal_debug_log("Checkout - No payer data. This payment cannot be processed.", false);
        return;
    }
    $payer = $order_details_data['payer'];
    if(!isset($order_details_data['purchase_units'][0])){
        wp_paypal_debug_log("Checkout - No purchase unit data. This payment cannot be processed.", false);
        return;
    }
    $purchase_units = $order_details_data['purchase_units'][0];
    if(!isset($purchase_units['payments']['captures'][0])){
        wp_paypal_debug_log("Checkout - No payment capture data. This payment cannot be processed.", false);
        return;
    }
    $capture = $purchase_units['payments']['captures'][0];
    $payment_status = '';
    if (isset($capture['status'])) {
        $payment_status = sanitize_text_field($capture['status']);
        wp_paypal_debug_log("Checkout - Payment Status - " . $payment_status, true);
    }
    if (isset($capture['status']['status_details']['reason'])) {
        $status_reason = sanitize_text_field($capture['status']['status_details']['reason']);
        wp_paypal_debug_log("Checkout - Reason - " . $status_reason, true);
    }
    $payment_data = array();
    $payment_data['txn_id'] = '';
    if (isset($capture['id'])) {
        $payment_data['txn_id'] = sanitize_text_field($capture['id']);
    } else {
        wp_paypal_debug_log("Checkout - No transaction ID. This payment cannot be processed.", false);
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
        wp_paypal_debug_log("Checkout - An order with this transaction ID already exists. This payment will not be processed.", false);
        return;
    } 
    $payer_name = '';
    $payment_data['given_name'] = '';
    $payment_data['first_name'] = '';
    if (isset($payer['name']['given_name'])) {
        $payment_data['given_name'] = sanitize_text_field($payer['name']['given_name']);
        $given_name_parts = explode(" ", $payment_data['given_name']);
        if(isset($given_name_parts[0]) && !empty($given_name_parts[0])){
            $payment_data['first_name'] = sanitize_text_field($given_name_parts[0]);
        }
        $payer_name .= $payment_data['given_name'];
    }
    $payment_data['last_name'] = '';
    if (isset($payer['name']['surname'])) {
        $payment_data['last_name'] = sanitize_text_field($payer['name']['surname']);
        $payer_name .= ' '.$payment_data['last_name'];
    }
    $payment_data['payer_email'] = '';
    if (isset($payer['email_address'])) {
        $payment_data['payer_email'] = sanitize_email($payer['email_address']);
    }
    $payment_data['item_names'] = '';
    $payment_data['description'] = '';
    if (isset($purchase_units['description'])) {
        $payment_data['description'] = sanitize_text_field($purchase_units['description']);
        $payment_data['item_names'] = $payment_data['description'];
    }
    $payment_data['mc_gross'] = '';
    if (isset($purchase_units['amount']['value'])) {
        $payment_data['mc_gross'] = sanitize_text_field($purchase_units['amount']['value']);
    }
    $payment_data['currency_code'] = '';
    if (isset($purchase_units['amount']['currency_code'])) {
        $payment_data['currency_code'] = sanitize_text_field($purchase_units['amount']['currency_code']);
    }
    $payment_data['custom'] = '';
    if (isset($checkoutvar['custom']) && !empty($checkoutvar['custom'])) {
        $payment_data['custom'] = sanitize_text_field($checkoutvar['custom']);
    } 
    $payment_data['variation'] = '';
    if (isset($checkoutvar['variation']) && !empty($checkoutvar['variation'])) {
        $payment_data['variation'] = sanitize_text_field($checkoutvar['variation']);
    }
    $payment_data['shipping_name'] = '';
    if (isset($purchase_units['shipping']['name'])) {
        $payment_data['shipping_name'] = isset($purchase_units['shipping']['name']['full_name']) ? sanitize_text_field($purchase_units['shipping']['name']['full_name']) : '';
    }
    $ship_to = '';
    $shipping_address = '';
    if (isset($purchase_units['shipping']['address'])) {
        $address_street = isset($purchase_units['shipping']['address']['address_line_1']) ? sanitize_text_field($purchase_units['shipping']['address']['address_line_1']) : '';
        $ship_to .= !empty($address_street) ? $address_street.'<br />' : '';
        $shipping_address .= !empty($address_street) ? $address_street.', ' : '';
        
        $address_city = isset($purchase_units['shipping']['address']['admin_area_2']) ? sanitize_text_field($purchase_units['shipping']['address']['admin_area_2']) : '';
        $ship_to .= !empty($address_city) ? $address_city.', ' : '';
        $shipping_address .= !empty($address_city) ? $address_city.', ' : '';
        
        $address_state = isset($purchase_units['shipping']['address']['admin_area_1']) ? sanitize_text_field($purchase_units['shipping']['address']['admin_area_1']) : '';
        $ship_to .= !empty($address_state) ? $address_state.' ' : '';
        $shipping_address .= !empty($address_state) ? $address_state.' ' : '';
        
        $address_zip = isset($purchase_units['shipping']['address']['postal_code']) ? sanitize_text_field($purchase_units['shipping']['address']['postal_code']) : '';
        $ship_to .= !empty($address_zip) ? $address_zip.'<br />' : '';
        $shipping_address .= !empty($address_zip) ? $address_zip.', ' : '';
        
        $address_country = isset($purchase_units['shipping']['address']['country_code']) ? sanitize_text_field($purchase_units['shipping']['address']['country_code']) : '';
        $ship_to .= !empty($address_country) ? $address_country : '';
        $shipping_address .= !empty($address_country) ? $address_country : '';
    }
    $payment_data['shipping_address'] = $shipping_address;
    $wp_paypal_order = array(
        'post_title' => 'order',
        'post_type' => 'wp_paypal_order',
        'post_content' => '',
        'post_status' => 'publish',
    );
    wp_paypal_debug_log("Checkout - Inserting order information", true);
    $post_id = wp_insert_post($wp_paypal_order, true);
    if (is_wp_error($post_id)) {
        wp_paypal_debug_log("Checkout - Error inserting order information: ".$post_id->get_error_message(), false);
        return;
    }
    if (!$post_id) {
        wp_paypal_debug_log("Checkout - Order information could not be inserted", false);
        return;
    }
    $post_updated = false;
    if ($post_id > 0) {
        $post_content = '';
        if(!empty($payment_data['description'])){
            $post_content .= '<strong>Description:</strong> '.$payment_data['description'].'<br />';
        }
        if(isset($payment_data['custom']) && !empty($payment_data['custom'])){
            $post_content .= '<strong>Custom:</strong> '.$payment_data['custom'].'<br />';
        }
        if(isset($payment_data['variation']) && !empty($payment_data['variation'])){
            $post_content .= '<strong>Variation:</strong> '.$payment_data['variation'].'<br />';
        }
        if(!empty($payment_data['mc_gross'])){
            $post_content .= '<strong>Amount:</strong> '.$payment_data['mc_gross'].'<br />';
        }
        if(!empty($payment_data['mc_currency'])){
            $post_content .= '<strong>Currency:</strong> '.$payment_data['mc_currency'].'<br />';
        }
        if(!empty($payer_name)){
            $post_content .= '<strong>Payer Name:</strong> '.$payer_name.'<br />';
        }
        if(!empty($payment_data['payer_email'])){
            $post_content .= '<strong>Email:</strong> '.$payment_data['payer_email'].'<br />';
        }
        if(!empty($ship_to)){
            $ship_to = '<h2>'.__('Ship To', 'wp-paypal').'</h2><br />'.$payment_data['shipping_name'].'<br />'.$ship_to.'<br />';
        }
        $post_content .= $ship_to;
        $post_content .= '<h2>'.__('Payment Data', 'wp-paypal').'</h2><br />';
        $post_content .= print_r($order_details_data, true);
        $updated_post = array(
            'ID' => $post_id,
            'post_title' => $post_id,
            'post_type' => 'wp_paypal_order',
            'post_content' => $post_content
        );
        $updated_post_id = wp_update_post($updated_post, true);
        if (is_wp_error($updated_post_id)) {
            wp_paypal_debug_log("Checkout - Error updating order information: ".$updated_post_id->get_error_message(), false);
            return;
        }
        if (!$updated_post_id) {
            wp_paypal_debug_log("Checkout - Order information could not be updated", false);
            return;
        }
        if ($updated_post_id > 0) {
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
        update_post_meta($post_id, '_custom', $payment_data['custom']);
        update_post_meta($post_id, '_payment_data', $payment_data);
        wp_paypal_debug_log("Checkout - Order information updated", true);
        
        $email_options = wp_paypal_get_email_option();
        add_filter('wp_mail_from', 'wp_paypal_set_email_from');
        add_filter('wp_mail_from_name', 'wp_paypal_set_email_from_name');
        $purchase_email_body = '';
        if(isset($email_options['purchase_email_enabled']) && !empty($email_options['purchase_email_enabled']) && !empty($payment_data['payer_email'])){
            $subject = $email_options['purchase_email_subject'];
            $subject = wp_paypal_do_email_tags($payment_data, $subject);
            $type = $email_options['purchase_email_type'];
            $body = $email_options['purchase_email_body'];
            $body = wp_paypal_do_email_tags($payment_data, $body);
            if($type == "html"){
                add_filter('wp_mail_content_type', 'wp_paypal_set_html_email_content_type');
                $body = apply_filters('wp_paypal_email_body_wpautop', true) ? wpautop($body) : $body;
            }
            //
            if(isset($body) && !empty($body)){
                $purchase_email_body = $body;
            }
            //
            wp_paypal_debug_log("Sending a purchase receipt email to ".$payment_data['payer_email'], true);
            $mail_sent = wp_mail($payment_data['payer_email'], $subject, $body);
            if($type == "html"){
                remove_filter('wp_mail_content_type', 'wp_paypal_set_html_email_content_type');
            }
            if($mail_sent == true){
                wp_paypal_debug_log("Checkout - Email was sent successfully by WordPress", true);
            }
            else{
                wp_paypal_debug_log("Checkout - Email could not be sent by WordPress", false);
            }
        }
        if(isset($email_options['sale_notification_email_enabled']) && !empty($email_options['sale_notification_email_enabled']) && !empty($email_options['sale_notification_email_recipient'])){
            $subject = $email_options['sale_notification_email_subject'];
            $subject = wp_paypal_do_email_tags($payment_data, $subject);
            $type = $email_options['sale_notification_email_type'];
            $body = $email_options['sale_notification_email_body'];
            $body = wp_paypal_do_email_tags($payment_data, $body);
            if($type == "html"){
                add_filter('wp_mail_content_type', 'wp_paypal_set_html_email_content_type');
                $body = apply_filters('wp_paypal_email_body_wpautop', true) ? wpautop($body) : $body;
            }
            //
            if(isset($email_options['sale_notification_email_append_purchase_email']) && !empty($email_options['sale_notification_email_append_purchase_email'])){
                $appended_content = PHP_EOL.PHP_EOL.'---Purchase Receipt Email---'.PHP_EOL.PHP_EOL;
                if($type == "html"){
                    $appended_content = wpautop($appended_content);
                }
                $appended_content .= $purchase_email_body;
                $body .= $appended_content;
            }
            //
            $email_recipients = explode(",", $email_options['sale_notification_email_recipient']);
            foreach($email_recipients as $email_recipient){
                $to = sanitize_email($email_recipient);
                if(is_email($to)){
                    wp_paypal_debug_log("Checkout - Sending a sale notification email to ".$to, true);
                    $mail_sent = wp_mail($to, $subject, $body);
                    if($mail_sent == true){
                        wp_paypal_debug_log("Checkout - Email was sent successfully by WordPress", true);
                    }
                    else{
                        wp_paypal_debug_log("Checkout - Email could not be sent by WordPress", false);
                    }
                }
            }
            if($type == "html"){
                remove_filter('wp_mail_content_type', 'wp_paypal_set_html_email_content_type');
            }
        }
        remove_filter('wp_mail_from', 'wp_paypal_set_email_from');
        remove_filter('wp_mail_from_name', 'wp_paypal_set_email_from_name');
        
        $order_details_data['post_order_id'] = $post_id;
        do_action('wp_paypal_checkout_order_processed', $payment_data, $order_details_data);
    } else {
        wp_paypal_debug_log("Checkout - Order information could not be updated", false);
        return;
    }
    wp_paypal_debug_log("Checkout - Payment processing completed", true, true);   
    return;    
}
