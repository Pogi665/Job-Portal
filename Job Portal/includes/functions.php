<?php
// Format date for display
function formatDate($date) {
    return date("M j, Y", strtotime($date));
}

// Clean input data
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

// Sanitize output for HTML display
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Alias for backward compatibility
function h($data) {
    return sanitizeOutput($data);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user has specific role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == $role;
}

// Redirect with message
function redirect($url, $message = '', $message_type = 'success') {
    if ($message != '') {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
    }
    header("Location: $url");
    exit;
}

// Display message
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message_type = $_SESSION['message_type'] ?? 'info';
        $alert_class = 'bg-blue-100 text-blue-700'; // Default info style
        
        if ($message_type == 'success') {
            $alert_class = 'bg-green-100 text-green-700';
        } else if ($message_type == 'error') {
            $alert_class = 'bg-red-100 text-red-700';
        } else if ($message_type == 'warning') {
            $alert_class = 'bg-yellow-100 text-yellow-700';
        }
        
        echo '<div class="' . $alert_class . ' p-4 mb-4 rounded">' . $_SESSION['message'] . '</div>';
        
        // Clear the message after displaying
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}
