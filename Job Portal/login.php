<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

session_start();

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Check for direct redirect parameter
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $role = cleanInput($_POST['role']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['avatar_url'] = $user['avatar_url'];
                
                // Update last login time
                $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                
                // Check for redirect URL
                if (!empty($redirect)) {
                    header("Location: " . $redirect);
                    exit;
                }
                
                // Check for session-stored redirect URL
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirectUrl = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: " . $redirectUrl);
                    exit;
                }
                
                // Default redirects based on user role
                if ($user['role'] == 'job_seeker') {
                    redirect('user/dashboard.php', 'Login successful!');
                } else if ($user['role'] == 'employer') {
                    redirect('employer/dashboard.php', 'Login successful!');
                } else if ($user['role'] == 'admin') {
                    redirect('admin/dashboard.php', 'Login successful!');
                } else {
                    redirect('index.php', 'Login successful!');
                }
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}

// Use a minimal header for the login page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to CareerLynk - Your Connection to Opportunity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-100 text-gray-800">

<header class="bg-white shadow-md">
    <nav class="container mx-auto px-4 lg:px-6 py-3 flex justify-between items-center">
        <div>
            <a href="login.php" class="text-2xl font-extrabold text-indigo-600 hover:text-indigo-700 transition-colors">CareerLynk</a>
        </div>
    </nav>
</header>

<main class="container mx-auto px-4 py-8 min-h-[calc(100vh-15rem)]">
    <?php displayMessage(); ?>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-12 py-8">
        <!-- Welcome content section -->
        <div class="md:w-1/2">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-6 text-indigo-700">Welcome to <span class="text-indigo-600">CareerLynk</span></h1>
            <p class="text-xl mb-8 text-gray-700">Your ultimate connection to career opportunities and professional growth.</p>
            
            <div class="space-y-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <i class="fas fa-search fa-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-medium text-gray-900">Find Your Dream Job</h3>
                        <p class="mt-1 text-gray-600">Access thousands of listings from top companies across industries.</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <i class="fas fa-user-tie fa-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-medium text-gray-900">Professional Profile</h3>
                        <p class="mt-1 text-gray-600">Create your professional profile and showcase your skills to employers.</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <i class="fas fa-building fa-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-medium text-gray-900">For Employers</h3>
                        <p class="mt-1 text-gray-600">Post jobs and find the perfect talent for your organization.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login form section -->
        <div class="md:w-2/5">
            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-6 text-center text-gray-700">Sign In</h2>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" id="email" name="email" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="password" name="password" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    </div>
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">Login as:</label>
                        <select id="role" name="role" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="job_seeker">Job Seeker</option>
                            <option value="employer">Employer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Login
                    </button>
                    <p class="text-sm text-center pt-2 text-gray-600">
                        Don't have an account? <a href="register.php" class="text-indigo-600 hover:underline font-medium">Sign up</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</main>

<footer class="bg-gray-800 text-white py-8">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between">
            <div class="mb-6 md:mb-0">
                <a href="login.php" class="text-2xl font-bold text-indigo-400">CareerLynk</a>
                <p class="mt-2 text-gray-400 max-w-sm">Connecting talent with opportunity and helping professionals advance their careers.</p>
            </div>
            
            <div class="grid grid-cols-2 gap-8 sm:grid-cols-3">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400">About</h3>
                    <div class="mt-4 space-y-2">
                        <a href="#" class="text-gray-300 hover:text-white block">About Us</a>
                        <a href="#" class="text-gray-300 hover:text-white block">Testimonials</a>
                        <a href="#" class="text-gray-300 hover:text-white block">Press</a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400">Resources</h3>
                    <div class="mt-4 space-y-2">
                        <a href="#" class="text-gray-300 hover:text-white block">Blog</a>
                        <a href="#" class="text-gray-300 hover:text-white block">Career Tips</a>
                        <a href="#" class="text-gray-300 hover:text-white block">Help Center</a>
                    </div>
                </div>
                
                <div class="col-span-2 sm:col-span-1">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400">Contact</h3>
                    <div class="mt-4 space-y-2">
                        <a href="#" class="text-gray-300 hover:text-white flex items-center">
                            <i class="fas fa-envelope mr-2 text-indigo-400"></i> support@careerlynk.com
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white flex items-center">
                            <i class="fas fa-phone mr-2 text-indigo-400"></i> +1 (555) 123-4567
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-8 border-t border-gray-700 pt-8 flex flex-col md:flex-row md:items-center md:justify-between">
            <p class="text-gray-400">&copy; <?php echo date('Y'); ?> CareerLynk. All rights reserved.</p>
            <div class="flex space-x-6 mt-4 md:mt-0">
                <a href="#" class="text-gray-400 hover:text-indigo-400">
                    <i class="fab fa-facebook fa-lg"></i>
                </a>
                <a href="#" class="text-gray-400 hover:text-indigo-400">
                    <i class="fab fa-twitter fa-lg"></i>
                </a>
                <a href="#" class="text-gray-400 hover:text-indigo-400">
                    <i class="fab fa-linkedin fa-lg"></i>
                </a>
                <a href="#" class="text-gray-400 hover:text-indigo-400">
                    <i class="fab fa-instagram fa-lg"></i>
                </a>
            </div>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Any JavaScript you need
});
</script>
</body>
</html>
