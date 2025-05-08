<?php
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php', 'You must be logged in to manage job alerts', 'error');
}

$userId = $_SESSION['user_id'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            // Create new job alert
            $name = cleanInput($_POST['name']);
            $keywords = cleanInput($_POST['keywords']);
            $location = cleanInput($_POST['location']);
            $category = cleanInput($_POST['category']);
            $job_type = cleanInput($_POST['job_type']);
            $experience_level = cleanInput($_POST['experience_level']);
            $min_salary = !empty($_POST['min_salary']) ? (int)cleanInput($_POST['min_salary']) : null;
            $frequency = cleanInput($_POST['frequency']);
            
            // Basic validation
            $errors = [];
            
            if (empty($name)) {
                $errors[] = "Alert name is required";
            }
            
            if (empty($keywords) && empty($location) && empty($category) && empty($job_type) && empty($experience_level) && empty($min_salary)) {
                $errors[] = "Please provide at least one search criteria";
            }
            
            if (empty($errors)) {
                $createAlertQuery = "INSERT INTO job_alerts 
                    (user_id, name, keywords, location, category, job_type, experience_level, min_salary, frequency, created_at, last_sent_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NULL)";
                
                $stmt = $conn->prepare($createAlertQuery);
                $stmt->bind_param("issssssss", 
                    $userId, 
                    $name, 
                    $keywords, 
                    $location, 
                    $category, 
                    $job_type, 
                    $experience_level, 
                    $min_salary,
                    $frequency
                );
                
                if ($stmt->execute()) {
                    redirect('job-alerts.php', 'Job alert created successfully', 'success');
                } else {
                    $errors[] = "Error creating job alert: " . $conn->error;
                }
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['alert_id'])) {
            // Delete job alert
            $alertId = (int)$_POST['alert_id'];
            
            $deleteAlertQuery = "DELETE FROM job_alerts WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($deleteAlertQuery);
            $stmt->bind_param("ii", $alertId, $userId);
            
            if ($stmt->execute()) {
                redirect('job-alerts.php', 'Job alert deleted successfully', 'success');
            } else {
                $errors[] = "Error deleting job alert: " . $conn->error;
            }
        } elseif ($_POST['action'] === 'update' && isset($_POST['alert_id'])) {
            // Update job alert
            $alertId = (int)$_POST['alert_id'];
            $name = cleanInput($_POST['name']);
            $keywords = cleanInput($_POST['keywords']);
            $location = cleanInput($_POST['location']);
            $category = cleanInput($_POST['category']);
            $job_type = cleanInput($_POST['job_type']);
            $experience_level = cleanInput($_POST['experience_level']);
            $min_salary = !empty($_POST['min_salary']) ? (int)cleanInput($_POST['min_salary']) : null;
            $frequency = cleanInput($_POST['frequency']);
            
            // Basic validation
            $errors = [];
            
            if (empty($name)) {
                $errors[] = "Alert name is required";
            }
            
            if (empty($keywords) && empty($location) && empty($category) && empty($job_type) && empty($experience_level) && empty($min_salary)) {
                $errors[] = "Please provide at least one search criteria";
            }
            
            if (empty($errors)) {
                $updateAlertQuery = "UPDATE job_alerts 
                    SET name = ?, keywords = ?, location = ?, category = ?, job_type = ?, 
                        experience_level = ?, min_salary = ?, frequency = ?
                    WHERE id = ? AND user_id = ?";
                
                $stmt = $conn->prepare($updateAlertQuery);
                $stmt->bind_param("ssssssssii", 
                    $name, 
                    $keywords, 
                    $location, 
                    $category, 
                    $job_type, 
                    $experience_level, 
                    $min_salary,
                    $frequency,
                    $alertId,
                    $userId
                );
                
                if ($stmt->execute()) {
                    redirect('job-alerts.php', 'Job alert updated successfully', 'success');
                } else {
                    $errors[] = "Error updating job alert: " . $conn->error;
                }
            }
        }
    }
}

// Get job alerts for the user
$alertsQuery = "SELECT * FROM job_alerts WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($alertsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$alerts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories for dropdown
$categoriesQuery = "SELECT DISTINCT category FROM jobs WHERE category IS NOT NULL ORDER BY category";
$categories = $conn->query($categoriesQuery)->fetch_all(MYSQLI_ASSOC);

