<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://imjignesh.com
 * @since      1.0.0
 *
 * @package    Schemagic
 * @subpackage Schemagic/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Schemagic
 * @subpackage Schemagic/admin
 * @author     Jignesh Patel <imjignesh2@gmail.com>
 */
class Schemagic_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_action('admin_enqueue_scripts', array($this, 'enqueue_ace_editor_script'));
		add_action('init', array($this, 'create_schemagic_cpt'));
		add_action('add_meta_boxes', array($this, 'add_schemagic_metabox'));
		add_action('save_post_schemagic', array($this, 'save_schemagic_template_metabox'));
		add_action('add_meta_boxes', array($this, 'add_display_metabox'));
		add_action('save_post_schemagic', array($this, 'save_display_metabox'));
		add_action('add_meta_boxes', array($this, 'add_reference_variables_metabox'));
		add_action('post_submitbox_misc_actions', array($this, 'add_custom_publish_buttons'));
		add_filter('manage_schemagic_posts_columns', array($this, 'custom_shortcode_column'));
		add_action('manage_schemagic_posts_custom_column', array($this, 'custom_shortcode_column_content'), 10, 2);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Schemagic_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Schemagic_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script('thickbox');
		wp_enqueue_style('thickbox');
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/schemagic-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Schemagic_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Schemagic_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/schemagic-admin.js', array('jquery'), $this->version, false);
	}


	/**
	 * Enqueues the Ace editor script and necessary dependencies.
	 *
	 * This function enqueues the Ace editor script along with its dependencies
	 * and Bootstrap for specific conditions. It is used to load JavaScript and CSS files
	 * required for the plugin to work properly.
	 */
	public function enqueue_ace_editor_script()
	{
		wp_enqueue_script(
			'ace-editor',
			plugins_url('js/ace/ace.js', __FILE__), // URL to your ace.js file in the plugin directory
			array(), // Dependencies (none in this case)
			'1.4.12', // Version number (change to match the Ace version)
			false // Load the script in the footer for best practice
		);

		wp_enqueue_script(
			'bootstrap',
			'https://netdna.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js', // Use the correct Bootstrap CDN link
			array('jquery'), // Specify jQuery as a dependency
			'4.5.2', // Adjust the version number
			true // Load the script in the footer for best practice
		);
		wp_enqueue_script(
			'qb-editor',
			plugins_url('js/qb/js/query-builder.standalone.js', __FILE__), // URL to your ace.js file in the plugin directory
			array('bootstrap'), // Dependencies (none in this case)
			'1.4.12', // Version number (change to match the Ace version)
			false // Load the script in the footer for best practice
		);

		global $pagenow, $typenow;
		if (($pagenow === 'post.php' || $pagenow === 'post-new.php') && $typenow === 'schemagic') {
			// Enqueue Bootstrap CSS
			wp_enqueue_style(
				'bootstrap-css',
				'https://netdna.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css',
				array(),
				'3.3.1'
			);
		}

		wp_enqueue_style(
			'query-builder-custom-css',
			plugins_url('js/qb/css/query-builder.default.css', __FILE__), // Adjust the path to your CSS file
			array(),
			'1.0'
		);
	}




	/**
	 * Creates a custom post type for Schema Templates.
	 *
	 * This function registers a custom post type named 'schemagic' for managing Schema Templates.
	 *
	 * @return void
	 */
	public function create_schemagic_cpt()
	{
		$labels = array(
			'name' => 'Schema',
			'singular_name' => 'Schema',
			'add_new' => 'Add New',
			'add_new_item' => 'Add New Schema Template',
			'edit_item' => 'Edit Schema Template',
			'new_item' => 'New Schema Template',
			'view_item' => 'View Schema Template',
			'search_items' => 'Search Schema Templates',
			'not_found' => 'No Schema Templates found',
			'not_found_in_trash' => 'No Schema Templates found in Trash',
			'parent_item_colon' => 'Parent Schema Template:',
		);

		$args = array(
			'labels' => $labels,
			'public' => false, // Set to false to disable access from front end
			'menu_icon' => 'dashicons-editor-code',
			'supports' => array('title', 'revisions'),
			'publicly_queryable' => false, // Set to false to disable queryability
			'show_ui' => true,
		);

		register_post_type('schemagic', $args);
	}




	/**
	 * Adds a metabox for Schema Templates.
	 *
	 * This function adds a metabox to the 'schemagic' custom post type for managing Schema Templates.
	 * The metabox is responsible for rendering the Schema Template content.
	 *
	 * @return void
	 */

	public function add_schemagic_metabox()
	{
		add_meta_box(
			'schemagic_template_metabox',
			'Schema Template',
			array($this, 'render_schemagic_template_metabox'),
			'schemagic',
			'normal',
			'default'
		);
	}


	/**
	 * Renders the Schema Template metabox content.
	 *
	 * This function is responsible for rendering the content of the Schema Template metabox
	 * for the 'schemagic' custom post type. It displays an Ace editor for editing JSON content.
	 *
	 * @param WP_Post $post The current WordPress post object.
	 *
	 * @return void
	 */
	public function render_schemagic_template_metabox($post)
	{
		// Retrieve the current schema template from the post meta
		$schema_template = get_post_meta($post->ID, '_schema_template', true);


?>
		<style>
			#editor {
				margin: 0;
				position: absolute;
				top: 0;
				bottom: 0;
				left: 0;
				right: 0;

			}
		</style>
		<div style="min-height: 400px;">

			<textarea name="schemagic_template_ace" id="schemagic_template_ace" style="display: none;"><?php echo esc_textarea($schema_template); ?></textarea>
			<pre id="editor"><?php echo esc_textarea($schema_template); ?></pre>
		</div>
		<script>
			var editor = ace.edit("editor");
			// editor.setTheme("ace/theme/twilight");
			editor.session.setMode("ace/mode/json");

			// Function to update the hidden textarea with Ace editor's content
			function updateHiddenTextarea() {
				var hiddenTextarea = document.getElementById("schemagic_template_ace");
				var editorContent = editor.getValue();
				hiddenTextarea.value = editorContent;
			}

			// Listen for changes in Ace editor and update the hidden textarea
			editor.getSession().on('change', updateHiddenTextarea);
		</script>
	<?php
	}

	/**
	 * Saves the data from the Schema Template metabox.
	 *
	 * This function is responsible for saving the data entered into the Schema Template metabox
	 * for the 'schemagic' custom post type.
	 *
	 * @param int $post_id The ID of the current WordPress post being saved.
	 *
	 * @return void
	 */
	public function save_schemagic_template_metabox($post_id)
	{
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

		// Get the data from the hidden textarea for Ace editor
		if (isset($_POST['schemagic_template_ace'])) {
			$new_schema_template_ace = wp_unslash($_POST['schemagic_template_ace']);

			// Update the schema template field
			update_post_meta($post_id, '_schema_template', $new_schema_template_ace);
			update_post_meta($post_id, 'previd', $_POST['previd']);
		}
	}




	/**
	 * Adds a custom metabox for controlling display conditions of the "schemagic" CPT.
	 *
	 * This function adds a custom metabox to the 'schemagic' custom post type for controlling
	 * the display conditions of the content.
	 *
	 * @return void
	 */
	public function add_display_metabox()
	{
		add_meta_box(
			'schemagic_display_metabox',
			'Display',
			array($this, 'render_display_metabox'),
			'schemagic',
			'normal',
			'default'
		);
	}



	/**
	 * Callback to render the metabox content for controlling display conditions.
	 *
	 * This function is responsible for rendering the content of the metabox that controls display conditions
	 * for the 'schemagic' custom post type. It includes a QueryBuilder interface for defining conditions.
	 *
	 * @param WP_Post $post The current WordPress post object.
	 *
	 * @return void
	 */
	public function render_display_metabox($post)
	{
		$categories = get_terms(array(
			'taxonomy' => 'category', // Taxonomy name
			'hide_empty' => false,   // Include empty categories
		));

		// Initialize an array to store category data
		$category_data = array();

		// Loop through the categories and store ID as key and name as value
		foreach ($categories as $category) {
			$category_data[$category->term_id] = $category->name;
		}
		if (empty($category_data)) {
			$category_data = array();
		}

		// Convert the array to JSON format
		$category_json = json_encode($category_data);

		$tags = get_terms(array(
			'taxonomy' => 'post_tag', // Taxonomy name for tags
			'hide_empty' => false,    // Include empty tags
		));

		// Initialize an array to store tag data
		$tag_data = array();

		// Loop through the tags and store ID as key and name as value
		if (is_array($tags) && !empty($tags)) {
			foreach ($tags as $tag) {
				$tag_data[$tag->term_id] = $tag->name;
			}
		}

		if (empty($tag_data)) {
			$tag_data = array();
		}
		// Convert the array to JSON format
		$tag_json = json_encode($tag_data);

		$bc_tags = get_terms(array(
			'taxonomy' => 'bc_tag', // Custom taxonomy name
			'hide_empty' => false,  // Include empty terms
		));

		// Initialize an array to store term data
		$bc_tag_data = array();

		// Loop through the terms and store ID as key and name as value
		if (is_array($bc_tags) && !empty($bc_tags)) {
			foreach ($bc_tags as $bc_tag) {
				$bc_tag_data[$bc_tag->term_id] = $bc_tag->name;
			}
		}

		if (empty($bc_tag_data)) {
			$bc_tag_data = array();
		}

		// Convert the array to JSON format
		$bc_tag_json = json_encode($bc_tag_data);

		$post_types = get_post_types(array('public' => true), 'objects');
		$json_data = array();

		foreach ($post_types as $post_type) {
			if ('schemagic' == $post_type->name) continue;
			$post_type_name = $post_type->name;
			$post_type_labels = $post_type->labels;

			$json_data[$post_type_name] = $post_type_labels->name;
		}

		$post_type_json = json_encode($json_data);



		// Initialize an empty array to store archive data
		$archive_data = array();

		// Get all registered post types
		$post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
		foreach ($post_types as $post_type) {
			$post_type_name = $post_type->name;
			$post_type_labels = get_post_type_labels($post_type);

			// Add the post type archive name and title to the array
			$archive_data[$post_type_name] = $post_type_labels->archives;
		}

		// Get all registered taxonomies
		$taxonomies = get_taxonomies(array('public' => true), 'objects');
		foreach ($taxonomies as $taxonomy) {
			$taxonomy_name = $taxonomy->name;
			$taxonomy_labels = get_taxonomy_labels($taxonomy);

			// Add the taxonomy archive name and title to the array
			$archive_data[$taxonomy_name] = $taxonomy_labels->archives;
		}
		// Manually add the "author" archive
		// $archive_data['author'] = 'Author Archives';


		// Convert the data into JSON
		$archive_json = json_encode($archive_data);



		// Retrieve the current JSON data from the post meta
		$display_data = get_post_meta($post->ID, '_display_data', true);

		$display_data = $display_data == "" ? '{"condition": "AND","rules": [{ empty: true }],"valid": true}' : $display_data;

		// Output the JSON data textarea
	?>
		<label for="schemagic_display_data">Display when these conditions are met</label>
		<textarea style="display: none;" name="schemagic_display_data" id="schemagic_display_data" rows="10" cols="50"><?php echo esc_textarea($display_data); ?></textarea>
		<div id="qb"></div>

		<script>
			jQuery(document).ready(function($) {

				function updateJsonData() {
					var jsonData = jQuery('#qb').queryBuilder('getRules');

					if (jsonData !== null) {
						var jsonString = JSON.stringify(jsonData, null, 2);
						jQuery('#schemagic_display_data').val(jsonString);
					} else {
						jQuery('#schemagic_display_data').val(`{"condition": "AND","rules": [{ empty: true }],"valid": true}`);
					}

				}
				// Initialize QueryBuilder
				jQuery('#qb').queryBuilder({

					filters: [{
							id: "post_type",
							label: "Post Type",
							type: "string",
							input: "select",
							values: <?php echo $post_type_json ?>,
							operators: [
								"equal",
								"not_equal",
							],
						},

						{
							id: "post_id",
							label: "Page or Post ID",
							type: "string",
							input: "textarea",
							operators: [
								"equal",
								"not_equal",
								"in",
								"not_in",
							],
						},
						{
							id: "author",
							label: "Author ID",
							type: "string",
							input: "textarea",
							operators: [
								"equal",
								"not_equal",
								"in",
								"not_in",
							],
						},
						{
							id: "is_archive",
							label: "Archive Type",
							type: "string",
							input: "select",
							values: <?php echo $archive_json ?>,
							operators: [
								"equal",
							],
						},
						{
							id: "is_author",
							label: "Author Archive",
							type: "string",
							input: "select",
							values: {
								'author': 'All Authors'
							},
							operators: [
								"equal",
							],
						},
						{
							id: "user_id",
							label: "user ID",
							type: "string",
							input: "textarea",
							operators: [
								"equal",
								"not_equal",
								"in",
								"not_in",
							],
						},
						{
							id: "category",
							label: "Post Category",
							type: "integer",
							input: "select",
							values: <?php echo $category_json ?>,
							operators: [
								"in",
								"not_in",
							],
						},
						{
							id: "tag",
							label: "Post Tag",
							type: "integer",
							input: "select",
							values: <?php echo $tag_json ?>,
							operators: [
								"equal",
								"not_equal",
							],
						},
						{
							id: "bctag",
							label: "Breadcrumb Tag",
							type: "integer",
							input: "select",
							values: <?php echo $bc_tag_json ?>,
							operators: [
								"equal",
								"not_equal",
							],
						},
						{
							id: "variable",
							label: "PHP Global Variable",
							type: "string",
							input: "text",
							operators: [
								"equal",
							],
						},
						{
							id: "meta_true",
							label: "Meta Key Has Value",
							type: "string",
							input: "text",
							operators: [
								'equal'
							],
						},
						{
							id: "meta_false",
							label: "Meta Key Empty",
							type: "string",
							input: "text",
							operators: [
								'equal'
							],
						},


					],
					//  rules: [<?php echo json_encode($display_data); ?>]
				});

				// Load and set pre-filled QueryBuilder rules
				jQuery('#qb').queryBuilder('setRules', <?php echo ($display_data); ?>);

				// Load QueryBuilder data into textarea on change
				jQuery('#qb').on('rulesChanged.queryBuilder', function(e) {
					updateJsonData();
				});
			})
		</script>
	<?php
	}



	/**
	 * Saves the data from the Display Conditions metabox.
	 *
	 * This function is responsible for saving the data entered into the Display Conditions metabox
	 * for the 'schemagic' custom post type. It updates the display conditions data in the post meta.
	 *
	 * @param int $post_id The ID of the current WordPress post being saved.
	 *
	 * @return void
	 */
	public function save_display_metabox($post_id)
	{
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

		if (isset($_POST['schemagic_display_data'])) {
			$new_display_data = wp_unslash($_POST['schemagic_display_data']);
			// Update the display data field
			update_post_meta($post_id, '_display_data', $new_display_data);
		}
	}


	/**
	 * Adds a custom metabox for managing Reference Variables of the "schemagic" CPT.
	 *
	 * This function adds a custom metabox to the 'schemagic' custom post type for managing
	 * Reference Variables associated with Schema Templates.
	 *
	 * @return void
	 */
	public function add_reference_variables_metabox()
	{
		add_meta_box(
			'schemagic_reference_variables_metabox',
			'Reference Variables',
			array($this, 'render_reference_variables_metabox'),
			'schemagic', // Your custom post type slug
			'side',      // Metabox position (e.g., 'side' for the right side)
			'default'
		);
	}


	/**
	 * Callback to render the "Reference Variables" metabox content.
	 *
	 * This function is responsible for rendering the content of the "Reference Variables" metabox
	 * for the 'schemagic' custom post type. It provides a list of reference variables and filters
	 * that can be used in Schema Templates.
	 *
	 * @param WP_Post $post The current WordPress post object.
	 *
	 * @return void
	 */
	public function render_reference_variables_metabox($post)
	{
	?>
		<div id="quickies">
			<div>
				<strong>Variables</strong><br />
				<strong><code>#post</code></strong><br />
				<p><code>.permalink</code>, <code>.image</code>, <code>.image-w</code>, <code>.image-h</code>, <code>.date</code>, <code>.modified</code>, <code>.author</code>, <code>.authorurl</code>, <code>.authoravatar</code>, <code>.title</code>, <code>.breadcrumb</code>, <code>.faqs</code>, <code>.itemlist</code>, <code>.content</code></p>
				<br />
			</div>
			<div>
				<strong><code>#meta</code></strong><br />
				<p><code>.keyname</code></p>
				<br />
			</div>
			<div>
				<strong><code>#option</code></strong><br />
				<p><code>.optionname</code></p>
				<br />
			</div>
			<div>
				<strong><code>#author</code></strong><br />
				<p><code>.fieldname</code> , <code>.nickname</code>, <code>.url</code> ,<code>.email</code> ,<code>.avatar</code> , <code>.description</code></p>
				<br />
			</div>
			<div>
				<strong><code>#table</code></strong><br />
				<p><code>.tablename(column)</code></p>
				<br />
			</div>
			<div>
				<strong><code>#date</code></strong><br />
				<p><code>.endofmonth</code></p>
				<br />
			</div>
			<div>
				<strong><code>#cb</code></strong><br />
				<p><code>.function</code></p>
				<br />
			</div>

		</div>
		<hr>
		<div>
			<strong>Filters</strong><br />
			<p><code>.slug</code>, <code>.collection</code>, <code>.seperator</code>, <code>.keyvalue</code>, <code>.multiline</code>, <code>.hash</code></p>
			<hr>
		</div>


		<!-- Additional filters can be added as needed -->

<?php
	}



	/**
	 * Add custom publish buttons and input field for the "schemagic" CPT.
	 *
	 * This function adds custom publish buttons and an input field to the WordPress editor screen
	 * for the 'schemagic' custom post type. It allows users to set a custom post ID.
	 *
	 * @return void
	 */
	public function add_custom_publish_buttons()
	{
		global $post;
		$plugin_directory_uri = plugins_url('', __FILE__);
		$pid = get_post_meta($post->ID, 'previd', true) ? get_post_meta($post->ID, 'previd', true) : '1';
		// Check if this is the desired post type (e.g., 'post' or 'page')
		if ($post->post_type === 'schemagic') {
			// Output your custom buttons or content here
			echo '<div class="misc-pub-section custom-buttons">';
			echo '<input type="text" class="input" id="previd" style="width:48%" name="previd" placeholder="postid" value="' . $pid . '"/>';

			echo '<button type="button" id="openIframeButton"  class="button button-primary float-right">Preview</button>';
			echo '<button type="button" id="openDebugButton"  class="button  float-right">Debug</button>';
			echo '</div>';
			echo '<script>var schemapreview = "' . $plugin_directory_uri . '";</script>';
		}
	}


	// Add a custom column to the 'schemagic' post type list in the admin panel
	public function custom_shortcode_column($columns)
	{
		$columns['shortcode_column'] = 'Token';
		return $columns;
	}

	// Display content for the custom column
	public function custom_shortcode_column_content($column_name, $post_id)
	{
		if ($column_name === 'shortcode_column') {

			$display_data = get_post_meta($post_id, '_display_data', true);
			$ruleset = json_decode($display_data, true);
			if ($ruleset) return;

			// Display a textbox with the shortcode value
			echo '<input type="text" value="#template.' . esc_attr($post_id) . '#" readonly="readonly" style="width: 100%;" onclick="this.select()" />';
		}
	}
}
