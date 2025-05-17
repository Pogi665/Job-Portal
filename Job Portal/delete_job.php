<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_SESSION["username"];
$role = $_SESSION["role"]; // Assumes role is stored in session

// Ensure job_id is passed in URL and it's a valid integer
if (isset($_GET['job_id']) && is_numeric($_GET['job_id'])) {
    $job_id = $_GET['job_id'];

    // Only allow deletion if the user is the employer of the job post
    $checkQuery = "SELECT employer FROM jobs WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $job = $result->fetch_assoc();
        if ($job['employer'] === $username) {
            // Delete the job post
            $deleteQuery = "DELETE FROM jobs WHERE id = ?";
            $deleteStmt = $conn->prepare($deleteQuery);
            if (!$deleteStmt) {
                die("Error preparing delete statement: " . $conn->error);
            }
            $deleteStmt->bind_param("i", $job_id);
            $deleteStmt->execute();
            
            if ($deleteStmt->affected_rows > 0) {
                // Redirect back to the job listing page after successful deletion
                header("Location: jobs.php?msg=Job deleted successfully.");
                exit();
            } else {
                // Debugging: Output the error message if delete didn't work
                echo "Error deleting the job. Affected rows: " . $deleteStmt->affected_rows;
            }
        } else {
            echo "You are not authorized to delete this job.";
        }
    } else {
        echo "Job not found.";
    }
} else {
    echo "Invalid job ID.";
}
?>
