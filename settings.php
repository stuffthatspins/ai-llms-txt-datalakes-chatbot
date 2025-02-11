<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class WP_ChatGPT_Settings {

	public function __construct() {
		add_action('admin_init', array($this, 'register_settings'));
	}

	public function register_settings() {
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_api_key');
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_bot_name');
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_bot_personality');
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_primary_color');
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_font_family');
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_training_data');
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_acf_exclude_fields'); // ✅ Register ignored ACF fields
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_export_post_types'); // ✅ Register exportable post types
	}

	public function settings_page() {
		?>
		<div class="wrap">
			<h1>ChatGPT Plugin Settings</h1>
			<form method="post" action="options.php">
				<?php settings_fields('wp_chatgpt_options_group'); ?>

				<h2>API Configuration</h2>
				<label for="wp_chatgpt_api_key">OpenAI API Key:</label>
				<br>
				<input type="text" id="wp_chatgpt_api_key" name="wp_chatgpt_api_key" value="<?php echo esc_attr(get_option('wp_chatgpt_api_key', '')); ?>" style="width: 100%; max-width: 400px;">
				<p><a href="https://openai.com/index/openai-api/" target="_blank">OpenAI API Key Needed</a></p>

				<h2>Chatbot Configuration</h2>
				<label for="wp_chatgpt_bot_name">Chatbot Name:</label>
				<br>
				<input type="text" id="wp_chatgpt_bot_name" name="wp_chatgpt_bot_name" value="<?php echo esc_attr(get_option('wp_chatgpt_bot_name', 'ChatGPT Bot')); ?>" style="width: 100%; max-width: 400px;">
				<br><br>
				<label for="wp_chatgpt_bot_personality">Chatbot Personality:</label>
				<br>
				<textarea id="wp_chatgpt_bot_personality" name="wp_chatgpt_bot_personality" rows="3" style="width: 100%; max-width: 400px;"><?php echo esc_textarea(get_option('wp_chatgpt_bot_personality', 'Friendly and helpful AI assistant.')); ?></textarea>

				<h2>Chatbot Training</h2>
				<label for="wp_chatgpt_training_data">Training Data (JSON or FAQs):</label>
				<br>
				<textarea id="wp_chatgpt_training_data" name="wp_chatgpt_training_data" rows="6" style="width: 100%; max-width: 600px;"><?php echo esc_textarea(get_option('wp_chatgpt_training_data', '')); ?></textarea>

				<h2>Chatbot Styling</h2>
				<label for="wp_chatgpt_primary_color">Primary Color:</label>
				<br>
				<input type="color" id="wp_chatgpt_primary_color" name="wp_chatgpt_primary_color" value="<?php echo esc_attr(get_option('wp_chatgpt_primary_color', '#0073aa')); ?>">
				<br>
				<label for="wp_chatgpt_font_family">Font Family:</label>
				<br>
				<input type="text" id="wp_chatgpt_font_family" name="wp_chatgpt_font_family" value="<?php echo esc_attr(get_option('wp_chatgpt_font_family', 'Arial, sans-serif')); ?>" style="width: 100%; max-width: 400px;">

				<h2>Export Settings</h2>
				
				<h3>Select Post Types to Export</h3>
				<p>Select which post types should be included in the datalake export:</p>
				<?php
				// Get all public post types
				$post_types = get_post_types(array('public' => true, 'show_ui' => true), 'objects');
				$selected_post_types = get_option('wp_chatgpt_export_post_types', ['post', 'page']); // Default to posts & pages
				
				foreach ($post_types as $post_type) {
					// Skip Media (attachments) & Unwanted Post Types
					if (in_array($post_type->name, ['attachment', 'saswp'])) continue;

					$checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
					echo '<label style="display:block;">
							<input type="checkbox" name="wp_chatgpt_export_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . '> 
							' . esc_html($post_type->label) . '
						</label>';
				}
				?>

				<h3>Exclude ACF Fields from Export</h3>
				<label for="wp_chatgpt_acf_exclude_fields">ACF Fields to Ignore (comma-separated):</label>
				<br>
				<textarea id="wp_chatgpt_acf_exclude_fields" name="wp_chatgpt_acf_exclude_fields" rows="3" style="width: 100%; max-width: 400px;"><?php echo esc_textarea(get_option('wp_chatgpt_acf_exclude_fields', '')); ?></textarea>
				<p>Specify ACF fields to ignore during export, separated by commas (e.g., "field1, field2, field3").</p>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

new WP_ChatGPT_Settings();
