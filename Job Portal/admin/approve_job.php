<?php
require_once 'admin_header.php'; // Includes session_start() and admin auth check
require_once '../database_connection.php';

$job_id_to_approve = $_GET['id'] ?? null;

if (!$job_id_to_approve || !filter_var($job_id_to_approve, FILTER_VALIDATE_INT)) {
    $_SESSION['job_action_message'] = 'Invalid job ID provided for approval.';
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

// Update the job status to 'active'
$new_status = 'active';
$stmt = $conn->prepare("UPDATE jobs SET status = ? WHERE id = ? AND status = 'pending_approval'");

if (!$stmt) {
    $_SESSION['job_action_message'] = "Error preparing statement: " . $conn->error;
    $_SESSION['job_action_message_type'] = 'error';
} else {
    $stmt->bind_param("si", $new_status, $job_id_to_approve);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['job_action_message'] = "Job ID: " . htmlspecialchars($job_id_to_approve) . " has been approved and set to active.";
            $_SESSION['job_action_message_type'] = 'success';

            // Fetch employer_username and job title to create notification
            $job_info_stmt = $conn->prepare("SELECT employer_username, title FROM jobs WHERE id = ?");
            if ($job_info_stmt) {
                $job_info_stmt->bind_param("i", $job_id_to_approve);
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
                            $employer_user_id = $user['id'];
                            $message = "Your job posting '" . htmlspecialchars($job_title) . "' has been approved.";
                            $type = "job_approved";
                            $admin_username = $_SESSION['username'] ?? 'admin';

                            $insert_notification_stmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, type, job_id) VALUES (?, ?, ?, ?, ?)");
                            if ($insert_notification_stmt) {
                                $insert_notification_stmt->bind_param("ssssi", $employer_username, $admin_username, $message, $type, $job_id_to_approve);
                                if (!$insert_notification_stmt->execute()) {
                                    error_log("Failed to insert approval notification for job ID {$job_id_to_approve}: " . $insert_notification_stmt->error);
                                }
                                $insert_notification_stmt->close();
                            } else {
                                error_log("Failed to prepare notification insert statement: " . $conn->error);
                            }
                        }
                        $user_stmt->close();
                    } else {
                         error_log("Failed to prepare user select statement: " . $conn->error);
                    }
                }
                $job_info_stmt->close();
            } else {
                error_log("Failed to prepare job info select statement: " . $conn->error);
            }

        } else {
            // This could happen if the job was not in 'pending_approval' status or ID not found
            $_SESSION['job_action_message'] = "Job ID: " . htmlspecialchars($job_id_to_approve) . " could not be approved (it might not have been pending or was already processed).";
            $_SESSION['job_action_message_type'] = 'warning'; // Use warning as it might not be a system error
        }
    } else {
        $_SESSION['job_action_message'] = "Error approving job: " . $stmt->error;
        $_SESSION['job_action_message_type'] = 'error';
    }
    $stmt->close();
}

$conn->close();

header("Location: manage_jobs.php");
exit;

// No HTML output from this script
?> 