<?php

// Defines
define('FL_CHILD_THEME_DIR', get_stylesheet_directory());
define('FL_CHILD_THEME_URL', get_stylesheet_directory_uri());

// Classes
require_once 'classes/class-fl-child-theme.php';

// Actions
add_action('wp_enqueue_scripts', 'FLChildTheme::enqueue_scripts', 1000);

//* Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'parallax_enqueue_scripts_styles', 1000);
function parallax_enqueue_scripts_styles()
{
    // Styles
    wp_enqueue_style('custom', get_stylesheet_directory_uri() . '/style.css', array());
    wp_enqueue_style('custom-style', get_stylesheet_directory_uri() . '/astyle.css', array());
    wp_enqueue_style('fonts', get_stylesheet_directory_uri() . '/fonts/stylesheet.css', array());
    wp_enqueue_script('customjs', get_stylesheet_directory_uri() . '/custom-script.js', array('jquery'));
}

//Remove Gutenberg Block Library CSS from loading on the frontend
function smartwp_remove_wp_block_library_css()
{
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-block-style'); // Remove WooCommerce block CSS
}
add_action('wp_enqueue_scripts', 'smartwp_remove_wp_block_library_css', 100);

add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('font-awesome'); // FontAwesome 4
    wp_enqueue_style('font-awesome-5'); // FontAwesome 5

    //wp_dequeue_style( 'jquery-magnificpopup' );
    //wp_dequeue_script( 'jquery-magnificpopup' );

    wp_dequeue_script('bootstrap');
    //    wp_dequeue_script( 'imagesloaded' ); //Commented by Saqib on 11/16/21
    wp_dequeue_script('jquery-fitvids');
    //    wp_dequeue_script( 'jquery-throttle' ); //Commented by Saqib on 11/16/21
    wp_dequeue_script('jquery-waypoints');
}, 9999);

remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);
function remove_image_zoom_support()
{
    remove_theme_support('wc-product-gallery-zoom');
}
add_action('wp', 'remove_image_zoom_support', 100);


// Remove breadcrumbs from single product pages
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);

// For changing text of tabs in single product page 
function change_product_tabs($tabs)
{
    if (isset($tabs['additional_information'])) {
        $tabs['additional_information']['title'] = __('Additional Product Information', 'woocommerce');
    }
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'change_product_tabs');

// Function to remove sale badge from related products on single product page
function remove_sale_badge_from_related_products($html, $post, $product)
{
    if (is_product()) {
        if ($product->is_on_sale() && is_singular('product')) {
            $html = ''; // Remove the sale badge HTML
        }
    }
    return $html;
}

// Add the filter to remove the sale badge
add_filter('woocommerce_sale_flash', 'remove_sale_badge_from_related_products', 10, 3);


// Function to add custom quantity heading in single product page
function custom_quantity_heading()
{
    echo '<h3 class="quantity-heading">Qty</h3>';
}

// Hook the function to display the quantity heading before the quantity input
add_action('woocommerce_before_add_to_cart_quantity', 'custom_quantity_heading', 9);


//for quantity increment button

add_action('woocommerce_before_add_to_cart_quantity', 'ts_quantity_minus_sign');
function ts_quantity_minus_sign()
{
    echo '<button type="button" class="minus" >-</button>';
}

add_action('woocommerce_after_add_to_cart_quantity', 'ts_quantity_plus_sign');
function ts_quantity_plus_sign()
{
    echo '<button type="button" class="plus" >+</button>';
}

