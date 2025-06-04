<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    error_log("Connection failed in view_job_posting.php: " . $conn->connect_error);
    // For a public page, redirect to a generic error or jobs list with a general DB error
    header("Location: jobs.php?error=db_unavailable"); 
    exit();
}

// Input validation for job_id
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($job_id <= 0) {
    header("Location: jobs.php?error=invalid_job_id");
    exit();
}

// Fetch job details
$job = null;
$stmtJob = $conn->prepare("SELECT j.*, u.full_name as employer_fullname FROM jobs j LEFT JOIN users u ON j.employer_username = u.username WHERE j.id = ?"); 
// Assuming 'jobs' table has 'employer_username' as username, and 'users' has 'full_name'
// Add a check for job status if there's an 'is_active' or 'status' column in 'jobs' table, e.g., AND j.status = 'active'

if (!$stmtJob) {
    error_log("Prepare failed for job fetch in view_job_posting.php: " . $conn->error);
    $conn->close();
    header("Location: jobs.php?error=job_fetch_prepare_failed");
    exit();
}

$stmtJob->bind_param("i", $job_id);
if (!$stmtJob->execute()) {
    error_log("Execute failed for job fetch in view_job_posting.php: " . $stmtJob->error);
    $stmtJob->close();
    $conn->close();
    header("Location: jobs.php?error=job_fetch_execute_failed");
    exit();
}

$resultJob = $stmtJob->get_result();
if ($resultJob->num_rows > 0) {
    $job = $resultJob->fetch_assoc();
} else {
    $stmtJob->close();
    $conn->close();
    header("Location: jobs.php?error=job_not_found");
    exit();
}
$stmtJob->close();

// Initialize variables for user-specific actions
$can_apply = false;
$already_applied = false;
$is_job_seeker = false;
$is_logged_in = isset($_SESSION['user_id']);
$apply_button_text = "Apply Now";
$apply_button_link = "#";
$apply_button_disabled = false;
$apply_button_class = "bg-blue-600 hover:bg-blue-700";

if ($is_logged_in) {
    $current_user_role = $_SESSION['role'];
    $current_user_username = $_SESSION['username'];

    if ($current_user_role === 'job_seeker') {
        $is_job_seeker = true;
        // Check if already applied
        $stmtCheckApplication = $conn->prepare("SELECT id FROM job_applications WHERE job_id = ? AND applicant_username = ?");
        if ($stmtCheckApplication) {
            $stmtCheckApplication->bind_param("is", $job_id, $current_user_username);
            if ($stmtCheckApplication->execute()) {
                $resultCheckApp = $stmtCheckApplication->get_result();
                if ($resultCheckApp->num_rows > 0) {
                    $already_applied = true;
                    $apply_button_text = "Already Applied";
                    $apply_button_disabled = true;
                    $apply_button_class = "bg-gray-400 cursor-not-allowed";
                }
            } else {
                error_log("Execute failed for check application: " . $stmtCheckApplication->error);
            }
            $stmtCheckApplication->close();
        } else {
            error_log("Prepare failed for check application: " . $conn->error);
        }
        
        if (!$already_applied) {
            $can_apply = true;
            $apply_button_link = "apply_for_job.php?job_id=" . $job_id;
        }
    }
} else {
    // Not logged in - button can link to login page
    $apply_button_text = "Login to Apply";
    // Construct the return_to URL carefully
    $current_page_url = "view_job_posting.php?job_id=" . $job_id;
    $apply_button_link = "login.php?notice=login_to_apply&return_to=" . urlencode($current_page_url);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($job['title']) ? htmlspecialchars($job['title']) : 'Job Details'; ?> - CareerLynk</title>
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; // Ensure header.php is suitable for public view or adapts ?>

    <main class="container mx-auto mt-8 mb-10 px-4">
        <?php if (isset($_GET['notice'])): ?>
            <div class="alert alert-info max-w-3xl mx-auto" role="alert">
                <?php 
                    if ($_GET['notice'] === 'login_to_apply') echo "Please log in to apply for this job.";
                    else echo htmlspecialchars($_GET['notice']); 
                ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): // For errors from apply_for_job.php redirects ?>
             <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative max-w-3xl mx-auto mb-4" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($job): ?>
            <div class="max-w-3xl mx-auto bg-white p-6 sm:p-8 rounded-lg shadow-xl">
                <div class="flex flex-col md:flex-row justify-between items-start mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
                        <?php 
                        $company_name_display = !empty($job['company']) ? $job['company'] : (!empty($job['employer_fullname']) ? $job['employer_fullname'] : $job['employer_username']);
                        ?>
                        <p class="text-lg text-gray-600"><?php echo htmlspecialchars($company_name_display); ?></p>
                        <p class="text-md text-gray-500 mb-1"><i class="fas fa-map-marker-alt mr-2"></i><?php echo htmlspecialchars($job['location']); ?></p>
                    </div>
                    <div class="mt-4 md:mt-0 text-left md:text-right">
                        <?php if ($is_job_seeker && $can_apply): ?>
                            <a href="<?php echo $apply_button_link; ?>"
                               class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white <?php echo $apply_button_class; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-paper-plane mr-2"></i><?php echo $apply_button_text; ?>
                            </a>
                        <?php elseif ($is_job_seeker && $already_applied): ?>
                            <button type="button" disabled
                               class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white <?php echo $apply_button_class; ?>">
                                <i class="fas fa-check-circle mr-2"></i><?php echo $apply_button_text; ?>
                            </button>
                        <?php elseif (!$is_logged_in): // Not logged in ?>
                             <a href="<?php echo $apply_button_link; ?>"
                               class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white <?php echo $apply_button_class; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-sign-in-alt mr-2"></i><?php echo $apply_button_text; ?>
                            </a>
                        <?php endif; // Employers or other roles won't see an apply button here ?> 
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <h2 class="text-xl font-semibold text-gray-700 mb-3">Job Description</h2>
                    <div class="prose prose-sm sm:prose lg:prose-lg xl:prose-xl max-w-none text-gray-600">
                        <?php echo htmlspecialchars($job['description']); ?>
                    </div>

                    <?php if (!empty($job['salary'])): ?>
                    <div class="mt-6">
                        <h3 class="text-lg font-medium text-gray-700"><i class="fas fa-dollar-sign mr-2"></i>Salary</h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($job['salary']); ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($job['job_type'])): ?>
                    <div class="mt-4">
                        <h3 class="text-lg font-medium text-gray-700"><i class="fas fa-briefcase mr-2"></i>Job Type</h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($job['job_type']); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="mt-6 text-sm text-gray-500">
                        Posted on: <?php echo date("F j, Y", strtotime($job['timestamp'])); ?> by <?php echo htmlspecialchars(!empty($job['employer_fullname']) ? $job['employer_fullname'] : $job['employer_username']); ?>
                    </div>
                </div>
            </div>

             <div class="mt-8 text-center">
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Job Listings
                </a>
            </div>

        <?php else: ?>
            <div class="text-center py-12">
                <h1 class="text-2xl font-bold text-red-600">Job Not Found</h1>
                <p class="text-gray-700 mt-2 mb-6">The job you are looking for could not be found. It may have been removed or the link is incorrect.</p>
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    <i class="fas fa-arrow-left mr-1"></i> Return to Job Listings
                </a>
            </div>
        <?php endif; ?>
    </main>

    <?php if(isset($conn)) $conn->close(); ?>
</body>
</html> 