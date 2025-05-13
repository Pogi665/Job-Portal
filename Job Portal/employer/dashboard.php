<?php
require_once '../includes/header.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !hasRole('employer')) {
    redirect('../login.php', 'You must be logged in as an employer to access this page', 'error');
}

$employerId = $_SESSION['user_id'];

// Get employer's company information
$companyQuery = "SELECT id, name, logo_url FROM companies WHERE employer_id = ?";
$stmt = $conn->prepare($companyQuery);
$stmt->bind_param("i", $employerId);
$stmt->execute();
$companyResult = $stmt->get_result();

// Check if employer has company profile
$hasCompanyProfile = ($companyResult->num_rows > 0);
$companyId = 0;

if ($hasCompanyProfile) {
    $company = $companyResult->fetch_assoc();
    $companyId = $company['id'];
}

// Get job statistics
$stats = [
    'active_jobs' => 0,
    'total_applications' => 0,
    'unreviewed_applications' => 0,
    'total_views' => 0
];

if ($hasCompanyProfile) {
    // Active jobs count
    $activeJobsQuery = "SELECT COUNT(*) as count FROM jobs WHERE company_id = ? AND status = 'Active'";
    $stmt = $conn->prepare($activeJobsQuery);
    $stmt->bind_param("i", $companyId);
    $stmt->execute();
    $stats['active_jobs'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get all job IDs for this employer
    $jobsQuery = "SELECT id FROM jobs WHERE company_id = ?";
    $stmt = $conn->prepare($jobsQuery);
    $stmt->bind_param("i", $companyId);
    $stmt->execute();
    $jobsResult = $stmt->get_result();
    $jobIds = [];
    
    while ($job = $jobsResult->fetch_assoc()) {
        $jobIds[] = $job['id'];
    }
    
    if (!empty($jobIds)) {
        // Total applications count
        $placeholders = str_repeat('?,', count($jobIds) - 1) . '?';
        $applicationsQuery = "SELECT COUNT(*) as count FROM applications WHERE job_id IN ($placeholders)";
        $stmt = $conn->prepare($applicationsQuery);
        $types = str_repeat('i', count($jobIds));
        $stmt->bind_param($types, ...$jobIds);
        $stmt->execute();
        $stats['total_applications'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Unreviewed applications count
        $unreviewedQuery = "SELECT COUNT(*) as count FROM applications WHERE job_id IN ($placeholders) AND status = 'Pending'";
        $stmt = $conn->prepare($unreviewedQuery);
        $stmt->bind_param($types, ...$jobIds);
        $stmt->execute();
        $stats['unreviewed_applications'] = $stmt->get_result()->fetch_assoc()['count'];
        
        // Total job views count
        $viewsQuery = "SELECT SUM(view_count) as total FROM jobs WHERE company_id = ?";
        $stmt = $conn->prepare($viewsQuery);
        $stmt->bind_param("i", $companyId);
        $stmt->execute();
        $viewsResult = $stmt->get_result()->fetch_assoc();
        $stats['total_views'] = $viewsResult['total'] ?? 0;
    }
}

// Get recent job listings
$recentJobs = [];
if ($hasCompanyProfile) {
    $recentJobsQuery = "SELECT j.*, 
                           (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as application_count
                       FROM jobs j 
                       WHERE j.company_id = ? 
                       ORDER BY j.posted_date DESC 
                       LIMIT 5";
    $stmt = $conn->prepare($recentJobsQuery);
    $stmt->bind_param("i", $companyId);
    $stmt->execute();
    $recentJobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get recent applications
$recentApplications = [];
if ($hasCompanyProfile && !empty($jobIds)) {
    $placeholders = str_repeat('?,', count($jobIds) - 1) . '?';
    $recentApplicationsQuery = "SELECT a.*, j.title as job_title, u.name as applicant_name, u.email as applicant_email
                               FROM applications a 
                               JOIN jobs j ON a.job_id = j.id
                               JOIN users u ON a.user_id = u.id
                               WHERE a.job_id IN ($placeholders)
                               ORDER BY a.application_date DESC
                               LIMIT 10";
    $stmt = $conn->prepare($recentApplicationsQuery);
    $types = str_repeat('i', count($jobIds));
    $stmt->bind_param($types, ...$jobIds);
    $stmt->execute();
    $recentApplications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <?php if (!$hasCompanyProfile): ?>
            <div class="max-w-3xl mx-auto bg-white shadow-md rounded-lg p-6 mb-6">
                <div class="text-center py-8">
                    <div class="text-indigo-600 mb-4">
                        <i class="fas fa-building fa-4x"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Complete Your Company Profile</h2>
                    <p class="text-gray-600 mb-6">You need to create a company profile before you can post jobs and access all employer features.</p>
                    <a href="company-profile.php" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-plus-circle mr-2"></i>Create Company Profile
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <div class="flex items-center mb-4 md:mb-0">
                    <?php if (!empty($company['logo_url'])): ?>
                        <img src="<?= h($company['logo_url']) ?>" alt="<?= h($company['name']) ?>" class="w-16 h-16 object-contain mr-4 rounded-lg border border-gray-200">
                    <?php else: ?>
                        <div class="w-16 h-16 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                            <span class="text-indigo-600 text-2xl font-bold"><?= substr($company['name'], 0, 1) ?></span>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900"><?= h($company['name']) ?> Dashboard</h1>
                        <p class="text-gray-600">Manage your job listings and applications</p>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <a href="company-profile.php" class="btn btn-outline text-sm">
                        <i class="fas fa-edit mr-2"></i>Edit Profile
                    </a>
                    <a href="post-job.php" class="btn btn-primary text-sm">
                        <i class="fas fa-plus-circle mr-2"></i>Post a Job
                    </a>
                </div>
            </div>
            
            <!-- Dashboard stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                            <i class="fas fa-briefcase fa-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Active Jobs</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?= number_format($stats['active_jobs']) ?></h3>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="jobs.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            View all jobs <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-file-alt fa-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Total Applications</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_applications']) ?></h3>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="applications.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            View all applications <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                            <i class="fas fa-exclamation-circle fa-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Pending Review</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?= number_format($stats['unreviewed_applications']) ?></h3>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="applications.php?status=Pending" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            Review applications <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-eye fa-lg"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Total Job Views</p>
                            <h3 class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_views']) ?></h3>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="analytics.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            View analytics <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent job listings -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-800">Recent Job Listings</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php if (empty($recentJobs)): ?>
                            <div class="p-6 text-center">
                                <p class="text-gray-500">You haven't posted any jobs yet.</p>
                                <a href="post-job.php" class="mt-3 inline-block text-indigo-600 hover:text-indigo-800 font-medium">
                                    Post your first job <i class="fas fa-plus-circle ml-1"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentJobs as $job): ?>
                                <div class="p-4 hover:bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <a href="../job-details.php?id=<?= $job['id'] ?>" class="text-lg font-semibold text-gray-800 hover:text-indigo-600">
                                                <?= h($job['title']) ?>
                                            </a>
                                            <p class="text-gray-600 text-sm"><?= h($job['location']) ?> · <?= h($job['type']) ?></p>
                                            <div class="flex items-center mt-1 text-sm">
                                                <span class="text-gray-500 flex items-center mr-4">
                                                    <i class="fas fa-file-alt mr-1"></i> <?= $job['application_count'] ?> applications
                                                </span>
                                                <span class="text-gray-500 flex items-center mr-4">
                                                    <i class="fas fa-eye mr-1"></i> <?= $job['view_count'] ?? 0 ?> views
                                                </span>
                                                <span class="text-gray-500">
                                                    Posted <?= time_elapsed_string($job['posted_date']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?= $job['status'] == 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                <?= h($job['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex mt-2 text-sm">
                                        <a href="edit-job.php?id=<?= $job['id'] ?>" class="text-indigo-600 hover:text-indigo-800 mr-4">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                        <a href="view-applications.php?job_id=<?= $job['id'] ?>" class="text-indigo-600 hover:text-indigo-800 mr-4">
                                            <i class="fas fa-users mr-1"></i> View Applications
                                        </a>
                                        <a href="toggle-job-status.php?id=<?= $job['id'] ?>&status=<?= $job['status'] == 'Active' ? 'Inactive' : 'Active' ?>" 
                                           class="text-<?= $job['status'] == 'Active' ? 'yellow' : 'green' ?>-600 hover:text-<?= $job['status'] == 'Active' ? 'yellow' : 'green' ?>-800">
                                            <i class="fas fa-<?= $job['status'] == 'Active' ? 'pause' : 'play' ?> mr-1"></i>
                                            <?= $job['status'] == 'Active' ? 'Pause' : 'Activate' ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="bg-gray-50 px-6 py-3 text-right">
                                <a href="jobs.php" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    View all jobs <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent applications -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-800">Recent Applications</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php if (empty($recentApplications)): ?>
                            <div class="p-6 text-center">
                                <p class="text-gray-500">No applications have been received yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentApplications as $application): ?>
                                <div class="p-4 hover:bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-gray-800">
                                                <?= h($application['applicant_name']) ?>
                                                <span class="text-gray-500 font-normal"> applied for </span>
                                                <a href="../job-details.php?id=<?= $application['job_id'] ?>" class="text-indigo-600 hover:text-indigo-800">
                                                    <?= h($application['job_title']) ?>
                                                </a>
                                            </p>
                                            <p class="text-gray-600 text-sm"><?= h($application['applicant_email']) ?></p>
                                            <p class="text-gray-500 text-sm">Applied <?= time_elapsed_string($application['application_date']) ?></p>
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?php
                                                switch ($application['status']) {
                                                    case 'Pending':
                                                        echo 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                    case 'Reviewed':
                                                        echo 'bg-blue-100 text-blue-800';
                                                        break;
                                                    case 'Shortlisted':
                                                        echo 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'Rejected':
                                                        echo 'bg-red-100 text-red-800';
                                                        break;
                                                    default:
                                                        echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?= h($application['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex mt-2 text-sm">
                                        <a href="view-application.php?id=<?= $application['id'] ?>" class="text-indigo-600 hover:text-indigo-800 mr-4">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </a>
                                        <?php if ($application['status'] == 'Pending'): ?>
                                        <a href="update-application-status.php?id=<?= $application['id'] ?>&status=Reviewed" class="text-blue-600 hover:text-blue-800 mr-4">
                                            <i class="fas fa-check mr-1"></i> Mark as Reviewed
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($application['status'] != 'Shortlisted'): ?>
                                        <a href="update-application-status.php?id=<?= $application['id'] ?>&status=Shortlisted" class="text-green-600 hover:text-green-800 mr-4">
                                            <i class="fas fa-star mr-1"></i> Shortlist
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($application['status'] != 'Rejected'): ?>
                                        <a href="update-application-status.php?id=<?= $application['id'] ?>&status=Rejected" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-times mr-1"></i> Reject
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="bg-gray-50 px-6 py-3 text-right">
                                <a href="applications.php" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    View all applications <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper function to calculate time elapsed
function time_elapsed_string($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) {
        return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    }
    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 6) {
        return floor($diff->d / 7) . ' week' . (floor($diff->d / 7) > 1 ? 's' : '') . ' ago';
    }
    if ($diff->d > 0) {
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    }
    if ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
    if ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    return 'just now';
}

require_once '../includes/footer.php';
?> 