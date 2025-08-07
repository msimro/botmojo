<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant (Triage Agent)</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            max-width: 1800px;
            margin: 2em auto;
            background-color: #f9f9f9;
            color: #333;
        }
        .container {
            background: #fff;
            padding: 2em;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #111;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        p {
            line-height: 1.6;
        }
        form {
            display: flex;
            gap: 10px;
            margin-bottom: 2em;
        }
        input {
            flex-grow: 1;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
        }
        button {
            padding: 12px 20px;
            background-color: #007aff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: #0056b3;
        }
        button:disabled {
            background-color: #999;
            cursor: not-allowed;
        }
        #console {
            background: #282c34;
            color: #abb2bf;
            padding: 1em;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: 'Menlo', 'Fira Code', 'Courier New', monospace;
            height: 500px;
            overflow-y: scroll;
            font-size: 0.9em;
            border: 1px solid #444;
        }
        .log-entry {
            border-bottom: 1px dashed #555;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .log-entry:last-child {
            border-bottom: none;
        }
        .log-title {
            font-weight: bold;
            color: #61afef; /* A nice blue */
        }
        .log-error {
            color: #e06c75; /* A reddish color for errors */
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>AI Personal Assistant</h1>
        <p>Type what you want to do in plain language below. The Triage Agent will analyze your request and create an execution plan.</p>
        
        <form id="chat-form">
            <input type="text" id="query-input" placeholder="e.g., My mother's name is Jane Doe" required autocomplete="off">
            <button type="submit" id="submit-button">Send</button>
        </form>

        <h2>Console Log</h2>
        <pre id="console"></pre>
    </div>

    <script>
        const form = document.getElementById('chat-form');
        const input = document.getElementById('query-input');
        const submitButton = document.getElementById('submit-button');
        const consoleLog = document.getElementById('console');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const query = input.value.trim();
            if (!query) return;

            // Disable form while processing
            submitButton.disabled = true;
            submitButton.textContent = 'Thinking...';
            
            const userLogHtml = createLogEntry('User Query', query);
            prependToConsole(userLogHtml);

            // Clear input for next query
            input.value = '';

            const formData = new FormData();
            formData.append('query', query);

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const responseText = await response.text();
                let responseHtml;

                try {
                    // Try to parse as JSON. This is the happy path.
                    const data = JSON.parse(responseText);
                    const formattedJson = JSON.stringify(data, null, 2); // Pretty-print JSON
                    responseHtml = createLogEntry('AI Response (Execution Plan)', formattedJson);
                } catch (jsonError) {
                    // If parsing fails, it's likely a PHP error message (HTML).
                    responseHtml = createLogEntry('FATAL ERROR', responseText, true);
                }

                prependToConsole(responseHtml);

            } catch (networkError) {
                const errorHtml = createLogEntry('Network Error', networkError.message, true);
                prependToConsole(errorHtml);
            } finally {
                // Re-enable form
                submitButton.disabled = false;
                submitButton.textContent = 'Send';
            }
        });

        function createLogEntry(title, content, isError = false) {
            const entryDiv = document.createElement('div');
            entryDiv.className = 'log-entry';
            
            const titleSpan = document.createElement('span');
            titleSpan.className = 'log-title';
            if (isError) {
                titleSpan.classList.add('log-error');
            }
            titleSpan.textContent = `> ${title}:\n`;
            
            const contentNode = document.createTextNode(content);
            
            entryDiv.appendChild(titleSpan);
            entryDiv.appendChild(contentNode);
            
            return entryDiv;
        }

        function prependToConsole(element) {
            consoleLog.prepend(element);
        }

    </script>
</body>
</html>