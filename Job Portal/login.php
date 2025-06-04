<?php
session_start(); // Must be the very first thing

$is_public_page = true; // Flag for header.php

// Redirect to dashboard if already logged in
if (isset($_SESSION["username"])) {
    // Redirect based on role
    if (isset($_SESSION["role"]) && $_SESSION["role"] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

// PHP Login Logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "job_portal");
    if ($conn->connect_error) {
        error_log("Database connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
        $_SESSION['message'] = "Database connection error. Please try again later.";
        $_SESSION['message_type'] = "error";
        header("Location: login.php");
        exit();
    }

    $username_input = $_POST["username"];
    $pass = $_POST["password"];

    $sql = "SELECT id, username, email, password, role, full_name FROM users WHERE username = ?"; // Corrected to full_name
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Prepare failed for SQL: " . $sql . " - Error: (" . $conn->errno . ") " . $conn->error);
        $_SESSION['message'] = "Login statement preparation failed. Please try again later.";
        $_SESSION['message_type'] = "error";
        header("Location: login.php");
        exit();
    }

    $stmt->bind_param("s", $username_input);
    
    if (!$stmt->execute()) {
        error_log("Execute failed for SQL: " . $sql . " - Error: (" . $stmt->errno . ") " . $stmt->error);
        $_SESSION['message'] = "Login statement execution failed. Please try again later.";
        $_SESSION['message_type'] = "error";
        header("Location: login.php");
        exit();
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row["password"])) {
            session_regenerate_id(true);
            
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["username"] = $row["username"]; 
            $_SESSION["email"] = $row["email"];
            $_SESSION["role"] = $row["role"];
            $_SESSION["fullname"] = $row["full_name"]; // Corrected to full_name and re-enabled
            
            // Set a success message for dashboard (optional)
            $_SESSION['message'] = "Welcome back, " . htmlspecialchars($row["full_name"] ?: $row["username"]) . "!";
            $_SESSION['message_type'] = "success";

            // Redirect based on role
            if (isset($_SESSION["role"]) && $_SESSION["role"] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $_SESSION['message'] = "Invalid username or password.";
            $_SESSION['message_type'] = "error";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['message'] = "User not found. Please check your username or create an account.";
        $_SESSION['message_type'] = "error";
        header("Location: login.php");
        exit();
    }
    $stmt->close();
    $conn->close();
}

// Clear any signup form input from session if the user navigates here
unset($_SESSION['signup_input']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - CareerLynk</title>
    <?php include 'header.php'; ?>
    <style>
        /* Custom focus style for better visibility if needed - can be moved to style.css if preferred */
        .form-input:focus {
            border-color: #3b82f6; /* blue-500 */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        #togglePassword i {
            color: #6b7280; /* gray-500 */
        }
        #togglePassword:hover i {
            color: #4b5563; /* gray-700 */
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">

<?php /* This line will be removed by the edit, as header.php is now included within the head tag */ ?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 sm:p-10 rounded-xl shadow-2xl">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Log in to your account
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Or <a href="signup_page.php" class="font-medium text-blue-600 hover:text-blue-500">
                    create a new account
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
                case 'success':
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
            echo '<p>' . htmlspecialchars($_SESSION['message']) . '</p>';
            echo '</div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>
        
        <form class="mt-8 space-y-6" action="login.php" method="POST" id="loginForm">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                <input id="username" name="username" type="text" autocomplete="username" required
                       class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                       placeholder="Enter your username">
            </div>
            
            <div class="mt-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           class="form-input appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm pr-10"
                           placeholder="••••••••">
                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5 focus:outline-none">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <div class="flex items-center">
                    <input id="remember-me" name="remember_me" type="checkbox"
                           class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-offset-0">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        Remember me
                    </label>
                </div>

                <div class="text-sm">
                    <a href="forgot_password.php" class="font-medium text-blue-600 hover:text-blue-500">
                        Forgot your password?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    Log In
                </button>
            </div>
        </form>
    </div>
</main>

<?php include 'footer.php'; ?>

<script>
    const togglePasswordButton = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (togglePasswordButton && passwordInput) {
        togglePasswordButton.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }

    // Removed old JavaScript for GET parameter error display. Session messages are handled by PHP.
</script>

</body>
</html>
