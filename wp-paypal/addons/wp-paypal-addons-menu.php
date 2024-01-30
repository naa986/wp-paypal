<?php

function wp_paypal_display_addons_menu()
{
    echo '<div class="wrap">';
    echo '<h2>' .__('WP PayPal Add-ons', 'wp-paypal') . '</h2>';
    
    $addons_data = array();

    $addon_1 = array(
        'name' => 'Variable Price',
        'thumbnail' => WP_PAYPAL_URL.'/addons/images/wp-paypal-variable-price.png',
        'description' => "Let buyers set the amount they will pay for your PayPal Checkout buttons",
        'page_url' => 'https://wphowto.net/wp-paypal-variable-price-6988',
    );
    array_push($addons_data, $addon_1);
    
    $addon_2 = array(
        'name' => 'Product Variations',
        'thumbnail' => WP_PAYPAL_URL.'/addons/images/wp-paypal-product-variations.png',
        'description' => 'Set up variations for your WP PayPal buttons',
        'page_url' => 'https://wphowto.net/how-to-configure-product-variations-in-wp-paypal-6413',
    );
    array_push($addons_data, $addon_2);
    
    $addon_3 = array(
        'name' => 'Custom Input',
        'thumbnail' => WP_PAYPAL_URL.'/addons/images/wp-paypal-custom-input.png',
        'description' => 'Show a text box at checkout to collect custom data from buyers',
        'page_url' => 'https://wphowto.net/how-to-show-a-text-box-at-wp-paypal-checkout-to-collect-custom-data-from-buyers-6517',
    );
    array_push($addons_data, $addon_3);
    
    $addon_4 = array(
        'name' => 'Variable Quantity',
        'thumbnail' => WP_PAYPAL_URL.'/addons/images/wp-paypal-variable-quantity.png',
        'description' => 'Allow buyers to enter a quantity for your WP PayPal Buy Now buttons',
        'page_url' => 'https://wphowto.net/how-to-add-a-quantity-field-to-a-paypal-button-6428',
    );
    array_push($addons_data, $addon_4);
    
    $addon_5 = array(
        'name' => 'Variable Subscription',
        'thumbnail' => WP_PAYPAL_URL.'/addons/images/wp-paypal-variable-subscription.png',
        'description' => 'Let buyers set the amount they will pay for your PayPal Subscribe buttons',
        'page_url' => 'https://wphowto.net/how-to-add-a-recurring-price-field-to-a-paypal-subscription-button-6450',
    );
    array_push($addons_data, $addon_5);
    
    $addon_6 = array(
        'name' => 'Mailchimp Integration',
        'thumbnail' => WP_PAYPAL_URL.'/addons/images/wp-paypal-mailchimp-integration.png',
        'description' => "Automatically add the buyer's email address to your Mailchimp Audience / List after a payment",
        'page_url' => 'https://wphowto.net/wp-paypal-mailchimp-integration-6559',
    );
    array_push($addons_data, $addon_6);
    
    $addon_7 = array(
        'name' => 'Contact Form 7 Integration',
        'thumbnail' => WP_PAYPAL_URL.'/addons/images/wp-paypal-contact-form-7-integration.png',
        'description' => "Show a pre-configured WP PayPal button after a Contact Form 7 submission",
        'page_url' => 'https://wphowto.net/wp-paypal-button-integration-with-contact-form-7-6710',
    );
    array_push($addons_data, $addon_7);
    
    $addon_8 = array(
        'name' => 'Custom Donations',
        'thumbnail' => WP_PAYPAL_URL.'/addons/images/wp-paypal-custom-donations.png',
        'description' => "Allow visitors to set a donation amount for your PayPal Donate buttons",
        'page_url' => 'https://wphowto.net/custom-donations-for-wordpress-paypal-donate-buttons-6778',
    );
    array_push($addons_data, $addon_8);
    
    $addon_9 = array(
        'name' => 'Buy Now Custom Amount',
        'thumbnail' => WP_PAYPAL_URL.'/addons/images/wp-paypal-buy-now-custom-amount.png',
        'description' => 'Allow buyers to enter a custom price amount for your WP PayPal Buy Now buttons',
        'page_url' => 'https://wphowto.net/how-to-add-a-custom-price-field-to-a-paypal-buy-now-button-6800',
    );
    array_push($addons_data, $addon_9);

    $addon_10 = array(
        'name' => 'Buy Now Discount',
        'thumbnail' => WP_PAYPAL_URL.'/addons/images/wp-paypal-buy-now-discount.png',
        'description' => 'Set up automatic discounts for your WP PayPal Buy Now buttons',
        'page_url' => 'https://wphowto.net/applying-discount-to-a-paypal-buy-button-in-wordpress-834',
    );
    array_push($addons_data, $addon_10);
    
    //Display the list
    foreach ($addons_data as $addon) {
        ?>
        <div class="wp_paypal_addons_item_canvas">
        <div class="wp_paypal_addons_item_thumb">
            <img src="<?php echo esc_url($addon['thumbnail']);?>" alt="<?php echo esc_attr($addon['name']);?>">
        </div>
        <div class="wp_paypal_addons_item_body">
        <div class="wp_paypal_addons_item_name">
            <a href="<?php echo esc_url($addon['page_url']);?>" target="_blank"><?php echo esc_html($addon['name']);?></a>
        </div>
        <div class="wp_paypal_addons_item_description">
        <?php echo esc_html($addon['description']);?>
        </div>
        <div class="wp_paypal_addons_item_details_link">
        <a href="<?php echo esc_url($addon['page_url']);?>" class="wp_paypal_addons_view_details" target="_blank">View Details</a>
        </div>    
        </div>
        </div>
        <?php
    } 
    echo '</div>';//end of wrap
    
}
