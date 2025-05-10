<?php
require_once 'includes/header.php';

// Check if job ID is provided
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$jobId) {
    redirect('jobs.php', 'Invalid job ID', 'error');
}

// Get job details
$jobQuery = "SELECT j.*, c.name as company_name, c.logo_url, c.description as company_description
            FROM jobs j 
            JOIN companies c ON j.company_id = c.id
            WHERE j.id = ? AND j.status = 'Active'";
$stmt = $conn->prepare($jobQuery);
$stmt->bind_param("i", $jobId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('jobs.php', 'Job not found or no longer active', 'error');
}

$job = $result->fetch_assoc();

// Get job skills
$skillsQuery = "SELECT skill FROM job_skills WHERE job_id = ?";
$stmt = $conn->prepare($skillsQuery);
$stmt->bind_param("i", $jobId);
$stmt->execute();
$skillsResult = $stmt->get_result();
$skills = [];

while ($row = $skillsResult->fetch_assoc()) {
    $skills[] = $row['skill'];
}

// Update view count
$updateViewQuery = "UPDATE jobs SET view_count = COALESCE(view_count, 0) + 1 WHERE id = ?";
$stmt = $conn->prepare($updateViewQuery);
$stmt->bind_param("i", $jobId);
$stmt->execute();

// Check if user has already applied
$hasApplied = false;
if (isLoggedIn()) {
    $checkAppliedQuery = "SELECT id FROM applications WHERE job_id = ? AND user_id = ?";
    $stmt = $conn->prepare($checkAppliedQuery);
    $stmt->bind_param("ii", $jobId, $_SESSION['user_id']);
    $stmt->execute();
    $hasApplied = ($stmt->get_result()->num_rows > 0);
}
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Breadcrumbs -->
            <nav class="mb-4 text-sm">
                <ol class="flex items-center space-x-2">
                    <li><a href="index.php" class="text-gray-500 hover:text-indigo-600">Home</a></li>
                    <li><span class="text-gray-400 mx-1">/</span></li>
                    <li><a href="jobs.php" class="text-gray-500 hover:text-indigo-600">Jobs</a></li>
                    <li><span class="text-gray-400 mx-1">/</span></li>
                    <li class="text-gray-800 font-medium truncate"><?= h($job['title']) ?></li>
                </ol>
            </nav>
            
            <!-- Job header -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden mb-6">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <?php if (!empty($job['logo_url'])): ?>
                                <img src="<?= h($job['logo_url']) ?>" alt="<?= h($job['company_name']) ?>" class="w-16 h-16 object-contain mr-4">
                            <?php else: ?>
                                <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mr-4">
                                    <span class="text-gray-500 text-xl font-bold"><?= substr($job['company_name'], 0, 1) ?></span>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900"><?= h($job['title']) ?></h1>
                                <p class="text-indigo-600 font-medium"><?= h($job['company_name']) ?></p>
                                <div class="flex items-center text-gray-500 text-sm mt-1">
                                    <span class="flex items-center mr-4"><i class="fas fa-map-marker-alt mr-1"></i> <?= h($job['location']) ?></span>
                                    <span class="flex items-center"><i class="fas fa-clock mr-1"></i> <?= h($job['type']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="inline-block bg-indigo-100 text-indigo-800 text-sm px-3 py-1 rounded-full font-medium mb-2">
                                <?= h($job['experience_level']) ?>
                            </div>
                            <?php if (!empty($job['category'])): ?>
                                <div class="inline-block bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full font-medium mb-2 ml-1">
                                    <?= h($job['category']) ?>
                                </div>
                            <?php endif; ?>
                            <p class="text-gray-500 text-sm">Posted <?= time_elapsed_string($job['posted_date']) ?></p>
                        </div>
                    </div>
                    
                    <!-- Apply and Save buttons -->
                    <div class="flex mt-6 space-x-3">
                        <?php if (isLoggedIn() && hasRole('job_seeker')): ?>
                            <?php if ($hasApplied): ?>
                                <button disabled class="btn bg-gray-300 text-gray-600 cursor-not-allowed">
                                    <i class="fas fa-check mr-2"></i>Applied
                                </button>
                            <?php else: ?>
                                <a href="apply.php?job_id=<?= $job['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-paper-plane mr-2"></i>Apply Now
                                </a>
                            <?php endif; ?>
                            <button id="saveJobBtn" class="btn btn-outline" data-job-id="<?= $job['id'] ?>">
                                <i class="far fa-bookmark mr-2"></i>Save Job
                            </button>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="login.php?redirect=job-details.php?id=<?= $job['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt mr-2"></i>Login to Apply
                            </a>
                        <?php endif; ?>
                        <button id="shareJobBtn" class="btn btn-outline" data-job-title="<?= h($job['title']) ?>" data-job-company="<?= h($job['company_name']) ?>">
                            <i class="fas fa-share-alt mr-2"></i>Share
                        </button>
                    </div>
                </div>
            </div>

            <!-- Job details -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <!-- Salary information -->
                    <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                        <div class="bg-white shadow-md rounded-lg overflow-hidden p-6">
                            <h2 class="text-lg font-bold text-gray-800 mb-4">Salary</h2>
                            <div class="flex items-center">
                                <div class="text-2xl font-bold text-green-600">
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
                                    }
                                    ?>
                                </div>
                                <div class="ml-2 text-gray-600 capitalize"><?= h($job['salary_period']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Job description -->
                    <div class="bg-white shadow-md rounded-lg overflow-hidden p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Job Description</h2>
                        <div class="prose max-w-none">
                            <?= nl2br(h($job['description'])) ?>
                        </div>
                    </div>
                    
                    <!-- Requirements -->
                    <?php if (!empty($job['requirements'])): ?>
                        <div class="bg-white shadow-md rounded-lg overflow-hidden p-6">
                            <h2 class="text-lg font-bold text-gray-800 mb-4">Requirements</h2>
                            <div class="prose max-w-none">
                                <?= nl2br(h($job['requirements'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Benefits -->
                    <?php if (!empty($job['benefits'])): ?>
                        <div class="bg-white shadow-md rounded-lg overflow-hidden p-6">
                            <h2 class="text-lg font-bold text-gray-800 mb-4">Benefits</h2>
                            <div class="prose max-w-none">
                                <?= nl2br(h($job['benefits'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Skills -->
                    <?php if (!empty($skills)): ?>
                        <div class="bg-white shadow-md rounded-lg overflow-hidden p-6">
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
                    
                    <!-- Application instructions -->
                    <?php if (!empty($job['application_instructions'])): ?>
                        <div class="bg-white shadow-md rounded-lg overflow-hidden p-6">
                            <h2 class="text-lg font-bold text-gray-800 mb-4">How to Apply</h2>
                            <div class="prose max-w-none">
                                <?= nl2br(h($job['application_instructions'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="space-y-6">
                    <!-- Company information -->
                    <div class="bg-white shadow-md rounded-lg overflow-hidden p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">About the Company</h2>
                        <div class="flex items-center mb-4">
                            <?php if (!empty($job['logo_url'])): ?>
                                <img src="<?= h($job['logo_url']) ?>" alt="<?= h($job['company_name']) ?>" class="w-12 h-12 object-contain mr-3">
                            <?php else: ?>
                                <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                                    <span class="text-gray-500 text-lg font-bold"><?= substr($job['company_name'], 0, 1) ?></span>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h3 class="font-semibold text-gray-800"><?= h($job['company_name']) ?></h3>
                                <a href="company.php?id=<?= $job['company_id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                    View company profile
                                </a>
                            </div>
                        </div>
                        <div class="prose max-w-none text-sm">
                            <?= !empty($job['company_description']) ? substr(nl2br(h($job['company_description'])), 0, 200) . '...' : 'No company description available.' ?>
                        </div>
                    </div>
                    
                    <!-- Application options -->
                    <div class="bg-white shadow-md rounded-lg overflow-hidden p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Apply via</h2>
                        <div class="space-y-3">
                            <?php if (!empty($job['application_email'])): ?>
                                <a href="mailto:<?= h($job['application_email']) ?>?subject=Application for <?= h($job['title']) ?>" class="block bg-indigo-50 hover:bg-indigo-100 p-3 rounded-md">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-indigo-100 rounded-full mr-3">
                                            <i class="fas fa-envelope text-indigo-600"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-800">Email</div>
                                            <div class="text-sm text-gray-600"><?= h($job['application_email']) ?></div>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($job['application_url'])): ?>
                                <a href="<?= h($job['application_url']) ?>" target="_blank" class="block bg-indigo-50 hover:bg-indigo-100 p-3 rounded-md">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-indigo-100 rounded-full mr-3">
                                            <i class="fas fa-globe text-indigo-600"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-800">External Website</div>
                                            <div class="text-sm text-gray-600">Apply on company website</div>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (isLoggedIn() && hasRole('job_seeker')): ?>
                                <a href="apply.php?job_id=<?= $job['id'] ?>" class="block bg-indigo-50 hover:bg-indigo-100 p-3 rounded-md">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-indigo-100 rounded-full mr-3">
                                            <i class="fas fa-paper-plane text-indigo-600"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-800">Apply via CareerLynk</div>
                                            <div class="text-sm text-gray-600">Quick apply with your profile</div>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Job information -->
                    <div class="bg-white shadow-md rounded-lg overflow-hidden p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Job Information</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Posted on:</span>
                                <span class="text-gray-800"><?= date('M j, Y', strtotime($job['posted_date'])) ?></span>
                            </div>
                            <?php if (!empty($job['deadline'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Closing date:</span>
                                    <span class="text-gray-800"><?= date('M j, Y', strtotime($job['deadline'])) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Job type:</span>
                                <span class="text-gray-800"><?= h($job['type']) ?></span>
                            </div>
                            <?php if (!empty($job['category'])): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Category:</span>
                                    <span class="text-gray-800"><?= h($job['category']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Experience:</span>
                                <span class="text-gray-800"><?= h($job['experience_level']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle share button
        const shareBtn = document.getElementById('shareJobBtn');
        if (shareBtn) {
            shareBtn.addEventListener('click', function() {
                const jobTitle = this.getAttribute('data-job-title');
                const jobCompany = this.getAttribute('data-job-company');
                const url = window.location.href;
                
                if (navigator.share) {
                    navigator.share({
                        title: `${jobTitle} at ${jobCompany}`,
                        text: `Check out this job: ${jobTitle} at ${jobCompany}`,
                        url: url
                    })
                    .catch((error) => console.log('Error sharing:', error));
                } else {
                    // Fallback for browsers that don't support the Web Share API
                    const textArea = document.createElement('textarea');
                    textArea.value = url;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    alert('Link copied to clipboard!');
                }
            });
        }
        
        // Handle save job button
        const saveJobBtn = document.getElementById('saveJobBtn');
        if (saveJobBtn) {
            saveJobBtn.addEventListener('click', function() {
                const jobId = this.getAttribute('data-job-id');
                
                fetch('save-job.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `job_id=${jobId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.innerHTML = '<i class="fas fa-bookmark mr-2"></i>Saved';
                        this.classList.remove('btn-outline');
                        this.classList.add('btn-success');
                    } else {
                        alert(data.message || 'Error saving job');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        }
    });
</script>

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
