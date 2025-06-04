<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'application_submitted':
            $success_message = 'Your application has been submitted successfully!';
            break;
        // Add more success cases here if needed
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'db_connection_critical':
            $error_message = 'The service is temporarily unavailable due to a database connection issue. Please try again later.';
            break;
        case 'user_info_fetch_failed':
            $error_message = 'Could not retrieve your user information. Please try logging out and back in.';
            break;
        case 'user_not_found_critical':
            $error_message = 'Your user account could not be verified. Please contact support.';
            break;
        case 'invalid_job_id':
            $error_message = 'The job you were trying to access is invalid or no longer available.';
            break;
        // Add more error cases here from other pages that might redirect here
        default:
            $error_message = 'An unexpected error occurred. Please try again.';
            break;
    }
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    error_log("Dashboard DB Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
    // Set error message for display, then exit if connection fails as no DB operations can proceed.
    $error_message = 'The service is critically unavailable. Please try again later or contact support.';
    // We can't proceed to render the rest of the page if DB is down.
    // So, we show a minimal page or just this error.
    // For this implementation, we'll let it fall through to display the error in the main layout, but ideally, a more robust error page would be shown.
} else {
    $username = $_SESSION["username"];
    $role = $_SESSION["role"]; // Assumes role is stored in session

    // Get the search term if available
    $searchTerm = isset($_POST['search']) ? $_POST['search'] : '';

    // Modify the query to filter by the search term and only show active jobs
    $jobsQuery = "
        SELECT * 
        FROM jobs 
        WHERE 
            status = 'active' 
            AND id NOT IN (SELECT job_id FROM job_applications WHERE applicant_username = ?) 
            AND (title LIKE ? OR employer_username LIKE ?) 
        ORDER BY timestamp DESC
    ";

    $stmt = $conn->prepare($jobsQuery);
    if ($stmt) {
        $searchTermLike = '%' . $searchTerm . '%';
        $stmt->bind_param("sss", $username, $searchTermLike, $searchTermLike);
        if (!$stmt->execute()) {
            error_log("Dashboard jobs query execution failed: (" . $stmt->errno . ") " . $stmt->error);
            $error_message = "Error fetching job listings. Please try again.";
            $jobsResult = false; // Indicate failure
        } else {
            $jobsResult = $stmt->get_result();
        }
        $stmt->close();
    } else {
        error_log("Dashboard jobs query prepare failed: (" . $conn->errno . ") " . $conn->error);
        $error_message = "Error preparing to fetch job listings. Please try again.";
        $jobsResult = false; // Indicate failure
    }
    // $conn->close(); // Close connection at the end of the script or when no longer needed
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - CareerLynk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- style.css is included via header.php, which provides base styles and Inter font -->
    <style>
        /* Minimal specific styles if needed, prefer Tailwind classes */
    </style>
</head>
<body class="bg-gray-100">
<?php include 'header.php'; ?>

<div class="container mx-auto mt-8 mb-10 px-4 sm:px-6 lg:px-8">
    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">Available Job Listings</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success mb-6" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message) && !($conn->connect_error && $error_message === 'The service is critically unavailable. Please try again later or contact support.')) : // Show specific errors, but not general DB down if a critical message is already set ?>
            <div class="alert alert-danger mb-6" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($conn) && $conn->connect_error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-6 rounded-md shadow-md" role="alert">
                <div class="flex">
                    <div class="py-1"><i class="fas fa-exclamation-triangle fa-2x mr-3"></i></div>
                    <div>
                        <p class="font-bold">Service Unavailable</p>
                        <p class="text-sm"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" class="mb-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                <input type="text" name="search" placeholder="Search jobs by title or employer..." 
                       value="<?php echo isset($searchTerm) ? htmlspecialchars($searchTerm) : ''; ?>" 
                       class="w-full sm:w-2/3 lg:w-1/2 px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" />
                <button type="submit" 
                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        <i class="fas fa-search mr-2"></i>Search
                </button>
            </form>

            <?php if ($jobsResult && $jobsResult->num_rows > 0): ?>
                <div class="space-y-6">
                <?php while ($job = $jobsResult->fetch_assoc()): ?>
                    <div class="bg-white p-6 rounded-lg shadow-lg border border-gray-200 hover:shadow-xl transition-shadow duration-300">
                        <h2 class="text-2xl font-semibold text-blue-700 hover:text-blue-800 mb-2">
                            <a href="view_job_posting.php?job_id=<?php echo $job['id']; ?>"><?php echo htmlspecialchars($job['title']); ?></a>
                        </h2>
                        <p class="text-lg text-gray-700 mb-1">At <span class="font-semibold"><?php echo htmlspecialchars($job['company']); ?></span></p>
                        <p class="text-sm text-gray-500 mb-3"><i class="fas fa-map-marker-alt mr-1.5 text-gray-400"></i><?php echo htmlspecialchars($job['location'] ?? 'Not specified'); ?></p>
                        <div class="text-gray-600 text-sm mb-3 leading-relaxed job-description-preview">
                            <?php 
                                $description_preview = strip_tags($job['description']);
                                echo nl2br(htmlspecialchars(mb_strimwidth($description_preview, 0, 220, "..."))); 
                            ?>
                        </div>
                        <p class="text-xs text-gray-400 mb-4">Posted on: <?php echo date("F j, Y, g:i a", strtotime($job['timestamp'])); ?></p>

                        <?php if (isset($role) && $role === 'job_seeker'): ?>
                            <div class="mt-4 text-right">
                                <a href="view_job_posting.php?job_id=<?php echo $job['id']; ?>"
                                   class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                                    View Details <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
                </div>
            <?php elseif (isset($conn) && !$conn->connect_error): ?>
                <div class="text-center bg-white p-10 rounded-lg shadow-md border border-gray-200">
                    <i class="fas fa-search-dollar fa-4x text-gray-400 mb-6"></i>
                    <p class="text-xl text-gray-700 font-semibold mb-2">No Job Listings Found</p>
                    <p class="text-gray-500">No job listings available that match your search criteria, or you may have applied to all available jobs.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<?php 
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>
</body>
</html>
