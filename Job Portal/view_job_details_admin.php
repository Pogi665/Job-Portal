<?php
session_start();
require_once 'database_connection.php'; // Ensure this path is correct relative to where this file will be.
require_once 'admin_view_header.php'; // Use the new simplified header

// Authentication and Authorization
if (!isset($_SESSION["username"]) || !isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    // Not logged in
    $_SESSION['message'] = "Authentication required. Please log in.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php"); // Redirect to a generic login page
    exit();
}

if ($_SESSION["role"] !== 'admin') {
    // Not an admin
    $_SESSION['message'] = "You are not authorized to access this page.";
    $_SESSION['message_type'] = "error";
    // Redirect to a relevant page, perhaps admin login or a general dashboard if they have one
    header("Location: dashboard.php"); 
    exit();
}

$job = null;
$page_message = '';
$page_message_type = '';

// Display session messages if any (e.g., from previous unauthorized attempts)
if (isset($_SESSION['message'])) {
    $page_message = $_SESSION['message'];
    $page_message_type = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

$job_id_get = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

if ($job_id_get <= 0) {
    $page_message = "Invalid job ID specified.";
    $page_message_type = "error";
    // No job to display, so we'll show this message in the HTML
} else {
    if ($conn->connect_error) {
        error_log("Connection failed in view_job_details_admin.php: " . $conn->connect_error);
        $page_message = "Database connection failed. Please try again later.";
        $page_message_type = "error";
    } else {
        $stmtJobQuery = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
        if (!$stmtJobQuery) {
            error_log("Prepare failed for jobQuery in view_job_details_admin.php: " . $conn->error);
            $page_message = "Error loading job details (DBP_ADMIN_VIEW).";
            $page_message_type = "error";
        } else {
            $stmtJobQuery->bind_param("i", $job_id_get);
            if (!$stmtJobQuery->execute()) {
                error_log("Execute failed for jobQuery in view_job_details_admin.php: " . $stmtJobQuery->error);
                $page_message = "Error loading job details (DBE_ADMIN_VIEW).";
                $page_message_type = "error";
            } else {
                $jobResult = $stmtJobQuery->get_result();
                if ($jobResult->num_rows > 0) {
                    $job = $jobResult->fetch_assoc();
                } else {
                    $page_message = "Job not found.";
                    $page_message_type = "warning";
                }
            }
            $stmtJobQuery->close();
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $job ? 'Admin View: ' . htmlspecialchars($job['title']) : 'Job Details'; ?> - CareerLynk</title>
    <!-- Assuming Tailwind CSS and Font Awesome are included via header.php or linked directly -->
    <link rel="stylesheet" href="style.css"> <!-- Link to your main stylesheet -->
</head>
<body class="bg-gray-100 font-inter">

<main class="container mx-auto mt-8 mb-16 px-4">

    <?php if (!empty($page_message)): ?>
        <div class="mb-6 p-4 rounded-md <?php echo ($page_message_type === 'error' ? 'bg-red-100 border-l-4 border-red-500 text-red-700' : ($page_message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : ($page_message_type === 'warning' ? 'bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700' : 'bg-blue-100 border-l-4 border-blue-500 text-blue-700'))); ?>" role="alert">
            <p class="font-bold"><?php echo htmlspecialchars(ucfirst($page_message_type)); ?></p>
            <p><?php echo htmlspecialchars($page_message); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($job): ?>
        <div class="bg-white shadow-xl rounded-lg p-6 md:p-8">
            <div class="mb-6 pb-6 border-b border-gray-200">
                <h1 class="text-3xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($job['title']); ?></h1>
                <p class="text-md text-gray-600">
                    <i class="fas fa-building mr-2 text-gray-500"></i><?php echo htmlspecialchars($job['company'] ?? 'N/A'); ?>
                    <span class="mx-2 text-gray-400">|</span>
                    <i class="fas fa-map-marker-alt mr-2 text-gray-500"></i><?php echo htmlspecialchars($job['location'] ?? 'N/A'); ?>
                </p>
            </div>

            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-2">Job Description</h2>
                <div class="prose max-w-full text-gray-600 leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-1"><i class="fas fa-briefcase mr-2 text-gray-500"></i>Job Type</h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($job['job_type'] ?? 'Not specified'); ?></p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-1"><i class="fas fa-money-bill-wave mr-2 text-gray-500"></i>Salary</h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($job['salary'] ?? 'Not specified'); ?></p>
                </div>
                 <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-1"><i class="fas fa-user-tie mr-2 text-gray-500"></i>Employer Username</h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($job['employer_username'] ?? 'N/A'); ?></p>
                </div>
                 <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-1"><i class="fas fa-check-circle mr-2 text-gray-500"></i>Status</h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $job['status'] ?? 'N/A'))); ?></p>
                </div>
            </div>
            
            <div class="mt-6 pt-4 border-t border-gray-200">
                 <p class="text-sm text-gray-500">Posted on: <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($job['timestamp']))); ?></p>
                 <?php if (isset($job['updated_at'])): ?>
                    <p class="text-sm text-gray-500">Last updated: <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($job['updated_at']))); ?></p>
                 <?php endif; ?>
            </div>

        </div>
    <?php elseif (empty($page_message)) : // Only show this if no other message (like invalid ID or DB error) is already set ?>
        <div class="bg-white shadow-xl rounded-lg p-8 text-center">
            <i class="fas fa-exclamation-triangle fa-4x text-yellow-500 mb-4"></i>
            <h1 class="text-2xl font-bold text-gray-700 mb-3">Job Information Unavailable</h1>
            <p class="text-gray-600">The requested job details could not be displayed at this time.</p>
        </div>
    <?php endif; ?>
    
</main>

<?php include 'footer.php'; // Assuming a standard footer ?>

</body>
</html> 