<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/*
{"messages":[{"role":"system","content":"You are a helpful assistant."},{"role":"user","content":"Tell me about Pixie-Aura Dust and a Career Fair"},{"role":"assistant","content":"Spreading Digital Marketing Skills and Inspiration We\u2019re not just guiding your business\u2019s digital success. We're also inspiring the next generation of developers and digital marketers. Kristina Darling, our own Director of Brand Strategy, spent her Friday afternoon talking to the&hellip; Learn more at: http:\/\/pixaura.local\/pixie-aura-dust-and-a-career-fair\/"}]}

*/

class WP_ChatGPT_Training {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'chatgpt_training';

        // ✅ Ensure table creation on plugin activation
        register_activation_hook(__FILE__, array($this, 'create_training_table'));

        add_action('admin_post_save_training', array($this, 'save_training'));
        add_action('admin_post_delete_training', array($this, 'delete_training'));
        add_action('admin_post_update_training', array($this, 'update_training'));
    }

    // ✅ Ensure the training table is created
    public function create_training_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            response TEXT NOT NULL
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // ✅ Display the chatbot training page
    public function display_training_page() {
        global $wpdb;
        $training_items = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY id DESC");
        $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
        $edit_item = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $edit_id)) : null;

        echo '<div class="wrap">';
        echo '<h1>' . ($edit_item ? 'Edit Training Data' : 'Train Chatbot') . '</h1>';

        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
        echo '<input type="hidden" name="action" value="' . ($edit_item ? 'update_training' : 'save_training') . '">';
        if ($edit_item) {
            echo '<input type="hidden" name="id" value="' . esc_attr($edit_item->id) . '">';
        }
        echo '<table class="form-table">';
        echo '<tr><th><label for="question">User Input</label></th>';
        echo '<td><input type="text" name="question" required class="regular-text" value="' . esc_attr($edit_item->question ?? '') . '"></td></tr>';
        echo '<tr><th><label for="response">Chatbot Response</label></th>';
        echo '<td><textarea name="response" required class="regular-text" style="height: 200px;">' . esc_textarea($edit_item->response ?? '') . '</textarea></td></tr>';

        echo '</table>';
        echo '<input type="submit" value="' . ($edit_item ? 'Update Training Data' : 'Add Training Data') . '" class="button button-primary">';
        if ($edit_item) {
            echo '<a href="' . admin_url('admin.php?page=ai_datalake_llms_bot&subpage=training') . '" class="button">Cancel</a>';
        }
        echo '</form>';
        
        echo '<hr><h2>Existing Training Data</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>User Prompt</th><th>Response</th><th>Actions</th></tr></thead>';
        echo '<tbody>';

        foreach ($training_items as $item) {
            echo '<tr>';
            echo '<td>' . esc_html($item->question) . '</td>';
            echo '<td>' . esc_html($item->response) . '</td>';
            echo '<td>';
            echo '<a href="' . admin_url('admin.php?page=ai_datalake_llms_bot&subpage=training&edit_id=' . esc_attr($item->id)) . '" class="button button-secondary">Edit</a>';
            echo '<form method="post" action="' . admin_url('admin-post.php') . '" style="display:inline;">';
            echo '<input type="hidden" name="action" value="delete_training">';
            echo '<input type="hidden" name="id" value="' . esc_attr($item->id) . '">';
            echo '<input type="submit" value="Delete" class="button button-danger" onclick="return confirm(\'Are you sure?\');">';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }


    public function save_training() {
        global $wpdb;
        $question = sanitize_text_field($_POST['question']);
        $response = sanitize_textarea_field($_POST['response']);

        $wpdb->insert($this->table_name, [
            'question' => $question,
            'response' => $response
        ]);
        wp_redirect(admin_url('admin.php?page=ai_datalake_llms_bot&subpage=training&saved=true'));
        exit;
    }

    public function update_training() {
        global $wpdb;
        $id = intval($_POST['id']);
        $question = sanitize_text_field($_POST['question']);
        $response = sanitize_textarea_field($_POST['response']);

        $wpdb->update(
            $this->table_name,
            ['question' => $question, 'response' => $response],
            ['id' => $id]
        );
        wp_redirect(admin_url('admin.php?page=ai_datalake_llms_bot&subpage=training&updated=true'));
        exit;
    }

    public function delete_training() {
        global $wpdb;
        $id = intval($_POST['id']);
        $wpdb->delete($this->table_name, ['id' => $id]);
        wp_redirect(admin_url('admin.php?page=ai_datalake_llms_bot&subpage=training&deleted=true'));
        exit;
    }

    public function get_training_response($query) {
        global $wpdb;
        $table = $this->table_name;
        $training_item = $wpdb->get_row($wpdb->prepare("SELECT response FROM $table WHERE question = %s LIMIT 1", $query));

        return $training_item ? $training_item->response : null;
    }
}

new WP_ChatGPT_Training();
