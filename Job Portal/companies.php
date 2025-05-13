<?php
require_once 'includes/header.php';

// Parameters for filtering and pagination
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$industry = isset($_GET['industry']) ? cleanInput($_GET['industry']) : '';
$location = isset($_GET['location']) ? cleanInput($_GET['location']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

// Base query
$query = "SELECT c.*, COUNT(j.id) as job_count 
          FROM companies c 
          LEFT JOIN jobs j ON c.id = j.company_id AND j.status = 'Active' 
          WHERE 1=1";
$countQuery = "SELECT COUNT(*) as total FROM companies WHERE 1=1";
$params = [];
$types = "";

// Add search filters if provided
if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.description LIKE ?)";
    $countQuery .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if (!empty($industry)) {
    $query .= " AND c.industry = ?";
    $countQuery .= " AND industry = ?";
    $params[] = $industry;
    $types .= "s";
}

if (!empty($location)) {
    $query .= " AND c.location LIKE ?";
    $countQuery .= " AND location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

// Group by company and add sorting and pagination
$query .= " GROUP BY c.id ORDER BY c.name ASC LIMIT ? OFFSET ?";
$params[] = $itemsPerPage;
$params[] = $offset;
$types .= "ii";

// Execute count query
$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    // For count query, we need to remove the pagination parameters (last 2)
    $countParams = array_slice($params, 0, count($params) - 2);
    // Also update the types string to match the parameter count
    $countTypes = substr($types, 0, strlen($types) - 2);
    
    // Only bind if we have parameters
    if (!empty($countParams)) {
        $stmt->bind_param($countTypes, ...$countParams);
    }
}
$stmt->execute();
$totalResult = $stmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$total = $totalRow['total'];
$totalPages = ceil($total / $itemsPerPage);

// Execute main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    // For the main query, we use all params with the full types string
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get unique industries for filter
$industriesQuery = "SELECT DISTINCT industry FROM companies WHERE industry IS NOT NULL AND industry != '' ORDER BY industry";
$industriesResult = $conn->query($industriesQuery);
$industries = [];
while ($row = $industriesResult->fetch_assoc()) {
    $industries[] = $row['industry'];
}

// Get unique locations for filter
$locationsQuery = "SELECT DISTINCT location FROM companies WHERE location IS NOT NULL AND location != '' ORDER BY location";
$locationsResult = $conn->query($locationsQuery);
$locations = [];
while ($row = $locationsResult->fetch_assoc()) {
    $locations[] = $row['location'];
}
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Page Title -->
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-gray-800">Companies</h1>
            <p class="text-gray-600 mt-2">Discover great places to work</p>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form action="companies.php" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" id="search" name="search" placeholder="Company name or keyword" 
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               value="<?php echo h($search); ?>">
                    </div>
                    <div>
                        <label for="industry" class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                        <select id="industry" name="industry" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Industries</option>
                            <?php foreach ($industries as $ind): ?>
                                <option value="<?php echo h($ind); ?>" <?php echo $industry == $ind ? 'selected' : ''; ?>><?php echo h($ind); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <select id="location" name="location" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo h($loc); ?>" <?php echo $location == $loc ? 'selected' : ''; ?>><?php echo h($loc); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-search mr-2"></i>Search Companies
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Companies Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if ($result->num_rows > 0): ?>
                <?php while($company = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <?php if (!empty($company['logo_url'])): ?>
                                    <img src="<?php echo h($company['logo_url']); ?>" alt="<?php echo h($company['name']); ?> Logo" class="w-16 h-16 object-contain rounded-md mr-4">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-gray-200 rounded-md flex items-center justify-center mr-4">
                                        <span class="text-gray-500 text-lg font-bold"><?php echo substr($company['name'], 0, 1); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800"><?php echo h($company['name']); ?></h2>
                                    <?php if (!empty($company['industry'])): ?>
                                        <p class="text-gray-600 text-sm"><?php echo h($company['industry']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($company['location'])): ?>
                                <div class="flex items-center text-gray-600 mb-4">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    <span><?php echo h($company['location']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['description'])): ?>
                                <p class="text-gray-600 mb-4 line-clamp-2"><?php echo h(substr($company['description'], 0, 150)) . (strlen($company['description']) > 150 ? '...' : ''); ?></p>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                                <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2 py-1 rounded">
                                    <?php echo $company['job_count']; ?> open <?php echo $company['job_count'] == 1 ? 'job' : 'jobs'; ?>
                                </span>
                                <a href="company-details.php?id=<?php echo $company['id']; ?>" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                                    View Profile <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full py-8 text-center">
                    <div class="bg-white rounded-lg shadow-sm p-8">
                        <i class="fas fa-building text-gray-300 text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No companies found</h3>
                        <p class="text-gray-500">Try adjusting your search criteria or check back later.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="mt-8 flex justify-center">
                <nav class="flex items-center">
                    <?php if ($page > 1): ?>
                        <a href="?search=<?php echo urlencode($search); ?>&industry=<?php echo urlencode($industry); ?>&location=<?php echo urlencode($location); ?>&page=<?php echo $page - 1; ?>" class="px-3 py-2 text-indigo-600 hover:text-indigo-900 border rounded-l-md border-r-0">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 text-gray-400 border rounded-l-md border-r-0">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $startPage + 4);
                    if ($endPage - $startPage < 4) {
                        $startPage = max(1, $endPage - 4);
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="px-4 py-2 text-indigo-600 bg-indigo-50 font-medium border">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="?search=<?php echo urlencode($search); ?>&industry=<?php echo urlencode($industry); ?>&location=<?php echo urlencode($location); ?>&page=<?php echo $i; ?>" class="px-4 py-2 text-gray-700 hover:bg-gray-100 border border-r-0">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?search=<?php echo urlencode($search); ?>&industry=<?php echo urlencode($industry); ?>&location=<?php echo urlencode($location); ?>&page=<?php echo $page + 1; ?>" class="px-3 py-2 text-indigo-600 hover:text-indigo-900 border rounded-r-md">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 text-gray-400 border rounded-r-md">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 