<?php
require_once 'admin_header.php';
require_once '../database_connection.php';

$job_id = $_GET['id'] ?? null;
$job = null;
$page_form_title = "Edit Job Posting"; // For the heading
$message = $_SESSION['job_action_message'] ?? ''; // Check for messages from redirect
$message_type = $_SESSION['job_action_message_type'] ?? ''; // 'success' or 'error'

// Clear session messages after displaying them
if (isset($_SESSION['job_action_message'])) unset($_SESSION['job_action_message']);
if (isset($_SESSION['job_action_message_type'])) unset($_SESSION['job_action_message_type']);

// Define available job statuses (can be expanded based on your needs)
$available_statuses = ['active', 'inactive', 'pending_approval', 'expired', 'filled', 'rejected']; 
// You might want to fetch these from a dedicated table or have them more configurable

if (!$job_id || !filter_var($job_id, FILTER_VALIDATE_INT)) {
    // Store message in session to display on manage_jobs.php
    $_SESSION['job_action_message'] = 'Invalid job ID provided for editing.';
    $_SESSION['job_action_message_type'] = 'error';
    header("Location: manage_jobs.php");
    exit;
}

if ($conn->connect_error) {
    $message = "Database connection failed: " . $conn->connect_error;
    $message_type = 'error';
} else {
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job'])) {
        // Sanitize and validate inputs
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $job_type = trim($_POST['job_type'] ?? '');
        $salary = trim($_POST['salary'] ?? '');
        $status = $_POST['status'] ?? '';
        // $employer_username remains unchanged through this form for now, could be made editable

        // Basic validation
        if (empty($title) || empty($description) || empty($company) || empty($location)) {
            $message = "Title, Description, Company, and Location are required.";
            $message_type = 'error';
        } elseif (!in_array($status, $available_statuses)) {
            $message = "Invalid status selected.";
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE jobs SET title = ?, description = ?, company = ?, location = ?, job_type = ?, salary = ?, status = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssssssi", $title, $description, $company, $location, $job_type, $salary, $status, $job_id);
                if ($stmt->execute()) {
                    $message = "Job posting updated successfully!";
                    $message_type = 'success';
                    $_SESSION['job_action_message'] = $message;
                    $_SESSION['job_action_message_type'] = $message_type;
                } else {
                    $message = "Error updating job posting: " . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
            } else {
                $message = "Error preparing statement: " . $conn->error;
                $message_type = 'error';
            }
        }
    }

    // Fetch job details for the form (even after update attempt to show current data)
    $stmt_fetch = $conn->prepare("SELECT id, title, description, employer_username, company, location, job_type, salary, status, rejection_reason, timestamp FROM jobs WHERE id = ?");
    if ($stmt_fetch) {
        $stmt_fetch->bind_param("i", $job_id);
        $stmt_fetch->execute();
        $result = $stmt_fetch->get_result();
        if ($result->num_rows === 1) {
            $job = $result->fetch_assoc();
        } else {
            if (empty($message)) { // Only set this message if no other error/success message is already set
                $message = "Job posting not found.";
                $message_type = 'error';
            }
            $job = null; // Clear $job if not found
        }
        $stmt_fetch->close();
    } else {
        $message = "Error fetching job details: " . $conn->error;
        $message_type = 'error';
    }
}

?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6 pb-2 border-b-2 border-gray-300">
        <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($page_form_title); ?></h1>
        <a href="manage_jobs.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-md text-sm transition duration-150 ease-in-out flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Back to Manage Jobs
        </a>
    </div>

    <?php if ($message && $message_type === 'success'): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php elseif ($message && $message_type === 'error'): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($job): ?>
    <form action="edit_job_admin.php?id=<?php echo htmlspecialchars($job_id); ?>" method="POST" class="bg-white shadow-xl rounded-lg px-8 pt-6 pb-8 mb-4">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="job_id_display" class="block text-gray-700 text-sm font-bold mb-2">Job ID:</label>
                <input type="text" id="job_id_display" value="<?php echo htmlspecialchars($job['id']); ?>" disabled class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight bg-gray-100 cursor-not-allowed">
            </div>
            <div>
                <label for="employer_username" class="block text-gray-700 text-sm font-bold mb-2">Employer Username:</label>
                <input type="text" id="employer_username" value="<?php echo htmlspecialchars($job['employer_username']); ?>" disabled class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight bg-gray-100 cursor-not-allowed">
            </div>
        </div>

        <div class="mb-6">
            <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Job Title <span class="text-red-500">*</span></label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($job['title'] ?? ($_POST['title'] ?? '')); ?>" required class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>

        <div class="mb-6">
            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description <span class="text-red-500">*</span></label>
            <textarea id="description" name="description" rows="6" required class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($job['description'] ?? ($_POST['description'] ?? '')); ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="company" class="block text-gray-700 text-sm font-bold mb-2">Company <span class="text-red-500">*</span></label>
                <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($job['company'] ?? ($_POST['company'] ?? '')); ?>" required class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label for="location" class="block text-gray-700 text-sm font-bold mb-2">Location <span class="text-red-500">*</span></label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($job['location'] ?? ($_POST['location'] ?? '')); ?>" required class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label for="job_type" class="block text-gray-700 text-sm font-bold mb-2">Job Type:</label>
                <input type="text" id="job_type" name="job_type" value="<?php echo htmlspecialchars($job['job_type'] ?? ($_POST['job_type'] ?? '')); ?>" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., Full-time, Part-time">
            </div>
            <div>
                <label for="salary" class="block text-gray-700 text-sm font-bold mb-2">Salary:</label>
                <input type="text" id="salary" name="salary" value="<?php echo htmlspecialchars($job['salary'] ?? ($_POST['salary'] ?? '')); ?>" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="e.g., $50,000 - $70,000 per year">
            </div>
        </div>

        <div class="mb-6">
            <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Status <span class="text-red-500">*</span></label>
            <select name="status" id="status" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <?php foreach ($available_statuses as $status_value): ?>
                    <option value="<?php echo htmlspecialchars($status_value); ?>" <?php echo (($job['status'] ?? ($_POST['status'] ?? '')) === $status_value) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $status_value))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($job['status'] === 'rejected' && !empty($job['rejection_reason'])): ?>
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-300 rounded-md">
            <h4 class="font-semibold text-yellow-800 mb-1"><i class="fas fa-info-circle mr-1"></i>Rejection Reason:</h4>
            <p class="text-sm text-yellow-700"><?php echo nl2br(htmlspecialchars($job['rejection_reason'])); ?></p>
            <p class="text-xs text-yellow-600 mt-2">Note: Changing status from 'rejected' via this form will not clear this reason. To modify the reason, use the dedicated rejection process.</p>
        </div>
        <?php endif; ?>

        <div class="flex items-center justify-start pt-6 border-t border-gray-200 mt-6">
            <button type="submit" name="update_job" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-md focus:outline-none focus:shadow-outline flex items-center transition duration-150 ease-in-out">
                <i class="fas fa-save mr-2"></i>Update Job Posting
            </button>
        </div>
    </form>
    <?php elseif (!$message && !$job): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mt-6" role="alert">
            <strong class="font-bold">Not Found:</strong>
            <span class="block sm:inline">Job posting not found or could not be loaded.</span>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'admin_footer.php';
?> 