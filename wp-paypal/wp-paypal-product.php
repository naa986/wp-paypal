<?php

if (!defined('ABSPATH')) {
    exit;
}

function wp_paypal_product_page() {
    $labels = array(
        'name'               => __('Products', 'wp-paypal'),
        'singular_name'      => __('Product', 'wp-paypal'),
        'menu_name'          => __('Products', 'wp-paypal'),
        'name_admin_bar'     => __('Product', 'wp-paypal'),
        'add_new'            => __('Add New', 'wp-paypal'),
        'add_new_item'       => __('Add New Product', 'wp-paypal'),
        'new_item'           => __('New Product', 'wp-paypal'),
        'edit_item'          => __('Edit Product', 'wp-paypal'),
        'view_item'          => __('View Product', 'wp-paypal'),
        'all_items'          => __('Products', 'wp-paypal'),
        'search_items'       => __('Search Products', 'wp-paypal'),
        'parent_item_colon'  => __('Parent Products:', 'wp-paypal'),
        'not_found'          => __('No Products found.', 'wp-paypal'),
        'not_found_in_trash' => __('No products found in Trash.', 'wp-paypal')
    );
    
    $capability = 'manage_options';
    $capabilities = array(
        'edit_post'              => $capability,
        'read_post'              => $capability,
        'delete_post'            => $capability,
        'create_posts'           => $capability,
        'edit_posts'             => $capability,
        'edit_others_posts'      => $capability,
        'publish_posts'          => $capability,
        'read_private_posts'     => $capability,
        'read'                   => $capability,
        'delete_posts'           => $capability,
        'delete_private_posts'   => $capability,
        'delete_published_posts' => $capability,
        'delete_others_posts'    => $capability,
        'edit_private_posts'     => $capability,
        'edit_published_posts'   => $capability
    );
    
    $args = array(
        'labels'              => $labels,
        'public'              => false,
        'menu_icon'           => 'dashicons-products',
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'show_ui'             => true,
        'show_in_nav_menus'   => false,
        'show_in_menu'        => current_user_can('manage_options') ? 'edit.php?post_type=wp_paypal_order' : false,
        'query_var'           => false,
        'rewrite'             => false,
        'capabilities'        => $capabilities,
        'has_archive'         => false,
        'hierarchical'        => false,
        'menu_position'       => null,
        'supports'            => array('title', 'editor')
    );

    register_post_type('wp_paypal_product', $args);
}

function wp_paypal_product_columns($columns) {
    unset($columns['date']);
    $edited_columns = array(
        'title'      => __('Product Name', 'wp-paypal'),
        'product_id' => __('Product ID', 'wp-paypal'),
        'price'      => __('Price', 'wp-paypal'),
        'shipping'   => __('Shipping', 'wp-paypal'),
        'shortcode'  => __('Shortcode', 'wp-paypal'),
        'date'       => __('Date', 'wp-paypal')
    );
    return array_merge($columns, $edited_columns);
}

function wp_paypal_product_custom_column($column, $post_id) {
    switch ($column) {
        case 'product_id':
            echo esc_html($post_id);
            break;
        case 'price':
            $price = get_post_meta($post_id, '_wppaypal_product_price', true);
            if (isset($price) && is_numeric($price)) {
                echo esc_html($price);
            } else {
                echo '&#8212;';
            }
            break;
        case 'shipping':
            $shipping = get_post_meta($post_id, '_wppaypal_product_shipping', true);
            if (isset($shipping) && is_numeric($shipping) && $shipping > 0) {
                echo esc_html($shipping);
            } else {
                echo '&#8212;';
            }
            break;
        case 'shortcode':
            $shortcode = '[wp_paypal_product id="'.$post_id.'"]';
            echo esc_html($shortcode);
            break;
    }
}

function wppaypal_product_meta_boxes($post) {
    $post_type = 'wp_paypal_product';
    add_meta_box(
        'wppaypal_product_details',
        __('Product Details', 'wp-paypal'),
        'wppaypal_render_product_details_meta_box',
        $post_type,
        'normal',
        'high'
    );
}

