<?php
require_once '../includes/header.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !hasRole('employer')) {
    redirect('../login.php', 'You must be logged in as an employer to access this page', 'error');
}

$employerId = $_SESSION['user_id'];

// Check if application ID is provided
$applicationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$applicationId) {
    redirect('applications.php', 'Invalid application ID', 'error');
}

// Get application details
$applicationQuery = "SELECT a.*, 
                        j.title as job_title, 
                        j.company_id, 
                        u.name as applicant_name, 
                        u.email as applicant_email, 
                        u.phone as applicant_phone,
                        u.location as applicant_location,
                        u.avatar_url as applicant_avatar
                    FROM applications a
                    JOIN jobs j ON a.job_id = j.id
                    JOIN users u ON a.user_id = u.id
                    WHERE a.id = ?";
$stmt = $conn->prepare($applicationQuery);
$stmt->bind_param("i", $applicationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('applications.php', 'Application not found', 'error');
}

$application = $result->fetch_assoc();

// Check if company belongs to logged in employer
$companyCheckQuery = "SELECT id FROM companies WHERE id = ? AND employer_id = ?";
$stmt = $conn->prepare($companyCheckQuery);
$stmt->bind_param("ii", $application['company_id'], $employerId);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    redirect('applications.php', 'You do not have permission to view this application', 'error');
}

// Get applicant resume
$resumeQuery = "SELECT resume_url FROM job_seeker_profiles WHERE user_id = ?";
$stmt = $conn->prepare($resumeQuery);
$stmt->bind_param("i", $application['user_id']);
$stmt->execute();
$resumeResult = $stmt->get_result();
$resumeUrl = '';
if ($resumeResult->num_rows > 0) {
    $resumeUrl = $resumeResult->fetch_assoc()['resume_url'];
}

// Get applicant skills
$skillsQuery = "SELECT skill FROM user_skills WHERE user_id = ?";
$stmt = $conn->prepare($skillsQuery);
$stmt->bind_param("i", $application['user_id']);
$stmt->execute();
$skillsResult = $stmt->get_result();
$skills = [];
while ($skill = $skillsResult->fetch_assoc()) {
    $skills[] = $skill['skill'];
}

// Process action if any
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['new_status'])) {
        $newStatus = cleanInput($_POST['new_status']);
        $allowedStatuses = ['Pending', 'Reviewed', 'Shortlisted', 'Interview', 'Rejected', 'Hired'];
        
        if (in_array($newStatus, $allowedStatuses)) {
            $updateQuery = "UPDATE applications SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("si", $newStatus, $applicationId);
            
            if ($stmt->execute()) {
                // Update the application object with new status
                $application['status'] = $newStatus;
                setMessage('success', "Application status updated to {$newStatus}");
            } else {
                setMessage('error', "Error updating application status");
            }
        }
    }
}

