<?php
// Database connection details
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "job_portal";

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    // Log error for debugging, but don't die or output HTML here for general include usage.
    // Pages including this script should handle connection errors appropriately.
    error_log("Database connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
    // Optionally, you could throw an exception or set a global error flag.
    // For now, pages will check $conn->connect_error themselves or rely on subsequent query failures.
}
?> 