<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class WP_ChatGPT_JSON_Export {
	private $json_file_path;
	private $jsonl_file_path;

	public function __construct() {
		$upload_dir = wp_upload_dir();
		$this->json_file_path = ABSPATH . 'datalake.json';
		$this->jsonl_file_path = ABSPATH . 'datalake.jsonl';

		// Register export action
		add_action('admin_post_export_json', array($this, 'export_json'));
	}

	// ✅ Display export page
	public function display_export_page() {
		echo '<div class="wrap">';
		echo '<h1>Export JSON & JSONL Datalake</h1>';
		echo '<p>Proposed <a href="https://www.pixaura.com/data-lakes-and-content-for-your-chatgpt-ready-site/" target="_blank">Datalakes for LLMs.</a>.</p>';

		echo '<a href="/datalake.json" target="_blank">Download JSON File</a> | 
			  <a href="/datalake.jsonl" target="_blank">Download JSONL File</a></p>';

		echo '<form method="post" action="' . admin_url('admin-post.php') . '" onsubmit="return confirm(\'Are you sure you want to generate new JSON & JSONL files?\');">';
		echo '<input type="hidden" name="action" value="export_json">';
		echo '<input type="submit" class="button button-primary" value="Generate JSON & JSONL Data">';
		echo '</form>';
		echo '</div>';
	}

	// ✅ Generate JSON and JSONL data
	public function export_json() {
		global $wpdb;

		$data = [
			"data_lake" => [
				"version" => "1.0",
				"last_updated" => gmdate("Y-m-d\TH:i:s\Z"),
				"categories" => []
			]
		];

		$jsonl_lines = []; // Stores AI-ready JSONL lines

		// ✅ Get selected post types from settings
		$selected_post_types = get_option('wp_chatgpt_export_post_types', ['post', 'page']); // Default to posts & pages
		if (empty($selected_post_types)) {
			$selected_post_types = ['post', 'page']; // Fallback to default
		}

		// ✅ Fetch ignored ACF fields from plugin settings
		$acf_exclude_fields = get_option('wp_chatgpt_acf_exclude_fields', '');
		$acf_exclude_list = array_map('trim', explode(',', $acf_exclude_fields));

		$post_types = get_post_types(array('public' => true, 'show_ui' => true), 'objects');

		foreach ($post_types as $post_type) {
			// 🚨 Exclude Media (attachments) and "Structured Data" (saswp)
			if (!in_array($post_type->name, $selected_post_types)) continue;
			if ($post_type->name === 'attachment' || $post_type->name === 'saswp') continue;

			$posts = get_posts(array(
				'post_type'      => $post_type->name,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'orderby'        => 'date',
				'order'          => 'DESC'
			));

			$category_data = [
				"category_name" => ucfirst($post_type->label),
				"category_id" => $post_type->name,
				"description" => "Data from {$post_type->label} section",
				"records" => []
			];

			foreach ($posts as $post) {
				$slug = get_post_field('post_name', $post->ID);
				$post_link = get_permalink($post->ID);
				$text_content = wp_strip_all_tags($post->post_content);
				$summary = wp_strip_all_tags(wp_trim_words($post->post_content, 40));

				// ✅ Fetch ACF Fields and Exclude Ignored Fields
				$acf_fields = get_fields($post->ID);
				$acf_data = [];

				if (!empty($acf_fields)) {
					foreach ($acf_fields as $field_name => $field_value) {
						// Skip ignored ACF fields
						if (in_array($field_name, $acf_exclude_list)) continue;

						// Only include text fields
						$field_type = get_field_object($field_name)['type']; 
						if ($field_type === 'text' && !empty($field_value)) {
							if (is_array($field_value)) {
								$acf_data[$field_name] = json_encode($field_value);
							} else {
								$acf_data[$field_name] = $field_value;
							}
						}
					}
				}

				// ✅ Structure for JSON file
				$post_data = [
					"title" => $post->post_title,
					"date" => $post->post_date,
					"modified_date" => $post->post_modified,
					"category" => ucfirst($post_type->label),
					"text" => $text_content,
					"summary" => $summary,
					"source" => $post_link
				];

				if (!empty($acf_data)) {
					$post_data["acf_fields"] = $acf_data;
				}

				$system_prompt = ["role" => "system", "content" => "You are a helpful assistant."];

				// ✅ Structure for JSONL (AI Training)
				$jsonl_line = [
					"messages" => [
						$system_prompt,
						["role" => "user", "content" => "Tell me about " . $post->post_title],
						["role" => "assistant", "content" => $summary . " [Learn more](" . $post_link .")" ] 
					]
				];

				$jsonl_lines[] = json_encode($jsonl_line);
				$category_data["records"][] = $post_data;
			}

			if (!empty($category_data["records"])) {
				$data["data_lake"]["categories"][] = $category_data;
			}
		}

		// ✅ Save JSON file
		file_put_contents($this->json_file_path, json_encode($data, JSON_PRETTY_PRINT));
		// ✅ Save JSONL file (one JSON object per line)
		file_put_contents($this->jsonl_file_path, implode("\n", $jsonl_lines));

		// ✅ Redirect to export page with success message
		wp_redirect(admin_url('admin.php?page=ai_datalake_llms_bot&subpage=export&exported=true'));
		exit;
	}
}

new WP_ChatGPT_JSON_Export();
