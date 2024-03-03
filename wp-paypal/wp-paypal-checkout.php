<?php

function wp_paypal_checkout_button_handler($atts) {
    if(!is_wp_paypal_checkout_configured()){
        return __('You need to configure checkout options in the settings', 'wp-paypal');
    }
    $atts = array_map('sanitize_text_field', $atts);
    if(!isset($atts['description']) || empty($atts['description'])){
        return __('You need to provide a valid description', 'wp-paypal');
    }
    $description = $atts['description'];
    $options = wp_paypal_checkout_get_option();
    $currency = $options['currency_code'];
    /* There seems to be a bug where currency override doesn't work on a per button basis
    if(isset($atts['currency']) && !empty($atts['currency'])){
        $currency = $atts['currency'];
    }
    */
    $return_url = (isset($options['return_url']) && !empty($options['return_url'])) ? $options['return_url'] : '';
    if(isset($atts['return_url']) && !empty($atts['return_url'])){
        $return_url = $atts['return_url'];
    }
    $return_output = '';
    if(!empty($return_url)){
        $return_output = 'window.location.replace("'.$return_url.'");';
    }
    $cancel_url = (isset($options['cancel_url']) && !empty($options['cancel_url'])) ? $options['cancel_url'] : '';
    if(isset($atts['cancel_url']) && !empty($atts['cancel_url'])){
        $cancel_url = $atts['cancel_url'];
    }
    $cancel_output = '';
    if(!empty($cancel_url)){
        $cancel_output = 'window.location.replace("'.$cancel_url.'");';
    }
    $no_shipping = '';
    if(isset($atts['no_shipping']) && $atts['no_shipping']=='1'){
        $no_shipping .= <<<EOT
        application_context: {
            shipping_preference: "NO_SHIPPING",
        },        
EOT;
    }
    $width = '300';
    if(isset($atts['width']) && !empty($atts['width'])){
        $width = $atts['width'];
    }
    $layout = 'vertical';
    if(isset($atts['layout']) && $atts['layout'] == 'horizontal'){
        $layout = 'horizontal';
    }
    $color = 'gold';
    if(isset($atts['color']) && $atts['color'] == 'blue'){
        $color = 'blue';
    }
    else if(isset($atts['color']) && $atts['color'] == 'silver'){
        $color = 'silver';
    }
    else if(isset($atts['color']) && $atts['color'] == 'white'){
        $color = 'white';
    }
    else if(isset($atts['color']) && $atts['color'] == 'black'){
        $color = 'black';
    }
    $shape = 'rect';
    if(isset($atts['shape']) && $atts['shape'] == 'pill'){
        $shape = 'pill';
    }
    $label = 'paypal';
    if(isset($atts['label']) && $atts['label'] == 'checkout'){
        $label = 'checkout';
    }
    else if(isset($atts['label']) && $atts['label'] == 'buynow'){
        $label = 'buynow';
    }
    else if(isset($atts['label']) && $atts['label'] == 'pay'){
        $label = 'pay';
    }
    $id = uniqid();
    $atts['id'] = $id;
    $button_code = '';
    if(!isset($atts['amount']) || !is_numeric($atts['amount'])){
        return __('You need to provide a valid price amount', 'wp-paypal');
    }
    $amount = $atts['amount'];
    $esc_js = 'esc_js';
    $additional_el = '';
    $button_id = 'wppaypalcheckout-button-'.$id;
    $button_container_id = 'wppaypalcheckout-button-container-'.$id;
    $button_code = '<div id="'.esc_attr($button_container_id).'" style="'.esc_attr('max-width: '.$width.'px;').'">';
    //
    $description_code = '<input class="wppaypal_checkout_description_input" type="hidden" name="description" value="'.esc_attr($description).'" required>';
    $description_queryselector = "document.querySelector('#{$button_container_id} .wppaypal_checkout_description_input')";
    $button_code .= $description_code;
    $amount_code = '<input class="wppaypal_checkout_amount_input" type="hidden" name="amount" value="'.esc_attr($amount).'" required>';
    $amount_queryselector = "document.querySelector('#{$button_container_id} .wppaypal_checkout_amount_input')";
    $variable_price_code = '';
    $variable_price_code = apply_filters('wppaypal_checkout_variable_price', $variable_price_code, $button_code, $atts);
    if(!empty($variable_price_code)){
        $amount_code = $variable_price_code;
        $amount_queryselector = "document.querySelector('#{$button_container_id} .wppaypal_checkout_variable_price_input')";
    }
    $button_code .= $amount_code;
    //
    $variation_code = '';
    $variation_queryselector = '""';
    $variation_code = apply_filters('wppaypal_checkout_variations', $variation_code, $button_code, $atts);
    if(!empty($variation_code)){
        $variation_queryselector = "document.querySelector('#{$button_container_id} .variation_select')";
        $additional_el .= ', variation';
        $button_code .= $variation_code;
    }
    //
    $custom_input_code = '';
    $custom_queryselector = '""';
    $custom_input_code = apply_filters('wppaypal_checkout_custom_input', $custom_input_code, $button_code, $atts);
    if(!empty($custom_input_code)){
        $custom_queryselector = "document.querySelector('#{$button_container_id} .wppaypal_checkout_custom_input')";
        $additional_el .= ', custom';
        $button_code .= $custom_input_code;
    }
    //
    $button_code .= '<div id="'.esc_attr($button_id).'" style="'.esc_attr('max-width: '.$width.'px;').'"></div>';
    $button_code .= '</div>';
    $ajax_url = admin_url('admin-ajax.php');
    $button_code .= <<<EOT
    <script>
    jQuery(document).ready(function() {
            
        function initPayPalButton{$id}() {
            var description = {$description_queryselector};
            var amount = {$amount_queryselector};
            var checkoutvar = {};
            var custom = {$custom_queryselector};
            var variation = {$variation_queryselector};
            var elArr = [description, amount{$additional_el}];

            var purchase_units = [];
            purchase_units[0] = {};
            purchase_units[0].amount = {};
   
            function validate(event) {
                if(event.value.length === 0){
                    return false;
                }
                if(event.name == "amount"){
                    if(!isNaN(Number(event.value)) && Number(event.value) < 0.1){
                        return false;
                    }
                }
                if(event.name == "custom"){
                    checkoutvar.custom = event.value;  
                }
                if(event.name == "variation"){
                    var variation_arr = event.value.split("_");
                    if(typeof variation_arr[0] !== 'undefined'){
                        checkoutvar.variation = variation_arr[0];
                    }
                    if(typeof variation_arr[1] !== 'undefined'){
                        amount.value = variation_arr[1];
                    }  
                }
                return true;
            }
            paypal.Buttons({
                style: {
                    layout: '{$layout}',
                    color: '{$color}',
                    shape: '{$shape}',
                    label: '{$label}'
                },
                onInit: function (data, actions) {
                    actions.disable();
                    var validated = true;
                    elArr.forEach(function (item) {
                        if(!validate(item)){
                            validated = false;    
                        }
                        item.addEventListener('change', function (event) {
                            var result = elArr.every(validate);
                            if (result) {
                                actions.enable();
                            } else {
                                actions.disable();
                            }
                        });
                    });
                    if(validated){
                        actions.enable();
                    }
                },  
                
                onClick: function () {
                    purchase_units[0].description = description.value;
                    purchase_units[0].amount.value = amount.value;
                },    
                    
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: purchase_units,
                        $no_shipping    
                    });
                },
                            
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        var data = {
                            'action': "wppaypalcheckout_ajax_process_order",
                            'wppaypalcheckout_ajax_process_order': "1",
                            'checkoutvar': checkoutvar,
                            'details': details 
                        };  
                        jQuery.ajax({
                            url : "{$ajax_url}",
                            type : "POST",
                            data : data,
                            success: function(response) {
                                $return_output
                            }
                        });
                    });
                },
                                    
                onError: function (err) {
                    console.log(err);
                },
                                    
                onCancel: function (data) {
                    $cancel_output
                }
                    
            }).render('#$button_id');
        }
        initPayPalButton{$id}();
    });                     
    </script>        
