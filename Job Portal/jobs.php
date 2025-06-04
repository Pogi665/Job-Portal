<?php
session_start();

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) { 
    error_log("Jobs.php DB Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
    die("A critical database connection error occurred. Please try again later or contact support.");
}

$is_logged_in = isset($_SESSION["username"]) && isset($_SESSION["user_id"]);
$current_username = $is_logged_in ? $_SESSION["username"] : null;
$current_user_role = $is_logged_in ? $_SESSION["role"] : null;

$error_message = '';
$success_message = '';

// Display messages from GET parameters (e.g., from post_job, delete_job, edit_job)
if (isset($_GET['error'])) {
    // Simple display for now, can be expanded with a switch for specific messages
    $error_message = htmlspecialchars($_GET['error']); 
}
if (isset($_GET['success'])) {
    $raw_success_message = $_GET['success'];
    if ($raw_success_message === 'job_updated') {
        $success_message = "Job Updated";
    } else {
        $success_message = htmlspecialchars($raw_success_message);
    }
}
if (isset($_GET['notice'])) {
    // Using success_message styling for notices for simplicity, can be different
    $success_message = htmlspecialchars($_GET['notice']); 
}


$jobsResult = null;
$page_title = "Available Jobs";
$show_employer_actions = false;

if ($is_logged_in && $current_user_role === 'job_employer') {
    $page_title = "Your Job Listings";
    $show_employer_actions = true;
    $jobsQuery = "
        SELECT jobs.*, COUNT(job_applications.id) as num_applicants
        FROM jobs 
        LEFT JOIN job_applications ON jobs.id = job_applications.job_id
        WHERE jobs.employer_username = ? 
        GROUP BY jobs.id
        ORDER BY jobs.timestamp DESC
    ";
    // Ensure rejection_reason is selected
    $jobsQuery = str_replace("SELECT jobs.*,", "SELECT jobs.*, jobs.rejection_reason,", $jobsQuery);

    $stmt = $conn->prepare($jobsQuery);
    if ($stmt) {
        $stmt->bind_param("s", $current_username);
        if ($stmt->execute()) {
            $jobsResult = $stmt->get_result();
        } else {
            error_log("Execute failed for employer jobs query: " . $stmt->error);
            $error_message = "Could not retrieve your job listings at this time.";
        }
        $stmt->close();
    } else {
        error_log("Prepare failed for employer jobs query: " . $conn->error);
        $error_message = "An error occurred while preparing to fetch your job listings.";
    }
} elseif ($is_logged_in && $current_user_role === 'job_seeker') {
    $page_title = "Applied Jobs";
    $jobsQuery = "
        SELECT j.* 
        FROM jobs j
        INNER JOIN job_applications ja ON j.id = ja.job_id
        WHERE ja.applicant_username = ?
        ORDER BY ja.application_date DESC";
    $stmt = $conn->prepare($jobsQuery);
    if ($stmt) {
        $stmt->bind_param("s", $current_username);
        if ($stmt->execute()) {
            $jobsResult = $stmt->get_result();
        } else {
            error_log("Execute failed for job seeker applied jobs query: " . $stmt->error);
            $error_message = "Could not retrieve your applied job listings at this time.";
        }
        $stmt->close();
    } else {
        error_log("Prepare failed for job seeker applied jobs query: " . $conn->error);
        $error_message = "An error occurred while preparing to fetch your applied job listings.";
    }
} else {
    $page_title = "Applied Jobs";
    $jobsResult = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> - CareerLynk</title>
</head>
<body class="bg-gray-100">
<?php include 'header.php'; // Ensure header adapts to logged-in state ?>

<main class="container mx-auto mt-8 mb-10 px-4">
    <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center"><?php echo htmlspecialchars($page_title); ?></h1>

    <?php if ($error_message): ?>
        <div class="alert alert-danger max-w-4xl mx-auto" role="alert">
            <?php echo $error_message; // Already htmlspecialchar'd ?>
        </div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="alert alert-success max-w-4xl mx-auto" role="alert">
            <?php echo $success_message; // Already htmlspecialchar'd ?>
        </div>
    <?php endif; ?>

    <?php if ($is_logged_in && $current_user_role === 'job_employer'): ?>
        <div class="text-center mb-6">
            <a href="post_job.php" class="inline-block bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                <i class="fas fa-plus-circle mr-2"></i>Post a New Job
            </a>
        </div>
    <?php endif; ?>

    <?php if ($jobsResult && $jobsResult->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($job = $jobsResult->fetch_assoc()): ?>
                <?php
                    $job_status = $job['status'] ?? 'unknown';
                    $card_style = 'bg-white';
                    $status_display = htmlspecialchars(ucfirst(str_replace('_', ' ', $job_status)));
                    $status_note = '';

                    if ($job_status !== 'active') {
                        // You might want different styling or notes for different non-active statuses
                        $card_style = 'bg-gray-50'; // Slightly different background for non-active
                        if ($job_status === 'pending_approval') {
                            $status_note = 'This job is currently pending review.';
                        } elseif ($job_status === 'rejected' || $job_status === 'expired' || $job_status === 'filled') {
                            $status_note = 'This job is no longer active (' . $status_display . ').';
                        } else {
                            $status_note = 'The status of this job is: ' . $status_display;
                        }
                    }
                ?>
                <div class="<?php echo $card_style; ?> rounded-lg shadow-lg overflow-hidden flex flex-col">
                    <div class="p-6 flex-grow">
                        <h2 class="text-xl font-semibold text-blue-600 mb-2">
                            <?php if ($show_employer_actions): ?>
                                <a href="job_details.php?job_id=<?php echo $job['id']; ?>" class="hover:underline">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            <?php else: ?>
                                <a href="view_job_posting.php?job_id=<?php echo $job['id']; ?>" class="hover:underline">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            <?php endif; ?>
                        </h2>
                        <p class="text-sm text-gray-600 mb-1">
                            <i class="fas fa-building mr-2 text-gray-500"></i><?php echo htmlspecialchars($job['company'] ?? 'N/A'); ?>
                        </p>
                        <p class="text-sm text-gray-600 mb-3">
                            <i class="fas fa-map-marker-alt mr-2 text-gray-500"></i><?php echo htmlspecialchars($job['location']); ?>
                        </p>
                        <p class="text-xs text-gray-500 mb-1">Status: <span class="font-semibold"><?php echo $status_display; ?></span></p>
                        <?php if ($status_note): ?>
                            <p class="text-xs text-orange-600 mb-2"><em><?php echo $status_note; ?></em></p>
                        <?php endif; ?>
                        <?php 
                        // Display rejection reason for employers if the job is rejected and reason exists
                        if ($current_user_role === 'job_employer' && $job_status === 'rejected' && !empty($job['rejection_reason'])):
                        ?>
                            <p class="text-xs text-red-700 mb-2"><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($job['rejection_reason']); ?></p>
                        <?php endif; ?>
                        <p class="text-gray-700 text-sm line-clamp-3 mb-4">
                            <?php echo htmlspecialchars($job['description']); ?>
                        </p>
                    </div>
                    <div class="px-6 pb-4 border-t border-gray-200">
                        <p class="text-xs text-gray-500 mt-3">Posted: <?php echo date("M j, Y", strtotime($job['timestamp'])); ?></p>
                        <?php if ($show_employer_actions): ?>
                            <p class="text-xs text-gray-500">Applicants: <?php echo $job['num_applicants'] ?? '0'; ?></p>
                            <div class="mt-4 flex justify-start space-x-2">
                                <a href="edit_job.php?job_id=<?php echo $job['id']; ?>" class="text-xs bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-3 rounded-full no-underline"><i class="fas fa-edit mr-1"></i>Edit</a>
                                <a href="delete_job.php?job_id=<?php echo $job['id']; ?>" class="text-xs bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-full no-underline" onclick="return confirm('Are you sure you want to delete this job: <?php echo htmlspecialchars(addslashes($job['title'])); ?>?')"><i class="fas fa-trash mr-1"></i>Delete</a>
                                <a href="job_details.php?job_id=<?php echo $job['id']; ?>" class="text-xs bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded-full no-underline"><i class="fas fa-users mr-1"></i>View Applicants</a>
                            </div>
                        <?php else: ?>
                             <div class="mt-4 text-right">
                                <a href="view_job_posting.php?job_id=<?php echo $job['id']; ?>" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg text-sm transition duration-150 ease-in-out">
                                    View Details <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <i class="fas fa-search fa-3x text-gray-400 mb-4"></i>
            <p class="text-xl text-gray-600">
                <?php 
                    if ($is_logged_in && $current_user_role === 'job_employer') {
                        echo "You have not posted any jobs yet.";
                    } elseif ($is_logged_in && $current_user_role === 'job_seeker') {
                        echo "You have not applied to any jobs yet.";
                    } else {
                        echo "Please <a href='login.php' class='text-blue-600 hover:underline'>login</a> to view your applied jobs.";
                    }
                ?>
            </p>
             <?php if ($is_logged_in && $current_user_role === 'job_employer'): ?>
                <a href="post_job.php" class="mt-4 inline-block bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-150 ease-in-out">
                    Post Your First Job
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<?php if(isset($conn)) $conn->close(); ?>
</body>
</html>
