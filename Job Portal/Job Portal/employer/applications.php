<?php
require_once '../includes/header.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !hasRole('employer')) {
    redirect('../login.php', 'You must be logged in as an employer to access this page', 'error');
}

$employerId = $_SESSION['user_id'];

// Get employer company information
$companyQuery = "SELECT id FROM companies WHERE employer_id = ?";
$stmt = $conn->prepare($companyQuery);
$stmt->bind_param("i", $employerId);
$stmt->execute();
$companyResult = $stmt->get_result();

if ($companyResult->num_rows === 0) {
    redirect('company-profile.php', 'Please create a company profile first', 'warning');
}

$company = $companyResult->fetch_assoc();
$companyId = $company['id'];

// Get filter parameters
$jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$status = isset($_GET['status']) ? cleanInput($_GET['status']) : '';
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? cleanInput($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 20;
$offset = ($page - 1) * $itemsPerPage;

// Build the base query
$query = "SELECT a.*, 
            j.title as job_title, 
            u.name as applicant_name, 
            u.email as applicant_email
          FROM applications a
          JOIN jobs j ON a.job_id = j.id
          JOIN users u ON a.user_id = u.id
          JOIN companies c ON j.company_id = c.id
          WHERE c.employer_id = ?";

$countQuery = "SELECT COUNT(*) as total
               FROM applications a
               JOIN jobs j ON a.job_id = j.id
               JOIN companies c ON j.company_id = c.id
               WHERE c.employer_id = ?";

$params = [$employerId];
$types = "i";

// Add filters
if ($jobId > 0) {
    $query .= " AND a.job_id = ?";
    $countQuery .= " AND a.job_id = ?";
    $params[] = $jobId;
    $types .= "i";
}

if (!empty($status)) {
    $query .= " AND a.status = ?";
    $countQuery .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($search)) {
    $searchTerm = "%{$search}%";
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR j.title LIKE ?)";
    $countQuery .= " AND (u.name LIKE ? OR u.email LIKE ? OR j.title LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// Add sorting
if ($sortBy == 'oldest') {
    $query .= " ORDER BY a.applied_date ASC";
} elseif ($sortBy == 'job_title') {
    $query .= " ORDER BY j.title ASC";
} elseif ($sortBy == 'applicant') {
    $query .= " ORDER BY u.name ASC";
} elseif ($sortBy == 'status') {
    $query .= " ORDER BY a.status ASC";
} else {
    // Default to newest
    $query .= " ORDER BY a.applied_date DESC";
}

// Get total count for pagination
$stmt = $conn->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalResults = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalResults / $itemsPerPage);

// Add pagination
$query .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $itemsPerPage;
$types .= "ii";

// Execute query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get jobs for filter dropdown
$jobsQuery = "SELECT j.id, j.title
              FROM jobs j
              JOIN companies c ON j.company_id = c.id
              WHERE c.employer_id = ?
              ORDER BY j.title";
