<?php
/**
 * Plugin Name: AI Datalake LLMs Text & Chatbot
 * Description: A Wordpress Plugin to create JSON Datalakes and llms.txt files. Also includes a ChatGPT-powered chatbot that logs chats, you can 'train' it, and more -- chat is in beta!
 * Version: 1.0
 * Author: Pixaura
 */


if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Include navigation and required files
require_once plugin_dir_path(__FILE__) . 'info.php';
require_once plugin_dir_path(__FILE__) . 'settings.php';
require_once plugin_dir_path(__FILE__) . 'log.php';
require_once plugin_dir_path(__FILE__) . 'export.php';
require_once plugin_dir_path(__FILE__) . 'training.php';
require_once plugin_dir_path(__FILE__) . 'llms-text.php';


class WP_ChatGPT_Chatbot {

	public function __construct() {
		add_action('admin_menu', array($this, 'add_chatbot_menu'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('rest_api_init', array($this, 'register_rest_routes'));
		register_activation_hook(__FILE__, array(__CLASS__, 'activate_plugin'));


		 // ✅ Register AJAX handlers for logged-in and guest users
		 add_action('wp_ajax_chatgpt_query', array($this, 'handle_chat_query_ajax'));
		 add_action('wp_ajax_nopriv_chatgpt_query', array($this, 'handle_chat_query_ajax'));


        // ✅ Register AJAX handlers
        add_action('wp_ajax_chatgpt_feedback', array($this, 'handle_feedback_submission'));
        add_action('wp_ajax_nopriv_chatgpt_feedback', array($this, 'handle_feedback_submission'));


	}

	public static function activate_plugin() {
		$instance = new self();
		$instance->create_chat_log_table();
		error_log("Plugin activated");
	}
	

	public function create_chat_log_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . "chatgpt_chat_log";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
			`id` BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			`user_id` BIGINT(20) UNSIGNED NULL,
			`user_query` TEXT NOT NULL,
			`bot_response` TEXT NOT NULL,
			`timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	public function add_chatbot_menu() {
		add_menu_page(
			'AI Datalakes<br>llms.txt Bot',
			'AI Datalakes<br>llms.txt Bot',
			'manage_options',
			'ai_datalake_llms_bot',
			array($this, 'main_plugin_page'),
			'dashicons-tagcloud'
		);
	}

	// Main plugin page - dynamically loads content based on `subpage` parameter
	public function main_plugin_page() {
		echo '<div class="wrap">';
		echo '<h1>AI Datalake LLMs.txt Chatbot</h1>';

		// Display navigation
		include plugin_dir_path(__FILE__) . 'navigation.php';

		// Get the requested subpage from navigation
		$current_subpage = isset($_GET['subpage']) ? sanitize_text_field($_GET['subpage']) : 'info';

		// Load the correct page content based on subpage
		switch ($current_subpage) {
			case 'settings':
				$settings_instance = new WP_ChatGPT_Settings();
				$settings_instance->settings_page();
				break;

			case 'training':
				$training_instance = new WP_ChatGPT_Training();
				$training_instance->display_training_page();

				break;
	

			case 'log':
				$log_instance = new WP_ChatGPT_Chat_Log();
				$log_instance->display_chat_logs();
				break;

			case 'llms-text':
				// Ensure export page displays content instead of triggering export automatically
				$export_instance = new WP_LLMS_Text_Export();
				$export_instance->display_llms_text_page();
				break;


			case 'export':
				// Ensure export page displays content instead of triggering export automatically
				$export_instance = new WP_ChatGPT_JSON_Export();
				$export_instance->display_export_page();
				break;

			case 'chatbot':
				echo '<p>Here\'s a sample chatbot from your <a href="/wp-admin/admin.php?page=ai_datalake_llms_bot&subpage=export">datalake</a>.</p>';
                echo'<p>I hope this chatbot will provide insight into how well the datalake can be used for LLMs</p>';
                echo'<p>Display chat with shortcode: [chatgpt_json_bot] or [chatgpt_json_bot training="true"]</p>';
				echo do_shortcode( '[chatgpt_json_bot training="true"]' );
				break;

			case 'info':
				default:
				$training_instance = new WP_ChatGPT_Info_Page();
				$training_instance->display_info_page();
				break;

		}

		echo '</div>';
	}

	public function register_settings() {
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_api_key');
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_bot_name');
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_bot_personality');
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_primary_color');
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_font_family');
		register_setting('wp_chatgpt_options_group', 'wp_chatgpt_training_data');
	}

// ✅ Ensure REST API handles direct fetch() requests as well

public function register_rest_routes() {
    register_rest_route('ai-datalake-bot/v1', '/query/', array(
        'methods'  => 'POST',
        'callback' => array($this, 'handle_chat_query_raw'),
        'permission_callback' => '__return_true',
    ));

    register_rest_route('ai-datalake-bot/v1', '/feedback/', array(
        'methods'  => 'POST',
        'callback' => array($this, 'handle_chat_feedback'),
        'permission_callback' => '__return_true',
    ));
}


public function handle_feedback_submission() {
    global $wpdb;

    // ✅ Ensure request is coming from AJAX
    if (!isset($_POST['query']) || !isset($_POST['response']) || !isset($_POST['feedback'])) {
        wp_send_json_error(['message' => '❌ ERROR: Missing required fields.']);
        wp_die();
    }

    // ✅ Sanitize input values
    $user_query = sanitize_text_field($_POST['query']);
    $bot_response = sanitize_textarea_field($_POST['response']);
    $feedback = sanitize_text_field($_POST['feedback']);

    // ✅ Ensure required data is not empty
    if (empty($user_query) || empty($bot_response) || empty($feedback)) {
        wp_send_json_error(['message' => '❌ ERROR: All fields are required.']);
        wp_die();
    }

    // ✅ Insert feedback into the database
    // $table_name = $wpdb->prefix . "chatgpt_feedback";

    $table_name = $wpdb->prefix . "chatgpt_chat_log";


    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id'      => get_current_user_id(),
            'user_query'   => $user_query,
            'bot_response' => $bot_response,
            'feedback'     => $feedback,
            'timestamp'    => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%s', '%s')
    );

    // ✅ Handle potential errors
    if ($result === false) {
        error_log("❌ ERROR: Feedback submission failed - " . $wpdb->last_error);
        wp_send_json_error(['message' => '❌ ERROR: Failed to save feedback.']);
    } else {
        error_log("✅ Feedback saved successfully.");
        wp_send_json_success(['message' => '✅ Feedback submitted successfully.']);
    }

    wp_die();
}


public function handle_chat_query(WP_REST_Request $request) {
    $user_query = strtolower(sanitize_text_field($request->get_param('query')));
    $response = $this->handle_chat_query_raw($user_query);

    // ✅ Fix: Ensure REST API response is correctly structured
    return rest_ensure_response(['data' => $response]);
}
public function handle_chat_query_ajax() {
    if (!isset($_POST['query'])) {
        wp_send_json_error(['response' => '❌ ERROR: No query received.']);
        wp_die();
    }

    $user_query = sanitize_text_field($_POST['query']);
    $response_data = $this->handle_chat_query_raw($user_query);

    if (!isset($response_data['response']) || empty($response_data['response'])) {
        wp_send_json_error(['response' => '❌ ERROR: Invalid chatbot response.']);
        wp_die();
    }

    // ✅ Debugging: Log Response Data
    error_log("🔵 Sending AJAX Response: " . json_encode($response_data));

    // ✅ Fix: Ensure only the response text is sent
    wp_send_json_success($response_data['response']); 
    wp_die();
}



public function handle_chat_query_raw($user_query) {
    global $wpdb;
    error_log("🔵 Chat query received: " . $user_query);

    $response_text = ""; // Initialize response to avoid undefined variable error

    // Step 1: Check training data and prioritize responses with positive feedback
    $trained_response = $wpdb->get_row(
        $wpdb->prepare("SELECT bot_response FROM {$wpdb->prefix}chatgpt_training 
        WHERE user_query LIKE %s AND feedback = 'positive' ORDER BY timestamp DESC LIMIT 1", "%$user_query%")
    );

    if ($trained_response) {
        error_log("✅ Returning trained response with positive feedback.");
        $response_text = wpautop($this->make_links_clickable($trained_response->bot_response));
        $this->save_chat_log($user_query, $response_text);
        return ['response' => $response_text];
    }

    // Step 2: Load JSONL Data lake
    $jsonl_file = ABSPATH . 'wp-content/uploads/datalake.jsonl';
    if (!file_exists($jsonl_file)) {
        return ['response' => wp_kses_post("❌ ERROR: Data lake file not found")];
    }

    $jsonl_content = file($jsonl_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$jsonl_content) {
        return ['response' => wp_kses_post("❌ ERROR: Unable to read the data lake file.")];
    }

    error_log("✅ JSONL Data lake loaded successfully");

    // Step 3: Perform Fuzzy Search using both `similar_text()` and `levenshtein()`
    $best_match = null;
    $best_similarity = 0;
    $best_levenshtein = PHP_INT_MAX; // Lower is better
    $similarity_threshold = 50; // Adjust similarity threshold (0-100)
    $levenshtein_threshold = 20; // Adjust max edit distance allowed

    foreach ($jsonl_content as $line) {
        $entry = json_decode($line, true);
        if (!$entry || !isset($entry['messages']) || !is_array($entry['messages'])) continue;

        // Extract user messages from JSONL
        $user_message = array_values(array_filter($entry['messages'], fn($msg) => $msg['role'] === 'user'));
        $assistant_message = array_values(array_filter($entry['messages'], fn($msg) => $msg['role'] === 'assistant'));

        if (!empty($user_message) && !empty($assistant_message)) {
            $stored_query = strtolower($user_message[0]['content']);
            $user_query_lower = strtolower($user_query);

            // Calculate similarity scores
            similar_text($user_query_lower, $stored_query, $similarity);
            $levenshtein_distance = levenshtein($user_query_lower, $stored_query);

            // Check if the match is good enough
            if (
                ($similarity > $best_similarity && $similarity >= $similarity_threshold) ||
                ($levenshtein_distance < $best_levenshtein && $levenshtein_distance <= $levenshtein_threshold)
            ) {
                $best_similarity = $similarity;
                $best_levenshtein = $levenshtein_distance;
                $best_match = $assistant_message[0]['content'];
            }
        }
    }

    // Step 4: Use the best match or provide a fallback response
    if ($best_match) {
        error_log("✅ Found best fuzzy match with similarity: $best_similarity and Levenshtein distance: $best_levenshtein -- $best_match");
        $response_text = $this->make_links_clickable($best_match);
    } else {
        error_log("⚠️ No close match found. Fetching response from OpenAI API...");

        // Fallback to ChatGPT API for general AI knowledge
        $response_text = $this->fetch_openai_response($user_query);
    }

    // ✅ Save chat log and return response
    $this->save_chat_log($user_query, $response_text);
    return ['response' => wp_kses_post($response_text)];
}

// ✅ Fetch AI response from OpenAI API
private function fetch_openai_response($query) {
    $api_key = get_option('wp_chatgpt_api_key', '');
    if (!$api_key) {
        return "I couldn't find a direct answer. Please refine your question.";
    }

    $url = "https://api.openai.com/v1/completions";
    $data = [
        "model" => "gpt-4",
        "prompt" => $query,
        "max_tokens" => 150
    ];

    $args = [
        "body" => json_encode($data),
        "headers" => [
            "Authorization" => "Bearer " . $api_key,
            "Content-Type" => "application/json"
        ],
        "timeout" => 10
    ];

    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        return "I couldn't process your request at the moment.";
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['choices'][0]['text'] ?? "I'm not sure, but I can try again!";
}




// ✅ Function to save chat logs in the database
private function save_chat_log($user_query, $bot_response) {
    global $wpdb;
    $table_name = $wpdb->prefix . "chatgpt_chat_log";

    $wpdb->insert(
        $table_name,
        array(
            'user_id'      => get_current_user_id(),
            'user_query'   => $user_query,
            'bot_response' => wpautop($this->make_links_clickable($bot_response)),
        ),
        array('%d', '%s', '%s')
    );

    if ($wpdb->last_error) {
        error_log("❌ ERROR: Chat log failed to save - " . $wpdb->last_error);
    } else {
        error_log("✅ Chat log saved successfully.");
    }
}

// ✅ Function to convert URLs into clickable links
private function make_links_clickable($text) {
    return preg_replace(
        '~(https?://[^\s]+)~i',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
        $text
    );
}


public function export_positive_training_data() {
    global $wpdb;
    $jsonl_file_path = ABSPATH . 'datalake-positive.jsonl';

    $positive_responses = $wpdb->get_results(
        "SELECT user_query, bot_response FROM {$wpdb->prefix}chatgpt_chat_log WHERE feedback = 'positive'"
    );

    $jsonl_lines = [];
    foreach ($positive_responses as $response) {
        $jsonl_lines[] = json_encode([
            "messages" => [
                ["role" => "system", "content" => "You are a helpful assistant."],
                ["role" => "user", "content" => $response->user_query],
                ["role" => "assistant", "content" => $response->bot_response]
            ]
        ]);
    }

    // Append new responses to the JSONL file
    file_put_contents($jsonl_file_path, implode("\n", $jsonl_lines), FILE_APPEND | LOCK_EX);

    return count($jsonl_lines);
}

}

function chatgpt_json_bot_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'training' => 'false', // Default to false
        ),
        $atts,
        'chatgpt_json_bot'
    );

    $show_feedback = ($atts['training'] === 'true') ? true : false;

    $bot_name = get_option('wp_chatgpt_bot_name', 'Chatbot');
    $primary_color = get_option('wp_chatgpt_primary_color', '#0073aa');
    $font_family = get_option('wp_chatgpt_font_family', 'Arial, sans-serif');

    ob_start();
    ?>
    <div id="chatgpt-bot" style="max-width: 400px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; font-family: <?php echo esc_attr($font_family); ?>;">
        <div id="chat-response" style="height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;">
            <p><strong><?php echo esc_html($bot_name); ?>:</strong> Hi! Ask me anything.</p>
        </div>
        <input type="text" id="chat-input" placeholder="Type your question..." style="width: 100%; padding: 8px;">
        <button id="chat-submit" style="width: 100%; padding: 8px; background: <?php echo esc_attr($primary_color); ?>; color: white; border: none; margin-top: 5px;">Send</button>
    </div>

    

    <script>



console.log("here")

document.getElementById('chat-submit').addEventListener('click', sendMessage);

// ✅ Allow "Enter" key to submit chat message
document.getElementById('chat-input').addEventListener('keypress', function(event) {
    if (event.key === "Enter") {
        event.preventDefault(); // Prevent new line
        sendMessage();
    }
});

function sendMessage() {
    console.log("sendMessage() triggered");
    let userInput = document.getElementById('chat-input').value.trim();
    let chatResponseDiv = document.getElementById('chat-response');

    if (!userInput) return; // Ignore empty messages

    chatResponseDiv.innerHTML += "<p><strong>You:</strong> " + userInput + "</p>";
    document.getElementById('chat-input').value = ''; // Clear input

    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'chatgpt_query',
            query: userInput
        })
    })
    .then(response => response.json()) 
    .then(data => {
        if (data.success && typeof data.data === "string") {
            appendBotResponse(chatResponseDiv, data.data, userInput, <?php echo $show_feedback ? 'true' : 'false'; ?>);
        } else {
            chatResponseDiv.innerHTML += "<p><strong><?php echo esc_html($bot_name); ?>:</strong> ❌ ERROR: Invalid response.</p>";
        }
    })
    .catch(error => {
        chatResponseDiv.innerHTML += "<p><strong><?php echo esc_html($bot_name); ?>:</strong> ❌ ERROR: Could not reach the server.</p>";
    });
}



    function appendBotResponse(chatResponseDiv, text, userQuery, showFeedback) {
        let responseWrapper = document.createElement('div');
        responseWrapper.classList.add("bot-response");

        let responseParagraph = document.createElement('p');
        responseParagraph.innerHTML = "<strong><?php echo esc_html($bot_name); ?>:</strong> ";
        responseWrapper.appendChild(responseParagraph);
        chatResponseDiv.appendChild(responseWrapper);

        let i = 0;
        function typeWriter() {
            if (i < text.length) {
                responseParagraph.innerHTML += text.charAt(i);
                i++;
                setTimeout(typeWriter, 7);
            } else if (showFeedback) {
                appendFeedbackButtons(responseWrapper, userQuery, text);
            }
        }
        typeWriter();

        chatResponseDiv.scrollTop = chatResponseDiv.scrollHeight;
    }

    function appendFeedbackButtons(responseWrapper, userQuery, botResponse) {
        let feedbackContainer = document.createElement('div');
        feedbackContainer.classList.add("feedback-buttons");

        let thumbsUp = document.createElement('button');
        thumbsUp.innerHTML = "👍";
        thumbsUp.classList.add("thumbs-up");
        thumbsUp.addEventListener("click", function () {
            submitFeedback(userQuery, botResponse, "positive");
        });

        let spacer = document.createElement('span');
        spacer.innerHTML = "&nbsp;";
        spacer.classList.add("spacer");

        let thumbsDown = document.createElement('button');
        thumbsDown.innerHTML = "👎";
        thumbsDown.classList.add("thumbs-down");
        thumbsDown.addEventListener("click", function () {
            submitFeedback(userQuery, botResponse, "negative");
        });

        feedbackContainer.appendChild(thumbsUp);
        feedbackContainer.appendChild(spacer);
        feedbackContainer.appendChild(thumbsDown);
        responseWrapper.appendChild(feedbackContainer);
    }

    function submitFeedback(userQuery, botResponse, feedback) {
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'chatgpt_feedback',
                query: userQuery,
                response: botResponse,
                feedback: feedback
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log("Feedback submitted:", data);
        })
        .catch(error => {
            console.error("Error submitting feedback:", error);
        });
    }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('chatgpt_json_bot', 'chatgpt_json_bot_shortcode');




new WP_ChatGPT_Chatbot();
