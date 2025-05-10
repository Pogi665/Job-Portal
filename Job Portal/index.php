<?php
// index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Require authentication
require_once 'includes/auth_guard.php';

require_once 'includes/header.php';

// Check if the database connection is working
echo "<!-- DB Connection: " . ($conn ? "OK" : "FAILED") . " -->";

// Get featured jobs using prepared statement
$featuredJobsSQL = "SELECT j.*, c.name as company_name, c.logo_url FROM jobs j 
                   JOIN companies c ON j.company_id = c.id 
                   WHERE j.status = 'Active' 
                   ORDER BY j.posted_date DESC LIMIT 6";
$featuredStmt = $conn->prepare($featuredJobsSQL);
$featuredStmt->execute();
$featuredJobs = $featuredStmt->get_result();

// Get top companies using prepared statement
$topCompaniesSQL = "SELECT c.* FROM companies c 
                   JOIN jobs j ON c.id = j.company_id 
                   GROUP BY c.id 
                   ORDER BY COUNT(j.id) DESC LIMIT 5";
$topCompaniesStmt = $conn->prepare($topCompaniesSQL);
$topCompaniesStmt->execute();
$topCompanies = $topCompaniesStmt->get_result();

if (!function_exists('sanitizeOutput')) {
    function sanitizeOutput($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    echo "<!-- sanitizeOutput function was missing -->";
}
?>

<div id="landingPage" class="page-content">
    <section class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 text-white py-20 sm:py-28 px-4 sm:px-6 lg:px-8 text-center rounded-xl shadow-2xl mb-16">
        <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold mb-6">
            Find Your Dream Job Today
        </h1>
        <p class="text-lg sm:text-xl md:text-2xl mb-10 max-w-3xl mx-auto text-indigo-100">
            CareerLynk connects talent with opportunity. Discover thousands of job listings from top companies and take the next step in your career.
        </p>
        <div class="max-w-2xl mx-auto">
            <form action="jobs.php" method="get" class="flex flex-col sm:flex-row gap-3 p-2 bg-white/20 rounded-lg backdrop-blur-sm border border-white/30">
                <div class="relative flex-grow">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input id="heroSearchTerm" name="keywords" type="text" placeholder="Job title, keywords, or company" class="w-full pl-10 pr-4 py-3.5 rounded-md shadow-sm text-gray-900 focus:ring-2 focus:ring-yellow-400 focus:border-transparent border-transparent placeholder-gray-500 text-base">
                </div>
                <div class="relative sm:w-auto">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fas fa-map-marker-alt text-gray-400"></i>
                    </div>
                    <input id="heroSearchLocation" name="location" type="text" placeholder="Location" class="w-full pl-10 pr-4 py-3.5 rounded-md shadow-sm text-gray-900 focus:ring-2 focus:ring-yellow-400 focus:border-transparent border-transparent placeholder-gray-500 sm:w-56 text-base">
                </div>
                <button type="submit" class="btn bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold py-3.5 px-6 shadow-md text-base">
                    <i class="fas fa-search mr-2"></i> Search Jobs
                </button>
            </form>
        </div>
    </section>

    <section class="py-16">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">How CareerLynk Works</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div class="card p-8 card-hover">
                <div class="flex items-center justify-center h-20 w-20 rounded-full bg-indigo-100 text-indigo-600 mx-auto mb-6 shadow-md">
                    <i class="fas fa-user-plus fa-2x"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">1. Create Account</h3>
                <p class="text-gray-600">Sign up as a job seeker or employer and build your profile in minutes.</p>
            </div>
            <div class="card p-8 card-hover">
                <div class="flex items-center justify-center h-20 w-20 rounded-full bg-indigo-100 text-indigo-600 mx-auto mb-6 shadow-md">
                    <i class="fas fa-search-dollar fa-2x"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">2. Find or Post</h3>
                <p class="text-gray-600">Search thousands of jobs or post openings to attract top talent.</p>
            </div>
            <div class="card p-8 card-hover">
                <div class="flex items-center justify-center h-20 w-20 rounded-full bg-indigo-100 text-indigo-600 mx-auto mb-6 shadow-md">
                    <i class="fas fa-paper-plane fa-2x"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">3. Apply & Connect</h3>
                <p class="text-gray-600">Apply with ease and connect directly with employers or candidates.</p>
            </div>
        </div>
    </section>

    <section class="py-16 bg-indigo-50 rounded-xl">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Featured Job Openings</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 px-4">
            <?php if ($featuredJobs->num_rows > 0): ?>
                <?php while ($job = $featuredJobs->fetch_assoc()): ?>
                    <div class="card card-hover flex flex-col justify-between h-full">
                        <div>
                            <div class="flex items-start justify-between mb-3">
                                <img src="<?php echo $job['logo_url'] ?? 'https://placehold.co/60x60/e2e8f0/334155?text=Logo'; ?>" 
                                     alt="<?php echo sanitizeOutput($job['company_name']); ?> logo" 
                                     class="h-12 w-12 rounded-lg mr-4 object-contain border border-gray-200 flex-shrink-0">
                                <div class="flex-grow min-w-0">
                                    <h3 class="text-lg font-semibold text-indigo-700 hover:underline cursor-pointer truncate" 
                                        title="<?php echo sanitizeOutput($job['title']); ?>">
                                        <a href="job-details.php?id=<?php echo (int)$job['id']; ?>"><?php echo sanitizeOutput($job['title']); ?></a>
                                    </h3>
                                    <p class="text-gray-600 text-sm cursor-pointer hover:text-indigo-500 truncate">
                                        <a href="company.php?id=<?php echo (int)$job['company_id']; ?>">
                                            <?php echo sanitizeOutput($job['company_name']); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-1.5 text-sm text-gray-700 mb-4">
                                <p class="flex items-center"><i class="fas fa-map-marker-alt w-4 mr-2 text-gray-400"></i> <?php echo sanitizeOutput($job['location']); ?></p>
                                <p class="flex items-center"><i class="fas fa-briefcase w-4 mr-2 text-gray-400"></i> <?php echo sanitizeOutput($job['type']); ?></p>
                                <p class="flex items-center"><i class="fas fa-dollar-sign w-4 mr-2 text-gray-400"></i> <?php echo sanitizeOutput($job['salary'] ?? 'Not Disclosed'); ?></p>
                                <p class="flex items-center"><i class="fas fa-clock w-4 mr-2 text-gray-400"></i> Posted: <?php echo formatDate($job['posted_date']); ?></p>
                            </div>
                            
                            <?php 
                            // Get job skills with prepared statement
                            $job_id = $job['id'];
                            $skillsQuery = "SELECT skill FROM job_skills WHERE job_id = ? LIMIT 4";
                            $skillsStmt = $conn->prepare($skillsQuery);
                            $skillsStmt->bind_param("i", $job_id);
                            $skillsStmt->execute();
                            $skillsResult = $skillsStmt->get_result();
                            if ($skillsResult->num_rows > 0):
                            ?>
                            <div class="mb-4">
                                <h4 class="font-medium text-xs text-gray-500 mb-1.5">Skills:</h4>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php 
                                    $skillCount = 0;
                                    while ($skill = $skillsResult->fetch_assoc()):
                                        if ($skillCount < 3):
                                    ?>
                                        <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap">
                                            <?php echo sanitizeOutput($skill['skill']); ?>
                                        </span>
                                    <?php 
                                        endif;
                                        $skillCount++;
                                    endwhile;
                                    
                                    // If there are more skills than we showed
                                    if ($skillsResult->num_rows > 3):
                                    ?>
                                        <span class="bg-gray-200 text-gray-700 px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap">
                                            +<?php echo $skillsResult->num_rows - 3; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn btn-primary w-full text-center mt-auto text-sm">
                            View Details
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="col-span-3 text-center text-gray-500 py-8">No featured jobs available at the moment.</p>
            <?php endif; ?>
        </div>
        <div class="text-center mt-12">
            <a href="jobs.php" class="btn btn-primary text-lg px-8 py-3">
                View All Jobs <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </section>

    <section class="py-16">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Top Companies Hiring</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6 items-center">
            <?php if ($topCompanies->num_rows > 0): ?>
                <?php while ($company = $topCompanies->fetch_assoc()): ?>
                    <div class="card !p-4 card-hover flex flex-col items-center text-center cursor-pointer" onclick="window.location='company.php?id=<?php echo (int)$company['id']; ?>'">
                        <img src="<?php echo $company['logo_url'] ?? 'https://placehold.co/100x50/e2e8f0/334155?text=Logo'; ?>" 
                             alt="<?php echo sanitizeOutput($company['name']); ?>" 
                             class="h-16 w-auto mb-3 object-contain rounded">
                        <h3 class="text-sm font-semibold text-gray-800 truncate w-full" title="<?php echo sanitizeOutput($company['name']); ?>">
                            <?php echo sanitizeOutput($company['name']); ?>
                        </h3>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="col-span-5 text-center text-gray-500 py-8">No companies available at the moment.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="py-16 bg-gray-50 rounded-xl">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">Why Choose CareerLynk?</h2>
        <div class="grid md:grid-cols-2 gap-8 px-4">
            <div class="card p-8">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-blue-100 text-blue-600 rounded-full mr-4"><i class="fas fa-user-tie fa-2x"></i></div>
                    <h3 class="text-2xl font-semibold text-gray-800">For Job Seekers</h3>
                </div>
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2 flex-shrink-0"></i><span>Access thousands of job listings from top companies.</span></li>
                    <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2 flex-shrink-0"></i><span>Create a professional profile to showcase your skills.</span></li>
                    <li class="flex items-start"><i class="fas fa-check-circle text-green-500 mt-1 mr-2 flex-shrink-0"></i><span>Receive personalized job recommendations.</span></li>
                </ul>
                <a href="register.php" class="btn btn-primary mt-6">Get Started</a>
            </div>
            <div class="card p-8">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-green-100 text-green-600 rounded-full mr-4"><i class="fas fa-building fa-2x"></i></div>
                    <h3 class="text-2xl font-semibold text-gray-800">For Employers</h3>
                </div>
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-start"><i class="fas fa-check-circle text-blue-500 mt-1 mr-2 flex-shrink-0"></i><span>Post jobs quickly and reach a vast talent pool.</span></li>
                    <li class="flex items-start"><i class="fas fa-check-circle text-blue-500 mt-1 mr-2 flex-shrink-0"></i><span>Utilize advanced search filters to find ideal candidates.</span></li>
                    <li class="flex items-start"><i class="fas fa-check-circle text-blue-500 mt-1 mr-2 flex-shrink-0"></i><span>Manage applications efficiently with our ATS Lite.</span></li>
                </ul>
                <a href="register.php?role=employer" class="btn bg-green-500 hover:bg-green-700 text-white mt-6">Post a Job</a>
            </div>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
