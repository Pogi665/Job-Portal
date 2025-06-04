<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION["username"]) || !isset($_SESSION["user_id"])) {
    header("Location: login.php?error=auth_required&return_to=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    // For a page that requires DB, better to show an error than a broken page
    $db_connection_error = "Database connection failed. Please try again later.";
    // No exit here yet, let the page structure load to display the error
}

$current_username = $_SESSION["username"];
$current_user_role = $_SESSION["role"] ?? '';

$page_error = '';
$page_success = '';
$page_notice = '';
$job = null; // Initialize job variable

// Get and validate job ID from GET request
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if (isset($_POST['job_id'])) { // If form submitted, job_id comes from POST
    $job_id = (int)$_POST['job_id'];
}

if ($job_id <= 0) {
    // If no job_id, can't edit. Redirect or show error.
    // For now, assume this is a hard error if reached without job_id for form display.
    // If POST, it means job_id wasn't in POST.
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
         header("Location: jobs.php?error=invalid_job_id_provided");
         exit();
    }
    // If POST and no job_id, it's a bad request.
    $page_error = "Invalid job ID. Cannot process update.";
}


// Only allow 'job_employer' role
if ($current_user_role !== 'job_employer') {
    error_log("User " . $current_username . " with role " . $current_user_role . " attempted to access edit_job.php");
    if ($_SERVER["REQUEST_METHOD"] !== "POST" && $job_id > 0) { // Avoid redirect loop if POST
        header("Location: jobs.php?error=unauthorized_action");
        exit();
    }
    $page_error = "You are not authorized to edit jobs.";
}


// Fetch job details for GET request or to repopulate form on POST error
// This needs to run before POST processing if we want to verify ownership from DB record
// Or, if POST, we use submitted values and only fetch for initial display.
// Let's fetch if not POST, or if POST and there was an error (to re-display form)
if ($job_id > 0 && empty($page_error) && !isset($db_connection_error)) {
    $jobQuery = "SELECT id, title, description, location, company, salary, job_type, employer_username FROM jobs WHERE id = ?";
    $stmtFetch = $conn->prepare($jobQuery);
    if (!$stmtFetch) {
        error_log("Prepare failed for jobQuery (edit_job): " . $conn->error);
        $page_error = "Error preparing to fetch job details.";
    } else {
        $stmtFetch->bind_param("i", $job_id);
        if (!$stmtFetch->execute()) {
            error_log("Execute failed for jobQuery (edit_job): " . $stmtFetch->error);
            $page_error = "Error fetching job details.";
        } else {
            $jobResult = $stmtFetch->get_result();
            if ($jobResult->num_rows === 0) {
                $page_error = "Job not found or you are not authorized to edit this job.";
                $job = null; // Ensure job is null
            } else {
                $job = $jobResult->fetch_assoc();
                // Authorization check: does this job belong to the current employer?
                if ($job['employer_username'] !== $current_username) {
                    error_log("User " . $current_username . " attempted to edit job ID " . $job_id . " not owned by them (owned by " . $job['employer_username'] . ").");
                    $page_error = "You are not authorized to edit this specific job.";
                    $job = null; // Nullify job if not authorized
                }
            }
        }
        if($stmtFetch) $stmtFetch->close();
    }
}


