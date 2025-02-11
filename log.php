<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class WP_ChatGPT_Chat_Log {

	public function __construct() {
		add_action('admin_post_clear_chat_logs', array($this, 'clear_chat_logs'));
		add_action('wp_ajax_add_to_training', array($this, 'add_to_training')); // ✅ AJAX action for adding training data
	}

	public function display_chat_logs() {
		global $wpdb;
		$table_name = $wpdb->prefix . "chatgpt_chat_log";
		$logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");

		echo '<div class="wrap"><h1>ChatGPT Chat Logs</h1>';

		// Clear logs button
		echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'Are you sure you want to delete all chat logs?\');">';
		echo '<input type="hidden" name="action" value="clear_chat_logs">';
		echo '<input type="submit" class="button button-danger" value="Clear All Logs">';
		echo '</form>';

		echo '<p>Click on a column to sort the data.</p>';
		echo '<table class="wp-list-table widefat fixed striped" id="chat-logs-table">';
		echo '<thead><tr>
				<th onclick="sortTable(0)">User</th>
				<th onclick="sortTable(1)">Query</th>
				<th onclick="sortTable(2)">Response</th>
				<th onclick="sortTable(3)">Feedback</th>
				<th onclick="sortTable(4)">Timestamp</th>
				<th>Actions</th>
			  </tr></thead>';
		echo '<tbody>';
		foreach ($logs as $log) {
			$user = $log->user_id ? get_userdata($log->user_id)->display_name : 'Guest';
			$emoji = ($log->feedback == "negative") ? "👎 Negative" : "👍 Positive";

			echo "<tr data-feedback='" . esc_attr($log->feedback) . "'>
					<td>{$user}</td>
					<td>{$log->user_query}</td>
					<td>{$log->bot_response}</td>
					<td>{$emoji}</td>
					<td>{$log->timestamp}</td>
					<td>
						<button class='add-to-training button' 
								data-question='" . esc_attr($log->user_query) . "' 
								data-response='" . esc_attr($log->bot_response) . "'>Add to Training</button>
					</td>
				</tr>";
		}
		echo '</tbody></table>';

		// ✅ Modal for Editing Training Data Before Saving
		echo '<div id="training-modal" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border: 1px solid #ddd; z-index: 9999;">
				<h2>Edit Training Data</h2>
				<label for="training-question">Question:</label>
				<input type="text" id="training-question" style="width:100%;">
				<label for="training-response">Response:</label>
				<textarea id="training-response" rows="4" style="width:100%;"></textarea>
				<br><br>
				<button id="save-training" class="button button-primary">Save to Training</button>
				<button id="close-modal" class="button">Cancel</button>
			  </div>';

		// ✅ JavaScript to Handle Modal and AJAX Request
		echo '<script>
			document.addEventListener("DOMContentLoaded", function() {
				let modal = document.getElementById("training-modal");
				let trainingQuestion = document.getElementById("training-question");
				let trainingResponse = document.getElementById("training-response");

				document.querySelectorAll(".add-to-training").forEach(button => {
					button.addEventListener("click", function() {
						trainingQuestion.value = this.getAttribute("data-question");
						trainingResponse.value = this.getAttribute("data-response");
						modal.style.display = "block";
					});
				});

				document.getElementById("close-modal").addEventListener("click", function() {
					modal.style.display = "none";
				});

				document.getElementById("save-training").addEventListener("click", function() {
					let question = trainingQuestion.value;
					let response = trainingResponse.value;

					fetch(ajaxurl, {
						method: "POST",
						headers: { "Content-Type": "application/x-www-form-urlencoded" },
						body: new URLSearchParams({
							action: "add_to_training",
							question: question,
							response: response
						})
					})
					.then(response => response.json())
					.then(data => {
						if (data.success) {
							alert("Training data saved successfully!");
							modal.style.display = "none";
						} else {
							alert("Error saving training data.");
						}
					})
					.catch(error => console.error("Error:", error));
				});
			});
		</script>';

		echo '</div>';
	}

	// ✅ Function to Handle Adding Training Data via AJAX
	public function add_to_training() {
		global $wpdb;
		$training_table = $wpdb->prefix . "chatgpt_training_data";

		$question = sanitize_text_field($_POST['question']);
		$response = sanitize_textarea_field($_POST['response']);

		$wpdb->insert($training_table, [
			'question' => $question,
			'response' => $response
		]);

		wp_send_json_success(["message" => "Training data saved successfully"]);
	}

	// ✅ Function to Clear Chat Logs
	public function clear_chat_logs() {
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized action.');
		}

		global $wpdb;
		$table_name = $wpdb->prefix . "chatgpt_chat_log";
		$wpdb->query("TRUNCATE TABLE $table_name");

		wp_redirect(admin_url('admin.php?page=ai_datalake_llms_bot&subpage=log&cleared=true'));
		exit;
	}
}

new WP_ChatGPT_Chat_Log();
