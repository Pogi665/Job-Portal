<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

session_start();

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$message = '';
$messageType = '';
$validToken = false;
$email = '';

// Check if token and email are provided in URL
if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = cleanInput($_GET['token']);
    $email = cleanInput($_GET['email']);
    
    // Verify token
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND reset_token = ? AND token_expires > NOW()");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $validToken = true;
        $user = $result->fetch_assoc();
    } else {
        $message = "Invalid or expired reset link. Please request a new password reset link.";
        $messageType = 'error';
    }
} else {
    $message = "Missing token information. Please request a new password reset link.";
    $messageType = 'error';
}

// Process password reset form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirmPassword)) {
        $message = 'Please fill in all fields';
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match';
        $messageType = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long';
        $messageType = 'error';
    } else {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user's password and remove reset token
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expires = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $message = 'Your password has been reset successfully. You can now <a href="login.php" class="text-indigo-600 hover:underline">login</a> with your new password.';
            $messageType = 'success';
            $validToken = false; // Hide the form
        } else {
            $message = 'An error occurred. Please try again.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CareerLynk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gray-50 text-gray-800">

<header class="bg-white shadow-md">
    <nav class="container mx-auto px-4 lg:px-6 py-3 flex justify-between items-center">
        <div>
            <a href="index.php" class="text-2xl font-extrabold text-indigo-600 hover:text-indigo-700 transition-colors">CareerLynk</a>
        </div>
    </nav>
</header>

<main class="container mx-auto px-4 py-12 min-h-[calc(100vh-15rem)]">
    <div class="max-w-md mx-auto">
        <div class="bg-white p-8 rounded-lg shadow-lg border border-gray-100">
            <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Reset Password</h2>
            
            <?php if ($message): ?>
                <div class="<?php echo $messageType === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?> p-4 rounded mb-6">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($validToken): ?>
                <p class="text-gray-600 mb-6">Please enter your new password below.</p>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?token=' . urlencode($_GET['token']) . '&email=' . urlencode($_GET['email']); ?>" method="post" class="space-y-5" id="passwordResetForm">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" id="password" name="password" required 
                                class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-300 focus:ring-opacity-50" 
                                placeholder="Enter new password">
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Password must be at least 8 characters long</div>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-300 focus:ring-opacity-50" 
                                placeholder="Confirm new password">
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
                    
                    <button type="submit" class="w-full py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                        Reset Password
                    </button>
                </form>
            <?php elseif ($messageType !== 'success'): ?>
                <div class="text-center">
                    <a href="forgot-password.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Forgot Password
                    </a>
                </div>
            <?php endif; ?>
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
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            toggleVisibility(password, this);
        });
    }
    
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (toggleConfirmPassword && confirmPassword) {
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
            if (password.length > 7) strength += 1;
            if (password.length > 11) strength += 1;
            
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
    
    // Check for password match
    const form = document.getElementById('passwordResetForm');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (form && passwordInput && confirmPasswordInput) {
        form.addEventListener('submit', function(event) {
            if (passwordInput.value !== confirmPasswordInput.value) {
                event.preventDefault();
                alert('Passwords do not match!');
            }
        });
    }
});
</script>

</body>
</html> 