EOT;
    
    return $button_code;
}

function wp_paypal_checkout_ajax_process_order(){
    wp_paypal_debug_log('Received a response from frontend', true);
    if(!isset($_POST['wppaypalcheckout_ajax_process_order'])){
        wp_die();
    }
    wp_paypal_debug_log('Checkout - Received a notification from PayPal', true);
    $post_data = $_POST;
    array_walk_recursive($post_data, function(&$v) { $v = sanitize_text_field($v); });
    wp_paypal_debug_log_array($post_data, true);
    if(!isset($post_data['details'])){
        wp_paypal_debug_log("Checkout - No transaction details. This payment cannot be processed.", false);
        wp_die();
    }
    //
    do_action('wp_paypal_checkout_process_order', $post_data);
    wp_die();
}

function wp_paypal_checkout_get_option(){
    $options = get_option('wp_paypal_checkout_options');
    if(!is_array($options)){
        $options = wp_paypal_checkout_get_empty_options_array();
    }
    return $options;
}

function wp_paypal_checkout_update_option($new_options){
    $empty_options = wp_paypal_checkout_get_empty_options_array();
    $options = wp_paypal_checkout_get_option();
    if(is_array($options)){
        $current_options = array_merge($empty_options, $options);
        $updated_options = array_merge($current_options, $new_options);
        update_option('wp_paypal_checkout_options', $updated_options);
    }
    else{
        $updated_options = array_merge($empty_options, $new_options);
        update_option('wp_paypal_checkout_options', $updated_options);
    }
}

