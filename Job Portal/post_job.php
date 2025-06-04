<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php?notice=login_required_post_job");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    error_log("Post Job DB Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
    $db_connection_error = "Database connection failed. Please try again later.";
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$role = $_SESSION["role"];

$page_error = '';
$page_success = '';

if ($role !== 'job_employer') {
    $page_error = "You are not authorized to post a job.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $role === 'job_employer' && !isset($db_connection_error)) {
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $job_type = trim($_POST['job_type'] ?? '');
    $salary = trim($_POST['salary'] ?? '');

    if (empty($title) || empty($company) || empty($description) || empty($location) || empty($job_type)) {
        $page_error = "All fields marked with * are required.";
    } else {
        if ($conn->connect_error) {
            $page_error = "Database connection error. Cannot save job.";
        } else {
            // Set default status for new jobs
            $default_status = 'pending_approval'; 

            $insertQuery = "INSERT INTO jobs (title, company, description, location, employer_username, job_type, salary, status, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insertQuery);
            if ($stmt === false) {
                error_log("Post Job Prepare failed (main insert): (" . $conn->errno . ") " . $conn->error);
                $page_error = "An error occurred while preparing to save the job. Please try again.";
            } else {
                // Add the status to bind_param. It's the 8th parameter, so type 's'
                $stmt->bind_param("ssssssss", $title, $company, $description, $location, $username, $job_type, $salary, $default_status);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $job_id = $stmt->insert_id;
                        $job_title_notification = htmlspecialchars($title);
                        
                        $conn_stmt = $conn->prepare("SELECT user2 FROM connections WHERE user1=? AND status='accepted'");
                        if ($conn_stmt === false) {
                            error_log("Post Job Prepare failed (connections select): (" . $conn->errno . ") " . $conn->error);
                        } else {
                            $conn_stmt->bind_param("s", $username);
                            if (!$conn_stmt->execute()) {
                                error_log("Post Job Execute failed (connections select): (" . $conn_stmt->errno . ") " . $conn_stmt->error);
                            } else {
                                $connected_users_result = $conn_stmt->get_result();
                                $message = $username . " posted a new job: " . $job_title_notification;
                                
                                $notif_stmt = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, type, message, job_id) VALUES (?, ?, 'job_post', ?, ?)");
                                if ($notif_stmt === false) {
                                    error_log("Post Job Prepare failed (notification insert): (" . $conn->errno . ") " . $conn->error);
                                } else {
                                    while ($row = $connected_users_result->fetch_assoc()) {
                                        $recipient_username = $row['user2'];
                                        $notif_stmt->bind_param("sssi", $recipient_username, $username, $message, $job_id);
                                        if (!$notif_stmt->execute()) {
                                            error_log("Post Job Execute failed (notification insert for recipient $recipient_username): (" . $notif_stmt->errno . ") " . $notif_stmt->error);
                                        }
                                    }
                                    $notif_stmt->close();
                                }
                            }
                            $conn_stmt->close();
                        }

                        header("Location: jobs.php?success=Job Posted");
                        exit();
                    } else {
                        error_log("Post Job Execute succeeded but no rows affected. SQL: " . $insertQuery . " Params: $title, $company, $description, $location, $username, $job_type, $salary");
                        $page_error = "Failed to post the job. No rows were affected. Please check your input or contact support.";
                    }
                } else {
                    error_log("Post Job Execute failed (main insert): (" . $stmt->errno . ") " . $stmt->error);
                    $page_error = "An error occurred while saving the job. Please try again.";
                }
                if($stmt) $stmt->close();
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($db_connection_error)) {
    $page_error = $db_connection_error;
}

if (isset($_GET['notice'])) {
    $page_success = htmlspecialchars($_GET['notice']);
}
if (isset($_GET['error'])) {
    $page_error = htmlspecialchars($_GET['error']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a New Job - CareerLynk</title>
    <?php 
        // header.php now includes Tailwind, FontAwesome, and style.css (which has Inter font)
        // So, no direct links needed here.
    ?>
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>

    <main class="container mx-auto mt-8 mb-10 px-4">
        <div class="max-w-2xl mx-auto bg-white p-6 sm:p-8 rounded-lg shadow-xl">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-6 text-center">Post a New Job</h1>

            <?php if (!empty($page_error)): ?>
                <div class="alert alert-danger mb-4" role="alert">
                    <?php echo $page_error; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($page_success)): ?>
                <div class="alert alert-success mb-4" role="alert">
                    <?php echo $page_success; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($db_connection_error)): ?>
                 <div class="alert alert-danger mb-4" role="alert">
                    <?php echo $db_connection_error; ?> Please try again later.
                </div>
            <?php endif; ?>

            <?php if ($role === 'job_employer' && !isset($db_connection_error) && (empty($page_error) || ($page_error === "All fields marked with * are required."))): ?>
            <form action="post_job.php" method="POST" class="space-y-6">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Job Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div>
                    <label for="company" class="block text-sm font-medium text-gray-700 mb-1">Company Name <span class="text-red-500">*</span></label>
                    <input type="text" name="company" id="company" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="<?php echo isset($_POST['company']) ? htmlspecialchars($_POST['company']) : ''; ?>">
                </div>

                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location (e.g., City, State or "Remote") <span class="text-red-500">*</span></label>
                    <input type="text" name="location" id="location" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                </div>
                
                <div>
                    <label for="job_type" class="block text-sm font-medium text-gray-700 mb-1">Job Type <span class="text-red-500">*</span></label>
                    <select name="job_type" id="job_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="" <?php echo (!isset($_POST['job_type']) || $_POST['job_type'] == '') ? 'selected' : ''; ?>>Select type...</option>
                        <option value="Full-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
                        <option value="Part-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                        <option value="Contract" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'Contract') ? 'selected' : ''; ?>>Contract</option>
                        <option value="Temporary" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'Temporary') ? 'selected' : ''; ?>>Temporary</option>
                        <option value="Internship" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'Internship') ? 'selected' : ''; ?>>Internship</option>
                        <option value="Volunteer" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'Volunteer') ? 'selected' : ''; ?>>Volunteer</option>
                    </select>
                </div>

                <div>
                    <label for="salary" class="block text-sm font-medium text-gray-700 mb-1">Salary (Optional, e.g., $50,000 - $70,000 per year, or hourly rate)</label>
                    <input type="text" name="salary" id="salary" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" value="<?php echo isset($_POST['salary']) ? htmlspecialchars($_POST['salary']) : ''; ?>">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Job Description <span class="text-red-500">*</span></label>
                    <textarea name="description" id="description" rows="6" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Post Job
                    </button>
                </div>
            </form>
            <?php elseif ($role !== 'job_employer' && empty($db_connection_error)): ?>
                 <p class="text-center text-red-600">You are not authorized to post a job. Please <a href="profile.php" class="underline hover:text-red-800">check your account type</a> or contact support.</p>
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
