<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Since this is an AJAX endpoint, we'll return JSON responses
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to save jobs', 'redirect' => 'login.php']);
    exit;
}

// Check if user is a job seeker
if (!hasRole('job_seeker')) {
    echo json_encode(['success' => false, 'message' => 'Only job seekers can save jobs']);
    exit;
}

// Check if job_id was provided
if (!isset($_POST['job_id'])) {
    echo json_encode(['success' => false, 'message' => 'No job specified']);
    exit;
}

$userId = $_SESSION['user_id'];
$jobId = (int)$_POST['job_id'];

// Check if the job exists
$jobQuery = "SELECT id FROM jobs WHERE id = ? AND status = 'Active'";
$stmt = $conn->prepare($jobQuery);
$stmt->bind_param("i", $jobId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Job not found or no longer active']);
    exit;
}

// Check if job is already saved by this user
$checkQuery = "SELECT id FROM saved_jobs WHERE user_id = ? AND job_id = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("ii", $userId, $jobId);
$stmt->execute();
$result = $stmt->get_result();

// If job is already saved, we'll unsave it (toggle functionality)
if ($result->num_rows > 0) {
    $deleteQuery = "DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $userId, $jobId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Job removed from saved jobs', 'saved' => false]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error removing job from saved jobs']);
    }
    exit;
}

// Otherwise, save the job
$insertQuery = "INSERT INTO saved_jobs (user_id, job_id) VALUES (?, ?)";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("ii", $userId, $jobId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Job saved successfully', 'saved' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error saving job']);
}
?>
