<?php
// Simple database connection test
require_once 'config.php';

try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        echo "Database connection failed: " . $db->connect_error;
    } else {
        echo "Database connection successful!";
        $result = $db->query("SELECT COUNT(*) as count FROM entities");
        if ($result) {
            $row = $result->fetch_assoc();
            echo " Entities table accessible. Current count: " . $row['count'];
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
