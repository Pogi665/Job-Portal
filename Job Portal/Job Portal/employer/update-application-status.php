<?php
require_once '../includes/header.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !hasRole('employer')) {
    redirect('../login.php', 'You must be logged in as an employer to access this page', 'error');
}

$employerId = $_SESSION['user_id'];

// Check if necessary parameters are provided
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    redirect('applications.php', 'Missing required parameters', 'error');
}

$applicationId = (int)$_GET['id'];
$newStatus = cleanInput($_GET['status']);

// Validate status
$allowedStatuses = ['Pending', 'Reviewed', 'Shortlisted', 'Interview', 'Rejected', 'Hired'];
if (!in_array($newStatus, $allowedStatuses)) {
    redirect('applications.php', 'Invalid status', 'error');
}

// Check if application exists and belongs to this employer's company
$applicationQuery = "SELECT a.*, j.company_id 
                    FROM applications a
                    JOIN jobs j ON a.job_id = j.id
                    WHERE a.id = ?";
$stmt = $conn->prepare($applicationQuery);
$stmt->bind_param("i", $applicationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('applications.php', 'Application not found', 'error');
}

$application = $result->fetch_assoc();

// Verify that the application belongs to a job from this employer's company
$companyQuery = "SELECT id FROM companies WHERE id = ? AND employer_id = ?";
$stmt = $conn->prepare($companyQuery);
$stmt->bind_param("ii", $application['company_id'], $employerId);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    redirect('applications.php', 'You do not have permission to update this application', 'error');
}

// Update application status
$updateQuery = "UPDATE applications SET status = ? WHERE id = ?";
$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("si", $newStatus, $applicationId);

if ($stmt->execute()) {
    // Determine where to redirect - either back to the application view or to the applications list
    $redirectUrl = isset($_GET['return_to']) && $_GET['return_to'] === 'view' 
                 ? "view-application.php?id={$applicationId}" 
                 : "applications.php";
    
    // Success message based on the status
    $statusMessages = [
        'Pending' => 'Application marked as pending',
        'Reviewed' => 'Application marked as reviewed',
        'Shortlisted' => 'Application shortlisted successfully',
        'Interview' => 'Application moved to interview stage',
        'Rejected' => 'Application rejected',
        'Hired' => 'Candidate marked as hired'
    ];
    
    $message = $statusMessages[$newStatus] ?? 'Application status updated successfully';
    redirect($redirectUrl, $message, 'success');
} else {
    redirect('applications.php', 'Error updating application status: ' . $conn->error, 'error');
}
?>