// Handle form submission to update the job
if ($_SERVER["REQUEST_METHOD"] == "POST" && $job_id > 0 && empty($page_error) && !isset($db_connection_error) && $current_user_role === 'job_employer') {
    // If $job is null here, it means initial fetch failed or auth issue before POST logic.
    // We re-check ownership before update anyway from DB.

    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $job_type = trim($_POST['job_type'] ?? '');
    $salary = trim($_POST['salary'] ?? ''); // Optional

    // Values to repopulate form in case of error
    $form_values = $_POST;

    if (empty($title) || empty($company) || empty($description) || empty($location) || empty($job_type)) {
        $page_error = "All fields marked with * are required.";
        // $job variable would still hold original values if initial GET was successful
        // If we want to show submitted values, we use $form_values
        if ($job) { // if original job was loaded, merge submitted values for repopulation
            $job = array_merge($job, $form_values);
        } else { // if original job failed to load, use submitted values directly
            $job = $form_values;
            $job['id'] = $job_id; // ensure id is part of job array for form action
        }

    } else {
        // Re-verify ownership just before update for security
        $checkOwnerQuery = "SELECT employer_username FROM jobs WHERE id = ? AND employer_username = ?";
        $stmtCheckOwner = $conn->prepare($checkOwnerQuery);
        if (!$stmtCheckOwner) {
            error_log("Prepare failed for checkOwnerQuery (edit_job): " . $conn->error);
            $page_error = "Error preparing to verify job ownership.";
        } else {
            $stmtCheckOwner->bind_param("is", $job_id, $current_username);
            if (!$stmtCheckOwner->execute()) {
                error_log("Execute failed for checkOwnerQuery (edit_job): " . $stmtCheckOwner->error);
                $page_error = "Error verifying job ownership.";
            } else {
                $ownerResult = $stmtCheckOwner->get_result();
                if ($ownerResult->num_rows === 0) {
                    $page_error = "Job not found or you no longer have permission to edit this job.";
                } else {
                    // Ownership confirmed, proceed with update
                    $updateQuery = "UPDATE jobs SET title = ?, company = ?, description = ?, location = ?, job_type = ?, salary = ? WHERE id = ? AND employer_username = ?";
                    $stmtUpdate = $conn->prepare($updateQuery);
                    if (!$stmtUpdate) {
                        error_log("Prepare failed for updateQuery (edit_job): " . $conn->error);
                        $page_error = "An error occurred while preparing to update the job.";
                    } else {
                        // Storing raw data. Sanitization (htmlspecialchars) is for output.
                        $stmtUpdate->bind_param("ssssssis", $title, $company, $description, $location, $job_type, $salary, $job_id, $current_username);
                        if ($stmtUpdate->execute()) {
                            if ($stmtUpdate->affected_rows > 0) {
                                header("Location: jobs.php?success=job_updated");
                                exit();
                            } else {
                                // No rows affected - could be same data submitted or issue.
                                // Fetch current job data again to show updated form
                                $jobQueryRefresh = "SELECT id, title, description, location, company, salary, job_type, employer_username FROM jobs WHERE id = ? AND employer_username = ?";
                                $stmtRefresh = $conn->prepare($jobQueryRefresh);
                                $stmtRefresh->bind_param("is", $job_id, $current_username);
                                $stmtRefresh->execute();
                                $jobRefreshResult = $stmtRefresh->get_result();
                                $job = $jobRefreshResult->fetch_assoc(); // Update $job with current DB state
                                $stmtRefresh->close();
                                $page_notice = "No changes were made to the job, or the submitted data was identical.";
                            }
                        } else {
                            error_log("Execute failed for updateQuery (edit_job): " . $stmtUpdate->error);
                            $page_error = "An error occurred while updating the job.";
                        }
                        if($stmtUpdate) $stmtUpdate->close();
                    }
                }
            }
            if($stmtCheckOwner) $stmtCheckOwner->close();
        }
        // If there was an error during update, repopulate $job with submitted values for the form
        if (!empty($page_error)) {
            if ($job) { // if original job was loaded, merge submitted values for repopulation
                 $job = array_merge($job, $form_values);
            } else { // if original job failed to load (e.g. $job_id was bad from start of POST), use submitted values
                 $job = $form_values;
                 $job['id'] = $job_id; // ensure id is part of job array
            }
        }
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($db_connection_error)) {
    $page_error = $db_connection_error; // Show DB error if POST attempt on failed initial connection
    $job = $_POST; // Repopulate form with POST data
    $job['id'] = $job_id;
}


// Get messages from GET parameters (e.g., after redirect from login)
if (isset($_GET['error'])) { $page_error = htmlspecialchars($_GET['error']); }
if (isset($_GET['success'])) { $page_success = htmlspecialchars($_GET['success']); }
if (isset($_GET['notice'])) { $page_notice = htmlspecialchars($_GET['notice']); }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - CareerLynk</title>
    <?php /* header.php includes Tailwind, FontAwesome, style.css (Inter font) */ ?>
</head>
<body class="bg-gray-100">
<?php include 'header.php'; ?>

<main class="container mx-auto mt-8 mb-10 px-4">
    <div class="max-w-2xl mx-auto bg-white p-6 sm:p-8 rounded-lg shadow-xl">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 text-center">Edit Job</h1>

        <?php if (!empty($page_error)): ?>
            <div class="alert alert-danger mb-4" role="alert"><?php echo $page_error; ?></div>
        <?php endif; ?>
        <?php if (!empty($page_success)): ?>
            <div class="alert alert-success mb-4" role="alert"><?php echo $page_success; ?></div>
        <?php endif; ?>
        <?php if (!empty($page_notice)): ?>
            <div class="alert alert-info mb-4" role="alert"><?php echo $page_notice; ?></div>
        <?php endif; ?>
        <?php if (isset($db_connection_error) && empty($page_error) && empty($page_success) && empty($page_notice)): // Show DB error only if no other message is more specific ?>
            <div class="alert alert-danger mb-4" role="alert"><?php echo $db_connection_error; ?></div>
        <?php endif; ?>

        <?php if ($job && $current_user_role === 'job_employer' && !isset($db_connection_error) && (empty($page_error) || $page_error === "All fields marked with * are required." || !empty($page_notice) ) ): ?>
        <form action="edit_job.php?job_id=<?php echo htmlspecialchars($job['id']); ?>" method="POST" class="space-y-6">
            <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job['id']); ?>">

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Job Title <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="title" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="<?php echo htmlspecialchars($job['title'] ?? ''); ?>">
            </div>

            <div>
                <label for="company" class="block text-sm font-medium text-gray-700 mb-1">Company Name <span class="text-red-500">*</span></label>
                <input type="text" name="company" id="company" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="<?php echo htmlspecialchars($job['company'] ?? ''); ?>">
            </div>

            <div>
                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location (e.g., City, State or "Remote") <span class="text-red-500">*</span></label>
                <input type="text" name="location" id="location" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="<?php echo htmlspecialchars($job['location'] ?? ''); ?>">
            </div>
            
            <div>
                <label for="job_type" class="block text-sm font-medium text-gray-700 mb-1">Job Type <span class="text-red-500">*</span></label>
                <select name="job_type" id="job_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="" <?php echo empty($job['job_type'] ?? '') ? 'selected' : ''; ?>>Select type...</option>
                    <?php 
                    $job_types = ["Full-time", "Part-time", "Contract", "Temporary", "Internship", "Volunteer"];
                    foreach ($job_types as $type): 
                        $selected = (isset($job['job_type']) && $job['job_type'] == $type) ? 'selected' : '';
                    ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="salary" class="block text-sm font-medium text-gray-700 mb-1">Salary (Optional)</label>
                <input type="text" name="salary" id="salary" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="<?php echo htmlspecialchars($job['salary'] ?? ''); ?>">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Job Description <span class="text-red-500">*</span></label>
                <textarea name="description" id="description" rows="6" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?php echo htmlspecialchars($job['description'] ?? ''); ?></textarea>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Update Job
                </button>
            </div>
        </form>
        <?php elseif (!$job && $job_id > 0 && empty($page_error) && !isset($db_connection_error)): // Case where job_id was valid but job not found for user ?>
            <p class="text-center text-red-500">Job not found or you are not authorized to edit this job.</p>
            <div class="mt-6 text-center">
                 <a href="jobs.php" class="text-blue-600 hover:text-blue-800 font-medium"><i class="fas fa-arrow-left mr-1"></i> Back to Your Job Listings</a>
            </div>
        <?php elseif ($current_user_role !== 'job_employer' && empty($db_connection_error) && empty($page_error)): // General auth error if no other error shown ?>
            <p class="text-center text-red-500">You are not authorized to edit jobs.</p>
        <?php endif; ?>
        <?php if (!$job && empty($page_error) && !isset($db_connection_error) && $job_id <=0 && $_SERVER["REQUEST_METHOD"] !== "POST"): // If job_id was invalid from start ?>
             <p class="text-center text-red-500">No job ID was provided or it was invalid.</p>
             <div class="mt-6 text-center">
                 <a href="jobs.php" class="text-blue-600 hover:text-blue-800 font-medium"><i class="fas fa-arrow-left mr-1"></i> Back to Your Job Listings</a>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php include 'footer.php'; ?>
<?php
if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
    $conn->close();
}
?>
</body>
</html>
