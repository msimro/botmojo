<?php
/**
 * BotMojo Frontend Interface - Chat Application Entry Point
 * 
 * This file serves as the main user interface for the BotMojo AI Personal Assistant.
 * It provides a responsive web-based chat interface that connects to the backend API
 * for intelligent conversation processing through the triage-first, agent-based system.
 * 
 * FEATURES:
 * - Real-time chat interface with message bubbles
 * - Debug mode for development and troubleshooting
 * - Conversation management with unique session IDs
 * - Example queries to demonstrate different agent capabilities
 * - Responsive design for desktop and mobile devices
 * - Auto-scroll chat container and keyboard shortcuts
 * 
 * ARCHITECTURE INTEGRATION:
 * - Connects to api.php for backend processing
 * - Maintains conversation context through session IDs
 * - Displays agent responses and debug information
 * - Links to dashboard.php for data visualization
 * 
 * @author BotMojo Development Team
 * @version 2.0.0
 * @since 2025-08-14
 * @see api.php Backend API orchestrator
 * @see dashboard.php Data visualization interface
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BotMojo - AI Personal Assistant</title>
    <style>
        /* =====================================================================
           CSS STYLESHEET FOR BOTMOJO CHAT INTERFACE
           ===================================================================== 
           
           This stylesheet defines the visual presentation for the BotMojo chat
           interface. It uses a mobile-first responsive design approach with
           flexible layouts and modern CSS features.
           
           DESIGN PRINCIPLES:
           - Clean, minimal interface to focus on conversation
           - High contrast for accessibility
           - Responsive grid layouts for multi-device support
           - Consistent spacing and typography hierarchy
           - Professional color scheme with semantic meaning
           
           ===================================================================== */
        
        /* ===================================================================
           GLOBAL LAYOUT AND TYPOGRAPHY FOUNDATION
           =================================================================== */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            min-height: 80vh;
        }
        
        /* ===================================================================
           NAVIGATION AND HEADER STYLING
           =================================================================== */
        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .nav-header h1 {
            margin: 0;
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.8rem;
        }
        
        .nav-links a {
            color: #007bff;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }
        
        .nav-links a:hover {
            background-color: #f8f9fa;
            color: #0056b3;
        }
        
        /* ===================================================================
           CHAT INTERFACE CONTAINER AND LAYOUT
           =================================================================== */
        .chat-container {
            border: 2px solid #e9ecef;
            height: 450px;
            overflow-y: auto;
            padding: 15px;
            margin-bottom: 25px;
            background-color: #fafbfc;
            border-radius: 8px;
            scroll-behavior: smooth;
        }
        
        /* ===================================================================
           INPUT CONTROLS AND BUTTON STYLING
           =================================================================== */
        .input-container {
            display: flex;
            gap: 12px;
            align-items: stretch;
        }
        
        #userInput {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.2s ease;
        }
        
        #userInput:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        /* Button base styling */
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
            min-width: 80px;
        }
        
        #sendBtn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        
        #sendBtn:hover {
            background: linear-gradient(135deg, #0056b3, #004085);
            transform: translateY(-1px);
        }
        
        #debugToggle {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }
        
        #debugToggle:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
        }
        
        #clearBtn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        #clearBtn:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
        }
        
        /* ===================================================================
           MESSAGE BUBBLE DESIGN AND LAYOUT
           =================================================================== */
        .message {
            margin-bottom: 15px;
            padding: 12px 16px;
            border-radius: 12px;
            max-width: 80%;
            word-wrap: break-word;
            line-height: 1.4;
        }
        
        .user-message {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            margin-left: auto;
            margin-right: 0;
            text-align: right;
            border-bottom-right-radius: 4px;
        }
        
        .assistant-message {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            margin-right: auto;
            margin-left: 0;
            border-bottom-left-radius: 4px;
        }
        
        .status {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            margin: 15px 0;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        
        /* Debug information styling */
        .debug-info {
            margin-top: 10px;
            padding: 12px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
            font-size: 11px;
            color: #495057;
            white-space: pre-wrap;
            overflow-x: auto;
            max-height: 200px;
        }
        
        /* ===================================================================
           EXAMPLE QUERIES GRID AND HELP SECTION
           =================================================================== */
        .examples-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .examples-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .example-category {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        .example-category strong {
            color: #2c3e50;
            font-size: 0.9rem;
            display: block;
            margin-bottom: 8px;
        }
        
        .example-category ul {
            margin: 0;
            padding-left: 18px;
            font-size: 0.85rem;
            color: #495057;
        }
        
        .example-category li {
            margin-bottom: 4px;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .example-category li:hover {
            color: #007bff;
        }
        
        /* ===================================================================
           RESPONSIVE DESIGN BREAKPOINTS
           =================================================================== */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                padding: 20px;
            }
            
            .nav-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .nav-links {
                align-self: stretch;
            }
            
            .input-container {
                flex-direction: column;
            }
            
            .btn {
                padding: 10px;
            }
            
            .examples-grid {
                grid-template-columns: 1fr;
            }
            
            .message {
                max-width: 95%;
            }
        }
        
        /* ===================================================================
           ACCESSIBILITY AND FOCUS STATES
           =================================================================== */
        .btn:focus,
        #userInput:focus {
            outline: 2px solid #007bff;
            outline-offset: 2px;
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .container {
                border: 2px solid #000;
            }
            
            .message {
                border: 1px solid;
            }
        }
        
        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
    <body>
        <div class="container">
            <!-- =============================================================
                 NAVIGATION HEADER SECTION
                 ============================================================= 
                 
                 Contains the main title and navigation links to other parts
                 of the application (dashboard, clear chat functionality).
                 Uses flexbox for responsive layout.
                 
                 ============================================================= -->
            <div class="nav-header">
                <h1>🤖 BotMojo - AI Personal Assistant</h1>
                <div class="nav-links">
                    <a href="dashboard.php" title="View your data and analytics">📊 Dashboard</a>
                    <a href="#" onclick="clearChat()" title="Clear current conversation">🗑️ Clear Chat</a>
                </div>
            </div>
            
            <!-- =============================================================
                 INTRODUCTION AND WELCOME MESSAGE
                 ============================================================= 
                 
                 Brief explanation of the system capabilities to help users
                 understand what they can do with the assistant.
                 
                 ============================================================= -->
            <div style="margin-bottom: 25px; padding: 15px; background: linear-gradient(135deg, #e3f2fd, #f3e5f5); border-radius: 8px; border-left: 4px solid #007bff;">
                <p style="margin: 0; color: #2c3e50; font-weight: 500;">
                    Welcome to BotMojo! 🚀 This intelligent assistant uses a <strong>triage-first, agent-based architecture</strong> 
                    to understand and manage your personal data across multiple domains. Simply type your request below and watch 
                    as specialized AI agents work together to help you.
                </p>
            </div>            
            <!-- =============================================================
                 MAIN CHAT INTERFACE CONTAINER
                 ============================================================= 
                 
                 This is the primary interaction area where users see the
                 conversation flow between themselves and the AI assistant.
                 Messages are dynamically added via JavaScript functions.
                 
                 FEATURES:
                 - Auto-scrolling to show latest messages
                 - Different styling for user vs assistant messages
                 - Status messages for processing indicators
                 - Debug information display when enabled
                 
                 ============================================================= -->
            <div class="chat-container" id="chatContainer">
                <div class="status">🎯 Ready to assist you! Start typing your message below to begin...</div>
            </div>
            
            <!-- =============================================================
                 USER INPUT AND CONTROL INTERFACE
                 ============================================================= 
                 
                 Contains the text input field and action buttons for user
                 interaction with the system. Uses flexbox for responsive
                 layout that adapts to different screen sizes.
                 
                 CONTROLS:
                 - Text input with Enter key support
                 - Send button for message submission
                 - Debug toggle for development mode
                 - Clear button for conversation reset
                 
                 ============================================================= -->
            <div class="input-container">
                <input 
                    type="text" 
                    id="userInput" 
                    placeholder="Ask me anything... (Try: 'I spent $25 on lunch' or 'Schedule meeting tomorrow at 3 PM')" 
                    autocomplete="off"
                    maxlength="500"
                    aria-label="Enter your message to the AI assistant"
                >
                <button id="sendBtn" class="btn" onclick="sendMessage()" title="Send message (or press Enter)">
                    Send
                </button>
                <button id="debugToggle" class="btn" onclick="toggleDebugMode()" title="Toggle debug information display">
                    Debug: Off
                </button>
                <button id="clearBtn" class="btn" onclick="clearChat()" title="Clear conversation history">
                    Clear
                </button>
            </div>
            
            <!-- =============================================================
                 EXAMPLE QUERIES AND AGENT DEMONSTRATION SECTION
                 ============================================================= 
                 
                 Provides users with concrete examples of how to interact with
                 different specialized agents. This helps users understand the
                 system capabilities and provides quick-start guidance.
                 
                 ORGANIZATION:
                 - Grouped by agent type for clear understanding
                 - Clickable examples for immediate testing
                 - Visual icons for easy agent identification
                 - Responsive grid layout for all screen sizes
                 
                 ============================================================= -->
            <div class="examples-section">
                <strong style="color: #2c3e50; font-size: 1rem;">💡 Try these example queries to see different agents in action:</strong>
                <div class="examples-grid">
                    <div class="example-category">
                        <strong>💰 Finance Agent</strong>
                        <ul>
                            <li onclick="sendExampleQuery(this.textContent)">"I spent $25 on lunch at McDonald's"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"I earned $1000 from freelancing"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"Paid $150 for groceries with my card"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"I bought coffee for $4.50 this morning"</li>
                        </ul>
                    </div>
                    <div class="example-category">
                        <strong>📅 Planner Agent</strong>
                        <ul>
                            <li onclick="sendExampleQuery(this.textContent)">"Schedule meeting with John tomorrow 3 PM"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"Remind me to call dentist next Friday"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"Add gym workout to my routine"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"I have a doctor appointment Monday at 2pm"</li>
                        </ul>
                    </div>
                    <div class="example-category">
                        <strong>🧠 Memory Agent</strong>
                        <ul>
                            <li onclick="sendExampleQuery(this.textContent)">"Remember that Sarah likes coffee"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"John works at Google as a developer"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"My favorite restaurant is Tony's Pizza"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"Sarah's birthday is March 15th"</li>
                        </ul>
                    </div>
                    <div class="example-category">
                        <strong>🏥 Health Agent</strong>
                        <ul>
                            <li onclick="sendExampleQuery(this.textContent)">"I went for a 30-minute run today"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"Track my water intake: 8 glasses"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"I feel stressed today"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"My blood pressure is 120/80"</li>
                        </ul>
                    </div>
                    <div class="example-category">
                        <strong>🧘 Spiritual Agent</strong>
                        <ul>
                            <li onclick="sendExampleQuery(this.textContent)">"I meditated for 10 minutes today"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"I'm feeling grateful for my family"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"Help me with mindfulness practice"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"I did yoga this morning"</li>
                        </ul>
                    </div>
                    <div class="example-category">
                        <strong>💬 General Agent</strong>
                        <ul>
                            <li onclick="sendExampleQuery(this.textContent)">"What's the weather like?"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"Tell me a joke"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"How are you today?"</li>
                            <li onclick="sendExampleQuery(this.textContent)">"What's the capital of France?"</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    <script>
        /**
         * ================================================================
         * BOTMOJO FRONTEND JAVASCRIPT - CHAT INTERFACE CONTROLLER
         * ================================================================
         * 
         * This script manages all client-side functionality for the BotMojo
         * chat interface. It handles user interactions, API communication,
         * message display, and debugging features.
         * 
         * MAIN RESPONSIBILITIES:
         * - Message sending and receiving
         * - Chat interface manipulation
         * - Conversation state management
         * - Debug mode functionality
         * - Error handling and user feedback
         * 
         * INTEGRATION POINTS:
         * - Communicates with api.php for backend processing
         * - Maintains conversation context via unique session IDs
         * - Displays structured responses from agent system
         * 
         * ================================================================
         */
        
        // ================================================================
        // GLOBAL STATE MANAGEMENT
        // ================================================================
        
        /**
         * Unique conversation identifier for this browser session
         * Generated using timestamp to ensure uniqueness across sessions
         * Used by backend to maintain conversation context and history
         * @type {string}
         */
        let conversationId = 'conv_' + Date.now();
        
        /**
         * Debug mode flag - when enabled, shows technical information
         * including triage data, agent processing details, and API responses
         * Useful for development, troubleshooting, and understanding system behavior
         * @type {boolean}
         */
        let debugMode = false;
        
        // ================================================================
        // MESSAGE DISPLAY AND UI MANIPULATION FUNCTIONS
        // ================================================================
        
        /**
         * Add a message bubble to the chat interface with proper styling
         * 
         * This function creates and displays messages in the chat container,
         * applying appropriate styling based on message type (user vs assistant).
         * It also handles debug information display when debug mode is enabled.
         * 
         * @param {string} content - The message text to display
         * @param {boolean} isUser - True for user messages, false for assistant responses
         * @param {object|null} debugData - Optional debug information to display below message
         * @returns {void}
         * 
         * @example
         * addMessage("Hello there!", true);  // User message
         * addMessage("Hi! How can I help?", false, debugData);  // Assistant with debug
         */
        function addMessage(content, isUser = false, debugData = null) {
            const chatContainer = document.getElementById('chatContainer');
            
            // Create message container element
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user-message' : 'assistant-message'}`;
            messageDiv.textContent = content;
            
            // Add timestamp for better context
            const timestamp = new Date().toLocaleTimeString();
            messageDiv.title = `Sent at ${timestamp}`;
            
            chatContainer.appendChild(messageDiv);
            
            // Display debug information if debug mode is enabled and data is available
            if (debugMode && debugData && !isUser) {
                const debugDiv = document.createElement('div');
                debugDiv.className = 'debug-info';
                debugDiv.innerHTML = '<strong>🔧 Debug Information:</strong><br>' + 
                                   JSON.stringify(debugData, null, 2);
                chatContainer.appendChild(debugDiv);
            }
            
            // Auto-scroll to show the latest message
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
        
        /**
         * Add a status message to indicate system state
         * 
         * Status messages are used to inform users about processing states,
         * system status, or provide contextual information about operations.
         * 
         * @param {string} content - The status text to display
         * @returns {void}
         * 
         * @example
         * addStatus("Processing your request...");
         * addStatus("✅ Chat cleared successfully");
         */
        function addStatus(content) {
            const chatContainer = document.getElementById('chatContainer');
            const statusDiv = document.createElement('div');
            statusDiv.className = 'status';
            statusDiv.textContent = content;
            chatContainer.appendChild(statusDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
        
        // ================================================================
        // CORE COMMUNICATION FUNCTIONS
        // ================================================================
        
        /**
         * Send user message to the AI assistant backend
         * 
         * This is the main communication function that handles the complete
         * flow of user interaction with the BotMojo backend system.
         * 
         * PROCESS FLOW:
         * 1. Validate user input
         * 2. Display user message in chat
         * 3. Send request to api.php backend
         * 4. Parse and display assistant response
         * 5. Handle errors gracefully
         * 6. Update UI state appropriately
         * 
         * @returns {Promise<void>} Resolves when message processing is complete
         * 
         * @example
         * // Called automatically when user clicks Send button or presses Enter
         * await sendMessage();
         */
        async function sendMessage() {
            const input = document.getElementById('userInput');
            const message = input.value.trim();
            
            // Input validation - ensure user entered something meaningful
            if (!message) {
                addStatus("⚠️ Please enter a message before sending.");
                return;
            }
            
            // Prevent very long messages that might cause issues
            if (message.length > 500) {
                addStatus("⚠️ Message too long. Please keep it under 500 characters.");
                return;
            }
            
            // Display user message and clear input field
            addMessage(message, true);
            input.value = '';
            
            // Show processing indicator to user
            addStatus('🤖 Processing your request through the agent system...');
            
            try {
                // Make POST request to backend API
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        query: message,
                        conversation_id: conversationId,
                        debug_mode: debugMode
                    })
                });
                
                // Check for HTTP-level errors
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Parse JSON response from backend
                const data = await response.json();
                
                // Debug: Log the response structure
                if (debugMode) {
                    console.log('API Response:', data);
                }
                
                // Handle response based on success status
                if (data.status === 'success' && data.response) {
                    // Display successful assistant response
                    addMessage(data.response, false, data.debug || data.plan);
                } else {
                    // Display error message to user
                    const errorMsg = data.message || data.error || 'Unknown error occurred';
                    addMessage(`❌ Sorry, I encountered an error: ${errorMsg}`, false);
                }
                
            } catch (error) {
                // Handle network errors, JSON parsing errors, etc.
                console.error('Communication error:', error);
                addMessage(`🔌 Connection error: ${error.message}. Please check your internet connection and try again.`, false);
            } finally {
                // Remove processing status indicator
                const statusElements = document.querySelectorAll('.status');
                if (statusElements.length > 1) {
                    statusElements[statusElements.length - 1].remove();
                }
            }
        }
        
        // ================================================================
        // UTILITY AND HELPER FUNCTIONS  
        // ================================================================
        
        /**
         * Send an example query by programmatically triggering a message
         * 
         * This function is called when users click on example queries in the
         * help section. It populates the input field and sends the message.
         * 
         * @param {string} query - The example query text to send
         * @returns {void}
         * 
         * @example
         * sendExampleQuery("I spent $25 on lunch at McDonald's");
         */
        function sendExampleQuery(query) {
            const input = document.getElementById('userInput');
            input.value = query.replace(/"/g, ''); // Remove quotes from examples
            sendMessage();
        }
        
        /**
         * Clear the entire chat interface and start fresh
         * 
         * This function removes all messages from the chat container and
         * generates a new conversation ID for a completely fresh start.
         * Useful when users want to begin a new conversation context.
         * 
         * @returns {void}
         * 
         * @example
         * clearChat(); // Called by Clear Chat button
         */
        function clearChat() {
            const chatContainer = document.getElementById('chatContainer');
            chatContainer.innerHTML = '<div class="status">✨ Chat cleared! Ready for a fresh conversation...</div>';
            
            // Generate new conversation ID for fresh start
            conversationId = 'conv_' + Date.now();
            
            // Focus back on input for immediate use
            document.getElementById('userInput').focus();
        }
        
        /**
         * Toggle debug mode on/off and update UI accordingly
         * 
         * Debug mode shows additional technical information including:
         * - Triage analysis results
         * - Agent processing details  
         * - API response structure
         * - Processing timing information
         * 
         * @returns {void}
         * 
         * @example
         * toggleDebugMode(); // Called by Debug toggle button
         */
        function toggleDebugMode() {
            debugMode = !debugMode;
            const debugBtn = document.getElementById('debugToggle');
            
            // Update button appearance and text
            debugBtn.textContent = debugMode ? 'Debug: On' : 'Debug: Off';
            debugBtn.style.background = debugMode ? 
                'linear-gradient(135deg, #28a745, #20c997)' : 
                'linear-gradient(135deg, #6c757d, #5a6268)';
            
            // Add status message about debug mode change
            addStatus(debugMode ? 
                '🔧 Debug mode enabled - technical details will be shown' : 
                '🔧 Debug mode disabled - clean responses only'
            );
        }
        
        // ================================================================
        // EVENT HANDLERS AND INITIALIZATION
        // ================================================================
        
        /**
         * Initialize the chat interface when page loads
         * 
         * Sets up event listeners, focuses input field, and prepares
         * the interface for user interaction.
         */
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on input field for immediate typing
            const userInput = document.getElementById('userInput');
            userInput.focus();
            
            // Add Enter key listener for message sending
            userInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault(); // Prevent form submission
                    sendMessage();
                }
            });
            
            // Add escape key listener to clear input
            userInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                }
            });
            
            // Auto-resize input field based on content (if needed in future)
            userInput.addEventListener('input', function() {
                // Placeholder for future auto-resize functionality
            });
        });
        
        /**
         * Handle keyboard shortcuts for power users
         */
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Enter to send message from anywhere
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
            
            // Ctrl/Cmd + L to clear chat
            if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
                e.preventDefault();
                clearChat();
            }
            
            // Ctrl/Cmd + D to toggle debug mode
            if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                e.preventDefault();
                toggleDebugMode();
            }
        });
        
    </script>
</body>
</html>
