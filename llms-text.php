<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WP_LLMS_Text_Export {
    private $json_file_path;
    private $llms_txt_path;
    private $llms_full_txt_path;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->json_file_path = ABSPATH . '/datalake.json';
        $this->llms_txt_path = ABSPATH . 'llms.txt';
        $this->llms_full_txt_path = ABSPATH . 'llms-full.txt';

        add_action('admin_post_export_llms_text', array($this, 'export_llms_text'));
    }

    // ✅ Display LLMS Text Export Page
    public function display_llms_text_page() {
        echo '<div class="wrap">';
        echo '<h1>Export LLMS Text Files</h1>';

 
        echo '<p>Proposed that LLMs like llms.txt and lllms-full.txt files. <a href="https://llmstxt.org/" target="_blank">llmstxt.org</a>.</p>
        <p>The llms.txt is created from the JSON datalake. You must create this file first.</p>
        <p>Submit your llms.txt files to directories: <a href="https://llmstxt.site/" target="_blank">https://llmstxt.site/</a> and <a href="https://directory.llmstxt.cloud/" target="_blank">https://directory.llmstxt.cloud/</a>
        
        ';

        if (isset($_GET['exported']) && $_GET['exported'] == 'true') {
            echo '<p style="color: green; font-weight: bold;">Export successful!';
        }

        echo '<p><a href="' . home_url('/llms.txt') . '" target="_blank">Download llms.txt</a> | 
            <a href="' . home_url('/llms-full.txt') . '" target="_blank">Download llms-full.txt</a></p>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'Are you sure you want to generate new LLMS text files?\');">';
        echo '<input type="hidden" name="action" value="export_llms_text">';
        echo '<input type="submit" class="button button-primary" value="Generate LLMS Text Files">';
        echo '</form>';
        echo '</div>';
    }

    // ✅ Generate LLMS Text Files
    public function export_llms_text() {
        if (!file_exists($this->json_file_path)) {
            wp_die("❌ ERROR: Data lake JSON file not found.");
        }

        $json_content = file_get_contents($this->json_file_path);
        $data = json_decode($json_content, true);

        if ($data === null) {
            wp_die("❌ ERROR: JSON parsing failed.");
        }

        $llms_lines = [];
        $llms_full_lines = [];

        foreach ($data['data_lake']['categories'] as $category) {
            foreach ($category['records'] as $record) {
                $title = isset($record['title']) ? wp_strip_all_tags($record['title']) : 'Untitled';
                $date = isset($record['date']) ? wp_strip_all_tags($record['date']) : 'Unknown Date';
                $category_name = isset($category['category_name']) ? wp_strip_all_tags($category['category_name']) : 'Uncategorized';
                $text = isset($record['text']) ? wp_strip_all_tags($record['text']) : 'No content available.';
                $summary = isset($record['summary']) ? wp_strip_all_tags($record['summary']) : 'No summary available.';
                $source = isset($record['source']) ? esc_url($record['source']) : '#';

                // ✅ Format for llms.txt (Summary)
                $llms_lines[] = "# $title\n";
                $llms_lines[] = "> $summary\n";
                $llms_lines[] = "\nImportant notes:\n";
                $llms_lines[] = "- Published on: $date\n";
                $llms_lines[] = "- Category: $category_name\n";
                // $llms_lines[] = "- Learn more: $source\n";
                $llms_lines[] = "- [$title]($source): $summary\n";
                $llms_lines[] = "\n-------------------------\n\n";

                // ✅ Format for llms-full.txt (Full Content)
                $llms_full_lines[] = "# $title\n";
                $llms_full_lines[] = "> $summary\n";
                $llms_full_lines[] = "\nFull Content:\n";
                $llms_full_lines[] = "$text\n";
				$llms_full_lines[] = "\nImportant notes:\n";
                $llms_full_lines[] = "- Published on: $date\n";
                $llms_full_lines[] = "- Category: $category_name\n";
                // $llms_full_lines[] = "- Learn more: $source\n";
				$llms_full_lines[] = "- [$title]($source): $summary\n";
                $llms_full_lines[] = "\n-------------------------\n\n";
            }
        }

		//- [FastHTML quick start](https://docs.fastht.ml/path/quickstart.html.md): A brief overview of many FastHTML features

        // ✅ Save the files
        file_put_contents($this->llms_txt_path, implode("", $llms_lines));
        file_put_contents($this->llms_full_txt_path, implode("", $llms_full_lines));

        // ✅ Redirect with success message
        wp_redirect(admin_url('admin.php?page=ai_datalake_llms_bot&subpage=llms-text&exported=true'));
        exit;
    }
}

new WP_LLMS_Text_Export();
