<?php

function wp_paypal_display_extensions_menu()
{
    echo '<div class="wrap">';
    echo '<h2>' .__('WP PayPal Extensions', 'wp-paypal') . '</h2>';
    
    $extensions_data = array();

    $extension_1 = array(
        'name' => 'Buy Now Custom Amount',
        'thumbnail' => WP_PAYPAL_URL.'/extensions/images/wp-paypal-buy-now-custom-amount.png',
        'description' => 'Allow buyers to enter a custom price amount for your WP PayPal Buy Now buttons',
        'page_url' => 'https://wphowto.net/wordpress-paypal-plugin-732',
    );
    array_push($extensions_data, $extension_1);

    $extension_2 = array(
        'name' => 'Buy Now Discount',
        'thumbnail' => WP_PAYPAL_URL.'/extensions/images/wp-paypal-buy-now-discount.png',
        'description' => 'Set up automatic discounts for your WP PayPal Buy Now buttons',
        'page_url' => 'https://wphowto.net/applying-discount-to-a-paypal-buy-button-in-wordpress-834',
    );
    array_push($extensions_data, $extension_2);
    
    $extension_3 = array(
        'name' => 'Product Variations',
        'thumbnail' => WP_PAYPAL_URL.'/extensions/images/wp-paypal-product-variations.png',
        'description' => 'Set up variations for your WP PayPal buttons',
        'page_url' => 'https://wphowto.net/how-to-configure-product-variations-in-wp-paypal-6413',
    );
    array_push($extensions_data, $extension_3);
    
    $extension_4 = array(
        'name' => 'Variable Quantity',
        'thumbnail' => WP_PAYPAL_URL.'/extensions/images/wp-paypal-variable-quantity.png',
        'description' => 'Allow buyers to enter a quantity for your WP PayPal Buy Now buttons',
        'page_url' => 'https://wphowto.net/how-to-add-a-quantity-field-to-a-paypal-button-6428',
    );
    array_push($extensions_data, $extension_4);
    
    $extension_5 = array(
        'name' => 'Variable Subscription',
        'thumbnail' => WP_PAYPAL_URL.'/extensions/images/wp-paypal-variable-subscription.png',
        'description' => 'Let buyers set the amount they will pay for your PayPal Subscribe buttons',
        'page_url' => 'https://wphowto.net/how-to-add-a-recurring-price-field-to-a-paypal-subscription-button-6450',
    );
    array_push($extensions_data, $extension_5);
    
    $extension_6 = array(
        'name' => 'Custom Input',
        'thumbnail' => WP_PAYPAL_URL.'/extensions/images/wp-paypal-custom-input.png',
        'description' => 'Show a text box at checkout to collect custom data from buyers',
        'page_url' => 'https://wphowto.net/how-to-show-a-text-box-at-wp-paypal-checkout-to-collect-custom-data-from-buyers-6517',
    );
    array_push($extensions_data, $extension_6);
    
    //Display the list
    foreach ($extensions_data as $extension) {
        ?>
        <div class="wp_paypal_extensions_item_canvas">
        <div class="wp_paypal_extensions_item_thumb">
            <img src="<?php echo esc_url($extension['thumbnail']);?>" alt="<?php echo esc_attr($extension['name']);?>">
        </div>
        <div class="wp_paypal_extensions_item_body">
        <div class="wp_paypal_extensions_item_name">
            <a href="<?php echo esc_url($extension['page_url']);?>" target="_blank"><?php echo esc_html($extension['name']);?></a>
        </div>
        <div class="wp_paypal_extensions_item_description">
        <?php echo esc_html($extension['description']);?>
        </div>
        <div class="wp_paypal_extensions_item_details_link">
        <a href="<?php echo esc_url($extension['page_url']);?>" class="wp_paypal_extensions_view_details" target="_blank">View Details</a>
        </div>    
        </div>
        </div>
        <?php
    } 
    echo '</div>';//end of wrap
    
}
