<?php
session_start();

// Redirect to login if user is not logged in or user_id/role is not set
if (!isset($_SESSION["username"]) || !isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    $_SESSION['message'] = "Authentication required. Please log in.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

// Ensure the user is a job employer or admin
if ($_SESSION["role"] !== 'job_employer' && $_SESSION["role"] !== 'admin') {
    error_log("User " . $_SESSION["username"] . " (Role: " . $_SESSION["role"] . ") attempted to access employer/admin job details page.");
    $_SESSION['message'] = "You are not authorized to access this page.";
    $_SESSION['message_type'] = "error";
    header("Location: dashboard.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    error_log("Connection failed in job_details.php: " . $conn->connect_error);
    $_SESSION['message'] = "Database connection failed. Please try again later.";
    $_SESSION['message_type'] = "error";
    header("Location: dashboard.php"); 
    exit();
}

$current_username = $_SESSION["username"];
$page_message = '';
$page_message_type = '';

// Display session messages
if (isset($_SESSION['message'])) {
    $page_message = $_SESSION['message'];
    $page_message_type = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Mark notification as read if notif_id is passed
if (isset($_GET['notif_id'])) {
    $notif_id = intval($_GET['notif_id']);
    if ($notif_id > 0) {
        $stmtUpdateNotif = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND recipient_username = ?");
        if ($stmtUpdateNotif) {
            $stmtUpdateNotif->bind_param("is", $notif_id, $current_username);
            if (!$stmtUpdateNotif->execute()) {
                error_log("Execute failed for update notification (ID: $notif_id): " . $stmtUpdateNotif->error);
            }
            $stmtUpdateNotif->close();
        } else {
            error_log("Prepare failed for update notification: " . $conn->error);
        }
    }
}

// Handle POST from applicant acceptance form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_acceptance'])) {
    $job_id_posted = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $application_id_posted = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
    $applicant_username = isset($_POST['applicant_username']) ? trim($_POST['applicant_username']) : '';
    $custom_message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if ($job_id_posted <= 0 || $application_id_posted <= 0 || empty($applicant_username) || empty($custom_message)) {
        $_SESSION['message'] = "Missing fields for applicant acceptance. Please fill all required information.";
        $_SESSION['message_type'] = "error";
        header("Location: job_details.php?job_id=" . $job_id_posted);
        exit();
    }

    $stmtCheckJobOwner = $conn->prepare("SELECT title FROM jobs WHERE id = ? AND employer_username = ?");
    if (!$stmtCheckJobOwner) {
        error_log("Prepare failed (check job owner for acceptance): " . $conn->error);
        $_SESSION['message'] = "Could not process acceptance due to an internal error (DBP01).";
        $_SESSION['message_type'] = "error";
        header("Location: job_details.php?job_id=" . $job_id_posted);
        exit();
    }
    $stmtCheckJobOwner->bind_param("is", $job_id_posted, $current_username);
    if (!$stmtCheckJobOwner->execute()) {
        error_log("Execute failed (check job owner for acceptance): " . $stmtCheckJobOwner->error);
        $stmtCheckJobOwner->close();
        $_SESSION['message'] = "Could not process acceptance due to an internal error (DBE01).";
        $_SESSION['message_type'] = "error";
        header("Location: job_details.php?job_id=" . $job_id_posted);
        exit();
    }
    $jobOwnerResult = $stmtCheckJobOwner->get_result();
    if ($jobOwnerResult->num_rows === 0) {
        error_log("User $current_username attempted to accept applicant for job ID $job_id_posted not owned by them or job not found.");
        $stmtCheckJobOwner->close();
        $_SESSION['message'] = "You are not authorized to perform this action on the specified job or the job was not found.";
        $_SESSION['message_type'] = "error";
        header("Location: jobs.php"); // Redirect to general jobs page
        exit();
    }
    $jobDataForNotif = $jobOwnerResult->fetch_assoc();
    $job_title = $jobDataForNotif['title'] ?? 'the job';
    $stmtCheckJobOwner->close();

    $conn->begin_transaction();
    try {
        // Use application_id for more precise update
        $stmtUpdateStatus = $conn->prepare("UPDATE job_applications SET status = 'accepted' WHERE id = ? AND job_id = ? AND applicant_username = ? AND status = 'pending'");
        if (!$stmtUpdateStatus) throw new Exception("Prepare failed (update app status): " . $conn->error);
        $stmtUpdateStatus->bind_param("iis", $application_id_posted, $job_id_posted, $applicant_username);
        if (!$stmtUpdateStatus->execute()) throw new Exception("Execute failed (update app status): " . $stmtUpdateStatus->error);
        $affected_rows = $stmtUpdateStatus->affected_rows;
        $stmtUpdateStatus->close();

        if ($affected_rows > 0) {
            $stmtInsertMessage = $conn->prepare("INSERT INTO messages (sender_username, recipient_username, message, job_id, timestamp, is_read) VALUES (?, ?, ?, ?, NOW(), 0)");
            if (!$stmtInsertMessage) throw new Exception("Prepare failed (insert message): " . $conn->error);
            $stmtInsertMessage->bind_param("sssi", $current_username, $applicant_username, $custom_message, $job_id_posted);
            if (!$stmtInsertMessage->execute()) throw new Exception("Execute failed (insert message): " . $stmtInsertMessage->error);
            $stmtInsertMessage->close();

            $notif_msg = "Congratulations! You have been accepted for the job: " . htmlspecialchars($job_title) . ". The employer sent you a message.";
            $notif_type = "job_acceptance"; // Ensure this type exists in ENUM or handle accordingly
            $stmtInsertNotif = $conn->prepare("INSERT INTO notifications (recipient_username, sender_username, message, type, job_id, created_at, is_read) VALUES (?, ?, ?, ?, ?, NOW(), 0)");
            if (!$stmtInsertNotif) throw new Exception("Prepare failed (insert notification): " . $conn->error);
            // Note: 'timestamp' column was in original, now 'created_at'. Ensure DB schema matches.
            // If 'type' enum 'job_acceptance' does not exist, this will fail.
            $stmtInsertNotif->bind_param("ssssi", $applicant_username, $current_username, $notif_msg, $notif_type, $job_id_posted);
            if (!$stmtInsertNotif->execute()) throw new Exception("Execute failed (insert notification): " . $stmtInsertNotif->error);
            $stmtInsertNotif->close();
        } else {
             // Application might not have been 'pending' or IDs didn't match.
             // Set a message to inform the employer.
             $_SESSION['message'] = "Applicant status was not updated. They may have already been processed or the application details were incorrect.";
             $_SESSION['message_type'] = "warning";
        }

        $conn->commit();
        if ($affected_rows > 0) { // Only set success if an applicant was actually accepted
            $_SESSION['message'] = "Applicant " . htmlspecialchars($applicant_username) . " accepted successfully and notified.";
            $_SESSION['message_type'] = "success";
        }
        header("Location: job_details.php?job_id=" . $job_id_posted);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Applicant acceptance transaction failed for job ID $job_id_posted, applicant $applicant_username: " . $e->getMessage());
        $_SESSION['message'] = "Applicant acceptance process failed due to a transaction error. Please try again.";
        $_SESSION['message_type'] = "error";
        header("Location: job_details.php?job_id=" . $job_id_posted);
        exit();
    }
}

$job_id_get = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

if ($job_id_get <= 0) {
    $_SESSION['message'] = "Invalid job ID specified.";
    $_SESSION['message_type'] = "error";
    header("Location: jobs.php");
    exit();
}

$job = null;
$applicants = ['pending' => [], 'accepted' => [], 'rejected' => []];
$applicants_fetch_error = ''; // This will be displayed directly if it occurs.

$stmtJobQuery = null; // Initialize stmtJobQuery

// Prepare the job query based on user role
if ($_SESSION["role"] === 'admin') {
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $job_id_get);
        $stmtJobQuery = $stmt;
    } else {
        error_log("Prepare failed for admin jobQuery: " . $conn->error);
        $_SESSION['message'] = "Error preparing job details (DBP02a). Please try again.";
        $_SESSION['message_type'] = "error";
    }
} else { // For 'job_employer'
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND employer_username = ?");
    if ($stmt) {
        $stmt->bind_param("is", $job_id_get, $current_username);
        $stmtJobQuery = $stmt;
    } else {
        error_log("Prepare failed for employer jobQuery: " . $conn->error);
        $_SESSION['message'] = "Error preparing job details (DBP02b). Please try again.";
        $_SESSION['message_type'] = "error";
    }
}