$stmt = $conn->prepare($jobsQuery);
$stmt->bind_param("i", $employerId);
$stmt->execute();
$jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Applications</h1>
            <a href="dashboard.php" class="btn btn-outline text-sm">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
        
        <!-- Filters -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Filter Applications</h2>
                <form action="applications.php" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label for="job_id" class="block text-sm font-medium text-gray-700 mb-1">Job</label>
                        <select id="job_id" name="job_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Jobs</option>
                            <?php foreach ($jobs as $job): ?>
                                <option value="<?= $job['id'] ?>" <?= $jobId == $job['id'] ? 'selected' : '' ?>><?= h($job['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Reviewed" <?= $status == 'Reviewed' ? 'selected' : '' ?>>Reviewed</option>
                            <option value="Shortlisted" <?= $status == 'Shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                            <option value="Interview" <?= $status == 'Interview' ? 'selected' : '' ?>>Interview</option>
                            <option value="Rejected" <?= $status == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="Hired" <?= $status == 'Hired' ? 'selected' : '' ?>>Hired</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" id="search" name="search" value="<?= h($search) ?>" 
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Search by name, email, job title...">
                    </div>
                    
                    <div>
                        <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select id="sort" name="sort" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= $sortBy == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="job_title" <?= $sortBy == 'job_title' ? 'selected' : '' ?>>Job Title</option>
                            <option value="applicant" <?= $sortBy == 'applicant' ? 'selected' : '' ?>>Applicant Name</option>
                            <option value="status" <?= $sortBy == 'status' ? 'selected' : '' ?>>Status</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-2 lg:col-span-4 flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Applications List -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
            <?php if (empty($applications)): ?>
                <div class="p-8 text-center">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-file-alt fa-4x"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No applications found</h3>
                    <p class="text-gray-600 mb-4">
                        <?php if (!empty($search) || !empty($status) || $jobId > 0): ?>
                            No applications match your current filters. Try changing your search criteria.
                        <?php else: ?>
                            You haven't received any job applications yet.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search) || !empty($status) || $jobId > 0): ?>
                        <a href="applications.php" class="text-indigo-600 hover:text-indigo-800 font-medium">
                            Clear all filters <i class="fas fa-times ml-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($applications as $application): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900"><?= h($application['applicant_name']) ?></div>
                                            <div class="text-sm text-gray-500 ml-2">(<?= h($application['applicant_email']) ?>)</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= h($application['job_title']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= getStatusBadgeClass($application['status']) ?>">
                                            <?= h($application['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($application['applied_date'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="view-application.php?id=<?= $application['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <div class="dropdown inline-block relative">
                                            <button class="text-gray-600 hover:text-gray-900 focus:outline-none">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 hidden z-10">
                                                <?php if ($application['status'] == 'Pending'): ?>
                                                    <a href="update-application-status.php?id=<?= $application['id'] ?>&status=Reviewed" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-check mr-2"></i> Mark as Reviewed
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($application['status'] != 'Shortlisted'): ?>
                                                    <a href="update-application-status.php?id=<?= $application['id'] ?>&status=Shortlisted" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-star mr-2"></i> Shortlist
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($application['status'] != 'Interview'): ?>
                                                    <a href="update-application-status.php?id=<?= $application['id'] ?>&status=Interview" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-user-tie mr-2"></i> Schedule Interview
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($application['status'] != 'Hired'): ?>
                                                    <a href="update-application-status.php?id=<?= $application['id'] ?>&status=Hired" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-check-circle mr-2"></i> Hire
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($application['status'] != 'Rejected'): ?>
                                                    <a href="update-application-status.php?id=<?= $application['id'] ?>&status=Rejected" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                        <i class="fas fa-times-circle mr-2"></i> Reject
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mb-8">
            <nav class="inline-flex rounded-md shadow-sm -space-x-px">
                <!-- Previous Page -->
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Previous</span>
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                        <span class="sr-only">Previous</span>
                        <i class="fas fa-chevron-left"></i>
                    </span>
                <?php endif; ?>
                
                <!-- Page Numbers -->
                <?php 
                $startPage = max(1, min($page - 2, $totalPages - 4));
                $endPage = min($totalPages, max(5, $page + 2));
                
                for ($i = $startPage; $i <= $endPage; $i++): 
                ?>
                    <?php if ($i == $page): ?>
                        <span class="relative inline-flex items-center px-4 py-2 border border-indigo-500 bg-indigo-50 text-sm font-medium text-indigo-600">
                            <?= $i ?>
                        </span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <!-- Next Page -->
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                        <span class="sr-only">Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Initialize dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        const dropdowns = document.querySelectorAll('.dropdown');
        
        dropdowns.forEach(dropdown => {
            const button = dropdown.querySelector('button');
            const menu = dropdown.querySelector('.dropdown-menu');
            
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            const menus = document.querySelectorAll('.dropdown-menu');
            menus.forEach(menu => {
                menu.classList.add('hidden');
            });
        });
    });
</script>

<?php
// Helper function to format status badges
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

require_once '../includes/footer.php';
?>
