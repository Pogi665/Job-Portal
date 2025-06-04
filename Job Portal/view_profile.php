<?php
session_start();
if (!isset($_SESSION["username"])) {
    // If not logged in, consider redirecting to login or showing a public error.
    // For now, maintaining existing behavior of redirecting to login.
    $_SESSION['message'] = "You must be logged in to view profiles.";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    error_log("DB Connection failed in view_profile.php: " . $conn->connect_error);
    // Can't show a styled error easily if DB is down and header.php relies on it for style links
    die("Database connection error. Please try again later."); 
}

$profile_user = null;
$page_error = '';
$viewing_username = '';

if (isset($_GET['applicant'])) {
    $viewing_username = trim($_GET['applicant']);

    if (empty($viewing_username)) {
        $page_error = "No applicant username specified.";
    } else {
        // Fetch applicant's profile information
        // Assuming consistent column names: full_name, email, bio, profile_picture_url
        $profileQuery = "SELECT username, full_name, email, bio, role, profile_picture_url, created_at FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($profileQuery);
        
        if ($stmt) {
            $stmt->bind_param("s", $viewing_username);
            $stmt->execute();
            $profileResult = $stmt->get_result();

            if ($profileResult->num_rows > 0) {
                $profile_user = $profileResult->fetch_assoc();
            } else {
                $page_error = "Profile not found for user: " . htmlspecialchars($viewing_username);
            }
            $stmt->close();
        } else {
            error_log("Prepare statement failed in view_profile.php: " . $conn->error);
            $page_error = "Error retrieving profile information.";
        }
    }
} else {
    $page_error = "No applicant specified to view.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile: <?php echo htmlspecialchars($profile_user['username'] ?? $viewing_username); ?> - Job Portal</title>
    <!-- Tailwind CSS, Inter font, and Font Awesome are included via header.php -->
</head>
<body class="bg-gray-100 flex flex-col min-h-screen font-sans">

<?php include 'header.php'; ?>

<main class="flex-grow container mx-auto mt-8 mb-12 px-4">
    <div class="max-w-3xl mx-auto">
        <?php if (!empty($page_error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md mb-6" role="alert">
                <p class="font-bold">Error</p>
                <p><?php echo htmlspecialchars($page_error); ?></p>
                <div class="mt-4">
                    <a href="directory.php" class="text-sm font-medium text-blue-600 hover:text-blue-500 hover:underline">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Directory
                    </a>
                </div>
            </div>
        <?php elseif ($profile_user): ?>
            <div class="bg-white shadow-xl rounded-lg overflow-hidden">
                <div class="md:flex">
                    <div class="md:flex-shrink-0 p-6 md:p-8 flex flex-col items-center md:items-start md:border-r md:border-gray-200">
                        <?php 
                        $avatar_url = !empty($profile_user['profile_picture_url']) ? htmlspecialchars($profile_user['profile_picture_url']) : 'images/default_avatar.png'; 
                        // Check if default avatar exists, otherwise use a Font Awesome icon
                        $avatar_path = $_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']) . '/' . $avatar_url;
                        if (strpos($avatar_url, 'default_avatar.png') !== false && !file_exists(str_replace('/', DIRECTORY_SEPARATOR, $avatar_path))) {
                            $avatar_display = '<div class="w-32 h-32 md:w-48 md:h-48 bg-gray-300 rounded-full flex items-center justify-center text-gray-500 text-6xl"><i class="fas fa-user"></i></div>';
                        } else {
                            $avatar_display = '<img class="h-32 w-32 md:h-48 md:w-48 rounded-full object-cover shadow-md" src="' . $avatar_url . '" alt="Profile picture of ' . htmlspecialchars($profile_user['full_name']) . '">';
                        }
                        echo $avatar_display;
                        ?>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mt-4 text-center md:text-left"><?php echo htmlspecialchars($profile_user['full_name']); ?></h1>
                        <p class="text-gray-600 text-center md:text-left">@<?php echo htmlspecialchars($profile_user['username']); ?></p>
                        <p class="text-sm text-gray-500 capitalize text-center md:text-left"><?php echo htmlspecialchars(str_replace('_', ' ', $profile_user['role'])); ?></p>
                        <p class="text-xs text-gray-400 mt-1 text-center md:text-left">Joined: <?php echo htmlspecialchars(date("M j, Y", strtotime($profile_user['created_at']))); ?></p>
                    </div>

                    <div class="p-6 md:p-8 flex-grow">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Profile Details</h2>
                        
                        <div class="mb-4">
                            <strong class="text-gray-700">Full Name:</strong>
                            <p class="text-gray-600"><?php echo htmlspecialchars($profile_user['full_name']); ?></p>
                        </div>
                        <div class="mb-4">
                            <strong class="text-gray-700">Email:</strong>
                            <p class="text-gray-600"><a href="mailto:<?php echo htmlspecialchars($profile_user['email']); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars($profile_user['email']); ?></a></p>
                        </div>
                        
                        <?php if (!empty($profile_user['bio'])): ?>
                        <div class="mb-4">
                            <strong class="text-gray-700">Bio:</strong>
                            <p class="text-gray-600 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></p>
                        </div>
                        <?php else: ?>
                        <div class="mb-4">
                            <strong class="text-gray-700">Bio:</strong>
                            <p class="text-gray-500 italic">No bio provided.</p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Add other profile information as needed -->
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <a href="directory.php" class="text-sm font-medium text-blue-600 hover:text-blue-500 hover:underline">
                                <i class="fas fa-arrow-left mr-1"></i> Back to User Directory
                            </a>
                             <?php if ($_SESSION['username'] === $profile_user['username']): ?>
                                <a href="edit_profile.php" class="ml-4 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                                    <i class="fas fa-edit mr-2"></i> Edit My Profile
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer class="bg-slate-800 text-slate-300 py-8 mt-auto">
    <div class="container mx-auto px-6">
        <div class="text-center text-sm">
            <p>&copy; <?php echo date("Y"); ?> Job Portal. All rights reserved. </p>
            <p class="text-xs text-slate-400">Powered by Tailwind CSS & PHP</p>
        </div>
    </div>
</footer>

</body>
</html>
