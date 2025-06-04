<?php
require_once 'admin_header.php'; // Includes session_start() and admin auth check
require_once '../database_connection.php';

$user_id_to_delete = $_GET['id'] ?? null;
$message = '';
$message_type = ''; // 'success' or 'error'

if (!$user_id_to_delete || !filter_var($user_id_to_delete, FILTER_VALIDATE_INT)) {
    $_SESSION['delete_message'] = 'Invalid user ID provided.';
    $_SESSION['delete_message_type'] = 'error';
    header("Location: manage_users.php");
    exit;
}

// Prevent admin from deleting their own account via this script
if (isset($_SESSION['user_id']) && $user_id_to_delete == $_SESSION['user_id']) {
    $_SESSION['delete_message'] = 'You cannot delete your own account from here.';
    $_SESSION['delete_message_type'] = 'error';
    header("Location: manage_users.php");
    exit;
}

// Check database connection
if ($conn->connect_error) {
    // Not ideal to die here, but for simplicity in this script:
    $_SESSION['delete_message'] = "Database connection failed: " . $conn->connect_error;
    $_SESSION['delete_message_type'] = 'error';
    header("Location: manage_users.php");
    exit;
}

// Before deleting, you might want to check for related records 
// (jobs, applications, messages) and decide how to handle them.
// For now, we proceed with direct deletion.

// Get the username of the user to be deleted for potential cleanup in other tables 
// if they use username as a foreign key (as seen in your schema).
$username_to_delete = null;
$stmt_get_username = $conn->prepare("SELECT username FROM users WHERE id = ?");
if ($stmt_get_username) {
    $stmt_get_username->bind_param("i", $user_id_to_delete);
    $stmt_get_username->execute();
    $result_username = $stmt_get_username->get_result();
    if ($user_data = $result_username->fetch_assoc()) {
        $username_to_delete = $user_data['username'];
    }
    $stmt_get_username->close();
} else {
    $_SESSION['delete_message'] = 'Error preparing to fetch username: ' . $conn->error;
    $_SESSION['delete_message_type'] = 'error';
    header("Location: manage_users.php");
    exit;
}

if (!$username_to_delete) {
    $_SESSION['delete_message'] = 'User not found or username could not be retrieved.';
    $_SESSION['delete_message_type'] = 'error';
    header("Location: manage_users.php");
    exit;
}

// Start a transaction
$conn->begin_transaction();

try {
    // Placeholder for deleting/anonymizing related data. 
    // Example: Update jobs table if it uses username
    // $stmt_jobs = $conn->prepare("UPDATE jobs SET employer_username = 'deleted_user' WHERE employer_username = ?");
    // $stmt_jobs->bind_param("s", $username_to_delete);
    // $stmt_jobs->execute();
    // $stmt_jobs->close();
    // Add similar statements for job_applications, messages, connections, notifications etc.
    // This is crucial to avoid foreign key constraint violations if those tables link by username.
    // For a true hard delete, you might delete rows from these tables first or use ON DELETE SET NULL/CASCADE in DB.

    // Delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id_to_delete);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $message = "User (ID: " . htmlspecialchars($user_id_to_delete) . ", Username: " . htmlspecialchars($username_to_delete) . ") deleted successfully.";
                $message_type = 'success';
                $conn->commit(); // Commit transaction
            } else {
                $message = "User not found or already deleted.";
                $message_type = 'error';
                $conn->rollback(); // Rollback transaction
            }
        } else {
            $message = "Error deleting user: " . $stmt->error . " (This might be due to existing related records like job posts, applications, or messages. Consider implementing soft deletes or handling related data.)";
            $message_type = 'error';
            $conn->rollback(); // Rollback transaction
        }
        $stmt->close();
    } else {
        $message = "Error preparing delete statement: " . $conn->error;
        $message_type = 'error';
        $conn->rollback(); // Rollback transaction
    }
} catch (Exception $e) {
    $conn->rollback(); // Rollback transaction on any exception
    $message = "An unexpected error occurred: " . $e->getMessage();
    $message_type = 'error';
}

$conn->close();

// Store message in session to display on manage_users.php
$_SESSION['delete_message'] = $message;
$_SESSION['delete_message_type'] = $message_type;

header("Location: manage_users.php");
exit;

// No HTML output from this script, admin_footer.php is not needed.
?> 