<?php
require_once 'admin_header.php'; // Includes session_start() and admin auth check
require_once '../database_connection.php'; // Contains $conn

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['apply_bulk_action'])) {
    $_SESSION['job_action_message'] = 'Invalid request to process bulk job actions.';
    $_SESSION['job_action_message_type'] = 'error';
    header("Location: manage_jobs.php");
    exit;
}

$bulk_action = $_POST['bulk_action'] ?? null;
$job_ids = $_POST['job_ids'] ?? [];

$valid_actions = ['approve', 'reject', 'delete']; // 'reject' here means reject without specific reason for bulk.

if (empty($job_ids)) {
    $_SESSION['job_action_message'] = 'No jobs selected for bulk action.';
    $_SESSION['job_action_message_type'] = 'warning';
    header("Location: manage_jobs.php");
    exit;
}

if (!$bulk_action || !in_array($bulk_action, $valid_actions)) {
    $_SESSION['job_action_message'] = 'Invalid bulk action selected.';
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

$success_count = 0;
$error_count = 0;
$messages = []; // To store individual results if needed, for now just counts

foreach ($job_ids as $job_id) {
    if (!filter_var($job_id, FILTER_VALIDATE_INT)) {
        $error_count++;
        $messages[] = "Invalid Job ID format: " . htmlspecialchars($job_id);
        continue;
    }

    $job_id = (int)$job_id;

    // Fetch job title and employer_username for notifications before potential deletion/update
    $job_info_stmt = $conn->prepare("SELECT title, employer_username FROM jobs WHERE id = ?");
    $job_title = 'Job ID ' . $job_id;
    $employer_username = null;
    if ($job_info_stmt) {
        $job_info_stmt->bind_param("i", $job_id);
        $job_info_stmt->execute();
        $job_info_result = $job_info_stmt->get_result();
        if ($job_info = $job_info_result->fetch_assoc()) {
            $job_title = $job_info['title'];
            $employer_username = $job_info['employer_username'];
        }
        $job_info_stmt->close();
    }

    switch ($bulk_action) {
        case 'approve':
            $stmt = $conn->prepare("UPDATE jobs SET status = 'active', rejection_reason = NULL, updated_at = NOW() WHERE id = ? AND status = 'pending_approval'");
            if ($stmt) {
                $stmt->bind_param("i", $job_id);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $success_count++;
                    if ($employer_username) {
                        $notif_message = "Your job posting '" . htmlspecialchars($job_title) . "' has been approved.";
                        $insert_notif_stmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, type, job_id) VALUES (?, 'admin', ?, 'job_approved', ?)");
                        if ($insert_notif_stmt) {
                            $insert_notif_stmt->bind_param("ssi", $employer_username, $notif_message, $job_id);
                            $insert_notif_stmt->execute();
                            $insert_notif_stmt->close();
                        } else {
                            $messages[] = "DB error preparing notification for job ID {$job_id}: ".$conn->error;
                            $error_count++;
                        }
                    }
                } else { $error_count++; $messages[] = "Failed to approve job ID {$job_id} (may not be pending or DB error: ".$stmt->error.")"; }
                $stmt->close();
            } else { $error_count++; $messages[] = "DB error preparing to approve job ID {$job_id}: ".$conn->error; }
            break;

        case 'reject': // Reject without specific reason
            $stmt = $conn->prepare("UPDATE jobs SET status = 'rejected', rejection_reason = NULL, updated_at = NOW() WHERE id = ? AND status = 'pending_approval'");
            if ($stmt) {
                $stmt->bind_param("i", $job_id);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $success_count++;
                    if ($employer_username) {
                        $notif_message = "Your job posting '" . htmlspecialchars($job_title) . "' has been rejected.";
                        $insert_notif_stmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, type, job_id) VALUES (?, 'admin', ?, 'job_rejected', ?)");
                        if ($insert_notif_stmt) {
                            $insert_notif_stmt->bind_param("ssi", $employer_username, $notif_message, $job_id);
                            $insert_notif_stmt->execute();
                            $insert_notif_stmt->close();
                        } else {
                            $messages[] = "DB error preparing notification for job ID {$job_id}: ".$conn->error;
                            $error_count++;
                        }
                    }
                } else { $error_count++; $messages[] = "Failed to reject job ID {$job_id} (may not be pending or DB error: ".$stmt->error.")"; }
                $stmt->close();
            } else { $error_count++; $messages[] = "DB error preparing to reject job ID {$job_id}: ".$conn->error; }
            break;

        case 'delete':
            $conn->begin_transaction();
            try {
                $stmt_app = $conn->prepare("DELETE FROM job_applications WHERE job_id = ?");
                if (!$stmt_app) throw new Exception("Error preparing to delete applications: " . $conn->error);
                $stmt_app->bind_param("i", $job_id);
                $stmt_app->execute(); // Fine if no applications exist
                $stmt_app->close();

                $stmt_job = $conn->prepare("DELETE FROM jobs WHERE id = ?");
                if (!$stmt_job) throw new Exception("Error preparing to delete job: " . $conn->error);
                $stmt_job->bind_param("i", $job_id);
                if ($stmt_job->execute() && $stmt_job->affected_rows > 0) {
                    $success_count++;
                    $conn->commit();
                    // No notification for deleted job to employer usually
                } else {
                    throw new Exception("Job ID {$job_id} not found or already deleted. No changes made for this job.");
                }
                $stmt_job->close();
            } catch (Exception $e) {
                $conn->rollback();
                $error_count++;
                $messages[] = "Error deleting job ID {$job_id}: " . $e->getMessage();
            }
            break;
    }
}

$final_message = "Bulk action '" . htmlspecialchars($bulk_action) . "' processed. Success: {$success_count}. Errors/Skipped: {$error_count}.";
if ($error_count > 0 && !empty($messages)) {
    $final_message .= " Details: " . implode("; ", array_map('htmlspecialchars', $messages)); // Show some error details
}

$_SESSION['job_action_message'] = $final_message;
$_SESSION['job_action_message_type'] = ($error_count > 0 && $success_count === 0) ? 'error' : (($error_count > 0) ? 'warning' : 'success');

$conn->close();
header("Location: manage_jobs.php");
exit;
?> 