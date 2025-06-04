<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php?notice=login_required_profile&return_to=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    // Consider a more user-friendly error page or message system for db errors
    $db_error_message = "Database connection failed. Please try again later.";
    // For now, we'll let the page try to render and show this error.
}

$current_viewing_username = $_SESSION["username"]; // The user who is logged in
$profile_username = isset($_GET['username']) ? $_GET['username'] : $current_viewing_username; // The profile being viewed

$user_data = null;
$page_title = "User Profile";

if (!isset($db_error_message)) {
    $sql = "SELECT username, full_name, role, bio, location, company, email, contact_number AS phone FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $profile_username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            $page_title = "Profile of " . htmlspecialchars($user_data['full_name'] ?? $user_data['username']);
        } else {
            $page_error = "Profile not found for user: " . htmlspecialchars($profile_username);
        }
        $stmt->close();
    } else {
        $page_error = "Error preparing to fetch profile data.";
        error_log("DB Prepare Error in profile.php: " . $conn->error);
    }
} else {
    $page_error = $db_error_message;
}

// Close connection if it was opened
if (isset($conn) && $conn instanceof mysqli && empty($db_error_message)) {
    // $conn->close(); // Keep open for header.php if it needs it, will be closed at end of page. 
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - CareerLynk</title>
    <?php /* header.php includes Tailwind, FontAwesome, style.css (Inter font) */ ?>
</head>
<body class="bg-gray-100">

<?php include 'header.php'; ?>

<main class="container mx-auto mt-8 mb-10 px-4">
    <div class="max-w-3xl mx-auto bg-white p-6 sm:p-8 rounded-lg shadow-xl">
        
        <?php if (!empty($page_error)): ?>
            <div class="alert alert-danger mb-6" role="alert">
                <?php echo $page_error; ?>
            </div>
            <?php if ($page_error === "Profile not found for user: " . htmlspecialchars($profile_username) || isset($db_error_message) ): ?>
                <div class="mt-6 text-center">
                    <a href="directory.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-arrow-left mr-1"></i> Back to User Directory
                    </a>
                </div>
            <?php endif; ?>
        <?php elseif ($user_data): ?>
            <div class="flex flex-col items-center md:flex-row md:items-start mb-6">
                <?php 
                // Basic avatar placeholder using initials
                $initials = '';
                if (!empty($user_data['full_name'])) {
                    $parts = explode(" ", $user_data['full_name']);
                    $initials = $parts[0][0] ?? '';
                    if (count($parts) > 1) {
                        $initials .= $parts[count($parts)-1][0] ?? '';
                    }
                } elseif (!empty($user_data['username'])) {
                    $initials = strtoupper($user_data['username'][0]);
                }
                ?>
                <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                    <div class="w-24 h-24 sm:w-32 sm:h-32 bg-blue-500 rounded-full flex items-center justify-center text-white text-4xl sm:text-5xl font-bold">
                        <?php echo htmlspecialchars(strtoupper($initials)); ?>
                    </div>
                </div>
                <div class="text-center md:text-left">
                    <h1 class="text-3xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($user_data['full_name'] ?? 'N/A'); ?></h1>
                    <p class="text-lg text-gray-600 mb-1">@<?php echo htmlspecialchars($user_data['username']); ?></p>
                    <p class="text-md text-blue-600 font-semibold capitalize"><?php echo htmlspecialchars(str_replace("_", " ", $user_data['role'] ?? 'N/A')); ?></p>
                </div>
            </div>

            <div class="space-y-6">
                <?php 
                $profile_fields = [
                    'Full Name' => $user_data['full_name'] ?? null,
                    'Role' => !empty($user_data['role']) ? ucfirst(str_replace("_"," ", $user_data['role'])) : null,
                    'Email' => $user_data['email'] ?? null,
                    'Phone' => $user_data['phone'] ?? null,
                    'Location' => $user_data['location'] ?? null,
                ];

                if ($user_data['role'] === 'job_employer') {
                    $profile_fields['Company'] = $user_data['company'] ?? null;
                }
                // Bio is handled separately due to nl2br
                ?>

                <?php foreach($profile_fields as $label => $value): ?>
                    <?php if (!empty($value)): ?>
                    <div class="border-t border-gray-200 pt-4">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 mb-1"><?php echo htmlspecialchars($label); ?></dt>
                            <dd class="text-md text-gray-800"><?php echo htmlspecialchars($value); ?></dd>
                        </dl>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if (!empty($user_data['bio'])): ?>
                <div class="border-t border-gray-200 pt-4">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 mb-1">Bio</dt>
                        <dd class="text-md text-gray-800 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($user_data['bio'])); ?></dd>
                    </dl>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($profile_username === $current_viewing_username): ?>
                <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                    <a href="edit_profile.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow hover:shadow-md transition duration-150 ease-in-out">
                        <i class="fas fa-pencil-alt mr-2"></i>Edit Profile
                    </a>
                </div>
            <?php endif; ?>

        <?php endif; /* end if user_data */ ?>

    </div>
</main>

<?php /* include 'footer.php'; // Temporarily removed to fix include error */ ?>
<?php
// Close connection if it was opened and is still active
if (isset($conn) && $conn instanceof mysqli && empty($db_error_message) && $conn->ping()) {
    $conn->close();
}
?>
</body>
</html>
