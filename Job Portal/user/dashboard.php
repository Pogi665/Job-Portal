<?php
// Use absolute path for includes
require_once dirname(__DIR__) . '/includes/auth_guard.php';

// Don't include header here as it will be loaded by auth_guard
// We will only require additional functionality
if (!defined('DB_LOADED')) {
    require_once dirname(__DIR__) . '/config/db.php';
}

// Check if user is a job seeker
if (!hasRole('job_seeker')) {
    redirect('../login.php', 'You must be logged in as a job seeker to access this page', 'error');
}

$userId = $_SESSION['user_id'];

// Get job applications
$applicationsQuery = "SELECT a.*, j.title as job_title, j.location, 
                      c.name as company_name, c.logo_url as company_logo 
                      FROM applications a 
                      JOIN jobs j ON a.job_id = j.id 
                      JOIN companies c ON j.company_id = c.id 
                      WHERE a.user_id = ? 
                      ORDER BY a.applied_date DESC";
$stmt = $conn->prepare($applicationsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$applications = $stmt->get_result();

// Get saved jobs
$savedJobsQuery = "SELECT s.*, j.title as job_title, j.location, j.type,
                   j.salary_min, j.salary_max, j.salary_currency, j.salary_period, 
                   c.name as company_name, c.logo_url as company_logo
                   FROM saved_jobs s
                   JOIN jobs j ON s.job_id = j.id
                   JOIN companies c ON j.company_id = c.id
                   WHERE s.user_id = ?
                   ORDER BY s.saved_date DESC";
$stmt = $conn->prepare($savedJobsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$savedJobs = $stmt->get_result();

// Get job alerts
$alertsQuery = "SELECT * FROM job_alerts WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($alertsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$alerts = $stmt->get_result();

// Get profile completion percentage
$profileCompletionPercentage = 0;
$profileFieldsQuery = "SELECT u.*, jsp.* FROM users u 
                      LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
                      WHERE u.id = ?";
$stmt = $conn->prepare($profileFieldsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$profileResult = $stmt->get_result();

if ($profileData = $profileResult->fetch_assoc()) {
    $totalFields = 10; // Adjust based on how many fields you want to count
    $completedFields = 0;
    
    if (!empty($profileData['name'])) $completedFields++;
    if (!empty($profileData['email'])) $completedFields++;
    if (!empty($profileData['headline'])) $completedFields++;
    if (!empty($profileData['location'])) $completedFields++;
    if (!empty($profileData['avatar_url'])) $completedFields++;
    if (!empty($profileData['summary'])) $completedFields++;
    if (!empty($profileData['job_title'])) $completedFields++;
    if (!empty($profileData['experience'])) $completedFields++;
    if (!empty($profileData['education'])) $completedFields++;
    if (!empty($profileData['resume_url'])) $completedFields++;
    
    $profileCompletionPercentage = floor(($completedFields / $totalFields) * 100);
}
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="flex flex-wrap">
            <!-- Sidebar -->
            <div class="w-full lg:w-1/4 mb-8 lg:mb-0">
                <div class="bg-white shadow rounded-lg overflow-hidden sticky top-24">
                    <div class="p-6 border-b">
                        <div class="flex items-center mb-4">
                            <img src="<?php echo isset($_SESSION['avatar_url']) ? h($_SESSION['avatar_url']) : '../assets/images/default-avatar.png'; ?>" alt="User Avatar" class="w-14 h-14 rounded-full mr-4">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800"><?php echo h($_SESSION['user_name']); ?></h2>
                                <p class="text-gray-500 text-sm">Job Seeker</p>
                            </div>
                        </div>
                        
                        <!-- Profile completion -->
                        <div class="mb-4">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">Profile Completion</span>
                                <span class="text-sm font-medium text-gray-700"><?php echo $profileCompletionPercentage; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: <?php echo $profileCompletionPercentage; ?>%"></div>
                            </div>
                            <?php if ($profileCompletionPercentage < 100): ?>
                                <a href="../profile.php" class="text-sm text-indigo-600 block mt-2">Complete your profile</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase mb-3">Dashboard</h3>
                        <nav>
                            <a href="#my-applications" class="flex items-center py-2 px-3 text-gray-700 hover:bg-gray-100 rounded">
                                <i class="fas fa-file-alt w-5 mr-2 text-indigo-500"></i>
                                <span>My Applications</span>
                            </a>
                            <a href="#saved-jobs" class="flex items-center py-2 px-3 text-gray-700 hover:bg-gray-100 rounded">
                                <i class="fas fa-heart w-5 mr-2 text-indigo-500"></i>
                                <span>Saved Jobs</span>
                            </a>
                            <a href="#job-alerts" class="flex items-center py-2 px-3 text-gray-700 hover:bg-gray-100 rounded">
                                <i class="fas fa-bell w-5 mr-2 text-indigo-500"></i>
                                <span>Job Alerts</span>
                            </a>
                            <a href="../profile.php" class="flex items-center py-2 px-3 text-gray-700 hover:bg-gray-100 rounded">
                                <i class="fas fa-user w-5 mr-2 text-indigo-500"></i>
                                <span>Edit Profile</span>
                            </a>
                        </nav>
                        
                        <hr class="my-4">
                        
                        <h3 class="text-sm font-semibold text-gray-600 uppercase mb-3">Actions</h3>
                        <nav>
                            <a href="../jobs.php" class="flex items-center py-2 px-3 text-gray-700 hover:bg-gray-100 rounded">
                                <i class="fas fa-search w-5 mr-2 text-indigo-500"></i>
                                <span>Find Jobs</span>
                            </a>
                            <a href="../job-alerts.php" class="flex items-center py-2 px-3 text-gray-700 hover:bg-gray-100 rounded">
                                <i class="fas fa-plus w-5 mr-2 text-indigo-500"></i>
                                <span>Create Job Alert</span>
                            </a>
                            <a href="../resources.php" class="flex items-center py-2 px-3 text-gray-700 hover:bg-gray-100 rounded">
                                <i class="fas fa-book w-5 mr-2 text-indigo-500"></i>
                                <span>Career Resources</span>
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="w-full lg:w-3/4 lg:pl-8">
                <!-- My Applications Section -->
                <div id="my-applications" class="mb-12">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">My Applications</h2>
                        <a href="../jobs.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            Browse More Jobs <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <?php if ($applications->num_rows > 0): ?>
                        <div class="bg-white shadow-md rounded-lg overflow-hidden">
                            <?php while($app = $applications->fetch_assoc()): ?>
                                <div class="border-b border-gray-100 p-6">
                                    <div class="flex flex-wrap md:flex-nowrap items-center">
                                        <!-- Company Logo -->
                                        <div class="mr-6 mb-4 md:mb-0">
                                            <?php if (!empty($app['company_logo'])): ?>
                                                <img src="<?php echo h($app['company_logo']); ?>" alt="<?php echo h($app['company_name']); ?>" class="w-14 h-14 object-contain rounded">
                                            <?php else: ?>
                                                <div class="w-14 h-14 bg-gray-200 flex items-center justify-center rounded">
                                                    <span class="text-lg font-bold text-gray-500"><?php echo substr($app['company_name'], 0, 1); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Job Info -->
                                        <div class="flex-grow mb-4 md:mb-0">
                                            <h3 class="text-lg font-semibold text-gray-800">
                                                <a href="../job-details.php?id=<?php echo $app['job_id']; ?>" class="hover:text-indigo-600">
                                                    <?php echo h($app['job_title']); ?>
                                                </a>
                                            </h3>
                                            <div class="flex flex-wrap text-sm text-gray-600">
                                                <p class="mr-6 mb-1">
                                                    <i class="fas fa-building mr-1"></i> <?php echo h($app['company_name']); ?>
                                                </p>
                                                <p>
                                                    <i class="fas fa-map-marker-alt mr-1"></i> <?php echo h($app['location']); ?>
                                                </p>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-1">
                                                Applied <?php echo timeElapsed($app['applied_date']); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Status -->
                                        <div class="w-full md:w-auto flex items-center justify-between">
                                            <?php 
                                            $statusClasses = [
                                                'Pending' => 'bg-yellow-100 text-yellow-800',
                                                'Reviewed' => 'bg-blue-100 text-blue-800',
                                                'Shortlisted' => 'bg-green-100 text-green-800',
                                                'Interview' => 'bg-purple-100 text-purple-800',
                                                'Rejected' => 'bg-red-100 text-red-800',
                                                'Hired' => 'bg-indigo-100 text-indigo-800'
                                            ];
                                            $statusClass = $statusClasses[$app['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $statusClass; ?>">
                                                <?php echo h($app['status']); ?>
                                            </span>
                                            
                                            <a href="../job-details.php?id=<?php echo $app['job_id']; ?>" class="ml-4 text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                                View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-white shadow-md rounded-lg p-8 text-center">
                            <div class="mb-4">
                                <i class="fas fa-file-alt text-gray-300 text-5xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No applications yet</h3>
                            <p class="text-gray-500 mb-6">Start applying to jobs to see your applications here</p>
                            <a href="../jobs.php" class="btn btn-primary">
                                <i class="fas fa-search mr-2"></i> Explore Jobs
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Saved Jobs Section -->
                <div id="saved-jobs" class="mb-12">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Saved Jobs</h2>
                        <a href="../jobs.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            Find More Jobs <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <?php if ($savedJobs->num_rows > 0): ?>
                        <div class="bg-white shadow-md rounded-lg overflow-hidden">
                            <?php while($job = $savedJobs->fetch_assoc()): ?>
                                <div class="border-b border-gray-100 p-6">
                                    <div class="flex flex-wrap md:flex-nowrap items-center">
                                        <!-- Company Logo -->
                                        <div class="mr-6 mb-4 md:mb-0">
                                            <?php if (!empty($job['company_logo'])): ?>
                                                <img src="<?php echo h($job['company_logo']); ?>" alt="<?php echo h($job['company_name']); ?>" class="w-14 h-14 object-contain rounded">
                                            <?php else: ?>
                                                <div class="w-14 h-14 bg-gray-200 flex items-center justify-center rounded">
                                                    <span class="text-lg font-bold text-gray-500"><?php echo substr($job['company_name'], 0, 1); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Job Info -->
                                        <div class="flex-grow mb-4 md:mb-0">
                                            <h3 class="text-lg font-semibold text-gray-800">
                                                <a href="../job-details.php?id=<?php echo $job['job_id']; ?>" class="hover:text-indigo-600">
                                                    <?php echo h($job['job_title']); ?>
                                                </a>
                                            </h3>
                                            <div class="flex flex-wrap text-sm text-gray-600">
                                                <p class="mr-6 mb-1">
                                                    <i class="fas fa-building mr-1"></i> <?php echo h($job['company_name']); ?>
                                                </p>
                                                <p class="mr-6 mb-1">
                                                    <i class="fas fa-map-marker-alt mr-1"></i> <?php echo h($job['location']); ?>
                                                </p>
                                                <p>
                                                    <i class="fas fa-briefcase mr-1"></i> <?php echo h($job['type']); ?>
                                                </p>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-1">
                                                <?php echo formatSalary($job['salary_min'], $job['salary_max'], $job['salary_currency'], $job['salary_period']); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="w-full md:w-auto flex items-center">
                                            <button type="button" class="text-red-600 hover:text-red-800 mr-4 text-sm font-medium unsave-job" data-job-id="<?php echo $job['job_id']; ?>">
                                                <i class="fas fa-trash-alt mr-1"></i> Remove
                                            </button>
                                            <a href="../job-details.php?id=<?php echo $job['job_id']; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                                View Job
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-white shadow-md rounded-lg p-8 text-center">
                            <div class="mb-4">
                                <i class="fas fa-heart text-gray-300 text-5xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No saved jobs</h3>
                            <p class="text-gray-500 mb-6">Save jobs you're interested in to apply later</p>
                            <a href="../jobs.php" class="btn btn-primary">
                                <i class="fas fa-search mr-2"></i> Explore Jobs
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Job Alerts Section -->
                <div id="job-alerts" class="mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Job Alerts</h2>
                        <a href="../job-alerts.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            Manage Alerts <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <?php if ($alerts->num_rows > 0): ?>
                        <div class="bg-white shadow-md rounded-lg overflow-hidden">
                            <?php while($alert = $alerts->fetch_assoc()): ?>
                                <div class="border-b border-gray-100 p-6">
                                    <div class="flex flex-wrap md:flex-nowrap items-center">
                                        <!-- Alert Icon -->
                                        <div class="mr-6 mb-4 md:mb-0">
                                            <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center">
                                                <i class="fas fa-bell text-xl"></i>
                                            </div>
                                        </div>
                                        
                                        <!-- Alert Info -->
                                        <div class="flex-grow mb-4 md:mb-0">
                                            <h3 class="text-lg font-semibold text-gray-800">
                                                <?php echo h($alert['name']); ?>
                                            </h3>
                                            <div class="flex flex-wrap text-sm text-gray-600">
                                                <?php if (!empty($alert['keywords'])): ?>
                                                    <span class="mr-4 mb-1">
                                                        <i class="fas fa-search mr-1"></i> <?php echo h($alert['keywords']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($alert['location'])): ?>
                                                    <span class="mr-4 mb-1">
                                                        <i class="fas fa-map-marker-alt mr-1"></i> <?php echo h($alert['location']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($alert['job_type'])): ?>
                                                    <span>
                                                        <i class="fas fa-briefcase mr-1"></i> <?php echo h($alert['job_type']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-1">
                                                <span class="mr-4"><i class="far fa-clock mr-1"></i> <?php echo ucfirst(h($alert['frequency'])); ?></span>
                                                <span>Created <?php echo timeElapsed($alert['created_at']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="w-full md:w-auto flex items-center">
                                            <a href="../job-alerts.php?edit=<?php echo $alert['id']; ?>" class="text-indigo-600 hover:text-indigo-800 mr-4 text-sm font-medium">
                                                <i class="fas fa-edit mr-1"></i> Edit
                                            </a>
                                            <a href="../job-alerts.php?delete=<?php echo $alert['id']; ?>" class="text-red-600 hover:text-red-800 text-sm font-medium" onclick="return confirm('Are you sure you want to delete this alert?')">
                                                <i class="fas fa-trash-alt mr-1"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-white shadow-md rounded-lg p-8 text-center">
                            <div class="mb-4">
                                <i class="fas fa-bell text-gray-300 text-5xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-700 mb-2">No job alerts</h3>
                            <p class="text-gray-500 mb-6">Create job alerts to get notified about new openings</p>
                            <a href="../job-alerts.php" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i> Create Job Alert
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle unsaving jobs
    const unsaveButtons = document.querySelectorAll('.unsave-job');
    unsaveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const jobId = this.getAttribute('data-job-id');
            if (confirm('Are you sure you want to remove this job from your saved jobs?')) {
                // Send AJAX request to save-job.php
                const formData = new FormData();
                formData.append('job_id', jobId);
                
                fetch('../save-job.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the job item from the UI
                        this.closest('.border-b').remove();
                        
                        // If no more saved jobs, show the empty state
                        const savedJobsContainer = document.querySelector('#saved-jobs');
                        if (savedJobsContainer && savedJobsContainer.querySelectorAll('.border-b').length === 0) {
                            const emptyState = `
                                <div class="bg-white shadow-md rounded-lg p-8 text-center">
                                    <div class="mb-4">
                                        <i class="fas fa-heart text-gray-300 text-5xl"></i>
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No saved jobs</h3>
                                    <p class="text-gray-500 mb-6">Save jobs you're interested in to apply later</p>
                                    <a href="../jobs.php" class="btn btn-primary">
                                        <i class="fas fa-search mr-2"></i> Explore Jobs
                                    </a>
                                </div>
                            `;
                            savedJobsContainer.querySelector('.bg-white').outerHTML = emptyState;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error unsaving job:', error);
                });
            }
        });
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({
                    top: target.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 