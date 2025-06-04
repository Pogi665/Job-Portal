<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// $is_public_page should be defined in the including file BEFORE this header is included.
// Default to false if not set, meaning it's an authenticated page.
$is_public_page = $is_public_page ?? false;

$username = $_SESSION['username'] ?? null;
$user_id_for_notif = $_SESSION['user_id'] ?? null;
$unreadNotifCount = 0;
$unreadMsgCount = 0;

if (!$is_public_page && $user_id_for_notif) { // Check user_id_for_notif for notification logic
    // User role for conditional display
    $user_role = $_SESSION['role'] ?? ''; // Default to empty string if not set

    $conn = new mysqli("localhost", "root", "", "job_portal");
    if ($conn->connect_error) {
        // Log error or handle gracefully, but avoid breaking public pages if DB is down
        error_log("DB connection error in header.php: " . $conn->connect_error);
        // For public pages, we might not want to die here.
        // For auth pages, it's more critical.
    } else {
        // Unread notifications
        $notifQuery = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE recipient_username = ? AND is_read = 0");
        if ($notifQuery) {
            $notifQuery->bind_param("s", $username);
            $notifQuery->execute();
            $notifResult = $notifQuery->get_result();
            $unreadNotifCount = $notifResult->fetch_assoc()['unread_count'] ?? 0;
            $notifQuery->close();
        } else {
            error_log("Failed to prepare notification query in header: " . $conn->error);
        }

        // Unread messages - column name in DB is 'receiver_username' or 'recipient_username'? It was 'receiver' in original snippet
        // Assuming 'recipient_username' for consistency with notifications table and previous fixes.
        // Also, messages table has sender_username, recipient_username. The query here was `messages WHERE receiver = ?`
        // Let's check the messages table structure again. Ah, it is `recipient_username`.
        $msgQuery = $conn->prepare("SELECT COUNT(*) as unread_messages FROM messages WHERE recipient_username = ? AND is_read = 0");
        if ($msgQuery) {
            $msgQuery->bind_param("s", $username);
            $msgQuery->execute();
            $msgResult = $msgQuery->get_result();
            $unreadMsgCount = $msgResult->fetch_assoc()['unread_messages'] ?? 0;
            $msgQuery->close();
        } else {
            error_log("Failed to prepare messages query in header: " . $conn->error);
        }
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>

<?php if (!$is_public_page): ?>
<header class="main-header bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-6 py-3 flex justify-between items-center">
        <a href="dashboard.php" class="text-2xl font-bold text-blue-600 flex items-center">
            <i class="fas fa-handshake mr-2"></i>CareerLynk
        </a>
        <nav class="space-x-4 md:space-x-6 flex items-center">
            <a href="dashboard.php" class="<?= ($currentPage == 'dashboard.php') ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300">Dashboard</a>
            <?php if ($user_role === 'admin'): ?>
                <a href="admin/manage_users.php" class="<?= (strpos($currentPage, 'admin/') === 0) ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300">Admin Panel</a>
            <?php endif; ?>
            <a href="jobs.php" class="<?= ($currentPage == 'jobs.php' || $currentPage == 'job_details.php' || $currentPage == 'post_job.php' || $currentPage == 'edit_job.php') ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300">
                <?php 
                if ($user_role === 'job_seeker') {
                    echo 'Applied Jobs';
                } else {
                    echo 'Jobs Posted';
                }
                ?>
            </a>
            <a href="directory.php" class="<?= ($currentPage == 'directory.php') ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300">Users</a>
            <a href="connections.php" class="<?= ($currentPage == 'connections.php') ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300">Connections</a>
            <a href="messages.php" class="<?= ($currentPage == 'messages.php' || $currentPage == 'message.php') ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300 relative">
                Messages
                <?php if ($unreadMsgCount > 0): ?>
                    <span class="absolute top-0 right-0 transform translate-x-1/2 -translate-y-1/2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full"><?php echo $unreadMsgCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="notifications.php" class="<?= ($currentPage == 'notifications.php') ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300 relative">
                Notifications
                <?php if ($unreadNotifCount > 0): ?>
                    <span class="absolute top-0 right-0 transform translate-x-1/2 -translate-y-1/2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full"><?php echo $unreadNotifCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="profile.php" class="<?= ($currentPage == 'profile.php' || $currentPage == 'edit_profile.php' || $currentPage == 'view_profile.php') ? 'text-blue-700 font-semibold' : 'text-gray-600'; ?> hover:text-blue-600 transition duration-300">Profile</a>
            <a href="logout.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 shadow hover:shadow-lg">
                Log Out
            </a>
            <!-- Mobile Menu Button (optional, if needed for smaller screens) -->
            <!-- 
            <button id="mobileMenuButton" class="md:hidden text-gray-600 hover:text-blue-600 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
            -->
        </nav>
    </div>
    <!-- Mobile Menu (optional, if needed for smaller screens) -->
    <!-- 
    <div id="mobileMenu" class="md:hidden hidden bg-white shadow-lg rounded-b-lg p-4">
        <a href="dashboard.php" class="block w-full text-left px-4 py-3 text-base <?= ($currentPage == 'dashboard.php') ? 'text-blue-700 bg-gray-100 font-semibold' : 'text-gray-700'; ?> rounded-md hover:bg-gray-100 hover:text-blue-600 focus:outline-none focus:bg-gray-100 focus:text-blue-600 transition duration-150 ease-in-out mb-1">Dashboard</a>
        ... (repeat for other links)
        <a href="logout.php" class="block w-full mt-2 px-4 py-3 text-base bg-blue-600 text-white text-center font-semibold rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out shadow-sm">Log Out</a>
    </div>
    -->
</header>
<?php else: // This is a public page, show a simpler header ?>
<header class="bg-white shadow-sm">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <a href="index.php" class="text-2xl font-bold text-blue-600 flex items-center">
            <i class="fas fa-handshake mr-2"></i>CareerLynk
        </a>
        <nav class="space-x-4">
            <?php 
            // Define pages where header buttons should be hidden
            $hideButtonsOnPages = ['terms_of_service.php', 'privacy_policy.php', 'forgot_password.php', 'reset_password.php'];
            $shouldHideButtons = in_array($currentPage, $hideButtonsOnPages);
            ?>

            <?php if (!$shouldHideButtons && $currentPage != 'login.php'): ?>
                <a href="login.php" class="text-blue-700 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 font-semibold py-2 px-4 rounded-md transition duration-300">Login</a>
            <?php elseif (!$shouldHideButtons && $currentPage == 'login.php'): ?>
                <a href="login.php" class="text-white bg-blue-600 font-semibold py-2 px-4 rounded-md transition duration-300">Login</a>
            <?php endif; ?>
            
            <?php if (!$shouldHideButtons && $currentPage != 'signup_page.php'): ?>
                <a href="signup_page.php" class="text-blue-700 hover:text-blue-800 bg-blue-100 hover:bg-blue-200 font-semibold py-2 px-4 rounded-md transition duration-300">Sign Up</a>
            <?php elseif (!$shouldHideButtons && $currentPage == 'signup_page.php'): ?>
                <a href="signup_page.php" class="text-white bg-blue-600 font-semibold py-2 px-4 rounded-md transition duration-300">Sign Up</a>
            <?php endif; ?>
            
            <?php if (!$shouldHideButtons && $currentPage != 'index.php'): // Only show 'Back to Home' if not on index.php and not on the specified pages ?>
             <a href="index.php" class="text-gray-700 hover:text-gray-800 bg-gray-200 hover:bg-gray-300 font-semibold py-2 px-4 rounded-md transition duration-300">Back to Home</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php endif; ?>

<script>
// Optional: Basic Mobile Menu Toggle if uncommented above
/*
const mobileMenuButton = document.getElementById('mobileMenuButton');
const mobileMenu = document.getElementById('mobileMenu');
if (mobileMenuButton && mobileMenu) {
    mobileMenuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });
}
*/
</script>
