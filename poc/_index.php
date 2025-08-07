<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant POC</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 2rem auto; background: #f9f9f9; }
        form { display: flex; gap: 10px; }
        input { flex-grow: 1; padding: 10px; }
        button { padding: 10px; }
        #response { margin-top: 20px; padding: 15px; background: #eee; border-radius: 5px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>AI Personal Assistant POC</h1>
    <form id="query-form">
        <input type="text" id="query-input" name="query" placeholder="Ask a question..." required>
        <button type="submit">Ask</button>
    </form>
    <div id="response">Your answer will appear here. Try asking "Who is Maria Garcia?"</div>

    <script>
        document.getElementById('query-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const queryInput = document.getElementById('query-input');
            const responseDiv = document.getElementById('response');
            
            responseDiv.textContent = 'Thinking...';

            const formData = new FormData();
            formData.append('query', queryInput.value);

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.error) {
                    responseDiv.textContent = 'Error: ' + data.error;
                } else {
                    responseDiv.textContent = data.answer;
                }
            } catch (error) {
                responseDiv.textContent = 'Failed to fetch from API. ' + error;
            }
        });
    </script>
</body>
</html>