// Helper function to format application status
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'Reviewed':
            return 'bg-blue-100 text-blue-800';
        case 'Shortlisted':
            return 'bg-green-100 text-green-800';
        case 'Interview':
            return 'bg-purple-100 text-purple-800';
        case 'Rejected':
            return 'bg-red-100 text-red-800';
        case 'Hired':
            return 'bg-indigo-100 text-indigo-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Breadcrumbs -->
            <nav class="mb-4 text-sm">
                <ol class="flex items-center space-x-2">
                    <li><a href="dashboard.php" class="text-gray-500 hover:text-indigo-600">Dashboard</a></li>
                    <li><span class="text-gray-400 mx-1">/</span></li>
                    <li><a href="applications.php" class="text-gray-500 hover:text-indigo-600">Applications</a></li>
                    <li><span class="text-gray-400 mx-1">/</span></li>
                    <li class="text-gray-800 font-medium truncate">Application from <?= h($application['applicant_name']) ?></li>
                </ol>
            </nav>
            
            <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-wrap items-start justify-between">
                        <div class="flex items-center mb-4 md:mb-0">
                            <?php if (!empty($application['applicant_avatar'])): ?>
                                <img src="<?= h($application['applicant_avatar']) ?>" alt="<?= h($application['applicant_name']) ?>" class="w-16 h-16 object-cover rounded-full mr-4">
                            <?php else: ?>
                                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mr-4">
                                    <span class="text-indigo-600 text-xl font-bold"><?= substr($application['applicant_name'], 0, 1) ?></span>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900"><?= h($application['applicant_name']) ?></h1>
                                <div class="mt-1 text-sm text-gray-500">
                                    <span class="flex items-center mb-1"><i class="fas fa-envelope mr-2"></i> <?= h($application['applicant_email']) ?></span>
                                    <?php if (!empty($application['applicant_phone'])): ?>
                                        <span class="flex items-center mb-1"><i class="fas fa-phone mr-2"></i> <?= h($application['applicant_phone']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($application['applicant_location'])): ?>
                                        <span class="flex items-center"><i class="fas fa-map-marker-alt mr-2"></i> <?= h($application['applicant_location']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="w-full md:w-auto">
                            <div class="inline-block mb-2">
                                <span class="px-3 py-1 rounded-full text-sm font-medium <?= getStatusBadgeClass($application['status']) ?>">
                                    <?= h($application['status']) ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">
                                Applied <?= date('M j, Y', strtotime($application['applied_date'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Applied for: <?= h($application['job_title']) ?></h2>
                    
                    <!-- Status Update Form -->
                    <form method="POST" class="mb-6">
                        <div class="flex flex-wrap items-center">
                            <label for="new_status" class="block text-sm font-medium text-gray-700 mr-3">Change Status:</label>
                            <div class="flex-grow mr-3 max-w-xs">
                                <select id="new_status" name="new_status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="Pending" <?= $application['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Reviewed" <?= $application['status'] == 'Reviewed' ? 'selected' : '' ?>>Reviewed</option>
                                    <option value="Shortlisted" <?= $application['status'] == 'Shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                                    <option value="Interview" <?= $application['status'] == 'Interview' ? 'selected' : '' ?>>Interview</option>
                                    <option value="Rejected" <?= $application['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                    <option value="Hired" <?= $application['status'] == 'Hired' ? 'selected' : '' ?>>Hired</option>
                                </select>
                            </div>
                            <input type="hidden" name="action" value="update_status">
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Update Status
                            </button>
                        </div>
                    </form>
                    
                    <!-- Actions -->
                    <div class="flex flex-wrap gap-3">
                        <?php if (!empty($application['applicant_email'])): ?>
                            <a href="mailto:<?= h($application['applicant_email']) ?>" class="btn btn-outline btn-sm">
                                <i class="fas fa-envelope mr-1"></i> Email Candidate
                            </a>
                        <?php endif; ?>
                        
                        <a href="../messages.php?to=<?= $application['user_id'] ?>" class="btn btn-outline btn-sm">
                            <i class="fas fa-comment mr-1"></i> Message
                        </a>
                        
                        <?php if ($application['status'] == 'Pending' || $application['status'] == 'Reviewed'): ?>
                            <a href="?id=<?= $applicationId ?>&shortlist=1" class="btn btn-success btn-sm">
                                <i class="fas fa-star mr-1"></i> Shortlist
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($application['status'] != 'Rejected'): ?>
                            <a href="?id=<?= $applicationId ?>&reject=1" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this application?')">
                                <i class="fas fa-times mr-1"></i> Reject
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Resume Section -->
                <?php if (!empty($resumeUrl)): ?>
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Resume</h2>
                    <div class="bg-gray-50 p-4 rounded-md">
                        <div class="flex items-center">
                            <div class="p-3 bg-indigo-100 rounded-full mr-4">
                                <i class="fas fa-file-pdf text-indigo-600 text-xl"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="font-medium"><?= basename($resumeUrl) ?></div>
                                <div class="text-sm text-gray-500">Click to download or view</div>
                            </div>
                            <a href="<?= h($resumeUrl) ?>" target="_blank" class="btn btn-outline btn-sm">
                                <i class="fas fa-download mr-1"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Skills Section -->
                <?php if (!empty($skills)): ?>
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Skills</h2>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($skills as $skill): ?>
                            <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm font-medium">
                                <?= h($skill) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Application Notes (if any) -->
                <?php if (!empty($application['notes'])): ?>
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Cover Letter / Notes</h2>
                    <div class="prose max-w-none">
                        <?= nl2br(h($application['notes'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Back button -->
            <div class="flex justify-between">
                <a href="applications.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Applications
                </a>
                
                <a href="../job-details.php?id=<?= $application['job_id'] ?>" class="btn btn-outline">
                    <i class="fas fa-briefcase mr-2"></i>View Job Posting
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
