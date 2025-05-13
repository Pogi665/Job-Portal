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
    <style>
        .bg-gradient {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        }
        .hero-section {
            background-image: linear-gradient(rgba(26, 32, 44, 0.75), rgba(45, 55, 72, 0.8)), url('assets/images/career-team.jpg?v=1');
            background-size: cover;
            background-position: center top;
            position: relative;
            z-index: 1;
        }
        .pattern-bg {
            background-color: #f9fafb;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%234f46e5' fill-opacity='0.05'%3E%3Cpath d='M0 38.59l2.83-2.83 1.41 1.41L1.41 40H0v-1.41zM0 1.4l2.83 2.83 1.41-1.41L1.41 0H0v1.41zM38.59 40l-2.83-2.83 1.41-1.41L40 38.59V40h-1.41zM40 1.41l-2.83 2.83-1.41-1.41L38.59 0H40v1.41zM20 18.6l2.83-2.83 1.41 1.41L21.41 20l2.83 2.83-1.41 1.41L20 21.41l-2.83 2.83-1.41-1.41L18.59 20l-2.83-2.83 1.41-1.41L20 18.59z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .role-btn {
            transition: all 0.3s ease;
        }
        .role-btn.active {
            background-color: #4f46e5;
            color: white;
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
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4 text-center">Join <span class="text-indigo-300">CareerLynk</span> Today</h1>
            <p class="text-xl mb-6 text-center max-w-3xl mx-auto">Create your account and unlock a world of opportunities.</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-12 -mt-8">
        <div class="flex flex-col lg:flex-row gap-12">
            <!-- Left column with form -->
            <div class="lg:w-1/2">
                <div class="bg-white p-8 rounded-xl shadow-xl border border-gray-100">
                    <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Create Your Account</h2>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6">
                            <?php echo $success; ?>
                            <div class="mt-4 text-center">
                                <a href="login.php" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                    <i class="fas fa-sign-in-alt mr-2"></i> Login Now
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="space-y-5">
                            <!-- Role selection buttons -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">I am a:</label>
                                <div class="flex rounded-md overflow-hidden border border-gray-300">
                                    <button type="button" class="role-btn flex-1 py-3 px-4 bg-indigo-600 text-white font-medium text-sm active" data-role="job_seeker" id="btn_job_seeker">
                                        <i class="fas fa-user-tie mr-2"></i>Job Seeker
                                    </button>
                                    <button type="button" class="role-btn flex-1 py-3 px-4 bg-gray-200 text-gray-700 font-medium text-sm" data-role="employer" id="btn_employer">
                                        <i class="fas fa-building mr-2"></i>Employer
                                    </button>
                                </div>
                                <input type="hidden" id="role" name="role" value="job_seeker">
                            </div>
                            
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" id="name" name="name" value="<?php echo $name; ?>" required 
                                        class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-300 focus:ring-opacity-50 h-12"
                                        placeholder="Enter your full name">
                                </div>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class="far fa-envelope"></i>
                                    </span>
                                    <input type="email" id="email" name="email" value="<?php echo $email; ?>" required 
                                        class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-300 focus:ring-opacity-50 h-12"
                                        placeholder="Enter your email">
                                </div>
                            </div>
                            
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" id="password" name="password" required 
                                        class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-300 focus:ring-opacity-50 h-12"
                                        placeholder="Create a password">
                                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Must be at least 6 characters long</p>
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" id="confirm_password" name="confirm_password" required 
                                        class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-300 focus:ring-opacity-50 h-12"
                                        placeholder="Confirm your password">
                                    <button type="button" id="toggleConfirmPassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mt-2" id="passwordStrength">
                                <div class="text-sm font-medium text-gray-700 mb-1">Password Strength</div>
                                <div class="h-2 bg-gray-200 rounded overflow-hidden">
                                    <div class="h-full bg-gray-400" id="passwordStrengthBar" style="width: 0%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1" id="passwordStrengthText">Enter a password</p>
                            </div>
                            
                            <div class="flex items-center mt-4">
                                <input type="checkbox" id="terms" name="terms" required class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="terms" class="ml-2 block text-sm text-gray-700">
                                    I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium">Terms of Service</a> and <a href="#" class="text-indigo-600 hover:text-indigo-800 font-medium">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="w-full py-3 px-4 border border-transparent rounded-md shadow-lg text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                                Create Account
                            </button>
                            
                            <div class="relative my-4">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white text-gray-500">Or sign up with</span>
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
                                <span class="text-gray-600">Already have an account?</span>
                                <a href="login.php" class="text-indigo-600 hover:text-indigo-800 font-medium ml-1">Sign in now <i class="fas fa-arrow-right text-xs ml-1"></i></a>
                            </p>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right column with benefits -->
            <div class="lg:w-1/2 pattern-bg rounded-xl p-8 mt-8 lg:mt-0">
                <div class="space-y-8">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-14 w-14 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 shadow-md">
                            <i class="fas fa-user-plus fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-semibold text-gray-900">Quick & Easy Sign Up</h3>
                            <p class="mt-1 text-gray-600">Create your account in minutes and start exploring opportunities customized to your skills and preferences.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-14 w-14 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 shadow-md">
                            <i class="fas fa-lock fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-semibold text-gray-900">Secure Platform</h3>
                            <p class="mt-1 text-gray-600">Your information is protected with the highest security standards, ensuring your data stays private and secure.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-14 w-14 flex items-center justify-center rounded-full bg-indigo-100 text-indigo-600 shadow-md">
                            <i class="fas fa-rocket fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-semibold text-gray-900">Boost Your Career</h3>
                            <p class="mt-1 text-gray-600">Access premium tools designed to help you succeed professionally, from resume builders to interview preparation.</p>
                        </div>
                    </div>
                    
                    <!-- Why Join Section -->
                    <div class="bg-white p-6 rounded-lg shadow-md mt-10">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">Why Join CareerLynk?</h3>
                        <ul class="space-y-3">
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Access to thousands of job opportunities</span>
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Connect with top employers in your industry</span>
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Personalized job recommendations</span>
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Career advice and professional resources</span>
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-700">Advanced resume building tools</span>
                            </li>
                        </ul>
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
                                <p class="text-gray-700 italic">"Signing up for CareerLynk was one of the best career decisions I've made. Within a month, I found the perfect position that matched my skills and career goals."</p>
                                <p class="text-sm font-medium text-gray-900 mt-2">Michael Rodriguez, Marketing Specialist</p>
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
    // Password visibility toggle for password field
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    if(togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            toggleVisibility(password, this);
        });
    }
    
    // Password visibility toggle for confirm password field
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPassword = document.getElementById('confirm_password');
    
    if(toggleConfirmPassword && confirmPassword) {
        toggleConfirmPassword.addEventListener('click', function() {
            toggleVisibility(confirmPassword, this);
        });
    }
    
    function toggleVisibility(inputField, buttonEl) {
        const type = inputField.getAttribute('type') === 'password' ? 'text' : 'password';
        inputField.setAttribute('type', type);
        
        // Toggle eye icon
        if(type === 'password') {
            buttonEl.querySelector('i').classList.remove('fa-eye-slash');
            buttonEl.querySelector('i').classList.add('fa-eye');
        } else {
            buttonEl.querySelector('i').classList.remove('fa-eye');
            buttonEl.querySelector('i').classList.add('fa-eye-slash');
        }
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
    
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    
    if (passwordInput && strengthBar && strengthText) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = 'Enter a password';
            
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthBar.className = 'h-full bg-gray-400';
                strengthText.textContent = feedback;
                return;
            }
            
            // Length check
            if (password.length > 5) strength += 1;
            if (password.length > 10) strength += 1;
            
            // Character variety checks
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Update the strength bar and text
            switch(strength) {
                case 0:
                case 1:
                    strengthBar.style.width = '20%';
                    strengthBar.className = 'h-full bg-red-500';
                    feedback = 'Very weak';
                    break;
                case 2:
                    strengthBar.style.width = '40%';
                    strengthBar.className = 'h-full bg-orange-500';
                    feedback = 'Weak';
                    break;
                case 3:
                    strengthBar.style.width = '60%';
                    strengthBar.className = 'h-full bg-yellow-500';
                    feedback = 'Good';
                    break;
                case 4:
                    strengthBar.style.width = '80%';
                    strengthBar.className = 'h-full bg-green-400';
                    feedback = 'Strong';
                    break;
                case 5:
                    strengthBar.style.width = '100%';
                    strengthBar.className = 'h-full bg-green-600';
                    feedback = 'Very strong';
                    break;
            }
            
            strengthText.textContent = feedback;
        });
    }
});
</script>

</body>
</html>
