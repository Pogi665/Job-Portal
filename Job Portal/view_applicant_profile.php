<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    // Consider logging this error to a file in a real application
    die("Connection failed. Please try again later.");
}

$page_error = "";
$application = null;
$job_title_page_variable = "Applicant Profile"; // Default title, renamed to avoid conflict

$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$applicant_username = isset($_GET['applicant']) ? trim($_GET['applicant']) : '';

if ($job_id <= 0 || empty($applicant_username)) {
    $page_error = "Invalid job ID or applicant identifier.";
} else {
    // Check if the logged-in user is the employer for this job
    $job_check_stmt = $conn->prepare("SELECT title, employer_username FROM jobs WHERE id = ?");
    if (!$job_check_stmt) {
        $page_error = "Error preparing statement: " . $conn->error;
    } else {
        $job_check_stmt->bind_param("i", $job_id);
        $job_check_stmt->execute();
        $job_result = $job_check_stmt->get_result();

        if ($job_result->num_rows > 0) {
            $job_data = $job_result->fetch_assoc();
            $job_title_page_variable = htmlspecialchars($job_data['title']);
            if ($job_data['employer_username'] !== $_SESSION['username']) {
                $page_error = "You are not authorized to view this applicant's profile.";
            }
        } else {
            $page_error = "Job not found.";
        }
        $job_check_stmt->close();
    }

    if (empty($page_error)) {
        // Fetch the applicant's data
        // Assuming 'applicant' in job_applications table stores the username of the applicant
        $query = "SELECT ja.*, u.full_name as applicant_full_name, u.email as applicant_email, u.contact_number as applicant_contact_number FROM job_applications ja JOIN users u ON ja.applicant_username = u.username WHERE ja.job_id = ? AND ja.applicant_username = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            $page_error = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("is", $job_id, $applicant_username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $application = $result->fetch_assoc();
            } else {
                $page_error = "Applicant data not found for this job.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $job_title_page_variable . " - Applicant: " . htmlspecialchars($applicant_username); ?></title>
    <!-- header.php includes Tailwind CSS, Font Awesome and style.css -->
</head>
<body class="bg-gray-100 font-sans">
<?php include 'header.php'; ?>

<main class="container mx-auto mt-8 mb-12 px-4">
    <?php if (!empty($page_error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"><?php echo htmlspecialchars($page_error); ?></span>
        </div>
        <div class="text-center">
             <a href="dashboard.php" class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                Go to Dashboard
            </a>
        </div>
    <?php elseif ($application): ?>
        <div class="bg-white shadow-xl rounded-lg p-6 md:p-8">
            <div class="flex flex-col md:flex-row items-center md:items-start mb-6 pb-6 border-b border-gray-200">
                <!-- Placeholder for applicant avatar - could be a generic icon or fetched if available -->
                <div class="w-24 h-24 bg-gray-300 rounded-full flex items-center justify-center text-gray-500 text-4xl mb-4 md:mb-0 md:mr-6">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($application['applicant_full_name']); ?></h1>
                    <p class="text-gray-600 text-lg">Viewing application for: <span class="font-semibold text-blue-600"><?php echo $job_title_page_variable; ?></span></p>
                     <p class="text-sm text-gray-500">Applied on: <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($application['application_date']))); ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-700 mb-3">Contact Information</h2>
                    <p class="text-gray-600 mb-1"><strong class="font-medium">Email:</strong> <?php echo htmlspecialchars($application['applicant_email']); ?></p>
                    <p class="text-gray-600"><strong class="font-medium">Phone:</strong> <?php echo htmlspecialchars($application['applicant_contact_number'] ?: 'Not Provided'); ?></p>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-700 mb-3">Resume</h2>
                    <?php if (!empty($application['resume_url'])): ?>
                        <a href="<?php echo htmlspecialchars($application['resume_url']); ?>" target="_blank" class="inline-block bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded transition duration-300 ease-in-out">
                            <i class="fas fa-file-alt mr-2"></i>View Resume
                        </a>
                    <?php else: ?>
                        <p class="text-gray-500">No resume provided.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-3">Cover Letter</h2>
                <div class="bg-gray-50 p-4 rounded-lg prose max-w-none">
                    <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                </div>
            </div>
            
            <div class="mt-8 text-center space-x-4">
                <a href="jobs.php" class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Job Applicants
                </a>
                 <?php if (isset($application['status']) && $application['status'] == 'pending'): ?>
                    <form action="reject_applicant.php" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to reject this applicant?');">
                        <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                        <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                         <input type="hidden" name="applicant_username" value="<?php echo htmlspecialchars($applicant_username); ?>">
                        <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                            <i class="fas fa-times-circle mr-2"></i>Reject Applicant
                        </button>
                    </form>
                 <?php elseif (isset($application['status']) && $application['status'] == 'rejected'): ?>
                     <p class="text-red-500 font-semibold inline-block ml-4"><i class="fas fa-exclamation-triangle mr-2"></i>Applicant Rejected</p>
                 <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php
// It's good practice to close the connection if it's still open.
// However, PHP usually closes it automatically at the end of the script.
if ($conn) {
    $conn->close();
}
?>
</body>
</html>
