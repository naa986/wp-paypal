<?php

function wp_paypal_product_button_handler($atts){
    if(!is_wp_paypal_checkout_configured()){
        return __('You need to configure checkout options in the settings', 'wp-paypal');
    }
    $atts = is_array($atts) ? array_map('sanitize_text_field', $atts) : array();
    $product_id = isset($atts['id']) ? sanitize_text_field($atts['id']) : '';
    if (empty($product_id)) {
        return __('You need to provide a product ID.', 'wp-paypal');
    }
    $product = function_exists('wppaypal_get_product') ? wppaypal_get_product($product_id) : null;
    if (!$product) {
        return __('Product not found.', 'wp-paypal');
    }
    $options = wp_paypal_checkout_get_option();
    $action_url = $options['checkout_page_url'];
    $button_text = 'Buy Now';
    if(isset($product['button_text']) && !empty($product['button_text'])){
        $button_text = $product['button_text'];
    }
    $button_code = '';
    $method = 'post';
    $target = '';
    if(isset($atts['target']) && !empty($atts['target'])) {
        $target = 'target="'.esc_attr($atts['target']).'" ';
    }
    $form_class = '';
    if(isset($atts['form_class']) && !empty($atts['form_class'])) {
        $form_class = 'class="'.esc_attr($atts['form_class']).'" ';
    }
    $button_code .= '<form '.$form_class.$target.'action="'.esc_url($action_url).'" method="'.$method.'" >';
    $button_code .= '<input type="hidden" name="wppp_prod_id" value="'.esc_attr($product_id).'">';
    $button_code .= '<input type="submit" value="'.esc_attr($button_text).'" />';
    $button_code .= '</form>';
    return $button_code;        
}

function wp_paypal_checkout_button_handler($atts) {
    if(!is_wp_paypal_checkout_configured()){
        return __('You need to configure checkout options in the settings', 'wp-paypal');
    }
    $atts = is_array($atts) ? array_map('sanitize_text_field', $atts) : array();
    $options = wp_paypal_checkout_get_option();

    // Product ID is required in URL query parameters
    $product_id = '';
    if(isset($_POST['wppp_prod_id'])){
        $product_id = sanitize_text_field($_POST['wppp_prod_id']);
    }
    else{
        return '';
    }
    //
    if (empty($product_id)) {
        return __('You need to provide a valid product ID', 'wp-paypal');
    }

    $product = function_exists('wppaypal_get_product') ? wppaypal_get_product($product_id) : null;
    if (!$product) {
        return __('Product not found', 'wp-paypal');
    }
    //
    if (!isset($product['title']) || empty($product['title'])) {
        return __('Product title not found', 'wp-paypal');
    }
    //
    $product_price = 0;
    if (isset($product['price']) && is_numeric($product['price']) && $product['price'] > 0 ) {
        $product_price = number_format($product['price'], 2, '.', '');
    }
    else{
        return __('Product price is not valid', 'wp-paypal');
    }
    //
    $shipping = 0;
    $has_shipping = false;
    if (isset($product['shipping']) && is_numeric($product['shipping']) && $product['shipping'] > 0 ) {
        $shipping = number_format($product['shipping'], 2, '.', '');
        $has_shipping = true;
    }
    //
    $currency = $options['currency_code'];
    if (!isset($currency) || empty($currency)) {
        return __('Currency not found', 'wp-paypal');
    }
    //
    $return_url = $options['return_url'];
    if (!isset($return_url) || empty($return_url)) {
        return __('Return URL not found', 'wp-paypal');
    }
    $return_output = '';
    if(!empty($return_url)){
        //$return_output = 'window.location.replace("'.esc_js(esc_url($return_url)).'");';
        $return_output = "let temp_return_url = '".esc_js(esc_url($return_url))."';";
	$return_output .= "let return_url = temp_return_url.replace(/&#038;/g, '&');";
        $return_output .= "window.location.replace(return_url);";
    }
    //
    $cancel_url = $options['cancel_url'];
    if (!isset($cancel_url) || empty($cancel_url)) {
        return __('Cancel URL not found', 'wp-paypal');
    }
    $cancel_output = '';
    if(!empty($cancel_url)){
        //$cancel_output = 'window.location.replace("'.esc_js(esc_url($cancel_url)).'");';
        $cancel_output = "let temp_cancel_url = '".esc_js(esc_url($cancel_url))."';";
	$cancel_output .= "let cancel_url = temp_cancel_url.replace(/&#038;/g, '&');";
        $cancel_output .= "window.location.replace(cancel_url);";
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
    $esc_js = 'esc_js';
    $button_id = 'wppaypalcheckout-button-'.$id;
    $button_container_id = 'wppaypalcheckout-button-container-'.$id;

    // Optional order summary box when product info is loaded
    if ($product && (!isset($atts['show_summary']) || $atts['show_summary'] !== '0')) {
        $button_code .= '<div class="wppaypal-order-summary" style="margin-bottom: 15px; padding: 12px 16px; border: 1px solid #e0e0e0; border-radius: 6px; background-color: #f9f9f9; max-width: ' . esc_attr($width) . 'px;">';
        $button_code .= '<h4 style="margin: 0 0 8px 0; font-size: 16px; color: #333;">' . esc_html($product['title']) . '</h4>';
        $button_code .= '<div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 14px; color: #555;"><span>' . __('Price:', 'wp-paypal') . '</span><span>' . esc_html($product_price . ' ' . $currency) . '</span></div>';
        if ($has_shipping) {
            $button_code .= '<div style="display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 14px; color: #555;"><span>' . __('Shipping:', 'wp-paypal') . '</span><span>' . esc_html($shipping . ' ' . $currency) . '</span></div>';
            $total_amount = $product_price + $shipping;
            $total_amount = number_format($total_amount, 2, '.', '');
            $button_code .= '<div style="display: flex; justify-content: space-between; margin-top: 6px; padding-top: 6px; border-top: 1px dashed #ccc; font-weight: bold; font-size: 14px; color: #222;"><span>' . __('Total:', 'wp-paypal') . '</span><span>' . esc_html($total_amount . ' ' . $currency) . '</span></div>';
        }
        $button_code .= '</div>';
    }

    $button_code .= '<div id="'.esc_attr($button_container_id).'" style="'.esc_attr('max-width: '.$width.'px;').'">';
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
            var checkoutvar = {};

            var purchase_units = [];
            purchase_units[0] = {};
   
            function validate(event) {
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

                },  
                
                onClick: function () {
                    purchase_units[0].custom_id = '{$esc_js($product_id)}';
                },    
                    
                createOrder: async function(data, actions) {
                    var order_data = {
                        intent: 'CAPTURE',
                        payment_source: {
                            paypal: {
                                experience_context: {
                                    payment_method_preference: 'IMMEDIATE_PAYMENT_REQUIRED',
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
    $options['checkout_page_url'] = '';
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
    if(!isset($options['checkout_page_url']) || empty($options['checkout_page_url'])){
        $configured = false;
    }
    return $configured;
}
