<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Personal Assistant - Core v1</title>
    <style>
        /* ========        
        // =================================================================
        // EVENT HANDLERS AND INITIALIZATION
        // =================================================================
        
        // Allow Enter key to send message (keyboard shortcut)
        document.getElementById('userInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Focus on input field when page loads for better UX
        window.onload = function() {
            document.getElementById('userInput').focus();
        };
    </script>
</body>
</html>==========================================
           MAIN LAYOUT AND TYPOGRAPHY
           ================================================================= */
        body {
            font-family: Arial, sans-serif;
            max-width: 1800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* =================================================================
           CHAT INTERFACE STYLING
           ================================================================= */
        .chat-container {
            border: 1px solid #ddd;
            height: 400px;
            overflow-y: auto;
            padding: 10px;
            margin-bottom: 20px;
            background-color: #fafafa;
            border-radius: 5px;
        }
        
        /* =================================================================
           INPUT AND BUTTON STYLING
           ================================================================= */
        .input-container {
            display: flex;
            gap: 10px;
        }
        #userInput {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        #sendBtn {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        #sendBtn:hover {
            background-color: #0056b3;
        }
        
        /* =================================================================
           MESSAGE BUBBLE STYLING
           ================================================================= */
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .user-message {
            background-color: #e3f2fd;
            text-align: right;
        }
        .assistant-message {
            background-color: #f1f8e9;
        }
        .status {
            color: #666;
            font-style: italic;
            text-align: center;
            margin: 10px 0;
        }
        
        /* =================================================================
           NAVIGATION STYLING
           ================================================================= */
        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .nav-links a {
            color: #007bff;
            text-decoration: none;
            margin-left: 15px;
            font-weight: 500;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- =================================================================
             NAVIGATION HEADER
             ================================================================= -->
        <div class="nav-header">
            <h1>AI Personal Assistant - Core v1</h1>
            <div class="nav-links">
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="#" onclick="clearChat()">🗑️ Clear Chat</a>
            </div>
        </div>
        
        <!-- =================================================================
             INTRODUCTION
             ================================================================= -->
        <p>Welcome to your intelligent personal assistant. This system uses a triage-first, agent-based architecture to understand and manage your life data.</p>
        
        <!-- =================================================================
             CHAT INTERFACE
             ================================================================= -->
        <div class="chat-container" id="chatContainer">
            <div class="status">Ready to assist you. Start typing your message below...</div>
        </div>
        
        <!-- =================================================================
             INPUT INTERFACE
             ================================================================= -->
        <div class="input-container">
            <input type="text" id="userInput" placeholder="Type your message here..." autocomplete="off">
            <button id="sendBtn" onclick="sendMessage()">Send</button>
        </div>
        
        <!-- =================================================================
             EXAMPLE QUERIES AND HELP
             ================================================================= -->
        <div style="margin-top: 20px; font-size: 12px; color: #666;">
            <strong>💡 Try these example queries to see different agents in action:</strong>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">
                <div>
                    <strong>💰 Finance Agent:</strong>
                    <ul style="margin: 5px 0; padding-left: 15px;">
                        <li>"I spent $25 on lunch at McDonald's"</li>
                        <li>"I earned $1000 from freelancing"</li>
                        <li>"Paid $150 for groceries with my card"</li>
                    </ul>
                </div>
                <div>
                    <strong>📅 Planner Agent:</strong>
                    <ul style="margin: 5px 0; padding-left: 15px;">
                        <li>"Schedule meeting with John tomorrow 3 PM"</li>
                        <li>"Remind me to call dentist next Friday"</li>
                        <li>"Add gym workout to my routine"</li>
                    </ul>
                </div>
                <div>
                    <strong>🧠 Memory Agent:</strong>
                    <ul style="margin: 5px 0; padding-left: 15px;">
                        <li>"Remember that Sarah likes coffee"</li>
                        <li>"John works at Google as a developer"</li>
                        <li>"My favorite restaurant is Tony's Pizza"</li>
                    </ul>
                </div>
                <div>
                    <strong>💬 General Agent:</strong>
                    <ul style="margin: 5px 0; padding-left: 15px;">
                        <li>"What's the weather like?"</li>
                        <li>"Tell me a joke"</li>
                        <li>"How are you today?"</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // =================================================================
        // GLOBAL VARIABLES AND INITIALIZATION
        // =================================================================
        
        // Generate unique conversation ID for this session
        let conversationId = 'conv_' + Date.now();
        
        // =================================================================
        // UI MANIPULATION FUNCTIONS
        // =================================================================
        
        /**
         * Add a message bubble to the chat interface
         * @param {string} content - The message text to display
         * @param {boolean} isUser - True for user messages, false for assistant
         */
        function addMessage(content, isUser = false) {
            const chatContainer = document.getElementById('chatContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user-message' : 'assistant-message'}`;
            messageDiv.textContent = content;
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight; // Auto-scroll to bottom
        }
        
        /**
         * Add a status message (for processing indicators, etc.)
         * @param {string} content - The status text to display
         */
        function addStatus(content) {
            const chatContainer = document.getElementById('chatContainer');
            const statusDiv = document.createElement('div');
            statusDiv.className = 'status';
            statusDiv.textContent = content;
            chatContainer.appendChild(statusDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight; // Auto-scroll to bottom
        }
        
        
        // =================================================================
        // MAIN MESSAGE SENDING FUNCTION
        // =================================================================
        
        /**
         * Send user message to the AI assistant API
         * Handles the complete flow: input validation, API call, response display
         */
        async function sendMessage() {
            const input = document.getElementById('userInput');
            const message = input.value.trim();
            
            // Validate that user entered a message
            if (!message) return;
            
            // Display user message in chat and clear input
            addMessage(message, true);
            input.value = '';
            
            // Show processing indicator
            addStatus('Processing your request...');
            
            try {
                // Make API call to backend
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        query: message,
                        conversation_id: conversationId
                    })
                });
                
                // Check for HTTP errors
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Parse JSON response
                const data = await response.json();
                
                // Display appropriate response based on success/failure
                if (data.success && data.response) {
                    addMessage(data.response);
                } else {
                    addMessage('Sorry, I encountered an error processing your request: ' + (data.error || 'Unknown error'));
                }
                
            } catch (error) {
                // Handle network or parsing errors
                console.error('Full error details:', error);
                console.error('Error message:', error.message);
                console.error('Error stack:', error.stack);
                addMessage('Sorry, I encountered a network error. Please try again. Error: ' + error.message);
            }
            
            // Remove the processing status message
            const statusElements = document.querySelectorAll('.status');
            if (statusElements.length > 1) {
                statusElements[statusElements.length - 1].remove();
            }
        }
        
        // =================================================================
        // UTILITY FUNCTIONS
        // =================================================================
        
        /**
         * Clear the chat interface
         */
        function clearChat() {
            const chatContainer = document.getElementById('chatContainer');
            chatContainer.innerHTML = '<div class="status">Chat cleared. Ready to assist you!</div>';
            // Generate new conversation ID for fresh start
            conversationId = 'conv_' + Date.now();
        }
        
        // =================================================================
        // EVENT LISTENERS AND INITIALIZATION
        // =================================================================
        
        // Allow Enter key to send message
        document.getElementById('userInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Focus on input when page loads
        window.onload = function() {
            document.getElementById('userInput').focus();
        };
    </script>
</body>
</html>
