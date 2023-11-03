<?php
// Load WordPress
define('WP_USE_THEMES', false);
require_once('../../../../wp-load.php');

if (!current_user_can('administrator')) {
    wp_die('Access denied.'); // You can customize the error message
}
$mode = "";
if (isset($_GET['mode'])) {
    $mode = "debug";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previewing...</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0
        }

        #editor {
            margin: 0;
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;

        }
    </style>

</head>

<body>
    <div id="preview">
        <?php

        // error_reporting(0);

        function smgcs_user_schema($id, $mode)
        {
            $GLOBALS['wp_query']->is_author = true;
            $GLOBALS['wp_query']->is_archive = true;
            $GLOBALS['wp_query']->is_post_type_archive = false;
            $GLOBALS['wp_query']->is_singular = false;
            $GLOBALS['wp_query']->is_home = false;
            $GLOBALS['wp_query']->is_search = false;
            $GLOBALS['wp_query']->is_404 = false;

            $args = array(
                'include' => array($id),
            );

            $user_query = new WP_User_Query($args);

            if (!empty($user_query->results)) {

                foreach ($user_query->results as $user) {


                    $preview =     new Schemagic_Public('Schagic', '0.0.1');
                    $preview->get_schema_templates();

                    $data = ($preview->get_schema_templates());

                    if ($mode == 'debug') {

                        $data = $preview->build_schema();
                    } else {

                        $data = ($preview->preview_schematic());
                    }
                    echo '<div style="min-height: 400px;"><pre id="editorpreview" onClick="selectText(\'editorpreview\');">';
                    echo esc_textarea($data);
                    echo   '</pre></div>';
                }
            } else {
                echo "User not found.";
            }
        }
        global $is_singular;
        $is_singular = true;


        // Check if an 'id' parameter is provided
        if (isset($_GET['id'])) {





            $id = intval($_GET['id']);

            // Perform a WP Query using the provided 'id'
            $args = array(
                'p' => $id,
                'is_singular' => true,
            );


            $query = new WP_Query($args);

            // Check if the query has posts
            if ($query->have_posts()) {
                $data = '';
                while ($query->have_posts()) {
                    $query->the_post();


                    $preview =     new Schemagic_Public('Schagic', '0.0.1');
                    $preview->get_schema_templates();

                    if ($mode == 'debug') {
                        $data = $preview->build_schema();
                    } else {

                        $data = ($preview->preview_schematic());
                    }
                    //  global $post;
                    if ($data) {
                        echo '<div style="min-height: 400px;"><pre id="editorpreview" onClick="selectText(\'editorpreview\');">';
                        echo esc_textarea($data);
                        echo   '</pre></div>';
                    }
                }
                wp_reset_postdata();

                if ($data == "") {
                    smgcs_user_schema($id, $mode);
                }
            } else {
                smgcs_user_schema($id, $mode);
                echo 'No posts found with the provided ID.';
            }
        } else {
            echo 'Please provide an ID parameter.';
        }
        ?>

    </div>

    <script>
        function selectText(elementId) {
            const element = document.getElementById(elementId);

            if (document.selection) {
                const range = document.body.createTextRange();
                range.moveToElementText(element);
                range.select();
            } else if (window.getSelection) {
                const range = document.createRange();
                range.selectNode(element);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
            }
        }
    </script>
</body>

</html>