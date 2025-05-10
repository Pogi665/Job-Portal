<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareerLynk - Your Connection to Opportunity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-100 text-gray-800">

<header class="bg-white shadow-md sticky top-0 z-50">
    <nav class="container mx-auto px-4 lg:px-6 py-3 flex justify-between items-center">
        <div>
            <a href="index.php" class="text-2xl font-extrabold text-indigo-600 hover:text-indigo-700 transition-colors">CareerLynk</a>
        </div>

        <div class="hidden lg:flex items-center space-x-1">
            <a href="index.php" class="nav-item text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Home</a>
            <a href="jobs.php" class="nav-item text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Find Jobs</a>
            <a href="companies.php" class="nav-item text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Companies</a>
            <a href="resources.php" class="nav-item text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Career Resources</a>
            <a href="blog.php" class="nav-item text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Blog</a>
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'employer'): ?>
            <a href="employer/dashboard.php" class="nav-item text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">For Employers</a>
            <?php endif; ?>
        </div>
        
        <div class="flex items-center space-x-3">
            <?php if (isLoggedIn()): ?>
            <a href="notifications.php" class="nav-item text-gray-500 hover:text-indigo-600 relative p-2 rounded-full hover:bg-gray-100 transition-colors">
                <i class="fas fa-bell fa-lg"></i>
                <?php 
                // Get notification count from database if user is logged in
                $notificationCount = 0;
                if (isset($_SESSION['user_id'])) {
                    // Check if notifications table exists to prevent errors
                    $tableCheckResult = $conn->query("SHOW TABLES LIKE 'notifications'");
                    if ($tableCheckResult->num_rows > 0) {
                        $notificationCountQuery = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
                        $stmt = $conn->prepare($notificationCountQuery);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result && $row = $result->fetch_assoc()) {
                            $notificationCount = $row['count'];
                        }
                    }
                }
                
                if ($notificationCount > 0): 
                ?>
                <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center"><?php echo $notificationCount; ?></span>
                <?php endif; ?>
            </a>
            
            <div class="relative">
                <img src="<?php 
                    if (isset($_SESSION['avatar_url'])) {
                        echo $_SESSION['avatar_url'];
                    } elseif (isset($_SESSION['user_name'])) {
                        echo 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['user_name']).'&background=random&color=fff';
                    } else {
                        echo 'https://ui-avatars.com/api/?name=User&background=random&color=fff';
                    }
                ?>" 
                     alt="User Avatar" 
                     class="w-10 h-10 rounded-full cursor-pointer border-2 border-transparent hover:border-indigo-500 transition-all" 
                     onclick="toggleUserMenu()">
                <div id="userMenu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-xl py-1 z-[100] border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <p class="text-sm font-semibold text-gray-800 truncate"><?php echo $_SESSION['user_name']; ?></p>
                        <p class="text-xs text-gray-500 truncate"><?php echo $_SESSION['user_email']; ?></p>
                    </div>
                    <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-user-circle w-5 mr-2.5 text-gray-400"></i>My Profile
                    </a>
                    <?php if ($_SESSION['user_role'] == 'job_seeker'): ?>
                    <a href="user/dashboard.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-tachometer-alt w-5 mr-2.5 text-gray-400"></i>Dashboard
                    </a>
                    <?php elseif ($_SESSION['user_role'] == 'employer'): ?>
                    <a href="employer/dashboard.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-briefcase w-5 mr-2.5 text-gray-400"></i>Employer Dashboard
                    </a>
                    <?php elseif ($_SESSION['user_role'] == 'admin'): ?>
                    <a href="admin/dashboard.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-user-shield w-5 mr-2.5 text-gray-400"></i>Admin Dashboard
                    </a>
                    <?php endif; ?>
                    <a href="messages.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                        <i class="fas fa-envelope w-5 mr-2.5 text-gray-400"></i>Messages
                    </a>
                    <div class="border-t border-gray-200 my-1"></div>
                    <a href="logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium transition-colors">
                        <i class="fas fa-sign-out-alt w-5 mr-2.5"></i>Logout
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="flex items-center space-x-2">
                <a href="login.php" class="btn btn-outline text-sm"><i class="fas fa-sign-in-alt mr-2"></i>Login</a>
                <a href="register.php" class="btn btn-primary text-sm"><i class="fas fa-user-plus mr-2"></i>Sign Up</a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="lg:hidden">
            <button id="mobileMenuButton" class="text-gray-500 hover:text-indigo-600 focus:outline-none p-2 rounded-md hover:bg-gray-100 transition-colors">
                <i class="fas fa-bars fa-lg"></i>
            </button>
        </div>
    </nav>
    <div id="mobileMenu" class="hidden lg:hidden bg-white shadow-lg border-t border-gray-200">
        <a href="index.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">Home</a>
        <a href="jobs.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">Find Jobs</a>
        <a href="companies.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">Companies</a>
        <a href="resources.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">Career Resources</a>
        <a href="blog.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">Blog</a>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'employer'): ?>
        <a href="employer/dashboard.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">For Employers</a>
        <?php endif; ?>
    </div>
</header>

<main id="mainContent" class="container mx-auto px-4 py-8 min-h-[calc(100vh-15rem)]">
<?php displayMessage(); ?>

</main>
</body>
</html>
