const fs = require('fs');
const OpenAI = require('openai');
const express = require('express');
const axios = require('axios');
require('dotenv').config();

const app = express();
const port = 3000;

const openai = new OpenAI({ apiKey: process.env.OPENAI_API_KEY });
const JSON_URL = 'https://yourdomain.com/wp-content/uploads/wordpress_export.json';
let dataLake = null;

// Load JSON data
async function loadDataLake() {
	try {
		const response = await axios.get(JSON_URL);
		dataLake = response.data;
		console.log('Data Lake Loaded Successfully');
	} catch (error) {
		console.error('Error loading Data Lake:', error);
	}
}

// Find relevant data from JSON
function searchDataLake(query) {
	if (!dataLake) return 'Data lake is not loaded yet.';
	let results = [];

	dataLake.data_lake.categories.forEach(category => {
		category.records.forEach(record => {
			if (record.title.toLowerCase().includes(query.toLowerCase()) ||
				record.content.text.toLowerCase().includes(query.toLowerCase())) {
				results.push({
					title: record.title,
					text: record.content.summary,
					link: record.categories.length ? record.categories[0].category_link : '#',
					image: record.metadata.featured_image || null
				});
			}
		});
	});
	return results.length ? results : 'No relevant data found.';
}

// Chatbot API
app.use(express.json());
app.post('/chat', async (req, res) => {
	const userQuery = req.body.query;
	const searchResults = searchDataLake(userQuery);
	
	let prompt = `You are a chatbot that only answers using the following JSON data:
	${JSON.stringify(searchResults, null, 2)}
	
	Question: ${userQuery}
	Answer:`;
	
	const response = await openai.Completion.create({
		model: 'gpt-4',
		prompt: prompt,
		max_tokens: 250
	});
	
	res.json({ response: response.choices[0].text.trim(), results: searchResults });
});

// Load Data and Start Server
loadDataLake();
app.listen(port, () => {
	console.log(`Chatbot server running on port ${port}`);
});