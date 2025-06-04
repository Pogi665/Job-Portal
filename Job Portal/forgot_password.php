<?php
session_start();

$is_public_page = true; // Flag for header.php

require 'database_connection.php'; // Use the new dedicated DB connection script

// Check if the database connection was successful
if ($conn->connect_error) {
    // Log the error and show a user-friendly message
    // This check is important because database_connection.php itself doesn't die or output HTML on failure
    error_log("Database connection failed in forgot_password.php after include: (" . $conn->connect_errno . ") " . $conn->connect_error);
    $_SESSION['message'] = "The service is temporarily unavailable due to a database connection issue. Please try again later.";
    $_SESSION['message_type'] = "error";
    // We might not want to redirect here if header.php hasn't been included yet,
    // or if we want to show the error on the current page structure.
    // For now, let the page attempt to load, and the message will be displayed.
    // If a redirect is essential, it should be to an error page or handled after header.php include.
    // For simplicity, we'll let the existing message display logic handle it.
}

// Clear previous messages
$page_message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$page_message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

$show_form = true;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "job_portal");
    if ($conn->connect_error) {
        error_log("Database connection failed in forgot_password.php: (" . $conn->connect_errno . ") " . $conn->connect_error);
        $_SESSION['message'] = "The service is temporarily unavailable. Please try again later.";
        $_SESSION['message_type'] = "error";
        header("Location: forgot_password.php");
        exit();
    } else {
        $email = trim($_POST["email"]);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['message'] = "Invalid email format provided.";
            $_SESSION['message_type'] = "error";
        } else {
            // Check if email exists in users table
            $sql = "SELECT id, username FROM users WHERE email = ? LIMIT 1";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    // User exists, generate token
                    $token = bin2hex(random_bytes(32)); // Secure token
                    $expires_at = date("Y-m-d H:i:s", strtotime('+1 hour')); // Token expires in 1 hour

                    // Store token. Assuming a password_reset_temp table or similar exists.
                    // Columns: email (or user_id), token, expires_at
                    // For security, it's good to delete any existing tokens for this email first.
                    $delete_old_stmt = $conn->prepare("DELETE FROM password_reset_temp WHERE email = ?");
                    if ($delete_old_stmt) {
                        $delete_old_stmt->bind_param("s", $email);
                        $delete_old_stmt->execute();
                        $delete_old_stmt->close();
                    } else {
                        error_log("Prepare failed for deleting old tokens: (" . $conn->errno . ") " . $conn->error);
                        // Non-fatal, continue with inserting new token
                    }

                    $insert_sql = "INSERT INTO password_reset_temp (email, token, expires_at) VALUES (?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("sss", $email, $token, $expires_at);
                        if ($insert_stmt->execute()) {
                            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname(str_replace('\\', '/', $_SERVER['PHP_SELF'])) . "/reset_password.php?token=" . $token;
                            
                            // Actual email sending logic would go here
                            // Example: sendEmail($email, "Password Reset Request", "Click here to reset: $reset_link");
                            
                            $_SESSION['message'] = "If an account with that email exists, a password reset link has been sent (or will be displayed below for testing).<br><br><strong>Reset Link (for testing):</strong> <a href='" . htmlspecialchars($reset_link) . "' class='text-blue-600 hover:text-blue-800 underline font-semibold'>" . htmlspecialchars($reset_link) . "</a>";
                            $_SESSION['message_type'] = "success";
                            $show_form = false; // Hide form on success
                        } else {
                            error_log("Failed to insert token for $email: (" . $insert_stmt->errno . ") " . $insert_stmt->error);
                            $_SESSION['message'] = "Could not process your request. Please try again.";
                            $_SESSION['message_type'] = "error";
                        }
                        $insert_stmt->close();
                    } else {
                        error_log("Prepare failed for token insert: (" . $conn->errno . ") " . $conn->error);
                        $_SESSION['message'] = "Could not process your request. Please try again.";
                        $_SESSION['message_type'] = "error";
                    }
                } else {
                    // Email not found, show a generic message to avoid user enumeration
                    $_SESSION['message'] = "If an account with that email exists, a password reset link has been sent.";
                    $_SESSION['message_type'] = "success"; // Still show as success to prevent enumeration
                     $show_form = false; // Hide form
                }
                $stmt->close();
            } else {
                error_log("Prepare failed for email check: (" . $conn->errno . ") " . $conn->error);
                $_SESSION['message'] = "Could not process your request due to a server error.";
                $_SESSION['message_type'] = "error";
            }
        }
        $conn->close();
        // Update page messages from session for immediate display
        $page_message = isset($_SESSION['message']) ? $_SESSION['message'] : $page_message;
        $page_message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : $page_message_type;
        
        // Unset the session messages after they have been copied to page variables for display
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        // No redirect here, messages are displayed on the same page.
        // If we were redirecting: header("Location: forgot_password.php"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Job Portal</title>
    <!-- Tailwind CSS, Inter font, and Font Awesome are included via header.php -->
</head>
<body class="bg-gray-100 flex flex-col min-h-screen font-sans">

<?php include 'header.php'; ?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 sm:p-10 rounded-xl shadow-xl">
        <div>
            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                Forgot Your Password?
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                No problem. Enter your email address below and we'll send you a link to reset your password.
            </p>
        </div>

        <?php if ($page_message && $page_message_type === 'success'): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Success</p>
                <p><?php echo $page_message; // We control this HTML, so direct echo is fine ?></p>
            </div>
        <?php endif; ?>
        <?php if ($page_message && $page_message_type === 'error'): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Error</p>
                <p><?php echo htmlspecialchars($page_message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($show_form): ?>
        <form class="mt-8 space-y-6" action="forgot_password.php" method="POST">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address <span class="text-red-500">*</span></label>
                <input id="email" name="email" type="email" autocomplete="email" required
                       class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-600 focus:border-blue-600 focus:z-10 sm:text-sm transition duration-150 ease-in-out"
                       placeholder="you@example.com">
            </div>
            
            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition duration-150 ease-in-out">
                    <i class="fas fa-paper-plane mr-2"></i>Send Password Reset Link
                </button>
            </div>
        </form>
        <?php endif; ?>

        <div class="mt-6 text-center">
            <a href="login.php" class="text-sm font-medium text-blue-600 hover:text-blue-500 hover:underline">
                <i class="fas fa-arrow-left mr-1"></i> Back to Login
            </a>
        </div>
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