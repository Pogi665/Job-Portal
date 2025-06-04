<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$error_message_display = '';
$success_message_display = ''; // For displaying success if already applied
$job_id = isset($_REQUEST['job_id']) ? intval($_REQUEST['job_id']) : 0;

if (isset($_GET['error'])) {
    $error_type = $_GET['error'];
    switch ($error_type) {
        case 'invalid_resume_url':
            $error_message_display = "Please provide a valid URL for your resume.";
            break;
        case 'application_failed_internal':
            $error_message_display = "Your application could not be processed due to an internal error. Please try again.";
            break;
        case 'application_prepare_failed':
        case 'application_execute_failed':
            $error_message_display = "There was a problem submitting your application. Please try again.";
            break;
        case 'already_applied':
            $error_message_display = "You have already applied for this job."; // More specific error for this case
            break;
        default:
            $error_message_display = "An unknown error occurred with your application form.";
            break;
    }
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    error_log("Apply_for_job DB Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
    header("Location: dashboard.php?error=db_connection_critical");
    exit();
}

$username = $_SESSION["username"];
$user_id = $_SESSION["user_id"];

$user_fullname = '';
$user_phone = '';
$user_email = '';
$has_applied = false; // Initialize check for existing application

// Fetch user details (outside transaction, read operation)
$userQuery = $conn->prepare("SELECT full_name, contact_number, email FROM users WHERE id = ?");
if ($userQuery) {
    $userQuery->bind_param("i", $user_id);
    if ($userQuery->execute()) {
        $userResult = $userQuery->get_result();
        if ($userRow = $userResult->fetch_assoc()) {
            $user_fullname = $userRow['full_name'];
            $user_phone = $userRow['contact_number'];
            $user_email = $userRow['email'];
        } else {
            error_log("Apply_for_job: User info not found for user_id: " . $user_id);
            // $error_message_display = "Critical error: Your user information could not be found."; // Or redirect
        }
    } else {
        error_log("Execute failed (userQuery): " . $userQuery->error);
    }
    $userQuery->close();
} else {
    error_log("Prepare failed (userQuery): " . $conn->error);
}

// Check if already applied - This check is now done before POST processing and form display
if ($job_id > 0 && !empty($username)) { // Ensure job_id and username are valid
    $check_applied_stmt = $conn->prepare("SELECT id FROM job_applications WHERE job_id = ? AND applicant_username = ?");
    if ($check_applied_stmt) {
        $check_applied_stmt->bind_param("is", $job_id, $username);
        $check_applied_stmt->execute();
        $check_applied_stmt->store_result();
        if ($check_applied_stmt->num_rows > 0) {
            $has_applied = true;
            // Set a message to be displayed, even if it's not a GET error
            if(empty($error_message_display)) { // Don't overwrite existing GET errors
                 $error_message_display = "You have already applied for this job.";
            }
        }
        $check_applied_stmt->close();
    } else {
        error_log("Prepare failed (check_applied_stmt): " . $conn->error);
        $error_message_display = "Could not verify application status. Please try again.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !$has_applied) { // Only process POST if not already applied
    $job_id_post = isset($_POST['job_id_hidden']) ? intval($_POST['job_id_hidden']) : 0;
    if($job_id_post <= 0){
        error_log("Apply_for_job: Invalid or missing job_id in POST.");
        header("Location: dashboard.php?error=invalid_job_id");
        exit();
    }
    // Re-assign job_id for consistency if coming from POST
    $job_id = $job_id_post; 

    $resume_url = filter_var(trim($_POST['resume_url']), FILTER_SANITIZE_URL);
    $cover_letter = htmlspecialchars(trim($_POST['cover_letter'])); // Trim before sanitizing

    if (empty($resume_url) || !filter_var($resume_url, FILTER_VALIDATE_URL)) {
        header("Location: apply_for_job.php?job_id=" . $job_id . "&error=invalid_resume_url");
        exit();
    }
    if (empty($cover_letter)) { // Example: making cover letter mandatory
        header("Location: apply_for_job.php?job_id=" . $job_id . "&error=cover_letter_required");
        exit();
    }

    // Double check if already applied, as a server-side guard against manipulated form submissions
    $double_check_applied_stmt = $conn->prepare("SELECT id FROM job_applications WHERE job_id = ? AND applicant_username = ?");
    if ($double_check_applied_stmt) {
        $double_check_applied_stmt->bind_param("is", $job_id, $username);
        $double_check_applied_stmt->execute();
        $double_check_applied_stmt->store_result();
        if ($double_check_applied_stmt->num_rows > 0) {
            $double_check_applied_stmt->close();
            header("Location: apply_for_job.php?job_id=" . $job_id . "&error=already_applied");
            exit();
        }
        $double_check_applied_stmt->close();
    } else {
        error_log("Prepare failed (double_check_applied_stmt): " . $conn->error);
        header("Location: apply_for_job.php?job_id=" . $job_id . "&error=application_failed_internal");
        exit();
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO job_applications (job_id, applicant_username, resume_url, cover_letter) VALUES (?, ?, ?, ?)");
        if (!$stmt) throw new Exception("Prepare failed (application insert): " . $conn->error);
        $stmt->bind_param("isss", $job_id, $username, $resume_url, $cover_letter);
        if (!$stmt->execute()) throw new Exception("Execute failed (application insert): " . $stmt->error);
        $stmt->close();

        $employer_username_for_notif = null;
        $job_title_for_notif = null;
        $jobInfoQuery = $conn->prepare("SELECT title, employer_username FROM jobs WHERE id = ?");
        if (!$jobInfoQuery) throw new Exception("Prepare failed (jobInfoQuery): " . $conn->error);
        $jobInfoQuery->bind_param("i", $job_id);
        if (!$jobInfoQuery->execute()) throw new Exception("Execute failed (jobInfoQuery): " . $jobInfoQuery->error);
        $jobInfoResult = $jobInfoQuery->get_result();
        if ($jobInfoRow = $jobInfoResult->fetch_assoc()) {
            $job_title_for_notif = $jobInfoRow['title'];
            $employer_username_for_notif = $jobInfoRow['employer_username'];
        } else {
            throw new Exception("Job details not found for job_id: " . $job_id);
        }
        $jobInfoQuery->close();

        if ($employer_username_for_notif && $job_title_for_notif) {
            $notifMsg = htmlspecialchars($user_fullname) . " applied for your job post: " . htmlspecialchars($job_title_for_notif);
            $type_application = 'job_application'; // Corrected type
            $notifStmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, job_id, type) VALUES (?, ?, ?, ?, ?)");
            if (!$notifStmt) throw new Exception("Prepare failed (employer notifStmt): " . $conn->error);
            $notifStmt->bind_param("sssis", $employer_username_for_notif, $username, $notifMsg, $job_id, $type_application);
            if (!$notifStmt->execute()) throw new Exception("Execute failed (employer notifStmt): " . $notifStmt->error);
            $notifStmt->close();

            // Auto-connect logic (simplified for brevity, original logic was more detailed)
            // Check if connection exists
            $checkConn = $conn->prepare("SELECT id, status FROM connections WHERE (user1 = ? AND user2 = ?) OR (user1 = ? AND user2 = ?)");
            if (!$checkConn) throw new Exception("Prepare failed (checkConn): " . $conn->error);
            $checkConn->bind_param("ssss", $username, $employer_username_for_notif, $employer_username_for_notif, $username);
            $checkConn->execute();
            $connResult = $checkConn->get_result();
            if ($connRow = $connResult->fetch_assoc()) {
                if ($connRow['status'] !== 'accepted') {
                    $updateConn = $conn->prepare("UPDATE connections SET status = 'accepted', accepted_at = NOW() WHERE id = ?");
                    if (!$updateConn) throw new Exception("Prepare failed (updateConn): " . $conn->error);
                    $updateConn->bind_param("i", $connRow['id']);
                    if (!$updateConn->execute()) throw new Exception("Execute failed (updateConn): " . $updateConn->error);
                    $updateConn->close();
                }
            } else {
                $insertConn = $conn->prepare("INSERT INTO connections (user1, user2, status, created_at, accepted_at) VALUES (?, ?, 'accepted', NOW(), NOW())");
                if (!$insertConn) throw new Exception("Prepare failed (insertConn): " . $conn->error);
                $insertConn->bind_param("ss", $username, $employer_username_for_notif);
                if (!$insertConn->execute()) throw new Exception("Execute failed (insertConn): " . $insertConn->error);
                $insertConn->close();
                // Send notification about new connection to applicant
                $connectionNotifMsg = "You are now connected with " . htmlspecialchars($employer_username_for_notif) . ".";
                $type_connection = 'connection_accepted'; // More specific type
                $applicantNotifStmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, type) VALUES (?, ?, ?, ?)");
                if (!$applicantNotifStmt) throw new Exception("Prepare failed (applicantNotifStmt): " . $conn->error);
                $applicantNotifStmt->bind_param("ssss", $username, $employer_username_for_notif, $connectionNotifMsg, $type_connection);
                if (!$applicantNotifStmt->execute()) throw new Exception("Execute failed (applicantNotifStmt): " . $applicantNotifStmt->error);
                $applicantNotifStmt->close();
            }
            $checkConn->close();
        }

        $conn->commit();
        header("Location: dashboard.php?success=application_submitted");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Apply_for_job Transaction Failed: " . $e->getMessage() . " for job_id: " . $job_id . " by user: " . $username);
        header("Location: apply_for_job.php?job_id=" . $job_id . "&error=application_failed_internal");
        exit();
    }
}

