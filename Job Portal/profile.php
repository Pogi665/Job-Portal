<?php
require_once 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php', 'You must be logged in to view your profile', 'error');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Common profile fields
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $location = cleanInput($_POST['location']);
    $bio = cleanInput($_POST['bio']);
    $website = cleanInput($_POST['website']);
    
    // Validate input
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid website URL";
    }
    
    // Check if email already exists (for someone else)
    $checkEmailQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($checkEmailQuery);
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email address is already in use by another account";
    }
    
    // Process job seeker specific fields
    $skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    $jobTitle = isset($_POST['job_title']) ? cleanInput($_POST['job_title']) : '';
    $jobTypes = isset($_POST['job_types']) ? $_POST['job_types'] : [];
    $salaryExpectation = isset($_POST['salary_expectation']) ? cleanInput($_POST['salary_expectation']) : '';
    $experience = isset($_POST['experience']) ? cleanInput($_POST['experience']) : '';
    $education = isset($_POST['education']) ? cleanInput($_POST['education']) : '';
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    
    // Handle profile picture upload
    $avatarUrl = $_SESSION['avatar_url'] ?? '';
    
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['avatar']['type'], $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPG, PNG and GIF are allowed";
        } elseif ($_FILES['avatar']['size'] > $maxSize) {
            $errors[] = "File is too large. Maximum size is 2MB";
        } else {
            // Create uploads directory if it doesn't exist
            $uploadDir = 'uploads/avatars/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $filename = uniqid('avatar_') . '_' . time() . '_' . $_FILES['avatar']['name'];
            $targetPath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
                $avatarUrl = $targetPath;
            } else {
                $errors[] = "Error uploading file";
            }
        }
    }
    
    // Handle resume upload
    $resumeUrl = '';
    if ($userRole == 'job_seeker' && isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['resume']['type'], $allowedTypes)) {
            $errors[] = "Invalid resume file type. Only PDF and DOC/DOCX are allowed";
        } elseif ($_FILES['resume']['size'] > $maxSize) {
            $errors[] = "Resume file is too large. Maximum size is 5MB";
        } else {
            // Create uploads directory if it doesn't exist
            $uploadDir = 'uploads/resumes/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $filename = uniqid('resume_') . '_' . time() . '_' . $_FILES['resume']['name'];
            $targetPath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
                $resumeUrl = $targetPath;
            } else {
                $errors[] = "Error uploading resume";
            }
        }
    }
    
    // If validation passes, update profile
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update user table (common fields)
            $updateUserQuery = "UPDATE users SET 
                name = ?, 
                email = ?, 
                phone = ?, 
                location = ?, 
                bio = ?, 
                website = ?, 
                avatar_url = ?
                WHERE id = ?";
            
            $stmt = $conn->prepare($updateUserQuery);
            $stmt->bind_param("sssssssi", 
                $name, 
                $email, 
                $phone, 
                $location, 
                $bio, 
                $website, 
                $avatarUrl, 
                $userId
            );
            $stmt->execute();
            
            // Update profile in session
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['avatar_url'] = $avatarUrl;
            
            // Update job seeker profile
            if ($userRole == 'job_seeker') {
                // Check if job seeker profile exists
                $checkQuery = "SELECT user_id FROM job_seeker_profiles WHERE user_id = ?";
                $stmt = $conn->prepare($checkQuery);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $profileExists = ($stmt->get_result()->num_rows > 0);
                
                if ($profileExists) {
                    // Update existing profile
                    $updateProfileQuery = "UPDATE job_seeker_profiles SET 
                        job_title = ?, 
                        experience = ?, 
                        education = ?, 
                        salary_expectation = ?, 
                        is_available = ?";
                    
                    // Add resume URL to query if a new one was uploaded
                    if (!empty($resumeUrl)) {
                        $updateProfileQuery .= ", resume_url = ?";
                    }
                    
                    $updateProfileQuery .= " WHERE user_id = ?";
                    
                    $stmt = $conn->prepare($updateProfileQuery);
                    
                    if (!empty($resumeUrl)) {
                        $stmt->bind_param("ssssis", 
                            $jobTitle, 
                            $experience, 
                            $education, 
                            $salaryExpectation, 
                            $isAvailable,
                            $resumeUrl,
                            $userId
                        );
                    } else {
                        $stmt->bind_param("ssssi", 
                            $jobTitle, 
                            $experience, 
                            $education, 
                            $salaryExpectation, 
                            $isAvailable,
                            $userId
                        );
                    }
                    $stmt->execute();
                } else {
                    // Create new profile
                    $createProfileQuery = "INSERT INTO job_seeker_profiles 
                        (user_id, job_title, experience, education, salary_expectation, resume_url, is_available) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($createProfileQuery);
                    $stmt->bind_param("isssssi", 
                        $userId, 
                        $jobTitle, 
                        $experience, 
                        $education, 
                        $salaryExpectation, 
                        $resumeUrl,
                        $isAvailable
                    );
                    $stmt->execute();
                }
                
                // Handle job types preferences
                if (!empty($jobTypes)) {
                    // Delete existing job types
                    $deleteJobTypesQuery = "DELETE FROM job_seeker_preferences WHERE user_id = ? AND preference_type = 'job_type'";
                    $stmt = $conn->prepare($deleteJobTypesQuery);
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // Insert new job types
                    $insertJobTypeQuery = "INSERT INTO job_seeker_preferences (user_id, preference_type, preference_value) VALUES (?, 'job_type', ?)";
                    $stmt = $conn->prepare($insertJobTypeQuery);
                    
                    foreach ($jobTypes as $jobType) {
                        $stmt->bind_param("is", $userId, $jobType);
                        $stmt->execute();
                    }
                }
                
                // Handle skills
                if (!empty($skills)) {
                    // Delete existing skills
                    $deleteSkillsQuery = "DELETE FROM user_skills WHERE user_id = ?";
                    $stmt = $conn->prepare($deleteSkillsQuery);
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    
                    // Insert new skills
                    $insertSkillQuery = "INSERT INTO user_skills (user_id, skill) VALUES (?, ?)";
                    $stmt = $conn->prepare($insertSkillQuery);
                    
                    foreach ($skills as $skill) {
                        $stmt->bind_param("is", $userId, $skill);
                        $stmt->execute();
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            displayMessage('Profile updated successfully', 'success');
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $errors[] = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Get user data
$userQuery = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get job seeker profile data if applicable
$jobSeekerProfile = null;
$skills = [];
$jobTypes = [];

if ($userRole == 'job_seeker') {
    $profileQuery = "SELECT * FROM job_seeker_profiles WHERE user_id = ?";
    $stmt = $conn->prepare($profileQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $jobSeekerProfile = $result->fetch_assoc();
    }
    
    // Get user skills
    $skillsQuery = "SELECT skill FROM user_skills WHERE user_id = ?";
    $stmt = $conn->prepare($skillsQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $skills[] = $row['skill'];
    }
    
    // Get job type preferences
    $jobTypesQuery = "SELECT preference_value FROM job_seeker_preferences WHERE user_id = ? AND preference_type = 'job_type'";
    $stmt = $conn->prepare($jobTypesQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $jobTypes[] = $row['preference_value'];
    }
}

// Get all skills for autocomplete
$allSkillsQuery = "SELECT DISTINCT skill FROM user_skills UNION SELECT DISTINCT skill FROM job_skills ORDER BY skill";
$allSkills = $conn->query($allSkillsQuery)->fetch_all(MYSQLI_ASSOC);
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Your Profile</h1>
            
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
            
            <form action="profile.php" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6 space-y-6">
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Basic Information</h2>
                        <div class="flex items-center mb-6">
                            <div class="mr-6">
                                <div class="relative w-32 h-32 group">
                                    <?php if (!empty($user['avatar_url'])): ?>
                                        <img src="<?= h($user['avatar_url']) ?>" alt="Profile Picture" class="w-32 h-32 rounded-full object-cover border-2 border-gray-200">
                                    <?php else: ?>
                                        <div class="w-32 h-32 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-4xl font-bold">
                                            <?= substr($user['name'], 0, 1) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute inset-0 bg-black bg-opacity-40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-white text-sm font-medium">Change Photo</span>
                                    </div>
                                    <input type="file" name="avatar" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*">
                                </div>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800"><?= h($user['name']) ?></h3>
                                <p class="text-gray-600"><?= h($user['email']) ?></p>
                                <p class="bg-indigo-100 text-indigo-800 text-sm inline-block px-2 py-1 rounded-full mt-2 capitalize">
                                    <?= str_replace('_', ' ', h($userRole)) ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-600">*</span></label>
                                <input type="text" id="name" name="name" required 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= h($user['name']) ?>">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-600">*</span></label>
                                <input type="email" id="email" name="email" required 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= h($user['email']) ?>">
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= h($user['phone'] ?? '') ?>">
                            </div>
                            
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <input type="text" id="location" name="location" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= h($user['location'] ?? '') ?>"
                                       placeholder="City, State or Country">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                                <textarea id="bio" name="bio" rows="3"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Tell us about yourself"><?= h($user['bio'] ?? '') ?></textarea>
                            </div>
                            
                            <div>
                                <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                <input type="url" id="website" name="website" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= h($user['website'] ?? '') ?>"
                                       placeholder="https://example.com">
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($userRole == 'job_seeker'): ?>
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Professional Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="job_title" class="block text-sm font-medium text-gray-700 mb-1">Job Title</label>
                                <input type="text" id="job_title" name="job_title" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= h($jobSeekerProfile['job_title'] ?? '') ?>"
                                       placeholder="e.g. Software Engineer">
                            </div>
                            
                            <div>
                                <label for="salary_expectation" class="block text-sm font-medium text-gray-700 mb-1">Salary Expectation</label>
                                <input type="text" id="salary_expectation" name="salary_expectation" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= h($jobSeekerProfile['salary_expectation'] ?? '') ?>"
                                       placeholder="e.g. $60,000 - $80,000 annually">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="experience" class="block text-sm font-medium text-gray-700 mb-1">Work Experience</label>
                                <textarea id="experience" name="experience" rows="4"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Summarize your work experience"><?= h($jobSeekerProfile['experience'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="education" class="block text-sm font-medium text-gray-700 mb-1">Education</label>
                                <textarea id="education" name="education" rows="3"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Your educational background"><?= h($jobSeekerProfile['education'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="md:col-span-2">
                                <div class="flex items-center">
                                    <input type="checkbox" id="is_available" name="is_available" 
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                           <?= isset($jobSeekerProfile['is_available']) && $jobSeekerProfile['is_available'] ? 'checked' : '' ?>>
                                    <label for="is_available" class="ml-2 block text-sm text-gray-900">
                                        I am open to job opportunities
                                    </label>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">Enabling this will make your profile more visible to employers</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Skills & Preferences</h2>
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Job Types</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="job_types[]" value="Full-time" 
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                               <?= in_array('Full-time', $jobTypes) ? 'checked' : '' ?>>
                                        <span class="ml-2 text-sm text-gray-700">Full-time</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="job_types[]" value="Part-time" 
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                               <?= in_array('Part-time', $jobTypes) ? 'checked' : '' ?>>
                                        <span class="ml-2 text-sm text-gray-700">Part-time</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="job_types[]" value="Contract" 
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                               <?= in_array('Contract', $jobTypes) ? 'checked' : '' ?>>
                                        <span class="ml-2 text-sm text-gray-700">Contract</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="job_types[]" value="Internship" 
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                               <?= in_array('Internship', $jobTypes) ? 'checked' : '' ?>>
                                        <span class="ml-2 text-sm text-gray-700">Internship</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label for="skills" class="block text-sm font-medium text-gray-700 mb-1">Skills</label>
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
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Resume</h2>
                        
                        <?php if (!empty($jobSeekerProfile['resume_url'])): ?>
                            <div class="mb-4">
                                <p class="text-sm text-gray-700 mb-2">Current resume:</p>
                                <div class="flex items-center p-3 bg-gray-50 rounded-md border border-gray-200">
                                    <i class="fas fa-file-pdf text-red-500 text-xl mr-3"></i>
                                    <div class="flex-grow">
                                        <p class="text-sm font-medium text-gray-700"><?= basename($jobSeekerProfile['resume_url']) ?></p>
                                    </div>
                                    <a href="<?= h($jobSeekerProfile['resume_url']) ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 ml-3">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <label for="resume" class="block text-sm font-medium text-gray-700 mb-1">Upload Resume</label>
                            <input type="file" id="resume" name="resume" 
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                   accept=".pdf,.doc,.docx">
                            <p class="mt-1 text-sm text-gray-500">Accepted formats: PDF, DOC, DOCX. Max size: 5MB</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-save mr-2"></i>Save Profile
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
        const availableSkills = <?= json_encode(array_column($allSkills, 'skill')) ?>;
        let selectedSkills = <?= json_encode($skills) ?>;
        
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

<?php require_once 'includes/footer.php'; ?> 