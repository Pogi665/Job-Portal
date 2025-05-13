<?php
// Authentication and session functions

/**
 * Check if user is logged in
 * @return boolean
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specified role
 * @param string $role Role to check (job_seeker, employer, admin)
 * @return boolean
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == $role;
}

/**
 * Set a message to be displayed on the next page load
 * @param string $type Type of message (success, error, warning, info)
 * @param string $message Message to display
 */
function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

/**
 * Display the message and clear it from session
 */
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $messageType = $_SESSION['message']['type'];
        $messageText = $_SESSION['message']['text'];
        
        // Define Bootstrap/Tailwind classes based on message type
        $classes = [
            'success' => 'bg-green-100 border-green-500 text-green-700',
            'error' => 'bg-red-100 border-red-500 text-red-700',
            'warning' => 'bg-yellow-100 border-yellow-500 text-yellow-700',
            'info' => 'bg-blue-100 border-blue-500 text-blue-700'
        ];
        
        $class = $classes[$messageType] ?? $classes['info'];
        
        echo "<div class=\"{$class} border-l-4 p-4 mb-6\">";
        echo "<p>{$messageText}</p>";
        echo "</div>";
        
        // Clear the message
        unset($_SESSION['message']);
    }
}

/**
 * Redirect to another page with optional message
 * @param string $url URL to redirect to
 * @param string $message Optional message to show on the next page
 * @param string $type Message type (success, error, warning, info)
 */
function redirect($url, $message = '', $type = 'info') {
    if (!empty($message)) {
        setMessage($type, $message);
    }
    header("Location: $url");
    exit;
}

/**
 * Clean user input to prevent XSS
 * @param string $data Input data
 * @return string Cleaned data
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Shorthand function for htmlspecialchars
 * @param string $str String to escape
 * @return string Escaped string
 */
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Format salary range for display
 * @param int $min Minimum salary
 * @param int $max Maximum salary
 * @param string $currency Currency code
 * @param string $period Salary period (hourly, monthly, annually)
 * @return string Formatted salary range
 */
function formatSalary($min, $max, $currency = 'USD', $period = 'annually') {
    $currencies = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'JPY' => '¥',
        'INR' => '₹'
    ];
    
    $symbol = $currencies[$currency] ?? $currency;
    
    if ($min && $max) {
        return "{$symbol}" . number_format($min) . " - {$symbol}" . number_format($max) . " ({$period})";
    } elseif ($min) {
        return "From {$symbol}" . number_format($min) . " ({$period})";
    } elseif ($max) {
        return "Up to {$symbol}" . number_format($max) . " ({$period})";
    } else {
        return "Not specified";
    }
}

/**
 * Format date in a human-readable format
 * @param string $date Date string
 * @param bool $includeTime Whether to include the time
 * @return string Formatted date
 */
function formatDate($date, $includeTime = false) {
    $timestamp = strtotime($date);
    if ($includeTime) {
        return date('M j, Y \a\t g:i a', $timestamp);
    }
    return date('M j, Y', $timestamp);
}

/**
 * Get time elapsed string (e.g., "5 days ago")
 * @param string $datetime Date/time string
 * @return string Time elapsed string
 */
function timeElapsed($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$string) {
        return 'just now';
    }
    
    return array_shift($string) . ' ago';
}
