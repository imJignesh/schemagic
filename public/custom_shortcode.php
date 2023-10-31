<?php
// Define a function to generate your shortcode content
function custom_shortcode_function($atts) {
    // Call your custom function to retrieve schema templates
    $atts = shortcode_atts(array(
        'id' => 0, // Default to 0 if 'id' is not provided
    ), $atts);

    

    $schema_template = get_post_meta($atts['id'], '_schema_template', true);
    $display_data = get_post_meta($atts['id'], '_display_data', true);
    // Return the template data
    $post=get_post($atts['id'] );

    schema_query($display_data, $post);
    return $schema_template;

}

// Register the shortcode with WordPress
function register_custom_shortcode() {
    add_shortcode('custom', 'custom_shortcode_function');
}


add_action('init', 'register_custom_shortcode');

function get_schema_templates() {
    global $post;
    $currentpost = $post;
    $args = array(
        'post_type' => 'schemagic', // Replace with your custom post type name
        'post_status' => 'publish', // Retrieve only published posts
        'posts_per_page' => -1, // Retrieve all published posts
    );

    $schemagic_posts = get_posts($args);

    $schemadata = array();

    foreach ($schemagic_posts as $post) {
        // Get the custom meta values you created
        $schema_template = get_post_meta($post->ID, '_schema_template', true);
        $display_data = get_post_meta($post->ID, '_display_data', true);
        echo $schema_template;

        // Do something with the custom meta values
        // echo "<h2>Title: " . get_the_title($currentpost) . "</h2>";
        // echo "<div>Schema Template: " . ($schema_template) . "</div>";
        // echo "<div>Display Data: " . ($display_data) . "</div>";
        if ($this->schema_query($display_data, $currentpost))
            $schemadata[] = $schema_template ? $schema_template : "";
        // Reset the post data
    }

    schema_query($display_data, $currentpost);

    wp_reset_postdata();
    global $scmagicdata;
    $scmagicdata =  join("", $schemadata);
    $post = $currentpost;
}
$post_id=8;
function get_template_data_by_id($post_id) {
    $post = get_post($post_id);

    if ($post) {
        return $post->post_content;
    }

    return 'Post not found or does not exist.';
}


function get_schema_template_by_id($post_id) {
    $args = array(
        'post_type' => 'schemagic', // Replace with your custom post type name
        'post_status' => 'publish', // Retrieve only published posts
        'p' => $post_id, // Retrieve a specific post by ID
    );

    $schemagic_post = get_posts($args);

    if (empty($schemagic_post)) {
        return 'Post not found or does not exist.';
    }

    $post = $schemagic_post[0];

    // Get the custom meta values you created
    $schema_template = get_post_meta($post->ID, '_schema_template', true);
    $display_data = get_post_meta($post->ID, '_display_data', true);

    // Return the schema template or any other data you need
    return $schema_template;
}


?>