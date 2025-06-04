<?php
session_start();
include '../database_connection.php'; // Path to your database connection
include 'admin_header.php'; // Includes admin session check

$job_id = null;
$job_title = '';
$error_message = '';
$success_message = '';

if (!isset($_GET['job_id']) || !is_numeric($_GET['job_id'])) {
    $_SESSION['error_message'] = "Invalid job ID specified.";
    header("Location: manage_jobs.php");
    exit;
}

$job_id = intval($_GET['job_id']);

// Fetch job details to display title
$stmt_job = $conn->prepare("SELECT title, status FROM jobs WHERE id = ?");
if ($stmt_job) {
    $stmt_job->bind_param("i", $job_id);
    $stmt_job->execute();
    $result_job = $stmt_job->get_result();
    if ($job_details = $result_job->fetch_assoc()) {
        $job_title = $job_details['title'];
        if ($job_details['status'] !== 'pending_approval') {
            $_SESSION['error_message'] = "This job is not currently pending approval and cannot be rejected with a reason at this time.";
            header("Location: manage_jobs.php");
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Job not found.";
        header("Location: manage_jobs.php");
        exit;
    }
    $stmt_job->close();
} else {
    // Log error, more graceful error for user
    error_log("Failed to prepare statement to fetch job title in reject_job_reason.php: " . $conn->error);
    $_SESSION['error_message'] = "An error occurred while trying to fetch job details.";
    header("Location: manage_jobs.php");
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // This form will submit to process_job_rejection.php, so POST handling will be there.
    // However, it's good practice to keep it flexible.
    // For now, we are directly creating process_job_rejection.php to handle this.
}

?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-red-600 text-white p-3">
                    <h4 class="mb-0 font-semibold">Reject Job Posting</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>

                    <p>You are about to reject the following job posting:</p>
                    <p><strong>Job Title:</strong> <?php echo htmlspecialchars($job_title); ?></p>
                    <p><strong>Job ID:</strong> <?php echo $job_id; ?></p>
                    
                    <form action="process_job_rejection.php" method="POST">
                        <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                        
                        <div class="form-group mb-3">
                            <label for="rejection_reason" class="form-label block text-sm font-medium text-gray-700 mb-1">Reason for Rejection (Optional):</label>
                            <textarea class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="rejection_reason" name="rejection_reason" rows="5" placeholder="Enter the reason why this job is being rejected. This will be visible to the employer."></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="manage_jobs.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg shadow hover:shadow-md transition duration-300">Cancel</a>
                            <button type="submit" name="reject_job_submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg shadow hover:shadow-md transition duration-300">Reject Job</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
<?php if(isset($conn)) $conn->close(); ?> 