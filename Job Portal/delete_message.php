<?php
session_start();
if (!isset($_SESSION["username"])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'User not authenticated.']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "job_portal");
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit();
}

$current_username = $_SESSION["username"];
$response = ['success' => false];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["message_id"])) {
    $message_id = intval($_POST["message_id"]);

    if ($message_id > 0) {
        // First, verify the message belongs to the current user to prevent unauthorized deletions
        $verify_stmt = $conn->prepare("SELECT sender_username FROM messages WHERE id = ?");
        if ($verify_stmt) {
            $verify_stmt->bind_param("i", $message_id);
            $verify_stmt->execute();
            $result = $verify_stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if ($row['sender_username'] === $current_username) {
                    // User is authorized, proceed with deletion
                    $delete_stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND sender_username = ?");
                    if ($delete_stmt) {
                        $delete_stmt->bind_param("is", $message_id, $current_username);
                        if ($delete_stmt->execute()) {
                            if ($delete_stmt->affected_rows > 0) {
                                $response['success'] = true;
                            } else {
                                $response['error'] = 'Message not found or already deleted.';
                            }
                        } else {
                            $response['error'] = 'Failed to delete message: ' . $delete_stmt->error;
                            error_log("Failed to execute delete statement: " . $delete_stmt->error);
                        }
                        $delete_stmt->close();
                    } else {
                        $response['error'] = 'Failed to prepare delete statement: ' . $conn->error;
                        error_log("Failed to prepare delete statement: " . $conn->error);
                    }
                } else {
                    $response['error'] = 'Unauthorized: You can only delete your own messages.';
                }
            } else {
                $response['error'] = 'Message not found.';
            }
            $verify_stmt->close();
        } else {
            $response['error'] = 'Failed to prepare verification statement: ' . $conn->error;
            error_log("Failed to prepare verification statement: " . $conn->error);
        }
    } else {
        $response['error'] = 'Invalid message ID.';
    }
} else {
    $response['error'] = 'Invalid request method or missing message ID.';
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

header('Content-Type: application/json');
echo json_encode($response);
exit();
?> 