function wppaypal_render_product_details_meta_box($post) {
    $post_id = $post->ID;
    $price = get_post_meta($post_id, '_wppaypal_product_price', true);
    if(!isset($price) || !is_numeric($price)){
        $price = '';
    }
    $shipping = get_post_meta($post_id, '_wppaypal_product_shipping', true);
    if(!isset($shipping) || !is_numeric($shipping)){
        $shipping = '';
    }
    $button_text = get_post_meta($post_id, '_wppaypal_product_button_text', true);
    if(!isset($button_text) || empty($button_text)){
        $button_text = '';
    }
    ?>
    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row">
                    <label for="_wppaypal_product_price"><?php _e('Price', 'wp-paypal'); ?></label>
                </th>
                <td>
                    <input name="_wppaypal_product_price" type="text" id="_wppaypal_product_price" value="<?php echo esc_attr($price); ?>" class="regular-text" required>
                    <p class="description"><?php _e('The price of the product (required). Example: 7.75', 'wp-paypal'); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <label for="_wppaypal_product_shipping"><?php _e('Shipping Cost', 'wp-paypal'); ?></label>
                </th>
                <td>
                    <input name="_wppaypal_product_shipping" type="text" id="_wppaypal_product_shipping" value="<?php echo esc_attr($shipping); ?>" class="regular-text">
                    <p class="description"><?php _e('shipping charge for this product (optional). Example: 1.65', 'wp-paypal'); ?></p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <label for="_wppaypal_product_button_text"><?php _e('Button Text', 'wp-paypal'); ?></label>
                </th>
                <td>
                    <input name="_wppaypal_product_button_text" type="text" id="_wppaypal_product_button_text" value="<?php echo esc_attr($button_text); ?>" class="regular-text">
                    <p class="description"><?php _e('The text displayed on the payment button (optional). Example: Buy Now', 'wp-paypal'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    wp_nonce_field(basename(__FILE__), 'wppaypal_product_details_meta_box_nonce');
}

function wppaypal_product_details_meta_box_save($post_id, $post) {
    if (!isset($_POST['wppaypal_product_details_meta_box_nonce']) || !wp_verify_nonce($_POST['wppaypal_product_details_meta_box_nonce'], basename(__FILE__))) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit'])) {
        return;
    }
    if (isset($post->post_type) && 'revision' == $post->post_type) {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['_wppaypal_product_price'])) {
        $price = sanitize_text_field($_POST['_wppaypal_product_price']);
        update_post_meta($post_id, '_wppaypal_product_price', $price);
    }
    if (isset($_POST['_wppaypal_product_shipping'])) {
        $shipping = sanitize_text_field($_POST['_wppaypal_product_shipping']);
        update_post_meta($post_id, '_wppaypal_product_shipping', $shipping);
    }
    if (isset($_POST['_wppaypal_product_button_text'])) {
        $button_text = sanitize_text_field($_POST['_wppaypal_product_button_text']);
        update_post_meta($post_id, '_wppaypal_product_button_text', $button_text);
    }
}

add_action('save_post_wp_paypal_product', 'wppaypal_product_details_meta_box_save', 10, 2);

function wppaypal_get_product($product_id) {
    if (empty($product_id) || !is_numeric($product_id)) {
        return null;
    }
    $post = get_post($product_id);
    if (!$post || $post->post_type !== 'wp_paypal_product') {
        return null;
    }

    $price = get_post_meta($product_id, '_wppaypal_product_price', true);
    $shipping = get_post_meta($product_id, '_wppaypal_product_shipping', true);
    $button_text = get_post_meta($product_id, '_wppaypal_product_button_text', true);

    return array(
        'id'          => $post->ID,
        'title'       => get_the_title($post->ID),
        'description' => $post->post_content,
        'price'       => $price,
        'shipping'    => $shipping,
        'button_text' => $button_text
    );
}
