<?php
session_start();

// Redirect logged-in users to the dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$is_public_page = true; // Flag for header.php

// Retrieve previous form input if available
$signup_input = $_SESSION['signup_input'] ?? [];

// Clear signup input from session after retrieving it so it's only used once
if (isset($_SESSION['signup_input'])) {
    unset($_SESSION['signup_input']);
}

// Define field names as they are in signup.php (full_name, contact_number)
$fn_fullname = htmlspecialchars($signup_input['fullname'] ?? '', ENT_QUOTES, 'UTF-8');
$fn_contact_number = htmlspecialchars($signup_input['phone'] ?? '', ENT_QUOTES, 'UTF-8');
$fn_username = htmlspecialchars($signup_input['username'] ?? '', ENT_QUOTES, 'UTF-8');
$fn_email = htmlspecialchars($signup_input['email'] ?? '', ENT_QUOTES, 'UTF-8');
$fn_role = htmlspecialchars($signup_input['role'] ?? '', ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - CareerLynk</title>
    <?php include 'header.php'; ?> 
    <style>
        .form-input:focus {
            border-color: #3b82f6; /* blue-500 */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .form-radio:checked {
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
            border-color: #3b82f6; /* blue-500 */
        }
        .form-radio:checked:hover {
            border-color: #2563eb; /* blue-600 */
        }
        .form-radio:focus {
             box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .password-toggle-icon {
            color: #6b7280; /* gray-500 */
        }
        .password-toggle-icon:hover {
            color: #4b5563; /* gray-700 */
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">

<?php /* Header navigation is now part of header.php */ ?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg w-full space-y-8 bg-white p-8 sm:p-10 rounded-xl shadow-2xl">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Create your CareerLynk account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Or <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">
                    sign in to your existing account
                </a>
            </p>
        </div>
        
        <?php
        if (isset($_SESSION['message'])) {
            $message_type_class = '';
            switch ($_SESSION['message_type']) {
                case 'error':
                    $message_type_class = 'bg-red-100 border-red-500 text-red-700';
                    break;
                case 'success': // Though success usually redirects, handle just in case
                    $message_type_class = 'bg-green-100 border-green-500 text-green-700';
                    break;
                case 'warning':
                    $message_type_class = 'bg-yellow-100 border-yellow-500 text-yellow-700';
                    break;
                default:
                    $message_type_class = 'bg-blue-100 border-blue-500 text-blue-700';
                    break;
            }
            echo '<div class="p-4 mb-4 text-sm border-l-4 rounded-md ' . $message_type_class . '" role="alert">';
            echo '<p class="font-bold">' . ucfirst(htmlspecialchars($_SESSION['message_type'] ?? 'info')) . '</p>';
            // Message from signup.php might contain HTML (like links), so don't escape it here IF it's designed to be safe.
            // However, to be safe by default, we should escape. If specific messages need HTML, they should be handled carefully in signup.php.
            // For now, assuming messages are plain text or pre-escaped if containing HTML links.
            // $_SESSION['message'] from signup.php can have links, so this should be okay if signup.php creates safe HTML.
            echo '<p>' . $_SESSION['message'] . '</p>'; 
            echo '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>
        
        <form class="mt-8 space-y-5" action="signup.php" method="POST" id="signupForm" novalidate>
            <div>
                <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                <input id="fullname" name="fullname" type="text" autocomplete="name" required value="<?php echo $fn_fullname; ?>"
                        class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                        placeholder="e.g., Jane Doe">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                <input id="phone" name="phone" type="tel" autocomplete="tel" required pattern="[0-9]{10,15}" title="Please enter a valid phone number (10-15 digits)." value="<?php echo $fn_contact_number; ?>"
                        class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                        placeholder="e.g., 09123456789">
            </div>
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                <input id="username" name="username" type="text" autocomplete="username" required minlength="4" value="<?php echo $fn_username; ?>"
                        class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                        placeholder="Choose a username (min. 4 characters)">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address <span class="text-red-500">*</span></label>
                <input id="email" name="email" type="email" autocomplete="email" required value="<?php echo $fn_email; ?>"
                        class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                        placeholder="you@example.com">
            </div>
            <div class="relative">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" title="Password must be at least 8 characters long."
                        class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm pr-10"
                        placeholder="•••••••• (min. 8 characters)">
                <button type="button" onclick="togglePasswordVisibility('password', this)" class="absolute inset-y-0 right-0 top-7 pr-3 flex items-center text-sm leading-5 focus:outline-none" aria-label="Toggle password visibility">
                    <i class="fas fa-eye password-toggle-icon"></i>
                </button>
            </div>
            <div class="relative">
                <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
                <input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" required
                        class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm pr-10"
                        placeholder="••••••••">
                <button type="button" onclick="togglePasswordVisibility('confirm-password', this)" class="absolute inset-y-0 right-0 top-7 pr-3 flex items-center text-sm leading-5 focus:outline-none" aria-label="Toggle confirm password visibility">
                    <i class="fas fa-eye password-toggle-icon"></i>
                </button>
            </div>
            <div id="password-match-error-container" class="text-sm"></div> <!-- Container for password match client-side error -->

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">I am a... <span class="text-red-500">*</span></label>
                <select id="role" name="role" required
                        class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm">
                    <option value="" disabled <?php if (empty($fn_role)) echo 'selected'; ?>>Select account type</option>
                    <option value="job_seeker" <?php if ($fn_role === 'job_seeker') echo 'selected'; ?>>Job Seeker</option>
                    <option value="job_employer" <?php if ($fn_role === 'job_employer') echo 'selected'; ?>>Employer</option>
                </select>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">
                    I agree to the <a href="terms_of_service.php" style="color: blue;">Terms of Service</a> and <a href="privacy_policy.php" style="color: blue;">Privacy Policy</a> <span style="color: red;" class="text-danger">*</span>
                </label>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    Create Account
                </button>
            </div>
        </form>
    </div>
</main>

<?php include 'footer.php'; ?>

<script>
    function togglePasswordVisibility(inputId, buttonElement) {
        const input = document.getElementById(inputId);
        const icon = buttonElement.querySelector('i');
        if (input && icon) {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const signupForm = document.getElementById('signupForm');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm-password');
        const passwordMatchErrorContainer = document.getElementById('password-match-error-container');
        // Server-side errors are now handled by PHP echoing messages above the form.
        // const serverErrorContainer = document.getElementById('signup-error-message-container'); // This div is removed

        if (signupForm) {
            signupForm.addEventListener('submit', function(event) {
                passwordMatchErrorContainer.innerHTML = ''; 
                confirmPasswordInput.classList.remove('border-red-500');
                passwordInput.classList.remove('border-red-500');

                if (passwordInput.value !== confirmPasswordInput.value) {
                    event.preventDefault();
                    passwordMatchErrorContainer.innerHTML = 
                        `<div class="alert alert-danger mt-2" role="alert">
                            <span class="block sm:inline">Passwords do not match. Please re-enter.</span>
                        </div>`;
                    confirmPasswordInput.classList.add('border-red-500');
                    passwordInput.classList.add('border-red-500');
                    confirmPasswordInput.focus();
                    return false;
                }
            });
        }

        // Removed old JavaScript for GET parameter error display.
        // The dynamic year update in the footer is now done by PHP.
    });
</script>

</body>
</html> 