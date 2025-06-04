<?php
session_start();

$is_public_page = true; // Flag for header.php

require 'database_connection.php'; // Use the new dedicated DB connection script

// Retrieve and clear session messages for immediate display
$page_message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$page_message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

$token_valid = false;
$show_form = false; // Initially, don't show form until token is validated
$email_for_reset = null; // Store email associated with valid token

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $page_message = "Invalid or missing password reset token.";
    $page_message_type = "error";
} else {
    // Check if the database connection was successful from the include
    if ($conn->connect_error) {
        error_log("Database connection failed in reset_password.php: (" . $conn->connect_errno . ") " . $conn->connect_error);
        $page_message = "The service is temporarily unavailable. Please try again later.";
        $page_message_type = "error";
        // $show_form remains false, $token_valid remains false
    } else {
        // Token validation and password reset logic will use the $conn from database_connection.php
        // Check if token is valid and not expired
        $sql = "SELECT email, expires_at FROM password_reset_temp WHERE token = ? AND expires_at > NOW() LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $email_for_reset = $row['email'];
                $token_valid = true;
                $show_form = true; // Token is valid, show the password reset form
            } else {
                $page_message = "Invalid or expired password reset token. Please request a new one.";
                $page_message_type = "error";
            }
            $stmt->close();
        } else {
            error_log("Prepare failed for token check in reset_password.php: (" . $conn->errno . ") " . $conn->error);
            $page_message = "Could not validate your request. Please try again.";
            $page_message_type = "error";
        }

        if ($token_valid && $_SERVER["REQUEST_METHOD"] == "POST") {
            // TODO: Implement CSRF token check here for the POST request if desired for extra security.
            $new_password = $_POST["new_password"];
            $confirm_password = $_POST["confirm_password"];

            if (empty($new_password) || empty($confirm_password)) {
                $page_message = "Please enter and confirm your new password.";
                $page_message_type = "error";
            } elseif ($new_password !== $confirm_password) {
                $page_message = "Passwords do not match. Please try again.";
                $page_message_type = "error";
            } elseif (strlen($new_password) < 8) { // Basic password strength check
                $page_message = "Password must be at least 8 characters long.";
                $page_message_type = "error";
            } else {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password in users table using the email fetched from the token
                $update_sql = "UPDATE users SET password = ? WHERE email = ?";
                $update_stmt = $conn->prepare($update_sql);
                if ($update_stmt) {
                    $update_stmt->bind_param("ss", $hashed_password, $email_for_reset);
                    if ($update_stmt->execute()) {
                        // Delete the token from password_reset_temp table
                        $delete_sql = "DELETE FROM password_reset_temp WHERE token = ?";
                        $delete_stmt = $conn->prepare($delete_sql);
                        if($delete_stmt){
                            $delete_stmt->bind_param("s", $token);
                            $delete_stmt->execute();
                            $delete_stmt->close();
                        } else {
                             error_log("Failed to prepare delete statement for token $token: (" . $conn->errno . ") " . $conn->error);
                             // Non-critical, but log it.
                        }
                        $page_message = "Your password has been successfully reset. You can now <a href='login.php' class='font-medium text-blue-600 hover:text-blue-500 underline'>log in</a> with your new password.";
                        $page_message_type = "success";
                        $show_form = false; // Hide form after successful reset
                    } else {
                        error_log("Failed to update password for $email_for_reset: (" . $update_stmt->errno . ") " . $update_stmt->error);
                        $page_message = "Could not update your password. Please try again.";
                        $page_message_type = "error";
                    }
                    $update_stmt->close();
                } else {
                    error_log("Prepare failed for password update: (" . $conn->errno . ") " . $conn->error);
                    $page_message = "Could not update your password due to a server error.";
                    $page_message_type = "error";
                }
            }
        }
        // Ensure $conn is closed only if it was successfully opened and operations are done
        if ($conn && !$conn->connect_error) {
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Job Portal</title>
    <!-- Tailwind CSS, Inter font, and Font Awesome are included via header.php -->
</head>
<body class="bg-gray-100 flex flex-col min-h-screen font-sans">

<?php include 'header.php'; ?>

<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-8 sm:p-10 rounded-xl shadow-xl">
        <div>
            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                Set Your New Password
            </h2>
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
        <form class="mt-8 space-y-6" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
            <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password <span class="text-red-500">*</span></label>
                <input id="new_password" name="new_password" type="password" required
                       class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-600 focus:border-blue-600 focus:z-10 sm:text-sm transition duration-150 ease-in-out"
                       placeholder="Enter new password (min. 8 characters)">
            </div>
            <div class="mt-4">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password <span class="text-red-500">*</span></label>
                <input id="confirm_password" name="confirm_password" type="password" required
                       class="appearance-none rounded-md relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-600 focus:border-blue-600 focus:z-10 sm:text-sm transition duration-150 ease-in-out"
                       placeholder="Confirm new password">
            </div>
            
            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition duration-150 ease-in-out">
                    <i class="fas fa-key mr-2"></i>Reset Password
                </button>
            </div>
        </form>
        <?php elseif (!$page_message && empty($token)): // Specific message if token was empty from start ?>
             <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md" role="alert">
                <p class="font-bold">Missing Token</p>
                <p>No password reset token was provided. Please use the link from your email or <a href="forgot_password.php" class="font-medium text-blue-600 hover:text-blue-500 underline">request a new one</a>.</p>
            </div>
        <?php elseif (!$show_form && !$page_message_type === 'success'): // Fallback for invalid/expired token when no other message is set yet ?>
            <div class="mt-6 text-center">
                 <p class="text-sm text-gray-600">
                    If your token is invalid or has expired, please <a href="forgot_password.php" class="font-medium text-blue-600 hover:text-blue-500 underline">request a new password reset link</a>.
                </p>
            </div>
        <?php endif; ?>
        
        <div class="mt-8 text-center">
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