<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$messages = []; // To store error or success messages for display

// Check for messages from redirects
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'update_failed') {
        $messages[] = ['type' => 'error', 'text' => 'Profile update failed. Please try again.'];
    } elseif ($_GET['error'] === 'validation') {
        // Specific validation errors would ideally be passed or handled by re-displaying form with details
        $messages[] = ['type' => 'error', 'text' => 'Invalid input. Please check your entries.'];
    }
}
if (isset($_GET['success']) && $_GET['success'] === 'profile_updated_previous') { // if redirected from self after update
    $messages[] = ['type' => 'success', 'text' => 'Profile updated successfully!'];
}


$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    error_log("Edit Profile DB Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
    // Can't show messages on this page if DB is down for initial fetch, redirect to a general error page or dashboard
    header("Location: dashboard.php?error=db_connection_critical"); 
    exit();
}

$username = $_SESSION["username"];
$user_id = $_SESSION["user_id"]; // Assuming user_id is in session for consistency

// Initialize variables for form display
$fullname = $role = $bio = $location = $company = $email = $phone = '';

// Fetch current user info
$sql = "SELECT full_name, role, bio, location, company, email, contact_number AS phone FROM users WHERE id=?"; // Use id for fetching, changed fullname and phone
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log("Edit Profile Prepare failed (fetch user): (" . $conn->errno . ") " . $conn->error . " for user_id: " . $user_id);
    $messages[] = ['type' => 'error', 'text' => 'Error preparing to fetch your profile. Please try again later.'];
} else {
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        error_log("Edit Profile Execute failed (fetch user): (" . $stmt->errno . ") " . $stmt->error . " for user_id: " . $user_id);
        $messages[] = ['type' => 'error', 'text' => 'Error fetching your profile. Please try again later.'];
    } else {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $fullname = $row['full_name'];
            $role = $row['role'];
            $bio = $row['bio'];
            $location = $row['location'];
            $company = $row['company'];
            $email = $row['email'];
            $phone = $row['phone'];
        } else {
            error_log("Edit Profile: User not found in DB for id: " . $user_id);
            $messages[] = ['type' => 'error', 'text' => 'Could not find your profile information.'];
            // Potentially redirect or prevent form display if user not found
        }
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $validation_errors = [];

    $new_fullname = trim($_POST["full_name"]);
    $new_bio = trim($_POST["bio"]);
    $new_location = trim($_POST["location"]);
    $new_email = trim($_POST["email"]);
    $new_phone = trim($_POST["contact_number"]);
    $new_company = ($role === "job_employer") ? trim($_POST["company"]) : null;

    // Validation
    if (empty($new_fullname)) {
        $validation_errors[] = "Full name is required.";
    }
    if (empty($new_email)) {
        $validation_errors[] = "Email is required.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $validation_errors[] = "Invalid email format.";
    }
    // Add more validation for phone, location, bio, company as needed

    if (!empty($validation_errors)) {
        foreach ($validation_errors as $error) {
            $messages[] = ['type' => 'error', 'text' => $error];
        }
        // Re-populate form fields with submitted (but potentially unsanitized for display here) values
        // Or better, use the sanitized versions for re-display. For now, simple re-population.
        $fullname = $new_fullname; 
        $bio = $new_bio; 
        $location = $new_location; 
        $email = $new_email; 
        $phone = $new_phone; 
        if ($role === "job_employer") $company = $new_company;
    } else {
        // Sanitize for database (htmlspecialchars is okay for general text, further specific sanitization might be needed)
        $s_fullname = htmlspecialchars($new_fullname, ENT_QUOTES, 'UTF-8');
        $s_bio = htmlspecialchars($new_bio, ENT_QUOTES, 'UTF-8');
        $s_location = htmlspecialchars($new_location, ENT_QUOTES, 'UTF-8');
        $s_email = htmlspecialchars($new_email, ENT_QUOTES, 'UTF-8'); // Email already validated
        $s_phone = htmlspecialchars($new_phone, ENT_QUOTES, 'UTF-8'); 
        $s_company = ($role === "job_employer") ? htmlspecialchars($new_company, ENT_QUOTES, 'UTF-8') : null;

        $updateSql = "UPDATE users SET full_name=?, bio=?, location=?, email=?, contact_number=?" . ($role === "job_employer" ? ", company=?" : "") . " WHERE id=?"; // use id for update, changed fullname and phone
        $updateStmt = $conn->prepare($updateSql);

        if ($updateStmt === false) {
            error_log("Edit Profile Prepare failed (update user): (" . $conn->errno . ") " . $conn->error . " for user_id: " . $user_id);
            $messages[] = ['type' => 'error', 'text' => 'Error preparing to update profile. Please try again.'];
        } else {
            if ($role === "job_employer") {
                $updateStmt->bind_param("ssssssi", $s_fullname, $s_bio, $s_location, $s_email, $s_phone, $s_company, $user_id);
            } else {
                $updateStmt->bind_param("sssssi", $s_fullname, $s_bio, $s_location, $s_email, $s_phone, $user_id);
            }

            if ($updateStmt->execute()) {
                // $_SESSION['username'] = $s_fullname; // Removed: This was causing issues, username should be the login ID, not display name.
                // If email is used for login or critical identification, consider re-verification or impact on session if it changes.
                $_SESSION['email'] = $s_email; // Update email in session if it was changed
                
                // Redirect to profile.php with a success message
                // $conn->close(); // Close before redirect
                // header("Location: profile.php?success=profile_updated");
                // For now, let's redirect to self to show message on edit_profile page, then user can go to profile
                header("Location: edit_profile.php?success=profile_updated_previous");
                exit();
            } else {
                error_log("Edit Profile Execute failed (update user): (" . $updateStmt->errno . ") " . $updateStmt->error . " for user_id: " . $user_id);
                $messages[] = ['type' => 'error', 'text' => 'Profile update failed. Please try again.'];
            }
            $updateStmt->close();
        }
    }
}
$conn->close(); // Close connection at the end of script processing if not already closed

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - CareerLynk</title>
</head>
<body class="bg-gray-100">
    <?php include 'header.php'; ?>

    <main class="container mx-auto mt-10 mb-10 px-4">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-xl">
            <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">Edit Profile</h1>

            <?php if (!empty($messages)): ?>
                <div class="mb-6">
                    <?php foreach ($messages as $message): ?>
                        <div class="alert <?php echo ($message['type'] === 'success' ? 'alert-success' : 'alert-danger'); ?> p-4 mb-4" role="alert">
                            <?php echo htmlspecialchars($message['text']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="edit_profile.php" class="space-y-6">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($fullname ?? ''); ?>" required
                           class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <?php if (isset($role) && $role === "job_employer"): ?>
                <div>
                    <label for="company" class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                    <input type="text" name="company" id="company" value="<?php echo htmlspecialchars($company ?? ''); ?>"
                           class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <?php endif; ?>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required
                           class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="contact_number" id="contact_number" value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                           class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea name="bio" id="bio" rows="5"
                              class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?php echo htmlspecialchars($bio ?? ''); ?></textarea>
                </div>

                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($location ?? ''); ?>"
                           class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <button type="submit"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
