<?php
require_once 'admin_header.php'; // Includes session_start() and admin auth check
require_once '../database_connection.php';

$job_id_to_delete = $_GET['id'] ?? null;

if (!$job_id_to_delete || !filter_var($job_id_to_delete, FILTER_VALIDATE_INT)) {
    $_SESSION['job_action_message'] = 'Invalid job ID provided for deletion.';
    $_SESSION['job_action_message_type'] = 'error';
    header("Location: manage_jobs.php");
    exit;
}

if ($conn->connect_error) {
    $_SESSION['job_action_message'] = "Database connection failed: " . $conn->connect_error;
    $_SESSION['job_action_message_type'] = 'error';
    header("Location: manage_jobs.php");
    exit;
}

// Start a transaction to ensure atomicity
$conn->begin_transaction();

try {
    // Step 1: Delete associated job applications
    $stmt_delete_apps = $conn->prepare("DELETE FROM job_applications WHERE job_id = ?");
    if (!$stmt_delete_apps) {
        throw new Exception("Error preparing to delete job applications: " . $conn->error);
    }
    $stmt_delete_apps->bind_param("i", $job_id_to_delete);
    if (!$stmt_delete_apps->execute()) {
        throw new Exception("Error deleting job applications: " . $stmt_delete_apps->error);
    }
    // We don't strictly need to check affected_rows for applications, 
    // as a job might not have any. Just proceed if execute was successful.
    $stmt_delete_apps->close();

    // Note: Consider other related data if necessary, e.g., 
    // - Messages linked to this job_id
    // - Notifications linked to this job_id
    // For now, we are only handling job_applications directly.

    // Step 2: Delete the job posting itself
    $stmt_delete_job = $conn->prepare("DELETE FROM jobs WHERE id = ?");
    if (!$stmt_delete_job) {
        throw new Exception("Error preparing to delete job: " . $conn->error);
    }
    $stmt_delete_job->bind_param("i", $job_id_to_delete);
    if ($stmt_delete_job->execute()) {
        if ($stmt_delete_job->affected_rows > 0) {
            $_SESSION['job_action_message'] = "Job posting (ID: " . htmlspecialchars($job_id_to_delete) . ") and its applications deleted successfully.";
            $_SESSION['job_action_message_type'] = 'success';
            $conn->commit(); // All good, commit the transaction
        } else {
            $_SESSION['job_action_message'] = "Job posting not found or already deleted.";
            $_SESSION['job_action_message_type'] = 'error';
            $conn->rollback(); // Rollback as the main entity wasn't found/deleted
        }
    } else {
        throw new Exception("Error deleting job posting: " . $stmt_delete_job->error);
    }
    $stmt_delete_job->close();

} catch (Exception $e) {
    $conn->rollback(); // Rollback on any error during the transaction
    $_SESSION['job_action_message'] = "An error occurred: " . $e->getMessage();
    $_SESSION['job_action_message_type'] = 'error';
}

$conn->close();

header("Location: manage_jobs.php");
exit;

// No HTML output from this script
?> 