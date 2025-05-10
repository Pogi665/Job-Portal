<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

session_start();

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';
$name = $email = $role = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = cleanInput($_POST['name']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = cleanInput($_POST['role']);
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already exists. Please use a different email or login.';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate avatar URL
            $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=random&color=fff';
            
            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, avatar_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $avatar_url);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
                // Clear form values on success
                $name = $email = $role = '';
            } else {
                $error = 'Registration failed. Please try again later.';
            }
        }
    }
}

// Use a minimal header for the register page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up for CareerLynk - Your Connection to Opportunity</title>
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
            <h1 class="text-4xl md:text-5xl font-extrabold mb-6 text-indigo-700">Join <span class="text-indigo-600">CareerLynk</span> Today</h1>
            <p class="text-xl mb-8 text-gray-700">Create your account and unlock a world of opportunities.</p>
            
            <div class="space-y-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <i class="fas fa-user-plus fa-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-medium text-gray-900">Quick & Easy Sign Up</h3>
                        <p class="mt-1 text-gray-600">Create your account in minutes and start exploring opportunities.</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <i class="fas fa-lock fa-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-medium text-gray-900">Secure Platform</h3>
                        <p class="mt-1 text-gray-600">Your information is protected with the highest security standards.</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                        <i class="fas fa-rocket fa-lg"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-medium text-gray-900">Boost Your Career</h3>
                        <p class="mt-1 text-gray-600">Access to premium tools designed to help you succeed professionally.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registration form section -->
        <div class="md:w-2/5">
            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-6 text-center text-gray-700">Create Your Account</h2>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 text-green-700 p-4 rounded mb-6">
                        <?php echo $success; ?>
                        <p class="mt-2 font-medium">
                            <a href="login.php" class="text-green-700 underline">Click here to login</a>
                        </p>
                    </div>
                <?php else: ?>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo $name; ?>" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo $email; ?>" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" id="password" name="password" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <p class="text-xs text-gray-500 mt-1">Must be at least 6 characters long</p>
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">I am a:</label>
                            <select id="role" name="role" required class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <option value="job_seeker" <?php echo ($role == 'job_seeker') ? 'selected' : ''; ?>>Job Seeker</option>
                                <option value="employer" <?php echo ($role == 'employer') ? 'selected' : ''; ?>>Employer</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Sign Up
                        </button>
                        <p class="text-sm text-center pt-2 text-gray-600">
                            Already have an account? <a href="login.php" class="text-indigo-600 hover:underline font-medium">Login</a>
                        </p>
                    </form>
                <?php endif; ?>
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
