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
        //$return_output = 'window.location.replace("'.esc_js(esc_url($return_url)).'");';
        $return_output = "let temp_return_url = '".esc_js(esc_url($return_url))."';";
	$return_output .= "let return_url = temp_return_url.replace(/&#038;/g, '&');";
        $return_output .= "window.location.replace(return_url);";
    }
    $cancel_url = (isset($options['cancel_url']) && !empty($options['cancel_url'])) ? $options['cancel_url'] : '';
    if(isset($atts['cancel_url']) && !empty($atts['cancel_url'])){
        $cancel_url = $atts['cancel_url'];
    }
    $cancel_output = '';
    if(!empty($cancel_url)){
        //$cancel_output = 'window.location.replace("'.esc_js(esc_url($cancel_url)).'");';
        $cancel_output = "let temp_cancel_url = '".esc_js(esc_url($cancel_url))."';";
	$cancel_output .= "let cancel_url = temp_cancel_url.replace(/&#038;/g, '&');";
        $cancel_output .= "window.location.replace(cancel_url);";
    }
    $shipping_preference = 'GET_FROM_FILE';
    if(isset($atts['shipping_preference']) && $atts['shipping_preference'] == 'NO_SHIPPING'){
        $shipping_preference = 'NO_SHIPPING';
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
    $break_down_amount = 'false';
    $shipping = '';
    if(isset($atts['shipping']) && is_numeric($atts['shipping'])){
        $shipping = $atts['shipping'];
        $break_down_amount = 'true';
    }
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
    /*
    2022, 2023, 2024 themes seem to convert front-end JavaScript & to &#038; automatically breaking the PayPal button
    changed the following logic because of this issue: https://core.trac.wordpress.org/ticket/45387#comment:14
    if(shipping.length !== 0 && !isNaN(shipping)){
    */
    $button_code .= <<<EOT
    <script>
    jQuery(document).ready(function() {
            
        function initPayPalButton{$id}() {
            var description = {$description_queryselector};
            var amount = {$amount_queryselector};
            var totalamount = 0;
            var shipping = "{$esc_js($shipping)}";
            var currency = "{$esc_js($currency)}";
            var break_down_amount = {$esc_js($break_down_amount)};
            var checkoutvar = {};
            var custom = {$custom_queryselector};
            var variation = {$variation_queryselector};
            var elArr = [description, amount{$additional_el}];

            var purchase_units = [];
            purchase_units[0] = {};
            purchase_units[0].amount = {};
   
            function validate(event) {
                if(event.required && event.value.length === 0){
                    return false;
                }
                if(event.name == "amount"){
                    if(!isNaN(Number(event.value)) && Number(event.value) < 0.1){
                        return false;
                    }
                }
                if(event.name == "custom"){
                    if(event.value.length !== 0){
                        checkoutvar.custom = event.value;  
                    }
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
                    layout: '{$esc_js($layout)}',
                    color: '{$esc_js($color)}',
                    shape: '{$esc_js($shape)}',
                    label: '{$esc_js($label)}'
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
                    if(break_down_amount){
                        purchase_units[0].amount.breakdown = {};
                        purchase_units[0].amount.breakdown.item_total = {};
                        purchase_units[0].amount.breakdown.item_total.currency_code = currency;
                        purchase_units[0].amount.breakdown.item_total.value = amount;
                    }
                    if(shipping.length !== 0){
                        if(!isNaN(shipping)){
                            purchase_units[0].amount.breakdown.shipping = {};
                            purchase_units[0].amount.breakdown.shipping.currency_code = currency;
                            purchase_units[0].amount.breakdown.shipping.value = shipping;
                            totalamount = parseFloat(amount)+parseFloat(shipping);
                        }
                    }
                    if(totalamount > 0){
                        purchase_units[0].amount.value = String(totalamount);
                    }
                },    
                    
                createOrder: async function(data, actions) {
                    var order_data = {
                        intent: 'CAPTURE',
                        payment_source: {
                            paypal: {
                                experience_context: {
                                    payment_method_preference: 'IMMEDIATE_PAYMENT_REQUIRED',
                                    shipping_preference: '{$esc_js($shipping_preference)}',
                                }
                            }
                        },
                        purchase_units: purchase_units,           
                    };
                    let post_data = 'action=wppaypalcheckout_pp_api_create_order&data=' + encodeURIComponent(JSON.stringify(order_data));
                    try {                
                        const response = await fetch('{$ajax_url}', {
                            method: "post",
                            headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: post_data
                        });

                        const response_data = await response.json();

                        if (response_data.order_id) {
                            console.log('Create-order API call to PayPal completed successfully');
                            return response_data.order_id;
                        } else {
                            const error_message = response_data.err_msg
                            console.error('Error occurred during create-order call to PayPal: ' + error_message);
                            throw new Error(error_message); //This will trigger an alert in the catch block below
                        }
                    } catch (error) {
                        console.error(error.message);
                        alert('Could not initiate PayPal Checkout - ' + error.message);
                    }
                },
                            
                onApprove: async function(data, actions) {
                    console.log('Sending AJAX request for capture-order call');
                    let pp_bn_data = {};
                    pp_bn_data.order_id = data.orderID;
                    pp_bn_data.checkoutvar = checkoutvar;   

                    let post_data = 'action=wppaypalcheckout_pp_api_capture_order&data=' + encodeURIComponent(JSON.stringify(pp_bn_data));
                    try {
                        const response = await fetch('{$ajax_url}', {
                            method: "post",
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: post_data
                        });

                        const response_data = await response.json();
                        if (response_data.success) {
                            console.log('Capture-order API call to PayPal completed successfully');
                            $return_output
                        } else {
                            const error_message = response_data.err_msg
                            console.error('Error: ' + error_message);
                            throw new Error(error_message); //This will trigger an alert in the catch block below
                        }

                    } catch (error) {
                        console.error(error);
                        alert('Order could not be captured. Error: ' + JSON.stringify(error));
                    }
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
    $options['test_mode'] = '';
    $options['app_sandbox_client_id'] = '';
    $options['app_sandbox_secret_key'] = '';
    $options['app_client_id'] = '';
    $options['app_secret_key'] = '';
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
    if(isset($options['test_mode']) && !empty($options['test_mode'])){
        if(!isset($options['app_sandbox_client_id']) || empty($options['app_sandbox_client_id'])){
            $configured = false;
        }
        if(!isset($options['app_sandbox_secret_key']) || empty($options['app_sandbox_secret_key'])){
            $configured = false;
        }
    }
    else{
        if(!isset($options['app_client_id']) || empty($options['app_client_id'])){
            $configured = false;
        }
        if(!isset($options['app_secret_key']) || empty($options['app_secret_key'])){
            $configured = false;
        }
    }
    if(!isset($options['currency_code']) || empty($options['currency_code'])){
        $configured = false;
    }
    return $configured;
}
