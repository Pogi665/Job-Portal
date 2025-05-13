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
    $remember = isset($_POST['remember']) ? true : false;
    
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
                
                // Set remember me cookie if checked
                if ($remember) {
                    $token = bin2hex(random_bytes(16));
                    $expires = time() + 60 * 60 * 24 * 30; // 30 days
                    
                    // Store token in database
                    $stmt = $conn->prepare("UPDATE users SET remember_token = ?, token_expires = FROM_UNIXTIME(?) WHERE id = ?");
                    $stmt->bind_param("sii", $token, $expires, $user['id']);
                    $stmt->execute();
                    
                    // Set cookie
                    setcookie('remember_token', $token, $expires, '/', '', true, true);
                    setcookie('user_id', $user['id'], $expires, '/', '', true, false);
                }
                
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
    <style>
        .bg-gradient {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        }
        .hero-section {
            background-image: linear-gradient(rgba(26, 32, 44, 0.7), rgba(45, 55, 72, 0.75)), url('assets/images/career-team.jpg?v=1');
            background-size: cover;
            background-position: center top;
            position: relative;
            z-index: 1;
        }
        .form-floating-label {
            position: relative;
        }
        .form-floating-label input:focus + label,
        .form-floating-label input:not(:placeholder-shown) + label {
            transform: translateY(-1.5rem) scale(0.85);
            background-color: white;
            padding: 0 0.25rem;
        }
        .form-floating-label label {
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            color: #6B7280;
            pointer-events: none;
            transition: 0.2s ease all;
            transform-origin: left top;
        }
        .role-btn {
            transition: all 0.3s ease;
        }
        .role-btn.active {
            background-color: #4f46e5;
            color: white;
        }
        .pattern-bg {
            background-color: #f9fafb;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%234f46e5' fill-opacity='0.05'%3E%3Cpath d='M0 38.59l2.83-2.83 1.41 1.41L1.41 40H0v-1.41zM0 1.4l2.83 2.83 1.41-1.41L1.41 0H0v1.41zM38.59 40l-2.83-2.83 1.41-1.41L40 38.59V40h-1.41zM40 1.41l-2.83 2.83-1.41-1.41L38.59 0H40v1.41zM20 18.6l2.83-2.83 1.41 1.41L21.41 20l2.83 2.83-1.41 1.41L20 21.41l-2.83 2.83-1.41-1.41L18.59 20l-2.83-2.83 1.41-1.41L20 18.59z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .grayscale {
            filter: grayscale(100%);
        }
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            .stats-item {
                width: 100%;
            }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

<header class="bg-white shadow-md">
    <nav class="container mx-auto px-4 lg:px-6 py-3 flex justify-between items-center">
        <div>
            <a href="index.php" class="text-2xl font-extrabold text-indigo-600 hover:text-indigo-700 transition-colors">CareerLynk</a>
        </div>
    </nav>
</header>

