<?php

function wp_paypal_display_extensions_menu()
{
    echo '<div class="wrap">';
    echo '<h2>' .__('WP PayPal Extensions', 'wp-paypal') . '</h2>';
    echo '<link type="text/css" rel="stylesheet" href="'.WP_PAYPAL_URL.'/extensions/wp-paypal-extensions-menu.css" />' . "\n";
    
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
    
    //Display the list
    $output = '';
    foreach ($extensions_data as $extension) {
        $output .= '<div class="wp_paypal_extensions_item_canvas">';

        $output .= '<div class="wp_paypal_extensions_item_thumb">';
        $img_src = $extension['thumbnail'];
        $output .= '<img src="' . $img_src . '" alt="' . $extension['name'] . '">';
        $output .= '</div>'; //end thumbnail

        $output .='<div class="wp_paypal_extensions_item_body">';
        $output .='<div class="wp_paypal_extensions_item_name">';
        $output .= '<a href="' . $extension['page_url'] . '" target="_blank">' . $extension['name'] . '</a>';
        $output .='</div>'; //end name

        $output .='<div class="wp_paypal_extensions_item_description">';
        $output .= $extension['description'];
        $output .='</div>'; //end description

        $output .='<div class="wp_paypal_extensions_item_details_link">';
        $output .='<a href="'.$extension['page_url'].'" class="wp_paypal_extensions_view_details" target="_blank">View Details</a>';
        $output .='</div>'; //end detils link      
        $output .='</div>'; //end body

        $output .= '</div>'; //end canvas
    }
    echo $output;
    
    echo '</div>';//end of wrap
}
