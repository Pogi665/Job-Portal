<?php
// Require authentication
require_once 'includes/auth_guard.php';

require_once 'includes/header.php';

// Check if user is a job seeker
if (!hasRole('job_seeker')) {
    setMessage('error', 'Only job seekers can apply for jobs');
    header('Location: index.php');
    exit;
}

// Check if job_id was provided
if (!isset($_GET['job_id'])) {
    setMessage('error', 'No job specified');
    header('Location: jobs.php');
    exit;
}

$userId = $_SESSION['user_id'];
$jobId = (int)$_GET['job_id'];

// Check if the job exists and is active
$jobQuery = "SELECT j.*, c.name as company_name FROM jobs j 
            JOIN companies c ON j.company_id = c.id 
            WHERE j.id = ? AND j.status = 'Active'";
$stmt = $conn->prepare($jobQuery);
$stmt->bind_param("i", $jobId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setMessage('error', 'Job not found or no longer active');
    header('Location: jobs.php');
    exit;
}

$job = $result->fetch_assoc();

// Check if already applied
$checkQuery = "SELECT id, status FROM applications WHERE user_id = ? AND job_id = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("ii", $userId, $jobId);
$stmt->execute();
$result = $stmt->get_result();

$alreadyApplied = false;
$applicationStatus = '';
if ($result->num_rows > 0) {
    $application = $result->fetch_assoc();
    $alreadyApplied = true;
    $applicationStatus = $application['status'];
}

// Get user's resumes
$resumeQuery = "SELECT resume_url FROM job_seeker_profiles WHERE user_id = ?";
$stmt = $conn->prepare($resumeQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$hasResume = ($result->num_rows > 0 && $result->fetch_assoc()['resume_url'] != '');

// Process application submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$alreadyApplied) {
    // Validate and process application
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $resumeId = null;
    
    // Check if using existing resume
    $useExisting = isset($_POST['use_existing']) && $_POST['use_existing'] == '1';
    
    // Handle resume upload if there's a file and not using existing
    $resumeUrl = '';
    if (!$useExisting && isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $allowed = array("pdf" => "application/pdf", "doc" => "application/msword", "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document");
        $filename = $_FILES['resume']['name'];
        $filetype = $_FILES['resume']['type'];
        $filesize = $_FILES['resume']['size'];
        
        // Validate file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed)) {
            $error = "Error: Please select a valid resume file format (PDF, DOC, DOCX).";
        } else if ($filesize > 5 * 1024 * 1024) { // 5MB max
            $error = "Error: File size is larger than the allowed limit (5MB).";
        } else {
            // Generate unique filename
            $newFilename = uniqid() . "_" . $userId . "." . $ext;
            $uploadPath = "uploads/resumes/" . $newFilename;
            
            // Create directory if it doesn't exist
            if (!file_exists('uploads/resumes/')) {
                mkdir('uploads/resumes/', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $uploadPath)) {
                $resumeUrl = $uploadPath;
                
                // Update user profile with new resume
                $updateResumeQuery = "UPDATE job_seeker_profiles SET resume_url = ? WHERE user_id = ?";
                $stmt = $conn->prepare($updateResumeQuery);
                $stmt->bind_param("si", $resumeUrl, $userId);
                $stmt->execute();
            } else {
                $error = "Error: There was a problem uploading your resume.";
            }
        }
    }
    
    // If no error, submit the application
    if (empty($error)) {
        // If using existing resume and no new one uploaded, get the existing resume URL
        if ($useExisting && empty($resumeUrl)) {
            $resumeQuery = "SELECT resume_url FROM job_seeker_profiles WHERE user_id = ?";
            $stmt = $conn->prepare($resumeQuery);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $resumeUrl = $row['resume_url'];
            }
        }
        
        $insertQuery = "INSERT INTO applications (job_id, user_id, cover_letter, contact_phone, resume_url, status, applied_date) 
                        VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iisss", $jobId, $userId, $coverLetter, $phone, $resumeUrl);
        
        if ($stmt->execute()) {
            $success = "Your application has been submitted successfully!";
            $alreadyApplied = true;
            $applicationStatus = 'Pending';
            
            // Optional: Send notification to employer
            // ...
        } else {
            $error = "Error submitting your application. Please try again.";
        }
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto bg-white shadow-md rounded-lg overflow-hidden">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Apply for: <?php echo htmlspecialchars($job['title']); ?></h1>
            <p class="text-gray-600 mb-4">at <?php echo htmlspecialchars($job['company_name']); ?> - <?php echo htmlspecialchars($job['location']); ?></p>
            
            <?php if(!empty($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if($alreadyApplied): ?>
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
                    <p class="font-medium">You have already applied for this position.</p>
                    <p>Application status: <span class="font-semibold"><?php echo $applicationStatus; ?></span></p>
                </div>
                <div class="mt-6">
                    <a href="job-details.php?id=<?php echo $jobId; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Job Details
                    </a>
                    <a href="jobs.php" class="ml-3 btn btn-primary">
                        <i class="fas fa-search mr-2"></i> Browse More Jobs
                    </a>
                </div>
            <?php else: ?>
                <form action="apply.php?job_id=<?php echo $jobId; ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-6">
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-input w-full" required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="resume" class="block text-sm font-medium text-gray-700 mb-1">Resume</label>
                        <input type="file" id="resume" name="resume" class="form-input w-full" <?php echo !$hasResume ? 'required' : ''; ?>>
                        <p class="text-sm text-gray-500 mt-1">Upload your resume (PDF, DOC, DOCX - Max 5MB)</p>
                        
                        <?php if($hasResume): ?>
                        <div class="mt-2">
                            <div class="flex items-center">
                                <input type="checkbox" id="use_existing" name="use_existing" value="1" class="form-checkbox h-4 w-4 text-indigo-600">
                                <label for="use_existing" class="ml-2 block text-sm text-gray-700">Use my existing resume on file</label>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-6">
                        <label for="cover_letter" class="block text-sm font-medium text-gray-700 mb-1">Cover Letter</label>
                        <textarea id="cover_letter" name="cover_letter" rows="6" class="form-textarea w-full" placeholder="Tell the employer why you're a good fit for this position..."></textarea>
                    </div>
                    
                    <div class="mt-8 flex justify-between">
                        <a href="job-details.php?id=<?php echo $jobId; ?>" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane mr-2"></i> Submit Application
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const useExistingCheckbox = document.getElementById('use_existing');
    const resumeInput = document.getElementById('resume');
    
    if (useExistingCheckbox && resumeInput) {
        useExistingCheckbox.addEventListener('change', function() {
            if (this.checked) {
                resumeInput.disabled = true;
                resumeInput.required = false;
            } else {
                resumeInput.disabled = false;
                resumeInput.required = <?php echo !$hasResume ? 'true' : 'false'; ?>;
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 