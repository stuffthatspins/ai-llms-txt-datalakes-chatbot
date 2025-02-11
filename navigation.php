<?php
if (!defined('ABSPATH') || !is_admin()) {
    exit; // Exit if accessed directly or outside the admin panel
}

// Ensure navigation only appears on the plugin's main page
if (!isset($_GET['page']) || $_GET['page'] !== 'ai_datalake_llms_bot') {
    return; // Stop execution if not in the plugin's admin page
}

// Get the current subpage slug
$current_subpage = isset($_GET['subpage']) ? sanitize_text_field($_GET['subpage']) : 'chatbot';

// Define the pages for this plugin
$plugin_pages = [
    'info' => 'ℹ️ Info',
    'chatbot' => '🤖 Chatbot',
    'settings' => '🔧 Settings',
    'training' => '📚  Train the Robots',
    'log' => '📜 Chat Logs',
    'export' => '💧 Create JSON Datalake',
    'llms-text' => '👾 Create llms.txt',
    
];

?>
<div class="wrap">
    <nav class="wp-chatbot-nav">
        <ul style="list-style:none; display:flex; gap:15px; padding:0; font-size:16px;">
            <?php foreach ($plugin_pages as $slug => $label) : ?>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=ai_datalake_llms_bot&subpage=' . esc_attr($slug)); ?>"
                       class="button <?php echo ($current_subpage === $slug) ? 'button-primary' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</div>
<hr>
