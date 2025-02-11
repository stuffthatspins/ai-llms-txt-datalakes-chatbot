<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the navigation
//include_once plugin_dir_path(__FILE__) . 'navigation.php';

class WP_ChatGPT_LLMS_Export {

    private $json_file_path;
    private $llms_file_path;
    private $llms_full_file_path;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->json_file_path = $upload_dir['basedir'] . '/datalake.json';
        $this->llms_file_path = ABSPATH . 'llms.txt';
        $this->llms_full_file_path = ABSPATH . 'llms-full.txt';

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_generate_llms', array($this, 'generate_llms_files'));
    }
    
    public function export_page() {
        echo '<div class="wrap">';
        echo '<h1>Generate LLMS Files</h1>';
        
        if (isset($_GET['exported']) && $_GET['exported'] == 'true') {
            echo '<p style="color: green; font-weight: bold;">Export successful! <a href="' . home_url('/llms.txt') . '" target="_blank">Download llms.txt</a> | 
            <a href="' . home_url('/llms-full.txt') . '" target="_blank">Download llms-full.txt</a></p>';
        }
    
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'Are you sure you want to regenerate the LLMS files?\');">';
        
        // 🔹 Security Nonce Field
        wp_nonce_field('generate_llms_nonce');

        echo '<input type="hidden" name="action" value="generate_llms">';
        echo '<input type="submit" class="button button-primary" value="Generate LLMS Files">';
        echo '</form>';
        echo '</div>';
    }

    public function generate_llms_files() {
        // ✅ Ensure user is logged in
        if (!is_user_logged_in()) {
            wp_die(__('Unauthorized action. You must be logged in.', 'wp-chatbot'));
        }

        // ✅ Ensure user has the correct capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized action. You do not have permission to perform this task.', 'wp-chatbot'));
        }

        // ✅ Verify Nonce (security check)
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'generate_llms_nonce')) {
            wp_die(__('Security check failed. Please try again.', 'wp-chatbot'));
        }

        // ✅ Debugging step: Check if file exists
        if (!file_exists($this->json_file_path)) {
            wp_die(__('ERROR: Data lake file not found.', 'wp-chatbot'));
        }

        // ✅ Read and decode JSON
        $json_content = file_get_contents($this->json_file_path);
        $data = json_decode($json_content, true);

        if (!$data) {
            wp_die(__('ERROR: Failed to parse JSON data.', 'wp-chatbot'));
        }

        // ✅ Process Data and Create LLMS Files
        $llms_content = [];
        $llms_full_content = [];

        foreach ($data['data_lake']['categories'] as $category) {
            foreach ($category['records'] as $record) {
                $title = $record['title'] ?? '';
                $summary = $record['content']['summary'] ?? '';
                $slug = $record['slug'] ?? '';
                $post_link = !empty($slug) ? home_url('/') . $slug : '';

                if (!empty($title) && !empty($summary)) {
                    $llms_content[] = "# " . $title . "\n> " . $summary . "\n";
                    $llms_full_content[] = "# " . $title . "\n> " . $summary . "\n\n" . $post_link . "\n";
                }
            }
        }

        // ✅ Write to Files
        file_put_contents($this->llms_file_path, implode("\n", $llms_content));
        file_put_contents($this->llms_full_file_path, implode("\n", $llms_full_content));

        // ✅ Redirect to admin page with success message
        wp_redirect(admin_url('admin.php?page=wp_json_llms_export&exported=true'));
        exit;
    }
}

// ✅ Properly instantiate the class so methods can be called dynamically
new WP_ChatGPT_LLMS_Export();