// Job details to display on the form page (GET request part)
$job_title_display = "Job"; // Default
if ($job_id > 0) {
    $job_title_stmt = $conn->prepare("SELECT title FROM jobs WHERE id = ?");
    if ($job_title_stmt) {
        $job_title_stmt->bind_param("i", $job_id);
        if ($job_title_stmt->execute()) {
            $job_title_res = $job_title_stmt->get_result();
            if ($job_title_row = $job_title_res->fetch_assoc()) {
                $job_title_display = $job_title_row['title'];
            }
        }
        $job_title_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for <?php echo htmlspecialchars($job_title_display); ?> - CareerLynk</title>
    <?php // Redundant CDN links and inline styles removed. Relies on header.php and style.css ?>
</head>
<body class="bg-gray-100 font-inter">
<?php include 'header.php'; ?>

<main class="container mx-auto mt-10 mb-10 px-4">
    <div class="max-w-3xl mx-auto bg-white p-6 sm:p-8 rounded-lg shadow-xl">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Apply for: <span class="text-blue-600"><?php echo htmlspecialchars($job_title_display); ?></span></h1>
        <p class="text-gray-600 mb-6">Please review your information and submit your application.</p>

        <?php if ($error_message_display): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
            <p class="font-bold">Error</p>
            <p><?php echo htmlspecialchars($error_message_display); ?></p>
        </div>
        <?php endif; ?>

        <div class="mb-8 p-6 border border-gray-200 rounded-lg bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-700 mb-3">Your Information</h2>
            <div class="space-y-2 text-gray-600">
                 <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user_fullname); ?></p>
                 <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($user_phone ? $user_phone : 'Not provided'); ?></p>
                 <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
            </div>
        </div>

        <?php if ($job_id <= 0): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-md" role="alert">
                <p class="font-bold">Job Not Specified</p>
                <p>No job has been selected for application, or the job ID is invalid. Please find a job to apply for from the <a href="jobs.php" class="underline hover:text-yellow-800">jobs page</a>.</p>
            </div>
        <?php elseif ($has_applied): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-md" role="alert">
                <p class="font-bold">Application Submitted</p>
                <p>You have already successfully applied for this job. You can check your application status on your dashboard (if available) or await communication from the employer.</p>
                <div class="mt-4">
                    <a href="jobs.php" class="text-sm text-green-600 hover:text-green-800 font-medium"><i class="fas fa-briefcase mr-1"></i> Find Other Jobs</a>
                    <span class="mx-2 text-gray-400">|</span>
                    <a href="dashboard.php" class="text-sm text-green-600 hover:text-green-800 font-medium"><i class="fas fa-tachometer-alt mr-1"></i> Go to Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <form action="apply_for_job.php?job_id=<?php echo $job_id; ?>" method="POST" class="space-y-6">
                <input type="hidden" name="job_id_hidden" value="<?php echo $job_id; ?>">

                <div>
                    <label for="resume_url" class="block text-sm font-medium text-gray-700 mb-1">Resume URL <span class="text-red-500">*</span></label>
                    <input type="url" name="resume_url" id="resume_url" required placeholder="https://example.com/your-resume.pdf"
                           class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-500">Link to your online resume (e.g., LinkedIn, Google Drive, personal website).</p>
                </div>

                <div>
                    <label for="cover_letter" class="block text-sm font-medium text-gray-700 mb-1">Cover Letter <span class="text-red-500">*</span></label>
                    <textarea name="cover_letter" id="cover_letter" rows="6" required placeholder="Explain why you are a good fit for this role..."
                              class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                </div>

                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        Submit Application
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php if (isset($conn) && $conn instanceof mysqli) $conn->close(); ?>
</body>
</html>
