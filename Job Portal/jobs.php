<?php
require_once 'includes/header.php';

// Get search parameters
$keywords = isset($_GET['keywords']) ? cleanInput($_GET['keywords']) : '';
$location = isset($_GET['location']) ? cleanInput($_GET['location']) : '';
$type = isset($_GET['type']) ? cleanInput($_GET['type']) : '';
$category = isset($_GET['category']) ? cleanInput($_GET['category']) : '';
$experience = isset($_GET['experience']) ? cleanInput($_GET['experience']) : '';
$sortBy = isset($_GET['sort']) ? cleanInput($_GET['sort']) : 'newest';
$min_salary = isset($_GET['min_salary']) ? (int)cleanInput($_GET['min_salary']) : 0;
$max_salary = isset($_GET['max_salary']) ? (int)cleanInput($_GET['max_salary']) : 0;
$salary_period = isset($_GET['salary_period']) ? cleanInput($_GET['salary_period']) : '';
$page = isset($_GET['page']) ? (int)cleanInput($_GET['page']) : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Build base SQL query
$sql = "SELECT j.*, c.name as company_name, c.logo_url 
        FROM jobs j 
        JOIN companies c ON j.company_id = c.id 
        WHERE j.status = 'Active'";

// Prepare parameter array
$params = [];
$types = "";

// Add search filters if provided
if (!empty($keywords)) {
    $keywordsLike = "%{$keywords}%";
    $sql .= " AND (j.title LIKE ? OR j.description LIKE ? OR c.name LIKE ? OR EXISTS (
                SELECT 1 FROM job_skills js WHERE js.job_id = j.id AND js.skill LIKE ?
              ))";
    $params[] = $keywordsLike;
    $params[] = $keywordsLike;
    $params[] = $keywordsLike;
    $params[] = $keywordsLike;
    $types .= "ssss";
}

if (!empty($location)) {
    $locationLike = "%{$location}%";
    $sql .= " AND j.location LIKE ?";
    $params[] = $locationLike;
    $types .= "s";
}

if (!empty($type)) {
    $sql .= " AND j.type = ?";
    $params[] = $type;
    $types .= "s";
}

if (!empty($category)) {
    $sql .= " AND j.category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($experience)) {
    $sql .= " AND j.experience_level = ?";
    $params[] = $experience;
    $types .= "s";
}

if ($min_salary > 0) {
    $sql .= " AND j.salary_min >= ?";
    $params[] = $min_salary;
    $types .= "i";
}

if ($max_salary > 0) {
    $sql .= " AND j.salary_max <= ?";
    $params[] = $max_salary;
    $types .= "i";
}

if (!empty($salary_period)) {
    $sql .= " AND j.salary_period = ?";
    $params[] = $salary_period;
    $types .= "s";
}

// Add sorting
if ($sortBy == 'oldest') {
    $sql .= " ORDER BY j.posted_date ASC";
} elseif ($sortBy == 'salary_high') {
    $sql .= " ORDER BY j.salary_max DESC, j.salary_min DESC";
} elseif ($sortBy == 'salary_low') {
    $sql .= " ORDER BY j.salary_min ASC, j.salary_max ASC";
} else {
    // Default to newest
    $sql .= " ORDER BY j.posted_date DESC";
}

// Add pagination
$countSql = str_replace("SELECT j.*, c.name as company_name, c.logo_url", "SELECT COUNT(*) as total", $sql);
$countSql = preg_replace('/ORDER BY.*$/i', '', $countSql);
$sql .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $itemsPerPage;
$types .= "ii";

// Prepare statement
$stmt = $conn->prepare($sql);

// Bind parameters if any
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);

// Execute the count query to get total number of results
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    // Remove the last two parameters (offset and limit) for the count query
    array_pop($params);
    array_pop($params);
    $types = substr($types, 0, -2);
    // Only bind parameters if $types is not empty
    if (!empty($types)) {
        $countStmt->bind_param($types, ...$params);
    }
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalResults = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalResults / $itemsPerPage);

// Get all job categories for filter dropdown
$categoriesQuery = "SELECT DISTINCT category FROM jobs WHERE category IS NOT NULL ORDER BY category";
$categories = $conn->query($categoriesQuery)->fetch_all(MYSQLI_ASSOC);

