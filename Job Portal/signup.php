<?php
session_start();
include 'database_connection.php';
include 'php_error_handling.php'; // Assuming this file handles error logging

// TODO: Implement CSRF token check here for enhanced security.
// e.g., if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) { /* handle error & redirect */ }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if terms and conditions are agreed to
    if (!isset($_POST['terms'])) {
        $_SESSION['message'] = 'You must agree to the Terms of Service and Privacy Policy to create an account.';
        $_SESSION['message_type'] = 'error';
        // Store other form inputs to repopulate the form
        $_SESSION['signup_input'] = $_POST;
        header("Location: signup_page.php");
        exit;
    }

    $conn = new mysqli("localhost", "root", "", "job_portal");
    if ($conn->connect_error) {
        error_log("Signup DB Connection failed: (" . $conn->connect_errno . ") " . $conn->connect_error);
        $_SESSION['message'] = "Database connection error. Please try again later.";
        $_SESSION['message_type'] = "error";
        header("Location: signup_page.php");
        exit();
    }

    // Retrieve and sanitize inputs
    $fullname = trim($_POST["fullname"] ?? '');
    $phone = trim($_POST["phone"] ?? '');
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? ''; // Don't trim password before validation
    $confirm_password = $_POST["confirm_password"] ?? '';
    $role = trim($_POST["role"] ?? '');

    // Store original inputs in session to repopulate form on error
    $_SESSION['signup_input'] = $_POST;

    $errors = [];

    // Validation logic
    if (empty($fullname)) $errors[] = "Full name is required.";
    if (empty($username)) $errors[] = "Username is required.";
    else if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) $errors[] = "Username must be 3-20 characters, letters, numbers, or underscores.";
    
    if (empty($email)) $errors[] = "Email is required.";
    else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";

    if (empty($password)) $errors[] = "Password is required.";
    else if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters long.";
    // Add more password complexity rules if desired, e.g.:
    // else if (!preg_match('/[A-Z]/', $password)) $errors[] = "Password must contain at least one uppercase letter.";
    // else if (!preg_match('/[a-z]/', $password)) $errors[] = "Password must contain at least one lowercase letter.";
    // else if (!preg_match('/[0-9]/', $password)) $errors[] = "Password must contain at least one number.";
    // else if (!preg_match('/[^\w]/', $password)) $errors[] = "Password must contain at least one special character.";

    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    $allowed_roles = ['job_seeker', 'job_employer'];
    if (empty($role)) $errors[] = "Role is required.";
    else if (!in_array($role, $allowed_roles)) $errors[] = "Invalid role selected.";
    
    // Optional: Validate contact number (e.g., basic numeric check or more complex regex)
    if (!empty($phone) && !preg_match('/^[0-9\s\-\+\(\)]+$/', $phone)) {
        $errors[] = "Invalid phone number format.";
    }

    // If no validation errors, check for existing username/email
    if (empty($errors)) {
        // Check for existing username
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        if ($stmt_check) {
            $stmt_check->bind_param("s", $username);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $errors[] = "Username already exists. Please choose another.";
            }
            $stmt_check->close();
        } else {
            $errors[] = "Database error (username check). Please try again.";
            error_log("Signup Prepare failed (username check): (" . $conn->errno . ") " . $conn->error);
        }

        // Check for existing email
        $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($stmt_check_email) {
            $stmt_check_email->bind_param("s", $email);
            $stmt_check_email->execute();
            if ($stmt_check_email->get_result()->num_rows > 0) {
                $errors[] = "Email address already registered. Please use a different email or log in.";
            }
            $stmt_check_email->close();
        } else {
            $errors[] = "Database error (email check). Please try again.";
            error_log("Signup Prepare failed (email check): (" . $conn->errno . ") " . $conn->error);
        }
    }

    if (!empty($errors)) {
        $_SESSION['message'] = implode("<br>", $errors);
        $_SESSION['message_type'] = "error";
        header("Location: signup_page.php");
        exit();
    }

    // All checks passed, proceed with insertion
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Using full_name and contact_number for consistency with other parts of the application
    $stmt = $conn->prepare("INSERT INTO users (full_name, contact_number, username, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt === false) {
        error_log("Signup Prepare failed (insert user): (" . $conn->errno . ") " . $conn->error);
        $_SESSION['message'] = "Registration statement preparation failed. Please try again.";
        $_SESSION['message_type'] = "error";
        header("Location: signup_page.php");
        exit();
    }

    $stmt->bind_param("ssssss", $fullname, $phone, $username, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        unset($_SESSION['signup_input']); // Clear stored input on success
        $_SESSION['message'] = "Registration successful! You can now log in.";
        $_SESSION['message_type'] = "success";
        header("Location: login.php"); 
        exit();
    } else {
        error_log("Signup Execute failed (insert user): (" . $stmt->errno . ") " . $stmt->error);
        $_SESSION['message'] = "Registration failed due to a server error. Please try again.";
        $_SESSION['message_type'] = "error";
        header("Location: signup_page.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    // If not a POST request, redirect to signup form or home page
    header("Location: signup_page.php");
    exit();
}
?>
