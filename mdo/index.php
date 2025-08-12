<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communicative AI Assistant</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 1800px; margin: 2em auto; background-color: #f9f9f9; }
        .container { background: #fff; padding: 2em; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1, h2 { color: #111; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        form { display: flex; gap: 10px; margin-bottom: 1em; }
        input[type="text"] { flex-grow: 1; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 1em; }
        button { padding: 12px 20px; background-color: #007aff; color: white; border: none; border-radius: 6px; font-size: 1em; cursor: pointer; }
        button:disabled { background-color: #999; }
        
        /* New styles for the AI response area */
        #ai-response-area { background-color: #eef7ff; border-left: 4px solid #007aff; padding: 15px; margin-bottom: 2em; border-radius: 4px; display: none; /* Hidden by default */ }
        #ai-response-text { font-size: 1.1em; color: #333; line-height: 1.6; }
        #ai-response-text strong { color: #0056b3; }

        #console { background: #282c34; color: #abb2bf; padding: 1em; border-radius: 4px; white-space: pre-wrap; font-family: 'Menlo', monospace; height: 400px; overflow-y: scroll; font-size: 0.9em; }
        /* Other styles from previous version can be copied here if desired */
    </style>
</head>
<body>
    <div class="container">
        <h1>Communicative AI Assistant</h1>
        
        <form id="chat-form">
            <input type="text" id="query-input" placeholder="e.g., My daughter's name is Maya" required autocomplete="off">
            <button type="submit" id="submit-button">Send</button>
        </form>

        <!-- The new area for the AI's friendly response -->
        <div id="ai-response-area">
            <p id="ai-response-text"><strong>Assistant:</strong> </p>
        </div>

        <h2>Developer Console</h2>
        <pre id="console"></pre>
    </div>

    <script>
        const form = document.getElementById('chat-form');
        const input = document.getElementById('query-input');
        const submitButton = document.getElementById('submit-button');
        const consoleLog = document.getElementById('console');
        const aiResponseArea = document.getElementById('ai-response-area');
        const aiResponseText = document.getElementById('ai-response-text');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const query = input.value.trim();
            if (!query) return;

            submitButton.disabled = true;
            submitButton.textContent = 'Thinking...';
            
            // Hide previous AI response
            aiResponseArea.style.display = 'none';

            const formData = new FormData();
            formData.append('query', query);

            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const data = await response.json();

                // ** NEW: Handle and display the user-facing message **
                if (data.ai_message_to_user) {
                    aiResponseText.innerHTML = `<strong>Assistant:</strong> ${data.ai_message_to_user}`;
                    aiResponseArea.style.display = 'block';
                }

                // Display the technical details in the developer console
                consoleLog.textContent = `> User Query: ${query}\n\n` +
                                         `> AI Response (Plan & Results):\n${JSON.stringify(data, null, 2)}`;

            } catch (error) {
                // Display error in the main response area for visibility
                aiResponseText.innerHTML = `<strong>Error:</strong> An unexpected error occurred. Check the console for details.`;
                aiResponseArea.style.display = 'block';
                consoleLog.textContent = `! ERROR: ${error}`;
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Send';
                input.value = '';
            }
        });
    </script>
</body>
</html>