// Get all experience levels for filter dropdown
$experienceQuery = "SELECT DISTINCT experience_level FROM jobs WHERE experience_level IS NOT NULL ORDER BY experience_level";
$experiences = $conn->query($experienceQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div id="jobSearchPage" class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Find Your Next Opportunity</h1>
        
        <!-- Search Form -->
        <form action="jobs.php" method="GET" class="bg-white shadow-md rounded-lg p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="keywords" class="block text-sm font-medium text-gray-700 mb-1">Keywords</label>
                    <input type="text" id="keywords" name="keywords" value="<?= h($keywords) ?>" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Job title, skills, or company">
                </div>
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input type="text" id="location" name="location" value="<?= h($location) ?>" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="City, state, or remote">
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Job Type</label>
                    <select id="type" name="type" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Types</option>
                        <option value="Full-time" <?= $type == 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                        <option value="Part-time" <?= $type == 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                        <option value="Contract" <?= $type == 'Contract' ? 'selected' : '' ?>>Contract</option>
                        <option value="Internship" <?= $type == 'Internship' ? 'selected' : '' ?>>Internship</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select id="category" name="category" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= h($cat['category']) ?>" <?= $category == $cat['category'] ? 'selected' : '' ?>><?= h($cat['category']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="experience" class="block text-sm font-medium text-gray-700 mb-1">Experience Level</label>
                    <select id="experience" name="experience" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Levels</option>
                        <?php foreach ($experiences as $exp): ?>
                            <option value="<?= h($exp['experience_level']) ?>" <?= $experience == $exp['experience_level'] ? 'selected' : '' ?>><?= h($exp['experience_level']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select id="sort" name="sort" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= $sortBy == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                        <option value="salary_high" <?= $sortBy == 'salary_high' ? 'selected' : '' ?>>Highest Salary</option>
                        <option value="salary_low" <?= $sortBy == 'salary_low' ? 'selected' : '' ?>>Lowest Salary</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label for="salary_period" class="block text-sm font-medium text-gray-700 mb-1">Salary Period</label>
                    <select id="salary_period" name="salary_period" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Any Period</option>
                        <option value="hourly" <?= $salary_period == 'hourly' ? 'selected' : '' ?>>Hourly</option>
                        <option value="monthly" <?= $salary_period == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="annually" <?= $salary_period == 'annually' ? 'selected' : '' ?>>Annually</option>
                    </select>
                </div>
                <div>
                    <label for="min_salary" class="block text-sm font-medium text-gray-700 mb-1">Min Salary</label>
                    <input type="number" id="min_salary" name="min_salary" value="<?= $min_salary ?>" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                           min="0" step="1000">
                </div>
                <div>
                    <label for="max_salary" class="block text-sm font-medium text-gray-700 mb-1">Max Salary</label>
                    <input type="number" id="max_salary" name="max_salary" value="<?= $max_salary ?>" 
                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                           min="0" step="1000">
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-search mr-2"></i>Search Jobs
                </button>
            </div>
        </form>
        
        <!-- Results Count -->
        <div class="mb-4 text-gray-600">
            Found <?= $totalResults ?> job<?= $totalResults != 1 ? 's' : '' ?> matching your criteria
        </div>
        
        <!-- Job Listings -->
        <div class="space-y-4">
            <?php if (count($jobs) > 0): ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow duration-300">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center">
                                    <?php if (!empty($job['logo_url'])): ?>
                                        <img src="<?= h($job['logo_url']) ?>" alt="<?= h($job['company_name']) ?>" class="w-12 h-12 object-contain mr-4">
                                    <?php else: ?>
                                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mr-4">
                                            <span class="text-gray-500 text-xl font-bold"><?= substr($job['company_name'], 0, 1) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-800">
                                            <a href="job-details.php?id=<?= $job['id'] ?>" class="hover:text-indigo-600"><?= h($job['title']) ?></a>
                                        </h2>
                                        <p class="text-gray-600"><?= h($job['company_name']) ?> · <?= h($job['location']) ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block bg-indigo-100 text-indigo-800 text-xs px-2 py-1 rounded-full font-medium">
                                        <?= h($job['type']) ?>
                                    </span>
                                    <?php if (!empty($job['category'])): ?>
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium ml-1">
                                            <?= h($job['category']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="mt-2 text-sm text-gray-500">
                                        Posted <?= time_elapsed_string($job['posted_date']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between items-center">
                                <div>
                                    <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                                        <div class="text-green-600 font-medium">
                                            <?php
                                            $salary = '';
                                            if (!empty($job['salary_min']) && !empty($job['salary_max'])) {
                                                $salary = number_format($job['salary_min']) . ' - ' . number_format($job['salary_max']);
                                            } elseif (!empty($job['salary_min'])) {
                                                $salary = 'From ' . number_format($job['salary_min']);
                                            } elseif (!empty($job['salary_max'])) {
                                                $salary = 'Up to ' . number_format($job['salary_max']);
                                            }
                                            
                                            if (!empty($salary)) {
                                                echo h($job['salary_currency']) . ' ' . $salary;
                                                echo ' (' . h($job['salary_period']) . ')';
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($job['experience_level'])): ?>
                                        <div class="text-gray-600 text-sm">
                                            Experience: <?= h($job['experience_level']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <a href="job-details.php?id=<?= $job['id'] ?>" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    View Details <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white shadow rounded-lg p-8 text-center">
                    <div class="text-gray-500 mb-4">
                        <i class="fas fa-search fa-3x"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No jobs found</h3>
                    <p class="text-gray-600 mb-4">Try adjusting your search criteria or check back later for new opportunities.</p>
                    <a href="jobs.php" class="text-indigo-600 hover:text-indigo-800 font-medium">
                        Clear all filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-8">
            <nav class="inline-flex rounded-md shadow-sm -space-x-px">
                <!-- Previous Page -->
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Previous</span>
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
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
                    <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
                        <span class="sr-only">Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </nav>
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

require_once 'includes/footer.php';
?>
