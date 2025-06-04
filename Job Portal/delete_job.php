<?php
session_start();

// TODO: Implement CSRF token check here for enhanced security.
// Example: if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) { /* handle error */ }

if (!isset($_SESSION["username"])) {
    // If not logged in, redirect to login page. 
    // No need to set a session message here as login page usually handles this.
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    error_log("Connection failed in delete_job.php: " . $conn->connect_error);
    $_SESSION['message'] = "Database connection error. Please try again later.";
    $_SESSION['message_type'] = "error";
    header("Location: dashboard.php"); // Or jobs.php, depending on desired flow
    exit();
}

$current_username = $_SESSION["username"];
// Assuming role is stored in session, and it's been checked for 'job_employer' before reaching delete link.
// If direct access to this script is possible without prior role check, uncomment and adjust:
/*
$current_user_role = isset($_SESSION["role"]) ? $_SESSION["role"] : '';
if ($current_user_role !== 'job_employer') {
    error_log("User " . $current_username . " (Role: " . $current_user_role . ") attempted to delete a job without 'job_employer' role.");
    $_SESSION['message'] = "You are not authorized to perform this action.";
    $_SESSION['message_type'] = "error";
    header("Location: dashboard.php");
    exit();
}
*/

$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : (isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0);

if ($job_id <= 0) {
    $_SESSION['message'] = "Invalid job identifier provided.";
    $_SESSION['message_type'] = "error";
    header("Location: dashboard.php"); // Or jobs.php
    exit();
}

$job_employer_username = null;

// First, get the employer_username for the job to verify ownership.
$check_stmt = $conn->prepare("SELECT employer_username FROM jobs WHERE id = ?");
if (!$check_stmt) {
    error_log("Prepare failed (check job ownership) in delete_job.php: " . $conn->error);
    $_SESSION['message'] = "Database error. Could not verify job ownership.";
    $_SESSION['message_type'] = "error";
    header("Location: dashboard.php");
    exit();
}

$check_stmt->bind_param("i", $job_id);
if (!$check_stmt->execute()) {
    error_log("Execute failed (check job ownership) in delete_job.php: " . $check_stmt->error);
    $_SESSION['message'] = "Database error. Failed to verify job ownership execution.";
    $_SESSION['message_type'] = "error";
    $check_stmt->close();
    $conn->close();
    header("Location: dashboard.php");
    exit();
}

$result = $check_stmt->get_result();
if ($result->num_rows === 1) {
    $job = $result->fetch_assoc();
    $job_employer_username = $job['employer_username'];
} else {
    $_SESSION['message'] = "Job not found or already deleted.";
    $_SESSION['message_type'] = "error";
    $check_stmt->close();
    $conn->close();
    header("Location: dashboard.php"); // Or jobs.php
    exit();
}
$check_stmt->close();

// Verify if the current user is the employer of the job post
if ($job_employer_username !== $current_username) {
    error_log("User " . $current_username . " attempted to delete job ID: " . $job_id . " owned by " . $job_employer_username . ". Unauthorized.");
    $_SESSION['message'] = "You are not authorized to delete this job posting.";
    $_SESSION['message_type'] = "error";
    $conn->close();
    header("Location: dashboard.php"); // Or jobs.php
    exit();
}

// User is authorized, proceed with deletion
// Consider deleting related data like applications or notifications if necessary (CASCADE or manual)
// For now, just deleting the job entry.

// Start transaction for atomicity (optional but good practice if deleting related data)
// $conn->begin_transaction();

$delete_stmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND employer_username = ?");
if (!$delete_stmt) {
    error_log("Prepare failed (delete job) in delete_job.php: " . $conn->error);
    $_SESSION['message'] = "Database error. Could not prepare job deletion.";
    $_SESSION['message_type'] = "error";
    // $conn->rollback(); // if transaction started
    $conn->close();
    header("Location: dashboard.php");
    exit();
}

$delete_stmt->bind_param("is", $job_id, $current_username);
if (!$delete_stmt->execute()) {
    error_log("Execute failed (delete job) in delete_job.php: " . $delete_stmt->error);
    $_SESSION['message'] = "Failed to delete the job. Please try again.";
    $_SESSION['message_type'] = "error";
    // $conn->rollback(); // if transaction started
    $delete_stmt->close();
    $conn->close();
    header("Location: dashboard.php");
    exit();
}

if ($delete_stmt->affected_rows > 0) {
    // $conn->commit(); // if transaction started
    $_SESSION['message'] = "Job successfully deleted.";
    $_SESSION['message_type'] = "success";
} else {
    // $conn->rollback(); // if transaction started
    error_log("Job ID: " . $job_id . " for employer: " . $current_username . " was not deleted. Affected rows: 0 (possibly already deleted or wrong owner).");
    $_SESSION['message'] = "Could not delete the job. It might have been already deleted or an issue occurred.";
    $_SESSION['message_type'] = "error";
}

$delete_stmt->close();
$conn->close();
header("Location: dashboard.php"); // Or jobs.php, consistently redirecting
exit();

?>