// Get experience levels for dropdown
$experienceQuery = "SELECT DISTINCT experience_level FROM jobs WHERE experience_level IS NOT NULL ORDER BY experience_level";
$experiences = $conn->query($experienceQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Job Alerts</h1>
                <button id="createAlertBtn" class="btn btn-primary text-sm">
                    <i class="fas fa-plus-circle mr-2"></i>Create New Alert
                </button>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <div class="font-bold">Please correct the following errors:</div>
                    <ul class="list-disc ml-5">
                        <?php foreach ($errors as $error): ?>
                            <li><?= h($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Create/Edit Alert Form (hidden by default) -->
            <div id="alertForm" class="bg-white shadow-md rounded-lg overflow-hidden mb-8 hidden">
                <form action="job-alerts.php" method="POST">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="alert_id" id="alertId" value="">
                    
                    <div class="p-6 border-b border-gray-200">
                        <h2 id="formTitle" class="text-xl font-bold text-gray-800 mb-4">Create Job Alert</h2>
                        <p class="text-gray-600 mb-4">Get notified when new jobs matching your criteria are posted.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Alert Name <span class="text-red-600">*</span></label>
                                <input type="text" id="name" name="name" required 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="e.g. Software Developer Jobs">
                            </div>
                            
                            <div>
                                <label for="frequency" class="block text-sm font-medium text-gray-700 mb-1">Alert Frequency <span class="text-red-600">*</span></label>
                                <select id="frequency" name="frequency" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="daily">Daily</option>
                                    <option value="weekly" selected>Weekly</option>
                                    <option value="instant">Instant</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="keywords" class="block text-sm font-medium text-gray-700 mb-1">Keywords</label>
                                <input type="text" id="keywords" name="keywords" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="e.g. developer, engineer, javascript">
                                <p class="mt-1 text-xs text-gray-500">Separate multiple keywords with commas</p>
                            </div>
                            
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <input type="text" id="location" name="location" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="e.g. New York, Remote">
                            </div>
                            
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <select id="category" name="category" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Any Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= h($category['category']) ?>"><?= h($category['category']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="job_type" class="block text-sm font-medium text-gray-700 mb-1">Job Type</label>
                                <select id="job_type" name="job_type" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Any Type</option>
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Internship">Internship</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="experience_level" class="block text-sm font-medium text-gray-700 mb-1">Experience Level</label>
                                <select id="experience_level" name="experience_level" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Any Level</option>
                                    <?php foreach ($experiences as $exp): ?>
                                        <option value="<?= h($exp['experience_level']) ?>"><?= h($exp['experience_level']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="min_salary" class="block text-sm font-medium text-gray-700 mb-1">Minimum Salary</label>
                                <input type="number" id="min_salary" name="min_salary" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="e.g. 50000"
                                       min="0" step="1000">
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                        <button type="button" id="cancelBtn" class="btn btn-outline text-sm">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary text-sm">
                            <span id="submitBtnText">Create Alert</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Alert Listings -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-xl font-bold text-gray-800">Your Job Alerts</h2>
                </div>
                
                <?php if (empty($alerts)): ?>
                    <div class="p-6 text-center">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-bell fa-4x"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">No alerts yet</h3>
                        <p class="text-gray-600 mb-4">Create job alerts to get notified when new jobs matching your criteria are posted.</p>
                        <button id="createFirstAlertBtn" class="btn btn-primary text-sm">
                            <i class="fas fa-plus-circle mr-2"></i>Create Your First Alert
                        </button>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($alerts as $alert): ?>
                            <div class="p-6 hover:bg-gray-50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800"><?= h($alert['name']) ?></h3>
                                        <p class="text-gray-600 text-sm">
                                            <?php 
                                            $criteria = [];
                                            if (!empty($alert['keywords'])) $criteria[] = "Keywords: " . h($alert['keywords']);
                                            if (!empty($alert['location'])) $criteria[] = "Location: " . h($alert['location']);
                                            if (!empty($alert['category'])) $criteria[] = "Category: " . h($alert['category']);
                                            if (!empty($alert['job_type'])) $criteria[] = "Job Type: " . h($alert['job_type']);
                                            if (!empty($alert['experience_level'])) $criteria[] = "Experience: " . h($alert['experience_level']);
                                            if (!empty($alert['min_salary'])) $criteria[] = "Min Salary: $" . number_format($alert['min_salary']);
                                            echo implode(" | ", $criteria);
                                            ?>
                                        </p>
                                        <div class="flex items-center mt-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 capitalize">
                                                <?= h($alert['frequency']) ?> alerts
                                            </span>
                                            <span class="text-gray-500 text-xs ml-4">
                                                Created <?= date('M j, Y', strtotime($alert['created_at'])) ?>
                                            </span>
                                            <?php if (!empty($alert['last_sent_at'])): ?>
                                                <span class="text-gray-500 text-xs ml-4">
                                                    Last sent: <?= date('M j, Y', strtotime($alert['last_sent_at'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button type="button" 
                                                class="edit-alert-btn text-indigo-600 hover:text-indigo-800"
                                                data-alert='<?= json_encode($alert) ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="job-alerts.php" method="POST" class="inline-block">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="alert_id" value="<?= $alert['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800" 
                                                    onclick="return confirm('Are you sure you want to delete this alert?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <a href="jobs.php?<?= http_build_query([
                                        'keywords' => $alert['keywords'],
                                        'location' => $alert['location'],
                                        'category' => $alert['category'],
                                        'type' => $alert['job_type'],
                                        'experience' => $alert['experience_level'],
                                        'min_salary' => $alert['min_salary']
                                    ]) ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                        View matching jobs <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const alertForm = document.getElementById('alertForm');
        const formTitle = document.getElementById('formTitle');
        const formAction = document.getElementById('formAction');
        const alertId = document.getElementById('alertId');
        const submitBtnText = document.getElementById('submitBtnText');
        const createAlertBtn = document.getElementById('createAlertBtn');
        const createFirstAlertBtn = document.getElementById('createFirstAlertBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const editBtns = document.querySelectorAll('.edit-alert-btn');
        
        // Form fields
        const nameField = document.getElementById('name');
        const keywordsField = document.getElementById('keywords');
        const locationField = document.getElementById('location');
        const categoryField = document.getElementById('category');
        const jobTypeField = document.getElementById('job_type');
        const experienceField = document.getElementById('experience_level');
        const minSalaryField = document.getElementById('min_salary');
        const frequencyField = document.getElementById('frequency');
        
        // Show create form
        function showCreateForm() {
            formTitle.textContent = 'Create Job Alert';
            formAction.value = 'create';
            alertId.value = '';
            submitBtnText.textContent = 'Create Alert';
            
            // Reset form fields
            nameField.value = '';
            keywordsField.value = '';
            locationField.value = '';
            categoryField.value = '';
            jobTypeField.value = '';
            experienceField.value = '';
            minSalaryField.value = '';
            frequencyField.value = 'weekly';
            
            alertForm.classList.remove('hidden');
            window.scrollTo({top: alertForm.offsetTop - 20, behavior: 'smooth'});
        }
        
        // Show edit form
        function showEditForm(alert) {
            formTitle.textContent = 'Edit Job Alert';
            formAction.value = 'update';
            alertId.value = alert.id;
            submitBtnText.textContent = 'Update Alert';
            
            // Populate form fields
            nameField.value = alert.name;
            keywordsField.value = alert.keywords;
            locationField.value = alert.location;
            categoryField.value = alert.category;
            jobTypeField.value = alert.job_type;
            experienceField.value = alert.experience_level;
            minSalaryField.value = alert.min_salary;
            frequencyField.value = alert.frequency;
            
            alertForm.classList.remove('hidden');
            window.scrollTo({top: alertForm.offsetTop - 20, behavior: 'smooth'});
        }
        
        // Hide form
        function hideForm() {
            alertForm.classList.add('hidden');
        }
        
        // Event listeners
        if (createAlertBtn) {
            createAlertBtn.addEventListener('click', showCreateForm);
        }
        
        if (createFirstAlertBtn) {
            createFirstAlertBtn.addEventListener('click', showCreateForm);
        }
        
        cancelBtn.addEventListener('click', hideForm);
        
        editBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const alert = JSON.parse(this.getAttribute('data-alert'));
                showEditForm(alert);
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?> 