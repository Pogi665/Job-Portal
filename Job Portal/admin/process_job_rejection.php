<?php
require_once 'admin_header.php'; // Includes session_start() and admin auth check
require_once '../database_connection.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['reject_job_submit'])) {
    $_SESSION['job_action_message'] = 'Invalid request to process job rejection.';
    $_SESSION['job_action_message_type'] = 'error';
    header("Location: manage_jobs.php");
    exit;
}

$job_id_to_reject = $_POST['job_id'] ?? null;
$rejection_reason = trim($_POST['rejection_reason'] ?? '');

if (!$job_id_to_reject || !filter_var($job_id_to_reject, FILTER_VALIDATE_INT)) {
    $_SESSION['job_action_message'] = 'Invalid job ID provided for rejection.';
    $_SESSION['job_action_message_type'] = 'error';
    header("Location: manage_jobs.php");
    exit;
}

if ($conn->connect_error) {
    // Log this, as it's a server-side issue
    error_log("Database connection failed in process_job_rejection.php: " . $conn->connect_error);
    $_SESSION['job_action_message'] = "A critical database error occurred. Please try again later.";
    $_SESSION['job_action_message_type'] = 'error';
    header("Location: manage_jobs.php");
    exit;
}

// Update the job status to 'rejected' and add rejection reason
$new_status = 'rejected';

// Prepare statement to prevent SQL injection
$stmt = $conn->prepare("UPDATE jobs SET status = ?, rejection_reason = ? WHERE id = ? AND status = 'pending_approval'");

if (!$stmt) {
    error_log("Error preparing statement in process_job_rejection.php: " . $conn->error);
    $_SESSION['job_action_message'] = "Error preparing to update job: " . $conn->error; // Displaying conn error might be too much for user, consider generic message
    $_SESSION['job_action_message_type'] = 'error';
} else {
    $stmt->bind_param("ssi", $new_status, $rejection_reason, $job_id_to_reject);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['job_action_message'] = "Job ID: " . htmlspecialchars($job_id_to_reject) . " has been rejected.";
            if (!empty($rejection_reason)) {
                $_SESSION['job_action_message'] .= " Reason provided.";
            }
            $_SESSION['job_action_message_type'] = 'success';

            // Fetch employer_username and job title to create notification
            $job_info_stmt = $conn->prepare("SELECT employer_username, title FROM jobs WHERE id = ?");
            if ($job_info_stmt) {
                $job_info_stmt->bind_param("i", $job_id_to_reject);
                $job_info_stmt->execute();
                $job_info_result = $job_info_stmt->get_result();
                if ($job_info = $job_info_result->fetch_assoc()) {
                    $employer_username = $job_info['employer_username'];
                    $job_title = $job_info['title'];

                    // Fetch employer user_id
                    $user_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                    if ($user_stmt) {
                        $user_stmt->bind_param("s", $employer_username);
                        $user_stmt->execute();
                        $user_result = $user_stmt->get_result();
                        if ($user = $user_result->fetch_assoc()) {
                            $employer_user_id = $user['id']; // We have employer's user ID if needed elsewhere, but notifications table uses username
                            $message = "Your job posting '" . htmlspecialchars($job_title) . "' has been rejected.";
                            if (!empty($rejection_reason)) {
                                $message .= " Reason: " . htmlspecialchars($rejection_reason);
                            }
                            $type = "job_rejected";
                            // Use recipient_username and sender_username as per notifications table schema
                            // Assuming admin's username is in $_SESSION['username'] from admin_header.php
                            $admin_username = $_SESSION['username'] ?? 'admin'; // Fallback if session variable not set

                            // Corrected INSERT statement for notifications table
                            $insert_notification_stmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, type, job_id) VALUES (?, ?, ?, ?, ?)");
                            if ($insert_notification_stmt) {
                                // Bind parameters: recipient_username, sender_username, message, type, job_id
                                $insert_notification_stmt->bind_param("ssssi", $employer_username, $admin_username, $message, $type, $job_id_to_reject);
                                if (!$insert_notification_stmt->execute()) {
                                    error_log("Failed to insert rejection notification for job ID {$job_id_to_reject}: " . $insert_notification_stmt->error);
                                }
                                $insert_notification_stmt->close();
                            } else {
                                error_log("Failed to prepare notification insert statement for rejection: " . $conn->error);
                            }
                        }
                        $user_stmt->close();
                    } else {
                        error_log("Failed to prepare user select statement for rejection notification: " . $conn->error);
                    }
                }
                $job_info_stmt->close();
            } else {
                error_log("Failed to prepare job info select statement for rejection notification: " . $conn->error);
            }
        } else {
            // Check current status if no rows affected
            $check_stmt = $conn->prepare("SELECT status FROM jobs WHERE id = ?");
            $check_stmt->bind_param("i", $job_id_to_reject);
            $check_stmt->execute();
            $current_status_res = $check_stmt->get_result();
            $current_status_row = $current_status_res->fetch_assoc();
            $check_stmt->close();

            if ($current_status_row && $current_status_row['status'] !== 'pending_approval') {
                $_SESSION['job_action_message'] = "Job ID: " . htmlspecialchars($job_id_to_reject) . " could not be rejected. It was not pending approval (current status: " . htmlspecialchars($current_status_row['status']) . ").";
            } else if (!$current_status_row) {
                 $_SESSION['job_action_message'] = "Job ID: " . htmlspecialchars($job_id_to_reject) . " not found.";
            } else {
                $_SESSION['job_action_message'] = "Job ID: " . htmlspecialchars($job_id_to_reject) . " could not be rejected (it might not have been pending or was already processed). No changes made.";
            }
            $_SESSION['job_action_message_type'] = 'warning';
        }
    } else {
        error_log("Error executing job rejection in process_job_rejection.php: " . $stmt->error);
        $_SESSION['job_action_message'] = "Error rejecting job: " . $stmt->error; // User might not need to see $stmt->error
        $_SESSION['job_action_message_type'] = 'error';
    }
    $stmt->close();
}

$conn->close();

header("Location: manage_jobs.php");
exit;

// No HTML output from this script
?> 