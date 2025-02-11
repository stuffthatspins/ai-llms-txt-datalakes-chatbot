<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WP_ChatGPT_Info_Page {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_info_page'));
    }

    public function add_info_page() {
       
    }

    public function display_info_page() {
        ?>
        <div class="wrap">
        <p>Welcome to the <a href="https://www.pixaura.com/data-lakes-and-content-for-your-chatgpt-ready-site/" target="_blank">AI Datalake JSON</a> and <a href="https://llmstxt.org/" target="_blank">llms.txt</a> Wordpress plugin.</p>

        <p>The datalake is a JSON flatfile for use with an AI Chatbot and the llms.txt file is for LLMs to read your site content.</p>

        <p>The goal is to make JSON Data lakes and llms.txt from your WordPress content so LLMs can 'read' your content easier.  
       
        <p>The AI ChatGPT chatbot reads the content from your sites' datalake - <a href="/wp-content/uploads/datalake.json" target="_blank">datalake.json</a> and <a href="/wp-content/uploads/datalake.jsonl" target="_blank">datalake.jsonl</a>. The JSONL is created for your chatbot.</p>
        <p>All of this is intended to help with <a href="https://www.pixaura.com/services/geo-generative-engine-optimization/" target="_blank">GEO - Generative Engine Optimization.</a></p>
        <p>Join my subreddit <a href="https://www.reddit.com/r/GEO_GenEngineTalk/" target="_blank">/r/GEO_GenEngineTalk/</a> to talk more about GEO.</p>
        <p>Finally, if you like this plugin, you can buy me a cup of coffee - <a href=https://account.venmo.com/u/stuffthatspins" target="_blank">Venmo</a>. Thanks!
        </div>


        <img style="width: 25%;" src="/wp-content/plugins/ai-datalake-llms-chatbot/img/data-lakes.jpg">


        <p>
        <img style="width: 150px;" src="/wp-content/plugins/ai-datalake-llms-chatbot/img/pixaura.png">
        <br>
        ...dreamed of by: james @ pixaura dot com

        </p>

        <?php
    }
}

new WP_ChatGPT_Info_Page();
