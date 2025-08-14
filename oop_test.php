<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BotMojo OOP Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .chat-container {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
        }
        .user-message {
            background-color: #e3f2fd;
            text-align: right;
            margin-left: 100px;
        }
        .bot-message {
            background-color: #f5f5f5;
            text-align: left;
            margin-right: 100px;
        }
        .input-area {
            display: flex;
            gap: 10px;
        }
        input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .version-toggle {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #fff3cd;
            border-radius: 4px;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>BotMojo OOP Test</h1>
    
    <div class="version-toggle">
        <label>
            <input type="radio" name="api-version" value="original" checked> Original API
        </label>
        <label>
            <input type="radio" name="api-version" value="oop"> OOP Version
        </label>
    </div>
    
    <div class="chat-container" id="chat-container">
        <div class="message bot-message">
            Hello! I'm BotMojo. How can I assist you today?
        </div>
    </div>
    
    <div class="input-area">
        <input type="text" id="user-input" placeholder="Type your message here..." />
        <button id="send-btn">Send</button>
    </div>
    
    <div id="response-details" style="margin-top: 20px;">
        <h3>API Response Details</h3>
        <pre id="response-json">No data yet</pre>
    </div>

    <script>
        const chatContainer = document.getElementById('chat-container');
        const userInput = document.getElementById('user-input');
        const sendBtn = document.getElementById('send-btn');
        const responseJson = document.getElementById('response-json');
        const apiVersionRadios = document.getElementsByName('api-version');
        
        // Keep track of conversation ID
        let conversationId = 'conv_' + Date.now();
        
        // Add event listeners
        sendBtn.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        function sendMessage() {
            const query = userInput.value.trim();
            if (!query) return;
            
            // Add user message to chat
            addMessage(query, 'user');
            userInput.value = '';
            
            // Get selected API version
            let apiEndpoint = '';
            for (const radio of apiVersionRadios) {
                if (radio.checked) {
                    apiEndpoint = radio.value === 'original' ? 'api.php' : 'api_oop.php';
                    break;
                }
            }
            
            // Create request body
            const requestBody = JSON.stringify({
                query: query,
                conversation_id: conversationId
            });
            
            // Show loading indicator
            addMessage('Processing...', 'bot', 'loading-message');
            
            // Send request to API
            fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: requestBody
            })
            .then(response => response.json())
            .then(data => {
                // Remove loading message
                const loadingMsg = document.getElementById('loading-message');
                if (loadingMsg) loadingMsg.remove();
                
                // Display response
                let botResponse;
                
                if (data.response) {
                    // Direct response from API
                    botResponse = data.response;
                } else if (data.plan && data.plan.suggested_response) {
                    // Response from plan
                    botResponse = data.plan.suggested_response;
                } else if (data.message) {
                    // Message (usually for errors)
                    botResponse = data.message;
                } else {
                    // Fallback
                    botResponse = "Received a response without a readable message.";
                }
                
                addMessage(botResponse, 'bot');
                
                // Display full response for debugging
                responseJson.textContent = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                // Remove loading message
                const loadingMsg = document.getElementById('loading-message');
                if (loadingMsg) loadingMsg.remove();
                
                // Display error
                addMessage('Error: ' + error.message, 'bot');
                console.error('Error:', error);
            });
        }
        
        function addMessage(text, sender, id = null) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}-message`;
            if (id) messageDiv.id = id;
            messageDiv.textContent = text;
            
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    </script>
</body>
</html>
