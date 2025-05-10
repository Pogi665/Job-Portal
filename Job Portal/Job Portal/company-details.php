<?php
require_once 'includes/header.php';

// Check if company ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('companies.php', 'Invalid company ID', 'error');
}

$companyId = (int)$_GET['id'];

// Get company details
$companyQuery = "SELECT c.*, u.email as contact_email,
                        (SELECT COUNT(*) FROM jobs WHERE company_id = c.id AND status = 'Active') as active_job_count
                        FROM companies c
                        LEFT JOIN users u ON c.user_id = u.id
                        WHERE c.id = ?";
$stmt = $conn->prepare($companyQuery);
$stmt->bind_param("i", $companyId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('companies.php', 'Company not found', 'error');
}

$company = $result->fetch_assoc();

// Get current active jobs for this company
$jobsQuery = "SELECT j.*, 
                    (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as application_count
              FROM jobs j
              WHERE j.company_id = ? AND j.status = 'Active'
              ORDER BY j.posted_date DESC
              LIMIT 10";
$stmt = $conn->prepare($jobsQuery);
$stmt->bind_param("i", $companyId);
$stmt->execute();
$jobs = $stmt->get_result();
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Company Header -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
            <div class="p-6 md:p-8">
                <div class="flex flex-wrap md:flex-nowrap">
                    <div class="w-full md:w-auto mb-6 md:mb-0 md:mr-8">
                        <div class="w-32 h-32 mx-auto md:mx-0">
                            <?php if (!empty($company['logo_url'])): ?>
                                <img src="<?php echo h($company['logo_url']); ?>" alt="<?php echo h($company['name']); ?>" class="w-full h-full object-contain rounded-md">
                            <?php else: ?>
                                <div class="w-full h-full bg-gray-200 rounded-md flex items-center justify-center">
                                    <span class="text-4xl font-bold text-gray-500"><?php echo substr($company['name'], 0, 1); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="w-full md:flex-1">
                        <div class="text-center md:text-left">
                            <h1 class="text-3xl font-bold text-gray-800"><?php echo h($company['name']); ?></h1>
                            
                            <div class="mt-2 text-gray-600 flex flex-wrap items-center justify-center md:justify-start">
                                <?php if (!empty($company['industry'])): ?>
                                    <span class="flex items-center mr-6 mb-2">
                                        <i class="fas fa-industry mr-2"></i>
                                        <?php echo h($company['industry']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['location'])): ?>
                                    <span class="flex items-center mr-6 mb-2">
                                        <i class="fas fa-map-marker-alt mr-2"></i>
                                        <?php echo h($company['location']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['size'])): ?>
                                    <span class="flex items-center mr-6 mb-2">
                                        <i class="fas fa-users mr-2"></i>
                                        <?php echo h($company['size']); ?> employees
                                    </span>
                                <?php endif; ?>
                                
                                <span class="flex items-center mb-2">
                                    <i class="fas fa-briefcase mr-2"></i>
                                    <?php echo $company['active_job_count']; ?> active jobs
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex flex-wrap justify-center md:justify-start">
                            <?php if (!empty($company['website'])): ?>
                                <a href="<?php echo h($company['website']); ?>" target="_blank" class="btn btn-outline btn-sm mr-3 mb-2">
                                    <i class="fas fa-globe mr-2"></i> Website
                                </a>
                            <?php endif; ?>
                            
                            <?php if (isLoggedIn() && hasRole('job_seeker')): ?>
                                <a href="messages.php?to=<?php echo $company['user_id']; ?>" class="btn btn-outline btn-sm mr-3 mb-2">
                                    <i class="fas fa-envelope mr-2"></i> Contact
                                </a>
                            <?php endif; ?>
                            
                            <a href="jobs.php?company_id=<?php echo $companyId; ?>" class="btn btn-primary btn-sm mb-2">
                                <i class="fas fa-search mr-2"></i> View All Jobs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- About Section -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">About <?php echo h($company['name']); ?></h2>
                        
                        <?php if (!empty($company['description'])): ?>
                            <div class="prose max-w-none">
                                <?php echo nl2br(h($company['description'])); ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500">No company description available.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Company Culture -->
                <?php if (!empty($company['culture_text'])): ?>
                <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Company Culture</h2>
                        <div class="prose max-w-none">
                            <?php echo nl2br(h($company['culture_text'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Current Job Openings -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden mb-8">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold text-gray-800">Current Job Openings</h2>
                            <span class="bg-indigo-100 text-indigo-800 text-sm font-semibold px-2.5 py-0.5 rounded-full">
                                <?php echo $company['active_job_count']; ?>
                            </span>
                        </div>
                        
                        <?php if ($jobs->num_rows > 0): ?>
                            <div class="space-y-4">
                                <?php while($job = $jobs->fetch_assoc()): ?>
                                    <a href="job-details.php?id=<?php echo $job['id']; ?>" class="block p-4 border border-gray-200 rounded-md hover:bg-gray-50">
                                        <h3 class="font-semibold text-gray-800"><?php echo h($job['title']); ?></h3>
                                        <div class="mt-1 text-sm text-gray-600 space-y-1">
                                            <div class="flex items-center">
                                                <i class="fas fa-map-marker-alt mr-2 w-4"></i>
                                                <?php echo h($job['location']); ?>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-briefcase mr-2 w-4"></i>
                                                <?php echo h($job['type']); ?>
                                            </div>
                                            <div class="flex items-center justify-between mt-2">
                                                <span class="text-xs text-gray-500">
                                                    Posted <?php echo timeElapsed($job['posted_date']); ?>
                                                </span>
                                                <span class="text-xs bg-green-100 text-green-800 py-1 px-2 rounded-full">
                                                    <?php echo $job['application_count']; ?> applicants
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                <?php endwhile; ?>
                                
                                <?php if ($company['active_job_count'] > $jobs->num_rows): ?>
                                    <a href="jobs.php?company_id=<?php echo $companyId; ?>" class="text-center block text-indigo-600 hover:text-indigo-800 font-medium mt-2">
                                        View all <?php echo $company['active_job_count']; ?> jobs
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-gray-500">No active job openings at this time.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Company Details -->
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Company Details</h2>
                        
                        <div class="space-y-3">
                            <?php if (!empty($company['industry'])): ?>
                                <div class="flex items-center text-gray-700">
                                    <div class="w-8">
                                        <i class="fas fa-industry"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Industry</p>
                                        <p><?php echo h($company['industry']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['location'])): ?>
                                <div class="flex items-center text-gray-700">
                                    <div class="w-8">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Location</p>
                                        <p><?php echo h($company['location']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['size'])): ?>
                                <div class="flex items-center text-gray-700">
                                    <div class="w-8">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Company Size</p>
                                        <p><?php echo h($company['size']); ?> employees</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['website'])): ?>
                                <div class="flex items-center text-gray-700">
                                    <div class="w-8">
                                        <i class="fas fa-globe"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Website</p>
                                        <a href="<?php echo h($company['website']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                                            <?php echo h(preg_replace('#^https?://#', '', $company['website'])); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center text-gray-700">
                                <div class="w-8">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Member Since</p>
                                    <p><?php echo date('F Y', strtotime($company['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 