if ($stmtJobQuery) { // Proceed only if statement was successfully prepared
    if (!$stmtJobQuery->execute()) {
        error_log("Execute failed for jobQuery: " . $stmtJobQuery->error);
        $_SESSION['message'] = "Error loading job details (DBE02). Please try again.";
        $_SESSION['message_type'] = "error";
    } else {
        $jobResult = $stmtJobQuery->get_result();
        if ($jobResult->num_rows > 0) {
            $job = $jobResult->fetch_assoc();
        } else {
            // Job not found. If not admin, set a specific message.
            // For admin, $job remaining null is handled by the page display.
            if ($_SESSION["role"] !== 'admin') {
                 $_SESSION['message'] = "Job not found or you do not have permission to view this job.";
                 $_SESSION['message_type'] = "warning";
            }
            // If page_message was already set (e.g. from a previous action), it will be displayed.
            // Otherwise, the HTML part will show a generic "job not found" or use the session message above.
        }
    }
    $stmtJobQuery->close();
}

if ($job) {
    // Fetch applicants. Using application_id as 'id' in the $applicant_data array
    $stmtApplicantsQuery = $conn->prepare("SELECT ja.id as application_id, ja.applicant_username, ja.status, ja.resume_url, ja.cover_letter, ja.application_date, u.full_name as applicant_fullname, u.email as applicant_email, u.contact_number as applicant_phone FROM job_applications ja JOIN users u ON ja.applicant_username = u.username WHERE ja.job_id = ? ORDER BY ja.application_date DESC");
    if (!$stmtApplicantsQuery) {
        error_log("Prepare failed for applicantsQuery: " . $conn->error);
        $applicants_fetch_error = "Could not load applicants due to a server error (DBP03).";
    } else {
        $stmtApplicantsQuery->bind_param("i", $job_id_get);
        if (!$stmtApplicantsQuery->execute()) {
            error_log("Execute failed for applicantsQuery: " . $stmtApplicantsQuery->error);
            $applicants_fetch_error = "Could not load applicants due to a server error (DBE03).";
        } else {
            $applicantsResult = $stmtApplicantsQuery->get_result();
            while ($applicant_row = $applicantsResult->fetch_assoc()) {
                // Use applicant's full name from users table if available, otherwise username from job_applications
                $display_name = !empty($applicant_row['applicant_fullname']) ? htmlspecialchars($applicant_row['applicant_fullname']) : htmlspecialchars($applicant_row['applicant_username']);
                $status_key = strtolower($applicant_row['status']);
                
                $applicant_data = [
                    'application_id' => $applicant_row['application_id'],
                    'username' => htmlspecialchars($applicant_row['applicant_username']), 
                    'display_name' => $display_name, // Already escaped
                    'resume_url' => !empty($applicant_row['resume_url']) ? htmlspecialchars($applicant_row['resume_url']) : null,
                    'cover_letter' => !empty($applicant_row['cover_letter']) ? nl2br(htmlspecialchars($applicant_row['cover_letter'])) : 'N/A',
                    'application_date' => htmlspecialchars(date("F j, Y, g:i a", strtotime($applicant_row['application_date']))),
                    'email' => !empty($applicant_row['applicant_email']) ? htmlspecialchars($applicant_row['applicant_email']) : 'N/A',
                    'phone' => !empty($applicant_row['applicant_phone']) ? htmlspecialchars($applicant_row['applicant_phone']) : 'N/A'
                ];

                if (array_key_exists($status_key, $applicants)) {
                     $applicants[$status_key][] = $applicant_data;
                } else {
                     // Fallback for unexpected status, treat as pending. Log this.
                     error_log("Unexpected applicant status '{$applicant_row['status']}' for job ID {$job_id_get}, applicant {$applicant_row['applicant_username']}. Falling back to pending.");
                     $applicants['pending'][] = $applicant_data;
                }
            }
        }
        if(isset($stmtApplicantsQuery)) $stmtApplicantsQuery->close();
    }
}
$conn->close(); // Close DB connection after all queries
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Job: <?php echo $job ? htmlspecialchars($job['title']) : 'Details'; ?> - CareerLynk</title>
    <!-- Tailwind CSS, Font Awesome, and custom styles are included via header.php -->
    <script>
        function toggleAcceptForm(applicantUsername, applicationId) {
            const form = document.getElementById('acceptForm-' + applicationId); // Use unique application ID
            const allForms = document.querySelectorAll('[id^="acceptForm-"]');
            allForms.forEach(f => {
                if (f.id !== form.id) {
                    f.classList.add('hidden');
                }
            });
            if (form) {
                form.classList.toggle('hidden');
                if (!form.classList.contains('hidden')) {
                    document.getElementById('message-' + applicationId).focus();
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 font-inter">

<?php include 'header.php'; ?>

<main class="container mx-auto mt-8 mb-16 px-4">

    <?php if (!empty($page_message)): ?>
        <div class="mb-6 p-4 rounded-md <?php echo ($page_message_type === 'error' ? 'bg-red-100 border-l-4 border-red-500 text-red-700' : ($page_message_type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : ($page_message_type === 'warning' ? 'bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700' : 'bg-blue-100 border-l-4 border-blue-500 text-blue-700'))); ?>" role="alert">
            <p class="font-bold"><?php echo htmlspecialchars(ucfirst($page_message_type)); ?></p>
            <p><?php echo htmlspecialchars($page_message); ?></p>
            <?php if (!$job && ($page_message_type === 'error' || strpos($page_message, 'Job not found') !== false || strpos($page_message, 'Error loading job details') !== false)): ?>
                <div class="mt-2">
                    <a href="jobs.php" class="text-sm <?php echo ($page_message_type === 'error' ? 'text-red-600 hover:text-red-800' : 'text-blue-600 hover:text-blue-800'); ?> font-medium"><i class="fas fa-arrow-left mr-1"></i> Back to My Job Postings</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($job): ?>
        <div class="bg-white shadow-xl rounded-lg p-6 md:p-8 mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 pb-6 border-b border-gray-200">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($job['title']); ?></h1>
                    <p class="text-md text-gray-600">
                        <i class="fas fa-building mr-2 text-gray-500"></i><?php echo htmlspecialchars($job['company'] ?? 'N/A'); ?>
                        <span class="mx-2 text-gray-400">|</span>
                        <i class="fas fa-map-marker-alt mr-2 text-gray-500"></i><?php echo htmlspecialchars($job['location'] ?? 'N/A'); ?>
                    </p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="edit_job.php?job_id=<?php echo $job['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-md shadow-sm transition duration-150 ease-in-out text-sm">
                        <i class="fas fa-edit mr-2"></i>Edit Job
                    </a>
                    <a href="delete_job.php?job_id=<?php echo $job['id']; ?>" onclick="return confirm('Are you sure you want to delete this job posting? This action cannot be undone.');" class="ml-2 bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-md shadow-sm transition duration-150 ease-in-out text-sm">
                        <i class="fas fa-trash-alt mr-2"></i>Delete Job
                    </a>
                </div>
            </div>

            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-2">Job Description</h2>
                <p class="text-gray-600 leading-relaxed prose max-w-full"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-1">Job Type</h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($job['job_type'] ?? 'Not specified'); ?></p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-1">Salary</h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($job['salary'] ?? 'Not specified'); ?></p>
                </div>
            </div>

            <p class="text-sm text-gray-500">Posted on: <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($job['timestamp']))); ?></p>
        </div>

        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Applicants</h2>

        <?php if (!empty($applicants_fetch_error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md" role="alert">
                <p class="font-bold">Error Loading Applicants</p>
                <p><?php echo htmlspecialchars($applicants_fetch_error); ?></p>
            </div>
        <?php endif; ?>
        
        <?php 
        $status_sections = [
            'pending' => 'Pending Review',
            'accepted' => 'Accepted Applicants',
            'rejected' => 'Rejected Applicants'
        ];
        $status_colors = [
            'pending' => 'blue',
            'accepted' => 'green',
            'rejected' => 'red'
        ];
        ?>

        <?php foreach ($status_sections as $status => $title): ?>
            <?php if (!empty($applicants[$status])): ?>
                <section class="mb-10">
                    <h3 class="text-xl font-semibold text-<?php echo $status_colors[$status]; ?>-600 mb-4 pb-2 border-b border-<?php echo $status_colors[$status]; ?>-300"><?php echo htmlspecialchars($title); ?> (<?php echo count($applicants[$status]); ?>)</h3>
                    <div class="space-y-6">
                        <?php foreach ($applicants[$status] as $applicant): ?>
                            <div class="bg-white shadow-lg rounded-lg p-5 border-l-4 border-<?php echo $status_colors[$status]; ?>-500">
                                <div class="flex flex-col sm:flex-row justify-between items-start">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-800 hover:text-blue-600">
                                            <a href="view_applicant_profile.php?applicant_username=<?php echo urlencode($applicant['username']); ?>&job_id=<?php echo $job_id_get; ?>&application_id=<?php echo $applicant['application_id']; ?>">
                                                <?php echo $applicant['display_name']; ?>
                                            </a>
                                            <span class="text-sm text-gray-500 ml-2">(<?php echo $applicant['username']; ?>)</span>
                                        </h4>
                                        <p class="text-sm text-gray-600"><i class="fas fa-envelope mr-1 text-gray-500"></i> <?php echo $applicant['email']; ?></p>
                                        <p class="text-sm text-gray-600"><i class="fas fa-phone mr-1 text-gray-500"></i> <?php echo $applicant['phone']; ?></p>
                                        <p class="text-xs text-gray-500 mt-1">Applied on: <?php echo $applicant['application_date']; ?></p>
                                    </div>
                                    <div class="mt-3 sm:mt-0 sm:ml-4 flex flex-col sm:items-end space-y-2">
                                        <?php if (!empty($applicant['resume_url'])): ?>
                                            <a href="<?php echo $applicant['resume_url']; ?>" target="_blank" class="text-sm bg-blue-500 hover:bg-blue-600 text-white font-medium py-1.5 px-3 rounded-md shadow-sm inline-flex items-center transition duration-150 ease-in-out">
                                                <i class="fas fa-file-alt mr-2"></i>View Resume
                                            </a>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-400 italic py-1.5 px-3 inline-flex items-center"><i class="fas fa-file-alt mr-2"></i>No Resume</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($status === 'pending'): ?>
                                            <button onclick="toggleAcceptForm('<?php echo $applicant['username']; ?>', <?php echo $applicant['application_id']; ?>)" class="text-sm bg-green-500 hover:bg-green-600 text-white font-medium py-1.5 px-3 rounded-md shadow-sm inline-flex items-center transition duration-150 ease-in-out">
                                                <i class="fas fa-check-circle mr-2"></i>Accept
                                            </button>
                                            <form action="reject_applicant.php" method="POST" class="inline-block">
                                                <input type="hidden" name="application_id" value="<?php echo $applicant['application_id']; ?>">
                                                <input type="hidden" name="job_id" value="<?php echo $job_id_get; ?>">
                                                <input type="hidden" name="applicant_username" value="<?php echo $applicant['username']; ?>">
                                                <button type="submit" name="reject_applicant_submit" onclick="return confirm('Are you sure you want to reject this applicant?');" class="text-sm bg-red-500 hover:bg-red-600 text-white font-medium py-1.5 px-3 rounded-md shadow-sm inline-flex items-center transition duration-150 ease-in-out">
                                                    <i class="fas fa-times-circle mr-2"></i>Reject
                                                </button>
                                            </form>
                                        <?php elseif ($status === 'accepted'): ?>
                                             <span class="text-sm bg-green-100 text-green-700 font-medium py-1.5 px-3 rounded-full inline-flex items-center">
                                                <i class="fas fa-check-circle mr-2"></i>Accepted
                                            </span>
                                        <?php elseif ($status === 'rejected'): ?>
                                            <span class="text-sm bg-red-100 text-red-700 font-medium py-1.5 px-3 rounded-full inline-flex items-center">
                                                <i class="fas fa-times-circle mr-2"></i>Rejected
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($applicant['cover_letter']) && $applicant['cover_letter'] !== 'N/A'): ?>
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <h5 class="text-sm font-semibold text-gray-700 mb-1">Cover Letter:</h5>
                                    <p class="text-sm text-gray-600 prose prose-sm max-w-none"><?php echo $applicant['cover_letter']; /* Already nl2br and htmlspecialchars */ ?></p>
                                </div>
                                <?php endif; ?>

                                <?php if ($status === 'pending'): ?>
                                <div id="acceptForm-<?php echo $applicant['application_id']; ?>" class="hidden mt-4 pt-4 border-t border-gray-200">
                                    <form action="job_details.php?job_id=<?php echo $job_id_get; ?>" method="POST">
                                        <input type="hidden" name="job_id" value="<?php echo $job_id_get; ?>">
                                        <input type="hidden" name="application_id" value="<?php echo $applicant['application_id']; ?>">
                                        <input type="hidden" name="applicant_username" value="<?php echo $applicant['username']; ?>">
                                        <div class="mb-3">
                                            <label for="message-<?php echo $applicant['application_id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">Message to Applicant (required):</label>
                                            <textarea id="message-<?php echo $applicant['application_id']; ?>" name="message" rows="3" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="e.g., Congratulations! We'd like to move forward with your application..."></textarea>
                                        </div>
                                        <button type="submit" name="submit_acceptance" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-md shadow-sm transition duration-150 ease-in-out text-sm">
                                            <i class="fas fa-paper-plane mr-2"></i>Send Acceptance & Message
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php elseif (empty($applicants_fetch_error)): // Only show "no applicants" if there wasn't a fetch error ?>
                <section class="mb-10">
                     <h3 class="text-xl font-semibold text-<?php echo $status_colors[$status]; ?>-600 mb-4 pb-2 border-b border-<?php echo $status_colors[$status]; ?>-300"><?php echo htmlspecialchars($title); ?></h3>
                    <div class="bg-white shadow-md rounded-lg p-6 text-center">
                        <i class="fas fa-user-slash fa-3x text-gray-400 mb-3"></i>
                        <p class="text-gray-500">No applicants in this category yet.</p>
                    </div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <?php 
            $total_applicants = count($applicants['pending']) + count($applicants['accepted']) + count($applicants['rejected']);
            if ($total_applicants === 0 && empty($applicants_fetch_error)): 
        ?>
            <div class="bg-white shadow-xl rounded-lg p-8 text-center">
                <i class="fas fa-users-slash fa-4x text-gray-400 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No Applicants Yet</h3>
                <p class="text-gray-500">There are currently no applications for this job posting.</p>
                <p class="text-gray-500 mt-1">Share your job posting to attract candidates!</p>
            </div>
        <?php endif; ?>

    <?php elseif (!$job && empty($page_message)): // If $job is null and no overriding page_message is set (e.g. from session for other errors) ?>
        <div class="bg-white shadow-xl rounded-lg p-8 text-center">
            <i class="fas fa-exclamation-triangle fa-4x text-yellow-500 mb-4"></i>
            <h1 class="text-2xl font-bold text-gray-700 mb-3">Job Not Found</h1>
            <p class="text-gray-600 mb-6">The job you are looking for could not be found, or you do not have permission to view it.</p>
            <a href="jobs.php" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2.5 px-5 rounded-md shadow-md transition duration-150 ease-in-out">
                <i class="fas fa-arrow-left mr-2"></i>Back to My Job Postings
            </a>
        </div>
    <?php endif; ?>

</main>

<?php include 'footer.php'; // Assuming a standard footer ?>

</body>
</html>
