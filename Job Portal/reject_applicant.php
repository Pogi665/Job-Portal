<?php
session_start();

// TODO: Implement CSRF token check here for enhanced security.
// e.g., if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) { /* handle error */ }

if (!isset($_SESSION["username"]) || !isset($_SESSION["role"])) {
    // No session message needed, login page handles unauthorized access.
    header("Location: login.php");
    exit();
}

if ($_SESSION["role"] !== 'job_employer') {
    $_SESSION['message'] = "You are not authorized to perform this action.";
    $_SESSION['message_type'] = "error";
    header("Location: dashboard.php"); // Redirect to a general page
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    error_log("Connection failed in reject_applicant.php: " . $conn->connect_error);
    $_SESSION['message'] = "Database connection error. Please try again later.";
    $_SESSION['message_type'] = "error";
    // Fallback redirect, job_id might not be available or trustworthy from POST yet
    header("Location: dashboard.php"); 
    exit();
}

$current_employer_username = $_SESSION["username"];
$application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0; // Expect job_id from POST as well
$applicant_username = isset($_POST['applicant_username']) ? trim($_POST['applicant_username']) : '';

// Determine redirect URL early, default to dashboard if job_id is invalid
$redirect_url = "dashboard.php";
if ($job_id > 0) {
    // If job_id is valid, prefer redirecting to the specific job posting or applicant view
    // For consistency with view_applicant_profile.php, let's redirect there.
    // It might be more user-friendly to redirect to view_job_posting.php?job_id=$job_id to see all applicants.
    // For now, matching the likely origin of the request from view_applicant_profile.php:
    $redirect_url = "view_applicant_profile.php?job_id=" . $job_id . "&applicant=" . urlencode($applicant_username);
    // Or, redirect to the list of applicants for that job:
    // $redirect_url = "view_job_posting.php?job_id=" . $job_id;
}

if ($application_id <= 0 || $job_id <= 0 || empty($applicant_username)) {
    $_SESSION['message'] = "Invalid application, job ID, or applicant details provided for rejection.";
    $_SESSION['message_type'] = "error";
    header("Location: " . $redirect_url);
    exit();
}

$job_name_for_message = "the job"; 

$conn->begin_transaction();

try {
    // 1. Verify employer owns this job AND get job title
    // Using employer_username for consistency
    $stmtCheckJobOwner = $conn->prepare("SELECT title FROM jobs WHERE id = ? AND employer_username = ?");
    if (!$stmtCheckJobOwner) throw new Exception("Prepare failed (check job owner): " . $conn->error);
    
    $stmtCheckJobOwner->bind_param("is", $job_id, $current_employer_username);
    if (!$stmtCheckJobOwner->execute()) throw new Exception("Execute failed (check job owner): " . $stmtCheckJobOwner->error);
    
    $jobOwnerResult = $stmtCheckJobOwner->get_result();
    if ($jobOwnerResult->num_rows === 0) {
        throw new Exception("Authorization failed: You do not own this job (ID: $job_id) or it does not exist.");
    }
    $job_data = $jobOwnerResult->fetch_assoc();
    $job_name_for_message = $job_data['title'] ?? "the job";
    $stmtCheckJobOwner->close();

    // 2. Check if the specific PENDING application exists (using application_id for precision)
    // Using applicant_username as part of the check is also good for belt-and-suspenders
    $stmtCheckApplication = $conn->prepare("SELECT id FROM job_applications WHERE id = ? AND job_id = ? AND applicant_username = ? AND status = 'pending'");
    if (!$stmtCheckApplication) throw new Exception("Prepare failed (check pending application): " . $conn->error);

    $stmtCheckApplication->bind_param("iis", $application_id, $job_id, $applicant_username);
    if (!$stmtCheckApplication->execute()) throw new Exception("Execute failed (check pending application): " . $stmtCheckApplication->error);

    $applicationResult = $stmtCheckApplication->get_result();
    if ($applicationResult->num_rows === 0) {
        $_SESSION['message'] = "Application not found, not pending, or does not match provided details.";
        $_SESSION['message_type'] = "warning"; // Use warning as it might have been processed already
        $conn->rollback(); // No changes made, but good practice in catch if any were possible
        $stmtCheckApplication->close();
        header("Location: " . $redirect_url);
        exit();
    }
    $stmtCheckApplication->close();

    // 3. Update application status to 'rejected' using application_id
    $stmtUpdateStatus = $conn->prepare("UPDATE job_applications SET status = 'rejected' WHERE id = ? AND job_id = ? AND applicant_username = ? AND status = 'pending'");
    if (!$stmtUpdateStatus) throw new Exception("Prepare failed (update app status): " . $conn->error);
    
    $stmtUpdateStatus->bind_param("iis", $application_id, $job_id, $applicant_username);
    if (!$stmtUpdateStatus->execute()) throw new Exception("Execute failed (update app status): " . $stmtUpdateStatus->error);
    
    if ($stmtUpdateStatus->affected_rows === 0) {
        // This might happen if the status was changed by another process between check and update.
        throw new Exception("Application status could not be updated. It might have been processed by another action.");
    }
    $stmtUpdateStatus->close();

    // 4. Insert notification for the applicant
    $notif_message = "We regret to inform you that your application for the position of '" . htmlspecialchars($job_name_for_message) . "' has not been successful at this time. We wish you the best in your job search.";
    $notif_type = "application_rejected";
    // Assuming recipient_username in notifications table is the applicant's username
    $stmtInsertNotif = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, type, job_id, application_id, created_at, is_read) VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)");
    if (!$stmtInsertNotif) throw new Exception("Prepare failed (insert notification): " . $conn->error);
    
    // Added application_id to notification for more context if needed
    $stmtInsertNotif->bind_param("ssssii", $applicant_username, $current_employer_username, $notif_message, $notif_type, $job_id, $application_id);
    if (!$stmtInsertNotif->execute()) throw new Exception("Execute failed (insert notification): " . $stmtInsertNotif->error);
    $stmtInsertNotif->close();

    $conn->commit();
    $_SESSION['message'] = "Applicant " . htmlspecialchars($applicant_username) . " has been rejected for the job '" . htmlspecialchars($job_name_for_message) . "'.";
    $_SESSION['message_type'] = "success";

} catch (Exception $e) {
    $conn->rollback();
    error_log("Applicant rejection failed (Job ID: $job_id, App ID: $application_id, Applicant: $applicant_username): " . $e->getMessage());
    $_SESSION['message'] = "Applicant rejection process failed: " . htmlspecialchars($e->getMessage());
    $_SESSION['message_type'] = "error";
    
    // If authorization specifically failed, redirect to dashboard to avoid info leakage on specific job
    if (str_contains($e->getMessage(), "Authorization failed")) {
        $redirect_url = "dashboard.php";
    }
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

header("Location: " . $redirect_url);
exit();
?> 