<main class="min-h-[calc(100vh-15rem)]">
    <?php displayMessage(); ?>

    <div class="w-full hero-section text-white py-16">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4 text-center">Welcome to <span class="text-indigo-300">CareerLynk</span></h1>
            <p class="text-xl mb-6 text-center max-w-3xl mx-auto">Your ultimate connection to career opportunities and professional growth.</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-12 -mt-8">
        <div class="flex flex-col lg:flex-row gap-12">
            <!-- Left column with form -->
            <div class="lg:w-1/2">
                <div class="bg-white p-8 rounded-xl shadow-xl border border-gray-100">
                    <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Sign In</h2>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="space-y-5">
                        <!-- Role selection buttons -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Login as:</label>
                            <div class="flex rounded-md overflow-hidden border border-gray-300">
                                <button type="button" class="role-btn flex-1 py-3 px-4 bg-indigo-600 text-white font-medium text-sm active" data-role="job_seeker" id="btn_job_seeker">
                                    <i class="fas fa-user-tie mr-2"></i>Job Seeker
                                </button>
                                <button type="button" class="role-btn flex-1 py-3 px-4 bg-gray-200 text-gray-700 font-medium text-sm" data-role="employer" id="btn_employer">
                                    <i class="fas fa-building mr-2"></i>Employer
                                </button>
                                <button type="button" class="role-btn flex-1 py-3 px-4 bg-gray-200 text-gray-700 font-medium text-sm" data-role="admin" id="btn_admin">
                                    <i class="fas fa-shield-alt mr-2"></i>Admin
                                </button>
                            </div>
                            <input type="hidden" id="role" name="role" value="job_seeker">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                    <i class="far fa-envelope"></i>
                                </span>
                                <input type="email" id="email" name="email" required 
                                    class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-300 focus:ring-opacity-50 h-12"
                                    placeholder="Enter your email">
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between mb-1">
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <a href="forgot-password.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Forgot Password?</a>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" id="password" name="password" required 
                                    class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-300 focus:ring-opacity-50 h-12"
                                    placeholder="Enter your password">
                                <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember" class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                        </div>
                        
                        <button type="submit" class="w-full py-3 px-4 border border-transparent rounded-md shadow-lg text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                            Sign In
                        </button>
                        
                        <div class="relative my-4">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Or continue with</span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-3">
                            <button type="button" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex justify-center items-center">
                                <i class="fab fa-google text-red-500 text-lg"></i>
                            </button>
                            <button type="button" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex justify-center items-center">
                                <i class="fab fa-linkedin text-blue-600 text-lg"></i>
                            </button>
                            <button type="button" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex justify-center items-center">
                                <i class="fab fa-facebook-f text-blue-800 text-lg"></i>
                            </button>
                        </div>
                        
                        <p class="text-center mt-6">
                            <span class="text-gray-600">Don't have an account?</span>
                            <a href="register.php" class="text-indigo-600 hover:text-indigo-800 font-medium ml-1">Create one now <i class="fas fa-arrow-right text-xs ml-1"></i></a>
                        </p>
                    </form>
                </div>
                
                <!-- Testimonial -->
                <div class="bg-indigo-50 p-5 rounded-lg mt-8 shadow-md">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                                <i class="fas fa-user text-indigo-600"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-700 italic">"CareerLynk helped me find my dream job within just 2 weeks of signing up! The process was smooth and the recommendations were spot on."</p>
                            <p class="text-sm font-medium text-gray-900 mt-2">Sarah Johnson, Software Developer</p>
                            <div class="flex mt-1 text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right column with benefits -->
            <div class="lg:w-1/2 pattern-bg rounded-xl p-8 mt-8 lg:mt-0">
                <div class="space-y-8">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-14 w-14 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 shadow-md">
                            <i class="fas fa-search fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-semibold text-gray-900">Find Your Dream Job</h3>
                            <p class="mt-1 text-gray-600">Access thousands of listings from top companies across industries, matched to your skills and preferences.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-14 w-14 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 shadow-md">
                            <i class="fas fa-user-tie fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-semibold text-gray-900">Professional Profile</h3>
                            <p class="mt-1 text-gray-600">Create your professional profile and showcase your skills to employers looking for talent like yours.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-14 w-14 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 shadow-md">
                            <i class="fas fa-building fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-semibold text-gray-900">For Employers</h3>
                            <p class="mt-1 text-gray-600">Post jobs and find the perfect talent for your organization from our pool of qualified candidates.</p>
                        </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="mt-10">
                        <h3 class="text-lg font-semibold text-center mb-6 text-gray-800">Join Our Growing Community</h3>
                        <div class="flex justify-between stats-container">
                            <div class="text-center stats-item">
                                <div class="text-3xl font-bold text-indigo-600">10,000+</div>
                                <div class="text-sm text-gray-600">Active Jobs</div>
                            </div>
                            <div class="text-center stats-item">
                                <div class="text-3xl font-bold text-indigo-600">5,000+</div>
                                <div class="text-sm text-gray-600">Companies</div>
                            </div>
                            <div class="text-center stats-item">
                                <div class="text-3xl font-bold text-indigo-600">50,000+</div>
                                <div class="text-sm text-gray-600">Job Seekers</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Partner logos -->
                    <div class="mt-10">
                        <p class="text-center text-sm text-gray-500 mb-4">Trusted by leading companies</p>
                        <div class="flex justify-center space-x-8 flex-wrap">
                            <div class="h-8 w-24 bg-gray-200 rounded flex items-center justify-center grayscale opacity-70 mb-3">
                                <span class="text-gray-500 text-xs">COMPANY 1</span>
                            </div>
                            <div class="h-8 w-24 bg-gray-200 rounded flex items-center justify-center grayscale opacity-70 mb-3">
                                <span class="text-gray-500 text-xs">COMPANY 2</span>
                            </div>
                            <div class="h-8 w-24 bg-gray-200 rounded flex items-center justify-center grayscale opacity-70 mb-3">
                                <span class="text-gray-500 text-xs">COMPANY 3</span>
                            </div>
                            <div class="h-8 w-24 bg-gray-200 rounded flex items-center justify-center grayscale opacity-70 mb-3">
                                <span class="text-gray-500 text-xs">COMPANY 4</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security indicator -->
                <div class="mt-8 flex items-center justify-center">
                    <i class="fas fa-shield-alt text-green-500 mr-2"></i>
                    <p class="text-sm text-gray-600">Secure login with encryption</p>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="bg-gray-800 text-white py-8">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between">
            <div class="mb-6 md:mb-0">
                <a href="index.php" class="text-2xl font-bold text-indigo-400">CareerLynk</a>
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
    // Password visibility toggle
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    if(togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle eye icon
            if(type === 'password') {
                this.querySelector('i').classList.remove('fa-eye-slash');
                this.querySelector('i').classList.add('fa-eye');
            } else {
                this.querySelector('i').classList.remove('fa-eye');
                this.querySelector('i').classList.add('fa-eye-slash');
            }
        });
    }
    
    // Role selection buttons
    const roleButtons = document.querySelectorAll('.role-btn');
    const roleInput = document.getElementById('role');
    
    roleButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            roleButtons.forEach(function(btn) {
                btn.classList.remove('active');
                btn.classList.remove('bg-indigo-600');
                btn.classList.remove('text-white');
                btn.classList.add('bg-gray-200');
                btn.classList.add('text-gray-700');
            });
            
            // Add active class to clicked button
            this.classList.remove('bg-gray-200');
            this.classList.remove('text-gray-700');
            this.classList.add('active');
            this.classList.add('bg-indigo-600');
            this.classList.add('text-white');
            
            // Set hidden input value
            roleInput.value = this.dataset.role;
        });
    });
    
    // Check for Caps Lock
    const inputFields = document.querySelectorAll('input[type="password"]');
    inputFields.forEach(function(input) {
        input.addEventListener('keyup', function(event) {
            if (event.getModifierState('CapsLock')) {
                if (!document.getElementById('capsWarning')) {
                    const warning = document.createElement('div');
                    warning.id = 'capsWarning';
                    warning.className = 'text-amber-600 text-xs mt-1';
                    warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Caps Lock is on';
                    this.parentNode.appendChild(warning);
                }
            } else {
                const warning = document.getElementById('capsWarning');
                if (warning) {
                    warning.remove();
                }
            }
        });
    });
});
</script>

</body>
</html>
