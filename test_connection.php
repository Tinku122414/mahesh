<?php
// Simple database connection test
require_once 'config/database.php';

echo "<h1>Database Connection Test</h1>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test if tables exist
    $tables = ['categories', 'products', 'users'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' missing</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<h2>Solution:</h2>";
    echo "<ol>";
    echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
    echo "<li>Create database named 'decathlon_db'</li>";
    echo "<li>Import the database_setup.sql file</li>";
    echo "</ol>";
}

echo "<p><a href='index.php'>Back to website</a></p>";
?>