add_action('wp_footer', 'ts_quantity_plus_minus');
function ts_quantity_plus_minus()
{
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            // Function to handle the click event on plus and minus buttons
            function updateQuantity(button) {
                // Get current quantity values
                var qty = button.closest('div').find('.qty');
                var val = parseFloat(qty.val());
                var max = parseFloat(qty.attr('max'));
                var min = parseFloat(qty.attr('min'));
                var step = parseFloat(qty.attr('step'));

                // Change the value if plus or minus
                if (button.is('.plus')) {
                    if (max && (max <= val)) {
                        qty.val(max);
                    } else {
                        qty.val(val + step);
                    }
                } else {
                    if (min && (min >= val)) {
                        qty.val(min);
                    } else if (val > 1) {
                        qty.val(val - step);
                    }
                }
            }

            // Single product page
            $('form.cart').on('click', 'button.plus, button.minus', function () {
                updateQuantity($(this));
            });

            // Product table
            $('.product-table').on('click', 'button.plus, button.minus', function () {
                updateQuantity($(this));
            });
        });
    </script>
    <?php
}


// Remove the default SKU display
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);

function product_code()
{
    global $product;
    $sku = $product->get_sku();

    if ($sku) {
        echo '<p class="product-code">' . 'Product Code: ' . $sku . '<p/>';
    }
}

// Add the new SKU display function
add_action('woocommerce_single_product_summary', 'product_code', 11);


// Add custom fields to the product category add form
function add_custom_fields_to_product_category()
{
    ?>
    <div class="form-field">
        <label for="quantity_discounts"><?php _e('Quantity Discounts Available'); ?></label>
        <textarea name="quantity_discounts" id="quantity_discounts" rows="5" cols="40"></textarea>
        <p class="description"><?php _e('Enter the quantity discounts information in HTML format.'); ?></p>
    </div>
    <div class="form-field">
        <label for="available_to_ship"><?php _e('Available to Ship'); ?></label>
        <input type="text" name="available_to_ship" id="available_to_ship" value="" />
        <p class="description"><?php _e('Enter whether the product is available to ship.'); ?></p>
    </div>
    <?php
}
add_action('product_cat_add_form_fields', 'add_custom_fields_to_product_category', 10, 2);

// Save custom fields when creating a new category
function save_custom_fields_product_category($term_id)
{
    if (isset($_POST['quantity_discounts'])) {
        update_term_meta($term_id, 'quantity_discounts', wp_kses_post($_POST['quantity_discounts']));
    }
    if (isset($_POST['available_to_ship'])) {
        update_term_meta($term_id, 'available_to_ship', sanitize_text_field($_POST['available_to_ship']));
    }
}
add_action('created_product_cat', 'save_custom_fields_product_category', 10, 2);

