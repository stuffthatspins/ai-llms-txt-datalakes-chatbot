
document.getElementById('chat-submit').addEventListener('click', function () {
    let userInput = document.getElementById('chat-input').value;
    let chatResponseDiv = document.getElementById('chat-response');

    if (!userInput) return;

    chatResponseDiv.innerHTML += "<p><strong>You:</strong> " + userInput + "</p>";
    document.getElementById('chat-input').value = '';

    console.log("🚀 Sending Request:", userInput);

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
        console.log("📡 AJAX Response:", data); 

        if (data.success && typeof data.data === "string") {
            console.log("✅ Bot Response:", data.data);
            appendBotResponse(chatResponseDiv, data.data, userInput);
        } else {
            console.error("❌ AJAX Error Response:", data);
            chatResponseDiv.innerHTML += "<p><strong><?php echo esc_html($bot_name); ?>:</strong> ❌ ERROR: Invalid response.</p>";
        }
    })
    .catch(error => {
        console.error("❌ AJAX Fetch Error:", error);
        chatResponseDiv.innerHTML += "<p><strong><?php echo esc_html($bot_name); ?>:</strong> ❌ ERROR: Could not reach the server.</p>";
    });
});

function appendBotResponse(chatResponseDiv, text, userQuery) {
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
            setTimeout(typeWriter, 7); // Adjust speed here
        } else {
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

    let thumbsDown = document.createElement('button');
    thumbsDown.innerHTML = "👎";
    thumbsDown.classList.add("thumbs-down");
    thumbsDown.addEventListener("click", function () {
        submitFeedback(userQuery, botResponse, "negative");
    });

    feedbackContainer.appendChild(thumbsUp);
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

