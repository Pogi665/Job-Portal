<?php
require_once '../includes/header.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !hasRole('employer')) {
    redirect('../login.php', 'You must be logged in as an employer to post jobs', 'error');
}

// Get employer's company information
$employerId = $_SESSION['user_id'];
$companyQuery = "SELECT id, name FROM companies WHERE employer_id = ?";
$stmt = $conn->prepare($companyQuery);
$stmt->bind_param("i", $employerId);
$stmt->execute();
$companyResult = $stmt->get_result();

// If employer doesn't have a company profile, redirect to create one
if ($companyResult->num_rows === 0) {
    redirect('company-profile.php', 'Please create a company profile before posting jobs', 'warning');
}

$company = $companyResult->fetch_assoc();
$companyId = $company['id'];

// Get skills for autocomplete
$skillsQuery = "SELECT DISTINCT skill FROM job_skills ORDER BY skill";
$skills = $conn->query($skillsQuery)->fetch_all(MYSQLI_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $title = cleanInput($_POST['title']);
    $location = cleanInput($_POST['location']);
    $type = cleanInput($_POST['type']);
    $category = cleanInput($_POST['category']);
    $experience_level = cleanInput($_POST['experience_level']);
    $salary_min = !empty($_POST['salary_min']) ? (int)cleanInput($_POST['salary_min']) : null;
    $salary_max = !empty($_POST['salary_max']) ? (int)cleanInput($_POST['salary_max']) : null;
    $salary_currency = cleanInput($_POST['salary_currency']);
    $salary_period = cleanInput($_POST['salary_period']);
    $description = cleanInput($_POST['description']);
    $requirements = cleanInput($_POST['requirements']);
    $benefits = cleanInput($_POST['benefits']);
    $application_url = cleanInput($_POST['application_url']);
    $application_email = cleanInput($_POST['application_email']);
    $application_instructions = cleanInput($_POST['application_instructions']);
    $skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    
    // Basic validation
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Job title is required";
    }
    
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    
    if (empty($type)) {
        $errors[] = "Job type is required";
    }
    
    if (empty($description)) {
        $errors[] = "Job description is required";
    }
    
    if (!empty($application_email) && !filter_var($application_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (!empty($application_url) && !filter_var($application_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid URL";
    }
    
    // If validation passes, insert job
    if (empty($errors)) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert job
            $jobQuery = "INSERT INTO jobs (
                company_id, title, location, type, category, experience_level, 
                salary_min, salary_max, salary_currency, salary_period,
                description, requirements, benefits, 
                application_url, application_email, application_instructions, 
                status, posted_date, expires_date
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?,
                ?, ?, ?, 
                ?, ?, ?, 
                'Active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)
            )";
            
            $stmt = $conn->prepare($jobQuery);
            $stmt->bind_param(
                "isssssiiissssss",
                $companyId,
                $title,
                $location,
                $type,
                $category,
                $experience_level,
                $salary_min,
                $salary_max,
                $salary_currency,
                $salary_period,
                $description,
                $requirements,
                $benefits,
                $application_url,
                $application_email,
                $application_instructions
            );
            
            $stmt->execute();
            $jobId = $conn->insert_id;
            
            // Insert skills
            if (!empty($skills)) {
                $skillQuery = "INSERT INTO job_skills (job_id, skill) VALUES (?, ?)";
                $skillStmt = $conn->prepare($skillQuery);
                
                foreach ($skills as $skill) {
                    $skillStmt->bind_param("is", $jobId, $skill);
                    $skillStmt->execute();
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            redirect('dashboard.php', 'Job posted successfully!', 'success');
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = "Error posting job: " . $e->getMessage();
        }
    }
}
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Post a New Job</h1>
                <a href="dashboard.php" class="btn btn-outline text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
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
            
            <form action="post-job.php" method="POST" class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6 space-y-6">
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Job Details</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Job Title <span class="text-red-600">*</span></label>
                                <input type="text" id="title" name="title" required 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= isset($_POST['title']) ? h($_POST['title']) : '' ?>">
                            </div>
                            
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-red-600">*</span></label>
                                <input type="text" id="location" name="location" required 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= isset($_POST['location']) ? h($_POST['location']) : '' ?>"
                                       placeholder="City, State or Remote">
                            </div>
                            
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Job Type <span class="text-red-600">*</span></label>
                                <select id="type" name="type" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select Job Type</option>
                                    <option value="Full-time" <?= isset($_POST['type']) && $_POST['type'] == 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                                    <option value="Part-time" <?= isset($_POST['type']) && $_POST['type'] == 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                                    <option value="Contract" <?= isset($_POST['type']) && $_POST['type'] == 'Contract' ? 'selected' : '' ?>>Contract</option>
                                    <option value="Internship" <?= isset($_POST['type']) && $_POST['type'] == 'Internship' ? 'selected' : '' ?>>Internship</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <input type="text" id="category" name="category" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= isset($_POST['category']) ? h($_POST['category']) : '' ?>"
                                       placeholder="e.g. Engineering, Marketing, Finance">
                            </div>
                            
                            <div>
                                <label for="experience_level" class="block text-sm font-medium text-gray-700 mb-1">Experience Level</label>
                                <select id="experience_level" name="experience_level" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select Experience Level</option>
                                    <option value="Entry Level" <?= isset($_POST['experience_level']) && $_POST['experience_level'] == 'Entry Level' ? 'selected' : '' ?>>Entry Level</option>
                                    <option value="Mid Level" <?= isset($_POST['experience_level']) && $_POST['experience_level'] == 'Mid Level' ? 'selected' : '' ?>>Mid Level</option>
                                    <option value="Senior Level" <?= isset($_POST['experience_level']) && $_POST['experience_level'] == 'Senior Level' ? 'selected' : '' ?>>Senior Level</option>
                                    <option value="Executive" <?= isset($_POST['experience_level']) && $_POST['experience_level'] == 'Executive' ? 'selected' : '' ?>>Executive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Salary Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="salary_min" class="block text-sm font-medium text-gray-700 mb-1">Minimum Salary</label>
                                <input type="number" id="salary_min" name="salary_min" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= isset($_POST['salary_min']) ? h($_POST['salary_min']) : '' ?>"
                                       min="0" step="1000">
                            </div>
                            
                            <div>
                                <label for="salary_max" class="block text-sm font-medium text-gray-700 mb-1">Maximum Salary</label>
                                <input type="number" id="salary_max" name="salary_max" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= isset($_POST['salary_max']) ? h($_POST['salary_max']) : '' ?>"
                                       min="0" step="1000">
                            </div>
                            
                            <div>
                                <label for="salary_currency" class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                                <select id="salary_currency" name="salary_currency" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="USD" <?= isset($_POST['salary_currency']) && $_POST['salary_currency'] == 'USD' ? 'selected' : '' ?>>USD</option>
                                    <option value="EUR" <?= isset($_POST['salary_currency']) && $_POST['salary_currency'] == 'EUR' ? 'selected' : '' ?>>EUR</option>
                                    <option value="GBP" <?= isset($_POST['salary_currency']) && $_POST['salary_currency'] == 'GBP' ? 'selected' : '' ?>>GBP</option>
                                    <option value="CAD" <?= isset($_POST['salary_currency']) && $_POST['salary_currency'] == 'CAD' ? 'selected' : '' ?>>CAD</option>
                                    <option value="AUD" <?= isset($_POST['salary_currency']) && $_POST['salary_currency'] == 'AUD' ? 'selected' : '' ?>>AUD</option>
                                    <option value="JPY" <?= isset($_POST['salary_currency']) && $_POST['salary_currency'] == 'JPY' ? 'selected' : '' ?>>JPY</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="salary_period" class="block text-sm font-medium text-gray-700 mb-1">Payment Period</label>
                                <select id="salary_period" name="salary_period" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="annually" <?= isset($_POST['salary_period']) && $_POST['salary_period'] == 'annually' ? 'selected' : '' ?>>Annually</option>
                                    <option value="monthly" <?= isset($_POST['salary_period']) && $_POST['salary_period'] == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                    <option value="hourly" <?= isset($_POST['salary_period']) && $_POST['salary_period'] == 'hourly' ? 'selected' : '' ?>>Hourly</option>
                                </select>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">Leave salary fields blank if you prefer not to disclose</p>
                    </div>
                    
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Job Description</h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-600">*</span></label>
                                <textarea id="description" name="description" rows="6" required
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Provide a detailed description of the job role, responsibilities, and expectations"><?= isset($_POST['description']) ? h($_POST['description']) : '' ?></textarea>
                            </div>
                            
                            <div>
                                <label for="requirements" class="block text-sm font-medium text-gray-700 mb-1">Requirements</label>
                                <textarea id="requirements" name="requirements" rows="4"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="List qualifications, skills, and experience required"><?= isset($_POST['requirements']) ? h($_POST['requirements']) : '' ?></textarea>
                            </div>
                            
                            <div>
                                <label for="benefits" class="block text-sm font-medium text-gray-700 mb-1">Benefits</label>
                                <textarea id="benefits" name="benefits" rows="4"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="List perks and benefits offered with this position"><?= isset($_POST['benefits']) ? h($_POST['benefits']) : '' ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Skills & Qualifications</h2>
                        
                        <div>
                            <label for="skills" class="block text-sm font-medium text-gray-700 mb-1">Required Skills</label>
                            <div class="skill-container">
                                <div id="skill-tags" class="flex flex-wrap gap-2 mb-2"></div>
                                <div class="flex">
                                    <input type="text" id="skill-input" 
                                           class="flex-grow border-gray-300 rounded-l-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="Type a skill and press Enter (e.g. JavaScript, Project Management)">
                                    <button type="button" id="add-skill" class="px-4 py-2 bg-indigo-600 text-white rounded-r-md hover:bg-indigo-700">Add</button>
                                </div>
                                <div id="skill-suggestions" class="hidden mt-1 max-h-40 overflow-y-auto bg-white border border-gray-300 rounded-md"></div>
                                <div id="skills-container" class="hidden"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Application Options</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="application_email" class="block text-sm font-medium text-gray-700 mb-1">Application Email</label>
                                <input type="email" id="application_email" name="application_email" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= isset($_POST['application_email']) ? h($_POST['application_email']) : '' ?>"
                                       placeholder="email@example.com">
                            </div>
                            
                            <div>
                                <label for="application_url" class="block text-sm font-medium text-gray-700 mb-1">External Application URL</label>
                                <input type="url" id="application_url" name="application_url" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= isset($_POST['application_url']) ? h($_POST['application_url']) : '' ?>"
                                       placeholder="https://yourcompany.com/careers/job123">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="application_instructions" class="block text-sm font-medium text-gray-700 mb-1">Application Instructions</label>
                                <textarea id="application_instructions" name="application_instructions" rows="3"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Any specific instructions for applicants"><?= isset($_POST['application_instructions']) ? h($_POST['application_instructions']) : '' ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-paper-plane mr-2"></i>Post Job
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Skills handling
        const skillInput = document.getElementById('skill-input');
        const skillTags = document.getElementById('skill-tags');
        const addSkillBtn = document.getElementById('add-skill');
        const skillSuggestions = document.getElementById('skill-suggestions');
        const skillsContainer = document.getElementById('skills-container');
        
        // Available skills for autocomplete (loaded from PHP)
        const availableSkills = <?= json_encode(array_column($skills, 'skill')) ?>;
        let selectedSkills = <?= isset($_POST['skills']) ? json_encode($_POST['skills']) : '[]' ?>;
        
        // Initialize selected skills
        selectedSkills.forEach(skill => addSkillTag(skill));
        
        // Add skill when button is clicked
        addSkillBtn.addEventListener('click', function() {
            addSkill();
        });
        
        // Add skill when Enter is pressed
        skillInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addSkill();
            }
        });
        
        // Show suggestions when typing
        skillInput.addEventListener('input', function() {
            const input = this.value.trim().toLowerCase();
            
            if (input.length < 2) {
                skillSuggestions.classList.add('hidden');
                return;
            }
            
            const matches = availableSkills.filter(skill => 
                skill.toLowerCase().includes(input) && !selectedSkills.includes(skill)
            ).slice(0, 5);
            
            if (matches.length > 0) {
                skillSuggestions.innerHTML = '';
                
                matches.forEach(skill => {
                    const div = document.createElement('div');
                    div.className = 'px-3 py-2 cursor-pointer hover:bg-gray-100';
                    div.textContent = skill;
                    div.addEventListener('click', function() {
                        skillInput.value = '';
                        addSkillTag(skill);
                        selectedSkills.push(skill);
                        updateHiddenSkills();
                        skillSuggestions.classList.add('hidden');
                    });
                    skillSuggestions.appendChild(div);
                });
                
                skillSuggestions.classList.remove('hidden');
            } else {
                skillSuggestions.classList.add('hidden');
            }
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!skillInput.contains(e.target) && !skillSuggestions.contains(e.target)) {
                skillSuggestions.classList.add('hidden');
            }
        });
        
        // Add skill function
        function addSkill() {
            const skill = skillInput.value.trim();
            
            if (skill && !selectedSkills.includes(skill)) {
                addSkillTag(skill);
                selectedSkills.push(skill);
                updateHiddenSkills();
                skillInput.value = '';
                skillSuggestions.classList.add('hidden');
            }
        }
        
        // Add a skill tag to the UI
        function addSkillTag(skill) {
            const tag = document.createElement('div');
            tag.className = 'bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm font-medium flex items-center';
            
            const text = document.createElement('span');
            text.textContent = skill;
            
            const removeBtn = document.createElement('button');
            removeBtn.className = 'ml-2 text-indigo-600 hover:text-indigo-800 focus:outline-none';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.type = 'button';
            removeBtn.addEventListener('click', function() {
                tag.remove();
                selectedSkills = selectedSkills.filter(s => s !== skill);
                updateHiddenSkills();
            });
            
            tag.appendChild(text);
            tag.appendChild(removeBtn);
            skillTags.appendChild(tag);
        }
        
        // Update hidden input fields for skills
        function updateHiddenSkills() {
            skillsContainer.innerHTML = '';
            selectedSkills.forEach((skill, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'skills[]';
                input.value = skill;
                skillsContainer.appendChild(input);
            });
        }
        
        // Initialize hidden skills inputs
        updateHiddenSkills();
    });
</script>

<?php require_once '../includes/footer.php'; ?> 