// Add custom fields to the category edit form
function edit_custom_fields_product_category($term)
{
    $quantity_discounts = get_term_meta($term->term_id, 'quantity_discounts', true);
    $available_to_ship = get_term_meta($term->term_id, 'available_to_ship', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="quantity_discounts"><?php _e('Quantity Discounts Available'); ?></label>
        </th>
        <td>
            <textarea name="quantity_discounts" id="quantity_discounts" rows="5"
                cols="50"><?php echo esc_textarea($quantity_discounts); ?></textarea>
            <p class="description"><?php _e('Enter the quantity discounts information in HTML format.'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="available_to_ship"><?php _e('Available to Ship'); ?></label></th>
        <td>
            <input type="text" name="available_to_ship" id="available_to_ship"
                value="<?php echo esc_attr($available_to_ship); ?>" />
            <p class="description"><?php _e('Enter whether the product is available to ship.'); ?></p>
        </td>
    </tr>
    <?php
}
add_action('product_cat_edit_form_fields', 'edit_custom_fields_product_category', 10, 2);

// Update custom fields when editing an existing category
function update_custom_fields_product_category($term_id)
{
    if (isset($_POST['quantity_discounts'])) {
        update_term_meta($term_id, 'quantity_discounts', wp_kses_post($_POST['quantity_discounts']));
    }
    if (isset($_POST['available_to_ship'])) {
        update_term_meta($term_id, 'available_to_ship', sanitize_text_field($_POST['available_to_ship']));
    }
}
add_action('edited_product_cat', 'update_custom_fields_product_category', 10, 2);

function product_single_line_text()
{
    $product_text = get_field('available-to-ship');

    if (!empty($product_text)) {
        echo '<p class="shipping-availability">' . esc_html($product_text) . '</p>';
    }
}

add_action('woocommerce_single_product_summary', 'product_single_line_text', 11);
function product_custom_field_1()
{
    $purchase_more_than_one = get_field('purchase_more_than_one_and_save_3%_to_15%!');

    if (!empty($purchase_more_than_one)) {
        echo '<p class="custom_field1 shipping-availability">' . esc_html($purchase_more_than_one) . '</p>';
    }
}

add_action('woocommerce_single_product_summary', 'product_custom_field_1', 12);

// add_action('woocommerce_single_product_summary', 'display_acf_wysiwyg_field', 13);

//     function display_acf_wysiwyg_field() {
//         global $product;

//         // Get the product ID
//         $product_id = $product->get_id();

//         // Get the WYSIWYG field value
//         $wysiwyg_field = get_field('quantity_discounts_available!', $product_id);

//         // Check if the WYSIWYG field is not empty
//         if ($wysiwyg_field) {
//             // Display the WYSIWYG field content
//             echo '<div class="custom-wysiwyg-content">' . '<strong class="quantity-head">Quantity discounts available!</strong> ' . $wysiwyg_field . '</div>';
//         }
//     }


/**
 * Remove product category main title on product category detail page.
 */
function remove_product_category_main_title()
{
    if (is_product_category() || is_shop()) {
        return false;
    }
    return true;
}
add_filter('woocommerce_show_page_title', 'remove_product_category_main_title');

// For removing result count,sale badge and product price on product category detail page
add_action('wp', 'custom_remove_woocommerce_actions');

function custom_remove_woocommerce_actions()
{
    if (is_product_category()) {
        remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
        remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
        remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
        remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
    }
}

// For modifying  category page product add to cart text
// add_filter('woocommerce_product_add_to_cart_text', 'custom_woocommerce_product_add_to_cart_text', 10, 2);

// function custom_woocommerce_product_add_to_cart_text($text, $product)
// {
//     // Check if we are on a product category page
//     if (is_product_category()) {
//         // Get the classes applied to the body tag
//         $body_classes = get_body_class();

//         // Check if the custom class is not present
//         if (!in_array('custom-layout-enabled', $body_classes)) {
//             // Change the text for the Add to Cart button
//             $text = 'Shop Now >';
//         }
//     }
//     return $text;
// }


/**
 * Add 'Shop Now' button to subcategory items on parent category page.
 */
function custom_add_shop_now_button_to_subcategories($category)
{
    // Get the subcategory URL
    $category_link = get_term_link($category->term_id, 'product_cat');
    echo '<a class="button shop-now-button" href="' . esc_url($category_link) . '">Shop Now ></a>';
}
add_action('woocommerce_after_subcategory_title', 'custom_add_shop_now_button_to_subcategories', 20, 1);




// Custom email to friend Button Functionality  
function add_email_to_friend_button($product_name, $product_permalink, $h1_title)
{
    $website_url = esc_url(home_url());

    // Button HTML
    $button_html = '<div class="email-to-friend-btn">
                    <a href="mailto:?subject=' . esc_html($h1_title) . ' at ' . $website_url . '&body=I found this ' . esc_html($product_name) . ' at ' . $website_url . ' and thought you might be interested: ' . $product_permalink . '" target="_blank">
                        <img src="/wp-content/uploads/2024/07/email.svg" alt="Envelope Icon">
                        <span>Email to a Friend</span>
                    </a>
                    </div>';

    return $button_html;
}
;
// Add a custom class to the body tag based on the category level and custom layout
function custom_body_class_for_category($classes)
{
    if (is_tax('product_cat')) {
        $term = get_queried_object();
        $term_id = $term->term_id;

        // Retrieve the value of the custom checkbox field
        $use_custom_layout = get_term_meta($term_id, 'custom_layout', true);

        // Add a class if custom layout is enabled
        if ($use_custom_layout) {
            $classes[] = 'custom-layout-enabled';
        }

    }

    return $classes;
}

add_filter('body_class', 'custom_body_class_for_category');



//Function for removing sale badge from product on shop page

function custom_remove_woocommerce_actions_shop()
{
    // Remove sale badge if it is the shop page
    if (is_shop()) {
        remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
    }
}

add_action('wp', 'custom_remove_woocommerce_actions_shop');


// Add the color code and image fields to the term add form
function add_color_and_image_fields_to_attribute($taxonomy)
{
    if ($taxonomy !== 'pa_available-colors') {
        return;
    }

    // Add the color code field
    ?>
    <div class="form-field">
        <label for="color_code"><?php esc_html_e('Color Code', 'textdomain'); ?></label>
        <input type="text" name="color_code" id="color_code" value="" />
        <p class="description">
            <?php esc_html_e('Enter a hex color code (e.g., #ff0000) or an image URL for the background (e.g., http://example.com/image.jpg).', 'textdomain'); ?>
        </p>

    </div>

    <!-- Add the image field -->
    <div class="form-field">
        <label for="color_image"><?php esc_html_e('Color Image', 'textdomain'); ?></label>
        <input type="text" name="color_image" id="color_image" value="" />
        <button type="button" class="button color_image_button"><?php esc_html_e('Upload Image', 'textdomain'); ?></button>
        <p class="description"><?php esc_html_e('Upload an image for the color.', 'textdomain'); ?></p>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            $('.color_image_button').click(function (e) {
                e.preventDefault();
                var image_frame;
                if (image_frame) {
                    image_frame.open();
                    return;
                }
                image_frame = wp.media({
                    title: 'Select Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });
                image_frame.on('select', function () {
                    var attachment = image_frame.state().get('selection').first().toJSON();
                    $('#color_image').val(attachment.url);
                });
                image_frame.open();
            });
        });
    </script>
    <?php
}
add_action('pa_available-colors_add_form_fields', 'add_color_and_image_fields_to_attribute', 10, 1);

// Add the color code and image fields to the term edit form
function edit_color_and_image_fields_to_attribute($term)
{
    if ('pa_available-colors' !== $term->taxonomy) {
        return;
    }

    // Retrieve the color code and image for the current term
    $color_code = get_term_meta($term->term_id, 'color_code', true);
    $color_image = get_term_meta($term->term_id, 'color_image', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="color_code"><?php esc_html_e('Color Code', 'textdomain'); ?></label></th>
        <td>
            <input type="text" name="color_code" id="color_code" value="<?php echo esc_attr($color_code); ?>" />
            <p class="description"><?php esc_html_e('Enter the hex color code (e.g., #ff0000).', 'textdomain'); ?></p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="color_image"><?php esc_html_e('Color Image', 'textdomain'); ?></label></th>
        <td>
            <input type="text" name="color_image" id="color_image" value="<?php echo esc_url($color_image); ?>" />
            <button type="button"
                class="button color_image_button"><?php esc_html_e('Upload Image', 'textdomain'); ?></button>
            <p class="description"><?php esc_html_e('Upload an image for the color.', 'textdomain'); ?></p>
            <?php if ($color_image): ?>
                <img src="<?php echo esc_url($color_image); ?>" style="max-width: 300px; height: auto; margin-top: 10px;" />
            <?php endif; ?>
        </td>
    </tr>
    <script>
        jQuery(document).ready(function ($) {
            $('.color_image_button').click(function (e) {
                e.preventDefault();
                var image_frame;
                if (image_frame) {
                    image_frame.open();
                    return;
                }
                image_frame = wp.media({
                    title: 'Select Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });
                image_frame.on('select', function () {
                    var attachment = image_frame.state().get('selection').first().toJSON();
                    $('#color_image').val(attachment.url);
                    $('img').attr('src', attachment.url);
                });
                image_frame.open();
            });
        });
    </script>
    <?php
}
add_action('pa_available-colors_edit_form_fields', 'edit_color_and_image_fields_to_attribute', 10, 1);

// Save custom field values
function save_color_and_image_fields($term_id)
{
    if (isset($_POST['color_code'])) {
        $color_code = sanitize_text_field($_POST['color_code']);
        update_term_meta($term_id, 'color_code', $color_code);
    }
    if (isset($_POST['color_image'])) {
        $color_image = esc_url_raw($_POST['color_image']);
        update_term_meta($term_id, 'color_image', $color_image);
    }
}
add_action('edited_pa_available-colors', 'save_color_and_image_fields', 10, 1);
add_action('create_pa_available-colors', 'save_color_and_image_fields', 10, 1);


function add_display_full_name_field_to_attributes($taxonomy)
{
    ?>
    <div class="form-field">
        <label for="display_full_name"><?php esc_html_e('Display Full Name', 'textdomain'); ?></label>
        <input type="text" name="display_full_name" id="display_full_name" value="" />
        <p class="description"><?php esc_html_e('Enter the full name to display for this attribute.', 'textdomain'); ?></p>
    </div>
    <?php
}
add_action('pa_available-colors_add_form_fields', 'add_display_full_name_field_to_attributes', 20);
add_action('pa_available-thicknesses_add_form_fields', 'add_display_full_name_field_to_attributes', 20);

function edit_display_full_name_field_for_attributes($term)
{
    // Retrieve the display_full_name for the current term
    $display_full_name = get_term_meta($term->term_id, 'display_full_name', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label
                for="display_full_name"><?php esc_html_e('Display Full Name', 'textdomain'); ?></label></th>
        <td>
            <input type="text" name="display_full_name" id="display_full_name"
                value="<?php echo esc_attr($display_full_name); ?>" />
            <p class="description"><?php esc_html_e('Enter the full name to display for this attribute.', 'textdomain'); ?>
            </p>
        </td>
    </tr>
    <?php
}
add_action('pa_available-colors_edit_form_fields', 'edit_display_full_name_field_for_attributes', 20);
add_action('pa_available-thicknesses_edit_form_fields', 'edit_display_full_name_field_for_attributes', 20);

function save_display_full_name_field($term_id)
{
    if (isset($_POST['display_full_name'])) {
        $display_full_name = sanitize_text_field($_POST['display_full_name']);
        update_term_meta($term_id, 'display_full_name', $display_full_name);
    }
}
add_action('edited_pa_available-colors', 'save_display_full_name_field', 10, 1);
add_action('create_pa_available-colors', 'save_display_full_name_field', 10, 1);
add_action('edited_pa_available-thicknesses', 'save_display_full_name_field', 10, 1);
add_action('create_pa_available-thicknesses', 'save_display_full_name_field', 10, 1);

// Add custom checkbox field to product category taxonomy
function add_custom_checkbox_field()
{
    ?>
    <div class="form-field"
        style="display: flex; gap: 5px; align-items: center; flex-direction: row-reverse; justify-content: flex-end;">
        <label for="custom_layout" style="margin-top: -4px;">Show Products Directly</label>
        <input type="checkbox" name="custom_layout" id="custom_layout" value="1" />
    </div>
    <?php
}
add_action('product_cat_add_form_fields', 'add_custom_checkbox_field', 10, 2);

// Edit custom checkbox field in product category taxonomy
function edit_custom_checkbox_field($term)
{
    $custom_layout = get_term_meta($term->term_id, 'custom_layout', true);
    ?>
    <tr class="form-field"
        style="display: flex; gap: 5px; align-items: center; flex-direction: row-reverse; justify-content: flex-end;">
        <th scope="row"><label for="custom_layout" style="margin-top: -4px;">Show Products Directly</label></th>
        <td>
            <input type="checkbox" name="custom_layout" id="custom_layout" value="1" <?php checked($custom_layout, 1); ?> />
        </td>
    </tr>
    <?php
}
add_action('product_cat_edit_form_fields', 'edit_custom_checkbox_field', 10, 2);

// Save custom checkbox field value
function save_custom_checkbox_field($term_id)
{
    if (isset($_POST['custom_layout'])) {
        update_term_meta($term_id, 'custom_layout', 1);
    } else {
        delete_term_meta($term_id, 'custom_layout');
    }
}
add_action('edited_product_cat', 'save_custom_checkbox_field');
add_action('create_product_cat', 'save_custom_checkbox_field');

/** Remove categories from shop and other pages
 * in Woocommerce
 */
function wc_hide_selected_terms($terms, $taxonomies, $args)
{
    $new_terms = array();
    if (in_array('product_cat', $taxonomies) && !is_admin() && is_shop()) {
        foreach ($terms as $key => $term) {
            if (!in_array($term->slug, array('uncategorized'))) {
                $new_terms[] = $term;
            }
        }
        $terms = $new_terms;
    }
    return $terms;
}
add_filter('get_terms', 'wc_hide_selected_terms', 10, 3);


/** 
 * Add a new custom checkbox field 'Residential Address' on the checkout page below ZIP Code field.
 * This fiel is added to show/hide the Fedex Ground shipping mehtods
 * By default, the checkbox will remaine uncheck and Fedex Ground shipping method will be displayed.
 * If a customer checks the checkbox, then Fedex Home Ground shipping method will be displayed.
 */
function add_residential_address_checkbox($fields)
{
    // Add a new checkbox field after the billing postcode field
    $fields['billing']['billing_residential_address'] = array(
        'type' => 'checkbox',
        'label' => 'Residential Address',  // Label without '(optional)'
        'required' => false,
        'class' => array('form-row-wide'),
        'priority' => 95,
    );

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'add_residential_address_checkbox');


function save_residential_address_checkbox($order_id)
{
    if (!empty($_POST['billing_residential_address'])) {
        update_post_meta($order_id, '_residential_address', sanitize_text_field($_POST['billing_residential_address']));
    }
}
add_action('woocommerce_checkout_update_order_meta', 'save_residential_address_checkbox');

function filter_shipping_methods_based_on_residential_address_checkbox($rates, $package)
{

    // Extract the post_data from the $_POST array
    parse_str($_POST['post_data'], $parsed_post_data);

    if (isset($parsed_post_data['billing_residential_address']) && $parsed_post_data['billing_residential_address'] == 1) {
        foreach ($rates as $rate_id => $rate) {
            if (strpos($rate->id, 'flexible_shipping_fedex:5') === 0) {
                unset($rates[$rate_id]);
            }
        }
    } else {
        foreach ($rates as $rate_id => $rate) {
            if (strpos($rate->id, 'flexible_shipping_fedex:6') === 0) {
                unset($rates[$rate_id]);
            }
        }
    }

    return $rates;
}
add_filter('woocommerce_package_rates', 'filter_shipping_methods_based_on_residential_address_checkbox', 10, 2);

function enqueue_custom_checkout_script_for_residential_address_checkbox()
{
    if (is_checkout()) {
        wp_enqueue_script('jquery'); // Ensure jQuery is loaded

        // Add inline script
        wp_add_inline_script('jquery', "
            jQuery(document).ready(function($){
                // Listen for changes on the checkbox
                $('#billing_residential_address_field input[type=\"checkbox\"]').change(function(){
                    // Trigger WooCommerce checkout update
                    $('body').trigger('update_checkout');
                });
            });
        ");
    }
}
add_action('wp_enqueue_scripts', 'enqueue_custom_checkout_script_for_residential_address_checkbox');

function custom_checkout_css_for_residential_address_checkbox()
{
    if (is_checkout()) { // Only add the CSS on the checkout page
        // Add the CSS inline
        wp_add_inline_style('woocommerce-inline', '
            #billing_residential_address_field .optional {
                display: none;
            }
        ');
    }
}
add_action('wp_enqueue_scripts', 'custom_checkout_css_for_residential_address_checkbox');
/** Residential address checkbox code end **/


add_shortcode('wrps_related_products', 'wpb_wrps_related_products');

add_action('product_cat_add_form_fields', 'remove_display_type_field');
add_action('product_cat_edit_form_fields', 'remove_display_type_field');

function remove_display_type_field()
{
    echo '<style>
        .form-field.term-display-type-wrap,
        .form-field:has(#wc_avatax_category_tax_code),
        .form-field:has(input[name="group_of_quantity"]) {
            display: none;
        }
            
    </style>';
}

add_filter('woocommerce_product_tabs', 'custom_remove_additional_info_tab', 98);
function custom_remove_additional_info_tab($tabs)
{
    if (isset($tabs['additional_information'])) {
        unset($tabs['additional_information']);
    }
    return $tabs;
}

add_filter('woocommerce_product_tabs', 'add_additional_information_tab');

function add_additional_information_tab($tabs)
{
    // Get the additional information ACF field value
    global $post;
    $additional_info = get_field('additional_information', $post->ID);

    // Check if there is data in the ACF field
    if ($additional_info) {
        $tabs['additional_informations'] = array(
            'title' => __('Additional Product Information', 'woocommerce'),
            'priority' => 50,
            'callback' => 'additional_information_tab_content'
        );
    }

    return $tabs;
}
function additional_information_tab_content()
{
    global $post;
    $additional_info = get_field('additional_information', $post->ID);

    if ($additional_info) {
        echo '<h2>' . __('Additional Product Information', 'woocommerce') . '</h2>';
        echo '<div>' . wp_kses_post($additional_info) . '</div>';
    }
}


// add_action('wc_avatax_order_processed', 'set_avatax_doc_status_uncommitted', 10, 1);

// function set_avatax_doc_status_uncommitted($order_id) {
//     // Get the order object
//     $order = wc_get_order($order_id);

//     // Check if the order exists and Avalara is enabled
//     if ($order && class_exists('WC_Avalara')) {
//         // Get the Avalara transaction ID associated with the order
//         $transaction_id = get_post_meta($order_id, '_avatax_transaction_id', true);

//         if ($transaction_id) {
//             // Assuming you have a method in WC_Avalara to set the status
//             $avalara = new WC_Avalara();

//             // Set the document status to uncommitted
//             $result = $avalara->update_transaction_status($transaction_id, 'uncommitted');

//             if (is_wp_error($result)) {
//                 // Log the error if updating the status fails
//                 error_log('Failed to set Avalara transaction status to uncommitted: ' . $result->get_error_message());
//             }
//         }
//     }
// }
add_action('woocommerce_after_shop_loop_item_title', 'custom_display_price_above_add_to_cart', 5);

function custom_display_price_above_add_to_cart()
{
    global $product;

    if (is_product_category()) {
        // Display the product price
        echo '<div class="custom-price">' . $product->get_price_html() . '</div>';
    }
}
// Show all products on the category pages
add_filter('loop_shop_per_page', 'custom_loop_shop_per_page', 20);
function custom_loop_shop_per_page($cols)
{
    return -1; // -1 means to show all products
}

// Remove pagination from category pages
add_action('woocommerce_after_shop_loop', 'remove_product_pagination', 5);
function remove_product_pagination()
{
    if (is_product_category()) {
        remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);
    }
}


add_action('init', 'check_and_update_wc_avatax_record_calculations');
function check_and_update_wc_avatax_record_calculations()
{
    // Get the current value of the option
    $current_value = get_option('wc_avatax_record_calculations');

    $new_value = 'yes';

    if ($current_value !== $new_value) {
        update_option('wc_avatax_record_calculations', $new_value);
    }
}



// Add Quantity Input Beside Product Name

add_filter('woocommerce_checkout_cart_item_quantity', 'bbloomer_checkout_item_quantity_input', 9999, 3);

function bbloomer_checkout_item_quantity_input($product_quantity, $cart_item, $cart_item_key)
{
    $product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
    if (!$product->is_sold_individually()) {
        $product_quantity = woocommerce_quantity_input(array(
            'input_name' => 'shipping_method_qty_' . $product_id,
            'input_value' => $cart_item['quantity'],
            'max_value' => $product->get_max_purchase_quantity(),
            'min_value' => '0',
        ), $product, false);
        $product_quantity .= '<input type="hidden" name="product_key_' . $product_id . '" value="' . $cart_item_key . '">';
    }
    return $product_quantity;
}

// ----------------------------
// Detect Quantity Change and Recalculate Totals

add_action('woocommerce_checkout_update_order_review', 'bbloomer_update_item_quantity_checkout');

function bbloomer_update_item_quantity_checkout($post_data)
{
    parse_str($post_data, $post_data_array);
    $updated_qty = false;
    foreach ($post_data_array as $key => $value) {
        if (substr($key, 0, 20) === 'shipping_method_qty_') {
            $id = substr($key, 20);
            WC()->cart->set_quantity($post_data_array['product_key_' . $id], $post_data_array[$key], false);
            $updated_qty = true;
        }
    }
    if ($updated_qty)
        WC()->cart->calculate_totals();
}


add_filter('woocommerce_package_rates', 'add_fixed_shipping_cost_per_item', 10, 2);

function add_fixed_shipping_cost_per_item($rates, $package)
{
    $total_fixed_shipping_cost = 0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];

        $product_shipping_cost = get_field('fixed_shipping_cost', $product_id);

        if ($product_shipping_cost && floatval($product_shipping_cost) > 0) {
            $total_fixed_shipping_cost += floatval($product_shipping_cost) * $quantity;
        }
    }

    if ($total_fixed_shipping_cost <= 0) {
        return $rates;
    }

    foreach ($rates as $rate_key => $rate) {
        if ($rate->cost == 0) {
            continue;
        }

        $rates[$rate_key]->cost += $total_fixed_shipping_cost;
    }

    return $rates;
}


// XXXXXXXXX GETTING DATA FROM THE DB FIELD TO ACF FIELD ON ADMIN_INIT XXXXXXXXX
// function update_acf_field_from_woocommerce_meta()
// {
//     // Query all products
//     $args = [
//         'post_type' => 'product',
//         'posts_per_page' => -1, // Retrieve all products
//         'post_status' => 'publish',
//     ];

//     $products = new WP_Query($args);

//     if ($products->have_posts()) {
//         while ($products->have_posts()) {
//             $products->the_post();

//             // Get the current product ID
//             $product_id = get_the_ID();

//             // Retrieve the WooCommerce meta field `_fixed_shippingcost`
//             $fixed_shippingcost = get_post_meta($product_id, '_fixed_shippingcost', true);

//             // Check if the meta field has a value
//             if (!empty($fixed_shippingcost)) {
//                 // Update the ACF field `fixed_shipping_cost`
//                 update_field('fixed_shipping_cost', $fixed_shippingcost, $product_id);
//             }
//         }
//     }

//     // Restore global post data after custom query
//     wp_reset_postdata();

//     echo 'ACF fields updated successfully.';
// }

// // Hook into WordPress admin area to run this function once
// add_action('admin_init', 'update_acf_field_from_woocommerce_meta');

// Add custom JavaScript for showing loader before page reload in your theme