function wp_paypal_checkout_get_empty_options_array(){
    $options = array();
    $options['app_client_id'] = '';
    $options['currency_code'] = '';
    $options['return_url'] = '';
    $options['cancel_url'] = '';
    $options['enable_funding'] = '';
    $options['disable_funding'] = '';
    return $options;
}

function is_wp_paypal_checkout_configured(){
    $options = wp_paypal_checkout_get_option();
    $configured = true;
    if(!isset($options['app_client_id']) || empty($options['app_client_id'])){
        $configured = false;
    }
    if(!isset($options['currency_code']) || empty($options['currency_code'])){
        $configured = false;
    }
    return $configured;
}

function wp_paypal_checkout_process_order_handler($post_data)
{
    $details = $post_data['details'];
    if(!isset($details['payer'])){
        wp_paypal_debug_log("Checkout - No payer data. This payment cannot be processed.", false);
        return;
    }
    $payer = $details['payer'];
    if(!isset($details['purchase_units'][0])){
        wp_paypal_debug_log("Checkout - No purchase unit data. This payment cannot be processed.", false);
        return;
    }
    $purchase_units = $details['purchase_units'][0];
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
        wp_paypal_debug_log("An order with this transaction ID already exists. This payment will not be processed.", false);
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
    $checkoutvar = $post_data['checkoutvar'];
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
        $post_content .= print_r($details, true);
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
        wp_paypal_debug_log("Checkout - Order information updated", true);
        
        $email_options = wp_paypal_get_email_option();
        add_filter('wp_mail_from', 'wp_paypal_set_email_from');
        add_filter('wp_mail_from_name', 'wp_paypal_set_email_from_name');
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
        
        $details['post_order_id'] = $post_id;
        do_action('wp_paypal_checkout_order_processed', $payment_data, $details);
    } else {
        wp_paypal_debug_log("Checkout - Order information could not be updated", false);
        return;
    }
    wp_paypal_debug_log("Checkout - Payment processing completed", true, true);   
    return;    
}
