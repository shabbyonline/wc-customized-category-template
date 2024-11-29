<?php
/**
 * The template for displaying product category pages
 *
 * This template can be overridden by copying it to bb-child/woocommerce/taxonomy-product_cat.php.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined('ABSPATH') || exit;

get_header('shop');

$term = get_queried_object();
$term_id = $term->term_id;

// Retrieve the value of the custom checkbox field
$use_custom_layout = get_term_meta($term_id, 'custom_layout', true);


function data_by_category($term_id, $category_id)
{
    $description_for_this_attribute_rows = get_field('description_for_this_attribute', 'post_tag_' . $term_id);
    $result = [
        'description' => '',
        'title' => '',
        'image' => ''
    ];

    if ($description_for_this_attribute_rows) {
        foreach ($description_for_this_attribute_rows as $row) {
            $field_category = $row['product_category'];
            $field_description = $row['description'];
            $field_title = $row['title'];
            $field_image_URL = $row['image'];

            // Check if the category matches the current one
            if ($field_category === $category_id) {
                $result['description'] = $field_description;
                $result['title'] = $field_title;
                $result['image'] = $field_image_URL;
                return $result;
            }
        }
    }
    return $result;
}
$child_categories = get_terms(
    array(
        'taxonomy' => 'product_cat',
        'parent' => $term_id,
        'hide_empty' => false,
    )
);

$is_last_level = empty($child_categories);

$url = $_SERVER['REQUEST_URI'];
$query_string = parse_url($url, PHP_URL_QUERY);
$query_based_layout = false;
if ($query_string) {
    parse_str($query_string, $query_array);
    if (isset($query_array['pa_available-colors']) || isset($query_array['pa_available-thicknesses'])) {
        $query_based_layout = true;
    }
}
$quantity_discounts = get_term_meta($term_id, 'quantity_discounts', true);
$available_to_ship = get_term_meta($term_id, 'available_to_ship', true);

if (is_product_category()) {
    // Get the current category
    $term = get_queried_object();
    $parent_id = $term->parent;

    // Check if the current category has a parent
    if ($parent_id) {
        // Get parent category object
        $parent_term = get_term($parent_id, 'product_cat');

        // Get child categories of the parent category
        $child_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'parent' => $parent_term->term_id,
            'hide_empty' => false,
        ));

        $customize_layout = false;

        foreach ($child_categories as $child_category) {
            // Check if the child category has products with attributes assigned
            $products_in_category = get_posts(array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $child_category->term_id,
                    ),
                ),
            ));

            if (!empty($products_in_category)) {
                foreach ($products_in_category as $product_post) {
                    $product = wc_get_product($product_post->ID);
                    $attributes = $product->get_attributes();

                    foreach ($attributes as $attribute) {
                        if ($attribute->is_taxonomy()) {
                            $attribute_name = wc_attribute_taxonomy_name($attribute->get_name());
                            $terms = get_terms(array(
                                'taxonomy' => $attribute_name,
                                'hide_empty' => false,
                            ));

                            // Check if any terms are assigned
                            if (!empty($terms)) {
                                $customize_layout = true;
                                break 3; // Exit all loops if layout customization is required
                            }
                        }
                    }
                }
            }
        }
    }
}

if ($use_custom_layout) {
    // If $use_custom_layout is true, display the default WooCommerce layout
    wc_get_template_part('archive', 'product');
} elseif ($query_based_layout) {
    // Add a class based on whether it's last level or second level
    $main_div_class = $is_last_level ? 'last-level-category' : 'second-level-category';

    // Display the custom layout for second-level and last-level categories
    echo '<div class="custom-category-layout ' . esc_attr($main_div_class) . '">';
    echo '<div class="category-container">';

    $current_category = get_queried_object();

    // Initialize variables for attribute image
    $attribute_image_url = '';

    // Fetch all products in the current category
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $term_id,
                ),
            ),
    );
    $products = new WP_Query($args);

    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            $product = wc_get_product(get_the_ID());

            // Get all attributes for the product
            $product_attributes = $product->get_attributes();

            foreach ($product_attributes as $attribute) {
                if ($attribute->is_taxonomy()) {
                    $attribute_name = $attribute->get_name();
                    $attribute_slug = str_replace('pa_', '', $attribute_name); // Get the attribute slug
                    $attribute_values = $product->get_attribute($attribute_name);

                    if ($attribute_values) {
                        $values = explode('|', $attribute_values);
                        foreach ($values as $value) {
                            $value = trim($value);
                            $term = get_term_by('name', $value, 'pa_' . $attribute_slug);
                            $term_image = get_term_meta($term->term_id, 'color_image', true); // Fetch the attribute image
                            $display_full_name = get_term_meta($term->term_id, 'display_full_name', true);

                            if ($display_full_name && isset($_GET['pa_' . $attribute_slug]) && sanitize_title($_GET['pa_' . $attribute_slug]) === $term->slug) {
                                $attribute_full_name = $display_full_name;
                            }
                            // Check if the term image exists and if it's selected
                            if ($term_image && isset($_GET['pa_' . $attribute_slug]) && sanitize_title($_GET['pa_' . $attribute_slug]) === $term->slug) {
                                $attribute_image_url = $term_image;
                                break 2; // Break out of both loops once an image is found
                            }
                        }
                    }
                }
            }
        }
        wp_reset_postdata();
    }

    // Use the attribute image if available, otherwise fallback to the default category image
    $thumbnail_id = get_term_meta($current_category->term_id, 'thumbnail_id', true);
    $default_image_url = wp_get_attachment_url($thumbnail_id);
    $image_url = $attribute_image_url ? $attribute_image_url : $default_image_url;

    if ($image_url) {
        echo '<div class="category-image">';
        echo '<img class="cat-img" src="' . esc_url($image_url) . '" alt="' . esc_attr($current_category->name) . '">';
        $button_text = $is_last_level
            ? $parent_term->name . ' - ' . $current_category->name
            : $current_category->name;
        echo add_email_to_friend_button($button_text, get_term_link($current_category->term_id), $current_category->name);
        echo '</div>';
    }

    // Display the category title with parent category name if it's the last level  
    echo '<div class="category-content">';
    echo '<div class="category-title">';

    $active_term_id = $selected_term->term_id;
    $active_category_id = $current_category->term_id;
    $data = data_by_category($active_term_id, $active_category_id);
    $dynamic_title = $data['title'];

    // Set $main_title based on the conditions
    if (!empty($attribute_full_name)) {
        $main_title = $attribute_full_name;
    } elseif (!empty($dynamic_title)) {
        $main_title = $dynamic_title;
    } else {
        $main_title = $current_category->name;
    }

    echo '<h1>' . esc_html($main_title) . '</h1>';

    // Display custom fields data above the "variants" section
    if ($quantity_discounts) {
        echo '<div class="quantity-discounts">';
        echo '<strong class="quantity-head">Quantity Discounts Available!</strong>';
        echo wp_kses_post($quantity_discounts);
        echo '</div>';
    }

    echo '<div class="variants">';

    // Fetch products in the current category
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $term_id,
                ),
            ),
    );
    $products = new WP_Query($args);

    // Initialize arrays to hold attributes and their values
    $attributes = array();
    $attributes_group = array();

    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            $product = wc_get_product(get_the_ID());

            // Get all attributes for the product
            $product_attributes = $product->get_attributes();

            /* Create attribute group array start */
            $options = [];
            foreach ($product_attributes as $attribute) {
                // Fetch the taxonomy name (e.g., pa_color, pa_size)
                $taxonomy = $attribute->get_name();

                // Fetch the option IDs for the current attribute
                $option_ids = $attribute->get_options();

                // Get the option terms (this will return the names or slugs for the attribute options)
                if (!empty($option_ids)) {
                    $terms = get_terms([
                        'taxonomy' => $taxonomy,
                        'include'  => $option_ids,
                        'hide_empty' => false,
                    ]);

                    // Loop through terms to get the option names or slugs
                    foreach ($terms as $term) {
                        $options[] = $term->slug; 
                    }
                }
            }            
            foreach ($options as $option_key => $option_value) {
                foreach ($options as $related_option_key => $related_option_value) {
                    // Skip if it's the same option
                    if ($option_value == $related_option_value) {
                        continue;
                    }

                    if (!isset($attributes_group[$option_value])) {
                        $attributes_group[$option_value] = [];
                    }

                    if (!in_array($related_option_value, $attributes_group[$option_value])) {
                        $attributes_group[$option_value][] = $related_option_value;
                    }
                }
            }
            /* Create attribute group array end */


            foreach ($product_attributes as $attribute) {
                if ($attribute->is_taxonomy()) {
                    $attribute_name = $attribute->get_name();
                    $attribute_slug = str_replace('pa_', '', $attribute_name); // Get the attribute slug

                    // Fetch attribute values
                    $attribute_values = $product->get_attribute($attribute_name);

                    if ($attribute_values) {
                        $values = explode('|', $attribute_values);
                        foreach ($values as $value) {
                            $value = trim($value);
                            if (!isset($attributes[$attribute_slug])) {
                                $attributes[$attribute_slug] = array();
                            }
                            if (!in_array($value, $attributes[$attribute_slug])) {
                                $attributes[$attribute_slug][] = $value;
                            }
                            $attributes_value_with_id[$value] = $attribute_id;
                        }
                    }
                }
            }
        }

        wp_reset_postdata();
    }
    
    foreach ($attributes as $attribute_slug => $values) {
        $attribute_display_name = wc_attribute_label('pa_' . $attribute_slug);

        echo '<div class="attribute-group ' . esc_attr($attribute_slug) . '">';
        echo '<strong>' . esc_html($attribute_display_name) . ':</strong>';
        echo '<ul class="attributes-list">';

        foreach ($values as $value) {
            $term = get_term_by('name', $value, 'pa_' . $attribute_slug);
            $value_slug = $term ? $term->slug : '';
            $class = '';
            $style = '';
            $link_text = $value;

            if ($attribute_slug === 'available-colors') {
                $color_code = get_term_meta($term->term_id, 'color_code', true);

                if (filter_var($color_code, FILTER_VALIDATE_URL)) {
                    // Treat as URL for background image
                    $style = 'background-image: url(' . esc_url($color_code) . '); background-size: contain;';
                    $class = ' image-background';
                } elseif ($color_code) {
                    // Treat as color code
                    $style = 'background-color: ' . esc_attr($color_code) . ';';
                    $class = ' color-background';
                } else {
                    $link_text = $value;
                }

                // If background image, set link text to empty
                if ($class === ' image-background' || $class === ' color-background') {
                    $link_text = '';
                }
            }

            $current_filter_value = isset($_GET['pa_' . $attribute_slug]) ? sanitize_title($_GET['pa_' . $attribute_slug]) : '';
            $is_active = $current_filter_value === $value_slug ? ' active' : '';


            $current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

            $parsed_url = parse_url($current_url);

            $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];

            if( $attributes_group[ $value_slug ] ){

                $current_value_found_in_group = false;

                if(isset( $_GET ) ){

                    foreach ($_GET as $key => $url_filter_value) {

                        if (strpos($key, 'pa') === 0) {

                            foreach($attributes_group[ $url_filter_value ] as $single_group_value){

                                if( $value_slug == $single_group_value){
                                    $current_value_found_in_group = true;
                                }
                            }
                        }
                    }
                }

                if($current_value_found_in_group){

                    $filter_url = add_query_arg('pa_' . $attribute_slug, $value_slug);

                }else{

                    $filter_url = add_query_arg('pa_' . $attribute_slug, $value_slug, $base_url);

                }
                
            }else{

                $filter_url = add_query_arg('pa_' . $attribute_slug, $value_slug, $base_url);

            }

            // $filter_url = add_query_arg('pa_' . $attribute_slug, $value_slug);

            echo '<li>';
            echo '<a href="' . esc_url($filter_url) . '" class="woocommerce-loop-category__title' . esc_attr($class) . ' ' . esc_attr($is_active) . '" data-attr="' . esc_attr('pa_' . $attribute_slug . ':' . $value_slug) . '" style="' . esc_attr($style) . '" title="' . esc_html($value) . '">';
            echo esc_html($link_text);
            echo '</a>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';

    }

    echo '</div>'; // .variants

    if ($available_to_ship) {
        echo '<p class="shipping-availability">' . esc_html($available_to_ship) . '</p>';
    }

    // Display the selected term description if active filters exist
    $active_filters = array();
    $selected_term_description = '';

    foreach ($attributes as $attribute_slug => $values) {
        if (isset($_GET['pa_' . $attribute_slug])) {
            $value_slug = sanitize_title($_GET['pa_' . $attribute_slug]);
            $active_filters[] = 'pa_' . $attribute_slug . ':' . $value_slug;

            $selected_term = get_term_by('slug', $value_slug, 'pa_' . $attribute_slug);
            if ($selected_term) {
                $selected_term_description = term_description($selected_term->term_id, 'pa_' . $attribute_slug);
            }
        }
    }

    $active_term_id = $selected_term->term_id;
    $active_category_id = $current_category->term_id;
    $data = data_by_category($active_term_id, $active_category_id);
    $json_data = json_encode($data);
    ?>

    <script type="text/javascript">
        // Create a JavaScript variable to store the JSON data
        var data = <?php echo $json_data; ?>;
        // Function to update the image and title
        function updateContent() {
            var imgElement = document.querySelector('.cat-img');
            if (imgElement && data.image) {
                imgElement.src = data.image;
            }

            var titleElement = document.querySelector('.category-title h1');
            if (titleElement && data.title) {
                titleElement.textContent = data.title;
            }
        }

        // Run the update function after the page has loaded
        window.onload = updateContent;

    </script>
    <?php
    if (!empty($data['description'])) {
        // If $data['description'] is not empty, display it
        echo '<div class="selected-term-description category-description">';
        echo wp_kses_post($data['description']); // Use esc_html() for plain text
        echo '</div>';
    } elseif ($selected_term_description) {
        // If $data['description'] is empty but $selected_term_description is not, display $selected_term_description
        echo '<div class="selected-term-description category-description">';
        echo wp_kses_post($selected_term_description); // Use wp_kses_post() for HTML content
        echo '</div>';
    } else {
        $description = term_description($current_category->term_id, 'product_cat');
        if ($description) {
            echo '<div class="category-description">';
            echo wp_kses_post($description);
            echo '</div>';
        }
    }

    echo '</div>'; // .category-content
    echo '</div>'; // .category-container
    echo '</div>'; // .custom-category-layout


    // Display product table shortcode
    $term_param = !empty($active_filters) ? implode('+', $active_filters) : '';
    echo do_shortcode('[product_table category="' . esc_attr($current_category->slug) . '" term="' . esc_attr($term_param) . '" columns="sku:Product Code,name,price:Our Price,buy:Qty"]');
    echo '<div class="related-products-slider">';
    echo '<div class="related-products-slider-container">';
    echo do_shortcode('[wrps_related_products]');
    echo '</div>';
    echo '</div>';

} elseif ($is_last_level) {
    $current_category = get_queried_object();
    $current_category_description = term_description($current_category->term_id, 'product_cat');
    $current_category_termId = $current_category->term_id;
    $thumbnail_id = get_term_meta($current_category->term_id, 'thumbnail_id', true);
    $default_image_url = wp_get_attachment_url($thumbnail_id);

    echo '<div class="category-container">';
    echo '<h1 class="category-heading">' . $current_category->name . '</h1>';

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $current_category_termId,
            ),
        ),
    );
    $products = new WP_Query($args);

    // Initialize arrays to hold attributes and their values
    $attributes = array();

    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            $product = wc_get_product(get_the_ID());

            // Get all attributes for the product
            $product_attributes = $product->get_attributes();

            foreach ($product_attributes as $attribute) {
                if ($attribute->is_taxonomy()) {
                    $attribute_name = $attribute->get_name();
                    $attribute_slug = str_replace('pa_', '', $attribute_name); // Get the attribute slug

                    // Fetch attribute values
                    $attribute_values = $product->get_attribute($attribute_name);

                    if ($attribute_values) {
                        $values = explode('|', $attribute_values);
                        foreach ($values as $value) {
                            $value = trim($value);
                            if (!isset($attributes[$attribute_slug])) {
                                $attributes[$attribute_slug] = array();
                            }
                            if (!in_array($value, $attributes[$attribute_slug])) {
                                $attributes[$attribute_slug][] = $value;
                            }
                        }
                    }
                }
            }
        }
        wp_reset_postdata();
    }
    if ($attribute_slug === 'available-thicknesses') {
        echo '<div style="text-align:center;"><img src="' . esc_url($default_image_url) . '"/></div>';
    }
    foreach ($attributes as $attribute_slug => $values) {
        $attribute_display_name = wc_attribute_label('pa_' . $attribute_slug);

        echo '<div class="attr-group">';
        echo '<ul class="attr-list ' . $attribute_slug . ' ">';

        foreach ($values as $value) {
            $term = get_term_by('name', $value, 'pa_' . $attribute_slug);
            $attr_term_image = get_term_meta($term->term_id, 'color_image', true);
            $value_slug = $term ? $term->slug : '';
            $link_text = $value;

            if ($attribute_slug === 'available-colors') {
                $color_code = get_term_meta($term->term_id, 'color_code', true);

                if (filter_var($color_code, FILTER_VALIDATE_URL)) {
                    // Treat as URL for background image
                    // $style = 'background-image: url(' . esc_url($color_code) . '); background-size: contain;';
                    // $class = ' image-background';
                } elseif ($color_code) {
                    // Treat as color code
                    // $style = 'background-color: ' . esc_attr($color_code) . ';';
                    // $class = ' color-background';
                } else {
                    $link_text = $value;
                }

                // If background image, set link text to empty
                // if ($class === ' image-background' || $class === ' color-background') {
                //     $link_text = $value;
                // }
            }

            // $current_filter_value = isset($_GET['pa_' . $attribute_slug]) ? sanitize_title($_GET['pa_' . $attribute_slug]) : '';
            // $is_active = $current_filter_value === $value_slug ? ' active' : '';

            $filter_url = add_query_arg('pa_' . $attribute_slug, $value_slug);

            echo '<li class="attr-list-item">';
            echo '<a href="' . esc_url($filter_url) . '" class="woocommerce-loop-category__title' . esc_attr($class) . ' ' . esc_attr($is_active) . '" data-attr="' . esc_attr('pa_' . $attribute_slug . ':' . $value_slug) . '" style="' . esc_attr($style) . '" title="' . esc_html($value) . '">';
            // echo '<pre>';
            if ($attr_term_image) {
                echo '<img class="attr-img" src="' . esc_url($attr_term_image) . '" alt="' . esc_attr($link_text) . '">';
            }
            // echo '</pre>';
            echo '<div class="attr-desc">';
            echo '<p class="title">' . esc_html($link_text) . '</p>';
            echo '<p class="shop-now-btn">Shop Now</p>';
            echo '<div>';
            echo '</a>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';
    }
    echo '<div class="category-description last-level" style="padding-bottom: 60px;">' . $current_category_description . '</div>';
    echo '</div>';
} elseif ($customize_layout) {
    if (is_product_category()) {
        // Get the current category
        $term = get_queried_object();
        $parent_id = $term->parent;
        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
        $category_image_url = wp_get_attachment_url($thumbnail_id);

        echo '<div class="category-container">';
        
        // Display the title of the current category
        echo '<h1 class="category-heading">' . esc_html($term->name) . '</h1>';
        if ($category_image_url) {
            echo '<div style="text-align:center; margin-bottom: 40px;"><img class="attr-img" src="' . esc_url($category_image_url) . '" alt="' . esc_attr($term->name) . '"></div>';
        }
        
       
        // Display sub-category titles of the current category with assigned product attributes list
        $child_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'parent' => $term->term_id,
            'hide_empty' => false,
        ));

        if (!empty($child_categories)) {
            echo '<ul class="sub-categories">';

            foreach ($child_categories as $child_category) {
                echo '<li><h2>' . esc_html($child_category->name) . '</h2>';

                // Get the products in the sub-category
                $products_in_sub_category = get_posts(array(
                    'post_type' => 'product',
                    'posts_per_page' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => $child_category->term_id,
                        ),
                    ),
                ));

                if (!empty($products_in_sub_category)) {
                    $attributes_terms = array();

                    foreach ($products_in_sub_category as $product_post) {
                        $product = wc_get_product($product_post->ID);

                        // Get product attributes
                        $product_attributes = $product->get_attributes();

                        foreach ($product_attributes as $attribute_name => $attribute) {
                            if ($attribute->is_taxonomy()) {
                                // For taxonomy attributes
                                $taxonomy = $attribute->get_name();
                                $terms = wp_get_post_terms($product_post->ID, $taxonomy, array('fields' => 'all'));

                                foreach ($terms as $term) {
                                    if (!isset($attributes_terms[$taxonomy])) {
                                        $attributes_terms[$taxonomy] = array();
                                    }

                                    // Ensure unique terms
                                    if (!isset($attributes_terms[$taxonomy][$term->term_id])) {
                                        $attributes_terms[$taxonomy][$term->term_id] = $term;
                                    }
                                }
                            }
                        }
                    }

                    // Display the assigned attribute terms as links with filter URLs
                    $assigned_attribute_name = str_replace('pa_', '', $attribute_name);
                    echo '<div class="attr-group">';
                    echo '<ul class="attr-list  ' . $assigned_attribute_name . '">';
                    foreach ($attributes_terms as $taxonomy => $terms) {
                        foreach ($terms as $term) {
                            $attr_term_image = get_term_meta($term->term_id, 'color_image', true);
                            // Extract slug and value
                            $taxonomy_slug = str_replace('pa_', '', $taxonomy);
                            $term_slug = $term->slug;

                            // Define the category URL (replace with your category URL structure)
                            $category_url = get_term_link($child_category);

                            // Generate filter URL with category and filter parameter
                            $filter_url = add_query_arg('pa_' . $taxonomy_slug, $term_slug, $category_url);

                            // Display the term as a link
                            echo '<li class="attr-list-item">';
                            echo '<a href="' . esc_url($filter_url) . '" class="woocommerce-loop-category__title" title="' . esc_attr($term->name) . '">';
                            if ($attr_term_image) {
                                echo '<img class="attr-img" src="' . esc_url($attr_term_image) . '" alt="' . esc_attr($term->name) . '">';
                            }
                            echo '<div class="attr-desc">';
                            echo '<p class="title">' . esc_html($term->name) . '</p>';
                            echo '<p class="shop-now-btn">Shop Now</p>';
                            echo '</div>';
                            echo '</a>';
                            echo '</li>';

                        }
                    }
                    echo '</ul>';
                }

                echo '</li>';
            }

            echo '</ul>';
            $term = get_queried_object();
            if (!empty($term->description)) {
                echo '<div class="category-description" style="padding-bottom: 60px;">' . wp_kses_post($term->description) . '</div>';
            }
            echo '</div>';
        }
        // Display the current category description
        
        echo '</div>'; //end category-container
    }
} else {
    // If custom layout is not enabled, display default WooCommerce category layout
    wc_get_template_part('archive', 'product');
}

get_footer('shop');
