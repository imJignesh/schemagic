<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://imjignesh.com
 * @since      1.0.0
 *
 * @package    Schemagic
 * @subpackage Schemagic/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Schemagic
 * @subpackage Schemagic/public
 * @author     Jignesh Patel <imjignesh2@gmail.com>
 */

global $scmagicdata;

class Schemagic_Public
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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$schemagic_opt = get_option('schemagic_opt', array());

		if (array_key_exists('enable', $schemagic_opt) && $schemagic_opt['enable']) {

			add_action('wp', array($this, 'get_schema_templates'));

			if ($schemagic_opt['location'] == 'header') {
				add_action('wp_head', array($this, 'print_schemagic'));
			} else if ($schemagic_opt['location'] == 'footer') {
				add_action('wp_footer', array($this, 'print_schemagic'));
			}
		}

		add_shortcode('schemagic', array($this, 'custom_shortcode_function'));
	}

	/**
	 * Method schema_query
	 *
	 * @param $display_data $display_data [explicite description]
	 * @param $post $post [explicite description]
	 *
	 * @return void
	 */
	public function schema_query($display_data, $post)
	{

		$ruleset = json_decode($display_data, true);

		if (!$ruleset) return;
		$condition  = $ruleset['condition'];
		$validation = array();
		foreach ($ruleset['rules'] as $rule) {
			if ('post_type' == $rule['id']) {
				if (is_singular()) {
					if ('equal' == $rule['operator']) {
						$validation[] =	$rule['value'] == get_post_type($post);
					} else {
						$validation[] =	$rule['value'] != get_post_type($post);
					}
				} else {
					$validation[] = null;
				}
			}

			if ('post_id' == $rule['id']) {
				if ('equal' == $rule['operator']) {
					$validation[] =	$rule['value'] == ($post->ID);
				}
				if ('not_equal' == $rule['operator']) {
					$validation[] =	$rule['value'] != ($post->ID);
				}
				if ('in' == $rule['operator']) {
					$validation[] =	 in_array($post->ID, explode(',', $rule['value']));
				}
				if ('not_in' == $rule['operator']) {
					$validation[] = !in_array($post->ID, explode(',', $rule['value']));
				}
			}


			if ('category' == $rule['id']) {
				$category_ids = wp_get_post_categories($post->ID);

				if ('in' == $rule['operator']) {
					$validation[] =	 in_array($rule['value'], $category_ids);
				}
				if ('not_in' == $rule['operator']) {
					$validation[] = !in_array($rule['value'], $category_ids);
				}
			}

			if ('tag' == $rule['id']) {

				$tag_ids = wp_get_post_tags($post->ID, array('fields' => 'ids'));


				if ('equal' == $rule['operator']) {
					$validation[] =	 in_array($rule['value'], $tag_ids);
				}
				if ('not_equal' == $rule['operator']) {
					$validation[] = !in_array($rule['value'], $tag_ids);
				}
			}

			if ('bctag' == $rule['id']) {
				$term_ids = wp_get_post_terms($post->ID, 'bc_tag', array('fields' => 'ids'));
				if ('equal' == $rule['operator']) {
					$validation[] =	 in_array($rule['value'], $term_ids);
				}
				if ('not_equal' == $rule['operator']) {
					$validation[] = !in_array($rule['value'], $term_ids);
				}
			}

			if ('is_archive' == $rule['id']) {
				$validation[] =	 is_post_type_archive($rule['value']);
			}
			if ('is_author' == $rule['id']) {
				$validation[] =	 is_author();
			}

			if ('user_id' == $rule['id']) {
				if (is_author()) {
					$user_id = get_the_author_meta('ID');

					if ('equal' == $rule['operator']) {
						$validation[] =	$rule['value'] == ($user_id);
					}
					if ('not_equal' == $rule['operator']) {
						$validation[] =	$rule['value'] != ($user_id);
					}
					if ('in' == $rule['operator']) {
						$validation[] =	 in_array($user_id, explode(',', $rule['value']));
					}
					if ('not_in' == $rule['operator']) {
						$validation[] = !in_array($user_id, explode(',', $rule['value']));
					}
				} else {
					$validation[] = null;
				}
			}

			if ('variable' == $rule['id']) {

				global ${$rule['value']};
				$validation[] = ${$rule['value']};
			}
			if ('meta_true' == $rule['id']) {
				$metakey = trim($rule['value']);
				$metaval  = get_post_meta($post->ID, $metakey, true);
				if ($metaval != "")
					$validation[] = ${$rule['value']};
			}
			if ('meta_false' == $rule['id']) {

				$metakey = trim($rule['value']);
				$metaval  = get_post_meta($post->ID, $metakey, true);
				if ($metaval == "")
					$validation[] = ${$rule['value']};
			}
		}

		$allTrue = array_reduce($validation, function ($carry, $item) {
			return $carry && $item;
		}, true);

		if ("OR" == $condition) {
			$isValid = in_array(true, $validation);
		} else {
			$isValid = $allTrue;
		}
		if (($isValid)) {
			// echo $isValid;

			return true;
		}
		// print_r($ruleset);
		//  echo  "---------------------";
		// print_r($validation);
		// return $validation;

	}
	public function get_schema_templates()
	{
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

			// Do something with the custom meta values
			//  echo "<h2>Title: " . get_the_title($currentpost) . "</h2>";
			// echo "<div>Schema Template: " . ($schema_template) . "</div>";
			//  echo "<div>Display Data: " . ($display_data) . "</div>";
			json_decode($schema_template);
			if (json_last_error() !== JSON_ERROR_NONE) {
				continue;
			}

			if ($this->schema_query($display_data, $currentpost))
				$schemadata[] = $schema_template ? $schema_template : "";
			// Reset the post data

		}
		wp_reset_postdata();
		global $scmagicdata;
		$scmagicdata =  join("", $schemadata);
		$post = $currentpost;
	}

	public function build_schema()
	{
		global $scmagicdata;
		$string = $scmagicdata;
		global $post;

		// $string = apply_filters('ls_print_schema',$string);

		$jsonData = $this->schemagic_replaceAll($string);

		return $jsonData;
	}
	public function preview_schematic()
	{
		$jsonData =	$this->build_schema();
		$jsonData = preg_replace('/\s+/', ' ', str_replace(["\r", "\n", "\t"], '', $jsonData));

		//print_r($jsonData);
		json_decode($jsonData);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return $jsonData;
		} else {
			$data = $this->removeEmptyKeys(json_decode($jsonData, true));
			$prettyJsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			return $prettyJsonData;
		}
	}

	public function print_schemagic()
	{
		$data = $this->preview_schematic();
		if ($data != "null" && $data != "[]") echo '<script id="seo" type="application/ld+json">' . $data . '</script>';
	}

	public function removeEmptyKeys($array)
	{
		// Initialize a new array to store non-empty values
		$result = [];
		//if(empty($array)) return $result;
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				// If the value is an array, recursively call the function
				$value = $this->removeEmptyKeys($value);

				// Add the key only if there are non-empty values within the sub-array
				if (!empty($value)) {
					$result[$key] = $value;
				}
			} else {
				// Add the key only if the value is not empty
				if (!empty($value)) {
					$result[$key] = $value;
				}
			}
		}

		return $result;
	}





	public function schema_filter($value, $filtername = null)
	{
		if (!$filtername) return $value;

		$filtername_part = explode("(", $filtername);


		switch ($filtername_part[0]) {
			case "slug":
				return sanitize_title($value);
				break;

			case "collection":
				$collection = explode(PHP_EOL, $value);
				$collection_str = "";
				foreach ($collection as $keyword) {
					if (trim($keyword))
						$collection_str .= trim($keyword) . ',';
				}
				return  substr($collection_str, 0, -1);
				break;

			case "seperator":
				$collection = explode(",", $value);
				$collection_str = "";
				foreach ($collection as $keyword) {
					if (trim($keyword))
						$collection_str .= '"' . trim($keyword) . '",';
				}
				return  '[' . substr($collection_str, 0, -1) . ']';
				break;

			case "keyvalue":
				$additionaldata = explode(PHP_EOL, $value);
				$additional_property = "";
				$properties = array();
				foreach ($additionaldata as $keyword) {
					$keywords = explode(":", $keyword);
					if (isset($keywords[1]) && isset($keywords[0])) $properties[] = '{"@type":"PropertyValue","name":"' . $keywords[0] . '","value":"' . $keywords[1] . '"}';
				}
				return "[" . join(",", $properties) . "]";
				break;

			case "multiline":
				$multiline = explode(PHP_EOL, $value);
				$properties = array();
				foreach ($multiline as $keyword) {
					$properties[] = '"' . $keyword . '"';
				}
				if ($properties) return  "[" . join(",", $properties) . "]";
				break;

			case "ifnull":
				$collection = trim(str_replace(")", "", $filtername_part[1]));
				return $collection;
				// return  '['.substr($collection_str,0,-1).']';
				break;

			case "hash":
				exit;
				$string = $value + 'sw48w9'; // Replace with the string you want to hash
				$hash = md5($string);

				return $hash;
				break;

			default:
				//if(is_array($value) && array_key_exists($filtername,$value)){ return $value[$filtername];}
				break;
		}
		// return apply_filters($value,$filtername);

		//exit;
	}


	public function schemagic_schema_value($key, $value, $post)
	{
		$key_content = explode(".", $key);
		$key_table = str_replace("#", "", $key_content[0]);
		if (array_key_exists(1, $key_content)) $key_key = str_replace("#", "", $key_content[1]);
		// $key_filter = str_replace("#","",$key_content[2]);
		if (isset($key_content[2])) {
			$key_filter = str_replace("#", "", $key_content[2]);
		} else {
			$key_filter = "";
		}


		global $wpdb;

		switch ($key_table) {
			case "post":
				if ("permalink" == $key_key) {
					return $this->schema_filter(get_permalink($post));
				}


				if ("image" == $key_key) {
					if (has_post_thumbnail($post)) {
						$image =  wp_get_attachment_url(get_post_thumbnail_id($post->ID), 'full');
						return $image;
					} else {
						global $post, $posts;
						$first_img = '';
						ob_start();
						ob_end_clean();
						$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
						$first_img = $matches[1][0];
						if ($first_img)
							return $first_img;
						else
							return '';
					}
				}
				if ("image-w" == $key_key) {
					if (has_post_thumbnail($post)) {
						$image =  wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
						return $image[1];
					}
				}
				if ("image-h" == $key_key) {
					if (has_post_thumbnail($post)) {
						$image =  wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
						return $image[2];
					}
				}
				if ("date" == $key_key) {
					remove_all_filters('get_the_date');
					remove_all_filters('get_the_time');
					return get_the_date('Y-m-d', $post);
				}
				if ("modified" == $key_key) {


					$timestamp = strtotime(get_the_modified_date('Y-m-d', $post->ID));
					$formattedDate = date('Y-m-d', $timestamp);
					return $formattedDate;
					//}

					//return get_the_modified_date('Y-m-d',$post);
				}
				if ("author" == $key_key) {
					$author_id = get_post_field('post_author', $post->ID);
					$author_name = get_the_author_meta('display_name', $author_id);
					return $author_name;
				}
				if ("authorurl" == $key_key) {
					$author_id = get_post_field('post_author', $post->ID);
					return  $this->schema_filter(get_author_posts_url($author_id));
				}
				if ("authoravatar" == $key_key) {
					$author_id = get_post_field('post_author', $post->ID);
					return  get_avatar_url($author_id);
				}

				if ("title" == $key_key) {
					return str_replace('"', '', wp_strip_all_tags(get_the_title()));
				}

				if ("content" == $key_key) {
					return str_replace('"', '', html_entity_decode(strip_tags(wp_strip_all_tags(strip_shortcodes(get_the_content())))));
				}

				if ("breadcrumb" == $key_key) {
					return  $this->schemagic_breadcrumb($post);
				}


				if ("faqs" == $key_key) {
					return $this->schemagic_faqs();
				}
				if ("itemlist" == $key_key) {
					$data = get_post_meta($post->ID, 'blocks', true);
					return $this->schemagic_itemlist($data, $post->ID);
				}

				break;


			case "meta":
				//if(schema_filter( trim(get_post_meta($post->ID,$key_key,true)),$key_filter)){
				return $this->schema_filter(str_replace('"', '', trim(get_post_meta($post->ID, $key_key, true))), $key_filter);

				//}

				break;
			case "option":

				$option = explode("(", $key_key);
				$option_name = str_replace("(", "", $option[0]);
				$option_key = str_replace(")", "", $option[1]);

				$optionvalue = get_option(trim($option_name));
				if (is_array($optionvalue) && array_key_exists($option_key, $optionvalue)) {
					return $this->schema_filter(trim($optionvalue[$option_key]), $key_filter);
				} else {
					return $this->schema_filter($optionvalue, $key_filter);
				}

				break;
			case "author":

				if (is_author()) {

					$user_id = get_the_author_meta('ID');
					$returnvalue = get_user_meta($user_id, $key_key, true) ? (get_user_meta($user_id, $key_key, true)) : "";
					if ($key_key == 'url') {
						$returnvalue = get_author_posts_url($user_id);
					}
					if ($key_key == 'email') {
						$returnvalue = get_the_author_meta('user_email');
					}
					if ($key_key == 'avatar') {

						$returnvalue =  get_avatar_url($user_id);
					}
					if ($key_key == 'name') {
						$returnvalue = get_the_author_meta('display_name');
					}

					return $this->schema_filter($returnvalue, $key_filter);
				} else {
					$user_id = get_post_field('post_author', $post->ID);
					$returnvalue = get_user_meta($user_id, $key_key, true) ? (get_user_meta($user_id, $key_key, true)) : "";
					return $this->schema_filter($returnvalue, $key_filter);
				}
				break;


			case "table":
				$table = explode("(", $key_key);
				$table_name = str_replace("(", "", $table[0]);
				$table_col = str_replace(")", "", $table[1]);
				$result = $wpdb->get_row($wpdb->prepare("select {$table_col} from {$wpdb->prefix}{$table_name} where post_id=%d", $post->ID), ARRAY_N);
				return str_replace('"', '', $result[0]);
				break;
			case "date":
				if ("endofmonth" == $key_key) {
					$lastDateOfThisMonth = strtotime('last day of this month');
					$lastDay = date('Y-m-d', $lastDateOfThisMonth);
					return $lastDay;
				}
				break;
			case "cb":
				global $post;
				// return null;
				if (function_exists($key_key)) {
					$optionvalue =  call_user_func($key_key, $post);
					return  $optionvalue;
				}
				break;
			case "template":

				if (is_numeric($key_key)) {
					$template = get_post_meta($key_key, '_schema_template', true);
					return $this->schemagic_replaceAll($template);
				} else {
					return "";
				}

				break;
			case "default":
				break;
		}
	}

	public function schemagic_replaceAll($code)
	{

		global $post;
		$pattern =  '/#.*#/mU';
		$pattern = '/#(.*?)#/';

		$s = [];
		if (is_array($code)) return;
		preg_match_all($pattern, $code, $matches, PREG_SET_ORDER, 0);


		foreach ($matches as $replacementcode) {
			$s[$replacementcode[0]] = wp_strip_all_tags($this->schemagic_schema_value($replacementcode[0], $code, $post));
		}
		$code = str_replace(array_keys($s), array_values($s), $code);
		$lines = array();


		$code = str_replace("#current_month", date('M'), $code);
		$code = str_replace("#current_year", date('Y'), $code);
		return do_shortcode($code);
	}




	public function schema_org_pros($post)
	{
		if (get_post_meta($post->ID, 'schema_org_enable_proscons', true)) {
			$value = get_post_meta($post->ID, 'schema_org_pros', true);
			$multiline = explode(PHP_EOL, $value);
			$properties = array();
			$i = 1;
			foreach ($multiline as $keyword) {
				if ("" != $keyword)
					$properties[] = array("@type" => "ListItem", "position" => $i++, "name" => $keyword);
			}
			return json_encode($properties);
			//	return  "[".join(",", json_encode($properties) )."]";
		}
		return [];
	}

	public function schema_org_cons($post)
	{
		if (get_post_meta($post->ID, 'schema_org_enable_proscons', true)) {
			$value = get_post_meta($post->ID, 'schema_org_cons', true);
			$multiline = explode(PHP_EOL, $value);
			$properties = array();
			$i = 1;
			foreach ($multiline as $keyword) {
				if ("" != $keyword)
					$properties[] = array("@type" => "ListItem", "position" => $i++, "name" => $keyword);
			}
			return json_encode($properties);
		}
		return [];
	}


	public function schemagic_breadcrumb($post)
	{
		// global $post;
		// $post = $p;
		$posttypes = array('post', 'page');
		$sitename = get_bloginfo('name');
		$siteurl =  get_bloginfo('url');
		$sitedesc =  get_bloginfo('description');
		$datepublished = get_the_date('Y-m-d');
		//$datemodified =  get_the_modified_date( 'Y-m-d',$post );
		$timestamp = strtotime(get_the_modified_date('Y-m-d', $post->ID));
		$datemodified = date('Y-m-d', $timestamp);
		$permalink = "";
		$posttitle = "";
		if (is_singular($posttypes)) {

			$posttitle = get_the_title();
			$permalink = get_permalink();
			$categories = get_the_category();
			if (!empty($categories)) {
				if (!is_home()) {
					$lc = array_pop($categories);
				} else {
					$lc = $categories[0];
				}
			}
			$result = preg_split('/<(.*?)>/u', $posttitle);
			$posttitlesmall = $result[0];
		} else if (is_archive() && !is_author()) {
			$posttitle =  strip_tags(get_the_archive_title());
			$obj_id = get_queried_object_id();
			$permalink = get_term_link($obj_id);

			$lc = (object)array();
			$lc->name = $posttitle;
			$lc->term_id = $obj_id;
		} else if (is_author()) {
			$posttitlesmall = $posttitle =  strip_tags(get_the_archive_title());
			$obj_id = get_queried_object_id();
			$permalink = get_author_posts_url($obj_id);
		} else if (is_home()) {
			$posttitle =  strip_tags(get_the_archive_title());
			$obj_id = get_queried_object_id();
			$permalink = get_post_type_archive_link('post');
		}

		if (!isset($obj_id)) {
			$posttitle = get_the_title();
			$permalink = get_permalink();
			$categories = get_the_category();
			if (!empty($categories)) {
				if (!is_home()) {
					$lc = array_pop($categories);
				} else {
					$lc = $categories[0];
				}
			}
			$result = preg_split('/<(.*?)>/u', $posttitle);
			$posttitlesmall = $result[0];
			$obj_id = $post->ID;
		}


		$breadcrumb = 1;

		if ($post) {
			$current_post_author_id = $post->post_author;
			$authorname = get_the_author_meta('display_name', $current_post_author_id);
		}
		$authorname = empty($authorname) ? "author" : $authorname;


		// $schema_org_options_options = get_option('schema_org_options_option_name');
		// $pageids = $schema_org_options_options['stopwebsite'];
		// $ids = explode(",", $pageids);
		// if (count($ids) && in_array($obj_id, $ids)) return;

		$schema = [
			"@type" => "BreadcrumbList",
			"@id" => esc_url($permalink) . '#breadcrumb',
			"itemListElement" => [
				[
					"@type" => "ListItem",
					"position" => $breadcrumb++,
					"item" => [
						"@id" => esc_url($siteurl),
						"url" => esc_url($siteurl),
						"name" => "Home"
					]
				]
			]
		];

		if ((is_archive() || is_singular($posttypes)) && !is_front_page()) {
			if (!empty($lc)) {
				$permalinkcat = get_term_link($lc->term_id);
				$permalinkcatname = $lc->name;
				$schema['itemListElement'][] = [
					"@type" => "ListItem",
					"position" => $breadcrumb++,
					"item" => [
						"@id" => esc_url($permalinkcat),
						"url" => esc_url($permalinkcat),
						"name" => esc_html($permalinkcatname)
					]
				];
			}
		}

		if (is_singular($posttypes) && has_term('', 'bc_tag', $post->ID)) {
			$terms = get_the_terms($post, 'bc_tag');
			if ($terms) {
				$schema['itemListElement'][] = [
					"@type" => "ListItem",
					"position" => $breadcrumb++,
					"item" => [
						"@id" => esc_url(get_term_link($terms[0], 'bc_tag')),
						"url" => esc_url(get_term_link($terms[0], 'bc_tag')),
						"name" => esc_html($terms[0]->name)
					]
				];
			}
		}

		if ((is_singular($posttypes) || is_author()) && !is_front_page()) {
			$schema['itemListElement'][] = [
				"@type" => "ListItem",
				"position" => $breadcrumb++,
				"item" => [
					"@id" => esc_url($permalink),
					"url" => esc_url($permalink),
					"name" => esc_html($this->schemagic_unslug(basename(get_permalink($post->ID))))
				]
			];
		}


		return json_encode($schema, JSON_PRETTY_PRINT);
	}


	public function schemagic_faqs()
	{
		global $post;
		$data = get_post_meta($post->ID, 'blocks', true);
		// do_faq_schema($blocks,$post->ID);

		$matchingElements = [];
		foreach ($data as $item) {
			if (isset($item["__type"]) && $item["__type"] === "faqs") {
				$matchingElements[] = $item;
			}
		}


		$schema = [];
		foreach ($matchingElements as $faq) {
			if ($faq['faqs']) {
				foreach ($faq['faqs'] as $faqlist) {

					$schema[] = [
						"@type" => "Question",
						"name" => $faqlist['question'],
						"acceptedAnswer" => [
							"@type" => "Answer",
							"text" => $faqlist['answer']
						]
					];
				}
			}
		}

		if (count($schema) > 0) {
			$schema = array(
				"@type" => "FAQPage",
				"mainEntity" =>  $schema
			);
			return json_encode($schema, JSON_PRETTY_PRINT);
		}
		return json_encode([]);
	}




	public function schemagic_unslug($str)
	{
		$title = ucwords(str_replace('-', ' ', $str));

		$keywords = $this->schemagic_title();
		$listogsttopwords = explode(' ', $keywords);
		$listofmanualkeywords = explode(PHP_EOL, get_option('custom_tools_keywords'));
		$listogsttopwords = array_merge($listogsttopwords, $listofmanualkeywords);


		foreach ($listogsttopwords as $word) {
			$title = str_replace(ucfirst(strtolower($word)), $word, $title);
		}

		return $title;
	}


	public function schemagic_title()
	{
		global $post;
		global $wpdb;
		$query = $wpdb->prepare("select title from {$wpdb->prefix}aioseo_posts where post_id=%d", $post->ID);
		$res = $wpdb->get_row($query, ARRAY_A);
		if ($res['title']) {
			return do_shortcode($res['title']);
		} else {
			$oldtitle = get_post_meta($post->ID, 'aioseo_title', true);
			if ($oldtitle) {
				return do_shortcode($oldtitle);
			} else {
				return do_shortcode(get_the_title());
			}
		}

		//
	}


	/**
	 * Method do_items_schema
	 *
	 * @param $data $data [string]
	 * @param $postid $postid [id]
	 * 
	 * for itemlist schema main function
	 *
	 * @return void
	 */
	public function schemagic_itemlist($data, $postid)
	{

		$matchingElements = [];
		foreach ($data as $item) {
			if (isset($item["__type"]) && $item["__type"] === "products") {
				$matchingElements[] = $item;
			}
		}

		if ($matchingElements) {
			$pdoducts = $matchingElements[0]['products'];
		}
		if (!$pdoducts || count($pdoducts) < 4) return;
		$string = $this->build_items_schema($pdoducts);

		$schema = [
			"@context" => "https://schema.org",
			"@type" => "ItemList",
			"name" => $this->schemagic_title(), //get_the_title($postid),
			"url" => get_the_permalink($postid),
			"itemListElement" => $string
		];
		// return $schema;
		return json_encode($schema);
	}

	public function generateReviewCount($postId, $titleLength)
	{
		// Generate a unique seed for the random number generator based on the post ID and title length
		$seed = intval($postId) + $titleLength;

		// Set the seed for the random number generator
		srand($seed);

		// Generate a random review count within the desired range (50 to 650)
		$reviewCount = rand(50, 650);

		return $reviewCount;
	}



	public function build_items_schema($products)
	{
		$schema = [];
		$i = 1;
		foreach ($products as $product_data) {
			$contentLines = explode(".", strip_tags($product_data["content"]));
			$description = trim($contentLines[0]);

			$postId = 201;
			$titleLength = strlen($product_data["title"]);

			$reviewCount = $this->generateReviewCount($postId, $titleLength);

			$product = [
				"@type" => "ListItem",
				"position" => $i++,
				"item" => [
					"@type" => "Product",
					"name" => $product_data["title"],
					"url" => $product_data["cta"],
					"image" => wp_get_attachment_image_url($product_data["image"]),
					"description" => $description,
					"brand" => [
						"name" => $product_data["brand"]
					],
					"aggregateRating" => [
						"@type"		  => "AggregateRating",
						"ratingValue" => $product_data["reviews"] ? $this->items_schema_avg_rating($product_data["reviews"]) : 1,
						"reviewCount" =>  $reviewCount
					]
				]
			];

			$schema[] = $product;
		}

		return $schema;
	}


	public function items_schema_avg_rating($reviewData)
	{
		$lines = explode("\n", $reviewData);
		$ratings = [];

		foreach ($lines as $line) {
			$matches = [];
			$value = explode(":", $line);
			if (!is_numeric($value[1])) continue;
			preg_match('/\d+(\.\d+)?/', $line, $matches);

			if (!empty($matches)) {
				$rating = floatval($matches[0]);
				$ratings[] = $rating;
			}
		}

		$averageRating = 0;
		if (!empty($ratings)) {
			$averageRating = round(array_sum($ratings) / count($ratings), 1);
		}

		return $averageRating;
	}



	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		//	wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/schemagic-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/schemagic-public.js', array( 'jquery' ), $this->version, false );

	}


	///custom shortcode


	public function custom_shortcode_function($atts)
	{
		// Call your custom function to retrieve schema templates
		$atts = shortcode_atts(array(
			'id' => 0, // Default to 0 if 'id' is not provided
		), $atts);



		$schema_template = get_post_meta($atts['id'], '_schema_template', true);
		$display_data = get_post_meta($atts['id'], '_display_data', true);
		// Return the template data
		//$post=get_post($atts['id'] );
		global $post;
		//print_r($post);
		if ($this->schema_query($display_data, $post))

			return $schema_template;
		// echo $display_data;
		// return $this->schema_query($display_data, $post);


	}
}
