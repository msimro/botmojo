<?php
/**
 * BotMojo Launcher
 * 
 * This script starts the DDEV server and opens the test interface in the default browser.
 */

// Start DDEV if it's not already running
echo "Starting DDEV server...\n";
exec('ddev start', $output, $returnVar);

if ($returnVar !== 0) {
    echo "Error starting DDEV server. Please check your DDEV configuration.\n";
    exit(1);
}

echo "DDEV server started successfully.\n";

// Open the test interface in the default browser
$url = "https://botmojo.ddev.site/oop_test.php";
echo "Opening {$url} in your default browser...\n";

// Detect platform and open browser accordingly
if (PHP_OS === 'Darwin') {
    // macOS
    exec("open \"{$url}\"");
} elseif (PHP_OS === 'WINNT') {
    // Windows
    exec("start \"{$url}\"");
} else {
    // Linux
    exec("xdg-open \"{$url}\"");
}

echo "BotMojo is ready to use!\n";
echo "API endpoint: https://botmojo.ddev.site/api_oop.php\n";
echo "Test interface: https://botmojo.ddev.site/oop_test.php\n";
echo "\n";
echo "Press Ctrl+C to exit this launcher (but the server will keep running).\n";

// Keep the script running to maintain the DDEV connection
while (true) {
    sleep(60);
}
