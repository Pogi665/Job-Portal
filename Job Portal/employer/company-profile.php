<?php
require_once '../includes/header.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !hasRole('employer')) {
    redirect('../login.php', 'You must be logged in as an employer to access this page', 'error');
}

$employerId = $_SESSION['user_id'];

// Check if company exists for this employer
$checkQuery = "SELECT * FROM companies WHERE employer_id = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("i", $employerId);
$stmt->execute();
$result = $stmt->get_result();
$isEdit = ($result->num_rows > 0);
$company = $isEdit ? $result->fetch_assoc() : null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $name = cleanInput($_POST['name']);
    $industry = cleanInput($_POST['industry']);
    $website = cleanInput($_POST['website']);
    $description = cleanInput($_POST['description']);
    $size = cleanInput($_POST['size']);
    $location = cleanInput($_POST['location']);
    $culture_text = cleanInput($_POST['culture_text']);
    
    // Basic validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Company name is required";
    }
    
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid website URL";
    }

    // Handle logo upload
    $logoUrl = $isEdit ? $company['logo_url'] : '';
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPG, PNG and GIF are allowed";
        } elseif ($_FILES['logo']['size'] > $maxSize) {
            $errors[] = "File is too large. Maximum size is 2MB";
        } else {
            // Create uploads directory if it doesn't exist
            $uploadDir = '../uploads/logos/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $filename = uniqid('logo_') . '_' . time() . '_' . $_FILES['logo']['name'];
            $targetPath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
                $logoUrl = $targetPath;
            } else {
                $errors[] = "Error uploading file";
            }
        }
    }
    
    // If validation passes, save company
    if (empty($errors)) {
        if ($isEdit) {
            // Update existing company
            $updateQuery = "UPDATE companies SET 
                name = ?, 
                industry = ?, 
                website = ?, 
                description = ?, 
                size = ?, 
                location = ?, 
                culture_text = ?";
            
            // Add logo to query if a new one was uploaded
            if (!empty($logoUrl)) {
                $updateQuery .= ", logo_url = ?";
            }
            
            $updateQuery .= " WHERE id = ?";
            
            $stmt = $conn->prepare($updateQuery);
            
            if (!empty($logoUrl)) {
                $stmt->bind_param("ssssssssi", 
                    $name, 
                    $industry, 
                    $website, 
                    $description, 
                    $size, 
                    $location, 
                    $culture_text,
                    $logoUrl,
                    $company['id']
                );
            } else {
                $stmt->bind_param("sssssssi", 
                    $name, 
                    $industry, 
                    $website, 
                    $description, 
                    $size, 
                    $location, 
                    $culture_text,
                    $company['id']
                );
            }
            
            if ($stmt->execute()) {
                redirect('dashboard.php', 'Company profile updated successfully', 'success');
            } else {
                $errors[] = "Error updating company: " . $conn->error;
            }
        } else {
            // Create new company
            $createQuery = "INSERT INTO companies 
                (employer_id, name, industry, website, description, size, location, culture_text, logo_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($createQuery);
            $stmt->bind_param("issssssss", 
                $employerId, 
                $name, 
                $industry, 
                $website, 
                $description, 
                $size, 
                $location, 
                $culture_text,
                $logoUrl
            );
            
            if ($stmt->execute()) {
                redirect('dashboard.php', 'Company profile created successfully', 'success');
            } else {
                $errors[] = "Error creating company: " . $conn->error;
            }
        }
    }
}
?>

<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-gray-900"><?= $isEdit ? 'Edit' : 'Create' ?> Company Profile</h1>
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

            <form action="company-profile.php" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded-lg overflow-hidden">
                <div class="p-6 space-y-6">
                    <div class="border-b pb-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Basic Information</h2>
                        <div class="flex items-center mb-6">
                            <div class="mr-6">
                                <div class="relative w-32 h-32 group">
                                    <?php if (!empty($logoUrl)): ?>
                                        <img src="<?= h($logoUrl) ?>" alt="Company Logo" class="w-32 h-32 object-contain border-2 border-gray-200 rounded-lg">
                                    <?php else: ?>
                                        <div class="w-32 h-32 bg-gray-200 flex items-center justify-center border-2 border-gray-300 rounded-lg">
                                            <i class="fas fa-building text-gray-400 text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded-lg">
                                        <span class="text-white text-sm font-medium">Upload Logo</span>
                                    </div>
                                    <input type="file" name="logo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*">
                                </div>
                            </div>
                            <div>
                                <p class="text-gray-600 mb-1">Upload your company logo</p>
                                <p class="text-sm text-gray-500">Recommended size: 400x400 pixels</p>
                                <p class="text-sm text-gray-500">Max file size: 2MB</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Company Name <span class="text-red-600">*</span></label>
                                <input type="text" id="name" name="name" required 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= $isEdit ? h($company['name']) : '' ?>">
                            </div>
                            
                            <div>
                                <label for="industry" class="block text-sm font-medium text-gray-700 mb-1">Industry</label>
                                <input type="text" id="industry" name="industry" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= $isEdit ? h($company['industry']) : '' ?>"
                                       placeholder="e.g. Technology, Healthcare, Finance">
                            </div>
                            
                            <div>
                                <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                <input type="url" id="website" name="website" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= $isEdit ? h($company['website']) : '' ?>"
                                       placeholder="https://example.com">
                            </div>
                            
                            <div>
                                <label for="size" class="block text-sm font-medium text-gray-700 mb-1">Company Size</label>
                                <select id="size" name="size" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Select Company Size</option>
                                    <option value="1-10" <?= $isEdit && $company['size'] == '1-10' ? 'selected' : '' ?>>1-10 employees</option>
                                    <option value="11-50" <?= $isEdit && $company['size'] == '11-50' ? 'selected' : '' ?>>11-50 employees</option>
                                    <option value="51-200" <?= $isEdit && $company['size'] == '51-200' ? 'selected' : '' ?>>51-200 employees</option>
                                    <option value="201-500" <?= $isEdit && $company['size'] == '201-500' ? 'selected' : '' ?>>201-500 employees</option>
                                    <option value="501-1000" <?= $isEdit && $company['size'] == '501-1000' ? 'selected' : '' ?>>501-1000 employees</option>
                                    <option value="1001+" <?= $isEdit && $company['size'] == '1001+' ? 'selected' : '' ?>>1001+ employees</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                                <input type="text" id="location" name="location" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                       value="<?= $isEdit ? h($company['location']) : '' ?>"
                                       placeholder="City, State or Country">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-4">About Your Company</h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Company Description</label>
                                <textarea id="description" name="description" rows="6"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Describe your company's mission, products/services, and what sets you apart"><?= $isEdit ? h($company['description']) : '' ?></textarea>
                            </div>
                            
                            <div>
                                <label for="culture_text" class="block text-sm font-medium text-gray-700 mb-1">Company Culture</label>
                                <textarea id="culture_text" name="culture_text" rows="4"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                          placeholder="Describe your company culture, values, and work environment"><?= $isEdit ? h($company['culture_text']) : '' ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <?= $isEdit ? '<i class="fas fa-save mr-2"></i>Update Profile' : '<i class="fas fa-plus-circle mr-2"></i>Create Profile' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
