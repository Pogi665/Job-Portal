<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'job_portal');

// Environment check (set this to 'production' in your live environment)
$environment = 'development'; // Options: 'development', 'production'

// Configure error reporting based on environment
if ($environment === 'development') {
    // Show all errors in development
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // Hide errors in production, but log them
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

try {
    // Check if MySQL extension is loaded
    if (!extension_loaded('mysqli')) {
        throw new Exception("MySQLi extension is not loaded. Please check your PHP configuration.");
    }
    
    // Create connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection to MySQL server failed: " . $conn->connect_error);
    }
    
    // Test query to verify connection is working fully
    $test = $conn->query("SELECT 1");
    if (!$test) {
        throw new Exception("Failed to execute test query: " . $conn->error);
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
    
    // Optional: Set timezone
    date_default_timezone_set('UTC');
    
} catch (Exception $e) {
    // Different error handling based on environment
    if ($environment === 'development') {
        // Display a comprehensive error with troubleshooting steps in development
        echo "<div style='color:red; background-color:#FEE; padding:15px; margin:15px; border:1px solid #FAA; border-radius:5px;'>";
        echo "<h2>Database Connection Error</h2>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<h3>Troubleshooting Steps:</h3>";
        echo "<ol>";
        echo "<li>Make sure XAMPP's MySQL service is running (check XAMPP Control Panel)</li>";
        echo "<li>Verify database credentials in config/db.php</li>";
        echo "<li>Check if the 'job_portal' database exists in phpMyAdmin</li>";
        echo "<li>Verify the MySQL port is correct (default is 3306)</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        // Simple generic error in production
        echo "<div style='padding:15px;'>A database error occurred. Please try again later or contact support.</div>";
        // Log the actual error
        error_log("Database Error: " . $e->getMessage());
    }
    die();
}
?>
